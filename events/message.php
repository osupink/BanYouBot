<?php
if (!defined('BotFramework')) {
	die();
}
global $conn, $reqJSONArr, $reqQQNumber, $reqGroupNumber, $reqRawMessage;
if (!isset($reqRawMessage)) {
	return;
}
function MatchCommandPrefix($str) {
	return (substr($str, 0, 1) === '!') ? true : false;
}
function TrimMultiSpace($str) {
	$str=preg_replace('/ {2,}/', ' ', $str);
	return $str;
}
function HandleMessage($type, $rawMessageSplit) {
	global $reqQQNumber, $reqGroupNumber, $sendMessageBuffer, $commandName, $commandContent, $commandArr;
	// $type/0:好友消息, 1:群组消息
	if (count($rawMessageSplit) > 0) {
		$messageSplit=array_filter($rawMessageSplit, 'MatchCommandPrefix');
	}
	if (count($messageSplit) < 1) {
		return;
	}
	foreach ($messageSplit as $message) {
		$message=substr(TrimMultiSpace(trim($message)), 1);
		$commandSplitArg=explode(' ', $message, 2);
		$commandName=$commandSplitArg[0];
		if (is_file("commands/10/{$commandName}.php")) {
			$commandType=10;
		} elseif (is_file("commands/{$type}/{$commandName}.php")) {
			$commandType=$type;
		} else {
			continue;
		}
		switch (CheckCommandBlacklist($commandName)) {
			case 2:
				die();
			case 1:
				continue;
			case 0:
			default:
				break;
		}
		if (count($commandSplitArg) > 1) {
			$commandContent=$commandSplitArg[1];
			if (count($commandSplitArg) > 2) {
				$commandArr=explode(' ', $commandContent);
			}
		}
		require_once("commands/{$commandType}/{$commandName}.php");
	}
	if (!empty($sendMessageBuffer)) {
		$sendMessageBuffer=trim($sendMessageBuffer);
		$sendMessageBufferSplit=str_split($sendMessageBuffer,3000);
		foreach ($sendMessageBufferSplit as $sendMessageContent) {
			switch ($type) {
				case 1:
					sendGroupMessage($reqGroupNumber, $sendMessageContent);
					break;
				case 0:
					sendMessage($reqQQNumber, $sendMessageContent);
					break;
				default:
					break;
			}
		}
	}
}
$sendMessageBuffer='';
$rawMessageSplit=explode("\r",$reqRawMessage);
switch ($reqJSONArr->sub_type) {
	case 'friend':
		HandleMessage(0, $rawMessageSplit);
		break;
	case 'group':
	case 'discuss':
		//HandleMessage(2,$messages);
		break;
	case 'normal':
		HandleMessage(1, $rawMessageSplit);
		break;
	default:
		break;
}
?>
