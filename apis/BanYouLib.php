<?php
global $conn, $lang;
function GetPlayerRankByUserID(int $mode, int $userid): int {
	global $conn, $userStatsTable;
	setGameMode($mode);
	$scoreType = ($mode === 2) ? 'ranked_score' : 'rank_score';
	$stmt = $conn->prepare("SELECT {$scoreType} FROM {$userStatsTable} WHERE user_id = ? LIMIT 1");
	if (!$stmt) {
		return 0;
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
		$stmt = $conn->prepare("SELECT count(*)+1 FROM {$userStatsTable} us JOIN osu_users u USING (user_id) WHERE us.user_id ! =  ? AND us.{$scoreType} > ? AND NOT EXISTS (SELECT 1 FROM osu_user_banhistory WHERE user_id = us.user_id LIMIT 1)");
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
function GetUserIDByUsername(string $username): int {
	global $conn;
	$stmt = $conn->prepare("SELECT user_id FROM osu_users WHERE username = ? LIMIT 1");
	if (!$stmt) {
		return 0;
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
function isBindID(int $qqNumber, string &$text): string {
	global $lang;
	$username = GetUsernameByQQ($qqNumber);
	if (empty($username)) {
		$text .= "{$lang['need_bindid']}\n";
		return '';
	}
	return $username;
}
function GetUsernameByQQ(int $qqNumber): string {
	global $conn;
	if (!empty($qqNumber)) {
		if (is_numeric($qqNumber)) {
			$stmt = $conn->prepare('SELECT username FROM osu_users WHERE user_qq = ? LIMIT 1');
			if ($stmt->bind_param('i', $qqNumber) && $stmt->execute() && $stmt->bind_result($username)) {
				if (!$stmt->fetch()) {
					$stmt->close();
					return 0;
				}
				$stmt->close();
			}
		}
		if (!empty($username)) {
			return $username;
		}
	}
	return '';
}
function GetQQByUsername(string $username): int {
	global $conn;
	if (!empty($username)) {
		$stmt = $conn->prepare('SELECT user_qq FROM osu_users WHERE username = ? LIMIT 1');
		if ($stmt->bind_param('s', $username) && $stmt->execute() && $stmt->bind_result($QQ)) {
			if (!$stmt->fetch()) {
				$stmt->close();
				return 0;
			}
			$stmt->close();
		}
		if (!empty($QQ)) {
			return $QQ;
		}
	}
	return 0;
}
?>
