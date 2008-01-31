<?php
/*
 * Created on Feb 28, 2007
 * 
 * SVN INFORMATION:::
 * ------------------
 * Last Author:         $Author$ 
 * Current Revision:    $Revision$ 
 * Repository Location: $HeadURL$ 
 * Last Updated:        $Date$
 */

/*
 * NOTE: the add & remove sections could very easily be abused, or accidentally linked to... 
 */

$sessionCache = new sessionCache("settings");

addAjax();

if($_POST) {
	//got post vars.  Check them, then reload.
	$goHere = $_SERVER['PHP_SELF'];
	
	if($_POST['action'] == 'update') {
		$result = $proj->tagObj->update_tag_modifier($_POST['tag_name_id'], $_POST['modifier']);
	}
	elseif($_POST['new_tag']) {
		//check for pre-existing tags of that name.
		$myTagList = $proj->tagObj->get_tag_list();
		$existingTagNameId = array_search($_POST['new_tag'], $myTagList);
		
		if(is_numeric($existingTagNameId)) {
			$newTagNameId = $existingTagNameId;
		}
		else {
			//create the tag.
			$newTagNameId = $proj->tagObj->create_new_tag_name($_POST['new_tag']);
		}
		
		//determine the outcome.
		if(is_numeric($existingTagNameId)) {
			$messageArr = array(
				'title'		=> 'Existing Tag Re-used',
				'message'	=> "The submitted tag, \"". $_POST['new_tag'] ."\", already exists (". $newTagNameId ."). " .
							"The existing tag's ID was used instead.",
				'type'		=> 'notice'
			);
		}
		elseif(is_numeric($newTagNameId)) {
			$messageArr = array(
				'title'		=> 'New Tag Created Successfully',
				'message'	=> "Your tag, \"". $_POST['new_tag'] ."\" (". $newTagNameId ."), was " .
						"created successfully.",
				'type'		=> 'status'
			);
		}
		else {
			$messageArr = array(
				'title'		=> 'Unable to Create Tag',
				'message'	=> "Error reported: ". $proj->tagObj->lastError,
				'type'		=> 'error'
			);
		}
		set_message_wrapper($messageArr);
	}
	
	if($_POST['goBack'] && strlen($_POST['goBack'])) {
		$goHere = $_POST['goBack'] ."&newTag=$newTagNameId";
	}
	
	if(strlen($goHere)) {
		
		//do the redirect to remove POST headers.
		conditional_header($goHere);
		exit;
	}
}

//get the list of tags, regardless of whether we'll show 'em all or not.
$myTagList = $proj->tagObj->get_tag_list(TRUE, FALSE);

$showData = TRUE;
$showTagNameId = $sessionCache->get_cache('tagNameId');


