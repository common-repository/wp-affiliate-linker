<?php
/**
 * Post class
 * 
 * @package   WP_Affiliate_Linker
 * @author    WP Affiliate Linker
 * @version   1.0.0
 * @copyright WP Affiliate Linker
 */
class WP_Affiliate_Linker_Post {

    /**
     * Holds the class object.
     *
     * @since 1.0.0
     *
     * @var object
     */
    public static $instance;

    /**
     * Holds the base class object.
     *
     * @since 1.0.0
     *
     * @var object
     */
    public $base;

    /**
     * Constructor
     *
     * @since   1.0.0
     */
    public function __construct() {

        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );

    }

    /**
     * Registers meta boxes on all public Post Types
     *
     * @since   1.0.0
     */
    public function add_meta_boxes() {

        // Get the tabs for the post screen
        $tabs = $this->get_screen_tabs();

        // If no tabs exist, don't output anything
        if ( empty( $tabs ) ) {
            return;
        }

        // Add Meta Box for each public Post Type
        // WP Affiliate Linker doesn't output anything for this meta box, but it allows
        // Addons to output Post/Page level settings.
        foreach ( WP_Affiliate_Linker_Common::get_instance()->get_post_types() as $post_type ) {
            add_meta_box( 
                'wp-affiliate-linker', 
                __( 'WP Affiliate Linker', 'wp-affiliate-linker' ), 
                array( $this, 'output_meta_box' ), 
                $post_type->name,
                'normal'
            );
        }

        // Save settings
        add_action( 'save_post', array( $this, 'save_settings' ), 10, 1 );

    }

    /**
     * Outputs the Meta Box on Affiliate Link Posts
     *
     * @since   1.0.0
     *
     * @param   WP_Post     $post   Affiliate Link Post
     */
    public function output_meta_box( $post ) {

        // Get base instance
        $this->base = WP_Affiliate_Linker::get_instance();

        // Get the tabs for the post screen
        $tabs = $this->get_screen_tabs();

        // Get the current tab
        // If no tab specified, get the first tab
        $tab = $this->get_current_screen_tab( $tabs );

        // Load view
        require_once( $this->base->plugin->folder . 'views/admin/post.php' ); 

    }

    /**
     * Returns an array of tabs for the Post screen
     *
     * @since   1.0.0
     *
     * @return  array               Tabs
     */
    private function get_screen_tabs() {

        // Define tabs array
        // WP Affiliate Linker doesn't provide any settings at Post level, but devs / addons might.
        $tabs = array();

        // Allow addons to define tabs on existing screens
        $tabs = apply_filters( 'wp_affiliate_linker_post_get_screen_tabs', $tabs );

        // Return
        return $tabs;

    }

    /**
     * Gets the current admin screen tab the user is on
     *
     * @since 1.0.0
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
     * Helper method to get the setting value from the Post setting, falling
     * back to the Plugin setting if no Post setting exists.
     *
     * @since 1.0.0
     *
     * @param   string    $screen   Screen
     * @param   string    $keys     Setting Key(s)
     * @param   int       $post_id  Post ID
     * @return  mixed               Value
     */
    private function get_setting( $screen, $key, $post_id ) {

        return WP_Affiliate_Linker_Settings::get_instance()->get_post_setting( $screen, $key, $post_id );

    }

    /**
     * Helper method to save settings for the given screen
     *
     * @since 1.0.0
     *
     * @param int     $post_id     Post ID
     */
    public function save_settings( $post_id ) {

        // Get base instance
        $this->base = WP_Affiliate_Linker::get_instance();

        // Run security checks
        // Missing nonce 
        if ( ! isset( $_POST[ $this->base->plugin->name . '_nonce' ] ) ) { 
            return false;
        }

        // Invalid nonce
        if ( ! wp_verify_nonce( $_POST[ $this->base->plugin->name . '_nonce' ], 'wp-affiliate-linker_post' ) ) {
            return false;
        }

        // Store the Plugin's POSTed data in an array
        $post_settings = ( isset( $_POST[ $this->base->plugin->name ] ) ? $_POST[ $this->base->plugin->name ] : array() );

        // Allow devs / addons to save settings now
        do_action( 'wp_affiliate_linker_post_save_settings', $post_id, $post_settings );

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