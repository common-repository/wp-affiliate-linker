<?php
/**
 * Called by admin-ajax, the Thickbox modal for Quicktags has no HTML or styling
 *
 * This view acts as a bootstrap to load what's needed.
 *
 * See includes/admin/editor.php for the rest of the enqueued CSS and JS
 *
 * @since   1.0.0
 */
?>
<!DOCTYPE html>
<head>
    <title><?php _e( 'Insert Affiliate Link', 'wp-affiliate-linker' ); ?></title>

    <?php
    // Run WordPress Header Actions
    do_action( 'admin_enqueue_scripts' );
    do_action( 'admin_print_styles' );
    do_action( 'admin_print_scripts' );
    do_action( 'wp_print_scripts' );
    do_action( 'admin_head' );
    ?>
</head>

<body class="wp-core-ui">
    <?php 
    // Load shared view
    require_once( 'modal.php' );

    // Run WordPress Footer Actions
    do_action( 'admin_footer' );
    do_action( 'shutdown' );
    ?>
</body>
</html>