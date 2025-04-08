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
	<div
		v-if="store.singleComment !== null"
		class="comment-info-full"
		style="margin-bottom: 1em;"
	>
		<span>{{ $i18n( 'comments-single-mode-banner' ) }}</span>
		&#183;
		<a @click="disableSingleComment">{{ $i18n( 'comments-single-mode-viewall' ) }}</a>
	</div>
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
	methods: {
		disableSingleComment() {
			this.$data.store.singleComment = null;
			const url = new URL( window.location )
			url.hash = '';
			history.pushState(null, '', url);
		}
	},
	mounted() {
		const self = this;
		// When the app first loads, determine whether we should be displaying the comments in a read-only form
		let readOnly = mw.config.get( 'wgComments' ).readOnly;

		// Get current URL, and determine whether we should only be showing a single comment
		let singleCommentId = new URLSearchParams( window.location.hash.substring(1) ).get( 'commentid' );
		if ( singleCommentId ) {
			this.$data.store.singleComment = singleCommentId;
		}

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
