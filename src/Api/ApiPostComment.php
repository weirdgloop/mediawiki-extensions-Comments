<?php

namespace MediaWiki\Extension\Comments\Api;

use MediaWiki\Extension\Comments\CommentFactory;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\Validator\JsonBodyValidator;
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

		$body = $this->getValidatedBody();
		$pageid = (int)$body[ 'pageid' ];

		$page = $this->titleFactory->newFromID( $pageid );
		if ( !$page || !$page->exists() ) {
			throw new HttpException( "Page with ID $pageid does not exist", 400 );
		}

		$html = trim( (string)$body[ 'html' ] );
		if ( !$html ) {
			throw new HttpException( 'Comment cannot be empty', 400 );
		}

		$parentId = (int)$body[ 'parentid' ];

		$parent = null;
		if ( $parentId ) {
			$parent = $this->commentFactory->newFromId( $parentId );

			if ( $parent->isDeleted() ) {
				throw new HttpException( "Parent comment $parentId does not exist", 400 );
			}
			if ( $parent->getParent() ) {
				throw new HttpException( "Cannot reply to a comment that already has a parent", 400 );
			}
		}

		// Create a new comment
		$comment = $this->commentFactory->newEmpty()
			->setTitle( $page )
			->setActor( $this->getAuthority()->getUser() )
			->setParent( $parent )
			->setHtml( $html );

		$comment->save();

		return $this->getResponseFactory()->createJson( [
			'comment' => $comment->toArray()
		] );
	}

	/**
	 * @inheritDoc
	 */
	public function getBodyValidator( $contentType ) {
		if ( $contentType !== 'application/json' ) {
			throw new HttpException( "Unsupported Content-Type",
				415,
				[ 'content_type' => $contentType ]
			);
		}

		return new JsonBodyValidator( [
			'pageid' => [
				self::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => true
			],
			'parentid' => [
				self::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => false
			],
			'html' => [
				self::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
			],
		] );
	}
}
