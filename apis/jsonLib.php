<?php
function toJSON($arr) {
	return json_encode($arr, JSON_NUMERIC_CHECK) . "\n";
}
?>
