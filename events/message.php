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
function HandleMessage($messageType, $rawMessageSplit) {
	global $conn, $reqQQNumber, $reqGroupNumber, $commandName, $commandContent, $commandFullContent, $commandArr, $commandSubType;
	// $messageType/0:好友消息, 1:群组消息
	if (count($rawMessageSplit) > 0) {
		$messageSplit=array_filter($rawMessageSplit, 'MatchCommandPrefix');
	}
	if (count($messageSplit) < 1) {
		return;
	}
	if (!isset($sendMessageBuffer)) {
		$sendMessageBuffer='';
	}
	foreach ($messageSplit as $message) {
		$message=substr(TrimMultiSpace(trim($message)), 1);
		$commandSplitArg=explode(' ', $message, 3);
		$commandName=$commandSplitArg[0];
		if (strtolower($commandName) !== 'bansay' && isBanSay()) {
			continue;
		}
		if (is_file("commands/10/{$commandName}.php")) {
			$commandType=10;
		} elseif (is_file("commands/{$messageType}/{$commandName}.php")) {
			$commandType=$messageType;
		} else {
			continue;
		}
		switch (CheckCommandBlacklist($commandName)) {
			case 2:
				die();
			case 1:
				continue 2;
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
		unset($commandSplitArg, $commandName, $commandSubType, $commandFullContent, $commandArr, $commandFullContent);
	}
	if (!empty($sendMessageBuffer)) {
		$sendMessageBuffer=trim($sendMessageBuffer);
		$sendMessageBufferSplit=str_split($sendMessageBuffer, 3000);
		foreach ($sendMessageBufferSplit as $sendMessageContent) {
			switch ($messageType) {
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
$rawMessageSplit=explode("\n", $reqRawMessage);
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
