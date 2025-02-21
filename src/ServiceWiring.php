<?php

namespace MediaWiki\Extension\Comments;

use MediaWiki\MediaWikiServices;

/**
 * Comments wiring for MediaWiki services.
 */
return [
	'Comments.CommentFactory' => static function ( MediaWikiServices $services ): CommentFactory {
		return new CommentFactory(
			$services->getDBLoadBalancerFactory(),
			$services->getTitleFactory(),
			$services->getActorStore()
		);
	}
];
