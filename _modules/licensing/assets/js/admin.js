jQuery( document ).ready( function( $ ) {

	// Initialize conditionals
	if ( typeof conditional !== 'undefined' ) {
		$( 'input,select' ).conditional();
	}

	// Initialize Clipboard
    if ( typeof Clipboard !== 'undefined' ) {
        var lum_clipboard = new Clipboard( '.clipboard-js' );
        $( document ).on( 'click', '.clipboard-js', function( e ) {
            e.preventDefault();
        } );
    }

} );