<?php
function toJSON(array $arr) {
	return json_encode($arr, JSON_NUMERIC_CHECK) . "\n";
}
?>
