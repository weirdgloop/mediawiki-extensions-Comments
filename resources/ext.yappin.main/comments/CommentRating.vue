<template>
	<div class="comment-rating-actions">
		<button
			class="comment-rating-btn"
			:title="$i18n( 'yappin-rating-upvote' )"
			data-type="upvote"
			:value="this.$props.comment.userRating === 1"
			@click="onButtonClick"
			:disabled="waiting"
		>
			<cdx-icon
				:icon="cdxIconUpTriangle"
				size="small"
			></cdx-icon>
		</button>
		<button
			class="comment-rating-btn"
			:title="$i18n( 'yappin-rating-downvote' )"
			data-type="downvote"
			:value="this.$props.comment.userRating === -1"
			@click="onButtonClick"
			:disabled="waiting"
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
	name: 'CommentRating',
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
			waiting: false
		}
	},
	methods: {
		onButtonClick( e ) {
			this.$data.waiting = true;
			const type = e.currentTarget.dataset.type;
			let newValue = 0;

			if ( type === 'upvote' ) {
				newValue = this.$props.comment.userRating === 1 ? 0 : 1;
			} else if ( type === 'downvote' ) {
				newValue = this.$props.comment.userRating === -1 ? 0 : -1;
			}

			// Change the UI state before the API call happens for quick visual feedback
			const oldValue = this.$props.comment.userRating;
			this.$props.comment.userRating = newValue;

			api.post( `/comments/v0/comment/${this.$props.comment.id}/vote`, {
				rating: newValue
			} ).then( ( data ) => {
				this.$props.comment.rating = data.comment.rating;
			} ).always( () => {
				this.$data.waiting = false;
			} ).fail( () => {
				// Reset the UI state back to the previous value if the API call failed
				this.$props.comment.userRating = oldValue
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
