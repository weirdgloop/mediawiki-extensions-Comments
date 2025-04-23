<?php

namespace MediaWiki\Extension\Comments;

use MediaWiki\Extension\Comments\Models\Comment;
use MediaWiki\Extension\Comments\Models\CommentRating;
use MediaWiki\MediaWikiServices;
use stdClass;
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
	 * @var int|null If set, will retrieve the user's rating for each comment.
	 */
	private $currentActor = null;

	/**
	 * @var bool Set to true to include deleted comments
	 */
	private bool $includeDeleted;

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
	 * @var string|null
	 */
	private ?string $sortMethod = null;

	/**
	 * @var int|null
	 */
	private ?int $filterByActor = null;

	/**
	 * @param array $options
	 * @param int|null $currentActor
	 * @param string|null $sortMethod
	 */
	public function __construct(
		array $options,
		int $currentActor = null,
		?string $sortMethod = self::SORT_DATE_DESC,
		?int $filterByActor = null
	) {
		$services = MediaWikiServices::getInstance();

		if ( $currentActor ) {
			$this->currentActor = $currentActor;
		}

		$this->commentFactory = $services->getService( 'Comments.CommentFactory' );
		$this->db = $services->getDBLoadBalancerFactory()->getPrimaryDatabase();

		$this->includeDeleted = !empty( $options['includeDeleted'] );
		$this->sortMethod = $sortMethod;

		if ( $filterByActor ) {
			$this->filterByActor = $filterByActor;
		}
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
	 * @param SelectQueryBuilder $builder
	 */
	private function addPageJoin( $builder ) {
		$builder->join( 'page', null, 'page_id = c_page' );
	}

	/**
	 * @param SelectQueryBuilder $builder
	 */
	private function addUserRatingJoin( $builder ) {
		if ( $this->currentActor !== null ) {
			$builder->leftJoin( 'com_rating', 'cr', [
				'cr_comment = c.c_id',
				'cr_actor' => $this->currentActor
			] )->select( 'cr.*' );
		}
	}

	/**
	 * Fetches the comments for a particular page by its ID.
	 * @param int $pageId
	 * @param bool $includeChildren
	 * @return stdClass[]
	 */
	public function fetchResultsForPage( $pageId, $includeChildren ) {
		$conds = [
			'c_page' => $pageId,
		];

		if ( !$this->includeDeleted ) {
			$conds[] = 'c_deleted_actor IS NULL';
		}

		if ( $this->filterByActor !== null ) {
			$conds[ 'c_actor' ] = $this->filterByActor;
		}

		$opts = [
			'ORDER BY' => $this->getOrderCondition()
		];

		if ( $includeChildren ) {
			$uqb = $this->db->newUnionQueryBuilder()->all();

			$childConds = [];

			if ( !$this->includeDeleted ) {
				$childConds[] = 'c_deleted_actor IS NULL';
			}

			$childSelect = $this->db->newSelectQueryBuilder()
				->select( 'c.*' )
				->from( Comment::TABLE_NAME, 'c' )
				->join( $this->db->newSelectQueryBuilder()
					->select( 'c_id' )
					->table( Comment::TABLE_NAME )
					->where( $conds )
					->limit( $this->limit ),
					'p',
					[ 'c.c_parent = p.c_id' ]
				)
				->where( $childConds );

			$this->addUserRatingJoin( $childSelect );
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
				->select( 'c.*' )
				->from(
					$this->db->buildSelectSubquery(
						Comment::TABLE_NAME,
						'*',
						$conds,
						__METHOD__,
						$opts + [ 'LIMIT' => $this->limit + 1 ]
					),
					'c'
				);

			$this->addUserRatingJoin( $parentSelect );

			$uqb->add( $parentSelect );
			return $this->reallyFetchResultsForPage( $uqb->caller( __METHOD__ ) );
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
			->select( 'c.*' )
			->from( Comment::TABLE_NAME, 'c' )
			->where( $conds )
			->options( $opts + [ 'LIMIT' => $this->limit ] )
			->caller( __METHOD__ );

		$this->addUserRatingJoin( $builder );
		return $this->reallyFetchResultsForPage( $builder );
	}

	/**
	 * @param SelectQueryBuilder|UnionQueryBuilder $builder
	 * @return stdClass[]
	 */
	private function reallyFetchResultsForPage( $builder ) {
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

			$comments[] = $this->formatResult( $c, $row );
		}

		return $comments;
	}

	/**
	 * Fetches all of the comments posted on the wiki.
	 * @return stdClass[]
	 */
	public function fetchAllResults() {
		$conds = [];

		if ( !$this->includeDeleted ) {
			$conds[] = 'c_deleted_actor IS NULL';
		}

		if ( $this->filterByActor !== null ) {
			$conds[ 'c_actor' ] = $this->filterByActor;
		}

		$opts = [
			'ORDER BY' => $this->getOrderCondition()
		];

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
			->select( 'c.*' )
			->from( Comment::TABLE_NAME, 'c' )
			->where( $conds )
			->options( $opts + [ 'LIMIT' => $this->limit + 1 ] )
			->caller( __METHOD__ );

		$this->addPageJoin( $builder );
		$this->addUserRatingJoin( $builder );

		$res = $builder->fetchResultSet();
		$prevContinue = $this->continue;
		$this->continue = null;

		$comments = [];

		foreach ( $res as $row ) {
			if ( count( $comments ) === $this->limit ) {
				// This is the extra row we queried for to work out if there's more rows that can be requested.
				if ( str_starts_with( $this->sortMethod, 'sort_date' ) ) {
					$this->continue = $row->c_timestamp;
				} else {
					$this->continue = $prevContinue + $this->limit;
				}
				continue;
			}
			$c = $this->commentFactory->newFromRow( $row );
			$comments[] = $this->formatResult( $c, $row );
		}

		return $comments;
	}

	/**
	 * Fetches the target parent ID's row, and the children of the target parent comment ID.
	 * @param int $parentId
	 * @return stdClass[]
	 */
	public function fetchResultsForParent( $parentId ) {
		$conds = [];

		if ( !$this->includeDeleted ) {
			$conds[] = 'c_deleted_actor IS NULL';
		}

		$uqb = $this->db->newUnionQueryBuilder()->all();
		$childConds = [
			'c_parent' => $parentId
		];

		$childSelect = $this->db->newSelectQueryBuilder()
			->select( 'c.*' )
			->from( Comment::TABLE_NAME, 'c' )
			->where( $conds + $childConds );

		$this->addPageJoin( $childSelect );
		$this->addUserRatingJoin( $childSelect );
		$uqb->add( $childSelect );

		$parentSelect = $this->db->newSelectQueryBuilder()
			->select( 'c.*' )
			->from( Comment::TABLE_NAME, 'c' )
			->where( [ 'c_id' => $parentId ] + $conds );

		$this->addPageJoin( $parentSelect );
		$this->addUserRatingJoin( $parentSelect );
		$uqb->add( $parentSelect );
		$uqb->orderBy( $this->getOrderCondition() );

		$res = $uqb->fetchResultSet();
		$comments = [];
		foreach ( $res as $row ) {
			$c = $this->commentFactory->newFromRow( $row );
			$comments[] = $this->formatResult( $c, $row );
		}
		return $comments;
	}

	/**
	 * @param Comment $comment
	 * @param stdClass $row
	 * @return stdClass[]
	 */
	private function formatResult( $comment, $row ) {
		return [
			// The comment object, returned as-is
			'c' => $comment,
			// The current user's rating, if we retrieved it
			'ur' => isset( $row->cr_rating ) ? CommentRating::newFromRow( $row )->getRating() : 0,
			// Whether this comment belongs to the current actor
			'ours' => $this->currentActor === $comment->mActorId,
			// The page for the comment, only returned if there was no target page given to the pager instance
			'p' => property_exists( $row, 'page_title' ) ? [
				'title' => $row->page_title,
				'ns' => (int)$row->page_namespace,
				'id' => (int)$row->page_id
			] : null
		];
	}
}
