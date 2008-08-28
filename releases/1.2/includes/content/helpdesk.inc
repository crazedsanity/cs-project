<?php
/*
 * SVN INFORMATION:::
 * ------------------
 * Last Author:         $Author:crazedsanity $ 
 * Current Revision:    $Revision:637 $ 
 * Repository Location: $HeadURL:https://cs-project.svn.sourceforge.net/svnroot/cs-project/trunk/includes/content/helpdesk.inc $ 
 * Last Updated:        $Date:2007-11-20 11:04:33 -0600 (Tue, 20 Nov 2007) $
 */




if($_POST) {
	$action = strtolower($_POST['action']);
	$helpdeskId = $_POST['id'];
	$baseUrl = "/content/helpdesk";
	$urlSection = "view";
	if(is_numeric($helpdeskId)) {
		$urlExtrasArr = array(
			"ID"	=> $helpdeskId
		);
	}
	$byPassUrlExtras = FALSE;
	if($action) {
		
		if($action == "modify") {
			//update the ancestry...
			$_POST['updates']['parentRecordId'] = $_POST['updates']['project_id'];
			unset($_POST['updates']['project_id']);
			
			
			//if they've changed the name, we gotta log it.
			//TODO: probably log this within helpdeskClass::update().
			if(isset($_POST['updates']['name'])) {
				//retrieve the old info.
				$oldData = $proj->helpdeskObj->get_record($_POST['id']);
				
				if($oldData['name'] !== $_POST['updates']['name']) {
					$addRemark = 'Updated [b]name[/b] from ([b][i]'. $oldData['name'] .'[/i][/b])';
				}
			}
			
			$doNotAcceptFields = array("solution", "remark");
			foreach($doNotAcceptFields as $unsetThis) {
				unset($_POST['updates'][$unsetThis]);
			}
			$result = $proj->helpdeskObj->update_record($helpdeskId, $_POST['updates']);
			
			if(strlen($addRemark)) {
				$proj->helpdeskObj->remark($_POST['id'], $addRemark);
			}
			
			
			//tell them what happened.
			if($result == 1) {
				set_message_wrapper(array(
					"title"		=> "Update Successful",
					"message"	=> "Your issue was updated successfully.",
					"type"		=> "status"
				));
			}
			else {
				set_message_wrapper(array(
					"title"		=> "Update FAILURE",
					"message"	=> "Update returned [$result].  Last error was [". $proj->helpdeskObj->lastError ."]",
					"type"		=> "error"
				));
			}
			$urlSection = "view";
		}
		elseif($action == "remark") {
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
			}
			else {
				set_message_wrapper(array(
					"title"		=> "Unable to Add Remark",
					"message"	=> "Dually noted, summarily denied.  Beotch.",
					"type"		=> "error"
				));
			}
		}
		elseif($action == "solve") {
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
			}
			else {
				set_message_wrapper(array(
					"title"		=> "Unable to Solve",
					"message"	=> "Try using a longer explanation... or check the error log.",
					"type"		=> "error"
				));
			}
		}
		elseif($action == "filter") {
			if($_POST['reset_filter']) {
				$page->ui->unset_cache("helpdesk/filter");
				$result = "unset";
			}
			else {
				//store the filter.
				$page->ui->set_cache("helpdesk/filter", $_POST['filter']);
				$result = "set";
			}
			$byPassUrlExtras = TRUE;
			unset($extra);
		}
		elseif($action == "re-open") {
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
		else {
			exit("invalid action: [$action]");
		}
	} elseif(isset($_POST['submit']) && count($_POST['data']) > 1) {
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
			}
			else {
				$helpdeskId = $result;
				$urlExtrasArr['ID'] = $result;
			}
		}
		else {
			//something bad happened.
			set_message_wrapper(array(
				"title"		=> "",
				"message"	=> "",
				"type"		=> "error"
			));
		}
	}
	
	//build the redirection URL.
	$url = $baseUrl;
	
	//don't bother redirecting to the "view" page if the ID isn't valid.
	if(isset($_POST['xajax'])) {
		//don't exit or anything here: let the AJAX stuff work itself out, I guess.
	}
	elseif(preg_match('/view/', $urlSection) && !is_numeric($urlExtrasArr['ID'])) {
		unset($urlSection);
		conditional_header('/content/helpdesk');
		exit;
	}
	else {
		if(isset($urlSection) && !preg_match('/\?/', $url)) {
			if(!preg_match('/$\//', $url)) {
				$urlSection = "/". $urlSection;
			}
			$url .= $urlSection;
		}
		if(!$byPassUrlExtras && count($urlExtrasArr) > 0) {
			$urlExtrasStr = string_from_array($urlExtrasArr, "url");
			if($urlExtrasStr !== 0) {
				$url .= "?". $urlExtrasStr .'&random='. time();
			}
		}
		conditional_header($url);
		exit;
	}
}
else {
	$helpdeskId = $_GET['ID'];
	$action = $page->ftsSections[2];
	
	if(is_numeric($page->ui->get_cache("project/linkToIssue"))) {
		$page->ui->unset_cache("project/linkToIssue");
	}
	
	
	//remove the link in the session that they should return to if not requested.
	if($_SESSION['goBackLink'] && !$_GET['goBack']) {
		unset($_SESSION['goBackLink']);
	}
	
	if($action == "create") {
		create_page_title($page, array('title' => "Create a Helpdesk Issue"));
		$page->set_all_block_rows("content");
		
		//show the "category" selection.
		$categoryList = $proj->helpdeskObj->get_category_list('bug', TRUE);
		$page->add_template_var("select_tags", $categoryList);
		
		//
		$page->add_template_var("email", $_SESSION['email']);
		$page->add_template_var("linked_proj", cleanString($_GET['proj']),"numeric");
		
	}
	elseif($action == "view" && !is_numeric($helpdeskId)) {
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
	}
	elseif($action == "view" && is_numeric($helpdeskId)) {
		$doNotRipRows = array("edit_form", "info_block", "access_block", "associatedUserBlock");
		$helpdeskData = $proj->helpdeskObj->get_record($helpdeskId);
		create_page_title($page, array('title' => "Issue #". $helpdeskId .": \"". $helpdeskData['name'] ."\""));
		
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
		
		//convert the returned data into htmlentities so brackets show properly.
		$helpdeskData['subject'] = $bbCodeParser->parseString(cleanString($helpdeskData['subject'], "htmlentity_plus_brackets"), TRUE);
		$helpdeskData['subject'] = preg_replace('/\s\s/', '&nbsp;&nbsp;', $helpdeskData['subject']);
		$helpdeskData['subject'] = preg_replace('/\S\s\s/', ' &nbsp;', $helpdeskData['subject']);
		
		
		$helpdeskData['name'] = cleanString($helpdeskData['name'], "htmlentity_plus_brackets");
		$helpdeskData['solution'] = cleanString($helpdeskData['solution'], "htmlentity_plus_brackets");
		
		//make the wrapping SANE.
		#$helpdeskData['subject'] = wordwrap($helpdeskData['subject'], FORMAT_WORDWRAP);	
		
		//show the buttons appropriate to their membership level & ownership.
		//if they're the owner, show the "solution" section.
		$assignedStr = $helpdeskData['leader_contact_id'];
		if($helpdeskData['status_id'] == 4) {
			//CLOSED: allow the current user to re-open this issue.
			$doNotRipRows[] = "assigned_text";
			$doNotRipRows[] = "priority_text";
			$doNotRipRows[] = "reopen_button";
		}
		else {
			//NOT closed...
			$doNotRipRows[] = "assigned_optionList";
			$doNotRipRows[] = "priority_optionList";
			$doNotRipRows[] = "editable_tags";
			if(($assignedStr == $_SESSION['contact_id']) || ($assignedStr == "" || is_null($assignedStr))) {
				//well, we can show 'em the update/modify button.
				$doNotRipRows[] = "update_button";
				if($_SESSION['contact_id'] == $assignedStr) {
					//they're the owner... show 'em more options.
					$doNotRipRows[] = "solution_row";
					$doNotRipRows[] = "owner_options";
				}
			//if they're the owner, show 'em more options...
			}
			elseif($_SESSION['isGroupOwner']) {
				//we got an OWNER!!!
				$doNotRipRows[] = "owner_options";
			}
			$doNotRipRows[] = "remark_row";
			$doNotRipRows[] = "buttons_row";
			$doNotRipRows[] = 'accessBlock__modifyButton';
			$doNotRipRows[] = 'linkToIssue';
			
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
			//TODO: since mainRecord{} tries to enforce having criteria of "is_helpdesk_issue" set, there's extra crap logic required.
			//TODO: fix the afore-mentioned bug by splitting helpdesk & project issues into separate tables.
			$oldIsHelpdesk = $proj->helpdeskObj->isHelpdeskIssue;
			$proj->helpdeskObj->isHelpdeskIssue=FALSE;
			$parentData = $proj->helpdeskObj->get_parent_record($helpdeskData['ancestry']);
			$proj->helpdeskObj->isHelpdeskIssue=$oldIsHelpdesk;
			$page->add_template_var("ancestryLinkList", $proj->get_ancestry_link_list($parentData['public_id'], TRUE, TRUE, TRUE));
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
		
		$taskData = $proj->helpdeskObj->get_tasks($helpdeskId);
		$page->set_all_block_rows('related_task');
		if(is_array($taskData)) {
			parse_related_task($taskData);
		}
		
		
		
		$useTextTags = FALSE;
		if($helpdeskData['status_id'] == 4) {
			$useTextTags = TRUE;
		}
		generate_tag_list($helpdeskData['record_id'], $useTextTags);
		
		
		
		//Display tags that can be added.
		generate_addable_tags($helpdeskData['record_id']);
		
		//now parse the note rows.
		if(is_array($myNotes) && count($myNotes)) {
			$baseRow = $page->templateRows['issueNotes'];
			foreach($myNotes as $repArr) {
				$repArr['body'] = $bbCodeParser->parseString($repArr['body']);
				
				$repArr['subject'] = cleanString($repArr['subject'], "htmlentity_plus_brackets");
		
				
				$repArr['solutionIndicator'] = "";
				if($repArr['is_solution'] == 't') {
					$repArr['solutionIndicator'] = "<img src='/images/checkmark_green.jpg' height='14px'>";
				}
				$issueNotesRow .= mini_parser($baseRow, $repArr, '%%', '%%');
			}
			$page->add_template_var('issueNotes', $issueNotesRow);
		}
		
		
		//show the list of associated users.
		$parsedRow = $page->templateRows['associatedUser_none'];
		if(is_array($helpdeskData['associatedUsers'])) {
			$baseRow = $page->templateRows['associatedUser_data'];
			$parsedRow = "";
			foreach($helpdeskData['associatedUsers'] as $id => $data) {
				$displayName = truncate_string($data['fname'] ." ". $data['lname'], 20);
				$repArr = $data;
				$repArr['displayName'] = $displayName;
				$parsedRow .= $page->mini_parser($baseRow, $repArr, '%%', '%%');
			}
		}
		$page->add_template_var('associatedUser_data', $parsedRow);
		
	}
	else {
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
	}
}

//call the function to add AJAX stuff...
addAjax();

//AJAX FUNCTIONS

function ajax__removeTag($tagNameId, $recordId) {
	ob_start();
	$tagObj = $GLOBALS['objects']['tag'];
	$page = $GLOBALS['objects']['page'];
	$objResponse = new xajaxResponse();
	
	$messageArr = array(
		'title'		=> 'Unable to Remove Tag',
		'message'	=> "There was an unknown error while attempting to remove the tag.",
		'type'		=> 'error'
	);
	
	if(is_numeric($tagNameId) && is_numeric($recordId)) {
		$result = $tagObj->remove_tag($recordId,$tagNameId);
		$tagList = $tagObj->get_tag_list();
		
		//check the results.
		if($result==1) {
			//Cool.  Tell 'em the tag was removed.
			$messageArr = array(
				'title'		=> 'Tag Successfully Removed',
				'message'	=> "We removed \"". $tagList[$tagNameId] ."\".",
				'type'		=> 'status'
			);
		}
		else {
			//already got most of the array, just change the actual message.
			$messageArr['message'] = "Result of trying to remove tag: (". $result .")";
		}
	}
	
	$newTagList = generate_tag_list($recordId);
	
	if(strlen($newTagList)) {
		$returnThis = $newTagList;
	}
	else {
		$returnThis = "invalid response from generate_tag_list($recordId)";
	}
	
	$page->set_message_wrapper($messageArr);
	$objResponse->addAssign('MAIN_error_message', 'innerHTML', $page->process_set_message());
	
	$objResponse->addAssign('tagList', 'innerHTML', $returnThis);
	$objResponse->addAssign('addTagOptionList', 'innerHTML', generate_addable_tags($recordId));
	
	
	ob_end_clean();
	return($objResponse);
}//end ajax__removeTag()



function ajax__addTag($tagNameId, $recordId) {
	ob_start();
	
	$tagObj = $GLOBALS['objects']['tag'];
	$page = $GLOBALS['objects']['page'];
	$objResponse = new xajaxResponse();
	
	$result = $tagObj->add_tag($recordId, $tagNameId);
	
	$objResponse->addAssign('tagList', 'innerHTML', generate_tag_list($recordId));
	$objResponse->addAssign('addTagOptionList', 'innerHTML', generate_addable_tags($recordId));
	
	//display a message saying it worked.
	if($result == 1) {
		$tagList = $tagObj->get_tag_list();
		$messageArr = array(
			'title'		=> 'Tag Added Successfully',
			'message'	=> "Successfully tagged record with \"". $tagList[$tagNameId] ."\"",
			'type'		=> 'status'
		);
	}
	else {
		$messageArr = array(
			'title'		=> 'Failed to Add Tag',
			'message'	=> "Result of adding tag was (". $result .")",
			'type'		=> 'error'
		);
	}
	$page->set_message_wrapper($messageArr);
	$objResponse->addAssign('MAIN_error_message', 'innerHTML', $page->process_set_message());
	
	
	return($objResponse);
	ob_end_clean();
	
}//end ajax__addTag()


function generate_tag_list($recordId, $useTextRows=FALSE) {
	$page = $GLOBALS['objects']['page'];
	$tagObj = $GLOBALS['objects']['tag'];
	
	if(!is_numeric($recordId)) {
		return("invalid recordId=(". $recordId .")");
	}
	elseif(!(is_object($tagObj) && get_class($tagObj) == 'tagClass')) {
		return("didn't get tagClass object");
	}
	elseif(!(is_object($page) && get_class($page) == 'cs_genericPage')) {
		return("invalid page object: (". get_class($page) .")");
	}
	
	
	$myTagList = $tagObj->get_tags_for_record($recordId, TRUE);
	if(is_array($myTagList)) {
		if($useTextRows) {
			$tagListVar = 'text_tags';
		}
		else {
			$tagListVar = 'removeable_tag';
		}
		
		if(!isset($page->templateRows[$tagListVar])) {
			$page->set_all_block_rows();
		}
		$baseRow = $page->templateRows[$tagListVar];
		
		$myTagRow = "";
		$tCount = 0;
		foreach($myTagList as $tagNameId=>$tagData) {
			$tagName = $tagData['name'];
			
			//don't show a separator for the end piece.
			$tCount++;
			$separator = ' || ';
			if($tCount == count($myTagList)) {
				$separator = "";
			}
			$repArr = array(
				'tag_name_id'	=> $tagNameId,
				'name'			=> $tagName,
				'separator'		=> $separator,
				'imgHtml'		=> $tagData['imgHtml']
			);
			$myTagRow .= mini_parser($baseRow, $repArr, '%%', '%%');
		}
		$myTagRow = mini_parser($myTagRow, array('record_id'=>$recordId), '{', '}');
		$page->add_template_var($tagListVar, $myTagRow);
		
		if(strlen($myTagRow)) {
			$retval = $myTagRow;
		}
		else {
			$retval = __METHOD__ .": No data parsed..?";
			throw new exception($retval);
		}
	}
	else {
		$retval = "";
	}
	
	return($retval);
}//end generate_tag_list()


function generate_addable_tags($recordId) {
	$tagObj = $GLOBALS['objects']['tag'];
	$page = $GLOBALS['objects']['page'];
	$availableTagsList = $tagObj->get_available_tags_for_record($recordId, 3);
	$retval = NULL;
	if(is_array($availableTagsList) && count($availableTagsList) > 0) {
		$myTagsList = array_as_option_list($availableTagsList,NULL,'select',$page->templateRows['addTag_option']);
		$myTagsList = '<select>'. $myTagsList .'</select>';
		$myTagsList = mini_parser($myTagsList, array('record_id' => $recordId), '{', '}');
		$page->add_template_var("add_tag_list", $myTagsList);
		$retval = $myTagsList;
	}
	return($retval);
}


?>