<!DOCTYPE unspecified PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<title>Program Data Loader</title>
</head>
<body>
<h1>Program Data Loader</h1>
<p><a href="index.php">Home</a></p>
<p>
<?php
require_once 'class-eyeqlog.inc';
require_once 'class-eyeq.inc';
require_once 'class-eyeqdb.inc';

function load()
{
	global $log, $eyeq, $eyeqdb, $channel;
	
	$tvchannels = array();
	$tvchannels[] = array('gnid'=>$channel->gnid, 'stamp'=>$channel->updateinfo);
	$result = $eyeq->tvGridBatchUpdate($tvchannels, 'tvgridbatch-last.xml');
	if ($result !== false)
	{
		$log->add("Got grid batch data: $result->UPDATE_INST ($result->STAMP)");
		if ($result->UPDATE_INST != 'NO_CHANGE')
		{
			$programupdates = $result->xpath("GRIDCHANGE[@TYPE='UPDATE_TVPROGRAM']");
			$newairings = $result->xpath("GRIDCHANGE[@TYPE='ADD_TVAIRING']");
			$deletedairings = $result->xpath("GRIDCHANGE[@TYPE='DELETE_TVAIRING']");
			$log->add(count($programupdates[0]) . " Program updates");
			$log->add(count($newairings[0]). " new airings");
			$log->add(count($deletedairings[0]). " deleted airings");
		}
		// Now follow the procedure from the eyeQ Web API implementation guide
		// under section 'Processing Update Instructions'
		// TODO think about moving this whole logic into the eyeQDB class
		switch($result->UPDATE_INST)
		{
			case 'MUST_RELOAD':
				$log->add('Updating full batch'); 
				break;
			case 'APPLY_CHANGES':
				$log->add('Apply changes');
				break;
			case 'NO_CHANGE':
				$log->add('No Changes');
				break;
		}
		switch($result->UPDATE_INST)
		{
			case 'MUST_RELOAD':
			case 'APPLY_CHANGES':
				// Clean up
				if (eyeQConfig::CLEAN_UP && ($r = $eyeqdb->cleanUpPrograms()) !== false)
				{
					$log->add("Cleaned up {$r[0]} airings and {$r[1]} programs folder than 7d (all channels)");
				} else
				{
					if (eyeQConfig::CLEAN_UP)
					{
						$log->add('Could not clean up programs (' . $eyeqdb->error() . ')', eyeQLog::LEVEL_ERROR);
						die ('Program Data Loader: could clean up programs (' . $eyeqdb->error() . ')');
					} else
					{
						$log->add('Clean up deactivated', eyeQLog::LEVEL_WARNING);
					}
				}
				// Log this batch
				$dfn = 'data/tvgridbatch-' . $channel->gnid . '-' . time() . '.xml';
				@$result->asXML($dfn);
				switch ($r = $eyeqdb->deleteAirings($channel->channelid, $deletedairings[0]))
				{
					case 0:
						if ($r === false)
						{
							$log->add('Could not delete airings (' . $eyeqdb->error() . ')', eyeQLog::LEVEL_ERROR);
							die ('Program Data Loader: could not add airings (' . $eyeqdb->error() . ')');
						}
						// no break here!
					case count($deletedairings[0]):
						$log->add("Deleted $r airings");
						break;
					default:
						if (count($deletedairings[0]) != 0)
						{
							$log->add("Number or deleted airings ($r) does not match expected number (" . count($deletedairings[0]) . ")", eyeQLog::LEVEL_WARNING);
						}
				}
				switch ($r = $eyeqdb->updatePrograms($channel->channelid, $programupdates[0]))
				{
					case 0:
						if ($r === false)
						{
							$log->add('Could not add airings (' . $eyeqdb->error() . ')', eyeQLog::LEVEL_ERROR);
							die ('Program Data Loader: could not add airings (' . $eyeqdb->error() . ')');
						}
						// no break here!
					case count($programupdates[0]):
						$log->add("Updated or added $r programs");
						break;
					default:
						if (count($programupdates[0]) != 0)
						{
							$log->add("Number or updated programs ($r) does not match expected number (" . count($programupdates[0]) . ")", eyeQLog::LEVEL_WARNING);
						}
				}
				switch ($r = $eyeqdb->addAirings($channel->channelid, $newairings[0]))
				{
					case 0:
						if ($r === false)
						{
							$log->add('Could not add airings (' . $eyeqdb->error() . ')', eyeQLog::LEVEL_ERROR);
							die ('Program Data Loader: could not add airings (' . $eyeqdb->error() . ')');
						}
						// no break here!
					case count($newairings[0]):
						$log->add("Added $r airings");
						break;
					default:
						if (count($newairings[0]) != 0)
						{
							$log->add("Number or added airings ($r) does not match expected number (" . count($newairings[0]) . ")", eyeQLog::LEVEL_WARNING);
						}
				}				
				// Clean up (again)
				if (eyeQConfig::CLEAN_UP && ($r = $eyeqdb->cleanUpPrograms()) !== false)
				{
					$log->add("Cleaned up {$r[0]} airings and {$r[1]} programs folder than 7d (all channels)");
				} else
				{
					if (eyeQConfig::CLEAN_UP)
					{
						$log->add('Could not clean up programs (' . $eyeqdb->error() . ')', eyeQLog::LEVEL_ERROR);
						die ('Program Data Loader: could clean up programs (' . $eyeqdb->error() . ')');
					} else
					{
						$log->add('Clean up deactivated', eyeQLog::LEVEL_WARNING);
					}
				}
				break;
		}
		if (!$eyeqdb->addChannelUpdateInfo($channel->channelid, 0, $result->STAMP))
		{
			$log->add('Could not update info for channel (' . $eyeqdb->error() . ')', eyeQLog::LEVEL_ERROR);
		}
	} else
	{
		if (!$eyeqdb->addChannelUpdateInfo($channel->channelid, $channel->updatetries + 1, $result->STAMP))
		{
			$log->add('Could not update info for channel (' . $eyeqdb->error() . ')', eyeQLog::LEVEL_ERROR);
		}
		$log->add('Failed to retrieve grid batch data (' . $eyeq->getLastError() . ')', eyeQLog::LEVEL_ERROR);
		die ('Program Data Loader: failed to retrieve grid batch data (' . $eyeq->getLastError() . ')');
	}
}

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
		die ('Program Data Loader: could not set user id (' . $eyeqdb->error() . ')');
	}
}
$log->add("User id is $userid");

