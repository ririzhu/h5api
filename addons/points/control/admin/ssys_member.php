<?php
/**
 * 推手管理
 *
 */
defined('DYMall') or exit('Access Invalid!');
class ssys_memberCtl extends SystemCtl{
	
	public function __construct(){
		parent::__construct();
	}


    /**
     * 会员管理
     */
    public function member(){

        $model_member = M('ssys_member');
        //分页检索条件(如果传值 按照传的值 否则默认10页)
        $pageSize = intval($_GET['pageSize']>0?$_GET['pageSize']:10);
        $_GET['pn'] = intval($_GET['currentPage']>0?$_GET['currentPage']:1);
        /**
         * 检索条件
         */
        if ($_GET['search_field_value'] != '') {
            switch ($_GET['search_field_name']){
                case 'member_name':
                    $condition['member_name'] = array('like', '%' . trim($_GET['search_field_value']) . '%');
                    break;
                case 'member_email':
                    $condition['member_email'] = array('like', '%' . trim($_GET['search_field_value']) . '%');
                    break;
                case 'member_truename':
                    $condition['member_truename'] = array('like', '%' . trim($_GET['search_field_value']) . '%');
                    break;
            }
        }
        switch ($_GET['search_state']){
            case 'no_informallow':
                $condition['inform_allow'] = '2';
                break;
            case 'no_isbuy':
                $condition['is_buy'] = '0';
                break;
            case 'no_isallowtalk':
                $condition['is_allowtalk'] = '0';
                break;
            case 'no_memberstate':
                $condition['member_state'] = '0';
                break;
        }
        /**
         * 排序
         */
        $order = trim($_GET['search_sort']);
        if (empty($order)) {
            $order = 'member_id desc';
        }
        $member_list = $model_member->getMemberList($condition, '*',$pageSize, $order);
        $total = $model_member->gettotalnum();
        /**
         * 整理会员信息
         */
        if (is_array($member_list)){
            foreach ($member_list as $k=> $v){
                $member_list[$k]['key'] = $v['member_id'];
                $member_list[$k]['member_avatar'] = $v['member_avatar']?UPLOAD_SITE_URL.DS.ATTACH_AVATAR.DS.$v['member_avatar']:UPLOAD_SITE_URL.'/'.ATTACH_COMMON.DS.C('default_user_portrait');
                $member_list[$k]['member_time'] = $v['member_time']?date('Y-m-d H:i:s',$v['member_time']):'';
                $member_list[$k]['member_login_time'] = $v['member_login_time']?date('Y-m-d H:i:s',$v['member_login_time']):'';
                $member_list[$k]['is_allow_login'] = $v['member_state']==1?'允许':'禁止';
                // 当前推手的上级
                $parent_spreader_info = array();
                $item_condition['shop_member_id'] = $value['shop_member_id'];
                $item_spreader_info = $model_member->get_member_nexus_find($item_condition,'member_id');
                if (isset($item_spreader_info['member_id']) && $item_spreader_info['member_id']) {
                    $parent_spreader_info = $model_member->getMemberInfoByID($item_spreader_info['member_id'],'member_id,member_name');
                }
                if (!empty($parent_spreader_info)) {
                    $member_list[$k]['parent_spreader_member_id'] = $parent_spreader_info['member_id'];
                    $member_list[$k]['parent_spreader_member_name'] = $parent_spreader_info['member_name'];
                }else{
                    $member_list[$k]['parent_spreader_member_id'] = 0;
                    $member_list[$k]['parent_spreader_member_name'] = '';
                }
                // 当前推手下级用户总数
                $count_condition['member_id'] = $v['member_id'];
                $sub_member_count = $model_member->get_member_nexus_count($count_condition);
                $member_list[$k]['child_member_count'] = $sub_member_count;
            }
        }else{
            $member_list = array();
        }
        echo json_encode(array('list'=>$member_list,'pagination'=>array('current'=>$_GET['pn'],'pageSize'=>$pageSize,'total'=>intval($total))));
    }

    // 用户的 提现明细
    public function get_more_info_about_yj()
    {
    	// 明细类型
    	// tx=>提现明细，dj=>冻结明细，sx=>失效明细

    	$allow_detail_states = array('all','tx','dj','sx');

    	$detail_state = $_REQUEST['detail_state'];
    	$member_id = intval($_REQUEST['member_id']);

        //分页检索条件(如果传值 按照传的值 否则默认10页)
        $pageSize = intval($_GET['pageSize']>0?$_GET['pageSize']:10);
        $_GET['pn'] = intval($_GET['currentPage']>0?$_GET['currentPage']:1);

        $state = 255;
        $data = '';
        $message = L('操作失败');


    	if (in_array($detail_state, $allow_detail_states) && $member_id > 0) {
	    	$condition = array();
	    	$cash_types = array();
	    	switch ($detail_state) {
	    		case 'tx':
	    			$cash_types = array('cash_pay','cash_del','cash_apply');
	    			break;
	    		case 'dj':
	    			$cash_types = array('order_pay','order_over','order_cancel','refund','cash_apply','cash_over');
	    			break;
	    		case 'sx':
	    			$cash_types = array('order_cancel','refund');
	    			break;
	    	}
	    	$condition['lg_member_id'] = $member_id;
	    	if (!empty($cash_types)) {
    			$condition['lg_type'] = array("IN",$cash_types);
	    	}
	    	$ssys_yj = M('ssys_yj');

	    	$yj_log_list = $ssys_yj->getPdLogList($condition,$pageSize);

            foreach ($yj_log_list as $key => $value) {
                $value['lg_add_time_str'] = date('Y-m-d H:i:s',$value['lg_add_time']);
                $yj_log_list[$key] = $value;
            }

	    	$data = array(
	    			'list'=>$yj_log_list,
	    			'pagination'=>array(
	    				'current'=>$_GET['pn'],
	    				'pageSize'=>$pageSize,
	    				'total'=>intval($ssys_yj->gettotalnum())
	    			)
	    		);
    		$state = 200;
    		$message = L('操作成功');
    	}else{
    		$message = L('参数错误');
    	}

        $return_last = array(
        		'state' => $state,
        		'data' => $data,
        		'msg' => $message,
        	);

        echo json_encode($return_last);

    }

