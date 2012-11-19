ergo = {
	
	dialog: function( widget ) {
		
		jQuery.post(
			'wp-admin/admin-ajax.php?action=ergo_dialog',
			function( response ) {
				jQuery( '.ergo-dialog' ).remove()
				jQuery( widget ).after( response )
				jQuery( '.ergo-dialog' ).dialog( { 'zIndex': 9999, 'width': 500, 'title': 'Ergo plugin' } )		
			}
		)
	}
}