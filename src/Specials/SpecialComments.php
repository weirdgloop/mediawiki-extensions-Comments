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

		$out->addHTML( '<div id="ext-comments-container"></div>' );
		$out->addModules( 'ext.comments.main' );
	}

	/**
	 * @return string
	 */
	public function getGroupName() {
		return 'pages';
	}
}
