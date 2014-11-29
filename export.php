<?php
require_once 'class-eyeqdb.inc';

$export = '';
$what = (isset($_REQUEST['what']))?$_REQUEST['what']:'ndl';
$region = isset($_REQUEST['region'])?$_REQUEST['region']:'EU';

$eyeqdb = new eyeQDB();
$export = $eyeqdb->export2CSV($what, $region);
if ($export != '')
{
	header('Content-Type: text/x-csv');
	header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
	header("Content-Disposition: attachment; filename=eyeq_{$what}_{$region}.csv");
	header('Pragma: no-cache');
	echo $export;
}
else
{
	echo "Nothing to do.";	
}
?>