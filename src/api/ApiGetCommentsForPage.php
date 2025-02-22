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

	/**
	 * @var CommentFactory
	 */
	private CommentFactory $commentFactory;

	public function __construct( TitleFactory $titleFactory, CommentFactory $commentFactory ) {
		$this->titleFactory = $titleFactory;
		$this->commentFactory = $commentFactory;
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
			null,
			$title,
			null
		);

		/** @var Comment[] $comments */
		$comments = [];
		/** @var Comment[] $comments */
		$childComments = [];

		$limit = (int)$params[ 'limit' ];
		$offset = (int)$params[ 'offset' ];

		$pager->setLimit( $limit );
		$pager->setOffset( $offset );

		$res = $pager->executeQuery();
		if ( $res->numRows() > 0 ) {
			$count = 0;
			foreach ( $res as $row ) {
				if ( ++$count > $limit ) {
					break;
				}

				$comment = $this->commentFactory->newFromRow( $row );
				if ( $comment->getParent() !== null ) {
					// If this is a child comment, add it to the child comments array for processing later
					$childComments[] = $comment;
				} else {
					$comments[] = $comment->toArray() + [
						'children' => []
					];
				}
			}
		}

		// Process all the child comments, nesting them under their parents
		foreach ( $childComments as $child ) {
			foreach ( $comments as $index => $comment ) {
				if ( $comment[ 'id' ] === $child->getParent()->getId() ) {
					$comments[ $index ][ 'children' ][] = $child->toArray();
				}
			}
		}

		return $this->getResponseFactory()->createJson( [
			'query' => [
				'limit' => $limit,
				'offset' => $res->key()
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
			'offset' => [
				self::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => 0
			]
		];
	}
}
