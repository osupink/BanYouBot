<?php
global $lang, $commandhelp;
if (!defined('BotFramework')) {
	return;
}
if (!isset($commandSubType)) {
	foreach ($commandhelp['user'] as $value) {
		$sendMessageBuffer .= "{$value[0]} - {$value[1]}\n";
	}
}
switch (strtolower($commandSubType)) {
	case 'supporter':
		if (!isset($commandArr)) {
			$sendMessageBuffer .= "{$lang['usage']}{$lang['colon']}{$commandhelp['user']['supporter'][0]}.\n";
			break;
		}
		$date = -1;
		if (count($commandArr) > 1) {
			if (date('Y-m-d', strtotime($commandArr[count($commandArr)-1])) == ($commandArr[count($commandArr)-1])) {
				$date = $commandArr[count($commandArr)-1];
				unset($commandArr[count($commandArr)-1]);
			} elseif ($commandArr[count($commandArr)-1] == "0") {
				$date = 0;
				unset($commandArr[count($commandArr)-1]);
			} elseif ($commandArr[count($commandArr)-1] == "1") {
				$date = 1;
				unset($commandArr[count($commandArr)-1]);
			}
		}
		$username = implode(' ',$commandArr);
		if ($date === -1) {
			$stmt = $conn->prepare('SELECT osu_subscriber, osu_subscriptionexpiry, username FROM osu_users WHERE username = ? LIMIT 1');
			if ($stmt->bind_param('s', $username) && $stmt->execute() && $stmt->bind_result($subscripter, $subscriptionexpiry, $username)) {
				if (!$stmt->fetch()) {
					$stmt->close();
					break;
				}
				$stmt->close();
			}
			$supporterExpiryTime = 0;
			if ($subscripter !== null) {
				$supporterExpiryTime=(!empty($subscriptionexpiry && $subscripter == 1)) ? $subscriptionexpiry : $subscripter;
				$sendMessageBuffer .= sprintf($lang['supporter_expirydate'],$username,$supporterExpiryTime);
			} else {
				$sendMessageBuffer .= $lang['user_not_found'];
			}
		} elseif ($isMaster) {
			$supporterExpiryTime = null;
			if (!is_numeric($date)) {
				if (strtotime($date) > time()) {
					$supporter = 1;
					$supporterExpiryTime = $date;
				} else {
					$supporter = 0;
				}
			} elseif ($date == 0 || $date == 1) {
				$supporter = $date;
			}
			$stmt = $conn->prepare('UPDATE osu_users SET osu_subscriber = ?, osu_supportplayer = 0, osu_subscriptionexpiry = ? WHERE username = ? LIMIT 1');
			$stmt->bind_param('iss', $supporter, $supporterExpiryTime, $username);
			$stmt->execute();
			$stmt->close();
			$sendMessageBuffer .= sprintf($lang['updated_supporter_expirydate'],$username);
			if ($supporter && $supporterExpiryTime != null) {
				$sendMessageBuffer .= sprintf($lang['updated_supporter_expirydate_to+'],trim($supporterExpiryTime,'\''));
			}
			if (FlushSupporter($username)) {
				$sendMessageBuffer .= "{$lang['comma']}{$lang['will_take_effect_immediately']}";
			}
			$sendMessageBuffer .= ".";
		}
		$sendMessageBuffer .= "\n";
		break;
	case 'supportplayer':
		if ($isMaster) {
			$username = $commandContent;
			$stmt = $conn->prepare('UPDATE osu_users SET osu_supportplayer = IF(osu_supportplayer = 1,0,1) WHERE osu_subscriber = 1 AND username = ? LIMIT 1');
			$stmt->bind_param('s', $username);
			$stmt->execute();
			$stmt->close();
		}
		break;
}
?>
