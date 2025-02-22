const
	Vue = require( 'vue' ),
	App = require( './App.vue' );

/**
 * @return {void}
 */
function initApp() {
	$( '#bodyContent' ).append(
		$( '<div>' ).attr( 'id', 'ext-comments-container' )
	);

	// MediaWiki-specific function
	Vue.createMwApp(
		App
	).mount( '#ext-comments-container' );
}

$( () => {
	initApp();
} );
