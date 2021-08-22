<?php
global $reqJSONArr;
$maxNumber=100;
if (isset($commandFullContent) && is_numeric($commandFullContent)) {
	$maxNumber=$commandFullContent;
}
$randomNumber=GetRandomNumber($maxNumber);
$username=GetUsernameByQQ($reqQQNumber);
if (!$username) {
	$username=($messageType === 1) ? "[CQ:at,qq={$reqQQNumber}]" : (isset($reqJSONArr->sender->nickname) ? $reqJSONArr->sender->nickname : $reqQQNumber);
}
$sendMessageBuffer.="{$username} rolls {$randomNumber} point(s).\n";
?>
