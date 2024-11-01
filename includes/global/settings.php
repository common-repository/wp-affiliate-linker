<?php
/**
 * Settings class
 * 
 * @package   WP_Affiliate_Linker
 * @author    WP Affiliate Linker
 * @version   1.0.0
 * @copyright WP Affiliate Linker
 */
class WP_Affiliate_Linker_Settings {

    /**
     * Holds the class object.
     *
     * @since 1.0.0
     *
     * @var object
     */
    public static $instance;

    /**
     * The key prefix to use for settings
     *
     * @since   1.0.0
     *
     * @var     string
     */
    private $key_prefix = '_wp_affiliate_linker';

    /**
     * Returns a Post setting for the given Post ID and Type
     *
     * @since   1.0.0
     *
     * @param   int     $post_id    Post ID
     * @param   string  $type       Type
     * @param   string  $key        Setting Key
     * @return  mixed               Setting Value
     */ 
    public function get_post_setting( $post_id, $type, $key ) {

        // Get post settings
        $settings = $this->get_post_settings( $post_id, $type );

        // Get post setting
        $setting = ( isset( $settings[ $key ] ) ? $settings[ $key ] : '' );

        // Allow devs / addons to filter setting
        $setting = apply_filters( 'wp_affiliate_linker_settings_get_post_setting', $setting, $post_id, $type, $key );

        // Return
        return $setting;

    }

    /**
     * Returns all Post settings for the given Post ID and Type
     *
     * @since   1.0.0
     *
     * @param   int     $post_id    Post ID
     * @param    string     $type   Type
     * @return   array              Settings
     */
    public function get_post_settings( $post_id, $type ) {

        // Get post settings
        $settings = get_post_meta( $post_id, $this->key_prefix . '_' . $type, true );

        // Get plugin settings
        $plugin_settings = $this->get_settings( $type );

        // If no post settings exist, fallback to the plugin settings
        if ( ! $settings ) {
            $settings = $plugin_settings;
        } else {
            // Iterate through the plugin_settings, checking if the settings have the same key
            // If not, add the setting key with the default value
            // This ensures that on a Plugin upgrade where new plugin settings are introduced,
            // they are immediately available for use without the user needing to save their
            // settings.
            foreach ( $plugin_settings as $default_key => $default_value ) {
                if ( ! isset( $settings[ $default_key ] ) ) {
                    $settings[ $default_key ] = $default_value;
                }
            }
        }

        // Allow devs / addons to filter settings
        $settings = apply_filters( 'wp_affiliate_linker_settings_get_post_settings', $settings, $type );

        // Return
        return $settings;

    }

    /**
     * Saves a single post setting for the given Post ID, Type and Key
     *
     * @since   1.0.0
     *
     * @param   int     $post_id    Post ID
     * @param   string  $type       Type
     * @param   string  $key        Setting Key
     * @param   mixed   $value      Setting Value
     * @return  bool                Success
     */
    public function update_post_setting( $post_id, $type, $key, $value ) {

        // Get post settings
        $settings = $this->get_post_settings( $post_id, $type );

        // Allow devs / addons to filter post setting
        $value = apply_filters( 'wp_affiliate_linker_settings_update_post_setting', $value, $post_id, $type, $key );

        // Update single post setting
        $settings[ $key ] = $value;

        // Update post settings
        return $this->update_post_settings( $post_id, $type, $settings );

    }

