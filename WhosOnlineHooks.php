<?php
/**
 * @file
 */
class WhosOnlineHooks {

	// update online data
	public static function onBeforePageDisplay() {
		global $wgUser;

		// write to DB (use master)
		$dbw = wfGetDB( DB_MASTER );
		$now = gmdate( 'YmdHis', time() );

		// row to insert to table
		$row = array(
			'userid' => $wgUser->getId(),
			'username' => $wgUser->getName(),
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