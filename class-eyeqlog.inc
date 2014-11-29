<?php
class eyeQLog
{
	const LEVEL_INFO = 'INFO';
	const LEVEL_WARNING = 'WARNING';
	const LEVEL_ERROR = 'ERROR';
	
	private $filename = "logfile.log";
	private $fh;
	private $pre = '';
	private $post= '';
	
	function __construct($filename=NULL, $pre='', $post='')
	{
		if ($filename === NULL)
		{
			$pi = pathinfo(__FILE__);
			$this->filename = $pi['filename'] . '.log';
			//$this->filename = basename(__FILE__, '.php') . 'log';
		} else
		{
			$this->filename = $filename;
		}
		$this->pre = $pre;
		$this->post = $post;
		return (($this->fh = @fopen($this->filename, 'a+')) !== FALSE);
	}
	
	function __destruct()
	{
		@fclose($this->fh);
	}
	
	// Getter and Setter
	
	public function getFilename()
	{
		return $this->filename;
	}
	
	/*
	 * Adds a new entry into the log
	 * Format is: [2005-08-15T15:52:01+00:00] [Module] [Level] Message 
	 */
	public function add($entry, $level=eyeQLog::LEVEL_INFO, $module='')
	{
		$logmessage = "[".date(DATE_ATOM)."] " . (($module=="")?"":"[$module] ") . "[$level] " . $entry . "\n";
		@fwrite($this->fh, $logmessage);
		if ($this->pre != '' || $this->post != '')
		{
			echo $this->pre . $logmessage . $this->post;
			flush();
		}
	}
}