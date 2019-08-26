<?php
if (!defined('BotFramework')) {
	die();
}
$silenceTime=(isset($commandContent)) ? intval($commandContent) : 0;
if ($silenceTime == 0 && !isset($commandContent)) {
	$silenceTime=32400;
} elseif ($silenceTime >= 1 && $silenceTime <= 1440) {
	$silenceTime*=60;
} else {
	$silenceTime=60;
}
if ($silenceTime <= 0) {
	return;
}
Silence($reqGroupNumber,$reqQQNumber,$silenceTime);
$sendMessageBuffer.="走好不送\n";
?>
