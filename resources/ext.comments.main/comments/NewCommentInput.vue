<template>
	<div class="comment-input-container">
		<div class="ve-area-wrapper">
			<textarea
				ref="input"
				rows="5"
			></textarea>
		</div>
		<div class="comment-input-actions">
			<cdx-button action="progressive" weight="primary" @click="submitComment">
				{{ $i18n( 'comments-post-submit' ).text() }}
			</cdx-button>
			<cdx-button action="destructive" @click="store.toggleWritingTopLevelComment()">
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
		commentId: {
			type: Number,
			default: null,
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

			if ( this.$props.commentId !== null ) {
				// Editing an existing comment
			} else {
				// Creating a new comment
				api.post( '/comments/v0/comment', {
					pageid: config.wgArticleId,
					parentid: this.$props.parentId,
					html: html
				} ).then( ( data ) => {
					this.$data.store.comments.unshift( new Comment( data.comment ) );
					store.toggleWritingTopLevelComment();
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
		}
	},
	data() {
		return {
			store,
			ve: null
		};
	},
	mounted() {
		const $input = $( this.$refs.input );

		if ( this.$props.value !== '' ) {
			$input.val( this.$props.value );
		}

		// Create the VE instance for this editor
		this.$data.ve = new mw.commentsExt.ve.Editor( $input, $input.val() );
	}
} );
</script>
