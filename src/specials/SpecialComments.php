<?php

namespace MediaWiki\Extension\Comments\Specials;

use MediaWiki\SpecialPage\SpecialPage;

class SpecialComments extends SpecialPage {
	public function __construct() {
		parent::__construct( 'Comments' );
	}

	public function execute( $par ) {
		$out = $this->getOutput();
		$this->setHeaders();

		$out->addWikiMsg( 'comments-no-js' );
	}

	/**
	 * @return string
	 */
	public function getGroupName() {
		return 'pages';
	}
}
