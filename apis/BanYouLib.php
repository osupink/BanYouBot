<?php
global $conn, $lang;
function GetPlayerRankByUserID($mode, $userid) {
	global $conn, $userStatsTable;
	$mode=(int) $mode;
	setGameMode($mode);
	$scoreType=($mode == 2) ? 'ranked_score' : 'rank_score';
	$playerPPScore=$conn->queryOne("SELECT {$scoreType} FROM {$userStatsTable} WHERE user_id={$userid} LIMIT 1");
	if (!empty($playerPPScore)) {
		$rank=$conn->queryOne("SELECT count(*)+1 FROM {$userStatsTable} us JOIN osu_users u USING (user_id) WHERE us.user_id != {$userid} AND us.{$scoreType} > {$playerPPScore} AND NOT EXISTS (SELECT 1 FROM osu_user_banhistory WHERE user_id=us.user_id LIMIT 1)");
		if (!empty($rank)) {
			return $rank;
		}
	}
	return 0;
}
function GetUserIDByUsername($username) {
	global $conn;
	$username=sqlstr($username);
	$userid=$conn->queryOne("SELECT user_id FROM osu_users WHERE username='{$username}' LIMIT 1");
	if ($userid !== 0) {
		return $userid;
	}
	return 0;
}
function isBindID($QQNumber, &$text) {
	global $lang;
	$username=GetUsernameByQQ($QQNumber);
	if (!$username) {
		$text.="{$lang['need_bindid']}\n";
		return 0;
	}
	return $username;
}
function GetUsernameByQQ($QQNumber) {
	global $conn;
	if (!empty($QQNumber)) {
		if (is_numeric($QQNumber)) {
			$username=$conn->queryOne("SELECT username FROM osu_users WHERE user_qq={$QQNumber} LIMIT 1");
		}
		if (!empty($username)) {
			return $username;
		}
	}
	return 0;
}
function GetQQByUsername($username) {
	global $conn;
	if (!empty($username)) {
		$username=sqlstr($username);
		$QQ=$conn->queryOne("SELECT user_qq FROM osu_users WHERE username='{$username}' LIMIT 1");
		if (!empty($QQ)) {
			return $QQ;
		}
	}
	return 0;
}
?>
