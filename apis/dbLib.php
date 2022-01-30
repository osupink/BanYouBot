<?php
if (!defined('BotFramework')) {
	return;
}
global $conn;
$conn = new mysqli(DbAddress, DbUsername, DbPassword, DbName);
?>
