<?php

namespace MediaWiki\Extension\Comments\Api;

use MediaWiki\Extension\Comments\CommentFactory;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\LocalizedHttpException;
use MediaWiki\Rest\Validator\JsonBodyValidator;
use MediaWiki\Title\TitleFactory;
use Wikimedia\Message\MessageValue;
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
			throw new LocalizedHttpException(
				new MessageValue( 'comments-submit-error-page-missing', $pageid ), 400 );
		}

		$html = trim( (string)$body[ 'html' ] );
		if ( !$html ) {
			throw new LocalizedHttpException(
				new MessageValue( 'comments-submit-error-empty' ), 400 );
		}

		$parentId = (int)$body[ 'parentid' ];

		$parent = null;
		if ( $parentId ) {
			$parent = $this->commentFactory->newFromId( $parentId );

			if ( $parent->isDeleted() ) {
				throw new LocalizedHttpException(
					new MessageValue( 'comments-submit-error-parent-missing', $parentId ), 400 );
			}
			if ( $parent->getParent() ) {
				throw new LocalizedHttpException(
					new MessageValue( 'comments-submit-error-parent-hasparent' ), 400 );
			}
		}

		// Create a new comment
		$comment = $this->commentFactory->newEmpty()
			->setTitle( $page )
			->setActor( $this->getAuthority()->getUser() )
			->setParent( $parent )
			->setHtml( $html );

		$isSpam = $comment->checkSpamFilters();
		if ( $isSpam ) {
			throw new LocalizedHttpException(
				new MessageValue( 'comments-submit-error-spam' ), 400
			);
		}

		$comment->save();

		return $this->getResponseFactory()->createJson( [
			'comment' => $comment->toArray(),
			'spam' => $isSpam
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
