<template>
	<div class="comment-list-toolbar">
		<h3 :style="{ 'visibility': store.isSpecialComments ? 'hidden' : null }">
			{{ $i18n( 'yappin-container-header' ).text()  }}
		</h3>
		<div class="comment-list-options">
			<div class="comment-list-option-sort-method">
				<cdx-field>
					<cdx-select
						v-model:selected="store.sortMethod"
						:menu-items="sortOptions"
					></cdx-select>
					<template #label>
						{{ $i18n( 'yappin-sort-label' ).text() }}
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
			{ label: mw.message( 'yappin-sort-highest-rated' ), value: 'sort_rating_desc' },
			{ label: mw.message( 'yappin-sort-newest' ), value: 'sort_date_desc' },
			{ label: mw.message( 'yappin-sort-oldest' ), value: 'sort_date_asc' },
		];

		return {
			sortOptions,
			cdxIconSpeechBubbleAdd,
		};
	}
} );
</script>
