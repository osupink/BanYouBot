<?php
global $commandhelp, $isMaster, $isFakeAdmin;
$allowCheckAdmin=0;
if (isset($commandFullContent)) {
	if ($commandFullContent == 1 && ($isMaster || $isFakeAdmin)) {
		$allowCheckAdmin=1;
	} elseif (isset($commandhelp[$commandFullContent]) && (is_file("commands/10/{$commandFullContent}.php") || is_file("commands/{$messageType}/{$commandFullContent}.php"))) {
		$commandKey=$commandFullContent;
	}
}
#$sendMessageBuffer.="messageCount: {$messagecount}, allowCheckAdmin: {$allowCheckAdmin}, commandKey: {$commandKey}.";
foreach ($commandhelp as $key => $value) {
	if (!CheckCommandBlacklist($key,$allowCheckAdmin) && (is_file("commands/10/{$key}.php") || is_file("commands/{$messageType}/{$key}.php"))) {
		if (isset($commandKey)) {
			if ($commandKey != $key) {
				continue;
			} elseif (count($commandhelp[$commandKey]) != count($commandhelp[$commandKey],1)) {
				foreach ($commandhelp[$commandKey] as $value) {
					$sendMessageBuffer.="{$value[0]} - {$value[1]}\n";
				}
				break;
			}
		}
		if (!isset($value[0])) {
			$sendMessageBuffer.="!{$key}\n";
		} else {
			$sendMessageBuffer.="{$value[0]} - {$value[1]}\n";
		}
	}
}
?>
