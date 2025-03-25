const { reactive } = require( 'vue' );

const store = reactive( {
	comments: [],
	sortMethod: 'sort_rating_desc',
	isReadOnly: false,
	globalCooldown: 0,
	// The ID of the comment that we're currently editing, or null if we aren't editing any
	isEditing: null,
} )

module.exports = store;
