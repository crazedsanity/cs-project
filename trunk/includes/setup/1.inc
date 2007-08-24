<?php
/*
 * Created on Aug 23, 2007
 * 
 */

$requiredFields = array('dbname', 'host', 'port', 'user', 'password');

if($_POST) {
	$page->gfObj->debug_print($_POST);
	store_setup_data(1, $_POST['fields']);
	store_setup_data(1, TRUE, 'result');
	
	$page->gfObj->conditional_header("/setup/2", TRUE);
}
else {
	
	$setupData = get_setup_data(1,'data');
	if(!is_array($setupData) || !count($setupData)) {
		//provide some defaults.
		$setupData = array(
			'dbname'	=> "cs_project",
			'host'		=> "localhost",
			'port'		=> "5432",
			'user'		=> "postgres",
			'password'	=> ""
		);
	}
	
	foreach($setupData as $name=>$value) {
		$page->add_template_var($name, $value);
	}
	
}

?>