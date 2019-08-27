<?php
if (!defined('BotFramework')) {
	die();
}
global $lang, $isMaster, $commandhelp, $isFakeAdmin;
if (!$isMaster && !$isFakeAdmin) {
	$sendMessageBuffer.="{$lang['fake_admin']}\n";
	return;
}
if (!isset($commandSubType)) {
	foreach ($commandhelp['botadmin'] as $value) {
		$sendMessageBuffer.="{$value[0]} - {$value[1]}\n";
	}
	return;
}
switch (strtolower($commandSubType)) {
	case 'blockqq':
		if (!isset($commandArr)) {
			$sendMessageBuffer.="{$lang['usage']}{$lang['colon']}{$commandhelp['botadmin']['blockqq'][0]}.\n";
			return;
		}
		$commandArr[0]=isAT($commandArr[0]);
		if (!is_numeric($commandArr[0]) || strlen($commandArr[0]) > 11 || strlen($commandArr[0]) < 5) {
			$sendMessageBuffer.="{$lang['not_a_true_qqnumber']}\n";
			return;
		}
		if (count($commandArr) > 1) {
			if (!is_numeric($commandArr[1]) || strlen($commandArr[1]) > 4 || strlen($commandArr[1]) < 1) {
				$sendMessageBuffer.="{$lang['not_a_true_silence_time']}\n";
				return;
			}
			$silenceTime=(int)$commandArr[1];
		} else {
			$silenceTime=0;
		}
		$blockQQNumber=(int)$commandArr[0];
		$conn->query("INSERT INTO bot_blockqqlist VALUES ({$reqGroupNumber},{$blockQQNumber},{$silenceTime}) ON DUPLICATE KEY UPDATE BlockTime = {$silenceTime}");
		break;
	case 'blocktext':
		if (!(isset($commandContent) && strlen($commandContent) <= 400)) {
			$sendMessageBuffer.="{$lang['usage']}{$lang['colon']}{$commandhelp['botadmin']['blocktext'][0]}.\n";
			return;
		}
		$commandContentBase64=base64_encode(strtolower($commandContent));
		$stmt=$conn->prepare('INSERT INTO bot_blocktextlist VALUES (?,?)');
		$stmt->bind_param('is', $reqGroupNumber, $commandContentBase64);
		$stmt->execute();
		$stmt->close();
		break;
	case 'unblockqq':
		if (!isset($commandContent)) {
			$sendMessageBuffer.="{$lang['usage']}{$lang['colon']}{$commandhelp['botadmin']['unblockqq'][0]}.\n";
			return;
		}
		$blockQQNumber=(int)isAT($commandContent);
		$stmt=$conn->prepare('DELETE FROM bot_blockqqlist WHERE group_number = ? AND BlockQQ = ? LIMIT 1');
		$stmt->bind_param('is', $reqGroupNumber, $blockQQNumber);
		$stmt->execute();
		$stmt->close();
		break;
	case 'unblocktext':
		if (!(isset($commandContent) && strlen($commandContent) <= 400)) {
			$sendMessageBuffer.="{$lang['usage']}{$lang['colon']}{$commandhelp['botadmin']['unblocktext'][0]}.\n";
			return;
		}
		$commandContentBase64=base64_encode(strtolower($commandContent));
		$stmt=$conn->prepare('DELETE FROM bot_blocktextlist WHERE group_number = ? AND BlockText = ? LIMIT 1');
		$stmt->bind_param('is', $reqGroupNumber, $commandContentBase64);
		$stmt->execute();
		$stmt->close();
		break;
	case 'blockqqlist':
		$res=$conn->query("SELECT BlockQQ, BlockTime FROM bot_blockqqlist WHERE group_number = {$reqGroupNumber}");
		$blockQQList=$res->fetch_all(MYSQLI_ASSOC);
		if (count($blockQQList) < 1) {
			$sendMessageBuffer.="{$lang['have_not_blockqqlist']}\n";
			return;
		}
		foreach ($blockQQList as $value) {
			$sendMessageBuffer.="QQ: {$value['BlockQQ']}, ";
			if ($reqGroupNumber == mainGroupNumber) {
				$stmt=$conn->prepare('SELECT username FROM osu_users WHERE user_qq = ? LIMIT 1');
				if ($stmt->bind_param('i', $value['BlockQQ']) && $stmt->execute() && $stmt->bind_result($osuID)) {
					if ($stmt->fetch() && !empty($osuID)) {
						$sendMessageBuffer.="BanYou(osu!) ID: {$osuID}, ";
					}
					$stmt->close();
				} else {
					$dbError='Unknown.';
					if ($stmt) {
						$dbError=$stmt->error;
						$stmt->close();
					}
					$sendMessageBuffer.="Database Error: {$dbError} ({$commandName} {$commandSubType}), ";
				}
			}
			$sendMessageBuffer.="Silence Time: {$value['BlockTime']}.\n";
		}
		break;
	case 'blocktextlist':
		$res=$conn->query("SELECT BlockText FROM bot_blocktextlist WHERE group_number = {$reqGroupNumber}");
		$blockTextList=$res->fetch_all(MYSQLI_ASSOC);
		if (count($blockTextList) < 1) {
			$sendMessageBuffer.="{$lang['have_not_blocktextlist']}\n";
			return;
		}
		foreach ($blockTextList as $value) {
			$value['BlockText']=base64_decode($value['BlockText']);
			$sendMessageBuffer.="Text: {$value['BlockText']}.\n";
		}
		break;
	case 'kick':
		if (!isset($commandContent)) {
			$sendMessageBuffer.="{$lang['usage']}{$lang['colon']}{$commandhelp['botadmin']['kick'][0]}.\n";
			return;
		}
		$kickQQNumber=(int)isAT($commandContent);
		Kick($reqGroupNumber, $kickQQNumber);
		break;
	case 'silence':
		if (!isset($commandArr)) {
			$sendMessageBuffer.="{$lang['usage']}{$lang['colon']}{$commandhelp['botadmin']['silence'][0]}.\n";
			return;
		}
		$silenceQQNumber=(int)isAT($commandArr[0]);
		$silenceTime=(isset($commandArr[1]) && is_numeric($commandArr[1])) ? (int)$commandArr[1] : 1;
		if ($silenceTime > 0 && $silenceTime <= 43200) {
			$silenceTime*=60;
		} else {
			$silenceTime=60;
		}
		Silence($reqGroupNumber, $silenceQQNumber, $silenceTime);
		break;
	case 'unsilence':
		if (!isset($commandContent)) {
			$sendMessageBuffer.="{$lang['usage']}{$lang['colon']}{$commandhelp['botadmin']['unsilence'][0]}.\n";
			return;
		}
		$unSilenceQQNumber=(int)isAT($commandContent);
		Silence($reqGroupNumber, $unSilenceQQNumber, 0);
		break;
	case 'changecard':
		if (!isset($commandArr)) {
			$sendMessageBuffer.="{$lang['usage']}{$lang['colon']}{$commandhelp['botadmin']['changecard'][0]}.\n";
			return;
		}
		$changeQQNumber=(!is_numeric($commandArr[0])) ? (int)isAT($commandArr[0]) : $commandArr[0];
		if (!(is_numeric($changeQQNumber) && $changeQQNumber !== 0 && isset($commandArr[1]))) {
			ChangeCard($reqGroupNumber, ($isMaster ? selfQQ : $reqQQNumber), $commandContent);
		} else {
			unset($commandArr[0]);
			ChangeCard($reqGroupNumber, $changeQQNumber, implode(' ', $commandArr));
		}
		break;
	default:
		break;
}
$sendMessageBuffer.="OK.\n";
?>
