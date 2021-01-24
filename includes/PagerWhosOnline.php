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

class PagerWhosOnline extends IndexPager {
	function __construct() {
		parent::__construct();
		$this->mLimit = $this->mDefaultLimit;
	}

	/** @inheritDoc */
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

	/**
	 * use classical LIMIT/OFFSET instead of sorting by table key
	 * @inheritDoc
	 */
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

	/** @inheritDoc */
	function getIndexField() {
		return 'username'; // dummy
	}

	/** @inheritDoc */
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

	/**
	 * @return int
	 */
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

	/** @inheritDoc */
	function getNavigationBar() {
		return $this->buildPrevNextNavigation(
			SpecialPage::getTitleFor( 'WhosOnline' ),
			$this->mOffset,
			$this->mLimit,
			[],
			$this->countUsersOnline() < ( $this->mLimit + (int)$this->mOffset ) // show next link
		);
	}
}
