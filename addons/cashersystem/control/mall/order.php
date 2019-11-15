<?php
/**
 * 订单 接口
 *
 */
defined('DYMall') or exit('Access Invalid!');

class orderCtl{

	protected $casher_info = array();

	public function __construct()
	{
		// 验证token 是否有效
		$this->checkToken();
	}

	// 收银操作（创建线下订单）
	public function confirm()
	{
        $input['dian_id'] = $dian_id = $this->casher_info['dian_id'];
        $input['dian_name'] = $dian_name = $this->casher_info['dian_name'];
        $input['vid'] = $vid = $this->casher_info['vid'];
        $buyer_id = $_POST['buyer_id'] ? $_POST['buyer_id'] : 0;
    	$buyer_name = '';
        $input['goods_buy_quantity'] = $goods_buy_quantity = $_POST['goods_buy_quantity'] ? $_POST['goods_buy_quantity'] :'';
        $input['payment_code'] = $payment_code = 'offline';//$_POST['payment_code'] ? $_POST['payment_code'] :'';

        $state = 200;
        $data = '';
        $message = 'success';

        $run_flag = true;

        $goods_buy_quantity_arr = explode(',', $goods_buy_quantity);
        foreach ($goods_buy_quantity_arr as $g_key => $g_value) {
            $item = explode('_', $g_value);
            if (!empty($item)) {
                $goods_buy_quantity_new[$g_key]['gid'] = $item[0];
                $goods_buy_quantity_new[$g_key]['num'] = $item[1];
            }
        }
        $input['goods_buy_quantity'] = $goods_buy_quantity = $goods_buy_quantity_new;

        if (empty($goods_buy_quantity)) {
            $run_flag = false;
        	$state = 255;
            $message = '参数错误';
        }

        if ($run_flag) {

            $model_order = M('cashsys_order','cashersystem');

            // 获取 是否有会员信息
            if ($buyer_id) {
                // 获取购买者用户信息
                $model_member = M('cashsys_member','cashersystem');
                $member_condition['member.member_id'] = $buyer_id;
                // $member_condition['cashsys_member_common.dian_id'] = $dian_id;
                $member_info = $model_member->getMemberInfo($member_condition,'member_name');
                $input['buyer_name'] = $buyer_name = $member_info['member_name'];
            }

            $gids = low_array_column($goods_buy_quantity,'gid');
            $gid_nums = low_array_column($goods_buy_quantity,'num','gid');
            // 获取商品价格
            $goods_condition['gid'] = array("IN",$gids);
            $goods_list = M('cashsys_goods')->getGoodsList($goods_condition,'*',0,'goods.gid');
            // 获取最终价格
            $goods_list = Model('goods_activity')->rebuild_goods_data($goods_list);

            // 计算 商品需要支付的价格
            foreach ($goods_list as $key => $value) {
                $num = $gid_nums[$value['gid']];
                $value['goods_num'] = $num;
                $store_final_order_total += $value['show_price'] * $num;
                $goods_list[$key] = $value;
            }
            $input['store_final_order_total'] = $store_final_order_total;

            $input['goods_list'] = $goods_list;

            //计算店铺分类佣金
            $input['store_gc_id_commis_rate_list'] = $store_gc_id_commis_rate_list = $this->getStoreGcidCommisRateList($goods_list);

            try {

                //开始事务
                $model_order->beginTransaction();

                //生成订单
                list($pay_sn,$order_list) = $this->createOrder($input, $buyer_id, $buyer_name);

                //记录订单日志
                $this->addOrderLog($order_list);

                //变更库存和销量
                $this->updateGoodsStorageNum($goods_buy_quantity,$input['dian_id']);

                //提交事务
                $model_order->commit();
                $state = 200;
                $data['store_final_order_total'] = $input['store_final_order_total'];
                $data['order_list'] = array_shift($order_list);
                $message = '创建订单成功';

            }catch (Exception $e){
                //回滚事务
                $model_order->rollback();
                $state = 255;
                $message = $e->getMessage();
            }
        }


        $return_last = array(
        		'state' => $state,
        		'data' => $data,
        		'msg' => $message,
        	);

        echo json_encode($return_last);

	}
	/**
     * 生成订单
     * @param array $input
     * @throws Exception
     * @return array array(支付单sn,订单列表)
     */
    public function createOrder($input, $member_id, $member_name) {
        extract($input);

        $model_order = M('cashsys_order','cashersystem');
        //存储生成的订单,函数会返回该数组
        $order_list = array();

        $pay_sn = $this->makePaySn($member_id);
        $order_pay = array();
        $order_pay['pay_sn'] = $pay_sn;
        $order_pay['buyer_id'] = $member_id;
        $order_pay_id = $model_order->addOrderPay($order_pay);
        if (!$order_pay_id) {
            throw new Exception('订单保存失败');
        }

        $order = array();
        $order_common = array();
        $order_goods = array();
        $order['order_sn'] = $this->makeOrderSn($order_pay_id);
        $order['pay_sn'] = $pay_sn;
        $order['vid'] = $vid;
        $order['dian_id'] = $dian_id;
        $order['dian_name'] = $dian_name;
        $order['buyer_id'] = $member_id;
        $order['buyer_name'] = $member_name;
        $order['casher_id'] = $this->casher_info['id'];
        $order['casher_name'] = $this->casher_info['casher_name'];
        $order['add_time'] = TIMESTAMP;
        $order['payment_code'] = $payment_code;
        $order['order_state'] = ORDER_STATE_NEW;
        $order['order_amount'] = $store_final_order_total;
        $order['goods_amount'] = $order['order_amount'];

        //如果支付金额为零，直接变成已支付
        if($order['order_amount']==0){
            $order['order_state'] = ORDER_STATE_PAY;
        }

        $order_id = $model_order->addOrder($order);

        if (!$order_id) {
            throw new Exception('订单保存失败');
        }

        $order['order_id'] = $order_id;

        $order_list[$order_id] = $order;

        //生成order_goods订单商品数据
        $i = 0;
        foreach ($goods_list as $goods_info) {
            //根据goods_commonid获取goods_storage_alarm   goods_common表就可以
            // 提醒[库存报警]  库存预警值必须大于0  为0的话不报警
            if ($goods_info['delete']==1 || $goods_info['stock'] <1 ) {
                throw new Exception('部分商品已经下架或库存不足，请重新选择');
            }
            if (10 >= ($goods_info['goods_storage'] - $goods_info['goods_num']) || ($goods_info['stock'] - $goods_info['goods_num']) < 0){
                // 门店库存不足
                throw new Exception('库存不足，请重新选择');
            }
            
            //如果不是优惠套装
            $order_goods[$i]['order_id'] = $order_id;
            $order_goods[$i]['gid'] = $goods_info['gid'];
            $order_goods[$i]['vid'] = $vid;
            $order_goods[$i]['dian_id'] = $dian_id;
            $order_goods[$i]['goods_name'] = $goods_info['goods_name'];
            $order_goods[$i]['goods_price'] = $goods_info['show_price'];
            $order_goods[$i]['goods_num'] = $goods_info['goods_num'];
            $order_goods[$i]['goods_image'] = $goods_info['goods_image'];
            $order_goods[$i]['buyer_id'] = $member_id;
            $order_goods[$i]['commis_rate'] = floatval($store_gc_id_commis_rate_list[$vid][$goods_info['gc_id']]);

            $order_goods[$i]['promotions_id'] = $goods_info['promotions_id'] ? $goods_info['promotions_id'] : 0;
            //计算商品金额
            $goods_total = $goods_info['show_price'] * $goods_info['goods_num'];
            //计算本件商品优惠金额(线下订单 无优惠)
            $promotion_value = floor($goods_total*0);
            $order_goods[$i]['goods_pay_price'] = $goods_total - $promotion_value;
            $i++;
        }

        $insert = $model_order->addOrderGoods($order_goods);
        if (!$insert) {
            throw new Exception('订单保存失败');
        }

        return array($pay_sn,$order_list);
    }

