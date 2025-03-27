<template>
	<div class="ext-comments-comments-list">
		<comment-item
			v-for="c in store.comments"
			:key="c.id"
			:comment="c"
		></comment-item>
		<button
			v-if="moreContinue"
			class="comment-info-full"
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
			class="comment-info-full"
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
	data() {
		return {
			store,
			// For tracking whether the user has already made a first API call, when this element is in view
			initialLoad: false,
			moreContinue: null,
			error: null
		};
	},
	methods: {
		resetComments() {
			this.$data.store.comments = [];
			this.$data.moreContinue = null;
			this.loadComments();
		},
		loadComments() {
			this.$data.error = null;

			if ( this.$data.store.singleComment ) {
				// Attempt to get the requested comment so that we can display it
				api.get(`/comments/v0/comment/${ this.$data.store.singleComment }?sort=${ this.$data.store.sortMethod }` )
					.done( ( res ) => {
						this.$data.store.comments = [ new Comment( res.comment ) ];
						this.$data.store.isMod = res.isMod;
					} )
					.fail( ( _, data ) => {
						if ( data && data.xhr && data.xhr.status ) {
							this.$data.error = data.xhr.status;
						} else {
							this.$data.error = true;
						}
					} )
			} else {
				// Get a list of all comments for the current page
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
						this.$data.store.isMod = res.isMod;
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
		checkVisible() {
			if ( isElementInView( this.$el ) && this.$data.store.ready && !this.$data.initialLoad ) {
				this.$data.initialLoad = true;
				this.loadComments();
			}
		}
	},
	watch: {
		'store.sortMethod': {
			immediate: false,
			handler() {
				// When the sort method changes, reset the list and make a request again
				this.resetComments();
			}
		},
		'store.ready': function( val ) {
			if ( val === true ) {
				this.checkVisible();
			}
		},
		'store.singleComment': {
			immediate: false,
			handler( oldVal, newVal ) {
				if ( oldVal !== newVal && newVal !== null ) {
					this.resetComments();
				}
			}
		}
	},
	mounted() {
		$( window ).on( 'DOMContentLoaded load resize scroll', this.checkVisible );
	}
} );
</script>
