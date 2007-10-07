<?php
/*
 * Created on Jul 2, 2007
 * 
 * SVN INFORMATION:::
 * ------------------
 * SVN Signature::::::: $Id$
 * Last Author::::::::: $Author$ 
 * Current Revision:::: $Revision$ 
 * Repository Location: $HeadURL$ 
 * Last Updated:::::::: $Date$
 * 
 */

require_once(dirname(__FILE__) .'/globalFunctions.php');
require_once(dirname(__FILE__) .'/cs-content/cs_phpDB.php');

class upgrade {
	
	private $fsObj;
	private $gfObj;
	private $config = NULL;
	private $db;
	
	private $versionFileVersion = NULL;
	private $configVersion = NULL;
	private $databaseVersion = NULL;
	
	//=========================================================================
	public function __construct() {
		$GLOBALS['DEBUGPRINTOPT'] = 1;
		$this->fsObj =  new cs_fileSystemClass(dirname(__FILE__) .'/../');
		$this->gfObj = new cs_globalFunctions;
		clearstatcache();
		
		//define some things for upgrades.
		define("UPGRADE_LOCKFILE",	dirname(__FILE__) ."/../UPGRADING_VERSION"); //relative to the directory beneath lib.
		define("UPGRADE_DIR",		dirname(__FILE__) ."/../upgrade");
	}//end __construct()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Where everything begins: checks if the version held in config.xml lines-up 
	 * with the one in the VERSION file; if it does, then it checks the version 
	 * listed in the database.
	 */
	public function check_versions($performUpgrade=TRUE) {
		//first, check that all files exist.
		$retval = NULL;
		
		//check to see if the lock files for upgrading exist.
		if($this->lock_file_exists()) {
			throw new exception(__METHOD__ .": upgrade in progress");
		}
		elseif(!file_exists(dirname(__FILE__) .'/'. CONFIG_FILENAME)) {
			throw new exception(__METHOD__ .": config.xml file missing");
		}
		elseif(!file_exists(dirname(__FILE__) .'/../VERSION')) {
			throw new exception(__METHOD__ .": VERSION file missing");
		}
		elseif(!file_exists(dirname(__FILE__) .'/../upgrade/upgrade.xml')) {
			throw new exception(__METHOD__ .": upgrade.xml file missing");
		}
		else {
			//okay, all files present: check the version in the VERSION file.
			$versionFileContents = $this->read_version_file();
			
			//now read data from the config.
			$versionFromConfig = $this->read_config_version();
			
			$versionsDiffer = TRUE;
			$retval = FALSE;
			if($versionFileContents == $versionFromConfig) {
				$versionConflict = $this->check_for_version_conflict();
				if($versionConflict === 0) {
					//all is good: no problems detected (all things match-up).
					$versionsDiffer=FALSE;
					$performUpgrade = FALSE;
				}
				else {
					//
					$versionsDiffer = TRUE;
				}
			}
			
			if($versionsDiffer == TRUE && $performUpgrade === TRUE) {
				//reset the return value, so it'll default to failure until we say otherwise.
				$retval = NULL;
				
				//Perform the upgrade!
				$this->perform_upgrade();
			}
		}
		
		return($retval);
	}//end check_versions()
	//=========================================================================
	
	
	
	//=========================================================================
	private function read_version_file() {
		$retval = NULL;
		
		//okay, all files present: check the version in the VERSION file.
		$versionFileContents = $this->fsObj->read('VERSION');
		
		//okay, rip it into bits. NOTE: this *depends* on "VERSION: " being on the third line.
		$lines = explode("\n", $versionFileContents);
		$versionLine = $lines[2];
		if(preg_match('/^VERSION: /', $versionLine)) {
			
			$retval = trim(preg_replace('/VERSION: /', '', $versionLine));
			$this->versionFileVersion = $retval;
		}
		else {
			throw new exception(__METHOD__ .": could not find VERSION data");
		}
		
		return($retval);
	}//end read_version_file()
	//=========================================================================
	
	
	
