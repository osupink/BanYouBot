<?php
if (!defined('BotFramework')) {
	die();
}
global $conn, $reqData, $reqJSONArr, $isMaster, $reqQQNumber, $reqGroupNumber, $reqRawMessage, $reqEventType, $isFakeAdmin;
$reqData=file_get_contents('php://input');
$reqJSONArr=json_decode($reqData);
$isMaster=false;
if (isset($reqJSONArr->user_id)) {
	$reqQQNumber=(int)$reqJSONArr->user_id;
	$isMaster=(masterQQ == $reqQQNumber);
}
if (isset($reqJSONArr->group_id)) {
	$reqGroupNumber=(int)$reqJSONArr->group_id;
}
if (isset($reqQQNumber,$reqGroupNumber)) {
	$isFakeAdmin=false;
	$stmt=$conn->prepare('SELECT 1 FROM bot_groupinfo WHERE group_number = ? AND bot_fakeadmin = ? LIMIT 1');
	if ($stmt->bind_param('ii', $reqGroupNumber, $reqQQNumber) && $stmt->execute() && $stmt->bind_result($status)) {
		if ($stmt->fetch() && $status) {
			$isFakeAdmin=true;
		}
		$stmt->close();
	} else {
		$dbError='Unknown.';
		if ($stmt) {
			$dbError=$stmt->error;
			$stmt->close();
		}
		trigger_error("Database Error: {$dbError}", E_USER_WARNING);
	}
}
if (isset($reqJSONArr->post_type)) {
	$reqEventType=$reqJSONArr->post_type;
}
?>
