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
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

class PagerWhosOnline extends IndexPager {
	function __construct() {
		parent::__construct();
		$this->mLimit = $this->mDefaultLimit;
	}

	function getQueryInfo() {
		global $wgWhosOnlineShowAnons;

		return [
			'tables'  => [ 'online' ],
			'fields'  => [ 'username' ],
			'options' => [
				'ORDER BY' => 'timestamp DESC',
				'GROUP BY' => 'username'
			],
			'conds'   => $wgWhosOnlineShowAnons
					? []
					: [ 'userid != 0' ]
		];
	}

	// use classical LIMIT/OFFSET instead of sorting by table key
	function reallyDoQuery( $offset, $limit, $descending ) {
		$info = $this->getQueryInfo();
		$tables = $info['tables'];
		$fields = $info['fields'];
		$conds = isset( $info['conds'] ) ? $info['conds'] : [];
		$options = isset( $info['options'] ) ? $info['options'] : [];

		$options['LIMIT']  = intval( $limit );
		$options['OFFSET'] = intval( $offset );

		$res = $this->mDb->select( $tables, $fields, $conds, __METHOD__, $options );

		return new ResultWrapper( $this->mDb, $res );
	}

	function getIndexField() {
		return 'username'; // dummy
	}

	function formatRow( $row ) {
		global $wgWhosOnlineShowRealName;

		$userPageLink = Title::makeTitle( NS_USER, $row->username )->getFullURL();
		$name = $row->username;
		if ( $wgWhosOnlineShowRealName ) {
			$user = User::newFromName( $name );
			if ( $user ) {
				$realName = $user->getRealName();
				if ( $realName !== '' ) {
					$name = $realName;
				}
			}
		}
		return '<li><a href="' . htmlspecialchars( $userPageLink, ENT_QUOTES ) . '">' .
			htmlspecialchars( $name, ENT_QUOTES ) . '</a></li>';
	}

	// extra methods
	function countUsersOnline() {
		$row = $this->mDb->selectRow(
			'online',
			'COUNT(*) AS cnt',
			'userid != 0',
			__METHOD__,
			'GROUP BY username'
		);
		$users = (int)$row->cnt;

		return $users;
	}

	function getNavigationBar() {
		return $this->getLanguage()->viewPrevNext(
			SpecialPage::getTitleFor( 'WhosOnline' ),
			$this->mOffset,
			$this->mLimit,
			[],
			$this->countUsersOnline() < ( $this->mLimit + $this->mOffset ) // show next link
		);
	}
}

class SpecialWhosOnline extends IncludableSpecialPage {
	public function __construct() {
		parent::__construct( 'WhosOnline' );
	}

	// get list of logged-in users being online
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
