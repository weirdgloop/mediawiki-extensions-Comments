class AddCommentController {
	constructor( restApi, config, commentListController ) {
		const self = this;

		/** @type MediaWiki.Rest */
		this.restApi = restApi;

		/** @type object */
		this.config = config;

		this.commentListController = commentListController;

		this.parentId = null;

		// Create all the DOM elements
		this.$container = $( '<div>' ).attr( 'id', 'ext-comments-add-comment' );
		this.$inputArea = $( '<div>' ).addClass( 've-area-wrapper' );
		this.$input = $( '<textarea>' )
			.attr( {
				rows: 5,
				placeholder: mw.msg( 'comments-add-comment-placeholder' )
			} );
		this.submitBtn = new OO.ui.ButtonWidget( {
			label: 'Post comment'
		} );
		this.submitBtn.on( 'click', () => {
			self.postComment();
		} );
		this.$toolbar = $( '<div>' ).attr( 'id', 'ext-comments-add-comment-toolbar' )
			.append( this.submitBtn.$element );

		// Add the elements to the DOM
		this.$inputArea.append( this.$input );
		this.$container.append( this.$inputArea, this.$toolbar );

		// Apply VE using VEForAll
		this.$input.applyVisualEditor();
	}

	getCurrentVe() {
		const ins = this.$input.getVEInstances();
		return ins[ ins.length - 1 ];
	}

	postComment() {
		const self = this;
		const target = self.getCurrentVe().target;
		const document = target.getSurface().getModel().getDocument();

		target.getWikitextFragment( document ).then(( wikitext ) => {
			this.restApi.post( '/comments/v0/comment', {
				pageid: self.config.wgArticleId,
				parentid: self.parentId,
				text: wikitext
			} );
		} );
	}
}

export default AddCommentController;
