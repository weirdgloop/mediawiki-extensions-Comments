<template>
	<h3>{{ $i18n( 'comments-container-header' ).text() }}</h3>
	<root-action-header></root-action-header>
	<div class="comment-top-level-input">
		<button
			v-if="!store.isWritingTopLevelComment"
			class="comment-top-level-input-placeholder"
			@click="store.toggleWritingTopLevelComment()"
		>
			{{ $i18n( 'comments-post-placeholder' ).text() }}
		</button>
		<div v-else>
			<new-comment-input></new-comment-input>
		</div>
	</div>
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
