<?php

namespace MediaWiki\Extension\Yappin\Api;

use MediaWiki\Config\Config;
use MediaWiki\Extension\Yappin\CommentFactory;
use MediaWiki\Extension\Yappin\Utils;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\LocalizedHttpException;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\Validator\JsonBodyValidator;
use MediaWiki\Title\TitleFactory;
use Wikimedia\Message\MessageValue;
use Wikimedia\ParamValidator\ParamValidator;

class ApiPostComment extends SimpleHandler {
	/**
	 * @var TitleFactory
	 */
	private TitleFactory $titleFactory;

	/**
	 * @var CommentFactory
	 */
	private CommentFactory $commentFactory;

	/**
	 * @var Config
	 */
	private Config $config;

	public function __construct(
		TitleFactory $titleFactory,
		CommentFactory $commentFactory,
		Config $config
	) {
		$this->titleFactory = $titleFactory;
		$this->commentFactory = $commentFactory;
		$this->config = $config;
	}

	/**
	 * @throws HttpException
	 */
	public function run() {
		$auth = $this->getAuthority();
		$canComment = Utils::canUserComment( $auth );
		if ( $canComment !== true ) {
			throw new LocalizedHttpException( $canComment, 403 );
		}

		$body = $this->getValidatedBody();
		$pageId = (int)$body[ 'pageid' ];
		$parentId = (int)$body[ 'parentid' ];

		// Must either provide a page ID or a parent ID
		if ( !$pageId && !$parentId ) {
			throw new HttpException( 'Must provide either page ID or parent ID' );
		}

		$html = trim( (string)$body[ 'html' ] );
		$wikitext = trim( (string)$body[ 'wikitext' ] );

		if ( !$html && !$wikitext ) {
			throw new LocalizedHttpException(
				new MessageValue( 'yappin-submit-error-empty' ), 400 );
		}

		$parent = null;
		if ( $parentId ) {
			$parent = $this->commentFactory->newFromId( $parentId );

			if ( $parent->isDeleted() ) {
				throw new LocalizedHttpException(
					new MessageValue( 'yappin-submit-error-parent-missing', $parentId ), 400 );
			}
			if ( $parent->getParent() ) {
				throw new LocalizedHttpException(
					new MessageValue( 'yappin-submit-error-parent-hasparent' ), 400 );
			}

			$pageId = $parent->getTitle()->getId();
		}

		$page = $this->titleFactory->newFromID( $pageId );
		if ( !$page || !$page->exists() ) {
			throw new LocalizedHttpException(
				new MessageValue( 'yappin-submit-error-page-missing', $pageId ), 400 );
		}

		if ( !Utils::isCommentsEnabled( $this->config, $page ) ) {
			throw new LocalizedHttpException(
				new MessageValue( 'yappin-submit-error-comments-disabled' ), 400 );
		}

		// Create a new comment
		$comment = $this->commentFactory->newEmpty()
			->setTitle( $page )
			->setActor( $this->getAuthority()->getUser() )
			->setParent( $parent );

		if ( $html ) {
			$comment->setHtml( $html );
		} else {
			$comment->setWikitext( $wikitext );
		}

		$isSpam = $comment->checkSpamFilters();
		if ( $isSpam ) {
			throw new LocalizedHttpException(
				new MessageValue( 'yappin-submit-error-spam' ), 400
			);
		}

		$comment->save();

		return $this->getResponseFactory()->createJson( [
			'comment' => $comment->toArray()
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
			'pageid' => [
				self::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => false
			],
			'parentid' => [
				self::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => false
			],
			'html' => [
				self::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false
			],
			'wikitext' => [
				self::PARAM_SOURCE => 'body',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false
			]
		] );
	}
}
