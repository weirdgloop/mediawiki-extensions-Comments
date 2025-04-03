<template>
	<div class="comment-list-toolbar">
		<h3 :style="{ 'visibility': store.isSpecialComments ? 'hidden' : null }">
			{{ $i18n( 'comments-container-header' ).text()  }}
		</h3>
		<div class="comment-list-options">
			<div class="comment-list-option-sort-method">
				<cdx-field>
					<cdx-select
						v-model:selected="store.sortMethod"
						:menu-items="sortOptions"
					></cdx-select>
					<template #label>
						{{ $i18n( 'comments-sort-label' ).text() }}
					</template>
				</cdx-field>
			</div>
		</div>
	</div>
</template>

<script>
const { defineComponent, ref } = require( 'vue' );
const { CdxSelect, CdxField, CdxButton, CdxIcon } = require( '@wikimedia/codex' );
const store = require( './store.js' );
const { cdxIconSpeechBubbleAdd } = require( '../icons.json' );

module.exports = exports = defineComponent( {
	name: 'Toolbar',
	components: {
		CdxSelect,
		CdxField,
		CdxButton,
		CdxIcon
	},
	data() {
		return {
			store,
		};
	},
	setup() {
		const sortOptions = [
			{ label: mw.message( 'comments-sort-highest-rated' ), value: 'sort_rating_desc' },
			{ label: mw.message( 'comments-sort-newest' ), value: 'sort_date_desc' },
			{ label: mw.message( 'comments-sort-oldest' ), value: 'sort_date_asc' },
		];

		return {
			sortOptions,
			cdxIconSpeechBubbleAdd,
		};
	}
} );
</script>
