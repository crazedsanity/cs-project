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
	if(strlen($action)) {
		if($action == "filter") {
			if($_POST['reset_filter']) {
				$page->ui->unset_cache("helpdesk/filter");
				$result = "unset";
			}
			else {
				//store the filter.
				$page->ui->set_cache("helpdesk/filter", $_POST['filter']);
				$result = "set";
			}
			unset($extra);
			$page->gfObj->conditional_header("/content/helpdesk");
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