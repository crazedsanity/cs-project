<?php
/*
 * Created on Aug 23, 2007
 * 
 */


if($_POST) {
	//check that step 1 was successful.
	if(get_setup_data(1, 'result')) {
		
		//first, check to see if the database already exists.
		try {
			$db = new cs_phpDB;
			$db->connect(get_setup_data(1));
			$page->gfObj->debug_print($db,1);
			$dbExists = TRUE;
		}
		catch(exception $e) {
			$page->gfObj->debug_print("Connection failed: ". $e->getMessage());
			$dbExists = FALSE;
		}
		
		if($dbExists) {
			
		}
	}
	else {
		throw new exception("Please enter data for step #1 before attempting to load schema!");
	}
}
else {
}

?>