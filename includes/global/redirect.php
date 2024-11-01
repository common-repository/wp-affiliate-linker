<?php
/**
 * Redirect class
 * 
 * @package   WP_Affiliate_Linker
 * @author    WP Affiliate Linker
 * @version   1.0.0
 * @copyright WP Affiliate Linker
 */
class WP_Affiliate_Linker_Redirect {

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
     * @since   1.0.0
     */
    public function __construct() {

        // Bail if we're in the admin interface
        if ( is_admin() ) {
            return;
        }

        // Get settings instance
        $settings = WP_Affiliate_Linker_Settings::get_instance();

        // Get the cloaking slug
        $cloaking_slug = $settings->get_setting( 'cloaking', 'slug' );
        
        // Fetch the permalink that's been requested
        $request_scheme = $this->get_request_scheme();
        $request_url = $request_scheme . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
        $site_url = get_bloginfo( 'url' );

        // Output some early debugging information
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG == true ) {
            echo '<!-- Request Scheme: ' . $request_scheme . ' -->' . "\n";
            echo '<!-- Request URL: ' . $request_url . ' -->' . "\n";
            echo '<!-- Site URL: ' . $site_url . ' -->' . "\n";
        }

        $endpoint = trim( str_replace( $site_url, '', $request_url ), '/' );
        if ( empty( $endpoint ) ) {
            return;
        }

        // Extract endpoint into parts
        $endpoint_parts = explode( '/', $endpoint );
        if ( ! is_array( $endpoint_parts ) ) {
            return;
        }

        // Bail if the first part of the endpoint isn't our cloaking slug
        if ( $endpoint_parts[0] != $cloaking_slug ) {
            return;
        }

        // Build the Post Name we're looking for
        unset( $endpoint_parts[0] );
        $post_name = implode( '/', $endpoint_parts );

        // If the Post Name is empty, we just accessed the cloaking slug
        if ( empty( $post_name ) ) {
            return;
        }

        // If here, this is a WP Affiliate Linker redirect URL

        // Find the Post Name
        global $wpdb;
        $post_id = $wpdb->get_col( $wpdb->prepare( "SELECT ID
                                                    FROM $wpdb->posts
                                                    WHERE post_name = %s
                                                    AND post_type = 'wp-affiliate-linker'
                                                    AND post_status = 'publish'
                                                    LIMIT 1",
                                                    (string) trim( $post_name ) ) );

        // If no Post ID was returned, bail
        if ( ! is_array( $post_id ) || $post_id[0] == 0 ) {
            return;
        }


        // Load WP Affiliate Linker Settings class
        require_once( WP_PLUGIN_DIR . '/wp-affiliate-linker/includes/global/settings.php' );

        // Get the Post ID, its redirect URL and redirect method
        $post_id    = (int) $post_id[0];
        $url        = $settings->get_post_setting( $post_id, 'link', 'url' );
        $redirect   = $settings->get_post_setting( $post_id, 'link', 'redirect' );

        // Allow devs / addons to filter the URL and Redirect
        $url        = apply_filters( 'wp_affiliate_linker_redirect_redirect_url',       $url,       $post_id );
        $redirect   = apply_filters( 'wp_affiliate_linker_redirect_redirect_redirect',  $redirect,  $post_id );

        // Allow devs / addons to perform any actions now, immediately before the redirect
        do_action( 'wp_affiliate_linker_redirect', $post_id, $url, $redirect );
        
        // Define the redirect header
        switch ( $redirect ) {
            case 301:
                header( 'HTTP/1.1 301 Moved Permanently' ); 
                break;

            case 302:
                header( 'HTTP/1.1 302 Found' ); 
                break;

            case 307:
                header( 'HTTP/1.1 307 Temporary Redirect' ); 
                break;

            case 308:
                header( 'HTTP/1.1 308 Permanent Redirect' ); 
                break;

        }

        // Perform redirect now
        header( 'Location: ' . $url );
        die();

    }

    /**
     * Fetches whether the site is HTTP or HTTPS
     *
     * @since   1.0.2
     */
    public function get_request_scheme() {

        // Use REQUEST_SCHEME, if available (Apache / nginx)
        if ( isset( $_SERVER['REQUEST_SCHEME'] ) && ! empty( $_SERVER['REQUEST_SCHEME'] ) ) {
            return $_SERVER['REQUEST_SCHEME'];
        }

        // Use HTTP_X_FORWARDED_PROTO, if available (Lightspeed, load balancers, reverse proxies)
        if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && ! empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ) {
            return $_SERVER['HTTP_X_FORWARDED_PROTO'];
        }

        // No request scheme could be found; fallback to is_ssl()
        if ( is_ssl() ) {
            return 'https';
        }

        return 'http';

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