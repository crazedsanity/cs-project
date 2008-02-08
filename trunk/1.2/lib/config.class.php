<?php

class config {
	
	
	private $data;
	private $fs;
	private $gf;
	
	private $fileExists;
	
	private $fileName;
	
	//-------------------------------------------------------------------------
    public function __construct($fileName=NULL, $redirectOnFileMissing=TRUE) {
    	$this->gf = new cs_globalFunctions();
    	$this->fs = new cs_fileSystemClass(dirname(__FILE__));
    	
    	$this->fileName = dirname(__FILE__) .'/'. CONFIG_FILENAME;
    	if(!is_null($fileName) && strlen($fileName)) {
    		$this->fileName = $fileName;
    	}
    	
    	//Redirect them to the setup page. 
		if(!file_exists($this->fileName)) {
			$this->fileExists = FALSE;
			if($redirectOnFileMissing) {
				$this->gf->conditional_header("/setup?from=". urlencode($_SERVER['REQUEST_URI']));
				exit;
			}
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
			$xmlString = $this->fs->read(CONFIG_FILENAME);
			
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
			throw new exception(__METHOD__ .": config file doesn't exist");
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
		
		if($defineConstants) {
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
}//end config{}
?>