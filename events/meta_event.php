<?php
if ($reqJSONArr->meta_event_type === 'heartbeat') {
	require_once('meta_events/heartbeat.php');
}
?>
