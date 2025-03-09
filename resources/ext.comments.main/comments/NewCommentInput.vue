<template>
	<div class="comment-input-container">
		<div class="ve-area-wrapper">
			<textarea
				ref="input"
				rows="5"
			></textarea>
		</div>
		<div class="comment-input-actions">
			<cdx-button action="progressive" weight="primary">
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
		}
	},
	data() {
		return {
			store,
		};
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
