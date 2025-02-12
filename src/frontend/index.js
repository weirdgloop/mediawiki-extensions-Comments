'use strict';

import { isElementInView } from './util';
import AddCommentController from './AddCommentController';

class Comments {
	constructor() {
		this.init = false;
		this.addCommentController = new AddCommentController();
		this.$commentTree = $( '<div>' ).attr( 'id', 'ext-comments-tree' );
		this.$container = $( '<div>' )
			.attr( 'id', 'ext-comments-container' )
			.append(
				$( '<h3>' ).text( mw.message( 'comments-container-header' ).text() ),
				this.addCommentController.$container,
				this.$commentTree
			);
		this.addEventListeners();
	}

	/**
	 * Add the container for the comments interface to the page
	 */
	addContainerToPage() {
		$( '#bodyContent' ).append( this.$container );
	}

	/**
	 * Add the required event listeners
	 */
	addEventListeners() {
		$( () => this.addContainerToPage() );

		$( window ).on( 'DOMContentLoaded load resize scroll', () => {
			if ( isElementInView( this.$container ) && !this.init ) {
				this.init = true;
				// TODO actually make initial API calls and render things
			}
		} );
	}
}

const comments = new Comments();
