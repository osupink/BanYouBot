<?php
global $conn;
function AddMoneyEvent($type, $qq, $money) {
    global $conn;
    if (is_numeric($qq) && (is_float($money) || is_numeric($money))) {
        if ($conn->query("INSERT INTO osu_pay (`type`,`qq`,`money`) VALUES ('{$type}',{$qq},{$money})")) {
            $res=$conn->query("SELECT LAST_INSERT_ID()");
            if ($res && list($result)=$res->fetch_row()) {
                return $result;
            }
        }
    }
    return 0;
}
function AddBuyEvent($qq, $store_id, $pay_id) {
    global $conn;
    if (is_numeric($qq) && is_numeric($store_id) && is_numeric($pay_id)) {
        return $conn->query("INSERT INTO osu_store_bill (`qq`,`store_id`,`pay_id`) VALUES ({$qq},{$store_id},{$pay_id})");
    }
    return 0;
}
function DeleteStoreBill($qq, $goodsid) {
    global $conn;
    if (!is_numeric($goodsid)) {
        return 0;
    }
    if ($conn->query("DELETE FROM osu_store_bill WHERE qq = {$qq} AND store_id = {$goodsid} LIMIT 1")) {
        return 1;
    }
    return 0;
}
function GiveBackMoney($payid) {
    global $conn;
    if (!is_numeric($payid)) {
        return 0;
    }
    if ($conn->query("DELETE FROM osu_store_bill WHERE pay_id = {$payid} LIMIT 1") && $conn->query("DELETE FROM osu_pay WHERE id = {$payid} LIMIT 1")) {
        return 1;
    }
    return 0;
}
function GetCurMoney($qq) {
    global $conn;
    if (!is_numeric($qq)) {
        return 0;
    }
    $res=$conn->query("SELECT SUM(money) FROM osu_pay WHERE qq = {$qq}");
    if ($res->num_rows < 1) {
        return 0;
    }
    list($money)=$res->fetch_row();
    return $money;
}
?>
