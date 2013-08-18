<?php

function getDevName($chip)
{
	$num = intval($chip['ID'])+1;
	$id = sprintf("%03d", $num);
	return $chip['Name'] . "-$id";
}
function getChipName($chip)
{
	return  "" . (intval($chip['ProcID'])+1);
}
function formatTime($time)
{
	$formatted = "N/A";
	if($time != null && $time != "")
	{
		$now = time();
		$diff = $now - intval($time);
		if($diff < 60*5)
		{
			$seconds = $diff == 1 ? "second" : "seconds";
			$formatted = "$diff $seconds ago";
		}
		elseif($diff < 60*60*24)
		{
			$formatted = date("g:i:s T");
		}
		else
		{
			$formatted = date("M d Y");
		}
	}
	return $formatted;
}
function getChipRowStr($chip, $is_single_chip_dev, $have_multiple_chip_devs, $have_device_with_temp)
{
	$name = getDevName($chip);
	$chipNum = intval(getChipName($chip));
	$speed = "" . sprintf("%.3f", floatval($chip['MHS 5s'])) . ' MH/s';
	$state = "" . $chip['Status'];
	$pool = "" . $chip['Last Share Pool'] . "";
	if($pool == "0" || $pool == "1"){ $pool = $pool == "0" ? "Primary" : "Failover" ; }
	$last_share = formatTime($chip['Last Share Time']);
	$last_valid_work = formatTime($chip['Last Valid Work']);
	$last_difficulty  =  $chip['Last Share Difficulty'];
	if($last_difficulty != null && $last_difficulty != "") { $last_difficulty = "" . intval($last_difficulty); }
	$temp = isset($chip['Temperature']) ? $chip['Temperature']. "&deg;" : "N/A";
	$rejected = $chip['Rejected'];
	$hwerrors = $chip['Hardware Errors'];
	$accepted = $chip['Accepted'];
	$totalshares = $accepted+$rejected+$hwerrors;
	if(isset($hwerrors) && isset($totalshares))
	{
		$pcthwerrors = round($hwerrors/$totalshares*100, 2);
	}
	if(isset($rejected) && isset($totalshares) )
	{
		$pctrejected = round($rejected/$totalshares*100, 2);
	}

	$result = "";
	$result = $result . "\t\t\t\t\t\t<tr class='devtr'>\n";
	$result = $result . "\t\t\t\t\t\t\t<td>$name</td>\n";
	if($have_multiple_chip_devs)
	{
		$chipStr = sprintf("%03d", $chipNum);
		$chipName = $is_single_chip_dev ? "Single" : $name . "-$chipStr";
		$result = $result . "\t\t\t\t\t\t\t<td>$chipName</td>\n";
	}
	$result = $result . "\t\t\t\t\t\t\t<td>$speed</td>\n";
	$result = $result . "\t\t\t\t\t\t\t<td>$state</td>\n";
	$result = $result . "\t\t\t\t\t\t\t<td>$pool</td>\n";
	$result = $result . "\t\t\t\t\t\t\t<td>$last_share</td>\n";
	$result = $result . "\t\t\t\t\t\t\t<td>$last_valid_work</td>\n";
	$result = $result . "\t\t\t\t\t\t\t<td>$last_difficulty</td>\n";
	$result = $result . "\t\t\t\t\t\t\t<td>$totalshares</td>\n";
#	$result = $result . "\t\t\t\t\t\t\t<td>$accepted</td>\n";
	if (isset($pctrejected) && isset($pcthwerrors)) 
	{
		$result = $result . "\t\t\t\t\t\t\t<td>$rejected ($pctrejected)</td>\n";
		$result = $result . "\t\t\t\t\t\t\t<td>$hwerrors ($pcthwerrors)</td>\n";
        }
	else
	{
		$result = $result . "\t\t\t\t\t\t\t<td>$rejected</td>\n";
		$result = $result . "\t\t\t\t\t\t\t<td>$hwerrors</td>\n";
	}

	if($have_device_with_temp)
	{
		$result = $result . "\t\t\t\t\t\t\t<td>$temp</td>\n";
	}
	$result = $result . "\t\t\t\t\t\t</tr>\n";
	return $result;
}

