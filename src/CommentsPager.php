<?php

namespace MediaWiki\Extension\Comments;

use MediaWiki\Extension\Comments\Models\Comment;
use MediaWiki\Extension\Comments\Models\CommentRating;
use MediaWiki\MediaWikiServices;
use stdClass;
use Title;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\SelectQueryBuilder;
use Wikimedia\Rdbms\UnionQueryBuilder;

/**
 * Helper class for retrieving comments from the database.
 */
class CommentsPager {
	public const SORT_DATE_DESC = 'sort_date_desc';
	public const SORT_DATE_ASC = 'sort_date_asc';
	public const SORT_RATING_DESC = 'sort_rating_desc';
	public const SORT_RATING_ASC = 'sort_rating_asc';

	/**
	 * @var CommentFactory
	 */
	private CommentFactory $commentFactory;

	/**
	 * @var IDatabase
	 */
	private IDatabase $db;

	/**
	 * @var int|null
	 */
	private int $targetPageId;

	/**
	 * @var bool Set to true to include child comments. Only has effect if $this->parent is null.
	 */
	private bool $includeChildren = true;

	/**
	 * @var int|null If set, will retrieve the user's rating for each comment.
	 */
	private $currentActor = null;

	/**
	 * @var bool Set to true to include deleted comments
	 */
	private bool $includeDeleted;

	/**
	 * @var int|null
	 */
	private $targetCommentId = null;

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
	 * @var string
	 */
	private string $sortMethod;

	/**
	 * Object containing three keys for each comment:
	 * - `c`: the Comment object
	 * - `ur`: the CommentRating for the provided user ($this->currentActor), if provided
	 * - `ours`: whether the comment belongs to $this->currentActor, if provided
	 * @var stdClass[]
	 */
	private array $res = [];

	/**
	 * @param array $options
	 * @param int|null $currentActor
	 * @param Title|int|null $targetTitle
	 * @param Comment|int|null $targetComment
	 * @param string $sortMethod
	 */
	public function __construct(
		array $options,
		int $currentActor = null,
		$targetTitle = null,
		$targetComment = null,
		string $sortMethod = self::SORT_DATE_DESC
	) {
		$services = MediaWikiServices::getInstance();

		if ( $currentActor ) {
			$this->currentActor = $currentActor;
		}

		if ( $targetTitle !== null ) {
			$this->targetPageId = ( $targetTitle instanceof Title ) ? $targetTitle->getId() : $targetTitle;
		}

		if ( $targetComment !== null ) {
			$this->targetCommentId = ( $targetComment instanceof Comment ) ? $targetComment->getId() : $targetComment;
		}

		$this->commentFactory = $services->getService( 'Comments.CommentFactory' );
		$this->db = $services->getDBLoadBalancerFactory()->getPrimaryDatabase();

		$this->includeDeleted = !empty( $options['includeDeleted'] );
		$this->sortMethod = $sortMethod;
	}

	/**
	 * Set the continue to use in the database query.
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
	 * @return string
	 */
	public function getSortMethod() {
		return $this->sortMethod;
	}

	/**
	 * Set the sort method for the query
	 * @param string $sortMethod one of the `SORT_*` constants
	 * @return void
	 */
	public function setSortMethod( $sortMethod ) {
		$this->sortMethod = $sortMethod;
	}

	/**
	 * @return object[]
	 */
	public function getResult() {
		if ( !$this->res ) {
			$this->execute();
		}

		return $this->res;
	}

	/**
	 * @return string[]|null
	 */
	private function getOrderCondition() {
		switch ( $this->sortMethod ) {
			case $this::SORT_DATE_DESC:
				return [ 'c_timestamp DESC' ];
			case $this::SORT_DATE_ASC:
				return [ 'c_timestamp ASC' ];
			case $this::SORT_RATING_DESC:
				return [ 'c_rating DESC, c_timestamp DESC' ];
			case $this::SORT_RATING_ASC:
				return [ 'c_rating ASC, c_timestamp DESC' ];
			default:
				return null;
		}
	}

	/**
	 * @return string|null
	 */
	private function getOffsetCondition() {
		switch ( $this->sortMethod ) {
			case self::SORT_DATE_DESC:
				return 'c_timestamp <= ' . $this->db->addQuotes( $this->db->timestamp( $this->continue ) );
			case self::SORT_DATE_ASC:
				return 'c_timestamp >= ' . $this->db->addQuotes( $this->db->timestamp( $this->continue ) );
			default:
				return null;
		}
	}

	/**
	 * If we're not getting results for a specific page, look up the page names for each comment in the page table.
	 * @param SelectQueryBuilder $builder
	 */
	private function addPageJoinIfRequired( $builder ) {
		if ( empty( $this->targetPageId ) ) {
			$builder->join( 'page', null, 'page_id = c_page' );
		}
	}

