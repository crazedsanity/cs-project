<?php

require_once(dirname(__FILE__) .'/cs-content/cs_siteConfig.class.php');

class config extends cs_siteConfig {
	
	
	private $data;
	private $fs;
	private $gf;
	
	private $fileExists;
	private $siteStatus;
	private $setupRequired = FALSE;
	private $fileName;
	protected $config;
	
	//-------------------------------------------------------------------------
    public function __construct($fileName=NULL) {
    	$this->gf = new cs_globalFunctions();
    	$this->fs = new cs_fileSystem(dirname(__FILE__) .'/..');
    	
    	$this->fileName = dirname(__FILE__) .'/'. CONFIG_FILENAME;
    	if(!is_null($fileName) && strlen($fileName)) {
    		$this->fileName = $fileName;
    	}
    	else {
    		$this->fileName = CONFIG_FILE_LOCATION;
    	}
    	
		if(!file_exists($this->fileName)) {
			$this->fileExists = FALSE;
		}
		else {
			$this->fileExists = TRUE;
		}
		
		if(!$this->fs->is_writable(CONFIG_DIRECTORY)) {
			throw new exception(__METHOD__ .": the config directory (". CONFIG_DIRECTORY .") isn't writable!");
		}
		
		if($this->fileExists) {
			//check to see if it all config items are directly under the root, or if they're already in sections.
			$xml = new cs_phpxmlParser($this->fs->read($this->fileName));
			$checkThis = $xml->get_attribute('/CONFIG', 'USECSSITECONFIG');
			
			if(!strlen($checkThis)) {
				$this->gf->debug_print(__METHOD__ .": converting to use sections...");
				$this->convert_to_sections();
			}
		}
		
		parent::__construct($this->fs->realcwd .'/'. $this->fileName);
		
		$this->config = $this->get_config_contents(TRUE);
    }//end __construct()
	//-------------------------------------------------------------------------
    
    
    
	//-------------------------------------------------------------------------
	/**
	 * Get the contents of the config file.
	 */
	public function get_config_contents($simple=TRUE, $setConstants=FALSE, $setEverything=FALSE) {
		if($this->fileExists) {
			$xmlString = $this->fs->read($this->fileName);
			
			//parse the file.
			$xmlParser = new cs_phpxmlParser($xmlString);
			
			if($simple) {
				$config = $xmlParser->get_tree(TRUE);
				$config = $config['CONFIG']['MAIN'];
			}
			else {
				$config = $xmlParser->get_path('/CONFIG/MAIN');
				unset($config['type'], $config['attributes']);
			}
			
			if($setConstants) {
				$myConfig = $config;
				if(!$simple) {
					$myConfig = $xmlParser->get_tree(TRUE);
					$myConfig = $myConfig['CONFIG']['MAIN'];
				}
				$conditionallySet = array('VERSION_STRING', 'WORKINGONIT');
				foreach($myConfig as $index=>$value) {
					if(in_array($index, $conditionallySet)) {
						//only set this part if we're told to.
						if($setEverything) {
							define($index, $value);
						}
					}
				}
			}
		}
		else {
			$config = NULL;
		}
		
		return($config);
		
	}//end get_config_contents()
	//-------------------------------------------------------------------------
	
	
	
	//-------------------------------------------------------------------------
	/**
	 * Read the XML config file & return it's simplified contents (just a 
	 * wrapper for get_config_contents(); kept for backwards-compatibility)
	 */
    public function read_config_file($defineConstants=TRUE, $setEverything=TRUE) {
		return($this->get_config_contents(TRUE));
    }//end read_config_file()
	//-------------------------------------------------------------------------
	
	
	
	//-------------------------------------------------------------------------
	public function do_setup_redirect() {
		if($this->check_site_status() && $this->setupRequired) {
			if(!($_SERVER['SCRIPT_NAME'] == '/setup')) {
				$this->gf->debug_print(__METHOD__ .": script_name check=(". ($_SERVER['script_name'] != '/setup') .")," .
					" check_site_status=(". $this->check_site_status() ."), setupRequired=(". $this->setupRequired .")", 1);
				$goHere = '/setup';
				if(strlen($_SERVER['REQUEST_URI']) > 1 && !isset($_SESSION['setup__viewed'])) {
					$goHere .= '?from='. urlencode($_SERVER['REQUEST_URI']);
				}
				$_SESSION['setup_redirect'] = time();
				$this->create_setup_config();
				$this->gf->conditional_header($goHere);
			}
		}
	}//end do_setup_redirect()
	//-------------------------------------------------------------------------
	
	
	
