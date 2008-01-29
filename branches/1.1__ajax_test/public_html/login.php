<?php
/*
 * Created on Mar 10, 2006
 *       by
 *  Dan Falconer
 */
/*This file should just be **SYMLINKED** as another filename: appropriate entries need to be made in the
	.htaccess file, to force it to be run as a PHP file.

EXAMPLE: public_html/content  ->  ./index.php
  ADD TO .htaccess:::
<files content>
	ForceType application/x-httpd-php
</files>
*/

require_once(dirname(__FILE__) ."/../lib/site_config.php");
$GLOBALS['DEBUGPRINTOPT'] = 1;
define("DEBUGPRINTOPT", 1);

$db = new cs_phpDB;
$db->connect(get_config_db_params());
$session = new Session($db);

$contentObj = new contentSystem();
$contentObj->handle_session($session);
$contentObj->finish();

?>
