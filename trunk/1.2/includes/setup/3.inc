<?php
/*
 * Created on Aug 23, 2007
 * 
 */


if($_POST) {
	$validSubmission = test_submitted_data($page, $_POST['users']);
	if($validSubmission === TRUE) {
		$setupObj->store_setup_data(3, $setupObj->handle_step(3), 'text');
	}
	else {
		store_setup_data(3, $_POST['users'], 'post_info');
		$page->set_message_wrapper(
			array(
				'title'		=> "Invalid User Data",
				'message'	=> "The user data you submitted appears to be invalid: <BR>\n". $validSubmission,
				'type'		=> "error"
			)
		);
		$page->conditional_header("/setup/3", TRUE);
	}
	
	$page->conditional_header("/setup/3", TRUE);
}
else {
	$myData = $setupObj->get_setup_data(3, 'post_info');
	if(is_array($myData)) {
		foreach($myData as $num=>$userArr) {
			foreach($userArr as $userField => $userValue) {
				$varName = "users__". $num ."__". $userField;
				if($userField == 'is_admin') {
					$varName .= "__checked";
					$userValue = "checked";
				}
				$page->add_template_var($varName, $userValue);
			}
		}
	}
}



//-----------------------------------------------------------------------------------------------------
function test_submitted_data(cs_genericPage &$page, array $users) {
	$counter = 0;
	$storeThis = array();
	$retval = "something terrible happened";
	foreach($users as $num=>$data) {
		$output = is_valid_user($page, $data);
		if(is_array($output)) {
			$counter++;
			$storeThis[$num] = $output;
		}
		else {
			$retval = "Failure on user #". $num .": ". $output;
			$counter = -1;
			break;
		}
	}
	
	if(is_numeric($counter) && $counter == count($users)) {
		store_setup_data(3, $storeThis, 'post_info');
		$retval = TRUE;
	}
	
	return($retval);
}//end test_submitted_data()
//-----------------------------------------------------------------------------------------------------



//-----------------------------------------------------------------------------------------------------
function is_valid_user(cs_genericPage &$page, array $data) {
	$requiredFields = array('username', 'password', 'confirmPassword', 'email', 'name');
	
	$good=0;
	$retval = "failure";
	foreach($requiredFields as $fieldname) {
		if(isset($data[$fieldname]) && strlen($data[$fieldname]) > 3) {
			$good++;
		}
		else {
			$retval = "Invalid length of data in ". $fieldname ." (or it's missing).";
			break;
		}
	}
	
	$goodToGo = FALSE;
	if($data['password'] == $data['confirmPassword']) {
		if(isset($data['is_admin'])) {
			if($data['is_admin'] === 't') {
				$goodToGo = TRUE;
			}
		}
		else {
			$goodToGo = TRUE;
		}
	}
	else {
		$retval = "Passwords don't match.";
	}
	
	if($good == count($requiredFields) && $goodToGo == TRUE) {
		$retval = $data;
		unset($retval['confirmPassword']);
	}
	
	$page->gfObj->debug_print(__METHOD__ .": RETURNING::: ". $retval);
	
	return($retval);
}//end is_valid_user()
//-----------------------------------------------------------------------------------------------------








?>