    //收款增加积分方法
    private function addPints($order_info,$pay_amount){
        //确认收货时添加会员积分
        if (C('points_isuse') == 1){
            $points_model = Model('points');
            $points_model->savePointsLog('order',array('pl_desc'=>'门店收银'+'消费','pl_memberid'=>$order_info['buyer_id'],'pl_membername'=>$order_info['buyer_name'],'orderprice'=>$pay_amount,'order_sn'=>$order_info['order_sn'],'order_id'=>$order_info['order_id']),true);
        }
    }

    // 收款操作
    public function shouKuan()
    {
        $payment_code = $_POST['payment_code'] ? $_POST['payment_code'] :'';
        $payment_money = $_POST['payment_money'] ? $_POST['payment_money'] : 0;
        $order_sn = $_POST['order_sn'] ? $_POST['order_sn'] :'';
        $auth_code = $_POST['auth_code'] ? $_POST['auth_code'] :'';

        $run_flag = true;

        $state = 200;
        $data = '';
        $message = 'success';

        // 获取支付方式数据
        $model_payment = M('cashsys_payment','cashersystem');
        $payment_list = $model_payment->getPaymentList(array('payment_state'=>1));
        $payment_list = low_array_column($payment_list,NULL,'payment_code');

        if (empty($payment_code) || empty($order_sn)) {
            $state = 255;
            $message = '参数错误';
            $run_flag = false;
        }

        if ($run_flag) {
            $model_order = M('cashsys_order','cashersystem');

            $order_condition['order_sn'] = $order_sn;
            $order_info = $model_order->getOrderInfo($order_condition);
            $pay_sn = $order_info['pay_sn'];
            if ($order_info['order_state'] != 10) {
                $state = 255;
                $message = '该订单不能进行收款操作';
                $run_flag = false;
            }
            if ($run_flag) {
                // 获取订单的支付方式 
                switch ($payment_code) {
                    case 'cash':
                        // 现金支付
                        try {
                            //记录订单日志(已付款)
                            $log_data = array();
                            $log_data['order_id'] = $order_info['order_id'];
                            $log_data['log_role'] = 'dian';
                            $log_data['log_user'] = $this->casher_info['id']; // 收银员ID
                            $log_data['log_msg'] = '已完成';
                            $log_data['log_orderstate'] = ORDER_STATE_SUCCESS;
                            $insert = $model_order->addOrderLog($log_data);
                            if (!$insert) {
                                throw new Exception('记录订单日志出现错误');
                            }
                            // 更新订单状态及实际支付金额
                            //订单状态 置为已支付
                            $update_order = array();
                            $update_order['order_state'] = ORDER_STATE_SUCCESS;
                            $update_order['order_amount'] = floatval($payment_money);
                            $update_order['payment_code'] = $payment_code;
                            $update_order['payment_time'] = TIMESTAMP;
                            $update_order['finnshed_time'] = TIMESTAMP;
                            $result = $model_order->editOrder($update_order,array('pay_sn'=>$pay_sn));

                            if (!$result) {
                                throw new Exception('订单更新失败');
                            }else{
                                $state = 200;
                                $message = '支付成功';
                            }
                        }catch (Exception $e){
                            //回滚事务
                            $model_order->rollback();
                            $state = 255;
                            $message = $e->getMessage();
                        }
                        break;
                    case 'alipay':
                        // 支付宝支付
                        // 校验用户授权码 是否存在
                        if (empty($auth_code)) {
                            $run_flag = false;
                            $state = 255;
                            $message = '未获取授权码';
                        }
                        // 获取配置
                        $payment_config = unserialize($payment_list['alipay']['payment_config']);
                        if (
                            (
                                empty($payment_config) || 
                                empty($payment_config['alipay_public_key']) || 
                                empty($payment_config['merchant_private_key']) || 
                                empty($payment_config['app_id'])
                            ) && $run_flag
                        ) {

                            $run_flag = false;
                            $state = 255;
                            $message = '当前支付未配置';
                        }
                        if ($run_flag) {
                            // 请求 支付宝支付
                            define('PAYMENT_ROOT', BASE_LIBRARY_PATH . DS .'api/payment');
                            require_once(PAYMENT_ROOT . DS . 'alipay' . DS . 'index.php');
                            // 可传入配置
                            $pay_config['alipay_public_key'] = $payment_config['alipay_public_key'];
                            $pay_config['merchant_private_key'] = $payment_config['merchant_private_key'];
                            $pay_config['app_id'] = $payment_config['app_id'];
                            $pay_Obj = new alipay($pay_config);
                            // 刷卡支付
                            $pay_data['auth_code'] = $auth_code;
                            $pay_data['body'] = "门店收银订单-".$pay_sn;
                            $pay_data['pay_sn'] = $pay_sn;
                            $pay_data['fee'] = floatval($payment_money);
                            $pay_return = $pay_Obj->micropay($pay_data);

                            if ($pay_return) {
                                // 发起支付 等待
                                // 存储 第三方日志
                                if ($pay_return->code == '10000') {
                                    $run_flag = true;
                                }else{
                                    $run_flag = false;
                                    $state = 255;
                                    $message = $pay_return->sub_msg;
                                }
                            }else{
                                // 扫码支付出现问题
                                $run_flag = false;
                                $state = 255;
                                $message = '支付出现问题，请重新支付';
                            }
                            if ($run_flag) {
                                // 支付成功
                                // 订单更新
                                try {
                                    //记录订单日志(已付款)
                                    $log_data = array();
                                    $log_data['order_id'] = $order_info['order_id'];
                                    $log_data['log_role'] = 'dian';
                                    $log_data['log_user'] = $this->casher_info['id']; // 收银员ID
                                    $log_data['log_msg'] = '已完成';
                                    $log_data['log_orderstate'] = ORDER_STATE_SUCCESS;
                                    $insert = $model_order->addOrderLog($log_data);
                                    if (!$insert) {
                                        throw new Exception('记录订单日志出现错误');
                                    }
                                    // 更新订单状态及实际支付金额
                                    //订单状态 置为已支付
                                    $update_order = array();
                                    $update_order['order_state'] = ORDER_STATE_SUCCESS;
                                    $update_order['order_amount'] = floatval($payment_money);
                                    $update_order['payment_time'] = TIMESTAMP;
                                    $update_order['payment_code'] = $payment_code;
                                    $update_order['finnshed_time'] = TIMESTAMP;
                                    $result = $model_order->editOrder($update_order,array('pay_sn'=>$pay_sn));

                                    if (!$result) {
                                        throw new Exception('订单更新失败');
                                    }else{
                                        $state = 200;
                                        $message = '支付成功';
                                    }
                                }catch (Exception $e){
                                    //回滚事务
                                    $model_order->rollback();
                                    $state = 255;
                                    $message = $e->getMessage();
                                }
                            }
                        }
                        break;
                    case 'wxpay':
                        // 微信支付
                        // 校验用户授权码 是否存在
                        if (empty($auth_code)) {
                            $run_flag = false;
                            $state = 255;
                            $message = '未获取授权码';
                        }
                        // 获取配置
                        $payment_config = unserialize($payment_list['wxpay']['payment_config']);
                        if (
                            (
                                empty($payment_config) || 
                                empty($payment_config['appId']) || 
                                empty($payment_config['partnerId']) || 
                                empty($payment_config['apiKey']) || 
                                empty($payment_config['appSecret'])
                            ) && $run_flag
                        ) {

                            $run_flag = false;
                            $state = 255;
                            $message = '当前支付未配置';
                        }
                        if ($run_flag) {
                            // 请求 微信支付
                            define('PAYMENT_ROOT', BASE_LIBRARY_PATH . DS .'api/payment');
                            require_once(PAYMENT_ROOT . DS . 'wxpay' . DS . 'index.php');
                            // 可传入配置
                            $pay_config['appId'] = $payment_config['appId'];
                            $pay_config['appMid'] = $payment_config['partnerId'];
                            $pay_config['appKey'] = $payment_config['apiKey'];
                            $pay_config['appSecret'] = $payment_config['appSecret'];
                            $wxpay_Obj = new wxpay($pay_config);
                            // 刷卡支付
                            $pay_data['auth_code'] = $auth_code;
                            $pay_data['body'] = "门店收银订单-".$pay_sn;
                            $pay_data['pay_sn'] = $pay_sn;
                            $pay_data['fee'] = floatval($payment_money) * 100;
                            $pay_return = $wxpay_Obj->micropay($pay_data);
                            if ($pay_return) {
                                // 发起支付 等待
                                // 存储 第三方日志
                                $run_flag = true;
                            }else{
                                // 扫码支付出现问题
                                $run_flag = false;
                                $state = 255;
                                $message = '支付出现问题，请重新支付';
                            }

                            if ($run_flag) {
                                // 订单更新
                                try {
                                    //记录订单日志(已付款)
                                    $log_data = array();
                                    $log_data['order_id'] = $order_info['order_id'];
                                    $log_data['log_role'] = 'dian';
                                    $log_data['log_user'] = $this->casher_info['id']; // 收银员ID
                                    $log_data['log_msg'] = '已完成';
                                    $log_data['log_orderstate'] = ORDER_STATE_SUCCESS;
                                    $insert = $model_order->addOrderLog($log_data);
                                    if (!$insert) {
                                        throw new Exception('记录订单日志出现错误');
                                    }
                                    // 更新订单状态及实际支付金额
                                    //订单状态 置为已支付
                                    $update_order = array();
                                    $update_order['order_state'] = ORDER_STATE_SUCCESS;
                                    $update_order['order_amount'] = floatval($payment_money);
                                    $update_order['payment_time'] = TIMESTAMP;
                                    $update_order['payment_code'] = $payment_code;
                                    $update_order['finnshed_time'] = TIMESTAMP;
                                    $result = $model_order->editOrder($update_order,array('pay_sn'=>$pay_sn));

                                    if (!$result) {
                                        throw new Exception('订单更新失败');
                                    }else{
                                        $state = 200;
                                        $message = '支付成功';
                                    }
                                }catch (Exception $e){
                                    //回滚事务
                                    $model_order->rollback();
                                    $state = 255;
                                    $message = $e->getMessage();
                                }
                            }
                        }
                        break;
                    case 'bank':
                        // 银行支付
                        $state = 255;
                        $message = '开发中';
                        break;
                    case 'predeposit':
                        // 商城余额支付
                        try {
                            $member_id = $order_info['buyer_id'];
                            $buyer_info = Model('member')->infoMember(array('member_id' => $member_id));
                            $available_pd_amount = floatval($buyer_info['available_predeposit']);
                            if ($available_pd_amount <= 0) {
                                throw new Exception('用户余额不足');
                            };

                            $model_pd = Model('predeposit');
                            $order_amount = floatval($payment_money);

                            $data_pd = array();
                            $data_pd['member_id'] = $member_id;
                            $data_pd['member_name'] = $buyer_info['member_name'];
                            $data_pd['amount'] = $order_amount;
                            $data_pd['order_sn'] = $order_info['order_sn'];
                            if ($available_pd_amount >= $order_amount) {
                                //预存款立即支付，订单支付完成
                                $model_pd->changePd('order_pay',$data_pd);

                                //记录订单日志(已付款)
                                $log_data = array();
                                $log_data['order_id'] = $order_info['order_id'];
                                $log_data['log_role'] = 'dian';
                                $log_data['log_msg'] = '使用商城余额支付';
                                $log_data['log_orderstate'] = ORDER_STATE_SUCCESS;
                                $insert = $model_order->addOrderLog($log_data);
                                if (!$insert) {
                                    throw new Exception('记录订单日志出现错误');
                                }

                                //订单状态 置为已支付
                                $update_order = array();
                                $update_order['order_state'] = ORDER_STATE_SUCCESS;
                                $update_order['order_amount'] = floatval($payment_money);
                                $update_order['payment_code'] = $payment_code;
                                $update_order['payment_time'] = TIMESTAMP;
                                $update_order['finnshed_time'] = TIMESTAMP;
                                $result = $model_order->editOrder($update_order,array('pay_sn'=>$pay_sn));
                                if (!$result) {
                                    throw new Exception('订单更新失败');
                                }

                            } else {
                                throw new Exception('用户余额不足');
                            }
                        }catch (Exception $e){
                            //回滚事务
                            $model_order->rollback();
                            $state = 255;
                            $message = $e->getMessage();
                        }
                        break;
                }
            }
            if($state == 200){
                $this->addPints($order_info,floatval($payment_money));
            }
        }
        $return_last = array(
                'state' => $state,
                'data' => $data,
                'msg' => $message,
            );
        echo json_encode($return_last);

    }

