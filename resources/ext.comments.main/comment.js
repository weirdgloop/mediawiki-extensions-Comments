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
		this.userRating = data.userRating || 0;
		this.html = data.html || '';
		this.wikitext = data.wikitext || '';
		this.user = data.user || {};
		this.created = data.created;
		this.edited = data.edited || null;
		this.ours = data.ours || false;
		this.page = data.page || null;
		this.parent = data.parent || null;

		/** @type Comment[] */
		this.children = [];

		if ( data.children !== undefined ) {
			for ( const child of data.children ) {
				this.children.push( new Comment( child ) );
			}
		}
	}
}

module.exports = Comment;
