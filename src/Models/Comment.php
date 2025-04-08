<?php

namespace MediaWiki\Extension\Comments\Models;

use ExtensionRegistry;
use InvalidArgumentException;
use MediaWiki\Config\Config;
use MediaWiki\Extension\AbuseFilter\AbuseFilterServices;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use MediaWiki\User\ActorStore;
use MediaWiki\User\UserIdentity;
use ParserOptions;
use Wikimedia\Rdbms\IDatabase;
use WikitextContent;

class Comment {
	public const TABLE_NAME = 'com_comment';

	/** @var int|null */
	public $mId = null;

	/** @var Title */
	private $mTitle;

	/** @var int */
	public $mPageId;

	/** @var UserIdentity */
	private $mActor;

	/** @var int */
	public $mActorId;

	/** @var string|null */
	public $mCreatedTimestamp = null;

	/** @var string|null */
	public $mEditedTimestamp = null;

	/** @var Comment|null */
	private $mParent = null;

	/** @var int */
	public $mParentId;

	/** @var bool */
	public $mDeleted = false;

	/** @var int */
	public $mRating = 0;

	/** @var string */
	public $mHtml;

	/** @var string */
	public $mWikitext;

	/** @var IDatabase */
	private $dbw;

	/** @var ActorStore */
	private $actorStore;

	/** @var Config */
	private $config;

	/**
	 * @internal
	 */
	public function __construct() {
		$services = MediaWikiServices::getInstance();
		$this->dbw = $services->getDBLoadBalancerFactory()->getPrimaryDatabase();
		$this->actorStore = $services->getActorStore();
		$this->config = $services->getMainConfig();
	}

	/**
	 * The ID of this comment
	 * @return int
	 */
	public function getId() {
		return $this->mId;
	}

	/**
	 * The wiki page the comment was posted on
	 * @return Title
	 */
	public function getTitle() {
		if ( $this->mTitle !== null ) {
			return $this->mTitle;
		}

		$this->mTitle = MediaWikiServices::getInstance()->getTitleFactory()->newFromID( $this->mPageId );
		return $this->mTitle;
	}

	/**
	 * Sets the Title (wiki page) that this comment has been posted on
	 *
	 * This method returns the current Comment object for easier chaining.
	 * @param Title $title
	 * @return Comment
	 */
	public function setTitle( $title ) {
		$this->mTitle = $title;
		$this->mPageId = $this->mTitle->getId();
		return $this;
	}

	/**
	 * The actor who posted the comment
	 * @return UserIdentity
	 */
	public function getActor() {
		if ( $this->mActor ) {
			return $this->mActor;
		}

		$this->mActor = $this->actorStore->getActorById( $this->mActorId, $this->dbw );
		return $this->mActor;
	}

	/**
	 * Sets the actor who posted this comment
	 *
	 * This method returns the current Comment object for easier chaining.
	 * @param UserIdentity $user
	 * @return Comment
	 */
	public function setActor( $user ) {
		$this->mActor = $user;
		$this->mActorId = $this->actorStore->acquireActorId( $user, $this->dbw );
		return $this;
	}

	/**
	 * The comment that is being replied to
	 * @return Comment|null
	 */
	public function getParent() {
		return $this->mParent;
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
		$this->mParent = $commentOrNull;
		$this->mParentId = $commentOrNull ? $commentOrNull->getId() : null;
		return $this;
	}

	/**
	 * Was this comment deleted by someone with permission?
	 * @return bool
	 */
	public function isDeleted() {
		return $this->mDeleted;
	}

	/**
	 * Sets whether or not this comment has been deleted.
	 *
	 * This method returns the current Comment object for easier chaining.
	 * @param bool $deleted
	 * @return Comment
	 */
	public function setDeleted( $deleted ) {
		$this->mDeleted = $deleted;
		return $this;
	}

	/**
	 * The parsed HTML for the comment
	 * @return string
	 */
	public function getHtml() {
		return $this->mHtml;
	}

	/**
	 * Sets the HTML for this comment.
	 *
	 * This method returns the current Comment object for easier chaining.
	 * @param string $html
	 * @return Comment
	 */
	public function setHtml( $html, $parse = true ) {
		$this->mHtml = $html;
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
		return $this->mWikitext;
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
		$this->mWikitext = $text;
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
		return $this->mCreatedTimestamp;
	}

	/**
	 * The edited timestamp for the comment
	 * @return string
	 */
	public function getEditedTimestamp() {
		return $this->mEditedTimestamp;
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
		return $this->mRating;
	}

	/**
	 * Gets the CommentRating object for a specific user. If the user has not rated this comment, then this method will
	 * return null.
	 *
	 * @param UserIdentity $user
	 * @return CommentRating
	 */
	public function getRatingForUser( $user ) {
		return CommentRating::fetchByCommentAndUser( $this->mId, $user );
	}

