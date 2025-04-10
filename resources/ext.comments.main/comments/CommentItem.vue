<template>
	<div class="ext-comments-comment-item" :class="{ 'is-highlighted': parseInt( store.singleComment ) === comment.id }" :data-comment-id="comment.id" :data-deleted="comment.deleted !== null">
		<div>
			<comment-rating :comment="comment" v-if="!comment.deleted"></comment-rating>
			<div class="comment-body">
				<div class="comment-header">
					<div class="comment-author-wrapper">
						<a class="comment-author" :href="userPageLink">
							{{ comment.user.anon ? $i18n( 'comments-anon' ) : comment.user.name }}
						</a>
						<div class="comment-info">
							<span
								class="comment-rating"
								:class="{
									'rating-positive': comment.rating > 0,
									'rating-negative': comment.rating < 0
								}"
							>{{ rating }}</span>
							&#183;
							<span class="comment-date" :title="comment.created">{{ date }}</span>
							<span class="comment-edited" v-if="comment.edited !== null">  {{ $i18n( 'comments-edited', editedDate ).text() }}</span>
							<span
								class="comment-page"
								v-if="targetPage"
							>
								&#183; <span v-html="targetPageText"></span>
							</span>
							<span
								class="comment-parent"
								v-if="!store.singleComment && store.isSpecialComments && comment.parent"
							>
								<span v-html="targetParentText"></span>
							</span>
						</div>
					</div>
					<div class="comment-actions">
						<comment-action
							v-if="!store.readOnly && !comment.deleted && comment.ours"
							class="comment-action-edit"
							:disabled="store.isEditing === comment.id"
							:icon="cdxIconEdit"
							:on-click="() => store.isEditing = comment.id"
							:title="$i18n( 'comments-action-label-edit' ).text()"
						></comment-action>
						<comment-action
							v-if="!store.readOnly && ( comment.ours && comment.deleted === null ) || store.isMod"
							class="comment-action-delete"
							:icon="comment.deleted ? cdxIconRestore : cdxIconTrash"
							:on-click="deleteComment"
							:title="$i18n(
							comment.deleted ? 'comments-action-label-undelete' : 'comments-action-label-delete'
						).text()"
						></comment-action>
						<comment-action
							v-if="!comment.deleted"
							class="comment-action-link"
							:on-click="linkComment"
							:icon="cdxIconLink"
							:title="$i18n( 'comments-action-label-link' ).text()"
						></comment-action>
					</div>
				</div>
				<edit-comment-input :comment="comment" v-if="store.isEditing === comment.id"></edit-comment-input>
				<div v-else class="comment-content" v-html="comment.html"></div>
				<div v-if="comment.children.length > 0" class="comment-children">
					<comment-item
						v-for="c in comment.children"
						:key="c.id"
						:comment="c"
						:parent-id="comment.id"
					></comment-item>
				</div>
				<new-comment-input
					v-if="!parentId"
					:parent-id="comment.id"
					:is-writing-comment="isWritingReply"
					:on-cancel="() => isWritingReply = false"
				></new-comment-input>
			</div>
		</div>
		<button
			v-if="!parentId && !isWritingReply && !comment.deleted"
			class="comment-reply-button"
			@click="isWritingReply = true"
		>
			<cdx-icon :icon="cdxIconShare" dir="rtl" size="small"></cdx-icon>
			<span>{{ $i18n( 'comments-post-placeholder-child' ) }}</span>
		</button>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );
const store = require( '../store.js' );
const Comment = require( '../comment.js' );
const CommentAction = require( './CommentAction.vue' );
const CommentRating = require( './CommentRating.vue' )
const NewCommentInput = require( '../comments/NewCommentInput.vue' );
const EditCommentInput = require( '../comments/EditCommentInput.vue' );
const { CdxIcon } = require( '@wikimedia/codex' );
const {
	cdxIconTrash, cdxIconLink, cdxIconEdit, cdxIconRestore, cdxIconShare
} = require( '../icons.json' );

const api = new mw.Rest();

module.exports = exports = defineComponent( {
	name: 'CommentItem',
	components: {
		NewCommentInput,
		EditCommentInput,
		CommentAction,
		CommentRating,
		CdxIcon
	},
	props: {
		comment: Comment,
		parentId: {
			type: Number,
			default: null,
			required: false
		}
	},
	data() {
		return {
			store,
			isWritingReply: false
		};
	},
	computed: {
		rating() {
			return mw.message( 'comments-rating',
				mw.language.convertNumber( this.comment.rating ),
				this.comment.rating
			);
		},
		date() {
			return moment( this.comment.created ).fromNow();
		},
		editedDate() {
			return moment( this.comment.edited ).fromNow();
		},
		userPageLink() {
			const title = new mw.Title( this.comment.user.name, 2 ); // 2 = User
			return title.getUrl();
		},
		/**
		 * @returns {mw.Title|null}
		 */
		targetPage() {
			if ( this.comment.page && this.store.isSpecialComments ) {
				return new mw.Title( this.comment.page.title, this.comment.page.ns );
			}

			return null;
		},
		targetPageText() {
			return mw.message( 'comments-page-link',
				`<a href="${this.targetPage.getUrl()}">${this.targetPage.getPrefixedText()}</a>` )
		},
		targetParentText() {
			const url = new URL( document.location );
			url.hash = `comment=${this.comment.parent}`;

			return mw.message( 'comments-parent-link',
				`<a href="${url}">${ mw.message( 'comments-parent-link-inner' ) }</a>`
			);
		}
	},
	methods: {
		deleteComment() {
			api.delete( `/comments/v0/comment/${this.$props.comment.id}/edit`, {
				delete: !this.$props.comment.deleted
			} ).then( ( data ) => {
				this.$props.comment.deleted = data.deleted;
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
		},
		linkComment() {
			const url = new URL(window.location);
			url.hash = 'comment=' + this.$props.comment.id;
			navigator.clipboard.writeText( url.href );
			mw.notify( mw.msg( 'comments-action-link-copied' ), { tag: 'copy-comment' } );
		}
	},
	setup() {
		return {
			cdxIconTrash,
			cdxIconLink,
			cdxIconEdit,
			cdxIconRestore,
			cdxIconShare
		}
	}
} );
</script>
