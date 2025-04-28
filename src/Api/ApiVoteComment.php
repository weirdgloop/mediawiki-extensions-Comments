<?php

namespace MediaWiki\Extension\Comments\Api;

use InvalidArgumentException;
use MediaWiki\Extension\Comments\CommentFactory;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\LocalizedHttpException;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\Validator\JsonBodyValidator;
use Wikimedia\Message\MessageValue;
use Wikimedia\ParamValidator\ParamValidator;

class ApiVoteComment extends SimpleHandler {
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
		$body = $this->getValidatedBody();
		$params = $this->getValidatedParams();

		$commentId = (int)$params[ 'commentid' ];
		$rating = (int)$body[ 'rating' ];

		// Should be a valid value, otherwise fail the call
		if ( $rating !== -1 && $rating !== 0 && $rating !== 1 ) {
			throw new LocalizedHttpException(
				new MessageValue( 'comments-rating-error-invalid' ), 400
			);
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

		$user = $this->getAuthority()->getUser();

//		if ( $comment->getUser()->getId() === $user->getId() ) {
//			throw new HttpException( "Cannot vote on user's own comment", 400 );
//		}

		$rating = $comment->setRatingForUser( $user, $rating );

		return $this->getResponseFactory()->createJson( [
			'comment' => $rating->getComment()->toArray()
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
			'rating' => [
				self::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => [ -1, 0, 1 ],
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
