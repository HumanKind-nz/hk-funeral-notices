<?php
/**
 * GitHub release auto-updater with Hoster fallback.
 *
 * Hooks into the WordPress update system so updates from GitHub releases
 * appear in Dashboard → Updates with one-click install. Supports both
 * GitHub (public) and Hoster (premium token-based) update sources.
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

	HK_Funeral_Notices_Updater::init( HKFN_PLUGIN_FILE, HKFN_HOSTER_REMOTE_URL );
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\init' );

/**
 * GitHub + Hoster release updater for self-hosted plugins.
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

	/** @var object|null|false Cached GitHub API response. */
	private $github_response = null;

	/** @var object|null|false Cached Hoster API response. */
	private $hoster_response = null;

	/** @var string Hoster remote URL template. */
	private string $hoster_remote_url;

	// ── Configuration ────────────────────────────────────────────────────

	/** GitHub organisation or username. */
	private const GITHUB_USERNAME = 'HumanKind-nz';

	/** GitHub repository name. */
	private const GITHUB_REPO = 'hk-funeral-notices';

	/** Plugin icon — small (128×128). Served from BunnyCDN. */
	public const ICON_SMALL = 'https://weave-hk-github.b-cdn.net/humankind/icon-128x128.png';

	/** Plugin icon — large (256×256). Served from BunnyCDN. */
	public const ICON_LARGE = 'https://weave-hk-github.b-cdn.net/humankind/icon-256x256.png';

	/** Transient key for caching the GitHub API response. */
	private const GITHUB_CACHE_KEY = 'hkfn_github_response';

	/** Transient key for caching the Hoster API response. */
	private const HOSTER_CACHE_KEY = 'hkfn_hoster_response';

	/** Hours to cache a successful API response. */
	private const CACHE_DURATION = 4;

	/** Hours to cache an error response (prevents constant retries). */
	private const ERROR_CACHE_DURATION = 1;

	// ── Lifecycle ────────────────────────────────────────────────────────

	/**
	 * Private constructor — use init() instead.
	 *
	 * @param string $file             Main plugin file path.
	 * @param string $hoster_remote_url Hoster API URL template.
	 */
	private function __construct( string $file, string $hoster_remote_url = '' ) {
		$this->file              = $file;
		$this->basename          = plugin_basename( $this->file );
		$this->active            = is_plugin_active( $this->basename );
		$this->hoster_remote_url = $hoster_remote_url;

		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_update' ] );
		add_filter( 'plugins_api', [ $this, 'plugin_info' ], 20, 3 );
		add_filter( 'upgrader_post_install', [ $this, 'after_install' ], 10, 3 );
		add_action( 'plugin_row_meta', [ $this, 'add_check_updates_button' ], 10, 2 );
		add_action( 'admin_post_hkfn_check_for_updates', [ $this, 'check_for_updates' ] );
	}

	/**
	 * Create (or return) the singleton instance.
	 *
	 * @param string $file             Main plugin file path.
	 * @param string $hoster_remote_url Hoster API URL template.
	 * @return self
	 */
	public static function init( string $file, string $hoster_remote_url = '' ): self {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new self( $file, $hoster_remote_url );
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
		return ltrim( $version, 'v' );
	}

	/**
	 * Get update information from Hoster first, GitHub as fallback.
	 *
	 * @return object|false Update info or false on failure.
	 */
	private function get_update_info() {
		// Try Hoster first if URL is provided.
		if ( ! empty( $this->hoster_remote_url ) ) {
			$hoster_info = $this->get_hoster_info();
			if ( $hoster_info ) {
				return $hoster_info;
			}
		}

		// Fallback to GitHub.
		return $this->get_github_info();
	}

	/**
	 * Build Hoster URL with stored token (premium) or null for freemium fallback.
	 *
	 * @return string|null Hoster URL with token, or null to trigger GitHub fallback.
	 */
	private function build_hoster_url(): ?string {
		if ( class_exists( 'HK_Funeral_Notices_License_Handler' ) ) {
			$license_handler = \HK_Funeral_Notices_License_Handler::init();
			$stored_token    = $license_handler->get_stored_token();

			if ( ! empty( $stored_token ) ) {
				return str_replace( 'HOSTER_TOKEN_HERE', $stored_token, $this->hoster_remote_url );
			}
		}

		// Freemium users get updates from GitHub (repo is public).
		return null;
	}

	/**
	 * Get update information from Hoster API.
	 *
	 * @return object|false Hoster info or false on failure.
	 */
	private function get_hoster_info() {
		if ( null !== $this->hoster_response ) {
			return $this->hoster_response;
		}

		$cached = get_transient( self::HOSTER_CACHE_KEY );

		if ( false !== $cached ) {
			if ( is_array( $cached ) && isset( $cached['status'] ) && 'error' === $cached['status'] ) {
				return false;
			}

			$this->hoster_response = $cached;
			return $this->hoster_response;
		}

		$hoster_url = $this->build_hoster_url();

		if ( empty( $hoster_url ) ) {
			set_transient( self::HOSTER_CACHE_KEY, [ 'status' => 'error' ], self::ERROR_CACHE_DURATION * HOUR_IN_SECONDS );
			return false;
		}

		$response = wp_remote_get( $hoster_url, [
			'timeout' => 15,
			'headers' => [
				'Accept'     => 'application/json',
				'User-Agent' => 'HumanKind-FuneralNotices/' . HKFN_VERSION . '; ' . get_bloginfo( 'url' ),
			],
		] );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			set_transient( self::HOSTER_CACHE_KEY, [ 'status' => 'error' ], self::ERROR_CACHE_DURATION * HOUR_IN_SECONDS );
			return false;
		}

		$decoded = json_decode( wp_remote_retrieve_body( $response ) );

		if ( JSON_ERROR_NONE !== json_last_error() ) {
			set_transient( self::HOSTER_CACHE_KEY, [ 'status' => 'error' ], self::ERROR_CACHE_DURATION * HOUR_IN_SECONDS );
			return false;
		}

		set_transient( self::HOSTER_CACHE_KEY, $decoded, self::CACHE_DURATION * HOUR_IN_SECONDS );
		$this->hoster_response = $decoded;

		return $this->hoster_response;
	}

	/**
	 * Fetch the latest release info from GitHub with transient caching.
	 *
	 * @return object|false Release object or false on failure.
	 */
	private function get_github_info() {
		if ( null !== $this->github_response ) {
			return $this->github_response;
		}

		$cached = get_transient( self::GITHUB_CACHE_KEY );

		if ( false !== $cached ) {
			if ( is_array( $cached ) && isset( $cached['status'] ) && 'error' === $cached['status'] ) {
				return false;
			}

			$this->github_response = $cached;
			return $this->github_response;
		}

		$request_uri = sprintf(
			'https://api.github.com/repos/%s/%s/releases/latest',
			self::GITHUB_USERNAME,
			self::GITHUB_REPO
		);

		$response = wp_remote_get( $request_uri, [
			'timeout' => 15,
			'headers' => [
				'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ),
				'Accept'     => 'application/vnd.github.v3+json',
			],
		] );

		if ( is_wp_error( $response ) ) {
			\HumanKind\FuneralNotices\Hooks\debug_log( 'GitHub API error: ' . $response->get_error_message() );
			set_transient( self::GITHUB_CACHE_KEY, [ 'status' => 'error' ], self::ERROR_CACHE_DURATION * HOUR_IN_SECONDS );
			return false;
		}

		$code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $code ) {
			\HumanKind\FuneralNotices\Hooks\debug_log( 'GitHub API returned HTTP ' . $code );
			set_transient( self::GITHUB_CACHE_KEY, [ 'status' => 'error' ], self::ERROR_CACHE_DURATION * HOUR_IN_SECONDS );
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ) );

		if ( ! isset( $body->tag_name, $body->assets ) || empty( $body->assets ) ) {
			\HumanKind\FuneralNotices\Hooks\debug_log( 'GitHub API response missing tag_name or assets.' );
			set_transient( self::GITHUB_CACHE_KEY, [ 'status' => 'error' ], self::ERROR_CACHE_DURATION * HOUR_IN_SECONDS );
			return false;
		}

		// Use the first release asset (the zip built by GitHub Actions).
		$body->zipball_url = $body->assets[0]->browser_download_url ?? '';

		if ( empty( $body->zipball_url ) ) {
			\HumanKind\FuneralNotices\Hooks\debug_log( 'No download URL found in release assets.' );
			set_transient( self::GITHUB_CACHE_KEY, [ 'status' => 'error' ], self::ERROR_CACHE_DURATION * HOUR_IN_SECONDS );
			return false;
		}

		set_transient( self::GITHUB_CACHE_KEY, $body, self::CACHE_DURATION * HOUR_IN_SECONDS );
		$this->github_response = $body;

		return $this->github_response;
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

		// Handle different response formats (Hoster vs GitHub).
		$latest       = '';
		$download_url = '';

		if ( isset( $update_info->version ) ) {
			// Hoster format.
			$latest       = $this->normalize_version( $update_info->version );
			$download_url = $update_info->download_url ?? '';
		} elseif ( isset( $update_info->tag_name ) ) {
			// GitHub format.
			$latest       = $this->normalize_version( $update_info->tag_name );
			$download_url = $update_info->zipball_url ?? '';
		}

		$plugin_entry = [
			'slug'         => dirname( $this->basename ),
			'plugin'       => $this->basename,
			'new_version'  => $latest,
			'url'          => $plugin_data['PluginURI'] ?? '',
			'tested'       => get_bloginfo( 'version' ),
			'requires_php' => $plugin_data['RequiresPHP'] ?? '8.1',
			'requires'     => $plugin_data['RequiresWP'] ?? '6.6',
			'package'      => $download_url,
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
				$plugin_entry['package'] = '';
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
		$info->tested       = get_bloginfo( 'version' );
		$info->requires     = $plugin_data['RequiresWP'] ?? '6.6';
		$info->requires_php = $plugin_data['RequiresPHP'] ?? '8.1';

		// Handle different response formats.
		if ( isset( $update_info->version ) ) {
			// Hoster format.
			$info->version       = $this->normalize_version( $update_info->version );
			$info->last_updated  = $update_info->last_updated ?? '';
			$info->download_link = $update_info->download_url ?? '';
			$info->sections      = [
				'description' => $plugin_data['Description'] ?? '',
				'changelog'   => $update_info->sections->changelog ?? '',
			];
		} elseif ( isset( $update_info->tag_name ) ) {
			// GitHub format.
			$info->version       = $this->normalize_version( $update_info->tag_name );
			$info->last_updated  = $update_info->published_at ?? '';
			$info->download_link = $update_info->zipball_url ?? '';
			$info->sections      = [
				'description' => $plugin_data['Description'] ?? '',
				'changelog'   => $update_info->body ?? '',
			];
		}

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

		// Clear both caches to force fresh check.
		delete_transient( self::GITHUB_CACHE_KEY );
		delete_transient( self::HOSTER_CACHE_KEY );

		// Trigger fresh check.
		$this->get_update_info();

		wp_safe_redirect( admin_url( 'plugins.php' ) );
		exit;
	}

	/**
	 * After installing an update, move files to the correct directory.
	 *
	 * GitHub release zips extract to a directory named {repo}-{version}.
	 * This renames it to match the existing plugin directory.
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

		// Clear caches so the next check fetches fresh data.
		delete_transient( self::GITHUB_CACHE_KEY );
		delete_transient( self::HOSTER_CACHE_KEY );

		return $result;
	}
}
