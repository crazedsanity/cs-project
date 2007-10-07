<?php
/*
 * Created on Sep 20, 2007
 *
 */

class Test_csphpxml extends UnitTestCase {
	
	function setUp() {
		$this->xmlParser = new XMLParser(file_get_contents(dirname(__FILE__) .'/data/test.xml'));
		$this->assertTrue(is_object($this->xmlParser));
		
		$this->xmlBuilder = new xmlBuilder(NULL);
		$this->assertTrue(is_object($this->xmlBuilder));
		
		$this->xmlCreator = new xmlCreator();
	}
	
}

?>