if($_GET['move']) {
	//move the tag, & then redirect.
	
	$result = $proj->tagObj->update_record_position_for_tag($_GET['tagId'], $showTagNameId, $_GET['move']);
	
	//set a message & redirect.
	$messageArr = array(
		'title'		=> 'Record Moved',
		'message'	=> "Moving the record appears to have been successful ($result).",
		'type'		=> 'notice'
	);
	set_message_wrapper($messageArr);
	$goHere = $_SERVER['PHP_SELF'] ."?showTag=". $showTagNameId;
	conditional_header($goHere);
	exit;
}
elseif(isset($_GET['showTag']) && is_numeric($_GET['showTag'])) {
	//Store it.
	$sessionCache->set_cache('tagNameId', $_GET['showTag']);
}
elseif(!is_array($myTagList) || !count($myTagList)) {
	//no tags.
	$messageArr = array(
		'title'		=> 'No Tags Available',
		'message'	=> "No tags have been created, or they've all been deleted. " .
					"Please create some, or stop viewing this page. ;) ",
		'type'		=> 'fatal'
	);
	set_message_wrapper($messageArr);
	$showData = FALSE;
}
elseif(!is_numeric($showTagNameId) || is_null($showTagNameId)) {
	$userSawNotice = $sessionCache->get_cache("no_tag_specified");
	
	$tagList = array_keys($myTagList);
	$showTagNameId = $tagList[0];
	
	if(!$userSawNotice) {
		$messageArr = array(
			'title'		=> 'No Tag Specified',
			'message'	=> "For your convenience, records for the first available tag, " .
							"\"". $myTagList[$showTagNameId]['name'] ."\" ($showTagNameId) are currently displayed.  " .
							"You may select a different tag from the list.",
			'type'		=> 'notice'
		);
		set_message_wrapper($messageArr);
		
		//store the fact that they saw it into the session.
		$sessionCache->set_cache("no_tag_specified", TRUE);
	}
	
	//store the id into the session.
	$sessionCache->set_cache("tagNameId", $showTagNameId);
}
elseif(is_numeric($showTagNameId) && !isset($myTagList[$showTagNameId])) {
	//pick the first item in the list.
	$tagList = array_keys($myTagList);
	$showTagNameId = $tagList[0];
	
	//tell 'em why they're probably not seeing what they expect to.
	$messageArr = array(
		'title'		=> 'Invalid Tag',
		'message'	=> "You've specified a tag that is either invalid or no longer exists.  For your " .
						"convenience, the first available tag, \"". $myTagList[$showTagNameId] ."\" " .
						"($showTagNameId) are currently displayed.",
		'type'		=> 'error'
	);
	set_message_wrapper($messageArr);
	
	//store the id into the session.
	$sessionCache->set_cache("tagNameId", $showTagNameId);
}


//retrieve the (possibly updated) setting
$showTagNameId = $sessionCache->get_cache('tagNameId');

if($showData) {
	#showCurrentTag($showTagNameId);
	updateTagList($showTagNameId);
	showCurrentTag($showTagNameId);
	showRecordList($showTagNameId);
}


//AJAX FUNCTIONS...

function showRecordList($showTagNameId) {
	$tagObj = $GLOBALS['objects']['tag'];
	$page = $GLOBALS['objects']['page'];
	
	//get data for this tag.
	$data = $tagObj->get_records_for_tag($showTagNameId);
	
	if(!isset($page->templateRows['associatedRecords'])) {
		$page->set_all_block_rows();
	}
	$parseTmpl = $page->templateRows['associatedRecords'];
	
	//now list all records for this tag (assign the "no records" row, then check).
	$tagRow = $page->templateRows['tag_row__noRecords'];
	if(count($data)) {
		//got data.
		$tagRow = "";
		$baseRow = $page->templateRows['tag_row'];
		$rowColor1 = NULL;
		$rowColor2 = NULL;
		foreach($data as $index=>$subData) {
			//add some things for alternating row colors.
			$subData['rowColor'] = swapValue($rowColor1, "rgb(213, 213, 213)", "rgb(194, 194, 194)");
			$subData['rowColor2'] = swapValue($rowColor2,"#D5D5D5", "#C2C2C2");
			
			//parse data into it.
			$tagRow .= mini_parser($baseRow, $subData, '%%', '%%');
		}
	}
	
	$repArr = array(
		'tag_row'				=> $tagRow,
		'currentTagData'		=> $page->templateRows['currentTagData'],
		'associatedRecords'	=> $page->templateRows['associatedRecords'],
		'tag_row__noRecords'	=> ""
	);
	
	$retval = mini_parser($parseTmpl, $repArr, '{', '}');
	foreach($repArr as $index=>$value) {
		$page->add_template_var($index, $value);
	}
	
	#$page->add_template_var('tag_row', $tagRow);
	#$page->add_template_var('currentTagData', $page->templateRows['currentTagData']);
	#$page->add_template_var('associatedRecords', $page->templateRows['associatedRecords']);
	
	return($retval);
}//end showRecordList()


