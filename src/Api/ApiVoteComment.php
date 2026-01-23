<?php

namespace MediaWiki\Extension\Yappin\Api;

use InvalidArgumentException;
use MediaWiki\Extension\Yappin\CommentFactory;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\LocalizedHttpException;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\User\TempUser\TempUserCreator;
use Wikimedia\Message\MessageValue;
use Wikimedia\ParamValidator\ParamValidator;

class ApiVoteComment extends SimpleHandler {
	private CommentFactory $commentFactory;
	private TempUserCreator $tempUserCreator;

	public function __construct( CommentFactory $commentFactory, TempUserCreator $tempUserCreator ) {
		$this->commentFactory = $commentFactory;
		$this->tempUserCreator = $tempUserCreator;
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
				new MessageValue( 'yappin-rating-error-invalid' ), 400
			);
		}

		try {
			$comment = $this->commentFactory->newFromId( $commentId );
		} catch ( InvalidArgumentException $ex ) {
			throw new LocalizedHttpException(
				new MessageValue( 'yappin-generic-error-comment-missing', [ $commentId ] ), 400
			);
		}

		if ( $comment->isDeleted() ) {
			throw new LocalizedHttpException(
				new MessageValue( 'yappin-generic-error-comment-missing', [ $commentId ] ), 400
			);
		}

		// Handle temporary users. Use "edit" action as it's the only one supported right now.
		$user = $this->getAuthority();
		if ( $this->tempUserCreator->shouldAutoCreate( $user, 'edit' ) ) {
			$status = $this->tempUserCreator->create(
				null,
				$this->getSession()->getRequest()
			);
			if ( $status->isOK() ) {
				$user = $status->getUser();
			} else {
				throw new LocalizedHttpException(
					new MessageValue( 'yappin-submit-error-tempusercreate' ), 400 );
			}
		}

		$rating = $comment->setRatingForUser( $user, $rating );

		return $this->getResponseFactory()->createJson( [
			'comment' => $rating->getComment()->toArray()
		] );
	}

	/**
	 * @inheritDoc
	 */
	public function getBodyParamSettings(): array {
		return [
			'rating' => [
				self::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => [ -1, 0, 1 ],
				ParamValidator::PARAM_REQUIRED => true
			],
		];
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
