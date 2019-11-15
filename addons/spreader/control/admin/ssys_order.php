<?php
/**
 * 订单管理
 *
 */
defined('DYMall') or exit('Access Invalid!');
class ssys_orderCtl extends SystemCtl{
	
	public function __construct(){
		parent::__construct();
	}


    /**
     * ssys 所有订单列表
     */
    public function getAllOrder(){
        $model_order = M('ssys_order');
        $condition  = array();
        $search = array();
        //分页检索条件(如果传值 按照传的值 否则默认10页)
        $pageSize = intval($_GET['pageSize'] > 0 ? $_GET['pageSize'] : 10);
        $_GET['pn'] = intval($_GET['currentPage'] > 0 ? $_GET['currentPage'] : 1);
        if($_GET['order_sn']) {
            $search['order_sn'] = $_GET['order_sn'];
            $condition['order_sn'] = $_GET['order_sn'];
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

        $order_list_array = $model_order->getSpreaderOrderInfos($condition, '*', $pageSize);
		$order_sn_list = low_array_column($order_list_array, 'order_sn');

		$rebuild_order_list_array = $this->get_order_list_by_api($order_sn_list);
		$rebuild_order_list_array = low_array_column($rebuild_order_list_array,NULL, 'order_id');

		foreach ($order_list_array as $key => $value) {
			$item_order_info = array();

			$item_order_info = $value;
			$rebuild_order_item_info = $rebuild_order_list_array[$value['order_id']];
			$item_order_info['state_desc'] = $rebuild_order_item_info['state_desc'];
			$item_order_info['order_state'] = $rebuild_order_item_info['order_state'];
			$item_order_info['order_amount'] = $rebuild_order_item_info['order_amount'];
			switch ($item_order_info['yj_status']) {
				case '-1':
					$item_order_info['yj_status_desc'] = '失效';
					break;
				case '0':
					$item_order_info['yj_status_desc'] = '冻结';
					break;
				case '1':
					$item_order_info['yj_status_desc'] = '已结算';
					break;
			}
			$item_order_info['add_time_str'] = date('Y-m-d H:i:s',$item_order_info['add_time']);
			$spreader_member_info = M('ssys_member')->getMemberInfoByID($item_order_info['member_id'],'member_name');
			$item_order_info['member_name'] = $spreader_member_info['member_name'];

			$order_list[] = $item_order_info;
		}
        
        $page_count = $model_order->gettotalpage();

        echo json_encode(array('list' => $order_list, 'pagination' => array('current' => $_GET['pn'], 'pageSize' => $pageSize, 'total' => intval($model_order->gettotalnum())),'searchlist'=>$search));

    }

    // 获取商城系统订单信息
    public function get_order_list_by_api($order_sn_array){
    	$model_order = Model('order');
    	$condition['order_sn'] = array("IN",$order_sn_array);
    	$shop_order_list = $model_order->getOrderList($condition, '', 'order_id,order_state,order_amount', '','',array('order_goods'));
    	
    	return $shop_order_list;
    }

}