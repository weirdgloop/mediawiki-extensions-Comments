class Comment {
	constructor( data ) {
		this.id = data.id || null;
		this.deleted = data.deleted !== null ? data.deleted : false;
		this.rating = data.rating || 0;
		this.html = data.html || '';
		this.wikitext = data.wikitext || '';
		this.actor = data.actor || {};

		this.isEditing = false;

		this.$element = $( '<div>' )
			.addClass( 'ext-comments-comment-wrapper' )
			.data( 'id', this.id )
			.html( this.html );
	}
}

export default Comment;
