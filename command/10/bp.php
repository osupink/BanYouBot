<?php
global $lang, $commandhelp, $scoreTable, $highScoreTable;
if (!defined('BotFramework')) {
	return;
}
if (!isset($commandFullContent)) {
	$sendMessageBuffer .= "{$lang['usage']}{$lang['colon']}{$commandhelp['bp'][0]}.\n";
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
setGameMode($mode);
$username = $conn->real_escape_string($username);
$res = $conn->query("SELECT u.username, sh.date, sh.rank, sh.beatmap_id, CONCAT(IF(b.artist != '',CONCAT(b.artist,' - ',b.title),b.title),' [',b.version,']') AS beatmap_name, s.pp, sh.pp as bp_pp, sh.score, sh.enabled_mods as mods FROM osu_users u JOIN $highScoreTable sh USING (user_id) JOIN $scoreTable s USING (score_id) LEFT JOIN osu_beatmaps b ON b.beatmap_id = sh.beatmap_id WHERE u.username = '{$username}' ORDER BY sh.pp DESC, s.pp DESC LIMIT 10");
$beatmapList = $res->fetch_all(MYSQLI_ASSOC);
if (count($beatmapList) < 1) {
	$sendMessageBuffer .= "{$lang['have_not_bp']}\n";
	return;
}
$count = 1;
foreach ($beatmapList as $value) {
	if ($count === 1) {
		$sendMessageBuffer .= "{$value['username']} {$lang['of']}{$lang['userpage']}{$lang['colon']}https://user.".BanYouDomain."/".rawurlencode($value['username']).(($mode > 0) ? "?m={$mode}" : "")."\n";
	}
	$mods = getShortModString($value['mods'],1);
	$beatmapLink = "https://osu.ppy.sh/b/{$value['beatmap_id']}";
	if ($mode > 0) {
		$beatmapLink .= "?m={$mode}";
	}
	$value['pp'] = sprintf('%.2f',$value['pp']);
	$value['bp_pp'] = sprintf('%.2f',$value['bp_pp']);
	$value['rank'] = str_replace('H','+Hidden',str_replace('X','SS',$value['rank']));
	$sendMessageBuffer .= "{$count}. {$value['date']} Rank:{$value['rank']} {$beatmapLink} ({$value['beatmap_name']})".(!empty($mods) ? " +{$mods} " : " ").(($mode != 2) ? "{$value['pp']}pp({$value['bp_pp']}pp)" : "Score:{$value['score']}")."\n";
	$count++;
}
?>
