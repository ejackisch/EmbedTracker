<?php
if(!defined('MEDIAWIKI'))exit(1);

$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'Embed Tracker',
	'description' => 'Stores info about embed requests from external sites in database',
	'version' => '0.1'
	'author' => 'Eric Jackisch'
);

//Tell MediaWiki where to find the files for the special page
$dir = dirname(__FILE__);
$wgAutoloadClasses['SpecialStatsPage'] = $dir . '/StatsPage_body.php';
$wgExtensionMessagesFiles['StatsPage'] = $dir . '/StatsPage.i18n.php';
$wgSpecialPages['StatsPage'] = 'SpecialStatsPage';