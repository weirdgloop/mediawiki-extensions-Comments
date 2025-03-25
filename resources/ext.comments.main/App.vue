<template>
	<h3>{{ $i18n( 'comments-container-header' ).text() }}</h3>
	<toolbar></toolbar>
	<new-comment-input></new-comment-input>
	<comments-list></comments-list>
</template>

<script>
const { defineComponent } = require( 'vue' );
const { CdxSelect, CdxField } = require( '@wikimedia/codex' );
const store = require( './store.js' );
const NewCommentInput = require( './comments/NewCommentInput.vue' );
const CommentsList = require( './CommentsList.vue' );
const Toolbar = require( './Toolbar.vue' );

module.exports = exports = defineComponent( {
	name: 'App',
	data() {
		return {
			store,
		};
	},
	components: {
		NewCommentInput,
		Toolbar,
		CommentsList,
		CdxSelect,
		CdxField
	},
	mounted() {
		const self = this;
		// When the app first loads, determine whether we should be displaying the comments in a read-only form
		let readOnly = mw.config.get( 'wgComments' ).readOnly;

		this.$data.store.isReadOnly = readOnly;

		setInterval( () => {
			if ( self.$data.store.globalCooldown > 0 ) {
				self.$data.store.globalCooldown -= 1;
			}
		}, 1000 )
	}
} );
</script>
