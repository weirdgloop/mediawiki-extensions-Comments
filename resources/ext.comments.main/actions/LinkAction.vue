<template>
	<button
		class="comment-action-btn"
		title="Copy link"
		@click="onButtonClick"
	>
		<cdx-icon
			:icon="cdxIconLink"
			size="small"
		></cdx-icon>
	</button>
</template>

<script>
const { defineComponent } = require( 'vue' );
const { CdxIcon } = require( '@wikimedia/codex' );
const { cdxIconLink } = require( '../icons.json' );

module.exports = exports = defineComponent( {
	name: 'CopyAction',
	components: {
		CdxIcon
	},
	props: [
		'commentId'
	],
	setup() {
		const onButtonClick = function ( e ) {
			// TODO: copy the link
		};

		return {
			onButtonClick,
			cdxIconLink
		};
	}
} );
</script>
