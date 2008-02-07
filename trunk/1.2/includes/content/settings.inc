<?php
/*
 * Created on Feb 16, 2006
 * 
 * SVN INFORMATION:::
 * ------------------
 * Last Author:         $Author$ 
 * Current Revision:    $Revision$ 
 * Repository Location: $HeadURL$ 
 * Last Updated:        $Date$
 */


$prefObj = new pref($page->db, $_SESSION['uid']);
$GLOBALS['objects']['pref'] = $prefObj;
if($user->is_admin() == 1) {
	//add the template to show various admin stuff.
	$page->add_template_var("admin_section", $page->templateRows['admin_section']);
	
	//create an object with administrative capabilities.
	$adminUserClass = new adminUserClass($page->db);
	$GLOBALS['objects']['admin'] = $adminUserClass;
}

addAjax();

require_once(dirname(__FILE__) .'/../../lib/adminUserClass.php');
require_once(dirname(__FILE__) .'/../../lib/prefClass.php');


if($_POST) {
	$action = $_POST['action'];
	$data = $_POST[$action];
	
	//setting.
	$goHere = "/content/settings";
	
	switch($action) {
		case 'changeLeader': {
			$adminClass = new adminUserClass($page->db);
			
			//set the array of updates.
			$updatesArr = array(
				'leader_uid'	=> $data['newLeader']
			);
			$result = $adminClass->update_group($data['groupId'], $updatesArr);
			
			if($result) {
				//SUCCESS!!!
				$messageArr = array(
					'title'		=> "Update Successful",
					'message'	=> "The leader for group #". $data['groupId'] ." was change successfully.",
					'type'		=> 'status' 
				);
			}
			else {
				//FAILURE!!!
				$messageArr = array(
					'title'		=> "Update Failed",
					'message'	=> "Unable to change leader... check the logs.",
					'type'		=> 'error'
				);
			}
			set_message_wrapper($messageArr);
		}
		break;
		
		
		case "change_password": {
			$changePassResult = $user->change_password($data['old_password'], $data['new_password'], $data['confirm_new']);
			if($changePassResult == 1) {
				//Good to go.
				set_message_wrapper(array(
					"title"		=> "SUCCESS",
					"message"	=> "Your password was changed successfully [$changePassResult].",
					"type"		=> "status"
				));
			}
			else {
				//FAILED!
				set_message_wrapper(array(
					"title"		=> "Error Changing Password",
					"message"	=> "Unable to change password.  Make sure you typed everything " .
							"correctly and then try again.<BR>\n" .
							"ERROR: " . $user->lastError ."<BR>\n" .
							"Result: [$changePassResult]",
					"type"		=> "error"
				));
			}
			$goHere = "/content/settings?result=$changePassResult";
			break;
		}
		break;
		
		case "addUser": {
			$adminUserClass = new adminUserClass($page->db);
			$newId = $adminUserClass->create_user($_POST['addUser']);
			
			if(is_numeric($newId)) {
				$messageArr = array(
					'title'		=> "Created User",
					'message'	=> "Created new user #". $newId ." successfully.",
					'type'		=> "status"
				);
			}
			else {
				$messageArr = array(
					'title'		=> "Unable to Create User!",
					'message'	=> "create_user() returned (". $newId .")",
					'type'		=> "error"
				);
			}
			set_message_wrapper($messageArr);
			$goHere = "/content/settings";
		}
		break;
			
		DEFAULT:
	}
	
	if(strlen($goHere)) {
		conditional_header($goHere);
	}
	exit;
}
else {
	//some display settings.
	$x = $page->set_all_block_rows("content");
	
	
	//show 'em their preferences.
	$allPrefs = $prefObj->list_all_prefs();
	$userPrefs = $prefObj->get_user_prefs();
	
	if(is_array($allPrefs)) {
		$rowColor = "#D3D3D3";
		foreach($allPrefs as $index=>$data) {
			$myOptions = $data['optionList'];
			
			//TODO: fix this... it's sloppy.
			$myOptionList = array();
			$optionListRepArr = array();
			foreach($data['optionList'] as $x=>$y) {
				$tKey = $y['pref_option_id'];
				$tVal = $y['name'];
				$myOptionList[$tKey] = $tVal;
				
				$optionListRepArr[$tKey] = $y;
			}
			
			unset($data['optionList'], $data['optionList']);
			
			$checkThis = NULL;
			if(strlen($userPrefs[$data['pref_type_id']])) {
				$checkThis = $userPrefs[$data['pref_type_id']];
				$data['currentSetting'] = $myOptionList[$checkThis];
			}
			else {
				$data['currentSetting'] = '<font color="red"><b>Select something!</b></font>';
			}
			
			$data['row_optionList'] = array_as_option_list(
				$myOptionList, $checkThis, 'select', $page->templateRows['pref_optionList'], $optionListRepArr
			);
			
			$rowColor = swapValue($rowColor, '#D3D3D3', '#C3C3C3');
			$data['rowColor'] = $rowColor;
			
			$prefRow .= mini_parser($page->templateRows['pref_row'], $data, '%%', '%%');
		}
		$page->add_template_var('pref_row', $prefRow);
	}
	
	if(count($page->ftsSections) == 3 && $page->ftsSections[2] == 'admin') {
		if($user->is_admin() == 1) {
			//add the template to show various admin stuff.
			$page->add_template_var("admin_section", $page->templateRows['admin_section']);
			
			//create an object with administrative capabilities.
			$adminUserClass = new adminUserClass($page->db);
			
			//build the list of available groups.
			$myGroups = $adminUserClass->get_groups(TRUE);
			$page->add_template_var("addUser_gruppe", array_as_option_list($myGroups, $_SESSION['login_group_id']));
			
			//Group administration.
			{
				$showGroup = $page->ui->get_cache("settings/showGroup");
				if(is_numeric($_GET['showGroup'])) {
					$showGroup = $_GET['showGroup'];
				}
				elseif(!is_numeric($showGroup)) {
					$showGroup = $_SESSION['login_group_id'];
				}
				$page->ui->set_cache("settings/showGroup", $showGroup);
				regenerate_group_lists($showGroup);
				display_current_leader($showGroup);
			}
		}
		else {
			$msg = array(
				'title'		=> "Insufficient Privileges ",
				'message'	=> "Administrative privileges are required to view this section.",
				'type'		=> "fatal"
			);
			$page->set_message_wrapper($msg);
		}
	}
}


