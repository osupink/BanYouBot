<?php
if (PHP_SAPI != "cli") {
	die();
}
require_once('botconfig.php');
require_once('nh-api/api/api.php');
define('NHExts', array('j' => 'jpg', 'p' => 'png', 'g' => 'gif'));
function realImage(string $filePath): string {
	foreach (NHExts as $key => $value) {
		$tmpFilePath = str_replace('{EXT}', $value, $filePath);
		if (is_file($tmpFilePath)) {
			return $tmpFilePath;
		}
	}
	return $filePath;
}
$conn = new mysqli(DbAddress, DbUsername, DbPassword, DbName);
$nhtai = new nhtaiAPI();
function kvListGen($arr) {
	global $conn;
	if (count($arr) < 1) {
		return false;
	}
	$keyList = '';
	$valueList = '';
	foreach ($arr as $key => $value) {
		$keyList .= "`{$key}`,";
		$value = $conn->escape_string($value);
		$valueList .= "'{$value}',";
	}
	$keyList = rtrim($keyList, ',');
	$valueList = rtrim($valueList, ',');
	return array($keyList, $valueList);
}
$j=35;
while ($j--) {
	if ($j === 0) {
		break;
	}
	$query = $nhtai->search("language:chinese tag:lolicon", $j);
	if ($query === false) {
		sleep(5);
		continue;
	}
	$search = $nhtai->parseSearch($query);
	if ($search === false || !isset($search->search['result'])) {
		sleep(5);
		continue;
	}
	foreach ($search->search['result'] as $value) {
		$arr=array('id' => (int)$value['id'], 'media_id' => (int)$value['media_id'], 'pages' => (int)$value['num_pages'], 'upload_date' => $value['upload_date'], 'tags' => '', 'title' => $value['title']['pretty'], 'engtitle' => $value['title']['english'], 'jpntitle' => $value['title']['japanese']);
		foreach ($value['tags'] as $tag) {
			$arr['tags'] .= "{$tag['id']}:" . strtolower($tag['type']) . "/{$tag['name']},";
		}
		$arr['tags'] = rtrim($arr['tags'], ',');
		$kvList = kvListGen($arr);
		$dirPath = "nh-api/cache/{$arr['media_id']}";
		if (!is_dir($dirPath) && !mkdir($dirPath)) {
			echo "Make Dir Error: {$dirPath}.\n";
			continue;
		}
		$coverFilePath = realImage("{$dirPath}/cover.{EXT}");
		if ($coverFilePath === "{$dirPath}/cover.{EXT}") {
			$coverImage = false;
			$coverImageType = $value['images']['cover']['t'];
			if (isset(NHExts[$coverImageType])) {
				$coverFilePath = str_replace('{EXT}', NHExts[$coverImageType], $coverFilePath);
				$coverImageURL = $nhtai->getCover($arr['media_id'], NHExts[$coverImageType]);
				$coverImage = @file_get_contents($coverImageURL, false, getContext);
			}
			if ($coverImage === false) {
				echo "Save Cover Image Error (Page ID: {$arr['id']}): {$coverFilePath}.\n";
				continue;
			}
			file_put_contents($coverFilePath, $coverImage);
			echo "Saved Cover Image: {$coverFilePath}\n";
		}
		for ($i=1; $i<=$arr['pages']; $i++) {
			$pageFilePath = realImage("{$dirPath}/{$i}.{EXT}");
			if ($pageFilePath === "{$dirPath}/{$i}.{EXT}") {
				$pageImage = false;
				$pageImageType = $value['images']['pages'][$i-1]['t'];
				if (isset(NHExts[$pageImageType])) {
					$pageFilePath = str_replace('{EXT}', NHExts[$pageImageType], $pageFilePath);
					$pageImageURL = $nhtai->getPage($arr['media_id'], $i, NHExts[$pageImageType]);
					$pageImage = @file_get_contents($pageImageURL, false, getContext);
				}
				if ($pageImage === false) {
					echo "Save Page Image Error (Page ID: {$arr['id']}): {$pageFilePath}.\n";
					continue;
				}
				file_put_contents($pageFilePath, $pageImage);
				echo "Saved Page Image (Page ID: {$arr['id']}): {$pageFilePath}\n";
			}
		}
		if ($conn->query("INSERT IGNORE INTO nhbot_list ({$kvList[0]}) VALUES ({$kvList[1]})")) {
			echo "Added:";
		} else {
			if (isset($conn->error)) {
				echo "{$conn->error}\n";
			}
			echo "DB Error:";
		}
		echo " {$arr['id']}-{$arr['media_id']}: {$arr['title']}.\n";
	}
	#sleep(3600);
}
?>
