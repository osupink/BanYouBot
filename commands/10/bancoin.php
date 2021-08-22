<?php
global $lang, $isMaster, $commandhelp;
$username=isBindID($reqQQNumber,$sendMessageBuffer);
if (!$username) {
	return;
}
if (!isset($commandSubType)) {
	foreach ($commandhelp['bancoin'] as $value) {
		$sendMessageBuffer.="{$value[0]} - {$value[1]}\n";
	}
	return;
}
$tmp='';
switch (strtolower($commandSubType)) {
	case 'change':
		if (isset($commandArr) && count($commandArr) > 1 && $isMaster) {
			$commandArr[0]=isAT($commandArr[0]);
			if (AddMoneyEvent('Change',$commandArr[0],$commandArr[1])) {
				$tmp.=$lang['change_balance_succeed'];
			} else {
				$tmp.=$lang['change_balance_failed'];
			}
		}
		break;
	case 'balance':
		if (isset($commandArr) && count($commandArr) > 0) {
			$commandArr[0]=isAT($commandArr[0]);
		}
		if ($isMaster && isset($commandArr) && count($commandArr) > 0 && is_numeric($commandArr[0])) {
			$username=GetUsernameByQQ($commandArr[0]);
			if (!$username) {
				$username="[CQ:at,qq=$commandArr[0]]";
			}
			$curMoney=GetCurMoney($commandArr[0]);
		} else {
			$curMoney=GetCurMoney($reqQQNumber);
		}
		if ($curMoney == 0) {
			$tmp.=$lang['no_money'];
		} else {
			$tmp.=sprintf($lang['balance_is_+'],$username,$curMoney);
		}
		break;
	case 'rank':
		if (isset($reqGroupNumber)) {
			$res=$conn->query("SELECT qq,SUM(money) as money FROM osu_pay GROUP BY qq ORDER BY money DESC LIMIT 10");
			$ranklist=$res->fetch_all(MYSQLI_ASSOC);
			$count=1;
			foreach ($ranklist as $value) {
				$userqq=$value['qq'];
				$username=GetUsernameByQQ($userqq);
				$usermoney=$value['money'];
				if (!$username) {
					
					$username="QQ:{$userqq}";
				}
				$sendMessageBuffer.="{$count}. {$username}{$lang['comma']}{$lang['bancoin_balance']}{$lang['colon']}{$usermoney}.\n";
				$count++;
			}
		}
		break;
	case 'bill':
		if (isset($commandArr) && count($commandArr) > 0) {
			$billQQNumber=isAT($commandArr[0]);
			if ($commandArr[0] == $billQQNumber) {
				$billQQNumber=0;
			} elseif (count($commandArr) > 1) {
				$commandArr[0]=$commandArr[1];
				unset($commandArr[1]);
			} else {
				unset($commandArr[0]);
			}
		}
		if (!isset($billQQNumber) || $billQQNumber === 0) {
			$billQQNumber=$reqQQNumber;
		}
		$page=(isset($commandArr) && count($commandArr) > 0 && is_numeric($commandArr[0]) && $commandArr[0] > 0) ? $commandArr[0] : 1;
		$maxLimit=$page*10;
		$res=$conn->query("SELECT COUNT(*) FROM osu_pay WHERE qq = {$billQQNumber}");
		if ($res->num_rows > 0) {
			list($curMaxLimit)=$res->fetch_row();
		}
		if (!isset($curMaxLimit) || $maxLimit > $curMaxLimit+9) {
			$sendMessageBuffer.=sprintf($lang['have_not_+_bill_or_out_of_range'],' BanCoin ');
			$sendMessageBuffer.="\n";
			break;
		}
		$minLimit=$maxLimit-9;
		$page=ceil($minLimit/10);
		$maxPage=ceil($curMaxLimit/10);
		$minLimit--;
		$res=$conn->query("SELECT id,time,type,money FROM osu_pay WHERE qq = {$billQQNumber} ORDER BY time DESC LIMIT {$minLimit},10");
		$billlist=$res->fetch_all(MYSQLI_ASSOC);
		foreach ($billlist as $value) {
			$type=(isset($billtypelist[$value['type']])) ? $billtypelist[$value['type']] : $value['type'];
			$sendMessageBuffer.="{$value['id']}. {$value['time']} {$lang['type']}{$lang['colon']}{$type}{$lang['comma']}{$lang['money']}{$lang['colon']}{$value['money']}.\n";
		}
		$sendMessageBuffer.="{$lang['page_number']}{$lang['colon']}{$page}/{$maxPage}.\n";
		break;
	case 'transfer':
		if (isset($commandArr) && count($commandArr) < 2) {
			$sendMessageBuffer.="{$lang['usage']}{$lang['colon']}{$commandhelp['bancoin']['transfer'][0]}.\n";
			break;
		}
		$commandArr[0]=isAT($commandArr[0]);
		if (is_numeric($commandArr[0]) && (is_numeric($commandArr[1]) || is_float($commandArr[1]))) {
			if ($commandArr[1] <= 0) {
				$tmp.=$lang['transfer_money_must_>_0'];
			} elseif ($commandArr[1] > 1000) {
				$tmp.=$lang['transfer_money_must_<=_1000'];
			} elseif (strlen($commandArr[0]) > 10) {
				$tmp.=sprintf($lang['+_length_is_not_true'],'QQ');
			} elseif (GetCurMoney($reqQQNumber) < $commandArr[1]) {
				$tmp.=$lang['not_enough_money'];
			} elseif (GetCurMoney($commandArr[0]) == 0) {
				$tmp.=$lang['get_money_before_receive_money'];
			} elseif ($reqQQNumber == $commandArr[0]) {
				$tmp.=$lang['can_not_transfer_to_myself'];
			} else {
				if (AddMoneyEvent('Transfer-',$reqQQNumber,"-{$commandArr[1]}")) {
					if (AddMoneyEvent('Transfer+',$commandArr[0],$commandArr[1])) {
						$tmp.=$lang['transfer_succeed'];
						$received_username=GetUsernameByQQ($commandArr[0]);
						if (!$received_username) {
							$received_username="QQ:{$commandArr[0]}";
						}
						$commandArr[1]=sprintf('%.2f',$commandArr[1]);
						Announce("[BanCoin] {$received_username} 通过转账被赠送了 {$commandArr[1]}.");
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
	$sendMessageBuffer.="[BanCoin] {$tmp}.\n";
	unset($tmp);
}
?>
