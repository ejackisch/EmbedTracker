<?php
if(!defined('MEDIAWIKI'))exit(1);

/**
 *	Class for the Special:EmbedTrackerStats page
 */
class EmbedTrackerStats extends SpecialPage {
	function __construct(){
		parent::__construct('EmbedTrackerStats');
	}
	
	
	/**
	 *	Build and output the content of the Special:EmbedTrackerStats page when it is accessed.
	 */
	function execute(){
		global $wgRequest, $wgEmbedTrackerCache;
		$articleTitle = $wgRequest->getText('article_title');
		$this->setHeaders();
		
		if($articleTitle):	
			//If we have an article title, get all the data for that article from the DB
			$this->singleArticle($articleTitle);		
		else:	
			//No $articleTitle is supplied so get the data for ALL articles from DB
			$this->allArticles();		
		endif; 
	}

	
	/**
	 *	Outputs stats page for all articles
	 */	
	function allArticles(){
		global $wgOut, $wgServer, $wgScript, $wgEmbedTrackerCache;
		$dbr = wfGetDB( DB_SLAVE );
		$referers=array();
		
		$cacheFile = $wgEmbedTrackerCache.'/Special:EmbedTrackerStats.html';
			if(is_writable($cacheFile) && time() < filemtime($cacheFile) + 60*60*8 ):
				//If possible, just show a file from cache.
				$wgOut->addHTML(file_get_contents($cacheFile));
				$wgOut->addHTML("<p>(Last refreshed: " . date('M j Y \a\t g:iA',filemtime($cacheFile)) . ")</p>");
			else:
				//Cache is expired or does not exist, so go to the database and build the page...
				
				$dbResult = $dbr->select(
					'EmbedTrackerStats',
					array('article_title', 'first_accessed', 'last_accessed', 'referer', 'hits'),	//select columns
					'',
					__METHOD__,
					array('ORDER BY' => 'article_title ASC')										//options
				);
				
				//Group them into an array of 2D arrays, one for each article
				foreach ($dbResult as $row):
					$referers[$row->article_title][] = $row;
				endforeach;
				
				//Output a table for each articles details
				foreach($referers as $currentArticleTitle => $table):
					$total = count($table);
					$wgOut->addHTML('<div class="mw-collapsible mw-collapsed" style="width:80%;">');
					$wgOut->addHTML('Embeds for the article: <a href="' . $wgServer . $wgScript . '/' . $currentArticleTitle . '">' . $currentArticleTitle . '</a> (Total: ' . $total . ')');
					$wgOut->addHTML('<div class="mw-collapsible-content">');
					$this->outputStatsTable($table, true);
					$wgOut->addHTML('</div></div>');
				endforeach;
				
				if(is_writable($wgEmbedTrackerCache)):
					file_put_contents($cacheFile,$wgOut->getHTML());
				endif;
			endif;
	}


	/**
	 *	Outputs stats page for a single article
	 *	@param $articleTitle 
	 *		String with Article Title (as in database) to display info for.
	 */	
	function singleArticle($articleTitle){
		global $wgOut, $wgServer, $wgScript;
		$dbr = wfGetDB( DB_SLAVE );
		$referers=array();
	
		$dbResult = $dbr->select(
				'EmbedTrackerStats',
				array('article_title', 'first_accessed', 'last_accessed', 'referer', 'hits'),	//select columns
				'article_title = ' . $dbr->addQuotes($articleTitle), 							//condition
				__METHOD__,
				array('ORDER BY' => 'last_accessed DESC')										//options
		);	
			
		//Put it all into a simple array
		foreach ($dbResult as $row):
			$referers[] = $row;
		endforeach;
		
		//Output the HTML
		$wgOut->addHTML('<div style="width:80%">');
		$wgOut->addHTML('Embeds for the article: <a href="' . $wgServer . $wgScript . '/' . $articleTitle . '">' . $articleTitle . '</a> ');
		$this->outputStatsTable($referers, false);
		$wgOut->addHTML('</div>');
	}
	
	
	/**
	 *	Outputs an HTML table with the stats for an article
	 *	@param $data 
	 *		2d array consisting of referers and their details
	 */
	function outputStatsTable($data){
		global $wgOut, $wgServer, $wgScript;
		
		//$wgOut->addHTML('Embeds for the article: <a href="' . $wgServer . $wgScript . '/' . $title . '">' . $title . '</a> ' . $total);
		
		$wgOut->addHTML('<table class="wikitable sortable" style="width:100%">');
		$wgOut->addHTML('
			<tr>
				<th>Referer</th>
				<th style="width:130px;">First Added</th>
				<th style="width:130px;">Last Access Date</th>
				<th style="width:75px;">Accesses</th>
			</tr>
		');
			
		foreach ($data as $row):
			$wgOut->addHTML('
			<tr>
				<td><a href="' . htmlspecialchars($row->referer) . '">' . htmlspecialchars($row->referer) . '</a></td>
				<td>' . date('Y-m-d \a\t H:i', $row->first_accessed) . '</td>
				<td>' . date('Y-m-d \a\t H:i', $row->last_accessed) . '</td>
				<td>' . $row->hits . '</td>
			</tr>
			');
		endforeach;
		
		$wgOut->addHTML('</table>');
	}
	
};
