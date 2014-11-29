<?php
$xr = new XMLReader();
$doc = new DOMDocument();
$xml = new SimpleXMLElement();

$result = $xr->open('tvsetupeu-last.xml');
while ($xr->read() && $xr->name != 'TVCHANNEL')
{
	switch ($xr->name)
	{
		case 'BATCH_TYPE':
			$batchtype = $xr->value;
			break;
		case 'GRIDCHANGE':
			$gridchangetype = $xr->getAttribute('TYPE');
			break;
	}
}
$xml->addChild('TVGRIDBATCH');
$xml->TVGRIDBATCH->addChild('BATCH_TYPE', $batchtype);
$xml->TVGRIDBATCH->addChild('GRIDCHANGE');
$xml->TVGRIDBATCH->GRIDCHANGE->addAttribute('TYPE', $gridchangetype);
while ($xr->name == 'TVCHANNEL')
{
	$tvchannel = simplexml_import_dom($doc->importNode($xr->expand(), true));
	// $xml->TVGRIDBATCH->GRIDCHANGE->add
	echo $tvchannel->NAME . "\n";
	$xr->next('TVCHANNEL');
}