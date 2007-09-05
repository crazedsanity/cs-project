<?php
/*
 * Created on Aug 23, 2007
 * 
 */

$requiredFields = array('dbname', 'host', 'port', 'user', 'password');

//TODO: consider adding a check that the /lib directory is writable, to avoid errors later on.

if($_POST) {
	$page->gfObj->debug_print($_POST);
	store_setup_data(1, $_POST['fields']);
	store_setup_data(1, 1, 'result');
	store_setup_data(1, 'Values stored successfully', 'text');
	
	//set the next step as accessible.
	store_setup_data(2, 1, 'accessible');
	
	//TODO: consider moving the database connectivity tests into this step (and alter the status message below accordingly).
	$page->set_message_wrapper(
		array(
			'title'		=> "Success",
			'message'	=> "Your data was stored successfully.",
			'type'		=> "status"
		)
	);
	$page->conditional_header("/setup/2", TRUE);
}
else {
	
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