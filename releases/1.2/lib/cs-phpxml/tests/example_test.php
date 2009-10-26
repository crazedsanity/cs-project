<?php
/*
 * Created on Jan 13, 2009
 * 
 * FILE INFORMATION:
 * 
 * $HeadURL: https://cs-phpxml.svn.sourceforge.net/svnroot/cs-phpxml/trunk/1.0/tests/example_test.php $
 * $Id: example_test.php 91 2009-07-23 16:20:35Z crazedsanity $
 * $LastChangedDate: 2009-07-23 11:20:35 -0500 (Thu, 23 Jul 2009) $
 * $LastChangedBy: crazedsanity $
 * $LastChangedRevision: 91 $
 */

require_once(dirname(__FILE__) .'/testOfCSPHPXML.php');


$test = &new TestOfCSContent();
$test->run(new HtmlReporter());

?>
