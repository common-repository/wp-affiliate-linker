<!-- Cloaking -->
<div class="panel cloaking-panel">
    <div class="postbox">
        <h3 class="hndle"><?php _e( 'Cloaking Settings', 'wp-affiliate-linker' ); ?></h3>

        <div class="option">
            <p class="description">
                <?php _e( 'How redirect links should be cloaked / disguised.  This applies to all Affiliate Links.', 'wp-affiliate-linker' ); ?>
            </p>
        </div>

        <div class="option">
            <div class="left">
                <strong><?php _e( 'Cloaking Slug', 'wp-affiliate-linker' ); ?></strong>
            </div>
            <div class="right">
                <input type="text" name="cloaking[slug]" value="<?php echo $this->get_setting( 'cloaking', 'slug' ); ?>" class="widefat" />
                
                <p class="description">
                    <?php echo sprintf( __( 'The cloaking slug to use.  Links will be formatted %s', 'wp-affiliate-linker' ), get_bloginfo( 'url' ) . '/slug/keyword' ); ?>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Links -->
<div class="panel link-panel">
    <div class="postbox">
        <h3 class="hndle"><?php _e( 'Link Settings', 'wp-affiliate-linker' ); ?></h3>

        <div class="option">
            <p class="description">
                <?php _e( 'The default settings to inherit when creating new Affiliate Links.  Each Affiliate Link has its own settings, which can be changed.', 'wp-affiliate-linker' ); ?>
            </p>
        </div>

        <div class="option">
            <div class="left">
                <strong><?php _e( 'Redirect Type', 'wp-affiliate-linker' ); ?></strong>
            </div>
            <div class="right">
                <select name="link[redirect]" size="1">
                    <?php
                    foreach ( WP_Affiliate_Linker_Common::get_instance()->get_redirect_types() as $redirect_type ) {
                        ?>
                        <option value="<?php echo $redirect_type['name']; ?>"<?php selected( $this->get_setting( 'link', 'redirect' ), $redirect_type['name'] ); ?>><?php echo $redirect_type['label']; ?></option>
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
                <select name="link[nofollow]" size="1">
                    <option value=""<?php selected( $this->get_setting( 'link', 'nofollow' ), '' ); ?>><?php _e( 'No (don\'t use nofollow)', 'wp-affiliate-linker' ); ?></option>
                    <option value="1"<?php selected( $this->get_setting( 'link', 'nofollow' ), 1 ); ?>><?php _e( 'Yes (use nofollow)', 'wp-affiliate-linker' ); ?></option>
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
                <select name="link[target]" size="1">
                    <option value=""<?php selected( $this->get_setting( 'link', 'target' ), '' ); ?>><?php _e( 'No (open link in same browser tab/window when clicked)', 'wp-affiliate-linker' ); ?></option>
                    <option value="_blank"<?php selected( $this->get_setting( 'link', 'target' ), '_blank' ); ?>><?php _e( 'Yes (open link in new browser tab/window when clicked)', 'wp-affiliate-linker' ); ?></option>
                </select>
                <p class="description">
                    <?php _e( 'If enabled, the Affiliate Link opens in a new browser tab/window when clicked.', 'wp-affiliate-linker' ); ?>
                </p>
            </div>
        </div>
    </div>
</div>

<?php
do_action( 'wp_affiliate_linker_admin_output_settings_panels' );
?>

<!-- Save -->
<div class="submit">
    <input type="submit" name="submit" value="<?php _e( 'Save', 'wp-affiliate-linker' ); ?>" class="button button-primary" />
</div>