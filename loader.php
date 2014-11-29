<?php
require_once 'class-eyeqlog.inc';
require_once 'class-eyeq.inc';
require_once 'class-eyeqdb.inc';

$log = new eyeQLog('eyeqdemo.log');
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
		$log->add('Could not set user id', eyeQLog::LEVEL_ERROR);
		die ('Loader: could not set user id');
	}
}
$log->add("User id is $userid");

// Get channel list from parameter "lineupid"
$lineupid = is_int($_REQUEST['lineupid'])?$_REQUEST['lineupid']:"1";
$log->add("Load channel list for lineup id $lineupid");
if (!($channellist = $eyeqdb->getChannelsForLineup($lineupid)))
{
	$log->add('Could not load channel list', eyeQLog::LEVEL_ERROR);
	die ('Loader: Could not load channel list');
}
// print_r($channellist);

// Find first channel which requires an update and load data for it 
if (($cn = count($channellist)) >= 1)
{
	$log->add("Channel list loaded, has $cn channels");
	$stepper = is_int($_REQUEST['stepper'])?$_REQUEST['stepper']:"1";
	$log->add("Going to limit number of channels to load to $stepper");
	foreach($channellist as $cl)
	{
		$lastupdate = $cl->lastupdate;
		if ($lastupdate == "")
		{
			// Fresh channel which was never updated yet
			// Load data for channel
			$log->add("First time load for channel id $cl->id, '$cl->name' ($cl->gnid)");
			$stepper -= 1;
			$tvchannels = array();
			$tvchannels[] = array('gnid'=>$cl->gnid, 'stamp'=>'');
			if (($result = $eyeq->tvGridBatchUpdate($tvchannels)))
			{
				$programupdates = $result->xpath("GRIDCHANGE[@TYPE='UPDATE_TVPROGRAM']");
				$newairings = $result->xpath("GRIDCHANGE[@TYPE='ADD_TVAIRING']");
				$deletedairings = $result->xpath("GRIDCHANGE[@TYPE='DELETE_AIRING']");
				$log->add("Got grid batch data: $result->UPDATE_INST ($result->STAMP)");
				$log->add(count($programupdates[0]) . " Program updates");
				$log->add(count($newairings[0]). " new airings");
				$log->add(count($deletedairings[0]). " deleted airings");
				// Now follow the procedure from the eyeQ Web API implementation guide
				// under section 'Processing Update Instructions'
				// TODO think about moving this whole logic into the eyeQDB class
				switch($result->UPDATE_INST)
				{
					case 'MUST_RELOAD':
						$log->add('Updating full batch'); 
						$eyeqdb->deleteAirings($cl->id);
						$eyeqdb->updatePrograms($cl->id, $programupdates[0]);
						$eyeqdb->addAirings($cl->id, $newairings[0]);
						break;
					case 'APPLY_CHANGES':
						$log->add('Apply changes');
						$eyeqdb->deleteAirings($cl->id, $deletedairings[0]);
						$eyeqdb->updatePrograms($cl->id, $programupdates[0]);
						$eyeqdb->addAirings($cl->id, $newairings[0]);						
						break;
					case 'NO_CHANGE':
						$log->add('No Changes');
						break;
				}
				// TODO Update channel info in DB
			} else
			{
				$log->add('Failed to retrieve grid batch data', eyeQLog::LEVEL_ERROR);
			}
		} else 
		{
			// TODO Check this, lastupdate does not come as timestamp as time() does
			if (($timesincelastupdate = time() - (int)$lastupdate) > eyeQConfig::UPDATE_INTERVALL)
			{
				// Channel needs update
				// Check how often we tried already
				
			} 
		}
		if ($stepper == 0)
		{
			$log->add("Reached max. limit for number of channels to load");
			break;
		}
	}
} else
{
	$log->add('Channel list is empty', eyeQLog::LEVEL_WARNING);
}

// Exit. The next run will continue with the next channel


print_r($eyeq->getCallStack());
print_r($eyeq->getErrorStack());
?>
