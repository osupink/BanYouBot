<?php
if (!defined('BotFramework') || !isset($reqRawMessage)) {
	return;
}
global $commandName, $commandContent, $commandFullContent, $commandArr, $commandSubType, $reqGroupNumber, $sendMessageBuffer;
function MatchCommandPrefix(string $str): bool {
	return (substr(TrimMultiSpace(trim($str)), 0, 1) === '!') ? true : false;
}
function TrimMultiSpace(string $str): string {
	$str = preg_replace('/ {2,}/', ' ', $str);
	return $str;
}
$rawMessageSplit = explode("\n", $reqRawMessage);
// $messageType/0:好友消息, 1:群组消息
switch ($reqJSONArr->sub_type) {
	case 'friend':
		$messageType = 0;
		break;
	case 'group':
	case 'discuss':
		#$messageType = 2;
		break;
	case 'normal':
		$messageType = 1;
		break;
	default:
		break;
}
if (count($rawMessageSplit) > 0) {
	$messageSplit = array_filter($rawMessageSplit, 'MatchCommandPrefix');
}
if (count($messageSplit) < 1) {
	return;
}
if (!isset($sendMessageBuffer)) {
	$sendMessageBuffer = '';
}
foreach ($messageSplit as $message) {
	$message = substr(TrimMultiSpace(trim($message)), 1);
	$commandSplitArg = explode(' ', $message, 3);
	$commandName = $commandSplitArg[0];
	if (in_array($reqGroupNumber, byPrefixGroupNumberList)) {
		if ($commandName !== 'by') {
			continue;
		}
		array_splice($commandSplitArg, 0, 1);
		$commandName = $commandSplitArg[0];
	}
	if (strtolower($commandName) !== 'bansay' && isBanSay()) {
		continue;
	}
	if (is_file("commands/10/{$commandName}.php")) {
		$commandType = 10;
	} elseif (is_file("commands/{$messageType}/{$commandName}.php")) {
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
	} elseif (count($commandSplitArg) > 1) {
		$commandSubType = $commandSplitArg[1];
		$commandFullContent = $commandSubType;
	}
	AddDebugInfo("Loading commands/{$commandType}/{$commandName}.php...");
	require("commands/{$commandType}/{$commandName}.php");
	unset($commandSplitArg, $commandName, $commandSubType, $commandFullContent, $commandArr, $commandFullContent);
}
?>
