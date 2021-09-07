<?php
if (!defined('BotFramework')) {
	return;
}
$r18 = (!isset($messageType) || $messageType === 1) ? 0 : 1;
if (!isset($tagsQuery)) {
	$tagsQuery = '&tag='. rawurlencode('萝莉');;
}
if (isset($commandFullContent)) {
	$tagsArr = explode('|', $commandFullContent);
	if (count($tagsArr) > 0) {
		foreach ($tagsArr as $value) {
			if (empty($value)) {
				continue;
			}
			$value = trim($value);
			if ($value === '萝莉') {
				continue;
			}
			$tagsQuery .= '&tag=' . rawurlencode($value);
		}
	}
}
$json = @file_get_contents("https://api.lolicon.app/setu/v2?proxy=http://i.pximg.net&r18={$r18}{$tagsQuery}");
if ($json === false) {
	$sendMessageBuffer .= "Error!\n";
	return;
}
$obj = json_decode($json);
if (!isset($obj->data[0]) || !empty($obj->error)) {
	$sendMessageBuffer .= "Nothing was found!\n";
	return;
}
$hImage = $obj->data[0];
$hImageURLArr = explode('/', $hImage->urls->original);
$LastHImageURLArr = end($hImageURLArr);
$hImageFilePath = RootPath . ReplacePathSeparator("/pixiv/cache/{$LastHImageURLArr}");
if (!file_exists($hImageFilePath)) {
	$hImageFile = @file_get_contents($hImage->urls->original, false, stream_context_create(array('http' => array('timeout' => 30, 'proxy' => 'tcp://127.0.0.1:7890', 'request_fulluri' => true, 'header' => "Referer:https://www.pixiv.net"))));
	if ($hImageFile === false) {
		$sendMessageBuffer .= "An error occurred while requesting a image!\n";
		return;
	}
	file_put_contents($hImageFilePath, $hImageFile);
}
$sendMessageBuffer .= "https://www.pixiv.net/artworks/{$hImage->pid}\n{$hImage->author} ({$hImage->uid}) - {$hImage->title}\n";
if (isset($hImage->tags) && count($hImage->tags) > 0) {
	$tagsStr = "Tags: ";
	foreach ($hImage->tags as $value) {
		$tagsStr .= "{$value}, ";
	}
	$tagsStr = rtrim($tagsStr, ', ');
	$tagsStr .= "\n";
	$sendMessageBuffer .= $tagsStr;
}
$sendMessageBuffer .= "[CQ:image,file=file:///{$hImageFilePath}]\n";
?>
