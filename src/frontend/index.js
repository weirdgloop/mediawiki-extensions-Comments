'use strict';

import AddCommentController from './AddCommentController';
import CommentListContoller from './CommentListContoller';

class Comments {
	constructor() {
		this.init = false;
		this.config = mw.config.get( [
			'wgArticleId'
		] );
		this.restApi = new mw.Rest();

		this.commentListController = new CommentListContoller( this.restApi, this.config );
		this.addCommentController = new AddCommentController( this.restApi, this.config, this.commentListController );
		this.$container = $( '<div>' )
			.attr( 'id', 'ext-comments-container' )
			.append(
				$( '<h3>' ).text( mw.message( 'comments-container-header' ).text() ),
				this.addCommentController.$container,
				this.commentListController.$container
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
		this.commentListController.addEventListeners();
	}
}

window.comments = new Comments();
