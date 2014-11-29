<?php
abstract class eyeQConfig
{
	// Access credentials for Gracenote eyeQ Web API
	const WEB_API_ENDPOINT = 'https://c952576.ipg.web.cddbp.net/webapi/xml/1.0/';
	const CLIENT_ID        = '952576-FE66D9A329DE5700E312A2CFFBEC981D';
	const MY_APP_INFO      = 'app="eyeQ Test",os="PHP",mfg="Dietmar Krauss",sdk="Web API"';
		
	// Access credentials for MySQL DB
	// 1&1
	const MYSQL_HOST     = 'db542612620.db.1and1.com';
	const MYSQL_USER     = 'dbo542612620';
	const MYSQL_PASSWORD = 'ncc1701d';
	const MYSQL_DATABASE = 'db542612620';

	// LoaderSettings
	// const UPDATE_INTERVAL =  86400; // 1 day
	const UPDATE_INTERVAL =  43200; // 1/2 day
	const UPDATE_MAX_TRIES = 3;
	const CLEAN_UP = true;
	private static $epgaudiotypes = array(
		'68003' => 'mono_dual',
		'68004' => 'stereo',
		'68005' => 'multichannel',
		'68006' => 'dolby',
		'68007' => 'description',
		'68008' => 'hardhearing',
		'68009' => 'mono_single',
		'68010' => 'ac3',
		'68011' => 'dolbydig',
		'68012' => 'original_language');

	private static $epgvideotypes = array(
		'68013' => 'v169',
		'68014' => 'hdtv',
		'68015' => 'pal30',
		'68016' => 'live',
		'68017' => 'rerun',
		'68018' => 'v149',
		'68019' => 'bw',
		'68020' => 'vt150');

	private static $epgcaptiontypes = array(
		'68021' => 'ebu',
		'68022' => 'dvb',
		'68023' => 'dvb_hardhearing',
		'68024' => 'closedcaption',
		'68025' => 'original_language',
		'68026' => 'local_language');

	private static $epgviewingtypes = array(
		'67998' => 'live',
		'67999' => 'rerun',
		'68000' => 'first_submit',
		'68001' => 'last_submit',
		'68002' => 'cod');
	
	public static function epgaudio($s)
	{
		$a = explode(',', $s);
		$r = array();
		foreach ($a as $v)
		{
			if (array_key_exists($v, self::$epgaudiotypes))
			{
				$r[] = self::$epgaudiotypes[$v];
			}
		}
		return implode(', ', $r);
	}

	public static function epgvideo($s)
	{
		$a = explode(',', $s);
		$r = array();
		foreach ($a as $v)
		{
			if (array_key_exists($v, self::$epgvideotypes))
			{
				$r[] = self::$epgvideotypes[$v];
			}
		}
		return implode(', ', $r);
	}

	public static function epgcaption($s)
	{
		$a = explode(',', $s);
		$r = array();
		foreach ($a as $v)
		{
			if (array_key_exists($v, self::$epgcaptiontypes))
			{
				$r[] = self::$epgcaptiontypes[$v];
			}
		}
		return implode(', ', $r);
	}

	public static function epgviewing($s)
	{
		$a = explode(',', $s);
		$r = array();
		foreach ($a as $v)
		{
			if (array_key_exists($v, self::$epgviewingtypes))
			{
				$r[] = self::$epgviewingtypes[$v];
			}
		}
		return implode(', ', $r);
	}
}
