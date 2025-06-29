<?php

namespace MediaWiki\Extension\Yappin\Hooks;

use DatabaseUpdater;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;

class SchemaHookHandlers implements
	LoadExtensionSchemaUpdatesHook
{
	/**
	 * @param DatabaseUpdater $updater
	 * @return void
	 */
	public function onLoadExtensionSchemaUpdates( $updater ) {
		$dir = dirname( __DIR__, 2 ) . '/sql';
		$maintenanceDb = $updater->getDB();
		$dbType = $maintenanceDb->getType();

		$updater->addExtensionTable( 'com_comment', "$dir/$dbType/tables-generated.sql" );
	}
}
