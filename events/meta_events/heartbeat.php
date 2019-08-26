<?php
return;
function CheckEvent() {
	global $conn,$groupNumberList,$devGroupNumber,$mainGroupNumber,$scoreTable,$highScoreTable;
	if (file_exists('lastEventID')) {
		$lastEventIDFile=fopen('lastEventID','r+');
		flock($lastEventIDFile,LOCK_EX);
		$lastEventID=fgets($lastEventIDFile);
		$eventList=$conn->queryAll("SELECT e.id, e.mode as mode, m.modename as modename, e.user_id as user_id, u.username as username, e.beatmap_id as beatmap_id, b.beatmapset_id as beatmapset_id, e.text as ranknumber, CONCAT(IF(b.artist != '',CONCAT(b.artist,' - ',b.title),b.title)) as beatmap_name, b.version as version, b.hit_length as hit_length, b.total_length as total_length, REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(et.`zh-ubbrule`,'{user_id}',e.user_id),'{username}',u.username),'{text}',e.text),'{beatmap_id}',e.beatmap_id),'{mode}',e.mode),'{artist}',IF(b.artist != '',CONCAT(b.artist,' - '),'')),'{title}',b.title),'{version}',b.version),'{modename}',m.modename) as text FROM osu_events e JOIN osu_users u USING (user_id) JOIN osu_beatmaps b USING (beatmap_id) JOIN osu_events_type et USING (type) JOIN osu_modes m ON m.id = e.mode WHERE e.type = 1 AND e.id > {$lastEventID} ORDER BY e.id");
		if (count($eventList) < 1) {
			flock($lastEventIDFile, LOCK_UN);
			fclose($lastEventIDFile);
			return;
		}
		foreach ($eventList as $value) {
			setGameMode($value['mode']);
			list($scoreID,$rank,$modsnumber,$finalpp,$score)=$conn->queryRow("SELECT score_id, rank, enabled_mods, pp, score FROM {$highScoreTable} WHERE user_id = {$value['user_id']} AND beatmap_id = {$value['beatmap_id']} LIMIT 1",1);
			$pp=$conn->queryOne("SELECT pp FROM {$scoreTable} WHERE score_id = {$scoreID} LIMIT 1");
			$rank=str_replace('H','+Hidden',str_replace('X','SS',$rank));
			if ($value['mode'] == 2) {
				$fullpptext="{$lang['score']}{$lang['colon']}{$score}";
			} else {
				$pp=sprintf('%.2f',$pp);
				$finalpp=sprintf('%.2f',$finalpp);
				$fullpptext="{$pp}pp({$finalpp}pp)";
			}
			$value['text']=str_replace('{score_id}',$scoreID,$value['text']);
			$value['text']=str_replace('{username}',$value['username'],$value['text']);
			$QQNumber=0;
			//$QQNumber=GetQQByUsername($value['username']);
			$value['text']=str_replace('{display_username}',($QQNumber !== 0 ? "[CQ:at,qq={$QQNumber}]" : $value['username']),$value['text']);
			$value['text']=str_replace('{ue_username}',rawurlencode($value['username']),$value['text']);
			$value['text']=str_replace('{rank}',$rank,$value['text']);
			$value['text']=str_replace('{pporscore}',$fullpptext,$value['text']);
			$value['text']=str_replace('{ranknumber}',$value['ranknumber'],$value['text']);
			$value['text']=str_replace('{beatmap_id}',$value['beatmap_id'],$value['text']);
			$value['text']=str_replace('{beatmapset_id}',$value['beatmapset_id'],$value['text']);
			$value['text']=str_replace('{beatmap_name}',$value['beatmap_name'],$value['text']);
			$value['text']=str_replace('{version}',$value['version'],$value['text']);
			$value['text']=str_replace('{mode}',$value['mode'],$value['text']);
			$value['text']=str_replace('{modename}',$value['modename'],$value['text']);
			$value['text']=str_replace('{hit_length}',$value['hit_length'],$value['text']);
			$value['text']=str_replace('{total_length}',$value['total_length'],$value['text']);
			$value['text']=str_replace('{mods}',getShortModString($modsnumber,0),$value['text']);
			foreach ($groupNumberList as $tmpNumber) {
				if ($tmpNumber == $devGroupNumber) {
					continue;
				}
				sendGroupMessage($tmpNumber, $value['text']);
			}
		}
	}
	$lastEventID=$conn->queryOne("SELECT id FROM osu_events ORDER BY id DESC LIMIT 1");
	rewind($lastEventIDFile);
	fwrite($lastEventIDFile, $lastEventID);
	flock($lastEventIDFile, LOCK_UN);
	fclose($lastEventIDFile);
}
CheckEvent();
?>
