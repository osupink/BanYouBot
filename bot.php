<?php
date_default_timezone_set('Asia/Shanghai');
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
