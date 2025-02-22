<template>
	<div class="ext-comments-comments-list">
		<comment-item
			v-for="c in comments"
			:key="c.id"
			:comment="c"
		></comment-item>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );
const { isElementInView } = require( './util.js' );
const Comment = require( './comment.js' );
const CommentItem = require( './CommentItem.vue' );

const api = new mw.Rest();

const config = mw.config.get( [
	'wgArticleId'
] );

module.exports = exports = defineComponent( {
	name: 'CommentsList',
	components: {
		CommentItem
	},
	data() {
		return {
			hasBeenVisible: false,
			comments: []
		};
	},
	methods: {
		loadComments() {
			api.get( `/comments/v0/page/${ config.wgArticleId }` )
				.done( ( res ) => {
					const comments = [];
					for ( const data of res.comments ) {
						comments.push( new Comment( data ) );
					}
					this.$data.comments = comments;
				} );
		}
	},
	mounted() {
		$( window ).on( 'DOMContentLoaded load resize scroll', () => {
			if ( isElementInView( this.$el ) && !this.$data.hasBeenVisible ) {
				this.$data.hasBeenVisible = true;
				this.loadComments();
			}
		} );
	}
} );
</script>
