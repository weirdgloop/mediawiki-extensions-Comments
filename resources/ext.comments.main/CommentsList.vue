<template>
	<div class="ext-comments-comments-list">
		<comment-item
			v-for="c in store.comments"
			:key="c.id"
			:comment="c"
		></comment-item>
		<button
			v-if="moreContinue"
			class="comment-list-footer"
			@click="loadComments"
		>
			{{ $i18n( 'comments-continue' ).text() }}
		</button>
		<div
			v-if="error"
			class="mw-message-box mw-message-box-error"
		>
			{{ $i18n( 'comments-load-error', error ).text() }}
		</div>
		<div
			v-else-if="$data.initialLoad && !store.comments.length"
			class="comment-list-footer"
		>
			{{ $i18n( 'comments-empty' ).text() }}
		</div>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );
const { isElementInView } = require( './util.js' );
const store = require( './store.js' );
const Comment = require( './comment.js' );
const CommentItem = require( './comments/CommentItem.vue' );

const api = new mw.Rest();

const config = mw.config.get( [
	'wgArticleId',
	'wgComments'
] );

module.exports = exports = defineComponent( {
	name: 'CommentsList',
	components: {
		CommentItem
	},
	props: {
		sortMethod: {
			type: String,
			default: '',
			required: true
		}
	},
	data() {
		return {
			store,
			initialLoad: false,
			moreContinue: null,
			error: null
		};
	},
	methods: {
		loadComments() {
			this.$data.error = null;

			const qsp = new URLSearchParams( {
				limit: config.wgComments.resultsPerPage,
				sort: this.$data.store.sortMethod
			} );
			if ( this.$data.moreContinue ) {
				qsp.set( 'continue', this.$data.moreContinue );
			}

			api.get( `/comments/v0/page/${ config.wgArticleId }?${ qsp.toString() }` )
				.done( ( res ) => {
					const comments = [];
					for ( const data of res.comments ) {
						comments.push( new Comment( data ) );
					}
					this.$data.store.comments = this.$data.store.comments.concat( comments );
					this.$data.moreContinue = res.query.continue;
				} )
				.fail( ( _, data ) => {
					if ( data && data.xhr && data.xhr.status ) {
						this.$data.error = data.xhr.status;
					} else {
						this.$data.error = true;
					}
				} )
		}
	},
	watch: {
		'store.sortMethod': {
			immediate: false,
			handler() {
				// When the sort method changes, reset the list and make a request again
				this.$data.store.comments = [];
				this.$data.moreContinue = null;
				this.loadComments();
			}
		}
	},
	mounted() {
		const checkVisible = () => {
			if ( isElementInView( this.$el ) && !this.$data.initialLoad ) {
				this.$data.initialLoad = true;
				this.loadComments();
			}
		};

		checkVisible();
		$( window ).on( 'DOMContentLoaded load resize scroll', checkVisible );
	}
} );
</script>
