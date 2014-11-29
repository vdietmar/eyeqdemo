<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Program Details</title>
</head>
<body>
<div style="float:right; position: relative; right:20px"><img src="style/gracenote.jpg"></div>
<h1>Program Details</h1>
<p><b>Info:</b> All times are for time zone Europe/Berlin.</p>
<?php 
require_once 'class-eyeq.inc';
require_once 'class-eyeqdb.inc';

function echoRow($name, $value)
{
	if ($value != '' && $value != '0')
	{
		echo "<tr><td><span style=\"color:grey; white-space:nowrap\">$name</span></td><td>$value</td></tr>\n";
	}
}

// We always work with UTC
date_default_timezone_set('UTC');

$date = new DateTime(null, new DateTimeZone('Europe/Berlin'));
$utc_offset = date_offset_get($date);
$utc_now = time();
$now = $utc_now + $utc_offset;

if(isset($_REQUEST['id']))
{
	$id = $_REQUEST['id'];
} else
{
	//$id = '5294';
	die ('Show Program: No id supplied.');
}
// Initialize eyeQ Web API
$eyeqdb = new eyeQDB();
if ($userid = $eyeqdb->getUserId())
{
	$eyeq = new eyeQ($userid);
} else
{
	$eyeq = new eyeQ();
	$userid = $eyeq->getUserId();
	if (!$eyeqdb->setUserId($userid))
	{
		die ('Show Program: could not set user id (' . $eyeqdb->error() . ')');
	}
}
if ($program = $eyeqdb->getProgram($id))
{
	// var_dump($program);
	$start = strtotime($program->start) + $utc_offset;
	$end = strtotime($program->end) + $utc_offset;
	echo "<table border=\"0\">\n";
	if ($program->imgurl != '') 
	{
		echoRow('Image', "<img src=\"$program->imgurl\">");
	}
	echoRow('Title', $program->title);
	echoRow('Subtitle', $program->subtitle);
	echoRow('Original Title', $program->orgtitle);
	echoRow('GNID', $program->gnid);
	echoRow('Channel', $program->channelname);
	echoRow('Start', date("l, d-M-y H:i:s", $start));
	echoRow('End', date("l, d-M-y H:i:s", $end));
	echoRow('Audio', eyeQConfig::epgaudio($program->audio));
	echoRow('Video', eyeQConfig::epgvideo($program->video));
	echoRow('Viewing', eyeQConfig::epgviewing($program->viewing));
	echoRow('Caption', eyeQConfig::epgcaption($program->caption));
	echoRow('Group Reference', $program->groupref);
	echoRow('Season', $program->season);
	echoRow('Episode Number', $program->episodenumber);
	echoRow('Episode Count', $program->episodecount);
	echoRow('Listing Text', $program->listing);
	echoRow('Synopsis', $program->synopsis);
	echoRow('Production Type', $program->type);
	echoRow('Production Date', $program->date);
	echoRow('Production Year', $program->orign);
	echoRow('Rank', $program->rank);
	echoRow('AV Work', $program->avwork);
	echoRow('AV Series', $program->avseries);
	echo "</table>\n";
}
?>
</body>
</html>