<?php
declare(strict_types=1);

namespace HumanKind\FuneralNotices\Modules;

/**
 * Module Interface
 * 
 * Contract that all modules must implement for consistent functionality
 * and integration with the dashboard system.
 * 
 * @since 2.0.0
 */
interface ModuleInterface {
    
    /**
     * Initialize the module
     * 
     * Called when the module is loaded. Should register hooks,
     * enqueue assets, and set up any required functionality.
     * 
     * @return void
     */
    public function init(): void;
    
    /**
     * Get module ID
     * 
     * Unique identifier for the module used in settings,
     * database options, and internal references.
     * 
     * @return string
     */
    public function get_id(): string;
    
    /**
     * Get module name
     * 
     * Human-readable name displayed in dashboard
     * and admin interface.
     * 
     * @return string
     */
    public function get_name(): string;
    
    /**
     * Get module description
     * 
     * Brief description of module functionality
     * for display in admin interface.
     * 
     * @return string
     */
    public function get_description(): string;
    
    /**
     * Get module version
     * 
     * Version number for tracking and compatibility.
     * 
     * @return string
     */
    public function get_version(): string;
    
    /**
     * Check if module is enabled
     * 
     * Determines if the module is currently active
     * based on user settings.
     * 
     * @return bool
     */
    public function is_enabled(): bool;
    
    /**
     * Enable the module
     * 
     * Activates the module and updates settings.
     * 
     * @return bool Success status
     */
    public function enable(): bool;
    
    /**
     * Disable the module
     * 
     * Deactivates the module and updates settings.
     * 
     * @return bool Success status
     */
    public function disable(): bool;
    
    /**
     * Render admin page
     * 
     * Outputs the HTML for the module's admin settings page.
     * Should include form elements, styling, and JavaScript.
     * 
     * @return void
     */
    public function render_admin_page(): void;
    
    /**
     * Handle form submission
     * 
     * Processes form data from the admin page.
     * Should validate, sanitize, and save settings.
     * 
     * @return bool Success status
     */
    public function handle_form_submission(): bool;
    
    /**
     * Get module settings
     * 
     * Returns array of current module settings
     * with default values for missing options.
     * 
     * @return array
     */
    public function get_settings(): array;
    
    /**
     * Get default settings
     * 
     * Returns array of default settings for the module.
     * Used for initialization and reset functionality.
     * 
     * @return array
     */
    public function get_default_settings(): array;
    
    /**
     * Update module settings
     * 
     * Updates module settings with validation and sanitization.
     * 
     * @param array $settings New settings array
     * @return bool Success status
     */
    public function update_settings(array $settings): bool;
    
    /**
     * Get admin page URL
     * 
     * Returns URL for the module's admin settings page.
     * 
     * @return string
     */
    public function get_admin_url(): string;
    
    /**
     * Get module features
     * 
     * Returns array of feature descriptions for display
     * in dashboard module cards.
     * 
     * @return array
     */
    public function get_features(): array;
    
    /**
     * Reset module settings
     * 
     * Resets all module settings to default values.
     * 
     * @return bool Success status
     */
    public function reset_settings(): bool;
}