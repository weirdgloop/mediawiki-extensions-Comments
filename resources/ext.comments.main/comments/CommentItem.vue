<template>
	<div class="ext-comments-comment-item" :data-comment-id="comment.id">
		<comment-rating :comment="comment"></comment-rating>
		<div class="comment-body">
			<div class="comment-header">
				<div class="comment-author-wrapper">
					<a class="comment-author" :href="userPageLink">{{ comment.user }}</a>
					<div class="comment-info">
						<span
							class="comment-rating"
							:class="{
								'rating-positive': comment.rating > 0,
								'rating-negative': comment.rating < 0
							}"
						>{{ rating }}</span>
						&#183;
						<span class="comment-date" :title="this.comment.created">{{ date }}</span>
						<span class="comment-edited" v-if="comment.edited !== null">  {{ $i18n( 'comments-edited', editedDate ).text() }}</span>
					</div>
				</div>
				<div class="comment-actions">
					<comment-action
						v-if="!store.readOnly && comment.ours"
						:disabled="store.isEditing === comment.id"
						:icon="cdxIconEdit"
						:on-click="() => store.isEditing = comment.id"
						:title="$i18n( 'comments-action-label-edit' ).text()"
					></comment-action>
					<comment-action
						v-if="!store.readOnly"
						:icon="cdxIconTrash"
						:title="$i18n( 'comments-action-label-delete' ).text()"
					></comment-action>
					<comment-action
						:icon="cdxIconLink"
						:title="$i18n( 'comments-action-label-link' ).text()"
					></comment-action>
				</div>
			</div>
			<edit-comment-input :comment="comment" :value="comment.html" v-if="store.isEditing === comment.id"></edit-comment-input>
			<div v-else class="comment-content" v-html="comment.html"></div>
			<div v-if="comment.children.length > 0" class="comment-children">
				<comment-item
					v-for="c in comment.children"
					:key="c.id"
					:comment="c"
					:parent-id="comment.id"
				></comment-item>
			</div>
			<new-comment-input v-if="!parentId" :parent-id="comment.id"></new-comment-input>
		</div>
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
const { cdxIconTrash, cdxIconLink, cdxIconEdit } = require( '../icons.json' );

module.exports = exports = defineComponent( {
	name: 'CommentItem',
	components: {
		NewCommentInput,
		EditCommentInput,
		CommentAction,
		CommentRating
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
			const title = new mw.Title( this.comment.user, 2 ); // 2 = User
			return title.getUrl();
		}
	},
	setup() {
		return {
			cdxIconTrash,
			cdxIconLink,
			cdxIconEdit
		}
	}
} );
</script>
