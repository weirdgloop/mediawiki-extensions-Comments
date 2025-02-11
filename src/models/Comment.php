<?php

namespace MediaWiki\Extension\Comments;

use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use MediaWiki\User\UserIdentity;

class Comment {
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

	/**
	 * @internal
	 */
	public function __construct() {
		$this->timestamp = wfTimestampNow();
	}

	/**
	 * @param stdClass $row
	 * @return void
	 */
	public function loadFromRow( $row ) {
		$this->id = (int)$row->c_id;
		$this->pageId = (int)$row->c_page;
		$this->actorId = (int)$row->c_actor;
		$this->parent = (int)$row->c_parent;
		$this->timestamp = wfTimestamp( TS_MW, $row->timestamp );
		$this->deleted = (bool)$row->c_deleted;
		$this->rating = (int)$row->c_rating;
		$this->html = (string)$row->c_html;
		$this->wikitext = (string)$row->c_wikitext;
	}

	/**
	 * The ID of this comment
	 * @return int
	 */
	public function getId() {
		return $this->id;
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

		$services = MediaWikiServices::getInstance();
		$dbw = $services->getDBLoadBalancerFactory()->getPrimaryDatabase();
		$this->actor = $services->getActorStore()->getActorById( $this->actorId, $dbw );
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
		$services = MediaWikiServices::getInstance();
		$dbw = $services->getDBLoadBalancerFactory()->getPrimaryDatabase();

		$this->actor = $user;
		if ( !$actorId ) {
			$this->actorId = $services->getActorStore()->findActorId( $user, $dbw );
		}
		return $this;
	}

	/**
	 * The comment that is being replied to
	 * @return Comment|null
	 */
	public function getParent() {
		// TODO
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
	 * Sets the HTML for this comment. This shouldn't be called manually, instead call `Comment::reparse`.
	 *
	 * This method returns the current Comment object for easier chaining.
	 * @param string $html
	 * @return Comment
	 */
	public function setHtml( $html ) {
		$this->html = $html;
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
			$this->reparse();
		}
		return $this;
	}

	/**
	 * Parse the wikitext and sets the HTML to the output. For convenience, this method also returns the HTML.
	 *
	 * This method should typically only be called once: when the wikitext is changed. Re-parsing the comment
	 * on every page view is expensive and unnecessary.
	 * @return string
	 */
	public function reparse() {
		if ( !$this->wikitext ) {
			throw new InvalidArgumentException( 'No wikitext provided; the comment could not be parsed.' );
		}

		$parser = MediaWikiServices::getInstance()->getParser();
		$this->html = $parser->recursiveTagParseFully( $this->wikitext );
		return $this->html;
	}

	/**
	 * @param UserIdentity|int $actor
	 * @return
	 */
	public function getActorRating( $actor ) {
		$services = MediaWikiServices::getInstance();
		$dbw = $services->getDBLoadBalancerFactory()->getPrimaryDatabase();

		if ( $actor instanceof UserIdentity ) {
			$actor = $services->getActorStore()->findActorId( $actor, $dbw );
		}

		$row = $dbw->newSelectQueryBuilder()
			->from( 'com_rating' )
			->where( [ 'cr_actor' => $actor ] )
			->caller( __METHOD__ )
			->fetchRow();

		if ( !$row ) {
			return false;
		}

		// TODO: else, construct comment rating object
	}

	/**
	 * Saves this object to the database and returns the insert ID
	 * @return int|null
	 */
	public function save() {
		$row = [
			'c_id' => $this->id,
			'c_page' => $this->pageId,
			'c_actor' => $this->actorId,
			'c_parent' => $this->parent->id,
			'c_timestamp' => $this->timestamp,
			'c_deleted' => (int)$this->deleted,
			'c_rating' => $this->rating,
			'c_html' => $this->html,
			'c_wikitext' => $this->wikitext
		];

		$dbw = MediaWikiServices::getInstance()->getDBLoadBalancerFactory()->getPrimaryDatabase();
		$dbw->newInsertQueryBuilder()
			->table( 'com_comment' )
			->row( $row )
			->caller( __METHOD__ )
			->onDuplicateKeyUpdate()
			->set( $row )
			->execute();

		return $dbw->affectedRows() ? $dbw->insertId() : null;
	}
}
