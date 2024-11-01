<?php
/**
* Installation class
* 
 * @package   WP_Affiliate_Linker
 * @author    WP Affiliate Linker
 * @version   1.0.0
 * @copyright WP Affiliate Linker
*/
class WP_Affiliate_Linker_Install {

    /**
     * Holds the class object.
     *
     * @since 1.0.0
     *
     * @var object
     */
    public static $instance;

    /**
     * Called when the plugin is activated. Deliberately cast as a static function.
     *
     * @since 1.0.0
     *
     * @param bool $network_wide Network Wide activation
     */
    static public function activate( $network_wide = false ) {

        // Check if we are on a multisite install, activating network wide, or a single install
        if ( is_multisite() && $network_wide ) {
            // Multisite network wide activation
            // Iterate through each blog in multisite, creating table
            $sites = wp_get_sites( array( 
                'limit' => 0 
            ) );
            foreach ( $sites as $site ) {
                switch_to_blog( $site->blog_id );
                WP_Affiliate_Linker_Install::get_instance()->install();
                restore_current_blog();
            }
        } else {
            // Single Site
            WP_Affiliate_Linker_Install::get_instance()->install();
        }

    }

    /**
     * Activation routine when a WPMU site is activated. Deliberately cast as a static function.
     *
     * We run this because a new WPMU site may be added after the plugin is activated
     * so will need necessary database tables
     *
     * @since 1.0.0
     */
    static public function activate_wpmu_site( $blog_id ) {

        switch_to_blog( $blog_id );
        WP_Affiliate_Linker_Install::get_instance()->install();
        restore_current_blog();

    }

    /**
     * Runs the installation routine on the currently active site
     *
     * @since 1.0.0
     */
    public function install() {

        // Get main plugin instance
        $instance = WP_Affiliate_Linker::get_instance();

        // Update the version number
        update_option( $instance->plugin->name . '-version', $instance->plugin->version ); 

        // Flush rewrite rules at the end of this request
        add_action( 'shutdown', array( $this, 'install_shutdown' ) );

    }

    /**
     * Runs migration routines when the plugin is updated
     *
     * @since 1.0.0
     */
    public function upgrade() {

        global $wpdb;

        // Get main plugin instance
        $instance = WP_Affiliate_Linker::get_instance();

        // Get current installed version number
        $installed_version = get_option( $instance->plugin->name . '-version' ); // false | 1.1.7

        // If the version number matches the plugin version, bail
        if ( $installed_version == $instance->plugin->version ) {
            return;
        }

        // Update the version number
        update_option( $instance->plugin->name . '-version', $instance->plugin->version );  

        // Flush rewrite rules at the end of this request
        add_action( 'shutdown', array( $this, 'install_shutdown' ) );

    }

    /**
     * Runs the uninstallation routine on the currently active site
     *
     * @since 1.0.0
     */
    public function uninstall() {

        // Flush rewrite rules at the end of this request
        add_action( 'shutdown', array( $this, 'install_shutdown' ) );

    }

    /**
     * Runs steps at the end of the activation, upgrade or deactivation of this Plugin.
     *
     * @since   1.0.0
     */
    public function shutdown() {

        // Flush permalinks
        flush_rewrite_rules();

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