<?php
function parseResult($json): array {
	if ($json === false) {
		return (array)null;
	}
	$result = toArray($json);
	if (!(!empty($result) && isset($result['retcode']) && $result['retcode'] === 0)) {
		return (array)null;
	}
	return $result;
}
function sendGroupMessage(int $groupNumber, string $message) {
	$message = rawurlencode($message);
	$status = @file_get_contents(APIURL . "/send_group_msg_async?group_id={$groupNumber}&message={$message}");
	$result = parseResult($status);
	return (!empty($result)) ? $result : false;
}
function sendMessage(int $qqNumber, string $message) {
	$message = rawurlencode($message);
	$status = @file_get_contents(APIURL . "/send_private_msg_async?user_id={$qqNumber}&message={$message}");
	$result = parseResult($status);
	return (!empty($result)) ? $result : false;
}
function sendTempMessage(int $groupNumber, int $qqNumber, string $message): bool {
}
function DeleteMessage(int $msg_id): bool {
	$status = @file_get_contents(APIURL . "/delete_msg_async?message_id={$msg_id}");
	$result = parseResult($status);
	return (!empty($result)) ? true : false;
}
function decodeCQCode(string $str): string {
	return str_replace(['&amp;', '&#91;', '&#93;', '&#44;'], ['&', '[', ']', ','], $str);
}
function Silence(int $groupNumber, int $qqNumber, int $silenceTime): bool {
	// 单位为秒
	$status = ($qqNumber === 0) ? @file_get_contents(APIURL . "/set_group_whole_ban?group_id={$groupNumber}&enable=" . (($silenceTime === 1) ? 'true' : 'false')) : @file_get_contents(APIURL . "/set_group_ban_async?group_id={$groupNumber}&user_id={$qqNumber}&duration={$silenceTime}");
	$result = parseResult($status);
	return (!empty($result)) ? true : false;
}
function Kick(int $groupNumber, int $qqNumber): bool {
	$status = @file_get_contents(APIURL . "/set_group_kick_async?group_id={$groupNumber}&user_id={$qqNumber}");
	$result = parseResult($status);
	return (!empty($result)) ? true : false;
}
function Announce(string $str, int $qqNumber = 0) {
	$str = trim($str);
	foreach (groupNumberList as $value) {
		if ($value === devGroupNumber || ($qqNumber !== 0 && (in_array($value, disableNotificationGroupNumberList) || !isInGroup($value, $qqNumber)))) {
			continue;
		}
		sendGroupMessage($value, $str);
	}
}
function Debug(string $str) {
	$str = trim($str);
	sendGroupMessage(devGroupNumber, $str);
}
function ChangeCard(int $groupNumber, int $qqNumber, string $card): bool {
	$card = rawurlencode($card);
	$status = @file_get_contents(APIURL . "/set_group_card_async?group_id={$groupNumber}&user_id={$qqNumber}&card={$card}");
	$result = parseResult($status);
	return (!empty($result)) ? true : false;
}
function isAT(string $str): int {
	if (preg_match('/^\[CQ:at,qq=(\d*)\]$/', $str, $matches)) {
		return (int)$matches[1];
	}
	return 0;
}
function isATorQQ(string $str): int {
	return (is_numeric($str)) ? (int)$str : isAT($str);
}
function isVaildQQ(int $qqNumber): bool {
	if ($qqNumber === 0 || strlen($qqNumber) > 11 || strlen($qqNumber) < 5) {
		return false;
	}
	return true;
}
function GetRandomNumber(int $maxNumber, int $minNumber = 1): int {
	$maxRandomNumber = mt_getrandmax();
	if ($maxNumber > $maxRandomNumber) {
		$maxNumber = $maxRandomNumber;
	} elseif ($maxNumber < 1) {
		return 1;
	}
	if ($minNumber > $maxNumber) {
		$minNumber = $maxNumber;
	}
	$randomNumber = mt_rand($minNumber, $maxNumber);
	return $randomNumber;
}
function isInGroup(int $groupNumber, int $qqNumber): bool {
	$status = @file_get_contents(APIURL . "/get_group_member_info?group_id={$groupNumber}&user_id={$qqNumber}");
	$result = parseResult($status);
	if (!empty($result)) {
		return true;
	}
	return false;
}
function SetGroupSpecialTitle(int $groupNumber, int $qqNumber, string $specialTitle): bool {
	$specialTitle = rawurlencode($specialTitle);
	$status = @file_get_contents(APIURL . "/set_group_special_title_async?group_id={$groupNumber}&user_id={$qqNumber}&special_title={$specialTitle}");
	$result = parseResult($status);
	return (!empty($result)) ? true : false;
}
?>
