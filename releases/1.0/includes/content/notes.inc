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
	$projectId = $_POST['updates']['projekt'];
	$noteId = $_POST['id'];
	if($_POST['update_note']) {
		$updateRes = $proj->noteObj->update_note($_POST['updates'], $noteId);
		
		//tell 'em what happened...
		if($updateRes == 1) {
			set_message_wrapper(array(
				"title"		=> "Update Successful!",
				"message"	=> "Note ID #$noteId was modified successfully.",
				"type"		=> "notice"
			));
		} else {
			set_message_wrapper(array(
				"title"		=> "Update Failure",
				"message"	=> "Something went wrong, and the note failed to get updated. " .
								$proj->noteObj->lastError,
				"type"		=> "error"
			));
		}
	} elseif($_POST['create_note']) {
		$result = $proj->noteObj->create_note($_POST['updates']);
		
		//tell 'em what happened.
		if($result == 0) {
			//insert outright failed...
			set_message_wrapper(array(
				"title"		=> "Unable to Create Note",
				"message"	=> "Something bad happened, and the note couldn't be created....<BR>ERROR=[$dberror]",
				"type"		=> "error"
			));
		} elseif($result > 0) {
			//seemed to work.
			set_message_wrapper(array(
				"title"		=> "Successfully Created Note",
				"message"	=> "The new note ID is: $result.",
				"type"		=> "notice"
			));
		} else {
			//insert worked, but couldn't get the note_id...
			set_message_wrapper(array(
				"title"		=> "Partial Failure...",
				"message"	=> "Seems like the note was created, but I couldn't get ",
				"type"		=> "error"
			));
		}
	}
	
	//okay, now send 'em to the right page.
	$url = "/content/notes?from=post";
	if($_SESSION['goBackLink']) {
		$url = $_SESSION['goBackLink'];
		unset($_SESSION['goBackLink']);
	}
	conditional_header($url);
	exit;
	
} else {
	//TODO: start using "$_GET['noteId']"... makes me SANE.
	$action = $page->ftsSections[2];
	$noteId = $_GET['ID'];
	
	
	if($action) {
		$showBadNoteError = TRUE;
		if($action && $action != "view") {
			//see what they're wanting to do...
			if($action == "create") {
				$showBadNoteError = FALSE;
				//attempt to retrieve a proper project ID.
				if(is_numeric($_GET['projectId'])) {
					//they sent one in the URL.  Nice peoples.
					$projectId = $_GET['projectId'];
				} else {
					//get the one we've stored in the session.
					$projectId = $page->ui->get_cache("project/ID");
				}
				
				//one last check to see if we've got a valid projectId.
				if(!is_numeric($projectId)) {
					//didn't get a good projectId to link it to.  Give 'em hell.
					set_message_wrapper(array(
						"title"		=> "Invalid Project ID",
						"message"	=> "Can't link the note to a project without a valid project ID!",
						"type"		=> "error"
					));
					conditional_header($_SESSION['goBackLink']);
					exit;
				} else {
					//got everything we need, I guess.
					$titleSub =  "Create New Note...";
					$page->add_template_var("record_id", $projectId);
					
					//change the submit button's name & value.
					$page->add_template_var("submit_name", "create_note");
					$page->add_template_var("submit_value", "Create Note");
					$page->add_template_var("note_id", "new");
				}
			} else {
				//invalid note, seems like.  :D 
				$showBadNoteError = TRUE;
			}
		} elseif($action == "view" && is_numeric($_GET['ID'])) {
			$showBadNoteError = FALSE;
			//grab info about the note.
			$noteInfo = $proj->noteObj->get_note($_GET['ID']);
			
			//fill in some info about the page.
			$titleSub = "Edit Note #". $_GET['ID'];
			$page->add_template_var("goBackLink", $_SESSION['goBackLink'] ."#note_". $_GET['ID']);
			
			foreach($noteInfo as $index=>$value) {
				$page->add_template_var("note_". $index, $value);
			}
			
			if($noteInfo['von'] == $_SESSION['uid']) {
				//they're the owner..
				$doNotRipRows[] = "owner_options";
			} else {
				$doNotRipRows[] = "nonowner_options";
				$page->add_template_var("note_name_readonly", "readonly");
			}
			//now change the submit button's name & value.
			$page->add_template_var("submit_name", "update_note");
			$page->add_template_var("submit_value", "Update Note");
			
			$page->set_all_block_rows("content", $doNotRipRows);
		}
		
		//TODO: make the if/elseif statement above more intelligent, so we don't have to do this "showBadNoteError" crap.
		if($showBadNoteError) {
			//nothing good will come of this.
			set_message_wrapper(array(
				"title"		=> "Invalid Note ID.",
				"message"	=> "Unable to retrieve the requested note, or the note/action was invalid.",
				"type"		=> "error"
			));
			conditional_header("/content/notes?error=badNote");
			exit;
		}
	} else {
		$page->set_all_block_rows("content");
		unset($proj->noteObj->projectId);
		
		$notesArray = $proj->noteObj->get_notes(array("gruppe"=>$_SESSION['user_group']), $currentSortArr);
		
		//if there's an array, show it...
		if(is_array($notesArray) && count($notesArray) > 0) {
			//got good data.
			$baseRow = $page->templateRows['notes_row'];
			$baseDeleteLink = $page->templateRows['deleteLink'];
			$deleteLink = NULL;
			foreach($notesArray as $noteId=>$subData) {
				unset($deleteLink);
				if($subData['von'] == $_SESSION['uid']) {
					$deleteLink = mini_parser($baseDeleteLink, $subData, "%%", "%%");
				}
				
				
				swapValue($bgColor, "rgb(213, 213, 213)", "rgb(194, 194, 194)");
				swapValue($bgColor2, "#d5d5d5", "#c2c2c2");
				
				$subData['bgColor'] = $bgColor;
				$subData['bgColor2']= $bgColor2;
				
				$subData['deleteLink'] = $deleteLink;
				$subData['note_id'] = $noteId;
				unset($subData['id']);
				
				//show the "created" and "updated" strings properly...
				$subData['created'] = parse_date_string($subData['div1'],TRUE);
				$subData['updated'] = parse_date_string($subData['div2'],TRUE);
				$finalRow .= mini_parser($baseRow, $subData, "%%", "%%");
			}
			$page->add_template_var("notes_row", $finalRow);
		} else {
			//something bad happened...
			set_message_wrapper(array(
				"title"		=> "Notes Unavailable",
				"message"	=> "There are no notes to display... sorry.",
				"type"		=> "notice"
			));
		}
	}
}

?>