	/**
	 * Execute the database query.
	 * After calling this method, the result will be available by calling `$this->getResult()`.
	 * @return void
	 */
	public function execute() {
		$conds = [];

		if ( !empty( $this->targetPageId ) ) {
			$conds[ 'c_page' ] = $this->targetPageId;
		}

		if ( !$this->includeDeleted ) {
			$conds[ 'c_deleted' ] = false;
		}

		if ( $this->targetCommentId ) {
			$conds[ 'c_id' ] = $this->targetCommentId;
		}

		$opts = [
			'ORDER BY' => $this->getOrderCondition()
		];

		if ( $this->includeChildren ) {
			$uqb = $this->db->newUnionQueryBuilder()->all();

			$childConds = [];

			if ( !$this->includeDeleted ) {
				$childConds[ 'c_deleted' ] = false;
			}

			if ( $this->targetCommentId !== null ) {
				$childConds[ 'c_parent' ] = $this->targetCommentId;
			} else {
				$childConds[] = 'c_parent IN ' . $this->db->buildSelectSubquery(
					Comment::TABLE_NAME,
					'c_id',
					$conds,
					__METHOD__,
					$opts
				);
			}

			$childSelect = $this->db->newSelectQueryBuilder()
				->select( '*' )
				->from( Comment::TABLE_NAME )
				->where( $childConds );

			$this->addPageJoinIfRequired( $childSelect );

			if ( $this->currentActor !== null ) {
				$childSelect->leftJoin( 'com_rating', null, [
					'cr_comment = c_id',
					'cr_actor' => $this->currentActor
				] );
			}

			$uqb->add( $childSelect );

			if ( $this->continue !== null ) {
				$offsetCond = $this->getOffsetCondition();
				if ( $offsetCond !== null ) {
					$conds[] = $offsetCond;
				}
				if ( !str_starts_with( $this->sortMethod, 'sort_date' ) ) {
					// For queries without dates, we will revert to using actual query offset,
					// which is probably slightly expensive for a large number of comments.
					$opts[ 'OFFSET' ] = $this->continue;
				}
			}

			$conds[ 'c_parent' ] = null;

			$parentSelect = $this->db->newSelectQueryBuilder()
				->select( '*' )
				->from(
					$this->db->buildSelectSubquery(
						Comment::TABLE_NAME,
						'*',
						$conds,
						__METHOD__,
						$opts + [ 'LIMIT' => $this->limit + 1 ]
					),
					'a'
				);

			$this->addPageJoinIfRequired( $parentSelect );

			if ( $this->currentActor !== null ) {
				$parentSelect->leftJoin( 'com_rating', null, [
					'cr_comment = c_id',
					'cr_actor' => $this->currentActor
				] );
			}

			$uqb->add( $parentSelect );

			return $this->reallyDoQuery( $uqb->caller( __METHOD__ ) );
		}

		if ( $this->continue !== null ) {
			$offsetCond = $this->getOffsetCondition();
			if ( $offsetCond !== null ) {
				$conds[] = $offsetCond;
			}
			if ( !str_starts_with( $this->sortMethod, 'sort_date' ) ) {
				// For queries without dates, we will revert to using actual query offset,
				// which is probably slightly expensive for a large number of comments.
				$opts[ 'OFFSET' ] = $this->continue;
			}
		}

		$builder = $this->db->newSelectQueryBuilder()
			->from( Comment::TABLE_NAME )
			->where( $conds )
			->options( $opts + [ 'LIMIT' => $this->limit ] )
			->caller( __METHOD__ );

		$this->addPageJoinIfRequired( $builder );

		return $this->reallyDoQuery( $builder );
	}

	/**
	 * @param SelectQueryBuilder|UnionQueryBuilder $builder
	 * @return void
	 */
	private function reallyDoQuery( $builder ) {
		$res = $builder->fetchResultSet();
		$prevContinue = $this->continue;
		$this->continue = null;

		$comments = [];

		$parentsSeen = 0;
		foreach ( $res as $row ) {
			$c = $this->commentFactory->newFromRow( $row );
			if ( $row->c_parent === null ) {
				if ( $parentsSeen === $this->limit ) {
					// This is the extra row we queried for to work out if there's more rows that can be requested.
					if ( str_starts_with( $this->sortMethod, 'sort_date' ) ) {
						$this->continue = $row->c_timestamp;
					} else {
						$this->continue = $prevContinue + $this->limit;
					}
					continue;
				} else {
					$parentsSeen++;
				}
			}

			$comments[] = [
				// The comment object, returned as-is
				'c' => $c,
				// The current user's rating, if we retrieved it
				'ur' => isset( $row->cr_rating ) ? CommentRating::newFromRow( $row )->getRating() : 0,
				// Whether this comment belongs to the current actor
				'ours' => $this->currentActor === $c->mActorId,
				// The page for the comment, only returned if there was no target page given to the pager instance
				'p' => property_exists( $row, 'page_title' ) ? [
					'title' => $row->page_title,
					'ns' => (int)$row->page_namespace
				] : null
			];
		}

		$this->res = $comments;
	}
}
