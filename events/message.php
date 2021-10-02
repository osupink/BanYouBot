<?php
if (!defined('BotFramework') || !isset($reqRawMessage)) {
	return;
}
global $commandName, $commandContent, $commandFullContent, $commandArr, $commandSubType;
function MatchCommandPrefix(string $str): bool {
	return (substr(TrimMultiSpace(trim($str)), 0, 1) === '!') ? true : false;
}
function TrimMultiSpace(string $str): string {
	$str = preg_replace('/ {2,}/', ' ', $str);
	return $str;
}
$rawMessageSplit = explode("\n", $reqRawMessage);
// $messageType/0:好友消息, 1:群组消息
switch (strtolower($reqJSONArr->message_type)) {
	case 'group':
		switch (strtolower($reqJSONArr->sub_type)) {
			//case 'anonymous':
			case 'normal':
				$messageType = 1;
				break;
			case 'notice':
			default:
				return;
		}
		break;
	case 'private':
		switch (strtolower($reqJSONArr->sub_type)) {
			case 'friend':
			//case 'group':
				$messageType = 0;
				break;
			case 'other':
			case 'group_self':
			default:
				return;
		}
		break;
	default:
		return;
}
if (count($rawMessageSplit) > 0) {
	$messageSplit = ($messageType === 1) ? array_filter($rawMessageSplit, 'MatchCommandPrefix') : $rawMessageSplit;
}
if (!isset($messageSplit) || count($messageSplit) < 1 || !isset($messageType)) {
	return;
}
if (!isset($sendMessageBuffer)) {
	$sendMessageBuffer = '';
}
foreach ($messageSplit as $message) {
	if ($messageType === 1 || MatchCommandPrefix($message)) {
		$message = substr(TrimMultiSpace(trim($message)), 1);
	}
	$commandSplitArg = explode(' ', $message, 3);
	$commandName = $commandSplitArg[0];
	if (in_array($reqGroupNumber, byPrefixGroupNumberList)) {
		if (strtolower($commandName) !== 'by') {
			continue;
		}
		array_splice($commandSplitArg, 0, 1);
		if (count($commandSplitArg) > 1) {
			$reCommandSplitArg=explode(' ', $commandSplitArg[1], 2);
			unset($commandSplitArg[1]);
			$commandSplitArg=array_merge($commandSplitArg, $reCommandSplitArg);
		}
		$commandName = $commandSplitArg[0];
	}
	if (strtolower($commandName) !== 'bansay' && isBanSay()) {
		continue;
	}
	if (is_file("command/10/{$commandName}.php")) {
		$commandType = 10;
	} elseif (is_file("command/{$messageType}/{$commandName}.php")) {
		$commandType = $messageType;
	} else {
		continue;
	}
	switch (CheckCommandBlacklist($commandName)) {
		case 2:
			return;
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
		$commandSubType = $commandSplitArg[1];
		$commandFullContent = "{$commandSplitArg[1]} {$commandSplitArg[2]}";
		$commandArr = explode(' ', $commandSplitArg[2]);
		$commandContent = $commandSplitArg[2];
		AddDebugValue(array("commandArr" => $commandArr, "commandContent" => $commandContent));
	} elseif (count($commandSplitArg) > 1) {
		$commandSubType = $commandSplitArg[1];
		$commandFullContent = $commandSubType;
	}
	if (isset($commandSubType) && isset($commandFullContent)) {
		AddDebugValue(array("commandSubType" => $commandSubType, "commandFullContent" => $commandFullContent));
	}
	$commandPath = "command/{$commandType}/{$commandName}.php";
	AddDebugInfo("Loading {$commandPath}...");
	switch (strtolower($commandName)) {
		/*
		case "checkin":
		*/
		case "h":
			require_once($commandPath);
			break;
		default:
			require($commandPath);
			break;
	}
	unset($commandSplitArg, $commandName, $commandSubType, $commandFullContent, $commandArr, $commandFullContent);
}
?>
