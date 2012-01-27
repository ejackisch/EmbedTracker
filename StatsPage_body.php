<?php

class SpecialStatsPage extends SpecialPage {
	function __construct(){
		parent::__construct('StatsPage');
	}
	
	/**
	 *	Builds the content of the Special:StatsPage when it is accessed.
	 */
	function execute(){
		global $wgRequest, $wgOut, $wgServer, $wgScript;
		$articleTitle = $wgRequest->getText('article_title');
		$this->setHeaders();
		
		//if(!$articleTitle){
		//Read the appropriate stuff from the database
		$dbr = wfGetDB( DB_SLAVE );
		
		
		$res = $dbr->select(
				'stats',
				array('article_title', 'first_accessed', 'last_accessed', 'referer', 'hits'),	//select columns
				'article_title = ' . $dbr->addQuotes($articleTitle), 							//condition
				__METHOD__,
				array('ORDER BY' => 'last_accessed DESC')										//options
		);	
		
		$result=array();
		foreach ($res as $row):
			$result[] = $row;
		endforeach;
		
		//The rest just outputs the HTML for the page content
		$wgOut->addHTML('Embeds for the article: <a href="' . $wgServer . $wgScript . '/' . $articleTitle . '">' . $articleTitle . '</a>');
		$this->outputStatsTable($result);
	}
	
	/**
	 *	Outputs an HTML table with the stats for an article
	 */
	function outputStatsTable($data){
		global $wgOut;
		$wgOut->addHTML('<table class="wikitable sortable" style="width:80%">');
		$wgOut->addHTML('
			<tr>
				<th>Referer</th>
				<th>First Added</th>
				<th>Last Access Date</th>
				<th>Total accesses</th>
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