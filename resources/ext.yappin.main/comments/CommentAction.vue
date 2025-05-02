<template>
	<button
		class="comment-action-btn"
		:title="title"
		@click="onClick"
	>
		<cdx-icon
			:icon="icon"
			size="small"
		></cdx-icon>
	</button>
</template>

<script>
const { defineComponent } = require( 'vue' );
const { CdxIcon } = require( '@wikimedia/codex' );

module.exports = exports = defineComponent( {
	name: 'DeleteAction',
	components: {
		CdxIcon
	},
	props: {
		icon: {
			default: null,
			required: true
		},
		onClick: {
			type: Function,
			default: null,
			required: true
		},
		title: {
			type: String,
			default: '',
			required: false
		}
	}
} );
</script>