    // 用户的下级
    public function get_more_info_by_member_id(){
    	$member_id = intval($_REQUEST['member_id']);

        //分页检索条件(如果传值 按照传的值 否则默认10页)
        $pageSize = intval($_GET['pageSize']>0?$_GET['pageSize']:10);
        $_GET['pn'] = intval($_GET['currentPage']>0?$_GET['currentPage']:1);

        $state = 255;
        $data = '';
        $message = L('操作失败');

        if ($member_id > 0) {

	        $last_return = array();

	        $ssys_member = M('ssys_member','spreader');

	        $condition['member_id'] = $member_id;
	        $sub_member_list = $ssys_member->get_member_nexus_select($condition,'*',$pageSize);

	        $page_total = $ssys_member->gettotalnum();

	        // 获取 当前推手的订单信息(冻结金额及已结算金额)
	        $ssys_order = M('ssys_order','spreader');
	        $order_condition['member_id'] =  $member_id;
	        $order_condition['yj_status'] = array("IN",array(0,1));
	        $order_condition['delete_state'] = 0;
	        $spreader_orders = $ssys_order->getSpreaderOrderInfos($order_condition,'order_id,order_sn,gid,member_id,yj_amount');
	        $shop_order_ids = low_array_column($spreader_orders, 'order_id');

	        $spreader_yj_amounts = array();
	        
	        foreach ($spreader_orders as $s_o_k => $s_o_v) {
	            if (isset($spreader_yj_amounts[$s_o_v['order_id']])) {
	                $spreader_yj_amounts[$s_o_v['order_id']] += $s_o_v['yj_amount']*1;
	            }else{
	                $spreader_yj_amounts[$s_o_v['order_id']] = $s_o_v['yj_amount']*1;
	            }
	        }

	        // 去重
	        $shop_order_ids = array_flip($shop_order_ids);
	        $shop_order_ids = array_flip($shop_order_ids);
	        $shop_order_ids = array_values($shop_order_ids);

	        $return_data = array();
	        if (is_array($sub_member_list) && !empty($sub_member_list)) {

	            $shop_member_ids = low_array_column($sub_member_list, 'shop_member_id');

	            // 去重
	            $shop_member_ids = array_flip($shop_member_ids);
	            $shop_member_ids = array_flip($shop_member_ids);
	            $shop_member_ids = array_values($shop_member_ids);

	            // 获取用户信息
	            $model_member = Model('member');
	            $member_condition['member_id'] = array('IN',$shop_member_ids);
	            $shop_member_infos = $model_member->getMemberList($member_condition,'member_id,member_time,member_name');

	            $ready_order_data = array();
	            if (is_array($shop_order_ids) && !empty($shop_order_ids)) {
	                // 根据订单id 获取对应的商城购买者用户
	                $model_order = Model('order');
	                $shop_order_condition['order_id'] = array('IN',$shop_order_ids);
	                $shop_order_condition['buyer_id'] = array('IN',$shop_member_ids);
	                $shop_order_list = $model_order->getOrderList($shop_order_condition,'','order_id,buyer_id');
	                foreach ($shop_order_list as $s_o_l_k => $s_o_l_v) {
	                    if (isset($spreader_yj_amounts[$s_o_l_v['order_id']])) {
	                        $ready_order_data[$s_o_l_v['buyer_id']][$s_o_l_v['order_id']] = $spreader_yj_amounts[$s_o_l_v['order_id']];
	                    }
	                }
	            }

	            foreach ($shop_member_infos as $key => $value) {
	                $member_total_yj_amount = array_sum($ready_order_data[$value['member_id']]);
	                $value['yj_amount'] = $member_total_yj_amount ? $member_total_yj_amount : 0;
	                $value['member_time'] = date('Y-m-d',$value['member_time']);
	                // $value['member_name'] = mb_substr($value['member_name'],0,1,'utf8').'******'.mb_substr($value['member_name'],-1,1,'utf8');
                    // 获取商城用户的推手用户ID
                    $item_condition['shop_member_id'] = $value['member_id'];
                    $item_spreader_info = M('ssys_member')->getMemberInfo($item_condition,'member_id');
                    $value['spreader_member_id'] = $item_spreader_info['member_id'] ? $item_spreader_info['member_id'] : 0;
	                $shop_member_infos[$key] = $value;
	            }

	            if (is_array($shop_member_infos) && !empty($shop_member_infos)) {
	                $return_data = $shop_member_infos;
	            }
	        }

	        $last_return = $return_data;

	    	$data = array(
	    			'list'=>$last_return,
	    			'pagination'=>array(
	    				'current'=>$_GET['pn'],
	    				'pageSize'=>$pageSize,
	    				'total'=>intval($page_total)
	    			)
	    		);

    		$state = 200;
    		$message = L('操作成功');
    	}else{
    		$message = L('参数错误');
    	}

        $return_last = array(
        		'state' => $state,
        		'data' => $data,
        		'msg' => $message,
        	);

        echo json_encode($return_last);
    }

}