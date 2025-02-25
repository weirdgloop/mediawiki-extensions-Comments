<template>
	<div class="add-comment-container">
		<div class="ve-area-wrapper">
			<textarea
				ref="input"
				rows="5"
				:placeholder="$i18n( 'comments-add-comment-placeholder' ).text()"
			></textarea>
		</div>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );

module.exports = exports = defineComponent( {
	name: 'CommentInput',
	props: {
		value: {
			type: String,
			default: '',
			required: false
		}
	},
	mounted() {
		const $input = $( this.$refs.input );

		if ( this.$props.value !== '' ) {
			$input.val( this.$props.value );
		}

		$input.applyVisualEditor();
		$input.trigger( 'focus' );
	}
} );
</script>