	//=========================================================================
	private function read_config_version() {
		
		$retval = NULL;
		if(!defined("CONFIG_FILENAME")) {
			throw new exception("upgrade::read_config_version(): constant CONFIG_FILENAME not present, can't locate config xml file");
		}
		else {
			$xmlString = $this->fsObj->read("lib/". CONFIG_FILENAME);
			
			//parse the file.
			$xmlParser = new xmlParser($xmlString);
			$config = $xmlParser->get_tree();
			$config = $config['CONFIG'];
			
			//now, let's see if there's a "version_string" index.
			if(isset($config['VERSION_STRING'])) {
				$retval = $config['VERSION_STRING']['value'];
			}
			else {
				$retval = "";
			}
		}
		
		$this->configVersion = $retval;
		return($retval);
	}//end read_config_version()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Read information from our config file, so we know what to expect.
	 */
	private function read_upgrade_config_file() {
		$xmlString = $this->fsObj->read("upgrade/upgrade.xml");
		
		//parse the file.
		$xmlParser = new xmlParser($xmlString);
		
		$config = $xmlParser->get_tree(TRUE);
		$this->config = $config['UPGRADE'];
	}//end read_upgrade_config_file()
	//=========================================================================
	
	
	
	//=========================================================================
	private function perform_upgrade() {
		//make sure there's not already a lockfile.
		if($this->lock_file_exists()) {
			//ew.  Can't upgrade.
			throw new exception(__METHOD__ .": found lockfile");
		}
		else {
			//attempt to create the lockfile.
			//TODO: to overcome filesystem permission issues, consider adding something to the config.xml to indicate it's locked.
			//TODO: stop lying about creating the lockfile.
			$this->fsObj->cd("/");
			$createFileRes = 1;
			
			//TODO: not only should the "create_file()" method be run, but also do a sanity check by calling lock_file_exists().
			if($createFileRes === 0) {
				//can't create the lockfile.  Die.
				throw new exception(__METHOD__ .": failed to create lockfile");
			}
			else {
				$this->gfObj->debug_print(__METHOD__ .": result of trying to create lockfile: (". $createFileRes .")");
				
				//check to see if our config file is writable.
				if(!$this->fsObj->is_writable("lib/config.xml")) {
					throw new exception(__METHOD__ .": config file isn't writable!");
				}
				
				//push data into our internal "config" array.
				$this->read_upgrade_config_file();
				$this->get_database_version();
				
				//check for version conflicts.
				$this->check_for_version_conflict();
				
				$maxIterations = 50;
				$i = 0;
				while($i < $maxIterations && $this->databaseVersion != $this->versionFileVersion) {
					$this->db->beginTrans();
					$this->do_single_upgrade();
					$this->get_database_version();
					$i++;
					$this->db->commitTrans();
				}
			}
		}
	}//end perform_upgrade()
	//=========================================================================
	
	
	
	//=========================================================================
	public function upgrade_in_progress() {
		$retval = $this->lock_file_exists();
		return($retval);
	}//end upgrade_in_progress()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Check for the existence of the lockfile: if it's there, that means there 
	 * is an upgrade process that's already been spawned.
	 */
	private function lock_file_exists() {
		if(file_exists(UPGRADE_LOCKFILE)) {
			$retval = TRUE;
		}
		else {
			$retval = FALSE;
		}
		
		return($retval);
	}//end lock_file_exists()
	//=========================================================================
	
	
	
