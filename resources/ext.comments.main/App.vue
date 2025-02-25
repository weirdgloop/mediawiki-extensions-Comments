<template>
	<h3>{{ $i18n( 'comments-container-header' ).text() }}</h3>
	<comment-input></comment-input>
	<comments-list></comments-list>
</template>

<script>
const { defineComponent } = require( 'vue' );
const CommentInput = require( './CommentInput.vue' );
const CommentsList = require( './CommentsList.vue' );

module.exports = exports = defineComponent( {
	name: 'App',
	components: {
		CommentInput,
		CommentsList
	}
} );
</script>
