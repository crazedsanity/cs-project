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
	if(strlen($action)) {
		
		if($action == "submit" || $action == "modify") {
			$proj->helpdeskObj->db->beginTrans();
			
			//SETUP THE MESSAGE THEY'LL SEE...
			$setMessage = array(
				"title"		=> "",
				"message"	=> "... SET ME!!! ...",
				"type"		=> "error"
			);
			$success = false;
			$remarks = array();
			
			
			$oldData = $proj->helpdeskObj->get_record($helpdeskId);
			
			
			#################################
			# ---------> BEGIN "update"
			#################################
			
			
			//if they've changed the name, we gotta log it.
			//TODO: probably log this within helpdeskClass::update().
			if(isset($_POST['updates']['name']) && $oldData['name'] !== $_POST['updates']['name']) {
				$remarks[] = array(
					"remark"	=> 'Updated [b]name[/b] from ([b][i]'. $oldData['name'] .'[/i][/b])',
					false
				);
			}
			
			$doNotAcceptFields = array("solution", "remark");
			foreach($doNotAcceptFields as $unsetThis) {
				unset($_POST['updates'][$unsetThis]);
			}
			$result = $proj->helpdeskObj->update_record($helpdeskId, $_POST['updates']);
			
			
			//SET/UPDATE THE TYPE OF MESSAGE THAT WILL DISPLAY...
			if($result == 1) {
				$success = true;
				$messageTitle[] = "Updated";
				$messageText[] = "Your issue was updated successfully.";
			}
			else {
				#$setMessage['type'] = "error";
				$success = false;
			}
			
			#################################
			# ---------> BEGIN "remark/solve"
			#################################
			if($success === true && strlen($_POST['remark'])) {
				//now check remark stuff.
				$useRespondLink = FALSE;
				if($_POST['isSolution']) {
					//IT'S A SOLUTION
					//solve it & make sure they know if it was good or not.
					#$result = $proj->helpdeskObj->solve($helpdeskId, $_POST['solution']);
					$remarks[] = array(
						'remark'		=> $_POST['remark'],
						'isSolution'	=> true
					);
					$extra = NULL;
					
					if($result == 1 && $success === true) {
						$messageTitle[] = "Solved";
						$messageText[] = "Your solution was logged, and the issue was updated accordingly.  Confirmation emails have been sent.";
					}
				}
				else {
					//JUST A REMARK...
					if($oldData['email'] != $_SESSION['email']) {
						$useRespondLink = $proj->helpdeskObj->create_md5($helpdeskId);
					}
					$remarks[] = array(
						'remark'		=> $_POST['remark'],
						'isSolution'	=> false,
						'link'			=> $useRespondLink
					);
					
					if($result > 0) {
						$success = true;
						$messageTitle[] = "Remarked Upon";
						$messageText[] = "Your thoughts have been dually noted in the database... or at least singly.  :D";
					}
				}//end REMARK
			}
					
			if($success) {
				//TODO: this is where a queued email system would be good; if the first remark succeeds but the second fails, this will generate an erroneous email.
				if(count($remarks)) {
					foreach($remarks as $num=>$remarkData) {
						$result = $proj->helpdeskObj->remark($helpdeskId, $remarkData['remark'], $remarkData['isSolution'], $remarkData['link']);
						if($result <= 0) {
							$success = false;
							break;
						}
					}
				}
				
				if($success === true) {
					$proj->helpdeskObj->db->commitTrans();
					//format the message so it looks nice...
					$setMessage['message'] = "";
					if(count($messageTitle) > 2) {
						$lastMessage = array_pop($setMessage);
						$setMessage['title'] = $page->gfObj->string_from_array($messageTitle, null, ", ");
						$setMessage['title'] .= " and ". $lastMessage;
					}
					else {
						$setMessage['title'] = $page->gfObj->string_from_array($messageTitle, null, " and ");
					}
					$setMessage['message'] = $page->gfObj->string_from_array($messageText, null, "<BR>\n");
					$setMessage['type'] = "status";
				}
				else {
					$proj->helpdeskObj->db->rollbackTrans();
					$setMessage = array(
						'title'		=> "Update FAILED",
						'message'	=> "The update would have worked, but you didn't put enough information into your remark.",
						'type'		=> "error"
					);
				}
			}
			else {
				$proj->helpdeskObj->db->rollbackTrans();
				$setMessage = array(
					'title'		=> "Update FAILED",
					'message'	=> "Update returned [". $result ."].  Last error was [". $proj->helpdeskObj->lastError ."]",
					'type'		=> "error"
				);
			}
			
			#$page->gfObj->debug_print($_POST,1);
			#$page->gfObj->debug_print($setMessage,1);
			#exit;
			$page->set_message_wrapper($setMessage);
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
		}
	}
	
	if(!isset($_POST['xajax'])) {
		$page->gfObj->conditional_header("/content/helpdesk/view?ID=". $helpdeskId);
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
}
//call the function to add AJAX stuff...
addAjax();

?>