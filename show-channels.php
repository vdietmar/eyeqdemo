<!DOCTYPE unspecified PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<title>All Channels</title>
<script type="text/javascript" charset="utf-8" src="DataTables-1.9.0/media/js/jquery.js"></script>
<script type="text/javascript" charset="utf-8" src="DataTables-1.9.0/media/js/jquery.dataTables.js"></script>
<style type="text/css" title="currentStyle">
    @import "DataTables-1.9.0/media/css/demo_table.css";
</style>
</head>
<body>
<div style="float:right; position: relative; right:20px"><img src="style/gracenote.jpg"></div>
<h1>All Channels</h1>
<p><a href="index.php">Home</a></p>
<p><b>Info:</b>
Channels which are not supposed to have listings (dataless) are dispalyed in <span style="font-style:italic; color:gray">gray italic</span>.
Short channel names are shown in () and call signs in [].</p>
<?php
echo "<p>";
$hide = (isset($_REQUEST['hide']) && ($_REQUEST['hide'] == '1'))?true:false;
$region = isset($_REQUEST['region'])?$_REQUEST['region']:'';
$mode = isset($_REQUEST['mode'])?$_REQUEST['mode']:'';
$triplets = (isset($_REQUEST['triplets']) && ($_REQUEST['triplets'] == '1'))?true:false;
if ($hide)
{
	echo "[ <a href=\"show-channels.php?region=$region&mode=$mode&triplets=$triplets\">Show</a> | <b>Hide</b> dataless channels ]\n";
} else
{
	echo "[ <b>Show</b> | <a href=\"show-channels.php?hide=1&region=$region&mode=$mode&triplets=$triplets\">Hide</a> dataless channels ]\n";
}

echo "[ ";
if ($region == 'EU')
{
	echo "<b>EU</b> | ";
} else
{
	echo "<a href=\"show-channels.php?hide=$hide&mode=$mode&region=EU&triplets=$triplets\">EU</a> | ";
}
if ($region == 'SA')
{
	echo "<b>SA</b> | ";
} else
{
	echo "<a href=\"show-channels.php?hide=$hide&mode=$mode&region=SA&triplets=$triplets\">SA</a> | ";
}
if ($region == 'JP')
{
	echo "<b>JP</b> | ";
} else
{
	echo "<a href=\"show-channels.php?hide=$hide&mode=$mode&region=JP&triplets=$triplets\">JP</a> | ";
}
if ($region == 'APAC')
{
	echo "<b>APAC</b> | ";
} else
{
	echo "<a href=\"show-channels.php?hide=$hide&mode=$mode&region=APAC&triplets=$triplets\">APAC</a> | ";
}
if ($region == 'ME')
{
	echo "<b>ME</b> | ";
} else
{
	echo "<a href=\"show-channels.php?hide=$hide&mode=$mode&region=ME&triplets=$triplets\">ME</a> | ";
}
if ($region == '')
{
	echo "<b>all</b>";
} else
{
	echo "<a href=\"show-channels.php?hide=$hide&mode=$mode&triplets=$triplets\">all</a>";
}
echo " ]";

if ($triplets)
{
	echo " [ <b>Show</b> | <a href=\"show-channels.php?region=$region&mode=$mode&hide=$hide\">Hide</a> triplets ]\n";
} else
{
	echo " [ <a href=\"show-channels.php?triplets=1&region=$region&mode=$mode&hide=$hide\">Show</a> | <b>Hide</b> triplets ]\n";
}

echo "</p>
<table width=\"100%\" id=\"channels\">
<thead>
<tr>
<th>Channel</th>
<th>Country/Language</th>\n";
if ($triplets)
{
	echo "<th>Triplet(s)</th>\n";
}
echo "<th>Update Status</th>
</tr>
</thead>
<tbody>\n";

require_once 'class-eyeqdb.inc';

$eyeqdb = new eyeQDB();
$channels = $eyeqdb->getAllChannels($region, ($mode=='wdata'));

foreach ($channels as $channel)
{
	if (!$hide || $channel->listingsavailable == 1)
	{
		$style = ($channel->listingsavailable == 0)?"font-style:italic; color:gray":"";
		$p = urlencode("list-channel.php?gnid=$channel->gnid");
		echo "<tr style=\"$style\">";
		echo "<td width=\"20%\"><a href=\"load-channel.php?gnid=$channel->gnid&rurl=$p\">$channel->name</a> ($channel->nameshort)";
		if ($channel->callsign != '')
		{
			echo " [" . str_replace(',', ', ', $channel->callsign) . "]";
		}
		echo "</td>";
		echo "<td width=\"10%\">$channel->country ($channel->lang)</td>";
		if ($triplets)
		{
			echo "<td width=\"60%\">" . str_replace(',', ', ', $channel->triplet) . "</td>";
		}
		if (strtotime($channel->lastupdate) != 0)
		{
			echo "<td width=\"10%\">$channel->lastupdate ($channel->updatetries|$channel->updateinfo)</td>";
		} else
		{
			echo "<td width=\"10%\">empty</td>";
		}
		echo "</tr>\n";
	}
}
?>
</tbody>
</table>
<script type="text/javascript" charset="utf-8">
$(document).ready( function () {
    $('#channels').dataTable( {
<?php
	if($mode=='wdata')
	{
		echo "\"aaSorting\": [[ 3, \"desc\" ]],\n";
		echo "\"iDisplayLength\": 100\n";
	}
?>
	} );
    $('input').focus();
} );
</script>
</body>
</html>