// $gnid = isset($_REQUEST['gnid'])?$_REQUEST['gnid']:'251533419-563443E04FBBA17D220609E7E51907F8'; // Das Erste
// $gnid = isset($_REQUEST['gnid'])?$_REQUEST['gnid']:'251533333-26F45A038CFBD8323F70D3944EB16008'; // ProSieben
// $gnid = isset($_REQUEST['gnid'])?$_REQUEST['gnid']:'251540459-43511CE0BFABB4E7F79387E1D5BC6B1F'; // MTV3 Fakt
// $gnid = isset($_REQUEST['gnid'])?$_REQUEST['gnid']:'251547114-2BB581CD9B90C4E217024ABA7CC4A9A5'; // Ð Ð¾Ñ�Ñ�Ð¸Ñ� 1
$gnid = isset($_REQUEST['gnid'])?$_REQUEST['gnid']:'251540452-97768C936F5E27ED470F0E94ED3D104E';
$rurl = isset($_REQUEST['rurl'])?$_REQUEST['rurl']:'';
if (isset($_REQUEST['forceupdate']))
{
	$forceupdate = $_REQUEST['forceupdate'];
	switch ($forceupdate)
	{
		case '1':
			$log->add('Force to load channel');
			break;
		case '2':
			$log->add('Force to empty and load channel');
			break;
	}
} else
{
	$forceupdate = '';
}
if ($gnid != '')
{
	$log->add("Loading program data for channel with GNID $gnid");
	if ($channel = $eyeqdb->getChannel($gnid))
	{
		if ($forceupdate == '2')
		{
			$channel->lastupdate = '';
			$channel->updateinfo = '';
			$channel->updateinfo = 0;
			$log->add("Force to empty and load channel id $channel->channelid, '$channel->name'");
		}
		$lastupdate = strtotime($channel->lastupdate);
		if ($lastupdate == 0 || $forceupdate == '1')
		{
			// First time
			if ($forceupdate == '1')
			{
				$log->add("Force to update channel id $channel->channelid, '$channel->name'");
			}
			$log->add("First time load for channel id $channel->channelid, '$channel->name'");
			load();
		} else 
		{
			$timesincelastupdate = time() - $lastupdate;
			$tries = (int)$channel->updatetries;
			if ($timesincelastupdate > eyeQConfig::UPDATE_INTERVAL)
			{
				// Channel needs update
				$log->add("Update for channel id $channel->channelid, '$channel->name' ($channel->updateinfo)");
				// Check how often we tried already
				if ($tries < eyeQConfig::UPDATE_MAX_TRIES)
				{
					$log->add("$tries tries so far");
					load();
				} else 
				{
					// Maximum limit reched, admin needs to interfere
					$log->add("No update, max tries ($tries) was reached - please reset", eyeQLog::LEVEL_WARNING);
				}
			} else
			{
				// We had a try in the last interval but it failed
				if ($tries > 0 && $tries < eyeQConfig::UPDATE_MAX_TRIES)
				{
					$log->add("$tries tries so far");
					load();
				} else 
				{
					$log->add("No update required for channel id $channel->channelid, '$channel->name', interval is set to " . eyeQConfig::UPDATE_INTERVAL . ' seconds (' . (eyeQConfig::UPDATE_INTERVAL/3600) . ' hours)');
				}
			}
		}
	} else
	{
		$log->add("Could not get details for GNID $gnid");
	}
} else
{
	echo "Nothing to do.\n";
}
echo "<!--\n";
print_r($eyeq->getCallStack());
print_r($eyeq->getErrorStack());
echo "-->\n";
?>
</p>
<script type="text/javascript" charset="utf-8">
<?php if ($rurl != '') echo "location.replace('$rurl');"?>
</script>
</body>
</html>