<?php
if (!defined('BotFramework')) {
	die();
}
if (!isset($reqJSONArr->request_type)) {
	return;
}
global $conn, $lang;
switch ($reqJSONArr->request_type) {
	case 'friend':
		$arr=array('approve'=>true);
		break;
	case 'group':
		$stmt=$conn->prepare('SELECT BlockTime FROM bot_blockqqlist WHERE group_number = ? AND BlockQQ = ? LIMIT 1');
		if ($stmt->bind_param('ii', $reqGroupNumber, $reqQQNumber) && $stmt->execute() && $stmt->bind_result($blockTime)) {
			if ($stmt->fetch() && $blockTime !== false && $blockTime == 0) {
				$arr=array('approve'=>false,'reason'=>"因为你在 BanYouBot 黑名单的列表中{$lang['comma']}所以你被拒绝加入群");
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
		break;
	default:
		break;
}
if (isset($arr) && is_array($arr)) {
	echo toJSON($arr);
}
?>
