<?php
global $lang;
if (!defined('BotFramework')) {
	return;
}
$username = isBindID($reqQQNumber,$sendMessageBuffer);
if (empty($username)) {
	return;
}
$userid = GetUserIDByUsername($username);
if (!isset($commandFullContent)) {
	$res = $conn->query("SELECT u.username,IF((SELECT 1 FROM osu_friends WHERE user_id = f.zebra_id AND zebra_id = {$userid} LIMIT 1),1,0) as mu FROM osu_friends f JOIN osu_users u ON u.user_id = f.zebra_id WHERE f.user_id = {$userid} ORDER BY u.user_lastvisit DESC LIMIT ".MaxFriendsCount);
	if (!($res && $res->num_rows > 0)) {
		$sendMessageBuffer .= "{$lang['you_have_not_added_any_friends_yet']}\n";
		return;
	}
	$friendsList = $res->fetch_all(MYSQLI_ASSOC);
	$sendMessageBuffer .= "{$username} {$lang['added_friends']}: ";
	foreach ($friendsList as $value) {
		if ($value['mu'] == 1) {
			$sendMessageBuffer .= "※ ";
		}
		$sendMessageBuffer .= $value['username'];
		$sendMessageBuffer .= ", ";
	}
	$sendMessageBuffer = rtrim($sendMessageBuffer,', ');
} else {
	$commandFullContent = $conn->escape_string($commandFullContent);
	$res = $conn->query("SELECT 1 FROM osu_friends f JOIN osu_users u ON username = '{$commandFullContent}' WHERE f.user_id = {$userid} AND f.zebra_id = u.user_id LIMIT 1");
	$isAdded=($res && $res->num_rows > 0) ? true : false;
	$res = $conn->query("SELECT 1 FROM osu_friends f JOIN osu_users u ON username = '{$commandFullContent}' WHERE u.user_id = f.user_id AND f.zebra_id = {$userid} LIMIT 1");
	$isBeAdded=($res && $res->num_rows > 0) ? true : false;
	$sendMessageBuffer .= (!empty($isAdded) ? $lang['you_have_added_him/her_as_a_friend'] : $lang['you_have_not_added_him/her_as_a_friend_yet']) . ', ' . (!empty($isBeAdded) ? $lang['he/she_has_added_you_as_a_friend'] : $lang['he/she_has_not_added_you_as_a_friend_yet']) . '.';
}
$sendMessageBuffer .= "\n";
?>
