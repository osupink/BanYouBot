<?php
function sendGroupMessage($groupNumber, $message) {
	$message = rawurlencode($message);
	$status=@file_get_contents(APIURL . "/send_group_msg_async?group_id={$groupNumber}&message={$message}");
	return ($status !== false) ? 1 : 0;
}
function sendMessage($qqNumber, $message) {
	$message = rawurlencode($message);
	$status=@file_get_contents(APIURL . "/send_private_msg_async?user_id={$qqNumber}&message={$message}");
	return ($status !== false) ? 1 : 0;
}
function sendTempMessage($groupNumber, $qqNumber, $message) {
}
function decodeCQCode($str) {
	return str_replace(['&amp;', '&#91;', '&#93;', '&#44;'], ['&', '[', ']', ','], $str);
}
function Silence($groupNumber, $QQNumber, $silenceTime) {
	// 单位为秒
	$status=@file_get_contents(APIURL . "/set_group_ban_async?group_id={$groupNumber}&user_id={$QQNumber}&duration={$silenceTime}");
	return ($status !== false) ? 1 : 0;
}
function Kick($groupNumber, $QQNumber) {
	$status=@file_get_contents(APIURL . "/set_group_kick_async?group_id={$groupNumber}&user_id={$QQNumber}");
	return ($status !== false) ? 1 : 0;
}
function Announce($str) {
	$str=trim($str);
	foreach (groupNumberList as $value) {
		if (!in_array($value, disableNotificationGroupNumberList)) {
			sendGroupMessage($value, $str);
		}
	}
}
function Debug($str) {
	$str=trim($str);
	sendGroupMessage(devGroupNumber, $str);
}
function ChangeCard($groupNumber, $qqNumber, $card) {
	$card = rawurlencode($card);
	$status=@file_get_contents(APIURL . "/set_group_card_async?group_id={$groupNumber}&user_id={$qqNumber}&card={$card}");
	return ($status !== false) ? 1 : 0;
}
function isAT($str) {
	if (preg_match('/^\[CQ:at,qq=(\d*)\]$/', $str, $matches)) {
		return (int) $matches[1];
	}
	return $str;
}
function GetRandomNumber($maxNumber) {
	$maxRandomNumber = mt_getrandmax();
	if ($maxNumber > $maxRandomNumber) {
		$maxNumber = $maxRandomNumber;
	}
	$randomNumber = mt_rand(1, $maxNumber);
	return $randomNumber;
}
?>
