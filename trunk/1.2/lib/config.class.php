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
	public function check_site_status() {
		if(!defined("PROJECT__INITIALSETUP") || PROJECT__INITIALSETUP !== TRUE) {
			$this->do_setup_redirect();
			$config = $this->read_config_file(FALSE);
			
			if(($config['WORKINGONIT'] != "0" && strlen($config['WORKINGONIT'])) || strlen($config['WORKINGONIT']) > 1) {
				//TODO: consider making this look prettier...
				$details = "The website/database is under construction... try back in a bit.";
				if(preg_match('/upgrade/i', $config['WORKINGONIT'])) {
					$details = "<b>Upgrade in progress</b>: ". $config['WORKINGONIT'];
				}
				elseif(strlen($config['WORKINGONIT']) > 1) {
					$details .= "MORE INFORMATION::: ". $config['WORKINGONIT'];
				}
				throw new exception($details);
			}
			else {
				//don't panic: we're going to check for upgrades, but this doesn't
				//	necessarily mean anything will ACTUALLY be upgraded.
				$upgrade = new upgrade;
				if($upgrade->upgrade_in_progress()) {
					throw new exception("Upgrade in progress... reload the page after a few minutes and it should be complete.  :) ");
				}
				else {
					$upgrade->check_versions();
				}
				$this->read_config_file(TRUE);
			}
		}
	}//end check_site_status()
	//-------------------------------------------------------------------------
	
}//end config{}
?>