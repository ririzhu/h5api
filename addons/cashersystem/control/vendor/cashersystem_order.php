<?php
/**
 * 订单 接口
 *
 */
defined('DYMall') or exit('Access Invalid!');

class cashersystem_orderCtl{

    protected $vendor_info = array();

	public function __construct()
	{
		$this->checkToken();
	}
	
	// 订单 列表
	public function orderList()
	{

        $search = array();
		
        $search['dian_id'] = $dian_id = isset($_GET['dian_id']) ? intval($_GET['dian_id']) : 0;
        $type = isset($_GET['type']) ? intval($_GET['type']) : 1;
        $search['search_val'] = $search_val = isset($_GET['search']) ? trim($_GET['search']) : '';
        $pageSize = intval($_GET['pageSize'] > 0 ? $_GET['pageSize'] : 10);
        $_GET['pn'] = intval($_GET['currentPage'] > 0 ? $_GET['currentPage'] : 1);


        $state = 200;
        $data = '';
        $message = 'success';

        if ($search_val) {
        	$condition['order_sn'] = array("LIKE","%".$search_val."%");
        }
        if ($dian_id) {
            $condition['dian_id'] = $dian_id;
        }
        $condition['vid'] = $this->vendor_info['vid'];

        // 支付方式
        if($_GET['payment_code']) {
            $search['payment_code'] = $_GET['payment_code'];
            $condition['payment_code'] = $_GET['payment_code'];
        }

        $if_start_time = preg_match('/^20\d{2}-\d{2}-\d{2}$/',$_GET['query_start_time']);
        $if_end_time = preg_match('/^20\d{2}-\d{2}-\d{2}$/',$_GET['query_end_time']);
        $start_unixtime = $if_start_time ? strtotime($_GET['query_start_time']) : null;
        $end_unixtime = $if_end_time ? strtotime($_GET['query_end_time']): null;
        if ($start_unixtime || $end_unixtime) {
            $search['query_start_time'] = $_GET['query_start_time'];
            $search['query_end_time'] = $_GET['query_end_time'];
            $condition['add_time'] = array('BETWEEN',array($start_unixtime,$end_unixtime));
        }

        if ($type == 1) {
            // 线下
            // 获取 订单 列表
            $model_order = M('cashsys_order','cashersystem');
            $condition['payment_code'] = array('NOT IN',array('online','offline'));
            $page_list = $model_order->getOrderList($condition,'*',$pageSize);

            // 获取支付方式数据
            $model_payment = M('cashsys_payment','cashersystem');
            $payment_list = $model_payment->getPaymentList();
            $payment_list = low_array_column($payment_list,'payment_name','payment_code');

            if (!empty($page_list)) {
                foreach ($page_list as $p_key => $p_value) {
                    $p_value['add_time_str'] = date('Y-m-d H:i',$p_value['add_time']);
                    $p_value['type'] = '线下';
                    $p_value['payment_name'] = $payment_list[$p_value['payment_code']] ? $payment_list[$p_value['payment_code']] : '';
                    switch ($p_value['order_state']) {
                        case ORDER_STATE_CANCEL:
                            $order_state = L('状态文字：已取消');
                            break;
                        case ORDER_STATE_NEW:
                            $order_state = L('状态文字：待付款');
                            break;
                        case ORDER_STATE_PAY:
                            $order_state = L('状态文字：交易完成');
                            break;
                    }
                    $p_value['buyer_name'] = !empty($p_value['buyer_name']) ? $p_value['buyer_name'] : '游客';
                    $p_value['order_state_name'] = strip_tags($order_state);
                    $page_list[$p_key] = $p_value;
                }
            }else{
                $state = 255;
                $data = '';
                $message = Language::get('没有数据');
            }
        }else if($type == 2){
            // 线上
            $model_order = Model('order');
            $page_list = $model_order->getOrderList($condition,'*',$pageSize);

            if (!empty($page_list)) {
                // 获取支付方式数据
                $model_payment = Model('payment');
                $payment_list = $model_payment->getPaymentList();
                $payment_list = low_array_column($payment_list,'payment_name','payment_code');

                foreach ($page_list as $p_key => $p_value) {
                    $p_value['add_time_str'] = date('Y-m-d H:i',$p_value['add_time']);
                    $p_value['type'] = '核销';
                    $p_value['payment_name'] = $payment_list[$p_value['payment_code']] ? $payment_list[$p_value['payment_code']] : '在线支付';
                    $p_value['order_state_name'] = strip_tags(orderState($p_value));
                    $page_list[$p_key] = $p_value;
                }
            }else{
                $state = 255;
                $data = '';
                $message = Language::get('没有数据');
            }
        }

        if (!empty($page_list)) {
            $model_dian = Model('dian');

            // 获取门店名称
            foreach ($page_list as $key => $value) {
                if ($value['dian_id']) {
                    $dian_info = $model_dian->getDianInfoByID($value['vid'],$value['dian_id']);
                    if (!empty($dian_info)) {
                        $value['dian_name'] = $dian_info['dian_name'];
                    }
                }
                $page_list[$key] = $value;
            }

            $data = array(
                    'list' => $page_list,
                    'pagination' => array(
                            'current' => $_GET['pn'],
                            'pageSize' => $pageSize,
                            'total' => intval($model_order->gettotalnum()),
                        ),
                    'searchlist' => $search
                );
        }else{
            $state = 255;
            $data = '';
            $message = Language::get('没有数据');
        }

        $return_last = array(
        		'state' => $state,
        		'data' => $data,
        		'msg' => $message,
        	);

        echo json_encode($return_last);

	}

