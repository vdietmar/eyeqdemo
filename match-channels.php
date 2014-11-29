<!DOCTYPE unspecified PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<title>Match Channels</title>
<script type="text/javascript" charset="utf-8" src="DataTables-1.9.0/media/js/jquery.js"></script>
<script type="text/javascript" charset="utf-8" src="DataTables-1.9.0/media/js/jquery.dataTables.js"></script>
<style type="text/css" title="currentStyle">
    @import "DataTables-1.9.0/media/css/demo_table.css";
</style>
</head>
<body>
<div style="float:right; position: relative; right:20px"><img src="style/gracenote.jpg"></div>
<h1>Match Channels</h1>
<p><a href="index.php">Home</a></p>
<p><b>Info:</b>
A few words on how this works. 
A signal must be presented as a DVB URI. We then try to match along 3 dimensions - triplet and name, triplet only and name only.
Based on the network type we then select one of the exact matches (if available) as final (automatic) exact match.
If not then all matches found could presented to the end user for manual selection.
For name matching we use cleaned version of the names, where spaces, '.', ',', '$', '%', '#', '[' and ']' are removed.</p>
<?php
$region = isset($_REQUEST['region'])?$_REQUEST['region']:'';
echo "<p>Region: ";
if ($region == 'EU')
{
	echo "<b>EU</b> | ";
} else
{
	echo "<a href=\"match-channels.php?region=EU\">EU</a> | ";
}
if ($region == 'SA')
{
	echo "<b>SA</b> | ";
} else
{
	echo "<a href=\"match-channels.php?region=SA\">SA</a> | ";
}
if ($region == 'JP')
{
	echo "<b>JP</b> | ";
} else
{
	echo "<a href=\"match-channels.php?region=JP\">JP</a> | ";
}
if ($region == 'APAC')
{
	echo "<b>APAC</b> | ";
} else
{
	echo "<a href=\"match-channels.php?region=APAC\">APAC</a> | ";
}
if ($region == 'ME')
{
	echo "<b>ME</b> | ";
} else
{
	echo "<a href=\"match-channels.php?region=ME\">ME</a> | ";
}
if ($region == '')
{
	echo "<b>all</b></p>";
} else
{
	echo "<a href=\"match-channels.php\">all</a></p>";
}
echo "<form action=\"match-channels.php?region=$region\" method=\"post\">\n";
echo "Copy list of DVB URIs here (one per line):<br/>\n";
echo "<textarea name=\"dvburis\" rows=\"10\" cols=\"50\">\n";
$dvburis = array();
if (isset($_REQUEST['dvburis']))
{
	$rdvburis = explode("\n", $_REQUEST['dvburis']);
	foreach($rdvburis as $key => $value)
	{
		if(strlen(trim($value)) > 0)
		{
			echo "$value\n";	
			$dvburis[] = trim($value);
		}		
	}
} else
{
	echo "dvbt://8468.3329.160/DasErste
dvbc://1.1.104/één
dvbs://1.1017.61301
dvb:///Animal Planet\n";
}
if (isset($_REQUEST['unique']) && $_REQUEST['unique'] == '1')
{
	$unique = true;
} else
{
	$unique = false;
}
if (isset($_REQUEST['dataless']) && $_REQUEST['dataless'] == '1')
{
	$dataless = true;
} else
{
	$dataless = false;
}
?>
</textarea>
<input type="checkbox" name="unique" value ="1" <?php if ($unique) echo "checked"?>>unique matches only<br/>
<input type="checkbox" name="dataless" value ="1" <?php if ($dataless) echo "checked"?>>include dataless channels<br/>
<input type="submit" value="Go">
</form>
<?php
require_once 'class-eyeqdb.inc';

function echoResult($r)
{
	global $unique;
	
	$s = '';
	switch(count($r))
	{
		case 0:
			$s .= "-";
			break;
		case 1:
			$v = $r[0];
			$p = urlencode("list-channel.php?gnid=$v->gnid");	
			$s .= "<a href=\"load-channel.php?gnid=$v->gnid&rurl=$p\">$v->name</a> ($v->lang)\n";
			break;
		default:
			if ($unique)
			{
				$s .= "multiple";
			} else
			{
				foreach($r as $v)
				{
					$p = urlencode("list-channel.php?gnid=$v->gnid");	
					$s .= "<a href=\"load-channel.php?gnid=$v->gnid&rurl=$p\">$v->name</a> ($v->lang)\n";
				}
			}
	}
	return $s;
}

if(count($dvburis) > 0)
{
	$eyeqdb = new eyeQDB();
	echo "<table id=\"matches\" width=\"100%\"><thead>
<tr><th>#</th>
<th>DVB URI</th>
<th>Triplet &amp; name</th>
<th>Triplet only</th>
<th>Name only</th>
<th>Automatic match</th></tr>
</thead><tbody>\n";
	$n = 0;
	foreach($dvburis as $dvburi)
	{
		$n++;
		$result = $eyeqdb->matchBroadcastService($dvburi, ($dataless)?false:true, $region);
		echo "<tr><td>$n</td>\n";
		echo "<td>$dvburi</td>\n";
		echo "<td>" . str_replace("\n", "<br>\n", echoResult($result[0])) .  "</td>\n";
		echo "<td>" . str_replace("\n", "<br>\n", echoResult($result[1])) .  "</td>\n";
		echo "<td>" . str_replace("\n", "<br>\n", echoResult($result[2])) .  "</td>\n";
		echo "<td>" . str_replace("\n", "<br>\n", echoResult($result[3])) .  "</td></tr>\n";
		echo "<!--\n";
		foreach ($eyeqdb->getMatchLog() as $log)
		{
			echo "$log<br>\n";
		}
		echo "-->\n";
	}
	echo "</tbody></table>\n";
} else {
	echo "<p>Nothing to do.</p>";
}
?>
<script type="text/javascript" charset="utf-8">
$(document).ready( function () {
    $('#matches').dataTable();
} );
</script>
</body>
</html>