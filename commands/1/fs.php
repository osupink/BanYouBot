<?php
if (!isset($commandSubType)) {
	return;
}
if (!is_numeric($commandSubType)) {
	$commandSubType=isAT($commandSubType);
	if (!is_numeric($commandSubType)) {
		return;
	}
}
$silenceQQ=$commandSubType;
$silenceCount=(isset($commandContent) && is_numeric($commandContent) && $commandContent <= 20) ? $commandContent : 3;
for ($i=0;$i<$silenceCount;$i++) {
	Silence($reqGroupNumber, $silenceQQ, $silenceCount);
	Silence($reqGroupNumber, $silenceQQ, 0);
}
?>
