<?php

namespace MediaWiki\Extension\Yappin\Api;

use InvalidArgumentException;
use MediaWiki\Extension\Yappin\CommentFactory;
use MediaWiki\Extension\Yappin\CommentsPager;
use MediaWiki\Extension\Yappin\Models\Comment;
use MediaWiki\Extension\Yappin\Models\CommentRating;
use MediaWiki\Extension\Yappin\Utils;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\LocalizedHttpException;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\User\ActorStore;
use Wikimedia\Message\MessageValue;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\LBFactory;

class ApiGetCommentById extends SimpleHandler {
	/**
	 * @var CommentFactory
	 */
	private CommentFactory $commentFactory;

	/**
	 * @var ActorStore
	 */
	private ActorStore $actorStore;

	/**
	 * @var IDatabase
	 */
	private $dbr;

	public function __construct(
		CommentFactory $commentFactory,
		ActorStore $actorStore,
		LBFactory $factory
	) {
		$this->commentFactory = $commentFactory;
		$this->actorStore = $actorStore;
		$this->dbr = $factory->getReplicaDatabase();
	}

	/**
	 * @param object{ c: Comment, ur: CommentRating, ours: bool } $r
	 * @return array
	 */
	private function getCommentDataFromResult( $r ) {
		return $r['c']->toArray() + [
			'children' => [],
			'userRating' => $r[ 'ur' ],
			'ours' => $r[ 'ours' ],
			'page' => $r[ 'p' ]
		];
	}

	/**
	 * @throws HttpException
	 */
	public function run() {
		$params = $this->getValidatedParams();
		$commentId = $params[ 'commentid' ];
		$showDeleted = Utils::canUserModerate( $this->getAuthority() );

		// Do not use ActorStore::acquireActorId, otherwise a new actor ID will be made for every anonymous page view
		$actor = $this->actorStore->findActorId( $this->getAuthority()->getUser(), $this->dbr );

		try {
			$comment = $this->commentFactory->newFromId( $commentId );
		} catch ( InvalidArgumentException $ex ) {
			throw new LocalizedHttpException(
				new MessageValue( 'yappin-generic-error-comment-missing', [ $commentId ] ), 400
			);
		}

		if ( !Utils::canUserModerate( $this->getAuthority() )
			&& $comment->isDeleted() && $comment->getActor() !== $actor ) {
			throw new LocalizedHttpException(
				new MessageValue( 'yappin-generic-error-comment-missing', [ $commentId ] ), 400
			);
		}

		$parentId = $comment->mParentId;
		$targetId = $parentId === null ? $comment->getId() : $parentId;

		$pager = new CommentsPager(
			[
				'includeDeleted' => $showDeleted
			],
			$actor,
			$params[ 'sort' ],
		);

		$pager->setLimit( 1 );
		$res = $pager->fetchResultsForParent( $targetId );

		/** @var Comment[] $comments */
		$childComments = [];

		$parent = null;
		foreach ( $res as $r ) {
			$data = $this->getCommentDataFromResult( $r );
			if ( $r['c']->mParentId !== null ) {
				// If this is a child comment, add it to the child comments array for processing later
				$childComments[] = $data;
			} else {
				$parent = $data;
			}
		}

		return $this->getResponseFactory()->createJson( [
			'comment' => array_merge( $parent, [
				'children' => $childComments
			] ),
			// Piggyback off this API call to return some extra info about the logged in user which the UI will use
			'isMod' => $showDeleted
		] );
	}

	public function getParamSettings() {
		return [
			'commentid' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => true
			],
			'sort' => [
				self::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => [
					CommentsPager::SORT_RATING_ASC,
					CommentsPager::SORT_RATING_DESC,
					CommentsPager::SORT_DATE_ASC,
					CommentsPager::SORT_DATE_DESC
				],
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => CommentsPager::SORT_DATE_DESC
			]
		];
	}
}
