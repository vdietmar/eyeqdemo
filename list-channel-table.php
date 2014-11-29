<!DOCTYPE unspecified PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<title>Listing for Channel</title>
<script type="text/javascript" charset="utf-8" src="DataTables-1.9.0/media/js/jquery.js"></script>
<script type="text/javascript" charset="utf-8" src="DataTables-1.9.0/media/js/jquery.dataTables.js"></script>
<style type="text/css" title="currentStyle">
    @import "DataTables-1.9.0/media/css/demo_table.css";
</style>
</head>
<body>
<h1>Listing for Channel</h1>
<p><a href="index.html">Home</a></p>
<p><b>Info:</b>
The following program data for the TV channel are retrieved online with a grid lookup.
All times are UTC!</p>
<?php
require_once 'class-eyeq.inc';
require_once 'class-eyeqdb.inc';

if(isset($_REQUEST['gnid']))
{
	$gnid = $_REQUEST['gnid'];
} else
{
	$gnid = '251533419-563443E04FBBA17D220609E7E51907F8'; // Das Erste
}
$now = time();
if(isset($_REQUEST['start']))
{
	$start = $_REQUEST['start'];
} else
{
	$start = $now;
}
if(isset($_REQUEST['end']))
{
	$end = $_REQUEST['end'];
} else
{
	$end = $now+86400;
}
$starts = date('Y-m-d\TH:i:s', $start);
$ends = date('Y-m-d\TH:i:s', $end);
$prev = $start - 86400;
$next = $end + 86400;
echo "<form action=\"list-channel.php\" method=\"get\">
GNID: <input type=\"text\" name=\"gnid\" size=\"42\" value=\"$gnid\"><br>
Start: <input type=\"text\" name=\"start\" size=\"19\" value=\"$starts\">
End: <input type=\"text\" name=\"end\" size=\"19\" value=\"$ends\">
<input type=\"submit\" value=\"Go\">
</form>
<p><a href=\"list-channel.php?gnid=$gnid&start=$prev&end=$start\">-1 day</a> |
<a href=\"list-channel.php?gnid=$gnid&start=$end&end=$next\">+1 day</a></p>\n";

// Initialize eyeQ Web API
$eyeqdb = new eyeQDB();
if ($userid = $eyeqdb->getUserId())
{
	$eyeq = new eyeQ($userid);
} else
{
	// TODO Check, something doesn't work here on 1&1
	$eyeq = new eyeQ();
	$userid = $eyeq->getUserId();
	if (!$eyeqdb->setUserId($userid))
	{
		die ('List Channel: could not set user id (' . $eyeqdb->error() . ')');
	}
}
$tvchannels = array();
$tvchannels[0] = $gnid;

if ($result = $eyeq->tvGridLookup($tvchannels, $start, $end))
{
	echo "<table id=\"listing\" width=\"100%\"><thead>
	<tr><th>Start</th>
	<th>End</th>
	<th>Program</th></tr>
	</thead><tbody>\n";	
	foreach($result->TVGRID[0]->TVAIRING as $airing)
	{
		echo "<tr><td>{$airing['START']}</td>\n";
		echo "<td>{$airing['END']}</td>\n";
		$prgid = (string)$airing['TVPROGRAM_GN_ID'];
		$program = $result->TVGRID[0]->xpath("TVPROGRAM[GN_ID='$prgid']");
		echo "<td>{$program[0]->TITLE} ({$program[0]->LISTING})</td></tr>\n";
	}
	echo "</tbody></table>\n";
} else
{
	echo "<p>Nothing to do or no data.</p>";
}
?>
<script type="text/javascript" charset="utf-8">
$(document).ready( function () {
    $('#listing').dataTable( {
        "iDisplayLength": 50
    } );
} );
</script>
</body>
</html>