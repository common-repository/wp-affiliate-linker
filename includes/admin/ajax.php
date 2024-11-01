<?php
/**
 * AJAX class
 * 
 * @package   WP_Affiliate_Linker
 * @author    WP Affiliate Linker
 * @version   1.0.0
 * @copyright WP Affiliate Linker
 */
class WP_Affiliate_Linker_AJAX {

    /**
     * Holds the class object.
     *
     * @since 1.0.0
     *
     * @var object
     */
    public static $instance;

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {

        // Actions
        add_action( 'wp_ajax_wp_affiliate_linker_output_tinymce_modal', array( $this, 'output_tinymce_modal' ) );
        add_action( 'wp_ajax_wp_affiliate_linker_output_quicktags_modal', array( $this, 'output_quicktags_modal' ) );
        add_action( 'wp_ajax_wp_affiliate_linker_search', array( $this, 'search' ) );

    }

    /**
     * Loads the view for the TinyMCE modal.
     *
     * @since   1.0.0
     */
    public function output_tinymce_modal() {

        // Load View
        require_once( WP_Affiliate_Linker::get_instance()->plugin->folder . '/views/admin/modal.php' ); 
        die();

    }

    /**
     * Loads the view for the Text Editor modal.
     *
     * @since   1.0.0
     */
    public function output_quicktags_modal() {

        // Load View
        require_once( WP_Affiliate_Linker::get_instance()->plugin->folder . '/views/admin/quicktags.php' ); 
        die();

    }

    /**
     * Searches for matching Link Posts based on the given keywords.
     *
     * @since 1.0.0
     */
    public function search() {

        // Run a security check first.
        check_ajax_referer( 'wp-affiliate-linker-search', 'nonce' );

        // Get vars
        $keywords       = sanitize_text_field( $_POST['keywords'] );

        // Run search
        $results = WP_Affiliate_Linker_Link::get_instance()->get_by_title( $keywords );

        // Return
        wp_send_json_success( $results );

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