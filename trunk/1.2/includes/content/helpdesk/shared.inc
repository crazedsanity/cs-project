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
		
		if($action == "submit") {
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