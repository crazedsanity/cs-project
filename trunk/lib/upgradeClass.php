<?php
/*
 * Created on Jul 2, 2007
 * 
 */


class upgrade {
	
	private $fsObj;
	private $gfObj;
	private $config = NULL;
	
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
		
		//TODO: upgrade the XMLParser{} so either it has something that handles turning it into a useable array, or update get_tree() to do it.
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
			#$oldVersionFileData = $this->fsObj->read("VERSION");
			#$createFileRes = $this->fsObj->create_file("UPGRADING_VERSION");
			$createFileRes = 1;
			
			//TODO: not only should the "create_file()" method be run, but also do a sanity check by calling lock_file_exists().
			if($createFileRes === 0) {
				//can't create the lockfile.  Die.
				throw new exception(__METHOD__ .": failed to create lockfile");
			}
			else {
				$this->gfObj->debug_print(__METHOD__ .": result of trying to create lockfile: (". $createFileRes .")");
				
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
		
		//check for BETA (if "major" is "BETA-3", then the major version is 3, & the prefix is "BETA").
		if(preg_match('/-/', $retval['version_major'])) {
			$tmp = explode('-', $retval['version_major']);
			$retval['version_major'] = $tmp[1];
			$retval['prefix'] = $tmp[0];
		}
		else {
			$retval['prefix'] = "";
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
			if($versionFileData['prefix'] == $dbVersion['prefix']) {
				if($versionFileData['version_major'] == $dbVersion['version_major']) {
					if($versionFileData['version_minor'] == $dbVersion['version_minor']) {
						if($versionFileData['version_maintenance'] == $dbVersion['version_maintenance']) {
							throw new exception(__METHOD__ .": no version upgrade detected, but version strings don't match (versionFile=". $versionFileData['version_string'] .", dbVersion=". $dbVersion['version_string'] .")");
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
			else {
				throw new exception(__METHOD__ .": transitioning from (or to) non-production versions is not supported");
			}
		}
		
		return($retval);
	}//end check_for_version_conflict()
	//=========================================================================
	
	
	
	//=========================================================================
	private function get_database_version() {
		//create a database object & attempt to read the database version.
		require_once(dirname(__FILE__) .'/pg_abstraction_layer.inc');
		
		if(!is_object($this->db) || get_class($this->db) != 'phpDB') {
			$this->db = new phpDB;
			$this->db->connect();
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
			$retval = 0;
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
		if(isset($this->config['MATCHING'][$this->databaseVersion])) {
			$scriptIndex = $this->databaseVersion;
			$myConfigFile = $this->config['MATCHING'][$scriptIndex]['SCRIPT_NAME'];
			$this->gfObj->debug_print("myConfigFile=($myConfigFile)");
			
			//we've got the filename, see if it exists.
			$fileName = UPGRADE_DIR .'/'. $myConfigFile;
			if(file_exists($fileName)) {
				$createClassName = $this->config['MATCHING'][strtoupper($scriptIndex)]['CLASS_NAME'];
				$classUpgradeMethod = $this->config['MATCHING'][strtoupper($scriptIndex)]['CALL_METHOD'];
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
		}
		else {
			throw new exception(__METHOD__ .": could not retrieve syntax to determine upgrade filename from (". $this->databaseVersion .")");
		}
	}//end do_single_upgrade()
	//=========================================================================
	
	
}//end upgrade{}


?>