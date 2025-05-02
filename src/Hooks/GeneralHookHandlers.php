<?php

namespace MediaWiki\Extension\Yappin\Hooks;

use MediaWiki\Block\Hook\GetAllBlockActionsHook;
use MediaWiki\Config\Config;
use MediaWiki\Extension\Yappin\Utils;
use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\Hook\ContributionsToolLinksHook;
use MediaWiki\MediaWikiServices;
use MediaWiki\Output\OutputPage;
use MediaWiki\ResourceLoader\Hook\ResourceLoaderGetConfigVarsHook;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;
use Skin;

class GeneralHookHandlers implements
	GetAllBlockActionsHook,
	BeforePageDisplayHook,
	ResourceLoaderGetConfigVarsHook,
	ContributionsToolLinksHook
{
	private Config $config;

	public function __construct( Config $config ) {
		$this->config = $config;
	}

	/**
	 * @param array &$actions
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

		Utils::loadCommentsModule( $out );
	}

	/**
	 * @param array &$vars
	 * @param string $skin
	 * @param Config $config
	 * @return void
	 */
	public function onResourceLoaderGetConfigVars( array &$vars, $skin, Config $config ): void {
		$vars['wgComments'] = [
			'resultsPerPage' => $config->get( 'CommentsResultsPerPage' ),
			'readOnly' => $config->get( 'CommentsReadOnly' )
		];
	}

	/**
	 * @param int $id
	 * @param Title $title
	 * @param array &$tools
	 * @param SpecialPage $specialPage
	 * @return void
	 */
	public function onContributionsToolLinks( $id, Title $title, array &$tools, SpecialPage $specialPage ) {
		$username = $title->getText();

		$tools['commentcontribs'] = MediaWikiServices::getInstance()->getLinkRenderer()->makeKnownLink(
			SpecialPage::getTitleFor( 'Comments' ),
			$specialPage->msg( 'yappin-contributions', $username ),
			[],
			[ 'user' => $username ]
		);
	}
}
