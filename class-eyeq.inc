<?php
require_once 'class-eyeqconfig.inc';
// TODO Change to config class

class eyeQ
{
	protected $userid = '';
	protected $country = 'DEU';
	protected $language = 'ger';
	protected $callstack = array();
	protected $errorstack = array();
	
	// Implicity perform registration or use existing user ID
	function __construct($userid=null)
	{
		if($userid == null)
		{
			// Create one, i.e. register
			$r = $response = $this->request('REGISTER');
			if($r)
			{
				$this->userid = (string)$response->USER; 
			} else 
			{
				// throw new Exception('Web API Registration failed');
				$this->errorstack = 'Web API Registration failed';
			}
		} else
		{
			// Use the provided one
			$this->userid = $userid;	
		}
	}
	
	// Getters and Setters
	
	public function setCountry($country)
	{
		$this->country = $country;
	}
	
	public function getCountry()
	{
		return $this->country;
	}
	
	public function setLanguage($language)
	{
		$this->language = $language;
	}
	
	public function getLanguage()
	{
		return $this->language;
	}
	
	public function getCallStack()
	{
		// TODO Check, something wrong here
		$cs = $this->callstack;
		$csclean = array();
		foreach ($cs as $key => $value)
		{
			if(strpos($value, '<CLIENT>') === false)
			{
				$csclean[] = $value;
			} else
			{
				$csclean[] = preg_replace('/<CLIENT>.*<\/CLIENT>/', '<CLIENT>Client ID removed</CLIENT>', $value);
			}
		}
		return $csclean;
	}
	
	public function getErrorStack()
	{
		return $this->errorstack;
	}
	
	public function getLastError()
	{
		$c = count($this->errorstack);
		if ($c > 0)
		{
			return $this->errorstack[$c-1];
		} else
		{
			return false;
		}
	}
	
	public function getUserId()
	{
		return $this->userid;
	}
	
	// Issue a request to the Web API
	private function request($cmd, $parameter=null)
	{
		$endpoint = eyeQConfig::WEB_API_ENDPOINT;
		$clientid = eyeQConfig::CLIENT_ID;
		$appinfo = eyeQConfig::MY_APP_INFO;
		$user = $this->getUserId();
		if($user != '' || $cmd = 'REGISTER')
		{
			if($user == '')
			{
				// Must be Register	
				$queries = "<QUERIES>
	<APP_INFO>$appinfo</APP_INFO>
	<QUERY CMD=\"REGISTER\">
		<CLIENT>$clientid</CLIENT>
	</QUERY>
</QUERIES>";				
			} else
			{
				// Any other command
				$queries = "<QUERIES>
	<AUTH>
		<CLIENT>$clientid</CLIENT>
		<USER>$user</USER>
	</AUTH>
	<LANG>{$this->getLanguage()}</LANG>
	<COUNTRY>{$this->getCountry()}</COUNTRY>
	<APP_INFO>$appinfo</APP_INFO>
	<QUERY CMD=\"$cmd\">
		$parameter
	</QUERY>
</QUERIES>";
			}
			$this->callstack[] = $endpoint . ":\n" . $queries;  
			
			$opts = array (
				'http' => array (
					'method' => 'POST',
					'header' => "Connection: close\r\nContent-type: application/x-www-form-urlencoded\r\nContent-Length: " . strlen($queries) . "\r\n",
                	'content' => $queries
				)
			);
			$response = file_get_contents($endpoint, false, stream_context_create($opts));
			$rh = $http_response_header;
			if($rh[0] != 'HTTP/1.1 200 OK')
			{
				$this->errorstack[] = 'HTTP Request failed: ' . $rh[0];
				return false;
			} 
			$xml = simplexml_load_string($response);
			if($xml->RESPONSE[0]->attributes()->STATUS == 'OK')
			{
				return $xml->RESPONSE[0];
			} else
			{
				$this->errorstack[] = 'Web API Request returned error: ' . $xml->MESSAGE;
				return false;
			}
		} else
		{
			// log error
			$this->errorstack[] = 'User ID missing';
			return false;
		}
	}
	
