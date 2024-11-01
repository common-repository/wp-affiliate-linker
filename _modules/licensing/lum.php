<?php
/**
 * License Update Manager Class
 * 
 * @package     LUM
 * @author      LUM
 * @version     1.0.0
 */
class Licensing_Update_Manager {

    /**
     * Holds the class object.
     *
     * @since   1.0.0
     *
     * @var     object
     */
    public static $instance;

    /**
     * Holds the Plugin object
     *
     * @since   1.0.0
     *
     * @var     object
     */
    private $plugin;

    /**
     * Holds the Licensing Endpoint API
     *
     * @since   1.0.0
     *
     * @var     string
     */
    private $endpoint;

    /**
     * Holds the path to this file's folder
     *
     * @since   1.0.0
     *
     * @var     string
     */
    private $licensing_folder;

    /**
     * Holds the URL to this file's folder
     *
     * @since   1.0.0
     *
     * @var     string
     */
    private $licensing_url;

     /**
     * Success and Error Notices
     *
     * @since 1.0.0
     *
     * @var array
     */
    private $notices = array(
        'success'   => array(),
        'errors'     => array(),
    );

    /**
     * Flag to determine if we've queried the remote endpoint
     * for updates. Prevents plugin update checks running
     * multiple times
     *
     * @since   1.0.0
     *
     * @var     boolean
     */
    public $update_check = false;

    /**
     * Constructor.
     *
     * @since 1.0.0
     * 
     * @param object $plugin    WordPress Plugin
     * @param string $endpoint  Licensing Endpoint
     */
    public function __construct( $plugin, $endpoint ) {

        // Plugin Details
        $this->plugin           = $plugin;
        $this->licensing_folder = plugin_dir_path( __FILE__ );
        $this->licensing_url    = plugin_dir_url( __FILE__ );
        $this->endpoint         = $endpoint;

        if ( is_admin() ) {
            // Register admin CSS and JS
            add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_scripts_css' ) );

            // Hook the Addons screen and Licensing functionality
            add_action( 'wp_loaded', array( $this, 'save_license_key' ), 1 );
            add_action( 'init', array( $this, 'manage_addons' ), 2 );
            add_action( str_replace( '-', '_', $this->plugin->name ) . '_admin_menu', array( $this, 'admin_menu' ), 98 );

            // Reviews
            add_action( 'wp_ajax_' . str_replace( '-', '_', $this->plugin->name ) . '_dismiss_review', array( $this, 'dismiss_review' ) );
            add_action( 'admin_notices', array( $this, 'display_review_request' ) );

            // Check for Addon Updates
            add_filter( 'pre_set_site_transient_update_plugins',    array( $this, 'update_addon_check' ) );
            add_filter( 'plugins_api',                              array( $this, 'plugins_api' ), 10, 3 );

            // Clear the Transient created by get_addons() when specific events occur
            add_action( 'delete_site_transient_update_plugins',     array( $this, 'clear_transients' ) );
            add_filter( 'upgrader_post_install',                    array( $this, 'clear_transients' ) );
            add_action( 'activated_plugin',                         array( $this, 'clear_transients' ) );
            add_action( 'deactivated_plugin',                       array( $this, 'clear_transients' ) );
        }
        
    }

