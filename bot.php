<?php
date_default_timezone_set('Asia/Shanghai');
require_once('include.key.php');
$commandhelp=array(
'bindid'=>array('!bindid <BanYou Username>[:BanYou Password (Only private chat is available)]',' Bind your BanYou ID'),
'roll'=>array('!roll [Number]','Roll a dice and get random result from 1 to number(default 100)'),
'bp'=>array('!bp <BanYou Username> [Mode:0 (STD), 1 (Taiko), 2 (Catch The Beat), 3 (osu!mania)]','Show player best performance list'),
'buy'=>array(''=>array('!buy <Goods Name>','Buy goods'),'bill'=>array('!buy bill','Show my buy bill'),'list'=>array('!buy list','Show goods list'),'mygoods'=>array('!buy mygoods','Show my goods list'),'sendgift'=>array('!buy sendgift <QQ> <Goods Name> <Count>','Send gift to other QQ')),
'sleep'=>array('!sleep [Time: Default = 12 Hours, Minute(s) <= 1440]','Switch to sleep mode'),
'checkin'=>array('!checkin','Checkin to get a prize'),
'user'=>array('supporter'=>array('!user supporter <BanYou Username>','View supporter expirydate')),
'bancoin'=>array('bill'=>array('!bancoin bill','Show my BanCoin bill'),'rank'=>array('!bancoin rank','Show player ranking (Only group chat is available)'),'showcard'=>array('!bancoin showcard','Show my card'),'balance'=>array('!bancoin balance','Query my balance'),'transfer'=>array('!bancoin transfer <QQ> <BanCoin>','Transfer BanCoin to other QQ')),
'weather'=>array('!weather <City>','Weather Forecast'),
'botadmin'=>array('blockqq'=>array('!botadmin blockqq <QQNumber> <Silence Time>','Add QQ into blocklist'),'blocktext'=>array('!botadmin blocktext <Text>','Add text into blocklist'),'unblockqq'=>array('!botadmin unblockqq <QQNumber>','Delete QQ from blocklist'),'unblocktext'=>array('!botadmin unblocktext <Text>','Delete text from blocklist'))
);
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
function GiveBackMoney($money) {
	global $conn;
	if (!is_numeric($money)) {
		return 0;
	}
	return $conn->exec("DELETE FROM osu_pay WHERE money = $money LIMIT 1");
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
function isGroup($isGroup) {
	return (($isGroup === 1) ? 1 : 0);
}
function SaveCardImage($qq) {
	if (!is_dir('card')) {
		mkdir('card');
	}
	$img=imagecreatefrompng('card.png');
	imagettftext($img,imagesx($img)/20,0,imagesx($img)/13.95,imagesy($img)/1.21,imagecolorallocate($img,255,255,255),'card.ttf',"226968 2646 {$qq}");
	imagesavealpha($img,1);
	$ret=imagepng($img,"card/{$qq}.png");
	imagedestroy($img);
	return $ret;
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
	global $groupNumber;
	if (!isset($_POST['ExternalId'])) {
		return 1;
	}
	return in_array($_POST['ExternalId'],$groupNumber);
}
function CheckCommandBlacklist($command,$admin=1) {
	// 0:不在黑名单, 1:指令黑名单, 2:QQ/群组黑名单.
	global $masterQQ,$isMaster,$groupNumber,$devGroupNumber,$mainGroupNumber;
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
		case 'botadmin':
			break;
		case 'sleep':
			if ($_POST['ExternalId'] == "707983601") {
				return 1;
			}
			break;
		case 'atall':
		case 'getkey':
		case 'bansay':
		case 'fs':
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
	global $conn,$masterQQ;
	switch ($fullmessage) {
		/*
		case '[image=A2DA722F8EAD905AC7883C6E4CDB85D3.jpg]':
			if ($_POST['QQ'] == "2839098896") {
				return 1;
			}
			break;
		*/
		default:
			if ($_POST['QQ'] != $masterQQ && !$conn->queryOne("SELECT 1 FROM bot_groupinfo WHERE group_number = {$_POST['ExternalId']} AND bot_fakeadmin = {$_POST['QQ']} LIMIT 1")) {
				$lowerfullmessage=strtolower($fullmessage);
				if ($conn->queryOne("SELECT 1 FROM bot_blocktextlist WHERE group_number = {$_POST['ExternalId']} AND LOCATE(BlockText,'{$lowerfullmessage}') > 0 LIMIT 1")) {
					return 10;
				} elseif ($blockTime=$conn->queryOne("SELECT BlockTime FROM bot_blockqqlist WHERE group_number = {$_POST['ExternalId']} AND BlockQQ = {$_POST['QQ']} LIMIT 1")) {
					return $blockTime;
				}
			}
			return 0;
	}
}
function Silence($groupNumber,$QQNumber,$silenceTime) {
	echo "<&&>Silenced<&>{$groupNumber}<&>{$QQNumber}<&>{$silenceTime}\n";
}
function CheckEvent() {
	global $conn,$groupNumber,$devGroupNumber,$mainGroupNumber,$highScoreTable;
	if (file_exists('lastEventID')) {
		$lastEventID=file_get_contents('lastEventID');
		$eventList=$conn->queryAll("SELECT e.id, e.mode as mode, m.modename as modename, e.user_id as user_id, u.username as username, e.beatmap_id as beatmap_id, b.beatmapset_id as beatmapset_id, e.text as ranknumber, CONCAT(IF(b.artist != '',CONCAT(b.artist,' - ',b.title),b.title)) as beatmap_name, b.version as version, b.hit_length as hit_length, b.total_length as total_length, REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(et.`zh-ubbrule`,'{user_id}',e.user_id),'{username}',u.username),'{text}',e.text),'{beatmap_id}',e.beatmap_id),'{mode}',e.mode),'{artist}',IF(b.artist != '',CONCAT(b.artist,' - '),'')),'{title}',b.title),'{version}',b.version),'{modename}',m.modename) as text FROM osu_events e JOIN osu_users u USING (user_id) JOIN osu_beatmaps b USING (beatmap_id) JOIN osu_events_type et USING (type) JOIN osu_modes m ON m.id = e.mode WHERE e.type = 1 AND e.id > {$lastEventID} ORDER BY e.id");
		foreach ($eventList as $value) {
			setGameMode($value['mode']);
			list($scoreID,$rank,$modsnumber,$finalpp)=$conn->queryRow("SELECT score_id, rank, enabled_mods, pp FROM {$highScoreTable} WHERE user_id = {$value['user_id']} AND beatmap_id = {$value['beatmap_id']} LIMIT 1",1);
			$pp=$conn->queryOne("SELECT pp FROM {$scoreTable} WHERE score_id = {$scoreID} LIMIT 1");
			$rank=str_replace('H','+Hidden',str_replace('X','SS',$rank));
			$value['text']=str_replace('{score_id}',$scoreID,$value['text']);
			$value['text']=str_replace('{username}',$value['username'],$value['text']);
			$QQNumber=0;
			//$QQNumber=GetQQByUsername($value['username']);
			$value['text']=str_replace('{display_username}',($QQNumber !== 0 ? "[@{$QQNumber}]" : $value['username']),$value['text']);
			$value['text']=str_replace('{ue_username}',rawurlencode($value['username']),$value['text']);
			$value['text']=str_replace('{rank}',$rank,$value['text']);
			$value['text']=str_replace('{pp}',$pp,$value['text']);
			$value['text']=str_replace('{finalpp}',$finalpp,$value['text']);
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
			foreach ($groupNumber as $tmpNumber) {
				if ($tmpNumber == $devGroupNumber) {
					continue;
				}
				echo "<&&>SendClusterMessage<&>{$tmpNumber}<&>{$value['text']}\n";
			}
		}
	}
	$lastEventID=$conn->queryOne("SELECT id FROM osu_events ORDER BY id DESC LIMIT 1");
	file_put_contents('lastEventID', $lastEventID);
}
function PrivateCommands($splitarr,$messagearr,$messagecount,&$text) {
	global $conn,$masterQQ,$isMaster,$devGroupNumber,$mainGroupNumber,$groupNumber,$commandhelp,$highScoreTable,$scoreTable;
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
	global $conn,$masterQQ,$isMaster,$devGroupNumber,$mainGroupNumber,$groupNumber,$commandhelp,$highScoreTable,$scoreTable;
	switch (strtolower($messagearr[0])) {
		case 'atall':
			$text.="[@全体成员] ";
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
			$audiopath=GetCurFullPath("走好不送.amr");
			Silence($_POST['ExternalId'],$_POST['QQ'],$silenceTime);
			echo "<&&>SendClusterMessage<&>{$_POST['ExternalId']}<&>[声音={$audiopath}]\n";
			break;
		case 'fs':
			if (count($splitarr) < 2) {
				break;
			}
			if (!isset($splitarr[2])) {
				$splitarr[2]=10;
			}
			for ($i=0;$i<$splitarr[2];$i++) {
				Silence($_POST['ExternalId'],$splitarr[1],600);
				Silence($_POST['ExternalId'],$splitarr[1],0);
			}
			break;
		case 'botadmin':
			if ($_POST['QQ'] != $masterQQ && !$conn->queryOne("SELECT 1 FROM bot_groupinfo WHERE group_number = {$_POST['ExternalId']} AND bot_fakeadmin = {$_POST['QQ']} LIMIT 1")) {
				$text.="You are fake admin!\n";
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
					if (count($splitarr) < 2) {
						$text.="Usage: {$commandhelp['botadmin']['blockqq'][0]}.\n";
						break;
					}
					if (!is_numeric($splitarr[0]) || strlen($splitarr[0]) > 11 || strlen($splitarr[0]) < 5) {
						$text.="It's not a true number.\n";
						break;
					}
					if (!is_numeric($splitarr[1]) || strlen($splitarr[1]) > 3 || strlen($splitarr[1]) < 1) {
						$text.="It's not a true silence time.\n";
						break;
					}
					$qqNumber=(int)$splitarr[0];
					$silenceTime=(int)$splitarr[1];
					$conn->exec("INSERT INTO bot_blockqqlist VALUES ({$_POST['ExternalId']},{$splitarr[0]},{$splitarr[1]})");
					$text.="OK.\n";
					break;
				case 'blocktext':
					if (count($splitarr) < 1) {
						$text.="Usage: {$commandhelp['botadmin']['blocktext'][0]}.\n";
						break;
					}
					$blockstr=sqlstr(implode(' ',$splitarr));
					$conn->exec("INSERT INTO bot_blocktextlist VALUES ({$_POST['ExternalId']},'{$blockstr}')");
					$text.="OK.\n";
					break;
				case 'unblockqq':
					if (count($splitarr) < 1) {
						$text.="Usage: {$commandhelp['botadmin']['unblockqq'][0]}.\n";
						break;
					}
					$qqNumber=(int)$splitarr[0];
					$conn->exec("DELETE FROM bot_blockqqlist WHERE group_number = {$_POST['ExternalId']} AND BlockQQ = {$qqNumber} LIMIT 1");
					$text.="OK.\n";
				case 'unblocktext':
					if (count($splitarr) < 1) {
						$text.="Usage: {$commandhelp['botadmin']['unblocktext'][0]}.\n";
						break;
					}
					$blockstr=sqlstr(implode(' ',$splitarr));
					$conn->exec("DELETE FROM bot_blocktextlist WHERE group_number = {$_POST['ExternalId']} AND BlockText = '{$blockstr}' LIMIT 1");
					$text.="OK.\n";
					break;
				default:
					break;
			}
			break;
		default:
			return 0;
			break;
	}
	return 1;
}
function PublicCommands($isGroup,$splitarr,$messagearr,$messagecount,&$text) {
	global $conn,$masterQQ,$isMaster,$devGroupNumber,$mainGroupNumber,$groupNumber,$commandhelp,$highScoreTable,$scoreTable;
	switch (strtolower($messagearr[0])) {
		case 'help':
			$allowCheckAdmin=0;
			if ($messagecount > 1) {
				if ($messagearr[1] != "1") {
					break;
				}
				$allowCheckAdmin=1;
			}
			foreach ($commandhelp as $key => $value) {
				if (!CheckCommandBlacklist($key,$allowCheckAdmin)) {
					if (!isset($value[0])) {
						foreach ($value as $value2) {
							$text.="{$value2[0]} - {$value2[1]}\n";
						}
					} else {
						$text.="{$value[0]} - {$value[1]}\n";
					}
				}
			}
			break;
		case 'bp':
			if ($messagecount < 2) {
				$text.="Usage: {$commandhelp['bp'][0]}.\n";
				break;
			}
			$mode=0;
			$modearr=explode(' ', $messagearr[1]);
			if (is_numeric($modearr[count($modearr)-1])) {
				$mode=(int)$modearr[count($modearr)-1];
			}
			if ($mode < 0 || $mode > 3) {
				$mode=0;
			} elseif (count($modearr) > 1) {
				unset($modearr[count($modearr)-1]);
				$messagearr[1]=implode(' ',$modearr);
			}
			setGameMode($mode);
			$username=sqlstr($messagearr[1]);
			$beatmapList=$conn->queryAll("SELECT sh.date, sh.rank, sh.beatmap_id, CONCAT(IF(b.artist != '',CONCAT(b.artist,' - ',b.title),b.title),' [',b.version,']') AS beatmap_name, s.pp, sh.pp as bp_pp, sh.score, sh.enabled_mods as mods FROM osu_users u JOIN $highScoreTable sh USING (user_id) JOIN $scoreTable s USING (score_id) LEFT JOIN osu_beatmaps b ON b.beatmap_id = sh.beatmap_id WHERE u.username = '{$username}' ORDER BY sh.pp DESC, s.pp DESC LIMIT 10");
			if (count($beatmapList) < 1) {
				$text.="The player hasn't bp :(.\n";
				break;
			}
			$text.="用户页：https://user.osupink.net/".rawurlencode($username).(($mode > 0) ? "?m={$mode}" : "")."\n";
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
						$text.="Usage: {$commandhelp['user']['supporter'][0]}.\n";
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
							$text.="{$username}'s supporter expirydate: {$supporterExpiryTime}";
						} else {
							$text.="User not found.";
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
						$text.="Updated {$username}'s supporter expirydate";
						if ($supporter && $supporterExpiryTime != 'NULL') {
							$text.=" to ".trim($supporterExpiryTime,'\'');
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
			if (is_numeric($messagearr[1])) {
				$maxNumber=$messagearr[1];
			}
			$randomNumber=GetRandomNumber($maxNumber);
			$username=GetUsernameByQQ($_POST['QQ']);
			if ($username === 0) {
				$username=isGroup($isGroup) ? "[@{$_POST['QQ']}]" : $_POST['NickName'];
			}
			$text.="{$username} rolls {$randomNumber} point(s).\n";
			/*
			for ($i=0;$i<(($randomNumber > 100) ? 100 : $randomNumber);$i++) {
				$text.="[face13.gif]";
			}
			*/
			break;
		case 'bindid':
			if ($messagecount < 2) {
				$text.="Usage: {$commandhelp['bindid'][0]}.\n";
				break;
			}
			if (GetQQByUsername($messagearr[1]) !== 0) {
				$text.="This BanYou Username has been bound.\n";
				break;
			}
			if (GetUsernameByQQ($_POST['QQ']) !== 0) {
				$text.="This QQ has been bound.\n";
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
				$text.="User not found";
				if (isset($password)) {
					$text.=" or password error";
				}
				$text.=".";
			} elseif (isset($password)) {
				$conn->exec("UPDATE osu_users SET user_qq = {$_POST['QQ']} WHERE user_id = {$userID} LIMIT 1");
				$conn->exec("DELETE FROM osu_tmpqq WHERE user_id = {$userID} OR tmp_qq = {$_POST['QQ']} LIMIT 2");
				$text.="Binding success!";
			} else {
				$conn->exec("INSERT INTO osu_tmpqq VALUES ({$userID},{$_POST['QQ']}) ON DUPLICATE KEY UPDATE tmp_qq=VALUES(tmp_qq)");
				$text.="Binding success! The Final Step: Send !bindqq {$_POST['QQ']} in osu! chat to verify.\n";
				$bindqqpath=GetCurFullPath("bindqq.png");
				$text.="[image={$bindqqpath}]";
			}
			$text.="\n";
			break;
		case 'bancoin':
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
						if (AddMoneyEvent('Change',$splitarr[0],$splitarr[1])) {
							$tmp.="修改余额成功";
						} else {
							$tmp.="修改余额失败";
						}
					}
					break;
				case 'showcard':
					if (!file_exists("card/{$_POST['QQ']}.png")) {
						if (!SaveCardImage($_POST['QQ'])) {
							$text.="卡片生成失败.\n";
							break;
						}
					}
					$cardpath=GetCurFullPath("card/{$_POST['QQ']}.png");
					$text.="[image={$cardpath}]\n";
					break;
				case 'balance':
					$curMoney=GetCurMoney($_POST['QQ']);
					if ($curMoney == 0) {
						$tmp.='你没有余额或是余额为 0';
					} else {
						$tmp.="你的余额为 {$curMoney}";
					}
					break;
				case 'rank':
					if (isGroup($isGroup)) {
						$ranklist=$conn->queryAll("SELECT qq,SUM(money) as money FROM osu_pay GROUP BY qq ORDER BY money DESC LIMIT 10",0);
						$count=1;
						foreach ($ranklist as $value) {
							$userqq=$value['qq'];
							$username=GetUsernameByQQ($userqq);
							$usermoney=$value['money'];
							if ($username === 0) {
								$json=json_decode(file_get_contents("http://127.0.0.1:8888/?key=".BotKey."&a=<%26%26>GetClusterMemberInfo<%26>{$_POST['ExternalId']}<%26>{$userqq}"));
								$username=($json->result[0]->Card != null) ? $json->result[0]->Card : "{$json->result[0]->Nick} (QQ:{$userqq})";
								//$username="QQ:{$userqq}";
							}
							$text.="{$count}. {$username}，BanCoin 余额：{$usermoney}.\n";
							$count++;
						}
					}
					break;
				case 'bill':
					$page=(count($splitarr) > 0 && is_numeric($splitarr[0]) && $splitarr[0] > 0) ? $splitarr[0] : 1;
					$maxLimit=$page*10;
					$curMaxLimit=$conn->queryOne("SELECT COUNT(*) FROM osu_pay WHERE qq = {$_POST['QQ']}");
					if ($maxLimit > $curMaxLimit+9) {
						$text.="没有 BanCoin 账单或页数超出限制.\n";
						break;
					}
					$minLimit=$maxLimit-9;
					$page=ceil($minLimit/10);
					$maxPage=ceil($curMaxLimit/10);
					$minLimit--;
					$typelist=array('Buy'=>'购买','Checkin'=>'免费签到','Checkin-'=>'签到支出','Checkin+'=>'签到收入','Transfer-'=>'转出','Transfer+'=>'转入','Change'=>'修改','Recharge'=>'充值');
					$billlist=$conn->queryAll("SELECT time,type,money FROM osu_pay WHERE qq = {$_POST['QQ']} ORDER BY time DESC LIMIT {$minLimit},10");
					foreach ($billlist as $value) {
						$type=(isset($typelist[$value['type']])) ? $typelist[$value['type']] : $value['type'];
						$text.="{$value['time']} 类型：{$type}，金额：{$value['money']}.\n";
					}
					$text.="页数：{$page}/{$maxPage}.\n";
					break;
				case 'transfer':
					if (count($splitarr) < 2) {
						$text.="Usage: {$commandhelp['bancoin']['transfer'][0]}.\n";
						break;
					}
					if (is_numeric($splitarr[0]) && (is_numeric($splitarr[1]) || is_float($splitarr[1]))) {
						if ($splitarr[1] <= 0) {
							$tmp.="转账金额必须大于 0";
						} elseif ($splitarr[1] > 1000) {
							$tmp.='单次转账不得超过 1000';
						} elseif (strlen($splitarr[0]) > 10) {
							$tmp.='QQ 位数不正确';
						} elseif (GetCurMoney($_POST['QQ']) < $splitarr[1]) {
							$tmp.="你的余额不足";
						} elseif (GetCurMoney($splitarr[0]) == 0) {
							$tmp.='被转账玩家的账号必须有余额';
						} elseif ($_POST['QQ'] == $splitarr[0]) {
							$tmp.='不能向自己转账';
						} else {
							if (AddMoneyEvent('Transfer-',$_POST['QQ'],"-{$splitarr[1]}")) {
								if (AddMoneyEvent('Transfer+',$splitarr[0],$splitarr[1])) {
									$tmp.="转账成功";
								} else {
									$tmp.="加款失败";
								}
							} else {
								$tmp.="扣款失败";
							}
						}
					} else {
						$tmp.="格式不正确";
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
			$username=GetUsernameByQQ($_POST['QQ']);
			if ($username === 0) {
				$text.="该指令需要绑定 BanYou 账号才能继续使用，请使用 !bindid 指令来进行绑定.\n";
				break;
			}
			switch (strtolower($splitarr[1])) {
				case 'list':
					$goodslist=$conn->queryAll("SELECT id,name,callname,stock,money FROM osu_store ORDER BY id ASC",0);
					foreach ($goodslist as $value) {
						if ($value['stock'] === null) {
							$value['stock']='不限';
						}
						$text.="{$value['id']}. 简称：{$value['name']}，品名：{$value['callname']}，库存：{$value['stock']}，价格：{$value['money']}.\n";
					}
					break 2;
				case 'bill':
					if (count($splitarr) > 2) {
						$laststr=$splitarr[count($splitarr)-1];
					}
					$page=(isset($laststr) && is_numeric($laststr) && $laststr > 0) ? $laststr : 1;
					$maxLimit=$page*10;
					$curMaxLimit=$conn->queryOne("SELECT COUNT(*) FROM osu_store_bill WHERE qq = {$_POST['QQ']}");
					if ($maxLimit > $curMaxLimit+9) {
						$text.="没有购买账单或页数超出限制";
						break;
					}
					$minLimit=$maxLimit-9;
					$page=ceil($minLimit/10);
					$maxPage=ceil($curMaxLimit/10);
					$minLimit--;
					$billlist=$conn->queryAll("SELECT s.name,s.callname,p.time,s.money,COUNT(*) as count FROM osu_store_bill sb JOIN osu_store s ON s.id = sb.store_id LEFT JOIN osu_pay p ON p.id = sb.pay_id WHERE sb.qq = {$_POST['QQ']} GROUP BY p.time ORDER BY p.time DESC LIMIT {$minLimit},10");
					foreach ($billlist as $value) {
						if (!empty($value['time'])) {
							$text.="{$value['time']} ";
						}
						$text.="简称：{$value['name']}，品名：{$value['callname']}，数量：{$value['count']}，金额：{$value['money']}.\n";
					}
					$text.="页数：{$page}/{$maxPage}";
					break;
				case 'mygoods':
					$goodslist=$conn->queryAll("SELECT COUNT(*) as count,s.name,s.callname FROM osu_store_bill sb JOIN osu_store s ON s.id = sb.store_id WHERE sb.qq = {$_POST['QQ']} GROUP BY store_id ORDER BY count DESC",0);
					if (count($goodslist) != 0) {
						foreach ($goodslist as $value) {
							$text.="数量：{$value['count']}，简称：{$value['name']}，品名：{$value['callname']}.\n";
						}
						break 2;
					}
					$text.="你没有任何库存商品";
					break;
				case 'sendgift':
					if (!(count($splitarr) > 4 && is_numeric($splitarr[2]) && is_numeric($splitarr[4]))) {
						$text.="Usage: {$commandhelp['buy']['sendgift'][0]}";
						break;
					}
					if ($_POST['QQ'] == $splitarr[2]) {
						$text.="自己不能赠送给自己";
						break;
					}
					$splitarr[3]=sqlstr($splitarr[3]);
					list($curGoodsCount,$curGoodsStoreID)=$conn->queryRow("SELECT COUNT(*), sb.store_id FROM osu_store_bill sb JOIN osu_store s ON s.id = sb.store_id WHERE sb.qq = {$_POST['QQ']} AND s.name = '{$splitarr[3]}'",1);
					if ($splitarr[4] < 1) {
						$text.="赠送数量必须大于 0";
						break;
					} elseif (empty($curGoodsStoreID)) {
						$text.="商店内没有这件商品";
						break;
					} elseif ($splitarr[4] > $curGoodsCount) {
						$text.="你的库存不足";
						break;
					}
					$conn->exec("DELETE FROM osu_store_bill WHERE qq = {$_POST['QQ']} AND store_id = {$curGoodsStoreID} LIMIT {$splitarr[4]}");
					for ($i=0;$i<$splitarr[4];$i++) {
						if (!AddBuyEvent($splitarr[2],$curGoodsStoreID,0)) {
							$text.="记账失败.\n";
						}
					}
					$text.="赠送完成";
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
							$text.="库存不足";
							break;
						}
						if ($buyCount > 1 && $goodsdisposable) {
							$text.="不能购买多个一次性商品";
							break;
						}
						$finalprice=$goodsprice*$buyCount;
						if (GetCurMoney($_POST['QQ']) < $finalprice) {
							$text.="你的余额不足";
							break;
						}
						if ($finalprice > 0) {
							$finalprice="-{$finalprice}";
						} elseif ($finalprice < 0) {
							$finalprice=abs($finalprice);
						}
						if ($finalprice != 0) {
							if (!$payid=AddMoneyEvent("Buy",$_POST['QQ'],$finalprice)) {
								$text.="扣款失败";
								break;
							}
						}
						for ($i=0;$i<$buyCount;$i++) {
							if (!AddBuyEvent($_POST['QQ'],$goodsid,((isset($payid) ? $payid : 0)))) {
								$text.="记账失败";
								break;
							}
						}
						if (!empty($goodssql)) {
							$goodssql=str_replace('{QQ}',$_POST['QQ'],$goodssql);
							$goodssql=str_replace('{username}',$username,$goodssql);
							$conn->exec($goodssql);
						}
						if (mysqli_affected_rows($conn) < 1) {
							$text.="交付商品时发生错误";
							if (!GiveBackMoney($finalprice)) {
								$text.="，退还余额失败";
							}
						} else {
							if ($goodsstock !== null && $goodsstock >= $buyCount) {
								$conn->exec("UPDATE osu_store SET stock=stock-{$buyCount} WHERE id = {$goodsid} LIMIT 1");
							}
							$text.="已扣款并交付商品";
						}
					} else {
						$text.='商店内没有这件商品';
					}
					break;
			}
			$text.=".\n";
			break;
		case 'checkin':
			$username=GetUsernameByQQ($_POST['QQ']);
			if ($username === 0) {
				$text.="该指令需要绑定 BanYou 账号才能继续使用，请使用 !bindid 指令来进行绑定.\n";
				break;
			}
			$checkinType='Checkin';
			if ($conn->queryOne("SELECT 1 FROM osu_pay WHERE type = 'Checkin' AND time >= CURDATE() AND qq = {$_POST['QQ']} LIMIT 1")) {
				if ($messagecount > 1 && $messagearr[1] == '0.3') {
					if (GetCurMoney($_POST['QQ']) < 0.3) {
						$text.="你的余额不足.\n";
						break;
					}
					if (!AddMoneyEvent('Checkin-',$_POST['QQ'],-0.3)) {
						$text.="扣款失败.\n";
						break;
					}
					$text.="已扣款 0.3 个 BanCoin.\n";
					$checkinType.='+';
				} else {
					$text.="请勿再次签到.\n";
					break;
				}
			}
			//$randomMoney=(GetRandomNumber(100)/100)-(GetRandomNumber(50)/100);
			$randomMoney=round(lcg_value()/2,2);
			AddMoneyEvent($checkinType,$_POST['QQ'],$randomMoney);
			$text.="签到成功，获得 {$randomMoney} 个 BanCoin.\n";
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
		case 'weather':
			if ($messagecount > 1) {
				$weather=GetNormalWeather($messagearr[1]);
				if ($weather == 0) {
					$text.="无法获取城市或天气数据.\n";
					break;
				}
				$skyconlist=array('CLEAR_DAY'=>'晴天','CLEAR_NIGHT'=>'晴夜','PARTLY_CLOUDY_DAY'=>'多云','PARTLY_CLOUDY_NIGHT'=>'多云','CLOUDY'=>'阴','RAIN'=>'雨','SNOW'=>'雪','WIND'=>'风','FOG'=>'雾','HAZE'=>'霾','SLEET'=>'冻雨');
				$weather['skycon']=$skyconlist[strtoupper($weather['skycon'])];
				$text.="{$messagearr[1]}实时天气：{$weather['skycon']}，温度：{$weather['temperature']}°，风速：{$weather['windspeed']} km/h，PM2.5：{$weather['pm25']}，云量：{$weather['cloudrate']}，相对湿度：{$weather['humidity']}";
				if (isset($weather['nearest'])) {
					$text.="，最近的降水地区离这里有 {$weather['nearest']['distance']} 公里远，降水量为 {$weather['nearest']['intensity']}";
				}
				$text.=".\n";
			}
			break;
		default:
			return 0;
			break;
	}
	return 1;
}
function HandleMessage($isGroup,$messages) {
	$text='';
	foreach ($messages as $fullmessage) {
		if (empty($fullmessage)) {
			continue;
		}
		if (isGroup($isGroup)) {
			$isSilence=CheckSilenceList($fullmessage);
			if ($isSilence !== 0) {
				$isSilence*=60;
				Silence($_POST['ExternalId'],$_POST['QQ'],$isSilence);
				die();
			}
		}
		$message=(substr($fullmessage,0,1) === '!') ? substr($fullmessage,1) : "";
		if (!empty($message)) {
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
	$sendConnent="Send";
	switch ($isGroup) {
		case 1:
			$sendConnent.="ClusterMessage<&>{$_POST['ExternalId']}";
			break;
		case 0:
			$sendConnent.="Message<&>{$_POST['QQ']}";
			break;
		case 2:
			$sendConnent.="TempMessage<&>{$_POST['ExternalId']}<&>{$_POST['QQ']}";
			break;
	}
	$text=rtrim($text);
	if (!empty($text)) {
		$textarr=str_split($text,3000);
		foreach ($textarr as $value) {
			echo "<&&>{$sendConnent}<&>{$value}\n";
		}
	}
}
if (strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST' || !isset($_POST['Event']) || !isset($_POST['Key'])) {
	die();
}
if ($_POST['Key'] !== BotKey) {
	die();
}
require_once('include.functions.php');
require_once('include.db.php');
$masterQQ='2143585062';
$devGroupNumber='609602961';
$mainGroupNumber='686469603';
$groupNumber=array(
	$devGroupNumber,
	$mainGroupNumber,
	'132783429'
);
if (isset($_POST['QQ'])) {
	$_POST['QQ']=(int)$_POST['QQ'];
	$isMaster=($_POST['QQ'] == $masterQQ);
}
if (isset($_POST['ExternalId'])) {
	$_POST['ExternalId']=(int)$_POST['ExternalId'];
}
switch ($_POST['Event']) {
	case 'KeepAlive':
		// 心跳包
		CheckEvent();
		break;
	case 'CardChanged':
		// 群成员名片发生改变
		break;
	case 'AddMeFriendNeedAuth':
		echo "<&&>AgreeFriendInvite<&>{$_POST['QQ']}<&>3<&>Agree\n";
		break;
	case 'QQPayEvent':
		// 转账事件
		AddMoneyEvent('Recharge',$_POST['QQ'],$_POST['Fee']);
		$username=GetUsernameByQQ($_POST['QQ']);
		if ($username === 0) {
			$username="QQ:{$_POST['QQ']}";
		}
		foreach ($groupNumber as $value) {
			echo "<&&>SendClusterMessage<&>{$value}<&>[BanCoin] {$username} 充值了 {$_POST['Fee']}.\n";
		}
		break;
	// 接收到新的私聊(临时/好友)消息
	case 'ReceiveTempIM':
		$messages=explode("\r",$_POST['Message']);
		HandleMessage(2,$messages);
		break;
	case 'ReceiveNormalIM':
		$messages=explode("\r",$_POST['Message']);
		HandleMessage(0,$messages);
		break;
	case 'ReceiveClusterIM':
		// 接收到新的群消息
		CheckEvent();
		$messages=explode("\r",$_POST['Message']);
		HandleMessage(1,$messages);
		break;
	case 'AddedToCluster':
		// 新成员入群
		if ($_POST['ExternalId'] == $mainGroupNumber) {
			echo "<&&>SendClusterMessage<&>{$mainGroupNumber}<&>[@{$_POST['QQ']}] 欢迎来到 BanYou 玩家群，请将你的名片改为 osu! ID。\n要进入 BanYou，请在群文件下载客户端和使用指南。\n成功邀请一个进入 BanYou 的新玩家将赠送 14 天 BanYou SupportPlayer。\n";
		} elseif ($blockTime=$conn->queryOne("SELECT BlockTime FROM bot_blockqqlist WHERE group_number = {$_POST['ExternalId']} AND BlockQQ = {$_POST['QQ']} LIMIT 1")) {
			Silence($_POST['ExternalId'],$_POST['QQ'],$blockTime*60);
		}
		break;
}
?>
