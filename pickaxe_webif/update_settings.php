<?php



require_once  "auth.php" ;


if(!validPasswordCookie(true, null, $GLOBALS['session_timeout']))
{
	header('Location: /index.php');

}
else
{

	$config_path="/usr/local/share/bfgminer/bfgminer.conf";

	$fh = fopen($config_path, "r");
	$config_data = fread($fh, filesize($config_path));
	fclose($fh);
	$json_config = json_decode($config_data, true);


	$need_bfgminer_restart = false;
	$pool_variables = [ "primary_url", "primary_user", "primary_pass", "fallback_url", "fallback_user", "fallback_pass" ] ;
	foreach($pool_variables as $pv) 
	{
		$pool_num = preg_match('/^primary/', $pv) == 1 ? 0 : 1;
		$pool_var = preg_replace('/^.*_/', '', $pv);
		if($json_config["pools"][$pool_num][$pool_var] != $_POST[$pv])
		{
			$need_bfgminer_restart = true;
			$json_config["pools"][$pool_num][$pool_var] = $_POST[$pv];
		}
	}

	$config_data = json_encode($json_config, JSON_PRETTY_PRINT);
	$fh = fopen($config_path, "w");
	fwrite($fh, $config_data);
	fclose($fh);


	$nl_status_file="/etc/pickaxe_show_nl_status";
	$use_tor_file="/etc/pickaxe_use_tor";
	if(isset($_POST['nl_status']))
	{
		system("sudo touch '$nl_status_file'");
		system("sudo chmod 644 '$nl_status_file'");

	}
	else
	{
		system("sudo rm '$nl_status_file'");
	}


	if(isset($_POST['use_tor']))
	{
		if(!file_exists($use_tor_file))
		{
			$need_bfgminer_restart = true;
			system("sudo touch '$use_tor_file'");
			system("sudo chmod 644 '$use_tor_file'");
			system("sudo update-rc.d tor enable");
			system("sudo /etc/init.d/tor restart ; sleep 10 ;");
			system("sudo /usr/sbin/update-rc.d torify enable");
			system("sudo /etc/init.d/torify restart");
		}
	}
	else
	{
		if(file_exists($use_tor_file))
		{
			$need_bfgminer_restart = true;
			system("sudo rm '$use_tor_file'");
			system("sudo /usr/sbin/update-rc.d tor disable");
			system("sudo /etc/init.d/tor stop ");
			system("sudo /usr/sbin/update-rc.d torify disable");
			system("sudo /etc/init.d/torify stop");
		}
	}


	if($_POST["web_pass_1"] ==  $_POST["web_pass_2"] && $_POST["web_pass_1"] != "")
	{
		$hashed_pass = setPassword($_POST["web_pass_1"]);
		setPasswordCookie($hashed_pass);
	}


	setcookie( "control_message", "Settings Saved", 0, "/");
	setcookie( "control_message_class", "data_span_good", 0, "/");


	if($need_bfgminer_restart)
	{
		system("sudo /etc/init.d/bfgminer restart ");
		sleep(10);
	}

	header('Location: /index.php');
}

?>
