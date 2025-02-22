<template>
	<div class="comment-rating-actions">
		<button
			class="comment-rating-btn"
			data-type="upvote"
			title="Upvote"
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
			title="Downvote"
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
const { defineComponent, ref } = require( 'vue' );
const { CdxIcon } = require( '@wikimedia/codex' );
const { cdxIconUpTriangle, cdxIconDownTriangle } = require( '../icons.json' );

module.exports = exports = defineComponent( {
	name: 'RatingAction',
	components: {
		CdxIcon
	},
	props: [
		'commentId'
	],
	setup() {
		// 0 = no vote, -1 = downvote, 1 = upvote
		const currentVote = ref( 0 );

		const onButtonClick = function ( e ) {
			const type = e.currentTarget.dataset.type;
			let newValue = 0;

			if ( type === 'upvote' ) {
				newValue = currentVote.value === 1 ? 0 : 1;
			} else if ( type === 'downvote' ) {
				newValue = currentVote.value === -1 ? 0 : -1;
			}

			// TODO: api call to update our rating before we update the UI state...
			currentVote.value = newValue;
		};

		return {
			onButtonClick,
			currentVote,
			cdxIconUpTriangle,
			cdxIconDownTriangle
		};
	}
} );
</script>
