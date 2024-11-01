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
        <a href="#<?php echo $tab_item['name']; ?>" id="<?php echo $tab_item['name']; ?>" class="nav-tab<?php echo ( $tab_item['name'] == $tab['name'] ? ' nav-tab-active' : '' ); ?>">
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
    <a href="https://wpaffiliatelinker.com/documentation/post-settings" class="nav-tab last" target="_blank">
        <?php _e( 'Documentation', 'wp-affiliate-linker' ); ?>
        <span class="dashicons dashicons-admin-page"></span>
    </a>
</h2>

<!-- Addons -->
<?php
do_action( 'wp_affiliate_linker_post_output_meta_box', $post );

// Load nonce field
wp_nonce_field( $this->base->plugin->name . '_post', $this->base->plugin->name . '_nonce' );    