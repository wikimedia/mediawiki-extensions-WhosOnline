<?php
/**
 * @file
 */
class WhosOnlineHooks {

	/**
	 * Update online data.
	 *
	 * @param OutputPage &$out
	 * @param Skin &$skin
	 * @throws \ConfigException
	 * @return true|void
	 */
	public static function onBeforePageDisplay( OutputPage &$out, Skin &$skin ) {
		// don't write to the DB if the DB is read-only
		if ( wfReadOnly() ) {
			return true;
		}

		// write to DB (use master)
		$dbw = wfGetDB( DB_PRIMARY );
		$now = gmdate( 'YmdHis', time() );

		$user = $out->getUser();
		// row to insert to table
		$row = [
			'userid' => $user->getId(),
			'username' => $user->getName(),
			'timestamp' => $now
		];

		$method = __METHOD__;
		$dbw->onTransactionCommitOrIdle( static function () use ( $dbw, $method, $row ) {
			$dbw->upsert(
				'online',
				$row,
				[ [ 'userid', 'username' ] ],
				[ 'timestamp' => $row['timestamp'] ],
				$method
			);
		} );
	}

	/**
	 * @param DatabaseUpdater $updater
	 */
	public static function onLoadExtensionSchemaUpdates( $updater ) {
		$updater->addExtensionTable( 'online', __DIR__ . '/../whosonline.sql' );
	}

}
