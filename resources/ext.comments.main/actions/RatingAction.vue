<template>
	<div class="comment-rating-actions">
		<button
			class="comment-rating-btn"
			:title="$i18n( 'comments-rating-upvote' )"
			data-type="upvote"
			:value="currentVote === 1"
			@click="onButtonClick"
		>
			<cdx-icon
				:icon="cdxIconUpTriangle"
				size="small"
			></cdx-icon>
		</button>
		<button
			class="comment-rating-btn"
			:title="$i18n( 'comments-rating-downvote' )"
			data-type="downvote"
			:value="currentVote === -1"
			@click="onButtonClick"
		>
			<cdx-icon
				:icon="cdxIconDownTriangle"
				size="small"
			></cdx-icon>
		</button>
	</div>
</template>

<script>
const Comment = require( '../comment.js' );
const store = require( '../store.js' );
const { defineComponent, ref } = require( 'vue' );
const { CdxIcon } = require( '@wikimedia/codex' );
const { cdxIconUpTriangle, cdxIconDownTriangle } = require( '../icons.json' );

const api = new mw.Rest();

module.exports = exports = defineComponent( {
	name: 'RatingAction',
	components: {
		CdxIcon
	},
	props: {
		comment: {
			type: Comment,
			default: null,
			required: true
		}
	},
	data() {
		return {
			store,
			currentVote: this.$props.comment.userRating
		}
	},
	methods: {
		onButtonClick( e ) {
			const type = e.currentTarget.dataset.type;
			let newValue = 0;

			if ( type === 'upvote' ) {
				newValue = this.$data.currentVote === 1 ? 0 : 1;
			} else if ( type === 'downvote' ) {
				newValue = this.$data.currentVote === -1 ? 0 : -1;
			}

			api.post( `/comments/v0/comment/${this.$props.comment.id}/vote`, {
				rating: newValue
			} ).then( ( data ) => {
				this.$data.currentVote = newValue;
				this.$props.comment.rating = data.comment.rating;
			} )
		}
	},
	setup() {
		return {
			cdxIconUpTriangle,
			cdxIconDownTriangle
		};
	}
} );
</script>
