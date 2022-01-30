<?php
if (!defined('BotFramework')) {
	return;
}
define('NHExts', array('j' => 'jpg', 'p' => 'png', 'g' => 'gif'));
function is_image(string $filePath): string {
	foreach (NHExts as $key => $value) {
		$tmpFilePath = str_replace('{EXT}', $value, $filePath);
		if (is_file($tmpFilePath)) {
			return $tmpFilePath;
		}
	}
	return '';
}
global $lang;
if (!$isMaster) {
	$username = isBindID($reqQQNumber,$sendMessageBuffer, false);
	if (!isset($memcache)) {
		$memcache = new Memcache;
		if (!$memcache->connect(MemcacheAddress, MemcachePort)) {
			define('MemcacheError', true);
		}
	}
	if (defined('MemcacheError')) {
		$sendMessageBuffer .= "Cache System Error!\n";
		return;
	}
	$memcacheKey = "BanYouBot.h.{$reqQQNumber}";
	$hUsageCount = $memcache->get($memcacheKey);
	if ($hUsageCount >= 100 || (empty($username) && $hUsageCount >= 10)) {
		$sendMessageBuffer .= "The maximum usage count have been reached today.\n";
		return;
	}
}
$page_id = 0;
$page_num = -1;
if (isset($commandArr) && is_numeric($commandArr[0])) {
	$page_num = (int)$commandArr[0];
	if ($page_num < 1) {
		$page_num = 1;
	}
	if (is_numeric($commandSubType)) {
		$page_id = (int)$commandSubType;
	}
} elseif (is_numeric($commandFullContent)) {
	$page_id = (int)$commandFullContent;
}
if ($page_id === 0 || $page_num === -1) {
	//$res = $conn->query("SELECT id, media_id, pages, tags, engtitle FROM nhbot_list WHERE ". (($page_id !== 0 && $page_num === -1) ? "id = {$page_id}" : "id >= (SELECT FLOOR(RAND()*((SELECT MAX(id) FROM nhbot_list)-(SELECT MIN(id) FROM nhbot_list))+(SELECT MIN(id) FROM nhbot_list))) ORDER BY id") . " LIMIT 1");
	$res = $conn->query("SELECT id, media_id, pages, tags, engtitle FROM nhbot_list ". (($page_id !== 0 && $page_num === -1) ? "WHERE id = {$page_id}" : "ORDER BY RAND()") . " LIMIT 1");
	if (!($res && $res->num_rows > 0)) {
		$sendMessageBuffer .= "{$lang['h_not_found']}\n";
		return;
	}
	list($page_id, $media_id, $pages, $tags, $engtitle) = $res->fetch_row();
	$coverImagePath = is_image(RootPath . ReplacePathSeparator("/nh-api/cache/{$media_id}/cover.{EXT}"));
	if (empty($coverImagePath)) {
		$sendMessageBuffer .= "{$lang['h_not_found']}\n";
		return;
	}
	$availablePages = array();
	for ($i=1;$i<=(int)$pages;$i++) {
		$pageImagePath = is_image(RootPath . ReplacePathSeparator("/nh-api/cache/{$media_id}/{$i}.{EXT}"));
		if (!empty($pageImagePath)) {
			$availablePages[] = $i;
		}
	}
	if (count($availablePages) < 1) {
		$availablePagesStr = 'None';
	} else {
		if (array_values($availablePages) === range(1, (int)$pages)) {
			$availablePagesStr = "1-{$pages}";
		} else {
			$availablePagesStr = implode(', ', $availablePages);
		}
	}
	if (!$isMaster) {
		if (!$hUsageCount) {
			$memcache->set($memcacheKey, 1, 0, MemcacheTime);
		} else {
			$memcache->increment($memcacheKey, 1);
		}
	}
	$sendMessageBuffer .= "Page ID: {$page_id}\nMedia ID: {$media_id}\nTotal Pages: {$pages}\nAvailable Pages: {$availablePagesStr}\nEnglish Title: {$engtitle}\n";
	$tagsSplit = explode(',', $tags);
	if (count($tagsSplit) > 0) {
		$tagsStr = '';
		$tagsList = array();
		foreach ($tagsSplit as $tag) {
			$tagSplit = explode(':', $tag);
			$nvSplit = explode('/', $tagSplit[1]);
			$tagsList[ucwords($nvSplit[0])][] = $nvSplit[1];
		}
		foreach ($tagsList as $key => $value) {
			$tagsStr .= "{$key}: ";
			foreach ($value as $tag) {
				$tagsStr .=  "{$tag}, ";
			}
			$tagsStr = rtrim($tagsStr, ', ');
			$tagsStr .= "\n";
		}
		$sendMessageBuffer .= $tagsStr;
	}
	$sendMessageBuffer .= "[CQ:image,file=file:///{$coverImagePath}]\n";
	return;
}
if ($page_num === -1) {
	$page_num = 1;
}
$res = $conn->query("SELECT media_id, pages, engtitle FROM nhbot_list WHERE id = {$page_id} AND pages >= {$page_num} LIMIT 1");
if (!($res && $res->num_rows > 0)) {
	$sendMessageBuffer .= "{$lang['h_not_found']}\n";
	return;
}
list($media_id, $pages, $engtitle) = $res->fetch_row();
$pageImagePath = is_image(RootPath . ReplacePathSeparator("/nh-api/cache/{$media_id}/{$page_num}.{EXT}"));
if (empty($pageImagePath)) {
	$sendMessageBuffer .= "{$lang['h_not_found']}\n";
	return;
}
if (!$isMaster) {
	if (!$hUsageCount) {
		$memcache->set($memcacheKey, 1, 0, MemcacheTime);
	} else {
		$memcache->increment($memcacheKey, 1);
	}
}
$sendMessageBuffer .= "{$page_num}/{$pages} {$engtitle}\n";
$sendMessageBuffer .= "[CQ:image,file=file:///{$pageImagePath}]\n";
?>
