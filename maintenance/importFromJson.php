<?php

require_once __DIR__ . '/../../../maintenance/Maintenance.php';

use MediaWiki\Page\PageReferenceValue;
use MediaWiki\Parser\Parsoid\ParsoidParserFactory;
use MediaWiki\User\ActorNormalization;
use MediaWiki\User\UserFactory;

class ImportFromJson extends Maintenance {
	public const REQUIRED_KEYS = [
		'id',
		'page',
		'timestamp',
		'wikitext'
	];

	public function __construct() {
		parent::__construct();

		$this->addDescription(
			"Imports comments into the database from a JSON file."
		);

		$this->addArg(
			'file',
			'JSON file',
			false
		);
	}

	public function execute() {
		$this->output( "Reading JSON file...\n" );

		if ( $this->hasArg( 0 ) ) {
			$file = file_get_contents( $this->getArg( 0 ) );
		} else {
			$file = $this->getStdin();
		}

		if ( !$file ) {
			$this->fatalError( "Unable to read file" );
		}

		$file = json_decode( $file, true );
		if ( $file === null ) {
			$this->fatalError( "Unable to parse JSON file" );
		}

		$total = count( $file );

		$dbw = $this->getDB( DB_PRIMARY );
		$services = $this->getServiceContainer();
		$pf = $services->getParsoidParserFactory();
		$an = $services->getActorNormalization();
		$uf = $services->getUserFactory();
		$titleFactory = $services->getTitleFactory();

		foreach ( $file as $ix => $item ) {
			// Make sure that this JSON object contains all of the required keys
			foreach ( self::REQUIRED_KEYS as $k ) {
				if ( $item[ $k ] === null ) {
					$this->error( "Skipping item with missing key \"$k\"" );
					continue;
				}
			}

			$this->handleCommentImport( $item, $dbw, $pf, $an, $uf );
			$this->output( "Inserted $ix/$total comments\n" );
		}
	}

	/**
	 * @param array $data
	 * @param IMaintainableDatabase $dbw
	 * @param ParsoidParserFactory $pf
	 * @param ActorNormalization $an
	 * @param UserFactory $uf
	 * @return void
	 */
	private function handleCommentImport( array $data, $dbw, $pf, $an, $uf ) {
		if ( array_key_exists( 'userId', $data ) ) {
			$user = $uf->newFromId( $data[ 'userId' ] );
			$actor = $an->acquireActorId( $user, $dbw );
		} elseif ( array_key_exists( 'userIp', $data ) ) {
			$user = $uf->newAnonymous( $data[ 'userIp' ] );
			$actor = $an->acquireActorId( $user, $dbw );
		} else {
			return;
		}

		$parser = $pf->create();
		$parserOpts = ParserOptions::newFromUser( $user );
		$parserOutput = $parser->parse( $data[ 'wikitext' ], PageReferenceValue::localReference( NS_MAIN, 'Test' ), $parserOpts );

		$set = [
			'c_id' => $data[ 'id' ],
			'c_page' => $data[ 'page' ],
			'c_timestamp' => $data[ 'timestamp' ],
			'c_actor' => $actor,
			'c_parent' => $data[ 'parentId' ] ?? null,
			'c_rating' => 0,
			'c_wikitext' => $data[ 'wikitext' ],
			'c_html' => $parserOutput->getText()
		];

		$dbw->newInsertQueryBuilder()
			->insertInto( 'com_comment' )
			->rows( [ $set ] )
			->caller( __METHOD__ )
			->execute();
	}
}

$maintClass = ImportFromJson::class;
require_once RUN_MAINTENANCE_IF_MAIN;
