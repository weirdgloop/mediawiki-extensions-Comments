<?php

namespace MediaWiki\Extension\Comments;

use InvalidArgumentException;
use MediaWiki\Extension\Comments\Models\Comment;
use MediaWiki\Title\TitleFactory;
use MediaWiki\User\ActorStore;
use stdClass;
use Wikimedia\Rdbms\LBFactory;

class CommentFactory {
	private LBFactory $lbFactory;

	private TitleFactory $titleFactory;

	private ActorStore $actorStore;

	public function __construct(
		LBFactory $lbFactory,
		TitleFactory $titleFactory,
		ActorStore $actorStore
	) {
		$this->lbFactory = $lbFactory;
		$this->titleFactory = $titleFactory;
		$this->actorStore = $actorStore;
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
		$comment->setId( (int)$row->c_id );

		$pageId = (int)$row->c_page;
		if ( !empty( $pageId ) ) {
			$comment->setTitle( $this->titleFactory->newFromID( $pageId ) );
		}

		$actorId = (int)$row->c_actor;
		if ( !empty( $actorId ) ) {
			$comment->setUser( $this->actorStore->getActorById(
				$actorId, $this->lbFactory->getReplicaDatabase() ), $actorId );
		}

		$parentId = (int)$row->c_parent;
		if ( !empty( $parentId ) ) {
			$comment->setParent( $this->newEmpty()->setId( $parentId ) );
		}

		$comment->setTimestamp( wfTimestamp( TS_ISO_8601, $row->c_timestamp ) );
		$comment->setDeleted( (bool)$row->c_deleted );
		$comment->setWikitext( (string)$row->c_wikitext, false );
		$comment->setHtml( (string)$row->c_html );
		$comment->setRating( (int)$row->c_rating );

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
