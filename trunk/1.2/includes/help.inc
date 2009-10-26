<?php
/*
 * Created on Nov 9, 2007
 */


$titlePart = "Help";

if(ISDEVSITE) {
	$titlePart = "**DEV** ". $titlePart;
}

if(isset($sectionArr[1])) {
	$module = $sectionArr[1];
	$module = ucwords(preg_replace('/_/', ' ', $module));
}
else {
	$module="Main Page";
}


$titleParts = array(
	'module'	=> $module,
	'title'		=> $titlePart
);
create_page_title($page, $titleParts);

?>
