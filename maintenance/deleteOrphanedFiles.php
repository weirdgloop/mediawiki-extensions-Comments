<?php

use MediaWiki\Extension\Comments\CommentFileService;

class DeleteOrphanedFiles extends Maintenance {
	public function __construct() {
		parent::__construct();

		$this->addDescription(
			'Deletes files that are still in the /tmp directory after 24 hours. These files are part of a comment'
			. ' that was never submitted or edited.'
		);
		$this->setBatchSize( 50 );
	}

	public function execute() {
		/** @var CommentFileService $cfs */
		$cfs = $this->getServiceContainer()->getService( 'Yappin.CommentFileService' );

		$uploadedBefore = wfTimestamp( TS_MW, strtotime( '-1 day' ) );

		$total = $cfs->getFileCount( '/temp' );
		$this->output( "Total number of temp files: $total\n" );

		$dir = $cfs->getBackend()->getContainerStoragePath( 'comments-files' );
		$list = $cfs->getBackend()->getFileList( [
			'dir' => "$dir/temp"
		] );
		if ( $list === null ) {
			$this->fatalError( 'Could not list files in file backend' );
		}

		$batch = [];
		$deleted = 0;

		$this->output( "Looping through file list...\n" );
		foreach ( $list as $file ) {
			$path = "$dir/temp/$file";

			$ts = $cfs->getBackend()->getFileTimestamp( [ 'src' => $path ] );
			if ( $ts < $uploadedBefore ) {
				$batch[] = [ 'op' => 'delete', 'src' => $path ];
				if ( count( $batch ) >= $this->getBatchSize() ) {
					$cfs->getBackend()->doQuickOperations( $batch );
					$deleted += count( $batch );
					$batch = [];
					$this->output( "Deleted $deleted files\n" );
				}
			}
		}

		if ( count( $batch ) ) {
			$cfs->getBackend()->doQuickOperations( $batch );
			$deleted += count( $batch );
			$this->output( "Deleted $deleted files\n" );
		}

		$this->output( "Finished!\n" );
	}
}

$maintClass = DeleteOrphanedFiles::class;
require_once RUN_MAINTENANCE_IF_MAIN;
