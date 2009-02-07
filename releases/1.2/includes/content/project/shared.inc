<?php
/*
 * SVN INFORMATION:::
 * ------------------
 * Last Author:         $Author$ 
 * Current Revision:    $Revision$ 
 * Repository Location: $HeadURL$ 
 * Last Updated:        $Date$
 */

if($_POST) {
	$projectId = $_POST['ID'];
	$proj->projectId = $projectId;
	
	
	//figure out what we need to change...
	if($_POST['action'] == "filter") {
		if($_POST['reset_filter']) {
			$page->ui->unset_cache("$module/filter");
			$result = "unset";
		}
		else {
			//store the filter.
			$page->ui->set_cache("$module/filter", $_POST['filter']);
			$result = "set";
		}
	}
	elseif($_POST['ID'] == "new") {
		unset($_POST['ID']);
		$result = $proj->create_project($_POST['data']);
		$extra = "/view?ID=". $result;
		#exit;
	}
	else {
		//TODO: check to see if somebody else has updated the project since this user viewed the page...
		$updateRes = $proj->update_project($_POST['data']);
		if($updateRes) {
			set_message_wrapper(array(
				"title"		=> "Successfully Updated",
				"message"	=> "Update successful.  Good job!",
				"type"		=> "status"
			));
			$extra = "/view?ID=". $projectId;
		}
		else {
			set_message_wrapper(array(
				"title"		=> "Unable to Update",
				"message"	=> "Something happened, and the update failed.  Sorry, dude.",
				"type"		=> "fatal"
			));
		}
	}
	 
	//okay, now send 'em to the right page.
	conditional_header("/content/project". $extra);
	exit;
}
else {
	
	// +++ SETUP FILTERING +++ 
	$filterData = $page->ui->get_cache("$module/filter");
	if(!is_array($filterData)) {
		//no filter array... set one.
		$filterData = array();
		$page->ui->set_cache("$module/filter", $filterData);
	}
	
	//setup the "linkExtra"...
	if(is_numeric($_GET['linkToIssue'])) {
		$page->ui->set_cache("project/linkToIssue", $_GET['linkToIssue']);
	}
	
	if(is_numeric($page->ui->get_cache("project/linkToIssue"))) {
		$page->set_message_wrapper(array(
			'title'		=> "Re-linking Issue",
			'message'	=> "Please select the project that issue #". $page->ui->get_cache("project/linkToIssue") ." will be linked to.",
			'type'		=> "notice",
			'linkURL'	=> "/content/helpdesk/view?ID=". $page->ui->get_cache("project/linkToIssue"),
			'linkText'	=> "Back to issue #". $page->ui->get_cache("project/linkToIssue")
		));
	}
		
	//the data will already show only items with the given status, so don't add that to the filter again.
	$primaryFilterArr = array("status_id" => $filterData['status_id']);
	if($filterData['status_id'] == "all") {
		//no filter: we're showing everything.
		unset($filterData['status_id']);
	}
	elseif(!isset($filterData['status_id'])) {
		//this shows everything EXCEPT the "ended" items.
		$filterData['status_id'] = $GLOBALS['STATUS_NOTENDED'];
	}
	$filter = $filterData;
	unset($filter['status']);
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
		
			$expand = "clear";	
			if($subArr['num_children'] > 0) {
				#if(in_array($id, $expandArr)) {
				if(isset($expandArr[$id])) {
					$expand = "open";
					$expand2= "close";
				}
				else {
					$expand = "close";
					$expand2="open";
				}
			}
			$repArr = $subArr;
			$repArr['expand'] = $expand;
			$repArr['expand2'] = $expand2;
			$repArr['rowColor'] = $rowColor1;
			$repArr['rowColor2'] = $rowColor2;
			$repArr['levelPad'] = $levelPad;
			$repArr['level'] = $linkLevel;//denotes which "level" the subprojects should be on...
			
			//if the current user owns the project, they can update it... show the stupid little box.
			$repArr['status'] .= " %";
			
			
			$myRow .= mini_parser($baseRow, $repArr, "%%", "%%");
		}
	}
	
	//done processing it... return our processed row.
	return($myRow);
}//end process_row()
//==========================================================================================


//==========================================================================================
function process_subProjects(&$page, &$proj, $filter) {
	//get our list of expanded projects.
	$expandArr = $page->ui->get_cache("expandArr");
	
	//okay, now let's start grabbing the projects.
	foreach($expandArr as $id=>$level) {
		//
		$filter['ancestry_level'] = ($level +1);
		$projectArr = $proj->list_projects($id,NULL,$filter);
		if(is_array($projectArr)) {
			$myRow = process_row($page, $projectArr, $expandArr, $page->templateRows['project_row'], $level);
			$page->add_template_var("subproject_". $id, $myRow);
		}
		else {
			debug_print("process_subProjects(): id #$id disappeared...");
			unset($expandArr[$id]);
			$page->ui->set_cache("expandArr", $expandArr);
		}
	}
	
}//end process_subProjects()
//==========================================================================================
?>
