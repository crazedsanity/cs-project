<?php
/*
 * Created on Nov 9, 2007
 */


$page->add_template_var('cs-project_version', VERSION_STRING);
$page->clear_content('infobar');



#$titlePart = NULL;
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
