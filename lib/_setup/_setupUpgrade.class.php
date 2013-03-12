<?php
/*
 * Created on Aug 23, 2007
 * 
 * SVN INFORMATION:::
 * -------------------
 * Last Author::::::::: $Author$ 
 * Current Revision:::: $Revision$ 
 * Repository Location: $HeadURL$ 
 * Last Updated:::::::: $Date$
 */


class _setupUpgrade extends cs_webdbupgrade {
	
	public function __construct(cs_phpDB $db) {
		$this->db = $db;
		parent::__construct();
	}//end __construct()
	
	public function finalize_conversion() {
		$myVersion = parent::read_version_file();
		$setDataResult = parent::run_sql("SELECT internal_data_set_value('converted_from_version', '". parent::read_version_file() ."')");
		parent::update_num_users_to_convert();
		$retval = parent::update_database_version($myVersion);
		debug_print(__METHOD__ .": myVersion=(". $myVersion ."), setDataResult=(". $setDataResult ."), retval=(". $retval .")");
		
		return($myVersion);
	}//end finalize_conversion()
}//end _setupUpgrade{}

?>
