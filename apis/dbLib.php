<?php
if (!defined('BotFramework')) {
	die();
}
global $conn;
$conn=new mysqli(DbAddress, DbUsername, DbPassword, DbName);
?>
