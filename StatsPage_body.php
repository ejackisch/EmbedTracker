<?php

class SpecialStatsPage extends SpecialPage {
	function __construct(){
		parent::__construct('StatsPage');
	}

	

	
	/**
	 *	Builds the content of the Special:StatsPage when it is accessed.
	 */
	function execute(){
		global $wgRequest, $wgOut, $wgServer, $wgScript, $wgEmbedTrackerCache;
		$articleTitle = $wgRequest->getText('article_title');
		$this->setHeaders();
		$dbr = wfGetDB( DB_SLAVE );
		$result=array();
		
		if($articleTitle):	
			//If we have an article title, get all the data for that article from the DB
			
			$res = $dbr->select(
				'stats',
				array('article_title', 'first_accessed', 'last_accessed', 'referer', 'hits'),	//select columns
				'article_title = ' . $dbr->addQuotes($articleTitle), 							//condition
				__METHOD__,
				array('ORDER BY' => 'last_accessed DESC')										//options
			);	
			
			//Put it all into a simple array
			foreach ($res as $row):
				$result[] = $row;
			endforeach;
		
			//Output the HTML
			$wgOut->addHTML('<div style="width:80%">');
			$wgOut->addHTML('Embeds for the article: <a href="' . $wgServer . $wgScript . '/' . $articleTitle . '">' . $articleTitle . '</a> ');
			$this->outputStatsTable($result, false);
			$wgOut->addHTML('</div>');
		
		
		
		else:	
			//$articleTitle is false so get the data for ALL articles from DB
			
			$cacheFile = $wgEmbedTrackerCache.'/_All.html';
			if(is_writable($cacheFile) && time() < filemtime($cacheFile) + 60*60*24 ):
				//If possible, just show a file from cache.
				$wgOut->addHTML(file_get_contents($cacheFile));
				$wgOut->addHTML("<p>(Last refreshed: " . date('M j Y \a\t g:iA',filemtime($cacheFile)) . ")</p>");
			else:
				//Cache is expired or does not exist, so go to the database and build the page...
				
				$res = $dbr->select(
					'stats',
					array('article_title', 'first_accessed', 'last_accessed', 'referer', 'hits'),	//select columns
					'',
					__METHOD__,
					array('ORDER BY' => 'article_title ASC')										//options
				);
				
				//Group them into an array of 2D arrays, one for each article
				foreach ($res as $row):
					$result[$row->article_title][] = $row;
				endforeach;
				
				//Output a table for each articles details
				foreach($result as $key => $table):
					$total = count($table);
					$wgOut->addHTML('<div class="mw-collapsible mw-collapsed" style="width:80%;">');
					$wgOut->addHTML('Embeds for the article: <a href="' . $wgServer . $wgScript . '/' . $key . '">' . $key . '</a> (Total: ' . $total . ')');
					$wgOut->addHTML('<div class="mw-collapsible-content">');
					$this->outputStatsTable($table, true);
					$wgOut->addHTML('</div></div>');
				endforeach;
				
				if(is_writable($wgEmbedTrackerCache)):
					file_put_contents($cacheFile,$wgOut->getHTML());
				endif;
			endif;
		endif; //if(!$articleTitle)
	}
	
	
	
	
	/**
	 *	Outputs an HTML table with the stats for an article
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
	
}
?>