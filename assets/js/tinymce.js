/**
 * Initialises the TinyMCE modal popup by registering a button
 * in the TinyMCE instance.
 *
 * @since 	1.0.0
 */
( function() {

	tinymce.PluginManager.add( 'wp_affiliate_linker_link', function( editor, url ) {

		// Add Button to Visual Editor Toolbar
		editor.addButton( 'wp_affiliate_linker_link', {
			title: 'Insert Affiliate Link',
			image: url + '../../../images/icon-20x20.png',
			cmd: 'wp_affiliate_linker_link',
		} );	

		// Load View when button clicked
		editor.addCommand( 'wp_affiliate_linker_link', function() {
			// Open the TinyMCE Modal
			editor.windowManager.open( {
				id: 	'wp-affiliate-linker-modal',
				title: 	'Insert Affiliate Link',
                width: 	500,
                height: 400,
                inline: 1,
                buttons:[],
            } );

			// Perform an AJAX call to load the modal's view
			jQuery.post( 
	            ajaxurl,
	            {
	                'action':       'wp_affiliate_linker_output_tinymce_modal'
	            },
	            function( response ) {
	            	// Inject HTML into modal
	            	jQuery( '#wp-affiliate-linker-modal-body' ).html( response );

	            	// Trigger a search, so that wp_affiliate_linker_modal_results is populated
    				jQuery( 'form.wp-affiliate-linker-modal input[type=search]' ).trigger( 'change' );
	            }
	        );
		} );
	} );

} )();