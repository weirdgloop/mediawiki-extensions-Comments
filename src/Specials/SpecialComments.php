<?php

namespace MediaWiki\Extension\Comments\Specials;

use MediaWiki\Extension\Comments\Utils;
use MediaWiki\SpecialPage\SpecialPage;

/**
 * Implement Special:Comments. It is essentially a dummy special page that simply loads our module, which then fetches
 * all of the comments on the wiki. Users without JS enabled will see a message telling them to enable it.
 */
class SpecialComments extends SpecialPage {
	public function __construct() {
		parent::__construct( 'Comments' );
	}

	/**
	 * @param string $subPage
	 * @return void
	 */
	public function execute( $subPage ) {
		$out = $this->getOutput();
		$this->setHeaders();

		Utils::loadCommentsModule( $out );
		$out->addHTML(
			'<noscript>' . $out->msg( 'comments-no-script' )->text() . '</noscript>'
		);
	}

	/**
	 * @return string
	 */
	public function getGroupName() {
		return 'pages';
	}
}
