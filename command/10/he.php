<?php
if (!$isFakeAdmin && !(isset($reqQQRole) && $reqQQRole > 0)) {
	switch ($reqQQNumber) {
		case masterQQ:
		case 502036330:
		case 1120180945:
		case 2967447833:
		case 1129385494:
		case 1483492332:
			break;
		default:
			return;
	}
}
$tagsQuery = '';
require_once('h.php');
?>
