<?php
/*
 * Created on Jan 25, 2009
 * 
 * FILE INFORMATION:
 * 
 * $HeadURL: https://cs-arraytopath.svn.sourceforge.net/svnroot/cs-arraytopath/trunk/tests/testOfA2P.php $
 * $Id: testOfA2P.php 25 2009-01-25 23:17:49Z crazedsanity $
 * $LastChangedDate: 2009-01-25 17:17:49 -0600 (Sun, 25 Jan 2009) $
 * $LastChangedBy: crazedsanity $
 * $LastChangedRevision: 25 $
 */

require_once(dirname(__FILE__) .'/../cs_arrayToPath.class.php');

class testOfA2P extends UnitTestCase {
	
	//-------------------------------------------------------------------------
	function setUp() {
		$this->a2p = new cs_arrayToPath(array());
		$this->gfObj = new cs_globalFunctions;
	}//end setUp()
	//-------------------------------------------------------------------------
	
	
	
	//-------------------------------------------------------------------------
	function tearDown() {
	}//end tearDown()
	//-------------------------------------------------------------------------
	
	
	
	//-------------------------------------------------------------------------
	function test_basics() {
		//make sure nothing is in the object initialially.
		$this->assertEqual(array(), $this->a2p->get_data());
		
		$newData = array(
			'look at me'	=> '23dasdvcv3q3qeedasd'
		);
		$this->a2p->reload_data($newData);
		$this->assertNotEqual(array(), $this->a2p->get_data());
		
		
		//load a complex array & test to ensure the returned value is the same.
		$newData = array(
			'x'		=> array(
				'y'		=> array(
					'z'		=> array(
						'fiNal'		=> 'asdfadsfadfadsfasdf'
					)
				),
				'_y_'	=> null,
				'-'		=> null
			),
			'a nother path2 Stuff -+=~!@#$'
		);
		$this->a2p->reload_data($newData);
		$this->assertEqual($newData, $this->a2p->get_data());
		$this->assertEqual($newData['x']['y']['z']['fiNal'], $this->a2p->get_data('/x/y/z/fiNal'));
		
		$this->a2p->set_data('/x/y/z/fiNal', null);
		$this->assertNotEqual($this->a2p->get_data('/x/y/z/fiNal'), $newData['x']['y']['z']['fiNal']);
		
		//ensure paths with dots are ok.
		$this->assertEqual($this->a2p->get_data('/x/y/z/fiNal'), $this->a2p->get_data('/x/y/z/g/q/x/../../../fiNal'));
		
		//make sure extra slashes are okay.
		$this->assertEqual($this->a2p->get_data('/x/y/z/fiNal'), $this->a2p->get_data('/x/y//z///fiNal//'));
	}//end test_basics()
	//-------------------------------------------------------------------------
	
}//end testOfA2P{}
?>
