<?php
if (!($_SERVER['REMOTE_ADDR'] === '127.0.0.1' && strtoupper($_SERVER['REQUEST_METHOD']) === 'POST')) {
	die();
}
?>
