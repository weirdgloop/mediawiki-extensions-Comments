<?php

namespace MediaWiki\Extension\Comments;

use MediaWiki\Extension\Comments\Models\Comment;
use MediaWiki\MediaWikiServices;
use MediaWiki\User\ActorStore;
use MediaWiki\User\UserIdentity;
use Title;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\IResultWrapper;
use Wikimedia\Rdbms\SelectQueryBuilder;
use Wikimedia\Rdbms\UnionQueryBuilder;

/**
 * Helper class for retrieving comments from the database.
 */
class CommentsPager {
	/**
	 * @var ActorStore
	 */
	private ActorStore $actorStore;

	/**
	 * @var CommentFactory
	 */
	private CommentFactory $commentFactory;

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
	 * The continue offset to use in the database query.
	 * @var string|null
	 */
	private ?string $continue = null;

	/**
	 * The limit to use in the database query. We'll always retrieve +1 extra row
	 * so that we know if there are more results or not.
	 * @var int
	 */
	private int $limit = 50;

	/**
	 * @var Comment[]
	 */
	private array $res = [];

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
		$this->commentFactory = $services->getService( 'Comments.CommentFactory' );
		$this->db = $services->getDBLoadBalancerFactory()->getPrimaryDatabase();

		$this->deletedOnly = !empty( $options['deletedOnly'] );
		$this->includeDeleted = !empty( $options['includeDeleted'] );
		$this->parent = $parent;
	}

	/**
	 * Set the continue to use in the database query
	 * @param string $continue
	 * @return void
	 */
	public function setContinue( $continue ) {
		$this->continue = $continue;
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
	 * Returns the timestamp that should be used for continuing this query (pagination).
	 * Calling `$this->execute()` will also continue the query.
	 * @return string|null
	 */
	public function getContinue() {
		return $this->continue;
	}

	/**
	 * Gets the result of the last query. If the query has not been executed yet, calling this method will do that.
	 * @return Comment[]
	 */
	public function getResult() {
		if ( !$this->res ) {
			$this->execute();
		}

		return $this->res;
	}

	/**
	 * Execute the database query.
	 * After calling this method, the result will be available by calling `$this->getResult()`.
	 * @return void
	 */
	public function execute() {
		$conds = [
			'c_page' => $this->targetTitle->getId()
		];

		$opts = [
			'ORDER BY' => [
				'c_timestamp DESC'
			]
		];

		if ( $this->includeChildren && !$this->parent ) {
			$conds[ 'c_parent' ] = null;

			$uqb = $this->db->newUnionQueryBuilder()->all();

			$uqb->add(
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

			if ( $this->continue !== null ) {
				$conds[] = 'c_timestamp < ' . $this->db->addQuotes( $this->db->timestamp( $this->continue ) );
			}

			$uqb->add(
				$this->db->newSelectQueryBuilder()
					->select( '*' )
					->from(
						$this->db->buildSelectSubquery(
							self::TABLE_NAME,
							'*',
							$conds,
							__METHOD__,
							$opts + [ 'LIMIT' => $this->limit + 1 ]
						),
						'a'
					),
			);

			return $this->reallyDoQuery( $uqb->caller( __METHOD__ ) );
		}

		if ( $this->parent ) {
			$conds[ 'c_parent' ] = $this->parent->getId();
		}

		if ( $this->continue !== null ) {
			$conds[] = 'c_timestamp < ' . $this->db->addQuotes( $this->db->timestamp( $this->continue ) );
		}

		return $this->reallyDoQuery( $this->db->newSelectQueryBuilder()
			->from( self::TABLE_NAME )
			->where( $conds )
			->options( $opts + [ 'LIMIT' => $this->limit ] )
			->caller( __METHOD__ )
		);
	}

	/**
	 * @param SelectQueryBuilder|UnionQueryBuilder $builder
	 * @return void
	 */
	private function reallyDoQuery( $builder ) {
		$res = $builder->fetchResultSet();
		$this->continue = null;

		$comments = [];

		$parentsSeen = 0;
		foreach ( $res as $row ) {
			$c = $this->commentFactory->newFromRow( $row );
			if ( $row->c_parent === null ) {
				if ( $parentsSeen === $this->limit ) {
					// This is the extra row we queried for to work out if there's more rows that can be requested.
					$this->continue = $row->c_timestamp;
					continue;
				} else {
					$parentsSeen++;
				}
			}

			$comments[] = $c;
		}

		$this->res = $comments;
	}
}
