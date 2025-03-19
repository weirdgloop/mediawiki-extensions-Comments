<?php

namespace MediaWiki\Extension\Comments\Api;

use MediaWiki\Extension\Comments\CommentFactory;
use MediaWiki\Extension\Comments\CommentsPager;
use MediaWiki\Extension\Comments\Models\Comment;
use MediaWiki\Extension\Comments\Utils;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Title\TitleFactory;
use Wikimedia\ParamValidator\ParamValidator;

class ApiGetCommentsForPage extends SimpleHandler {
	/**
	 * @var TitleFactory
	 */
	private TitleFactory $titleFactory;

	public function __construct( TitleFactory $titleFactory ) {
		$this->titleFactory = $titleFactory;
	}

	/**
	 * @throws HttpException
	 */
	public function run() {
		$params = $this->getValidatedParams();
		$pageid = $params[ 'pageid' ];

		$title = $this->titleFactory->newFromID( $pageid );
		if ( !$title || !$title->exists() ) {
			throw new HttpException( "Page with ID $pageid does not exist", 400 );
		}

		$showDeleted = Utils::canUserModerate( $this->getAuthority() );

		$pager = new CommentsPager(
			[
				'includeDeleted' => $showDeleted
			],
			$this->getAuthority()->getUser(),
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
				$comments[] = $r['c']->toArray() + [
					'children' => [],
					'userRating' => $r['ur']
				];
			}
		}

		// Process all the child comments, nesting them under their parents
		foreach ( $childComments as $child ) {
			foreach ( $comments as $index => $topLevelComment ) {
				if ( $topLevelComment[ 'id' ] === $child['c']->mParentId ) {
					$comments[ $index ][ 'children' ][] = $child['c']->toArray() + [
						'userRating' => $child['ur']
					];
				}
			}
		}

		return $this->getResponseFactory()->createJson( [
			'query' => [
				'limit' => $limit,
				'continue' => $continue
			],
			'comments' => $comments
		] );
	}

	public function getParamSettings() {
		return [
			'pageid' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => true
			],
			'limit' => [
				self::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => 50
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