	//=========================================================================
	public function parse_version_string($versionString) {
		if(is_null($versionString) || !strlen($versionString)) {
			throw new exception(__METHOD__ .": invalid version string ($versionString)");
		}
		$tmp = explode('.', $versionString);
		$retval = array(
			'version_string'	=> $versionString,
			'version_major'		=> $tmp[0],
			'version_minor'		=> $tmp[1]
		);
		if(count($tmp) == 3) {
			$retval['version_maintenance'] = $tmp[2];
		}
		else {
			$retval['version_maintenance'] = "0";
		}
		
		//check for a prefix or a suffix.
		if(preg_match('/-/', $versionString)) {
			//make sure there's only ONE dash.
			$tmp = explode('-', $versionString);
			if(count($tmp) == 2) {
				if(preg_match('/-/', $retval['version_major'])) {
					//example: BETA-3.3.0
					
					throw new exception(__METHOD__ .": versions that contain prefixes cannot be upgraded");
					
					#$tmp = explode('-', $retval['version_major']);
					#$retval['version_major'] = $tmp[1];
					#$retval['prefix'] = $tmp[0];
				}
				elseif(preg_match('/-/', $retval['version_maintenance'])) {
					//example: 1.0.0-ALPHA1
					$tmp = explode('-', $retval['version_maintenance']);
					$retval['version_maintenance'] = $tmp[0];
					$retval['version_suffix'] = $tmp[1];
				}
				else {
					throw new exception(__METHOD__ .": invalid location of prefix/suffix in (". $versionString .")");
				}
			}
			else {
				throw new exception(__METHOD__ .": too many dashes in version string (". $versionString .")");
			}
		}
		else {
			$retval['version_suffix'] = "";
		}
		
		return($retval);
	}//end parse_version_string()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Checks for issues with versions.
	 * 0		== No problems.
	 * (string)	== upgrade applicable (indicates "major"/"minor"/"maintenance").
	 * NULL		== encountered error
	 */
	private function check_for_version_conflict() {
		//set a default return...
		$retval = NULL;
		
		//call to ensure files have been processed.
		#$this->check_versions(FALSE);
		$this->read_config_version();
		$this->read_version_file();
		$configVersion = NULL;
		
		//parse the version strings.
		if(strlen($this->configVersion)) {
			$configVersion = $this->parse_version_string($this->configVersion);
		}
		$versionFile = $this->parse_version_string($this->versionFileVersion);
		
		
		$dbVersion = $this->get_database_version();
		$versionFileData = $this->parse_version_string($this->versionFileVersion);
		
		if($versionFileData['version_string'] == $dbVersion['version_string']) {
			//good to go: no upgrade needed.
			$retval = 0;
		}
		else {
			//NOTE: this seems very convoluted, but it works.
			if($versionFileData['version_major'] == $dbVersion['version_major']) {
				if($versionFileData['version_minor'] == $dbVersion['version_minor']) {
					if($versionFileData['version_maintenance'] == $dbVersion['version_maintenance']) {
						if($versionFileData['version_suffix'] == $dbVersion['version_suffix']) {
							throw new exception(__METHOD__ .": no version upgrade detected, but version strings don't match (versionFile=". $versionFileData['version_string'] .", dbVersion=". $dbVersion['version_string'] .")");
						}
						else {
							$this->gfObj->debug_print(__METHOD__ .": upgrading suffix");
							$retval = "suffix";
						}
					}
					elseif($versionFileData['version_maintenance'] > $dbVersion['version_maintenance']) {
						$this->gfObj->debug_print(__METHOD__ .": upgrading maintenance versions");
						$retval = "maintenance";
					}
					else {
						throw new exception(__METHOD__ .": downgrading from maintenance versions is unsupported");
					}
				}
				elseif($versionFileData['version_minor'] > $dbVersion['version_minor']) {
					$this->gfObj->debug_print(__METHOD__ .": upgrading minor versions");
					$retval = "minor";
				}
				else {
					throw new exception(__METHOD__ .": downgrading minor versions is unsupported");
				}
			}
			elseif($versionFileData['version_major'] > $dbVersion['version_major']) {
				$this->gfObj->debug_print(__METHOD__ .": upgrading major versions");
				$retval = "major";
			}
			else {
				throw new exception(__METHOD__ .": downgrading major versions is unsupported");
			}
		}
		
		return($retval);
	}//end check_for_version_conflict()
	//=========================================================================
	
	
	
