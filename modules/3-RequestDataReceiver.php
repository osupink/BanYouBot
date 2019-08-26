<?php
global $reqData, $reqJSONArr, $isMaster, $reqQQNumber, $reqGroupNumber, $reqRawMessage;
$reqData=file_get_contents('php://input');
$reqJSONArr=json_decode($reqData);
$isMaster=false;
if (isset($reqJSONArr->user_id)) {
	$reqQQNumber=$reqJSONArr->user_id;
	$isMaster=(masterQQ == $reqQQNumber);
}
if (isset($reqJSONArr->group_id)) {
	$reqGroupNumber=$reqJSONArr->group_id;
}
?>
