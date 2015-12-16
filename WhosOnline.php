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

// Extension credits that show up on Special:Version
$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'WhosOnline',
	'version' => '1.7.0',
	'author' => 'Maciej Brencz',
	'descriptionmsg' => 'whosonline-desc',
	'url' => 'https://www.mediawiki.org/wiki/Extension:WhosOnline',
	'license-name' => 'GPL-2.0+',
);

// Showing anonymous users' IP addresses can be a security threat!
$wgWhosOnlineShowAnons = false;

// By default, only show usernames rather than user full names.
$wgWhosOnlineShowRealName = false;

// Timeout before WhosOnline decides a use has gone offline. Default 3600s (1h).
$wgWhosOnlineTimeout = 3600;

$wgMessagesDirs['WhosOnline'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['WhosOnlineAlias'] = __DIR__ . '/WhosOnline.alias.php';

// Set up the special page
$wgAutoloadClasses['SpecialWhosOnline'] = __DIR__ . '/WhosOnlineSpecialPage.php';
$wgAutoloadClasses['PagerWhosOnline'] = __DIR__ . '/WhosOnlineSpecialPage.php';
$wgSpecialPages['WhosOnline'] = 'SpecialWhosOnline';

$wgAutoloadClasses['WhosOnlineHooks'] = __DIR__ . '/WhosOnlineHooks.php';
$wgHooks['BeforePageDisplay'][] = 'WhosOnlineHooks::onBeforePageDisplay';
$wgHooks['LoadExtensionSchemaUpdates'][] = 'WhosOnlineHooks::onLoadExtensionSchemaUpdates';