	// 获取 订单 详情
	public function getOrderInfo()
	{

        $order_id = $_GET['order_id'];
		$type = $_GET['type'] ? $_GET['type'] : 1;

        $state = 200;
        $data = '';
        $message = 'success';

		// 删除数据
    	
    	$condition['order_id'] = $order_id;

        if ($type == 1) {
            // 获取支付方式数据
            $model_payment = M('cashsys_payment','cashersystem');
            $payment_list = $model_payment->getPaymentList();
            $payment_list = low_array_column($payment_list,'payment_name','payment_code');

            $model_order = M('cashsys_order','cashersystem');
            $order_detail = $model_order->getOrderInfo($condition,array('order_goods'));

            if (!empty($order_detail)) {

                // 获取门店名称
                if ($order_detail['dian_id']) {
                    $dian_info = Model('dian')->getDianInfoByID($order_detail['vid'],$order_detail['dian_id']);
                    if (!empty($dian_info)) {
                        $order_detail['dian_name'] = $dian_info['dian_name'];
                    }
                }
                
                $order_detail['add_time_str'] = date('Y-m-d H:i',$order_detail['add_time']);
                $order_detail['payment_time_str'] = $order_detail['payment_time'] ? date('Y-m-d H:i',$order_detail['payment_time']) : '';
                $order_detail['finnshed_time_str'] = $order_detail['finnshed_time'] ? date('Y-m-d H:i',$order_detail['finnshed_time']) : '';
                $order_detail['type'] = '线下';
                $order_detail['payment_name'] = $payment_list[$order_detail['payment_code']] ? $payment_list[$order_detail['payment_code']] : '';
                switch ($order_detail['order_state']) {
                    case ORDER_STATE_CANCEL:
                        $order_state = L('状态文字：已取消');
                        break;
                    case ORDER_STATE_NEW:
                        $order_state = L('状态文字：待付款');
                        break;
                    case ORDER_STATE_PAY:
                        $order_state = L('状态文字：交易完成');
                        break;
                }
                $order_detail['order_state_name'] = strip_tags($order_state);
            }else{
                $state = 255;
                $data = '';
                $message = Language::get('没有数据');
            }
        }else if($type == 2){
            // 获取支付方式数据
            $model_payment = Model('payment');
            $payment_list = $model_payment->getPaymentList();
            $payment_list = low_array_column($payment_list,'payment_name','payment_code');

            $model_order = Model('order');
            $order_detail = $model_order->getOrderInfo($condition,array('order_goods'));

            if (!empty($order_detail)) {

                // 获取门店名称
                if ($order_detail['dian_id']) {
                    $dian_info = Model('dian')->getDianInfoByID($order_detail['vid'],$order_detail['dian_id']);
                    if (!empty($dian_info)) {
                        $order_detail['dian_name'] = $dian_info['dian_name'];
                    }
                }
                
                $order_detail['add_time_str'] = date('Y-m-d H:i',$order_detail['add_time']);
                $order_detail['payment_time_str'] = $order_detail['payment_time'] ? date('Y-m-d H:i',$order_detail['payment_time']) : '';
                $order_detail['finnshed_time_str'] = $order_detail['finnshed_time'] ? date('Y-m-d H:i',$order_detail['finnshed_time']) : '';
                $order_detail['type'] = '核销';
                $order_detail['payment_name'] = $payment_list[$order_detail['payment_code']] ? $payment_list[$order_detail['payment_code']] : '在线支付';
                $order_detail['order_state_name'] = strip_tags(orderState($order_detail));
            }else{
                $state = 255;
                $data = '';
                $message = Language::get('没有数据');
            }
        }

        $data = $order_detail;

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

        $model_bwap_vendor_token = Model('bwap_vendor_token');
        $bwap_vendor_token_info = $model_bwap_vendor_token->getSellerTokenInfoByToken($token);
        if (empty($bwap_vendor_token_info)) {
        	$check_flag = false;
        }

        $model_vendor = Model('vendor');
        $seller_info = model()->table('seller')->where(['seller_id'=>$bwap_vendor_token_info['seller_id']])->find();
        $this->vendor_info = $model_vendor->getStoreInfo(array('vid'=>$seller_info['vid']));
        if(empty($this->vendor_info)) {
        	$check_flag = false;
        } else {
            $this->vendor_info['token'] = $bwap_vendor_token_info['token'];
        }

		if (!$check_flag) {
			$state = 275;
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