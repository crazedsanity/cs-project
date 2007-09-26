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
	//okay, now do the requested action.
	$action = strtolower($_POST['action']);
	$todoId = $_POST['todo_id'];
	if($action == 're-open') {
		//re-open the item.
		$updateArr = array(
			"status_id"	=> 6,
			"progress"	=> 0
		);
		$retval = $proj->todoObj->update_todo($todoId, $updateArr);
		
		$commentData = 'Re-opened.';
		if(strlen($_POST['comment'])) {
			$commentData = $_POST['comment'];
		}
		$proj->todoObj->add_comment($todoId, 'Re-opened.', 'Re-open [auto-generated]');
	}
	elseif($action == "undertake") {
		$updateArr = array(
			"status_id"	=> 2,
			"assigned_contact_id"	=> $_SESSION['contact_id']
		);
		$retval = $proj->todoObj->update_todo($todoId, $updateArr);
	} elseif($action == "create") {
		unset($_POST['updates']['add_elapsed']);
		$retval = $proj->todoObj->create_todo($_POST['updates']);
		$todoId = $retval;
	} elseif($action == "comment") {
		$retval = $proj->todoObj->add_comment($todoId, $_POST['comment']);
	} elseif($action == "modify") {
		$currentData = $proj->todoObj->get_todo($todoId);
		
		//reset status if needs-be.
		if($_POST['updates']['progress'] >= 100 || $_POST['done']) {
			//it's at 100%: end it!
			$_POST['updates']['status_id'] = 4;
			$_POST['updates']['progress'] = 100;
			
			//comment on it, so it is known who closed it.
			$commentData = 'Completed.';
			if(strlen($_POST['comment'])) {
				//don't 
				$commentData = $_POST['comment'];
			}
			$proj->todoObj->add_comment($todoId, 'Completed.', 'Completed [auto-generated]');
		}
		elseif(strlen($_POST['comment'])) {
			//they passed a comment.
			$proj->todoObj->add_comment($todoId, $_POST['comment']);
		}
		
		$retval = $proj->todoObj->update_todo($todoId, $_POST['updates']);
	} else {
		#$retval = $proj->todoObj->update_todo($todoId, $_POST['updates']);
		$retval = 0;
	}
	
	if($action == "create" && $retval > 0) {
		set_message_wrapper(array(
			"title"		=> "Creation Successful",
			"message"	=> "Todo #$retval was created successfully.",
			"type"		=> "notice"
		));
	} elseif($retval == 1) {
		set_message_wrapper(array(
			"title"		=> "Update Successful",
			"message"	=> "Todo was updated successfully.",
			"type"		=> "notice"
		));
	} else {
		set_message_wrapper(array(
			"title"		=> "Update FAILED!",
			"message"	=> "Something happened, and the sh*t hit the fan.  Sorry.<BR>\n" .
							"LAST ERROR: ". $proj->todoObj->lastError ."<BR>\n" .
							"RESULT RETURNED: [$retval]",
			"type"		=> "error"
		));
	}
	
	//now redirect.
	$url = "/content/todo/view?ID=$todoId&updateResult=$retval";
	conditional_header($url);
	exit;
} else {
	$action = $page->ftsSections[2];
	$todoId = $_GET['ID'];
	if($action == "create") {
		
		//sanity check: need to link it to an actual record.
		if(!isset($_GET['parentPublicId']) || !is_numeric($_GET['parentPublicId'])) {
			throw new exception("Unable to create todo without specifying a parent!!!");
		}
		else {
			//get the details.
			$projectDetails = $proj->get_details($_GET['parentPublicId']);
			$page->add_template_var("record_id", $projectDetails['record_id']);
		}
		
		//update the title.
		$titleSub = "Create New Todo";
		
		//show the proper drop-downs.
		$page->add_template_var("user_assign_list", $proj->create_user_option_list());
		#$page->add_template_var("select_project_list", $proj->create_project_option_list($_GET['projectId'], TRUE, TRUE));
		$page->add_template_var("select_priority_list", create_priority_option_list(9,9,0));
		
		//automatically fill the begin date & deadline.
		$page->add_template_var("started", date("Y-m-d"));
		$page->add_template_var("deadline", date('Y-m-d', strtotime('1 month')));
		
		$doNotRipRows = array("assign_list", "action_buttons", "display_modifiable", "input_orig_estimate");
		$page->add_template_var("modify_or_create_button", "Create");
		$page->set_all_block_rows("content", $doNotRipRows);
		
	} elseif(is_numeric($todoId)) { 
	
		if($action == "view")
		{
			//set the page's title.
			$titleSub = "Edit Todo #$todoId";
			
			$todoDetails = $proj->todoObj->get_todo($todoId);
			
			
			//format the body, so long sentences fit properly.
			$todoDetails['body'] = wordwrap($todoDetails['body'], FORMAT_WORDWRAP);
			$todoDetails['body'] = cleanString($todoDetails['body'], 'htmlentity_plus_brackets');
			
			$commentsArray = $todoDetails['comments'];
			unset($todoDetails['comments']);
	
			foreach($todoDetails as $field=>$value) {
				$page->add_template_var($field, $value);
			}
			
			//TODO: only give ability to update if they're the creator or if it's been assigned to them.
			
			//create the list of users.
			$page->add_template_var("user_assign_list", $proj->create_user_option_list($todoDetails['assigned_contact_id']));
			
			//should really check based off uid...
			$page->add_template_var("note_readonly", "readonly");
			if(in_array($todoDetails['status_id'], $GLOBALS['STATUS_NOTENDED'])) {
				if($todoDetails['assigned_contact_id'] == "" || $todoDetails['assigned_contact_id'] == $_SESSION['contact_id']) {
					if($todoDetails['status_id'] == 0 || $todoDetails['status_id'] == 1) {
						//they're assigned, but haven't taken it yet.
						$doNotRipRows[] = "undertake_button";
						$doNotRipRows[] = "readonly_started";
						$doNotRipRows[] = "readonly_deadline";
						$doNotRipRows[] = "readonly_curr_estimate";
						$doNotRipRows[] = "readonly_priority";
						$doNotRipRows[] = "assigned_user_text";
					}
					else {
						//they're assigned & have taken control of it.
						$doNotRipRows[] = "modifiable_started";
						$doNotRipRows[] = "modifiable_deadline";
						$doNotRipRows[] = "assign_list";
						$doNotRipRows[] = "modifiable_curr_estimate";
						$doNotRipRows[] = "modifiable_priority";
						$doNotRipRows[] = "edit_title_link";
					}
				} else {
					$doNotRipRows[] = "undertake_button";
					$doNotRipRows[] = "assign_list";
					$doNotRipRows[] = "readonly_started";
					$doNotRipRows[] = "readonly_deadline";
					$doNotRipRows[] = "readonly_curr_estimate";
					$doNotRipRows[] = "readonly_priority";
				}
				
				//It's either "Pending" (1) or "Running/Accepted" (2)...
				$displaySection = "modifiable";
				$doNotRipRows[] = 'done_box';
				$doNotRipRows[] = 'addComment';
				$doNotRipRows[] = "action_buttons";
				$doNotRipRows[] = 'addElapsed';
				
				
			} else {
				//probably "Ended/Solved" (4).
				$displaySection = "readonly";
				$doNotRipRows[] = "reopen_button";
			}
			
			$doNotRipRows[] = "progress_text";
			//add the project list.
			#$page->add_template_var("select_project_list", $proj->create_project_option_list($todoDetails['record_id'], TRUE));
			
			//create the priority list.
			$page->add_template_var("select_priority_list", create_priority_option_list($todoDetails['priority'],9,0));
			
			$doNotRipRows[] = "from_data";
			$doNotRipRows[] = "remarks_and_comments_data";
			$doNotRipRows[] = "status_data";
			$doNotRipRows[] = "progress_data";
			$doNotRipRows[] = "comment_button";
			$doNotRipRows[] = "display_". $displaySection;
			$doNotRipRows[] = "input_curr_estimate";
			$page->add_template_var("modify_or_create_button", "Modify");
			$page->set_all_block_rows("content", $doNotRipRows);
			
			//Build the comments...
			if(is_array($commentsArray) && count($commentsArray)) {
				$useThis = $commentsArray;
				$baseRow = $page->templateRows['previousComments'];
				foreach($useThis as $index=>$data) {
					$data['body'] = cleanstring($data['body'], "htmlentity_plus_brackets");
					$data['body'] = wordwrap($data['body'], FORMAT_WORDWRAP);
					$myRow .= mini_parser($baseRow, $data, '%%', '%%');
				}
				$page->add_template_var('previousComments', $myRow);
			}
			
			if(in_array($todoDetails['status_id'], $GLOBALS['STATUS_NOTENDED'])) {
				//show hours logged.
				$hoursLoggedLimit = 5;
				$page->add_template_var('hoursLoggedLimit', $hoursLoggedLimit);
				$hoursLogged = $proj->todoObj->get_hours_logged($todoId, NULL, $hoursLoggedLimit);
				$baseRow = $page->templateRows["logEstimateRecord_row"];
				if(is_array($hoursLogged)) {
					$myRow = "";
					foreach($hoursLogged as $garbage=>$repArr) {
						$rowClass = swapValue($rowClass, '#D5D5D5', '#C2C2C2');
						$repArr['class'] = $rowClass;
						$myRow .= mini_parser($baseRow, $repArr, '%%', '%%');
					}
					$page->add_template_var("logEstimateRecord_row", $myRow);
				}
			}
			
		}
	} else {
		//list everything...
		$todoArr = $proj->todoObj->get_todos(array("gruppe"=>$_SESSION['user_group']), $currentSortArr);
		$page->set_all_block_rows("content");
		
		$baseRow = $page->templateRows['todo_row'];
		if(is_array($todoArr)) {
			foreach($todoArr as $todoId => $subArr) {
				$repArr = $subArr;
				swapValue($bgColor, "rgb(213, 213, 213)", "rgb(194, 194, 194)");
				swapValue($bgColor2, "#d5d5d5", "#c2c2c2");
					
				$repArr['bgColor'] = $bgColor;
				$repArr['bgColor2']= $bgColor2;
				$finalRow .= mini_parser($baseRow, $repArr, "%%", "%%");
			}
			$page->add_template_var("todo_row", $finalRow);
		}
	}
}


?>
