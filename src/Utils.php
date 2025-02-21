<?php

namespace MediaWiki\Extension\Comments;

use MediaWiki\Permissions\Authority;
use MediaWiki\User\User;

class Utils {
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
