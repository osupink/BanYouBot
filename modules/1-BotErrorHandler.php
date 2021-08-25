<?php
if (!defined('BotFramework')) {
	return;
}
function sendmsg(string $text) {
	file_get_contents("http://127.0.0.1:5700/send_group_msg?group_id=609602961&message={$text}");
}
function addnotice(string $title, string $text) {
	//$title=str_replace(array('_','*','&','['),array('\\_','\\*','\\&','\\['),$title);
	//$text=str_replace(array('_','*','&','['),array('\\_','\\*','\\&','\\['),$text);
	sendmsg(rawurlencode("*$title*\n$text"));
}
function botErrorHandler(int $errno, string $errstr, string $errfile, int $errline, array $errcontext) {
	switch ($errno)
	{
		case E_ERROR:               $type = "Error";                  break;
		case E_WARNING:             $type = "Warning";                break;
		case E_PARSE:               $type = "Parse Error";            break;
		case E_NOTICE:              $type = "Notice";                 break;
		case E_CORE_ERROR:          $type = "Core Error";             break;
		case E_CORE_WARNING:        $type = "Core Warning";           break;
		case E_COMPILE_ERROR:       $type = "Compile Error";          break;
		case E_COMPILE_WARNING:     $type = "Compile Warning";        break;
		case E_USER_ERROR:          $type = "User Error";             break;
		case E_USER_WARNING:        $type = "User Warning";           break;
		case E_USER_NOTICE:         $type = "User Notice";            break;
		case E_STRICT:              $type = "Strict Notice";          break;
		case E_RECOVERABLE_ERROR:   $type = "Recoverable Error";      break;
		default:                    $type = "Unknown error ($errno)"; break;
	}
	addnotice("New $type occurred in BanYouBot","$errstr ($errfile:$errline)");
}
set_error_handler('botErrorHandler');
?>
