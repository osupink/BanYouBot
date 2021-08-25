<?php
global $lang, $modeName, $scoreTable, $highScoreTable, $artist, $title, $bmversion, $bmstar, $hitLength, $totalTime;
if (!defined('BotFramework')) {
	return;
}
$mode = (isset($commandFullContent) && is_numeric($commandFullContent) && $commandFullContent <= 3 && $commandFullContent >= 0) ? (int)$commandFullContent : 0;
$username = isBindID($reqQQNumber,$sendMessageBuffer);
if (empty($username)) {
	return;
}
$userid = GetUserIDByUsername($username);
setGameMode($mode);
$res = $conn->query("SELECT score_id, beatmap_id, rank, enabled_mods, pp, date FROM {$highScoreTable} WHERE user_id = {$userid} ORDER BY date DESC LIMIT 1");
if (!($res && $res->num_rows > 0)) {
	$sendMessageBuffer .= "{$lang['no_play_records']}\n";
	return;
}
list($scoreID,$beatmapID,$rank,$mods,$finalpp,$date) = $res->fetch_row();
$res = $conn->query("SELECT pp FROM {$scoreTable} WHERE score_id = {$scoreID} LIMIT 1");
list($pp) = $res->fetch_row();
$pp = sprintf('%.2f',$pp);
$finalpp = sprintf('%.2f',$finalpp);
$rank = str_replace('H','+Hidden',str_replace('X','SS',$rank));
$mods = getShortModString($mods,0);
$sendMessageBuffer .= "{$username}'s BanYou Recent ({$modeName}) [{$date}]\n";
getbeatmapinfo("b={$beatmapID}","beatmap_id = {$beatmapID}",0,1,0,1,1);
if (!empty($title)) {
	if (!empty($artist)) {
		$sendMessageBuffer .= "{$artist} - ";
	}
	$bmstar = round($bmstar,2);
	$sendMessageBuffer .= "{$title} [{$bmversion}] {$bmstar}*\n";
}
$sendMessageBuffer .= "{$lang['rank']}{$lang['colon']}{$rank}{$lang['comma']}Mods{$lang['colon']}{$mods}{$lang['comma']}{$pp}pp({$finalpp}pp)\n";
$sendMessageBuffer .= "{$lang['beatmap']}{$lang['colon']}https://osu.ppy.sh/b/{$beatmapID}";
if (!empty($hitLength)) {
	$sendMessageBuffer .= "{$lang['comma']}{$lang['beatmap_hit_length']}{$lang['colon']}{$hitLength} {$lang['second']}{$lang['comma']}{$lang['beatmap_total_length']}{$lang['colon']}{$totalTime} {$lang['second']}";
}
$sendMessageBuffer .= "\n";
$sendMessageBuffer .= "{$lang['userpage']}{$lang['colon']}https://user.".BanYouDomain."/".rawurlencode($username);
$sendMessageBuffer .= "\n";
?>
