<?php
/**
 * Common class
 * 
 * @package   WP_Affiliate_Linker
 * @author    WP Affiliate Linker
 * @version   1.0.0
 * @copyright WP Affiliate Linker
 */
class WP_Affiliate_Linker_Common {

    /**
     * Holds the class object.
     *
     * @since 1.0.0
     *
     * @var object
     */
    public static $instance;

    /**
     * Helper method to retrieve public Post Types
     *
     * @since   1.0.0
     *
     * @param   bool    $output_objects Output Post Type Objects
     * @return  array                   Public Post Types
     */
    public function get_post_types( $output_objects = true ) {

        // Get public Post Types
        $types = get_post_types( array(
            'public' => true,
        ), ( $output_objects ? 'objects' : 'names' ) );

        // Filter out excluded post types
        $excluded_types = $this->get_excluded_post_types();
        if ( is_array( $excluded_types ) ) {
            foreach ( $excluded_types as $excluded_type ) {
                unset( $types[ $excluded_type ] );
            }
        }

        // Return filtered results
        return apply_filters( 'wp_affiliate_linker_common_get_post_types', $types );

    }

    /**
     * Helper method to retrieve excluded Post Types
     *
     * @since   1.0.0
     *
     * @return  array   Excluded Post Types
     */
    public function get_excluded_post_types() {

        // Define any excluded post types
        $types = array( 'attachment', WP_Affiliate_Linker_PostType::get_instance()->post_type_name );

        // Return filtered results
        return apply_filters( 'wp_affiliate_linker_common_get_excluded_post_types', $types );

    }

    /**
     * Helper method to retrieve Redirect types
     *
     * @since   1.0.0
     *
     * @return  array   Redirect Types
     */
    public function get_redirect_types() {

        // Define redirect types
        $redirect_types = array(
            301 => array(
                'name'  => 301,
                'label' => __( '301 Permanent', 'wp-affiliate-linker' ),
            ),
            302 => array(
                'name'  => 302,
                'label' => __( '302 Temporary', 'wp-affiliate-linker' ),
            ),
            307 => array(
                'name'  => 307,
                'label' => __( '307 Temporary (Alternative)', 'wp-affiliate-linker' ),
            ),
        );

        // Filter to add/remove redirect types
        $redirect_types = apply_filters( 'wp_affiliate_linker_common_get_redirect_types', $redirect_types );

        // Return
        return $redirect_types;

    }

    /**
     * Helper method to return the IP address of the current user
     *
     * Checks various $_SERVER keys to try and get the most accurate result
     *
     * @since   1.0.0
     *
     * @return  string  IP Address
     */
    public function get_user_ip_address() {

        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif ( ! empty($_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        // Return filtered results
        return apply_filters( 'wp_affiliate_linker_common_get_user_ip_address', $ip, $_SERVER );

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