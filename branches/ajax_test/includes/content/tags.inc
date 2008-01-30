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

if($page->ftsSections[2] == 'add') {
	//okay, they're adding a tag: the URL should look 
	//	something like: /content/tags/add/helpdesk/2/555
	//	where helpdesk is the source section, and 2 is that tag_name_id to attach record_id #555 to.
	
	//okay... let's do some pre-checking.
	$tagNameId = $page->ftsSections[3];
	$recordId = $page->ftsSections[4];
	
	
	//set a default message.
	$messageArr = array(
		'title'			=> 'Failed to Add Tag',
		'message'		=> "Either no tag was specified, or the tag was invalid.",
		'type'			=> 'error'
	);
	
	//here's where to go.
	$goBack = $_SERVER['HTTP_REFERER'];
	
	if(is_numeric($tagNameId) && is_numeric($recordId)) {
		$tagList = $proj->tagObj->get_tag_list();
		
		//okay, let's do the do.
		$result = $proj->tagObj->add_tag($recordId, $tagNameId);
		
		if($result == 1) {
			//it worked.
			$messageArr = array(
				'title'		=> 'Tag Added Successfully',
				'message'	=> "Successfully tagged record with \"". $tagList[$tagNameId] ."\"",
				'type'		=> 'status'
			);
		}
	}
	else {
		set_message_wrapper(array(
			'title'			=> 'Failed to Add Tag',
			'message'		=> "Either no tag was specified, or the tag was invalid.",
			'type'			=> 'error'
		));
	}
	set_message_wrapper($messageArr);
	conditional_header($goBack);
	exit;
}
elseif($page->ftsSections[2] == 'remove') {
	//here's where to go.
	$goBack = $_SERVER['HTTP_REFERER'];
	
	//set some vars.
	$tagNameId = $page->ftsSections[3];
	$recordId = $page->ftsSections[4];
	$tagList = $proj->tagObj->get_tag_list();
	
	//default message to display.
	$messageArr = array(
		'title'		=> 'Unable to Remove Tag',
		'message'	=> "There was an unknown error while attempting to remove the tag.",
		'type'		=> 'error'
	);
	
	if(is_numeric($tagNameId) && is_numeric($recordId)) {
		$result = $proj->tagObj->remove_tag($recordId,$tagNameId);
		
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
	
	set_message_wrapper($messageArr);
	conditional_header($goBack);
	
	exit;
}
elseif($_POST) {
	//got post vars.  Check them, then reload.
	debug_print($_POST);
	$goHere = $_SERVER['PHP_SELF'];
	
	if($_POST['action'] == 'update') {
		$result = $proj->tagObj->update_tag_modifier($_POST['tag_name_id'], $_POST['modifier']);
	}
	elseif($_POST['new_tag']) {
		//check for pre-existing tags of that name.
		$myTagList = $proj->tagObj->get_tag_list();
		$existingTagNameId = array_search($_POST['new_tag'], $myTagList);
		debug_print($myTagList);
		debug_print($existingTagNameId);
		
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
	
	//do the redirect to remove POST headers.
	conditional_header($goHere);
	exit;
}

//get the list of tags, regardless of whether we'll show 'em all or not.
$myTagList = $proj->tagObj->get_tag_list(TRUE, FALSE);

$showData = TRUE;
#$showTagNameId = $sessionCache->get_cache('tagNameId');


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
	//get data for this tag.
	$data = $proj->tagObj->get_records_for_tag($showTagNameId);
	
	//set some vars.
	$page->add_template_var("tagName", $myTagList[$showTagNameId]['name']);
	$page->add_template_var('tagModifier', $myTagList[$showTagNameId]['modifier']);
	$page->add_template_var("tagRecordCount", count($data));
	$page->add_template_var("showTagNameId", $showTagNameId);
	
	$page->add_template_var('modifier_option_list', create_priority_option_list($myTagList[$showTagNameId]['modifier'], 5, -5));
	
	create_page_title($page, array('title' => "Tag #". $showTagNameId . ": ". $myTagList[$showTagNameId]['name']));
	
	//rip all block rows.
	$page->set_all_block_rows('content');
	
	//now list all records for this tag (assign the "no records" row, then check).
	$tagRow = $page->templateRows['tag_row__noRecords'];
	if(count($data)) {
		//got data.
		$tagRow = "";
		$baseRow = $page->templateRows['tag_row'];
		foreach($data as $index=>$subData) {
			//add some things for alternating row colors.
			$subData['rowColor'] = swapValue($rowColor1, "rgb(213, 213, 213)", "rgb(194, 194, 194)");
			$subData['rowColor2'] = swapValue($rowColor2,"#D5D5D5", "#C2C2C2");
			
			//parse data into it.
			$tagRow .= mini_parser($baseRow, $subData, '%%', '%%');
		}
	}
	$page->add_template_var('tag_row', $tagRow);
	
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
			$tmpRow = mini_parser($tmpRow, array('availableTagsList__tagIcon'=>$parsedIconDisplay), '{', '}');
		}
		$myRow .= $tmpRow;
	}
	$page->add_template_var("availableTagsList", $myRow);
}

?>