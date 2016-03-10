<?php
global $_W, $_GPC;
$this -> backlists();
load() -> func('tpl');
$op = $_GPC['op'];
if (empty($op)) {
	$this->updategourp();
	//更新团状态
	$groupstatus = $_GPC['groupstatus'];
	$will_die = $_GPC['will_die'];
	$pindex = max(1, intval($_GPC['page']));
	$psize = 10;
	/*搜索条件*/
	$condition = "uniacid = {$_W['uniacid']}";
	$time = $_GPC['time'];
	if (empty($starttime) || empty($endtime)) {
		$starttime = strtotime('-1 month');
		$endtime = time();
	}
	if (!empty($_GPC['time'])) {
		$starttime = strtotime($_GPC['time']['start']);
		$endtime = strtotime($_GPC['time']['end']) + 86399;
		$condition .= " AND  starttime >= {$starttime} AND  starttime <= {$endtime} ";

	}
	if (!empty($_GPC['keyword'])) {
		$condition .= " AND groupnumber LIKE '%{$_GPC['keyword']}%'";
	}
	if (!empty($groupstatus)) {
		$condition .= " AND groupstatus ='{$groupstatus}'";
	}
	if (!empty($will_die)) {
		if (empty($_GPC['endhour'])) {
			$nowtime = time();
			$endtime_tuan = $nowtime + 3600;
			$condition .= " AND endtime <= {$endtime_tuan}";
			if (!empty($_GPC['lacknumber'])) {
				$condition .= " AND lacknum = {$_GPC['lacknumber']} ";
			}
		} else {
			$endhour = $_GPC['endhour'];
			$nowtime = time();
			$endtime_tuan = $nowtime + $endhour * 3600;
			$condition .= " AND endtime <= {$endtime_tuan}";
			if (!empty($_GPC['lacknumber'])) {
				$condition .= " AND lacknum = {$_GPC['lacknumber']} ";
			}
		}

	}
	$condition .= " AND lacknum <>neednum";

	/*搜索条件*/
	$alltuan = pdo_fetchall("select * from" . tablename('tg_group') . "where $condition order by id desc " . "LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
	$nowtime = time();
	foreach ($alltuan as $key => $value) {
		$refund_orders = pdo_fetchall("select * from" . tablename('tg_order') . "where tuan_id='{$value['groupnumber']}' and uniacid='{$_W['uniacid']}' and status=7");
		$send_orders = pdo_fetchall("select * from" . tablename('tg_order') . "where tuan_id='{$value['groupnumber']}' and uniacid='{$_W['uniacid']}' and status in(3,4)");
		$alltuan[$key]['lasttime'] = $value['endtime'] - $nowtime;
		$alltuan[$key]['refundnum'] = count($refund_orders);
		$alltuan[$key]['sendnum'] = count($send_orders);
	}
	$alltuan2 = pdo_fetchall("select * from" . tablename('tg_group') . "where $condition order by id desc ");
	$total = count($alltuan2);
	$pager = pagination($total, $pindex, $psize);
} elseif ($op == 'group_detail') {
	$groupnumber = intval($_GPC['groupnumber']);
	//指定团的id
	$thistuan = pdo_fetch("select * from" . tablename('tg_group') . "where groupnumber = '{$groupnumber}' and uniacid='{$_W['uniacid']}'");
	$orders = pdo_fetchall("SELECT * FROM " . tablename('tg_order') . " WHERE tuan_id = '{$groupnumber}' and uniacid='{$_W['uniacid']}'");
	$goods = pdo_fetch("select * from" . tablename('tg_goods') . "where id='{$thistuan['goodsid']}'");
} elseif ($op == 'autogroup') {
	$groupnumber = intval($_GPC['groupnumber']);
	//指定团的id
	$thistuan = pdo_fetch("select * from" . tablename('tg_group') . "where groupnumber = '{$groupnumber}' and uniacid='{$_W['uniacid']}'");
	$orders2 = pdo_fetchall("SELECT * FROM " . tablename('tg_order') . " WHERE tuan_id = '{$groupnumber}' and uniacid='{$_W['uniacid']}'");
	$goods = pdo_fetch("select * from" . tablename('tg_goods') . "where id='{$thistuan['goodsid']}'");
	//虚拟订单
	$num = array();
	$lacknum = $thistuan['lacknum'];
	$lack = $thistuan['lacknum'];
	for ($i = 0; $i < $lacknum; $i++) {
		$num[$i] = $i;
		if (!empty($_GPC[$i]) && !empty($_GPC['addnickname' . $i])) {
			$lack = $lack - 1;
			$avatar = $_GPC[$i];
			$nickname = $_GPC['addnickname' . $i];
			$data = array('uniacid' => $_W['uniacid'], 'gnum' => 1, 'openid' => $avatar, 'ptime' => '', //支付成功时间
			'orderno' => date('Ymd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99)), 'price' => 0, 'status' => 1, //订单状态：0未支,1支付，2待发货，3已发货，4已签收，5已取消，6待退款，7已退款
			'addressid' => 0, 'addname' => $nickname, 'mobile' => '虚拟', 'address' => '虚拟', 'g_id' => $thistuan['goodsid'], 'tuan_id' => $thistuan['groupnumber'], 'is_tuan' => 1, 'tuan_first' => 0, 'starttime' => TIMESTAMP, 'createtime' => TIMESTAMP);
			pdo_insert('tg_order', $data);
		}
	}
	pdo_update('tg_group', array('lacknum' => $lack), array('groupnumber' => $thistuan['groupnumber']));
	$nowthistuan = pdo_fetch("select * from" . tablename('tg_group') . "where groupnumber = '{$groupnumber}' and uniacid='{$_W['uniacid']}'");
	if ($nowthistuan['lacknum'] == 0) {
		pdo_update('tg_group', array('groupstatus' => 2), array('groupnumber' => $nowthistuan['groupnumber']));
		$orders3 = pdo_fetchall("SELECT * FROM " . tablename('tg_order') . " WHERE tuan_id = '{$groupnumber}' and uniacid='{$_W['uniacid']}' and status=1 and mobile<>'虚拟' ");
		foreach ($orders3 as $key => $value) {
			pdo_update('tg_order', array('status' => 2), array('id' => $value['id']));
		}

	}
	$orders = pdo_fetchall("SELECT * FROM " . tablename('tg_order') . " WHERE tuan_id = '{$groupnumber}' and uniacid='{$_W['uniacid']}'");
} elseif ($op == 'refundgroup') {
	$groupnumber = intval($_GPC['groupnumber']);
	//指定团的id
	$orders = pdo_fetchall("SELECT * FROM " . tablename('tg_order') . " WHERE tuan_id = '{$groupnumber}' and uniacid='{$_W['uniacid']}' and status in(1,2,6) ");
	$success_num=0;
	$fail_num=0;
	foreach ($orders as $k => $value) {
			$refund_ids = pdo_fetch("select * from".tablename('tg_order')."where id='{$value['id']}'");
					$res = $this->refund($refund_ids['orderno'],'',2);
					if($res == 'success'){
						$success_num+=1;
					}else{
						$fail_num+=1;
					}
	}
	message('全团退款操作成功！成功' . $success_num . '人,失败' . $fail_num . '人', referer(), 'success');
} elseif ($op == 'output') {
	$groupstatus = $_GPC['groupstatus'];
	if ($groupstatus == 1) {
		$str = '团购失败订单_' . time();
	}
	if ($groupstatus == 2) {
		$str = '团购成功订单_' . time();
	}
	if ($groupstatus == 3) {
		$str = '组团中订单_' . time();
	}
	if (empty($groupstatus)) {
		$str = '所有团订单_' . time();
	}
	$con = "uniacid = {$_W['uniacid']}";
	if (!empty($groupstatus)) {
		$con .= " and groupstatus='{$groupstatus}' ";
	}
	if (!empty($_GPC['starttime'])) {
		$con .= " and starttime >='{$_GPC['starttime']}' ";
	}
	if (!empty($_GPC['endtime'])) {
		$con .= " and starttime <='{$_GPC['endtime']}' ";
	}
	$groups = pdo_fetchall("select * from" . tablename('tg_group') . "where $con ");

	$html = "\xEF\xBB\xBF";
	$filter = array('ll' => '团编号', 'mm' => '团状态', 'aa' => '订单编号', 'bb' => '姓名', 'cc' => '电话', 'dd' => '总价(元)', 'ee' => '状态', 'ff' => '下单时间', 'gg' => '商品名称', 'hh' => '收货地址', 'ii' => '微信订单号', 'jj' => '快递单号', 'kk' => '快递名称');
	foreach ($filter as $key => $title) {
		$html .= $title . "\t,";
	}
	//					$html .= "\n";
	foreach ($groups as $k => $v) {
		$html .= "\n";
		$orders = pdo_fetchall("select * from" . tablename('tg_order') . "where tuan_id='{$v['groupnumber']}' and uniacid='{$_W['uniacid']}'");
		if ($v['groupstatus'] == 1) {
			$tuanstatus = '团购失败';
		}
		if ($v['groupstatus'] == 2) {
			$tuanstatus = '团购成功';
		}
		if ($v['groupstatus'] == 3) {
			$tuanstatus = '组团中';
		}
		foreach ($orders as $kk => $vv) {
			if ($vv['status'] == 0) {
				$thistatus = '待付款';
			}
			if ($vv['status'] == 1) {
				$thistatus = '已支付';
			}
			if ($vv['status'] == 2) {
				$thistatus = '待发货';
			}
			if ($vv['status'] == 3) {
				$thistatus = '已发货';
			}
			if ($vv['status'] == 4) {
				$thistatus = '已签收';
			}
			if ($vv['status'] == 5) {
				$thistatus = '已取消';
			}
			if ($vv['status'] == 6) {
				$thistatus = '待退款';
			}
			if ($vv['status'] == 7) {
				$thistatus = '已退款';
			}
			$goods = pdo_fetch("select * from" . tablename('tg_goods') . "where id = '{$vv['g_id']}' and uniacid='{$_W['uniacid']}'");
			$time = date('Y-m-d H:i:s', $vv['createtime']);
			$orders[$kk]['ll'] = $v['groupnumber'];
			$orders[$kk]['mm'] = $tuanstatus;
			$orders[$kk]['aa'] = $vv['orderno'];
			$orders[$kk]['bb'] = $vv['addname'];
			$orders[$kk]['cc'] = $vv['mobile'];
			$orders[$kk]['dd'] = $vv['price'];
			$orders[$kk]['ee'] = $thistatus;
			$orders[$kk]['ff'] = $time;
			$orders[$kk]['gg'] = $goods['gname'];
			$orders[$kk]['hh'] = $vv['address'];
			$orders[$kk]['ii'] = $vv['transid'];
			$orders[$kk]['jj'] = $vv['expresssn'];
			$orders[$kk]['kk'] = $vv['express'];
			foreach ($filter as $key => $title) {
				$html .= $orders[$kk][$key] . "\t,";
			}
			$html .= "\n";
		}

	}
	/* 输出CSV文件 */
	header("Content-type:text/csv");
	header("Content-Disposition:attachment; filename={$str}.csv");
	echo $html;
	exit();
}
include $this -> template('web/grouporder');
?>