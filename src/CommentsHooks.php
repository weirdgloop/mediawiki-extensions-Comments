<?php

namespace MediaWiki\Extension\Comments;

use ChangesListBooleanFilter;
use DatabaseUpdater;
use MediaWiki\Block\Hook\GetAllBlockActionsHook;
use MediaWiki\Config\Config;
use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;
use MediaWiki\Output\OutputPage;
use MediaWiki\Permissions\Authority;
use MediaWiki\SpecialPage\ChangesListSpecialPage;
use MediaWiki\SpecialPage\Hook\ChangesListSpecialPageStructuredFiltersHook;
use MediaWiki\User\User;
use Skin;

class CommentsHooks implements
	LoadExtensionSchemaUpdatesHook,
	GetAllBlockActionsHook,
	BeforePageDisplayHook,
	ChangesListSpecialPageStructuredFiltersHook
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

	/**
	 * @param ChangesListSpecialPage $special
	 * @return void
	 */
	public function onChangesListSpecialPageStructuredFilters( $special ) {
		new ChangesListBooleanFilter( [
			'name' => 'hidecomments',
			'priority' => -10,
			'group' => $special->getFilterGroup( 'changeType' ),
			'label' => 'comments-rcfilters-comments-label',
			'default' => false,
			'description' => 'comments-rcfilters-comments-desc',
			'showHideSuffix' => 'showhidecomments',
			'isRowApplicableCallable' => static function ( $ctx, $rc ) {
				return true;
			}
		] );
	}

	/**
	 * Returns whether the given user can comment or not.
	 * @param User|Authority $userOrAuthority
	 * @return bool
	 */
	public static function canUserComment( $userOrAuthority ) {
		$block = $userOrAuthority->getBlock();
		return (
			$userOrAuthority->isAllowed( 'comments' ) &&
			( !$block || ( $block->isSitewide() || $block->appliesToRight( 'comments' ) ) )
		);
	}

	/**
	 * Returns whether the given user can moderate comments or not.
	 * @param User|Authority $userOrAuthority
	 * @return bool
	 */
	public static function canUserModerate( $userOrAuthority ) {
		return $userOrAuthority->isAllowed( 'comments-manage' );
	}
}
