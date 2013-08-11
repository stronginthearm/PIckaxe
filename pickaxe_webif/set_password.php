<?php

require_once  "auth.php" ;

$password_1 = $_POST['web_pass_1'];
$password_2 = $_POST['web_pass_2'];
$hashed_pass_file = "/etc/pickaxe_hashed_pass";

if($password_1 == $password_2 && $password_1 != "")
{
	$hashed_pass = setPassword($password_1);
	setPasswordCookie($hashed_pass);
	setcookie("control_message", 'Password Set', 1, '/');
	setcookie("control_message_class", 'data_span_good', 1, '/');
}

header('Location: /index.php');


?>	
