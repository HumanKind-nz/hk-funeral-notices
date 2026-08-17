<?php
declare(strict_types=1);

namespace HumanKind\FuneralNotices\FieldGroups;

use HumanKind\FuneralNotices\Services\LicenseService;

use HumanKind\FuneralNotices\Address\AddressFieldManager;

/**
 * Field Group Manager
 * Handles registration of modular ACF field groups
 * 
 * @since 2.0.0
 */
class FieldGroupManager {
    
    private static bool $registered = false;

    /**
     * Register all field groups
     */
    public function register(): void {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        // Initialize default venue hooks
        $this->init_default_venue_hooks();

        // Register immediately if ACF is already initialized
        if (did_action('acf/init')) {
            $this->register_all_groups();
        } else {
            // Register on multiple hooks to ensure fields are available
            add_action('acf/init', [$this, 'register_all_groups'], 5); // Early priority
            add_action('init', [$this, 'register_all_groups'], 20); // Backup hook
            add_action('admin_init', [$this, 'register_all_groups'], 5); // Admin backup
        }
    }

    /**
     * Register all field groups at once
     */
    public function register_all_groups(): void {
        // Prevent duplicate registration
        if (self::$registered) {
            return;
        }
        
        // Debug logging removed for production
        $this->register_personal_details();
        $this->register_notice_content();
        $this->register_event_details();
        $this->register_streaming_details();
        $this->register_media_documents();
        // Ensure taxonomy fields for funeral locations are available
        $this->register_location_taxonomy_fields();
        
        self::$registered = true;
        // Debug logging removed for production
    }
    
