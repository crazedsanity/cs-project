<?php
/*
 * Created on Oct 26, 2009
 *
 *  SVN INFORMATION:::
 * -------------------
 * Last Author::::::::: $Author$ 
 * Current Revision:::: $Revision$ 
 * Repository Location: $HeadURL$ 
 * Last Updated:::::::: $Date$
 */


try {
	$db = new cs_phpDB;
	$db->connect(get_config_db_params());
	$page->db = $db;
	
	$tProj = new projectClass($db);
	$page->add_template_var('cs-project_version', $tProj->get_version());
	$page->add_template_var('VERSION_STRING', $tProj->get_version());
	unset($tProj);
}
catch(exception $e) {
	//nothing to see here, move along (probably not setup yet).
}
?>
