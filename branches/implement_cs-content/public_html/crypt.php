<?php
/*
 * Created on Mar 23, 2006
 *       by
 *  Dan Falconer
 */
if($_POST['password']) {
	$cryptedPass = crypt($_POST['password'], $_POST['password']);
	print "Crypted Pass: $cryptedPass";
} else {
	print '<form method="POST"><table>' .
			'<tr><td>password</td><td><input type="password" name="password"></td></tr>' .
			'<tr><td colspan="2"><input type="submit"></td></tr>' .
			'</table></form>';
}
?>
