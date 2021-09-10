<?php
if (!defined('BotFramework')) {
	return;
}
if (isset($reqJSONArr->meta_event_type)) {
	$event = strtolower($reqJSONArr->meta_event_type);
	if (is_file("events/meta_events/{$event}.php")) {
		AddDebugInfo("Loading events/meta_events/{$event}.php...");
		require_once("events/meta_events/{$event}.php");
	}
}
?>