	//=========================================================================
	private function get_database_version() {
		//create a database object & attempt to read the database version.
		
		if(!is_object($this->db) || get_class($this->db) != 'cs_phpDB') {
			$this->db = new cs_phpDB;
			$this->db->connect(get_config_db_params());
		}
		
		$sql = "SELECT " .
			"internal_data_get_value('version_string') AS version_string, " .
			"internal_data_get_value('version_major') AS version_major, " .
			"internal_data_get_value('version_minor') AS version_minor, " .
			"internal_data_get_value('version_maintenance') AS version_maintenance";
		$numrows = $this->db->exec($sql);
		$dberror = $this->db->errorMsg();
		
		if(strlen($dberror) || $numrows != 1) {
			//fail.
			throw new exception(__METHOD__ .": failed to retrieve version... numrows=(". $numrows ."), DBERROR::: ". $dberror);
		}
		else {
			$retval = $this->db->farray_fieldnames();
			if(preg_match('/-/', $retval['version_major'])) {
				$tmp = explode('-', $retval['version_major']);
				$retval['version_major'] = $tmp[1];
				$retval['prefix'] = $tmp[0];
			}
			else {
				$retval['prefix'] = "";
			}
			$this->databaseVersion = $retval['version_string'];
		}
		
		return($retval);
	}//end get_database_version()
	//=========================================================================
	
	
	
	//=========================================================================
	private function do_single_upgrade() {
		//Use the "matching_syntax" data in the upgrade.xml file to determine the filename.
		$versionIndex = "V". $this->databaseVersion;
		if(isset($this->config['MATCHING'][$versionIndex])) {
			$scriptIndex = $versionIndex;
			
			$upgradeData = $this->config['MATCHING'][$versionIndex];
			
			if(isset($upgradeData['TARGET_VERSION'])) {
				$this->newVersion = $upgradeData['TARGET_VERSION'];
				//now, figure out if it's a simple version upgrade, or if it requires
				//	a script to do the deed.
				if(count($upgradeData) > 1) {
					if(isset($upgradeData['SCRIPT_NAME']) && isset($upgradeData['CLASS_NAME']) && isset($upgradeData['CALL_METHOD'])) {
						//good to go; it's a scripted upgrade.
						$this->do_scripted_upgrade($upgradeData);
						$this->update_database_version($upgradeData['TARGET_VERSION']);
					}
					else {
						throw new exception(__METHOD__ .": not enough information to run scripted upgrade for ". $versionIndex);
					}
				}
				else {
					//version-only upgrade.
					$this->update_database_version($upgradeData['TARGET_VERSION']);
				}
				$this->update_config_file();
			}
			else {
				throw new exception(__METHOD__ .": target version not specified, unable to proceed with upgrade for ". $versionIndex);
			}
		}
		else {
			throw new exception(__METHOD__ .": could not retrieve syntax to determine upgrade filename from (". $this->databaseVersion .")");
		}
	}//end do_single_upgrade()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Updates information that's stored in the database, internal to cs-project, 
	 * so the version there is consistent with all the others.
	 */
	private function update_database_version($newVersionString) {
		$versionArr = $this->parse_version_string($newVersionString);
		
		$queryArr = array();
		foreach($versionArr as $index=>$value) {
			$queryArr[$index] = "SELECT internal_data_set_value('". $index ."', '". $value ."');";
		}
		
		$retval = NULL;
		foreach($queryArr as $name=>$sql) {
			if($this->run_sql($sql, 1)) {
				$retval++;
			}
		}
		
		//okay, now check that the version string matches the updated bits.
		if(!$this->check_database_version($this->newVersion)) {
			throw new exception(__METHOD__ .": database version information is invalid: (". $this->newVersion .")");
		}
		
		return($retval);
		
	}//end update_database_version()
	//=========================================================================
	
	
	
	//=========================================================================
	/**
	 * Checks consistency of version information in the database, and optionally 
	 * against a given version string.
	 */
	private function check_database_version($checkThisVersion=NULL) {
		//retrieve the internal version information.
		$sql = "select internal_data_get_value('version_string') as version_string, (" .
			"internal_data_get_value('version_major') || '.' || " .
			"internal_data_get_value('version_minor') || '.' || " .
			"internal_data_get_value('version_maintenance')) as check_version, " .
			"internal_data_get_value('version_suffix') AS version_suffix";
		
		$retval = NULL;
		if($this->run_sql($sql,1)) {
			$data = $this->db->farray_fieldnames();
			$versionString = $data['version_string'];
			$checkVersion = $data['check_version'];
			
			if(strlen($data['version_suffix'])) {
				//the version string already would have this, but the checked version wouldn't.
				$checkVersion .= "-". $data['version_suffix'];
			}
			
			if($versionString == $checkVersion) {
				$retval = TRUE; 
			}
			else {
				$retval = FALSE;
			}
		}
		else {
			$retval = FALSE;
		}
		
		if(!$retval) {
			$this->gfObj->debug_print($data);
			$this->gfObj->debug_print(__METHOD__ .": versionString=(". $versionString ."), checkVersion=(". $checkVersion .")");
		}
		
		return($retval);
		
	}//end check_database_version()
	//=========================================================================
	
	
	
