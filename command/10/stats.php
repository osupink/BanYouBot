<?php
global $lang, $userStatsTable, $modeName;
if (!defined('BotFramework')) {
	return;
}
if (!isset($commandFullContent)) {
	$sendMessageBuffer .= "{$lang['no_username_provide']}\n";
	return;
}
$mode = 0;
$username = $commandFullContent;
if (isset($commandArr) && is_numeric($commandArr[count($commandArr)-1])) {
	$mode = (int)$commandArr[count($commandArr)-1];
	if ($mode < 0 || $mode > 3) {
		$mode = 0;
	} else {
		unset($commandArr[count($commandArr)-1]);
		$username = $commandSubType;
		if (count($commandArr) > 0) {
			$username .= ' ' . implode(' ', $commandArr);
		}
	}
}
$userid=GetUserIDByUsername($username);
if ($userid === 0) {
	$sendMessageBuffer .= "{$lang['user_not_found']}\n";
	return;
}
$rank = GetPlayerRankByUserID($mode,$userid);
if ($rank === 0) {
	$sendMessageBuffer .= "{$lang['no_play_records']}\n";
	return;
}
setGameMode($mode);
$res = $conn->query("SELECT u.username, us.rank_score, us.ranked_score, us.playcount, us.level, us.accuracy FROM {$userStatsTable} us JOIN osu_users u USING (user_id) WHERE us.user_id = {$userid} LIMIT 1");
list($username, $pp, $score, $playcount, $level, $accuracy)=$res->fetch_row();
$score = number_format($score);
$level = floor($level);
$accuracy *= 100;
$sendMessageBuffer .= "https://user." . BanYouDomain . "/" . rawurlencode($username). "\n";
$sendMessageBuffer .= "Stats for {$username} ({$modeName}):\n";
if ($pp > 0) {
	$sendMessageBuffer .= "PP: {$pp}, ";
}
$sendMessageBuffer .= "Score: {$score} (#{$rank})\n";
$sendMessageBuffer .= "Plays: {$playcount} (lv{$level})\n";
$sendMessageBuffer .= "Accuracy: {$accuracy}%\n";
?>
