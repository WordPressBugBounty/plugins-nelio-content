/* eslint-disable @typescript-eslint/no-unsafe-call */
/* eslint-disable @typescript-eslint/no-unsafe-member-access */
( function ( $ ) {
	'use strict';

	fixHelpButtons();
	addSubpageNameToUrl();

	// =======
	// HELPERS
	// =======

	function fixHelpButtons() {
		$( 'img.nelio-settings-help' ).on( 'click', function ( ev ) {
			ev.preventDefault();
			$( this ).closest( 'tr' ).find( '.setting-help' ).toggle();
		} );
	} //end fixHelpButtons()

	function addSubpageNameToUrl() {
		const url = window.location.href;
		const pageName = document
			.getElementById( 'nelio-settings-current-subpage' )
			?.getAttribute( 'value' );
		if ( ! pageName ) {
			return;
		} //end if

		if ( /\bsubpage=/.test( url ) ) {
			const newUrl = url.replace(
				/\bsubpage=[^&]+/,
				`subpage=${ pageName }`
			);
			window.history.replaceState( {}, '', newUrl );
			return;
		} //end if

		const sep = url.indexOf( '?' ) ? '&' : '?';
		const newUrl = `${ url }${ sep }subpage=${ pageName }`;
		window.history.replaceState( {}, '', newUrl );
	} //end addSubpageNameToUrl()
} )( window.jQuery );
