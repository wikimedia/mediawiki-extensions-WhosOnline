<?php
/**
 * API for WhosOnline extension
 *
 * @file
 * @ingroup API
 * @author Maciej Brencz <macbre@wikia-inc.com>
 * @author Maciej Błaszkowski <marooned@wikia-inc.com> - optimization
 */

class ApiQueryWhosOnline extends ApiQueryBase {

	/** @var WANObjectCache Injected via services magic and set up in the extension.json file */
	private $cache;

	/**
	 * @param ApiQuery $query
	 * @param string $action
	 * @param WANObjectCache $wanCache
	 */
	public function __construct(
		ApiQuery $query,
		$action,
		WANObjectCache $wanCache
	) {
		parent::__construct( $query, $action );
		$this->cache = $wanCache;
	}

	/**
	 * Main function
	 */
	public function execute() {
		$config = $this->getConfig();
		// Not using $cache->makeKey() on $key intentionally to keep the key a
		// global one; makeKey() automagically adds the current DB name to it as
		// a prefix
		$key = 'whosonline:data';
		$memcData = $this->cache->get( $key );

		if ( !is_array( $memcData ) ) {
			// database instance
			$dbr = $this->getDB();

			// build query
			$this->addTables( [ 'online' ] );
			$this->addFields( [ 'userid', 'username', 'timestamp', 'wikiid' ] );

			$this->addOption( 'ORDER BY', 'timestamp DESC' );

			$maxAge = wfTimestamp( TS_UNIX ) - $config->get( 'WhosOnlineTimeout' );
			$this->addWhere( "timestamp >= '$maxAge'" );
			if ( !$config->get( 'WhosOnlineShowAnons' ) ) {
				$this->addWhere( 'userid != 0' );
			}

			// build results
			$data = [];
			$res = $this->select( __METHOD__ );

			$i = $countUsers = $countAnons = 0;

			foreach ( $res as $row ) {
				// count both anons and logged-in
				if ( $row->userid != 0 ) {
					// add only logged-in
					$data[$i] = [
						'userid' => $row->userid,
						'user' => $row->username,
						'time' => $row->timestamp,
						'wikiid' => $row->wikiid
					];
					$countUsers++;
				} else {
					$countAnons++;
				}
				$i++;
			}

			$memcData = [
				'data' => $data,
				'countUsers' => $countUsers,
				'countAnons' => $countAnons
			];
			$this->cache->set( $key, $memcData, $config->get( 'WhosOnlineTimeout' ) );
		} else {
			$data = $memcData['data'];
			$countUsers = (int)$memcData['countUsers'];
			$countAnons = (int)$memcData['countAnons'];
		}

		$params = $this->extractRequestParams();
		$limit  = is_numeric( $params['limit'] ) ? $params['limit'] : 50;
		$offset = is_numeric( $params['offset'] ) ? $params['offset'] : 0;

		if ( !$config->get( 'WhosOnlinePerWiki' ) ) {
			// Look on every wiki and display only one record for one user (the newest)
			$tmpUsers = [];
			for ( $i = count( $data ) - 1; $i >= 0; $i-- ) {
				if ( empty( $tmpUsers[$data[$i]['user']] ) ) {
					$tmpUsers[$data[$i]['user']] = 1;
				} else {
					$data[$i]['userid'] == 0 ? $countAnons-- : $countUsers--;
					unset( $data[$i] );
				}
			}
		} else {
			// Look only on current wiki
			for ( $i = count( $data ) - 1; $i >= 0; $i-- ) {
				if ( $data[$i]['wikiid'] != $config->get( 'DBname' ) ) {
					$data[$i]['userid'] == 0 ? $countAnons-- : $countUsers--;
					unset( $data[$i] );
				}
			}
		}

		// limit results
		$data = array_slice( $data, $offset, $limit );

		$result = $this->getResult();
		ApiResult::setIndexedTagName( $data, 'online' );
		$result->addValue( [ 'query', $this->getModuleName() ], null, $data );
		$result->addValue( [ 'query', 'users' ], null, intval( $countUsers ) );
		$result->addValue( [ 'query', 'anons' ], null, intval( $countAnons ) );
	}

	/** @inheritDoc */
	public function getAllowedParams() {
		return [
			'limit' => [
				ApiBase::PARAM_TYPE => 'integer'
			],
			'offset' => [
				ApiBase::PARAM_TYPE => 'integer'
			]
		];
	}

	/** @inheritDoc */
	protected function getExamplesMessages() {
		return [
			'action=query&list=whosonline' =>
				'apihelp-query+whosonline-example-1',
			'action=query&list=whosonline&limit=5' =>
				'apihelp-query+whosonline-example-2',
			'action=query&list=whosonline&limit=5&offset=15' =>
				'apihelp-query+whosonline-example-3',
		];
	}

}
