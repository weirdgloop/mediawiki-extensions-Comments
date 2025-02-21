<?php

namespace MediaWiki\Extension\Comments\Api;

use InvalidArgumentException;
use MediaWiki\Extension\Comments\CommentFactory;
use MediaWiki\Extension\Comments\Utils;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\Validator\JsonBodyValidator;
use Wikimedia\ParamValidator\ParamValidator;

class ApiDeleteComment extends CommentApiHandler {
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
		$commentId = (int)$body[ 'commentid' ];
		$delete = (bool)$body[ 'delete' ];

		try {
			$comment = $this->commentFactory->newFromId( $commentId );
		} catch ( InvalidArgumentException $ex ) {
			throw new HttpException( "Comment does not exist", 400 );
		}
		if ( $comment->isDeleted() && $delete ) {
			throw new HttpException( "Comment does not exist", 400 );
		}

		$ownComment = $comment->getUser()->getId() === $this->getAuthority()->getUser()->getId();
		$isMod = Utils::canUserModerate( $this->getAuthority() );

		if ( $ownComment || $isMod ) {
			if ( $comment->isDeleted() === $delete ) {
				throw new HttpException( "No changes to be made", 400 );
			}
			$comment->setDeleted( $delete );
			$comment->save();
		} else {
			// Not user's own comment, no permission
			throw new HttpException( "No permission", 400 );
		}

		if ( !$ownComment && $isMod ) {
			// TODO: if we're a moderator and this isn't our own comment, then also log the deletion?
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

		return new JsonBodyValidator( [
			'commentid' => [
				self::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => true
			],
			'delete' => [
				self::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'boolean',
				ParamValidator::PARAM_REQUIRED => true
			]
		] );
	}
}
