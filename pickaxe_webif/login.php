<?php

require_once  "auth.php" ;

$login_password = $_POST['login_pass'];
$hashed_pass = getHashedPass();
$valid = false;
if($hashed_pass != null)
{
	if(loginValid($login_password, $hashed_pass, $GLOBALS['session_timeout']))
	{
		setPasswordCookie($hashed_pass);
		$valid = true;
	}
}
if(!$valid)
{
	setcookie("login_message", 'Invalid Password', 0, '/');
	setcookie("login_message_class", 'data_span_bad', 0, '/');
}
header('Location: /index.php');

?>

