<?php
/*
 * Created on Oct 15, 2007
 * 
 * SVN INFORMATION:::
 * ------------------
 * Last Author:         $Author$ 
 * Current Revision:    $Revision$ 
 * Repository Location: $HeadURL$ 
 * Last Updated:        $Date$
 */

require_once(dirname(__FILE__) .'/../../lib/contactClass.php');

$contactObj = new contactClass($page->db);


if($_POST) {
	//TODO: handle changes.
}
else {
	if(count($sectionArr) == 4 && $sectionArr[2] == 'view') {
		$contactId = $sectionArr[3];
	}
	
	if(is_numeric($_GET['contactId'])) {
		
		//TODO: parse contact details.
	}
	else {
		$page->rip_all_block_rows('content');
		$contactListing = $contactObj->get_all_contacts();
		
		$baseRow = $page->templateRows['dataRow'];
		
		$myRow = "";
		foreach($contactListing as $conId=>$data) {
			$data['rowColor'] = swapValue($rowColor1, "rgb(213, 213, 213)", "rgb(194, 194, 194)");
			$data['rowColor2'] = swapValue($rowColor2,"#D5D5D5", "#C2C2C2");
			$myRow .= $page->mini_parser($baseRow, $data, '{', '}');
		}
		$page->add_template_var('dataRow', $myRow);
	}
}

?>