    /**
     * 店铺购买列表 王强标记更新库存（减库存）
     * @param array $goods_buy_quantity 商品ID与购买数量数组
     * @param  int  $dian_id   门店的id  建对应门店的库存
     * @param  boolean  $is_cancel   是否是取消订单操作 （反向操作）
     * @throws Exception
     */
    public function updateGoodsStorageNum($goods_buy_quantity,$dian_id,$is_cancel=false) {
        if (empty($goods_buy_quantity) || !is_array($goods_buy_quantity)) return;
        if($dian_id){
            $model_goods = M('cashsys_goods','cashersystem');
            foreach ($goods_buy_quantity as $key => $value) {
            	$gid = $value['gid'];
            	$quantity = $value['num'];
                $data = array();
                if ($is_cancel) {
                    $data['stock'] = array('exp', 'stock+' . $quantity);
                }else{
                	$data['stock'] = array('exp', 'stock-' . $quantity);
                }
                $result = $model_goods->editGoods($data, array('goods_id' => $gid,'dian_id'=>$dian_id));
                if (!$result) throw new Exception('更新库存失败');
            }
        }
    }

    /**
     * 生成支付单编号(两位随机 + 从2000-01-01 00:00:00 到现在的秒数+微秒+会员ID%1000)，该值会传给第三方支付接口
     * 长度 =2位 + 10位 + 3位 + 3位  = 18位
     * 1000个会员同一微秒提订单，重复机率为1/100
     * @return string
     */
    public function makePaySn($member_id) {
        return mt_rand(10,99)
              . sprintf('%010d',time() - 946656000)
              . sprintf('%03d', (float) microtime() * 1000)
              . sprintf('%03d', (int) $member_id % 1000);
    }

