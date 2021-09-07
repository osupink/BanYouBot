<?php
if (!defined('BotFramework')) {
	return;
}
function AddNotice(string $title, string $text) {
	//$title=str_replace(array('_','*','&','['),array('\\_','\\*','\\&','\\['),$title);
	//$text=str_replace(array('_','*','&','['),array('\\_','\\*','\\&','\\['),$text);
	Debug("*$title*\n$text");
}
function botErrorHandler(int $errno, string $errstr, string $errfile, int $errline, array $errcontext) {
	if (error_reporting() === 0) {
		return;
	}
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
		default:                    $type = "Unknown error ({$errno})"; break;
	}
	AddNotice("New {$type} occurred in BanYouBot","{$errstr} ({$errfile}:{$errline})");
}
set_error_handler('botErrorHandler');
?>
