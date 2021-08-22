<?php
if (isset($commandFullContent)) {
	Announce($commandFullContent);
} else {
	if (!empty($sendMessageBuffer)) {
		Announce($sendMessageBuffer);
		$sendMessageBuffer='';
	}
}
?>