    /**
     * 订单编号生成规则，n(n>=1)个订单表对应一个支付表，
     * 生成订单编号(年取1位 + $pay_id取13位 + 第N个子订单取2位)
     * 1000个会员同一微秒提订单，重复机率为1/100
     * @param $pay_id 支付表自增ID
     * @return string
     */
    public function makeOrderSn($pay_id) {
        //记录生成子订单的个数，如果生成多个子订单，该值会累加
        static $num;
        if (empty($num)) {
            $num = 1;
        } else {
            $num ++;
        }
        return (date('y',time()) % 9+1) . sprintf('%013d', $pay_id) . sprintf('%02d', $num);
    }

    /**
     * 记录订单日志
     * @param array $order_list
     */
    public function addOrderLog($order_list = array()) {
        if (empty($order_list) || !is_array($order_list)) return;
        $model_order = M('cashsys_order','cashersystem');
        foreach ($order_list as $order_id => $order) {
            $data = array();
            $data['order_id'] = $order_id;
            $data['log_role'] = 'dian';
            $data['log_msg'] = '提交了订单';
            $data['log_user'] = $this->casher_info['id'];
            $data['log_orderstate'] = $order['payment_code'] == 'offline' ? ORDER_STATE_PAY : ORDER_STATE_NEW;
            $model_order->addOrderLog($data);
        }
    }

