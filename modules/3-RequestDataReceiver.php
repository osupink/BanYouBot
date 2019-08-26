<?php
if (!defined('BotFramework')) {
	die();
}
global $reqData, $reqJSONArr, $isMaster, $reqQQNumber, $reqGroupNumber, $reqRawMessage, $reqEventType;
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
if (isset($reqJSONArr->post_type)) {
	$reqEventType=$reqJSONArr->post_type;
}
?>