function showCurrentTag($showTagNameId) {
	
	$page = $GLOBALS['objects']['page'];
	$tagObj = $GLOBALS['objects']['tag'];
	
	$myTagList = $tagObj->get_tag_list(TRUE, FALSE);
	
	//get data for this tag.
	$data = $tagObj->get_records_for_tag($showTagNameId);
	
	if(!isset($page->templateRows['currentTagData_header'])) {
		$page->set_all_block_rows();
	}
	$parseTemplate = $page->templateRows['currentTagData_header'];
	
	$repArr = array(
		'tagName'					=> $myTagList[$showTagNameId]['name'],
		'tagModifier'				=> $myTagList[$showTagNameId]['modifier'],
		'tagRecordCount'			=> count($data),
		'showTagNameId'			=> $showTagNameId,
		'modifier_option_list'	=> create_priority_option_list($myTagList[$showTagNameId]['modifier'], 5, -5)
	);
	
	$retval = mini_parser($parseTemplate, $repArr, '{', '}');
	
	foreach($repArr as $tmplVar=>$value) {
		$page->add_template_var($tmplVar, $value);
	}
	
	$page->add_template_var('currentTagData_header', $page->templateRows['currentTagData_header']);
	
	return($retval);
}//end showCurrentTag()


function updateTagList($showTagNameId) {
	$page = $GLOBALS['objects']['page'];
	$tagObj = $GLOBALS['objects']['tag'];
	
	$myTagList = $tagObj->get_tag_list(TRUE, FALSE);
	create_page_title($page, array('title' => "Tag #". $showTagNameId . ": ". $myTagList[$showTagNameId]['name']));
	
	//rip all block rows.
	$page->set_all_block_rows('content');
	
	$recordList = showRecordList($showTagNameId);
	
	//display the list of available tags.
	$tagListBaseRow = $page->templateRows['availableTagsList'];
	$showIconRow = $page->templateRows['availableTagsList__tagIcon'];
	$myRow = "";
	foreach($myTagList as $tagNameId=>$data) {
		$tagName = $data['name'];
		$tagListBaseRow = $page->templateRows['availableTagsList'];
		if($tagNameId == $showTagNameId) {
			//switch to a row without the link, so it's more apparent what's selected.
			$tagListBaseRow = $page->templateRows['availableTagsList_noLink'];
		}
		$repArr = array(
			'tagNameId'	=> $tagNameId,
			'tagName'	=> $tagName
		);
		$tmpRow = mini_parser($tagListBaseRow, $repArr, '%%', '%%');
		if(strlen($data['icon_name'])) {
			$parsedIconDisplay = mini_parser($showIconRow, array('icon_name' => $data['icon_name']), '%%', '%%');
		}
		else {
			$parsedIconDisplay = "";
		}
		$tmpRow = mini_parser($tmpRow, array('availableTagsList__tagIcon'=>$parsedIconDisplay), '{', '}');
		$myRow .= $tmpRow;
	}
	$page->add_template_var("availableTagsList", $myRow);
	$retval = mini_parser($myRow, array('tag_row'=>$recordList), '{', '}');
	
	return($retval);
}//end updateTagList()


function ajax__showTagRecords($tagNameId) {
	$objResponse = new xajaxResponse();
	
	//update the list of tags.
	$tagList = updateTagList($tagNameId);
	$objResponse->addAssign('tagList', 'innerHTML', $tagList);
	
	//now update the data for the current tag.
	$currentTagData = showCurrentTag($tagNameId);
	$objResponse->addAssign('currentTagData_header', 'innerHTML', $currentTagData);
	
	//update associated record list.
	$objResponse->addAssign('associatedRecords', 'innerHTML', showRecordList($tagNameId));
	
	//store in the session, so if they reload, the selected tag will remain.
	$sessionCache = new sessionCache("settings");
	$sessionCache->set_cache('tagNameId', $tagNameId);
	
	return($objResponse);
}//end ajax__showTagRecords()

?>