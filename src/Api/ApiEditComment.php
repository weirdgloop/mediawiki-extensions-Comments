<?php

namespace MediaWiki\Extension\Comments\Api;

use InvalidArgumentException;
use MediaWiki\Extension\Comments\CommentFactory;
use MediaWiki\Extension\Comments\Utils;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\LocalizedHttpException;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\Validator\JsonBodyValidator;
use Wikimedia\Message\MessageValue;
use Wikimedia\ParamValidator\ParamValidator;

class ApiEditComment extends SimpleHandler {
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
		if ( $this->getRequest()->getMethod() === 'PUT' ) {
			return $this->runEditComment();
		} else {
			return $this->runDeleteComment();
		}
	}

	private function runEditComment() {
		$auth = $this->getAuthority();

		$canComment = Utils::canUserComment( $auth );
		if ( $canComment !== true ) {
			throw new LocalizedHttpException( $canComment, 403 );
		}

		$body = $this->getValidatedBody();
		$params = $this->getValidatedParams();
		$commentId = (int)$params[ 'commentid' ];

		$html = trim( (string)$body[ 'html' ] );
		$wikitext = trim( (string)$body[ 'wikitext' ] );

		if ( !$html && !$wikitext ) {
			throw new LocalizedHttpException(
				new MessageValue( 'comments-submit-error-empty' ), 400 );
		}

		try {
			$comment = $this->commentFactory->newFromId( $commentId );
		} catch ( InvalidArgumentException $ex ) {
			throw new LocalizedHttpException(
				new MessageValue( 'comments-generic-error-comment-missing', [ $commentId ] ), 400
			);
		}

		if ( $comment->isDeleted() ) {
			throw new LocalizedHttpException(
				new MessageValue( 'comments-generic-error-comment-missing', [ $commentId ] ), 400
			);
		}
		if ( $comment->getActor()->getId() !== $this->getAuthority()->getUser()->getId() ) {
			throw new LocalizedHttpException(
				new MessageValue( 'comments-generic-error-notself' ), 400
			);
		}

		if ( $html ) {
			$comment->setHtml( $html );
		} else {
			$comment->setWikitext( $wikitext );
		}

		$isSpam = $comment->checkSpamFilters();
		if ( $isSpam ) {
			throw new LocalizedHttpException(
				new MessageValue( 'comments-submit-error-spam' ), 400
			);
		}

		$comment->setEditedTimestamp( wfTimestamp( TS_ISO_8601 ) );
		$comment->save();

		return $this->getResponseFactory()->createJson( [
			'comment' => $comment->toArray()
		] );
	}

	private function runDeleteComment() {
		$body = $this->getValidatedBody();
		$params = $this->getValidatedParams();
		$commentId = (int)$params[ 'commentid' ];
		$delete = (bool)$body[ 'delete' ];

		try {
			$comment = $this->commentFactory->newFromId( $commentId );
		} catch ( InvalidArgumentException $ex ) {
			throw new LocalizedHttpException(
				new MessageValue( 'comments-generic-error-comment-missing', [ $commentId ] ), 400
			);
		}

		$ownComment = $comment->getActor()->getId() === $this->getAuthority()->getUser()->getId();
		$isMod = Utils::canUserModerate( $this->getAuthority() );

		if ( $ownComment || $isMod ) {
			if ( $comment->isDeleted() !== $delete ) {
				$comment->setDeleted( $delete );
				$comment->save();
			}
		} else {
			// Not user's own comment, no permission
			throw new LocalizedHttpException(
				new MessageValue( 'comments-generic-error-notself' ), 400
			);
		}

		return $this->getResponseFactory()->createJson( [
			'deleted' => $comment->isDeleted()
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

		if ( $this->getRequest()->getMethod() === 'PUT' ) {
			$body = [
				'html' => [
					self::PARAM_SOURCE => 'body',
					ParamValidator::PARAM_TYPE => 'string',
					ParamValidator::PARAM_REQUIRED => false
				],
				'wikitext' => [
					self::PARAM_SOURCE => 'body',
					ParamValidator::PARAM_TYPE => 'string',
					ParamValidator::PARAM_REQUIRED => false
				]
			];
		} else {
			$body = [
				'delete' => [
					self::PARAM_SOURCE => 'body',
					ParamValidator::PARAM_TYPE => 'boolean',
					ParamValidator::PARAM_REQUIRED => true
				]
			];
		}

		return new JsonBodyValidator( $body );
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
