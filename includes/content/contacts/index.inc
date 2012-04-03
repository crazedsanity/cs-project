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


?>
