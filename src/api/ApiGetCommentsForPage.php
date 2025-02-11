<?php

namespace MediaWiki\Extension\Comments\Api;

use MediaWiki\Extension\Comments\CommentFactory;
use MediaWiki\Extension\Comments\CommentsHooks;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Title\TitleFactory;
use Wikimedia\ParamValidator\ParamValidator;

class ApiGetCommentsForPage extends SimpleHandler {
	/**
	 * @var TitleFactory
	 */
	private TitleFactory $titleFactory;

	/**
	 * @var CommentFactory
	 */
	private CommentFactory $commentFactory;

	public function __construct( TitleFactory $titleFactory, CommentFactory $commentFactory ) {
		$this->titleFactory = $titleFactory;
		$this->commentFactory = $commentFactory;
	}

	/**
	 * @throws HttpException
	 */
	public function run() {
		$params = $this->getValidatedParams();
		$pageid = $params[ 'pageid' ];

		$title = $this->titleFactory->newFromID( $pageid );
		if ( !$title || !$title->exists() ) {
			throw new HttpException( "Page with ID $pageid does not exist", 400 );
		}

		$showDeleted = CommentsHooks::canUserModerate( $this->getAuthority() );

		// TODO: pagination
		$comments = $this->commentFactory->getPageComments( $title, $showDeleted );
		$resp = $this->getResponseFactory()->createJson( [ 'comments' => $comments ] );

		return $resp;
	}

	public function getParamSettings() {
		return [
			'pageid' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => true
			]
		];
	}
}
