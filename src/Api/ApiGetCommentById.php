<?php

namespace MediaWiki\Extension\Comments\Api;

use MediaWiki\Extension\Comments\CommentsPager;
use MediaWiki\Extension\Comments\Utils;
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
	 * @var ActorStore
	 */
	private ActorStore $actorStore;

	/**
	 * @var IDatabase
	 */
	private $dbr;

	public function __construct(
		ActorStore $actorStore,
		LBFactory $factory
	) {
		$this->actorStore = $actorStore;
		$this->dbr = $factory->getReplicaDatabase();
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

		// Create a pager to get the children of this comment
		$pager = new CommentsPager(
			[
				'includeDeleted' => $showDeleted
			],
			$actor,
			null,
			$commentId,
			$params[ 'sort' ]
		);

		$res = $pager->getResult();
		if ( empty( $res ) ) {
			throw new LocalizedHttpException(
				new MessageValue( 'comments-generic-error-comment-missing', [ $commentId ] ), 400
			);
		}

		$targetComment = [];
		$children = [];
		foreach ( $res as $r ) {
			if ( $r['c']->mParentId !== null ) {
				$children[] = $r['c']->toArray() + [
					'userRating' => $r['ur'],
					'ours' => $r['ours']
				];
			} else {
				$targetComment = $r['c']->toArray() + [
					'children' => [],
					'userRating' => $r['ur'],
					'ours' => $r['ours']
				];

				if ( $r['c']->isDeleted() && $showDeleted === false ) {
					throw new LocalizedHttpException(
						new MessageValue( 'comments-generic-error-comment-missing', [ $commentId ] ), 400
					);
				}
			}
		}

		$targetComment[ 'children' ] = $children;

		return $this->getResponseFactory()->createJson( [
			'comment' => $targetComment,
			// Piggyback off this API call to return some extra info about the logged in user which the UI will use
			'isMod' => Utils::canUserModerate( $this->getAuthority() )
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
