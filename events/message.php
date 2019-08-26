<?php
if (!defined('BotFramework')) {
	die();
}
if (!isset($reqRawMessage)) {
	return;
}
global $reqRawMessage;
function MatchCommandPrefix($str) {
	return (substr($str, 0, 1) === '!') ? true : false;
}
function TrimMultiSpace($str) {
	$str=preg_replace('/ {2,}/', ' ', $str);
	return $str;
}
function HandleMessage($type, $rawMessageSplit) {
	global $sendMessageBuffer, $commandName, $commandContent, $commandArr;
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
		if (is_file("commands/3/{$commandName}.php")) {
			$commandType=3;
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
