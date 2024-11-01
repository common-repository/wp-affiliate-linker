/**
 * Initialises the QuickTags modal popup by registering a button
 * in the QuickTags instance.
 *
 * @since 	1.0.0
 */
QTags.addButton(
	'wp-affiliate-linker',
	wp_affiliate_linker_quicktags.title,
	function() {
		console.log( wp_affiliate_linker_quicktags.ajax + '?action=' + wp_affiliate_linker_quicktags.action + '&width=500&height=400&TB_iframe=true' );
		tb_show( wp_affiliate_linker_quicktags.title, wp_affiliate_linker_quicktags.ajax + '?action=' + wp_affiliate_linker_quicktags.action + '&width=500&height=400&TB_iframe=true' );	
	},
	'',
	'',
	wp_affiliate_linker_quicktags.description,
	200
);

/**
 * Fetches the selected text from the Text Editor.
 *
 * Called by the QuickTags Thickbox Modal once the user has selected
 * a link and clicked 'Add Link'.
 */
function wp_affiliate_linker_get_selected_text() {

	var editor = parent.document.getElementById( 'content' ),
		selected_text = {};

	if ( parent.document.selection != undefined ) {
		editor.focus();
		var sel = parent.document.selection.createRange();
		selected_text.text = sel.text;
		selected_text.start = sel.start;
		selected_text.end = sel.end;
	} else if ( editor.selectionStart != undefined ) {
		var start = editor.selectionStart;
		var end = editor.selectionEnd;
		selected_text.text = editor.value.substring( start, end )
		selected_text.start = start;
		selected_text.end = end;
	}

	return selected_text;

}