    /**
     * Register JS scripts, which Plugins may optionally load via wp_enqueue_script()
     * Enqueues CSS
     *
     * @since   1.0.0
     */
    public function register_admin_scripts_css() {   

        // Load the non-minified versions if we're using SCRIPT_DEBUG
        $path = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG === true ) ? '' : 'min/';
        $file = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG === true ) ? '' : '-min';

        // JS
        wp_register_script( 'lum-admin-conditional',    $this->licensing_url . 'assets/js/' . $path . 'jquery.form-conditionals' . $file . '.js', array( 'jquery' ), $this->plugin->version, true );
        wp_register_script( 'lum-admin-clipboard',      $this->licensing_url . 'assets/js/' . $path . 'clipboard' . $file . '.js',                array( 'jquery' ), $this->plugin->version, true );
        wp_register_script( 'lum-admin-inline-search',  $this->licensing_url . 'assets/js/' . $path . 'inline-search' . $file . '.js',            array( 'jquery' ), $this->plugin->version, true );
        wp_register_script( 'lum-admin-media-library',  $this->licensing_url . 'assets/js/' . $path . 'media-library' . $file . '.js',            array( 'jquery' ), $this->plugin->version, true );
        wp_register_script( 'lum-admin-tabs',           $this->licensing_url . 'assets/js/' . $path . 'tabs' . $file . '.js',                     array( 'jquery' ), $this->plugin->version, true );
        wp_register_script( 'lum-admin-tags',           $this->licensing_url . 'assets/js/' . $path . 'tags' . $file . '.js',                     array( 'jquery' ), $this->plugin->version, true );
        wp_register_script( 'lum-admin',                $this->licensing_url . 'assets/js/' . $path . 'admin' . $file . '.js',                    array( 'jquery' ), $this->plugin->version, true );
           
        // CSS
        // Always enqueue, so the Addons screen can use it
        wp_enqueue_style( 'lum-admin',                 $this->licensing_url . 'assets/css/admin.css' ); 
        ?>
        <style type="text/css">
            li#menu-posts-<?php echo $this->plugin->name; ?> ul.wp-submenu li:last-child a { color: #74bf84; }
        </style>
        <?php

    }

    /**
     * Displays a dismissible WordPress Administration notice requesting a review, if the main
     * plugin's key action has been completed.
     *
     * @since   1.0.0
     */
    public function display_review_request() {

        // If we're not an Admin user, bail
        if ( ! function_exists( 'current_user_can' ) ) {
            return;
        }
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }

        // If the review request was dismissed by the user, bail.
        if ( $this->dismissed_review() ) {
            return;
        }

        // If no review request has been set by the plugin, bail.
        if ( ! $this->requested_review() ) {
            return;
        }

        // If here, display the request for a review
        include_once( $this->licensing_folder . '/views/review-notice.php' );

    }

    /**
     * Flag to indicate whether a review has been requested.
     *
     * @since   1.0.0
     *
     * @return  bool    Review Requested
     */
    public function requested_review() {

        $time = get_option( $this->plugin->name . '-review-request' );
        if ( empty( $time ) ) {
            return false;
        }

        // Check the current date and time matches or is later than the above value
        $now = time();
        if ( $now >= ( $time + ( 3 * DAY_IN_SECONDS ) ) ) {
            return true;
        }

        // We're not yet ready to show this review
        return false;

    }

    /**
     * Requests a review notification, which is displayed on subsequent page loads.
     *
     * @since   1.0.0
     */
    public function request_review() {

        // If a review has already been requested, bail
        $time = get_option( $this->plugin->name . '-review-request' );
        if ( ! empty( $time ) ) {
            return;
        }

        // Request a review, setting the value to the date and time now.
        update_option( $this->plugin->name . '-review-request', time() );

    }

    /**
     * Flag to indicate whether a review request has been dismissed by the user.
     *
     * @since   1.0.0
     *
     * @return  bool    Review Dismissed
     */
    public function dismissed_review() {

        return get_option( $this->plugin->name . '-review-dismissed' );

    }

    /**
     * Dismisses the review notification, so it isn't displayed again.
     *
     * @since   1.0.0
     */
    public function dismiss_review() {

        update_option( $this->plugin->name . '-review-dismissed', 1 );

        // Send success response if called via AJAX
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            wp_send_json_success( 1 );
        }

    }

    /**
     * Saves the license key, if it's been submitted.
     *
     * @since   1.0.0
     */
    public function save_license_key() {

        // Bail if no license key was submitted in the request
        if ( ! isset( $_POST[ $this->plugin->name ] ) ) {
            return;
        }
        if ( ! isset( $_POST[ $this->plugin->name ]['license_key'] ) ) {
            return;
        }

        // Store the supplied license key
        update_option( $this->plugin->name . '_license_key', $_POST[ $this->plugin->name ]['license_key'] );

        // Clear transients
        $this->clear_transients();

    }

    /**
     * Installs, activates or deactivates an Addon (WordPress Plugin).
     *
     * @since   1.0.0
     */
    public function manage_addons() {

        // Bail if no action is set
        if ( ! isset( $_REQUEST['action'] ) ) {
            return;
        }
        if ( ! isset( $_REQUEST['addon'] ) ) {
            return;
        }

        $action = sanitize_text_field( $_REQUEST['action'] );
        $addon = sanitize_text_field( $_REQUEST['addon'] );
        switch ( $action ) {
            /**
             * Install
             */
            case 'install':
                $result = $this->install_addon( $addon );
                $action_label = __( 'installed', 'lum' );
                break;

            /**
             * Activate
             */
            case 'activate':
                $result = $this->activate_addon( $addon );
                $action_label = __( 'activated', 'lum' );
                break;

            /**
             * Deactivate
             */
            case 'deactivate': 
                $result = $this->deactivate_addon( $addon );
                $action_label = __( 'deactivated', 'lum' );
                break;

            default:
                $result = new WP_Error( 'lum_manage_addons_invalid_action', __( 'Invalid action specified!', 'lum' ) );
                break;

        }

        // Add the result to the error or success notices
        if ( is_wp_error( $result ) ) {
            $this->notices['errors'][] = $result->get_error_message();
        } else {
            $this->notices['success'][] = sprintf( __( 'Addon %s successfully', 'lum' ), $action_label );
        }

    }

    /**
     * Add Addons Menu Link to the WordPress Administration interface
     *
     * @since   1.0.0
     *
     * @param   string  $parent_slug   Parent Slug 
     */
    public function admin_menu( $parent_slug = '' ) {

        // If a parent slug is defined, attach the submenu items to that
        // Otherwise use the plugin's name
        $slug = ( ! empty( $parent_slug ) ? $parent_slug : $this->plugin->name );

        add_submenu_page( $slug, __( 'Addons', $this->plugin->name ), __( 'Addons', $this->plugin->name ), 'manage_options', $this->plugin->name . '-addons', array( $this, 'addons_screen' ) );
    
    }

    /**
     * Outputs the Licensing and Addons screen.
     *
     * @since   1.0.0
     */
    public function addons_screen() {

        global $wp_version;

        // Define data for the view
        // License key check is forced here, populating $this->notices as necessary
        $screen = array(
            'data' => array(
                'license_key'               => $this->get_license_key(),
                'license_key_is_constant'   => $this->is_license_key_defined_as_constant(),
                'license_key_valid'         => $this->check_license_key_valid( true ),
                'addons'                    => $this->get_addons(),
                'wordpress_version'         => $wp_version,
            ),
        );

        // Output view
        include_once( $this->licensing_folder . '/views/addons.php' );

    }

    /**
     * Gets the license key from either the wp-config constant, or the options table
     *
     * @since   1.0.0
     *
     * @return  string  License Key
     */
    public function get_license_key() {

        // If the license key is defined in wp-config, use that
        if ( $this->is_license_key_defined_as_constant() ) {
            // Get from constant
            $license_key = constant( strtoupper( $this->plugin->name ) . '_LICENSE_KEY' );
        } else {
            // Get from options table
            $license_key = get_option( $this->plugin->name . '_license_key' );
        }

        return $license_key;

    }

    /**
     * Returns a flag indicating whether the license key is defined as a constant,
     * and is not empty.
     *
     * @since   1.0.0
     *
     * @return  bool    Defined as Constant
     */
    public function is_license_key_defined_as_constant() {

        return defined( strtoupper( $this->plugin->name ) . '_LICENSE_KEY' ) && ! empty( constant( strtoupper( $this->plugin->name ) . '_LICENSE_KEY' ) );

    }
    
    /**
     * Checks whether a license key has been specified in the settings table.
     * 
     * @since 1.0.0
     *
     * @return  bool    License Key Exists
     */                   
    public function check_license_key_exists() {

        // If the license key is defined in wp-config, use that
        if ( defined( strtoupper( $this->plugin->name ) . '_LICENSE_KEY' ) ) {
            $license_key = constant( strtoupper( $this->plugin->name ) . '_LICENSE_KEY' );
        } else {
            // Get from options table
            $license_key = get_option( $this->plugin->name . '_license_key' );
        }
        
        // Return license key
        return ( ( isset( $license_key ) && trim( $license_key ) != '' ) ? true : false );
    
    }    
    
    /**
     * Checks whether the license key stored in the settings table exists and is valid.
     *
     * If so, we store the latest remote plugin details in a transient, which can then be used when
     * updating plugins.
     * 
     * @since   1.0.0
     *
     * @param   bool    $force  Force License Key Check (used when saving the licensing screen form options)
     * @return  bool            License Key Valid
     */
    public function check_license_key_valid( $force = false ) { 

        // Check last result from transient
        // If it exists and is valid, assume the license key is still valid until
        // this transient expires
        if ( ! $force ) {
            if ( absint( get_site_transient( $this->plugin->name . '_valid' ) ) == 1 ) {
                // OK
                return true;
            }
        }
        
        // If here, we're either forcing a check, the transient does not exist / has expired,
        // or the license key wasn't valid last time around, so we need to keep checking.

        // If no license key exists, clear transients and just exit.
        if ( ! $this->check_license_key_exists() ) {
            delete_site_transient( $this->plugin->name . '_valid' );
            delete_site_transient( $this->plugin->name . '_version' );
            delete_site_transient( $this->plugin->name . '_package' );
            return false;
        }

        // Get site URL, excluding http(s), and whether this is an MS install
        $site_url = str_replace( parse_url( get_bloginfo('url'), PHP_URL_SCHEME ) . '://', '', get_bloginfo( 'url' ) );
        $is_multisite = ( is_multisite() ? '1' : '0' );

        // Get license key
        $license_key = $this->get_license_key();

        // Build endpoint
        $url = $this->endpoint . "/wp-content/plugins/lum/index.php?request=validate_license_key&params[]=" . $license_key . '&params[]=' . $this->plugin->name . '&params[]=' . urlencode( $site_url ) . '&params[]=' . $is_multisite;

        // Send license key check
        // Set user agent to beat aggressive caching
        $response = wp_remote_get( $url, array(
            'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.131 Safari/537.36',
        ) );

        // Check response
        if ( is_wp_error( $response ) ) {
            // Could not connect to licensing server
            // Assume the license key is valid, so the plugin can run, but don't permit updates right now
            set_site_transient( $this->plugin->name . '_valid', 1, ( HOUR_IN_SECONDS * 12 ) );

            $this->notices['errors'][] = sprintf( __( '%s: Could not connect to the licensing server. Please try again later.', 'wp-simple-seo' ), $this->plugin->displayName );
            return true;
        }

        // Retrieve the body content
        $body = wp_remote_retrieve_body( $response );
        
        if ( empty( $body ) ) {
            // Something went wrong
            // Assume the license key is valid, so the plugin can run, but don't permit updates right now
            set_site_transient( $this->plugin->name . '_valid', 1, ( HOUR_IN_SECONDS * 12 ) );

            $this->notices['errors'][] = sprintf( __( '%s: Response from the licensing server was invalid. Please try again later.', 'wp-simple-seo' ), $this->plugin->displayName );
            return true;
        }

        // Decode the body JSON into an array
        $result = json_decode( $body );

        // If result is null, an error occured on the licensing server
        if ( is_null( $result ) ) {
            // Something went wrong
            delete_site_transient( $this->plugin->name . '_valid' );

            $this->notices['errors'][] = sprintf( __( '%s: Response from the licensing server was not in the expected format. Please try again later.', 'wp-simple-seo' ), $this->plugin->displayName );
            return false;
        }

        // Check license key is valid
        if ( (int) $result->code != 1 ) {
            delete_site_transient( $this->plugin->name . '_valid' );
            
            // Define error message as license key is not valid
            $this->notices['errors'][] = $this->plugin->displayName . ': ' . (string) $result->codeDescription;
            return false;   
        }

        // If here, license key is valid
        // Update in plugin settings, and store the remote packages available
        set_site_transient( $this->plugin->name . '_valid', 1, ( HOUR_IN_SECONDS * 12 ) );
        if ( isset( $result->products ) ) {
            set_site_transient( $this->plugin->name . '_products', $result->products, ( HOUR_IN_SECONDS * 12 ) );
        }
        $this->notices['success'][] = $this->plugin->displayName . ': ' . (string) $result->codeDescription;
        
        return true;

    }  

    /**
     * Returns an array of Addons from the server.
     *
     * If a license key exists, each Addon will have ->attributes->licensed set as true or false,
     * as well as information on the currently available version based on the license validity.
     *
     * Results are stored in a Transient to prevent multiple API calls.
     *
     * @since   1.0.0
     *
     * @param   bool    $force          Force Addons API call (false will use the Transient, if available)
     * @return  mixed   false | array
     */
    public function get_addons( $force = false ) {

        // Return the transient data if we're not forcing an API call and data exists
        $addons = get_transient( $this->plugin->name . '_addons' );
        if ( ! $force && $addons !== false ) {
            return $addons;
        }

        // If here, we need to make an API call

        // Build endpoint
        $url = $this->endpoint . '/wp-content/plugins/lum/index.php?request=get_products';

        // If a license key exists, append it to the request
        $license_key = $this->get_license_key();
        if ( ! empty( $license_key ) ) {
            $url .= "&params[]=" . $license_key;
        }

        // Send request
        // Set user agent to beat aggressive caching
        $response = wp_remote_get( $url, array(
            'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.131 Safari/537.36',
        ) );

        // Check response
        if ( is_wp_error( $response ) ) {
            // Could not connect to server
            return false;
        }

        // Retrieve the body content
        $body = wp_remote_retrieve_body( $response );

        // Decode the body JSON into an array
        $result = json_decode( $body );

        // Check response data is valid
        if ( ! isset( $result->code ) || (int) $result->code != 1 ) {
            return false;
        }

        // Fetch list of installed WordPress Plugins, iterating through
        // the Addons available, marking them as installed and activated if
        // they already are on this WordPress installation and are already activated
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $installed_plugins = get_plugins();
        foreach ( $result->products as $key => $product ) {
            // If this product matches the Plugin information we have, remove it from the list, as it's not an Addon
            if ( $product->attributes->name == $this->plugin->name ) {
                unset( $result->products[ $key ] );
                continue;
            }

            // Define the plugin folder and filename
            $result->products[ $key ]->attributes->plugin_folder_file = $product->attributes->name . '/' . $product->attributes->name . '.php';

            // Installed?
            if ( array_key_exists( $result->products[ $key ]->attributes->plugin_folder_file, $installed_plugins ) ) {
                $result->products[ $key ]->attributes->installed = true;
                $result->products[ $key ]->attributes->installed_version = $installed_plugins[ $result->products[ $key ]->attributes->plugin_folder_file ]['Version'];
            } else {
                $result->products[ $key ]->attributes->installed = false;
                $result->products[ $key ]->attributes->installed_version = false;
            }

            // Activated?
            $result->products[ $key ]->attributes->active = is_plugin_active( $result->products[ $key ]->attributes->plugin_folder_file );

            // Define Installation, Activation and Deactivation URLs
            $result->products[ $key ]->attributes->install_url = add_query_arg( array( 
                'action'    => 'install',
                'addon'     => $result->products[ $key ]->attributes->name, 
            ), 'admin.php?page=' . $this->plugin->name . '-addons' );

            $result->products[ $key ]->attributes->activate_url = add_query_arg( array( 
                'action'    => 'activate',
                'addon'     => $result->products[ $key ]->attributes->name, 
            ), 'admin.php?page=' . $this->plugin->name . '-addons' );

            $result->products[ $key ]->attributes->deactivate_url = add_query_arg( array( 
                'action'    => 'deactivate',
                'addon'     => $result->products[ $key ]->attributes->name, 
            ), 'admin.php?page=' . $this->plugin->name . '-addons' );

        }

        // Store in a transient
        set_transient( $this->plugin->name . '_addons', $result->products, DAY_IN_SECONDS );

        // Return products
        return $result->products;

    }

    /**
     * Installs a Product / Addon (i.e. a WordPress Plugin) that exists in get_addons()
     *
     * @since   1.0.0
     *
     * @param   string  $name   Product Name
     */
    public function install_addon( $name ) {

        // Get all Addons
        $addons = $this->get_addons();
        if ( ! $addons ) {
            return new WP_Error( 'lum_install_addon', __( 'Could not fetch the list of Addons', 'lum' ) );
        }

        // Find Product to install
        foreach ( $addons as $key => $addon ) {
            // If this isn't the Product we want, continue
            if ( $addon->attributes->name != $name ) {
                continue;
            }

            // Don't install if the license doesn't permit use of this Addon
            if ( ! $addon->attributes->licensed ) {
                return new WP_Error( 'lum_install_addon', __( 'Your license does not include this Addon.', 'lum' ) );
            }

            // Don't install if the license has expired
            if ( $addon->attributes->license_expired ) {
                return new WP_Error( 'lum_install_addon', __( 'Your license includes this Addon, however your license has expired. To install the latest version of this Addon, please renew your license.', 'lum' ) );
            }

            // Get file
            $file = $addon->attributes->file;
            if ( ! $file ) {
                return new WP_Error( 'lum_install_addon', __( 'This Addon does not have a file specified to be installed. Please contact the developer.', 'lum' ) );    
            }

            // Install file
            require_once ( ABSPATH . 'wp-admin/includes/plugin.php' );
            require_once ( ABSPATH . 'wp-admin/includes/plugin-install.php' );
            require_once ( ABSPATH . 'wp-admin/includes/file.php' );
            require_once ( ABSPATH . 'wp-admin/includes/misc.php' );
            require_once ( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
            require_once ( $this->licensing_folder . '/includes/admin/skin.php' );
            $installer = new Plugin_Upgrader( $skin = new Licensing_Update_Manager_Skin() );
            $result = $installer->install( $file->url );

            // If no plugin info is found, something went wrong with the Installation
            if ( ! $installer->plugin_info() || $result != true ) {
                return new WP_Error( 'lum_install_addon', __( 'Addon could not be installed. Please try again.', 'lum' ) );
            }

            // Done
            return true;
        }

        // If here, we couldn't find the Addon
        return new WP_Error( 'lum_install_addon', __( 'Addon could not be found.', 'lum' ) );

    }

    /**
     * Activates an installed Addon (a WordPress Plugin)
     *
     * @since   1.0.0
     *
     * @param   string  $name   Addon Name
     * @return  mixed           WP_Error | null
     */
    public function activate_addon( $name ) {

        // Load required functions if the function does not exist.
        if ( ! function_exists( 'activate_plugin' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        return activate_plugin( $name . '/' . $name . '.php' );

    }

    /**
     * Deactivates an activated Addon (a WordPress Plugin)
     *
     * @since   1.0.0
     *
     * @param   string  $name   Addon Name
     * @return  mixed           WP_Error | null
     */
    public function deactivate_addon( $name ) {

        // Load required functions if the function does not exist.
        if ( ! function_exists( 'deactivate_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        return deactivate_plugins( $name . '/' . $name . '.php' );

    }
    
    /**
     * Hooks into the plugin update check process, populating the transient data to tell WordPress
     * about the latest version of all Addons.
     *
     * @since   1.0.0
     *
     * @param   array   $transient  Transient of all Plugins with update information
     * @return  array               Transient of all Plugins with update information
     */
    public function update_addon_check( $transient ) {

        // Get all Addons
        // We'll only get Product update data if a license key exists and is valid
        $addons = $this->get_addons();

        if ( ! $addons ) {
            return $transient;
        }

        foreach ( $addons as $addon ) {
            // If this Addon is not installed, we can't update it
            if ( ! $addon->attributes->installed ) {
                continue;
            }

            // If this Addon is not licensed, don't update it
            if ( ! $addon->attributes->licensed ) {
                continue;
            }

            // If this Addon does not have a file (package), we have nothing to download
            if ( ! $addon->attributes->file ) {
                continue;
            }

            // Build data to add to transient
            $update                 = new stdClass;
            $update->slug           = $addon->attributes->name;
            $update->plugin         = $addon->attributes->name . '/' . $addon->attributes->name . '.php';
            $update->new_version    = $addon->attributes->version;
            $update->url            = $addon->guid;
            $update->package        = $addon->attributes->file->url;
            $update->upgrade_notice = __( 'Please keep Addons updated, for best performance and security.', $this->plugin->name );
            $update->tested         = $addon->attributes->max_wp_version;

            // Add the data to the response or no_update objects, depending on whether this Addon's version
            // is newer than the currently installed version.
            if ( $addon->attributes->version != $addon->attributes->installed_version ) {
                // Add to list of updates
                $transient->response[ $addon->attributes->name . '/' . $addon->attributes->name . '.php' ] = $update;   
            } else {
                // Add to no_update
                $transient->no_update[ $addon->attributes->name . '/' . $addon->attributes->name . '.php' ] = $update;
            }   
        }

        return $transient;

    }

    /**
     * Hooks into the plugins_api process, telling WordPress information about our Addon, such
     * as the WordPress compatible version and the changelog.
     *
     * @since   1.0.0
     *
     * @param   object    $api      The original plugins_api object.
     * @param   string    $action   The action sent by plugins_api.
     * @param   array     $args     Additional args to send to plugins_api.
     * @return  object              New stdClass with Addon information on success, default response on failure.
     */
    public function plugins_api( $api, $action = '', $args = null ) {

        // Check if we are requesting Plugin Information
        if ( $action != 'plugin_information' ) {
            return $api;
        }
        if ( ! isset( $args->slug ) ) {
            return $api;
        }

        // Check if we're fetching information for an Addon
        $addons = $this->get_addons();
        if ( ! is_array( $addons ) ) {
            return $api;
        }

        // Iterate through Addons
        foreach ( $addons as $addon ) {
            // Skip if this Addon isn't the one the user wants information for
            if ( $addon->attributes->name != $args->slug ) {
                continue;
            }

            // If this Addon is not installed, skip
            if ( ! $addon->attributes->installed ) {
                continue;
            }

            // If this Addon is not licensed, skip
            if ( ! $addon->attributes->licensed ) {
                continue;
            }

            // If this Addon does not have a file (package), skip
            if ( ! $addon->attributes->file ) {
                continue;
            }

            // If here, we found the Addon that the user wants to get the information for.
            // Create a new stdClass object and populate it with our plugin information.
            $api                        = new stdClass;
            $api->name                  = $this->plugin->name . ': ' . $addon->post_title;
            $api->slug                  = $addon->attributes->name;
            $api->plugin                = $addon->attributes->name . '/' . $addon->attributes->name . '.php';
            $api->version               = $addon->attributes->version;
            $api->author                = $this->plugin->displayName;
            $api->author_profile        = $this->plugin->support_url;
            $api->requires              = $addon->attributes->min_wp_version;
            $api->tested                = $addon->attributes->max_wp_version;
            $api->last_updated          = $addon->post_modified_gmt;
            $api->homepage              = $this->plugin->home_url;
            $api->sections['changelog'] = $addon->attributes->changelog;
            $api->download_link         = $addon->attributes->file->url;
        }

        // Return
        return $api;

    }

    /**
     * Deletes the Addons transient
     *
     * @since   1.0.0
     */
    public function clear_transients() {

        delete_transient( $this->plugin->name . '_addons' );

    }

}