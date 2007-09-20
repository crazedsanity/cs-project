<?php
/*
 * Created on Sep 20, 2007
 * 
 */

class Test_cscontent extends UnitTestCase {
	
	function setUp() {
		$this->gfObj = new cs_globalFunctions;
		$this->fsObj = new cs_fileSystemClass;
		$this->contentObj = new contentSystem;
	}//end setUp()
	
	function testVersionAndProject() {
		$expectedVersion = '0.8.0';
		$expectedProject = 'cs-content';
		
		$this->assertEqual($this->gfObj->get_version(), $expectedVersion);
		$this->assertEqual($this->gfObj->get_project(), $expectedProject);
		
		$this->assertEqual($this->fsObj->get_version(), $expectedVersion);
		$this->assertEqual($this->gfObj->get_project(), $expectedProject);
		
		$this->assertEqual($this->contentObj->get_version(), $expectedVersion);
		$this->assertEqual($this->contentObj->get_project(), $expectedProject);
	}//end testVersionAndProject
	
	
	function testFileReading() {
		unset($this->fsObj);
		$this->fsObj = new cs_fileSystemClass(dirname(__FILE__));
		$dirData = $this->fsObj->ls();
		
		if($this->assertTrue(is_array($dirData)) && $this->assertTrue(isset($dirData['data'])) && $this->assertTrue($dirData['data']['type'] == 'dir')) {
			$this->fsObj->cd('data');
			$dirData = $this->fsObj->ls();
			$this->assertTrue(is_array($dirData), "Output is INVALID");
			$this->assertTrue(isset($dirData['test.xml']), "Test file doesn't exist cwd=(". $this->fileSystemObj->realcwd .")");
			
			$this->assertEqual('/data', $this->fsObj->cwd, "Invalid CWD (". $this->fsObj->cwd .")?	");
			$this->assertTrue($this->fsObj->is_readable('test.xml'));
			$fileContents = $this->fsObj->read('test.xml');
			
			//look for a specific line, so we know we've got the right file.
			$this->assertTrue(preg_match('/csFileSystemTest="hereItIs"/', $fileContents));
		}
	}//end testFileReading()
	
	
	function testCreateListAndStringFromArray() {
		
		$testData = array(
			'listWithEvalIssues'	=> array(
				'expect'	=> 'test, x, 0, 0, , 1, ',
				'input'		=> array('test', 'x', '0', 0, FALSE, TRUE, NULL),
			),
			'duplicateLastValue'	=> array(
				'expect'	=> 'test 123, test 123, test123, test123',
				'input'		=> array('test 123', 'test 123', 'test123, test123')
			),
			'nullFirstValue'		=> array(
				'expect'	=> 'second char',
				'input'		=> array(NULL, 'second char')
			),
			
			//TODO: is this what cs-project expects???
			'nullSecondValue'		=> array(
				'expect'	=> 'first char, , third char',
				'input'		=> array('first char', NULL, 'third char')
			),
		);
		
		foreach($testData as $testName => $data) {
			$output = "";
			foreach($data['input'] as $addThis) {
				$output = $this->gfObj->create_list($output, $addThis);
			}
			$this->assertEqual($output, $data['expect']);
			$output2 = $this->gfObj->string_from_array($data['input']);
			$this->assertEqual($output2, $data['expect']);
		}
	}//end testCreateListAndStringFromArray()
	
	
	function testSQLCreation() {
		$testData = array(
			'basic'	=> array(
				'input'	=> array(
					'field1'	=> 'value1',
					'field2'	=> 'value2'
				),
				'styles'	=> array(
					'select'	=> "field1='value1' AND field2='value2'",
					'insert'	=> "(field1, field2) VALUES ('value1', 'value2')",
					'update'	=> "field1='value1', field2='value2'"
				)
			),
		);
		
		foreach($testData as $name=>$data) {
			foreach($data['styles'] as $styleName => $expectedOutput) {
				$realOutput = $this->gfObj->string_from_array($data['input'], $styleName, NULL, 'sql');
				$this->assertEqual($realOutput, $expectedOutput, "invalid output for style (".$styleName ."): ". $realOutput);
			}
		}
	}//end testSQLCreation()
}

?>
