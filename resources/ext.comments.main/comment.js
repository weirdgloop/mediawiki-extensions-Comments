/**
 * Class representation of a comment object returned from the API.
 *
 * This object should not contain any UI-dependent methods - those should be inside Comment.vue.
 */
class Comment {
	constructor( data ) {
		this.id = data.id || null;
		this.deleted = data.deleted !== null ? data.deleted : false;
		this.rating = data.rating || 0;
		this.html = data.html || '';
		this.wikitext = data.wikitext || '';
		this.actor = data.actor || {};
		this.timestamp = data.timestamp;
	}
}

module.exports = Comment;
