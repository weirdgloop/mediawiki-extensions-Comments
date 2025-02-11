'use strict';

import { isElementInView } from './util';

let HAS_INITED = false,
	$container,
	$addCommentContainer,
	$commentTree;

/**
 * Initialise the frontend for the comments extension. We do not initialise until the container is in the viewport,
 * to prevent unnecessary API calls for all page views.
 */
const init = () => {
	console.log( 'Initialised Comments' );
};

/**
 * Build the comment container and add it to the DOM
 */
const buildCommentsContainer = () => {
	$addCommentContainer = $( '<div>' ).attr( 'id', 'mw-comments-add-comment' );
	$commentTree = $( '<div>' ).attr( 'id', 'mw-comments-tree' );

	$container = $( '<div>' )
		.attr( 'id', 'mw-comments-container' )
		.append(
			$( '<h3>' ).text( mw.message( 'comments-container-header' ).text() ),
			$addCommentContainer,
			$commentTree
		);

	$( '#bodyContent' ).append( $container );
};

$( () => buildCommentsContainer() );

$( window ).on( 'DOMContentLoaded load resize scroll', ( ) => {
	if ( isElementInView( $container ) && !HAS_INITED ) {
		HAS_INITED = true;
		init();
	}
} );
