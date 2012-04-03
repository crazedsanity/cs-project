<?php
/*
 * Created on Oct 23, 2009
 *
 *  SVN INFORMATION:::
 * -------------------
 * Last Author::::::::: $Author$ 
 * Current Revision:::: $Revision$ 
 * Repository Location: $HeadURL$ 
 * Last Updated:::::::: $Date$
 */

require_once(constant('LIBDIR') .'/recaptchalib.php');

//TODO: put these into the site config and make 'em constants.
// Get a key from http://recaptcha.net/api/getkey

$error = null;
$resp = null;

$captchaResult = false;

if($_POST && isset($_POST["recaptcha_challenge_field"])) {
	$resp = recaptcha_check_answer (constant('RECAPTCHA_PRIVATEKEY'),
		$_SERVER["REMOTE_ADDR"],
		$_POST["recaptcha_challenge_field"],
		$_POST["recaptcha_response_field"]
	);
	if($resp->is_valid) {
		$captchaResult = true;
	}
	else {
		$error = $resp->error;
	}
}


$page->add_template_var('captcha', recaptcha_get_html(constant('RECAPTCHA_PUBLICKEY'), $error));

?>
