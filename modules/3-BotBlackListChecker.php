<?php
global $conn, $isMaster, $isFakeAdmin, $reqQQNumber, $reqGroupNumber, $reqRawMessage;
function isBanSay() {
	return file_exists('bansay');
}
function ChangeSayStatus() {
	if (isBanSay()) {
		unlink('bansay');
		return 0;
	} else {
		file_put_contents('bansay','1');
		return 1;
	}
}
function isAllowGroupMessage($groupNumber=false) {
	if ($groupNumber === false) {
		return 1;
	}
	return in_array($groupNumber, groupNumberList);
}
function CheckCommandBlacklist($command, $admin=1) {
	// 0:不在黑名单, 1:指令黑名单, 2:QQ/群组黑名单.
	global $conn, $isMaster, $isFakeAdmin, $reqGroupNumber, $reqQQNumber;
	if ($isMaster && $admin) {
		return 0;
	}
	if (isBanSay()) {
		return 2;
	}
	switch ($command) {
		case 'help':
		case 'roll':
		#case 'weather':
		case 'br':
			break;
		case 'say':
		case 'atall':
		case 'botadmin':
			if (!$isFakeAdmin) {
				return 1;
			}
			break;
		case 'getkey':
		case 'bansay':
		case 'fs':
		case 'announce':
			return 1;
			break;
		default:
			if (isset($reqGroupNumber) && !isAllowGroupMessage($reqGroupNumber)) {
				return 2;
			}
			break;
	}
	return 0;
}
function CheckSilenceList($fullmessage) {
	// 0:不在禁言名单, 1:在禁言名单中, 2:在黑名单中
	global $conn, $isMaster, $isFakeAdmin, $reqGroupNumber, $reqQQNumber;
	switch ($fullmessage) {
		/*
		case '[image=A2DA722F8EAD905AC7883C6E4CDB85D3.jpg]':
			if ($_POST['QQ'] == "2839098896") {
				return 1;
			}
			break;
		*/
		default:
			if (!$isMaster) {
				$stmt=$conn->prepare('SELECT BlockTime FROM bot_blockqqlist WHERE group_number = ? AND BlockQQ = ? LIMIT 1');
				if ($stmt->bind_param('ii', $reqGroupNumber, $reqQQNumber) && $stmt->execute() && $stmt->bind_result($blockTime)) {
					if (!($stmt->fetch() && $blockTime !== false)) {
						$stmt->close();
					} else {
						$stmt->close();
						if ($blockTime == 0) {
							Kick($reqGroupNumber,$reqQQNumber);
							return 2;
						}
						if ($blockTime) {
							Silence($reqGroupNumber, $reqQQNumber, $blockTime*60);
							return 1;
						}
					}
				} else {
					$dbError='Unknown.';
					if ($stmt) {
						$dbError=$stmt->error;
						$stmt->close();
					}
					trigger_error("Database Error: {$dbError}", E_USER_WARNING);
					return;
				}
				if ($isFakeAdmin) {
					break;
				}
				$lowerfullmessage=utf8_encode(strtolower($fullmessage));
				$stmt=$conn->prepare('SELECT 1 FROM bot_blocktextlist WHERE group_number = ? AND LOCATE(BlockText,?) > 0 LIMIT 1');
				if ($stmt->bind_param('is', $reqGroupNumber, $lowerfullmessage) && $stmt->execute() && $stmt->bind_result($status)) {
					if ($stmt->fetch() && $status) {
						$stmt->close();
						Silence($reqGroupNumber, $reqQQNumber, 10*60);
						return 1;
					} else {
						$stmt->close();
					}
				} else {
					$dbError='Unknown.';
					if ($stmt) {
						$dbError=$stmt->error;
						$stmt->close();
					}
					trigger_error("Database Error: {$dbError}", E_USER_WARNING);
					return;
				}
			}
			break;
	}
	return 0;
}
function isBanQQ($QQNumber) {
	if ($QQNumber === 80000000 || $QQNumber === selfQQ) {
		return 1;
	}
	return 0;
}
// 防止自激
if (isset($reqQQNumber)) {
	if (isBanQQ($reqQQNumber) || (isset($reqGroupNumber) && !isAllowGroupMessage($reqGroupNumber))) {
		die();
	}
	if (isset($reqJSONArr->message)) {
		$reqRawMessage=decodeCQCode($reqJSONArr->message);
		CheckSilenceList($reqRawMessage);
	}
}
?>
