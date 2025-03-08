<template>
	<h3>{{ $i18n( 'comments-container-header' ).text() }}</h3>
	<comment-input v-if="!readOnly"></comment-input>
	<div class="comment-list-options">
		<div class="comment-list-option-sort-method">
			<cdx-field>
				<cdx-select
					v-model:selected="sortSelection"
					:menu-items="sortOptions"
				></cdx-select>
				<template #label>
					{{ $i18n( 'comments-sort-label' ).text() }}
				</template>
			</cdx-field>
		</div>
	</div>
	<comments-list :sort-method="sortSelection"></comments-list>
</template>

<script>
const { defineComponent, ref } = require( 'vue' );
const { CdxSelect, CdxField } = require( '@wikimedia/codex' );
const CommentInput = require( './CommentInput.vue' );
const CommentsList = require( './CommentsList.vue' );

module.exports = exports = defineComponent( {
	name: 'App',
	components: {
		CommentInput,
		CommentsList,
		CdxSelect,
		CdxField
	},
	setup() {
		const sortOptions = [
			{ label: mw.message( 'comments-sort-newest' ), value: 'sort_date_desc' },
			{ label: mw.message( 'comments-sort-oldest' ), value: 'sort_date_asc' },
			{ label: mw.message( 'comments-sort-highest-rated' ), value: 'sort_rating_desc' },
			{ label: mw.message( 'comments-sort-lowest-rated' ), value: 'sort_rating_asc' }
		];

		const readOnly = mw.config.get( 'wgComments' ).readOnly;
		const sortSelection = ref( sortOptions[ 0 ].value );

		return {
			readOnly,
			sortSelection,
			sortOptions
		};
	}
} );
</script>
