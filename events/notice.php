<?php
if (!defined('BotFramework') || !isset($reqJSONArr->notice_type)) {
	return;
}
global $lang;
switch (strtolower($reqJSONArr->notice_type)) {
	case 'group_increase':
		if (!isset($reqGroupNumber,$reqQQNumber)) {
			break;
		}
		if ($reqGroupNumber === mainGroupNumber) {
			sendGroupMessage(mainGroupNumber, "[CQ:at,qq={$reqQQNumber}] 欢迎来到 BanYou 玩家群{$lang['comma']}请将你的名片改为 osu! ID。\n目前 BanYou 已经停止维护{$lang['comma']}要尝试进入 BanYou{$lang['comma']}可在群文件下载客户端和使用指南。");
		}
		break;
	default:
		break;
}
?>
