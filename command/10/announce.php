<?php
if (!defined('BotFramework')) {
	return;
}
if (isset($commandFullContent)) {
	Announce(str_replace('\n', "\n", $commandFullContent));
} else {
	if (!empty($sendMessageBuffer)) {
		Announce($sendMessageBuffer);
		$sendMessageBuffer = '';
	}
}
?>
