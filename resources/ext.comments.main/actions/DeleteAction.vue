<template>
	<button
		class="comment-action-btn"
		title="Delete comment"
		@click="onButtonClick"
	>
		<cdx-icon
			:icon="cdxIconTrash"
			size="small"
		></cdx-icon>
	</button>
</template>

<script>
const { defineComponent } = require( 'vue' );
const { CdxIcon } = require( '@wikimedia/codex' );
const { cdxIconTrash } = require( '../icons.json' );

module.exports = exports = defineComponent( {
	name: 'DeleteAction',
	components: {
		CdxIcon
	},
	props: [
		'commentId'
	],
	setup() {
		const onButtonClick = function ( e ) {
			// TODO: api call to delete the comment
		};

		return {
			onButtonClick,
			cdxIconTrash
		};
	}
} );
</script>
