<?php
if (!defined('MemcacheError') && isset($memcache)) {
	$memcache->close();
}
?>
