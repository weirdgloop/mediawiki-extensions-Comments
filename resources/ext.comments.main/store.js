const { reactive } = require( 'vue' );

const isSpecialComments = !!document.querySelector( 'body.mw-special-Comments' );

const store = reactive( {
	// Whether we're on Special:Comments instead of a normal wiki page
	isSpecialComments,
	ready: false,
	comments: [],
	sortMethod: isSpecialComments ? 'sort_date_desc' : 'sort_rating_desc',
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
