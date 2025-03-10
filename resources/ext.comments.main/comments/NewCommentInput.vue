<template>
	<div class="comment-input-container">
		<button
			v-show="!isWritingComment"
			class="comment-input-placeholder"
			@click="isWritingComment = true"
		>
			<span v-if="isTopLevel">{{ $i18n( 'comments-post-placeholder-top-level' ).text() }}</span>
			<span v-else>{{ $i18n( 'comments-post-placeholder-child' ).text() }}</span>
		</button>
		<div v-show="isWritingComment">
			<div class="ve-area-wrapper">
				<textarea
					ref="input"
					rows="5"
				></textarea>
			</div>
			<div class="comment-input-actions">
				<cdx-button action="progressive" weight="primary" @click="submitComment">
					<span v-if="isTopLevel">{{ $i18n( 'comments-post-submit-top-level' ).text() }}</span>
					<span v-else>{{ $i18n( 'comments-post-submit-child' ).text() }}</span>
				</cdx-button>
				<cdx-button action="destructive" @click="isWritingComment = false">
					{{ $i18n( 'cancel' ).text() }}
				</cdx-button>
			</div>
		</div>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );
const { CdxButton } = require( '@wikimedia/codex' );
const store = require( '../store.js' );
const Comment = require( '../comment.js' );

const api = new mw.Rest();

const config = mw.config.get( [
	'wgArticleId'
] );

module.exports = exports = defineComponent( {
	name: 'CommentInput',
	components: {
		CdxButton
	},
	props: {
		value: {
			type: String,
			default: '',
			required: false
		},
		parentId: {
			type: Number,
			default: null,
			required: false
		}
	},
	methods: {
		submitComment() {
			const html = this.$data.ve.target.getSurface().getHtml()

			// We're going to pass the raw HTML from VE to our API. However, the API will parse it using Parsoid
			// which will sanitize it before saving it in the database.
			api.post( '/comments/v0/comment', {
				pageid: config.wgArticleId,
				parentid: this.$props.parentId,
				html: html
			} ).then( ( data ) => {
				this.$data.store.comments.unshift( new Comment( data.comment ) );
				this.$data.isWritingComment = false;
			} ).fail( ( _, result ) => {
				if ( result.xhr.responseJSON ) {
					mw.notify( result.xhr.responseJSON.message, { type: 'error', tag: 'post-comment-error' } );
				} else {
					mw.notify( 'There was a problem. Please try again.', {
						type: 'error',
						tag: 'post-comment-error'
					} )
				}
			} )
		}
	},
	data() {
		return {
			store,
			ve: null,
			isWritingComment: false
		};
	},
	watch: {
		isWritingComment( val ) {
			if ( val === true && this.$data.ve === null ) {
				const $input = $( this.$refs.input );

				if ( this.$props.value !== '' ) {
					$input.val( this.$props.value );
				}

				// Create the VE instance for this editor
				this.$data.ve = new mw.commentsExt.ve.Editor( $input, $input.val() );
			} else if ( val === true ) {
				this.$data.ve.target.getSurface().getView().focus();
			}
		}
	},
	computed: {
		isTopLevel() {
			return this.$props.parentId === null
		}
	}
} );
</script>
