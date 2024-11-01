<?php
/**
 * Export class
 * 
 * @package   WP_Affiliate_Linker
 * @author    WP Affiliate Linker
 * @version   1.0.0
 * @copyright WP Affiliate Linker
 */
class WP_Affiliate_Linker_Export {

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

        
    }

    /**
     * Export data, forcing a browser download
     *
     * @since 1.0.0
     *
     * @return  array           Data
     */
    public function export() {

        // Get Plugin Settings, Terms and Posts
        $cloaking   = WP_Affiliate_Linker_Settings::get_instance()->get_settings( 'cloaking' );
        $link       = WP_Affiliate_Linker_Settings::get_instance()->get_settings( 'link' );
        $terms      = WP_Affiliate_Linker_Taxonomy::get_instance()->get_terms( array(
            'hide_empty' => 0,
        ) );
        $posts = WP_Affiliate_Linker_Link::get_instance()->get_all();

        // Build settings array
        $settings = array(
            'cloaking'  => $cloaking,
            'link'      => $link,
        );
        if ( ! is_wp_error( $terms ) && $terms !== false ) {
            $settings['_terms'] = $terms;
        }
        if ( ! is_wp_error( $posts ) && $posts !== false ) {
            $settings['_posts'] = $posts;
        }
        
        // Allow addons to add their own settings to the export file now
        $settings = apply_filters( 'wp_affiliate_linker_export', $settings );

        // Build JSON
        return json_encode( $settings );
        
    }

    /**
     * Force a browser download comprising of the given JSON data
     *
     * @since   1.0.0
     *
     * @param   string  $json   JSON Data for file
     */
    public function force_file_download( $json ) {

        // Output JSON, prompting the browser to auto download as a JSON file now
        header( "Content-type: application/x-msdownload" );
        header( "Content-Disposition: attachment; filename=export.json" );
        header( "Pragma: no-cache" );
        header( "Expires: 0" );
        echo $json;
        exit();

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