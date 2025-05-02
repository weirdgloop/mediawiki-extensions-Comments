<?php

namespace MediaWiki\Extension\Yappin;

use ExtensionRegistry;
use MediaWiki\MediaWikiServices;
use MediaWiki\Output\OutputPage;
use MediaWiki\Permissions\Authority;
use MediaWiki\User\User;
use Wikimedia\Message\MessageValue;

class Utils {
	/**
	 * If the user cannot comment, this method returns a MessageValue object indicating why.
	 * @param User|Authority $userOrAuthority
	 * @return MessageValue|true
	 */
	public static function canUserComment( $userOrAuthority ) {
		if ( !$userOrAuthority->isAllowed( 'comments' ) ) {
			return new MessageValue( 'yappin-submit-error-noperm' );
		}

		$block = $userOrAuthority->getBlock();
		if ( $block && ( $block->isSitewide() || $block->appliesToRight( 'comments' ) ) ) {
			return new MessageValue( 'yappin-submit-error-blocked' );
		}

		return true;
	}

	/**
	 * Returns whether the given user can moderate comments or not.
	 * @param User|Authority $userOrAuthority
	 * @return bool
	 */
	public static function canUserModerate( $userOrAuthority ) {
		return $userOrAuthority->isAllowed( 'comments-manage' );
	}

	/**
	 * @param OutputPage $out
	 * @return void
	 */
	public static function loadCommentsModule( OutputPage $out ) {
		// On desktop, load VE dependencies. On mobile, we will just use a normal <input> for writing a comment.
		$services = MediaWikiServices::getInstance();
		if ( !(
			ExtensionRegistry::getInstance()->isLoaded( 'MobileFrontend' ) &&
			$services->getService( 'MobileFrontend.Context' )->shouldDisplayMobileView()
		) ) {
			$out->addModules( [ 'ext.yappin.ve.desktop' ] );
		}

		$out->addModules( [ 'ext.yappin.main' ] );
	}
}
