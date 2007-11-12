<?php
/*
 * Created on Nov 9, 2007
 * 
 * TODO: use CAPTCHA to avoid automated username harvesting...
 */


if($_POST) {
	
	switch($_POST['action']) {
		case 'submitRequest': {
			if(strlen($_POST['username'])) {
				//look up the user.
				$userObj = new userClass($page->db);
				$info = $userObj->get_user_info($_POST['username']);
				
				
				if(is_array($info)) {
					$contactObj = new contactClass($page->db);
					$contactObj->set_contact_id($info['contact_id']);
					$myData = $contactObj->get_contact();
					
					debug_print($myData);
					
					//now let's build the authToken.
					$atObj = new authToken($page->db);
					
					$atObj->db->beginTrans();
					$myTokenData = $atObj->create_token($myData['contact_id'], $myData['email'], $myData['username']);
					debug_print($myTokenData);
					
					//now that we've got what we need, send an email.
					$page->add_template_file('__passTmpl', 'email/lost_password.tmpl');
					$templateContents = $page->templateVars['__passTmpl'];
					$subject = PROJ_NAME ." Lost Password Request #". $myTokenData['id'] ." -- ". $myData['email'];
					
					$parseArr = array(
						'PROJECT_NAME'		=> PROJ_NAME,
						'VERSION_STRING'	=> VERSION_STRING,
						'PROJECT_URL'		=> PROJECT_URL,
						'tokenNumber'		=> $myTokenData['id'],
						'authToken'			=> $myTokenData['hash']
					);
					
					try {
						$sendEmailRes = send_email('dan.falconer@gmail.com', $subject, $templateContents, $parseArr);
						$message = "Successfully sent message (". $sendEmailRes .")";
						$messageType = "notice";
						
						//TODO: commit the transaction!
					}
					catch(exception $e) {
						$message = "Failed to send message: ". $e->getMessage();
						$messageType = "error";
						
						//TODO: rollback the transaction!
					}
					
					$page->set_message_wrapper(array(
						'title'		=> "Result of Sending Email",
						'message'	=> $message,
						'type'		=> $messageType
					));
				}
				else {
					$page->set_message_wrapper(array(
						'title'		=> "No Such Username",
						'message'	=> "The username you submitted (". $_POST['username'] .") doesn't exist.  Did you misspell it?",
						'type'		=> "error"
					));
				}
			}
			else {
				$page->set_message_wrapper(array(
					'title'		=> "No Data Submitted",
					'message'	=> "It appears there wasn't enough data submitted to do anything useful with.  Please try again.",
					'type'		=> "notice"
				));
			}
		}
	}//end of switch
	
	$page->conditional_header('/help/lost_password?random='. time(), TRUE);
}
else {
	//TODO: handle tokens.
}

?>
