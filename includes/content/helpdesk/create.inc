<?php
/*
 * SVN INFORMATION:::
 * ------------------
 * Last Author:         $Author:crazedsanity $ 
 * Current Revision:    $Revision:637 $ 
 * Repository Location: $HeadURL:https://cs-project.svn.sourceforge.net/svnroot/cs-project/trunk/includes/content/helpdesk.inc $ 
 * Last Updated:        $Date:2007-11-20 11:04:33 -0600 (Tue, 20 Nov 2007) $
 */


if($_POST) {
	//CREATING NEW RECORD!!!
	$dataArr = $_POST['data'];
	$result = $proj->helpdeskObj->create_record($dataArr);
	
	if($result > 0) {
		//TODO: implement email portion.
		set_message_wrapper(array(
			"title"		=> "Success",
			"message"	=> "Helpdesk issue #". $result ." created successfully.  You should receive an email confirmation shortly",
			"type"		=> "notice"
		));
		
		//only go back to helpdesk if we're supposed to.
		if($_SESSION['goBackLink']) {
			$byPassUrlExtras = TRUE;
			$baseUrl = $_SESSION['goBackLink'];
		}
			else {
			$helpdeskId = $result;
			$urlExtrasArr['ID'] = $result;
		}
	}
	else {
		//something bad happened.
		set_message_wrapper(array(
			"title"		=> "",
			"message"	=> "",
			"type"		=> "error"
		));
	}
	
	//redirect them.
	$url = "/content/helpdesk/view?ID=". $result;
	if($_SESSION['goBackLink']) {
		$url = $_SESSION['goBackLink'];
	}
	$page->gfObj->conditional_header($url);
}
else {
	create_page_title($page, array('title' => "Create a Helpdesk Issue"));
	$page->set_all_block_rows("content");
	
	//show the "category" selection.
	$categoryList = $proj->helpdeskObj->get_category_list('bug', TRUE);
	$page->add_template_var("select_tags", $categoryList);
	
	//
	$page->add_template_var("email", $_SESSION['email']);
	$page->add_template_var("linked_proj", cleanString($_GET['proj']),"numeric");
}
?>