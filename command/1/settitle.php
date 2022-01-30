<?php
if (!defined('BotFramework')) {
	return;
}
if (isVaildQQ($reqQQNumber)) {
	$sendMessageBuffer .= (SetGroupSpecialTitle($reqGroupNumber, $reqQQNumber, ((isset($commandFullContent)) ? $commandFullContent : ''))) ? 'OK' : 'Failed';
	$sendMessageBuffer .= ".\n";
}
