<?php
if (!($_SERVER['REMOTE_ADDR'] === '127.0.0.1' && strtoupper($_SERVER['REQUEST_METHOD']) === 'POST')) {
	die();
}
date_default_timezone_set('Asia/Shanghai');
define('BotFramework', 1);
function RequireOnceAllFilesinDir($dir) {
	$files=scandir($dir);
	$fileBlockList=array('.', '..');
	foreach ($files as $file) {
		$filesplit=explode(".", $file);
		if (!in_array($file, $fileBlockList) && !is_dir($file) && (strtolower(end($filesplit)) === 'php')) {
			require_once($dir.'/'.$file);
		}
	}
}
require_once('botconfig.php');
require_once('lang.php');
RequireOnceAllFilesinDir('apis');
RequireOnceAllFilesinDir('modules');
?>
