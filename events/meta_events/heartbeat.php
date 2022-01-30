<?php
if (!defined('BotFramework')) {
	return;
}
function CheckEvent() {
	global $conn, $lang, $scoreTable, $highScoreTable;
	if (file_exists('lastEventID')) {
		$lastEventIDFile = fopen('lastEventID', 'r+');
		flock($lastEventIDFile, LOCK_EX);
		$lastEventID = (int)fgets($lastEventIDFile);
		$res = $conn->query("SELECT e.id as eventID, e.mode as mode, m.modename as modename, b.difficultyrating as bmstar, e.user_id as user_id, u.username as username, u.user_qq as qqNumber, e.beatmap_id as beatmap_id, b.beatmapset_id as beatmapset_id, e.text as ranknumber, CONCAT(IF(b.artist != '',CONCAT(b.artist,' - ',b.title),b.title)) as beatmap_name, b.version as version, b.hit_length as hit_length, b.total_length as total_length, REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(et.`zh-ubbrule`,'{user_id}',e.user_id),'{username}',u.username),'{text}',e.text),'{beatmap_id}',e.beatmap_id),'{mode}',e.mode),'{artist}',IF(b.artist != '',CONCAT(b.artist,' - '),'')),'{title}',b.title),'{version}',b.version),'{modename}',m.modename) as text FROM osu_events e JOIN osu_users u USING (user_id) JOIN osu_beatmaps b USING (beatmap_id) JOIN osu_events_type et USING (type) JOIN osu_modes m ON m.id = e.mode WHERE e.type = 1 AND e.id > {$lastEventID} ORDER BY e.id");
		$eventList = $res->fetch_all(MYSQLI_ASSOC);
		foreach ($eventList as $event) {
			extract($event);
			setGameMode($mode);
			$stmt = $conn->prepare("SELECT score_id, rank, enabled_mods, pp, score FROM {$highScoreTable} WHERE user_id = ? AND beatmap_id = ? LIMIT 1");
			if ($stmt->bind_param('ii', $user_id, $beatmap_id) && $stmt->execute() && $stmt->bind_result($scoreID, $rank, $modsnumber, $finalpp, $score) && $stmt->fetch()) {
				$stmt->close();
			} else {
				$dbError = 'Unknown.';
				if ($stmt) {
					$dbError = $stmt->error;
					$stmt->close();
				}
				trigger_error("Database Error: {$dbError}", E_USER_WARNING);
				return;
			}
			$stmt = $conn->prepare("SELECT pp FROM {$scoreTable} WHERE score_id = ? LIMIT 1");
			if ($stmt->bind_param('i', $scoreID) && $stmt->execute() && $stmt->bind_result($pp) && $stmt->fetch()) {
				$stmt->close();
			} else {
				$pp=0;
			}
			$rank = str_replace(array('H', 'X'), array('+Hidden', 'SS'), $rank);
			if ($mode == 2) {
				$fullpptext = "{$lang['score']}{$lang['colon']}{$score}";
			} else {
				$pp = sprintf('%.2f', $pp);
				$finalpp = sprintf('%.2f', $finalpp);
				$fullpptext = "{$pp}pp({$finalpp}pp)";
			}
			#$text = str_replace('{display_username}',($qqNumber !== 0 ? "[CQ:at,qq={$qqNumber}]" : $username),$text);
			$text = str_replace(array('{score_id}', '{username}', '{display_username}', '{ue_username}', '{rank}', '{pporscore}', '{ranknumber}', '{beatmap_id}', '{beatmapset_id}', '{beatmap_name}', '{version}', '{mode}', '{modename}', '{bmstar}', '{hit_length}', '{total_length}', '{mods}'), array($scoreID, $username, $username, rawurlencode($username), $rank, $fullpptext, $ranknumber, $beatmap_id, $beatmapset_id, $beatmap_name, $version, $mode, $modename, round($bmstar,2), $hit_length, $total_length, getShortModString($modsnumber, 0)),$text);
			foreach (groupNumberList as $groupNumber) {
				if (in_array($groupNumber, disableNotificationGroupNumberList)) {
					continue;
				}
				if ($groupNumber !== mainGroupNumber && !isInGroup($groupNumber, $qqNumber)) {
					continue;
				}
				for ($i=0; $i<3; $i++) {
					if (sendGroupMessage($groupNumber, $text) !== false) {
						break;
					}
				}
			}
			$latestEventID = $eventID;
		}
		if (isset($latestEventID)) {
			rewind($lastEventIDFile);
			fwrite($lastEventIDFile, $latestEventID);
		}
		flock($lastEventIDFile, LOCK_UN);
		fclose($lastEventIDFile);
	}
}
function CheckDeleteMessage() {
	$cacheFileDir = RootPath . ReplacePathSeparator("/cache");
	if (!is_dir($cacheFileDir)) {
		return;
	}
	foreach (glob($cacheFileDir . ReplacePathSeparator("/messages/*.txt")) as $filePath) {
		if ((int)file_get_contents($filePath) >= time()) {
			continue;
		}
		unlink($filePath);
		for ($i=0; $i<3; $i++) {
			if (DeleteMessage(basename($filePath, '.txt')) !== false) {
				break;
			}
		}
	}
}
CheckEvent();
CheckDeleteMessage();
?>
