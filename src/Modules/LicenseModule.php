<?php
declare(strict_types=1);

namespace WeaveStudios\FuneralNotices\Modules;

use WeaveStudios\FuneralNotices\Services\LicenseService;

/**
 * License Module
 *
 * Premium license management for enabling advanced features.
 * Handles license key validation, storage, and feature gating
 * for premium functionality like video hosting.
 *
 * @since 2.1.4
 */
class LicenseModule extends BaseModule {

    protected array $default_settings = [
        'license_key' => '',
        'license_status' => 'inactive',
        'last_check' => '',
        'enabled_features' => [],
        'license_expires' => '',
        'site_limit' => 1,
        'auto_check' => true,
    ];

    // License validation constants
    private const LICENSE_API_BASE_URL = WFN_HOSTER_API_URL;
    private const LICENSE_DOWNLOAD_ID = WFN_HOSTER_DOWNLOAD_ID;
    private const LICENSE_CHECK_INTERVAL = DAY_IN_SECONDS;
    private const OPTION_PREFIX = 'wfn_license_';

    private $license_service;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(
            'license',
            'Premium License',
            'Manage premium license keys and enable advanced features',
            '1.0.0'
        );

        // Initialize the license service
        $this->license_service = LicenseService::getInstance();
    }

    /**
     * Register admin page (required for dashboard access)
     */
    public function register_admin_page(): void {
        // Register the admin page but don't add to menu - accessed via Dashboard
        add_submenu_page(
            null, // Parent slug = null means no menu item
            $this->module_name . ' Settings',
            $this->module_name,
            'manage_options',
            'hkfn-module-' . $this->module_id,
            [$this, 'render_admin_page']
        );
    }

    /**
     * Initialize the license module
     */
    public function init(): void {
        // Initialize admin hooks manually
        if (is_admin()) {
            add_action('admin_menu', [$this, 'register_admin_page']);
            add_action('admin_init', [$this, 'process_admin_form']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        }

        // Only initialize frontend functionality if enabled
        if ($this->is_enabled()) {
            $this->init_frontend();
        }

        // Schedule license check if not already scheduled
        if (!wp_next_scheduled('wfn_check_license')) {
            wp_schedule_event(time(), 'daily', 'wfn_check_license');
        }

        // Register license check hook
        add_action('wfn_check_license', [$this, 'check_license_status']);

        // Add AJAX handlers for admin interface
        if (is_admin()) {
            add_action('wp_ajax_wfn_validate_license', [$this, 'ajax_validate_license']);
            add_action('wp_ajax_wfn_deactivate_license', [$this, 'ajax_deactivate_license']);
        }
    }

    /**
     * Check if a premium feature is available
     *
     * @param string $feature Feature name to check
     * @return bool True if feature is available
     */
    public function has_premium_feature(string $feature): bool {
        $status = $this->get_license_status();

        if (!$status['valid']) {
            return false;
        }

        // Check if license has expired
        if (!empty($status['expires']) && strtotime($status['expires']) < current_time('timestamp')) {
            return false;
        }

        // Check if feature is enabled
        return in_array($feature, $status['features'] ?? []);
    }

    /**
     * Get current license status
     *
     * @return array License status information
     */
    public function get_license_status(): array {
        $status = $this->license_service->getLicenseStatus();

        // Convert to expected format for existing UI
        return [
            'valid' => $status['status'] === 'active',
            'features' => $status['status'] === 'active' ? ['video_hosting'] : [],
            'expires' => '',
            'message' => $status['message'],
            'last_check' => current_time('mysql'),
            'customer_name' => '',
            'license_type' => $status['status'] === 'active' ? 'premium' : 'inactive'
        ];
    }

    /**
     * Get stored license key
     *
     * @return string License key or empty string
     */
    public function get_license_key(): string {
        return $this->license_service->getLicenseKey();
    }

    /**
     * Validate license key with hoster API
     *
     * @param string $license_key License key to validate
     * @return array Validation result with status, features, and expiration
     */
    public function validate_license(string $license_key): array {
        // Use the new license service
        $result = $this->license_service->activateLicense($license_key);

        if ($result['success']) {
            return [
                'valid' => true,
                'message' => $result['message'],
                'features' => ['video_hosting'],
                'expires' => '',
                'license_type' => 'premium'
            ];
        } else {
            return [
                'valid' => false,
                'message' => $result['message'],
                'features' => [],
                'expires' => ''
            ];
        }
    }

    // Old API methods removed - now using LicenseService

    /**
     * Deactivate license
     *
     * @param string $license_key License key to deactivate (optional)
     * @return array Deactivation result
     */
    public function deactivate_license(string $license_key = ''): array {
        return $this->license_service->deactivateLicense();
    }

    // License data management handled by LicenseService

    /**
     * Placeholder for scheduled license check
     * Will be implemented in sub-task 1.6
     */
    public function check_license_status(): void {
        // Placeholder - will be implemented in sub-task 1.6
    }

    /**
     * AJAX handler for license validation
     */
    public function ajax_validate_license(): void {
        // Verify nonce for security
        if (!check_ajax_referer('wfn_license_nonce', 'nonce', false)) {
            wp_send_json_error([
                'message' => 'Security verification failed. Please refresh the page and try again.'
            ]);
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => 'You do not have permission to manage licenses.'
            ]);
        }

        // Get and sanitize license key
        $license_key = sanitize_text_field($_POST['license_key'] ?? '');

        if (empty($license_key)) {
            wp_send_json_error([
                'message' => 'Please enter a license key.'
            ]);
        }

        // Validate the license
        $result = $this->validate_license($license_key);

        if ($result['valid']) {
            wp_send_json_success([
                'message' => $result['message'],
                'features' => $result['features'],
                'expires' => $result['expires'],
                'customer_name' => $result['customer_name'] ?? '',
                'license_type' => $result['license_type'] ?? 'premium'
            ]);
        } else {
            wp_send_json_error([
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'UNKNOWN_ERROR'
            ]);
        }
    }

    /**
     * AJAX handler for license deactivation
     */
    public function ajax_deactivate_license(): void {
        // Verify nonce for security
        if (!check_ajax_referer('wfn_license_nonce', 'nonce', false)) {
            wp_send_json_error([
                'message' => 'Security verification failed. Please refresh the page and try again.'
            ]);
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => 'You do not have permission to manage licenses.'
            ]);
        }

        // Deactivate license with dplugins API
        $result = $this->deactivate_license();

        if ($result['success']) {
            wp_send_json_success([
                'message' => $result['message']
            ]);
        } else {
            wp_send_json_error([
                'message' => $result['message']
            ]);
        }
    }

    /**
     * Render admin page wrapper (uses BaseModule structure)
     */
    public function render_admin_page(): void {
        ?>
        <div class="wrap wfn-module-admin">
            <div class="wfn-module-header">
                <div class="wfn-header-content">
                    <div class="wfn-header-text">
                        <h1><?php echo esc_html($this->module_name); ?> Settings</h1>
                        <p class="wfn-header-description"><?php echo esc_html($this->module_description); ?></p>
                        <div class="wfn-back-to-dashboard">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=hk-funeral-notices-dashboard')); ?>" class="button button-secondary">
                                <span class="dashicons dashicons-arrow-left-alt2"></span> Back to Dashboard
                            </a>
                        </div>
                    </div>
                    <div class="wfn-plugin-logo">
                        <img src="<?php echo esc_url(WFN_PLUGIN_URL . 'assets/images/wfn-logo.png'); ?>" alt="WFN Logo" class="wfn-logo-image">
                    </div>
                </div>
            </div>

            <div class="wfn-module-content">
                <?php $this->render_module_admin_content(); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render module admin content (required by BaseModule)
     */
    public function render_module_admin_content(): void {
        $license_key = $this->get_license_key();
        $license_status = $this->get_license_status();
        $nonce = wp_create_nonce('wfn_license_nonce');
        ?>
            <div class="wfn-admin-content">
                <!-- License Status Card -->
                <div class="wfn-card" style="margin-bottom: 20px;">
                    <div class="wfn-card-header">
                        <h2>License Status</h2>
                    </div>
                    <div class="wfn-card-body">
                        <?php if ($license_status['valid']): ?>
                            <div class="wfn-status-success">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <strong>Active Premium License</strong>
                            </div>

                            <div class="wfn-license-details" style="margin-top: 15px;">
                                <?php if (!empty($license_status['customer_name'])): ?>
                                    <p><strong>Licensed to:</strong> <?php echo esc_html($license_status['customer_name']); ?></p>
                                <?php endif; ?>

                                <?php if (!empty($license_status['expires'])): ?>
                                    <p><strong>Expires:</strong>
                                        <span class="<?php echo strtotime($license_status['expires']) < strtotime('+30 days') ? 'wfn-expiry-warning' : 'wfn-expiry-good'; ?>">
                                            <?php echo esc_html(date('F j, Y', strtotime($license_status['expires']))); ?>
                                        </span>
                                    </p>
                                <?php endif; ?>

                                <?php if (!empty($license_status['features'])): ?>
                                    <p><strong>Enabled Features:</strong></p>
                                    <ul class="wfn-features-list">
                                        <?php foreach ($license_status['features'] as $feature): ?>
                                            <li><span class="dashicons dashicons-yes"></span> <?php echo esc_html(ucwords(str_replace('_', ' ', $feature))); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>

                                <?php if (!empty($license_status['last_check'])): ?>
                                    <p style="color: #666; font-size: 12px;">
                                        Last verified: <?php echo esc_html(human_time_diff(strtotime($license_status['last_check']), current_time('timestamp'))); ?> ago
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="wfn-status-inactive">
                                <span class="dashicons dashicons-warning"></span>
                                <strong>No Active License</strong>
                            </div>
                            <p style="margin-top: 10px;">Enter your premium license key below to unlock advanced features.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- License Management Form -->
                <div class="wfn-card">
                    <div class="wfn-card-header">
                        <h2><?php echo $license_status['valid'] ? 'Update License' : 'Activate License'; ?></h2>
                    </div>
                    <div class="wfn-card-body">
                        <form id="wfn-license-form" method="post">
                            <div class="wfn-form-group">
                                <label for="license_key">License Key</label>
                                <div style="display: flex; gap: 10px; align-items: flex-start;">
                                    <input type="<?php echo $license_status['valid'] ? 'password' : 'text'; ?>"
                                           id="license_key"
                                           name="license_key"
                                           value="<?php echo esc_attr($license_key); ?>"
                                           placeholder="12345678901234567890123456789012"
                                           maxlength="32"
                                           pattern="[a-fA-F0-9]{32}"
                                           style="width: 100%; max-width: 400px; font-family: monospace;"
                                           required>
                                    <?php if ($license_status['valid'] && !empty($license_key)): ?>
                                        <button type="button" id="toggle-license-visibility" class="button button-secondary" style="min-width: 80px;">
                                            <span class="dashicons dashicons-visibility" style="margin-top: 3px;"></span>
                                            <span class="toggle-text">Show</span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <p class="wfn-form-description">
                                    Enter your 32-character premium license key (provided by HumanKind support)
                                    <?php if ($license_status['valid']): ?>
                                        <br><em>License key is hidden for security. Click "Show" to reveal it.</em>
                                    <?php endif; ?>
                                </p>
                            </div>

                            <div class="wfn-form-actions">
                                <button type="button" id="validate-license" class="button button-primary">
                                    <span class="button-text">
                                        <?php echo $license_status['valid'] ? 'Update License' : 'Activate License'; ?>
                                    </span>
                                    <span class="spinner" style="display: none;"></span>
                                </button>

                                <?php if ($license_status['valid']): ?>
                                    <button type="button" id="deactivate-license" class="button button-secondary" style="margin-left: 10px;">
                                        <span class="button-text">Deactivate License</span>
                                        <span class="spinner" style="display: none;"></span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </form>

                        <div id="license-message" style="margin-top: 15px;"></div>
                    </div>
                </div>

                <!-- Premium Features Info -->
                <div class="wfn-card" style="margin-top: 20px;">
                    <div class="wfn-card-header">
                        <h2>Premium Features</h2>
                    </div>
                    <div class="wfn-card-body">
                        <p>Activate your premium license to unlock these advanced features:</p>

                        <div class="wfn-features-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 15px;">
                            <div class="wfn-feature-item">
                                <h4><span class="dashicons dashicons-video-alt3"></span> Video Hosting</h4>
                                <p>Upload memorial videos with professional CDN hosting and modal playback.</p>
                            </div>

                            <div class="wfn-feature-item">
                                <h4><span class="dashicons dashicons-chart-line"></span> Advanced Analytics</h4>
                                <p>Detailed statistics and usage reporting for video content and site performance.</p>
                            </div>

                            <div class="wfn-feature-item">
                                <h4><span class="dashicons dashicons-images-alt2"></span> Enhanced Media</h4>
                                <p>Premium image hosting and advanced media management capabilities.</p>
                            </div>

                            <div class="wfn-feature-item">
                                <h4><span class="dashicons dashicons-admin-tools"></span> Priority Support</h4>
                                <p>Access to priority technical support and advanced configuration assistance.</p>
                            </div>
                        </div>

                        <div style="margin-top: 20px; padding: 15px; background: #f0f6fc; border: 1px solid #c3dafe; border-radius: 4px;">
                            <p style="margin: 0;"><strong>Need a Premium License?</strong></p>
                            <p style="margin: 5px 0 0;">Contact <a href="mailto:support@weave.co.nz">support@weave.co.nz</a> or visit our website to purchase a premium license.</p>
                        </div>

                        <?php if (self::LICENSE_DOWNLOAD_ID === 1): ?>
                        <div style="margin-top: 15px; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">
                            <p style="margin: 0; color: #856404;"><strong>⚠️ Development Note:</strong></p>
                            <p style="margin: 5px 0 0; color: #856404; font-size: 14px;">
                                The download ID is set to 1 (placeholder). Please create the "HumanKind Funeral Notices" download
                                in the <a href="https://weave.co.nz/wp-admin/post.php?post_type=hoster_downloads" target="_blank">dplugins hoster admin</a>
                                and update the LICENSE_DOWNLOAD_ID constant.
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            const nonce = '<?php echo $nonce; ?>';

            // Format license key input (hex validation)
            $('#license_key').on('input', function() {
                let value = $(this).val().replace(/[^a-fA-F0-9]/g, '').toLowerCase();
                if (value.length > 32) value = value.substr(0, 32);
                $(this).val(value);
            });

            // Toggle license key visibility
            $('#toggle-license-visibility').on('click', function() {
                const $input = $('#license_key');
                const $button = $(this);
                const $icon = $button.find('.dashicons');
                const $text = $button.find('.toggle-text');

                if ($input.attr('type') === 'password') {
                    $input.attr('type', 'text');
                    $icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
                    $text.text('Hide');
                } else {
                    $input.attr('type', 'password');
                    $icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
                    $text.text('Show');
                }
            });

            // Validate license
            $('#validate-license').on('click', function() {
                const $button = $(this);
                const $message = $('#license-message');
                const licenseKey = $('#license_key').val().trim();

                if (!licenseKey) {
                    showMessage('Please enter a license key.', 'error');
                    return;
                }

                setButtonLoading($button, true);
                $message.empty();

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wfn_validate_license',
                        license_key: licenseKey,
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            showMessage(response.data.message, 'success');
                            setTimeout(() => window.location.reload(), 1500);
                        } else {
                            showMessage(response.data.message || 'License validation failed.', 'error');
                        }
                    },
                    error: function() {
                        showMessage('Connection error. Please try again.', 'error');
                    },
                    complete: function() {
                        setButtonLoading($button, false);
                    }
                });
            });

            // Deactivate license
            $('#deactivate-license').on('click', function() {
                if (!confirm('Are you sure you want to deactivate your license? Premium features will be disabled.')) {
                    return;
                }

                const $button = $(this);
                const $message = $('#license-message');

                setButtonLoading($button, true);
                $message.empty();

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wfn_deactivate_license',
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            showMessage(response.data.message, 'success');
                            setTimeout(() => window.location.reload(), 1500);
                        } else {
                            showMessage(response.data.message || 'License deactivation failed.', 'error');
                        }
                    },
                    error: function() {
                        showMessage('Connection error. Please try again.', 'error');
                    },
                    complete: function() {
                        setButtonLoading($button, false);
                    }
                });
            });

            function setButtonLoading($button, loading) {
                if (loading) {
                    $button.prop('disabled', true);
                    $button.find('.button-text').hide();
                    $button.find('.spinner').show().css('visibility', 'visible');
                } else {
                    $button.prop('disabled', false);
                    $button.find('.button-text').show();
                    $button.find('.spinner').hide();
                }
            }

            function showMessage(message, type) {
                const $message = $('#license-message');
                $message.removeClass('notice-success notice-error notice-warning')
                        .addClass('notice notice-' + (type === 'success' ? 'success' : type === 'warning' ? 'warning' : 'error'))
                        .html('<p>' + message + '</p>')
                        .show();
            }
        });
        </script>

        <style>
        .wfn-status-success {
            color: #00a32a;
            font-size: 16px;
        }
        .wfn-status-success .dashicons {
            font-size: 18px;
            margin-right: 8px;
        }
        .wfn-status-inactive {
            color: #d63638;
            font-size: 16px;
        }
        .wfn-status-inactive .dashicons {
            font-size: 18px;
            margin-right: 8px;
        }
        .wfn-license-details p {
            margin: 8px 0;
        }
        .wfn-features-list {
            margin: 10px 0;
            padding-left: 0;
        }
        .wfn-features-list li {
            list-style: none;
            margin: 5px 0;
            color: #00a32a;
        }
        .wfn-features-list .dashicons {
            font-size: 14px;
            margin-right: 8px;
        }
        .wfn-expiry-warning {
            color: #d63638;
            font-weight: bold;
        }
        .wfn-expiry-good {
            color: #00a32a;
        }
        .wfn-feature-item h4 {
            margin: 0 0 8px 0;
            color: #1d2327;
        }
        .wfn-feature-item h4 .dashicons {
            margin-right: 8px;
            color: #0073aa;
        }
        .wfn-feature-item p {
            margin: 0;
            color: #646970;
            font-size: 14px;
        }
        </style>
        <?php
    }
}