<?php
global $conn, $lang;
function GetPlayerRankByUserID($mode, $userid) {
	global $conn, $userStatsTable;
	$mode=(int)$mode;
	setGameMode($mode);
	$scoreType=($mode == 2) ? 'ranked_score' : 'rank_score';
	$stmt=$conn->prepare("SELECT {$scoreType} FROM {$userStatsTable} WHERE user_id = ? LIMIT 1");
	if (!$stmt) {
		return;
	}
	$stmt->bind_param('i', $userid);
	$stmt->execute();
	$stmt->bind_result($playerPPScore);
	if (!$stmt->fetch()) {
		$stmt->close();
		return 0;
	}
	$stmt->close();
	if (!empty($playerPPScore)) {
		$stmt=$conn->prepare("SELECT count(*)+1 FROM {$userStatsTable} us JOIN osu_users u USING (user_id) WHERE us.user_id != ? AND us.{$scoreType} > ? AND NOT EXISTS (SELECT 1 FROM osu_user_banhistory WHERE user_id = us.user_id LIMIT 1)");
		if (!$stmt) {
			return 0;
		}
		$stmt->bind_param('id', $userid, $playerPPScore);
		$stmt->execute();
		$stmt->bind_result($rank);
		if (!$stmt->fetch()) {
			$stmt->close();
			return 0;
		}
		$stmt->close();
		if (!empty($rank)) {
			return $rank;
		}
	}
	return 0;
}
function GetUserIDByUsername($username) {
	global $conn;
	$stmt=$conn->prepare("SELECT user_id FROM osu_users WHERE username = ? LIMIT 1");
	if (!$stmt) {
		return;
	}
	$stmt->bind_param('s', $username);
	$stmt->execute();
	$stmt->bind_result($userid);
	if (!$stmt->fetch()) {
		$stmt->close();
		return 0;
	}
	$stmt->close();
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
			$stmt=$conn->prepare('SELECT username FROM osu_users WHERE user_qq = ? LIMIT 1');
			if ($stmt->bind_param('i', $QQNumber) && $stmt->execute() && $stmt->bind_result($username)) {
				if (!$stmt->fetch()) {
					$stmt->close();
					return 0;
				}
				$stmt->close();
			} else {
				$dbError='Unknown.';
				if ($stmt) {
					$dbError=$stmt->error;
					$stmt->close();
				}
				trigger_error("Database Error: {$dbError}", E_USER_WARNING);
				return;
			}
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