	//-------------------------------------------------------------------------
	/**
	 * Determines if the site is a fresh install, undergoing setup, being 
	 * upgraded (by someone else), or good to go.
	 * 
	 * @param (void)	No parameters accepted
	 * 
	 * TODO: make this actually WORK.
	 * TODO: match actual returns with those specified below
	 * TODO: implement a "reload timer" so the page with do a meta-refresh after X minutes/seconds (make sure any POST vars are retained!!!)
	 * TODO: instead of a ton of returns, just set true/false for return, and have internal message explaining what's up.
	 * 
	 * @return TRUE		OK: display normal page (no upgrade/setup needed/running)
	 * @return FALSE	FAIL: somebody else is running setup, or the site is otherwise locked.
	 */
	public function check_site_status() {
		
		//check for the OLD config file.
		/**
		 * WHY THE CHECK IS HERE:::
		 * 
		 * The setup system expects that the config file exists in the location 
		 * specified by CONFIG_FILE_LOCATION; if it's not there, the site_config 
		 * will assume setup must be run... so we have to circumvent that 
		 * behaviour right here.
		 */
		if(file_exists(OLD_CONFIG_FILE_LOCATION)) {
			//copy old file to new location...
			$fs = new cs_fileSystemClass(dirname(__FILE__) .'/../');
			$moveRes = $fs->move_file(OLD_CONFIG_FILE_LOCATION, CONFIG_FILE_LOCATION);
			if(!$moveRes) {
				throw new exception(__METHOD__ .": failed to move existing config into new location (". $moveRes .")");
			}
			
			//set some parameters.
			$this->siteStatus = 'Old config file moved... ';
			$this->setupRequired = FALSE;
			$retval = TRUE;
			
			//set a parameter so get_config_contents() works, then read config parameters (like in __construct()).
			$this->fileExists=TRUE;
			$this->config = $this->get_config_contents(TRUE);
		}
		elseif($this->setup_config_exists()) {
			if($this->setup_config_exists(TRUE)) {
				//the currently logged-in user is actually running the setup, no worries.
				$this->siteStatus = 'You are running setup... please continue.';
				$this->setupRequired = TRUE;
				$retval = TRUE;
			}
			else {
				//tell 'em somebody is working on setup and to WAIT.
				$this->siteStatus = 'Setup is in progress by another user.  Please wait.';
				$retval = FALSE;
			}
		}
		elseif($this->fileExists) {
			//got an existing config file.
			
			if($this->is_workingonit_set()) {
				//site access is locked; get the message and show 'em.
				$this->siteStatus = $this->is_workingonit_set(TRUE);
				$retval = $this->siteStatus;
			}
			else {
				//config exists, site not locked... GOOD TO GO!
				$this->siteStatus = 'Normal (site is setup).';
				$retval = TRUE;
			}
		}
		else {
			//good to go!
			$this->siteStatus = 'No existing config or setup file: you may initiate the setup process now.';
			$this->setupRequired = TRUE;
			$retval = TRUE;
		}
		
		return($retval);
		
	}//end check_site_status()
	//-------------------------------------------------------------------------
	
	
	
	//-------------------------------------------------------------------------
	private function is_workingonit_set($giveValue=FALSE) {
		if((is_numeric($this->config['WORKINGONIT']) && $this->config['WORKINGONIT'] == 0) || ($this->config['WORKINGONIT'] === FALSE)) {
			$retval = FALSE;
		}
		else {
			$retval = TRUE;
		}
		
		if($giveValue === TRUE) {
			$retval = $this->config['WORKINGONIT'];
		}
		
		return($retval);
	}//end is_workingonit_set()
	//-------------------------------------------------------------------------
	
	
	
	//-------------------------------------------------------------------------
	public function setup_config_exists($checkOwnership=FALSE) {
		$retval = FALSE;
		
		$dirContents = $this->fs->ls(CONFIG_DIRECTORY);
		if($dirContents[SETUP_FILENAME]) {
			$retval = TRUE;
			
			if($checkOwnership === TRUE) {
				//read the object.
				$xmlParser = new xmlParser($this->fs->read(SETUP_FILE_LOCATION));
				$configData = $xmlParser->get_tree(TRUE);
				$configData = $configData['CONFIG'];
				
				//now that we've got the data, determine if the current user is the owner.
				if($configData['OWNER_SESSION'] === session_id()) {
					$retval = TRUE;
				}
				else {
					$retval = FALSE;
				}
			}
		}
		
		return($retval);
	}//end setup_config_exists()
	//-------------------------------------------------------------------------
	
	
	
