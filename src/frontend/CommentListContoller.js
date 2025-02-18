import { isElementInView } from './util';
import Comment from './Comment';

class CommentListContoller {
	constructor( restApi, config ) {
		this.init = false;
		/** @type MediaWiki.Rest */
		this.restApi = restApi;
		/** @type object */
		this.config = config;

		this.$container = $( '<div>' ).attr( 'id', 'ext-comments-tree' );
	}

	loadComments() {
		const self = this;
		this.restApi.get( '/comments/v0/page/' + self.config.wgArticleId, {} ).then(( res ) => {
			if ( Object.prototype.hasOwnProperty.call( res, [ 'comments' ] ) ) {
				for ( const data of res.comments ) {
					this.comments.push( new Comment( data ) );
				}
			}
		} );
	}

	addEventListeners() {
		const self = this;
		$( window ).on( 'DOMContentLoaded load resize scroll', () => {
			if ( isElementInView( this.$container ) && !this.init ) {
				this.init = true;
				self.loadComments();
			}
		} );
	}
}

export default CommentListContoller;
