<?php
global $isMaster;
if (!$isMaster || !isset($commandFullContent)) {
	return;
}
$sendMessageBuffer.=$commandFullContent."\n";
?>
