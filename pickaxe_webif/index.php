<?php

require_once  "auth.php" ;

$GLOBALS['hashed_pass'] = getHashedPass($hashed_pass);


$message_vars = [ 'login_message', 'login_message_class', 'control_message', 'control_message_class' ];
foreach ($message_vars as $var_name)
{
	if(isset($_COOKIE[$var_name]))
	{
		$GLOBALS[$var_name] = $_COOKIE[$var_name];
		
		unset($_COOKIE[$var_name]);
		setcookie($var_name, '', 1, '/');
	}
}


if(validPasswordCookie(false, $GLOBALS['hashed_pass'], $GLOBALS['session_timeout']))
{
	setPasswordCookie($GLOBALS['hashed_pass']);
}


?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>

		<title>PIckaxe - Bitcoin Mining Interface for the Raspberry PI</title>
		<meta http-equiv="content-type" content="text/html; charset=UTF-8;charset=utf-8">

		<!-- background pattern brickwall by Benjamin Ward taken from www.subtlepatterns.com, http://subtlepatterns.com/brick-wall/ -->
		<link rel="stylesheet" href="style.css" type="text/css" />
		<link rel="icon"  href="pickaxe_logo.ico" />
	</head>
	<body>

<?php
	require_once 'bfg_api.php';
	require_once 'status.php';


	
	$config_path="/usr/local/share/bfgminer/bfgminer.conf";
	$fh = fopen($config_path, "r");
	$config_data = fread($fh, filesize($config_path));
	fclose($fh);

	$json_config = json_decode($config_data, true);

	$primary_url    = $json_config["pools"][0]["url"];
	$primary_worker = $json_config["pools"][0]["user"];
	$primary_pass   = $json_config["pools"][0]["pass"];

	$fallback_url    = $json_config["pools"][1]["url"];
	$fallback_worker = $json_config["pools"][1]["user"];
	$fallback_pass   = $json_config["pools"][1]["pass"];


	$summary = request('{"command":"summary"}');


