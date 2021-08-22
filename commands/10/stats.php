<?php
global $lang, $userStatsTable, $modeName;
if (!isset($commandFullContent)) {
	$sendMessageBuffer.="{$lang['no_username_provide']}\n";
	return;
}
$mode=$commandArr[count($commandArr)-1];
$username=$commandFullContent;
if (is_numeric($mode) && $mode <= 3 && $mode >= 0) {
	unset($commandArr[count($commandArr)-1]);
	$username=$commandSubType;
	if (count($commandArr) > 0) {
		$username.=' '.implode(' ',$commandArr);
	}
	$mode=(int)$mode;
} else {
	$mode=0;
}
$userid=(int)GetUserIDByUsername($username);
if (!$userid) {
	$sendMessageBuffer.="{$lang['user_not_found']}\n";
	return;
}
$rank=GetPlayerRankByUserID($mode,$userid);
if (!$rank) {
	$sendMessageBuffer.="{$lang['no_play_records']}\n";
	return;
}
setGameMode($mode);
$res=$conn->query("SELECT ranked_score, playcount, level, accuracy FROM {$userStatsTable} WHERE user_id = {$userid} LIMIT 1");
list($score,$playcount,$level,$accuracy)=$res->fetch_row();
$score=number_format($score);
$level=floor($level);
$accuracy*=100;
$sendMessageBuffer.="Stats for {$username} ({$modeName}):\n";
$sendMessageBuffer.="Score: {$score} (#{$rank})\n";
$sendMessageBuffer.="Plays: {$playcount} (lv{$level})\n";
$sendMessageBuffer.="Accuracy: {$accuracy}%\n";
?>