function ajax__update_preference($prefId, $newValue) {
	$page = $GLOBALS['objects']['page'];
	$prefObj = $GLOBALS['objects']['pref'];
	$objResponse = new xajaxResponse();
	
	$prefData = $prefObj->list_all_prefs(array('pref_type_id' => $prefId));
	
	//update the preference.
	$result = $prefObj->update_user_pref($prefId, $newValue);
	
	$properSelectedName = $prefData['optionList'][$newValue]['name'];
	
	if($result == 1) {
		$messageArr = array(
			'title'		=> "Preference Updated",
			'message'	=> "Your preference for <b>". $prefData['display_name'] ."</b> was successfully " .
					"stored (<i>". $properSelectedName ."</i>).",
			'type'		=> "status"
		);
	}
	else {
		$messageArr = array(
			'title'		=> "Error Updating Preference",
			'message'	=> "There was an error while attempting to update your preference ($result).",
			'type'		=> "error"
		);
	}
	$page->set_message_wrapper($messageArr);
	$objResponse->addAssign('MAIN_error_message', 'innerHTML', $page->process_set_message());
	
	$objResponse->addAssign('pref_currentSetting_'. $prefId, 'innerHTML', $properSelectedName);
	
	return($objResponse);
	
}//end ajax__update_preference()




