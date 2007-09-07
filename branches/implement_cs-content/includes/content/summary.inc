<?php
/*
 * Created on Dec 7, 2006
 * 
 */

//remove block rows.
$blockRows = $page->set_all_block_rows('content');

//retrieve the list of projects ($proj initialized in parent script).
$filterArr = array('status_id' => array(2,6));
$projectsArr = $proj->list_projects(NULL,NULL,$filterArr);

$projectCount = 0;
if(is_array($projectsArr)) {
	$projectCount = count($projectsArr);
}
$page->add_template_var('projectCount', $projectCount);
$projectRows = process_row($page, $projectsArr, array(), $page->templateRows['project_row'], 0);
$page->add_template_var('project_row', $projectRows);



//  +++ DISPLAY REPORTS +++
{
	//retrieve the data.
	$startReportDate = date('Y-m-d', strtotime('-1 month'));
	$endReportDate = date('Y-m-d');
	$myReportsList = $proj->logsObj->get_reports($startReportDate, $endReportDate);
	
	//if we got anything, let's display it.
	$reportCount = 0;
	$myReportRow = "";
	if(is_array($myReportsList) && count($myReportsList)) {
		$reportCount = count($myReportsList);
		
		//got data: loop through it.
		$baseRow = $page->templateRows['report_row'];
		$baseRow_noLink = $page->templateRows['report_row-noLink'];
		foreach($myReportsList as $index=>$dataArr) {
			
			$myBaseRow = $baseRow;
			
			//setup some background colors for mouseover effects.
			swapValue($reportBg1, "rgb(213, 213, 213)", "rgb(194, 194, 194)");
			swapValue($reportBg2, "#d5d5d5", "#c2c2c2");
			$dataArr['reportBg1'] = $reportBg1;
			$dataArr['reportBg2'] = $reportBg2;
			
			if(is_numeric($dataArr['record_id'])) {
				//setup the "module".
				$myModule = strtolower($dataArr['category_name']);
				$dataArr['myModule'] = $myModule;
			}
			else {
				//no record, no link.
				$myBaseRow = $baseRow_noLink;
			}
			
			//make the details pretty.
			$dataArr['details'] = str_replace("\n", "<BR>\n", $dataArr['details']);
			
			//parse the data.
			$myReportRow .= mini_parser($myBaseRow, $dataArr, '%%', '%%');
		}
	}
	$page->add_template_var('reportCount', $reportCount);
	$page->add_template_var('report_row', $myReportRow);
}


// +++ HELPDESK ISSUES +++ 
//pull the recordset.
$helpdeskCriteria = array('leader_contact_id' => $_SESSION['contact_id'], 'status_id' => array(0,1,2,6));
$helpdeskArr = $proj->helpdeskObj->get_records($helpdeskCriteria, array('priority'=>'ASC'));

if(is_array($helpdeskArr)) {
	//got some data.
	$page->add_template_var('helpdeskIssueCount', count($helpdeskArr));
}
else {
	//no data.
	$page->add_template_var('helpdeskIssueCount', "0");
}

//if there's an array, show it...
if(is_array($helpdeskArr) && count($helpdeskArr) > 0) {
	//got good data.
	$baseRow = $page->templateRows['helpdesk_row'];
	foreach($helpdeskArr as $helpdeskId=>$subData) {
		unset($deleteLink);
		if($subData['von'] == $_SESSION['uid'])
		{
			$deleteLink = mini_parser($baseDeleteLink, $subData, "%%", "%%");
		}
	
	
		swapValue($bgColor, "rgb(213, 213, 213)", "rgb(194, 194, 194)");
		swapValue($bgColor2, "#d5d5d5", "#c2c2c2");
		
		$subData['bgColor'] = $bgColor;
		$subData['bgColor2']= $bgColor2;
	
		//define the text fields, so they can be cleaned & truncated.
		$previewLength = 110;
		$textFieldArr = array("name", "note", "solution", "remark");
		foreach($textFieldArr as $cleanThisField) {
			//truncate them...
			$subData[$cleanThisField] = truncate_string($subData[$cleanThisField], $previewLength, "... [more]", TRUE);
				
			//now clean 'em (clean after to avoid truncating html special characters.
			$subData[$cleanThisField] = cleanString($subData[$cleanThisField], "htmlentity");
			
			//more special parsing to ensure GenericPage{} doesn't make the $ (and random characters after it) disappear.
			$subData[$cleanThisField] = str_replace('$', '\$', $subData[$cleanThisField]);
		}
		
		//force the "text" preview to wrap.
		$subData['note'] = wordwrap($subData['note'], (($previewLength * 2)/3), "\n", 1);
		
		//truncate the email address...
		$subData['email'] = truncate_string($subData['email'], 20, "...", TRUE);
		
		$subData['helpdesk_id'] = $helpdeskId;
		unset($subData['id']);
		
		//show the "created" and "updated" strings properly...
		$subData['created'] = parse_date_string($subData['div1'],TRUE);
		$subData['updated'] = parse_date_string($subData['div2'],TRUE);
		$finalRow .= mini_parser($baseRow, $subData, "%%", "%%");
	}
	$page->add_template_var("helpdesk_row", $finalRow);
}


//==========================================================================================
function process_row(&$page, $projectArr, $expandArr, $baseRow, $level=0) {
	//loop through the array...
	$linkLevel = $level+1;
		
	
	//pre-pad subprojects, so they appear indented.
	if($level > 0) {
		$basePad = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		for($i=0; $i < $linkLevel; $i++) {
			$levelPad .= $basePad;
		}
	}
	if(is_array($projectArr)) {
		foreach($projectArr as $id=>$subArr) {
			//parse stuff into the template row...
			$rowColor1 = swapValue($rowColor1, "rgb(213, 213, 213)", "rgb(194, 194, 194)");
			$rowColor2 = swapValue($rowColor2,"#D5D5D5", "#C2C2C2");
		
			$expand = "t";	
			if($subArr['subproject_count'] > 0) {
				#if(in_array($id, $expandArr)) {
				if(isset($expandArr[$id])) {
					$expand = "open";
					$expand2= "close";
				} else {
					$expand = "close";
					$expand2="open";
				}
			}
			$repArr = array(
				"id"		=> $id,
				"expand"	=> $expand,
				"expand2"	=> $expand2,
				"rowColor"	=> $rowColor1,
				"rowColor2"	=> $rowColor2,
				"levelPad"	=> $levelPad,
				"level"		=> $linkLevel			//denotes which "level" the subprojects should be on...
			);
		
			foreach($subArr as $f=>$v) {
				$repArr[$f] = $v;
			}
			
			//if the current user owns the project, they can update it... show the stupid little box.
			$repArr['status'] .= " %";
			
			$myRow .= mini_parser($baseRow, $repArr, "%%", "%%");
		}
	}
	
	//done processing it... return our processed row.
	return($myRow);
}//end process_row()
//==========================================================================================
?>