    // 核销单查询 (根据核销码获取线上订单信息)
    public function getOrderDetailOnline()
    {
        // 核销码
        $chain_code = trim($_GET['chain_code']);

        $run_flag = true;

        $state = 200;
        $data = '';
        $message = 'success';

        if(empty($chain_code) || !is_numeric($chain_code)||strlen($chain_code)!=16){
            $state = 255;
            $message = '参数错误';
            $run_flag = false;
        }

        if ($run_flag) {
            $model_order = Model('order');
            // 获取订单号
            $order_sn = $model_order->decode($chain_code,$this->casher_info['vid'].$this->casher_info['dian_id']);

            $condition['ziti'] = 1;
            $condition['dian_id'] = $this->casher_info['dian_id'];
            $condition['order_state'] = array('gt',2);
            $condition['order_sn'] = $order_sn;
            $order_data = $model_order->getOrderInfo($condition,array('order_goods','order_common','member'));
            if(!$order_data){
                $state = 255;
                $message = '核销码不正确';
                $run_flag = false;
            }else{

                // 获取支付方式数据
                $model_payment = Model('payment');
                $payment_list = $model_payment->getPaymentList();
                $payment_list = low_array_column($payment_list,'payment_name','payment_code');

                $order_data['order_state_name'] = strip_tags(orderState($order_data));
                $order_data['payment_name'] = $payment_list[$order_data['payment_code']] ? $payment_list[$order_data['payment_code']] : '';
                $order_data['add_time_str'] = date("Y-m-d H:i:s",$order_data['add_time']);

                if ($order_data['extend_member']) {
                    $member_mobile = $order_data['extend_member']['member_mobile'];
                    unset($order_data['extend_member']);
                    $order_data['extend_member']['member_mobile'] = $member_mobile;
                }

                $state = 200;
                $data = $order_data;
            }
        }

        $return_last = array(
                'state' => $state,
                'data' => $data,
                'msg' => $message,
            );

        echo json_encode($return_last);

    }

