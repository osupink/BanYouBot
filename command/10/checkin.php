<?php
global $lang;
if (!defined('BotFramework')) {
	return;
}
$username = isBindID($reqQQNumber,$sendMessageBuffer);
if (empty($username)) {
	return;
}
$checkinType = 'Checkin';
//$randomMoney=(GetRandomNumber(100)/100)-(GetRandomNumber(50)/100);
$randomMoney = round(lcg_value(),2);
$res = $conn->query("SELECT 1 FROM osu_pay WHERE (type = 'Checkin' OR type = 'Checkin+') AND time >= CURDATE() AND qq = {$reqQQNumber}");
if ($res && $res->num_rows > 0) {
	if ($res->num_rows > 5) {
		$sendMessageBuffer .= "{$lang['max_repeat_checkin_count_has_been_reached']}\n";
		return;
	}
	$checkinType .= '+';
	#$randomMoney = 0.4-$randomMoney;
	$randomMoney = 0.6-$randomMoney;
} else {
	$randomMoney *= 2;
	$sendMessageBuffer .= $lang['first'];
}
AddMoneyEvent($checkinType,$reqQQNumber,$randomMoney);
$sendMessageBuffer .= "{$lang['checkin_succeed']}{$lang['comma']}{$lang['get']} {$randomMoney} BanCoin.\n";
?>
