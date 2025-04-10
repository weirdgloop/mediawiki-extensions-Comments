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
use MediaWiki\User\UserNameUtils;
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

	/**
	 * @var UserNameUtils
	 */
	private $userNameUtils;

	public function __construct(
		TitleFactory $titleFactory,
		ActorStore $actorStore,
		LBFactory $factory,
		UserNameUtils $userNameUtils
	) {
		$this->titleFactory = $titleFactory;
		$this->actorStore = $actorStore;
		$this->dbr = $factory->getReplicaDatabase();
		$this->userNameUtils = $userNameUtils;
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

		if ( $pageid !== null ) {
			$title = $this->titleFactory->newFromID( $pageid );
			if ( !$title || !$title->exists() ) {
				throw new HttpException( "Page with ID $pageid does not exist", 400 );
			}
		}

		$showDeleted = Utils::canUserModerate( $this->getAuthority() );

		$targetActor = null;
		$targetUserName = $params[ 'user' ] ? ucfirst( trim( $params[ 'user' ] ) ) : null;
		if ( !empty( $targetUserName ) ) {
			// To avoid useless DB lookups, check whether the name would be valid
			if ( !$this->userNameUtils->isIP( $targetUserName ) && !$this->userNameUtils->isValid( $targetUserName ) ) {
				return $this->getResponseFactory()->createJson( [
					'query' => [],
					'comments' => [],
					'isMod' => $showDeleted
				] );
			}

			$targetActor = $this->actorStore->findActorIdByName( $params[ 'user' ], $this->dbr );
			if ( $targetActor === null ) {
				return $this->getResponseFactory()->createJson( [
					'query' => [],
					'comments' => [],
					'isMod' => $showDeleted
				] );
			}
		}

		// Do not use ActorStore::acquireActorId, otherwise a new actor ID will be made for every anonymous page view
		$actor = $this->actorStore->findActorId( $this->getAuthority()->getUser(), $this->dbr );

		$pager = new CommentsPager(
			[
				'includeDeleted' => $showDeleted
			],
			$actor,
			$params[ 'sort' ],
			$targetActor
		);

		/** @var Comment[] $comments */
		$comments = [];
		/** @var Comment[] $comments */
		$childComments = [];

		$limit = (int)$params[ 'limit' ];
		if ( $limit > 100 ) {
			// Do not allow the limit to be above 100
			$limit = 100;
		}

		$continue = $params[ 'continue' ];

		$pager->setLimit( $limit );
		$pager->setContinue( $continue );

		if ( $pageid !== null ) {
			$res = $pager->fetchResultsForPage( $pageid, true );

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
		} else {
			$res = $pager->fetchAllResults();
			foreach ( $res as $r ) {
				$comments[] = $this->getCommentDataFromResult( $r );
			}
		}

		$continue = $pager->getContinue();

		return $this->getResponseFactory()->createJson( [
			'query' => [
				'limit' => $limit,
				'continue' => $continue
			],
			'comments' => $comments,
			// Piggyback off this API call to return some extra info about the logged in user which the UI will use
			'isMod' => $showDeleted
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
			],
			'user' => [
				self::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => null
			]
		];
	}
}