	//=========================================================================
	private function do_scripted_upgrade(array $upgradeData) {
		$myConfigFile = $upgradeData['SCRIPT_NAME'];
		$this->gfObj->debug_print("myConfigFile=($myConfigFile)");
		
		//we've got the filename, see if it exists.
		$fileName = UPGRADE_DIR .'/'. $myConfigFile;
		if(file_exists($fileName)) {
			$createClassName = $upgradeData['CLASS_NAME'];
			$classUpgradeMethod = $upgradeData['CALL_METHOD'];
			$this->gfObj->debug_print(__METHOD__ .": classname: ". $createClassName);
			require_once($fileName);
			
			//now check to see that the class we need actually exists.
			if(class_exists($createClassName)) {
				$upgradeObj = new $createClassName($this->db);
				if(method_exists($upgradeObj, $classUpgradeMethod)) {
					$upgradeResult = $upgradeObj->$classUpgradeMethod();
				}
				else {
					throw new exception(__METHOD__ .": upgrade method doesn't exist (". $createClassName ."::". $classUpgradeMethod 
						."), unable to perform upgrade ");
				}
				$this->gfObj->debug_print($upgradeObj);
			}
			else {
				throw new exception(__METHOD__ .": unable to locate upgrade class name (". $createClassName .")");
			}
		}
		else {
			throw new exception(__METHOD__ .": upgrade filename (". $fileName .") does not exist");
		}
	}//end do_scripted_upgrade()
	//=========================================================================
	
	
	
	//=========================================================================
	private function run_sql($sql, $expectedNumrows=1) {
		if(!$this->db->is_connected()) {
			$this->db->connect(get_config_db_params());
		}
		$numrows = $this->db->exec($sql);
		$dberror = $this->db->errorMsg();
		
		if(strlen($dberror)) {
			$details = "DBERROR::: ". $dberror;
			throw new exception(__METHOD__ .": SQL FAILED::: ". $sql ."\n\nDETAILS: ". $details);
		}
		elseif(!is_null($expectedNumrows) && $numrows != $expectedNumrows) {
			throw new exception(__METHOD__ .": SQL FAILED::: ". $sql ."\n\nDETAILS: " .
				"rows affected didn't match expectation (". $numrows ." != ". $expectedNumrows .")");
		}
		elseif(is_null($expectedNumrows) && $numrows < 1) {
			throw new exception(__METHOD__ .": SQL FAILED::: ". $sql ."\n\nDETAILS: " .
				"invalid number of rows affected (". $numrows .")");
		}
		else {
			$retval = TRUE;
		}
		
		return($retval);
	}//end run_sql()
	//=========================================================================
	
	
	
	//=========================================================================
	private function update_config_file() {
		$gf = new cs_globalFunctions;
		$myConfigFile = 'lib/'. CONFIG_FILENAME;
		$fs = new cs_fileSystemClass(dirname(__FILE__) .'/../');
		$xmlParser = new XMLParser($fs->read($myConfigFile));
		$xmlCreator = new XMLCreator;
		$xmlCreator->load_xmlparser_data($xmlParser);
		
		//define items that should be added to the config.
		$newElements = array(
			'version_string'				=> $this->newVersion
		);
		
		$gf->debug_print(__METHOD__ .": running... ");
		
		foreach($newElements as $name=>$value) {
			$xmlCreator->add_tag($name, $value);
		}
		
		$xmlString = $xmlCreator->create_xml_string();
		$fs->write($xmlString, $myConfigFile);
	}//end update_config_file()
	//=========================================================================
	
	
}//end upgrade{}


?>