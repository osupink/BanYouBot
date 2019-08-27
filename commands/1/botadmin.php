<?php
if (!defined('BotFramework')) {
    die();
}
if ($reqQQNumber !== masterQQ) {
    $stmt = $conn->prepare('SELECT 1 FROM bot_groupinfo WHERE group_number = ? AND bot_fakeadmin = ? LIMIT 1');
    if ($stmt->bind_param('ii', $reqGroupNumber, $reqQQNumber) && $stmt->execute() && $stmt->bind_result($status) && $stmt->fetch()) {
        $stmt->close();
        if (!$status) {
            $sendMessageBuffer .= "{$lang['fake_admin']}\n";
            return;
        }
    } else {
        $dbError = 'Unknown.';
        if ($stmt) {
            $dbError = $stmt->error;
            $stmt->close();
        }
        $sendMessageBuffer .= "Database Error: {$dbError} (botadmin)\n";
        return;
    }
}
if (!isset($commandContent)) {
    foreach ($commandhelp['botadmin'] as $value) {
        $sendMessageBuffer .= "{$value[0]} - {$value[1]}\n";
    }
    return;
}
switch (strtolower($commandSubType)) {
case 'blockqq':
    if (!isset($commandArr)) {
        $sendMessageBuffer .= "{$lang['usage']}{$lang['colon']}{$commandhelp['botadmin']['blockqq'][0]}.\n";
        return;
    }
    $commandArr[1] = isAT($commandArr[1]);
    if (!is_numeric($commandArr[1]) || strlen($commandArr[1]) > 11 || strlen($commandArr[1]) < 5) {
        $sendMessageBuffer .= "{$lang['not_a_true_qqnumber']}\n";
        return;
    }
    if (count($commandArr) > 2) {
        if (!is_numeric($commandArr[2]) || strlen($commandArr[2]) > 4 || strlen($commandArr[2]) < 1) {
            $sendMessageBuffer .= "{$lang['not_a_true_silence_time']}\n";
            return;
        }
        $silenceTime = (int) $commandArr[2];
    } else {
        $silenceTime = 0;
    }
    $blockQQNumber = (int) $commandArr[1];
    if (!$isMaster) {
        $stmt = $conn->prepare('SELECT 1 FROM bot_groupinfo WHERE group_number = ? AND bot_fakeadmin = ? LIMIT 1');
        if ($stmt->bind_param('ii', $reqGroupNumber, $blockQQNumber) && $stmt->execute() && $stmt->bind_result($status) && $stmt->fetch()) {
            $stmt->close();
            if (!$status) {
                $sendMessageBuffer .= "{$lang['not_a_true_qqnumber']}\n";
                return;
            }
        } else {
            $dbError = 'Unknown.';
            if ($stmt) {
                $dbError = $stmt->error;
                $stmt->close();
            }
            $sendMessageBuffer .= "Database Error: {$dbError} ({$commandName} {$commandSubType})\n";
            return;
        }
    }
    $conn->query("INSERT INTO bot_blockqqlist VALUES ({$reqGroupNumber},{$blockQQNumber},{$silenceTime})");
    break;
case 'blocktext':
    if (count($splitarr) < 1) {
        $sendMessageBuffer .= "{$lang['usage']}{$lang['colon']}{$commandhelp['botadmin']['blocktext'][0]}.\n";
        break 2;
    }
    $blockstr = sqlstr(implode(' ', $splitarr));
    if (strlen($blockstr) > 400) {
        $sendMessageBuffer .= "{$lang['usage']}{$lang['colon']}{$commandhelp['botadmin']['blocktext'][0]}.\n";
        break 2;
    }
    $conn->exec("INSERT INTO bot_blocktextlist VALUES ({$groupNumber},'{$blockstr}')");
    break;
case 'unblockqq':
    if (count($splitarr) < 1) {
        $sendMessageBuffer .= "{$lang['usage']}{$lang['colon']}{$commandhelp['botadmin']['unblockqq'][0]}.\n";
        break 2;
    }
    $blockQQNumber = (int) isAT($splitarr[0]);
    $conn->exec("DELETE FROM bot_blockqqlist WHERE group_number = {$groupNumber} AND BlockQQ = {$blockQQNumber} LIMIT 1");
    break;
case 'unblocktext':
    if (count($splitarr) < 1) {
        $sendMessageBuffer .= "{$lang['usage']}{$lang['colon']}{$commandhelp['botadmin']['unblocktext'][0]}.\n";
        break 2;
    }
    $blockstr = sqlstr(implode(' ', $splitarr));
    if (strlen($blockstr) > 400) {
        $sendMessageBuffer .= "{$lang['usage']}{$lang['colon']}{$commandhelp['botadmin']['unblocktext'][0]}.\n";
        break 2;
    }
    $conn->exec("DELETE FROM bot_blocktextlist WHERE group_number = {$groupNumber} AND BlockText = '{$blockstr}' LIMIT 1");
    break;
case 'blockqqlist':
    $blockQQList = $conn->queryAll("SELECT BlockQQ, BlockTime FROM bot_blockqqlist WHERE group_number = {$groupNumber}");
    if (count($blockQQList) < 1) {
        $sendMessageBuffer .= "{$lang['have_not_blockqqlist']}\n";
        break 2;
    }
    foreach ($blockQQList as $value) {
        $sendMessageBuffer .= "QQ: {$value['BlockQQ']}, ";
        if ($groupNumber == $mainGroupNumber) {
            $osuID = $conn->queryOne("SELECT username FROM osu_users WHERE user_qq = {$value['BlockQQ']} LIMIT 1");
            if (!empty($osuID)) {
                $sendMessageBuffer .= "BanYou(osu!) ID: {$osuID}, ";
            }
        }
        $sendMessageBuffer .= "Silence Time: {$value['BlockTime']}.\n";
    }
    break;
case 'blocktextlist':
    $blockTextList = $conn->queryAll("SELECT BlockText FROM bot_blocktextlist WHERE group_number = {$groupNumber}");
    if (count($blockTextList) < 1) {
        $sendMessageBuffer .= "{$lang['have_not_blocktextlist']}\n";
        break 2;
    }
    foreach ($blockTextList as $value) {
        $sendMessageBuffer .= "Text: {$value['BlockText']}.\n";
    }
    break;
case 'kick':
    if (count($splitarr) < 1) {
        $sendMessageBuffer .= "{$lang['usage']}{$lang['colon']}{$commandhelp['botadmin']['kick'][0]}.\n";
        break 2;
    }
    $kickQQNumber = (int) isAT($splitarr[0]);
    if (!$isMaster && $conn->queryOne("SELECT 1 FROM bot_groupinfo WHERE group_number = {$groupNumber} AND bot_fakeadmin = {$kickQQNumber} LIMIT 1")) {
        $sendMessageBuffer .= "{$lang['usage']}{$lang['colon']}{$commandhelp['botadmin']['kick'][0]}.\n";
        break 2;
    }
    Kick($groupNumber, $kickQQNumber);
    break;
case 'silence':
    if (count($splitarr) < 1) {
        $sendMessageBuffer .= "{$lang['usage']}{$lang['colon']}{$commandhelp['botadmin']['silence'][0]}.\n";
        break 2;
    }
    $silenceQQNumber = (int) isAT($splitarr[0]);
    if (!$isMaster && $conn->queryOne("SELECT 1 FROM bot_groupinfo WHERE group_number = {$groupNumber} AND bot_fakeadmin = {$silenceQQNumber} LIMIT 1")) {
        $sendMessageBuffer .= "{$lang['usage']}{$lang['colon']}{$commandhelp['botadmin']['silence'][0]}.\n";
        break 2;
    }
    $silenceTime = (count($splitarr) > 1) ? (int) $splitarr[1] : 1;
    if ($silenceTime > 0 && $silenceTime <= 43200) {
        $silenceTime *= 60;
    } else {
        $silenceTime = 60;
    }
    Silence($groupNumber, $silenceQQNumber, $silenceTime);
    break;
case 'unsilence':
    if (count($splitarr) < 1) {
        $sendMessageBuffer .= "{$lang['usage']}{$lang['colon']}{$commandhelp['botadmin']['unsilence'][0]}.\n";
        break 2;
    }
    $unSilenceQQNumber = (int) isAT($splitarr[0]);
    Silence($groupNumber, $unSilenceQQNumber, 0);
    break;
case 'changecard':
    if (count($splitarr) < 1) {
        $sendMessageBuffer .= "{$lang['usage']}{$lang['colon']}{$commandhelp['botadmin']['changecard'][0]}.\n";
        break 2;
    }
    if (count($splitarr) < 2) {
        ChangeCard(($isMaster ? $selfQQ : $qqNumber), implode(' ', $splitarr));
    } else {
        $changeQQNumber = (!is_numeric($splitarr[0])) ? (int) isAT($splitarr[0]) : $splitarr[0];
        if (!$isMaster && $conn->queryOne("SELECT 1 FROM bot_groupinfo WHERE group_number = {$groupNumber} AND bot_fakeadmin = {$changeQQNumber} LIMIT 1")) {
            $sendMessageBuffer .= "{$lang['usage']}{$lang['colon']}{$commandhelp['botadmin']['changecard'][0]}.\n";
            break 2;
        }
        unset($splitarr[0]);
        if ($changeQQNumber === 0) {
            $sendMessageBuffer .= "{$lang['usage']}{$lang['colon']}{$commandhelp['botadmin']['changecard'][0]}.\n";
            break 2;
        }
        ChangeCard($changeQQNumber, implode(' ', $splitarr));
    }
    break;
default:
    break 2;
}
$sendMessageBuffer .= "OK.\n";
break;
default:
    return 0;
    break;
