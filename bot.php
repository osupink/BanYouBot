<?php
date_default_timezone_set('Asia/Shanghai');
require_once('include.key.php');
require_once('lang.php');
require_once('web/include.notice.php');
set_error_handler('botErrorHandler');
define('BanYouDomain','osupink.com');
define('MaxFriendsCount',32);
define('APIURL','http://127.0.0.1:5700');
function botErrorHandler($errno,$errstr,$errfile,$errline,$errcontext) {
	switch ($errno)
	{
		case E_ERROR:               $type = "Error";                  break;
		case E_WARNING:             $type = "Warning";                break;
		case E_PARSE:               $type = "Parse Error";            break;
		case E_NOTICE:              $type = "Notice";                 break;
		case E_CORE_ERROR:          $type = "Core Error";             break;
		case E_CORE_WARNING:        $type = "Core Warning";           break;
		case E_COMPILE_ERROR:       $type = "Compile Error";          break;
		case E_COMPILE_WARNING:     $type = "Compile Warning";        break;
		case E_USER_ERROR:          $type = "User Error";             break;
		case E_USER_WARNING:        $type = "User Warning";           break;
		case E_USER_NOTICE:         $type = "User Notice";            break;
		case E_STRICT:              $type = "Strict Notice";          break;
		case E_RECOVERABLE_ERROR:   $type = "Recoverable Error";      break;
		default:                    $type = "Unknown error ($errno)"; break;
	}
	addnotice("New $type occurred in BanYouBot","$errstr ($errfile:$errline)");
}
function toJSON($arr) {
	return json_encode($arr, JSON_NUMERIC_CHECK)."\n";
}
function sendGroupMessage($groupNumber, $message) {
	$message=rawurlencode($message);
	file_get_contents(APIURL."/send_group_msg_async?group_id={$groupNumber}&message={$message}");
}
function sendMessage($qqNumber, $message) {
	$message=rawurlencode($message);
	file_get_contents(APIURL."/send_private_msg_async?user_id={$qqNumber}&message={$message}");
}
function sendTempMessage($groupNumber, $qqNumber, $message) {
}
function decodeCQCode($str) {
	return str_replace(['&amp;','&#91;','&#93;','&#44;'],['&','[',']',','],$str);
}
function isBanSay() {
	if (file_exists('bansay')) {
		return 1;
	}
	return 0;
}
function ChangeSayStatus() {
	if (isBanSay()) {
		unlink('bansay');
		return 0;
	} else {
		file_put_contents('bansay','1');
		return 1;
	}
}
function GetCityXY($city) {
	global $conn;
	$city=sqlstr($city);
	list($cityx,$cityy)=$conn->queryRow("SELECT city_x, city_y FROM weather_city WHERE city_county = '{$city}' LIMIT 1",1);
	if ($cityx <= 0) {
		return 0;
	}
	return array($cityx,$cityy);
}
function GetNormalWeather($city) {
	$cityxy=GetCityXY($city);
	if (!is_array($cityxy)) {
		return 0;
	}
	list($cityx,$cityy)=$cityxy;
	$json=json_decode(file_get_contents("http://api.caiyunapp.com/v2/".WeatherAPIKey."/{$cityx},{$cityy}/realtime.json?unit=metric:v2"));
	if (strtolower($json->status) != "ok") {
		return 0;
	}
	$result=$json->result;
	if (strtolower($result->status) != "ok") {
		return 0;
	}
	$arr=array('temperature'=>$result->temperature, 'skycon'=>$result->skycon,'windspeed'=>$result->wind->speed, 'pm25'=>$result->pm25, 'cloudrate'=>$result->cloudrate, 'humidity'=>$result->humidity);
	if (isset($result->precipitation->nearest) && strtolower($result->precipitation->nearest->status) == "ok") {
		if ($result->precipitation->nearest->intensity != 0) {
			$arr['nearest']=array('distance'=>$result->precipitation->nearest->distance, 'intensity'=>$result->precipitation->nearest->intensity);
		}
	}
	return $arr;
}
function AddMoneyEvent($type,$qq,$money) {
	global $conn;
	if (is_numeric($qq) && (is_float($money) || is_numeric($money))) {
		if ($conn->exec("INSERT INTO osu_pay (`type`,`qq`,`money`) VALUES ('{$type}',{$qq},{$money})")) {
			return $conn->queryOne("SELECT LAST_INSERT_ID()");
		}
	}
	return 0;
}
function AddBuyEvent($qq,$store_id,$pay_id) {
	global $conn;
	if (is_numeric($qq) && is_numeric($store_id) && is_numeric($pay_id)) {
		return $conn->exec("INSERT INTO osu_store_bill (`qq`,`store_id`,`pay_id`) VALUES ({$qq},{$store_id},{$pay_id})");
	}
	return 0;
}
function DeleteStoreBill($qq,$goodsid) {
	global $conn;
	if (!is_numeric($goodsid)) {
		return 0;
	}
	if ($conn->exec("DELETE FROM osu_store_bill WHERE qq = {$qq} AND store_id = {$goodsid} LIMIT 1")) {
		return 1;
	}
	return 0;
}
function GiveBackMoney($payid) {
	global $conn;
	if (!is_numeric($payid)) {
		return 0;
	}
	if ($conn->exec("DELETE FROM osu_store_bill WHERE pay_id = {$payid} LIMIT 1") && $conn->exec("DELETE FROM osu_pay WHERE id = {$payid} LIMIT 1")) {
		return 1;
	}
	return 0;
}
function GetCurMoney($qq) {
	global $conn;
	if (!is_numeric($qq)) {
		return 0;
	}
	return $conn->queryOne("SELECT SUM(money) FROM osu_pay WHERE qq = {$qq}");
}
function GetRandomNumber($maxNumber) {
	$maxRandomNumber=mt_getrandmax();
	if ($maxNumber > $maxRandomNumber) {
		$maxNumber=$maxRandomNumber;
	}
	$randomNumber=mt_rand(1,$maxNumber);
	return $randomNumber;
}
function GetPlayerRankByUserID($mode,$userid) {
	global $conn,$userStatsTable;
	$mode=(int)$mode;
	setGameMode($mode);
	$scoreType=($mode == 2) ? 'ranked_score' : 'rank_score';
	$playerPPScore=$conn->queryOne("SELECT {$scoreType} FROM {$userStatsTable} WHERE user_id = {$userid} LIMIT 1");
	if (!empty($playerPPScore)) {
		$rank=$conn->queryOne("SELECT count(*)+1 FROM {$userStatsTable} us JOIN osu_users u USING (user_id) WHERE us.user_id != {$userid} AND us.{$scoreType} > {$playerPPScore} AND NOT EXISTS (SELECT 1 FROM osu_user_banhistory WHERE user_id = us.user_id LIMIT 1)");
		if (!empty($rank)) {
			return $rank;
		}
	}
	return 0;
}
function GetUserIDByUsername($username) {
	global $conn;
	$username=sqlstr($username);
	$userid=$conn->queryOne("SELECT user_id FROM osu_users WHERE username = '{$username}' LIMIT 1");
	if ($userid !== 0) {
		return $userid;
	}
	return 0;
}
function isGroup($isGroup) {
	return (($isGroup === 1) ? 1 : 0);
}
function isAT($str) {
	if (preg_match('/^\[CQ:at,qq=(\d*)\]$/', $str, $matches)) {
		return (int)$matches[1];
	}
	return $str;
}
function isBindID($QQNumber,&$text) {
	global $lang;
	$username=GetUsernameByQQ($QQNumber);
	if (!$username) {
		$text.="{$lang['need_bindid']}\n";
		return 0;
	}
	return $username;
}
function TrimMultiSpace($str) {
	$str=preg_replace('/ {2,}/', ' ', $str);
	return $str;
}
function GetCurFullPath($filename) {
	return getcwd().DIRECTORY_SEPARATOR.$filename;
}
function GetUsernameByQQ($QQNumber) {
	global $conn;
	if (!empty($QQNumber)) {
		if (is_numeric($QQNumber)) {
			$username=$conn->queryOne("SELECT username FROM osu_users WHERE user_qq = {$QQNumber} LIMIT 1");
		}
		if (!empty($username)) {
			return $username;
		}
	}
	return 0;
}
function GetQQByUsername($username) {
	global $conn;
	if (!empty($username)) {
		$username=sqlstr($username);
		$QQ=$conn->queryOne("SELECT user_qq FROM osu_users WHERE username = '{$username}' LIMIT 1");
		if (!empty($QQ)) {
			return $QQ;
		}
	}
	return 0;
}
function isAllowGroupMessage() {
	global $groupNumberList,$groupNumber;
	if (!isset($groupNumber)) {
		return 1;
	}
	return in_array($groupNumber,$groupNumberList);
}
function CheckCommandBlacklist($command,$admin=1) {
	// 0:不在黑名单, 1:指令黑名单, 2:QQ/群组黑名单.
	global $conn,$masterQQ,$isMaster,$groupNumberList,$groupNumber,$qqNumber,$devGroupNumber,$mainGroupNumber;
	if ($isMaster && $admin) {
		return 0;
	}
	if (isBanSay()) {
		return 2;
	}
	switch ($command) {
		case 'help':
		case 'roll':
		case 'weather':
		case 'br':
			break;
		case 'sleep':
			if (!in_array($groupNumber,$groupNumberList)) {
				return 2;
			}
			break;
		case 'botadmin':
			if (!$conn->queryOne("SELECT 1 FROM bot_groupinfo WHERE group_number = {$groupNumber} AND bot_fakeadmin = {$qqNumber} LIMIT 1")) {
				return 1;
			}
			break;
		case 'atall':
		case 'getkey':
		case 'bansay':
		case 'fs':
		case 'announce':
			return 1;
			break;
		default:
			if (!isAllowGroupMessage()) {
				return 2;
			}
			break;
	}
	return 0;
}
function CheckSilenceList($fullmessage) {
	// 0:不在禁言名单, 其它:禁言分钟数
	global $conn,$masterQQ,$qqNumber,$groupNumber;
	switch ($fullmessage) {
		/*
		case '[image=A2DA722F8EAD905AC7883C6E4CDB85D3.jpg]':
			if ($_POST['QQ'] == "2839098896") {
				return 1;
			}
			break;
		*/
		default:
			if ($qqNumber != $masterQQ) {
				$blockTime=$conn->queryOne("SELECT BlockTime FROM bot_blockqqlist WHERE group_number = {$groupNumber} AND BlockQQ = {$qqNumber} LIMIT 1");
				if ($blockTime !== false && $blockTime == "0") {
					Kick($groupNumber,$qqNumber);
					return -1;
				}
				if ($blockTime) {
					return $blockTime;
				}
				if ($conn->queryOne("SELECT 1 FROM bot_groupinfo WHERE group_number = {$groupNumber} AND bot_fakeadmin = {$qqNumber} LIMIT 1")) {
					break;
				}
				$lowerfullmessage=strtolower($fullmessage);
				if ($conn->queryOne("SELECT 1 FROM bot_blocktextlist WHERE group_number = {$groupNumber} AND LOCATE(BlockText,'{$lowerfullmessage}') > 0 LIMIT 1")) {
					return 10;
				}
			}
			break;
	}
	return 0;
}
function isAnonymous($QQNumber) {
	if ($QQNumber == 80000000) {
		return 1;
	}
	return 0;
}
function Silence($groupNumber,$QQNumber,$silenceTime) {
	// 单位为秒
	if (isAnonymous($QQNumber)) {
		return;
	}
	file_get_contents(APIURL."/set_group_ban_async?group_id={$groupNumber}&user_id={$QQNumber}&duration={$silenceTime}");
}
function Kick($groupNumber,$QQNumber) {
	if (isAnonymous($QQNumber)) {
		return;
	}
	file_get_contents(APIURL."/set_group_kick_async?group_id={$groupNumber}&user_id={$QQNumber}");
}
function Announce($str) {
	global $groupNumberList;
	foreach ($groupNumberList as $value) {
		sendGroupMessage($value, $str);
	}
}
function ChangeCard($QQNumber,$card) {
	global $groupNumber;
	if (isAnonymous($QQNumber)) {
		return;
	}
	$card=rawurlencode($card);
	file_get_contents(APIURL."/set_group_card_async?group_id={$groupNumber}&user_id={$QQNumber}&card={$card}");
}
function CheckEvent() {
	global $conn,$groupNumberList,$devGroupNumber,$mainGroupNumber,$scoreTable,$highScoreTable;
	if (file_exists('lastEventID')) {
		$lastEventID=file_get_contents('lastEventID');
		$eventList=$conn->queryAll("SELECT e.id, e.mode as mode, m.modename as modename, e.user_id as user_id, u.username as username, e.beatmap_id as beatmap_id, b.beatmapset_id as beatmapset_id, e.text as ranknumber, CONCAT(IF(b.artist != '',CONCAT(b.artist,' - ',b.title),b.title)) as beatmap_name, b.version as version, b.hit_length as hit_length, b.total_length as total_length, REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(et.`zh-ubbrule`,'{user_id}',e.user_id),'{username}',u.username),'{text}',e.text),'{beatmap_id}',e.beatmap_id),'{mode}',e.mode),'{artist}',IF(b.artist != '',CONCAT(b.artist,' - '),'')),'{title}',b.title),'{version}',b.version),'{modename}',m.modename) as text FROM osu_events e JOIN osu_users u USING (user_id) JOIN osu_beatmaps b USING (beatmap_id) JOIN osu_events_type et USING (type) JOIN osu_modes m ON m.id = e.mode WHERE e.type = 1 AND e.id > {$lastEventID} ORDER BY e.id");
		foreach ($eventList as $value) {
			setGameMode($value['mode']);
			list($scoreID,$rank,$modsnumber,$finalpp,$score)=$conn->queryRow("SELECT score_id, rank, enabled_mods, pp, score FROM {$highScoreTable} WHERE user_id = {$value['user_id']} AND beatmap_id = {$value['beatmap_id']} LIMIT 1",1);
			$pp=$conn->queryOne("SELECT pp FROM {$scoreTable} WHERE score_id = {$scoreID} LIMIT 1");
			$rank=str_replace('H','+Hidden',str_replace('X','SS',$rank));
			if ($value['mode'] == 2) {
				$fullpptext="{$lang['score']}{$lang['colon']}{$score}";
			} else {
				$pp=sprintf('%.2f',$pp);
				$finalpp=sprintf('%.2f',$finalpp);
				$fullpptext="{$pp}pp({$finalpp}pp)";
			}
			$value['text']=str_replace('{score_id}',$scoreID,$value['text']);
			$value['text']=str_replace('{username}',$value['username'],$value['text']);
			$QQNumber=0;
			//$QQNumber=GetQQByUsername($value['username']);
			$value['text']=str_replace('{display_username}',($QQNumber !== 0 ? "[CQ:at,qq={$QQNumber}]" : $value['username']),$value['text']);
			$value['text']=str_replace('{ue_username}',rawurlencode($value['username']),$value['text']);
			$value['text']=str_replace('{rank}',$rank,$value['text']);
			$value['text']=str_replace('{pporscore}',$fullpptext,$value['text']);
			$value['text']=str_replace('{ranknumber}',$value['ranknumber'],$value['text']);
			$value['text']=str_replace('{beatmap_id}',$value['beatmap_id'],$value['text']);
			$value['text']=str_replace('{beatmapset_id}',$value['beatmapset_id'],$value['text']);
			$value['text']=str_replace('{beatmap_name}',$value['beatmap_name'],$value['text']);
			$value['text']=str_replace('{version}',$value['version'],$value['text']);
			$value['text']=str_replace('{mode}',$value['mode'],$value['text']);
			$value['text']=str_replace('{modename}',$value['modename'],$value['text']);
			$value['text']=str_replace('{hit_length}',$value['hit_length'],$value['text']);
			$value['text']=str_replace('{total_length}',$value['total_length'],$value['text']);
			$value['text']=str_replace('{mods}',getShortModString($modsnumber,0),$value['text']);
			foreach ($groupNumberList as $tmpNumber) {
				if ($tmpNumber == $devGroupNumber) {
					continue;
				}
				sendGroupMessage($tmpNumber, $value['text']);
			}
		}
	}
	$lastEventID=$conn->queryOne("SELECT id FROM osu_events ORDER BY id DESC LIMIT 1");
	file_put_contents('lastEventID', $lastEventID);
}
function PrivateCommands($splitarr,$messagearr,$messagecount,&$text) {
	global $conn,$lang,$masterQQ,$isMaster,$groupNumber,$qqNumber,$devGroupNumber,$mainGroupNumber,$groupNumberList,$commandhelp,$highScoreTable,$scoreTable;
	switch (strtolower($messagearr[0])) {
		case 'getkey':
			if ($messagearr > 1) {
				$text.=hash('sha512',ClientAccKey." {$messagearr[1]} ".ClientAccKey);
			}
		default:
			return 0;
			break;
	}
	return 1;
}
function GroupCommands($splitarr,$messagearr,$messagecount,&$text) {
	global $conn,$lang,$masterQQ,$isMaster,$jsonarr,$selfQQ,$groupNumber,$qqNumber,$devGroupNumber,$mainGroupNumber,$groupNumberList,$commandhelp,$highScoreTable,$scoreTable;
	switch (strtolower($messagearr[0])) {
		case 'atall':
			$text.="[CQ:at,qq=all] ";
			break;
		case 'sleep':
			unset($messagearr[0]);
			$silenceTime=(count($messagearr) > 0) ? intval(implode(' ',$messagearr)) : 0;
			if ($silenceTime == 0 && count($messagearr) < 1) {
				$silenceTime=43200;
			} elseif ($silenceTime >= 1 && $silenceTime <= 1440) {
				$silenceTime*=60;
			} else {
				$silenceTime=60;
			}
			if ($silenceTime <= 0) {
				break;
			}
			Silence($groupNumber,$qqNumber,$silenceTime);
			sendGroupMessage($groupNumber, '走好不送');
			break;
		case 'fs':
			if (count($splitarr) < 2) {
				break;
			}
			if (!isset($splitarr[2])) {
				$splitarr[2]=10;
			}
			$splitarr[1]=isAT($splitarr[1]);
			for ($i=0;$i<$splitarr[2];$i++) {
				Silence($groupNumber,$splitarr[1],600);
				Silence($groupNumber,$splitarr[1],0);
			}
			break;
		case 'botadmin':
			if ($qqNumber != $masterQQ && !$conn->queryOne("SELECT 1 FROM bot_groupinfo WHERE group_number = {$groupNumber} AND bot_fakeadmin = {$qqNumber} LIMIT 1")) {
				$text.="{$lang['fake_admin']}\n";
				break;
			}
			if (count($splitarr) < 2) {
				foreach ($commandhelp['botadmin'] as $value) {
					$text.="{$value[0]} - {$value[1]}\n";
				}
				break;
			}
			$subtype=$splitarr[1];
			unset($splitarr[0],$splitarr[1]);
			$splitarr=array_merge($splitarr);
			switch (strtolower($subtype)) {
				case 'blockqq':
					if (count($splitarr) < 1) {
						$text.="{$lang['usage']}{$lang['colon']}{$commandhelp['botadmin']['blockqq'][0]}.\n";
						break 2;
					}
					$splitarr[0]=isAT($splitarr[0]);
					if (!is_numeric($splitarr[0]) || strlen($splitarr[0]) > 11 || strlen($splitarr[0]) < 5) {
						$text.="{$lang['not_a_true_qqnumber']}\n";
						break 2;
					}
					if (count($splitarr) > 1) {
						if (!is_numeric($splitarr[1]) || strlen($splitarr[1]) > 4 || strlen($splitarr[1]) < 1) {
							$text.="{$lang['not_a_true_silence_time']}\n";
							break 2;
						}
						$silenceTime=(int)$splitarr[1];
					} else {
						$silenceTime=0;
					}
					$blockQQNumber=(int)$splitarr[0];
					if (!$isMaster && $conn->queryOne("SELECT 1 FROM bot_groupinfo WHERE group_number = {$groupNumber} AND bot_fakeadmin = {$blockQQNumber} LIMIT 1")) {
						$text.="{$lang['not_a_true_qqnumber']}\n";
						break 2;
					}
					$conn->exec("INSERT INTO bot_blockqqlist VALUES ({$groupNumber},{$blockQQNumber},{$silenceTime})");
					break;
				case 'blocktext':
					if (count($splitarr) < 1) {
						$text.="{$lang['usage']}{$lang['colon']}{$commandhelp['botadmin']['blocktext'][0]}.\n";
						break 2;
					}
					$blockstr=sqlstr(implode(' ',$splitarr));
					if (strlen($blockstr) > 400) {
						$text.="{$lang['usage']}{$lang['colon']}{$commandhelp['botadmin']['blocktext'][0]}.\n";
						break 2;
					}
					$conn->exec("INSERT INTO bot_blocktextlist VALUES ({$groupNumber},'{$blockstr}')");
					break;
				case 'unblockqq':
					if (count($splitarr) < 1) {
						$text.="{$lang['usage']}{$lang['colon']}{$commandhelp['botadmin']['unblockqq'][0]}.\n";
						break 2;
					}
					$blockQQNumber=(int)isAT($splitarr[0]);
					$conn->exec("DELETE FROM bot_blockqqlist WHERE group_number = {$groupNumber} AND BlockQQ = {$blockQQNumber} LIMIT 1");
					break;
				case 'unblocktext':
					if (count($splitarr) < 1) {
						$text.="{$lang['usage']}{$lang['colon']}{$commandhelp['botadmin']['unblocktext'][0]}.\n";
						break 2;
					}
					$blockstr=sqlstr(implode(' ',$splitarr));
					if (strlen($blockstr) > 400) {
						$text.="{$lang['usage']}{$lang['colon']}{$commandhelp['botadmin']['unblocktext'][0]}.\n";
						break 2;
					}
					$conn->exec("DELETE FROM bot_blocktextlist WHERE group_number = {$groupNumber} AND BlockText = '{$blockstr}' LIMIT 1");
					break;
				case 'blockqqlist':
					$blockQQList=$conn->queryAll("SELECT BlockQQ, BlockTime FROM bot_blockqqlist WHERE group_number = {$groupNumber}");
					if (count($blockQQList) < 1) {
						$text.="{$lang['have_not_blockqqlist']}\n";
						break 2;
					}
					foreach ($blockQQList as $value) {
						$text.="QQ: {$value['BlockQQ']}, ";
						if ($groupNumber == $mainGroupNumber) {
							$osuID=$conn->queryOne("SELECT username FROM osu_users WHERE user_qq = {$value['BlockQQ']} LIMIT 1");
							if (!empty($osuID)) {
								$text.="BanYou(osu!) ID: {$osuID}, ";
							}
						}
						$text.="Silence Time: {$value['BlockTime']}.\n";
					}
					break;
				case 'blocktextlist':
					$blockTextList=$conn->queryAll("SELECT BlockText FROM bot_blocktextlist WHERE group_number = {$groupNumber}");
					if (count($blockTextList) < 1) {
						$text.="{$lang['have_not_blocktextlist']}\n";
						break 2;
					}
					foreach ($blockTextList as $value) {
						$text.="Text: {$value['BlockText']}.\n";
					}
					break;
				case 'kick':
					if (count($splitarr) < 1) {
						$text.="{$lang['usage']}{$lang['colon']}{$commandhelp['botadmin']['kick'][0]}.\n";
						break 2;
					}
					$kickQQNumber=(int)isAT($splitarr[0]);
					if (!$isMaster && $conn->queryOne("SELECT 1 FROM bot_groupinfo WHERE group_number = {$groupNumber} AND bot_fakeadmin = {$kickQQNumber} LIMIT 1")) {
						$text.="{$lang['usage']}{$lang['colon']}{$commandhelp['botadmin']['kick'][0]}.\n";
						break 2;
					}
					Kick($groupNumber,$kickQQNumber);
					break;
				case 'silence':
					if (count($splitarr) < 1) {
						$text.="{$lang['usage']}{$lang['colon']}{$commandhelp['botadmin']['silence'][0]}.\n";
						break 2;
					}
					$silenceQQNumber=(int)isAT($splitarr[0]);
					if (!$isMaster && $conn->queryOne("SELECT 1 FROM bot_groupinfo WHERE group_number = {$groupNumber} AND bot_fakeadmin = {$silenceQQNumber} LIMIT 1")) {
						$text.="{$lang['usage']}{$lang['colon']}{$commandhelp['botadmin']['silence'][0]}.\n";
						break 2;
					}
					$silenceTime=(count($splitarr) > 1) ? (int)$splitarr[1] : 1;
					if ($silenceTime > 0 && $silenceTime <= 43200) {
						$silenceTime*=60;
					} else {
						$silenceTime=60;
					}
					Silence($groupNumber,$silenceQQNumber,$silenceTime);
					break;
				case 'unsilence':
					if (count($splitarr) < 1) {
						$text.="{$lang['usage']}{$lang['colon']}{$commandhelp['botadmin']['unsilence'][0]}.\n";
						break 2;
					}
					$unSilenceQQNumber=(int)isAT($splitarr[0]);
					Silence($groupNumber,$unSilenceQQNumber,0);
				case 'changecard':
					if (count($splitarr) < 1) {
						$text.="{$lang['usage']}{$lang['colon']}{$commandhelp['botadmin']['changecard'][0]}.\n";
						break 2;
					}
					if (count($splitarr) < 2) {
						ChangeCard(($isMaster ? $selfQQ : $qqNumber),implode(' ',$splitarr));
					} else {
						$changeQQNumber=(!is_numeric($splitarr[0])) ? (int)isAT($splitarr[0]) : $splitarr[0];
						if (!$isMaster && $conn->queryOne("SELECT 1 FROM bot_groupinfo WHERE group_number = {$groupNumber} AND bot_fakeadmin = {$changeQQNumber} LIMIT 1")) {
							$text.="{$lang['usage']}{$lang['colon']}{$commandhelp['botadmin']['changecard'][0]}.\n";
							break 2;
						}
						unset($splitarr[0]);
						if ($changeQQNumber === 0) {
							$text.="{$lang['usage']}{$lang['colon']}{$commandhelp['botadmin']['changecard'][0]}.\n";
							break 2;
						}
						ChangeCard($changeQQNumber,implode(' ',$splitarr));
					}
					break;
				default:
					break 2;
			}
			$text.="OK.\n";
			break;
		default:
			return 0;
			break;
	}
	return 1;
}
function PublicCommands($isGroup,$splitarr,$messagearr,$messagecount,&$text) {
	global $conn,$lang,$masterQQ,$isMaster,$jsonarr,$qqNumber,$groupNumber,$devGroupNumber,$mainGroupNumber,$groupNumberList,$commandhelp,$highScoreTable,$scoreTable,$userStatsTable,$modeName,$billtypelist;
	switch (strtolower($messagearr[0])) {
		case 'help':
			$allowCheckAdmin=0;
			if ($messagecount > 1) {
				if ($messagearr[1] == "1" || (count($splitarr) > 2 && $splitarr[2] == "1")) {
					$allowCheckAdmin=1;
				}
				if (!$allowCheckAdmin || count($splitarr) > 2) {
					$commandKey=$splitarr[1];
				}
			}
			#$text.="messageCount: {$messagecount}, allowCheckAdmin: {$allowCheckAdmin}, commandKey: {$commandKey}.";
			foreach ($commandhelp as $key => $value) {
				if (!CheckCommandBlacklist($key,$allowCheckAdmin)) {
					if (isset($commandKey) && isset($commandhelp[$commandKey])) {
						if ($commandKey != $key) {
							continue;
						} elseif (count($commandhelp[$commandKey]) != count($commandhelp[$commandKey],1)) {
							foreach ($commandhelp[$commandKey] as $value) {
								$text.="{$value[0]} - {$value[1]}\n";
							}
							break;
						}
					}
					if (!isset($value[0])) {
						$text.="!{$key}\n";
					} else {
						$text.="{$value[0]} - {$value[1]}\n";
					}
				}
			}
			break;
		case 'bp':
			if ($messagecount < 2) {
				$text.="{$lang['usage']}{$lang['colon']}{$commandhelp['bp'][0]}.\n";
				break;
			}
			$mode=0;
			$modearr=explode(' ', $messagearr[1]);
			if (count($modearr) > 1 && is_numeric($modearr[count($modearr)-1])) {
				$mode=(int)$modearr[count($modearr)-1];
				if ($mode < 0 || $mode > 3) {
					$mode=0;
				} else {
					unset($modearr[count($modearr)-1]);
					$messagearr[1]=implode(' ',$modearr);
				}
			}
			setGameMode($mode);
			$username=sqlstr($messagearr[1]);
			$beatmapList=$conn->queryAll("SELECT sh.date, sh.rank, sh.beatmap_id, CONCAT(IF(b.artist != '',CONCAT(b.artist,' - ',b.title),b.title),' [',b.version,']') AS beatmap_name, s.pp, sh.pp as bp_pp, sh.score, sh.enabled_mods as mods FROM osu_users u JOIN $highScoreTable sh USING (user_id) JOIN $scoreTable s USING (score_id) LEFT JOIN osu_beatmaps b ON b.beatmap_id = sh.beatmap_id WHERE u.username = '{$username}' ORDER BY sh.pp DESC, s.pp DESC LIMIT 10");
			if (count($beatmapList) < 1) {
				$text.="{$lang['have_not_bp']}\n";
				break;
			}
			$text.="{$lang['userpage']}{$lang['colon']}https://user.".BanYouDomain."/".rawurlencode($username).(($mode > 0) ? "?m={$mode}" : "")."\n";
			$count=1;
			foreach ($beatmapList as $value) {
				$mods=getShortModString($value['mods'],1);
				$beatmapLink="https://osu.ppy.sh/b/{$value['beatmap_id']}";
				if ($mode > 0) {
					$beatmapLink.="?m={$mode}";
				}
				$value['pp']=sprintf('%.2f',$value['pp']);
				$value['bp_pp']=sprintf('%.2f',$value['bp_pp']);
				$value['rank']=str_replace('H','+Hidden',str_replace('X','SS',$value['rank']));
				$text.="{$count}. {$value['date']} Rank:{$value['rank']} {$beatmapLink} ({$value['beatmap_name']})".(!empty($mods) ? " +{$mods} " : " ").(($mode != 2) ? "{$value['pp']}pp({$value['bp_pp']}pp)" : "Score:{$value['score']}")."\n";
				$count++;
			}
			break;
		case 'user':
			if (count($splitarr) < 2) {
				foreach ($commandhelp['user'] as $value) {
					$text.="{$value[0]} - {$value[1]}\n";
				}
				break;
			}
			$subtype=$splitarr[1];
			unset($splitarr[0],$splitarr[1]);
			$splitarr=array_merge($splitarr);
			switch (strtolower($subtype)) {
				case 'supporter':
					if (count($splitarr) < 1) {
						$text.="{$lang['usage']}{$lang['colon']}{$commandhelp['user']['supporter'][0]}.\n";
						break;
					}
					$date=-1;
					if (count($splitarr) > 1) {
						if (date('Y-m-d', strtotime($splitarr[count($splitarr)-1])) == ($splitarr[count($splitarr)-1])) {
							$date=$splitarr[count($splitarr)-1];
							unset($splitarr[count($splitarr)-1]);
						} elseif ($splitarr[count($splitarr)-1] == "0") {
							$date=0;
							unset($splitarr[count($splitarr)-1]);
						} elseif ($splitarr[count($splitarr)-1] == "1") {
							$date=1;
							unset($splitarr[count($splitarr)-1]);
						}
					}
					$username=sqlstr(implode(' ',$splitarr));
					if ($date === -1) {
						$supporterRow=$conn->queryRow("SELECT osu_subscriber, osu_subscriptionexpiry, username FROM osu_users WHERE username = '{$username}' LIMIT 1",1);
						$supporterExpiryTime=0;
						if ($supporterRow != 0 && count($supporterRow) > 0) {
							$username=$supporterRow[2];
							$supporterExpiryTime=(!empty($supporterRow[1]) && $supporterRow[0] == 1) ? $supporterRow[1] : $supporterRow[0];
							$text.=sprintf($lang['supporter_expirydate'],$username,$supporterExpiryTime);
						} else {
							$text.=$lang['user_not_found'];
						}
					} elseif ($isMaster) {
						$supporterExpiryTime='NULL';
						if (!is_numeric($date)) {
							if (strtotime($date) > time()) {
								$supporter=1;
								$supporterExpiryTime="'{$date}'";
							} else {
								$supporter=0;
							}
						} elseif ($date == 0 || $date == 1) {
							$supporter=$date;
						}
						$conn->exec("UPDATE osu_users SET osu_subscriber = {$supporter}, osu_supportplayer = 0, osu_subscriptionexpiry = {$supporterExpiryTime} WHERE username = '{$username}' LIMIT 1");
						$text.=sprintf($lang['updated_supporter_expirydate'],$username);
						if ($supporter && $supporterExpiryTime != 'NULL') {
							$text.=sprintf($lang['updated_supporter_expirydate_to+'],trim($supporterExpiryTime,'\''));
						}
						$text.=".";
					}
					$text.="\n";
					break;
				case 'supportplayer':
					if ($isMaster) {
						$username=sqlstr(implode(' ',$splitarr));
						$conn->exec("UPDATE osu_users SET osu_supportplayer = IF(osu_supportplayer = 1,0,1) WHERE osu_subscriber = 1 AND username = '{$username}' LIMIT 1");
					}
					break;
			}
			break;
		case 'roll':
			$maxNumber=100;
			if (count($messagearr) > 1 && is_numeric($messagearr[1]) && $messagearr[1] > 0 && $messagearr[1] < 2143585063) {
				$maxNumber=$messagearr[1];
			}
			$randomNumber=GetRandomNumber($maxNumber);
			$username=GetUsernameByQQ($qqNumber);
			if (!$username) {
				$username=isGroup($isGroup) ? "[CQ:at,qq={$qqNumber}]" : $jsonarr->sender->nickname;
			}
			$text.="{$username} rolls {$randomNumber} point(s).\n";
			/*
			for ($i=0;$i<(($randomNumber > 100) ? 100 : $randomNumber);$i++) {
				$text.="[face13.gif]";
			}
			*/
			break;
		case 'announce':
			if (count($messagearr) > 1) {
				Announce($messagearr[1]);
			} else {
				if (!empty($text)) {
					Announce($text);
					$text='';
				}
			}
			break;
		case 'bindid':
			if ($messagecount < 2) {
				$text.="{$lang['usage']}{$lang['colon']}{$commandhelp['bindid'][0]}.\n";
				break;
			}
			if (GetQQByUsername($messagearr[1]) !== 0) {
				$text.="{$lang['username_has_been_bound']}\n";
				break;
			}
			if (GetUsernameByQQ($qqNumber) !== 0) {
				$text.="{$lang['qq_has_been_bound']}\n";
				break;
			}
			$othersql='';
			$userarr=explode(':',$messagearr[1],2);
			if (!isGroup($isGroup) && count($userarr) > 1) {
				$username=sqlstr($userarr[0]);
				$password=md5($userarr[1]);
				$othersql="AND user_password = '{$password}'";
			} else {
				$username=sqlstr($messagearr[1]);
			}
			$userID=$conn->queryOne("SELECT user_id FROM osu_users WHERE username = '{$username}' {$othersql} LIMIT 1");
			if (empty($userID)) {
				$text.=rtrim($lang['user_not_found'],'.');
				if (isset($password)) {
					$text.=$lang['user_not_found_or_password+'];
				}
				$text.=".";
			} elseif (isset($password)) {
				$conn->exec("UPDATE osu_users SET user_qq = {$qqNumber} WHERE user_id = {$userID} LIMIT 1");
				$conn->exec("DELETE FROM osu_tmpqq WHERE user_id = {$userID} OR tmp_qq = {$qqNumber} LIMIT 2");
				$text.=$lang['binding_success'];
			} else {
				$conn->exec("INSERT INTO osu_tmpqq VALUES ({$userID},{$qqNumber}) ON DUPLICATE KEY UPDATE tmp_qq=VALUES(tmp_qq)");
				$text.=$lang['binding_success'].sprintf($lang['binding_success+'],$qqNumber);
				#$bindqqpath=GetCurFullPath("bindqq.png");
				$text.="[CQ:image,file=bindqq.png]";
			}
			$text.="\n";
			break;
		case 'bancoin':
			$username=isBindID($qqNumber,$text);
			if (!$username) {
				break;
			}
			if (count($splitarr) < 2) {
				foreach ($commandhelp['bancoin'] as $value) {
					$text.="{$value[0]} - {$value[1]}\n";
				}
				break;
			}
			$tmp='';
			$subtype=$splitarr[1];
			unset($splitarr[0],$splitarr[1]);
			$splitarr=array_merge($splitarr);
			switch (strtolower($subtype)) {
				case 'change':
					if (count($splitarr) > 1 && $isMaster) {
						$splitarr[0]=isAT($splitarr[0]);
						if (AddMoneyEvent('Change',$splitarr[0],$splitarr[1])) {
							$tmp.=$lang['change_balance_succeed'];
						} else {
							$tmp.=$lang['change_balance_failed'];
						}
					}
					break;
				case 'balance':
					if (count($splitarr) > 0) {
						$splitarr[0]=isAT($splitarr[0]);
					}
					if ($isMaster && count($splitarr) > 0 && is_numeric($splitarr[0])) {
						$username=GetUsernameByQQ($splitarr[0]);
						if (!$username) {
							$username="[CQ:at,qq=$splitarr[0]]";
						}
						$curMoney=GetCurMoney($splitarr[0]);
					} else {
						$curMoney=GetCurMoney($qqNumber);
					}
					if ($curMoney == 0) {
						$tmp.=$lang['no_money'];
					} else {
						$tmp.=sprintf($lang['balance_is_+'],$username,$curMoney);
					}
					break;
				/*
				case 'rank':
					if (isGroup($isGroup)) {
						$ranklist=$conn->queryAll("SELECT qq,SUM(money) as money FROM osu_pay GROUP BY qq ORDER BY money DESC LIMIT 10",0);
						$count=1;
						foreach ($ranklist as $value) {
							$userqq=$value['qq'];
							$username=GetUsernameByQQ($userqq);
							$usermoney=$value['money'];
							if (!$username) {
								$json=json_decode(file_get_contents("http://127.0.0.1:8888/?key=".BotKey."&a=<%26%26>GetClusterMemberInfo<%26>{$_POST['ExternalId']}<%26>{$userqq}"));
								$username=($json->result[0]->Card != null) ? $json->result[0]->Card : "{$json->result[0]->Nick} (QQ:{$userqq})";
								//$username="QQ:{$userqq}";
							}
							$text.="{$count}. {$username}{$lang['comma']}{$lang['bancoin_balance']}{$lang['colon']}{$usermoney}.\n";
							$count++;
						}
					}
					break;
				*/
				case 'bill':
					if (count($splitarr) > 0) {
						$billQQNumber=isAT($splitarr[0]);
						if ($splitarr[0] == $billQQNumber) {
							$billQQNumber=0;
						} elseif (count($splitarr) > 1) {
							$splitarr[0]=$splitarr[1];
							unset($splitarr[1]);
						} else {
							unset($splitarr[0]);
						}
					}
					if (!isset($billQQNumber) || $billQQNumber === 0) {
						$billQQNumber=$qqNumber;
					}
					$page=(count($splitarr) > 0 && is_numeric($splitarr[0]) && $splitarr[0] > 0) ? $splitarr[0] : 1;
					$maxLimit=$page*10;
					$curMaxLimit=$conn->queryOne("SELECT COUNT(*) FROM osu_pay WHERE qq = {$billQQNumber}");
					if ($maxLimit > $curMaxLimit+9) {
						$text.=sprintf($lang['have_not_+_bill_or_out_of_range'],' BanCoin ');
						$text.="\n";
						break;
					}
					$minLimit=$maxLimit-9;
					$page=ceil($minLimit/10);
					$maxPage=ceil($curMaxLimit/10);
					$minLimit--;
					$billlist=$conn->queryAll("SELECT id,time,type,money FROM osu_pay WHERE qq = {$billQQNumber} ORDER BY time DESC LIMIT {$minLimit},10");
					foreach ($billlist as $value) {
						$type=(isset($billtypelist[$value['type']])) ? $billtypelist[$value['type']] : $value['type'];
						$text.="{$value['id']}. {$value['time']} {$lang['type']}{$lang['colon']}{$type}{$lang['comma']}{$lang['money']}{$lang['colon']}{$value['money']}.\n";
					}
					$text.="{$lang['page_number']}{$lang['colon']}{$page}/{$maxPage}.\n";
					break;
				case 'transfer':
					if (count($splitarr) < 2) {
						$text.="{$lang['usage']}{$lang['colon']}{$commandhelp['bancoin']['transfer'][0]}.\n";
						break;
					}
					$splitarr[0]=isAT($splitarr[0]);
					if (is_numeric($splitarr[0]) && (is_numeric($splitarr[1]) || is_float($splitarr[1]))) {
						if ($splitarr[1] <= 0) {
							$tmp.=$lang['transfer_money_must_>_0'];
						} elseif ($splitarr[1] > 1000) {
							$tmp.=$lang['transfer_money_must_<=_1000'];
						} elseif (strlen($splitarr[0]) > 10) {
							$tmp.=sprintf($lang['+_length_is_not_true'],'QQ');
						} elseif (GetCurMoney($qqNumber) < $splitarr[1]) {
							$tmp.=$lang['not_enough_money'];
						} elseif (GetCurMoney($splitarr[0]) == 0) {
							$tmp.=$lang['get_money_before_receive_money'];
						} elseif ($qqNumber == $splitarr[0]) {
							$tmp.=$lang['can_not_transfer_to_myself'];
						} else {
							if (AddMoneyEvent('Transfer-',$qqNumber,"-{$splitarr[1]}")) {
								if (AddMoneyEvent('Transfer+',$splitarr[0],$splitarr[1])) {
									$tmp.=$lang['transfer_succeed'];
									$received_username=GetUsernameByQQ($splitarr[0]);
									if (!$received_username) {
										$received_username="QQ:{$splitarr[0]}";
									}
									$splitarr[1]=sprintf('%.2f',$splitarr[1]);
									Announce("[BanCoin] {$received_username} 通过转账被赠送了 {$splitarr[1]}.");
								} else {
									$tmp.=$lang['add_money_failed'];
								}
							} else {
								$tmp.=$lang['deduct_money_failed'];
							}
						}
					} else {
						$tmp.=$lang['format_is_not_true'];
					}
					break;
			}
			if (!empty($tmp)) {
				$text.="[BanCoin] {$tmp}.\n";
				unset($tmp);
			}
			break;
		case 'buy':
			if (count($messagearr) < 2) {
				foreach ($commandhelp['buy'] as $value) {
					$text.="{$value[0]} - {$value[1]}\n";
				}
				break;
			}
			$username=isBindID($qqNumber,$text);
			if (!$username) {
				break;
			}
			switch (strtolower($splitarr[1])) {
				case 'list':
					$goodslist=$conn->queryAll("SELECT id,name,callname,stock,money FROM osu_store ORDER BY id ASC",0);
					$i=0;
					foreach ($goodslist as $value) {
						if ($value['stock'] === null) {
							$value['stock']=$lang['unrestricted'];
						} elseif ($value['stock'] < 1) {
							continue;
						}
						$i++;
						$text.="{$i}. {$lang['shorter_goods_name']}{$lang['colon']}{$value['name']}{$lang['comma']}{$lang['goods_name']}{$lang['colon']}{$value['callname']}{$lang['comma']}{$lang['stock']}{$lang['colon']}{$value['stock']}{$lang['comma']}{$lang['price']}{$lang['colon']}{$value['money']}.\n";
					}
					break 2;
				case 'bill':
					if (count($splitarr) > 2) {
						$laststr=$splitarr[count($splitarr)-1];
					}
					$page=(isset($laststr) && is_numeric($laststr) && $laststr > 0) ? $laststr : 1;
					$maxLimit=$page*10;
					$curMaxLimit=$conn->queryOne("SELECT COUNT(*) FROM osu_store_bill WHERE qq = {$qqNumber}");
					if ($maxLimit > $curMaxLimit+9) {
						$text.=sprintf($lang['have_not_+_bill_or_out_of_range'],'购买');
						break;
					}
					$minLimit=$maxLimit-9;
					$page=ceil($minLimit/10);
					$maxPage=ceil($curMaxLimit/10);
					$minLimit--;
					$billlist=$conn->queryAll("SELECT sb.pay_id,s.name,s.callname,p.time,s.money,COUNT(*) as count FROM osu_store_bill sb JOIN osu_store s ON s.id = sb.store_id LEFT JOIN osu_pay p ON p.id = sb.pay_id WHERE sb.qq = {$qqNumber} GROUP BY p.time ORDER BY p.time DESC LIMIT {$minLimit},10");
					foreach ($billlist as $value) {
						if (!empty($value['time'])) {
							$text.="{$value['pay_id']}. ";
							$text.="{$value['time']} ";
						}
						$text.="{$lang['shorter_goods_name']}{$lang['colon']}{$value['name']}{$lang['comma']}{$lang['goods_name']}{$lang['colon']}{$value['callname']}{$lang['comma']}{$lang['count']}{$lang['colon']}{$value['count']}{$lang['comma']}{$lang['money']}{$lang['colon']}{$value['money']}.\n";
					}
					$text.="{$lang['page_number']}{$lang['colon']}{$page}/{$maxPage}";
					break;
				case 'mygoods':
					$goodslist=$conn->queryAll("SELECT COUNT(*) as count,s.name,s.callname FROM osu_store_bill sb JOIN osu_store s ON s.id = sb.store_id WHERE sb.qq = {$qqNumber} AND s.disposable = 0 GROUP BY store_id ORDER BY count DESC",0);
					if (count($goodslist) != 0) {
						foreach ($goodslist as $value) {
							$text.="{$lang['count']}{$lang['colon']}{$value['count']}{$lang['comma']}{$lang['shorter_goods_name']}{$lang['colon']}{$value['name']}{$lang['comma']}{$lang['goods_name']}{$lang['colon']}{$value['callname']}.\n";
						}
						break 2;
					}
					$text.=$lang['have_not_any_goods'];
					break;
				case 'sendgift':
					if (count($splitarr) > 4) {
						$splitarr[2]=isAT($splitarr[2]);
					}
					if (!(count($splitarr) > 4 && is_numeric($splitarr[2]) && is_numeric($splitarr[4]))) {
						$text.="{$lang['usage']}{$lang['colon']}{$commandhelp['buy']['sendgift'][0]}";
						break;
					}
					if ($qqNumber == $splitarr[2]) {
						$text.=$lang['can_not_send_gift_to_myself'];
						break;
					}
					$splitarr[3]=sqlstr($splitarr[3]);
					list($curGoodsCount,$curGoodsStoreID)=$conn->queryRow("SELECT COUNT(*), sb.store_id FROM osu_store_bill sb JOIN osu_store s ON s.id = sb.store_id WHERE sb.qq = {$qqNumber} AND s.disposable = 0 AND s.name = '{$splitarr[3]}'",1);
					if ($splitarr[4] < 1) {
						$text.=$lang['send_gift_count_must_>_0'];
						break;
					} elseif (empty($curGoodsStoreID)) {
						$text.=$lang['have_not_this_goods'];
						break;
					} elseif ($splitarr[4] > $curGoodsCount) {
						$text.=$lang['have_not_this_goods_count'];
						break;
					}
					$conn->exec("DELETE FROM osu_store_bill WHERE qq = {$qqNumber} AND store_id = {$curGoodsStoreID} LIMIT {$splitarr[4]}");
					for ($i=0;$i<$splitarr[4];$i++) {
						if (!AddBuyEvent($splitarr[2],$curGoodsStoreID,0)) {
							$text.="{$lang['bookkeeping_failed']}\n";
						}
					}
					$text.=$lang['send_gift_succeed'];
					break;
				default:
					$goodsarr=explode(' ',$messagearr[1]);
					$buyCount=1;
					if ($goodsarr > 1 && is_numeric($goodsarr[count($goodsarr)-1]) && $goodsarr[count($goodsarr)-1] > 0) {
						$buyCount=$goodsarr[count($goodsarr)-1];
						unset($goodsarr[count($goodsarr)-1]);
						$messagearr[1]=implode(' ',$goodsarr);
					}
					$goodsname=sqlstr(strtolower($messagearr[1]));
					list($goodsid,$goodsname,$goodsstock,$goodsprice,$goodssql,$goodsdisposable)=$conn->queryRow("SELECT id,name,stock,money,`sql`,disposable FROM osu_store WHERE name = '{$goodsname}' LIMIT 1",1);
					if (!empty($goodsid)) {
						if ($goodsstock !== null && $goodsstock == 0) {
							$text.=$lang['not_enough_stock'];
							break;
						}
						if ($buyCount > 1 && $goodsdisposable) {
							$text.=$lang['can_not_buy_multi_disposable_goods'];
							break;
						}
						$finalprice=$goodsprice*$buyCount;
						if (GetCurMoney($qqNumber) < $finalprice) {
							$text.="{$lang['your']}{$lang['not_enough_money']}";
							break;
						}
						if ($finalprice > 0) {
							$finalprice="-{$finalprice}";
						} elseif ($finalprice < 0) {
							$finalprice=abs($finalprice);
						}
						if ($finalprice != 0) {
							if (!$payid=AddMoneyEvent("Buy",$qqNumber,$finalprice)) {
								$text.=$lang['deduct_money_failed'];
								break;
							}
						}
						for ($i=0;$i<$buyCount;$i++) {
							if (!AddBuyEvent($qqNumber,$goodsid,((isset($payid) ? $payid : 0)))) {
								$text.=$lang['bookkeeping_failed'];
								break;
							}
						}
						if (!empty($goodssql)) {
							$goodssql=str_replace('{QQ}',$qqNumber,$goodssql);
							$goodssql=str_replace('{username}',$username,$goodssql);
							$conn->exec($goodssql);
						}
						if (mysqli_affected_rows($conn) < 1) {
							$text.=$lang['error_occurred_when_provide_goods'];
							if (isset($payid)) {
								if (!GiveBackMoney($payid)) {
									$text.="{$lang['comma']}{$lang['return_money_failed']}";
								}
							}
							if (!DeleteStoreBill($qqNumber,$goodsid)) {
								$text.="{$lang['comma']}{$lang['delete_store_bill_failed']}";
							}
						} else {
							if ($goodsstock !== null && $goodsstock >= $buyCount) {
								$conn->exec("UPDATE osu_store SET stock=stock-{$buyCount} WHERE id = {$goodsid} LIMIT 1");
							}
							$text.=$lang['deduct_money_and_provide_goods_succeed'];
						}
					} else {
						$text.=$lang['have_not_this_goods_in_store'];
					}
					break;
			}
			$text.=".\n";
			break;
		case 'checkin':
			$username=isBindID($qqNumber,$text);
			if (!$username) {
				break;
			}
			$checkinType='Checkin';
			//$randomMoney=(GetRandomNumber(100)/100)-(GetRandomNumber(50)/100);
			$randomMoney=round(lcg_value(),2);
			if ($conn->queryOne("SELECT 1 FROM osu_pay WHERE type = 'Checkin' AND time >= CURDATE() AND qq = {$qqNumber} LIMIT 1")) {
				$checkinType.='+';
				$randomMoney=0.4-$randomMoney;
			}
			AddMoneyEvent($checkinType,$qqNumber,$randomMoney);
			$text.="{$lang['checkin_succeed']}{$lang['comma']}{$lang['get']} {$randomMoney} BanCoin.\n";
			break;
		case 'bansay':
			ChangeSayStatus();
			break;
		case 'say':
			if (!$isMaster) {
				break;
			}
			if (count($splitarr) > 2 && is_numeric($splitarr[1])) {
				$saycount=($splitarr[1] > 9 || $splitarr[1] < 1) ? 1 : $splitarr[1];
				for ($i=0;$i<$saycount;$i++) {
					$text.="{$splitarr[2]}\n";
				}
			} elseif (count($splitarr) > 1) {
				$text.="{$messagearr[1]}\n";
			}
			break;
		/*
		case 'weather':
			if ($messagecount < 2) {
				$text.="{$lang['usage']}{$lang['colon']}{$commandhelp['weather'][0]}";
				break;
			}
			$weather=GetNormalWeather($messagearr[1]);
			if ($weather == 0) {
				$text.="无法获取城市或天气数据.\n";
				break;
			}
			$skyconlist=array('CLEAR_DAY'=>'晴天','CLEAR_NIGHT'=>'晴夜','PARTLY_CLOUDY_DAY'=>'多云','PARTLY_CLOUDY_NIGHT'=>'多云','CLOUDY'=>'阴','RAIN'=>'雨','SNOW'=>'雪','WIND'=>'风','FOG'=>'雾','HAZE'=>'霾','SLEET'=>'冻雨');
			$weather['skycon']=$skyconlist[strtoupper($weather['skycon'])];
			$text.="{$messagearr[1]}实时天气{$lang['colon']}{$weather['skycon']}{$lang['comma']}温度{$lang['colon']}{$weather['temperature']}°{$lang['comma']}风速{$lang['colon']}{$weather['windspeed']} km/h{$lang['comma']}PM2.5{$lang['colon']}{$weather['pm25']}{$lang['comma']}云量{$lang['colon']}{$weather['cloudrate']}{$lang['comma']}相对湿度{$lang['colon']}{$weather['humidity']}";
			if (isset($weather['nearest'])) {
				$text.="{$lang['comma']}最近的降水地区离这里有 {$weather['nearest']['distance']} 公里远{$lang['comma']}降水量为 {$weather['nearest']['intensity']}";
			}
			$text.=".\n";
			break;
		*/
		case 'stat':
		case 'stats':
			if ($messagecount > 1) {
				$mode=$splitarr[count($splitarr)-1];
				if (is_numeric($mode) && $mode <= 3 && $mode >= 0) {
					unset($splitarr[count($splitarr)-1]);
					unset($splitarr[0]);
					$messagearr[1]=implode(' ',$splitarr);
					$mode=(int)$mode;
				} else {
					$mode=0;
				}
				$userid=GetUserIDByUsername($messagearr[1]);
				if (!$userid) {
					$text.="{$lang['user_not_found']}\n";
					break;
				}
				$rank=GetPlayerRankByUserID($mode,$userid);
				if (!$rank) {
					$text.="{$lang['no_play_records']}\n";
					break;
				}
				setGameMode($mode);
				list($score,$playcount,$level,$accuracy)=$conn->queryRow("SELECT ranked_score, playcount, level, accuracy FROM {$userStatsTable} WHERE user_id = {$userid} LIMIT 1",1);
				$score=number_format($score);
				$level=floor($level);
				$accuracy*=100;
				$text.="Stats for {$messagearr[1]} ({$modeName}):\n";
				$text.="Score: {$score} (#{$rank})\n";
				$text.="Plays: {$playcount} (lv{$level})\n";
				$text.="Accuracy: {$accuracy}%\n";
			} else {
				$text.="{$lang['no_username_provide']}\n";
			}
			break;
		case 'friend':
		case 'friends':
			$username=isBindID($qqNumber,$text);
			if (!$username) {
				break;
			}
			$userid=GetUserIDByUsername($username);
			if ($messagecount < 2) {
				$friendsList=$conn->queryAll("SELECT u.username,IF((SELECT 1 FROM osu_friends WHERE user_id = f.zebra_id AND zebra_id = {$userid} LIMIT 1),1,0) as mu FROM osu_friends f JOIN osu_users u ON u.user_id = f.zebra_id WHERE f.user_id = {$userid} ORDER BY u.user_lastvisit DESC LIMIT ".MaxFriendsCount);
				if (count($friendsList) < 1) {
					$text.="{$lang['you_have_not_added_any_friends_yet']}\n";
					break;
				}
				$text.="{$username} {$lang['added_friends']}: ";
				foreach ($friendsList as $value) {
					if ($value['mu'] == 1) {
						$text.="※ ";
					}
					$text.=$value['username'];
					$text.=", ";
				}
				$text=rtrim($text,', ');
			} else {
				$messagearr[1]=sqlstr($messagearr[1]);
				$isAdded=$conn->queryOne("SELECT 1 FROM osu_friends f JOIN osu_users u ON username = '{$messagearr[1]}' WHERE f.user_id = {$userid} AND f.zebra_id = u.user_id LIMIT 1");
				$isBeAdded=$conn->queryOne("SELECT 1 FROM osu_friends f JOIN osu_users u ON username = '{$messagearr[1]}' WHERE u.user_id = f.user_id AND f.zebra_id = {$userid} LIMIT 1");
				$text.=(!empty($isAdded) ? $lang['you_have_added_him/her_as_a_friend'] : $lang['you_have_not_added_him/her_as_a_friend_yet']).', '.(!empty($isBeAdded) ? $lang['he/she_has_added_you_as_a_friend'] : $lang['he/she_has_not_added_you_as_a_friend_yet']).'.';
			}
			$text.="\n";
			break;
		case 'br':
			$mode=0;
			if ($messagecount > 1) {
				if (is_numeric($messagearr[1]) && $messagearr[1] <= 3 && $messagearr[1] >= 0) {
					$mode=(int)$messagearr[1];
				} else {
					break;
				}
			}
			$username=isBindID($qqNumber,$text);
			if (!$username) {
				break;
			}
			$userid=GetUserIDByUsername($username);
			setGameMode($mode);
			list($scoreID,$beatmapID,$rank,$mods,$finalpp,$date)=$conn->queryRow("SELECT score_id, beatmap_id, rank, enabled_mods, pp, date FROM {$highScoreTable} WHERE user_id = {$userid} ORDER BY date DESC LIMIT 1",1);
			if (empty($rank)) {
				$text.="{$lang['no_play_records']}\n";
				break;
			}
			$pp=$conn->queryOne("SELECT pp FROM {$scoreTable} WHERE score_id = {$scoreID} LIMIT 1");
			$pp=sprintf('%.2f',$pp);
			$finalpp=sprintf('%.2f',$finalpp);
			$rank=str_replace('H','+Hidden',str_replace('X','SS',$rank));
			$mods=getShortModString($mods,0);
			$text.="{$username}'s BanYou Recent ({$modeName}) [{$date}]\n";
			list($hit_length,$total_length,$beatmap_name,$beatmap_version)=$conn->queryRow("SELECT hit_length, total_length, CONCAT(IF(artist != '',CONCAT(artist,' - ',title),title)), version FROM osu_beatmaps WHERE beatmap_id = {$beatmapID} LIMIT 1",1);
			if (!empty($beatmap_name)) {
				$text.="{$beatmap_name} [{$beatmap_version}]\n";
			}
			$text.="{$lang['rank']}{$lang['colon']}{$rank}{$lang['comma']}Mods{$lang['colon']}{$mods}{$lang['comma']}{$pp}pp({$finalpp}pp)\n";
			$text.="{$lang['beatmap']}{$lang['colon']}https://osu.ppy.sh/b/{$beatmapID}";
			if (!empty($hit_length)) {
				$text.="{$lang['comma']}{$lang['beatmap_hit_length']}{$lang['colon']}{$hit_length} {$lang['second']}{$lang['comma']}{$lang['beatmap_total_length']}{$lang['colon']}{$total_length} {$lang['second']}";
			}
			$text.="\n";
			$text.="{$lang['userpage']}{$lang['colon']}https://user.".BanYouDomain."/".rawurlencode($username);
			$text.="\n";
			break;
		default:
			return 0;
			break;
	}
	return 1;
}
function HandleMessage($isGroup,$messages) {
	global $message_id,$groupNumber,$qqNumber;
	$text='';
	if (count($messages) < 1) {
		return;
	} elseif (count($messages) < 2) {
		$messages=explode('|',$messages[0]);
	}
	foreach ($messages as $fullmessage) {
		$fullmessage=trim($fullmessage);
		if (empty($fullmessage)) {
			continue;
		}
		if (isGroup($isGroup)) {
			$isSilence=CheckSilenceList($fullmessage);
			if ($isSilence !== 0) {
				if ($isSilence !== -1) {
					$isSilence*=60;
					Silence($groupNumber,$qqNumber,$isSilence);
				}
				die();
			}
		}
		$message=(substr($fullmessage,0,1) === '!') ? substr($fullmessage,1) : "";
		if (!empty($message)) {
			$message=TrimMultiSpace($message);
			$splitarr=explode(' ', $message);
			$messagearr=explode(' ',$message,2);
			$messagecount=count($messagearr);
			if ($messagecount > 1) {
				$messagearr[1]=trim($messagearr[1]);
			}
			switch (CheckCommandBlacklist(strtolower($messagearr[0]))) {
				case 2:
					die();
				case 1:
					continue 2;
				case 0:
				default:
					break;
			}
			if (!PublicCommands($isGroup,$splitarr,$messagearr,$messagecount,$text)) {
				if (isGroup($isGroup)) {
					GroupCommands($splitarr,$messagearr,$messagecount,$text);
				} else {
					PrivateCommands($splitarr,$messagearr,$messagecount,$text);
				}
			}
		}
	}
	$text=rtrim($text);
	if (!empty($text)) {
		$textarr=str_split($text,3000);
		foreach ($textarr as $value) {
			switch ($isGroup) {
				case 1:
					sendGroupMessage($groupNumber,$value);
					break;
				case 0:
					sendMessage($qqNumber,$value);
					break;
				case 2:
					sendTempMessage($groupNumber,$qqNumber,$value);
					break;
			}
		}
	}
}
if (!($_SERVER['REMOTE_ADDR'] === '127.0.0.1' && strtoupper($_SERVER['REQUEST_METHOD']) === 'POST')) {
	die();
}
require_once('include.functions.php');
require_once('web/include.db.php');
$selfQQ='2487084757';
$masterQQ='2143585062';
$devGroupNumber='609602961';
$mainGroupNumber='686469603';
$groupNumberList=array(
	$devGroupNumber,
	$mainGroupNumber,
	'132783429'
);
$reqdata=file_get_contents('php://input');
$jsonarr=json_decode($reqdata);
if (isset($jsonarr->user_id)) {
	$qqNumber=$jsonarr->user_id;
}
if (isset($jsonarr->group_id)) {
	$groupNumber=$jsonarr->group_id;
}
if (isset($jsonarr->message)) {
	$message=$jsonarr->message;
	$message=decodeCQCode($message);
}
switch ($jsonarr->post_type) {
	case 'meta_event':
		switch ($jsonarr->meta_event_type) {
			// 心跳包
			case 'heartbeat':
				CheckEvent();
				break;
			default:
				break;
		}
		break;
	// 接收到消息
	case 'message':
		$isMaster=($qqNumber == $masterQQ);
		$messages=explode("\r",$message);
		switch ($jsonarr->sub_type) {
			case 'friend':
				HandleMessage(0,$messages);
				break;
			case 'group':
			case 'discuss':
				//HandleMessage(2,$messages);
				break;
			case 'normal':
				HandleMessage(1,$messages);
				break;
			default:
				break;
		}
		break;
	// 接收到通知
	case 'notice':
		if (!isAllowGroupMessage()) {
			break;
		}
		switch ($jsonarr->notice_type) {
			case 'group_increase':
					$blockTime=$conn->queryOne("SELECT BlockTime FROM bot_blockqqlist WHERE group_number = {$groupNumber} AND BlockQQ = {$qqNumber} LIMIT 1");
					if ($blockTime !== false && $blockTime == "0") {
						Kick($groupNumber,$qqNumber);
						break;
					}
					if ($blockTime) {
						Silence($groupNumber,$qqNumber,$blockTime*60);
					}
					if ($groupNumber == $mainGroupNumber) {
						sendGroupMessage($mainGroupNumber, "[CQ:at,qq={$qqNumber}] 欢迎来到 BanYou 玩家群{$lang['comma']}请将你的名片改为 osu! ID。\n要进入 BanYou{$lang['comma']}请在群文件下载客户端和使用指南。\n成功邀请一个进入 BanYou 的新玩家将赠送 14 天 BanYou SupportPlayer。");
					}
				break;
			default:
				break;
		}
		break;
	// 接收到请求
	case 'request':
		switch ($jsonarr->request_type) {
			case 'friend':
				$arr=array('approve'=>true);
				break;
			case 'group':
				$blockTime=$conn->queryOne("SELECT BlockTime FROM bot_blockqqlist WHERE group_number = {$groupNumber} AND BlockQQ = {$qqNumber} LIMIT 1");
				if ($blockTime !== false && $blockTime == "0") {
					$arr=array('approve'=>false,'reason'=>"因为你在机器人黑名单的列表中{$lang['comma']}所以你被拒绝加入群");
				}
				break;
			default:
				break;
		}
		if (isset($arr) && is_array($arr)) {
			echo toJSON($arr);
		}
		break;
}
?>
