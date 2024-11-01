<?php
/**
 * Taxonomy class
 * 
 * @package   WP_Affiliate_Linker
 * @author    WP Affiliate Linker
 * @version   1.0.0
 * @copyright WP Affiliate Linker
 */
class WP_Affiliate_Linker_Taxonomy {

    /**
     * Holds the class object.
     *
     * @since 1.0.0
     *
     * @var object
     */
    public static $instance;

    /**
     * Holds the Post Type name.
     *
     * @since   1.0.0
     *
     * @var     string
     */
    public $taxonomy_name = 'wp-affiliate-linker-categories';

    /**
     * Constructor
     *
     * @since   1.0.0
     */
    public function __construct() {

        // Actions
        add_action( 'init', array( $this, 'register_taxonomy' ), 20 );

    }

    /**
     * Registers the Taxonomy
     *
     * @since   1.0.0
     */
    public function register_taxonomy() {
        
        // Register taxonomy
        register_taxonomy( $this->taxonomy_name, array( WP_Affiliate_Linker_PostType::get_instance()->post_type_name ), array(
            'labels'                => array(
                'name'              => __( 'Link Categories', 'wp-affiliate-linker' ),
                'singular_name'     => __( 'Link Category', 'wp-affiliate-linker' ),
                'search_items'      => __( 'Search Link Categories', 'wp-affiliate-linker' ),
                'all_items'         => __( 'All Link Categories', 'wp-affiliate-linker' ),
                'parent_item'       => __( 'Parent Link Category', 'wp-affiliate-linker' ),
                'parent_item_colon' => __( 'Parent Link Category:', 'wp-affiliate-linker' ),
                'edit_item'         => __( 'Edit Link Category', 'wp-affiliate-linker' ),
                'update_item'       => __( 'Update Link Category', 'wp-affiliate-linker' ),
                'add_new_item'      => __( 'Add New Link Category', 'wp-affiliate-linker' ),
                'new_item_name'     => __( 'New Link Category', 'wp-affiliate-linker' ),
                'menu_name'         => __( 'Link Categories', 'wp-affiliate-linker' ),
            ),
            'hierarchical'          => true,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'query_var'             => true,
        ) );

    }

    /**
     * Returns all Terms for this Taxonomy
     *
     * @since   1.0.0
     *
     * @param   array   $args   get_terms() compatible arguments
     * @return  mixed           false | WP_Error | array
     */
    public function get_terms( $args = array() ) {

        // If no taxonomy set, define it now
        if ( ! isset( $args['taxonomy'] ) ) {
            $args['taxonomy'] = 'wp-affiliate-linker-categories';
        }

        // Get Terms
        $terms = get_terms( $args );

        // Bail if an error
        if ( is_wp_error( $terms ) ) {
            return $terms;
        }

        // Filter
        $terms = apply_filters( 'wp_affiliate_linker_taxonomy_get_terms', $terms, $args );

        // Return
        return $terms;

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