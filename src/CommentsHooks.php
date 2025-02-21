<?php

namespace MediaWiki\Extension\Comments;

use DatabaseUpdater;
use MediaWiki\Block\Hook\GetAllBlockActionsHook;
use MediaWiki\Config\Config;
use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;
use MediaWiki\Output\OutputPage;
use Skin;

class CommentsHooks implements
	LoadExtensionSchemaUpdatesHook,
	GetAllBlockActionsHook,
	BeforePageDisplayHook
{
	private Config $config;

	public function __construct( Config $config ) {
		$this->config = $config;
	}

	/**
	 * @param DatabaseUpdater $updater
	 * @return void
	 */
	public function onLoadExtensionSchemaUpdates( $updater ) {
		$dir = dirname( __DIR__ ) . '/sql';
		$maintenanceDb = $updater->getDB();
		$dbType = $maintenanceDb->getType();

		$updater->addExtensionTable( 'comments', "$dir/$dbType/tables-generated.sql" );
	}

	/**
	 * @param $actions
	 * @return void
	 */
	public function onGetAllBlockActions( &$actions ) {
		$actions[ 'comments' ] = 300;
	}

	/**
	 * @param OutputPage $out
	 * @param Skin $skin
	 */
	public function onBeforePageDisplay( $out, $skin ): void {
		$title = $out->getTitle();

		// Do not run on special pages, pages that do not exist, or actions other than action=view
		if ( $title->isSpecialPage() || !$title->exists() || $out->getActionName() !== 'view' ) {
			return;
		}

		// Do not run on the main page unless the config option is set
		if ( !$this->config->get( 'CommentsShowOnMainPage' ) && $title->isMainPage() ) {
			return;
		}

		$out->addModules( 'ext.comments' );
	}
}
