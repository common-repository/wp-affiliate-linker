<!-- Tabs -->
<h2 class="nav-tab-wrapper needs-js" data-panel="panel" data-form="#post">
    <?php
    // Iterate through this screen's tabs
    foreach ( (array) $tabs as $tab_item ) {
        // Check if an icon needs to be displayed
        $icon = '';
        if ( ! empty( $tab_item['menu_icon'] ) ) {
            $icon = 'dashicons ' . $tab_item['menu_icon'];
        }
        ?>
        <a href="#<?php echo $tab_item['name']; ?>" id="<?php echo $tab_item['name']; ?>" class="nav-tab<?php echo ( $tab_item['name'] == $tab['name'] ? ' nav-tab-active' : '' ); ?>"<?php echo ( isset( $tab_item['documentation'] ) ? ' data-documentation="' . $tab_item['documentation'] . '"' : '' ); ?>>
            <?php
            if ( ! empty( $icon ) ) {
                ?>
                <span class="<?php echo $icon; ?>"></span>
                <?php
            }
            
            echo $tab_item['label'];
            ?>
        </a>
        <?php
    }

    // Add a Documentation Tab
    ?>
    <a href="https://wpaffiliatelinker.com/documentation/post-settings" class="nav-tab last documentation" target="_blank">
        <?php _e( 'Documentation', 'wp-affiliate-linker' ); ?>
        <span class="dashicons dashicons-admin-page"></span>
    </a>
</h2>

<!-- Link -->
<div class="panel link-panel">
    <div class="option">
    	<div class="left">
    		<strong><?php _e( 'URL', 'wp-affiliate-linker' ); ?></strong>
    	</div>
    	<div class="right">
    		<input type="text" name="<?php echo $this->base->plugin->name; ?>[link][url]" value="<?php echo $this->get_post_setting( $post->ID, 'link', 'url' ); ?>" class="widefat" />
    	
        	<p class="description">
        		<?php _e( 'The destination URL to take the visitor to when they click this link.  This can be HTTP(S), tel:, bbmi:, intent: etc.', 'wp-affiliate-linker' ); ?>
        	</p>
    	</div>
    </div>

    <div class="option">
        <div class="left">
            <strong><?php _e( 'Redirect Type', 'wp-affiliate-linker' ); ?></strong>
        </div>
        <div class="right">
            <select name="<?php echo $this->base->plugin->name; ?>[link][redirect]" size="1">
                <?php
                foreach ( WP_Affiliate_Linker_Common::get_instance()->get_redirect_types() as $redirect_type ) {
                    ?>
                    <option value="<?php echo $redirect_type['name']; ?>"<?php selected( $this->get_post_setting( $post->ID, 'link', 'redirect' ), $redirect_type['name'] ); ?>><?php echo $redirect_type['label']; ?></option>
                    <?php
                }
                ?>
            </select>

            <p class="description">
                <?php _e( 'The type of redirect that will be performed when a user clicks an Affiliate Link', 'wp-affiliate-linker' ); ?>
            </p>
        </div>
    </div>

    <div class="option">
        <div class="left">
            <strong><?php _e( 'Use nofollow?', 'wp-affiliate-linker' ); ?></strong>
        </div>
        <div class="right">
            <select name="<?php echo $this->base->plugin->name; ?>[link][nofollow]" size="1">
                <option value=""<?php selected( $this->get_post_setting( $post->ID, 'link', 'nofollow' ), '' ); ?>><?php _e( 'No (don\'t use nofollow)', 'wp-affiliate-linker' ); ?></option>
                <option value="1"<?php selected( $this->get_post_setting( $post->ID, 'link', 'nofollow' ), 1 ); ?>><?php _e( 'Yes (use nofollow)', 'wp-affiliate-linker' ); ?></option>
            </select>
            <p class="description">
                <?php _e( 'If enabled, rel="nofollow" is added to the link when inserted into content. This ensures no link score is passed onto the Affiliate Link.', 'wp-affiliate-linker' ); ?>
            </p>
        </div>
    </div>

    <div class="option">
        <div class="left">
            <strong><?php _e( 'Open in New Window?', 'wp-affiliate-linker' ); ?></strong>
        </div>
        <div class="right">
            <select name="<?php echo $this->base->plugin->name; ?>[link][target]" size="1">
                <option value=""<?php selected( $this->get_post_setting( $post->ID, 'link', 'target' ), '' ); ?>><?php _e( 'No (open link in same browser tab/window when clicked)', 'wp-affiliate-linker' ); ?></option>
                <option value="_blank"<?php selected( $this->get_post_setting( $post->ID, 'link', 'target' ), '_blank' ); ?>><?php _e( 'Yes (open link in new browser tab/window when clicked)', 'wp-affiliate-linker' ); ?></option>
            </select>
            <p class="description">
                <?php _e( 'If enabled, the Affiliate Link opens in a new browser tab/window when clicked.', 'wp-affiliate-linker' ); ?>
            </p>
        </div>
    </div>
</div>

<!-- Addons -->
<?php
do_action( 'wp_affiliate_linker_link_output_meta_box', $post );

// Load nonce field
wp_nonce_field( $this->base->plugin->name . '_link', $this->base->plugin->name . '_nonce' );    