<?php
global $lang, $commandhelp, $reqQQNumber;
if (!isset($commandFullContent)) {
	$sendMessageBuffer.="{$lang['usage']}{$lang['colon']}{$commandhelp['bindid'][0]}.\n";
	return;
}
if (GetQQByUsername($commandFullContent) !== 0) {
	$sendMessageBuffer.="{$lang['username_has_been_bound']}\n";
	return;
}
if (GetUsernameByQQ($reqQQNumber) !== 0) {
	$sendMessageBuffer.="{$lang['qq_has_been_bound']}\n";
	return;
}
$othersql='';
$userArr=explode(':', $commandFullContent, 2);
if ((isset($isGroup) && $isGroup === 0) && count($userArr) > 1) {
	$username=$userArr[0];
	$password=md5($userArr[1]);
	$othersql='AND user_password = ?';
} else {
	$username=$commandFullContent;
}
$stmt=$conn->prepare("SELECT user_id FROM osu_users WHERE username = ? {$othersql} LIMIT 1");
if (!empty($othersql)) {
	if (!$stmt->bind_param('ss', $username, $password)) {
		$stmt->close();
		return;
	}
} else {
	if (!$stmt->bind_param('s', $username)) {
		$stmt->close();
		return;
	}
}
if ($stmt->execute() && $stmt->bind_result($userID)) {
	$stmt->fetch();
	$stmt->close();
}
if (empty($userID)) {
	$sendMessageBuffer.=rtrim($lang['user_not_found'],'.');
	if (isset($password)) {
		$sendMessageBuffer.=$lang['user_not_found_or_password+'];
	}
	$sendMessageBuffer.=".";
} elseif (isset($password)) {
	$conn->query("UPDATE osu_users SET user_qq = {$reqQQNumber} WHERE user_id = {$userID} LIMIT 1");
	$conn->query("DELETE FROM osu_tmpqq WHERE user_id = {$userID} OR tmp_qq = {$reqQQNumber} LIMIT 1");
	$sendMessageBuffer.=$lang['binding_success'];
} else {
	$conn->query("INSERT INTO osu_tmpqq VALUES ({$userID},{$reqQQNumber}) ON DUPLICATE KEY UPDATE tmp_qq=VALUES(tmp_qq)");
	$sendMessageBuffer.=$lang['binding_success'].sprintf($lang['binding_success+'],$reqQQNumber);
	$bindqqpath=RootPath.DIRECTORY_SEPARATOR."bindqq.png";
	$sendMessageBuffer.="\n[CQ:image,file=file:///{$bindqqpath}]";
}
$sendMessageBuffer.="\n";
?>
