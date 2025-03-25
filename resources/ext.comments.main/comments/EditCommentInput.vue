<template>
	<div class="comment-input-container">
		<div>
			<div class="ve-area-wrapper">
				<textarea
					ref="input"
					rows="5"
				></textarea>
			</div>
			<div class="comment-input-actions">
				<cdx-button :disabled="store.globalCooldown" action="progressive" weight="primary" @click="submitComment">
					<span v-if="store.globalCooldown">{{ $i18n( 'comments-submit-cooldown', store.globalCooldown ).text() }}</span>
					<span v-else>{{ $i18n( 'comments-post-edit' ).text() }}</span>
				</cdx-button>
				<cdx-button action="destructive" @click="store.isEditing = null">
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
	'wgContentLanguage'
] );

module.exports = exports = defineComponent( {
	name: 'EditCommentInput',
	components: {
		CdxButton
	},
	props: {
		comment: {
			type: Comment,
			default: null,
			required: true
		},
		value: {
			type: String,
			default: '',
			required: false
		}
	},
	methods: {
		submitComment() {
			const html = this.$data.ve.target.getSurface().getHtml()

			// We're going to pass the raw HTML from VE to our API. However, the API will parse it using Parsoid
			// which will sanitize it before saving it in the database.
			api.put( `/comments/v0/comment/${this.$props.comment.id}`, {
				html: html
			} ).then( ( data ) => {
				const newComment = new Comment( data.comment );
				this.$props.comment.html = newComment.html;
				this.$props.comment.wikitext = newComment.wikitext;
				this.$props.comment.edited = newComment.edited;
				this.$data.store.isEditing = null;
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