    /**
     * Saves all Post settings for the given Post ID and Type
     *
     * @since 1.0.0
     *
     * @param   int     $post_id    Post ID
     * @param   string  $type       Type
     * @param   array   $settings   Settings
     * @return  bool                Success
     */
    public function update_post_settings( $post_id, $type, $settings ) {

        // Allow devs / addons to filter post settings
        $settings = apply_filters( 'wp_affiliate_linker_settings_update_post_settings', $settings, $post_id, $type, $this->key_prefix );

        // Detect if there has been a change in settings
        $existing_settings = $this->get_post_settings( $post_id, $type );
        $difference = $this->array_diff_assoc_recursive( $settings, $existing_settings );
        if ( ! empty( $difference ) ) {
            // A setting has changed; clear all of the cache
            WP_Affiliate_Linker_Cache::get_instance()->delete_all();
        }

        // Update settings
        update_post_meta( $post_id, $this->key_prefix . '_' . $type, $settings );

        // Store some post settings as individual key/value pairs, so they can be used in WP_Query calls.
        // URL
        if ( isset( $settings['url'] ) ) {
            update_post_meta( $post_id, $this->key_prefix . '_' . $type . '_url', $settings['url'] );
        } else {
            delete_post_meta( $post_id, $this->key_prefix . '_' . $type . '_url' );
        }

        // Allow devs / addons to set their own individual meta key/value pairs now
        do_action( 'wp_affiliate_linker_settings_update_post_settings_after', $post_id, $type, $settings, $this->key_prefix );
        
        return true;

    }

    /**
     * Deletes a single Post setting for the given Post ID, Type and Key
     *
     * @since   1.0.0
     *
     * @param   int     $post_id    Post ID
     * @param   string  $type       Type
     * @param   string  $key        Key
     * @return  bool                Success
     */
    public function delete_post_setting( $post_id, $type, $key ) {

        // Get post_settings
        $settings = $this->get_post_settings( $post_id, $type );

        // Delete single post setting
        if ( isset( $settings[ $key ] ) ) {
            unset( $settings[ $key ] );
        }

        // Allow devs / addons to filter post settings
        $settings = apply_filters( 'wp_affiliate_linker_settings_delete_post_setting', $settings, $post_id, $type, $key );

        // Update settings
        return $this->update_settings( $post_id, $type, $settings );

    }

    /**
     * Deletes all Post settings for the given Post ID and Type
     *
     * @since   1.0.0
     *
     * @param   int     $post_id    Post ID
     * @param   string  $type       Type
     * @return  bool                Success
     */
    public function delete_post_settings( $post_id, $type ) {

        // Delete post settings
        delete_post_meta( $post_id, $this->key_prefix . '_' . $type );

        // Allow devs / addons to run any other actions now
        do_action( 'wp_affiliate_linker_settings_delete_post_settings', $post_id, $type );

        return true;

    }
    
    /**
     * Returns a setting for the given Type
     *
     * @since   1.0.0
     *
     * @param   string  $type   Type
     * @param   string  $key    Setting Key
     * @return  mixed           Setting Value
     */ 
    public function get_setting( $type, $key ) {

        // Get settings
        $settings = $this->get_settings( $type );

        // Get setting
        $setting = ( isset( $settings[ $key ] ) ? $settings[ $key ] : '' );

        // Allow devs / addons to filter setting
        $setting = apply_filters( 'wp_affiliate_linker_settings_get_setting', $setting, $type, $key );

        // Return
        return $setting;

    }

    /**
     * Returns all settings for the given Type
     *
     * @since   1.0.0
     *
     * @param    string     $type   Type
     * @return   array              Settings
     */
    public function get_settings( $type ) {

        // Get settings
        $settings = get_option( $this->key_prefix . '_' . $type );

        // Get default settings
        $defaults = $this->get_default_settings( $type );

        // If no settings exists, fallback to the defaults
        if ( ! $settings ) {
            $settings = $defaults;
        } else {
            // Iterate through the defaults, checking if the settings have the same key
            // If not, add the setting key with the default value
            // This ensures that on a Plugin upgrade where new defaults are introduced,
            // they are immediately available for use without the user needing to save their
            // settings.
            foreach ( $defaults as $default_key => $default_value ) {
                if ( ! isset( $settings[ $default_key ] ) ) {
                    $settings[ $default_key ] = $default_value;
                }
            }
        }

        // Allow devs / addons to filter settings
        $settings = apply_filters( 'wp_affiliate_linker_settings_get_settings', $settings, $type );

        // Return
        return $settings;

    }

