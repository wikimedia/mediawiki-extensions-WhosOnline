<?php
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

	/**
	 * get list of logged-in users being online
	 * @return int
	 */
	protected function getAnonsOnline() {
		$dbr = wfGetDB( DB_REPLICA );

		$row = $dbr->selectRow(
			'online',
			'COUNT(*) AS cnt',
			'userid = 0',
			__METHOD__,
			'GROUP BY username'
		);
		$guests = (int)$row->cnt;

		return $guests;
	}

	/** @inheritDoc */
	public function execute( $para ) {
		global $wgWhosOnlineTimeout;

		$timeout = 3600;
		if ( is_numeric( $wgWhosOnlineTimeout ) ) {
			$timeout = $wgWhosOnlineTimeout;
		}

		$db = wfGetDB( DB_MASTER );
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

		if ( $showNavigation ) {
			$this->getOutput()->addHTML( $pager->getNavigationBar() );
		}

		$this->getOutput()->addHTML( '<ul>' . $body . '</ul>' );
	}
}
