<!DOCTYPE unspecified PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<title>Listing for Channel</title>
<link rel='stylesheet' type='text/css' href='fullcalendar-1.5.3/fullcalendar/fullcalendar.css' />
<script type='text/javascript' src='fullcalendar-1.5.3/jquery/jquery-1.7.1.min.js'></script>
<script type='text/javascript' src='fullcalendar-1.5.3/fullcalendar/fullcalendar.js'></script>
<script type="text/javascript" charset="utf-8">
<?php
require_once 'class-eyeq.inc';
require_once 'class-eyeqdb.inc';

// TODO add some stats on how many progs have syn, img, links...

function dohlinfo()
{
	global $hl, $gnid, $hasimg, $hassyn, $haswork, $hasseries, $hasepisodenumber, $gaps, $overlaps;
	
	$s = "Highlight programs with ";
	$s.= ($hl == 'syn')?"<b>synopsis ($hassyn)</b>":"<a href=\"list-channel.php?gnid=$gnid&hl=syn\">synopsis ($hassyn)</a>";
	$s.= " | ";
	$s.= ($hl == 'img')?"<b>image ($hasimg)</b>":"<a href=\"list-channel.php?gnid=$gnid&hl=img\">image ($hasimg)</a>";
	$s.= " | ";
	$s.= ($hl == 'work')?"<b>work link ($haswork)</b>":"<a href=\"list-channel.php?gnid=$gnid&hl=work\">work link ($haswork)</a>";
	$s.= " | ";
	$s.= ($hl == 'series')?"<b>series link ($hasseries)</b>":"<a href=\"list-channel.php?gnid=$gnid&hl=series\">series link ($hasseries)</a>";
	$s.= " | ";
	$s.= ($hl == 'episode')?"<b>episode number ($hasepisodenumber)</b>":"<a href=\"list-channel.php?gnid=$gnid&hl=episode\">episode number ($hasepisodenumber)</a>";
	$s.= " | ";
	$s.= ($hl == 'gaps')?"<b>gaps ($gaps)</b>":"<a href=\"list-channel.php?gnid=$gnid&hl=gaps\">gaps ($gaps)</a>";
	$s.= " | ";
	$s.= ($hl == 'overlaps')?"<b>overlaps ($overlaps)</b>":"<a href=\"list-channel.php?gnid=$gnid&hl=overlaps\">overlaps ($overlaps)</a>";
	return $s;
}

if (isset($_REQUEST['hl']))
{
	$hl = $_REQUEST['hl'];
} else
{
	$hl = '';
}

// We always work with UTC
date_default_timezone_set('UTC');

$date = new DateTime(null, new DateTimeZone('Europe/Berlin'));
$utc_offset = date_offset_get($date);
$utc_now = time();
$now = $utc_now + $utc_offset;

if(isset($_REQUEST['gnid']))
{
	$gnid = $_REQUEST['gnid'];
} else
{
	$gnid = '251533419-563443E04FBBA17D220609E7E51907F8'; // Das Erste
	//$gnid = '251533333-26F45A038CFBD8323F70D3944EB16008'; // ProSieben
	//$gnid = '251543850-4FAE84AA0BB18CD3D05B7454587B3BDA'; // Intereconomia
}
$options = "slotMinutes: 10,
	timeFormat: 'H:mm',
	axisFormat: 'H:mm'";
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
		die ('List Channel: could not set user id (' . $eyeqdb->error() . ')');
	}
}
if (!($channel = $eyeqdb->getChannel($gnid)))
{
	die ("List Channel: could not find channel with GNID $gnid");
}
$events = '';
$gaps = 0;
$overlaps = 0;
$mindate = '';
$maxdate = '';
$hasimg = 0;
$hassyn = 0;
$haswork = 0;
$hasseries = 0;
$hasepisodenumber = 0;
if ($programs = $eyeqdb->getPrograms($channel->channelid))
{
	$numprogs = count($programs);
	$lastend = strtotime($programs[0]->start)+ $utc_offset;
	$mindate = $programs[0]->start;
	$events .= "events: [\n";
	foreach($programs as $program)
	{
		$start = strtotime($program->start) + $utc_offset;
		$end = strtotime($program->end) + $utc_offset;
		$bc = '';
		if ($program->imgurl != '')
		{
			$hasimg++;
			if ($hl == 'img')
			{
				$bc = ",color:\"#BB00BB\"";
			}
		}
		if ($program->synopsis != '')
		{
			$hassyn++;
			if ($hl == 'syn')
			{
				$bc = ",color:\"#BB00BB\"";
			}
		}
		if ($program->avwork != '')
		{
			$haswork++;
			if ($hl == 'work')
			{
				$bc = ",color:\"#BB00BB\"";
			}
		}
		if ($program->avseries != '')
		{
			$hasseries++;
			if ($hl == 'series')
			{
				$bc = ",color:\"#BB00BB\"";
			}
		}
		if ($program->episodenumber != '')
		{
			$hasepisodenumber++;
			if ($hl == 'episode')
			{
				$bc = ",color:\"#BB00BB\"";
			}
		}
		if ($start < $lastend)
		{
			$overlaps++;
			if ($hl == 'overlaps')
			{
				$bc = ",color:\"#BB00BB\"";
			}
		}
		if ($start > $lastend)
		{
			$gaps++;
			if ($hl == 'gaps')
			{
				$bc = ",color:\"#BB00BB\"";
			}
		}		
		$lastend = strtotime($program->end) + $utc_offset;
		$vinfo = (strpos((eyeQConfig::epgviewing($program->viewing)), 'rerun') !== false)?' (R)':'';
		$vinfo = (strpos((eyeQConfig::epgviewing($program->viewing)), 'first_submit') !== false)?' (New)':$vinfo;
		$events .= "{id:$program->airingid,title:\"" . str_replace("\"", "\\\"", $program->title) . $vinfo . "\",start:\"" . date('c', $start) . "\",end:\"". date('c', $end) . "\",allDay:false$bc},\n";
	}
	$maxdate = $program->start;	
	$events .= "]\n";
}
echo "$(document).ready(function() {
    $('#calendar').fullCalendar({
		header: {
			left: 'prev,next today',
			center: 'title',
			right: 'month,agendaWeek'
		},
		$options,
    	$events,
    	eventClick: function(event) {
            window.open('show-program.php?id=' + event.id);
            return false;
	    }
    })
});";
$p = "gnid=$gnid&rurl=" . urlencode("list-channel.php?gnid=$gnid");
$hlinfo = dohlinfo();
$chlinfo = "$channel->lastupdate ($channel->updatetries|$channel->updateinfo)";
?>
</script>
</head>
<body>
<div style="float:right; position: relative; right:20px"><img src="style/gracenote.jpg"></div>
<h1>Listing for Channel "<?php echo $channel->name?>"</h1>
<p><a href="index.php">Home</a> |
<a href="load-channel.php?<?php echo $p?>&forceupdate=1">Refresh now</a> |
<a href="load-channel.php?<?php echo $p?>&forceupdate=2">Empty and reload</a></p>
<p><b>Info:</b>
The following program data for the TV channel are retrieved from local database which was updated last <?php echo $chlinfo?>.
All times are for time zone Europe/Berlin.</p>
<p><?php echo "$channel->name has $numprogs events (airings) from $mindate to $maxdate."?></p>
<p><?php echo $hlinfo?> (experimental)</p>
<div id='calendar'></div>
</body>
</html>