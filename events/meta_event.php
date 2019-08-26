<?php
if (!defined('BotFramework')) {
	die();
}
if (isset($reqJSONArr->meta_event_type) && strtolower($reqJSONArr->meta_event_type) === 'heartbeat') {
	require_once('meta_events/heartbeat.php');
}
?>
