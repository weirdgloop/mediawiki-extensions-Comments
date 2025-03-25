<?php

namespace MediaWiki\Extension\Comments\Api;

use InvalidArgumentException;
use MediaWiki\Extension\Comments\CommentFactory;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\LocalizedHttpException;
use MediaWiki\Rest\Validator\JsonBodyValidator;
use Wikimedia\Message\MessageValue;
use Wikimedia\ParamValidator\ParamValidator;

class ApiEditComment extends CommentApiHandler {
	/**
	 * @var CommentFactory
	 */
	private CommentFactory $commentFactory;

	public function __construct( CommentFactory $commentFactory ) {
		$this->commentFactory = $commentFactory;
	}

	/**
	 * @throws HttpException
	 */
	public function run() {
		parent::run();

		$body = $this->getValidatedBody();
		$params = $this->getValidatedParams();
		$commentId = (int)$params[ 'commentid' ];

		try {
			$comment = $this->commentFactory->newFromId( $commentId );
		} catch ( InvalidArgumentException $ex ) {
			throw new LocalizedHttpException(
				new MessageValue( 'comments-edit-error-comment-missing' ), 400
			);
		}

		if ( $comment->isDeleted() ) {
			throw new LocalizedHttpException(
				new MessageValue( 'comments-edit-error-comment-missing' ), 400
			);
		}
		if ( $comment->getActor()->getId() !== $this->getAuthority()->getUser()->getId() ) {
			throw new LocalizedHttpException(
				new MessageValue( 'comments-edit-error-notself' ), 400
			);
		}

		$html = $body[ 'html' ];
		$comment->setHtml( $html );

		$isSpam = $comment->checkSpamFilters();
		if ( $isSpam ) {
			throw new LocalizedHttpException(
				new MessageValue( 'comments-submit-error-spam' ), 400
			);
		}

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
			'html' => [
				self::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
			],
		] );
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSettings() {
		return [
			'commentid' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => true
			]
		];
	}
}
