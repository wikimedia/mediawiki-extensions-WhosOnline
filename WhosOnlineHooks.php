<?php
/**
 * @file
 */
class WhosOnlineHooks {

	// update online data
	public static function onBeforePageDisplay( OutputPage &$out, Skin &$skin ) {
		// don't write to the DB if the DB is read-only
		if ( wfReadOnly() ) {
			return true;
		}

		// write to DB (use master)
		$dbw = wfGetDB( DB_MASTER );
		$now = gmdate( 'YmdHis', time() );

		$user = $out->getUser();
		// row to insert to table
		$row = array(
			'userid' => $user->getId(),
			'username' => $user->getName(),
			'timestamp' => $now
		);

		$method = __METHOD__;
		$dbw->onTransactionIdle( function() use ( $dbw, $method, $row ) {
			$dbw->upsert(
				'online',
				$row,
				array( array( 'userid', 'username' ) ),
				array( 'timestamp' => $row['timestamp'] ),
				$method
			);
		} );

		return true;
	}

	public static function onLoadExtensionSchemaUpdates( $updater ) {
		$updater->addExtensionUpdate( array( 'addTable', 'online',
			__DIR__ . '/whosonline.sql', true ) );
		return true;
	}

}