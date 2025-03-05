<template>
	<h3>{{ $i18n( 'comments-container-header' ).text() }}</h3>
	<comment-input></comment-input>
	<div class="comment-list-options">
		<div class="comment-list-option-sort-method">
			<cdx-field>
				<cdx-select
					v-model:selected="sortSelection"
					:menu-items="sortOptions"
				></cdx-select>
				<template #label>
					Sort by
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

const sortOptions = [
	{ label: 'Newest', value: 'sort_date_desc' },
	{ label: 'Oldest', value: 'sort_date_asc' },
	{ label: 'Highest rated', value: 'sort_rating_desc' },
	{ label: 'Lowest rated', value: 'sort_rating_asc' }
];

module.exports = exports = defineComponent( {
	name: 'App',
	components: {
		CommentInput,
		CommentsList,
		CdxSelect,
		CdxField
	},
	setup() {
		const sortSelection = ref( sortOptions[ 0 ].value );

		return {
			sortSelection,
			sortOptions
		};
	}
} );
</script>
