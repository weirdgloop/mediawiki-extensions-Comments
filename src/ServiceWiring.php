<?php

namespace MediaWiki\Extension\Yappin;

use MediaWiki\Extension\Yappin\Files\CommentFileService;
use MediaWiki\MainConfigNames;
use MediaWiki\MediaWikiServices;

/**
 * Comments wiring for MediaWiki services.
 */
return [
	'Yappin.CommentFactory' => static function ( MediaWikiServices $services ): CommentFactory {
		return new CommentFactory(
			$services->getDBLoadBalancerFactory()
		);
	},
	'Yappin.CommentFileService' => static function ( MediaWikiServices $services ): CommentFileService {
		return new CommentFileService(
			$services->getFileBackendGroup(),
			$services->getMainConfig()->get( MainConfigNames::UploadDirectory ),
			$services->getMainConfig()->get( 'CommentsFileBackend' ),
		);
	}
];
