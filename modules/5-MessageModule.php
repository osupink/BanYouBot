<?php
if (!defined('BotFramework')) {
	return;
}
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
			$result = sendGroupMessage($reqGroupNumber, $sendMessageBuffer);
			break;
		case 0:
			$result = sendMessage($reqQQNumber, $sendMessageBuffer);
			break;
		default:
			break;
	}
	if (isset($result) && isset($result['data']['message_id'])) {
		$globalMessageID = $result['data']['message_id'];
		AddDebugValue(array('globalMessageID' => $globalMessageID));
	}
	if (isset($globalMessageID) && $isNeedDelete > 0) {
		$cacheFileDir = RootPath . ReplacePathSeparator("/cache/messages");
		if (!is_dir($cacheFileDir) && !mkdir($cacheFileDir)) {
			trigger_error("Creating cacheFile Directory Error: {$cacheFileDir}", E_USER_WARNING);
			return;
		}
		$cacheFilePath = $cacheFileDir . ReplacePathSeparator("/{$globalMessageID}.txt");
		AddDebugValue(array('cacheFilePath' => $cacheFilePath));
		file_put_contents($cacheFilePath, time() + $isNeedDelete);
	}
}
?>
