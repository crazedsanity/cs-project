<?php
/*
 * Created on Oct 15, 2007
 * 
 * SVN INFORMATION:::
 * ------------------
 * Last Author:         $Author$ 
 * Current Revision:    $Revision$ 
 * Repository Location: $HeadURL$ 
 * Last Updated:        $Date$
 */

$contactObj = new contactClass($page->db);

$page->allow_invalid_urls(TRUE);

if($_POST) {
	if(strlen($_POST['action']) && is_numeric($_POST['contact_id'])) {
		$contactObj->set_contact_id($_POST['contact_id']);
		
		$messageArr = array(
			'title'		=> "Update Results",
			'message'	=> "",
			'type'		=> 'status'
		);
		
		switch($_POST['action']) {
			
			case 'update': {
				$contactObj->db->beginTrans("_POST_updateAction");
				
				$totalUpdates = 0;
				$totalFailures = 0;
				
				$updatesArr = array();
				
				if(strlen($_POST['new_contact_email'])) {
					$isPrimary = FALSE;
					if(!is_numeric($_POST['contactData']['contact_email_id'])) {
						$isPrimary = TRUE;
						unset($_POST['contactData']['contact_email_id']);
						if(!count($_POST['contactData'])) {
							unset($_POST['contactData']);
						}
					}
					
					if($contactObj->create_contact_email($_POST['new_contact_email'], $isPrimary)) {
						$totalUpdates++;
						$res = "Successfully added new email";
						if($isPrimary) {
							$res .= " and set as primary address";
						}
						add_update_result($updatesArr, $res);
					}
					else {
						$totalFailures++;
						add_update_result($updatesArr, "FAILED to create new email address...");
					}
				}
				
				if(is_array($_POST['contactData'])) {
					if($contactObj->update_contact_data($_POST['contactData'])) {
						$totalUpdates++;
						add_update_result($updatesArr, "Successfully updated contact data");
					}
					else {
						$totalFailures++;
						add_update_result($updatesArr, "FAILED to update contact data");
					}
				}
				
				if(is_array($_POST['attributes'])) {
					if($contactObj->mass_update_contact_attributes($_POST['attributes'])) {
						$totalUpdates++;
							add_update_result($updatesArr, "Successfully updated attributes");
					}
					else {
						$totalFailures++;
							add_update_result($updatesArr, "FAILED to update attributes");
					}
				}
				
				if(is_array($_POST['delAttrib'])) {
					foreach($_POST['delAttrib'] as $name) {
						if($contactObj->delete_contact_attribute($name)) {
							$totalUpdates++;
							add_update_result($updatesArr, "Successfully deleted: <b>". $name ."</b>");
						}
						else {
							$totalFailures++;
							add_update_result($updatesArr, "FAILED to delete attribute \"<b>". $name ."</b>\"");
							break;
						}
					}
				}
				
				if(is_array($_POST['addAttribute']) && count($_POST['addAttribute']) == 2) {
					if($contactObj->create_contact_attribute($_POST['addAttribute']['name'], $_POST['addAttribute']['value'])) {
						$totalUpdates++;
						add_update_result($updatesArr, "Successfully added attribute (". $_POST['addAttribute']['name'] .")");
					}
					else {
						$totalFailures++;
						add_update_result($updatesArr, "FAILED to add attribute (". $_POST['addAttribute']['name'] .")");
					}
				}
				
				$messageArr['message'] = create_update_result($updatesArr);
				$page->set_message_wrapper($messageArr);
				
				$contactObj->db->commitTrans();
				
			}
			break;
			
			default: {
				$page->set_message_wrapper(array(
					'title'		=> "Invalid Action",
					'message'	=> "The requested action, [". $_POST['action'] ."], is invalid.",
					'type'		=> "error"
				));
			}
		}
	}
	else {
		$page->set_message_wrapper(array(
			'title'		=> "Insufficient Information",
			'message'	=> "The requested update could not be handled due to insufficient information.",
			'type'		=> "error"
		));
	}
	
	$page->conditional_header('/content/contacts/view/'. $_POST['contact_id']);
}
else {
	if(count($sectionArr) == 4 && $sectionArr[2] == 'view') {
		$contactId = $sectionArr[3];
	}
	
	if(is_numeric($contactId)) {
		try{
			$page->rip_all_block_rows();
			
			
			$contactObj->set_contact_id($contactId);
			$contactDetails = $contactObj->get_contact();
			
			$userDetails = $user->get_user_info(array('contact_id'=>$contactId));
			if(is_array($userDetails)) {
				$contactDetails['username'] = $userDetails['username'];
				$page->add_template_var('display_username', $page->templateRows['display_username']);
			}
			
			
			//set the main details.
			foreach($contactDetails as $name=>$value) {
				$page->add_template_var($name, $value);
			}
			
			$addableAttribs = $contactObj->get_attribute_list(2);
			$page->add_template_var('attributeOptionList', $page->gfObj->array_as_option_list($addableAttribs));
			
			$contactAttributes = $contactObj->get_contact_attributes();
			$attributeFormRows = "";
			$baseRow = $page->templateRows['attributeRow'];
			foreach($contactAttributes as $name=>$value) {
				$displayValue = $value;
				if(!strlen($value) || !strlen(preg_replace('/ /', '', $value))) {
					$displayValue = "<font color='red'><b>[EMPTY]</b></font>";
				}
				$repArr=array(
					'name'			=> $name,
					'displayValue'	=> $displayValue,
					'value'			=> $value
				);
				
				$attributeFormRows .= $page->gfObj->mini_parser($baseRow, $repArr, '{', '}');
			}
			$page->add_template_var('attributeRow', $attributeFormRows);
			
			$emailList = $contactObj->get_contact_email_list();
			$emailRow = "";
			foreach($emailList as $id=>$value) {
				$repArr = array(
					'contact_email_id'	=> $id,
					'email'				=> $value,
					'isPrimary'			=> ""
				);
				if($id == $contactDetails['contact_email_id']) {
					$repArr['isPrimary'] = 'checked';
				}
				$emailRow .= $page->mini_parser($page->templateRows['contactEmailAddress'], $repArr, '%%', '%%');
			}
			$page->add_template_var('contactEmailAddress', $emailRow);
		}
		catch(exception $e) {
			$page->set_message_wrapper(array(
				'title'		=> "Error Encountered",
				'message'	=> "Failed to retrieve data: ". $e->getMessage(),
				'type'		=> "fatal"
			));
		}
	}
	else {
		$page->rip_all_block_rows();
		$contactListing = $contactObj->get_all_contacts();
		
		$baseRow = $page->templateRows['dataRow'];
		
		$myRow = "";
		foreach($contactListing as $conId=>$data) {
			$data['rowColor'] = swapValue($rowColor1, "rgb(213, 213, 213)", "rgb(194, 194, 194)");
			$data['rowColor2'] = swapValue($rowColor2,"#D5D5D5", "#C2C2C2");
			$myRow .= $page->mini_parser($baseRow, $data, '{', '}');
		}
		$page->add_template_var('dataRow', $myRow);
	}
}


function add_update_result(array &$updateResults, $newResult) {
	$updateResults[] = $newResult;
}//end add_update_result()


function create_update_result(array $updateResults) {
	#$retval = "Result of updates: <ul>";
	$retval = "";
	$gf = new cs_globalFunctions;
	foreach($updateResults as $result) {
		$retval = $gf->create_list($retval, "<li>". $result ."</li>", "\n");
	}
	$retval = "Result of updates: <ul>". $retval;
	$retval .= "</ul>";
	return($retval);
}//end create_update_result()



?>
