<?php
if (!defined('BotFramework') || !isset($commandSubType)) {
	return;
}
if (!is_numeric($commandSubType)) {
	$silenceQQ = isATorQQ($commandSubType);
	if (!isVaildQQ($silenceQQ)) {
		return;
	}
}
$silenceCount = (isset($commandContent) && is_numeric($commandContent) && $commandContent <= 20) ? $commandContent : 3;
for ($i=0; $i<$silenceCount; $i++) {
	Silence($reqGroupNumber, $silenceQQ, $silenceCount);
	Silence($reqGroupNumber, $silenceQQ, 0);
}
?>
