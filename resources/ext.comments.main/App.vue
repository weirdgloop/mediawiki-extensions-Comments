<template>
	<h3>Comments</h3>
	<comments-list></comments-list>
</template>

<script>
const { defineComponent } = require( 'vue' );
const CommentsList = require( './CommentsList.vue' );

module.exports = exports = defineComponent( {
	name: 'App',
	components: {
		CommentsList
	}
} );
</script>