function ajax__group_user($groupId, $uid, $addRemove) {
	$user = $GLOBALS['objects']['user'];
	$page = $GLOBALS['objects']['page'];
	$objResponse = new xajaxResponse();
	if($user->is_admin()) {
		
		$adminUserClass = $GLOBALS['objects']['admin'];
		if($addRemove == 'add') {
			$result = $adminUserClass->add_user_to_group($uid, $groupId);
		}
		else {
			$result = $adminUserClass->remove_user_from_group($uid, $groupId);
		}
		regenerate_group_lists($groupId, $objResponse);
		
		$msg = array(
			'title'		=> "Update Complete",
			'message'	=> "Result of <b>". $addRemove ."</b>: (". $result .")",
			'type'		=> "status"
		);
		
		
		$page->set_message_wrapper($msg);
		
		//now regenerate the current users & the excluded users lists.
	}
	else {
		$msg = array(
			'title'		=> "Admin Privileges Required",
			'message'	=> "You must have admin privileges in order to perform this action.",
			'type'		=> "error"
		);
		$page->set_message_wrapper($msg);
	}
	
	$objResponse->addAssign('MAIN_error_message', 'innerHTML', $page->process_set_message());
	
	return($objResponse);
}//end ajax__group_user()


function regenerate_group_lists($groupId, xajaxResponse &$objResponse=NULL) {
	$user = $GLOBALS['objects']['user'];
	$page = $GLOBALS['objects']['page'];
	$page->ui = new sessionCache("/userInput/content");
	$objResponse = new xajaxResponse();
	
	if($user->is_admin()) {
		
		if(!isset($page->templateRows['group_nonMembers'])) {
			$page->set_all_block_rows();
		}
		//array('group_nonMembers', 'group_currentMembers')
		$page->add_template_var('group_nonMembers', $page->templateRows['group_nonMembers']);
		$page->add_template_var('group_currentMembers', $page->templateRows['group_currentMembers']);
		
		$adminUserClass = $GLOBALS['objects']['admin'];
		$myGroups = $adminUserClass->get_groups(TRUE);
		
		//get a complete list of users.
		$allUsers = $adminUserClass->get_users();
		
		//display the list of available groups.
		$repArr = $adminUserClass->get_groups(FALSE);
		$displayGroupList = array_as_option_list(
			$myGroups, $groupId, "select", $page->templateRows['show_group_list'], $repArr
		);
		$page->add_template_var('show_group_list', $displayGroupList);
		
		//set this for later use... 
		$myGroupData = $repArr[$groupId];
		
		//now show all the users (if there are any) associated with that group.
		$groupData = $adminUserClass->get_group_user($groupId);
		$page->add_template_var('groupId', $groupId);
					
		$addUsers = 0;
		$removeUsers = 0;
		//TODO: is there any reason to do special processing if there is no $groupData?
		#if(is_array($groupData) && count($groupData)) {
			$baseRow = $page->templateRows['show_group_list__selectUser'];
			$addUsersRow = "";
			$removeUsersRow = "";
			
			//build the list of users that CAN be added, and the list that can be removed.
			$addUsers = 0;
			$removeUsers = 0;
			$changeLeaderOptionArr = array();
			foreach($allUsers as $uid=>$userData) {
				$userData['group_id'] = $groupId;
				if(isset($groupData[$uid])) {
					//they're already in this group.
					$removeUsersRow .= mini_parser($page->templateRows['show_group_list__removeUser'], $userData, '%%', '%%');
					$removeUsers++;
					$changeLeaderOptionArr[$uid] = $userData['username'];
					
					//
				}
				else {
					//not in this group.
					$addUsersRow .= mini_parser($page->templateRows['show_group_list__selectUser'], $userData, '%%', '%%');
					$addUsers++;
				}
			}
			
			if($removeUsers) {
				//show 'em the list to be removed.
				$page->add_template_var('show_group_list__removeUser', $removeUsersRow);
			}
			else {
				//tell 'em there's no users to be removed.
				$page->add_template_var('show_group_list__removeUser', $page->templateRows['show_group_list__noRemoveUser']);
				$removeUsersRow = $page->templateRows['show_group_list__noRemoveUser'];
			}
			
			if($addUsers) {
				//show 'em.
				$page->add_template_var('show_group_list__selectUser', $addUsersRow);
			}
			else {
				//no users to add.
				$page->add_template_var('show_group_list__selectUser', $page->templateRows['show_group_list__noUser']);
				$addUsersRow = $page->templateRows['show_group_list__noUser'];
			}
		/*}
		else {
			//show the row that says there's no users.
			$page->add_template_var('show_group_list__selectUser', $page->templateRows['show_group_list__noUser']);
			
			//now put ALL users into the list that can be added...
			foreach($allUsers as $uid=>$userData) {
				$userData['group_id'] = $groupId;
				$addUsersRow .= mini_parser($page->templateRows['show_group_list__selectUser'], $userData, '%%', '%%');
				$addUsers++;
			}
			$page->add_template_var('show_group_list__selectUser', $addUsersRow);
			$page->add_template_var('show_group_list__removeUser', $page->templateRows['show_group_list__noRemoveUser']);
		}#*/
	}
	else {
		$msg = array(
			'title'		=> "Admin Privileges Required",
			'message'	=> "You must have admin privileges in order to perform this action.",
			'type'		=> "error"
		);
		$page->set_message_wrapper($msg);
	}
	
	if(!is_null($objResponse)) {
		$ajax_nonUsers = mini_parser(
			$page->templateRows['group_nonMembers'],
			array(
				'show_group_list__removeUser'		=> $removeUsersRow,
				'show_group_list__noRemoveUser'	=> ""
			),
			'{', '}'
		);
		
		$ajax_curUsers = mini_parser(
			$page->templateRows['group_currentMembers'],
			array(
				'show_group_list__selectUser'		=> $addUsersRow,
				'show_group_list__noUser'			=> ""
			),
			'{', '}'
		);
		
		$objResponse->addAssign('group_currentMembers', 'innerHTML', $ajax_curUsers);
		$objResponse->addAssign('group_nonMembers', 'innerHTML', $ajax_nonUsers);
		$objResponse->addAssign('MAIN_error_message', 'innerHTML', $page->process_set_message());
	}
	
	return($objResponse);
}//end regenerate_group_lists()


