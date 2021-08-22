<?php
global $lang;
$username=isBindID($reqQQNumber,$sendMessageBuffer);
if (!$username) {
	return;
}
$checkinType='Checkin';
//$randomMoney=(GetRandomNumber(100)/100)-(GetRandomNumber(50)/100);
$randomMoney=round(lcg_value(),2);
$res=$conn->query("SELECT 1 FROM osu_pay WHERE type = 'Checkin' AND time >= CURDATE() AND qq = {$reqQQNumber} LIMIT 1");
if ($res && $res->num_rows > 0) {
	$checkinType.='+';
	$randomMoney=0.4-$randomMoney;
} else {
	$sendMessageBuffer.=$lang['first'];
}
AddMoneyEvent($checkinType,$reqQQNumber,$randomMoney);
$sendMessageBuffer.="{$lang['checkin_succeed']}{$lang['comma']}{$lang['get']} {$randomMoney} BanCoin.\n";
?>
