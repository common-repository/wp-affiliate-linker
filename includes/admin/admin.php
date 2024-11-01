<?php
/**
 * Administration class
 * 
 * @package   WP_Affiliate_Linker
 * @author    WP Affiliate Linker
 * @version   1.0.0
 * @copyright WP Affiliate Linker
 */
class WP_Affiliate_Linker_Admin {

    /**
     * Holds the class object.
     *
     * @since   1.0.0
     *
     * @var     object
     */
    public static $instance;

    /**
     * Holds the base class object.
     *
     * @since   1.0.0
     *
     * @var     object
     */
    public $base;

    /**
     * Success, Warning and Error Notices
     *
     * @since 1.0.0
     *
     * @var array
     */
    public $notices = array(
        'success'   => array(),
        'warning'   => array(),
        'error'     => array(),
    );

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {

        // Export / Support Redirects
        add_action( 'init', array( $this, 'maybe_export' ), 99 );
        add_action( 'plugins_loaded', array( $this, 'maybe_redirect_to_support' ) );

        // Actions
        add_action( 'admin_enqueue_scripts', array( $this, 'scripts_css' ) );
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );

        // Screens
        add_filter( 'wp_affiliate_linker_admin_get_current_screen_import-export', array( $this, 'get_current_screen_import_export' ), 10, 2 );

    }

    /**
     * If the Export button was clicked, generate a JSON file and prompt its download now
     *
     * @since   1.0.0
     */
    public function maybe_export() {

        // Get current screen
        $screen = $this->get_current_screen();
        if ( ! $screen || is_wp_error( $screen ) ) {
            return;
        }

        // Check we're on the Import / Export screen
        if ( $screen['name'] != 'import-export' ) {
            return;
        }

        // Check we requested the export action
        if ( ! isset( $_GET['export'] ) ) {
            return;
        }
        if ( $_GET['export'] != 1 ) {
            return;
        }

        // Get export data
        $exporter = WP_Affiliate_Linker_Export::get_instance();
        $settings = $exporter->export();
        $exporter->force_file_download( $settings ); // This ends the PHP operation

    }

    /**
     * If the Support menu item was clicked, redirect
     *
     * @since   1.0.0
     */
    public function maybe_redirect_to_support() {

        // Get current screen
        $screen = $this->get_current_screen();
        if ( ! $screen || is_wp_error( $screen ) ) {
            return;
        }

        // Check we're on the Support screen
        if ( $screen['name'] != 'support' ) {
            return;
        }

        // Redirect
        wp_redirect( WP_Affiliate_Linker::get_instance()->plugin->support_url );
        die();

    }

    /**
     * Enqueues JS and CSS if we're on a plugin screen or the welcome screen.
     *
     * @since 1.0.0
     */
    public function scripts_css() {

        // Bail if we can't get the current admin screen, or we're not viewing a screen
        // belonging to this plugin.
        if ( ! function_exists( 'get_current_screen' ) ) {
            return;
        }

        // Get current screen and registered plugin screens
        $screen = get_current_screen();
        $screens = $this->get_screens();

        // If we're on Add / Edit Affiliate Link screen, enqueue
        if ( $screen->id == WP_Affiliate_Linker_PostType::get_instance()->post_type_name ) {
            $this->enqueue_scripts_css( 'link', $screen, $screens );
            return;
        }
        if ( $screen->id == 'edit-' . WP_Affiliate_Linker_PostType::get_instance()->post_type_name ) {
            $this->enqueue_scripts_css( 'link', $screen, $screens );
            return;
        }

        // If we're on any other Add / Edit Post/Page/CPT screen, enqueue
        if ( $screen->base == 'post' ) {
            $this->enqueue_scripts_css( 'post', $screen, $screens );
            return;
        }

        // Iterate through the registered screens, to see if we're viewing that screen
        foreach ( $screens as $registered_screen ) {
            if ( $screen->id == WP_Affiliate_Linker_PostType::get_instance()->post_type_name . '_page_' . WP_Affiliate_Linker_PostType::get_instance()->post_type_name . '-' . $registered_screen['name'] ) {
                // We're on a plugin screen
                $this->enqueue_scripts_css( $registered_screen['name'], $screen, $screens );
                return;
            }
        }

    }

    /**
     * Enqueues scripts and CSS
     *
     * @since 1.0.0
     *
     * @param   string      $plugin_screen_name     Plugin Screen Name (settings|import-export|link|post)
     * @param   WP_Screen   $screen                 Current WordPress Screen object
     * @param   array       $screens                Registered Plugin Screens (optional)
     */
    public function enqueue_scripts_css( $plugin_screen_name, $screen, $screens = '' ) {

        global $post;

        // Enqueue JS
        // These scripts are registered in _modules/licensing/lum.php
        wp_enqueue_script( 'lum-admin-clipboard' );
        wp_enqueue_script( 'lum-admin-tabs' );
        wp_enqueue_script( 'lum-admin' );

        // Enqueue CSS
        wp_enqueue_style( $this->base->plugin->name . '-admin', $this->base->plugin->url . 'assets/css/admin.css' );
        
        // Allow devs to load their JS / CSS now
        do_action( 'wp_affiliate_linker_admin_scripts_js', $screen, $screens );
        do_action( 'wp_affiliate_linker_admin_scripts_js_' . $plugin_screen_name, $screen, $screens );
        
        do_action( 'wp_affiliate_linker_admin_scripts_css', $screen, $screens );
        do_action( 'wp_affiliate_linker_admin_scripts_css_' . $plugin_screen_name, $screen, $screens );

    }

    /**
     * Enqueues JS and CSS for the Social Settings screen
     *
     * @since 1.0.0
     */
    public function social_scripts_css() {

        wp_enqueue_media();

    }
    
    /**
     * Adds menu and sub menu items to the WordPress Administration
     *
     * @since 1.0.0
     */
    public function admin_menu() {

        // Get the registered screens
        $screens = $this->get_screens();

        // Get base instance
        $this->base = WP_Affiliate_Linker::get_instance();

        // Iterate through screens, adding as submenu items
        foreach ( (array) $screens as $screen ) {
            // Add submenu page
            add_submenu_page( 'edit.php?post_type=' . WP_Affiliate_Linker_PostType::get_instance()->post_type_name, $screen['label'], $screen['label'], 'manage_options', $this->base->plugin->name . '-' . $screen['name'], array( $this, 'admin_screen' ) );
        }

        // Allow Licensing (Addons) submodule to add its menu link now
        do_action( str_replace( '-', '_', $this->base->plugin->name ) . '_admin_menu', 'edit.php?post_type=' . WP_Affiliate_Linker_PostType::get_instance()->post_type_name );

    }

    /**
     * Returns an array of screens for the plugin's admin
     *
     * @since 1.0
     *
     * @return array Sections
     */
    private function get_screens() {

        // Get base instance
        $this->base = WP_Affiliate_Linker::get_instance();

        // Define the settings screen
        $screens = array(
            'settings'   => array(
                'name'          => 'settings',
                'label'         => __( 'Settings', 'wp-affiliate-linker' ),
                'description'   => __( 'Defines how your redirect / affiliate links should look and function.', 'wp-affiliate-linker' ),
                'view'          => $this->base->plugin->folder . 'views/admin/settings-general.php',
                'columns'       => 2,
                'data'          => array(),
                'documentation' => 'https://wpaffiliatelinker.com/documentation/settings',
            ),
        );

        // Allow addons to specify additional screens
        $screens = apply_filters( 'wp_affiliate_linker_admin_get_screens', $screens );

        // Finally, add the Import & Export and Support Screens
        // This ensures they are always at the end of the admin menu
        $screens['import-export'] = array(
            'name'          => 'import-export',
            'label'         => __( 'Import &amp; Export', 'wp-affiliate-linker' ),
            'description'   => 
                __( 'Import configuration data from another WP Affiliate Linker installation, or a third party affiliate linker plugin 
                    that has been previously used on this site.
                    <br />
                    Export WP Affiliate Linker configuration data to a JSON file.', 'wp-affiliate-linker' ),
            'view'          => $this->base->plugin->folder . 'views/admin/settings-import-export.php',
            'columns'       => 1,
            'data'          => array(),
            'documentation' => 'https://wpaffiliatelinker.com/documentation',
        );
        $screens['support'] = array(
            'name'          => 'support',
            'label'         => __( 'Support', 'wp-affiliate-linker' ),
        );

        // Return
        return $screens;

    }

    /**
     * Gets the current admin screen the user is on
     *
     * @since 1.0.0
     *
     * @return array    Screen name and label
     */
    private function get_current_screen() {

        // Bail if no page given
        if ( ! isset( $_GET['page'] ) ) {
            return;
        }

        // Get current screen name
        $screen = sanitize_text_field( $_GET['page'] );

        // Get registered screens
        $screens = $this->get_screens();

        // If screen name matches plugin, we're on either the general screen or welcome screen
        if ( $screen == $this->base->plugin->name ) {
            return apply_filters( 'wp_affiliate_linker_admin_get_current_screen_welcome', $screens['welcome'], $screen );
        }

        // Remove the plugin name from the screen
        $screen = str_replace( $this->base->plugin->name . '-', '', $screen );

        // Check if the screen exists
        if ( ! isset( $screens[ $screen ] ) ) {
            return new WP_Error( 'screen_missing', __( 'The requested administration screen does not exist', 'wp-affiliate-linker' ) );
        }

        // Filter the result, to allow third parties to inject any data they want to access in their screen view now
        $screens[ $screen ] = apply_filters( 'wp_affiliate_linker_admin_get_current_screen_' . $screen, $screens[ $screen ], $screen );

        // Return the screen
        return $screens[ $screen ];

    }

    /**
     * Injects Import Sources information into the Import / Export screen data, for use by the view.
     *
     * @since   1.0.0
     *
     * @param   array   $screen         Screen
     * @param   string  $screen_name    Screen Name
     * @return  array                   Screen
     */
    public function get_current_screen_import_export( $screen, $screen_name ) {

        $screen['data'] = array(
            'import_sources' => WP_Affiliate_Linker_Import::get_instance()->get_import_sources(),
        );

        return $screen;

    }

    /**
     * Gets the current admin screen name the user is on
     *
     * @since   1.0.0
     *
     * @return  mixed  false | Screen Name
     */
    private function get_current_screen_name() {

        // If no page name was given, we're not on a plugin screen.
        if ( ! isset( $_GET['page'] ) ) {
            return false;
        }

        // Get screen name
        $screen = sanitize_text_field( $_GET['page'] );

        // Return
        return $screen;

    }

    /**
     * Gets the current admin screen tab the user is on
     *
     * @since   1.0.0
     *
     * @param   array   $tabs   Screen Tabs
     * @return  array           Tab name and label
     */
    private function get_current_screen_tab( $tabs ) {

        // If the supplied tabs are an empty array, return false
        if ( empty( $tabs ) ) {
            return false;
        }

        // If no tab defined, get the first tab name from the tabs array
        if ( ! isset( $_REQUEST['tab'] ) ) {
            foreach ( $tabs as $tab ) {
                return $tab;
            }
        }

        // Return the requested tab, if it exists
        if ( isset( $tabs[ $_REQUEST['tab'] ] ) ) {
            $tab = $tabs[ $_REQUEST['tab'] ];
            return $tab;
        } else {
            foreach ( $tabs as $tab ) {
                return $tab;
            }
        }

    }

    /**
     * Returns an array of tabs for each plugin section
     *
     * @since   1.0.0
     *
     * @param   string  $screen     Screen
     * @return  array               Tabs
     */
    private function get_screen_tabs( $screen ) {

        // Define tabs array
        $tabs = array();

        // Define the tabs depending on which screen is specified
        switch ( $screen ) {

            /**
             * Settings
             */
            case 'settings':
                $tabs = array(
                    'cloaking' => array(
                        'name'          => 'cloaking',
                        'label'         => __( 'Cloaking', 'wp-affiliate-linker' ),
                        'documentation' => 'https://wpaffiliatelinker.com/documentation/settings',
                    ),
                    'link' => array(
                        'name'          => 'link',
                        'label'         => __( 'Links', 'wp-affiliate-linker' ),
                        'documentation' => 'https://wpaffiliatelinker.com/documentation/settings',
                    ),
                );
                break;

            /**
             * Import & Export
             */
            case 'import-export':
                // Default tabs
                $tabs = array(
                    'import' => array(
                        'name'          => 'import',
                        'label'         => __( 'Import from WP Affiliate Linker', 'wp-affiliate-linker' ),
                        'documentation' => 'https://wpaffiliatelinker.com/documentation/import-wp-affiliate-linker/',
                    ),
                );

                // Depending on whether any third party affiliate linker plugin data is present in this install,
                // add additional tabs.
                $import_sources = WP_Affiliate_Linker_Import::get_instance()->get_import_sources();
                if ( count( $import_sources ) > 0 ) {
                    foreach ( $import_sources as $import_source ) {
                        $tabs[ 'import-' . $import_source['name'] ] = array(
                            'name'          => 'import-' . $import_source['name'],
                            'label'         => sprintf( __( 'Import from %s', 'wp-affiliate-linker' ), $import_source['label'] ),
                            'documentation' => $import_source['documentation'],
                        );
                    }
                }

                // Finally, add the export tab
                $tabs['export'] = array(
                    'name'          => 'export',
                    'label'         => __( 'Export', 'wp-affiliate-linker' ),
                    'documentation' => 'https://wpaffiliatelinker.com/documentation/export-configuration/',
                );

                break;

        }

        // Allow addons to define tabs on existing screens
        $tabs = apply_filters( 'wp_affiliate_linker_admin_get_screen_tabs', $tabs, $screen );

        // Return
        return $tabs;

    }

    /**
     * Output the Settings screen
     * Save POSTed data from the Administration Panel into a WordPress option
     *
     * @since 1.0.0
     */
    public function admin_screen() {

        // Get the current screen
        $screen = $this->get_current_screen();
        if ( ! $screen || is_wp_error( $screen ) ) {
            return;
        }

        // Maybe save settings
        $this->save_settings( $screen['name'] );

        // Hacky; get the current screen again, so its data is refreshed post save and actions
        // @TODO optimize this
        $screen = $this->get_current_screen();
        if ( ! $screen || is_wp_error( $screen ) ) {
            return;
        }
        
        // Get the tabs for the given screen
        $tabs = $this->get_screen_tabs( $screen['name'] );

        // Get the current tab
        // If no tab specified, get the first tab
        $tab = $this->get_current_screen_tab( $tabs );

        // Define a string of conditional tabs
        // The tabs are only displayed if the General > Enabled option is checked
        $conditional_tabs = '';
        foreach ( $tabs as $tab_key => $data ) {
            if ( $tab_key == 'settings' ) {
                continue;
            }

            $conditional_tabs .= $tab_key . ',';
        }
        $conditional_tabs = trim( $conditional_tabs, ',' );

        // Load View
        require_once( $this->base->plugin->folder . '/views/admin/settings.php' ); 
    
    }

    /**
     * Save settings for the given screen
     *
     * @since 1.0
     *
     * @param string     $screen     Screen (settings|import-export)
     */
    public function save_settings( $screen = 'settings' ) {

        // Check that some data was submitted in the request
        if ( ! isset( $_REQUEST[ $this->base->plugin->name . '_nonce' ] ) ) { 
            return;
        }

        // Invalid nonce
        if ( ! wp_verify_nonce( $_REQUEST[ $this->base->plugin->name . '_nonce' ], 'wp-affiliate-linker_' . $screen ) ) {
            $this->notices['error'][] = __( 'Invalid nonce specified. Settings NOT saved.', 'wp-affiliate-linker' );
            return false;
        }

        // Depending on the screen we're on, save the data and perform some actions
        switch ( $screen ) {
            /**
             * Settings
             */
            case 'settings':
                // Cloaking
                $result = WP_Affiliate_Linker_Settings::get_instance()->update_settings( 'cloaking', $_POST['cloaking'] );
                if ( is_wp_error( $result ) ) {
                    $this->notices['error'][] = $result->get_error_message();
                    return;
                }

                // Links
                $result = WP_Affiliate_Linker_Settings::get_instance()->update_settings( 'link', $_POST['link'] );
                if ( is_wp_error( $result ) ) {
                    $this->notices['error'][] = $result->get_error_message();
                    return;
                }

                // Addons
                $result = apply_filters( 'wp_affiliate_linker_admin_save_settings', true, $_POST );
                if ( is_wp_error( $result ) ) {
                    $this->notices['error'][] = $result->get_error_message();
                    return;
                }

                // If here, OK
                $this->notices['success'][] = __( 'Settings saved.', 'wp-affiliate-linker' );

                // Exit
                return;
                break;

            /**
             * Import
             */
            case 'import-export':
                // Determine which plugin we're importing settings from
                $import_sources = WP_Affiliate_Linker_Import::get_instance()->get_import_sources();
                if ( is_array( $import_sources ) && count( $import_sources ) > 0 ) {
                    foreach ( $import_sources as $import_source => $label ) {
                        // If a POST variable is set, import from this Plugin
                        if ( isset( $_POST['import_' . $import_source ] ) ) {
                            // See includes/admin/import.php for build in Importers; developers can add their own to hook here too
                            $result = apply_filters( 'wp_affiliate_linker_import_' . $import_source, false );
                            break;
                        }
                    }

                    if ( isset( $result ) ) {
                        break;
                    }
                }

                // If here, we might be importing a JSON file
                // Check if a file was uploaded
                if ( ! is_array( $_FILES ) ) {
                    $result = new WP_Error( __( 'No JSON file uploaded.', 'wp-affiliate-linker' ) );
                    break;
                }

                // Check if the uploaded file encountered any errors
                if ( $_FILES['import']['error'] != 0 ) {
                    $result = new WP_Error( __( 'Error when attempting to upload JSON file for import.', 'wp-affiliate-linker' ) );
                    break;
                }

                // Read file
                $handle = fopen( $_FILES['import']['tmp_name'], 'r' );
                $json = fread( $handle, $_FILES['import']['size'] );
                fclose( $handle );
                $data = json_decode( $json, true );

                // Import data
                $result = apply_filters( 'wp_affiliate_linker_import', false, $data );
                if ( is_wp_error( $result ) ) {
                    $this->notices['error'][] = $result->get_error_message();
                    return;
                }
                break;

            /**
             * Addons
             */
            default:
                // Allow devs / addons to run their tasks now
                $result = apply_filters( 'wp_affiliate_linker_admin_save_settings_' . $screen, '', $_POST );
                
                // If nothing was returned, bail
                if ( empty( $result ) ) {
                    return;
                }

                // If an error was returned, add it as the error message for output
                if ( is_wp_error( $result ) ) {
                    $this->notices['error'][] = $result->get_error_message();
                    return;
                }

                // If here, a success message was returned. Add it as the success message for output
                $this->notices['success'][] = $result;
                return;

                break;
        }

        // Define error or success message, depending on what happened.
        if ( is_wp_error( $result ) ) {
            $this->notices['error'][] = $result->get_error_message();
        } else {
            $this->notices['success'][] = __( 'Import successful.', 'wp-affiliate-linker' );
        }

    }

    /**
     * Helper method to get the setting value from the Plugin settings
     *
     * @since 1.0.0
     *
     * @param   string    $screen   Screen
     * @param   string    $keys     Setting Key(s)
     * @return  mixed               Value
     */
    public function get_setting( $screen = '', $key = '' ) {

        return WP_Affiliate_Linker_Settings::get_instance()->get_setting( $screen, $key );

    }

    /**
     * Returns the singleton instance of the class.
     *
     * @since 1.0.0
     *
     * @return object Class.
     */
    public static function get_instance() {

        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof self ) ) {
            self::$instance = new self;
        }

        return self::$instance;

    }

}