	/**
	 * Sets a rating for a particular user.
	 *
	 * @param UserIdentity $user
	 * @param int $rating an integer matching `-1`, `0`, or `1`
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
	public function incrementRatingCount( $amount = 1 ) {
		$this->dbw->newUpdateQueryBuilder()
			->table( $this::TABLE_NAME )
			->set( [ 'c_rating=c_rating+' . $amount ] )
			->where( [ 'c_id' => $this->mId ] )
			->caller( __METHOD__ )->execute();

		$this->mRating = (int)$this->dbw->newSelectQueryBuilder()
			->select( 'c_rating' )
			->table( $this::TABLE_NAME )
			->where( [ 'c_id' => $this->mId ] )
			->caller( __METHOD__ )->fetchField();
	}

	/**
	 * Decrement the current rating count for the comment. This method will update the decrement the current live
	 * value in the database, reloading this Comment object with the updated value.
	 *
	 * This method should ONLY be called on comments that already exist in the database.
	 * @return void
	 */
	public function decrementRatingCount( $amount = 1 ) {
		$this->dbw->newUpdateQueryBuilder()
			->table( $this::TABLE_NAME )
			->set( [ 'c_rating=c_rating-' . $amount ] )
			->where( [ 'c_id' => $this->mId ] )
			->caller( __METHOD__ )->execute();

		$this->mRating = (int)$this->dbw->newSelectQueryBuilder()
			->select( 'c_rating' )
			->table( $this::TABLE_NAME )
			->where( [ 'c_id' => $this->mId ] )
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
		$this->mRating = $rating;
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
			if ( !$this->mHtml ) {
				throw new InvalidArgumentException( 'No HTML provided; the comment could not be parsed.' );
			}

			$transform = MediaWikiServices::getInstance()->getHtmlTransformFactory()
				->getHtmlToContentTransform( $this->mHtml, $this->getTitle() );

			$transform->setOptions( [
				'contentmodel' => CONTENT_MODEL_WIKITEXT,
				'offsetType' => 'byte'
			] );

			$content = $transform->htmlToContent();
			if ( !$content instanceof WikitextContent ) {
				// TODO better exception class
				throw new InvalidArgumentException( 'Unable to convert to wikitext' );
			}

			$this->mWikitext = $content->getText();
		} else {
			if ( !$this->mWikitext ) {
				throw new InvalidArgumentException( 'No wikitext provided; the comment could not be parsed.' );
			}

			$parser = MediaWikiServices::getInstance()->getParsoidParserFactory()->create();
			$parserOpts = $this->mActor ? ParserOptions::newFromUser( $this->mActor ) : ParserOptions::newFromAnon();
			$parserOutput = $parser->parse( $this->mWikitext, $this->getTitle(), $parserOpts );

			$this->mHtml = $parserOutput->getText();
		}
	}

	/**
	 * Check whether this Comment object would violate one of the wiki's anti-abuse measures. If the result from this
	 * method is null, then the comment passed validation. Else, it will return an array of errors from
	 * `Status::getErrorsArray()`.
	 * @return array[]|null
	 */
	public function checkSpamFilters() {
		$user = MediaWikiServices::getInstance()->getUserFactory()->newFromUserIdentity( $this->mActor );

		// Run the comment through AbuseFilter, if it is installed and enabled
		if ( $this->config->get( 'CommentsUseAbuseFilter' ) &&
			ExtensionRegistry::getInstance()->isLoaded( 'Abuse Filter' ) ) {
			$vars = AbuseFilterServices::getVariableGeneratorFactory()
				->newGenerator()
				->addUserVars( $this->mActor )
				->addTitleVars( $this->mTitle, 'page' )
				->addGenericVars()
				->getVariableHolder();
			$vars->setVar( 'action', 'comment' );
			$vars->setVar( 'new_wikitext', $this->mWikitext );
			$vars->setLazyLoadVar( 'new_size', 'length', [ 'length-var' => 'new_wikitext' ] );

			$rf = AbuseFilterServices::getFilterRunnerFactory();
			$runner = $rf->newRunner( $user, $this->mTitle, $vars, 'default' );
			$status = $runner->run();

			if ( !$status->isOK() ) {
				return $status->getErrorsArray();
			}
		}

		return null;
	}

	/**
	 * Saves this object to the database and returns the insert ID
	 * @return int|null
	 */
	public function save() {
		$isUpdate = $this->mId !== null;

		if ( !$this->mCreatedTimestamp ) {
			$this->mCreatedTimestamp = wfTimestamp( TS_ISO_8601 );
		}

		if ( $isUpdate ) {
			$this->mEditedTimestamp = wfTimestampOrNull( TS_ISO_8601, 0 );
		}

		$row = [
			'c_page' => $this->mPageId,
			'c_actor' => $this->mActorId,
			'c_parent' => $this->mParentId,
			'c_timestamp' => $this->dbw->timestamp( $this->mCreatedTimestamp ),
			'c_deleted' => (int)$this->mDeleted,
			'c_rating' => $this->mRating,
			'c_html' => $this->mHtml,
			'c_wikitext' => $this->mWikitext,
			'c_edited_timestamp' => $this->dbw->timestampOrNull( $this->mEditedTimestamp )
		];

		if ( !$isUpdate ) {
			// If there is no ID for this object, then we'll presume it doesn't exist.
			$this->dbw->newInsertQueryBuilder()
				->insertInto( self::TABLE_NAME )
				->row( $row )
				->caller( __METHOD__ )
				->execute();

			// Set the ID of this object to the newly inserted object ID
			$this->mId = $this->dbw->insertId();
		} else {
			// Perform an update instead
			$this->dbw->newUpdateQueryBuilder()
				->table( self::TABLE_NAME )
				->set( $row )
				->where( [ 'c_id' => $this->mId ] )
				->caller( __METHOD__ )
				->execute();
		}

		return $this->dbw->affectedRows() ? $this->mId : null;
	}

	/**
	 * @return array
	 */
	public function toArray() {
		return [
			'id' => $this->mId,
			'created' => wfTimestamp( TS_ISO_8601, $this->mCreatedTimestamp ),
			'edited' => wfTimestampOrNull( TS_ISO_8601, $this->mEditedTimestamp ),
			'user' => [
				'name' => $this->getActor()->getName(),
				'anon' => !$this->getActor()->isRegistered()
			],
			'parent' => $this->mParentId,
			'deleted' => $this->mDeleted,
			'rating' => $this->mRating,
			'html' => $this->mHtml,
			'wikitext' => $this->mWikitext
		];
	}
}