    /**
     * Saves a single setting for the given Type and Key
     *
     * @since   1.0.0
     *
     * @param   string  $type   Type
     * @param   string  $key    Setting Key
     * @param   mixed   $value  Setting Value
     * @return  bool            Success
     */
    public function update_setting( $type, $key, $value ) {

        // Get settings
        $settings = $this->get_settings( $type );

        // Allow devs / addons to filter setting
        $value = apply_filters( 'wp_affiliate_linker_settings_update_setting', $value, $type, $key );

        // Update single setting
        $settings[ $key ] = $value;

        // Update settings
        return $this->update_settings( $type, $settings );

    }

    /**
     * Saves all settings for the given Type
     *
     * @since 1.0.0
     *
     * @param    string  $type       Type
     * @param    array   $settings   Settings
     * @return   bool                Success
     */
    public function update_settings( $type, $settings ) {

        // Allow devs / addons to filter settings
        $settings = apply_filters( 'wp_affiliate_linker_settings_update_settings', $settings, $type );

        // Detect if there has been a change in settings
        $existing_settings = $this->get_settings( $type );
        $difference = $this->array_diff_assoc_recursive( $settings, $existing_settings );
        if ( ! empty( $difference ) ) {
            // A setting has changed; clear all of the cache
            WP_Affiliate_Linker_Cache::get_instance()->delete_all();
        }

        // Update settings
        update_option( $this->key_prefix . '_' . $type, $settings );
        
        return true;

    }

    /**
     * Deletes a single setting for the given Type and Key
     *
     * @since   1.0.0
     *
     * @param   string  $type   Type
     * @param   string  $key    Key
     * @return  bool            Success
     */
    public function delete_setting( $type, $key ) {

        // Get settings
        $settings = $this->get_settings( $type );

        // Delete single setting
        if ( isset( $settings[ $key ] ) ) {
            unset( $settings[ $key ] );
        }

        // Allow devs / addons to filter settings
        $settings = apply_filters( 'wp_affiliate_linker_settings_delete_setting', $settings, $type, $key );

        // Update settings
        return $this->update_settings( $type, $settings );

    }

    /**
     * Deletes all settings for the given Type
     *
     * @since   1.0.0
     *
     * @param   string  $type   Type
     * @return  bool            Success
     */
    public function delete_settings( $type ) {

        // Delete settings
        delete_option( $this->key_prefix . '_' . $type );

        // Allow devs / addons to run any other actions now
        do_action( 'wp_affiliate_linker_settings_delete_settings', $type );

        return true;

    }

    /**
     * Returns the default settings for the given Type
     *
     * @since   1.0.0
     *
     * @param   string $type    Type
     * @return  mixed           Default Settings | empty string
     */
    private function get_default_settings( $type ) {

        // Define defaults
        $defaults = array(
            /**
             * Cloaking
             */
            'cloaking' => array(
                'slug'      => 'go',
            ),

            /**
             * Link
             */
            'link' => array(
                'url'       => '',
                'redirect'  => 301,
                'nofollow'  => 1,
                'target'    => '_blank',
                'newsetting' => 'blah',
            ),
        );

        // Allow devs to filter defaults
        $defaults = apply_filters( 'wp_affiliate_linker_settings_get_default_settings', $defaults, $type );

        // Return
        return ( isset( $defaults[ $type ] ) ? $defaults[ $type ] : '' );

    }

    /**
     * Recursive array_diff function, to tell us if the two sets of settings
     * are different.
     *
     * @since   1.0.0
     *
     * @param   array   $array1     Settings
     * @param   array   $array2     Existing Settings
     * @return  array               Difference
     */
    private function array_diff_assoc_recursive( $array1, $array2 ) {

        $difference = array();

        foreach( $array1 as $key => $value ) {
            if( is_array( $value ) ) {
                if( ! isset( $array2[ $key ]) || ! is_array( $array2[ $key ]) ) {
                    $difference[ $key ] = $value;
                } else {
                    $new_diff = $this->array_diff_assoc_recursive( $value, $array2[ $key ] );
                    if( ! empty( $new_diff ) ) {
                        $difference[ $key ] = $new_diff;
                    }
                }
            } else if( ! array_key_exists( $key, $array2 ) || $array2[ $key ] != $value ) {
                $difference[ $key ] = $value;
            }
        }

        return $difference;

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