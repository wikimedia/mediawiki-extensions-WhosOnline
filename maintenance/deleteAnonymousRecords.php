<?php
/**
 * Delete anonymous user records from the online table
 *
 * This maintenance script removes all records from the online table
 * where userid = 0 (anonymous/unauthenticated users). This is useful
 * for cleaning up databases that accumulated anonymous user records
 * before the extension was modified to track only authenticated users.
 *
 * Usage:
 *   php maintenance/run.php extensions/WhosOnline/maintenance/deleteAnonymousRecords.php
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * @file
 * @ingroup Maintenance
 * @author Greg Rundlett
 * @license GPL-2.0-or-later
 */

use MediaWiki\Maintenance\Maintenance;
use MediaWiki\MediaWikiServices;

// @codeCoverageIgnoreStart
require_once __DIR__ . '/../../../maintenance/Maintenance.php';
// @codeCoverageIgnoreEnd

/**
 * Maintenance script to delete anonymous user records from the online table
 */
class DeleteAnonymousRecords extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Delete all anonymous user records from the online table' );
		$this->addOption( 'dry-run', 'Show what would be deleted without actually deleting', false, false );
		$this->requireExtension( 'WhosOnline' );
	}

	/**
	 * @inheritDoc
	 */
	public function execute() {
		$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
		$dryRun = $this->hasOption( 'dry-run' );

		// Check if the table exists
		if ( !$dbw->tableExists( 'online', __METHOD__ ) ) {
			$this->error( "Table 'online' does not exist. Has the extension been installed?\n" );
			return false;
		}

		// Count anonymous records before deletion
		$count = $dbw->selectRowCount(
			'online',
			'*',
			[ 'userid' => 0 ],
			__METHOD__
		);

		if ( $count === 0 ) {
			$this->output( "No anonymous user records found in the online table.\n" );
			return true;
		}

		$this->output( "Found $count anonymous user record(s) in the online table.\n" );

		if ( $dryRun ) {
			$this->output( "DRY RUN: Would delete $count record(s). Run without --dry-run to actually delete.\n" );
			return true;
		}

		// Delete anonymous user records
		$dbw->delete(
			'online',
			[ 'userid' => 0 ],
			__METHOD__
		);

		$deletedCount = $dbw->affectedRows();
		$this->output( "Successfully deleted $deletedCount anonymous user record(s) from the online table.\n" );

		return true;
	}
}

// @codeCoverageIgnoreStart
$maintClass = DeleteAnonymousRecords::class;
require_once RUN_MAINTENANCE_IF_MAIN;
// @codeCoverageIgnoreEnd
