<?php

namespace MediaWiki\Extension\Comments\Api;

use MediaWiki\Extension\Comments\CommentsPager;
use MediaWiki\Extension\Comments\Models\Comment;
use MediaWiki\Extension\Comments\Models\CommentRating;
use MediaWiki\Extension\Comments\Utils;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\ActorStore;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\LBFactory;

class ApiGetAllComments extends SimpleHandler {
	/**
	 * @var TitleFactory
	 */
	private TitleFactory $titleFactory;

	/**
	 * @var ActorStore
	 */
	private ActorStore $actorStore;

	/**
	 * @var IDatabase
	 */
	private $dbr;

	public function __construct(
		TitleFactory $titleFactory,
		ActorStore $actorStore,
		LBFactory $factory
	) {
		$this->titleFactory = $titleFactory;
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
		$pageid = $params[ 'pageid' ];

		$title = null;
		if ( $pageid !== null ) {
			$title = $this->titleFactory->newFromID( $pageid );
			if ( !$title || !$title->exists() ) {
				throw new HttpException( "Page with ID $pageid does not exist", 400 );
			}
		}

		$showDeleted = Utils::canUserModerate( $this->getAuthority() );

		// Do not use ActorStore::acquireActorId, otherwise a new actor ID will be made for every anonymous page view
		$actor = $this->actorStore->findActorId( $this->getAuthority()->getUser(), $this->dbr );

		$pager = new CommentsPager(
			[
				'includeDeleted' => $showDeleted
			],
			$actor,
			$title,
			null,
			$params[ 'sort' ]
		);

		/** @var Comment[] $comments */
		$comments = [];
		/** @var Comment[] $comments */
		$childComments = [];

		$limit = (int)$params[ 'limit' ];
		$continue = $params[ 'continue' ];

		$pager->setLimit( $limit );
		$pager->setContinue( $continue );
		$res = $pager->getResult();

		$continue = $pager->getContinue();
		foreach ( $res as $r ) {
			if ( $r['c']->mParentId !== null ) {
				// If this is a child comment, add it to the child comments array for processing later
				$childComments[] = $r;
			} else {
				$comments[] = $this->getCommentDataFromResult( $r );
			}
		}

		// Process all the child comments, nesting them under their parents
		foreach ( $childComments as $child ) {
			foreach ( $comments as $index => $topLevelComment ) {
				if ( $topLevelComment[ 'id' ] === $child['c']->mParentId ) {
					$comments[ $index ][ 'children' ][] = $this->getCommentDataFromResult( $child );
				}
			}
		}

		return $this->getResponseFactory()->createJson( [
			'query' => [
				'limit' => $limit,
				'continue' => $continue
			],
			'comments' => $comments,
			// Piggyback off this API call to return some extra info about the logged in user which the UI will use
			'isMod' => Utils::canUserModerate( $this->getAuthority() )
		] );
	}

	public function getParamSettings() {
		return [
			'pageid' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => false
			],
			'limit' => [
				self::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => 10
			],
			'continue' => [
				self::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => null
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
