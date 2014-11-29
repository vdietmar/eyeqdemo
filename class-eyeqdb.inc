<?php
require_once 'class-eyeqconfig.inc';

class eyeQDB
{
	private $mysqli = null;
	private $matchlog = array();
	
	function __construct()
	{
		$host = eyeQConfig::MYSQL_HOST;
		$user = eyeQConfig::MYSQL_USER;
		$password = eyeQConfig::MYSQL_PASSWORD;
		$database = eyeQConfig::MYSQL_DATABASE;
		
		$this->mysqli = new mysqli($host, $user, $password, $database);
		if (mysqli_connect_error())
		{
			die('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
		}
	}
	
	function __destruct()
	{
		$this->mysqli->close();
	}
	
	public function error()
	{
		return $this->mysqli->error;
	}
	
	public function getUserId()
	{
		$result = $this->mysqli->query("SELECT `value` FROM `eyeq_config` where `key` = 'userid'");
		if ($result)
		{
			$row = $result->fetch_assoc();
			return ($row['value']);
		}
		return false;
	}
	
	public function setUserId($userid)
	{
		$userid = $this->mysqli->real_escape_string($userid);
		$result = $this->mysqli->query("INSERT `eyeq_config` SET `key` = 'userid', `value` = '$userid'");
		if ($result)
		{
			return ($this->mysqli->affected_rows > 0);
		}
		return false;
	}
	
	public function getChannelsForLineup($lineupid)
	{
		$lineupid = $this->mysqli->real_escape_string($lineupid);
		$result = $this->mysqli->query("SELECT ec.* FROM `eyeq_channels` ec, `eyeq_lineup_channel` el WHERE ec.`channelid` = el.`channelid` and el.`lineupid` = $lineupid");
		if ($result)
		{
			while($obj = $result->fetch_object())
			{
				$channellist[] = $obj;
			}
			return ($channellist);
		}
		return false;
	}
	
	public function deleteAirings($channelid, $airings=null)
	{
		$channelid = (int)$channelid;
		if (!is_int($channelid))
		{
			return false;
		}
		if ($airings == null)
		{
			// Delete all airings for channel
			$result = $this->mysqli->query("DELETE FROM `eyeq_airings` WHERE `channelid` = $channelid");
			if ($result === false)
			{
				return false;
			} else 
			{
				return $this->mysqli->affected_rows;
			}
		} else
		{
			// Run through airing list and delete airings
			$rows = 0;
			foreach($airings as $airing)
			{
				// First translate the GN_ID for the program
				$gnid = (string)$airing->attributes()->GN_ID;
				if ($result = $this->mysqli->query("SELECT `programid` from `eyeq_programs` WHERE `gnid` = '$gnid'"))
				{
					// Now delete the airing using channel, program, start and end as keys
					$row = $result->fetch_row();
					$prgid = $row[0];
					$start = (string)$airing->attributes()->START;
					$end = (string)$airing->attributes()->END;
					$query = "DELETE FROM `eyeq_airings` WHERE `programid` = $prgid AND `channelid` = $channelid AND `start` = '$start' AND `end` = '$end'";
					if ($result = $this->mysqli->query($query))
					{
						$rows++;
					}
				}	
			}
			return $rows;
		}
	}
	
	// TODO Add category and contributor support
	public function updatePrograms($channelid, $programs)
	{
		$rows = 0;
		
		$channelid = (int)$channelid;
		if (!is_int($channelid))
		{
			return false;
		}
		// Run through program list and update data
		foreach($programs as $program)
		{
			$gnid = $this->mysqli->real_escape_string($program->GN_ID);
			$title = $this->mysqli->real_escape_string($program->TITLE);
			$subtitle = $this->mysqli->real_escape_string($program->TITLE_SUB);
			$orgtitle = $this->mysqli->real_escape_string($program->TITLE_ORIGINAL);
			$synopsis = $this->mysqli->real_escape_string($program->SYNOPSIS);
			$listing = $this->mysqli->real_escape_string($program->LISTING);
			$date = $this->mysqli->real_escape_string($program->DATE);
			$origin = $this->mysqli->real_escape_string($program->ORIGIN);
			$type = $this->mysqli->real_escape_string($program->EPGPRODUCTION_TYPE);
			$rank = (int)$this->mysqli->real_escape_string($program->RANK);
			$groupref = (int)$this->mysqli->real_escape_string($program->GROUPREF);
			// TODO separate category and real image
			$imgurl = '';
			if ($program->URLGROUP)
			{
				// eyeQ switched back and forth in using 'medium' and '450' type infos
				// so we simply check for both
				$xp = $program->URLGROUP->xpath("URL[@SIZE='MEDIUM']");
				$imgurl = (string)$xp[0];
				if ($imgurl == '')
				{
					$xp = $program->URLGROUP->xpath("URL[@SIZE='SMALL']");
					$imgurl = (string)$xp[0];
				}
				if ($imgurl == '')
				{
					$xp = $program->URLGROUP->xpath("URL[@SIZE='450']");
					$imgurl = (string)$xp[0];
				}
				if ($imgurl == '')
				{
					$xp = $program->URLGROUP->xpath("URL[@SIZE='170']");
					$imgurl = (string)$xp[0];
				} 
			}
			$episodenumber = $this->mysqli->real_escape_string($program->EPISODE_NUM);
			$episodecount = (int)$this->mysqli->real_escape_string($program->EPISODE_CNT);
			/*
			if ($program->EPISODE_NUM)
			{
				$episodecount = (int)$this->mysqli->real_escape_string($program->EPISODE_NUM->attributes()->COUNT);
			} else 
			{
				$episodecount = 0;
			} 
			*/
			$season = (int)$this->mysqli->real_escape_string($program->SEASON_NUM);
			$avwork = '';
			$avseries = '';
			if ($program->AV_WORK)
			{
				$avwork = $this->mysqli->real_escape_string($program->AV_WORK->GN_ID);
			}
			if ($program->SERIES)
			{
				$avseries = $this->mysqli->real_escape_string($program->SERIES->GN_ID);
			}
			// Check if program already exists
			$query = "SELECT `programid` FROM `eyeq_programs` ep WHERE ep.`gnid` = '$gnid'";
			$result = $this->mysqli->query($query);
			if ($result && $result->num_rows)
			{
				// Yes, update
				$query = "UPDATE `eyeq_programs` SET
					`title` = '$title',
					`subtitle` = '$subtitle',
					`orgtitle` = '$orgtitle',
					`synopsis` = '$synopsis',
					`listing` = '$listing',
					`date` = '$date',
					`origin` = '$origin',
					`type` = '$type',
					`rank` = $rank,
					`groupref` = $groupref,
					`imgurl` = '$imgurl',
					`episodenumber` = '$episodenumber',
					`episodecount` = $episodecount,
					`season` = $season,
					`avwork` = '$avwork',
					`avseries` = '$avseries' WHERE `gnid` = '$gnid'";
			} else 
			{
				// No, insert
				$query = "INSERT INTO `eyeq_programs` SET
					`gnid` = '$gnid',
					`title` = '$title',
					`subtitle` = '$subtitle',
					`orgtitle` = '$orgtitle',
					`synopsis` = '$synopsis',
					`listing` = '$listing',
					`date` = '$date',
					`origin` = '$origin',
					`type` = '$type',
					`rank` = $rank,
					`groupref` = $groupref,
					`imgurl` = '$imgurl',
					`episodenumber` = '$episodenumber',
					`episodecount` = $episodecount,
					`season` = $season,
					`avwork` = '$avwork',
					`avseries` = '$avseries'";
			}
			$result = $this->mysqli->query($query);
			if ($result === false)
			{
				return false;
			}
			$rows += $this->mysqli->affected_rows;
		}
		return $rows;
	}
	
	private function implodeByID($element)
	{
		$ar = array();
		$result = '';
		
		if ($element)
		{
			foreach($element as $value)
			{
				$ar[] = (string)$value->attributes()->ID;
			}
			$result = $this->mysqli->real_escape_string(implode(',', $ar));
		}
		return $result;		
	}
	
	public function addAirings($channelid, $airings)
	{
		$ar = array();
		$rows = 0;		
		
		$channelid = (int)$channelid;
		if (!is_int($channelid))
		{
			return false;
		}
		// Run through airing list and add data
		foreach($airings as $airing)
		{
			// We assume that airings com from grid batch here
			// Translate GN_ID of the program
			$gnid = (string)$airing->attributes()->GN_ID;
			$query = "SELECT `programid` from `eyeq_programs` WHERE `gnid` = '$gnid'";
			if ($result = $this->mysqli->query($query))
			{
				// Let's be robust when there is an airing referencing
				// to a program which does not exist
				if ($row = $result->fetch_row())
				{
					$prgid = $row[0];
					$start = (string)$airing->attributes()->START;
					$end = (string)$airing->attributes()->END;
					$video = $this->implodeByID($airing->EPGVIDEO_TYPE);
					$audio = $this->implodeByID($airing->EPGAUDIO_TYPE);
					$viewing = $this->implodeByID($airing->EPGVIEWING_TYPE);
					$caption = $this->implodeByID($airing->EPGCAPTION_TYPE);
					$agerating = '';
					if ($airing->RATING)
					{
						$agerating = $airing->RATING[0]->CODE . '|' . $airing->RATING[0]->SYSTEM;
					}	
					$query = "INSERT INTO `eyeq_airings` SET
						`programid` = $prgid,
						`channelid` = $channelid,
						`start` = '$start',
						`end` = '$end',
						`video` = '$video',
						`audio` = '$audio',
						`viewing` = '$viewing',
						`caption` = '$caption',
						`agerating` = '$agerating'";
					$result = $this->mysqli->query($query);
					if ($result === false)
					{
						return false;
					}
					$rows += $this->mysqli->affected_rows;
				}
			}
		}
		return $rows;
	}
	
	// TODO Clean up old airings (<-7d)
	public function cleanUpPrograms()
	{
		$r = array(0,0);
		
		$query = 'DELETE FROM `eyeq_airings` WHERE `end` < DATE_SUB(CURDATE(),INTERVAL 7 DAY)';
		$result = $this->mysqli->query($query);
		if ($result !== false)
		{
			$r[0] = $this->mysqli->affected_rows;	
		}
		$query = 'DELETE FROM `eyeq_programs` WHERE `programid` NOT IN (SELECT DISTINCT `programid` FROM `eyeq_airings`)'; 
		$result = $this->mysqli->query($query);
		if ($result !== false)
		{
			$r[1] = $this->mysqli->affected_rows;
		}
		return ($r);
	}
	
	public function getPrograms($channelid)
	{
		$programs = array();
		
		$channelid = (int)$channelid;
		$result = $this->mysqli->query("SELECT ea.*,ep.* FROM `eyeq_airings` ea, `eyeq_programs` ep WHERE ea.`channelid` = $channelid AND ea.`programid` = ep.`programid` ORDER BY ea.`start`");
		if ($result)
		{
			while($obj = $result->fetch_object())
			{
				$programs[] = $obj;
			}
			return ($programs);
		}
		return false;
	}
	
	// TODO: Return previous and next program as well (or magic prev/next?)
	public function getProgram($airingid)
	{
		$airingid = (int)$airingid;
		$result = $this->mysqli->query("SELECT ea.*, ep.*, ec.`channelid`, ec.`name` AS `channelname` FROM `eyeq_airings` ea, `eyeq_programs` ep, `eyeq_channels` ec WHERE ea.`airingid` = $airingid AND ea.`programid` = ep.`programid` AND ea.`channelid` = ec.`channelid`");
		if ($result)
		{
			return $result->fetch_object();
		}
		return false;
	}
	
	private function cleanName($name)
	{
		$patterns = array();
		$patterns[] = '/\$/';
		$patterns[] = '/\[/';
		$patterns[] = '/\]/';
		$patterns[] = '/\s/';
		$patterns[] = '/\./';
		$patterns[] = '/\%/';
		// Can't do this one because we use ',' as separater ourselves - not optimal, yes
		//$patterns[] = '/\,/';
		$patterns[] = '/\#/';
		return strtolower(preg_replace($patterns, '', $name));
	}
	
	private function implodeByElement($element)
	{
		$ar = array();
		$result = '';

		if ($element)
		{
			foreach($element as $value)
			{
				$ar[] = $value;
			}
			$result = $this->mysqli->real_escape_string(implode(',', $ar));
		}
		return $result;
	}
	
	public function addChannelUpdateInfo($channelid, $tries, $info)
	{
		$channelid = (int)$channelid;
		$tries = (int)$tries;
		$info = $this->mysqli->real_escape_string($info);
		$query = "UPDATE `eyeq_channels` SET
			`lastupdate` = NOW(),
			`updatetries` = $tries,
			`updateinfo`= '$info'
			WHERE `channelid` = $channelid";
		$result = $this->mysqli->query($query);
		return ($result !== false);
	}
	
	/*
	 * channel: SimpleXMLObject
	 */
	public function addChannel($channel, $keep=false, $region='EU')
	{
		$gnid = $this->mysqli->real_escape_string($channel->GN_ID);
		$region = $this->mysqli->real_escape_string($region);
		$name = $this->mysqli->real_escape_string($channel->NAME);
		$nameshort = $this->mysqli->real_escape_string($channel->NAME_SHORT);
		$lang = $this->mysqli->real_escape_string($channel->EDITORIALLANG);
		$listingsavailable = 1;
		if ($channel->LISTINGS_AVAILABLE)
		{
			$listingsavailable = ($channel->LISTINGS_AVAILABLE == 'N')?0:1;
		}
		$callsign = $this->implodeByElement($channel->CALLSIGN);
		if ($callsign != '')
		{
			$cleannames = $this->cleanName($name) . ',' . $this->cleanName($nameshort) . ',' . $this->cleanName($callsign);
		} else
		{
			$cleannames = $this->cleanName($name) . ',' . $this->cleanName($nameshort);
		}
		$country = $this->implodeByElement($channel->COUNTRY);
		$triplet = '';
		$ar = array();
		if ($channel->DVBIDS)
		{
			foreach($channel->DVBIDS as $value)
			{
				$ar[] = 'dvb' . strtolower($value['TYPE']) . '://' . $value->ONID . '.' . $value->TSID . '.' . $value->SID;
			}
			$triplet = $this->mysqli->real_escape_string(implode(',', $ar));
		}
		if ($channel->TRANSPORTIDS)
		{
			foreach($channel->TRANSPORTIDS as $value)
			{
				$ar[] = strtolower($value['SYSTEM']) . strtolower($value['SUBSYSTEM']) . '://' . $value->TRANSPORTID[0] . '.' . $value->TRANSPORTID[1] . '.' . $value->TRANSPORTID[2];
			}
			if ($triplet == '')
			{
				$triplet = $this->mysqli->real_escape_string(implode(',', $ar));
				
			} else
			{
				$triplet.= ','.$this->mysqli->real_escape_string(implode(',', $ar));
			}
		}
		$lastupdate = '';
		$updatetries = 0;
		$updateinfo = '';
		if ($keep)
		{
			// Copy channel status
			if ($result = $this->mysqli->query("SELECT * FROM `eyeq_channels_bak` WHERE `gnid` = '$gnid'"))
			{
				// Yes channel existed before
				$oldchannel = $result->fetch_object();
				$lastupdate = ($oldchannel->lastupdate == null)?'':$oldchannel->lastupdate;
				$updatetries = ($oldchannel->updatetries == null)?0:$oldchannel->updatetries;
				$updateinfo = ($oldchannel->updateinfo == null)?'':$oldchannel->updateinfo;
			}
		}
		$triplet_s = ($triplet == '')?'':$triplet.',';
		$query = "INSERT INTO `eyeq_channels` SET
			`gnid` = '$gnid',
			`region`= '$region',
			`name` = '$name',
			`nameshort` = '$nameshort',
			`callsign` = '$callsign',
			`cleannames` = '$cleannames',
			`country` = '$country',
			`lang` = '$lang',
			`triplet` = '$triplet_s',
			`listingsavailable` = $listingsavailable,
			`lastupdate` = '$lastupdate',
			`updatetries` = $updatetries,
			`updateinfo` = '$updateinfo'";
		$result = $this->mysqli->query($query);
		if($result === false)
		{
			echo $query;
			return false;
		}
		$channelid = $this->mysqli->insert_id;
		//$triplets = explode(',', $triplet);
		//$table = ($tmp)?'eyeq_channel_triplet_tmp':'eyeq_channel_triplet';
		$table = 'eyeq_channel_triplet';
		foreach($ar as $t)
		{
			$query = "INSERT INTO `$table` SET
				`channelid` = '$channelid',
				`triplet` = '$t'";
			$result = $this->mysqli->query($query);
			if($result === false)
			{
				echo $query;
				return false;
			}
		}
		return (true);
	}
		
	/*
	 * gridchange: SimpleXMLObject 
	 * TODO: rewrite how we update channels by not deleting but marking, updating and then remove what hasn't been updated
	 */
	public function swapChannels($gridchange, $region='EU')
	{
		$rows = 0;
		$c = 0;
		
		if((!is_object($gridchange) || count($gridchange->TVCHANNEL) == 0) && (!is_array($gridchange) || count($gridchange) == 0))
		{
			return false;
		}
		// Create backups
		$result = $this->mysqli->query("CREATE TABLE IF NOT EXISTS `eyeq_channels_bak` LIKE `eyeq_channels`");
		$result = $this->mysqli->query("DELETE FROM `eyeq_channels_bak`");
		$result = $this->mysqli->query("INSERT INTO `eyeq_channels_bak` SELECT * FROM `eyeq_channels`");		
		$result = $this->mysqli->query("CREATE TABLE IF NOT EXISTS `eyeq_channel_triplet_bak` LIKE `eyeq_channel_triplet`");
		$result = $this->mysqli->query("DELETE FROM `eyeq_channel_triplet_bak`");
		$result = $this->mysqli->query("INSERT INTO `eyeq_channel_triplet_bak` SELECT * FROM `eyeq_channel_triplet`");
		// Remove current entries for region
		// EYEQ_CHANNEL_TRIPLET MUST BE EMPTIED FIRST!
		$result = $this->mysqli->query("DELETE FROM `eyeq_channel_triplet` WHERE `channelid` IN (SELECT DISTINCT ec.`channelid` FROM `eyeq_channels` ec WHERE ec.`region`= '$region')");
		$result = $this->mysqli->query("DELETE FROM `eyeq_channels` WHERE `region` = '$region'");
		// Run through all channels and add them to the tables
		if (is_object($gridchange))
		{
			$c = count($gridchange->TVCHANNEL);
			foreach($gridchange->TVCHANNEL as $channel)
			{
				if ($this->addChannel($channel, false, $region))
				{
					$rows++;
				} else
				{
					break;
				}
			}
		}
		if (is_array($gridchange))
		{
			$c = count($gridchange);
			foreach($gridchange as $channel)
			{
				if ($this->addChannel($channel, false, $region))
				{
					$rows++;
				} else
				{
					break;
				}
			}
		}
		// Either all or nothing
		if ($rows == $c)
		{
			// All fine, remove backups
			$result = $this->mysqli->query("DROP TABLE `eyeq_channels_bak`");
			$result = $this->mysqli->query("DROP TABLE `eyeq_channel_triplet_bak`");
			return true;
		} else
		{
			echo "c:$c, rows:$rows";
			// Errors, copy backup back
			$result = $this->mysqli->query("DELETE FROM `eyeq_channels`");
			$result = $this->mysqli->query("INSERT INTO `eyeq_channels` SELECT * FROM `eyeq_channels_bak`");		
			$result = $this->mysqli->query("DELETE FROM `eyeq_channel_triplet`");
			$result = $this->mysqli->query("INSERT INTO `eyeq_channel_triplet` SELECT * FROM `eyeq_channel_triplet_bak`");
			$result = $this->mysqli->query("DROP TABLE `eyeq_channels_bak`");
			$result = $this->mysqli->query("DROP TABLE `eyeq_channel_triplet_bak`");
			return false;
		}
	}
	
	public function getChannel($gnid)
	{
		$gnid = $this->mysqli->real_escape_string($gnid);
		$result = $this->mysqli->query("SELECT * FROM `eyeq_channels` ec WHERE ec.`gnid` = '$gnid'");
		if ($result)
		{
			return ($result->fetch_object());
		}
		return false;
	}
	
	public function getAllChannels($region='', $wdata=false)
	{
		$channellist = array();
		$region = $this->mysqli->real_escape_string($region);
		
		if ($region == '')
		{
			$rs = "";
			if ($wdata)
			{
				$rs.= " WHERE ec.`lastupdate` != 0 ORDER BY ec.`lastupdate` DESC";
			}
		} else
		{
			$rs = " WHERE ec.`region` = '$region'";
			if ($wdata)
			{
				$rs.= " AND ec.`lastupdate` != 0 ORDER BY ec.`lastupdate` DESC";
			}
		}
		$result = $this->mysqli->query("SELECT * FROM `eyeq_channels` ec".$rs);
		if ($result)
		{
			while($obj = $result->fetch_object())
			{
				$channellist[] = $obj;
			}
			return ($channellist);
		}
		return false;
	}
	
	// Based on the network type use the match results
	// Exact matches can be used for automatic matching
	// Multiple matches will usually be used for manual selection
	// Check Type more strictly
	public function matchBroadcastService($tripletURI, $haslistings=true, $region='')
	{
		$channellist = array();
		$region = $this->mysqli->real_escape_string($region);
		
		if ($region == '')
		{
			$rs = "";
		} else
		{
			$rs = "ec.`region` = '$region' AND";
		}
		unset($this->matchlog);
		$this->matchlog[] = "Got $tripletURI for region '$region'";
		// Split the DVB or ISDB URI into its components
		// $result = preg_match_all("/dvb([sct]?):\/\/([0-9]{1,5}\.[0-9]{1,5}\.[0-9]{1,5})\/(.*)/", $tripletURI, $matches);
		$result = preg_match_all("/([dvb]*[isdb\-j]*[isdb]*[sctb]?):\/\/([\.0-9]*)\/?(.*)/", $tripletURI, $matches);
		if ($result !== false)
		{
			$this->matchlog[] = "Trying to match '{$matches[0][0]}'";
			$type = $this->mysqli->real_escape_string($matches[1][0]);
			$triplet = $this->mysqli->real_escape_string($matches[2][0]);
			$name = $this->mysqli->real_escape_string($matches[3][0]);
			$nameclean = $this->cleanName($name);
			$this->matchlog[] = ($type == '')?'Type is empty':"Type is '$type'";
			$this->matchlog[] = ($triplet == '')?'Triplet is empty':"Triplet is '$triplet'";
			$this->matchlog[] = ($name == '')?'Name is empty':"Name is '$name'";
			$la = '';
			if ($haslistings)
			{
				$la = 'ec.`listingsavailable` = 1 AND';
				$this->matchlog[] = "Limit matching to channels with listings only";
			}
			// Try three types of matching and remember the results
			// Type 1: Triplet & name match
			$channellist[0] = array();
			if ($triplet && $name)
			{
				$this->matchlog[] = '(1) Looking for triplet and name match';
				$query = "SELECT ec.* FROM `eyeq_channels` ec WHERE $rs $la (ec.`triplet` LIKE '%/$triplet,%' AND (ec.`cleannames` LIKE '$nameclean,%' OR ec.`cleannames` LIKE '%,$nameclean,%' OR ec.`cleannames` LIKE '%,$nameclean'))";
				$result = $this->mysqli->query($query);
				switch ($result->num_rows)
				{
					case 0:
						$this->matchlog[] = '(1) Found no triplet and name match';
						break;
					case 1:
						$this->matchlog[] = '(1) Found excact triplet and name match';
						$channel = $result->fetch_object();
						$channellist[0][] = $channel;
						$this->matchlog[] = "(1) Match is '$channel->name ($channel->lang)' ($channel->gnid)";
						break;
					default:
						$this->matchlog[] = "(1) Found multiple triplet and name matches";
						while($channel = $result->fetch_object())
						{
							$channellist[0][] = $channel;
							$this->matchlog[] = "(1) Match is '$channel->name ($channel->lang)' ($channel->gnid)";
						}
				}
			} else
			{
				$this->matchlog[] = '(1) Skip triplet and name matching because one of them is missing';
			}
			// Type 2: Triplet only match
			$channellist[1] = array();
			if ($triplet)
			{
				$this->matchlog[] = '(2) Looking for triplet only match';
				$query = "SELECT ec.* FROM `eyeq_channels` ec WHERE $rs $la (ec.`triplet` LIKE '%/$triplet,%')";
				$result = $this->mysqli->query($query);
				switch ($result->num_rows)
				{
					case 0:
						$this->matchlog[] = '(2) Found no triplet only match';
						break;
					case 1:
						$this->matchlog[] = '(2) Found excact triplet only match';
						$channel = $result->fetch_object();
						$channellist[1][] = $channel;
						$this->matchlog[] = "(2) Match is '$channel->name ($channel->lang)' ($channel->gnid)";
						break;
					default:
						$this->matchlog[] = "(2) Found multiple triplet only matches";
						while($channel = $result->fetch_object())
						{
							$channellist[1][] = $channel;
							$this->matchlog[] = "(2) Match is '$channel->name ($channel->lang)' ($channel->gnid)";
						}
				}
			} else
			{
				$this->matchlog[] = '(2) Skip triplet only matching because triplet is missing';
			}
			// Type 3: Name only match
			// We only match here if the channel name has at least 2 characters
			$channellist[2] = array();
			if ($name and strlen($name) > 1)
			{
				$this->matchlog[] = "(3) Looking for name only match using '$nameclean'";
				$query = "SELECT ec.* FROM `eyeq_channels` ec WHERE $rs $la (ec.`cleannames` LIKE '$nameclean,%' OR ec.`cleannames` LIKE '%,$nameclean,%' OR ec.`cleannames` LIKE '%,$nameclean')";
				$result = $this->mysqli->query($query);
				switch ($result->num_rows)
				{
					case 0:
						$this->matchlog[] = '(3) Found no name only match';
						break;
					case 1:
						$this->matchlog[] = '(3) Found excact name only match';
						$channel = $result->fetch_object();
						$channellist[2][] = $channel;
						$this->matchlog[] = "(3) Match is '$channel->name ($channel->lang)' ($channel->gnid)";
						break;
					default:
						$this->matchlog[] = "(3) Found multiple name only matches";
						while($channel = $result->fetch_object())
						{
							$channellist[2][] = $channel;
							$this->matchlog[] = "(3) Match is '$channel->name ($channel->lang)' ($channel->gnid)";
						}
				}				
				
			} else
			{
				$this->matchlog[] = '(3) Skip name only matching because name is missing or too short';
			}
			$channellist[3] = array();
			// Now qualify the results using the network type (if available)
			// Allways give triplet and name match preference
			if(count($channellist[0]) == 1)
			{
				$channellist[3][] = $channellist[0][0];
			} else
			{
				switch($type)
				{
					case 'dvbs':
					case 'dvbt':
					case 'isdbs':
					case 'isdbt':
					case 'isdb-jb':
						// Satellite & Terrestrial
						// Use triplet only as secondary criteria and name only as last option
						if(count($channellist[3]) == 0 && count($channellist[1]) == 1)
						{
							$channellist[3][] = $channellist[1][0];
						} else
						{
							if(count($channellist[2]) == 1)
							{
								$channellist[3][] = $channellist[2][0];
							}
						}
						break;
					case 'dvbc':
					case 'isdbc':
						// Cable
						// Use name only as secondary criteria and triplet only as last option
						if(count($channellist[3]) == 0 && count($channellist[2]) == 1)
						{
							$channellist[3][] = $channellist[2][0];
						} else
						{
							if(count($channellist[1]) == 1)
							{
								$channellist[3][] = $channellist[1][0];
							}
						}
						break;
					default:
						// No additional action
						// I.e. when no type is provided then triplet only and name only are not considered
				}
			}
			if(count($channellist[3]) == 0)
			{
				$this->matchlog[] = '(4) No exact match found';
			} else
			{
				$channel = $channellist[3][0];
				$this->matchlog[] = "(4) Exact match is '$channel->name ($channel->lang)' ($channel->gnid)";;
			}
			return $channellist;
		}
		return false;
	}
	
	public function getMatchLog()
	{
		return $this->matchlog;
	}

	public function statChannels($region='EU')
	{
		$stats = array();
		$region = $this->mysqli->real_escape_string($region);

		$result = $this->mysqli->query("SELECT count(*) AS `Total Channels` FROM `eyeq_channels` ec WHERE ec.`region`= '$region'");
		$stats[] = ($result === false)?0:$result->fetch_assoc();
		$result = $this->mysqli->query("SELECT count(*) AS `Non-dataless Channels`FROM `eyeq_channels` ec WHERE ec.`region`= '$region' AND ec.`listingsavailable` = 1");
		$stats[] = ($result === false)?0:$result->fetch_assoc();
		$result = $this->mysqli->query("SELECT count(*) AS `Total Channels with triplets` FROM `eyeq_channels` ec WHERE ec.`region`= '$region' AND ec.`listingsavailable` = 1 AND ec.`triplet` != ''");
		$stats[] = ($result === false)?0:$result->fetch_assoc();
		$result = $this->mysqli->query("SELECT count(*) AS `Non-dataless Channels without triplets` FROM `eyeq_channels` ec WHERE ec.`region`= '$region' AND ec.`listingsavailable` = 1 AND ec.`triplet` = ''");
		$stats[] = ($result === false)?0:$result->fetch_assoc();
		$result = $this->mysqli->query("SELECT count(ect.`triplet`) AS `Total triplet URIs` FROM `eyeq_channel_triplet` ect, `eyeq_channels` ec WHERE ect.`channelid` = ec.`channelid` AND ec.`region`= '$region'");
		$stats[] = ($result === false)?0:$result->fetch_assoc();
		$result = $this->mysqli->query("SELECT count(ect.`triplet`) AS `Total triplet URIs for non-dataless Channels` FROM `eyeq_channel_triplet` ect, `eyeq_channels` ec WHERE ect.`channelid` = ec.`channelid` AND ec.`region`= '$region' AND ec.`listingsavailable` = 1");
		$stats[] = ($result === false)?0:$result->fetch_assoc();
		$result = $this->mysqli->query("SELECT count(distinct ect.`triplet`) AS `Unique triplet URIs for non-dataless Channels` FROM `eyeq_channel_triplet` ect, `eyeq_channels` ec WHERE ect.`channelid` = ec.`channelid` AND `region`= '$region' AND ec.`listingsavailable` = 1");
		$stats[] = ($result === false)?0:$result->fetch_assoc();
		$result = $this->mysqli->query("SELECT COUNT(distinct tmp.`triplet`) AS `Unique plain triplets for non-dataless Channels` FROM (SELECT ect.`channelid` AS `channelid`, SUBSTRING(ect.`triplet`, 8) AS `triplet` FROM `eyeq_channel_triplet` AS ect) AS tmp, `eyeq_channels` ec WHERE tmp.`channelid` = ec.`channelid` AND `region`= '$region' AND ec.`listingsavailable` = 1");
		$stats[] = ($result === false)?0:$result->fetch_assoc();
		$result = $this->mysqli->query("SELECT COUNT(distinct tmp.`triplet`) AS `Unique plain triplets used more than once` FROM (SELECT ect.`triplet`, COUNT(ect.`triplet`) AS `ct` FROM `eyeq_channel_triplet` AS ect, `eyeq_channels` AS `ec` WHERE ect.`channelid` = ec.`channelid` AND `region`= '$region' AND ec.`listingsavailable` = 1 GROUP BY ect.`triplet` HAVING `ct` != 1) AS tmp");
		$stats[] = ($result === false)?0:$result->fetch_assoc();
		return $stats;
	}
	
	public function statPrograms()
	{
		$stats = array();
		
		$result = $this->mysqli->query("select count(*) FROM (Select `gnid`, count(*) AS `cn` from `eyeq_programs` ec GROUP BY `gnid` HAVING `cn`> 1) `tmp`");
		$stats[] = ($result === false)?0:$result->fetch_assoc();		
	}
		
	private function arraytoCSV($ar)
	{
		$result = '';
		
		for ($j = 0; $j<count($ar); $j++)
		{
			$result .= "\"" . str_replace("\"", "\"\"", $ar[$j]) . "\"";
			if($j != count($ar)-1)
			{
				$result .= ";";
			}
		}
		return $result;
	}
	
	public function exportTable2CSV($tablename)
	{
		$export = '';
		
		$tablename = $this->mysqli->real_escape_string($tablename);
		$result = $this->mysqli->query("SELECT * FROM `$tablename`");
		$finfo = $result->fetch_fields();
		$names = array();
		foreach($finfo as $val)
		{
			$names[] = $val->name;
		}
		$export = $this->arraytoCSV($names) . "\r\n";
		while($row = $result->fetch_row())
		{
			$export .= $this->arraytoCSV($row) . "\r\n";
		}
		return $export;
	}
	
	public function export2CSV($what, $region='EU')
	{
		$query = '';
		$export = '';
		switch ($what)
		{
			case 'ndl':
				// Non-dataless channels
				$query = "SELECT * FROM `eyeq_channels` WHERE `listingsavailable` = 1 AND `region` = '$region'";
				break;
			case 'ndl-wot':
				// Non-dateless channels not having triplets
				$query = "SELECT * FROM `eyeq_channels` WHERE `listingsavailable` = 1 AND `triplet` = '' AND `region` = '$region'";
				break;
			case 'ndt':
				// Non-distinct triplets 
				$query = "SELECT ect.`triplet`, COUNT(ect.`triplet`) AS `ct` FROM `eyeq_channel_triplet` AS ect, `eyeq_channels` AS `ec` WHERE ect.`channelid` = ec.`channelid` AND ec.`listingsavailable` = 1 AND ec.`region` = '$region' GROUP BY ect.`triplet` HAVING `ct` != 1";
				break;	
		}
		if ($query != '')
		{
			$result = $this->mysqli->query($query);
			$finfo = $result->fetch_fields();
			$names = array();
			foreach($finfo as $val)
			{
				$names[] = $val->name;
			}
			$export = $this->arraytoCSV($names) . "\r\n";
			while($row = $result->fetch_row())
			{
				$export .= $this->arraytoCSV($row) . "\r\n";
			}
		}
		return $export;		
	}
	
}