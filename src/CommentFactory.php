<?php

namespace MediaWiki\Extension\Comments;

use InvalidArgumentException;
use MediaWiki\Extension\Comments\Models\Comment;
use stdClass;
use Wikimedia\Rdbms\LBFactory;

class CommentFactory {
	private LBFactory $lbFactory;

	public function __construct(
		LBFactory $lbFactory
	) {
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
		$comment->mId = (int)$row->c_id;
		$comment->mPageId = (int)$row->c_page;
		$comment->mActorId = (int)$row->c_actor;

		$parentId = (int)$row->c_parent;
		if ( !empty( $parentId ) ) {
			$comment->mParentId = $parentId;
		}

		$comment->mCreatedTimestamp = wfTimestamp( TS_MW, $row->c_timestamp );
		$comment->mEditedTimestamp = wfTimestampOrNull( TS_MW, $row->c_edited_timestamp );

		$comment->mDeletedActorId = $row->c_deleted_actor;
		$comment->mWikitext = (string)$row->c_wikitext;
		$comment->mHtml = (string)$row->c_html;
		$comment->mRating = (int)$row->c_rating;

		return $comment;
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
