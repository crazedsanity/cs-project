<?php
/*
 * Created on Jan 13, 2009
 * 
 * FILE INFORMATION:
 * 
 * $HeadURL: https://cs-phpxml.svn.sourceforge.net/svnroot/cs-phpxml/trunk/tests/example_test.php $
 * $Id: example_test.php 56 2009-01-26 05:48:26Z crazedsanity $
 * $LastChangedDate: 2009-01-25 23:48:26 -0600 (Sun, 25 Jan 2009) $
 * $LastChangedBy: crazedsanity $
 * $LastChangedRevision: 56 $
 */

require_once(dirname(__FILE__) .'/testOfCSPHPXML.php');


$test = &new TestOfCSContent();
$test->run(new HtmlReporter());

?>
