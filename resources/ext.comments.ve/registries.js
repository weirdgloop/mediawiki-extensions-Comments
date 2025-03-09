/**
 * The code in this file creates new registries for VE so that we can override specific behaviour without impacting
 * the normal VisualEditor.
 *
 * Adapted from the DiscussionTools extension
 */

// Adapted from ve.ui.MWWikitextDataTransferHandlerFactory
function importRegistry( parent, child ) {
	var name;
	// Copy existing items
	for ( name in parent.registry ) {
		child.register( parent.registry[ name ] );
	}
	// Copy any new items when they're added
	parent.on( 'register', function ( n, data ) {
		child.register( data );
	} );
}

const sequenceRegistry = new ve.ui.SequenceRegistry();
importRegistry( ve.ui.sequenceRegistry, sequenceRegistry );

// Disable headings 1-6, as these should not appear in the context of a comment
sequenceRegistry.unregister( 'wikitextHeading' );

module.exports = {
	sequenceRegistry
}