	public function tvChannelSetup($dumpfilename=null, $region='EU')
	{
		$parameter = "<STATE_INFO>
	<STATE_TYPE>TVSETUP_REGION-$region</STATE_TYPE>
</STATE_INFO>";
		if (($result = $this->request('TVGRIDBATCH_UPDATE', $parameter)) !== false)
		{
			$updateinfo = $result->UPDATE_INFO[0];
			try
			{
				$response = file_get_contents($updateinfo->URL);
				$rh = $http_response_header;
				if($rh[0] != 'HTTP/1.1 200 OK')
				{
					// throw new Exception('Web API Fequest failed');
					$this->errorstack[] = 'HTTP Request failed: ' . $rh[0];
					return false;
				}
				$decoded = gzinflate(substr($response, 10, -8));				
				if($dumpfilename != null)
				{
					@file_put_contents($dumpfilename.'-'.$region, $decoded);
				}
				$xml = simplexml_load_string($decoded);
				return ($xml);
			} catch (Exception $e)
			{
				$this->errorstack[] = 'Exception: ' . $e->getMessage();
				return false;
			}
		}
		return false;
	}
	
	public function tvChannelSetupasArray($dumpfilename=null, $region='EU')
	{
		$parameter = "<STATE_INFO>
	<STATE_TYPE>TVSETUP_REGION-$region</STATE_TYPE>
</STATE_INFO>";
		
		if (($result = $this->request('TVGRIDBATCH_UPDATE', $parameter)) !== false)
		{
			$updateinfo = $result->UPDATE_INFO[0];
			try
			{
				$response = file_get_contents($updateinfo->URL);
				$rh = $http_response_header;
				if($rh[0] != 'HTTP/1.1 200 OK')
				{
					// throw new Exception('Web API Fequest failed');
					$this->errorstack[] = 'HTTP Request failed: ' . $rh[0];
					return false;
				}
				$decoded = gzinflate(substr($response, 10, -8));				
				if($dumpfilename != null)
				{
					@file_put_contents($dumpfilename.'-'.$region, $decoded);
				}
				$xr = new XMLReader();
				$doc = new DOMDocument();
				$tvchannels = array();
				if ($xr->xml($decoded))
				{
					while ($xr->read() && $xr->name != 'TVCHANNEL');
					while ($xr->name == 'TVCHANNEL')
					{
						$tvchannels[] = simplexml_import_dom($doc->importNode($xr->expand(), true));
						$xr->next('TVCHANNEL');
					}
					return ($tvchannels);
				} else
				{
					return false;
				}
			} catch (Exception $e)
			{
				$this->errorstack[] = 'Exception: ' . $e->getMessage();
				return false;
			}
		}
		return false;
	}
	
	
	public function tvChannelSetupEU($dumpfilename=null)
	{
		$parameter = "<STATE_INFO>
	<STATE_TYPE>TVSETUP_REGION-EU</STATE_TYPE>
</STATE_INFO>";
		if (($result = $this->request('TVGRIDBATCH_UPDATE', $parameter)) !== false)
		{
			$updateinfo = $result->UPDATE_INFO[0];
			try
			{
				$response = file_get_contents($updateinfo->URL);
				$rh = $http_response_header;
				if($rh[0] != 'HTTP/1.1 200 OK')
				{
					// throw new Exception('Web API Fequest failed');
					$this->errorstack[] = 'HTTP Request failed: ' . $rh[0];
					return false;
				}
				$decoded = gzinflate(substr($response, 10, -8));				
				if($dumpfilename != null)
				{
					@file_put_contents($dumpfilename, $decoded);
				}
				$xml = simplexml_load_string($decoded);
				return ($xml);
			} catch (Exception $e)
			{
				$this->errorstack[] = 'Exception: ' . $e->getMessage();
				return false;
			}
		}
		return false;
	}
	
