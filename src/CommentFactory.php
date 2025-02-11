<?php

namespace MediaWiki\Extension\Comments;

use InvalidArgumentException;
use MediaWiki\Title\Title;
use stdClass;
use Wikimedia\Rdbms\LBFactory;

class CommentFactory {
	private LBFactory $lbFactory;

	public function __construct( LBFactory $lbFactory ) {
		$this->lbFactory = $lbFactory;
	}

	/**
	 * Create a new empty Comment object
	 * @return Comment
	 */
	public function newEmpty() {
		return new Comment();
	}

	/**
	 * Create a new Comment object from a database row
	 * @param stdClass $row
	 * @return Comment
	 */
	public function newFromRow( $row ) {
		$comment = new Comment();
		$comment->loadFromRow( $row );
		return $comment;
	}

	/**
	 * Retrieve all of the comments for a given page
	 * @param Title|int $page
	 * @param bool $deleted Return deleted comments (default: false)
	 * @return Comment[]
	 */
	public function getPageComments( $page, $deleted = false ) {
		// TODO: add limit and offset param
		if ( $page instanceof Title ) {
			$page = $page->getId();
		}

		$db = $this->lbFactory->getPrimaryDatabase();
		$res = $db->newSelectQueryBuilder()
			->fields( '*' )
			->from( 'com_comment' )
			->where( [ 'c_page' => $page, 'c_deleted' => (int)$deleted ] )
			->caller( __METHOD__ )
			->fetchResultSet();

		$c = [];
		foreach ( $res as $row ) {
			$c[] = $this->newFromRow( $row );
		}
		return $c;
	}

	/**
	 * Retrieve all of the comments for a given parent comment
	 * @param Comment $parent
	 * @param bool $deleted Return deleted comments (default: false)
	 * @return Comment[]
	 */
	public function getChildComments( $parent, $deleted = false ) {
		$db = $this->lbFactory->getPrimaryDatabase();
		$res = $db->newSelectQueryBuilder()
			->fields( '*' )
			->from( 'com_comment' )
			->where( [ 'c_parent' => $parent->getId(), 'c_deleted' => (int)$deleted ] )
			->caller( __METHOD__ )
			->fetchResultSet();

		$c = [];
		foreach ( $res as $row ) {
			$c[] = $this->newFromRow( $row );
		}
		return $c;
	}

	/**
	 * Create a new Comment object from a given ID. The ID should already exist in the database.
	 * @param int $id
	 * @return Comment
	 * @throws InvalidArgumentException
	 */
	public function newFromId( $id ) {
		$db = $this->lbFactory->getPrimaryDatabase();
		$row = $db->newSelectQueryBuilder()
			->fields( '*' )
			->from( 'com_comment' )
			->where( [ 'c_id' => $id ] )
			->caller( __METHOD__ )
			->fetchRow();

		if ( !$row ) {
			throw new InvalidArgumentException( "No comment found with ID: $id" );
		}

		return $this->newFromRow( $row );
	}
}
