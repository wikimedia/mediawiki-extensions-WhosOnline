<?php
/**
 * @file
 */

use MediaWiki\MediaWikiServices;

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
		global $wgWhosOnlineTimeout;

		$services = MediaWikiServices::getInstance();
		// don't write to the DB if the DB is read-only
		if ( $services->getReadOnlyMode()->isReadOnly() ) {
			return true;
		}

		$user = $out->getUser();
		$userOptionsManager = $services->getUserOptionsManager();
		$lastVisit = $userOptionsManager->getOption( $user, 'LastVisit' );
		$currentTime = wfTimestamp( TS_UNIX );
		if ( empty( $lastVisit ) || $currentTime - $lastVisit > $wgWhosOnlineTimeout ) {

			if ( !$user->isAnon() ) {
				$userOptionsManager->setOption( $user, 'LastVisit', $currentTime );
			}

			// write to DB (use master)
			$dbw = wfGetDB( DB_PRIMARY );
			$now = gmdate( 'YmdHis', time() );

			// row to insert to table
			$row = [
				'userid' => $user->getId(),
				'username' => $user->getName(),
				'timestamp' => $now,
				'wikiid' => $out->getConfig()->get( 'DBname' )
			];

			$method = __METHOD__;
			$dbw->onTransactionCommitOrIdle( static function () use ( $dbw, $method, $row, $services ) {
				$dbw->upsert(
					'online',
					$row,
					[ [ 'userid', 'username', 'wikiid' ] ],
					[ 'timestamp' => $row['timestamp'] ],
					$method
				);

				// Per ApiQueryWhosOnline.php:
				// Not using $cache->makeKey() on $key intentionally to keep the key a
				// global one; makeKey() automagically adds the current DB name to it as
				// a prefix
				$key = 'whosonline:data';
				$services->getMainWANObjectCache()->delete( $key );
			} );
		}
	}

	/**
	 * Apply database schema changes when MediaWiki core updater script (update.php) is re-run
	 *
	 * @param DatabaseUpdater $updater
	 */
	public static function onLoadExtensionSchemaUpdates( $updater ) {
		$updater->addExtensionTable( 'online', __DIR__ . '/../sql/whosonline.sql' );
		// wikiid field since WhosOnline version 1.8.0
		if ( !$updater->getDB()->fieldExists( 'whosonline', 'wikiid' ) ) {
			$updater->addExtensionField( 'online', 'wikiid',
				__DIR__ . '/../sql/patch-add-wikiid-field.sql' );
		}
	}

}