	//-------------------------------------------------------------------------
	public function is_setup_required() {
		return($this->setupRequired);
	}//end is_setup_required()
	//-------------------------------------------------------------------------
	
	
	
	//-------------------------------------------------------------------------
	private function create_setup_config() {
		$xmlCreator = new xmlCreator('config');
		$attributes = array(
			'creation'	=> time()
		);
		$xmlCreator->add_tag('/config/owner_session', session_id(), $attributes);
		
		$this->fs->create_file(SETUP_FILE_LOCATION);
		$this->fs->write($xmlCreator->create_xml_string());
	}//end create_setup_config()
	//-------------------------------------------------------------------------
	
	
	
	//-------------------------------------------------------------------------
	public function get_site_status() {
		return($this->siteStatus);
	}//end get_site_status()
	//-------------------------------------------------------------------------
	
	
	
	//-------------------------------------------------------------------------
	public function remove_setup_config() {
		return($this->fs->rm(SETUP_FILE_LOCATION));
	}//end remove_setup_config()
	//-------------------------------------------------------------------------
	
	
	
	//-------------------------------------------------------------------------
	/**
	 * Convert sections to be in a format that cs_siteConfig{} expects them to 
	 * be in.
	 */
	private function convert_to_sections() {
		
		//make a copy of the old data.
		$backupFilename = $this->fileName .".backup_convertToSections__". date("YmdHis");
		$this->fs->create_file($backupFilename);
		$this->fs->write($this->fs->read($this->fileName));
		
		$xml = new cs_phpxmlParser($this->fs->read($this->fileName));
		$rootElement = $xml->get_root_element();
		
		$creator=new cs_phpxmlCreator($rootElement);
		
		$dataArray = $xml->get_tree();
		$dataArray = $dataArray[$rootElement];
		
		
		$rootAttribs = $dataArray['attributes'];
		
		//remove unnecessary indexes.
		unset($dataArray['type'], $dataArray['attributes']);
		
		if(!is_array($rootAttribs)) {
			$rootAttribs = array();
		}
		$rootAttribs['USECSSITECONFIG'] = 1;
		$creator->add_attribute('/'. $rootElement, $rootAttribs);
		
		//set special value for SITE_ROOT
		$dataArray['SITE_ROOT']['value'] = '{_DIRNAMEOFFILE_}/..';
		$dataArray['SITE_ROOT']['attributes']['cleanpath'] = 1;
		
		
		{//Set some other special values...
			$dataArray['DOCUMENT_ROOT']['value'] = '{MAIN/SITE_ROOT}';
			$dataArray['LIBDIR']['value'] = '{MAIN/SITE_ROOT}/lib';
			$dataArray['TMPLDIR']['value'] = '{MAIN/SITE_ROOT}/templates';
			$dataArray['SEQ_HELPDESK']['value'] = 'special__helpdesk_public_id_seq';
			$dataArray['SEQ_PROJECT']['value'] = 'special__project_public_id_seq';
			$dataArray['SEQ_MAIN']['value'] = 'record_table_record_id_seq';
			$dataArray['TABLE_TODOCOMMENT']['value'] = 'task_comment_table';
			$dataArray['FORMAT_WORDWRAP']['value'] = '90';
		}
		
		//define what items should also be GLOBAL.
		$defineAsGlobal = array('SITE_ROOT', 'LIBDIR', 'TMPLDIR');
		
		//skip some things that should NOT be automatically set as constants.
		$skipSetConstant = array('VERSION_STRING', 'WORKINGONIT');
		
		//create the main section that our old settings will go under.
		$mainPath = '/'. $rootElement .'/MAIN';
		$creator->add_tag($mainPath);
		$creator->set_tag_as_multiple($mainPath);
		$creator->add_attribute($mainPath, array('FIX'=>'sanitizeDirs'));
		
		
		//now add all the old settings.
		foreach($dataArray as $index=>$data) {
			if(!is_array($data['attributes'])) {
				$data['attributes'] = array();
			}
			if(!in_array($index, $skipSetConstant)) {
				$data['attributes']['setconstant']=1;
			}
			if(in_array($index, $defineAsGlobal)) {
				$data['attributes']['setglobal']=1;
			}
			$creator->add_tag($mainPath .'/'. $index, $data['value'], $data['attributes']);
		}
		
		
		//now write changes to the file.
		$this->fs->truncate_file($this->fileName);
		$this->fs->write($creator->create_xml_string(), $this->fileName);
	}//end convert_to_sections()
	//-------------------------------------------------------------------------
}//end config{}
?>