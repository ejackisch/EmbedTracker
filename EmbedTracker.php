<?php
/**
 * Embed Tracker:
 * Keeps track of embed requests for each article (via WikiEmbed) and displays them on a Special Page
 *
 * Usage:
 * 	-Add require_once("extensions/embedTracker/embedTracker.php"); in LocalSettings.php
 *  -Run the SQL query in stats_table.txt to create the DB table.
 *  -For caching, set $wgEmbedTrackerCache (below) to the desired directory and make sure it
 *		exists and is writeable.
 */
if(!defined('MEDIAWIKI'))exit(1);

$wgEmbedTrackerCache = $IP.'/cache/EmbedTracker';

include('StatsPage.php');

$wgHooks['MediaWikiPerformAction'][] = 'trackRequest';
$wgHooks['SkinTemplateToolboxEnd'][] = 'addToolBoxLink';
$wgHooks['ArticleAfterFetchContent'][] = 'showEmbedsOnArticlePage';



/**
 * Handles MediaWikiPerformAction event,
 * Stores requests from WikiEmbed or EmbedPage in DB
 */
function trackRequest( $output, $article, $title, $user, $request, $wiki ){
	
	$action = $request->getVal( 'action' );
	if( $action == 'render' ):	//We're only looking at embed requests which all use action=render
		$userAgent = $request->getHeader('USER-AGENT');
		$articleTitle = $request->getVal('title');
		$referer = getenv('HTTP_REFERER');
		$time = time();	
		
		//Determine if it's (probably) a WikiEmbed request and get the refering blog url if so
		if( strpos($userAgent, 'WordPress') !== false ):
			$referer = substr($userAgent, strpos($userAgent, ';')+2);
		elseif( strpos($userAgent, 'EmbedPage') !== false ):	//Is it from the javascript embed?		
			//...
		else:
			return true; 
		endif;
		
		if(empty($referer)):
			//No point in logging it if we don't know where it came from.
			return true;
		endif;
		
		//Check for an existing record
		$dbr = wfGetDB (DB_SLAVE);
		$res=$dbr->select(
				'stats',
				array('id','hits'),
				array('referer'=> $referer, 'article_title' => $articleTitle)
		);
		
		//If one exists, update it, otherwise insert a new one
		$dbw = wfGetDB (DB_MASTER);
		$results = $res->result;
		if( $res->numRows() ):
			$row=$res->fetchObject();
			$dbw->update(
					'stats',
					array('hits'=>($row->hits+1),'last_accessed'=>time()),
					array('id = '.$row->id)
			);
		else:
			$dbw->insert(
					'stats',
					array('article_title' => $articleTitle, 'referer' => $referer, 'first_accessed' => $time, 'last_accessed' => $time, 'hits' => 1 )
			);
		endif;
	endif;	//if action=render
	
	return true;	//continue with default performAction after we're done
}



/**
 * Handles ArticleAfterFetchContent event,
 * Displays a list of places the article is embeded at the bottom of the article 
 * (Comment out the ArticleAfterFetchContent hook at the top of this file to disable)
 */
function showEmbedsOnArticlePage( &$article, &$content ) {
	global $wgRequest, $wgServer, $wgScript;
	
	//Make sure we're actually on the article view page
	$action = $wgRequest->getVal( 'action' );
	if( ($action && $action != 'view') || $wgRequest->getVal('oldid') ):
		return true;
	endif;
	
	//Get the list of referers for this article
	$titleKey = $article->getTitle()->getPrefixedDBkey();
	$dbr = wfGetDB (DB_SLAVE);
	$res=$dbr->select(
			'stats',
			array('referer'),
			array('article_title' => $titleKey),
			__METHOD__,
			array('ORDER BY' => 'hits DESC')
	);
	
	if($res->numRows()):
	
		$stats='
			<br /><hr />
			<div style="font-size:85%;">
			This article is being embedded in other sites:
			<ul class="mw-collapsible mw-collapsed">
		';
		
		foreach ($res as $row):
			$stats.='<li>'.htmlspecialchars($row->referer).'</li>';
		endforeach;
		
		$stats.='<li>See [' . $wgServer . $wgScript . '/Special:StatsPage?article_title=' . $titleKey . ' Embed Stats] for details</li></ul></div>';
		
		$content .= $stats;
		
	endif;
	
	return true;
}



/**
 * Handles SkinTemplateToolboxEnd event,
 * Adds a link to the Stats page to the toolbox (if the theme supports it)
 */
function addToolBoxLink($template){
	global $wgServer, $wgScript, $wgArticle;
 
 	// If we're not looking at an article, don't show this link.
 	if (!$wgArticle) return true;
	
	$pageTitle = $wgArticle->getTitle();
    $pageUrl = $pageTitle->getPrefixedDBkey();
	echo '<li><a href="' . $wgServer . $wgScript . '/Special:StatsPage?article_title=' . $pageUrl . '">Embed Stats</a></li>';
	return true;
}