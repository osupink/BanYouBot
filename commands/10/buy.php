<?php
global $lang, $commandhelp;
if (!isset($commandSubType)) {
	foreach ($commandhelp['buy'] as $value) {
		$sendMessageBuffer.="{$value[0]} - {$value[1]}\n";
	}
	return;
}
$username=isBindID($reqQQNumber,$sendMessageBuffer);
if (!$username) {
	return;
}
switch (strtolower($commandSubType)) {
	case 'list':
		$res=$conn->query("SELECT id,name,callname,stock,money FROM osu_store ORDER BY id ASC");
		$goodslist=$res->fetch_all(MYSQLI_ASSOC);
		$i=0;
		foreach ($goodslist as $value) {
			if ($value['stock'] === null) {
				$value['stock']=$lang['unrestricted'];
			} elseif ($value['stock'] < 1) {
				continue;
			}
			if ($value['money'] == 0) {
				$value['money']=$lang['free'];
			}
			$i++;
			$sendMessageBuffer.="{$i}. {$lang['shorter_goods_name']}{$lang['colon']}{$value['name']}{$lang['comma']}{$lang['goods_name']}{$lang['colon']}{$value['callname']}{$lang['comma']}{$lang['stock']}{$lang['colon']}{$value['stock']}{$lang['comma']}{$lang['price']}{$lang['colon']}{$value['money']}.\n";
		}
		if ($i === 0) {
			$sendMessageBuffer.=$lang['have_not_any_goods_in_store'];
		} else {
			return;
		}
		break;
	case 'bill':
		$page=(isset($commandContent) && is_numeric($commandContent) && $commandContent > 0) ? $commandContent : 1;
		$maxLimit=$page*10;
		$res=$conn->query("SELECT COUNT(*) FROM (SELECT 1 FROM osu_store_bill sb LEFT JOIN osu_pay p ON p.id = sb.pay_id WHERE sb.qq = {$reqQQNumber} GROUP BY p.time ORDER BY p.time) g");
		list($curMaxLimit)=$res->fetch_row();
		if ($maxLimit > $curMaxLimit+9) {
			$sendMessageBuffer.=sprintf($lang['have_not_+_bill_or_out_of_range'],'购买');
			break;
		}
		$minLimit=$maxLimit-9;
		$page=ceil($minLimit/10);
		$maxPage=ceil($curMaxLimit/10);
		$minLimit--;
		$res=$conn->query("SELECT sb.pay_id,s.name,s.callname,p.time,s.money,COUNT(*) as count FROM osu_store_bill sb JOIN osu_store s ON s.id = sb.store_id LEFT JOIN osu_pay p ON p.id = sb.pay_id WHERE sb.qq = {$reqQQNumber} GROUP BY p.time ORDER BY p.time DESC LIMIT {$minLimit},10");
		$billlist=$res->fetch_all(MYSQLI_ASSOC);
		foreach ($billlist as $value) {
			if (!empty($value['time'])) {
				$sendMessageBuffer.="{$value['pay_id']}. ";
				$sendMessageBuffer.="{$value['time']} ";
			}
			$sendMessageBuffer.="{$lang['shorter_goods_name']}{$lang['colon']}{$value['name']}{$lang['comma']}{$lang['goods_name']}{$lang['colon']}{$value['callname']}{$lang['comma']}{$lang['count']}{$lang['colon']}{$value['count']}{$lang['comma']}{$lang['money']}{$lang['colon']}{$value['money']}.\n";
		}
		$sendMessageBuffer.="{$lang['page_number']}{$lang['colon']}{$page}/{$maxPage}";
		break;
	case 'mygoods':
		$res=$conn->query("SELECT COUNT(*) as count,s.name,s.callname FROM osu_store_bill sb JOIN osu_store s ON s.id = sb.store_id WHERE sb.qq = {$reqQQNumber} AND s.disposable = 0 GROUP BY store_id ORDER BY count DESC");
		$goodslist=$res->fetch_all(MYSQLI_ASSOC);
		if (count($goodslist) != 0) {
			foreach ($goodslist as $value) {
				$sendMessageBuffer.="{$lang['count']}{$lang['colon']}{$value['count']}{$lang['comma']}{$lang['shorter_goods_name']}{$lang['colon']}{$value['name']}{$lang['comma']}{$lang['goods_name']}{$lang['colon']}{$value['callname']}.\n";
			}
			return;
		}
		$sendMessageBuffer.=$lang['have_not_any_goods'];
		break;
	case 'sendgift':
		if (isset($commandArr)) {
			$commandArr[0]=isAT($commandArr[0]);
		}
		if (!(count($commandArr) > 2 && is_numeric($commandArr[0]) && is_numeric($commandArr[2]))) {
			$sendMessageBuffer.="{$lang['usage']}{$lang['colon']}{$commandhelp['buy']['sendgift'][0]}";
			break;
		}
		if ($reqQQNumber == $commandArr[0]) {
			$sendMessageBuffer.=$lang['can_not_send_gift_to_myself'];
			break;
		}
		if ($commandArr[2] < 1) {
			$sendMessageBuffer.=$lang['send_gift_count_must_>_0'];
			break;
		}
		$commandArr[1]=$conn->escape_string($commandArr[1]);
		$res=$conn->query("SELECT COUNT(*), sb.store_id FROM osu_store_bill sb JOIN osu_store s ON s.id = sb.store_id WHERE sb.qq = {$reqQQNumber} AND s.disposable = 0 AND s.name = '{$commandArr[1]}'");
		list($curGoodsCount,$curGoodsStoreID)=$res->fetch_row();
		if (empty($curGoodsStoreID)) {
			$sendMessageBuffer.=$lang['have_not_this_goods'];
			break;
		} elseif ($commandArr[2] > $curGoodsCount) {
			$sendMessageBuffer.=$lang['have_not_this_goods_count'];
			break;
		}
		$conn->query("DELETE FROM osu_store_bill WHERE qq = {$reqQQNumber} AND store_id = {$curGoodsStoreID} LIMIT {$commandArr[2]}");
		for ($i=0;$i<$commandArr[2];$i++) {
			if (!AddBuyEvent($commandArr[0],$curGoodsStoreID,0)) {
				$sendMessageBuffer.="{$lang['bookkeeping_failed']}\n";
			}
		}
		$sendMessageBuffer.=$lang['send_gift_succeed'];
		break;
	default:
		$buyCount=1;
		if (isset($commandArr) && is_numeric($commandArr[count($commandArr)-1]) && $commandArr[count($commandArr)-1] > 0) {
			$buyCount=$commandArr[count($commandArr)-1];
		}
		$goodsname=$conn->escape_string($commandSubType);
		$res=$conn->query("SELECT id,name,stock,money,`sql`,disposable FROM osu_store WHERE name = '{$goodsname}' LIMIT 1");
		list($goodsid,$goodsname,$goodsstock,$goodsprice,$goodssql,$goodsdisposable)=$res->fetch_row();
		if (!empty($goodsid)) {
			if ($goodsstock !== null && $goodsstock == 0) {
				$sendMessageBuffer.=$lang['not_enough_stock'];
				break;
			}
			if ($buyCount > 1 && $goodsdisposable) {
				$sendMessageBuffer.=$lang['can_not_buy_multi_disposable_goods'];
				break;
			}
			$finalprice=$goodsprice*$buyCount;
			if (GetCurMoney($reqQQNumber) < $finalprice) {
				$sendMessageBuffer.="{$lang['your']}{$lang['not_enough_money']}";
				break;
			}
			if ($finalprice > 0) {
				$finalprice="-{$finalprice}";
			} elseif ($finalprice < 0) {
				$finalprice=abs($finalprice);
			}
			if ($finalprice != 0) {
				if (!$payid=AddMoneyEvent("Buy",$reqQQNumber,$finalprice)) {
					$sendMessageBuffer.=$lang['deduct_money_failed'];
					break;
				}
			}
			for ($i=0;$i<$buyCount;$i++) {
				if (!AddBuyEvent($reqQQNumber,$goodsid,((isset($payid) ? $payid : 0)))) {
					$sendMessageBuffer.=$lang['bookkeeping_failed'];
					break;
				}
			}
			if (!empty($goodssql)) {
				$goodssql=str_replace('{QQ}',$reqQQNumber,$goodssql);
				$goodssql=str_replace('{username}',$username,$goodssql);
				$conn->query($goodssql);
			}
			if ($conn->affected_rows < 1) {
				$sendMessageBuffer.=$lang['error_occurred_when_provide_goods'];
				if (isset($payid)) {
					if (!GiveBackMoney($payid)) {
						$sendMessageBuffer.="{$lang['comma']}{$lang['return_money_failed']}";
					}
				}
				if (!DeleteStoreBill($reqQQNumber,$goodsid)) {
					$sendMessageBuffer.="{$lang['comma']}{$lang['delete_store_bill_failed']}";
				}
			} else {
				if ($goodsstock !== null && $goodsstock >= $buyCount) {
					$conn->query("UPDATE osu_store SET stock=stock-{$buyCount} WHERE id = {$goodsid} LIMIT 1");
				}
				$sendMessageBuffer.=$lang['deduct_money_and_provide_goods_succeed'];
			}
		} else {
			$sendMessageBuffer.=$lang['have_not_this_goods_in_store'];
		}
		break;
}
$sendMessageBuffer.=".\n";
?>
