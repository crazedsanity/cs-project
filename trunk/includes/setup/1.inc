<?php
/*
 * Created on Aug 23, 2007
 * 
 */

$requiredFields = array('db_name', 'db_host', 'db_port', 'db_user', 'db_pass');

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
			'db_name'	=> "cs_project",
			'db_host'	=> "localhost",
			'db_port'	=> "5432",
			'db_user'	=> "postgres",
			'db_pass'	=> ""
		);
	}
	
	foreach($setupData as $name=>$value) {
		$page->add_template_var($name, $value);
	}
	
}

?>