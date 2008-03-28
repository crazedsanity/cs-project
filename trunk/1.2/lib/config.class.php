<?php

class config {
	
	
	private $data;
	private $fs;
	private $gf;
	
	private $fileExists;
	
	private $fileName;
	
	//-------------------------------------------------------------------------
    public function __construct($fileName=NULL) {
    	$this->gf = new cs_globalFunctions();
    	$this->fs = new cs_fileSystemClass(dirname(__FILE__) .'/..');
    	
    	$this->fileName = dirname(__FILE__) .'/'. CONFIG_FILENAME;
    	if(!is_null($fileName) && strlen($fileName)) {
    		$this->fileName = $fileName;
    	}
    	
		if(!file_exists($this->fileName)) {
			$this->fileExists = FALSE;
		}
		else {
			$this->fileExists = TRUE;
		}
    }//end __construct()
	//-------------------------------------------------------------------------
    
    
    
	//-------------------------------------------------------------------------
	/**
	 * Get the contents of the config file.
	 */
	public function get_config_contents($simple=TRUE) {
		if($this->fileExists) {
			$xmlString = $this->fs->read($this->fileName);
			
			//parse the file.
			$xmlParser = new xmlParser($xmlString);
			
			if($simple) {
				$config = $xmlParser->get_tree(TRUE);
				$config = $config['CONFIG'];
			}
			else {
				$config = $xmlParser->get_path('/CONFIG');
				unset($config['type'], $config['attributes']);
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
	 * Read the XML config file & return it's simplified contents
	 */
    public function read_config_file($defineConstants=TRUE, $setEverything=TRUE) {
		$config = $this->get_config_contents(TRUE);
		
		if(!is_null($config) && $defineConstants) {
			$conditionallySet = array('VERSION_STRING', 'WORKINGONIT');
			foreach($config as $index=>$value) {
				if(in_array($index, $conditionallySet)) {
					//only set this part if we're told to.
					if($setEverything) {
						define($index, $value);
					}
				}
				else {
					define($index, $value);
				}
			}
		}
		
		return($config);
    }//end read_config_file()
	//-------------------------------------------------------------------------
	
	
	
	//-------------------------------------------------------------------------
	public function do_setup_redirect() {
		if(!preg_match('/^setup/', $_SERVER['REQUEST_URI']) && !$_SESSION[SESSION_SETUP_KEY]) {
			
			//set something in the session so we know.
			if(!isset($_SESSION[SESSION_SETUP_KEY])) {
				$_SESSION[SESSION_SETUP_KEY]++;
			}
			else {
				throw new exception(__METHOD__ .": setup key (". SESSION_SETUP_KEY .") found in session already");
			}
			
			
			$goHere = '/setup';
			
			if(strlen($_SERVER['REQUEST_URI']) > 1) {
				$goHere .= '?from='. urlencode($_SERVER['REQUEST_URI']);
			}
			$this->gf->conditional_header($goHere);
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
	 * @return 1		OK: setup required (perform redirect)
	 * @return 2		OK: setup initiated by current user (display setup page)
	 * @return 3		ERROR: setup initiated by OTHER user (show "setup running" error)
	 * @return 4		OK: upgrade required (starts upgrade process, then displays normal page)
	 * @return 5		ERROR: upgrade started by other user (show temporary "upgrade in progress" message, set reload timer)
	 */
	public function check_site_status() {
		
		//=============================================================
		//BEGIN pseudo code:
		//--------------------------------------------------
		
		
		if($this->configFileExists()) {
			//got an existing config file.
			
			if($this->isWorkingOnItSet()) {
				//site access is locked (probably an upgrade); get the message and show 'em.
				$retval = $this->isWorkingOnItSet(TRUE);
				$this->showFatalError($retval);
			}
			elseif($this->setupConfigExists()) {
				if($this->setupConfigExists() === 'current_user') {
					//the currently logged-in user is actually running the setup, no worries.
					$retval = 'undergoing setup by current user';
				}
				else {
					//tell 'em somebody is working on setup and to WAIT.
					$retval = $this->showSetupMessage();
				}
			}
			else {
				//config exists, site not locked... GOOD TO GO!
				$retval = $this->setOkay();
			}
		}
		else {
			//check for the OLD config file.
			if($this->oldConfigFileExists()) {
				//copy old file to new location...
				$this->copyOldConfigFile();
				
				//now check if the site is locked.
				if($this->isWorkingOnItSet()) {
					//upgrade running.  Show 'em the message.
					$retval = $this->isWorkingOnItSet(TRUE);
					$this->showFatalError($retval);
				}
				elseif($this->setupConfigExists()) {
					//SETUP IN PROGRESS...
					
					if($this->setupConfigExists() === 'current_user') {
						//the currently logged-in user is actually running the setup, no worries.
						$retval = 'undergoing setup by current user';
					}
					else {
						//tell 'em somebody is working on setup and to WAIT.
						$retval = $this->showSetupMessage();
					}
				}
				else {
					//good to go!
					$retval=$this->setOkay();
				}
			}
			else {
				//okay, no config file (new or old), no setup running, so current user has option
				//	to run setup (viewing the page not enough; must click something to create the
				//	setup config file which locks setup to their session)
				$this->do_setup_redirect();
			}
		}
		
		return($retval);
		//--------------------------------------------------
		//END pseudo code;
		//=============================================================
		
	}//end check_site_status()
	//-------------------------------------------------------------------------
	
}//end config{}
?>