<?php
/*
 * Created on Dec 14, 2009
 *
 *  SVN INFORMATION:::
 * -------------------
 * Last Author::::::::: $Author$ 
 * Current Revision:::: $Revision$ 
 * Repository Location: $HeadURL$ 
 * Last Updated:::::::: $Date$
 */

sleep(1);
$page->printOnFinish=false;

$xml = new cs_phpxmlCreator("ajaxresponse");
$xml->add_tag("time", microtime());
$xml->add_tag("callback_success", 'callback_test');
$xml->add_tag("post_data", htmlentities($page->gfObj->debug_print($_POST,0)));
print($xml->create_xml_string());


exit;
?>
