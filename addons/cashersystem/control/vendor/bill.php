<?php
/**
 * 结算管理
 *
 *
 *
 *
 */
defined('DYMall') or exit('Access Invalid!');
class cashersystem_billAdd extends BaseSellerCtl{

	public function show_bill($par)
	{
		$_GET = $par['get'];
		$order_condition = $par['order_condition'];
	    //订单列表
	    $model_order = M('cashsys_order','cashersystem');
	    $order_condition['order_state'] = array("IN",array(ORDER_STATE_PAY,ORDER_STATE_SUCCESS));
	    $order_list = $model_order->getOrderList($order_condition,20);

	    foreach ($order_list as $key => $value) {
	    	$value['shipping_fee'] = '0.00';

	    	$order_list[$key] = $value;
	    }
	    
	    //然后取订单商品佣金
	    $order_id_array = array();
	    if (is_array($order_list)) {
	        foreach ($order_list as $order_info) {
	            $order_id_array[] = $order_info['order_id'];
	        }
	    }
	    $order_goods_condition = array();
	    $order_goods_condition['order_id'] = array('in',$order_id_array);
	    $field = 'SUM(goods_pay_price*commis_rate/100) as commis_amount,order_id';
	    $commis_list = $model_order->getOrderGoodsList($order_goods_condition,$field,null,null,'','order_id','order_id');

		$return_data['commis_list'] = $commis_list;
		$return_data['order_list'] = $order_list;
		$return_data['showpage'] = $model_order->showpage();

		return $return_data;
	}

}