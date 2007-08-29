<?php
/*
 * Created on Aug 23, 2007
 * 
 */


if($_POST) {
	
	$obj = new __setupDefaultValues();
	$obj->go();
}
else {
}


class __setupDefaultValues {
	
	
	private $db;
	private $gfObj;
	
	//=========================================================================
	public function __construct() {
		$this->db = new cs_phpDB;
		$this->gfObj = new cs_globalFunctions;
	}//end __construct()
	//=========================================================================
	
	
	
	//=========================================================================
	public function go() {
		
		try {
			$params = get_setup_data(1, 'data');
			$this->db->connect($params);
			$this->db->beginTrans();
			$retval = "Connected successfully to the database.";
			
			//now that we've connected, start doing stuff.
			$retval = $this->set_version();
		}
		catch(exception $e) {
			$retval = "An error occurred: ". $e->getMessage();
		}
		
		$this->gfObj->debug_print(__METHOD__ .": RETURNING: ". $retval);
		return($retval);
		
	}//end go()
	//=========================================================================
	
	
	
	//=========================================================================
	private function set_version() {
		//get the version string.
		$fullVersionString = read_version_file();
		
		$suffixData = explode('-', $fullVersionString);
		if(count($suffixData) == 2 && preg_match('/\./', $suffixData[0]) && !preg_match('/\./', $suffixData[1])) {
			//there's a suffix, and it doesn't contain periods (i.e. "1.0.0-ALPHA1")
			$suffix = $suffixData[1];
		}
		elseif(count($suffixData) == 1) {
			//no suffix.
			$suffix = "";
		}
		else {
			//there's a dash in the name, but it's invalid or contains periods (i.e. "BETA-1.0.0" or "1.0.0-ALPHA1.0")
			throw new exception(__METHOD__ .": version string is invalid (". $fullVersionString ."), suffix contains dashes, or there is a prefix");
		}
		
		//remove the suffix & parse it.
		$versionString = $suffixData[0];
		$versionData = explode('.', $versionString);
		
		
		$sqlData = array(
			'version_string'		=> $fullVersionString,
			'version_major'			=> $versionData[0],
			'version_minor'			=> $versionData[1],
			'version_maintenance'	=> $versionData[2],
			'version_suffix'		=> $suffix
		);
		
		$retval = 0;
		foreach($sqlData as $name => $value) {
			$sql = "SELECT internal_data_set_value('". $name ."', '". $value ."')";
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(!strlen($dberror) && $numrows == 1) {
				$retval++;
			}
			else {
				throw new exception(__METHOD__ .": failed to set (". $name .") as (". $value .")::: ". $dberror);
			}
		}
		
		if($retval == count($sqlData)) {
			//okay, the final test: run a query that straps everything together, to ensure it all has the same version.
			$sql = "SELECT internal_data_get_value('version_major') || '.' || internal_data_get_value('version_minor') " .
				" || '.' || internal_data_get_value('version_maintenance') || '-' || internal_data_get_value('version_suffix') as text;";
			$numrows = $this->db->exec($sql);
			$dberror = $this->db->errorMsg();
			
			if(!strlen($dberror) && $numrows == 1) {
				$data = $this->db->farray();
				$dbVersionString = $data[0];
				
				if($dbVersionString === $fullVersionString) {
					//okay, one final test: check that the "version_string" in the database matches ours.
					$sql = "SELECT internal_data_get_value('version_string')";
					$numrows = $this->db->exec($sql);
					$dberror = $this->db->errorMsg();
					
					if(!strlen($dberror) && $numrows == 1) {
						$data = $this->db->farray();
						$dbVersionString = $data[0];
						
						if($dbVersionString === $fullVersionString) {
							$retval = "Successfully set version string";
						}
						else {
							throw new exception(__METHOD__ .": derived database version string (". $dbVersionString .") doesn't match our version (". $fullVersionString .")");
						}
					}
					else {
						throw new exception(__METHOD__ .": failed to retrieve full version_string from database::: ". $dberror ."<BR>\nSQL::: ". $sql);
					}
				}
				else {
					throw new exception(__METHOD__ .": derived database version string (". $dbVersionString .") doesn't match our version (". $fullVersionString .")");
				}
			}
			else {
				throw new exception(__METHOD__ .": failed to retrieve derived database version string::: ". $dberror ."<BR>\nSQL::: ". $sql);
			}
		}
		else {
			//it's cryptic, but what should it really say???
			throw new exception(__METHOD__ .": internal error, checksum didn't match");
		}
		
		return($retval);
		
	}//end set_version()
	//=========================================================================
	
}//end __setupDefaultValues{}

?>