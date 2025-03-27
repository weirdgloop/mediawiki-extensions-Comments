const { reactive } = require( 'vue' );

const store = reactive( {
	ready: false,
	comments: [],
	sortMethod: 'sort_rating_desc',
	isReadOnly: false,
	globalCooldown: 0,
	// The ID of the comment that we're currently editing, or null if we aren't editing any
	isEditing: null,
	// Whether the current user can moderate other people's comments. This variable is set when a new list of comments
	// is retrieved from the database via the API call.
	isMod: false,
	// If this is set to a comment ID, then the UI will render this comment and its children only,
	// rather than the entire comment tree.
	singleComment: null,
} )

module.exports = store;
