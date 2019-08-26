<?php
if (!isset($reqEventType)) {
	die();
}
if (is_file(RootPath.ReplacePathSeparator("/events/{$reqEventType}.php"))) {
	require_once(RootPath.ReplacePathSeparator("/events/{$reqEventType}.php"));
}
?>
