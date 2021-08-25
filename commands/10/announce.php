<?php
if (!defined('BotFramework')) {
	return;
}
if (isset($commandFullContent)) {
	Announce($commandFullContent);
} else {
	if (!empty($sendMessageBuffer)) {
		Announce($sendMessageBuffer);
		$sendMessageBuffer = '';
	}
}
?>
