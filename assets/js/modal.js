/**
 * Controls the TinyMCE and QuickTags popup modal for searching links,
 * selecting links and inserting links into content.
 *
 * @since   1.0.0
 */

var wp_affiliate_linker_modal_searching = false,
    wp_affiliate_linker_modal_results   = {};

jQuery( document ).ready( function( $ ) {

    $( 'body' ).on( 'change', 'form.wp-affiliate-linker-modal input[type=search]', function( e ) {

        // If we're currently running an AJAX request, don't run another one
        if ( wp_affiliate_linker_modal_searching ) {
            return;
        }

        // Set the flag so we know we're performing an AJAX request
        wp_affiliate_linker_modal_searching = true;

        // Clear existing results object
        wp_affiliate_linker_modal_results = {};

        // Disable the Add Link button
        $( 'form input[type=submit]' ).prop( 'disabled', true );

        // Clear results
        $( 'form ul li' ).remove();

        // Show spinner
        $( 'span.spinner' ).show();

        // Send an AJAX request to fetch the parsed title, URL and description
        $.post( 
            wp_affiliate_linker_modal.ajax, 
            {
                'action':       'wp_affiliate_linker_search',
                'nonce':        wp_affiliate_linker_modal.nonce,
                'keywords':     $( this ).val()
            },
            function( response ) {
                // We've finished searching
                wp_affiliate_linker_modal_searching = false;

                // Hide spinner
                $( 'span.spinner' ).hide();

                // If something went wrong, show an error
                if ( ! response.success ) {
                    $( 'form ul' ).append( '<li>1' + wp_affiliate_linker_modal.strings.error + '</li>' );
                    return;
                }

                // If no results, show an error
                if ( ! response.data ) {
                    $( 'form ul' ).append( '<li>' + wp_affiliate_linker_modal.strings.no_results + '</li>' );
                    return;
                }

                // Iterate through results, building output
                for ( var i = 0; i < response.data.length; i++ ) {
                    // Get Link Post
                    wp_affiliate_linker_modal_results[ response.data[ i ].ID ] = response.data[ i ];

                    // Build output
                    var output = '<li>';
                    output += '<input type="radio" name="post_id" value="' + wp_affiliate_linker_modal_results[ response.data[ i ].ID ].ID + '" />';
                    output += '<span class="title">' + wp_affiliate_linker_modal_results[ response.data[ i ].ID ].post_title + '</span>';
                    output += '<span class="url">' + wp_affiliate_linker_modal_results[ response.data[ i ].ID ].link.url + '</span>';
                    
                    // Append to unordered list
                    $( 'form ul' ).append( output );
                }   
            }
        );

    } );

    // Trigger a search, so that wp_affiliate_linker_modal_results is populated
    // Note: This only fires for QuickTags, and does not fire for TinyMCE.  Therefore this
    // call is also made at includes/js/tinymce.js once the view has been loaded into the TinyMCE modal.
    $( 'form.wp-affiliate-linker-modal input[type=search]' ).trigger( 'change' );

    // Select radio input on list item click
    $( 'body' ).on( 'click', 'form.wp-affiliate-linker-modal ul li', function( e ) {

        // If the clicked item isn't a result (i.e. it's an error message), don't do anything
        if ( $( 'input[type=radio]', $( this ).parent() ).length == 0 ) {
            return;
        }

        // Deselect all
        $( 'form ul li.selected' ).removeClass( 'selected' );
        $( 'input[type=radio]', $( this ).parent() ).prop( 'checked', false );

        // Select this item
        $( this ).addClass( 'selected' );
        $( 'input[type=radio]', $( this ) ).prop( 'checked', true );

        // Enable the Add Link button
        $( 'form input[type=submit]' ).prop( 'disabled', false );

    } );

    // Cancel
    $( 'body' ).on( 'click', 'form.wp-affiliate-linker-modal button.close', function( e ) {

        if ( $( '#wp-affiliate-linker-modal' ).length > 0 ) {
            // Visual Editor
            tinymce.activeEditor.windowManager.close();
        } else {
            // Text Editor
            parent.tb_remove();
        }

    } );

    // Add Link into Editor
    $( 'body' ).on( 'click', 'form.wp-affiliate-linker-modal input[type=submit]', function( e ) {

        // Prevent default action
        e.preventDefault();

        // Get Post ID
        var post_id   = $( 'form.wp-affiliate-linker-modal input[name=post_id]:checked' ).val();
        
        // If no Post ID exists, something went wrong
        if ( typeof post_id == undefined ) {
            alert( wp_affiliate_linker_modal.strings.no_link_selected );
            return;
        }

        // If no Link Post for the selected Post ID exists, something went wrong
        if ( typeof wp_affiliate_linker_modal_results[ post_id ] == undefined ) {
            alert( 'Link was selected, but its data could not be loaded. Please close the modal and try again.' );
            return;
        }

        // Build link
        var link = '<a href="' + wp_affiliate_linker_modal_results[ post_id ].cloaked_url + '"';
        if ( wp_affiliate_linker_modal_results[ post_id ].link.target == '_blank' ) {
            link += ' target="_blank"';
        }
        if ( wp_affiliate_linker_modal_results[ post_id ].link.nofollow == '1' ) {
            link += ' rel="nofollow"';
        }
        link += '>';

        /**
         * Finish building the link, and insert it, depending on whether we were initialized from
         * the Visual Editor or not.
         */
        if ( $( '#wp-affiliate-linker-modal' ).length > 0 ) {
            // TinyMCE
            // tinyMCE.activeEditor will give you the TinyMCE editor instance that's being used.
            
            // If content is selected in the active TinyMCE editor instance, use that as the title
            // Otherwise use the Link's Post Title
            if ( tinyMCE.activeEditor.selection.getContent().length > 0 ) {
                link += tinyMCE.activeEditor.selection.getContent();
            } else {
                link += wp_affiliate_linker_modal_results[ post_id ].post_title;
            }

            // Close link
            link += '</a>';

            // Insert into editor
            tinyMCE.activeEditor.execCommand( 'mceReplaceContent', false, link );

            // Close modal
            tinyMCE.activeEditor.windowManager.close();

            // Done
            return;
        } else {
            // If content is selected in the active TinyMCE editor instance, use that as the title
            // Otherwise use the Link's Post Title
            var selected_text = parent.wp_affiliate_linker_get_selected_text();
            if ( selected_text.text.length > 0 ) {
                link += selected_text.text;
            } else {
                link += wp_affiliate_linker_modal_results[ post_id ].post_title;
            }

            // Close link
            link += '</a>';

            // Insert into text editor
            var editor = parent.document.getElementById( 'content' ),
                existing_content = editor.value;

            editor.value = existing_content.slice( 0, selected_text.start ) + link + existing_content.slice( selected_text.end );

            // Trigger a change event for scripts that watch for changes to the Text editor content
            $( editor ).trigger( 'change' ); 

            // Close popup
            parent.tb_remove();
        }

    } );

} );