    /**
     * Check if legacy JSON field groups exist
     */
    private function has_legacy_field_groups(): bool {
        // Check for the main legacy field group key
        $legacy_groups = [
            'group_6125700a6a0a7', // Main funeral notice fields
            'group_61285c27a1a63'  // Funeral locations taxonomy
        ];
        
        foreach ($legacy_groups as $group_key) {
            if (acf_get_field_group($group_key)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get dynamic address field configuration based on available systems
     */
    private function get_address_field_config(): array {
        $address_manager = new AddressFieldManager();
        $mode = $address_manager->get_field_mode();
        
        // Debug logging removed for production
        
        if ($mode === AddressFieldManager::MODE_ACFE) {
            // Debug logging removed for production
            return $this->get_acfe_address_field();
        } else {
            // Debug logging removed for production
            return $this->get_custom_address_field();
        }
    }
    
    /**
     * Get ACFE Pro Google Maps field configuration
     */
    private function get_acfe_address_field(): array {
        return [
            'key' => 'field_hkfn_custom_address_acfe',
            'label' => 'Custom Location/Address',
            'name' => 'custom_address',
            'type' => 'acfe_google_maps',
            'instructions' => 'Search and select the funeral venue address',
            'conditional_logic' => [
                [
                    [
                        'field' => 'field_hkfn_location_type',
                        'operator' => '==',
                        'value' => 'custom',
                    ],
                ],
            ],
            'center_lat' => '-41.2865',
            'center_lng' => '174.7762',
            'zoom' => 14,
            'height' => 400,
            'wrapper' => ['width' => '100'],
        ];
    }
    
    /**
     * Get custom native Google Maps field configuration
     */
    private function get_custom_address_field(): array {
        return [
            'key' => 'field_hkfn_custom_address_native',
            'label' => 'Custom Location/Address',
            'name' => 'custom_address',
            'type' => 'hkfn_google_maps',
            'instructions' => 'Search and select the funeral venue address',
            'conditional_logic' => [
                [
                    [
                        'field' => 'field_hkfn_location_type',
                        'operator' => '==',
                        'value' => 'custom',
                    ],
                ],
            ],
            'center_lat' => '-41.2865',
            'center_lng' => '174.7762',
            'zoom' => 14,
            'height' => 400,
            'wrapper' => ['width' => '100'],
        ];
    }

    /**
     * Personal Details Field Group
     */
    public function register_personal_details(): void {
        acf_add_local_field_group([
            'key' => 'group_hkfn_personal_v2',
            'title' => 'Personal Details',
            'fields' => [
                [
                    'key' => 'field_hkfn_personal_group',
                    'label' => 'Personal Information',
                    'name' => 'hkfn_person_group',
                    'type' => 'group',
                    'instructions' => $this->get_help_instructions('personal'),
                    'layout' => 'block',
                    'sub_fields' => [
                        [
                            'key' => 'field_hkfn_firstname',
                            'label' => 'First Name(s)',
                            'name' => 'firstname',
                            'type' => 'text',
                            'instructions' => 'Also add title if needed, eg Dr, Captain',
                            'required' => 1,
                            'wrapper' => ['width' => '50', 'class' => 'names'],
                            'placeholder' => 'John',
                        ],
                        [
                            'key' => 'field_hkfn_lastname',
                            'label' => 'Last Name',
                            'name' => 'lastname',
                            'type' => 'text',
                            'instructions' => 'Family name',
                            'required' => 1,
                            'wrapper' => ['width' => '50', 'class' => 'names'],
                            'placeholder' => 'Smith',
                        ],
                        [
                            'key' => 'field_hkfn_birth_year',
                            'label' => 'Birth Year',
                            'name' => 'birth_year',
                            'type' => 'number',
                            'instructions' => 'Year of birth (optional)',
                            'wrapper' => ['width' => '25'],
                            'min' => 1900,
                            'max' => date('Y'),
                            'placeholder' => '1950',
                        ],
                        [
                            'key' => 'field_hkfn_death_year',
                            'label' => 'Death Year',
                            'name' => 'death_year',
                            'type' => 'number',
                            'instructions' => 'Year of death (optional)',
                            'wrapper' => ['width' => '25'],
                            'min' => 1900,
                            'max' => date('Y') + 1,
                            'placeholder' => date('Y'),
                            'default_value' => date('Y'),
                        ],
                        [
                            'key' => 'field_hkfn_person_image',
                            'label' => 'Person\'s Image',
                            'name' => '',
                            'type' => 'acfe_post_field',
                            'instructions' => 'Use the <strong>Upload photo</strong> button below to add the person\'s photo straight from your computer, then crop it for the grid and list pages. The full photo shows on the funeral notice page.',
                            'wrapper' => ['width' => '100'],
                            'field_type' => 'featured_image',
                            'instruction_placement' => 'above_field',
                        ],
                    ],
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'funeral-notice',
                    ],
                ],
            ],
            'menu_order' => 1,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'left',
            'instruction_placement' => 'label',
            'active' => true,
            'description' => 'Basic information about the deceased person',
        ]);
    }

    /**
     * Notice Content Field Group
     */
    public function register_notice_content(): void {
        acf_add_local_field_group([
            'key' => 'group_hkfn_content_v2',
            'title' => 'Notice Content',
            'fields' => [
                [
                    'key' => 'field_hkfn_content_group',
                    'label' => 'Notice Content',
                    'name' => 'hkfn_notice_group',
                    'type' => 'group',
                    'instructions' => $this->get_help_instructions('content'),
                    'layout' => 'block',
                    'sub_fields' => [
                        [
                            'key' => 'field_hkfn_memorial_header',
                            'label' => 'Memorial Header Text',
                            'name' => 'memorial_header',
                            'type' => 'text',
                            'instructions' => 'This text appears at the very top of the single funeral notice page, above the person\'s name and photo. Common examples: "In loving memory of", "Celebrating the life of", "In remembrance of", "A tribute to". Leave blank if you don\'t want any header text.',
                            'default_value' => $this->get_default_memorial_header(),
                            'placeholder' => $this->get_default_memorial_header(),
                            'prepend' => '',
                            'append' => '[Person\'s Name]',
                            'wrapper' => ['width' => '70'],
                        ],
                        [
                            'key' => 'field_hkfn_celebration_text',
                            'label' => 'Celebration Text',
                            'name' => 'celebration_text',
                            'type' => 'textarea',
                            'instructions' => 'Customize the celebration message. Use these placeholders: {firstname} for first name, {lastname} for last name, {fullname} for full name. Default: "Please join us in celebrating {firstname} {lastname}\'s life". Leave completely blank to hide this text.',
                            'default_value' => 'Please join us in celebrating {firstname} {lastname}\'s life',
                            'placeholder' => 'Please join us in celebrating {firstname} {lastname}\'s life',
                            'rows' => 2,
                            'new_lines' => 'br',
                            'wrapper' => ['width' => '100'],
                        ],
                        [
                            'key' => 'field_hkfn_newspaper_notice',
                            'label' => 'Newspaper Notice',
                            'name' => '',
                            'type' => 'acfe_post_field',
                            'instructions' => 'Copy & Paste the funeral notice here.',
                            'required' => 1,
                            'field_type' => 'content',
                            'instruction_placement' => 'above_field',
                        ],
                    ],
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'funeral-notice',
                    ],
                ],
            ],
            'menu_order' => 2,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'left',
            'instruction_placement' => 'label',
            'active' => true,
            'description' => 'Main content and text for the funeral notice',
        ]);
    }

    /**
     * Event Details Field Group
     */
    public function register_event_details(): void {
        acf_add_local_field_group([
            'key' => 'group_hkfn_event_v2',
            'title' => 'Event Details',
            'fields' => [
                [
                    'key' => 'field_hkfn_event_group',
                    'label' => 'Funeral Event Details',
                    'name' => 'hkfn_details_group',
                    'type' => 'group',
                    'instructions' => $this->get_help_instructions('event'),
                    'layout' => 'block',
                    'sub_fields' => [
                        [
                            'key' => 'field_hkfn_funeral_date',
                            'label' => 'Funeral Date',
                            'name' => 'funeral_date',
                            'type' => 'date_picker',
                            'instructions' => 'Date of the funeral service',
                            'wrapper' => ['width' => '33'],
                            'display_format' => 'd/m/Y',
                            'return_format' => 'Y-m-d',
                            'first_day' => 1,
                        ],
                        [
                            'key' => 'field_hkfn_funeral_time',
                            'label' => 'Funeral Time',
                            'name' => 'funeral_time',
                            'type' => 'time_picker',
                            'instructions' => 'Time of the funeral service',
                            'wrapper' => ['width' => '33'],
                            'display_format' => 'g:i a',
                            'return_format' => 'H:i:s',
                        ],
                [
                    'key' => 'field_hkfn_hide_datetime',
                    'label' => 'Hide Date/Time/Venue',
                    'name' => 'hide_datetime',
                    'type' => 'true_false',
                    'instructions' => 'Hide date, time, and venue/location from public display',
                    'wrapper' => ['width' => '34'],
                    'ui' => 1,
                    'ui_on_text' => 'Hidden',
                    'ui_off_text' => 'Show',
                ],
                        [
                            'key' => 'field_hkfn_location_type',
                            'label' => 'Location Display',
                            'name' => 'location_type',
                            'type' => 'radio',
                            'instructions' => 'Choose how to display the funeral location',
                            'choices' => [
                                'none' => 'Display No Location',
                                'existing' => 'Use one of our saved Venues',
                                'custom' => 'Enter a Custom Location/Address',
                            ],
                            'default_value' => 'existing',
                            'layout' => 'vertical',
                            'return_format' => 'value',
                        ],
                        [
                            'key' => 'field_hkfn_location',
                            'label' => 'Saved Venues/Locations',
                            'name' => 'location',
                            'type' => 'taxonomy',
                            'instructions' => 'Select from your Saved Venues (to add a saved venue <a href="mailto:support@weave.co.nz?subject=New%20Saved%20Location%20for%20Funeral%20Notices">contact Weave/HumanKind</a>)',
                            'conditional_logic' => [
                                [
                                    [
                                        'field' => 'field_hkfn_location_type',
                                        'operator' => '==',
                                        'value' => 'existing',
                                    ],
                                ],
                            ],
                            'taxonomy' => 'funeral-location',
                            'field_type' => 'select',
                            'allow_null' => 1,
                            'add_term' => 1,
                            'save_terms' => 1,
                            'load_terms' => 1,
                            'return_format' => 'object',
                            'wrapper' => ['width' => '100'],
                        ],
                        $this->get_address_field_config(),
                    ],
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'funeral-notice',
                    ],
                ],
            ],
            'menu_order' => 3,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'left',
            'instruction_placement' => 'label',
            'active' => true,
            'description' => 'Date, time and location information for the funeral',
        ]);
    }

