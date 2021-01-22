<?php
if (!defined('BotFramework')) {
	die();
}
if (isset($reqJSONArr->meta_event_type)) {
	$event=strtolower($reqJSONArr->meta_event_type);
	if (is_file("meta_events/{$event}.php")) {
		require_once("meta_events/{$event}.php");
	}
}
?>
