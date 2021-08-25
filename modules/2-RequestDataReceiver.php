<?php
global $conn, $reqData, $reqJSONArr, $isMaster, $reqQQNumber, $reqGroupNumber, $reqRawMessage, $reqEventType, $reqMessageID, $reqReplyMessageID, $isFakeAdmin;
if (!defined('BotFramework')) {
	return;
}
$reqData = file_get_contents('php://input');
$reqJSONArr = json_decode($reqData);
$isMaster = false;
$isFakeAdmin = false;
AddDebugValue(array('reqData' => $reqData, 'reqJSONArr' => $reqJSONArr));
if (isset($reqJSONArr->user_id)) {
	$reqQQNumber = (int)$reqJSONArr->user_id;
	$isMaster = (masterQQ === $reqQQNumber);
	AddDebugValue(array('reqQQNumber' => $reqQQNumber));
}
AddDebugValue(array('isMaster' => $isMaster));
if (isset($reqJSONArr->group_id)) {
	$reqGroupNumber = (int)$reqJSONArr->group_id;
	AddDebugValue(array('reqGroupNumber' => $reqGroupNumber));
}
if (isset($reqQQNumber, $reqGroupNumber)) {
	$stmt = $conn->prepare('SELECT 1 FROM bot_groupinfo WHERE group_number = ? AND bot_fakeadmin = ? LIMIT 1');
	if ($stmt->bind_param('ii', $reqGroupNumber, $reqQQNumber) && $stmt->execute() && $stmt->bind_result($status)) {
		if ($stmt->fetch() && $status) {
			$isFakeAdmin = true;
		}
		$stmt->close();
	} else {
		$dbError = 'Unknown.';
		if ($stmt) {
			$dbError = $stmt->error;
			$stmt->close();
		}
		trigger_error("Database Error: {$dbError}", E_USER_WARNING);
	}
}
AddDebugValue(array('isFakeAdmin' => $isFakeAdmin));
if (isset($reqJSONArr->post_type)) {
	$reqEventType = $reqJSONArr->post_type;
	AddDebugValue(array('reqEventType' => $reqEventType));
}
if (isset($reqJSONArr->message_id)) {
	$reqMessageID=(int)$reqJSONArr->message_id;
	AddDebugValue(array('reqMessageID' => $reqMessageID));
}
if (isset($reqJSONArr->message)) {
	$reqRawMessage = decodeCQCode($reqJSONArr->message);
	AddDebugValue(array('reqRawMessage' => $reqRawMessage));
	if (preg_match("/^\[CQ:reply,id=(-?\d+)\]/", $reqRawMessage, $matches)) {
		$reqReplyMessageID = (int)$matches[1];
		AddDebugValue(array('reqReplyMessageID' => $reqReplyMessageID));
		$reqRawMessage = str_replace($matches[0], '', $reqRawMessage);
	}
}
?>
