<!DOCTYPE unspecified PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<title>Channel Loader</title>
</head>
<body>
<div style="float:right; position: relative; right:20px"><img src="style/gracenote.jpg"></div>
<h1>Channel Loader</h1>
<p><a href="index.php">Home</a></p>
<p>Automatically loading channels now...</p>
<p>
<?php
require_once 'class-eyeqlog.inc';
require_once 'class-eyeq.inc';
require_once 'class-eyeqdb.inc';

$log = new eyeQLog('eyeqdemo.log', '', "<br>\n");
$log->add('Start');

// Initialize eyeQ Web API
$log->add('Initialize Web API');
$eyeqdb = new eyeQDB();
if ($userid = $eyeqdb->getUserId())
{
	$log->add('Use existing user id');
	$eyeq = new eyeQ($userid);
} else
{
	$log->add('Regiser for new user id');
	$eyeq = new eyeQ();
	$userid = $eyeq->getUserId();
	if (!$eyeqdb->setUserId($userid))
	{
		$log->add('Could not set user id (' . $eyeqdb->error() . ')', eyeQLog::LEVEL_ERROR);
		die ('Channel Loader: could not set user id (' . $eyeqdb->error() . ')');
	}
}
$log->add("User id is $userid");

$region = isset($_REQUEST['region'])?$_REQUEST['region']:'EU';

// Request and read channel setup and dump local copy
$log->add("Requesting TV channel setup data for $region");
//$xml = $eyeq->tvChannelSetup('tvsetup-last.xml', $region);
$tvchannels = $eyeq->tvChannelSetupasArray('tvsetup-last.xml', $region);
//if ($xml === FALSE)
if ($tvchannels === FALSE)
{
	$log->add("Error getting TV channel setup data for $region (" . $eyeq->getLastError(). ')', eyeQLog::LEVEL_ERROR);
	die("Channel Loader: Error getting TV channel setup data for $region (" . $eyeq->getLastError(). ')');
}
//$result = $xml->xpath("GRIDCHANGE[@TYPE='UPDATE_TVCHANNEL']");
//$gridchange = $result[0]; // There's only one always
//$tvc = count($gridchange->TVCHANNEL);
$tvc = count($tvchannels);
$log->add("$tvc TV channels available");
if ($tvc > 0)
{
	$log->add("Starting to swap channels for $region");
	//if ($eyeqdb->swapChannels($gridchange, $region))
	if ($eyeqdb->swapChannels($tvchannels, $region))
	{
		$log->add("Successfully swapped channels for $region");
	} else
	{
		$log->add("Failed to swap channels for $region (" . $eyeqdb->error() . ')', eyeQLog::LEVEL_ERROR);
		die("Channel Loader: Failed to swap channels for $region (" . $eyeqdb->error() . ')');
	}
} else
{
	$log->add("$region file does not contain any channels for", eyeQLog::LEVEL_WARNING);
}
?>
</p>
</body>
</html>