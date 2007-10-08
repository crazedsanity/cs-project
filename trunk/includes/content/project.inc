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
		} else {
			//store the filter.
			$page->ui->set_cache("$module/filter", $_POST['filter']);
			$result = "set";
		}
	} elseif($_POST['ID'] == "new") {
		unset($_POST['ID']);
		$result = $proj->create_project($_POST['data']);
		$extra = "/view?ID=". $result;
		#exit;
	} else {
		//TODO: check to see if somebody else has updated the project since this user viewed the page...
		$updateRes = $proj->update_project($_POST['data']);
		if($updateRes) {
			set_message_wrapper(array(
				"title"		=> "Successfully Updated",
				"message"	=> "Update successful.  Good job!",
				"type"		=> "status"
			));
			$extra = "/view?ID=". $projectId;
		} else {
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
} else {
	
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
	if($filterData['status_id'] == "all")
	{
		//no filter: we're showing everything.
		unset($filterData['status_id']);
	}
	elseif(!isset($filterData['status_id']))
	{
		//this shows everything EXCEPT the "ended" items.
		$filterData['status_id'] = $GLOBALS['STATUS_NOTENDED'];
	}
	$filter = $filterData;
	unset($filter['status']);
		
	
	if($_GET['action']) {
		//it's a get var... use it.
		$action = $_GET['action'];
	} else {
		//get the action from our handy URL.
		$action = $page->ftsSections[2];
		
		//example URL: /content/project/view/365
		//                 ^0     ^1      ^2         ^3
	}
	$showAllProjects = TRUE;
	
	//TODO: fix the logic of this if/elseif/else statement, and make the "showAllProjects" better, so there is a GOOD DEFAULT.
	//CREATE A NEW PROJECT!!!
	if($action == "create") {
		
		//set the "parent" arbitrarily (give it a default), then check the URL to see if it's specified.
		$parent = 0;
		if(is_numeric($_GET['parent'])) {
			$parent = $_GET['parent'];
		}
		//make sure all the projects aren't displayed...
		$showAllProjects = FALSE;
		
		//now set the proper content.
		$titleSub = "Create New Project";
		$page->add_template_var("parent", $parent);
		$page->add_template_var("submit_name", "Create Project");
	
		//create the "chef" and "participants" list.	
		//TODO: if creating a sub-project, select the list of users that had been selected for the parent...
		$page->add_template_var("select_linked_users", $proj->create_user_option_list($_SESSION['user_ID'],NULL,NULL,FALSE));
		$page->add_template_var("select_leader_uid", $proj->create_user_option_list($_SESSION['user_ID']));
		
		//add the priority thing.
		$priorityOptionLIst = create_priority_option_list(50);
		$page->add_template_var("select_priority", $priorityOptionLIst);
		
		//give a default for the begin & end dates.
		//TODO: if it's a sub-project (if $_GET['parent'] > 0), use the "end" value for the parent.
		$page->add_template_var("start_date", date("Y-m-d"));
		$page->add_template_var("deadline", strftime("%Y-%m-%d", strtotime("+1 month")));
		
		$page->add_template_var("parentProjectId", $_GET['parent']);
	


	//attempting to view the details of a project, but had an invalid ID.
	} elseif($action == "view" && !is_numeric($_GET['ID'])) {
		set_message_wrapper(array(
			"title"		=> "Invalid ID",
			"message"	=> "An invalid ID was specified [". $_GET['ID'] ."].<BR>\n" .
							"You've been redirected here instead.",
			"type"		=> "error"
		));
		conditional_header("/content/project/");
		exit;
		
		
	//VIEW THE DETAILS OF A PROJECT!!!
	} elseif($action == "view" && is_numeric($_GET['ID'])) {
		
		$showAllProjects = FALSE;
		$projectId = $page->ui->magic_cache("ID", NULL, "project","_GET,_SESSION");
		$detailsArr = $proj->get_details($projectId);
		
		if(is_numeric($page->ui->get_cache("project/linkToIssue"))) {
			$linkIssue = $page->ui->get_cache("project/linkToIssue");
			$page->ui->unset_cache("project/linkToIssue");
			
			//now link it!
			$helpdeskObj = new helpdeskClass($proj->db);
			$helpdeskObj->helpdeskId = $linkIssue;
			$issueData = $helpdeskObj->get_record($linkIssue);
			$fixedIssueAncestry = preg_replace('/:'. $issueData['record_id'] .'$/', '', $issueData['ancestry']);
			if($fixedIssueAncestry !== $detailsArr['ancestry']) {
				try {
					$remarkDetails = "Re-linked to [project_id=". $projectId ."]";
					if($issueData['ancestry_level'] > 1) {
						$parentRecord= $helpdeskObj->get_parent_record($issueData['ancestry']);
						$remarkDetails .= " from [project_id=". $parentRecord['public_id'] ."]";
					}
					$linkRes = $helpdeskObj->update_record($linkIssue, array('parentRecordId' => $projectId));
					$helpdeskObj->remark($linkIssue, $remarkDetails);
					
					if($linkRes) {
						$page->set_message_wrapper(array(
							'title'		=> "Issue Linked Successfully",
							'message'	=> "Issue #". $linkIssue ." was successfully linked to this project.",
							'type'		=> "status"
						));
					}
					else {
						$page->set_message_wrapper(array(
							'title'		=> "Issue Linking FAILED",
							'message'	=> "Failed to link issue #". $linkIssue ." to this project, result was (". $linkRes .").",
							'type'		=> "status"
						));
					}
					
					//now get the details for this project again.
					$detailsArr = $proj->get_details($projectId);
				}
				catch(exception $e) {
					$page->set_message_wrapper(array(
						'title'		=> "An Error Occurred",
						'message'	=> "While trying to link the record, a fatal error was encountered: ". $e->getMessage(),
						'type'		=> 'error'
					));
				}
			}
			else {
				$page->set_message_wrapper(array(
					'title'		=> "Issue Linkage Not Updated",
					'message'	=> "You chose the project that issue #". $linkIssue ." was already attached to; doing nothing.",
					'type'		=> 'error',
					'linkText'	=> "Back to issue #". $linkIssue,
					'linkUrl'	=> "/content/helpdesk/view?ID=". $linkIssue
				));
			}
		}
		
		//TODO: Create a link or something for moving it to a new parent (something like the main list, where clicking will change the parent).
		$page->add_template_var("submit_name", "Modify");
		
		$titleSub = "Edit Project #". $_GET['ID'];
		$page->add_template_var("note_readonly", "readonly");
		$page->set_all_block_rows("content", array("related", "new_subproject_link", "status_section", "note_show", "show_ancestry_data"));
		$page->set_all_block_rows("related_todo");
		$page->set_all_block_rows("related_note");
		$page->set_all_block_rows("related_issue");
		
		
		//TODO: re-implement tags for projects.
		/*/Display tags that can be added.
		$availableTagsList = $proj->tagObj->get_available_tags_for_record($_GET['ID'], 1); 
		debug_print($availableTagsList);
		if(is_array($availableTagsList) && count($availableTagsList) > 0)
		{
			$myTagsList = array_as_option_list($availableTagsList,NULL,'select',$page->templateRows['addTag_option']);
			$page->add_template_var("addTag_option", $myTagsList);
		}
		
		//create the list of attached tags.
		$associatedTags = $proj->tagObj->get_tags_for_record($_GET['ID'], 1);
		if(is_array($associatedTags))
		{
			$assocTagsBaseRow = $page->templateRows['project_tag'];
			foreach($associatedTags as $tagId=>$tagName)
			{
				$tagsRepArr = array
				(
					'tagId'		=> $tagId,
					'tagName'	=> $tagName
				);
				$myRow .= mini_parser($assocTagsBaseRow, $tagsRepArr, '%%', '%%');
			}
			$page->add_template_var('project_tag', $myRow);
			unset($associatedTags, $tagId, $tagName, $tagsRepArr);
		}
		#*/
		
		//re-add the "modify" button.
		$page->add_template_var("modify_button", $page->templateRows['modify_button']);
		
		//format the "note" area.
		if(strlen($detailsArr['subject']) > 0)
		{
			$detailsArr['subject'] = cleanString($detailsArr['subject'], "htmlspecial_nq");
			$detailsArr['subject'] = cleanString($detailsArr['subject'], "htmlentity_plus_brackets");
			$detailsArr['subject'] = $bbCodeParser->parseString($detailsArr['subject']);
			$detailsArr['subject'] = preg_replace("/\n/", "<BR>\n", $detailsArr['subject']);
			$detailsArr['subject'] = preg_replace("/\s\s/", "&nbsp;&nbsp;", $detailsArr['subject']);
		}
		else
		{
			//no data.
			$detailsArr['subject'] = '<font color="red"><b>[NO DATA]</b></font>';
		}
		
		//retrieve a list of it's parents.
		$ancestryString = $proj->get_ancestry_link_list($projectId);
		if(is_null($ancestryString)) {
			//
			$ancestryString = '<font color="red"><b>[N/A]</b></font>';
		}
		$page->add_template_var('ancestry_string', $ancestryString);
		
		//list the immediate children.
		$childFilter = $filter;
		$childFilter['ancestry_level'] = count(explode(':', $detailsArr['ancestry'])) +1;
		$childrenString = $proj->get_children_string($projectId, $childFilter);
		$childAncestryBoxSize = 100;
		if(is_null($childrenString))
		{
			//set something.
			$childrenString = '<font color="red"><b>[N/A]</b></font>';
			$childAncestryBoxSize = 20;
		}
		$page->add_template_var('children_string_height', $childAncestryBoxSize);
		$page->add_template_var('children_string', $childrenString);
		
		
		//format the start_date
		$tmpStartDate = explode(" ", $detailsArr['start_date']);
		$detailsArr['start_date'] = $tmpStartDate[0];
		
		foreach($detailsArr as $field=>$value) {
			$page->add_template_var($field, $value);
		}
		$page->add_template_var("project_name", $detailsArr['name']);
		$page->add_template_var("select_status_id_". $detailsArr['status_id'], "selected");
		
		
		//TODO: make this use a better name...
		$linkedUserList = array();
		if(is_array($detailsArr['linked_users'])) {
			$linkedUserList = array_flip($detailsArr['linked_users']);
		}
		$page->add_template_var("select_linked_users", $proj->create_user_option_list($linkedUserList,NULL,NULL,FALSE));
		$page->add_template_var("select_leader_uid", $proj->create_user_option_list($detailsArr['leader_contact_id'], NULL, NULL, ' ---- none ----'));
	
		//add the priority (wichtung).
		$priorityOptionLIst = create_priority_option_list($detailsArr['priority']);
		$page->add_template_var("select_priority", $priorityOptionLIst);
		unset($userList,$tSelected,$userListArr, $selLeader, $leaderSelect);
		
		//pull a couple of preferences, so we can say if they're getting the whole picture or not.
		$todoPref = $proj->prefObj->get_pref_value_by_name('projectDetails_todoDisplayOnlyMine');
		$helpdeskPref = $proj->prefObj->get_pref_value_by_name('projectDetails_showCompletedIssues');
		
		if($todoPref !== 'all') {
			$page->add_template_var("todoPrefWarning", $page->templateRows['todoPrefWarning']);
		}
		
		if($helpdeskPref == 0) {
			$page->add_template_var("helpdeskPrefWarning", $page->templateRows['helpdeskPrefWarning']);
		}
		
		//parse-in everything under "related"...
		foreach($detailsArr['related'] as $type => $subArr) {
			$rowName = "related_". $type ."_row";
			if(is_array($subArr) && isset($page->templateRows[$rowName])) {
				//build the related rows...
				unset($myRow, $bgColor, $bgColor2);
				$baseRow = $page->templateRows[$rowName];
				foreach($subArr as $id=>$arr) {
					swapValue($bgColor, "rgb(213, 213, 213)", "rgb(194, 194, 194)");
					swapValue($bgColor2, "#d5d5d5", "#c2c2c2");
					$repArr = array(
						"id"		=> $id,
						"bgColor"	=> $bgColor,
						"bgColor2"	=> $bgColor2
					);
					
					if($type == "issue") {
						$arr['display_id'] = $id;
						//special parsing of data for issues.
						if(preg_match('/solved/', strtolower($arr['status_text']))) {
							$strikeThroughArr = array(
								"name", "assigned", "submit", "helpdesk_cat_name", "priority", "status_text", "display_id"
							);
							foreach($strikeThroughArr as $field) {
								$arr[$field] = '<del>&nbsp;'. $arr[$field] .'&nbsp;</del>';
							}
							unset($strikeThroughArr);
						}
						$repArr['font_weight'] = 'normal';
					} elseif($type == "todo") {
						$changeFontColor=TRUE;
						//Making TODO stuff look purdy.  Boy.
						if(!preg_match('/accepted/', strtolower($arr['status_text']))) {
							$repArr['bgColor'] = "e0e0e0";
							$repArr['bgColor2'] = "e0e0e0";
						}
						
						//Add dashes to fields that don't have any information in them.
						if(strlen($arr['deadline']) <6) {
							$changeFontColor = FALSE;
							$arr['deadline'] = "&#151;&#151;&#151;&#151;";
						}
						if(strlen($arr['begin_date']) <6) {
							$changeFontColor = FALSE;
							$arr['begin_date'] = "&#151;&#151;&#151;&#151;";
						}
						if(strlen($arr['assigned_user']) == 0) {
							//put some dashes in if nobody is assigned.
							$arr['assigned_user'] = "&#151;&#151;";
						} 
						
						//cross-out all items that are ended.
						if(preg_match('/ended/', strtolower($arr['status_text']))) {
							$changeFontColor = FALSE;
							$strikeThroughArr = array(
								"creator", "creator", "assigned", "status_text", "remark", "begin_date", "deadline"
							);
							foreach($strikeThroughArr as $myField) {
								$arr[$myField] = '<del>&nbsp;'. $arr[$myField] .'&nbsp;</del>';
							}
							unset($strikeThroughArr);
						}
						
						if(strlen($arr['deadline']) && preg_match('/ended/', strtolower($arr['status_text']))) {
							//make the deadline look nice.
							$today = date("Ymd");
							$myDeadline = str_replace("-", "", $arr['deadline']);
							settype($myDeadline, "int");
							settype($today, "int");
							
							if(!$changeFontColor) {
								//do nothing.
							} elseif($myDeadline <= $today) {
								$arr['deadline'] = '<font color="red"><b>'. $arr['deadline'] .'</b></font>';
							} else {
								$arr['deadline'] = '<font color="green">'. $arr['deadline'] .'</font>';
							}
						}
					
						if($arr['status_id'] == 2) {
							$repArr['font_weight'] = "bold";
						} else {
							$repArr['font_weight'] = "normal";
						}
					}
					
					//put every item in our array into the replacement array.
					foreach($arr as $x=>$y) {
						$repArr[$x] = $y;
					}
					$myRow .= mini_parser($baseRow, $repArr, "%%", "%%");
				}
				$page->add_template_var($rowName, $myRow);
			}
		}
		
		//set a var in the session to go back to here...
		$_SESSION['goBackLink'] = "/content/project/view?ID=". $_GET['ID'];
	} 
	
	
	if($showAllProjects) {
		//check to see if something's been selected to expand...
		$expandArr = $page->ui->get_cache("expandArr");
		if(!is_array($expandArr)) {
			$expandArr = array();
		}
		$expandId = $_GET['expandId'];
		$expandAction = $_GET['expand'];
		if($expandAction && $expandId) {
			//let's add it to the array.
			if($expandAction == "open" && !isset($expandArr[$expandId])) {
				//add it to the array!!!
				$expandArr[$expandId] = $_GET['level'];
			} elseif($expandAction == "close"  && isset($expandArr[$expandId])) {
				//remove it, so it's not expanded anymore.
				unset($expandArr[$expandId]);
			}
		}
		$page->ui->set_cache("expandArr", $expandArr);
		
		#$page->change_content(html_file_to_string("modules/project/project_main.tmpl"));
		$page->set_all_block_rows("content");
		
		//set some template vars for the filtering.
		$page->add_template_var("filter_keyword", $filterData['keyword']);
		$page->add_template_var("filter_field_". $filterData['field'] ."_selected", "selected");
		$page->add_template_var("filter_status_id_". $filterData['status_id'] ."_selected", "selected");
			
		//show the main (root) level projects.
		$sortArr = $page->ui->get_cache("$module/currentSort");
		$projectsArr = $proj->list_projects(0,$sortArr,$filter);
		$page->add_template_var("project_row", process_row($page, $projectsArr, $expandArr, $page->templateRows['project_row']));
		
		//loop through the sub-projects.
		process_subProjects($page, $proj, $filter);
	}
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
				} else {
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
		} else {
			debug_print("process_subProjects(): id #$id disappeared...");
			unset($expandArr[$id]);
			$page->ui->set_cache("expandArr", $expandArr);
		}
	}
	
}//end process_subProjects()
//==========================================================================================
?>