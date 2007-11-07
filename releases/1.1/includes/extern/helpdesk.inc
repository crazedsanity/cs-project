<?php
/*
 * 
 * SVN INFORMATION:::
 * ------------------
 * Last Author::::::::: $Author$ 
 * Current Revision:::: $Revision$ 
 * Repository Location: $HeadURL$ 
 * Last Updated:::::::: $Date$

 */

$page->set_all_block_rows("header");

$myContactId = $page->session->get_cookie('CS_CONTACT_ID');

if($_POST) {
	$postAction = strtolower($_POST['action']);
	if($postAction == "remark") {
		$helpdeskId = $_POST['ID'];
		
		if(is_numeric($helpdeskId)) {
			//now check to make sure they can actually remark on it...
			$helpdeskData = $proj->helpdeskObj->get_record($helpdeskId);
			
			if(is_array($helpdeskData)) {
				//okay, so they can still remark.
				
				//get information about the user based on the email address...
				if(strlen($_POST['email'])) {
					$contactObj = new contactClass($proj->db);
					$myContactId = $contactObj->get_contact_id_from_email($_POST['email'], TRUE);
					$page->session->create_cookie('CS_CONTACT_ID', $myContactId);
					
					//Add "contact_id" to the session, so the note gets created properly.
					$_SESSION['contact_id'] = $myContactId;
				}
				
				$result = $proj->helpdeskObj->remark($helpdeskId, $_POST['remark']);
					
				//now tell 'em what happened.
				if($result >= 1) {
					set_message_wrapper(array(
						"title"		=> "Remark Accepted",
						"message"	=> "Your remark has been added to the database.  Thank you for your feedback.",
						"type"		=> "status"
					));
				}
				else {
					set_message_wrapper(array(
						"title"		=> "Remark Failed",
						"message"	=> "Seems like the issue may have been updated in the interim: check your email for a new link to be used for remarking.",
						"type"		=> "error"
					));
				}
			}
			else {
				//not an array.  Something screwed up in the session?
				set_message_wrapper(array(
					"title"		=> "Unable to retrieve issue data",
					"message"	=> "Something went horribly wrong.  Tell somebody, would ya?",
					"type"		=> "error"
				));
			}
			conditional_header("/extern/helpdesk/view?ID=$helpdeskId");
			exit;
		}
		else {
			//hmmm... invalid ID.
			set_message_wrapper(array(
				"title"		=> "Data Error",
				"message"	=> "Unable to retrieve the helpdesk issue's ID.<BR>\n" .
								"Remarking failed.",
				"type"		=> "error"
			));
			conditional_header("/extern/helpdesk/");
			exit;
		}
	}
	elseif($_POST && strtolower($_POST['submit']) == "go" && !isset($_POST['action'])) {
		//they're CREATING an issue.
		$result = $proj->helpdeskObj->create_record($_POST['data']);
		
		//first, determine what happened from the result...
		if($result > 0) {
			//everything worked.
			set_message_wrapper(array(
				"title"		=> "Issue Successfully Created",
				"message"	=> "Your issue has been added to the helpdesk.  You should receive an email shortly with confirmation.",
				"type"		=> "notice"
			));
		}
		else {
			//something failed.
			set_message_wrapper(array(
				"title"		=> "Unable to Create Issue",
				"message"	=> "Something broke.  Sorry.",
				"type"		=> "notice"
			));
		}
		
		//send 'em packing.
		conditional_header("/extern/helpdesk/view?ID=$result");
		exit;
	}
	elseif($_POST['action'] == "filter") {
		if($_POST['reset_filter']) {
			$page->ui->unset_cache("filter");
		}
		else {
			//store the filter.
			$page->ui->set_cache("filter", $_POST['filter']);
		}
		
		//now redirect to the main page: it'll have to pull that filter data & use it.
		conditional_header($_GLOBALS['PHP_SELF'] ."?step=kb&from=filter");
		exit;
	}
}
else {
	$action = $page->ftsSections[2];
	$helpdeskId = $_GET['ID'];
	$page->add_template_var("id", $helpdeskId);
	if(isset($_GET['sort'])) {
		store_and_return_sorting($page, $_GET['sort'], $_GET['sortType']);
		conditional_header($GLOBALS['PHP_SELF'] ."?from=re-sort&sortFieldWas=". $_GET['sort']);
		exit;
	}
	elseif($action == 'view' && $_GET['dropCookie']) {
		$page->session->drop_cookie('CS_CONTACT_ID');
		$goHere = $_SERVER['PHP_SELF'] .'?ID='. $_GET['ID'] .'&random='. time();
		$page->conditional_header($goHere, TRUE);
	}
	elseif(($action == "view" || $action == "respond") && is_numeric($helpdeskId)) {
		$htmlTitle = "View Issue #$helpdeskId";
		$helpdeskData = $proj->helpdeskObj->get_record($helpdeskId);
		
		$bbCodeParser = new bbCodeParser($proj, $proj->helpdeskObj);
		
		//show it's "access" properly..
		$page->add_template_var("select_acc_". $helpdeskData['acc'], "selected");
		
		//convert the returned data into htmlentities so brackets show properly.
		$helpdeskData['name'] = cleanString($helpdeskData['name'], "htmlspecial");
		$myNotes = $helpdeskData['notes'];
		unset($helpdeskData['notes']);
		
		//make the wrapping SANE.
		$helpdeskData['subject'] = wordwrap($helpdeskData['subject'], FORMAT_WORDWRAP);
		$helpdeskData['subject'] = $bbCodeParser->parseString($helpdeskData['subject'],FALSE);
		
		//add everything to the page as a template var...
		foreach($helpdeskData as $field=>$value) {
			$page->add_template_var($field, $value);
		}
		
		//remove all block rows unless they're supposed to be kept... 
		$doNotRipRows = array();
		if(is_numeric($page->session->get_cookie('CS_CONTACT_ID'))) {
			$doNotRipRows[] = 'remark_showEmail';
			$contactObj = new contactClass($page->db);
			$contactObj->set_contact_id($page->session->get_cookie('CS_CONTACT_ID'));
			$data = $contactObj->get_contact();
			$page->add_template_var('displayEmail', $data['email']);
		}
		else {
			$doNotRipRows[] = 'remark_editEmail';
		}
		
		if(in_array($helpdeskData['status_id'], $GLOBALS['STATUS_NOTENDED'])) {
			$doNotRipRows[] = 'remark_row';
		}
		$page->set_all_block_rows("content", $doNotRipRows);
		
		//now parse the note rows.
		if(is_array($myNotes) && count($myNotes)) {
			$baseRow = $page->templateRows['issueNotes'];
			foreach($myNotes as $repArr) {
				$repArr['body'] = cleanString($repArr['body'], "htmlentity_plus_brackets");
				$repArr['body'] = $bbCodeParser->parseString($repArr['body'], FALSE);
				$repArr['subject'] = cleanString($repArr['subject'], "htmlentity_plus_brackets");
				
				$repArr['solutionIndicator'] = "";
				if($repArr['is_solution'] == 't') {
					$repArr['solutionIndicator'] = "<img src='/images/checkmark_green.jpg' height='14px'>";
				}
				$issueNotesRow .= mini_parser($baseRow, $repArr, '%%', '%%');
			}
			$page->add_template_var('issueNotes', $issueNotesRow);
		}
		
	}
	elseif($action == "create") {
		//set the html title.
		$page->add_template_var("html_title", "Create a Helpdesk Issue");
		$page->add_template_var("email", $_SESSION['email']);
		$page->set_all_block_rows("content", array("create_form"));
		
		//show the "category" properly.
		$categoryList = $proj->helpdeskObj->get_category_list($helpdeskData['helpdesk_cat_id']);
		$page->add_template_var("select_category_list", $categoryList);
		$page->add_template_var("select_tags", $categoryList);
		$page->add_template_var("id", $helpdeskId);
	}
	else {
		
		//================================================================================================================
		$titleSub = "Show All";
		//change the content of the page properly.
		#$page->change_content(html_file_to_string("modules/helpdesk/helpdesk_main.tmpl"));
		$page->set_all_block_rows("content", array("helpdesk_search"));
		
		$filterData = $page->ui->get_cache("filter");
		$currentSortArr = store_and_return_sorting($page,NULL,NULL);
		if(is_array($filterData)) {
			$page->add_template_var("filter_keyword", $filterData['keyword']);
			$page->add_template_var("filter_field_". $filterData['field'] ."_selected", "selected");
		}
		else {
			//no filter array... set one.
			$filterData = array();
			$page->ui->set_cache("filter", $filterData);
		}
		$page->add_template_var("filter_status_id_". $filterData['status_id'] ."_selected", "selected");
		unset($filterData['status']);
		
		//the data will already show only items with the given status, so don't add that to the filter again.
		$primaryFilterArr = array("status_id" => $filterData['status_id']);
		if($filterData['status_id'] == 'all') {
			//literally show ALL records.
			$primaryFilterArr = NULL;
		}
		elseif(!is_numeric($filterData['status_id'])) {
			//only show the "not ended" ones.
			$primaryFilterArr = array('status_id' => array(0,1,2,6));
		}
		$filter = $filterData;
		unset($filter['status']);
		
		//pull the recordset.
		$helpdeskArr = $proj->helpdeskObj->get_records($primaryFilterArr, $currentSortArr, $filter);
		$page->add_template_var("numRows", count($helpdeskArr));
		
		//if there's an array, show it...
		if(is_array($helpdeskArr) && count($helpdeskArr) > 0) {
			//got good data.
			$baseRow = $page->templateRows['helpdesk_row'];
			foreach($helpdeskArr as $helpdeskId=>$subData) {
				unset($deleteLink);
				if($subData['von'] == $_SESSION['uid']) {
					$deleteLink = mini_parser($baseDeleteLink, $subData, "%%", "%%");
				}
				
				
				swapValue($bgColor, "rgb(213, 213, 213)", "rgb(194, 194, 194)");
				swapValue($bgColor2, "#d5d5d5", "#c2c2c2");
				
				$subData['bgColor'] = $bgColor;
				$subData['bgColor2']= $bgColor2;
				
				//define the text fields, so they can be cleaned & truncated.
				$previewLength = 160;
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
				$subData['subject'] = wordwrap($subData['subject'], (($previewLength * 2)/3), "\n", 1);
				
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

#$page->print_page();

?>