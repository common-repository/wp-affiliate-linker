<?php
/**
 * Link Post class
 * 
 * @package   WP_Affiliate_Linker
 * @author    WP Affiliate Linker
 * @version   1.0.0
 * @copyright WP Affiliate Linker
 */
class WP_Affiliate_Linker_Link {

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
     * Holds the post type name
     *
     * @since   1.0.0
     *
     * @var string
     */
    private $post_type_name = '';

    /**
     * Constructor
     *
     * @since   1.0.0
     */
    public function __construct() {

        // Get the post type name from the Post Type class
        $this->post_type_name = WP_Affiliate_Linker_PostType::get_instance()->post_type_name;

        // Admin Columns
        add_filter( 'manage_edit-' . $this->post_type_name . '_columns', array( $this, 'register_admin_columns' ) );
        add_action( 'manage_' . $this->post_type_name . '_posts_custom_column', array( $this, 'output_admin_columns_data' ), 10, 2 );

        // Admin Search
        add_filter( 'posts_join', array( $this, 'search_meta_data_join' ) );
        add_filter( 'posts_where', array( $this, 'search_meta_data_where' ) );

        // Post Type Metabox
        add_filter( 'enter_title_here', array( $this, 'enter_title_here' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post', array( $this, 'save_settings' ), 10, 1 );

    }
    
    /**
     * Adds columns to the Custom Post Type within the WordPress Administration List Table
     * 
     * @since   1.0.0
     *
     * @param   array   $columns    Columns
     * @return  array               New Columns
     */
    public function register_admin_columns( $columns ) {

        // The columns we want to add
        $plugin_columns = array();
        $plugin_columns['cloaked_url'] = __( 'Cloaked URL', 'wp-affiliate-linker' );
        $plugin_columns['url']         = __( 'Destination URL', 'wp-affiliate-linker' );
        $plugin_columns['redirect']    = __( 'Redirect Type', 'wp-affiliate-linker' );
        $plugin_columns['nofollow']    = __( 'Use nofollow?', 'wp-affiliate-linker' );
        $plugin_columns['target']      = __( 'Open in New Window?', 'wp-affiliate-linker' );

        // Allow devs / addons to filter columns
        $plugin_columns = apply_filters( 'wp_affiliate_linker_link_register_admin_columns', $plugin_columns );

        // Inject the columns after the checkbox and title, but before any other columns that are registered
        // (e.g. Link Categories / Date)
        $start_columns = array_slice( $columns, 0, 2 );
        $end_columns = array_slice( $columns, 2 );

        // Merge all three arrays to build our final columns
        $columns = array_merge( $start_columns, $plugin_columns, $end_columns );

        // Return
        return $columns;

    }

    /**
     * Outputs data to the Custom Post Type columns registered in register_admin_columns()
     * 
     * @since   1.0.0
     *
     * @param   string  $column_name    Column Name
     * @param   int     $post_id        Post ID
     */
    public function output_admin_columns_data( $column_name, $post_id ) {

        switch ( $column_name ) {
            /**
             * Cloaked URL
             */
            case 'cloaked_url':
                ?>
                <code><?php echo get_permalink( $post_id ); ?></code>
                <br />
                <a href="#" class="clipboard-js" data-clipboard-text="<?php echo get_permalink( $post_id ); ?>"><?php _e( 'Copy Text Link', 'wp-affiliate-linker' ); ?></a>
                <br />
                <a href="#" class="clipboard-js" data-clipboard-text="<?php echo get_permalink( $post_id ); ?>"><?php _e( 'Copy HTML Link', 'wp-affiliate-linker' ); ?></a>
                <?php
                break;

            /**
             * URL
             */
            case 'url':
                $url = $this->get_post_setting( $post_id, 'link', 'url' );
                ?>
                <code><?php echo $url; ?></code>
                <?php
                break;

            /**
             * Redirect Type
             */
            case 'redirect':
                $redirect = $this->get_post_setting( $post_id, 'link', 'redirect' );
                $redirect_types = WP_Affiliate_Linker_Common::get_instance()->get_redirect_types();
                echo ( isset( $redirect_types[ $redirect ] ) ? $redirect_types[ $redirect ]['label'] : $redirect );
                break;

            /**
             * Use nofollow?
             */
            case 'nofollow':
                $nofollow = $this->get_post_setting( $post_id, 'link', 'nofollow' );
                echo ( $nofollow ? __( 'Yes', 'wp-affiliate-linker' ) : __( 'No', 'wp-affiliate-linker' ) );
                break;

            /**
             * Open in New Window?
             */
            case 'target':
                $target = $this->get_post_setting( $post_id, 'link', 'target' );
                echo ( ( $target == '_blank' ) ? __( 'Yes', 'wp-affiliate-linker' ) : __( 'No', 'wp-affiliate-linker' ) );
                break;

            /**
             * Default
             */
            default:
                // Allow devs / addons to output their data now
                do_action( 'wp_affiliate_linker_link_output_admin_columns_data', $column_name, $post_id );
                break;
        }

    }

    /**
     * Adds a join to the WordPress meta table for Custom Post Type searches
     *
     * @since   1.0.0
     *
     * @param   string  $join   SQL JOIN Statement
     * @return  string          SQL JOIN Statement
     */
    public function search_meta_data_join( $join ) {

        global $wp_query, $wpdb;
        
        // Bail if not a search query
        if ( empty( $wp_query->query_vars['s'] ) ) {
            return $join;
        }

        // Bail if not searching our Custom Post Type
        if ( $wp_query->query_vars['post_type'] != $this->post_type_name ) {
            return $join;
        }
        
        // Append JOIN and return
        $join .= " LEFT JOIN $wpdb->postmeta ON $wpdb->posts.ID = $wpdb->postmeta.post_id ";

        // Allow devs / addons to filter the JOIN now
        $join = apply_filters( 'wp_affiliate_linker_link_search_meta_data_join', $join );

        // Return
        return $join;

    }
    
    /**
     * Adds a where clause to the WordPress meta table for Custom Post Type searches
     *
     * @since   1.0.0
     *
     * @param   string  $where      SQL WHERE
     * @param   return              SQL WHERE
     */
    public function search_meta_data_where( $where ) {

        global $wp_query, $wpdb;

        // Bail if we're not performing a search
        if ( empty( $wp_query->query_vars['s'] ) ) {
            return $where;
        }

        // Bail if we're not on this Plugin's Post Type
        if ( $wp_query->query_vars['post_type'] != $this->post_type_name ) {
            return $where;
        }
        
        // Extract the details of the query
        $start_of_query = substr( $where, 0, 7 );
        if ( $start_of_query != ' AND ((' ) {
            return $where;
        }
        $rest_of_query = substr( $where, 7 );

        // Define the search query
        $search_query = "(" . $wpdb->postmeta . ".meta_value LIKE '%" . $wp_query->query_vars['s'] . "%')";
        
        // Allow devs / addons to filter the search part of the WHERE clause now
        $search_query = apply_filters( 'wp_affiliate_linker_link_search_meta_data_where', $search_query );

        // Build the final WHERE clause, with grouping to avoid duplicate Post output
        $where = $start_of_query . $search_query . " OR " . $rest_of_query . " GROUP BY " . $wpdb->posts . ".id";

        // Return        
        return $where;

    }

    /**
     * Changes the placeholder text for the Title field when editing our Post Type
     *
     * @since   1.0.0
     *
     * @param   string  $title  Title
     * @return  string          Title
     */
    public function enter_title_here( $title ) {

        // Check we are on our CPT
        $screen = get_current_screen();
        if ( $screen->post_type !== $this->post_type_name ) {
            return $title;
        }

        // Change title
        return __( 'Keywords / Title', 'wp-affiliate-linker' );

    }

    /**
     * Registers meta boxes on the Affiliate Links CPT
     *
     * @since   1.0.0
     */
    public function add_meta_boxes() {

        // Remove all metaboxes
        $this->remove_all_meta_boxes();

        // Add Meta Box for Affiliate Links
        add_meta_box( 
            'wp-affiliate-linker', 
            __( 'WP Affiliate Linker', 'wp-affiliate-linker' ), 
            array( $this, 'output_meta_box' ), 
            'wp-affiliate-linker',
            'normal'
        );

    }

    /**
     * Removes metaboxes added by most other Plugins and WordPress, so we can control
     * the UI better.
     *
     * @since   1.0.0
     *
     * @global  array   $wp_meta_boxes  Array of registered metaboxes.
     */
    private function remove_all_meta_boxes() {

        global $wp_meta_boxes;

        // Get permitted meta boxes
        $permitted_meta_boxes = $this->permitted_meta_boxes();

        // Bail if no meta boxes for the Affiliate Links CPT exist
        if ( ! isset( $wp_meta_boxes[ $this->post_type_name ] ) ) {
            return;
        }

        // Iterate through all registered meta boxes, removing those that aren't permitted
        foreach ( $wp_meta_boxes[ $this->post_type_name ] as $position => $contexts ) {
            foreach ( $contexts as $context => $meta_boxes ) {
                foreach ( $meta_boxes as $meta_box_id => $meta_box ) {
                    // If this meta box isn't in the array of permitted meta boxes, remove it now
                    if ( ! in_array( $meta_box_id, $permitted_meta_boxes ) ) {
                        unset( $wp_meta_boxes[ $this->post_type_name ][ $position ][ $context ][ $meta_box_id ] );
                    }
                }
            }
        }

    }


    /**
     * Defines the meta boxes which are permitted for display on the Affiliate Links screen.
     *
     * @since   1.0.0
     *
     * @return  array   Permitted Meta Boxes
     */
    private function permitted_meta_boxes() {

        // Define permitted meta boxes
        $permitted_meta_boxes = array(
            'submitdiv',
            'slugdiv',
            'wp-affiliate-linker-categoriesdiv',
        );

        // Filter permitted meta boxes
        $permitted_meta_boxes = apply_filters( 'wp_affiliate_linker_link_permitted_meta_boxes', $permitted_meta_boxes );

        // Return
        return $permitted_meta_boxes;

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
        require_once( $this->base->plugin->folder . 'views/admin/link.php' ); 

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
        $tabs = array(
            'link' => array(
                'name'  => 'link',
                'label' => __( 'Link Settings', 'wp-affiliate-linker' ),
            ),
        );

        // Allow addons to define tabs on existing screens
        $tabs = apply_filters( 'wp_affiliate_linker_link_get_screen_tabs', $tabs );

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
     * @param   int       $post_id  Post ID (optional)
     * @return  mixed               Value
     */
    private function get_post_setting( $post_id, $screen, $key ) {

        return WP_Affiliate_Linker_Settings::get_instance()->get_post_setting( $post_id, $screen, $key );

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

        // Check if any data was submitted for this section
        if ( ! isset( $_POST ) || empty( $_POST ) || ! isset( $_POST[ $this->base->plugin->name ] ) ) {
            return;
        }
        
        // Run security checks
        // Missing nonce 
        if ( ! isset( $_POST[ $this->base->plugin->name . '_nonce' ] ) ) { 
            return false;
        }

        // Invalid nonce
        if ( ! wp_verify_nonce( $_POST[ $this->base->plugin->name . '_nonce' ], 'wp-affiliate-linker_link' ) ) {
            return false;
        }

        // Store the Plugin's POSTed data in an array
        $post_settings = $_POST[ $this->base->plugin->name ];

        // Save Settings: Link
        WP_Affiliate_Linker_Settings::get_instance()->update_post_settings( $post_id, 'link', $post_settings['link'] );

        // Request Review
        $this->base->licensing->request_review();
        
        // Allow devs / addons to save settings now
        do_action( 'wp_affiliate_linker_link_save_settings', $post_id, $post_settings );

    }

    /**
     * Returns all Link Posts
     *
     * @since   1.0.0
     *
     * @return  mixed               false | array
     */
    public function get_all() {

        // Search for posts
        $posts = new WP_Query( array(
            'post_type'         => $this->post_type_name,
            'post_status'       => 'any',
            'posts_per_page'    => -1,
            'orderby'           => 'title',
            'order'             => 'ASC',
        ) );

        // Bail if no posts found
        if ( count( $posts->posts ) == 0 ) {
            return false;
        }

        // Build an array of data
        $results = array();
        foreach ( $posts->posts as $post ) {
            $result = array(
                'ID'            => $post->ID,
                'post_status'   => $post->post_status,
                'post_title'    => $post->post_title,
                'post_name'     => $post->post_name,
                'post_excerpt'  => $post->post_excerpt,
                'post_author'   => $post->post_author,
                'post_date'     => $post->post_date,
                'post_date_gmt' => $post->post_date_gmt,
                'post_modified' => $post->post_modified,
                'post_modified_gmt' => $post->post_modified_gmt,
                'cloaked_url'   => get_permalink( $post->ID ), 
                'link'          => array(
                    'url'       => $this->get_post_setting( $post->ID, 'link', 'url' ),
                    'redirect'  => $this->get_post_setting( $post->ID, 'link', 'redirect' ),
                    'nofollow'  => $this->get_post_setting( $post->ID, 'link', 'nofollow' ),
                    'target'    => $this->get_post_setting( $post->ID, 'link', 'target' ),
                ),
                'term_ids'      => wp_get_post_terms( $post->ID, WP_Affiliate_Linker_Taxonomy::get_instance()->taxonomy_name, array(
                    'fields' => 'ids',
                ) ),
            );

            // Allow devs / addons to filter results
            $result = apply_filters( 'wp_affiliate_linker_link_get_all', $result, $post );

            // Add result to array
            $results[] = $result;
        }

        return $results;

    }

    /**
     * Returns Link Posts that match the given Title
     *
     * @since   1.0.0
     *
     * @param   string  $keywords   Keywords
     * @return  mixed               false | array
     */
    public function get_by_title( $keywords = '' ) {

        // Search for posts
        $posts = new WP_Query( array(
            'post_type'         => $this->post_type_name,
            'post_status'       => 'publish',
            'posts_per_page'    => 10,
            's'                 => $keywords,
            'orderby'           => 'title',
            'order'             => 'ASC',
        ) );

        // Bail if no posts found
        if ( count( $posts->posts ) == 0 ) {
            return false;
        }

        // Build an array of data
        $results = array();
        foreach ( $posts->posts as $post ) {
            $result = array(
                'ID'            => $post->ID,
                'post_title'    => $post->post_title,
                'cloaked_url'   => get_permalink( $post->ID ), 
                'link'          => array(
                    'url'       => $this->get_post_setting( $post->ID, 'link', 'url' ),
                    'redirect'  => $this->get_post_setting( $post->ID, 'link', 'redirect' ),
                    'nofollow'  => $this->get_post_setting( $post->ID, 'link', 'nofollow' ),
                    'target'    => $this->get_post_setting( $post->ID, 'link', 'target' ),
                ),
            );

            // Allow devs / addons to filter results
            $result = apply_filters( 'wp_affiliate_linker_link_get_by_title', $result, $post, $keywords );

            // Add result to array
            $results[] = $result;
        }

        return $results;

    }

    /**
     * Returns Link Posts that match the given URL
     *
     * @since   1.0.0
     *
     * @param   string  $url    URL
     * @return  mixed           false | array
     */
    public function get_by_url( $url ) {

        // Search for posts
        $posts = new WP_Query( array(
            'post_type'         => $this->post_type_name,
            'post_status'       => 'publish',
            'posts_per_page'    => 10,
            'meta_query'        => array(
                array(
                    'key'       => '_wp_affiliate_linker_link_url',
                    'value'     => $url,
                ),
            ),
            'orderby'           => 'title',
            'order'             => 'ASC',
        ) );

        // Bail if no posts found
        if ( count( $posts->posts ) == 0 ) {
            return false;
        }

        // Build an array of data
        $results = array();
        foreach ( $posts->posts as $post ) {
            $result = array(
                'ID'            => $post->ID,
                'post_title'    => $post->post_title,
                'cloaked_url'   => get_permalink( $post->ID ),
                'link'          => array(
                    'url'       => $this->get_post_setting( $post->ID, 'link', 'url' ),
                    'redirect'  => $this->get_post_setting( $post->ID, 'link', 'redirect' ),
                    'nofollow'  => $this->get_post_setting( $post->ID, 'link', 'nofollow' ),
                    'target'    => $this->get_post_setting( $post->ID, 'link', 'target' ),
                ),
            );

            // Allow devs / addons to filter results
            $result = apply_filters( 'wp_affiliate_linker_link_get_by_url', $result, $post, $url );

            // Add result to array
            $results[] = $result;
        }

        return $results;

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