<template>
	<div class="ext-comments-comment-item">
		<rating-action :comment-id="comment.id"></rating-action>
		<div class="comment-body">
			<div class="comment-header">
				<div class="comment-author-wrapper">
					<div class="comment-author" :data-actor-id="comment.actor.id">
						<a :href="userPageLink">{{ comment.actor.name }}</a>
					</div>
					<div class="comment-info">
						<span
							class="comment-rating"
							:class="{
								'rating-positive': comment.rating > 0,
								'rating-negative': comment.rating < 0
							}"
						>{{ rating }}</span>
						&#183;
						<span class="comment-date">{{ date }}</span>
					</div>
				</div>
				<div class="comment-actions"></div>
			</div>
			<div class="comment-content" v-html="comment.html"></div>
			<div v-if="comment.children.length > 0" class="comment-children">
				<comment-item
					v-for="c in comment.children"
					:key="c.id"
					:comment="c"
				></comment-item>
			</div>
		</div>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );
const Comment = require( './comment.js' );
const RatingAction = require( './actions/RatingAction.vue' );

module.exports = exports = defineComponent( {
	name: 'CommentItem',
	components: {
		RatingAction
	},
	props: {
		comment: Comment
	},
	computed: {
		rating() {
			return mw.message( 'comments-rating',
				mw.language.convertNumber( this.comment.rating ),
				this.comment.rating
			);
		},
		date() {
			return moment( this.comment.timestamp ).fromNow();
		},
		userPageLink() {
			const title = new mw.Title( this.comment.actor.name, 2 ); // 2 = User
			return title.getUrl();
		}
	}
} );
</script>
