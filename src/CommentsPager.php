<?php

namespace MediaWiki\Extension\Comments;

use IContextSource;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\MediaWikiServices;
use MediaWiki\Pager\IndexPager;
use MediaWiki\User\ActorStore;
use MediaWiki\User\UserIdentity;
use stdClass;
use Title;

class CommentsPager extends IndexPager {
	/**
	 * @var ActorStore
	 */
	private ActorStore $actorStore;

	/**
	 * @var UserIdentity|null
	 */
	private ?UserIdentity $targetUser = null;

	/**
	 * @var Title
	 */
	private Title $targetTitle;

	/**
	 * @var bool Set to true to include deleted comments
	 */
	private bool $includeDeleted;

	/**
	 * @var bool Set to true to show only deleted comments
	 */
	private bool $deletedOnly;

	/**
	 * @var Comment|null
	 */
	private ?Comment $parent = null;

	public function __construct(
		IContextSource $context = null,
		array $options,
		LinkRenderer $linkRenderer = null,
		UserIdentity $targetUser = null,
		ActorStore $actorStore = null,
		Title $targetTitle = null,
		Comment $parent = null
	) {
		$services = MediaWikiServices::getInstance();

		if ( $targetUser ) {
			$this->targetUser = $targetUser;
		}
		if ( $targetTitle ) {
			$this->targetTitle = $targetTitle;
		}

		$this->actorStore = $actorStore ?? $services->getActorStore();

		$this->deletedOnly = !empty( $options['deletedOnly'] );
		$this->includeDeleted = !empty( $options['includeDeleted'] );
		$this->parent = $parent;

		parent::__construct( $context, $linkRenderer );
	}

	/**
	 * @param stdClass|mixed $row
	 * @return string
	 */
	public function formatRow( $row ) {
		return '';
	}

	/**
	 * @return array
	 */
	public function getQueryInfo() {
		$queryInfo = [
			'tables' => [ 'com_comment' ],
			'fields' => [
				'c_id', 'c_page', 'c_actor', 'c_timestamp', 'c_parent', 'c_deleted', 'c_rating',
				'c_html', 'c_wikitext'
			],
			'conds' => [],
			'options' => [],
			'join_conds' => []
		];

		$queryInfo['conds']['c_page'] = $this->targetTitle->getId();

		if ( $this->targetUser ) {
			$queryInfo['conds']['c_actor'] = $this->actorStore->findActorId( $this->targetUser, $this->getDatabase() );
		}

		if ( !$this->includeDeleted ) {
			$queryInfo['conds'][] = 'c_deleted == 0';
		} elseif ( $this->deletedOnly ) {
			$queryInfo['conds'][] = 'c_deleted != 0';
		}

		if ( $this->parent ) {
			$queryInfo['conds']['c_parent'] = $this->parent->getId();
		}

		return $queryInfo;
	}

	/**
	 * @return string
	 */
	public function getIndexField() {
		return 'c_timestamp';
	}

	/**
	 * @return void
	 */
	public function getNavigationBar() {}
}