function isHashing($summary)
{
	$is_hashing = false;
	if($summary != null)
	{
		$mhs_av = $summary["SUMMARY"][0]["MHS av"];
		if(floatval($mhs_av) > 0)
		{
			$is_hashing = true;
		}
	}
	return $is_hashing;
}	
function generateStatusHtml($summary, $devs)
{
	if($summary != null)
	{
		$mhs_av = $summary["SUMMARY"][0]["MHS av"];
		if($devs != null)
		{
			unset($devs['STATUS']);
			$devs = $devs['DEVS'];
		}
		if($devs == null)
		{
			$devs = [];
		}
	}
	else
	{
		$mhs_av = "0.000";
	}

	$mhs_5s_total = 0;
	$multiple_chip_devs=[];
	$num_mining_devices = 0;
	$have_device_with_temp = false;
	if(count($devs) > 0)
	{
		foreach ($devs as $chip)
		{
			$name = getDevName($chip);
			$chipNum = intval(getChipName($chip));
			$have_device_with_temp = $have_device_with_temp || isset($chip['Temperature']);
			if($chipNum > 1)
			{
				if(!isset($multiple_chip_devs[$name]))
				{
					$multiple_chip_devs[$name] = $chipNum;
				}
				else
				{
					$mulitple_chip_devs[$name] = $mulitple_chip_devs[$name] > $chipNum ? $mulitple_chip_devs[$name] : $chipNum;
				}
			}
			else
			{
				$num_mining_devices++;
			}
			$mhs_5s_total = $mhs_5s_total + $chip['MHS 5s'];

		}
		$num_mining_devices = "" . $num_mining_devices . "";
	}
	else
	{
		$num_mining_devices=`cat /usr/local/share/bfgminer/BFG_DEVICES | wc -l`;
		if($num_mining_devices == "")
		{
			$num_mining_devices = "0";
		}
	}
	
	$status = "";
	$status_class = "";
	if( $summary != null && floatval($mhs_av) > 0)
	{
		$status = "Mining";
		$status_class="data_span_good";


		#$speed = floatval($mhs_av);
		$speed = $mhs_5s_total;
		$units = " MH/s";
		if($speed > 1000*1000)
		{
			$units = " TH/s";
			$speed = $speed/(1000*1000);
		}
		elseif($speed > 1000)
		{
			$units = " GH/s";
			$speed = $speed/(1000);
		}
		$speed  = sprintf("%.3f", $speed);
		$status = $status . " @ " . $speed . $units;

	}
	else
	{
		$status = "Not Mining";
		$status_class = "data_span_bad";
		if(floatval($num_mining_devices) == 0)
		{
			$status = $status . " - No Mining Devices Detected\n";
		}
		elseif($primary_url == "" || $primary_worker == "" || $primary_pass == "")
		{
			$status = $status . " - No Pool and/or Pool Credentials Specified";
		}
		else
		{
			$status = $status . " - Verify Pool Credentials Are Accurate";
		}

		#should probably check network connectivity too, at some point
	}

	$status_html = "";
	$status_html = $status_html . "\t\t\t\t<div>\n";
	$status_html = $status_html . "\t\t\t\t\t<span class=\"label_span\">Status:</span>\n";
	$status_html = $status_html . "\t\t\t\t\t<span class=\"$status_class\" >\n";
	$status_html = $status_html . "\t\t\t\t\t\t$status\n";
	$status_html = $status_html . "\t\t\t\t\t</span>\n";
	$status_html = $status_html . "\t\t\t\t</div>\n";


	if(floatval($num_mining_devices) > 0)
	{
		$status_html = $status_html . "\t\t\t\t<div>\n";
		$status_html = $status_html . "\t\t\t\t\t<span class=\"label_span\">Detected Devices:</span>\n";
		$status_html = $status_html . "\t\t\t\t\t<span class=\"data_span\" >\n";
		$status_html = $status_html . "\t\t\t\t\t\t" . $num_mining_devices . "\n";
		$status_html = $status_html . "\t\t\t\t\t</span>\n";
		$status_html = $status_html . "\t\t\t\t</div>\n";
	}
	if($status_class == "data_span_good")
	{
		$status_html = $status_html . "\t\t\t\t<div>\n";
		$status_html = $status_html . "\t\t\t\t\t<span class=\"label_span\">Device Details:</span>\n";
		$status_html = $status_html . "\t\t\t\t\t<span class=\"data_span\" >&nbsp;</span>\n";
		$status_html = $status_html . "\t\t\t\t</div>\n";
		
		$status_html = $status_html . "\t\t\t\t<div class='devtable'>\n";
		$status_html = $status_html . "\t\t\t\t\t<table>\n";
		$status_html = $status_html . "\t\t\t\t\t\t<tr class='devtrh'>\n";
		$status_html = $status_html . "\t\t\t\t\t\t\t<td class='devth'>Device</td>\n";
		if(count($multiple_chip_devs) > 0)
		{
			$status_html = $status_html . "\t\t\t\t\t\t\t<td class='devth'>Chip</td>\n";
		}
		$status_html = $status_html . "\t\t\t\t\t\t\t<td class='devth'>Speed</td>\n";
		$status_html = $status_html . "\t\t\t\t\t\t\t<td class='devth'>State</td>\n";
		$status_html = $status_html . "\t\t\t\t\t\t\t<td class='devth'>Pool</td>\n";
		$status_html = $status_html . "\t\t\t\t\t\t\t<td class='devth'>Last Share</td>\n";
		$status_html = $status_html . "\t\t\t\t\t\t\t<td class='devth'>Last Valid Work</td>\n";
		$status_html = $status_html . "\t\t\t\t\t\t\t<td class='devth'>Last Diff.</td>\n";
		$status_html = $status_html . "\t\t\t\t\t\t\t<td class='devth'>Total Shares</td>\n";
#		$status_html = $status_html . "\t\t\t\t\t\t\t<td class='devth'>Accepted</td>\n";
		$status_html = $status_html . "\t\t\t\t\t\t\t<td class='devth'>Rejected (%)</td>\n";
		$status_html = $status_html . "\t\t\t\t\t\t\t<td class='devth'>HWErrors (%)</td>\n";

		if($have_device_with_temp)
		{
			$status_html = $status_html . "\t\t\t\t\t\t\t<td class='devth'>Temperature</td>\n";
		}

		$status_html = $status_html . "\t\t\t\t\t\t</tr>\n";
		while(count($devs) > 0)
		{
			$row   = array_shift($devs);
			$name  = getDevName($row);
			$rowStr = getChipRowStr($row, (isset($multiple_chip_devs[$name]) ? false : true), (count($multiple_chip_devs) > 0) , $have_device_with_temp);
			$status_html = $status_html . "$rowStr";
		}
		$status_html = $status_html . "\t\t\t\t\t</table>\n";
		$status_html = $status_html . "\t\t\t\t</div>\n";
#####For learning:
##print_r(array_keys($chip));

	}

	return $status_html;
}


?>