    /**
     * Streaming Details Field Group
     */
    public function register_streaming_details(): void {
        acf_add_local_field_group([
            'key' => 'group_hkfn_streaming_v2',
            'title' => 'Streaming Details',
            'fields' => [
                [
                    'key' => 'field_hkfn_streaming_group',
                    'label' => 'Live Streaming Options',
                    'name' => 'hkfn_streaming_group',
                    'type' => 'group',
                    'instructions' => $this->get_help_instructions('streaming'),
                    'layout' => 'block',
                    'sub_fields' => [
                        [
                            'key' => 'field_hkfn_streaming_url',
                            'label' => 'Live Stream URL',
                            'name' => 'streaming_url',
                            'type' => 'url',
                            'instructions' => 'Enter the streaming URL. Supports OneRoom, YouTube, Vimeo, Vimeo Pro, and other services. Leave blank for no streaming.',
                            'placeholder' => 'https://view.oneroomstreaming.com/... or https://youtube.com/watch?v=... or https://vimeo.com/...',
                            'wrapper' => ['width' => '70'],
                        ],
                        [
                            'key' => 'field_hkfn_streaming_private',
                            'label' => 'Private Streaming',
                            'name' => 'streaming_private',
                            'type' => 'true_false',
                            'instructions' => 'Hide streaming from public visitors - only show to logged-in users',
                            'ui' => 1,
                            'ui_on_text' => 'Private',
                            'ui_off_text' => 'Public',
                            'wrapper' => ['width' => '30'],
                        ],
                        [
                            'key' => 'field_hkfn_streaming_note',
                            'label' => 'Streaming Note',
                            'name' => 'streaming_note',
                            'type' => 'text',
                            'instructions' => 'Optional note to display with the streaming link (e.g. "Service starts at 2:00 PM")',
                            'placeholder' => 'e.g. Service starts at 2:00 PM',
                        ],
                    ],
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'funeral-notice',
                    ],
                ],
            ],
            'menu_order' => 4,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'left',
            'instruction_placement' => 'label',
            'active' => true,
            'description' => 'Live streaming and video options for the funeral service',
        ]);
    }

    /**
     * Media & Documents Field Group
     */
    public function register_media_documents(): void {
        acf_add_local_field_group([
            'key' => 'group_hkfn_media_v2',
            'title' => 'Media & Documents',
            'fields' => [
                [
                    'key' => 'field_hkfn_media_group',
                    'label' => 'Service Documents',
                    'name' => 'hkfn_media_group',
                    'type' => 'group',
                    'instructions' => $this->get_help_instructions('media'),
                    'layout' => 'block',
                    'sub_fields' => [
                        [
                            'key' => 'field_hkfn_service_sheet',
                            'label' => 'Add Service Sheet',
                            'name' => 'service_sheet',
                            'type' => 'file',
                            'instructions' => 'Upload the funeral service order/program (PDF works best)',
                            'return_format' => 'array',
                            'library' => 'all',
                            'mime_types' => 'pdf,doc,docx',
                        ],
                        // Conditionally register video field OR upgrade message field
                        $this->get_video_field_or_upgrade_message(),
                        [
                            'key' => 'field_hkfn_additional_documents',
                            'label' => 'Additional Links or Attachments',
                            'name' => 'additional_documents',
                            'type' => 'repeater',
                            'instructions' => 'Add extra links or file attachments that visitors can access',
                            'layout' => 'block',
                            'button_label' => 'Add Link or Attachment',
                            'min' => 0,
                            'max' => 5,
                            'sub_fields' => [
                                [
                                    'key' => 'field_hkfn_document_title',
                                    'label' => 'Button Text',
                                    'name' => 'title',
                                    'type' => 'text',
                                    'instructions' => 'This text will appear on the button that visitors click',
                                    'placeholder' => 'e.g. Photo Gallery, Donate to Family, Video Tribute',
                                    'required' => 1,
                                ],
                                [
                                    'key' => 'field_hkfn_document_type',
                                    'label' => 'Link Type',
                                    'name' => 'document_type',
                                    'type' => 'radio',
                                    'instructions' => 'Choose what you want to add',
                                    'choices' => [
                                        'file' => 'Upload a File (PDF, Word doc, etc.)',
                                        'url' => 'Link to External Website'
                                    ],
                                    'default_value' => 'file',
                                    'layout' => 'vertical',
                                ],
                                [
                                    'key' => 'field_hkfn_document_file',
                                    'label' => 'Upload Your File',
                                    'name' => 'file',
                                    'type' => 'file',
                                    'instructions' => 'Choose a file from your computer to upload',
                                    'return_format' => 'array',
                                    'mime_types' => 'pdf,doc,docx,jpg,jpeg,png',
                                    'conditional_logic' => [
                                        [
                                            [
                                                'field' => 'field_hkfn_document_type',
                                                'operator' => '==',
                                                'value' => 'file',
                                            ],
                                        ],
                                    ],
                                ],
                                [
                                    'key' => 'field_hkfn_document_url',
                                    'label' => 'Website Address',
                                    'name' => 'url',
                                    'type' => 'url',
                                    'instructions' => 'Copy and paste the full website address (starting with https://)',
                                    'placeholder' => 'https://www.example.com/donation-page',
                                    'conditional_logic' => [
                                        [
                                            [
                                                'field' => 'field_hkfn_document_type',
                                                'operator' => '==',
                                                'value' => 'url',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'funeral-notice',
                    ],
                ],
            ],
            'menu_order' => 5,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'left',
            'instruction_placement' => 'label',
            'active' => true,
            'description' => 'Documents and media files related to the funeral service',
        ]);
    }


    /**
     * Get help instructions for each section
     */
    private function get_help_instructions(string $section): string {
        $instructions = [
            'personal' => 'Enter the basic information about the deceased person. The image uses WordPress featured image.',
            'content' => 'Add the main content for the funeral notice. This uses the WordPress post content editor.',
            'event' => 'Set the date, time and location details for the funeral service. The location selector will appear in the main form when you choose to use a different location.',
            'streaming' => 'Paste any streaming link from OneRoom, YouTube, Vimeo, iStream and we\'ll automatically detect the service type and embed it for you.',
            'media' => 'Upload service documents, memorial video slideshows, and additional files for the funeral.',
        ];

        return $instructions[$section] ?? '';
    }
    
    /**
     * Get default memorial header text from settings
     */
    private function get_default_memorial_header(): string {
        $settings = hkfn_get_option('module_settings', []);
        return $settings['default_memorial_header'] ?? 'In loving memory of';
    }
    
    /**
     * Funeral Location taxonomy fields (venue address and map link)
     * Mirrors legacy JSON group for 'funeral-location' taxonomy.
     */
    private function register_location_taxonomy_fields(): void {
        acf_add_local_field_group([
            'key' => 'group_hkfn_funeral_locations_v2',
            'title' => 'Funeral Locations',
            'fields' => [
                [
                    'key' => 'field_hkfn_location_address',
                    'label' => 'Location Address',
                    'name' => 'location_address',
                    'type' => 'textarea',
                    'rows' => 3,
                    'new_lines' => 'br',
                ],
                [
                    'key' => 'field_hkfn_location_map_link',
                    'label' => 'Location Google Link',
                    'name' => 'location_map_link',
                    'type' => 'link',
                    'return_format' => 'array',
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'taxonomy',
                        'operator' => '==',
                        'value' => 'funeral-location',
                    ],
                ],
            ],
            'menu_order' => 0,
            'position' => 'acf_after_title',
            'style' => 'default',
            'label_placement' => 'left',
            'instruction_placement' => 'label',
            'active' => true,
            'description' => 'Add your Chapel locations',
        ]);
    }

    /**
     * Get video field or setup message based on whether video is configured
     *
     * Returns either a functional video upload field (when Bunny credentials
     * are present) or a message field explaining how to set it up.
     */
    private function get_video_field_or_upgrade_message(): array {
        if ($this->has_premium_license()) {
            // Return functional video upload field
            return [
                'key' => 'field_hkfn_video_slideshow',
                'label' => 'Memorial Video Slideshow',
                'name' => 'video_slideshow',
                'type' => 'file',
                'instructions' => 'Upload a memorial video slideshow (MP4, MOV, AVI, WMV). Maximum 900MB. Video will be professionally hosted and streamed with BunnyStream CDN.<br><strong>Videos will take up to 10 minutes to be encoded and added to the funeral notice.</strong>',
                'return_format' => 'array',
                'library' => 'all',
                'mime_types' => 'mp4,mov,avi,wmv,webm',
                'max_size' => 900, // 900MB limit
            ];
        } else {
            // Return minimal message field explaining how to switch video on
            return [
                'key' => 'field_hkfn_video_upgrade_message',
                'label' => 'Memorial Video Slideshow',
                'name' => 'video_upgrade_message',
                'type' => 'message',
                'message' => '<strong>Not set up yet:</strong> memorial videos need your own Bunny Stream credentials. Add <code>HKFN_VIDEO_LIBRARY_ID</code> and <code>HKFN_VIDEO_API_KEY</code> to wp-config.php.',
                'new_lines' => '',
                'esc_html' => 0,
            ];
        }
    }

    /**
     * Get instructions for video field based on whether video is configured
     *
     * @deprecated Use get_video_field_or_upgrade_message() instead
     */
    private function get_video_field_instructions(): string {
        if (!$this->has_premium_license()) {
            return '<strong>Not set up yet:</strong> memorial videos need your own Bunny Stream credentials. ' .
                   'Add <code>HKFN_VIDEO_LIBRARY_ID</code> and <code>HKFN_VIDEO_API_KEY</code> to wp-config.php.';
        }

        return 'Upload a memorial video slideshow (MP4, MOV, AVI, WMV). Maximum 900MB. Video will be professionally hosted and streamed with BunnyStream CDN.<br><strong>Videos will take up to 10 minutes to be encoded and added to the funeral notice.</strong>';
    }

    /**
     * Check if premium license is active
     */
    private function has_premium_license(): bool {
        return LicenseService::isVideoConfigured();
    }

    /**
     * Initialize default venue hooks
     *
     * Sets up ACF filters to populate default venue on new posts
     */
    private function init_default_venue_hooks(): void {
        // Hook into ACF to set default value for venue field
        add_filter('acf/load_value/name=location', [$this, 'set_default_venue_value'], 10, 3);
    }

    /**
     * Set default venue value for new funeral notices
     *
     * @param mixed $value Current value
     * @param int|string $post_id Post ID
     * @param array $field Field configuration
     * @return mixed Default venue term ID or current value
     */
    public function set_default_venue_value($value, $post_id, array $field) {
        // Only apply to funeral-notice post type
        if (!is_numeric($post_id)) {
            return $value;
        }

        $post_type = get_post_type($post_id);
        if ($post_type !== 'funeral-notice') {
            return $value;
        }

        // Don't override existing values
        if (!empty($value)) {
            return $value;
        }

        // Only for new posts (no existing value in database)
        global $pagenow;
        $is_new_post = ($pagenow === 'post-new.php');

        if (!$is_new_post) {
            return $value;
        }

        // Get default venue from settings
        $settings = hkfn_get_option('module_settings', []);
        $default_venue = $settings['default_venue_location'] ?? '';

        // Return default venue term ID if set
        if (!empty($default_venue)) {
            return (int) $default_venue;
        }

        return $value;
    }
}
