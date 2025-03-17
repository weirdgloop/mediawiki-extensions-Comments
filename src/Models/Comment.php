<?php

namespace MediaWiki\Extension\Comments\Models;

use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use MediaWiki\User\ActorStore;
use MediaWiki\User\UserIdentity;
use ParserOptions;
use stdClass;
use Wikimedia\Rdbms\IDatabase;
use WikitextContent;

class Comment {
	public const TABLE_NAME = 'com_comment';

	/** @var int */
	private $id;

	/** @var Title */
	private $title;

	/** @var int */
	private $pageId;

	/** @var UserIdentity */
	private $actor;

	/** @var int */
	private $actorId;

	/** @var string */
	private $timestamp;

	/** @var Comment|null */
	private $parent = null;

	/** @var bool */
	private $deleted = false;

	/** @var int */
	private $rating = 0;

	/** @var string */
	private $html;

	/** @var string */
	private $wikitext;

	/** @var IDatabase */
	private $dbw;

	/** @var ActorStore */
	private $actorStore;

	/**
	 * @internal
	 */
	public function __construct() {
		$this->timestamp = wfTimestamp( TS_ISO_8601 );

		$services = MediaWikiServices::getInstance();
		$this->dbw = $services->getDBLoadBalancerFactory()->getPrimaryDatabase();
		$this->actorStore = $services->getActorStore();
	}

	/**
	 * The ID of this comment
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Sets the ID of this comment. Once a comment as been assigned an ID, the ID is **immutable**.
	 *
	 * This method returns the current Comment object for easier chaining.
	 * @param int $id
	 * @return $this
	 */
	public function setId( $id ) {
		$this->id = $id;
		return $this;
	}

	/**
	 * The wiki page the comment was posted on
	 * @return Title
	 */
	public function getTitle() {
		if ( $this->title ) {
			return $this->title;
		}

		$this->title = MediaWikiServices::getInstance()->getTitleFactory()->newFromID( $this->pageId );
		return $this->title;
	}

	/**
	 * Sets the Title (wiki page) that this comment has been posted on
	 *
	 * This method returns the current Comment object for easier chaining.
	 * @param Title $title
	 * @return Comment
	 */
	public function setTitle( $title ) {
		$this->title = $title;
		$this->pageId = $this->title->getId();
		return $this;
	}

	/**
	 * The user (actor) who posted the comment
	 * @return UserIdentity
	 */
	public function getUser() {
		if ( $this->actor ) {
			return $this->actor;
		}

		$this->actor = $this->actorStore->getActorById( $this->actorId, $this->dbw );
		return $this->actor;
	}

	/**
	 * Sets the user (actor) who posted this comment
	 *
	 * This method returns the current Comment object for easier chaining.
	 * @param UserIdentity $user
	 * @param int|null $actorId
	 * @return Comment
	 */
	public function setUser( $user, $actorId = null ) {
		$this->actor = $user;
		if ( !$actorId ) {
			$this->actorId = $this->actorStore->findActorId( $user, $this->dbw );
		}
		return $this;
	}

	/**
	 * The comment that is being replied to
	 * @return Comment|null
	 */
	public function getParent() {
		return $this->parent;
	}

	/**
	 * Sets the parent comment of this comment.
	 *
	 * A comment can only have one parent, and comments can only be nested
	 * one level deep. Once set, a comment's parent should *not* be mutated.
	 *
	 * This method returns the current Comment object for easier chaining.
	 * @param Comment|null $commentOrNull
	 * @return Comment
	 */
	public function setParent( $commentOrNull ) {
		$this->parent = $commentOrNull;
		return $this;
	}

	/**
	 * Was this comment deleted by someone with permission?
	 * @return bool
	 */
	public function isDeleted() {
		return $this->deleted;
	}

	/**
	 * Sets whether or not this comment has been deleted.
	 *
	 * This method returns the current Comment object for easier chaining.
	 * @param bool $deleted
	 * @return Comment
	 */
	public function setDeleted( $deleted ) {
		$this->deleted = $deleted;
		return $this;
	}

