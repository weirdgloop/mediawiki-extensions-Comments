const { reactive } = require( 'vue' );

const store = reactive( {
	sortMethod: 'sort_rating_desc',
	isWritingTopLevelComment: false,
	toggleWritingTopLevelComment() {
		this.isWritingTopLevelComment = !this.isWritingTopLevelComment
	}
} )

module.exports = store;
