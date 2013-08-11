<?php

#
# Simple file-based password auth system based on crypt() and sha512 hashing 
# Fairly reasonable and secure for a single user, not useful if you have more than one authorized user ;-)
# 
# Session timeout is by default 15 minutes (variable is in seconds)
# 

$GLOBALS['session_timeout'] = 60*15;


function validPasswordCookie($cookie_vars_from_post=false, $hashed_pass=null, $session_timeout=null)
{
	$hashed_pass_file = "/etc/pickaxe_hashed_pass";

	if($session_timeout == null)
	{
		$sessionh_timout = $GLOBALS['session_timeout'];
	}

	$user_agent  = $_SERVER['HTTP_USER_AGENT'];
	$source_ip   = $_SERVER['REMOTE_ADDR'];
	$cookie_time = $cookie_vars_from_post ? $_POST['cookie_time'] : $_COOKIE['time'];
	$cookie_hash = $cookie_vars_from_post ? $_POST['cookie_hash'] : $_COOKIE['hash'];
	if($cookie_time == null || $cookie_hash == null)
	{
		return false;
	}
	if(time() - intval($cookie_time) > $session_timeout)
	{
		#expired session 
		return false;
	}
	
	$retval = false;
	$hashed_pass = getHashedPass($hashed_pass);
	if($hashed_pass != null)
	{
		if(hash("sha512", $hashed_pass . $user_agent . $source_ip . $cookie_time, false) == "$cookie_hash")
		{
			$retval = true;
		}
	}
	return $retval;
}

function loginValid($login_password, $hashed_pass=null)
{
	$valid = false;
	$hashed_pass = getHashedPass($hashed_pass);
	
	if($hashed_pass != null)
	{
		if( (crypt($login_password, $hashed_pass) == $hashed_pass) )
		{
			$valid = true;
		}
	}
	return $valid;
}



function setPasswordCookie($hashed_pass=null)
{
	$now = time();
	$user_agent  = $_SERVER['HTTP_USER_AGENT'];
	$source_ip   = $_SERVER['REMOTE_ADDR'];
	$hashed_pass = getHashedPass($hashed_pass);
	if($hashed_pass != null)
	{
		setcookie( "time", "$now", 0, "/");
		setcookie( "hash" , hash("sha512", $hashed_pass . $user_agent . $source_ip . "$now", false), 0, "/");
	}

}

function setPassword($password)
{
	$hashed_pass_file = "/etc/pickaxe_hashed_pass";
	$hashed_pass = crypt($password);
		
	system("sudo touch '$hashed_pass_file'");
	system("sudo chmod 777 '$hashed_pass_file'");
	$fh = fopen($hashed_pass_file, "w");
	fwrite($fh, $hashed_pass);
	fclose($fh);
	system("sudo chmod 644 '$hashed_pass_file'");

	return $hashed_pass;
}

function getHashedPass($loaded_hashed_pass=null)
{
	$hashed_pass_file = "/etc/pickaxe_hashed_pass";
	if($loaded_hashed_pass == null)
	{
		if(file_exists($hashed_pass_file))
		{
			$fh = fopen("$hashed_pass_file", 'r');
			$loaded_hashed_pass = fread($fh, filesize($hashed_pass_file));
			fclose($fh);
		}
	}
	return $loaded_hashed_pass;
}


?>
