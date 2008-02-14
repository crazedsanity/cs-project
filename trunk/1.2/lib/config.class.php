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
    	
    	//Redirect them to the setup page. 
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
	/**
	 * Create a page (portion of a page, actually) to set/update config settings.
	 */
	public function build_update_interface(cs_genericPage &$page) {
		
		//read the sample config.
		$config = new config(dirname(__FILE__) .'/config.xml', FALSE);
		$myData = $config->get_config_contents();
		
		//parse the sample config for it's attributes, so we can display the page properly.
		$sampleConfig = new config(dirname(__FILE__) .'/../docs/samples/sample_config.xml', FALSE);
		$systemData = $sampleConfig->get_config_contents(FALSE);
		
		$mainAttributes = $myData['attributes'];
		
		unset($myData['type'], $myData['attributes']);
		
		$parsedRows = "";
		$defaultRowName = 'setting_text';
		foreach($systemData as $indexName=>$defaultValue) {
			if(is_array($myData) && isset($myData[$indexName])) {
				$value = $myData[$indexName];
			}
			else {
				$value = $systemData[$indexName]['value'];
			}
			$attributes = $systemData[$indexName]['attributes'];
			$indexName = strtolower($indexName);
			
			//pull the appropriate template row.
			$rowName = $defaultRowName;
			if(strlen($attributes['TYPE'])) {
				$rowName = 'setting_'. $attributes['TYPE'];
				
				$optionList = NULL;
				if($attributes['TYPE'] == 'select' && isset($attributes['OPTIONS'])) {
					#debug_print(explode('|', $attributes['OPTIONS']));
					$tmpOptionList = explode('|', $attributes['OPTIONS']);
					$optionList = array();
					foreach($tmpOptionList as $optionInfo) {
						$x = explode('=', $optionInfo);
						$optionList[$x[0]] = $x[1];
					}
					$optionList = $page->gfObj->array_as_option_list($optionList, $attributes['DEFAULT']);
				}
			}
			
			if(!isset($page->templateRows[$rowName])) {
				$page->set_block_row('content', $rowName);
				if(!isset($page->templateRows[$rowName])) {
					throw new exception(__METHOD__ .": failed to retrieve block row named (". $rowName .")");
				}
			}
			
			//now parse stuff into the row...
			$repArr = array(
				'disabled'		=> $attributes['disabled'],
				'index'			=> $indexName,
				'title'			=> $attributes['TITLE'],
				'description'	=> $attributes['DESCRIPTION'],
				'value'			=> $value
			);
			if(!is_null($optionList)) {
				$repArr['setting_select__normal'] = $optionList;
			}
			$parsedRows .= $page->mini_parser($page->templateRows[$rowName], $repArr);
		}
		#debug_print($parsedRows);
		$page->add_template_var($defaultRowName, $parsedRows);
	}//end build_update_interface()
	//-------------------------------------------------------------------------
}//end config{}
?>