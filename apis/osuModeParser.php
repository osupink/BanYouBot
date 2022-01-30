<?php
function setGameMode(int $mode): int {
    global $scoreTable, $highScoreTable, $userStatsTable, $leadersTable, $replaysTable, $tableSuffix, $modeName;
    switch ($mode) {
        case 1:
            //Taiko
            $tableSuffix = "_taiko";
            $modeName = "Taiko";
            break;
        case 2:
            //CatchTheBeat
            $tableSuffix = "_fruits";
            $modeName = "Catch the Beat";
            break;
        case 3:
            //osu!mania
            $tableSuffix = "_mania";
            $modeName = "osu!mania";
            break;
        case 0:
        default:
            //OsuStandard
            $tableSuffix = "";
            $modeName = "osu!";
            break;
    }

    //Table name constants
    $scoreTable = "osu_scores$tableSuffix";
    $highScoreTable = $scoreTable."_high";
    $leadersTable = "osu_leaders$tableSuffix";
    $userStatsTable = "osu_user_stats$tableSuffix";
    $replaysTable = "osu_replays$tableSuffix";

    return $mode;
}
function getShortModString(int $modVal, bool $hideNone, bool $withcomma = true, bool $retnothing = false): string {
    $modList = "";
    if (($modVal & 1) > 0) {
        $modList .= "NF" . ($withcomma ? "," : "");
    }

    if (($modVal & 2) > 0) {
        $modList .= "EZ" . ($withcomma ? "," : "");
    }

    if (($modVal & 8) > 0) {
        $modList .= "HD" . ($withcomma ? "," : "");
    }

    if (($modVal & 1048576) > 0) {
        $modList .= "FI" . ($withcomma ? "," : "");
    }

    if (($modVal & 16) > 0) {
        $modList .= "HR" . ($withcomma ? "," : "");
    }

    if (($modVal & 512) > 0) {
        $modList .= "NC" . ($withcomma ? "," : "");
    } else if (($modVal & 64) > 0) {
        $modList .= "DT" . ($withcomma ? "," : "");
    }

    if (($modVal & 128) > 0) {
        $modList .= "Relax" . ($withcomma ? "," : "");
    }

    if (($modVal & 256) > 0) {
        $modList .= "HT" . ($withcomma ? "," : "");
    }

    if (($modVal & 1024) > 0) {
        $modList .= "FL" . ($withcomma ? "," : "");
    }

    if (($modVal & 4096) > 0) {
        $modList .= "SO" . ($withcomma ? "," : "");
    }

    if (($modVal & 8192) > 0) {
        $modList .= "AP" . ($withcomma ? "," : "");
    }

    if (($modVal & 16384) > 0) {
        $modList .= "PF" . ($withcomma ? "," : "");
    } else if (($modVal & 32) > 0) {
        $modList .= "SD" . ($withcomma ? "," : "");
    }

    if (($modVal & 32768) > 0) {
        $modList .= "4K" . ($withcomma ? "," : "");
    } else if (($modVal & 65536) > 0) {
        $modList .= "5K" . ($withcomma ? "," : "");
    } else if (($modVal & 131072) > 0) {
        $modList .= "6K" . ($withcomma ? "," : "");
    } else if (($modVal & 262144) > 0) {
        $modList .= "7K" . ($withcomma ? "," : "");
    } else if (($modVal & 524288) > 0) {
        $modList .= "8K" . ($withcomma ? "," : "");
    } else if (($modVal & 16777216) > 0) {
        $modList .= "9K" . ($withcomma ? "," : "");
    }

    if (strlen($modList) === 0) {
        if ($retnothing) {
            return '';
        }
        if (!$hideNone) {
            $modList = "None";
        }
    }

    if (strlen($modList) > 0) {
        $modList = trim($modList, ",");
    }

    return $modList;
}
?>
