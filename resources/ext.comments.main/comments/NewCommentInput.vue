<template>
	<div v-show="isWritingComment" class="comment-input-container">
		<div class="ve-area-wrapper">
			<textarea
				ref="input"
				rows="5"
			></textarea>
		</div>
		<div class="comment-input-actions">
			<cdx-button :disabled="store.globalCooldown" action="progressive" weight="primary" @click="submitComment">
				<span v-if="store.globalCooldown">{{ $i18n( 'comments-submit-cooldown', store.globalCooldown ).text() }}</span>
				<span v-else-if="isTopLevel">{{ $i18n( 'comments-post-submit-top-level' ).text() }}</span>
				<span v-else>{{ $i18n( 'comments-post-submit-child' ).text() }}</span>
			</cdx-button>
			<cdx-button action="destructive" @click="onCancel">
				{{ $i18n( 'cancel' ).text() }}
			</cdx-button>
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
	'wgArticleId',
	'wgContentLanguage'
] );

module.exports = exports = defineComponent( {
	name: 'CommentInput',
	components: {
		CdxButton
	},
	props: {
		isWritingComment: {
			type: Boolean,
			default: false,
			required: true
		},
		onCancel: {
			type: Function,
			required: true
		},
		parentId: {
			type: Number,
			default: null,
			required: false
		}
	},
	methods: {
		submitComment() {
			const body = {
				pageid: config.wgArticleId,
				parentid: this.$props.parentId
			};

			if ( this.$data.ve ) {
				// We're going to pass the raw HTML from VE to our API. However, the API will parse it using Parsoid
				// which will sanitize it before saving it in the database.
				body[ 'html' ] = this.$data.ve.target.getSurface().getHtml();
			} else {
				// If we're not using VE, just send the raw value of the input as wikitext.
				body[ 'wikitext' ] = $( this.$refs.input ).val();
			}

			api.post( '/comments/v0/comment', body ).then( ( data ) => {
				let newComment = new Comment( data.comment );

				if ( this.$props.parentId ) {
					// Reply to an existing comment, add it to the end of the children list
					const ix = this.$data.store.comments.findIndex( ( c ) => c.id = this.$props.parentId );
					this.$data.store.comments[ix].children.push( newComment );
				} else {
					// Top-level comment, just throw it to the top of the comments list
					this.$data.store.comments.unshift( newComment );
				}

				this.$props.onCancel();
			} ).fail( ( _, result ) => {
				if ( result.xhr.responseJSON && Object.prototype.hasOwnProperty.call(
					result.xhr.responseJSON, 'messageTranslations' ) ) {
					if ( result.xhr.responseJSON.errorKey === 'comments-submit-error-spam' ) {
						// If the comment was rejected for spam/abuse, add a small cooldown
						this.$data.store.globalCooldown = 10;
					}

					if ( config.wgContentLanguage in result.xhr.responseJSON.messageTranslations ) {
						error = result.xhr.responseJSON.messageTranslations[ config.wgContentLanguage ];
					} else {
						error = result.xhr.responseJSON.messageTranslations.en
					}
				} else {
					error = mw.Message( 'unknown-error' );
				}
				mw.notify( error, { type: 'error', tag: 'post-comment-error' } );
			} )
		}
	},
	data() {
		return {
			store,
			ve: null
		};
	},
	watch: {
		isWritingComment( val ) {
			const $input = $( this.$refs.input );
			if ( val === true && this.$data.ve === null && mw.commentsExt.ve.Editor.static.isSupported() ) {
				// Create the VE instance for this editor
				this.$data.ve = new mw.commentsExt.ve.Editor( $input, $input.val() );
			} else if ( val === true ) {
				if ( this.$data.ve ) {
					this.$data.ve.target.getSurface().getView().focus();
				} else {
					setTimeout(() => $input.focus(), 0);
				}
			} else {
				if ( this.$data.ve ) {
					// When we're no longer writing a comment, kill the VE instance
					this.$data.ve.target.destroy();
					this.$data.ve = null;
				} else {
					$input.val('');
				}
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
