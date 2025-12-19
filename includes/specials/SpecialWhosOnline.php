<?php

use MediaWiki\MediaWikiServices;

/**
 * WhosOnline extension - creates a list of logged-in users & anons currently online
 * The list can be viewed at Special:WhosOnline
 *
 * @file
 * @ingroup Extensions
 * @author Maciej Brencz <macbre(at)-spam-wikia.com> - minor fixes and improvements
 * @author ChekMate Security Group - original code
 * @see http://www.chekmate.org/wiki/index.php/MW:_Whos_Online_Extension
 * @license GPL-2.0-or-later
 */

class SpecialWhosOnline extends IncludableSpecialPage {
	public function __construct() {
		parent::__construct( 'WhosOnline' );
	}

	/** @inheritDoc */
	public function execute( $para ) {
		global $wgWhosOnlineTimeout;

		$timeout = 3600;
		if ( is_numeric( $wgWhosOnlineTimeout ) ) {
			$timeout = $wgWhosOnlineTimeout;
		}

		$db = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
		$old = wfTimestamp( TS_MW, time() - $timeout );
		$db->delete( 'online', [ 'timestamp < "' . $old . '"' ], __METHOD__ );

		$this->setHeaders();

		$pager = new PagerWhosOnline();

		$showNavigation = !$this->including();
		if ( $para ) {
			$bits = preg_split( '/\s*,\s*/', trim( $para ) );
			foreach ( $bits as $bit ) {
				if ( $bit == 'shownav' ) {
					$showNavigation = true;
				}
				if ( is_numeric( $bit ) ) {
					$pager->mLimit = $bit;
				}

				$m = [];
				if ( preg_match( '/^limit=(\d+)$/', $bit, $m ) ) {
					$pager->mLimit = intval( $m[1] );
				}
			}
		}

		$body = $pager->getBody();

		// Checking for both to ensure that we don't show the useless navigation
		// stuff when $body is empty, i.e. no registered users are online
		if ( $showNavigation && $body ) {
			$this->getOutput()->addHTML( $pager->getNavigationBar() );
		}
		if ( $body ) {
			$this->getOutput()->addHTML( '<ul>' . $body . '</ul>' );
		} else {
			// Nothing to display, hmm? Well, no point in continuing further, then...
			// Just get us out of here.
			$this->getOutput()->addHTML( $this->msg( 'specialpage-empty' )->parse() );
		}
	}
}
