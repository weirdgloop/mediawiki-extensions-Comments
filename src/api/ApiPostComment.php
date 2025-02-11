<?php

namespace MediaWiki\Extension\Comment\Api;

use MediaWiki\Extension\Comments\Api\CommentApiHandler;
use MediaWiki\Extension\Comments\CommentFactory;
use MediaWiki\Rest\HttpException;
use MediaWiki\Title\TitleFactory;
use Wikimedia\ParamValidator\ParamValidator;

class ApiPostComment extends CommentApiHandler {
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
		parent::run();

		$params = $this->getValidatedParams();
		$pageid = $params[ 'pageid' ];

		$page = $this->titleFactory->newFromID( $pageid );
		if ( !$page || !$page->exists() ) {
			throw new HttpException( "Page with ID $pageid does not exist", 400 );
		}

		$text = $params[ 'text' ];
		$parentId = $params[ 'parentid' ];

		$parent = null;
		if ( $parentId ) {
			$parent = $this->commentFactory->newFromId( $parentId );

			if ( $parent->isDeleted() ) {
				throw new HttpException( "Parent comment $parentId does not exist", 400 );
			}
		}

		// Create a new comment
		$comment = $this->commentFactory->newEmpty()
			->setTitle( $page )
			->setUser( $this->getAuthority()->getUser() )
			->setParent( $parent )
			->setWikitext( $text );

		$comment->save();
	}

	public function getParamSettings() {
		return [
			'pageid' => [
				self::PARAM_SOURCE => 'post',
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => true
			],
			'parentid' => [
				self::PARAM_SOURCE => 'post',
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => false
			],
			'text' => [
				self::PARAM_SOURCE => 'post',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
			]
		];
	}
}
