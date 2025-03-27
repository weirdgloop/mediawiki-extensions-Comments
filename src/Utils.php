<?php

namespace MediaWiki\Extension\Comments;

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
			return new MessageValue( 'comments-submit-error-noperm' );
		}

		$block = $userOrAuthority->getBlock();
		if ( $block && ( $block->isSitewide() || $block->appliesToRight( 'comments' ) ) ) {
			return new MessageValue( 'comments-submit-error-blocked' );
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
}
