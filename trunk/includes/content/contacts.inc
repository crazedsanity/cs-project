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

require_once(dirname(__FILE__) .'/../../lib/contactClass.php');

$contactObj = new contactClass($page->db);


if($_POST) {
	//TODO: handle changes.
	debug_print($_POST);
	exit;
	
	if(strlen($_POST['action']) && is_numeric($_POST['contact_id'])) {
		$contactObj->set_contact_id($_POST['contact_id']);
		switch($_POST['action']) {
			
			case 'update': {
				$contactObj->mass_update_contact($_POST['contactData']);
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
			$contactObj->set_contact_id($contactId);
			$contactDetails = $contactObj->get_contact();
			$page->rip_all_block_rows();
			
			//set the main details.
			$mainDetails = array('contact_id', 'fname', 'lname', 'company', 'email');
			foreach($mainDetails as $name) {
				$page->add_template_var($name, $contactDetails[$name]);
			}
			
			$editableAttributes = $contactDetails;
			$deletableAttributes = $contactDetails;
			unset($editableAttributes['contact_id'], $deletableAttributes['fname'], $deletableAttributes['lname']);
			
			$attributeFormRows = "";
			$baseRow = $page->templateRows['attributeRow'];
			foreach($editableAttributes as $name=>$value) {
				$repArr=array(
					'name'	=> $name,
					'value'	=> $value
				);
				
				$attributeFormRows .= $page->gfObj->mini_parser($baseRow, $repArr, '{', '}');
			}
			$page->add_template_var('attributeRow', $attributeFormRows);
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

?>
