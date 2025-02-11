<?php

namespace MediaWiki\Extension\Comments\Api;

use MediaWiki\Extension\Comments\CommentsHooks;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\SimpleHandler;

class CommentApiHandler extends SimpleHandler {
	/**
	 * @throws HttpException
	 */
	public function run() {
		$auth = $this->getAuthority();
		if ( !CommentsHooks::canUserComment( $auth ) ) {
			throw new HttpException( 'No permission to comment', 403 );
		}
	}
}
