const { reactive } = require( 'vue' );

const store = reactive( {
	comments: [],
	sortMethod: 'sort_rating_desc'
} )

module.exports = store;
