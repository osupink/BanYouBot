<?php
function getbeatmapinfo(string $urlquery, string $sqlquery, bool $force = false, bool $cache = true, bool $needarray = false, bool $skipmode = false, bool $reterr = false) {
	global $conn;
	$res = $conn->query("SELECT 1 FROM osu_bmcacheexp WHERE query = '{$urlquery}' AND UNIX_TIMESTAMP() < UNIX_TIMESTAMP(date)+86400 LIMIT 1");
	if ($res->num_rows > 0) {
		if ($reterr) {
			return false;
		}
		die('error: empty beatmap data');
	}
	if ($needarray) {
		global $beatmaps;
		$beatmaps = [];
	} else {
		if (!$skipmode) {
			global $mode;
		}
		global $beatmapId, $beatmapSetId, $approvedStatus, $totalTime, $hitLength, $approvedDate, $lastUpdate, $diffSize, $diffOverall, $diffApproach, $diffDrain, $artist, $title, $creator, $source, $tags, $genreId, $languageId, $bmmaxcombo, $bmversion, $bmstar, $fileChecksum;
	}
	if (!$force) {
		$res = $conn->query("SELECT beatmap_id, beatmapset_id, approved, total_length, hit_length, approved_date, last_update, diff_size, diff_overall, diff_approach, diff_drain, artist, title, creator, source, tags, mode, genre_id, language_id, max_combo, version, difficultyrating, file_md5 FROM osu_beatmaps WHERE $sqlquery LIMIT 1");
		if ($res->num_rows > 0) {
			list($beatmapId, $beatmapSetId, $approvedStatus, $totalTime, $hitLength, $approvedDate, $lastUpdate, $diffSize, $diffOverall, $diffApproach, $diffDrain, $artist, $title, $creator, $source, $tags, $mode, $genreId, $languageId, $bmmaxcombo, $bmversion, $bmstar, $fileChecksum) = $res->fetch_row();
			if ($needarray) {
				$beatmaps[] = array('beatmap_id' => $beatmapId,'beatmapset_id' => $beatmapSetId,'approved' => $approvedStatus,'total_length' => $totalTime,'hit_length' => $hitLength,'approved_date' => $approvedDate,'last_update' => $lastUpdate,'diff_size' => $diffSize,'diff_overall' => $diffOverall,'diff_approach' => $diffApproach,'diff_drain' => $diffDrain,'artist' => $artist,'title' => $title,'creator' => $creator,'source' => $source,'tags' => $tags,'mode' => $mode,'genre_id' => $genreId,'language_id' => $languageId,'max_combo' => $bmmaxcombo,'version' => $bmversion,'difficultyrating' => $bmstar,'file_md5' => $fileChecksum);
			}
		}
	}
	if (empty($beatmapId) || !$beatmapId || $beatmapId <= 0) {
		$urlrequest = @file_get_contents("https://osu.ppy.sh/api/get_beatmaps?k=".osuAPIKey."&{$urlquery}");
		$json = json_decode($urlrequest);
		if (count($json) < 1) {
			if ($urlrequest !== false) {
				$conn->query("INSERT INTO osu_bmcacheexp (query) VALUES ('{$urlquery}') ON DUPLICATE KEY UPDATE date = IF(UNIX_TIMESTAMP() > UNIX_TIMESTAMP(date)+86400,CURRENT_TIMESTAMP(),VALUES(date))");
			}
			if ($reterr) {
				return false;
			}
			die('error: empty beatmap data');
		}
		foreach ($json as $value) {
			$beatmapId = (int)$value->beatmap_id;
			$beatmapSetId = (int)$value->beatmapset_id;
			$approvedStatus = $value->approved;
			$totalTime = (int)$value->total_length;
			$hitLength = (int)$value->hit_length;
			$approvedDate = $value->approved_date;
			$lastUpdate = $value->last_update;
			$diffSize = (int)$value->diff_size;
			$diffOverall = (int)$value->diff_overall;
			$diffApproach = (int)$value->diff_approach;
			$diffDrain = (int)$value->diff_drain;
			$artist = $value->artist;
			$title = $value->title;
			$creator = $value->creator;
			$source = $value->source;
			$tags = $value->tags;
			$mode = $value->mode;
			$genreId = (int)$value->genre_id;
			$languageId = (int)$value->language_id;
			$bmmaxcombo = (int)$value->max_combo;
			$bmversion = $value->version;
			$bmstar = (double)$value->difficultyrating;
			$fileChecksum = $value->file_md5;
			if (empty($beatmapId) || !$beatmapId || $beatmapId <= 0) {
				die('error: bad beatmap data');
			}
			if ($needarray) {
				$beatmaps[] = array('beatmap_id' => $beatmapId,'beatmapset_id' => $beatmapSetId,'approved' => $approvedStatus,'total_length' => $totalTime,'hit_length' => $hitLength,'approved_date' => $approvedDate,'last_update' => $lastUpdate,'diff_size' => $diffSize,'diff_overall' => $diffOverall,'diff_approach' => $diffApproach,'diff_drain' => $diffDrain,'artist' => $artist,'title' => $title,'creator' => $creator,'source' => $source,'tags' => $tags,'mode' => $mode,'genre_id' => $genreId,'language_id' => $languageId,'max_combo' => $bmmaxcombo,'version' => $bmversion,'difficultyrating' => $bmstar,'file_md5' => $fileChecksum);
			}
			if ($cache) {
				addbmcache($beatmapId, $beatmapSetId, $approvedStatus, $totalTime, $hitLength, $bmversion, $fileChecksum, $diffSize, $diffOverall, $diffApproach, $diffDrain, $mode, $approvedDate, $lastUpdate, $artist, $title, $creator, $source, $tags, $genreId, $languageId, $bmmaxcombo, $bmstar);
			}
		}
	}
	return true;
}
function deletebmcache(int $beatmapId, int $beatmapSetId, string $fileChecksum) {
	global $conn;
	if (empty($beatmapId)) {
		$wherecaluse = "";
		if (!empty($fileChecksum)) {
			$wherecaluse .=  "file_md5 = '$fileChecksum'";
		}
		if (!empty($beatmapSetId)) {
			if (!empty($fileChecksum)) {
				$wherecaluse .= " AND ";
			}
			$wherecaluse .= "beatmapset_id = $beatmapSetId";
		}
		if (empty($wherecaluse)) {
			return;
		}
		$wherecaluse = "WHERE $wherecaluse";
		$res = $conn->query("SELECT beatmap_id, beatmapset_id, file_md5 FROM osu_beatmaps $wherecaluse LIMIT 1");
		list($beatmapId, $beatmapSetId, $fileChecksum) = $res->fetch_row();
	} elseif (empty($fileChecksum)) {
		$res = $conn->query("SELECT beatmap_id, beatmapset_id, file_md5 FROM osu_beatmaps WHERE beatmap_id = $beatmapId LIMIT 1");
		list($beatmapId, $beatmapSetId, $fileChecksum) = $res->fetch_row();
	}
	if (empty($beatmapId)) {
		return;
	}
	$conn->query("DELETE FROM osu_beatmaps WHERE beatmap_id = $beatmapId AND file_md5 = $fileChecksum LIMIT 1");
	if (file_exists("../../osu-beatmap/osu/{$beatmapId}.osu")) {
		unlink("../../osu-beatmap/osu/{$beatmapId}.osu");
	}
	if (file_exists("../cache/score-{$fileChecksum}.txt")) {
		unlink("../cache/score-{$fileChecksum}.txt");
	}
	for ($i=0;$i<=3;$i++) {
		list($modename, $scoretable, $highscoretable, $userstatstable) = getmodeinfo($i);
		$conn->query("DELETE FROM $scoretable WHERE beatmap_id = $beatmapId");
		$conn->query("DELETE FROM $highscoretable WHERE beatmap_id = $beatmapId");
	}
}
function addbmcache(int $beatmapId, int $beatmapSetId, int $approvedStatus, int $totalTime, int $hitLength, string $bmversion, string $fileChecksum, int $diffSize, int $diffOverall, int $diffApproach, int $diffDrain, int $mode, string $approvedDate, string $lastUpdate, string $artist, string $title, string $creator, string $source, string $tags, int $genreId, int $languageId, int $bmmaxcombo, float $bmstar) {
	global $conn;
	$bmversion = $conn->escape_string($bmversion);
	$fileChecksum = $conn->escape_string($fileChecksum);
	$approvedDate = $conn->escape_string($approvedDate);
	$lastUpdate = $conn->escape_string($lastUpdate);
	$artist = $conn->escape_string($artist);
	$title = $conn->escape_string($title);
	$creator = $conn->escape_string($creator);
	$source = $conn->escape_string($source);
	$tags = $conn->escape_string($tags);
	if (!empty($fileChecksum) && $beatmapId > 0 && $beatmapSetId > 0) {
		$conn->query("REPLACE INTO osu_beatmaps (beatmap_id, beatmapset_id, approved, total_length, hit_length, version, file_md5, diff_size, diff_overall, diff_approach, diff_drain, mode, approved_date, last_update, artist, title, creator, source, tags, genre_id, language_id, max_combo, difficultyrating) VALUES ($beatmapId, $beatmapSetId, $approvedStatus, $totalTime, $hitLength, '$bmversion', '$fileChecksum', $diffSize, $diffOverall, $diffApproach, $diffDrain, $mode, '$approvedDate', '$lastUpdate', '$artist', '$title', '$creator', '$source', '$tags', $genreId, $languageId, $bmmaxcombo, $bmstar)");
	}
}
?>
