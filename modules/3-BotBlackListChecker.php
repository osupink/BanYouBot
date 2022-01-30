<?php
global $conn, $isNeedDelete;
if (!defined('BotFramework')) {
	return;
}
function isBanSay(): bool {
	$status = file_exists('bansay');
	AddDebugValue(array('isBanSay' => $status));
	return $status;
}
function ChangeSayStatus(): bool {
	if (isBanSay()) {
		unlink('bansay');
		$status = false;
	} else {
		file_put_contents('bansay','1');
		$status = true;
	}
	AddDebugValue(array('ChangeSayStatus' => $status));
	return $status;
}
function isAllowGroupMessage(int $groupNumber = 0): bool {
	$status = ($groupNumber !== 0) ? in_array($groupNumber, groupNumberList) : true;
	AddDebugValue(array('isAllowGroupMessage' => $status));
	return $status;
}
function CheckCommandBlacklist(string $command, bool $admin = true): int {
	// 0:不在黑名单, 1:指令黑名单, 2:QQ/群组黑名单.
	global $conn, $isMaster, $isFakeAdmin, $reqGroupNumber, $reqQQRole, $reqQQNumber;
	$status = 0;
	if (!($isMaster && $admin)) {
		if (isBanSay()) {
			$status = 2;
		} else {
			switch (strtolower($command)) {
				case 'help':
				case 'roll':
				#case 'weather':
				case 'br':
				case 'h':
					break;
				case 'hr':
					//if (isset($reqGroupNumber) && $reqGroupNumber !== 132783429 && !$isFakeAdmin && !(isset($reqQQRole) && $reqQQRole > 0)) {
					if (isset($reqGroupNumber) && !$isFakeAdmin && !(isset($reqQQRole) && $reqQQRole > 0)) {
						$status = 1;
					}
					break;
				case 'he':
					if (!$isFakeAdmin && !(isset($reqGroupNumber) && ($reqGroupNumber === mainGroupNumber || $reqGroupNumber === 132783429)) && !(isset($reqQQRole) && $reqQQRole > 0)) {
						$status = 1;
					}
					break;
				case 'say':
					if (!$isFakeAdmin) {
						$status = 1;
					}
					break;
				case 'atall':
				case 'botadmin':
					// 用于测试使用，方便在 !help 指令模拟查看没有权限玩家的指令使用结果
					if ($isMaster || (!$isFakeAdmin && $reqQQRole < 1)) {
					//if ($isMaster || !$isFakeAdmin) {
						$status = 1;
					}
					break;
				case 'kick':
				case 'getkey':
				case 'bansay':
				case 'fs':
				case 'announce':
					$status = 1;
					break;
				case 'settitle':
					if (!isset($reqGroupNumber) || $reqGroupNumber !== 132783429) {
						$status = 1;
					}
					break;
				default:
					if (isset($reqGroupNumber) && !isAllowGroupMessage($reqGroupNumber)) {
						$status = 2;
					}
					break;
			}
		}
	}
	AddDebugValue(array('CheckCommandBlacklist' => $status));
	return $status;
}
function CheckSilenceList(string $fullMessage): int {
	// 0:不在禁言名单, 1:在禁言名单中, 2:在黑名单中
	global $conn, $isMaster, $isFakeAdmin, $reqGroupNumber, $reqQQNumber, $reqMessageID;
	switch ($fullMessage) {
		/*
		case '[image=A2DA722F8EAD905AC7883C6E4CDB85D3.jpg]':
			if ($_POST['QQ'] == "2839098896") {
				return 1;
			}
			break;
		*/
		default:
			if (!$isMaster) {
				$stmt = $conn->prepare('SELECT BlockTime FROM bot_blockqqlist WHERE group_number = ? AND BlockQQ = ? LIMIT 1');
				if ($stmt->bind_param('ii', $reqGroupNumber, $reqQQNumber) && $stmt->execute() && $stmt->bind_result($blockTime)) {
					if (!($stmt->fetch() && $blockTime !== false)) {
						$stmt->close();
					} else {
						$stmt->close();
						if ($blockTime == -1) {
							Kick($reqGroupNumber,$reqQQNumber);
							$status = 2;
						}
						$status = 1;
						if ($blockTime > 0) {
							Silence($reqGroupNumber, $reqQQNumber, $blockTime*60);
						}
						DeleteMessage($reqMessageID);
						break;
					}
				} else {
					$dbError = 'Unknown.';
					if ($stmt) {
						$dbError = $stmt->error;
						$stmt->close();
					}
					trigger_error("Database Error: {$dbError}", E_USER_WARNING);
					$status = 0;
					break;
				}
				if ($isFakeAdmin) {
					break;
				}
				$lowerfullmessage = utf8_encode(strtolower($fullMessage));
				$stmt = $conn->prepare('SELECT 1 FROM bot_blocktextlist WHERE group_number = ? AND LOCATE(BlockText,?) > 0 LIMIT 1');
				if ($stmt->bind_param('is', $reqGroupNumber, $lowerfullmessage) && $stmt->execute() && $stmt->bind_result($rstatus)) {
					if ($stmt->fetch() && $rstatus) {
						$stmt->close();
						Silence($reqGroupNumber, $reqQQNumber, 10*60);
						DeleteMessage($reqMessageID);
						$status = 1;
						break;
					} else {
						$stmt->close();
					}
				} else {
					$dbError = 'Unknown.';
					if ($stmt) {
						$dbError = $stmt->error;
						$stmt->close();
					}
					trigger_error("Database Error: {$dbError}", E_USER_WARNING);
					$status = 0;
					break;
				}
			}
			break;
	}
	if (!isset($status)) {
		$status = 0;
	}
	AddDebugValue(array('CheckSilenceList' => $status));
	return $status;
}
function isBanQQ($QQNumber): bool {
	$status = false;
	if ($QQNumber === 80000000 || $QQNumber === selfQQ) {
		$status = true;
	}
	AddDebugValue(array('isBanQQ' => $status));
	return $status;
}
$isNeedDelete = 0;
// 防止自激
if (isset($reqQQNumber)) {
	if (isBanQQ($reqQQNumber) || (isset($reqGroupNumber) && !isAllowGroupMessage($reqGroupNumber))) {
		return;
	}
	if (isset($reqRawMessage) && CheckSilenceList($reqRawMessage) > 0) {
		return;
	}
}
?>
