<?php
global $conn;
function AddMoneyEvent(string $type, int $qq, $money): int {
	global $conn;
	if ($conn->query("INSERT INTO osu_pay (`type`,`qq`,`money`) VALUES ('{$type}',{$qq},{$money})")) {
		$res = $conn->query("SELECT LAST_INSERT_ID()");
		if ($res && list($result) = $res->fetch_row()) {
			if ($result !== null) {
				return $result;
			}
		}
	}
	return 0;
}
function AddBuyEvent(int $qq, int $store_id, int $pay_id): bool {
	global $conn;
	return $conn->query("INSERT INTO osu_store_bill (`qq`,`store_id`,`pay_id`) VALUES ({$qq},{$store_id},{$pay_id})");
}
function DeleteStoreBill(int $qq, int $goodsid): bool {
	global $conn;
	if ($conn->query("DELETE FROM osu_store_bill WHERE qq = {$qq} AND store_id = {$goodsid} LIMIT 1")) {
		return true;
	}
	return false;
}
function GiveBackMoney(int $payid): bool {
	global $conn;
	if ($conn->query("DELETE FROM osu_store_bill WHERE pay_id = {$payid} LIMIT 1") && $conn->query("DELETE FROM osu_pay WHERE id = {$payid} LIMIT 1")) {
		return true;
	}
	return false;
}
function GetCurMoney(int $qq): float {
	global $conn;
	$res = $conn->query("SELECT SUM(money) FROM osu_pay WHERE qq = {$qq}");
	if ($res->num_rows < 1) {
		return 0;
	}
	list($money) = $res->fetch_row();
	if ($money === null) {
		return 0;
	}
	return $money;
}
?>
