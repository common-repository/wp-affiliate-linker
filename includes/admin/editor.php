<?php
/**
 * Editor class
 * 
 * @package   WP_Affiliate_Linker
 * @author    WP Affiliate Linker
 * @version   1.0.0
 * @copyright WP Affiliate Linker
 */
class WP_Affiliate_Linker_Editor {

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
     * @since 1.0.0
     */
    public function __construct() {

        add_action( 'admin_init', array( $this, 'setup_tinymce_plugins' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_quicktags_modal' ) );

    }

    /**
     * Enqueues the Modal and QuickTags script for the Text Editor
     *
     * @since   1.0.0
     */
    public function enqueue_scripts() {

        // Get base instance
        $this->base = WP_Affiliate_Linker::get_instance();

        // modal.js
        // Used for both TinyMCE and Quick Tag Modals
        wp_enqueue_script( $this->base->plugin->name . '-modal', $this->base->plugin->url . '/assets/js/min/modal-min.js', array( 'jquery' ) );
        wp_localize_script( $this->base->plugin->name . '-modal', 'wp_affiliate_linker_modal', array(
            'ajax'      => admin_url( 'admin-ajax.php' ),
            'nonce'     => wp_create_nonce( 'wp-affiliate-linker-search' ),
            'strings'   => array(
                'error'             => __( 'An error occured. Please try another search.', 'wp-affiliate-linker' ),
                'no_results'        => __( 'No results found. Please try another search.', 'wp-affiliate-linker' ),
                'no_link_selected'  => __( 'No Link was selected. Please try again.', 'wp-affiliate-linker' ),
            ),
        ) );

        // Quicktags
        wp_enqueue_script( $this->base->plugin->name . '-quicktags', $this->base->plugin->url . '/assets/js/min/quicktags-min.js', array( 'quicktags' ) );
        wp_localize_script( $this->base->plugin->name . '-quicktags', 'wp_affiliate_linker_quicktags', array(
            'ajax'          => admin_url( 'admin-ajax.php' ),
            'action'        => 'wp_affiliate_linker_output_quicktags_modal',
            'description'   => __( 'Inserts an Affiliate Link', 'wp-affiliate-linker' ),
            'title'         => __( 'Insert Affiliate Link', 'wp-affiliate-linker' ),
        ) );

    }

    /**
     * Enqueues CSS and JS for the QuickTags modal.
     *
     * @since   1.0.0
     */
    public function maybe_enqueue_quicktags_modal() {

        // Bail if this isn't the action we want
        if ( ! isset( $_REQUEST['action'] ) ) {
            return;
        }
        if ( $_REQUEST['action'] != 'wp_affiliate_linker_output_quicktags_modal' ) {
            return;
        }

        // Enqueue some WordPress CSS and JS
        wp_enqueue_style( 'buttons' );
        wp_enqueue_style( 'common' );
        wp_enqueue_style( 'forms' );
        
        // Enqueue Plugin CSS and JS
        WP_Affiliate_Linker_Admin::get_instance()->enqueue_scripts_css( 'modal', 'quicktags' );

    }

    /**
     * Setup calls to add a button and plugin to the WP_Editor
     *
     * @since   1.0.0
     */
    public function setup_tinymce_plugins() {

        // Check if rich editing is enabled for the user
        if ( get_user_option( 'rich_editing' ) != 'true' ) {
            return;
        }

        // Add filters to register TinyMCE Plugins
        add_filter( 'mce_external_plugins', array( $this, 'register_tinymce_plugins' ) );
        add_filter( 'mce_buttons', array( $this, 'register_tinymce_buttons' ) );

    }

    /**
     * Register JS plugins for the TinyMCE Editor
     *
     * @since   1.0.0
     *
     * @param   array   $plugins    JS Plugins
     * @return  array               JS Plugins
     */
    public function register_tinymce_plugins( $plugins ) {

        // Get base instance
        $this->base = WP_Affiliate_Linker::get_instance();

        // Register TinyMCE Plugins
        $plugins['wp_affiliate_linker_link']      = $this->base->plugin->url . 'assets/js/min/tinymce-min.js';

        // Allow devs / addons to register their own TinyMCE Plugins now
        $plugins = apply_filters( 'wp_affiliate_linker_editor_register_tinymce_plugins', $plugins );

        // Return
        return $plugins;

    }

    /**
     * Registers buttons in the TinyMCE Editor
     *
     * @since   1.0.0
     *
     * @param   array   $buttons    Buttons
     * @return  array               Buttons
     */
    public function register_tinymce_buttons( $buttons ) {

        // Add buttons
        array_push( $buttons, 'wp_affiliate_linker_link' );

        // Allow devs / addons to register their own TinyMCE buttons now
        $buttons = apply_filters( 'wp_affiliate_linker_editor_register_tinymce_buttons', $buttons );

        // Return
        return $buttons;

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