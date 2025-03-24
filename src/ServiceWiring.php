<?php

namespace MediaWiki\Extension\Comments;

use MediaWiki\Extension\Comments\Files\CommentFileService;
use MediaWiki\MainConfigNames;
use MediaWiki\MediaWikiServices;

/**
 * Comments wiring for MediaWiki services.
 */
return [
	'Comments.CommentFactory' => static function ( MediaWikiServices $services ): CommentFactory {
		return new CommentFactory(
			$services->getDBLoadBalancerFactory()
		);
	},
	'Comments.CommentFileService' => static function ( MediaWikiServices $services ): CommentFileService {
		return new CommentFileService(
			$services->getFileBackendGroup(),
			$services->getMainConfig()->get( MainConfigNames::UploadDirectory ),
			$services->getMainConfig()->get( 'CommentsFileBackend' ),
		);
	}
];
