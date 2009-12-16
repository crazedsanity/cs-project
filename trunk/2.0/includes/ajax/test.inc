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

$page->printOnFinish=false;

$xml = new cs_phpxmlCreator("ajaxresponse");

$xml->add_tag("time", microtime());
if($_POST) {
	$xml->add_tag("callback_success", 'callback_test');
	$xml->add_tag("post_data", base64_encode($page->gfObj->debug_print($_POST,0)));
}
else {
	$xml->add_tag("callback_success", 'callback_get');
	if(count($_GET)) {
		foreach($_GET as $gName=>$gVal) {
			$xml->add_tag("_get_". $gName, $gVal);
		}
		$xml->add_tag("all_get_tags", base64_encode($page->gfObj->debug_print($_GET)));
	}
}
print($xml->create_xml_string());


exit;
?>
