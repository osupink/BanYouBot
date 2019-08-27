<?php
function sendGroupMessage($groupNumber, $message) {
	$message = rawurlencode($message);
	file_get_contents(APIURL . "/send_group_msg_async?group_id={$groupNumber}&message={$message}");
}
function sendMessage($qqNumber, $message) {
	$message = rawurlencode($message);
	file_get_contents(APIURL . "/send_private_msg_async?user_id={$qqNumber}&message={$message}");
}
function sendTempMessage($groupNumber, $qqNumber, $message) {
}
function decodeCQCode($str) {
	return str_replace(['&amp;', '&#91;', '&#93;', '&#44;'], ['&', '[', ']', ','], $str);
}
function Silence($groupNumber, $QQNumber, $silenceTime) {
	// 单位为秒
	file_get_contents(APIURL . "/set_group_ban_async?group_id={$groupNumber}&user_id={$QQNumber}&duration={$silenceTime}");
}
function Kick($groupNumber, $QQNumber) {
	file_get_contents(APIURL . "/set_group_kick_async?group_id={$groupNumber}&user_id={$QQNumber}");
}
function Announce($groupNumberList, $str) {
	foreach ($groupNumberList as $value) {
		sendGroupMessage($value, $str);
	}
}
function ChangeCard($groupNumber, $qqNumber, $card) {
	$card = rawurlencode($card);
	file_get_contents(APIURL . "/set_group_card_async?group_id={$groupNumber}&user_id={$qqNumber}&card={$card}");
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
