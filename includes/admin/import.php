<?php
/**
 * Import class
 * 
 * @package   WP_Affiliate_Linker
 * @author    WP Affiliate Linker
 * @version   1.0.0
 * @copyright WP Affiliate Linker
 */
class WP_Affiliate_Linker_Import {

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

        // Importers
        add_action( 'wp_affiliate_linker_import', array( $this, 'import' ), 10, 2 );
        add_filter( 'wp_affiliate_linker_import_easy_affiliate_links', array( $this, 'import_easy_affiliate_links' ) );
        add_filter( 'wp_affiliate_linker_import_pretty_link', array( $this, 'import_pretty_link' ) );
        add_filter( 'wp_affiliate_linker_import_thirsty_affiliates', array( $this, 'import_thirsty_affiliates' ) );
        
    }

    /**
     * Helper method to retrieve an array of import sources that this plugin
     * can import link data from.
     *
     * These will typically be other WordPress Plugins that have data stored
     * in this WordPress installation.
     *
     * @since   1.0.0
     *
     * @return  array   Import Sources
     */
    public function get_import_sources() {

        // Determine which SEO Plugins the user might be able to import data from
        $import_sources = array();

        // Easy Affiliate Links
        $easy_affiliate_links = get_option( 'eafl_settings' );
        if ( is_array( $easy_affiliate_links ) && ! empty( $easy_affiliate_links ) ) {
            $import_sources['easy_affiliate_links'] = array(
                'name'          => 'easy_affiliate_links',
                'label'         => __( 'Easy Affiliate Links', 'wp-affiliate-linker' ),
                'documentation' => 'https://wpaffiliatelinker.com/documentation/import-easy-affiliate-links/',
            );
        }

        // Pretty Link
        $pretty_link = get_option( 'prli_options' );
        if ( is_array( $pretty_link ) && ! empty( $pretty_link ) ) {
            $import_sources['pretty_link'] = array(
                'name'          => 'pretty_link',
                'label'         => __( 'Pretty Link', 'wp-affiliate-linker' ),
                'documentation' => 'https://wpaffiliatelinker.com/documentation/import-pretty-link/',
            );
        }
        
        // ThirstyAffiliates
        $thirsty_affiliates = get_option( 'thirstyOptions' );
        if ( is_array( $thirsty_affiliates ) && ! empty( $thirsty_affiliates ) ) {
            $import_sources['thirsty_affiliates'] = array(
                'name'          => 'thirsty_affiliates',
                'label'         => __( 'Thirsty Affiliates', 'wp-affiliate-linker' ),
                'documentation' => 'https://wpaffiliatelinker.com/documentation/import-thirstyaffiliates/',
            );
        }

        // Allow devs to filter import sources
        $import_sources = apply_filters( 'wp_affiliate_linker_import_get_import_sources', $import_sources );

        // Return
        return $import_sources;
        
    }


    /**
     * Import data created by this Plugin's export functionality
     *
     * @since   1.0.0
     *
     * @param   bool    $success    Success
     * @param   array   $data       Array
     * @return  mixed               WP_Error | bool
     */
    public function import( $success, $data ) {

        // Check data is an array
        if ( ! is_array( $data ) ) {
            return new WP_Error( 'wp_affiliate_linker_import_error', __( 'Supplied file is not a valid JSON settings file, or has become corrupt.', 'wp-affiliate-linker' ) );
        }

        // Get settings instance
        $settings_instance = WP_Affiliate_Linker_Settings::get_instance();

        /**
         * 1. Plugin Settings
         */
        if ( isset( $data['cloaking'] ) ) {
            $settings_instance->update_settings( 'cloaking', $data['cloaking'] );
        }
        if ( isset( $data['link'] ) ) {
            $settings_instance->update_settings( 'link', $data['link'] );
        }
        
        /**
         * 2. Link Categories
         */
        if ( isset( $data['_terms'] ) && count( $data['_terms'] ) > 0 ) {
            $groups_assoc = array();
            $taxonomy_name = WP_Affiliate_Linker_Taxonomy::get_instance()->taxonomy_name;
      
            foreach ( $data['_terms'] as $key => $group ) {
                // Assign the group to our associative array for later use
                $groups_assoc[ $group['term_id'] ] = $group;

                // Create a new Link Category, if one with the same name doesn't exist
                $term = get_term_by( 'name', $group['name'], $taxonomy_name );
                if ( $term !== false ) {
                    // Term already exists; make a note of the Term ID
                    $groups_assoc[ $group['term_id'] ]['term_id'] = $term->term_id;
                    continue;
                }

                // Define wp_insert_term() arguments
                $term_args = array(
                    'description' => $group['description'],
                );

                // Allow devs / addons to filter wp_insert_term() arguments now
                $term_args = apply_filters( 'wp_affiliate_linker_import_import_term_arguments', $term_args, $group );

                // Insert term
                $term = wp_insert_term( $group['name'], $taxonomy_name, $term_args );

                // Bail if something went wrong
                if ( is_wp_error( $term ) ) {
                    return $term;
                }

                // Allow devs / addons to perform any other import tasks related to this Term now
                $result = apply_filters( 'wp_affiliate_linker_import_import_term', true, $term, $group );

                // Bail if something went wrong
                if ( is_wp_error( $result ) ) {
                    return $result;
                }

                // Make a note of the Term ID against the Group
                $groups_assoc[ $group['term_id'] ]['term_id'] = $term['term_id'];
            }
        }

        /**
         * 3. Links
         */
        if ( isset( $data['_posts'] ) && count( $data['_posts'] ) > 0 ) {
            $post_type_name = WP_Affiliate_Linker_PostType::get_instance()->post_type_name;

            // Iterate through links, importing each one
            foreach ( $data['_posts'] as $key => $wal_link ) {
                // Create Link, if it doesn't already exist
                $results = WP_Affiliate_Linker_Link::get_instance()->get_by_url( $wal_link['link']['url'] );

                // If results exist, skip
                if ( $results !== false ) {
                    continue;
                }

                // Define wp_insert_post() arguments
                $post_args = array(
                    'post_type'     => $post_type_name,
                    'post_status'   => $wal_link['post_status'],
                    'post_title'    => $wal_link['post_title'],
                    'post_name'     => $wal_link['post_name'],
                    'post_excerpt'  => $wal_link['post_excerpt'],
                    'post_author'   => $wal_link['post_author'],
                    'post_date'     => $wal_link['post_date'],
                    'post_date_gmt' => $wal_link['post_date_gmt'],
                    'post_modified' => $wal_link['post_modified'],
                    'post_modified_gmt' => $wal_link['post_modified_gmt'],
                );

                // Allow devs / addons to filter wp_insert_term() arguments now
                $post_args = apply_filters( 'wp_affiliate_linker_import_import_post_arguments', $post_args, $group );

                // Create Link
                $link_id = wp_insert_post( $post_args );

                // Bail if something went wrong
                if ( is_wp_error( $link_id ) ) {
                    return $link_id;
                }

                // Save Link Settings
                $link_settings = array(
                    'url'       => ( isset( $wal_link['link']['url'] ) ? $wal_link['link']['url'] : '' ),
                    'redirect'  => ( isset( $wal_link['link']['redirect'] ) ? $wal_link['link']['redirect'] : '' ),
                    'nofollow'  => ( isset( $wal_link['link']['nofollow'] ) ? $wal_link['link']['nofollow'] : '' ),
                    'target'    => ( isset( $wal_link['link']['target'] ) ? $wal_link['link']['target'] : '' ),
                );
                
                // Save settings
                $settings_instance->update_post_settings( $link_id, 'link', $link_settings );

                // Allow devs / addons to perform any other import tasks related to this Post now
                $result = apply_filters( 'wp_affiliate_linker_import_import_post', true, $link_id, $wal_link );

                // Bail if something went wrong
                if ( is_wp_error( $result ) ) {
                    return $result;
                }

                // Get Link Terms
                if ( ! isset( $wal_link['term_ids'] ) ) {
                    continue;
                }
                if ( count( $wal_link['term_ids'] ) == 0 ) {
                    continue;
                }

                // Build array of Imported Link Term IDs to apply to this link
                if ( ! isset( $wal_link['term_ids'] ) ) {
                    continue;
                }
                if ( empty( $wal_link['term_ids'] ) ) {
                    continue;
                }

                // Build array of new Term IDs based on the imported Term ID mappings
                $term_ids = array();
                foreach ( $wal_link['term_ids'] as $wal_link_term_id ) {
                    // Skip if we don't have an equivalent Link Category
                    if ( ! isset( $groups_assoc[ $wal_link_term_id ] ) ) {
                        continue;
                    }

                    $term_ids[] = (int) $groups_assoc[ $wal_link_term_id ]['term_id'];
                }

                // Skip if no Link Terms were found
                if ( empty( $term_ids ) ) {
                    continue;
                }

                // Set Terms
                $result = wp_set_post_terms( $link_id, $term_ids, $taxonomy_name );
                
                // Bail if something went wrong
                if ( is_wp_error( $result ) ) {
                    return $result;
                }
            }
        }

        // Allow devs / addons to import additional data from WP Affiliate Linker now
        $result = apply_filters( 'wp_affiliate_linker_import_import', $success, $data );
        if ( is_wp_error( $result ) ) {
            return $result;
        }

        // Done
        return true;
        
    }

    /**
     * Import data from Easy Affiliate Links
     *
     * @since   1.0.0
     *
     * @param   bool    Success
     * @return  mixed   WP_Error | bool
     */
    public function import_easy_affiliate_links( $success = false ) {

        // Get instances
        $settings_instance = WP_Affiliate_Linker_Settings::get_instance();

        /**
         * 1. Plugin Settings
         */
        $import = get_option( 'eafl_settings' );
        $cloaking = $settings_instance->get_settings( 'cloaking' );
        $link = $settings_instance->get_settings( 'link' );

        // Cloaking Slug
        if ( isset( $import['shortlink_slug'] ) ) {
            $cloaking['slug'] = $import['shortlink_slug'];
        }

        // Redirect
        if ( isset( $import['default_redirect_type'] ) ) {
            $link['redirect'] = $import['default_redirect_type'];
        }

        // Nofollow
        if ( isset( $import['default_nofollow'] ) ) {
            $link['nofollow'] = ( $import['default_nofollow'] == 'follow' ? 0 : 1 );
        }

        // Target
        if ( isset( $import['default_target'] ) ) {
            $link['target'] = $import['default_target'];
        }

        // Save Settings
        $settings_instance->update_settings( 'cloaking', $cloaking );
        $settings_instance->update_settings( 'link', $link );

        // Allow devs / addons to import additional Plugin Settings
        $result = apply_filters( 'wp_affiliate_linker_import_import_easy_affiliate_links_settings', true, $import );
        if ( is_wp_error( $result ) ) {
            return $result;
        }

        /**
         * 2. Link Categories
         */
        $taxonomy_name = WP_Affiliate_Linker_Taxonomy::get_instance()->taxonomy_name;
        $groups = get_terms( array(
            'taxonomy'  => 'eafl_category',
            'hide_empty'=> false,
        ) );
        $groups_assoc = array();
        if ( count( $groups ) > 0 ) {
            foreach ( $groups as $key => $group ) {
                // Assign the group to our associative array for later use
                $groups_assoc[ $group->term_id ] = $group;

                // Create a new Link Category, if one with the same name doesn't exist
                $term = get_term_by( 'name', $group->name, $taxonomy_name );
                if ( $term !== false ) {
                    // Term already exists; make a note of the Term ID
                    $groups_assoc[ $group->term_id ]->term_id = $term->term_id;
                    continue;
                }

                // Define wp_insert_term() arguments
                $term_args = array(
                    'description' => $group->description,
                );

                // Allow devs / addons to filter wp_insert_term() arguments now
                $term_args = apply_filters( 'wp_affiliate_linker_import_import_easy_affiliate_links_term_arguments', $term_args, $group );

                // If here, Term does not exist - create now
                $term = wp_insert_term( $group->name, $taxonomy_name, $term_args );

                // Bail if something went wrong
                if ( is_wp_error( $term ) ) {
                    return $term;
                }

                // Make a note of the Term ID against the Group
                $groups_assoc[ $group->term_id ]->term_id = $term['term_id'];
            }
        }

        /**
         * 3. Links
         */
        $post_type_name = WP_Affiliate_Linker_PostType::get_instance()->post_type_name;
        $links = new WP_Query( array(
            'post_type'     => 'easy_affiliate_link',
            'post_status'   => 'any',
            'posts_per_page'=> -1,
        ) );
        
        // Finished if no links found
        if ( count( $links->posts ) == 0 ) {
            return true;
        }

        // Iterate through links, importing each one
        foreach ( $links->posts as $key => $eal_link ) {
            // Create Link, if it doesn't already exist
            $results = WP_Affiliate_Linker_Link::get_instance()->get_by_url( get_post_meta( $eal_link->ID, 'eafl_url', true ) );

            // If results exist, skip
            if ( $results !== false ) {
                continue;
            }

            // Define wp_insert_post() arguments
            $post_args = array(
                'post_type'     => $post_type_name,
                'post_status'   => $eal_link->post_status,
                'post_title'    => $eal_link->post_title,
                'post_name'     => $eal_link->post_name,
                'post_excerpt'  => get_post_meta( $eal_link->ID, 'eafl_description', true ),
                'post_author'   => $eal_link->post_author,
                'post_date'     => $eal_link->post_date,
                'post_date_gmt' => $eal_link->post_date_gmt,
                'post_modified' => $eal_link->post_modified,
                'post_modified_gmt' => $eal_link->post_modified_gmt,
            );

            // Allow devs / addons to filter wp_insert_term() arguments now
            $post_args = apply_filters( 'wp_affiliate_linker_import_import_easy_affiliate_links_post_arguments', $post_args, $group );

            // Create Link
            $link_id = wp_insert_post( $post_args );

            // Bail if something went wrong
            if ( is_wp_error( $link_id ) ) {
                return $link_id;
            }

            // Save Link Settings
            $link_settings = array(
                'url'       => get_post_meta( $eal_link->ID, 'eafl_url', true ),
                'redirect'  => get_post_meta( $eal_link->ID, 'eafl_redirect_type', true ),
                'nofollow'  => get_post_meta( $eal_link->ID, 'eafl_nofollow', true ),
                'target'    => get_post_meta( $eal_link->ID, 'eafl_target', true ),
            );

            // Adjust Link Settings depending on whether defaults are to be used
            if ( $link_settings['redirect'] == 'default' ) {
                unset( $link_settings['redirect'] );
            }
            if ( $link_settings['nofollow'] == 'nofollow' ) {
                $link_settings['nofollow'] = 1;
            }
            if ( $link_settings['nofollow'] == 'default' ) {
                unset( $link_settings['nofollow'] );
            }
            if ( $link_settings['target'] == 'default' ) {
                unset( $link_settings['target'] );
            }
            
            // Save settings
            $settings_instance->update_post_settings( $link_id, 'link', $link_settings );

            // Allow devs / addons to perform any other import tasks related to this Post now
            $result = apply_filters( 'wp_affiliate_linker_import_easy_affiliate_links_post', true, $link_id, $eal_link );

            // Bail if something went wrong
            if ( is_wp_error( $result ) ) {
                return $result;
            }

            // Get EAL Link Terms
            $eal_link_terms = wp_get_post_terms( $eal_link->ID, 'eafl_category' );

            // Bail if something went wrong
            if ( is_wp_error( $eal_link_terms ) ) {
                return $eal_link_terms;
            }
            if ( empty( $eal_link_terms ) ) {
                continue;
            }

            // Build array of Link Term IDs from EAL
            $term_ids = array();
            foreach ( $eal_link_terms as $eal_link_term ) {
                // Skip if we don't have an equivalent Link Category
                if ( ! isset( $groups_assoc[ $eal_link_term->term_id ] ) ) {
                    continue;
                }

                $term_ids[] = (int) $groups_assoc[ $eal_link_term->term_id ]->term_id;
            }

            // Skip if no Link Terms were found
            if ( empty( $term_ids ) ) {
                continue;
            }

            // Set Terms
            $result = wp_set_post_terms( $link_id, $term_ids, $taxonomy_name );
            
            // Bail if something went wrong
            if ( is_wp_error( $result ) ) {
                return $result;
            }
        }

        // Done
        return true;

    }

    /**
     * Import data from Pretty Link
     *
     * @since   1.0.0
     *
     * @param   bool    Success
     * @return  mixed   WP_Error | bool
     */
    public function import_pretty_link( $success = false ) {

        global $wpdb;

        // Get instances
        $settings_instance = WP_Affiliate_Linker_Settings::get_instance();

        /**
         * 1. Plugin Settings
         * - Note: No cloaking settings (i.e. slug) exist in Pretty Link
         */
        $import = get_option( 'prli_options' );
        $link = $settings_instance->get_settings( 'link' );

        // Redirect
        if ( isset( $import['link_redirect_type'] ) ) {
            $link['redirect'] = $import['link_redirect_type'];
        }

        // Nofollow
        if ( isset( $import['link_nofollow'] ) ) {
            $link['nofollow'] = (int) $import['link_nofollow'];
        }

        // Save Settings
        $settings_instance->update_settings( 'link', $link );

        // Allow devs / addons to import additional Plugin Settings
        $result = apply_filters( 'wp_affiliate_linker_import_import_pretty_link_settings', true, $import );
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        
        /**
         * 2. Link Categories
         */
        $taxonomy_name = WP_Affiliate_Linker_Taxonomy::get_instance()->taxonomy_name;
        $groups = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "prli_groups" );
        $groups_assoc = array();
        if ( count( $groups ) > 0 ) {
            foreach ( $groups as $key => $group ) {
                // Assign the group to our associative array for later use
                $groups_assoc[ $group->id ] = $group;

                // Create a new Link Category, if one with the same name doesn't exist
                $term = get_term_by( 'name', $group->name, $taxonomy_name );
                if ( $term !== false ) {
                    // Term already exists; make a note of the Term ID
                    $groups_assoc[ $group->id ]->term_id = $term->term_id;
                    continue;
                }

                // If here, Term does not exist - create now
                // Define wp_insert_term() arguments
                $term_args = array(
                    'description' => $group->description,
                );

                // Allow devs / addons to filter wp_insert_term() arguments now
                $term_args = apply_filters( 'wp_affiliate_linker_import_import_pretty_link_term_arguments', $term_args, $group );

                // Insert term
                $term = wp_insert_term( $group->name, $taxonomy_name, $term_args );

                // Bail if something went wrong
                if ( is_wp_error( $term ) ) {
                    return $term;
                }

                // Make a note of the Term ID against the Group
                $groups_assoc[ $group->id ]->term_id = $term['term_id'];
            }
        }

        /**
         * 3. Links
         */
        $post_type_name = WP_Affiliate_Linker_PostType::get_instance()->post_type_name;
        $links = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "prli_links" );

        // Finished if no links found
        if ( count( $links ) == 0 ) {
            return true;
        }

        // Iterate through links, importing each one
        foreach ( $links as $key => $pretty_link ) {
            // Create Link, if it doesn't already exist
            $results = WP_Affiliate_Linker_Link::get_instance()->get_by_url( $pretty_link->url );

            // If results exist, skip
            if ( $results !== false ) {
                continue;
            }

            // Define wp_insert_post() arguments
            $post_args = array(
                'post_type'     => $post_type_name,
                'post_status'   => 'publish',
                'post_title'    => $pretty_link->name,
                'post_excerpt'  => $pretty_link->description,
                'post_author'   => get_current_user_id(),
            );

            // Allow devs / addons to filter wp_insert_term() arguments now
            $post_args = apply_filters( 'wp_affiliate_linker_import_import_pretty_link_post_arguments', $post_args, $group );

            // Create Link
            $link_id = wp_insert_post( $post_args );

            // Bail if something went wrong
            if ( is_wp_error( $link_id ) ) {
                return $link_id;
            }

            // Save Link Settings
            $link_settings = array(
                'url'       => $pretty_link->url,
                'redirect'  => $pretty_link->redirect_type,
                'nofollow'  => $pretty_link->nofollow,
                // Target doesn't exist in Pretty Link
            );
            
            // Save settings
            $settings_instance->update_post_settings( $link_id, 'link', $link_settings );

            // Allow devs / addons to perform any other import tasks related to this Post now
            $result = apply_filters( 'wp_affiliate_linker_import_pretty_link_post', true, $link_id, $pretty_link );

            // Bail if something went wrong
            if ( is_wp_error( $result ) ) {
                return $result;
            }

            // Assign the Link to a Link Category, if required
            if ( $pretty_link->group_id == 0 ) {
                continue;
            }
            if ( ! isset( $groups_assoc[ $pretty_link->group_id ] ) ) {
                continue;
            }
            
            $result = wp_set_post_terms( $link_id, (int) $groups_assoc[ $pretty_link->group_id ]->term_id, $taxonomy_name );

            // Bail if something went wrong
            if ( is_wp_error( $result ) ) {
                return $result;
            }
        }

        // Done
        return true;

    }

    /**
     * Import data from Thirsty Affiliates
     *
     * @since   1.0.0
     *
     * @param   bool    Success
     * @return  mixed   WP_Error | bool
     */
    public function import_thirsty_affiliates( $success = false ) {

        // Get instances
        $settings_instance = WP_Affiliate_Linker_Settings::get_instance();

        /**
         * 1. Plugin Settings
         */
        $import = get_option( 'thirstyOptions' );
        $cloaking = $settings_instance->get_settings( 'cloaking' );
        $link = $settings_instance->get_settings( 'link' );

        // Cloaking Slug
        if ( isset( $import['linkprefix'] ) ) {
            $cloaking['slug'] = $import['linkprefix'];
        }

        // Redirect
        if ( isset( $import['linkredirecttype'] ) ) {
            $link['redirect'] = $import['linkredirecttype'];
        }

        // Nofollow
        if ( isset( $import['nofollow'] ) ) {
            $link['nofollow'] = ( $import['nofollow'] == 'on' ? 1 : 0 );
        }

        // Target
        if ( isset( $import['newwindow'] ) ) {
            $link['target'] = ( $import['newwindow'] == 'on' ? '_blank' : '' );
        }

        // Save Settings
        $settings_instance->update_settings( 'cloaking', $cloaking );
        $settings_instance->update_settings( 'link', $link );

        // Allow devs / addons to import additional Plugin Settings
        $result = apply_filters( 'wp_affiliate_linker_import_import_thirsty_affiliates_settings', true, $import );
        if ( is_wp_error( $result ) ) {
            return $result;
        }

        /**
         * 2. Link Categories
         */
        $taxonomy_name = WP_Affiliate_Linker_Taxonomy::get_instance()->taxonomy_name;
        $groups = get_terms( array(
            'taxonomy'  => 'thirstylink-category',
            'hide_empty'=> false,
        ) );
        $groups_assoc = array();
        if ( count( $groups ) > 0 ) {
            foreach ( $groups as $key => $group ) {
                // Assign the group to our associative array for later use
                $groups_assoc[ $group->term_id ] = $group;

                // Create a new Link Category, if one with the same name doesn't exist
                $term = get_term_by( 'name', $group->name, $taxonomy_name );
                if ( $term !== false ) {
                    // Term already exists; make a note of the Term ID
                    $groups_assoc[ $group->term_id ]->term_id = $term->term_id;
                    continue;
                }

                // If here, Term does not exist - create now
                // Define wp_insert_term() arguments
                $term_args = array(
                    'description' => $group->description,
                );

                // Allow devs / addons to filter wp_insert_term() arguments now
                $term_args = apply_filters( 'wp_affiliate_linker_import_import_thirsty_affiliates_arguments', $term_args, $group );

                // Insert term
                $term = wp_insert_term( $group->name, $taxonomy_name, $term_args );

                // Bail if something went wrong
                if ( is_wp_error( $term ) ) {
                    return $term;
                }

                // Make a note of the Term ID against the Group
                $groups_assoc[ $group->term_id ]->term_id = $term['term_id'];
            }
        }

        /**
         * 3. Links
         */
        $post_type_name = WP_Affiliate_Linker_PostType::get_instance()->post_type_name;
        $links = new WP_Query( array(
            'post_type'     => 'thirstylink',
            'post_status'   => 'any',
            'posts_per_page'=> -1,
        ) );
        
        // Finished if no links found
        if ( count( $links->posts ) == 0 ) {
            return true;
        }

        // Iterate through links, importing each one
        foreach ( $links->posts as $key => $thirsty_link ) {
            // Fetch settings
            $thirsty_link_settings = get_post_meta( $thirsty_link->ID, 'thirstyData', true );
            if ( empty( $thirsty_link_settings ) ) {
                continue;
            }

            // Unserialize, as for some reason ThirstyAffiliates wrap serialized data so WP can't do this in get_post_meta()
            $thirsty_link_settings = unserialize( $thirsty_link_settings );
            
            // Create Link, if it doesn't already exist
            $results = WP_Affiliate_Linker_Link::get_instance()->get_by_url( $thirsty_link_settings['linkurl'] );

            // If results exist, skip
            if ( $results !== false ) {
                continue;
            }

            // Define wp_insert_post() arguments
            $post_args = array(
                'post_type'     => $post_type_name,
                'post_status'   => $thirsty_link->post_status,
                'post_title'    => $thirsty_link->post_title,
                'post_name'     => $thirsty_link->post_name,
                'post_excerpt'  => $thirsty_link->post_excerpt,
                'post_author'   => $thirsty_link->post_author,
                'post_date'     => $thirsty_link->post_date,
                'post_date_gmt' => $thirsty_link->post_date_gmt,
                'post_modified' => $thirsty_link->post_modified,
                'post_modified_gmt' => $thirsty_link->post_modified_gmt,
            );

            // Allow devs / addons to filter wp_insert_term() arguments now
            $post_args = apply_filters( 'wp_affiliate_linker_import_import_thirsty_affiliates_arguments', $post_args, $group );

            // Create Link
            $link_id = wp_insert_post( $post_args );

            // Bail if something went wrong
            if ( is_wp_error( $link_id ) ) {
                return $link_id;
            }

            // Save Link Settings
            $link_settings = array(
                'url'       => $thirsty_link_settings['linkurl'],
                'redirect'  => $thirsty_link_settings['linkredirecttype'],
                'nofollow'  => ( $thirsty_link_settings['nofollow'] == 'on' ? 1 : 0 ),
                'target'    => ( $thirsty_link_settings['newwindow'] == 'on' ? '_blank' : '' ),
            );
            
            // Save settings
            $settings_instance->update_post_settings( $link_id, 'link', $link_settings );

            // Allow devs / addons to perform any other import tasks related to this Post now
            $result = apply_filters( 'wp_affiliate_linker_import_import_thirsty_affiliates_post', true, $link_id, $thirsty_link, $thirsty_link_settings );

            // Bail if something went wrong
            if ( is_wp_error( $result ) ) {
                return $result;
            }

            // Get ThirstyAffiliates Link Terms
            $thirsty_affiliates_link_terms = wp_get_post_terms( $thirsty_link->ID, 'thirstylink-category' );

            // Bail if something went wrong
            if ( is_wp_error( $thirsty_affiliates_link_terms ) ) {
                return $thirsty_affiliates_link_terms;
            }
            if ( empty( $thirsty_affiliates_link_terms ) ) {
                continue;
            }

            // Build array of Link Term IDs from ThirstyAffiliates
            $term_ids = array();
            foreach ( $thirsty_affiliates_link_terms as $thirsty_affiliates_link_term ) {
                // Skip if we don't have an equivalent Link Category
                if ( ! isset( $groups_assoc[ $thirsty_affiliates_link_term->term_id ] ) ) {
                    continue;
                }

                $term_ids[] = (int) $groups_assoc[ $thirsty_affiliates_link_term->term_id ]->term_id;
            }

            // Skip if no Link Terms were found
            if ( empty( $term_ids ) ) {
                continue;
            }

            // Set Terms
            $result = wp_set_post_terms( $link_id, $term_ids, $taxonomy_name );
            
            // Bail if something went wrong
            if ( is_wp_error( $result ) ) {
                return $result;
            }
        }

        // Done
        return true;

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