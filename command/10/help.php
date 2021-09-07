<?php
global $lang, $commandhelp;
if (!defined('BotFramework')) {
	return;
}
$allowCheckAdmin = false;
if (isset($commandFullContent)) {
	if ($commandFullContent == 1 && ($isMaster || $isFakeAdmin)) {
		$allowCheckAdmin = true;
	} elseif (isset($commandhelp[$commandFullContent]) && (is_file("command/10/{$commandFullContent}.php") || is_file("command/{$messageType}/{$commandFullContent}.php"))) {
		$commandKey = $commandFullContent;
	}
}
#$sendMessageBuffer .= "messageCount: {$messagecount}, allowCheckAdmin: {$allowCheckAdmin}, commandKey: {$commandKey}.";
if (in_array($reqGroupNumber, byPrefixGroupNumberList)) {
	$sendMessageBuffer .= $lang['attention:command_requires_prefix_in_this_group'] . "\n";
}
foreach ($commandhelp as $key => $value) {
	if (!CheckCommandBlacklist($key, $allowCheckAdmin) && (is_file("command/10/{$key}.php") || is_file("command/{$messageType}/{$key}.php"))) {
		if (isset($commandKey)) {
			if ($commandKey != $key) {
				continue;
			} elseif (count($commandhelp[$commandKey]) != count($commandhelp[$commandKey],1)) {
				foreach ($commandhelp[$commandKey] as $value) {
					$sendMessageBuffer .= "{$value[0]} - {$value[1]}\n";
				}
				break;
			}
		}
		if (!isset($value[0])) {
			$sendMessageBuffer .= "!{$key}\n";
		} else {
			$sendMessageBuffer .= "{$value[0]} - {$value[1]}\n";
		}
	}
}
?>