    /**
     * 核销操作
     * */
    public function goHexiao(){
        // 核销码
        $order_sn = trim($_GET['order_sn']);

        $run_flag = true;

        $state = 200;
        $data = '';
        $message = 'success';

        $model_order = Model('order');
        $condition = array();
        $condition['order_sn'] = $order_sn;
        $condition['vid'] = $this->casher_info['vid'];
        $condition['dian_id'] = $this->casher_info['dian_id'];
        $order_info = $model_order->getOrderInfo($condition);
        $if_allow = (!$order_info['lock_state'] && $order_info['dian_id'] = $this->casher_info['dian_id'] && $order_info['order_state'] == ORDER_STATE_SEND);
        if (!$if_allow) {
            $state = 255;
            $message = '该订单已核销';
            $run_flag = false;
        }

        if ($run_flag) {
            $update_order = array();
            $update_order['finnshed_time'] = TIMESTAMP;
            $update_order['order_state'] = ORDER_STATE_SUCCESS;
            $update = $model_order->editOrder($update_order,array('order_id'=>$order_info['order_id']));
            if (!$update) {
                $state = 255;
                Language::get('保存失败');
                $run_flag = false;
            }else{
                //记录订单日志
                $log_data = array();
                $log_data['order_id'] = $order_info['order_id'];
                $log_data['log_role'] = 'dian';
                $log_data['log_user'] = $this->casher_info['casher_name'];
                $log_data['log_msg'] = '核销了订单';
                $model_order->addOrderLog($log_data);
                // 增加操作收银员 记录
                $c_data['order_id'] = $order_info['order_id'];
                $c_data['casher_id'] = $this->casher_info['id'];
                $c_data['add_time'] = time();
                M('cashsys_order','cashersystem')->saveCasherActionData($c_data);
            }
        }

        $return_last = array(
                'state' => $state,
                'data' => $data,
                'msg' => $message,
            );

        echo json_encode($return_last);
    }

