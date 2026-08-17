<?php
/**
 * Plugin auto-updater.
 *
 * Hooks into the WordPress update system so new releases appear in
 * Dashboard → Updates with one-click install.
 *
 * Version checks read a small JSON manifest published as a release asset.
 * Release assets are served by redirect rather than through the API, so they
 * are not subject to the 60 requests/hour unauthenticated API limit. That limit
 * is the reason the manifest is not read from api.github.com: a GridPane server
 * hosting 90+ sites shares one outbound IP and will sit permanently over quota,
 * which is exactly how this plugin stopped offering updates.
 *
 * If the manifest cannot be reached the updater falls back to the releases API,
 * so a site with no manifest yet still gets updates.
 *
 * Uses a class because the updater maintains state (transients, version
 * info, plugin data). This is the one exception to the functions-only rule.
 *
 * @package HumanKind\FuneralNotices
 */

declare( strict_types=1 );

namespace HumanKind\FuneralNotices\GitHubUpdater;

defined( 'ABSPATH' ) || exit;

/**
 * Initialise the updater on admin pages.
 */
function init(): void {
	if ( ! is_admin() ) {
		return;
	}

	HK_Funeral_Notices_Updater::init( HKFN_PLUGIN_FILE );
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\init' );

/**
 * Release updater for self-hosted plugins.
 */
class HK_Funeral_Notices_Updater {

	/** @var string Main plugin file path. */
	private string $file;

	/** @var array|null Plugin header data — loaded lazily. */
	private ?array $plugin = null;

	/** @var string Plugin basename (e.g. "hk-funeral-notices/hk-funeral-notices.php"). */
	private string $basename;

	/** @var bool Whether the plugin is currently active. */
	private bool $active;

	/** @var object|null|false Cached update manifest. */
	private $update_info = null;

	// ── Configuration ────────────────────────────────────────────────────

	/**
	 * Update manifest.
	 *
	 * This is a release asset, not an API call. GitHub answers it with a plain
	 * 302 to the asset store and no rate-limit headers, so it avoids the 60
	 * requests/hour unauthenticated API limit that a GridPane server hosting
	 * 90+ sites would sit permanently over on one shared IP.
	 *
	 * The "latest" path always resolves to the newest published release, so the
	 * URL never changes between versions.
	 */
	private const MANIFEST_URL = 'https://github.com/HumanKind-nz/hk-funeral-notices/releases/latest/download/update.json';

	/** GitHub organisation or username — fallback source only. */
	private const GITHUB_USERNAME = 'HumanKind-nz';

	/** GitHub repository name — fallback source only. */
	private const GITHUB_REPO = 'hk-funeral-notices';

	/** Plugin icon — small (128×128). Served from BunnyCDN. */
	public const ICON_SMALL = 'https://weave-hk-github.b-cdn.net/humankind/icon-128x128.png';

	/** Plugin icon — large (256×256). Served from BunnyCDN. */
	public const ICON_LARGE = 'https://weave-hk-github.b-cdn.net/humankind/icon-256x256.png';

	/** Transient key for caching the resolved update manifest. */
	private const CACHE_KEY = 'hkfn_update_manifest';

	/** Hours to cache a successful response. */
	private const CACHE_DURATION = 6;

	/**
	 * Hours to cache an error response.
	 *
	 * Deliberately longer than the success cache. A short error cache makes a
	 * failing fleet retry faster than a healthy one, which is how the previous
	 * updater held a shared IP permanently over GitHub's rate limit.
	 */
	private const ERROR_CACHE_DURATION = 12;

	// ── Lifecycle ────────────────────────────────────────────────────────

	/**
	 * Private constructor — use init() instead.
	 *
	 * @param string $file Main plugin file path.
	 */
	private function __construct( string $file ) {
		$this->file     = $file;
		$this->basename = plugin_basename( $this->file );
		$this->active   = is_plugin_active( $this->basename );

		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_update' ] );
		add_filter( 'plugins_api', [ $this, 'plugin_info' ], 20, 3 );
		add_filter( 'upgrader_post_install', [ $this, 'after_install' ], 10, 3 );
		add_action( 'plugin_row_meta', [ $this, 'add_check_updates_button' ], 10, 2 );
		add_action( 'admin_post_hkfn_check_for_updates', [ $this, 'check_for_updates' ] );
	}

	/**
	 * Create (or return) the singleton instance.
	 *
	 * @param string $file Main plugin file path.
	 * @return self
	 */
	public static function init( string $file ): self {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new self( $file );
		}

		return $instance;
	}

	// ── Helpers ──────────────────────────────────────────────────────────

	/**
	 * Get plugin header data, loading it lazily.
	 *
	 * @return array Plugin data array.
	 */
	private function get_plugin_data(): array {
		if ( null === $this->plugin && function_exists( 'get_plugin_data' ) ) {
			$this->plugin = get_plugin_data( $this->file );
		}

		return $this->plugin ?? [];
	}

	/**
	 * Normalise a version string by stripping a leading "v".
	 *
	 * @param string $version Raw version string (e.g. "v1.2.3").
	 * @return string Normalised version (e.g. "1.2.3").
	 */
	private function normalize_version( string $version ): string {
		return ltrim( $version, 'vV' );
	}

	/**
	 * Cache an error result so a failing check does not retry on every load.
	 *
	 * A small random offset spreads retries out across a fleet of sites that
	 * would otherwise all expire their cache at the same moment.
	 */
	private function cache_error(): void {
		$ttl = ( self::ERROR_CACHE_DURATION * HOUR_IN_SECONDS ) + wp_rand( 0, HOUR_IN_SECONDS );
		set_transient( self::CACHE_KEY, [ 'status' => 'error' ], $ttl );
	}

	/**
	 * Get update information, preferring the CDN manifest.
	 *
	 * @return object|false Normalised update info, or false on failure.
	 */
	private function get_update_info() {
		if ( null !== $this->update_info ) {
			return $this->update_info;
		}

		$cached = get_transient( self::CACHE_KEY );

		if ( false !== $cached ) {
			if ( is_array( $cached ) && isset( $cached['status'] ) && 'error' === $cached['status'] ) {
				return false;
			}

			$this->update_info = $cached;
			return $this->update_info;
		}

		$info = $this->fetch_manifest();

		if ( ! $info ) {
			$info = $this->fetch_github_release();
		}

		if ( ! $info ) {
			$this->cache_error();
			return false;
		}

		set_transient( self::CACHE_KEY, $info, self::CACHE_DURATION * HOUR_IN_SECONDS );
		$this->update_info = $info;

		return $this->update_info;
	}

	/**
	 * Perform a remote GET and return the decoded JSON body.
	 *
	 * @param string $url Request URL.
	 * @param string $accept Accept header value.
	 * @return object|array|null Decoded body, or null on any failure.
	 */
	private function get_json( string $url, string $accept = 'application/json' ) {
		$response = wp_remote_get( $url, [
			'timeout' => 15,
			'headers' => [
				'Accept'     => $accept,
				'User-Agent' => 'HumanKind-FuneralNotices/' . HKFN_VERSION . '; ' . get_bloginfo( 'url' ),
			],
		] );

		if ( is_wp_error( $response ) ) {
			\HumanKind\FuneralNotices\Hooks\debug_log( 'Update check failed for ' . $url . ': ' . $response->get_error_message() );
			return null;
		}

		$code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $code ) {
			\HumanKind\FuneralNotices\Hooks\debug_log( 'Update check for ' . $url . ' returned HTTP ' . $code );
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ) );

		if ( JSON_ERROR_NONE !== json_last_error() ) {
			\HumanKind\FuneralNotices\Hooks\debug_log( 'Update check for ' . $url . ' returned invalid JSON.' );
			return null;
		}

		return $body;
	}

	/**
	 * Fetch the update manifest from the CDN.
	 *
	 * @return object|false Normalised update info, or false on failure.
	 */
	private function fetch_manifest() {
		$body = $this->get_json( self::MANIFEST_URL );

		if ( ! is_object( $body ) || empty( $body->version ) || empty( $body->download_url ) ) {
			return false;
		}

		return (object) [
			'version'      => $this->normalize_version( (string) $body->version ),
			'download_url' => (string) $body->download_url,
			'last_updated' => $body->last_updated ?? '',
			'requires'     => $body->requires ?? '',
			'requires_php' => $body->requires_php ?? '',
			'tested'       => $body->tested ?? '',
			'changelog'    => $body->sections->changelog ?? '',
		];
	}

	/**
	 * Fetch the latest release from the GitHub API.
	 *
	 * Fallback only, used when the CDN manifest is unreachable. Subject to
	 * GitHub's unauthenticated rate limit, which is shared across every site
	 * on the same outbound IP.
	 *
	 * @return object|false Normalised update info, or false on failure.
	 */
	private function fetch_github_release() {
		$url = sprintf(
			'https://api.github.com/repos/%s/%s/releases/latest',
			self::GITHUB_USERNAME,
			self::GITHUB_REPO
		);

		$body = $this->get_json( $url, 'application/vnd.github.v3+json' );

		if ( ! is_object( $body ) || empty( $body->tag_name ) || empty( $body->assets ) ) {
			return false;
		}

		$download_url = $body->assets[0]->browser_download_url ?? '';

		if ( empty( $download_url ) ) {
			return false;
		}

		return (object) [
			'version'      => $this->normalize_version( (string) $body->tag_name ),
			'download_url' => (string) $download_url,
			'last_updated' => $body->published_at ?? '',
			'requires'     => '',
			'requires_php' => '',
			'tested'       => '',
			'changelog'    => $body->body ?? '',
		];
	}

	// ── WordPress update hooks ───────────────────────────────────────────

	/**
	 * Check whether a newer version is available.
	 *
	 * @param object $transient The update_plugins transient data.
	 * @return object Modified transient.
	 */
	public function check_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$plugin_data = $this->get_plugin_data();
		$update_info = $this->get_update_info();

		if ( ! $update_info || empty( $plugin_data['Version'] ) ) {
			return $transient;
		}

		$current = $this->normalize_version( $plugin_data['Version'] );
		$latest  = $update_info->version;

		$plugin_entry = [
			'slug'         => dirname( $this->basename ),
			'plugin'       => $this->basename,
			'new_version'  => $latest,
			'url'          => $plugin_data['PluginURI'] ?? '',
			'tested'       => $update_info->tested ?: get_bloginfo( 'version' ),
			'requires_php' => $update_info->requires_php ?: ( $plugin_data['RequiresPHP'] ?? '8.1' ),
			'requires'     => $update_info->requires ?: ( $plugin_data['RequiresWP'] ?? '6.6' ),
			'package'      => $update_info->download_url,
			'icons'        => [
				'1x' => self::ICON_SMALL,
				'2x' => self::ICON_LARGE,
			],
		];

		if ( version_compare( $latest, $current, '>' ) ) {
			$transient->response[ $this->basename ] = (object) $plugin_entry;
		} else {
			unset( $transient->response[ $this->basename ] );

			if ( ! isset( $transient->no_update[ $this->basename ] ) ) {
				$plugin_entry['package']                 = '';
				$transient->no_update[ $this->basename ] = (object) $plugin_entry;
			}
		}

		return $transient;
	}

	/**
	 * Provide plugin details for the "View details" modal in the admin.
	 *
	 * @param object|false $res    Existing result.
	 * @param string       $action API action.
	 * @param object       $args   Request arguments.
	 * @return object|false Plugin info or pass-through.
	 */
	public function plugin_info( $res, $action, $args ) {
		if ( 'plugin_information' !== $action || $args->slug !== dirname( $this->basename ) ) {
			return $res;
		}

		$plugin_data = $this->get_plugin_data();
		$update_info = $this->get_update_info();

		if ( ! $update_info ) {
			return $res;
		}

		$info               = new \stdClass();
		$info->name         = $plugin_data['Name'] ?? 'HumanKind Funeral Notices';
		$info->slug         = dirname( $this->basename );
		$info->author       = $plugin_data['AuthorName'] ?? 'Weave Digital Studio';
		$info->homepage     = $plugin_data['PluginURI'] ?? '';
		$info->version      = $update_info->version;
		$info->last_updated = $update_info->last_updated;
		$info->tested       = $update_info->tested ?: get_bloginfo( 'version' );
		$info->requires     = $update_info->requires ?: ( $plugin_data['RequiresWP'] ?? '6.6' );
		$info->requires_php = $update_info->requires_php ?: ( $plugin_data['RequiresPHP'] ?? '8.1' );
		$info->download_link = $update_info->download_url;

		$info->sections = [
			'description' => $plugin_data['Description'] ?? '',
			'changelog'   => $update_info->changelog,
		];

		$info->icons = [
			'1x' => self::ICON_SMALL,
			'2x' => self::ICON_LARGE,
		];

		return $info;
	}

	/**
	 * Add "Check for updates" link to plugin row meta.
	 *
	 * @param array  $plugin_meta Plugin metadata.
	 * @param string $plugin_file Plugin file.
	 * @return array Modified plugin metadata.
	 */
	public function add_check_updates_button( array $plugin_meta, string $plugin_file ): array {
		if ( $plugin_file === $this->basename ) {
			$url           = wp_nonce_url(
				admin_url( 'admin-post.php?action=hkfn_check_for_updates&plugin=' . $this->basename ),
				'hkfn_check_for_updates'
			);
			$plugin_meta[] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Check for updates', 'hk-funeral-notices' ) . '</a>';
		}

		return $plugin_meta;
	}

	/**
	 * Handle manual update check request.
	 */
	public function check_for_updates(): void {
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to update plugins.', 'hk-funeral-notices' ) );
		}

		check_admin_referer( 'hkfn_check_for_updates' );

		// Clear our cache and WordPress's, then force a fresh check.
		delete_transient( self::CACHE_KEY );
		delete_site_transient( 'update_plugins' );

		$this->update_info = null;
		wp_update_plugins();

		wp_safe_redirect( admin_url( 'plugins.php' ) );
		exit;
	}

	/**
	 * After installing an update, move files to the correct directory.
	 *
	 * Release zips extract to a directory named after the repo. This renames it
	 * to match the existing plugin directory.
	 *
	 * @param bool|\WP_Error $response   Install response.
	 * @param array          $hook_extra Extra arguments.
	 * @param array          $result     Installation result data.
	 * @return array Modified result.
	 */
	public function after_install( $response, $hook_extra, $result ) {
		// Only act when *this* plugin is being updated.
		if ( empty( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->basename ) {
			return $result;
		}

		global $wp_filesystem;

		$install_directory = plugin_dir_path( $this->file );
		$wp_filesystem->move( $result['destination'], $install_directory );
		$result['destination'] = $install_directory;

		if ( $this->active ) {
			activate_plugin( $this->basename );
		}

		// Clear the cache so the next check fetches fresh data.
		delete_transient( self::CACHE_KEY );

		return $result;
	}
}
