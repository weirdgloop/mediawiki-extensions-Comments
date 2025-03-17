<?php

namespace MediaWiki\Extension\Comments\Models;

use MediaWiki\Extension\Comments\CommentFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\User\ActorStore;
use MediaWiki\User\UserIdentity;
use stdClass;
use Wikimedia\Rdbms\IDatabase;

class CommentRating {
	/** @var int */
	private $actorId;

	/** @var int */
	private $commentId;

	/** @var Comment */
	private $comment;

	/** @var int -1, 0, or 1 */
	private $rating;

	/** @var IDatabase */
	private $dbw;

	/** @var ActorStore */
	private $actorStore;

	public function __construct() {
		$services = MediaWikiServices::getInstance();
		$this->dbw = $services->getDBLoadBalancerFactory()->getPrimaryDatabase();
		$this->actorStore = $services->getActorStore();
	}

	/**
	 * Create a CommentRating object from a database row
	 *
	 * @param stdClass $row
	 * @return CommentRating
	 */
	public static function newFromRow( $row ) {
		$obj = new CommentRating();
		$obj->setComment( (int)$row->cr_comment )
			->setActor( (int)$row->cr_actor )
			->setRating( (int)$row->cr_rating );
		return $obj;
	}

	/**
	 * Returns a CommentRating object for a specific user's rating on a comment. If the user has never interacted with
	 * the rating on a comment, then this will return null.
	 *
	 * @param Comment|int $comment either a Comment object or an integer representing the comment ID
	 * @param UserIdentity|int $actor either a UserIdentity object or an integer representing the actor ID of the user
	 * @return CommentRating|null
	 */
	public static function fetchByCommentAndUser( $comment, $actor ) {
		if ( $comment instanceof Comment ) {
			$comment = $comment->getId();
		}

		$services = MediaWikiServices::getInstance();
		$dbr = $services->getDBLoadBalancerFactory()->getReplicaDatabase();

		if ( $actor instanceof UserIdentity ) {
			$actor = $services->getActorStore()->findActorId( $actor, $dbr );
		}

		$row = $dbr->newSelectQueryBuilder()
			->from( 'com_rating' )
			->where( [ 'cr_comment' => $comment->getId(), 'cr_actor' => $actor ] )
			->caller( __METHOD__ )
			->fetchRow();

		return $row ? CommentRating::newFromRow( $row ) : null;
	}

	/**
	 * The Comment object that this rating is for
	 * @return Comment
	 */
	public function getComment() {
		return $this->comment;
	}

	/**
	 * ID of the actor
	 * @return int
	 */
	public function getActorId() {
		return $this->actorId;
	}

	/**
	 * Rating (-1, 0, or 1)
	 * @return int
	 */
	public function getRating() {
		return $this->rating;
	}

	/**
	 * Sets the actor for this comment rating.
	 *
	 * This method returns the current CommentRating object for easier chaining.
	 * @param UserIdentity|int $actor
	 * @return $this
	 */
	public function setActor( $actor ) {
		if ( $actor instanceof UserIdentity ) {
			$actor = $this->actorStore->acquireActorId( $actor, $this->dbw );
		}

		$this->actorId = $actor;
		return $this;
	}

	/**
	 * Sets the user's rating of the comment (-1, 0, or 1)
	 *
	 * This method returns the current CommentRating object for easier chaining.
	 * @param $rating
	 * @return $this
	 */
	public function setRating( $rating ) {
		$this->rating = $rating;
		return $this;
	}

	/**
	 * Sets the comment that this rating relates to.
	 *
	 * This method returns the current CommentRating object for easier chaining.
	 * @param Comment|int $comment
	 * @return $this
	 */
	public function setComment( $comment ) {
		if ( is_int( $comment ) ) {
			$comment = MediaWikiServices::getInstance()->getService( 'Comments.CommentFactory' )->newFromId( $comment );
		}

		$this->comment = $comment;
		return $this;
	}

	/**
	 * Saves this object to the database, overwriting the existing object if necessary.
	 * @return void
	 */
	public function save() {
		$prev = $this->dbw->newSelectQueryBuilder()
			->select( 'cr_rating' )
			->table( 'com_rating' )
			->where( [ 'cr_actor' => $this->actorId, 'cr_comment' => $this->comment->getId() ] )
			->caller( __METHOD__ )->fetchField();

		$row = [
			'cr_comment' => $this->comment->getId(),
			'cr_actor' => $this->actorId,
			'cr_rating' => $this->rating
		];

		$this->dbw->newInsertQueryBuilder()
			->insertInto( 'com_rating' )
			->row( $row )
			->onDuplicateKeyUpdate()
			->uniqueIndexFields( [ 'cr_comment', 'cr_actor' ] )
			->set( [ 'cr_rating' => $this->rating ] )
			->caller( __METHOD__ )
			->execute();

		if ( is_null( $prev ) ) {
			// User had not rated this comment before
			if ( $this->rating === -1 ) {
				$this->comment->decrementRatingCount();
			} else if ( $this->rating === 1 ) {
				$this->comment->incrementRatingCount();
			}
		} else if ( (int)$prev !== $this->rating ) {
			// Rating is different to what the previous value was for this user
			if ( $this->rating < (int)$prev ) {
				$this->comment->decrementRatingCount();
			} else {
				$this->comment->incrementRatingCount();
			}
		}
	}
}
