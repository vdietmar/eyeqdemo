<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>eyeQ Demo</title>
</head>
<body>
<div style="float:right; position: relative; right:20px"><img src="style/gracenote.jpg"></div>
<h1>eyeQ Data Browsing</h1>
<p><b>Info:</b>
These are some simple tools to work with eyeQ data retrieved via the Web API and
stored in a local MySQL database. Choose an option and off you go.</p>
<h2>See the data</h2>
<ul>
<li><a href="show-channels.php?hide=1">All Channels</a> (browse, search)</li>
<li><a href="match-channels.php">Match Channels</a> (test a DVB signal)</li>
<li><a href="stats.php">Channel Statistics</a> (overview, KPI export)</li>
<li>Latest Channels:
<ul>
<?php
require_once 'class-eyeqdb.inc';

$eyeqdb = new eyeQDB();
$channels = $eyeqdb->getAllChannels('', true);
if ($channels !== false)
{
	$i = 0;
	foreach($channels as $channel)
	{
		$p = urlencode("list-channel.php?gnid=$channel->gnid");
		echo "<li><a href=\"load-channel.php?gnid=$channel->gnid&rurl=$p\">$channel->name</a> ($channel->lang) - $channel->lastupdate\n";
		if ($i++ >30) break;
	}
}
?>
<li><a href="show-channels.php?hide=1&mode=wdata">more...</a></li>
</ul></li>
</ul>
<h2>Advanced Options</h2>
<p>Only use them if you know what you are doing.</p>
<ul>
<li>Load Channels (takes a few seconds, required only once a day)
	<ul>
	<li><a href="load-channels.php?region=EU">EU</a></li>
	<li><a href="load-channels.php?region=SA">SA</a></li>
	<li><a href="load-channels.php?region=JP">JP</a></li>
	<li><a href="load-channels.php?region=APAC">APAC</a></li>
	<li><a href="load-channels.php?region=ME">ME</a></li>
	</ul></li>
<li>Most recent Channel XML files (huge!)
	<ul>
	<li><a href="tvsetup-last.xml-EU">EU</a></li>
	<li><a href="tvsetup-last.xml-SA">SA</a></li>
	<li><a href="tvsetup-last.xml-JP">JP</a></li>
	<li><a href="tvsetup-last.xml-APAC">APAC</a></li>
	<li><a href="tvsetup-last.xml-ME">ME</a></li>
	</ul></li>
<li><a href="data/">Gridbatch XML files</a> (by GNID and stamp)</li>
<li><a href="eyeqdemo.log">Logfile</a> (starting with oldest entries)</li>
</ul>
</body>
</html>