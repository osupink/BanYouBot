<?php
global $billtypelist, $lang, $commandhelp;
$billtypelist=array(
	'Buy'=>'购买',
	'Checkin'=>'免费签到',
	'Checkin-'=>'签到支出',
	'Checkin+'=>'签到收入',
	'Transfer-'=>'转出',
	'Transfer+'=>'转入',
	'Change'=>'修改',
	'Recharge'=>'充值'
);
$lang=array(
	'need_bindid'=>'该指令需要绑定 BanYou 账号才能继续使用, 请使用 !bindid 指令来进行绑定.',
	'type'=>'类型',
	'money'=>'金额',
	'first'=>'首次',
	'colon'=>': ',
	'comma'=>', ',
	'usage'=>'用法',
	'score'=>'分数',
	'stock'=>'库存',
	'price'=>'价格',
	'count'=>'数量',
	'free'=>'免费',
	'your'=>'你的',
	'get'=>'获得',
	'of'=>'的',
	'rank'=>'评级',
	'second'=>'秒',
	'beatmap'=>'谱面',
	'accuracy'=>'准确度',
	'userpage'=>'用户页',
	'page_number'=>'页数',
	'unrestricted'=>'不限',
	'bancoin_balance'=>'BanCoin 余额',
	'goods_name'=>'品名',
	'shorter_goods_name'=>'简称',
	'beatmap_hit_length'=>'谱长',
	'beatmap_total_length'=>'总长',
	'added_friends'=>'添加的好友',
	'not_a_true_qqnumber'=>'提供的 QQ 号不正确.',
	'not_a_true_silence_time'=>'提供的禁言时间不正确.',
	'have_not_bp'=>'这个玩家没有最佳成绩 :(.',
	'have_not_+_bill_or_out_of_range'=>'没有%s账单或页数超出限制.',
	'user_not_found'=>'找不到这个玩家.',
	'user_not_found_or_password+'=>'或者提供的密码错误',
	'no_play_records'=>'没有游玩记录.',
	'no_username_provide'=>'没有提供玩家名.',
	'max_repeat_checkin_count_has_been_reached'=>'重复签到次数已达上限.',
	'attention:command_requires_prefix_in_this_group'=>'注意: 本群命令需要 !by 前缀',
	'supporter_expirydate'=>'%s 的 Supporter/SupportPlayer 到期日期: %s',
	'updated_supporter_expirydate'=>'已更新 %s 的到期日期',
	'updated_supporter_expirydate_to+'=>'至 %s',
	'username_has_been_bound'=>'这个 BanYou 账号名已经被绑定了.',
	'qq_has_been_bound'=>'这个 QQ 已经被绑定了.',
	'binding_success'=>'绑定成功！',
	'binding_success+'=>'最后一步: 在游戏内的聊天窗口中发送 !bindqq %s 以完成验证.',
	'change_balance_succeed'=>'修改余额成功',
	'change_balance_failed'=>'修改余额失败',
	'no_money'=>'你没有余额或是余额为 0',
	'not_enough_money'=>'余额不足',
	'get_money_before_receive_money'=>'被转账玩家的账号必须有余额',
	'can_not_transfer_to_myself'=>'不能转账给自己',
	'balance_is_+'=>'%s 的余额为 %.2f',
	'transfer_money_must_>_0'=>'转账金额必须大于 0',
	'transfer_money_must_<=_1000'=>'转账金额必须小于等于 1000',
	'+_length_is_not_true'=>'%s 位数不正确',
	'transfer_succeed'=>'转账成功',
	'add_money_failed'=>'加款失败',
	'deduct_money_failed'=>'扣款失败',
	'format_is_not_true'=>'格式不正确',
	'have_not_any_goods'=>'你没有任何库存商品',
	'can_not_send_gift_to_myself'=>'自己不能赠送给自己',
	'send_gift_count_must_>_0'=>'赠送数量必须大于 0',
	'have_not_this_goods'=>'你没有这件商品',
	'have_not_this_goods_count'=>'你的库存不足',
	'bookkeeping_failed'=>'记账失败.',
	'send_gift_succeed'=>'赠送完成',
	'not_enough_stock'=>'库存不足',
	'h_not_found'=>'找不到本子！',
	'can_not_buy_multi_disposable_goods'=>'不能购买多个一次性商品',
	'error_occurred_when_provide_goods'=>'交付商品时发生错误',
	'return_money_failed'=>'退还余额失败',
	'delete_store_bill_failed'=>'删除购买记录失败',
	'deduct_money_and_provide_goods_succeed'=>'已扣款并交付商品',
	'will_take_effect_immediately'=>'将立即生效',
	'have_not_this_goods_in_store'=>'商店内没有这件商品',
	'have_not_any_goods_in_store'=>'商店内没有任何商品',
	'deducted_+_money'=>'已扣款 %s 个 BanCoin.',
	'do_not_checkin_again'=>'请勿再次签到.',
	'checkin_succeed'=>'签到成功',
	'you_have_not_added_any_friends_yet'=>'你还没有添加任何好友！',
	'you_have_added_him/her_as_a_friend'=>'你已经添加了他/她为好友',
	'you_have_not_added_him/her_as_a_friend_yet'=>'你还没有添加他/她为好友',
	'he/she_has_added_you_as_a_friend'=>'他/她已经添加了你为好友',
	'he/she_has_not_added_you_as_a_friend_yet'=>'他/她还没有添加你为好友',
	'have_not_blockqqlist'=>'没有任何 QQ 在黑名单里.',
	'have_not_blocktextlist'=>'没有任何文本在黑名单里.'
);
$commandhelp=array(
	'buy'=>array(
		''=>array('!buy <商品简称>','购买商品'),
		'bill'=>array('!buy bill','列出我在商店的账单'),
		'list'=>array('!buy list','列出商品列表'),
		'mygoods'=>array('!buy mygoods','查看我拥有的商品'),
		'sendgift'=>array('!buy sendgift <QQ> <商品简称> <数量>','赠送我所拥有的商品给指定 QQ 当礼物')
	),
	'user'=>array(
		'supporter'=>array('!user supporter <BanYou 账号名>','查看指定玩家 Supporter/SupportPlayer 的到期日期')
	),
	'bancoin'=>array(
		'bill'=>array('!bancoin bill','列出我的 BanCoin 账单'),
		'rank'=>array('!bancoin rank','查看 BanCoin 余额排名 (仅群组内可用)'),
		'balance'=>array('!bancoin balance','显示我的余额'),
		'transfer'=>array('!bancoin transfer <QQ> <BanCoin 数量>','给指定 QQ 转账指定数量的 BanCoin')
	),
	'botadmin'=>array(
		'kick'=>array('!botadmin kick <QQ>','将指定 QQ 踢出群'),
		'silence'=>array('!botadmin silence <QQ/All> [禁言时间 (分钟, 指定 QQ 可用)]','将指定 QQ 或全体成员禁言 (默认 1 分钟)'),
		'unsilence'=>array('!botadmin unsilence <QQ/All>','将指定 QQ 或全体成员解除禁言'),
		'blockqq'=>array('!botadmin blockqq <QQ> [禁言时间 (分钟)]','将指定 QQ 拉入禁言/踢人黑名单'),
		'blocktext'=>array('!botadmin blocktext <文本>','将指定文本加入黑名单'),
		'unblockqq'=>array('!botadmin unblockqq <QQ>','将指定 QQ 从黑名单中移出'),
		'unblocktext'=>array('!botadmin unblocktext <文本>','将指定文本从黑名单中移出'),
		'blockqqlist'=>array('!botadmin blockqqlist','列出本群的 QQ 黑名单'),
		'blocktextlist'=>array('!botadmin blocktextlist','列出本群的文本黑名单'),
		'changecard'=>array('!botadmin changecard <QQ> <名片>','修改 Bot/指定 QQ 的名片'),
		'settitle'=>array('!botadmin settitle <QQ> <专属头衔>','修改 Bot/指定 QQ 的专属头衔 (仅在 Bot 为群主时可用)'),
		'deletemsg'=>array('!botadmin deletemsg','回复消息时使用以撤回该消息')
	),
	'atall'=>array('!atall','艾特全体成员'),
	'checkin'=>array('!checkin','签到并获得 BanCoin'),
	'friends'=>array('!friends [BanYou 账号名]','查看我在 BanYou 的好友列表'),
	'roll'=>array('!roll [最大随机数]','得到从 1 到指定数字的随机数 (默认的数字是 100)'),
	'bindid'=>array('!bindid <BanYou 账号>[:BanYou 密码 (仅私聊内可用)]','绑定 BanYou ID'),
	'h'=>array('!h [Tag(|Tag|...)]','从 Pixiv 随机获取一张涩图 (可指定 Tag, 多 Tag 需同时满足)'),
	'sleep'=>array('!sleep [时间: 默认 = 720 分钟 (12 小时), 最大 <= 1440 分钟]','让我好好睡个觉, 谁也别吵我'),
	'br'=>array('!br [Mode:0 (STD), 1 (Taiko), 2 (Catch The Beat), 3 (osu!mania)]','查看我在 BanYou 的最近游玩'),
	'br-overall'=>array('!br-overall [Mode:0 (STD), 1 (Taiko), 2 (Catch The Beat), 3 (osu!mania)]','查看全服 BanYou 的最近游玩'),
	'bp'=>array('!bp <BanYou 账号名> [Mode:0 (STD), 1 (Taiko), 2 (Catch The Beat), 3 (osu!mania)]','列出指定玩家的最佳成绩'),
	'stats'=>array('!stats <BanYou 账号名> [Mode:0 (STD), 1 (Taiko), 2 (Catch The Beat), 3 (osu!mania)]','得到指定玩家的游玩数据')
	/*'weather'=>array('!weather <城市名>','查看指定城市的天气预报'),*/
);
?>
