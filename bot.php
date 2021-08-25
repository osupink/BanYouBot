<?php
declare(strict_types=1);
if (!($_SERVER['REMOTE_ADDR'] === '127.0.0.1' && strtoupper($_SERVER['REQUEST_METHOD']) === 'POST')) {
	return;
}
date_default_timezone_set('Asia/Shanghai');
define('BotFramework', 1);
function RequireOnceAllFilesinDir($dir) {
	$files=scandir($dir);
	$fileBlockList=array('.', '..');
	foreach ($files as $file) {
		$filesplit=explode(".", $file);
		if (!in_array($file, $fileBlockList) && !is_dir($file) && (strtolower(end($filesplit)) === 'php')) {
			AddDebugInfo("Loading {$dir}/{$file}...");
			require_once("{$dir}/{$file}");
		}
	}
}
require_once('botconfig.php');
$debugMessageBuffer = '';
function AddDebugValue(array $values) {
	if (!DEBUG) {
		return;
	}
	global $debugMessageBuffer;
	foreach ($values as $key => $value) {
		$debugMessageBuffer .= "{$key}: " . var_export($value, true) . ", ";
	}
}
function AddDebugInfo(string $info) {
	if (!DEBUG) {
		return;
	}
	global $debugMessageBuffer;
	$debugMessageBuffer = rtrim($debugMessageBuffer) . "\n[DEBUG] {$info}\n";
}
require_once('lang.php');
RequireOnceAllFilesinDir('apis');
RequireOnceAllFilesinDir('modules');
?>
