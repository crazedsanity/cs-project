<?php
/*
 * Created on Aug 23, 2007
 * 
 */


if($_POST) {
	$obj = new __finalStep($page, $stepNames);
	$writeConfigResult = $obj->write_config($page);
	
	$page->set_message_wrapper(array(
		'title'		=> "Setup Complete",
		'message'	=> $writeConfigResult,
		'type'		=> "status",
		'linkText'	=> "Proceed To Login",
		'linkURL'	=> '/setup/5?removeData=1'
	));
	$page->conditional_header("/setup/5", TRUE);
}
elseif($_GET['removeData'] == 1) {
	unset($_SESSION['setup']);
	$page->conditional_header('/login.php');
}
else {
	if(get_setup_data(5, 'result')) {
		$page->clear_content();
		
		$test = &new TestSuite("All Tests");
		$test->addTestFile(dirname(__FILE__) .'/../../lib/_unitTests_/cs-content_tests.php');
		$test->addTestFile(dirname(__FILE__) .'/../../lib/cs-arrayToPath/tests/testOfA2P.php');
		$test->addTestFile(dirname(__FILE__) .'/../../lib/cs-content/tests/testOfCSContent.php');
		$test->addTestFile(dirname(__FILE__) .'/../../lib/cs-phpxml/tests/testOfCSPHPXML.php');
		$display = new HtmlReporter();
		$test->run($display);
		
		$page->gfObj->debug_print("Passes: (". $display->getPassCount() .")");
		
		//log our result into the database.
		$db = new cs_phpDB;
		$db->connect(get_config_db_params());
		
		$log = new logsClass($db, 'SETUP');
		$log->log_by_class('UNIT TEST DATA::: passes='. $display->getPassCount() .', fails='. $display->getFailCount() .', exceptions='. $display->getExceptionCount(), 'Information');
		
		if(!is_array($_SESSION['message'])) {
			$page->set_message_wrapper(array(
				'title'		=> "Setup Complete",
				'message'	=> "Setup has been completed successfully.  If you would like to remove setup data and proceed to login, click the link below.",
				'type'		=> 'status',
				'linkText'	=> "Proceed to Login",
				'linkURL'	=> "/setup/5?removeData=1"
			));
		}
	}
}


class __finalStep {
	
	
	private $page;
	private $gfObj;
	
	
	//=========================================================================
	public function __construct(cs_genericPage $page, array $stepData) {
		$this->page = $page;
		$this->stepData = $stepData;
		unset($this->stepData[5]);
		
		$this->gfObj = new cs_globalFunctions;
		$this->fsObj = new cs_fileSystem(dirname(__FILE__) ."/../../". CONFIG_DIRECTORY);
	}//end __construct()
	//=========================================================================
	
	
	
	//=========================================================================
	function write_config() {
		if($this->fsObj->is_writable(NULL)) {
			$lsData = $this->fsObj->ls();
			if(!is_array($lsData[CONFIG_FILENAME])) {
				$myData = array();
				foreach($this->stepData as $stepNum=>$garbage) {
					$tempStepData = get_setup_data($stepNum, 'data');
					if(is_array($tempStepData)) {
						$myData = array_merge($tempStepData, $myData);
					}
					else {
						throw new exception(__METHOD__ .": step #". $stepNum ." has no valid data... ". $this->gfObj->debug_print($tempStepData,0));
					}
				}
				
				//now that we've built the array successfully, now let's turn it into XML.
				$xmlCreator = new cs_phpxmlCreator('config');
				$tagPath = "/config/main";
				$xmlCreator->add_tag($tagPath);
				$xmlCreator->add_attribute($tagPath, array('fix'=>"sanitizeDirs"));
				$xmlCreator->set_tag_as_multiple($tagPath);
				
				//Special values (including vars that cs_siteConfig{} handles)
				$specialValues = array(
					'site_root'			=> '{_DIRNAMEOFFILE_}/..',
					'document_root'		=> '{MAIN/SITE_ROOT}',
					'libdir'			=> '{MAIN/SITE_ROOT}/lib',
					'tmpldir'			=> '{MAIN/SITE_ROOT}/templates',
					'seq_helpdesk'		=> 'special__helpdesk_public_id_seq',
					'seq_project'		=> 'special__project_public_id_seq',
					'seq_main'			=> 'record_table_record_id_seq',
					'table_todocomment'	=> 'task_comment_table',
					'format_wordwrap'	=> '90'
				);
				$defineAsGlobal=array('site_root', 'libdir', 'tmpldir');
				foreach($specialValues as $index=>$value) {
					$xmlCreator->add_tag($tagPath .'/'. $index, $value);
					$attributes = array('setconstant'=>1);
					if(array_search($index, $defineAsGlobal)) {
						$attributes['setglobal']=1;
					}
					$xmlCreator->add_attribute($tagPath .'/'. $index, $attributes);
				}
				
				$skipSetConstant = array('version_string', 'workingonit');
				foreach($myData as $index=>$value) {
					$xmlCreator->add_tag($tagPath ."/". $index, $value);
					$attributes=array();
					if(!strlen(array_search($index, $skipSetConstant))) {
						$attributes['setconstant']=1;
					}
					$xmlCreator->add_attribute($tagPath .'/'. $index, $attributes);
				}
				$extraAttributes = array(
					'generated'			=> date('Y-m-d H:m:s'),
					'version'			=> $myData['version_string'],
					'usecssiteconfig'	=> 1
				);
				$xmlCreator->add_attribute('/config', $extraAttributes);
				
				//now, create an XML string...
				$xmlString = $xmlCreator->create_xml_string();
				
				$this->fsObj->create_file(CONFIG_FILENAME, TRUE);
				$writeRes = $this->fsObj->write($xmlString, CONFIG_FILENAME);
				
				if($writeRes > 0) {
					$retval = "Successfully created the XML config file";
					store_setup_data(5, 1, 'result');
					store_setup_data(5, $retval, 'text');
				}
				else {
					throw new exception(__METHOD__ .": failed to write any data to the config file");
				}
			}
			else {
				throw new exception(__METHOD__ .": ". CONFIG_FILE_LOCATION ." already exists!");
			}
		}
		else {
			throw new exception(__METHOD__ .": the config directory is not writable!");
		}
		
		$configObj = new config(CONFIG_FILE_LOCATION);
		$configObj->remove_setup_config();
		
		return($retval);
	}//end write_config()
	//=========================================================================
}


//#######################################################################################
/**
 * Built to avoid always printing-out the results (so we can retrieve result data separately.
 */
class MyDisplay extends SimpleReporter {
    
    function paintHeader($test_name) {
    }
    
    function paintFooter($test_name) {
    }
    
    function paintStart($test_name, $size) {
        parent::paintStart($test_name, $size);
    }
    
    function paintEnd($test_name, $size) {
        parent::paintEnd($test_name, $size);
    }
    
    function paintPass($message) {
        parent::paintPass($message);
    }
    
    function paintFail($message) {
        parent::paintFail($message);
    }
}
//#######################################################################################


class unitTest extends UnitTestCase {
	
	function testOfTester () {
		$this->assertTrue(FALSE);
	}
}

?>
