<?php
/*
 * Created on Aug 23, 2007
 * 
 */


if($_POST) {
	$obj = new __finalStep($page, $stepNames);
	$writeConfigResult = $obj->write_config($page);
	
	$page->set_message_wrapper(array(
		'title'		=> "Reloaded... ",
		'message'	=> "Result of writing the config file:<BR>\n". $writeConfigResult,
		'type'		=> "status"
	));
	$page->conditional_header("/setup/5", TRUE);
}
else {
	if(get_setup_data(5, 'result')) {
		$page->clear_content();
		#$page->set_block_row('content', 'submitButton');
		
		$test = &new TestSuite("All Tests");
		$test->addTestFile(dirname(__FILE__) .'/../../lib/_unitTests_/cs-content_tests.php');
		#$test->addTestFile(dirname(__FILE__) .'/../../lib/_unitTests_/cs-arrayToPath_tests.php');
		#$test->addTestFile(dirname(__FILE__) .'/../../lib/_unitTests_/cs-phpxml_tests.php');
		$display = new HtmlReporter();
		$test->run($display);
		
		$page->gfObj->debug_print("Passes: (". $display->getPassCount() .")");
	}
	else {
		
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
		$this->fsObj = new cs_fileSystemClass(dirname(__FILE__) ."/../../lib");
	}//end __construct()
	//=========================================================================
	
	
	
	//=========================================================================
	function write_config() {
		if($this->fsObj->is_writable(NULL)) {
			$lsData = $this->fsObj->ls();
			if(!is_array($lsData['config.xml'])) {
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
				$xmlCreator = new xmlCreator('config');
				foreach($myData as $index=>$value) {
					$xmlCreator->add_tag($index, $value);
				}
				$extraAttributes = array(
					'generated'		=> date('Y-m-d H:m:s'),
					'version'		=> $myData['version_string']
				);
				$xmlCreator->add_attribute('/config', $extraAttributes);
				
				//now, create an XML string...
				$xmlString = $xmlCreator->create_xml_string();
				
				$this->fsObj->create_file('config.xml', TRUE);
				$writeRes = $this->fsObj->write($xmlString, 'config.xml');
				
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
				throw new exception(__METHOD__ .": config.xml already exists!");
			}
		}
		else {
			throw new exception(__METHOD__ .": the lib directory is not writable!");
		}
		
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
