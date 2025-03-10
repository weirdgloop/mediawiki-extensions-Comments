<template>
	<h3>{{ $i18n( 'comments-container-header' ).text() }}</h3>
	<root-action-header></root-action-header>
	<new-comment-input></new-comment-input>
	<comments-list></comments-list>
</template>

<script>
const { defineComponent } = require( 'vue' );
const { CdxSelect, CdxField } = require( '@wikimedia/codex' );
const store = require( './store.js' );
const NewCommentInput = require( './comments/NewCommentInput.vue' );
const CommentsList = require( './CommentsList.vue' );
const RootActionHeader = require( './actions/RootActionHeader.vue' );

module.exports = exports = defineComponent( {
	name: 'App',
	data() {
		return {
			store,
		};
	},
	components: {
		NewCommentInput,
		RootActionHeader,
		CommentsList,
		CdxSelect,
		CdxField
	},
	setup() {
		const readOnly = mw.config.get( 'wgComments' ).readOnly;

		return {
			readOnly,
		};
	}
} );
</script>