    /**
     * 取得店铺下商品分类佣金比例
     * @param array $goods_list
     * @return array 店铺ID=>array(分类ID=>佣金比例)
     */
    public function getStoreGcidCommisRateList($goods_list) {
        if (empty($goods_list) || !is_array($goods_list)) return array();

        //定义返回数组
        $store_gc_id_commis_rate = array();

        //取得每个店铺下有哪些商品分类
        $store_gc_id_list = array();
        foreach ($goods_list as $goods) {
            if (!intval($goods['gc_id'])) continue;
            if (!in_array($goods['gc_id'],(array)$store_gc_id_list[$goods['vid']])) {
                if (in_array($goods['vid'],array(DEFAULT_PLATFORM_STORE_ID))) {
                    //平台店铺佣金为0
                    $store_gc_id_commis_rate[$goods['vid']][$goods['gc_id']] = 0;
                } else {
                    $store_gc_id_list[$goods['vid']][] = $goods['gc_id'];
                }
            }
        }

        if (empty($store_gc_id_list)) return array();

        $model_bind_class = Model('vendor_bind_category');
        $condition = array();
        foreach ($store_gc_id_list as $vid => $gc_id_list) {
            $condition['vid'] = $vid;
            $condition['class_1|class_2|class_3'] = array('in',$gc_id_list);
            $bind_list = $model_bind_class->getStoreBindClassList($condition);
            if (!empty($bind_list) && is_array($bind_list)) {
                foreach ($bind_list as $bind_info) {
                    if ($bind_info['vid'] != $vid) continue;
                    //如果class_1,2,3有一个字段值匹配，就有效
                    $bind_class = array($bind_info['class_3'],$bind_info['class_2'],$bind_info['class_1']);
                    foreach ($gc_id_list as $gc_id) {
                        if (in_array($gc_id,$bind_class)) {
                            $store_gc_id_commis_rate[$vid][$gc_id] = $bind_info['commis_rate'];
                        }
                    }
                }
            }
        }
        return $store_gc_id_commis_rate;

    }

