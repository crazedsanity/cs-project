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
				
				if(is_array($_POST['contactData'])) {
					if($contactObj->update_contact_data($_POST['contactData'])) {
						$totalUpdates++;
					}
					else {
						$totalFailures++;
					}
				}
				
				if(is_array($_POST['attributes'])) {
					if($contactObj->mass_update_contact_attributes($_POST['attributes'])) {
						$totalUpdates++;
					}
					else {
						$totalFailures++;
					}
				}
				
				if(is_array($_POST['delAttrib'])) {
					foreach($_POST['delAttrib'] as $name) {
						if($contactObj->delete_contact_attribute($name)) {
							$totalUpdates++;
						}
						else {
							$totalFailures++;
							break;
						}
					}
				}
				
				if(is_array($_POST['addAttribute']) && count($_POST['addAttribute']) == 2) {
					if($contactObj->create_contact_attribute($_POST['addAttribute']['name'], $_POST['addAttribute']['value'])) {
						$totalUpdates++;
					}
					else {
						$totalFailures++;
					}
				}
				
				$messageArr['message'] = "Done, total updates=(". $totalUpdates ."), totalFailures=(". $totalFailures .")";
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
			$contactObj->set_contact_id($contactId);
			$contactDetails = $contactObj->get_contact();
			$page->rip_all_block_rows();
			
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
