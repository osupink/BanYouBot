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
$randomMoney = lcg_value();
$res = $conn->query("SELECT 1 FROM osu_pay WHERE (type = 'Checkin' OR type = 'Checkin+') AND time >= CURDATE() AND qq = {$reqQQNumber}");
if ($res && $res->num_rows > 0) {
	if ($res->num_rows > 3) {
		$sendMessageBuffer .= "{$lang['max_repeat_checkin_count_has_been_reached']}\n";
		return;
	}
	$checkinType .= '+';
	#$randomMoney = 0.4-$randomMoney;
	#$randomMoney = 0.6-$randomMoney;
	$randomMoney -= 0.55;
	#$randomMoney = round($randomMoney, 2);
} else {
	#$randomMoney *= 2;
	$randomMoney /= 2;
	$randomMoney += 1;
/*
	$newYearRandom = lcg_value();
	if ($newYearRandom < 0.01) {
		$randomMoney += 50;
	} elseif ($newYearRandom < 0.21) {
		$randomMoney += 5;
	}
*/
	$sendMessageBuffer .= $lang['first'];
}
$randomMoney = round($randomMoney, 2);
AddMoneyEvent($checkinType,$reqQQNumber,$randomMoney);
$sendMessageBuffer .= "{$lang['checkin_succeed']}{$lang['comma']}{$lang['get']} {$randomMoney} BanCoin.\n";
?>
