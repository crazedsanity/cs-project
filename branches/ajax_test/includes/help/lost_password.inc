<?php
/*
 * Created on Nov 9, 2007
 * 
 * TODO: use CAPTCHA to avoid automated username harvesting...
 */

$doNotRipRows = array();
if($_POST) {
	
	
	//now let's build the authToken.
	$atObj = new authToken($page->db);
	
	switch($_POST['action']) {
		case 'submitRequest': {
			
			//artificially slow processing so the little "processing" image will show...
			sleep(2);
			
			if(strlen($_POST['username'])) {
				//look up the user.
				$userObj = new userClass($page->db);
				$info = $userObj->get_user_info($_POST['username']);
				
				
				if(is_array($info)) {
					$contactObj = new contactClass($page->db);
					$contactObj->set_contact_id($info['contact_id']);
					$myData = $contactObj->get_contact();
					
					$atObj->db->beginTrans();
					$myTokenData = $atObj->create_token($myData['contact_id'], $myData['email'], $myData['username']);
					
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
						$sendEmailRes = send_email($myData['email'], $subject, $templateContents, $parseArr);
						$message = "Successfully sent message (". $sendEmailRes .")";
						$messageType = "notice";
						
						$atObj->db->commitTrans(__METHOD__);						
					}
					catch(exception $e) {
						$message = "Failed to send message: ". $e->getMessage();
						$messageType = "error";
						
						$atObj->db->rollbackTrans(__METHOD__);
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
		}//end of case "submitRequest"
		break;
		
		
		case 'authenticateRequest': {
			debug_print($_POST);
			$redirectUrl = NULL;
			if(count($_POST['auth']) == 2 && strlen($_POST['auth']['token']) == 32 
			&& strlen($_POST['auth']['checksum']) > 3 && is_numeric($_POST['tokenId'])) {
				//okay, try to authenticate the token!
				$hashThis = $_POST['checksum'];
				
				//retrieve contact data from the email address...
				$contactObj = new contactClass($page->db);
				$contactIdFromChecksum = $contactObj->get_contact_id_from_email($_POST['auth']['checksum'], FALSE);
				$userObj = new userClass($page->db);
				$myUid = $userObj->uid_from_contact_id($contactIdFromChecksum);
				$_SESSION['uid'] = $myUid;
				
				if(is_numeric($contactIdFromChecksum) && $contactIdFromChecksum > 0 && is_numeric($myUid) && $myUid > 0) {
					$tokenContactId = $atObj->authenticate_token($_POST['tokenId'], $_POST['auth']['token'], $_POST['auth']['checksum']);
					if($contactIdFromChecksum == $tokenContactId) {
						if($_POST['pass']['check'] == $_POST['pass']['confirm']) {
							//token good, passwords match... all is okay.
							$userObj->bypassAuthCheck = 1;
							$userObj->change_password(NULL, $_POST['pass']['check'], $_POST['pass']['confirm']);
							$destroyRes = $atObj->destroy_token($_POST['tokenId']);
							$redirectUrl = '/login.php?from=lost_password&random='. time();
							$page->set_message_wrapper(array(
								'title'		=> "Password Changed",
								'message'	=> "Your password has been successfully updated.  Please log in.",
								'type'		=> "notice"
							));
						}
						else {
							//token okay, passwords don't match (javascript should keep this from ever happening).
							$page->set_message_wrapper(array(
								'title'		=> "Passwords Don't Match",
								'message'	=> "The passwords you set do not match.  Please try again.",
								'type'		=> "error"
							));
							$redirectUrl = "/help/lost_password?validate=". $_POST['auth']['token'] ."&random=". time();
						}
					}
					else {
						//we got contact data from checksum, and the token is good, but the contact_id for the 
						//	token doesn't match the one retrieved for their email address...
						$page->set_message_wrapper(array(
							'title'		=> "Token Information Mismatch",
							'message'	=> "The data in the token does not match information for your " .
											"contact.  Something has changed on your contact record, or " .
											"you've attempted to forge a token.",
							'type'		=> "fatal"
						));
					}
				}
				else {
					//couldn't get any contact information; they probably faked the request.
					$page->set_message_wrapper(array(
						'title'		=> "Invalid Token",
						'message'	=> "The requested token is not valid.  Have you used it already?" .
									"myContactDataId=(". $contactIdFromChecksum .")",
						'type'		=> "error"
					));
				}
				unset($_SESSION['uid']);
			}
			else {
				exit("poop");
				//invalid data in request.
				$page->set_message_wrapper(array(
					'title'		=> "Not Enough Information",
					'message'	=> "Your request was missing some important information.  Please try again.",
					'type'		=> "error"
				));
			}
		}//end of case "authenticateRequest"
		break;
		
		default: {
			debug_print($_POST);
			exit;				
		}
	}//end of switch
	
	//now redirect to get rid of the _POST vars.
	if(is_null($redirectUrl)) {
		$redirectUrl = '/help/lost_password?random='. time();
	}
	$page->conditional_header($redirectUrl, TRUE);
}
else {
	
	$atObj = new authToken($page->db);
	if(isset($_GET['validate']) && is_numeric($_GET['validate'])) {
		if($atObj->token_exists($_GET['validate'])) {
			$page->add_template_var('HTMLBODY_EXTRA', 'onLoad="javascript:void(setInterval(\'lostPassword_validate()\', 500));"');
			
			$page->add_template_var('tokenId', $_GET['validate']);
			
			$doNotRipRows[] = 'authenticateRequest';
		}
		else {
			$page->set_message_wrapper(array(
				'title'		=> "Invalid Token",
				'message'	=> "The token you attempted to validate does not exist.  It has already been used or " .
								"has expired.  You can delete the email that contains this invalid token, and " .
								"simply generate a new one.",
				'type'		=> 'error'
			));
			$page->conditional_header('/help/lost_password?invalidToken&random='. time());
		}
	}
	else {
		$doNotRipRows[] = 'newSubmission';
	}
}

$page->set_all_block_rows('content', $doNotRipRows);

?>
