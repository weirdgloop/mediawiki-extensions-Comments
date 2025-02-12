class AddCommentController {
	constructor() {
		this.$container = $( '<div>' ).attr( 'id', 'ext-comments-add-comment' );
		this.$input = $( '<textarea>' ).attr( 'id', 'ext-comments-add-comment-input' );
	}
}

export default AddCommentController;
