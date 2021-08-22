<?php
if (!defined('BotFramework')) {
	die();
}
chdir(__DIR__);
function CheckEvent() {
	global $conn, $lang, $scoreTable, $highScoreTable;
	if (file_exists('lastEventID')) {
		$lastEventIDFile=fopen('lastEventID','r+');
		flock($lastEventIDFile,LOCK_EX);
		$lastEventID=(int)fgets($lastEventIDFile);
		$res=$conn->query("SELECT e.id as eventID, e.mode as mode, m.modename as modename, e.user_id as user_id, u.username as username, e.beatmap_id as beatmap_id, b.beatmapset_id as beatmapset_id, e.text as ranknumber, CONCAT(IF(b.artist != '',CONCAT(b.artist,' - ',b.title),b.title)) as beatmap_name, b.version as version, b.hit_length as hit_length, b.total_length as total_length, REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(et.`zh-ubbrule`,'{user_id}',e.user_id),'{username}',u.username),'{text}',e.text),'{beatmap_id}',e.beatmap_id),'{mode}',e.mode),'{artist}',IF(b.artist != '',CONCAT(b.artist,' - '),'')),'{title}',b.title),'{version}',b.version),'{modename}',m.modename) as text FROM osu_events e JOIN osu_users u USING (user_id) JOIN osu_beatmaps b USING (beatmap_id) JOIN osu_events_type et USING (type) JOIN osu_modes m ON m.id = e.mode WHERE e.type = 1 AND e.id > {$lastEventID} ORDER BY e.id");
		$eventList=$res->fetch_all(MYSQLI_ASSOC);
		foreach ($eventList as $event) {
			extract($event);
			setGameMode($mode);
			$stmt=$conn->prepare("SELECT score_id, rank, enabled_mods, pp, score FROM {$highScoreTable} WHERE user_id = ? AND beatmap_id = ? LIMIT 1");
			if ($stmt->bind_param('ii', $user_id, $beatmap_id) && $stmt->execute() && $stmt->bind_result($scoreID,$rank,$modsnumber,$finalpp,$score) && $stmt->fetch()) {
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
			$stmt=$conn->prepare("SELECT pp FROM {$scoreTable} WHERE score_id = ? LIMIT 1");
			if ($stmt->bind_param('i', $scoreID) && $stmt->execute() && $stmt->bind_result($pp) && $stmt->fetch()) {
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
			$rank=str_replace('H','+Hidden',str_replace('X','SS',$rank));
			if ($mode == 2) {
				$fullpptext="{$lang['score']}{$lang['colon']}{$score}";
			} else {
				$pp=sprintf('%.2f',$pp);
				$finalpp=sprintf('%.2f',$finalpp);
				$fullpptext="{$pp}pp({$finalpp}pp)";
			}
			$text=str_replace('{score_id}',$scoreID,$text);
			$text=str_replace('{username}',$username,$text);
			$QQNumber=0;
			//$QQNumber=GetQQByUsername($username);
			$text=str_replace('{display_username}',($QQNumber !== 0 ? "[CQ:at,qq={$QQNumber}]" : $username),$text);
			$text=str_replace('{ue_username}',rawurlencode($username),$text);
			$text=str_replace('{rank}',$rank,$text);
			$text=str_replace('{pporscore}',$fullpptext,$text);
			$text=str_replace('{ranknumber}',$ranknumber,$text);
			$text=str_replace('{beatmap_id}',$beatmap_id,$text);
			$text=str_replace('{beatmapset_id}',$beatmapset_id,$text);
			$text=str_replace('{beatmap_name}',$beatmap_name,$text);
			$text=str_replace('{version}',$version,$text);
			$text=str_replace('{mode}',$mode,$text);
			$text=str_replace('{modename}',$modename,$text);
			$text=str_replace('{hit_length}',$hit_length,$text);
			$text=str_replace('{total_length}',$total_length,$text);
			$text=str_replace('{mods}',getShortModString($modsnumber,0),$text);
			foreach (groupNumberList as $groupNumber) {
				if (in_array($groupNumber, disableNotificationGroupNumberList)) {
					continue;
				}
				sendGroupMessage($groupNumber, $text);
			}
			$latestEventID=$eventID;
		}
	}
	if (isset($latestEventID)) {
		rewind($lastEventIDFile);
		fwrite($lastEventIDFile, $latestEventID);
		flock($lastEventIDFile, LOCK_UN);
		fclose($lastEventIDFile);
	}
}
CheckEvent();
?>
