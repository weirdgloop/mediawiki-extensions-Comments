<template>
	<toolbar></toolbar>
	<button
		class="comment-input-placeholder"
		v-if="!store.isSpecialComments"
		v-show="!isWritingTopLevelComment"
		@click="isWritingTopLevelComment = true"
	>
		<span>{{ $i18n( 'comments-post-placeholder-top-level' ).text() }}</span>
	</button>
	<new-comment-input
		v-if="store.singleComment === null"
		:is-writing-comment="isWritingTopLevelComment"
		:on-cancel="() => isWritingTopLevelComment = false"
	></new-comment-input>
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
			isWritingTopLevelComment: false
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

		const handleHashChange = () => {
			const params = new URLSearchParams( window.location.hash.substring(1) );

			// Determine whether we should only be showing a single comment
			let singleCommentId = params.get( 'comment' );
			if ( singleCommentId ) {
				this.$data.store.singleComment = singleCommentId;
				document.querySelector( '#ext-comments-container' ).scrollIntoView();
			}

			// Filters
			let targetUser = params.get( 'user' );
			if ( targetUser ) {
				targetUser = targetUser.trim();
				this.$data.store.filterByUser = targetUser.charAt(0).toUpperCase() + targetUser.substring(1);
			}
		}

		window.addEventListener( 'hashchange', handleHashChange )
		handleHashChange(); // fire it once on page load

		this.$data.store.isReadOnly = readOnly;

		setInterval( () => {
			if ( self.$data.store.globalCooldown > 0 ) {
				self.$data.store.globalCooldown -= 1;
			}
		}, 1000 )

		this.$data.store.ready = true;
	}
} );
</script>
