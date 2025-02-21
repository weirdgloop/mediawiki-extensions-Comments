<?php

namespace MediaWiki\Extension\Comments\Api;

use MediaWiki\Extension\Comments\CommentFactory;
use MediaWiki\Extension\Comments\CommentsPager;
use MediaWiki\Extension\Comments\Utils;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Title\TitleFactory;
use RequestContext;
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
			RequestContext::getMain(), [
				'includeDeleted' => $showDeleted
			],
			null,
			null,
			null,
			$title,
			null
		);

		$comments = [];

		$limit = (int)$params[ 'limit' ];
		$offset = (int)$params[ 'offset' ];
		wfDebug( "comments offset is $offset" );

		$pager->setLimit( $limit );
		$pager->setOffset( $offset );

		if ( $pager->getNumRows() > 0 ) {
			$count = 0;
			foreach ( $pager->getResult() as $row ) {
				if ( ++$count > $limit ) {
					break;
				}
				$comments[] = $this->commentFactory->newFromRow( $row )->toArray();
			}
		}

		return $this->getResponseFactory()->createJson( [
			'query' => [
				'limit' => $limit,
				'offset' => $pager->getResultOffset()
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
