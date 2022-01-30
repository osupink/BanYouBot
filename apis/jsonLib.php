<?php
function toJSON(array $arr) {
	return json_encode($arr, JSON_NUMERIC_CHECK) . "\n";
}
function toObject(string $json) {
	return json_decode($json, false, 512, JSON_NUMERIC_CHECK);
}
function toArray(string $json) {
	return json_decode($json, true, 512, JSON_NUMERIC_CHECK);
}
?>
