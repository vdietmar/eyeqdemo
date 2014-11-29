<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Statistics</title>
</head>
<body>
<div style="float:right; position: relative; right:20px"><img src="style/gracenote.jpg"></div>
<h1>Statistics</h1>
<p><a href="index.php">Home</a></p>
<p><b>Info:</b>
Numbers of special interest are marked <b>bold</b>.</p>
<table>
<thead>
<tr>
<th>KPI</th>
<th>EU</th>
<th>SA</th>
<th>JP</th>
<th>APAC</th>
<th>ME</th>
</tr>
</thead>
<tbody>
<?php
require_once 'class-eyeqdb.inc';

function echoStat($region, $value, $export='')
{
	if ($export == '')
	{
		echo "<td>&nbsp;$value&nbsp;</td>";
	} else
	{
		echo "<td>&nbsp;<a href=\"export.php?region=$region&what=$export\"><b>$value</b></a>&nbsp;</td>";
	}
}

$eyeqdb = new eyeQDB();
$statsEU = $eyeqdb->statChannels('EU');
$statsSA = $eyeqdb->statChannels('SA');
$statsJP = $eyeqdb->statChannels('JP');
$statsAPAC = $eyeqdb->statChannels('APAC');
$statsME = $eyeqdb->statChannels('ME');
$kpi = array('', 'ndl', '', 'ndl-wot', '', '', '', '', 'ndt');
//var_dump($statsEU);
for($i=0; $i<9; $i++)
{
	echo "<tr>\n";
	$export = $kpi[$i];
	foreach($statsEU[$i] as $title => $value)
	{
		if ($export == '')
		{
			echo "<td>$title&nbsp;</td>";
		} else
		{
			echo "<td><b>$title</b>&nbsp;</td>";
		}
		$v = $value;
	}
	echoStat('EU', $v, $export);
	foreach($statsSA[$i] as $title => $value)
	{
		$v = $value;
	}
	echoStat('SA', $v, $export);
	foreach($statsJP[$i] as $title => $value)
	{
		$v = $value;
	}
	echoStat('JP', $v, $export);
	foreach($statsAPAC[$i] as $title => $value)
	{
		$v = $value;
	}
	echoStat('APAC', $v, $kpi[$i]);
	foreach($statsME[$i] as $title => $value)
	{
		$v = $value;
	}
	echoStat('ME', $v, $kpi[$i]);
	echo "</tr>\n";	
}
?>
</tbody>
</table>
</body>
</html>