	public function tvChannelSetupEUasArray($dumpfilename=null)
	{
		$parameter = "<STATE_INFO>
	<STATE_TYPE>TVSETUP_REGION-EU</STATE_TYPE>
</STATE_INFO>";
		
		if (($result = $this->request('TVGRIDBATCH_UPDATE', $parameter)) !== false)
		{
			$updateinfo = $result->UPDATE_INFO[0];
			try
			{
				$response = file_get_contents($updateinfo->URL);
				$rh = $http_response_header;
				if($rh[0] != 'HTTP/1.1 200 OK')
				{
					// throw new Exception('Web API Fequest failed');
					$this->errorstack[] = 'HTTP Request failed: ' . $rh[0];
					return false;
				}
				$decoded = gzinflate(substr($response, 10, -8));				
				if($dumpfilename != null)
				{
					@file_put_contents($dumpfilename, $decoded);
				}
				$xr = new XMLReader();
				$doc = new DOMDocument();
				$tvchannels = array();
				if ($xr->xml($decoded))
				{
					while ($xr->read() && $xr->name != 'TVCHANNEL');
					while ($xr->name == 'TVCHANNEL')
					{
						$tvchannels[] = simplexml_import_dom($doc->importNode($xr->expand(), true));
						$xr->next('TVCHANNEL');
					}
					return ($tvchannels);
				} else
				{
					return false;
				}
			} catch (Exception $e)
			{
				$this->errorstack[] = 'Exception: ' . $e->getMessage();
				return false;
			}
		}
		return false;
	}
	
	public function tvChannelLookupDVB($DVBtriplets)
	{
	}
	
	// Load EPG data for one channel
	public function tvGridBatchUpdate($tvchannels, $dumpfilename=null)
	{
		foreach($tvchannels as $cl)
		{
			if ($cl['stamp'] != "")
			{
				$parameter = "<STATE_INFO>
	<STATE_TYPE>TVCHANNEL</STATE_TYPE>
	<STAMP>{$cl['stamp']}</STAMP>
	<GN_ID>{$cl['gnid']}</GN_ID>
</STATE_INFO>
<OPTION>
	<PARAMETER>FULL_TVPROGRAM</PARAMETER>
	<VALUE>YES</VALUE>
</OPTION>";
			} else
			{
				$parameter = "<STATE_INFO>
	<STATE_TYPE>TVCHANNEL</STATE_TYPE>
	<GN_ID>{$cl['gnid']}</GN_ID>
</STATE_INFO>
<OPTION>
	<PARAMETER>FULL_TVPROGRAM</PARAMETER>
	<VALUE>YES</VALUE>
</OPTION>";
			}
		}
		if (($result = $this->request('TVGRIDBATCH_UPDATE', $parameter)) !== false)
		{
			if($dumpfilename != null)
			{
				@$result->asXML($dumpfilename);
			}				
			// TODO Add support for multiple channels
			$updateinfo = $result->UPDATE_INFO[0];
			if ($updateinfo->UPDATE_INST == 'NO_CHANGE')
			{
				return $updateinfo;
			} else
			{
				try
				{
					$response = @file_get_contents($updateinfo->URL);
					$rh = $http_response_header;
					if($rh[0] != 'HTTP/1.1 200 OK')
					{
						// throw new Exception('Web API Fequest failed');
						$this->errorstack[] = 'HTTP Request failed: ' . $rh[0];
						return false;
					}
					$xml = simplexml_load_string($response);
					// Fix the issue of the actual batch not having the stamp if its an update batch
					if ($updateinfo->UPDATE_INST == 'APPLY_CHANGES')
					{
						$xml->addChild('STAMP', $updateinfo->STAMP);
					}
					return ($xml);
				} catch (Exception $e)
				{
					$this->errorstack[] = 'Exception: ' . $e->getMessage();
					return false;
				}
			}
		}
		return false;
	}
	
	public function tvGridLookup($tvchannels, $start=0, $end=0)
	{
		$now = time();
		$start = ($start == 0)?$now:$start;
		$end = ($end == 0)?$now+3600:$end;
		$starts = date('Y-m-d\TH:i:s', $start);
		$ends = date('Y-m-d\TH:i:s', $end);
		
		$parameter = '<TVCHANNEL>\n';
		foreach($tvchannels as $key => $value)
		{
			$parameter .= "<GN_ID>$value</GN_ID>\n";
		}
		$parameter .= "</TVCHANNEL>
<DATE TYPE=\"START\">$starts</DATE>
<DATE TYPE=\"END\">$ends</DATE>
<OPTION>
	<PARAMETER>SELECT_EXTENDED</PARAMETER>
	<VALUE>TVPROGRAM_IMAGE,IPGCATEGORY_IMAGE</VALUE>
</OPTION>\n";
		if (($result = $this->request('TVGRID_LOOKUP', $parameter)) !== false)
		{
			return($result);
		} else
		{
			return false;
		}
	
	}
}