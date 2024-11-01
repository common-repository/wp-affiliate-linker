<?php
/**
* Plugin Name: WP Affiliate Linker
* Plugin URI: https://wpaffiliatelinker.com
* Version: 1.0.2
* Author: WP Affiliate Linker
* Author URI: https://wpaffiliatelinker.com
* Description: Cloak, redirect and manage your affiliate links.
*/

/**
 * WP Affiliate Linker Class
 * 
 * @package   WP_Affiliate_Linker
 * @author    WP Affiliate Linker
 * @version   1.0.0
 * @copyright WP Affiliate Linker
 */
class WP_Affiliate_Linker {

    /**
     * Holds the class object.
     *
     * @since 1.0.0
     *
     * @var object
     */
    public static $instance;

    /**
     * Holds the plugin information object.
     *
     * @since 1.0.0
     *
     * @var object
     */
    public $plugin = '';

    /**
     * Holds the licensing class object.
     *
     * @since 1.0.0
     *
     * @var object
     */
    public $licensing = '';

    /**
    * Constructor. Acts as a bootstrap to load the rest of the plugin
    *
    * @since 1.0.0
    */
    public function __construct() {

        // Plugin Details
        $this->plugin = new stdClass;
        $this->plugin->name         = 'wp-affiliate-linker';
        $this->plugin->displayName  = 'WP Affiliate Linker';
        $this->plugin->folder       = plugin_dir_path( __FILE__ );
        $this->plugin->url          = plugin_dir_url( __FILE__ );
        $this->plugin->version      = '1.0.2';
        $this->plugin->home_url     = 'https://wpaffiliatelinker.com';
        $this->plugin->support_url  = 'https://wpaffiliatelinker.com/documentation';
        $this->plugin->purchase_url = 'https://wpaffiliatelinker.com/pricing';
        $this->plugin->review_notice = sprintf( __( 'Thanks for using %s to cloak your Affiliate Links!', $this->plugin->name ), $this->plugin->displayName );

        // Licensing Submodule
        if ( ! class_exists( 'Licensing_Update_Manager' ) ) {
            require_once( $this->plugin->folder . '_modules/licensing/lum.php' );
        }
        $this->licensing = new Licensing_Update_Manager( $this->plugin, 'https://wpaffiliatelinker.com', $this->plugin->name );

        // Initialize non-static classes that contain WordPress Actions or Filters
        // Admin
        if ( is_admin() ) {
            $wp_affiliate_linker_admin      = WP_Affiliate_Linker_Admin::get_instance();
            $wp_affiliate_linker_ajax       = WP_Affiliate_Linker_AJAX::get_instance();
            $wp_affiliate_linker_editor     = WP_Affiliate_Linker_Editor::get_instance();
            $wp_affiliate_linker_import     = WP_Affiliate_Linker_Import::get_instance();
            $wp_affiliate_linker_link       = WP_Affiliate_Linker_Link::get_instance();
            $wp_affiliate_linker_post       = WP_Affiliate_Linker_Post::get_instance();
            
            add_action( 'init', array( $this, 'upgrade' ) );
        }

        // Global
        $wp_affiliate_linker_posttype = WP_Affiliate_Linker_PostType::get_instance();
        $wp_affiliate_linker_taxonomy = WP_Affiliate_Linker_Taxonomy::get_instance();
        $wp_affiliate_linker_redirect = WP_Affiliate_Linker_Redirect::get_instance();

    }

    /**
     * Runs the upgrade routine once the plugin has loaded
     *
     * @since 1.0.0
     */
    public function upgrade() {

        // Run upgrade routine
        WP_Affiliate_Linker_Install::get_instance()->upgrade();

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

/**
 * Define the autoloader for this Plugin
 *
 * @since   1.0.0
 *
 * @param   string  $class_name     The class to load
 */
function WP_Affiliate_Linker_Autoloader( $class_name ) {

    // Define the required start of the class name
    $class_start_name = 'WP_Affiliate_Linker';

    // Get the number of parts the class start name has
    $class_parts_count = count( explode( '_', $class_start_name ) );

    // Break the class name into an array
    $class_path = explode( '_', $class_name );

    // Bail if it's not a minimum length (i.e. doesn't potentially have WP_Affiliate_Linker Autolinker)
    if ( count( $class_path ) < $class_parts_count ) {
        return;
    }

    // Build the base class path for this class
    $base_class_path = '';
    for ( $i = 0; $i < $class_parts_count; $i++ ) {
        $base_class_path .= $class_path[ $i ] . '_';
    }
    $base_class_path = trim( $base_class_path, '_' );

    // Bail if the first parts don't match what we expect
    if ( $base_class_path != $class_start_name ) {
        return;
    }

    // Define the file name we need to include
    $file_name = strtolower( implode( '-', array_slice( $class_path, $class_parts_count ) ) ) . '.php';

    // Define the paths with file name we need to include
    $include_paths = array(
        dirname( __FILE__ ) . '/includes/admin/' . $file_name,
        dirname( __FILE__ ) . '/includes/global/' . $file_name,
    );

    // Iterate through the include paths to find the file
    foreach ( $include_paths as $path_file ) {
        if ( file_exists( $path_file ) ) {
            require_once( $path_file );
            return;
        }
    }

    // If here, we couldn't find the file!

}
spl_autoload_register( 'WP_Affiliate_Linker_Autoloader' );

// Initialise class
$wp_affiliate_linker = WP_Affiliate_Linker::get_instance();

// Register activation hooks
register_activation_hook( __FILE__, array( 'WP_Affiliate_Linker_Install', 'activate' ) );
add_action( 'activate_wpmu_site', array( 'WP_Affiliate_Linker_Install', 'activate_wpmu_site' ) );