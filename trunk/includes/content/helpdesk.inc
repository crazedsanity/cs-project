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
	$action = strtolower($_POST['action']);
	$helpdeskId = $_POST['id'];
	$baseUrl = "/content/helpdesk";
	$urlSection = "view";
	$urlExtrasArr = array(
		"ID"	=> $helpdeskId
	);
	$byPassUrlExtras = FALSE;
	if($action) {
		
		if($action == "modify") {
			//update the ancestry...
			$_POST['updates']['parentRecordId'] = $_POST['updates']['project_id'];
			unset($_POST['updates']['project_id']);
			
			//that means we've gotta update everything sent... right?
			$doNotAcceptFields = array("solution", "remark");
			foreach($doNotAcceptFields as $unsetThis) {
				unset($_POST['updates'][$unsetThis]);
			}
			$result = $proj->helpdeskObj->update_record($helpdeskId, $_POST['updates']);
			
			//tell them what happened.
			if($result == 1) {
				set_message_wrapper(array(
					"title"		=> "Update Successful",
					"message"	=> "Your issue was updated successfully.",
					"type"		=> "status"
				));
			} else {
				set_message_wrapper(array(
					"title"		=> "Update FAILURE",
					"message"	=> "Update returned [$result].  Last error was [". $proj->helpdeskObj->lastError ."]",
					"type"		=> "error"
				));
			}
			$urlSection = "view";
		}
		elseif($action == "remark")
		{
			$helpdeskData = $proj->helpdeskObj->get_record($helpdeskId);
			//only care about the remark data... if they changed something, well... they're stupid.
			$useRespondLink = FALSE;
			if($helpdeskData['email'] != $_SESSION['email']) {
				$useRespondLink = $proj->helpdeskObj->create_md5($helpdeskId);
			}
			$result = $proj->helpdeskObj->remark($helpdeskId, $_POST['remark'], FALSE, $useRespondLink);
			
			if($result > 0) {
				set_message_wrapper(array(
					"title"		=> "Remark Added Successfully",
					"message"	=> "Your thoughts have been dually noted in the database... or at least singly.  :D",
					"type"		=> "status"
				));
			} else {
				set_message_wrapper(array(
					"title"		=> "Unable to Add Remark",
					"message"	=> "Dually noted, summarily denied.  Beotch.",
					"type"		=> "error"
				));
			}
		}
		elseif($action == "solve")
		{
			//solve it & make sure they know if it was good or not.
			$result = $proj->helpdeskObj->solve($helpdeskId, $_POST['solution']);
			$extra = NULL;
			
			if($result == 1) {
				set_message_wrapper(array(
					"title"		=> "Solved Successfully",
					"message"	=> "Your solution was logged, and the issue was updated accordingly.  Confirmation emails " .
							"have been sent.",
					"type"		=> "status"
				));
			} else {
				set_message_wrapper(array(
					"title"		=> "Unable to Solve",
					"message"	=> "Try using a longer explanation... or check the error log.",
					"type"		=> "error"
				));
			}
		}
		elseif($action == "filter")
		{
			if($_POST['reset_filter']) {
				$page->ui->unset_cache("helpdesk/filter");
				$result = "unset";
			} else {
				//store the filter.
				$page->ui->set_cache("helpdesk/filter", $_POST['filter']);
				$result = "set";
			}
			$byPassUrlExtras = TRUE;
			unset($extra);
		}
		elseif($action == "re-open")
		{
			//we'll update some fields, then remark the old solution.
			$oldData = $proj->helpdeskObj->get_record($helpdeskId);
			$oldSolution = $oldData['solution'];
			$updatesArr = array(
				"status_id"	=> 6
			);
			
			$updateRes = $proj->helpdeskObj->update_record($helpdeskId, $updatesArr);
			
			//make the old solution... better.
			$oldSolution = "Re-opened.";
			$remarkRes = $proj->helpdeskObj->remark($helpdeskId, $oldSolution);
			
		}
		else
		{
			exit("invalid action: [$action]");
		}
	} elseif($_POST['submit'] == "go" && count($_POST['data']) > 1) {
		//CREATING NEW RECORD!!!
		$dataArr = $_POST['data'];
		$result = $proj->helpdeskObj->create_record($dataArr);
		
		if($result > 0) {
			//TODO: implement email portion.
			set_message_wrapper(array(
				"title"		=> "Success",
				"message"	=> "Helpdesk issue #". $result ." created successfully.  You should receive an email confirmation shortly",
				"type"		=> "notice"
			));
			
			//only go back to helpdesk if we're supposed to.
			if($_SESSION['goBackLink']) {
				$byPassUrlExtras = TRUE;
				$baseUrl = $_SESSION['goBackLink'];
			} else {
				$helpdeskId = $result;
				$urlExtrasArr['ID'] = $result;
			}
		} else {
			//something bad happened.
			set_message_wrapper(array(
				"title"		=> "",
				"message"	=> "",
				"type"		=> "error"
			));
		}
	} else {
		//the didn't post enough...
		set_message_wrapper(array(
			"title"		=> "FAILED TO CREATE ISSUE!",
			"message"	=> "Unable to create the message... [$retval]",
			"type"		=> "error"
		));
	}
	
	//build the redirection URL.
	$url = $baseUrl;
	
	//don't bother redirecting to the "view" page if the ID isn't valid.
	if(preg_match('/view/', $urlSection) && !is_numeric($urlExtrasArr['ID'])) {
		unset($urlSection);
	}
	if(isset($urlSection) && !preg_match('/\?/', $url)) {
		if(!preg_match('/$\//', $url)) {
			$urlSection = "/". $urlSection;
		}
		$url .= $urlSection;
	}
	if(!$byPassUrlExtras && count($urlExtrasArr) > 0) {
		$urlExtrasStr = string_from_array($urlExtrasArr, "url");
		if($urlExtrasStr !== 0) {
			$url .= "?". $urlExtrasStr;
		}
	}
	conditional_header($url);
	exit;
} else {
	$helpdeskId = $_GET['ID'];
	$action = $page->ftsSections[2];
	
	
	//remove the link in the session that they should return to if not requested.
	if($_SESSION['goBackLink'] && !$_GET['goBack']) {
		unset($_SESSION['goBackLink']);
	}
	
	if($action == "create") {
		$titleSub = "Create a Helpdesk Issue";
		$page->set_all_block_rows("content");
		
		//show the "category" selection.
		$categoryList = $proj->helpdeskObj->get_category_list();
		$page->add_template_var("select_tags", $categoryList);
		
		//
		$page->add_template_var("email", $_SESSION['email']);
		$page->add_template_var("linked_proj", cleanString($_GET['proj']),"numeric");
		
	} elseif($action == "view" && !is_numeric($helpdeskId)) {
		//missing ID: throw a message & send 'em packin'.
		set_message_wrapper(array(
			"title"		=> "Invalid ID Specified",
			"message"	=> "Unable to retrieve the requested record, due to an invalid ID:<BR>" .
							" you were automatically redirected.<BR>" .
							"Requested ID: [$helpdeskId]",
			"type"		=> "error"
		));
		conditional_header("/". $page->ftsSections[0] ."/". $page->ftsSections[1]);
		exit;
	} elseif($action == "view" && is_numeric($helpdeskId)) {
		$titleSub = "Edit Issue #$helpdeskId";
		$doNotRipRows = array("edit_form", "info_block", "access_block");
		$helpdeskData = $proj->helpdeskObj->get_record($helpdeskId);
		
		//TODO: handle this better, somehow... AJAX comes to mind. ;) 
		if($_GET['newTag'] && !$_SESSION['addTag_cache'][$helpdeskData['record_id']][$_GET['newTag']]) {
			$tempObj = new tagClass($proj->helpdeskObj->db);
			$addRes = $tempObj->add_tag($helpdeskData['record_id'], $_GET['newTag']);
			if($addRes) {
				$_SESSION['addTag_cache'][$helpdeskData['record_id']][$_GET['newTag']] = $addRes;
			}
		}
		
		//show it's "access" properly..
		$page->add_template_var("select_acc_". $helpdeskData['acc'], "selected");
		
		//show the "category" properly.
		$categoryList = $proj->helpdeskObj->get_category_list($helpdeskData['helpdesk_cat_id']);
		$page->add_template_var("select_category_list", $categoryList);
		
		$bbParser = new bbCodeParser;
		
		//convert the returned data into htmlentities so brackets show properly.
		$helpdeskData['subject'] = $bbParser->parseString(cleanString($helpdeskData['subject'], "htmlentity_plus_brackets"));
		$helpdeskData['name'] = cleanString($helpdeskData['name'], "htmlentity_plus_brackets");
		$helpdeskData['solution'] = cleanString($helpdeskData['solution'], "htmlentity_plus_brackets");
		
		//make the wrapping SANE.
		$helpdeskData['subject'] = wordwrap($helpdeskData['subject'], FORMAT_WORDWRAP);	
		$rowsToUse = count(explode("\n", $helpdeskData['subject']));
		if($rowsToUse < 5) {
			$rowsToUse = 5;
		}
		$page->add_template_var("note_rows", $rowsToUse);
		
		//show the buttons appropriate to their membership level & ownership.
		//if they're the owner, show the "solution" section.
		$assignedStr = $helpdeskData['leader_contact_id'];
		if($helpdeskData['status_id'] == 4) {
			//allow the current user to re-open this issue.
			$doNotRipRows[] = "reopen_button";
		} else {
			if(($assignedStr == $_SESSION['contact_id']) || ($assignedStr == "" || is_null($assignedStr))) {
				//well, we can show 'em the update/modify button.
				$doNotRipRows[] = "update_button";
				if($_SESSION['contact_id'] == $assignedStr) {
					//they're the owner... show 'em more options.
					$doNotRipRows[] = "solution_row";
					$doNotRipRows[] = "owner_options";
				}
			//if they're the owner, show 'em more options...
			} elseif($_SESSION['isGroupOwner']) {
				//we got an OWNER!!!
				$doNotRipRows[] = "owner_options";
			}
			$doNotRipRows[] = "remark_row";
			$doNotRipRows[] = "buttons_row";
			
			if($helpdeskData['status_id'] == 6) {
				//yeah, it's dirty... but it highlights the fact that it's been re-opened.
				$helpdeskData['status_text'] = '<font color="red">'. $helpdeskData['status_text'] .'</font>';
			}
			
			//keep the set of data allows it to be linked to a project.
			$doNotRipRows[] = "edit_project_selection";
			
			//TODO: implement a link for updating project associations.
			#$page->add_template_var("select_project_list", $proj->create_project_option_list($helpdeskData['ancestry'], NULL));
		}
		$doNotRipRows[] = 'update_button';
		
		//TODO: get ancestry data for this!
		if($helpdeskData['ancestry_level'] > 1) {
			$doNotRipRows[] = "link_to_parent_project";
			
			//get the parent.
			$parentData = $proj->helpdeskObj->get_parent_record($helpdeskData['ancestry']);
			$page->add_template_var('parent_record_name', $parentData['name']);
			$page->add_template_var('parent_record_public_id', $parentData['public_id']);
		}
		
		//create the priority list.
		$page->add_template_var("select_priority_list", create_priority_option_list($helpdeskData['priority'],9,0));
		
		//create the list of users.
		//TODO: retrieve list of attached contacts. 
		$page->add_template_var("user_assign_list", $proj->create_user_option_list($helpdeskData['leader_contact_id'],NULL,NULL,"n/a"));
		
		//add everything to the page as a template var...
		$myNotes = $helpdeskData['notes'];
		unset($helpdeskData['notes']);
		foreach($helpdeskData as $field=>$value) {
			$page->add_template_var($field, $value);
		}
		
		//now, rip all block rows that don't need to be there...
		$page->set_all_block_rows("content", $doNotRipRows);
		
		
		
		$myTagList = $proj->tagObj->get_tags_for_record($helpdeskData['record_id']);
		if(is_array($myTagList)) {
			$baseRow = $page->templateRows['removeable_tag'];
			$myTagRow = "";
			$tCount = 0;
			foreach($myTagList as $tagNameId=>$tagName) {
				
				//don't show a separator for the end piece.
				$tCount++;
				$separator = ' || ';
				if($tCount == count($myTagList)) {
					$separator = "";
				}
				$repArr = array(
					'tag_name_id'	=> $tagNameId,
					'name'			=> $tagName,
					'separator'		=> $separator
				);
				$myTagRow .= mini_parser($baseRow, $repArr, '%%', '%%');
			}
		}
		else {
			//tell 'em there's no tags!
			$myTagRow = "";
		}
		$page->add_template_var('removeable_tag', $myTagRow);
		
		//Display tags that can be added.
		$availableTagsList = $proj->tagObj->get_available_tags_for_record($helpdeskData['record_id'], 3);
		if(is_array($availableTagsList) && count($availableTagsList) > 0)
		{
			$myTagsList = array_as_option_list($availableTagsList,NULL,'select',$page->templateRows['addTag_option']);
			$page->add_template_var("add_tag_list", $myTagsList);
		}
		
		//now parse the note rows.
		if(is_array($myNotes) && count($myNotes)) {
			$baseRow = $page->templateRows['issueNotes'];
			foreach($myNotes as $repArr) {
				$repArr['body'] = wordwrap($repArr['body'], FORMAT_WORDWRAP);
				$repArr['body'] = $bbParser->parseString(cleanString($repArr['body'], "htmlentity_plus_brackets"));
				$repArr['subject'] = cleanString($repArr['subject'], "htmlentity_plus_brackets");
				
				$repArr['solutionIndicator'] = "";
				if($repArr['is_solution'] == 't') {
					$repArr['solutionIndicator'] = "<img src='/images/checkmark_green.jpg' height='14px'>";
				}
				$issueNotesRow .= mini_parser($baseRow, $repArr, '%%', '%%');
			}
			$page->add_template_var('issueNotes', $issueNotesRow);
		}
		
	} else {
		//change the content of the page properly.
		$page->set_all_block_rows("content", array("helpdesk_search"));
		
		$filterData = $page->ui->get_cache("helpdesk/filter");
		if(is_array($filterData)) {
			$page->add_template_var("filter_keyword", $filterData['keyword']);
			$page->add_template_var("filter_field_". $filterData['field'] ."_selected", "selected");
		} else {
			//no filter array... set one.
			$filterData = array();
			$page->ui->set_cache("helpdesk/filter", $filterData);
		}
		$page->add_template_var("filter_status_id_". $filterData['status_id'] ."_selected", "selected");
		
		if($filterData['status_id'] == 'all')
		{
			$primaryFilterArr = NULL;
		}
		elseif(is_numeric($filterData['status_id']))
		{
			//the data will already show only items with the given status, so don't add that to the filter again.
			$primaryFilterArr = array("status_id" => $filterData['status_id']);
		}
		else
		{
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
				$textFieldArr = array("name", "subject", "solution", "remark");
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
	}
}
?>