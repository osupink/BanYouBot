<?php
if (!defined('BotFramework')) {
	return;
}
global $sendMessageBuffer;
if (DEBUG) {
	global $reqEventType, $reqGroupNumber, $debugMessageBuffer;
	if (isset($reqGroupNumber) && $reqGroupNumber === devGroupNumber && $reqEventType !== 'meta_event') {
		Debug(rtrim($debugMessageBuffer));
	}
}
if (!empty($sendMessageBuffer)) {
	$sendMessageBuffer = trim($sendMessageBuffer);
	switch ($messageType) {
		case 1:
			sendGroupMessage($reqGroupNumber, $sendMessageBuffer);
			break;
		case 0:
			sendMessage($reqQQNumber, $sendMessageBuffer);
			break;
		default:
			break;
	}
}
?>
