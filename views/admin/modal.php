<?php
/**
 * Called by TinyMCE and Quickbox, the main modal view for searching and inserting
 * links into content.
 *
 * @since   1.0.0
 */
?>
<form class="wp-affiliate-linker-modal">
    <div class="option">
        <div class="left">
            <strong><?php _e( 'Search', 'wp-affiliate-linker' ); ?></strong>
        </div>
        <div class="right">
            <input type="search" name="search" value="" class="widefat" />
        </div>
    </div>

    <div class="option results">
        <span class="spinner"></span>

        <ul>
            <?php
            $posts = WP_Affiliate_Linker_Link::get_instance()->get_by_title();
            if ( is_array( $posts ) ) {
                foreach ( $posts as $post ) {
                    ?>
                    <li>
                        <input type="radio" name="post_id" value="<?php echo $post['ID']; ?>" />
                        <span class="title"><?php echo $post['post_title']; ?></span>
                        <span class="url"><?php echo $post['link']['url']; ?></span>
                    </li>
                    <?php    
                }
            }
            ?>
        </ul>
    </div>

    <div class="option buttons">
        <div class="left">
            <button type="button" class="close button"><?php _e( 'Cancel', 'wp-affiliate-linker' ); ?></button>
        </div>
        <div class="right">
            <input name="submit" type="submit" value="<?php _e( 'Add Link', 'wp-affiliate-linker' ); ?>" class="button button-primary right" disabled="disabled" />
        </div>
    </div>
</form>