<?php
/*
 * SVN INFORMATION:::
 * ------------------
 * Last Author:         $Author:crazedsanity $ 
 * Current Revision:    $Revision:637 $ 
 * Repository Location: $HeadURL:https://cs-project.svn.sourceforge.net/svnroot/cs-project/trunk/includes/content/helpdesk.inc $ 
 * Last Updated:        $Date:2007-11-20 11:04:33 -0600 (Tue, 20 Nov 2007) $
 */


	$helpdeskId = $_GET['ID'];
	$action = $page->ftsSections[2];
	
	if(is_numeric($page->ui->get_cache("project/linkToIssue"))) {
		$page->ui->unset_cache("project/linkToIssue");
	}
	
	
	//remove the link in the session that they should return to if not requested.
	if($_SESSION['goBackLink'] && !$_GET['goBack']) {
		unset($_SESSION['goBackLink']);
	}
	
	
		//DISPLAY ALL AVAILABLE HELPDESK ISSUES.
		//change the content of the page properly.
		$page->set_all_block_rows("content", array("helpdesk_search"));
		
		$filterData = $page->ui->get_cache("helpdesk/filter");
		if(is_array($filterData)) {
			$page->add_template_var("filter_keyword", $filterData['keyword']);
			$page->add_template_var("filter_field_". $filterData['field'] ."_selected", "selected");
		}
		else {
			//no filter array... set one.
			$filterData = array();
			$page->ui->set_cache("helpdesk/filter", $filterData);
		}
		$page->add_template_var("filter_status_id_". $filterData['status_id'] ."_selected", "selected");
		
		if($filterData['status_id'] == 'all') {
			$primaryFilterArr = NULL;
		}
		elseif(is_numeric($filterData['status_id'])) {
			//the data will already show only items with the given status, so don't add that to the filter again.
			$primaryFilterArr = array("status_id" => $filterData['status_id']);
		}
		else {
			$primaryFilterArr = array();
		}
		$filter = $filterData;
		unset($filter['status_id'], $filter['status']);
	
		//pull the recordset.
		$helpdeskArr = $proj->helpdeskObj->get_records($primaryFilterArr, $currentSortArr, $filter);
		
		$issueCount = 0;
		if(is_array($helpdeskArr)) {
			$issueCount = count($helpdeskArr);
		}
		$page->add_template_var("numRows", $issueCount);
		//if there's an array, show it...
		if(is_array($helpdeskArr) && count($helpdeskArr) > 0) {
			//got good data.
			$baseRow = $page->templateRows['helpdesk_row'];
			foreach($helpdeskArr as $helpdeskId=>$subData) {
				unset($deleteLink);
				
				
				swapValue($bgColor, "rgb(213, 213, 213)", "rgb(194, 194, 194)");
				swapValue($bgColor2, "#d5d5d5", "#c2c2c2");
				
				$subData['bgColor'] = $bgColor;
				$subData['bgColor2']= $bgColor2;
				
				//define the text fields, so they can be cleaned & truncated.
				$previewLength = 110;
				$textFieldArr = array("name", "subject");
				foreach($textFieldArr as $cleanThisField) {
					//truncate them...
					$subData[$cleanThisField] = truncate_string($subData[$cleanThisField], $previewLength, "... [more]", TRUE);
					
					//now clean 'em (clean after to avoid truncating html special characters.
					$subData[$cleanThisField] = cleanString($subData[$cleanThisField], "htmlentity");
					
					//more special parsing to ensure GenericPage{} doesn't make the $ (and random characters after it) disappear.
					$subData[$cleanThisField] = str_replace('$', '\$', $subData[$cleanThisField]);
				}
				
				//parse bbCode in preview...
				$subData['subject'] = $bbCodeParser->parseString($subData['subject']);
				
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

?>