<?php

namespace MediaWiki\Extension\Comments;

use MediaWiki\Extension\Comments\Models\Comment;
use MediaWiki\MediaWikiServices;
use MediaWiki\User\ActorStore;
use MediaWiki\User\UserIdentity;
use Title;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\IResultWrapper;

/**
 * Helper class for retrieving comments from the database.
 */
class CommentsPager {
	/**
	 * @var ActorStore
	 */
	private ActorStore $actorStore;

	/**
	 * @var IDatabase
	 */
	private IDatabase $db;

	/**
	 * @var UserIdentity|null
	 */
	private ?UserIdentity $targetUser = null;

	/**
	 * @var Title
	 */
	private Title $targetTitle;

	/**
	 * @var bool Set to true to include child comments. Only has effect if $this->parent is null.
	 */
	private bool $includeChildren = true;

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

	/**
	 * The offset to use in the database query. We'll always retrieve +1 extra row
	 * so that we know if there are more results or not.
	 * @var int
	 */
	private int $offset = 0;

	/**
	 * The limit to use in the database query
	 * @var int
	 */
	private int $limit = 50;

	private const TABLE_NAME = 'com_comment';

	public function __construct(
		array $options,
		UserIdentity $targetUser = null,
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

		$this->actorStore = $services->getActorStore();
		$this->db = $services->getDBLoadBalancerFactory()->getPrimaryDatabase();

		$this->deletedOnly = !empty( $options['deletedOnly'] );
		$this->includeDeleted = !empty( $options['includeDeleted'] );
		$this->parent = $parent;
	}

	/**
	 * Set the offset to use in the database query
	 * @param int $offset
	 * @return void
	 */
	public function setOffset( $offset ) {
		$this->offset = $offset;
	}

	/**
	 * Set the limit to use in the database query
	 * @param int $limit
	 * @return void
	 */
	public function setLimit( $limit ) {
		$this->limit = $limit;
	}

	/**
	 * Execute the database query
	 * @return IResultWrapper
	 */
	public function executeQuery() {
		$conds = [
			'c_page' => $this->targetTitle->getId()
		];

		$opts = [
			'ORDER BY' => [
				'c_timestamp DESC'
			],
			'LIMIT' => $this->limit + 1,
			'OFFSET' => $this->offset
		];

		if ( $this->includeChildren && !$this->parent ) {
			$conds[ 'c_parent' ] = null;

			$uqb = $this->db->newUnionQueryBuilder()->all();
			$uqb->add(
				$this->db->newSelectQueryBuilder()
					->select( '*' )
					->from(
						$this->db->buildSelectSubquery(
							self::TABLE_NAME,
							'*',
							$conds,
							__METHOD__,
							$opts
						),
						'a'
					),
			)->add(
				$this->db->newSelectQueryBuilder()
					->select( '*' )
					->from( self::TABLE_NAME )
					->where( 'c_parent IN ' . $this->db->buildSelectSubquery(
						self::TABLE_NAME,
							'c_id',
							$conds,
							__METHOD__,
							$opts
					) )
			);

			return $uqb->caller( __METHOD__ )
				->fetchResultSet();
		}

		if ( $this->parent ) {
			$conds[ 'c_parent' ] = $this->parent->getId();
		}

		return $this->db->newSelectQueryBuilder()
			->from( self::TABLE_NAME )
			->where( $conds )
			->options( $opts )
			->caller( __METHOD__ )
			->fetchResultSet();
	}
}
