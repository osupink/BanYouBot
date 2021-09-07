<?php
if (!defined('BotFramework')) {
	return;
}
$maxNumber = 100;
if (isset($commandFullContent) && is_numeric($commandFullContent)) {
	$maxNumber = $commandFullContent;
}
$randomNumber = GetRandomNumber($maxNumber);
$username = GetUsernameByQQ($reqQQNumber);
if (empty($username)) {
	$username=($messageType === 1) ? "[CQ:at,qq={$reqQQNumber}]" : (isset($reqQQNickname) ? $reqQQNickname : $reqQQNumber);
}
$sendMessageBuffer .= "{$username} rolls {$randomNumber} point(s).\n";
?>
