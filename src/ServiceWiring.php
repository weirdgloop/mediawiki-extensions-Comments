<?php

namespace MediaWiki\Extension\Yappin;

use MediaWiki\MediaWikiServices;

/**
 * Comments wiring for MediaWiki services.
 */
return [
	'Yappin.CommentFactory' => static function ( MediaWikiServices $services ): CommentFactory {
		return new CommentFactory(
			$services->getDBLoadBalancerFactory()
		);
	}
];
