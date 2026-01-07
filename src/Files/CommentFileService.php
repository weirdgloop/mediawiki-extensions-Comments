<?php

namespace MediaWiki\Extension\Yappin\Files;

use Wikimedia\FileBackend\FileBackend;
use Wikimedia\FileBackend\FileBackendGroup;
use Wikimedia\FileBackend\FSFileBackend;
use MediaWiki\Extension\Yappin\Utils;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\Permissions\Authority;
use MediaWiki\WikiMap\WikiMap;
use NullLockManager;
use Wikimedia\Message\MessageValue;

/**
 * Main service for handling files within comments.
 */
class CommentFileService {
	/** @var FileBackend */
	private $backend;

	/**
	 * @param FileBackendGroup $fileBackendGroup
	 * @param string $uploadDirectory
	 * @param string $backendName
	 */
	public function __construct(
		$fileBackendGroup,
		$uploadDirectory,
		$backendName
	) {
		if ( $backendName ) {
			$this->backend = $fileBackendGroup->get( $backendName );
		} else {
			$this->backend = new FSFileBackend( [
				'name'           => 'comments-backend',
				'wikiId'         => WikiMap::getCurrentWikiId(),
				'lockManager'    => new NullLockManager( [] ),
				'containerPaths' => [ 'comments-files' => "{$uploadDirectory}/comments" ],
				'fileMode'       => 777,
				'obResetFunc'    => 'wfResetOutputBuffers',
				'streamMimeFunc' => [ 'StreamFile', 'contentTypeFromPath' ],
				'logger' => LoggerFactory::getInstance( 'comments' ),
			] );
		}
	}

	/**
	 * @return FileBackend
	 */
	public function getBackend() {
		return $this->backend;
	}

	/**
	 * Returns the count of how many files exist in the backend.
	 * @param string $dir directory to append to the storage path
	 * @return int
	 */
	public function getFileCount( $dir = '' ) {
		$list = $this->backend->getFileList( [
			'dir' => $this->backend->getContainerStoragePath( 'comments-files' ) . $dir
		] );

		return iterator_count( $list );
	}

	/**
	 * Checks if the authority can upload a file. If they are not allowed, returns an instance of MessageValue which
	 * should be used as the response to show the user.
	 * @param Authority $authority
	 * @return MessageValue|true
	 */
	public function isAllowedToUpload( $authority ) {
		if ( !$authority->isAllowed( 'comments-upload' ) ) {
			return new MessageValue( 'yappin-upload-error-noperm' );
		}
		return Utils::canUserComment( $authority );
	}
}