function ajax__change_current_group($groupId) {
	$cache = new sessionCache("/userInput/content");
	$cache->set_cache("settings/showGroup", $groupId);
	$objResponse = regenerate_group_lists($groupId);
	
	//update the current leader.
	$curLeaderInfo = display_current_leader($groupId, TRUE);
	$objResponse->addAssign('currentGroupLeader', 'innerHTML', $curLeaderInfo);
	
	return($objResponse);
}//end ajax__change_current_group()


function display_current_leader($groupId, $isAjaxCall=FALSE) {
	$user = $GLOBALS['objects']['user'];
	$page = $GLOBALS['objects']['page'];
	$adminUserClass = $GLOBALS['objects']['admin'];
	$allUsers = $adminUserClass->get_users();
	
	$allGroupData = $adminUserClass->get_groups(FALSE);
	$myGroupData = $allGroupData[$groupId];
	#$page->add_template_var('currentGroupLeader', $allUsers[$myGroupData['leader_uid']]['username']);
	
			
	$changeLeaderOptionArr = array();
	$groupData = $adminUserClass->get_group_user($groupId);
	
	foreach($allUsers as $uid=>$userData) {
		if(isset($groupData[$uid])) {
			$changeLeaderOptionArr[$uid] = $userData['username'];
		}
	}
	
	//set the option list for changing the leader!
	$changeLeaderOption = array_as_option_list($changeLeaderOptionArr, $myGroupData['leader_uid']);
	$page->add_template_var('changeGroupLeader_list', $changeLeaderOption);
	
	$addTemplateVars = array(
		'currentGroupLeader'		=> $allUsers[$myGroupData['leader_uid']]['username'],
		'changeGroupLeader_list'	=> $changeLeaderOption
	);
	
	
	if($isAjaxCall) {
		$retval = $page->templateRows['currentGroupLeaderBox'];
		$retval = mini_parser($retval, $addTemplateVars, '{', '}');
		return($retval);
	}
	else {
		foreach($addTemplateVars as $name=>$value) {
			$page->add_template_var($name, $value);
		}
		$page->add_template_var('currentGroupLeaderBox', $page->templateRows['currentGroupLeaderBox']);
	}
}//end display_current_leader()
?>
