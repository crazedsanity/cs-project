<?php
/*
 * Created on Jan 13, 2009
 * 
 * FILE INFORMATION:
 * 
 * $HeadURL: https://cs-content.svn.sourceforge.net/svnroot/cs-content/trunk/1.0/tests/example_test.php $
 * $Id: example_test.php 316 2009-01-19 16:43:10Z crazedsanity $
 * $LastChangedDate: 2009-01-19 10:43:10 -0600 (Mon, 19 Jan 2009) $
 * $LastChangedBy: crazedsanity $
 * $LastChangedRevision: 316 $
 */

require_once(dirname(__FILE__) .'/testOfCSContent.php');


$test = &new TestOfCSContent();
$test->run(new HtmlReporter());

?>
