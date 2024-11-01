<?php
/**
 * Post Type class
 * 
 * @package   WP_Affiliate_Linker
 * @author    WP Affiliate Linker
 * @version   1.0.0
 * @copyright WP Affiliate Linker
 */
class WP_Affiliate_Linker_PostType {

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
    public $post_type_name = 'wp-affiliate-linker';

    /**
     * Constructor
     *
     * @since   1.0.0
     */
    public function __construct() {

        // Actions
        add_action( 'init', array( $this, 'register_post_type' ), 10 );

    }

    /**
     * Registers the Post Type
     *
     * @since   1.0.0
     */
    public function register_post_type() {
        
        // Register Custom Post Type
        register_post_type( $this->post_type_name, array(
            'labels'              => array(
                'name'               => __( 'Affiliate Links', 'wp-affiliate-linker' ),
                'singular_name'      => __( 'Affiliate Link', 'wp-affiliate-linker' ),
                'add_new'            => __( 'Add New', 'wp-affiliate-linker' ),
                'add_new_item'       => __( 'Add New Affiliate Link', 'wp-affiliate-linker' ),
                'edit_item'          => __( 'Edit Affiliate Link', 'wp-affiliate-linker' ),
                'new_item'           => __( 'New Affiliate Link', 'wp-affiliate-linker' ),
                'view_item'          => __( 'View Affiliate Links', 'wp-affiliate-linker' ),
                'search_items'       => __( 'Search Affiliate Links', 'wp-affiliate-linker' ),
                'not_found'          => __( 'No affiliate links found.', 'wp-affiliate-linker' ),
                'not_found_in_trash' => __( 'No affiliate links found in trash.', 'wp-affiliate-linker' ),
                'parent_item_colon'  => '',
                'menu_name'          => __( 'Affiliate Linker', 'wp-affiliate-linker' ),
            ),
            'public'              => true,
            'exclude_from_search' => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_admin_bar'   => true,
            'rewrite'             => false,
            'query_var'           => false,
            'menu_position'       => 100,
            'menu_icon'           => WP_Affiliate_Linker::get_instance()->plugin->url . '/assets/images/icon-20x20.png',
            'supports'            => array( 'title' ),
            'rewrite'             => array(
                'slug'      => WP_Affiliate_Linker_Settings::get_instance()->get_setting( 'cloaking', 'slug' ),
                'with_front'=> true,
                'feeds'     => false,
                'pages'     => false,
            ),
        ) );

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