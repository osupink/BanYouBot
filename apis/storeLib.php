<?php
global $conn;
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
?>
