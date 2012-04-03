<?php
/*
 * Created on Aug 23, 2007
 * 
 */

$requiredFields = array('dbname', 'host', 'port', 'user', 'password');

//TODO: consider adding a check that the /lib directory is writable, to avoid errors later on.

if($_POST) {
	reset_all_steps(FALSE);
	store_setup_data(1, $_POST['fields']);
	$dbTestRes = test_db_stuff();
	if($dbTestRes === TRUE) {
		store_setup_data(1, 1, 'result');
		store_setup_data(1, 'Values stored successfully', 'text');
		
		//set the next step as accessible.
		store_setup_data(2, 1, 'accessible');
		
		//TODO: consider moving the database connectivity tests into this step (and alter the status message below accordingly).
		$page->set_message_wrapper(array(
			'title'		=> "Success",
			'message'	=> "Your data was stored successfully.",
			'type'		=> "status"
		));
		$page->conditional_header("/setup/2", TRUE);
	}
	else {
		//oops...
		store_setup_data(1, 0, 'result');
		store_setup_data(1, $dbTestRes, 'text');
		
		$page->set_message_wrapper(array(
			'title'		=> "Failure",
			'message'	=> "There was an error while testing the database connection:<BR>\n". $dbTestRes,
			'type'		=> "error"
		));
		$page->conditional_header("/setup/1", TRUE);
	}
}
else {
	//check if the /lib dir is writeable, so they've been warned.
	$fsObj = new cs_fileSystemClass(dirname(__FILE__) .'/../../lib');
	if(!$fsObj->is_writable(NULL)) {
		$page->set_message_wrapper(array(
			'title'		=> "Warning",
			'message'	=> "The lib directory of your install isn't writable.  Please correct this <BR>\n" .
					"before continuing, as setup will not be able create the config.xml file.",
			'type'		=> 'notice'
		));
	}
	
	$setupData = get_setup_data(1,'data');
	if(!is_array($setupData) || !count($setupData)) {
		//provide some defaults.
		$setupData = array(
			'database__dbname'	=> "cs_project",
			'database__host'		=> "localhost",
			'database__port'		=> "5432",
			'database__user'		=> "postgres",
			'database__password'	=> ""
		);
	}
	
	foreach($setupData as $name=>$value) {
		$page->add_template_var($name, $value);
	}
	
}

?>