<?php
if (!defined('BotFramework')) {
	return;
}
if (isset($commandFullContent)) {
	$sendMessageBuffer .= hash('sha512',ClientAccKey . " {$commandFullContent} " . ClientAccKey);
}
?>
