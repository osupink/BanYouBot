<?php
if (!defined('BotFramework')) {
	die();
}
if (!isset($reqRawMessage)) {
	return;
}
function MatchCommandPrefix($str) {
	return (substr(TrimMultiSpace(trim($str)), 0, 1) === '!') ? true : false;
}
function TrimMultiSpace($str) {
	$str=preg_replace('/ {2,}/', ' ', $str);
	return $str;
}
function HandleMessage($type, $rawMessageSplit) {
	global $conn, $reqQQNumber, $reqGroupNumber, $sendMessageBuffer, $commandName, $commandContent, $commandFullContent, $commandArr, $commandSubType;
	// $type/0:好友消息, 1:群组消息
	if (count($rawMessageSplit) > 0) {
		$messageSplit=array_filter($rawMessageSplit, 'MatchCommandPrefix');
	}
	if (count($messageSplit) < 1) {
		return;
	}
	foreach ($messageSplit as $message) {
		$message=substr(TrimMultiSpace(trim($message)), 1);
		$commandSplitArg=explode(' ', $message, 3);
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
		/*
		$commandArr: 不包括 SubType 的文本组分割(通常使用在更高的指令中)
		$commandContent: 不包括 SubType 的文本组(使用在二级指令中, 可能使用在更高的指令中)
		$commandFullContent: 包括 SubType 的文本组(使用在一级指令中)
		没有 Arr 和 Content 即说明只有一个参数(可能为 SubType), 这时, 不包括 Name 的完整内容就在 SubType 和 Content 内.
		而没有 SubType 和 FullContent 则说明没有参数(不包括 Name)
		*/
		if (count($commandSplitArg) > 2) {
			$commandSubType=$commandSplitArg[1];
			$commandFullContent="{$commandSplitArg[1]} {$commandSplitArg[2]}";
			$commandArr=explode(' ', $commandSplitArg[2]);
			$commandContent=$commandSplitArg[2];
		} elseif (count($commandSplitArg) > 1) {
			$commandSubType=$commandSplitArg[1];
			$commandFullContent=$commandSubType;
		}
		require("commands/{$commandType}/{$commandName}.php");
	}
	if (!empty($sendMessageBuffer)) {
		$sendMessageBuffer=trim($sendMessageBuffer);
		$sendMessageBufferSplit=str_split($sendMessageBuffer, 3000);
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
$rawMessageSplit=explode("\r", $reqRawMessage);
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
