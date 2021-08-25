<?php
if (!defined('BotFramework') || !isset($reqEventType)) {
	return;
}
if (is_file(RootPath . ReplacePathSeparator("/events/{$reqEventType}.php"))) {
	AddDebugInfo("Loading events/{$reqEventType}.php...");
	require_once(RootPath . ReplacePathSeparator("/events/{$reqEventType}.php"));
}
?>
