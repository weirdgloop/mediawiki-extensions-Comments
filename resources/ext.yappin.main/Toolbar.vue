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
						v-on:update:selected="store.setSortMethod"
						:menu-items="SORT_OPTIONS"
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
const { defineComponent } = require( 'vue' );
const { CdxSelect, CdxField, CdxButton, CdxIcon } = require( '@wikimedia/codex' );
const store = require( './store.js' );
const { cdxIconSpeechBubbleAdd } = require( '../icons.json' );
const { SORT_OPTIONS } = require( './util.js' );

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
		return {
			SORT_OPTIONS,
			cdxIconSpeechBubbleAdd,
		};
	}
} );
</script>