	/**
	 * The parsed HTML for the comment
	 * @return string
	 */
	public function getHtml() {
		return $this->html;
	}

	/**
	 * Sets the HTML for this comment.
	 *
	 * This method returns the current Comment object for easier chaining.
	 * @param string $html
	 * @return Comment
	 */
	public function setHtml( $html, $parse = true ) {
		$this->html = $html;
		if ( $parse === true ) {
			$this->reparse( true );
		}
		return $this;
	}

	/**
	 * The wikitext for the comment, used to populate the textarea when editing the comment.
	 * This field is not used to render the comment, use `Comment::getHtml` instead.
	 * @return string
	 */
	public function getWikitext() {
		return $this->wikitext;
	}

	/**
	 * Sets the wikitext for this comment, and triggers a parse of it if necessary.
	 *
	 * This method returns the current Comment object for easier chaining.
	 * @param string $text
	 * @param bool $parse
	 * @return Comment
	 */
	public function setWikitext( $text, $parse = true ) {
		$this->wikitext = $text;
		if ( $parse === true ) {
			$this->reparse( false );
		}
		return $this;
	}

	/**
	 * The timestamp for the comment
	 * @return string
	 */
	public function getTimestamp() {
		return $this->timestamp;
	}

	/**
	 * Sets the timestamp for this comment.
	 *
	 * This method returns the current Comment object for easier chaining.
	 *
	 * @param string $ts
	 * @return $this
	 */
	public function setTimestamp( $ts ) {
		$this->timestamp = wfTimestamp( TS_ISO_8601, $ts );
		return $this;
	}

	/**
	 * The overall rating for the comment.
	 *
	 * This is not necessarily equivalent to a SUM() of all CommentRating objects
	 * associated with this comment, and is instead used as a quick lookup,
	 * similarly to `user_editcount` in MediaWiki core.
	 *
	 * @return int
	 */
	public function getRating() {
		return $this->rating;
	}

	/**
	 * Gets the CommentRating object for a specific user. If the user has not rated this comment, then this method will
	 * return null.
	 *
	 * @param UserIdentity $user
	 * @return CommentRating
	 */
	public function getRatingForUser( $user ) {
		return CommentRating::fetchByCommentAndUser( $this->id, $user );
	}

	/**
	 * Sets a rating for a particular user.
	 *
	 * @param $user UserIdentity
	 * @param $rating int an integer matching `-1`, `0`, or `1`
	 * @return CommentRating
	 */
	public function setRatingForUser( $user, $rating ) {
		$obj = new CommentRating();
		$obj->setComment( $this )
			->setActor( $user )
			->setRating( $rating )
			->save();

		return $obj;
	}

	/**
	 * Increments the current rating count for the comment. This method will update the increment the current live
	 * value in the database, reloading this Comment object with the updated value.
	 *
	 * This method should ONLY be called on comments that already exist in the database.
	 * @return void
	 */
	public function incrementRatingCount() {
		$this->dbw->newUpdateQueryBuilder()
			->table( $this::TABLE_NAME )
			->set( [ 'c_rating=c_rating+' . 1 ] )
			->where( [ 'c_id' => $this->id ] )
			->caller( __METHOD__ )->execute();

		$this->rating = (int)$this->dbw->newSelectQueryBuilder()
			->select( 'c_rating' )
			->table( $this::TABLE_NAME )
			->where( [ 'c_id' => $this->id ] )
			->caller( __METHOD__ )->fetchField();
	}

	/**
	 * Decrement the current rating count for the comment. This method will update the decrement the current live
	 * value in the database, reloading this Comment object with the updated value.
	 *
	 * This method should ONLY be called on comments that already exist in the database.
	 * @return void
	 */
	public function decrementRatingCount() {
		$this->dbw->newUpdateQueryBuilder()
			->table( $this::TABLE_NAME )
			->set( [ 'c_rating=c_rating-' . 1 ] )
			->where( [ 'c_id' => $this->id ] )
			->caller( __METHOD__ )->execute();

		$this->rating = (int)$this->dbw->newSelectQueryBuilder()
			->select( 'c_rating' )
			->table( $this::TABLE_NAME )
			->where( [ 'c_id' => $this->id ] )
			->caller( __METHOD__ )->fetchField();
	}