    // 取消订单
    public function cancelOrder()
    {
        $order_sn = trim($_POST['order_sn']) ? trim($_POST['order_sn']) : '';

        $run_flag = true;

        $state = 200;
        $data = '';
        $message = 'success';

        if (empty($order_sn)) {
            $state = 255;
            $message = '无效的订单';
            $run_flag = false;
        }

        // 获取
        if ($run_flag) {
            // 删除订单
            $model_order = M('cashsys_order','cashersystem');
            $condition['order_sn'] = $order_sn;
            $condition['order_state'] = array("IN",array(ORDER_STATE_CANCEL,ORDER_STATE_NEW));
            $return_flag = $model_order->deleteOrder($condition);
            if ($return_flag !== false) {
                $state = 200;
                $message = '操作成功';
            }else{
                $state = 255;
                $message = '操作失败';
            }
        }

        $return_last = array(
                'state' => $state,
                'data' => $data,
                'msg' => $message,
            );

        echo json_encode($return_last);

    }

	// 校验token
	public function checkToken()
	{
		$check_flag = true;
		// 校验token
        $token = $_REQUEST['token'];

        $model_cashsys_token = M('cashsys_token','cashersystem');
        $cashsys_token_info = $model_cashsys_token->getTokenInfoByToken($token);
        if (empty($cashsys_token_info)) {
        	$check_flag = false;
        }

        $model_users = M('cashsys_users');
		$this->casher_info = $model_users->getCashsysUsersInfo(array('id'=>$cashsys_token_info['casher_id']));
        if(empty($this->casher_info)) {
        	$check_flag = false;
        } else {
            $this->casher_info['token'] = $cashsys_token_info['token'];
            // 获取门店名称
            $dian_info = Model('dian')->getDianInfoByID('',$this->casher_info['dian_id']);
            $this->casher_info['dian_name'] = $dian_info['dian_name'];
        }

		if (!$check_flag) {
			$state = 255;
			$data = '';
			$message = Language::get('请登录');
	        $return_last = array(
	        		'state' => $state,
	        		'data' => $data,
	        		'msg' => $message,
	        	);

	        echo json_encode($return_last);exit;
		}
	}

}