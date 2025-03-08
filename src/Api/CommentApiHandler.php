<?php

namespace MediaWiki\Extension\Comments\Api;

use MediaWiki\Extension\Comments\Utils;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\SimpleHandler;

class CommentApiHandler extends SimpleHandler {
	/**
	 * @throws HttpException
	 */
	public function run() {
		$auth = $this->getAuthority();
		if ( !Utils::canUserComment( $auth ) ) {
			throw new HttpException( 'No permission', 403 );
		}
	}
}