?>




		<?php
			if($GLOBALS['hashed_pass'] != null && (validPasswordCookie(false, $GLOBALS['hashed_pass'], $GLOBALS['session_timeout'] ) || file_exists("/etc/pickaxe_show_nl_status") ) )
			{
		?>

		<div id="status" class="control_container">
			<?php
				$devs = null;
				if($summary != null)
				{
					$devs = request('{"command":"devs"}'); 
				}
				$status_html =  generateStatusHtml($summary, $devs);
				echo "$status_html"
			?>
				
		</div>

		<?php
			}
		?>



		<div id="controls" class="control_container">



			<?php
				if($GLOBALS['hashed_pass'] == null)
				{
			?>
			<form  action="set_password.php" id="main_form" method="post" accept-charset="utf-8">

				
				<div>
					<span class="label_span">&nbsp;</span>
					<span class="data_span" >This is your first visit. Set a login password</span>
				</div>

				<div class="spacer"></div>

			
				<div>
					<span class="label_span">Password:</span>
					<span class="input_span">
						<input class='rounded_text' type='password' name='web_pass_1' />
					</span>
				</div>
				<div>
					<span class="label_span">Confirm Password:</span>
					<span class="input_span">
						<input class='rounded_text' type='password' name='web_pass_2' />
					</span>
				</div>
				
				<div>
					<span class="label_span">&nbsp;</span>
					<span class="input_span">
						<input type="button" class="gold_button" value="Set Password" id="update" />
					</span>
				</div>
			</form>
			<?php
				}
				elseif( !validPasswordCookie(false, $GLOBALS['hashed_pass'], $GLOBALS['session_timeout'] ) )
				{

					if(isset($GLOBALS['login_message']) && isset($GLOBALS['login_message_class']))
					{
						echo "\t\t\t<div>\n";
						echo "\t\t\t\t<span class=\"label_span\">&nbsp;</span>\n";					
						echo "\t\t\t\t<span class=\"" . $GLOBALS['login_message_class'] . "\" >\n";
						echo "\t\t\t\t\t" . $GLOBALS['login_message'] . "\n";
						echo "\t\t\t\t</span>\n";
						echo "\t\t\t</div>\n";
					}

			?>
			<form  action="login.php" id="main_form" method="post" accept-charset="utf-8">

				<div class="spacer"></div>
			
				<div>
					<span class="label_span">Login:</span>
					<span class="input_span">
						<input class='rounded_text' type='password' name='login_pass' />
					</span>
				</div>
				
				<div>
					<span class="label_span">&nbsp;</span>
					<span class="input_span">
						<input type="button" class="gold_button" value="Login" id="update" />
					</span>
				</div>
			</form>

			<?php
				}
				else
				{
					
					if(isset($GLOBALS['control_message']) && isset($GLOBALS['control_message_class']))
					{
						echo "\t\t\t<div>\n";
						echo "\t\t\t\t<span class=\"label_span\">&nbsp;</span>\n";					
						echo "\t\t\t\t<span class=\"" . $GLOBALS['control_message_class'] . "\" >\n";
						echo "\t\t\t\t\t" . $GLOBALS['control_message'] . "\n";
						echo "\t\t\t\t</span>\n";
						echo "\t\t\t</div>\n";
					}

			?>



			<form  action="update_settings.php" id="main_form" method="post" accept-charset="utf-8">

				
				<div class="spacer"></div>
				<div>
					<span class="label_span">Primary Pool URL:</span>
					<span class="input_span">
						<?php echo "\t\t\t\t<input class='rounded_text' type='text' name='primary_url' value='$primary_url'/>\n"; ?>
					</span>
				</div>
				<div>
					<span class="label_span">Primary Worker:</span>
					<span class="input_span">
						<?php echo "\t\t\t\t<input class='rounded_text' type='text' name='primary_user' value='$primary_worker'/>\n"; ?>
					</span>
				</div>
				<div>
					<span class="label_span">Primary Worker Password:</span>
					<span class="input_span">
						<?php echo "\t\t\t\t<input class='rounded_text' type='text' name='primary_pass' value='$primary_pass'/>\n"; ?>
					</span>
				</div>

				<div class="spacer"></div>
				
				<div>
					<span class="label_span">Failover Pool URL:</span>
					<span class="input_span">
						<?php echo "\t\t\t\t<input class='rounded_text' type='text' name='fallback_url' value='$fallback_url'/>\n"; ?>
					</span>
				</div>
				<div>
					<span class="label_span">Failover Worker:</span>
					<span class="input_span">
						<?php echo "\t\t\t\t<input class='rounded_text' type='text' name='fallback_user' value='$fallback_worker'/>\n"; ?>
					</span>
				</div>
				<div>
					<span class="label_span">Failover Worker Password:</span>
					<span class="input_span">
						<?php echo "\t\t\t\t<input class='rounded_text' type='text' name='fallback_pass' value='$fallback_pass'/>\n"; ?>
					</span>
				</div>

				<div class="spacer"></div>

				<div>
					<span class="label_span">Display Status Without Login:</span>
					<span class="input_span">
						<div class="check_3d">
							<?php
								$nl_status_checked = file_exists("/etc/pickaxe_show_nl_status") ? "checked=\"yes\"" : ""; 
								echo "\t\t\t\t\t\t\t<input type=\"checkbox\"  id=\"nl_status\" $nl_status_checked name=\"nl_status\" />\n";
							?>
							<label for="nl_status"></label> 
						</div>
						<br/>
					</span>
				</div>
				<div>
					<span class="label_span">Connect Through Tor:</span>
					<span class="input_span">
						<div class="check_3d">
							<?php
								$use_tor_checked = file_exists("/etc/pickaxe_use_tor") ? "checked=\"yes\"" : ""; 
								echo "\t\t\t\t\t\t\t<input type=\"checkbox\"  id=\"use_tor\" $use_tor_checked name=\"use_tor\" />\n";
							?>
							<label for="use_tor"></label> 
						</div>
						<br/>
					</span>
				</div>

				<div class="spacer"></div>

				<div>
					<span class="label_span">Change Web Password:</span>
					<span class="input_span">
						<input class='rounded_text' type='password' value="" name='web_pass_1' />
					</span>
				</div>
				<div>
					<span class="label_span">Confirm Web Password:</span>
					<span class="input_span">
						<input class='rounded_text' type='password' value="" name='web_pass_2' />
					</span>
				</div>

				<div class="spacer"></div>

				<?php
					echo "<input type=\"hidden\" style=\"display:none\" name=\"cookie_time\" value=\"" . $_COOKIE['time'] . "\" />\n";
					echo "<input type=\"hidden\" style=\"display:none\" name=\"cookie_hash\" value=\"" . $_COOKIE['hash'] . "\" />\n";
				?>



				<div>
					<span class="label_span">&nbsp;</span>
					<span class="input_span">
						<?php
							if(isHashing($summary))
							{
								echo "\t\t\t\t\t\t<input type=\"button\" class=\"gold_button\" value=\"Update Settings\" id=\"update\" />";
							}
							else
							{
								echo "\t\t\t\t\t\t<input type=\"button\" class=\"gold_button\" value=\"Start Mining\" id=\"update\" />";
							}
						?>
						&nbsp;&nbsp;&nbsp;&nbsp;
						<input type="button" class="gold_button" value="Logout" id="logout" />
					</span>
				</div>
			</form>
			<?php
				}
			?>
		</div>



		<div id="controls" class="control_container">
			<div>
				<span class="donate">PIckaxe is free software licensed under the GNU Public License v2. If you find it useful, consider donating: 1M9GY1qNKf6Fo1HRUyFxnyH5MuztMbPBg3</span>
			</div>
		</div>


	<div id="wait" class="disable">
	</div>
	<div id="wait_message" class="disabled_message">
		<!-- wait_arrows.gif generated from http://ajaxload.info/ -->
		<p><img src="wait_arrows.gif" />&nbsp;&nbsp;Please Wait</p>
	</div>

	<script>
		function update()
		{
			var settingsForm = document.getElementById("main_form");
			if(settingsForm != null)
			{
				document.getElementById("wait").style.display = "block";
				document.getElementById("wait_message").style.display = "block";
				settingsForm.submit();
			}
		}
		function logout()
		{
			document.getElementById("wait").style.display = "block";
			document.getElementById("wait_message").style.display = "block";

			var yesterday = new Date();
			var tomorrow = new Date();
			yesterday.setDate(yesterday.getDate()-1);
			tomorrow.setDate(yesterday.getDate()+1);
			document.cookie = "hash=;expires=" + yesterday;
			document.cookie = "time=;expires=" + yesterday;
			document.cookie = "login_message=" + escape("Logged Out") + ";"
			document.cookie = "login_message_class=data_span_good;"

			window.location = window.location ;
		}

		function getCookie(name)
		{
			var a_all_cookies = document.cookie.split( ';' );
			var a_temp_cookie = '';
			var cookie_name = '';
			var cookie_value = '';
			var b_cookie_found = false; 
			for ( i = 0; i < a_all_cookies.length; i++ )
			{
				a_temp_cookie = a_all_cookies[i].split( '=' );
				cookie_name = a_temp_cookie[0].replace(/^\s+|\s+$/g, '');
				if ( cookie_name == name )
				{
					b_cookie_found = true;
					if ( a_temp_cookie.length > 1 )
					{
						cookie_value = unescape( a_temp_cookie[1].replace(/^\s+|\s+$/g, '') );
					}
					return cookie_value;
					break;
				}
				a_temp_cookie = null;
				cookie_name = '';
			}
			if ( !b_cookie_found )
			{
				return null;
			}
		}

		var doing_update = false;
		var update_time = 0;
		function updateStatus()
		{

			if(document.getElementById("status") == null) { return ; }
				
			var now = (new Date).valueOf();	
			if(doing_update && (now - update_time) < 10000) { return ; }
			doing_update = true;
			update_time = now;

			var xhr = false;
			var setStatus = function()
			{
				if(4 == xhr.readyState)
				{
					var sdiv = document.getElementById("status");
					var shtml = xhr.responseText;
					/* alert("shtml=\n" + shtml); */
					if(shtml != null && shtml != "")
					{
						sdiv.innerHTML = shtml;
					}
					doing_update = false;

				}
			}


			try
			{
				xhr = new XMLHttpRequest();
			}
			catch(e) { }
			if(xhr)
			{
				var hash = getCookie("hash");
				var time = getCookie("time");
				var pass_vars = "cookie_hash=" + hash + "&cookie_time=" + time;

				xhr.onreadystatechange = setStatus;
				xhr.open("POST", "get_status.php", true);
				xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
				xhr.send(pass_vars);
			}
		}



		
		document.getElementById("update").onclick = update;
		if(document.getElementById("logout") != null)
		{
			document.getElementById("logout").onclick = logout;
		}

		
		setInterval(updateStatus, 2000);
		document.getElementById("wait").style.display = "none";
		document.getElementById("wait_message").style.display = "none";


	</script>



	</body>
</html>
