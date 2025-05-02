( function ( $, mw, OO, ve ) {
	'use strict';

	/**
	 * @class
	 * @constructor
	 */
	mw.commentsExt.ve.MWWikitextWarningCommand = function () {
		mw.commentsExt.ve.MWWikitextWarningCommand.super.call(
			this, 'mwWikitextWarning'
		);
		this.warning = null;
	};

	OO.inheritClass( mw.commentsExt.ve.MWWikitextWarningCommand, ve.ui.MWWikitextWarningCommand );

	/**
	 * @inheritDoc
	 */
	mw.commentsExt.ve.MWWikitextWarningCommand.prototype.execute = function () {
		var command = this;
		if ( this.warning && this.warning.isOpen ) {
			return false;
		}
		// eslint-disable-next-line no-jquery/no-html
		var $message = $( '<div>' ).html( ve.init.platform.getParsedMessage( 'yappin-visualeditor-wikitext-warning' ) );
		ve.targetLinksToNewWindow( $message[ 0 ] );
		ve.init.platform.notify(
			$message.contents(),
			ve.msg( 'visualeditor-wikitext-warning-title' ),
			{ tag: 'yappin-visualeditor-wikitext-warning' }
		).then( function ( message ) {
			command.warning = message;
		} );
		return true;
	};

}( jQuery, mediaWiki, OO, ve ) );
