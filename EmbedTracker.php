<?php

/**
 * Embed Tracker:
 * Keeps track of embed requests for each article (via WikiEmbed) and displays them on a Special Page,
 *
 * Usage:
 * 	-Add require_once("extensions/embedTracker/embedTracker.php"); in LocalSettings.php
 *  -Run the SQL query in AddStatsTable.sql to create the DB table (substituting your SQL table prefix
 *		before 'stats')
 *  -For caching, set $wgEmbedTrackerCache (below) to the desired directory and make sure it
 *		exists and is writeable.
 */

if(!defined('MEDIAWIKI'))exit(1);

$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'Embed Tracker',
	'description' => 'Stores info about embed requests from external sites in database',
	'version' => '0.1',
	'author' => 'Eric Jackisch'
);

//Tell MediaWiki where to find the files for the special page
$wgAutoloadClasses['EmbedTrackerStats'] = dirname(__FILE__) . '/EmbedTrackerStatsPage.php';
$wgExtensionMessagesFiles['EmbedTrackerStats'] = dirname(__FILE__) . '/EmbedTracker.i18n.php';
$wgSpecialPages['EmbedTrackerStats'] = 'EmbedTrackerStats';

$wgEmbedTrackerCache = $IP.'/cache/EmbedTracker';

require_once('EmbedTrackerHooks.php');

$wgHooks['MediaWikiPerformAction'][] = 'EmbedTrackerHooks::trackRequest';
$wgHooks['SkinTemplateToolboxEnd'][] = 'EmbedTrackerHooks::toolBoxLink';
$wgHooks['ArticleAfterFetchContent'][] = 'EmbedTrackerHooks::showEmbedsOnArticlePage';