	/**
	 * Sets the rating for this comment. This should not typically be called manually.
	 *
	 * This method returns the current Comment object for easier chaining.
	 *
	 * @param number $rating
	 * @return $this
	 */
	public function setRating( $rating ) {
		$this->rating = $rating;
		return $this;
	}

	/**
	 * Parse the wikitext and sets the output as appropriate. For convenience, this method also returns the output.
	 *
	 * This method should typically only be called once when the comment is changed. Re-parsing the comment
	 * on every page view is expensive and unnecessary.
	 *
	 * @param bool $fromHtml - whether to use $this->html to
	 * @return string
	 */
	public function reparse( $fromHtml = false ) {
		if ( $fromHtml ) {
			if ( !$this->html ) {
				throw new InvalidArgumentException( 'No HTML provided; the comment could not be parsed.' );
			}

			$transform = MediaWikiServices::getInstance()->getHtmlTransformFactory()
				->getHtmlToContentTransform( $this->html, $this->title );

			$transform->setOptions( [
				'contentmodel' => CONTENT_MODEL_WIKITEXT,
				'offsetType' => 'byte'
			] );

			$content = $transform->htmlToContent();
			if ( !$content instanceof WikitextContent ) {
				// TODO better exception class
				throw new InvalidArgumentException( 'Unable to convert to wikitext' );
			}

			$this->wikitext = $content->getText();
			return $this->wikitext;
		} else {
			if ( !$this->wikitext ) {
				throw new InvalidArgumentException( 'No wikitext provided; the comment could not be parsed.' );
			}

			$parser = MediaWikiServices::getInstance()->getParsoidParserFactory()->create();
			$parserOpts = $this->actor ? ParserOptions::newFromUser( $this->actor ) : ParserOptions::newFromAnon();
			$parserOutput = $parser->parse( $this->wikitext, $this->title, $parserOpts );

			$this->html = $parserOutput->getText();
			return $this->html;
		}
	}

	/**
	 * Saves this object to the database and returns the insert ID
	 * @return int|null
	 */
	public function save() {
		$row = [
			'c_page' => $this->pageId,
			'c_actor' => $this->actorId,
			'c_parent' => $this->parent->id,
			'c_timestamp' => wfTimestamp( TS_MW, $this->timestamp ),
			'c_deleted' => (int)$this->deleted,
			'c_rating' => $this->rating,
			'c_html' => $this->html,
			'c_wikitext' => $this->wikitext
		];

		if ( !$this->id ) {
			// If there is no ID for this object, then we'll presume it doesn't exist.
			$this->dbw->newInsertQueryBuilder()
				->insertInto( self::TABLE_NAME )
				->row( $row )
				->caller( __METHOD__ )
				->execute();

			// Set the ID of this object to the newly inserted object ID
			$this->id = $this->dbw->insertId();
		} else {
			// Perform an update instead
			$set = [ 'c_id' => $this->id ] + $row;

			$this->dbw->newUpdateQueryBuilder()
				->table( self::TABLE_NAME )
				->set( $set )
				->caller( __METHOD__ )
				->execute();
		}

		return $this->dbw->affectedRows() ? $this->id : null;
	}

	/**
	 * @return array
	 */
	public function toArray() {
		return [
			'id' => $this->id,
			'timestamp' => $this->timestamp,
			'actor' => $this->actor ? [
				'id' => $this->actor->getId(),
				'name' => $this->actor->getName()
			] : null,
			'deleted' => $this->deleted,
			'rating' => $this->rating,
			'html' => $this->html,
			'wikitext' => $this->wikitext
		];
	}
}
