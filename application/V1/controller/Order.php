<?php
namespace app\V1\controller;

use app\V1\model\Dian;
use app\V1\model\GoodsActivity;
use app\V1\model\Refund;
use app\V1\model\UserOrder;
use think\Lang;
class Order extends Base
{
    /**
     * 买家我的订单，以总订单pay_sn来分组显示
     *
     */
    public function index() {
        $model_order = new UserOrder();
        if(!input("member_id")){
            $data['message'] = lang("参数错误");
            return json_encode($data);
        }
        $page = input("page",0);
        $memberId = input("member_id");
        //搜索
        $condition = array();
        $condition['buyer_id'] = $memberId;

//        if (preg_match('/^\d{10,20}$/',$_GET['order_sn'])) {
//            $condition['order_sn'] = $_GET['order_sn'];
//        }
//       if (preg_match('/^\d{10,20}$/',$_GET['pay_sn'])) {
//           $condition['pay_sn'] = $_GET['pay_sn'];
//       }
//        $if_start_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/',$_GET['query_start_date']);
//        $if_end_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/',$_GET['query_end_date']);
//        $start_unixtime = $if_start_date ? strtotime($_GET['query_start_date']) : null;
//		$end_unixtime = $if_end_date ? strtotime($_GET['query_end_date']): null;

        if (input("key") && input("key") != '') {
            $condition['order_sn'] = input("key");
        }
        $sdate = input("sdate",null);
        $edate = input("edate",null);
        $if_start_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/',$sdate);
        $if_end_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/',$edate);
        $start_unixtime = $if_start_date ? strtotime($sdate) : null;
        $end_unixtime = $if_end_date ? strtotime($edate): null;
        if ($start_unixtime || $end_unixtime) {
            $condition['add_time'] = array('time',array($start_unixtime,$end_unixtime));
        }
        if(input("s")) {
            if (input('s') != '') {
                switch (input('s')) {
                    case 1:
                        $condition['order_state'] = ORDER_STATE_NEW;
                        break;
                    case 256:
                        $condition['order_state'] = ORDER_STATE_PAY;
                        break;
                    case 1024:
                        $condition['order_state'] = ORDER_STATE_SEND;
                        break;
                    case 2048:
                        $condition['order_state'] = ORDER_STATE_SUCCESS;
                        break;
                    case 4096:
                        $condition['order_state'] = ORDER_STATE_CANCEL;
                        break;
                    case "nocomment":
                        $condition['order_state'] = ORDER_STATE_SUCCESS;
                        break;
                }
            }
            if (input('s') == '1') {
                $condition['chain_code'] = 0;
            }
            if (input('s') == 'nocomment') {
                $condition['evaluation_state'] = 0;
                $condition['order_state'] = ORDER_STATE_SUCCESS;
                $condition['finnshed_time'] = array('gt', TIMESTAMP - ORDER_EVALUATE_TIME);
            }
        }
        //回收站
        if (input('recycle')) {
            $condition['delete_state'] = 1;
        } else {
            $condition['delete_state'] = 0;
        }
        $order_list = $model_order->getOrderList($condition, $page, '*', 'order_id desc',10, array('order_common','order_goods','store'));

//        dd($order_list);
        // 获取订单 活动类型（根据订单商品的 goods_type 进行判断）
        $ga = new GoodsActivity();
        $order_list = $ga->rebuild_order_data($order_list);

        $model_refund_return = new Refund();

        $order_list = $model_refund_return->getGoodsRefundList($order_list,1);//订单商品的退款退货显示

        //查询消费者保障服务
        if (Config('contract_allow') == 1) {
            $contract_item = Model('contract')->getContractItemByCache();
        }

        //订单列表以支付单pay_sn分组显示
        $order_group_list = array();
        $order_pay_sn_array = array();

        //Language::read('member_member_index');
        //$lang	= Language::getLangContent();
        foreach ($order_list as $order_id => $order) {





            //显示取消订单
            $order['if_cancel'] = $model_order->getOrderOperateState('buyer_cancel',$order);

            //显示退款取消订单
            $order['if_refund_cancel'] = $model_order->getOrderOperateState('refund_cancel',$order);

            //显示投诉
            $order['if_complain'] = $model_order->getOrderOperateState('tousu',$order);

            //显示收货
            $order['if_receive'] = $model_order->getOrderOperateState('receive',$order);

            //显示锁定中
            $order['if_lock'] = $model_order->getOrderOperateState('lock',$order);

            //显示收货
            $order['if_receive'] = $model_order->getOrderOperateState('receive',$order);

            //显示物流跟踪
            $order['if_deliver'] = $model_order->getOrderOperateState('deliver',$order);

            //显示评价
            $order['if_evaluation'] = $model_order->getOrderOperateState('evaluation',$order);

            //显示分享
            $order['if_share'] = $model_order->getOrderOperateState('share',$order);

            // 显示追加评价
            $order['if_evaluation_again'] = $model_order->getOrderOperateState('evaluation_again',$order);

            //显示删除订单(放入回收站)
            $order['if_delete'] = $model_order->getOrderOperateState('delete',$order);
            //显示永久删除
            $order['if_drop'] = $model_order->getOrderOperateState('drop',$order);

            //显示还原订单
            $order['if_restore'] = $model_order->getOrderOperateState('restore',$order);
            if(isset( $order['refund_list'][0])){
                $refund_all = $order['refund_list'][0];
            }else{
                $refund_all = array();
            }
            if (!empty($refund_all) && $refund_all['seller_state'] < 3) {//订单全部退款商家审核状态:1为待审核,2为同意,3为不同意
                $order['refund_all'] = $refund_all;
            }
            if (is_array($order['extend_order_goods'])) {
                foreach ($order['extend_order_goods'] as $k=>$value) {
                    $value['image_60_url'] = cthumb($value['goods_image'], 60, $value['vid']);
                    $value['image_240_url'] = cthumb($value['goods_image'], 240, $value['vid']);
                    $value['goods_type_cn'] = orderGoodsType($value['goods_type']);
                    $value['goods_url'] = urlShop('goods','index',array('gid'=>$value['gid']));
                    //处理消费者保障服务
                    if (trim($value['goods_contractid']) && $contract_item) {
                        $goods_contractid_arr = explode(',',$value['goods_contractid']);
                        foreach ((array)$goods_contractid_arr as $gcti_v) {
                            $value['contractlist'][] = $contract_item[$gcti_v];
                        }
                    }
                    if ($value['goods_type'] == 5) {
                        $order['zengpin_list'][] = $value;
                    } else {
                        $order['goods_list'][] = $value;
                    }

                    switch ($value['course_type']){
                        case 1 : $order['extend_order_goods'][$k]['bs'] = Lang('公开课'); break;
                        case 2 : $order['extend_order_goods'][$k]['bs'] = lang('在线课'); break;
                        case 3 : $order['extend_order_goods'][$k]['bs'] = lang('教材'); break;
                    }
                }
            }

            if (empty($order['zengpin_list'])) {
                if(isset($order['goods_list']))
                $order['goods_count'] = count($order['goods_list']);
                else
                    $order['goods_count'] = 0;
            } else {
                $order['goods_count'] = count($order['goods_list']) + 1;
            }
            $order_group_list[$order['pay_sn']]['order_list'][] = $order;

            //如果有在线支付且未付款的订单则显示合并付款链接
            if ($order['order_state'] == ORDER_STATE_NEW) {
                if(isset($order_group_list[$order['pay_sn']]['pay_amount']))
                $order_group_list[$order['pay_sn']]['pay_amount'] += $order['order_amount'];
                else
                    $order_group_list[$order['pay_sn']]['pay_amount'] =0;
            }
            $order_group_list[$order['pay_sn']]['add_time'] = $order['add_time'];

            //记录一下pay_sn，后面需要查询支付单表
            $order_pay_sn_array[] = $order['pay_sn'];
        }


        //取得这些订单下的支付单列表
        $condition = array('pay_sn'=>array('in',arrayToString(array_unique($order_pay_sn_array))));
        $order_pay_list = $model_order->getOrderPayList($condition,'','*','','pay_sn');
        if(!empty($order_pay_list)){
            foreach ($order_group_list as $pay_sn => $pay_info) {
                $order_group_list[$pay_sn]['pay_info'] = $order_pay_list[$pay_sn];
            }
        }
        $memberInfo = $this->get_member_info($memberId);
        $data['order_list'] = $order_list;
        $data['order_pay_list'] = $order_pay_list;
        $data['order_group_list'] = $order_group_list;
        $data['member_info'] = $memberInfo;
        return json_encode($data);

        //Template::output('order_group_list',$order_group_list);


        //dd($order_group_list['510604670229535002']['order_list'][0]['extend_order_goods']);


        //Template::output('order_pay_list',$order_pay_list);
        //Template::output('show_page',$model_order->showpage());


        //self::profile_menu('member_order');
        //Template::showpage('member_order.index');
    }
    /**
     * 买家的左侧上部的头像和订单数量
     *
     */
    public function get_member_info($memberId) {
        //生成缓存的键值
        $hash_key = $memberId;
        //写入缓存的数据
        $cachekey_arr = array('member_name','vid','member_avatar','member_qq','member_email','member_ww','member_goldnum','member_points',
            'available_predeposit','member_snsvisitnum','credit_arr','order_nopay','order_noreceiving','order_noeval','fan_count');
        if (false){
            foreach ($_cache as $k=>$v){
                $member_info[$k] = $v;
            }
        } else {
            $model_order = new UserOrder();
            $model_member = new \app\V1\model\User();
            $member_info = $model_member->getMemberInfo(array('member_id'=>$memberId));
            $member_info['order_nopay'] = $model_order->getOrderStateNewCount(array('buyer_id'=>$memberId));
            $member_info['order_nodelivery'] = $model_order->getOrderStatePayCount(array('buyer_id'=>$memberId));
            $member_info['order_noreceiving'] = $model_order->getOrderStateSendCount(array('buyer_id'=>$memberId));
            $member_info['order_noeval'] = $model_order->getOrderStateEvalCount(array('buyer_id'=>$memberId));
        }
        return $member_info;
        //Template::output('member_info',$member_info);
        //Template::output('header_menu_sign','snsindex');//默认选中顶部“买家首页”菜单
    }
    /**
     * 订单详细
     *
     */
    public function show_order() {
        $order_id = intval(input('order_id'));
        if ($order_id <= 0) {
            $data['message'] = lang('该订单不存在');
        }
        $memberId = input("member_id");
        $model_order = new UserOrder();
        $condition = array();
        $condition['order_id'] = $order_id;
        $condition['buyer_id'] = $memberId;
        $order_info = $model_order->getOrderInfo($condition,array('order_goods','order_common','store'));
        if (empty($order_info)) {
            $data['message'] = lang('该订单不存在');
        }
        $model_refund_return = new Refund();
        $order_list = array();
        $order_list[$order_id] = $order_info;
        $order_list = $model_refund_return->getGoodsRefundList($order_list,1);//订单商品的退款退货显示
        $order_info = $order_list[$order_id];
        if(isset($order_info['refund_list'])) {
            $refund_all = $order_info['refund_list'][0];
            if (!empty($refund_all) && $refund_all['seller_state'] < 3) {//订单全部退款商家审核状态:1为待审核,2为同意,3为不同意
                //Template::output('refund_all',$refund_all);
            }
        }
        //显示锁定中
        $order_info['if_lock'] = $model_order->getOrderOperateState('lock',$order_info);
        //显示锁定中
        $order_info['if_send'] = $model_order->getOrderOperateState('send',$order_info);

        //显示取消订单
        $order_info['if_buyer_cancel'] = $model_order->getOrderOperateState('buyer_cancel',$order_info);

        //显示退款取消订单
        $order_info['if_refund_cancel'] = $model_order->getOrderOperateState('refund_cancel',$order_info);

        //显示投诉
        $order_info['if_complain'] = $model_order->getOrderOperateState('complain',$order_info);

        //显示收货
        $order_info['if_receive'] = $model_order->getOrderOperateState('receive',$order_info);

        //显示物流跟踪
        $order_info['if_deliver'] = $model_order->getOrderOperateState('deliver',$order_info);

        //显示评价
        $order_info['if_evaluation'] = $model_order->getOrderOperateState('evaluation',$order_info);

        //显示删除订单(放入回收站)
        $order_info['if_delete'] = $model_order->getOrderOperateState('delete',$order_info);
        //显示永久删除
        $order_info['if_drop'] = $model_order->getOrderOperateState('drop',$order_info);

        //显示还原订单
        $order_info['if_restore'] = $model_order->getOrderOperateState('restore',$order_info);

        //显示系统自动取消订单日期
        if ($order_info['order_state'] == ORDER_STATE_NEW) {
            $order_info['order_cancel_day'] = $order_info['add_time'] + ORDER_AUTO_CANCEL_TIME * 3600;
        }

        //显示快递信息
        if ($order_info['shipping_code'] != '') {
            $base =new Base();
            $express = $base->rkcache('express',true);
            $order_info['express_info']['e_code'] = $express[$order_info['extend_order_common']['shipping_express_id']]['e_code'];
            $order_info['express_info']['e_name'] = $express[$order_info['extend_order_common']['shipping_express_id']]['e_name'];
            $order_info['express_info']['e_url'] = $express[$order_info['extend_order_common']['shipping_express_id']]['e_url'];
        }

        //显示系统自动收获时间
        if ($order_info['order_state'] == ORDER_STATE_SEND) {
            $order_info['order_confirm_day'] = $order_info['delay_time'] + ORDER_AUTO_RECEIVE_DAY * 24 * 3600;
        }

        //查询消费者保障服务
        if (Config('contract_allow') == 1) {
            $contract_item = Model('contract')->getContractItemByCache();
        }
        foreach ($order_info['extend_order_goods'] as $value) {
            $value['image_60_url'] = cthumb($value['goods_image'], 60, $value['vid']);
            $value['image_240_url'] = cthumb($value['goods_image'], 240, $value['vid']);
            $value['goods_type_cn'] = orderGoodsType($value['goods_type']);
            $value['goods_url'] = urlShop('goods','index',array('gid'=>$value['gid']));
            //处理消费者保障服务
            if (trim($value['goods_contractid']) && $contract_item) {
                $goods_contractid_arr = explode(',',$value['goods_contractid']);
                foreach ((array)$goods_contractid_arr as $gcti_v) {
                    $value['contractlist'][] = $contract_item[$gcti_v];
                }
            }
            if ($value['goods_type'] == 5) {
                $order_info['zengpin_list'][] = $value;
            } else {
                $order_info['goods_list'][] = $value;
            }
        }

        if (empty($order_info['zengpin_list'])) {
            if(isset($order_info['goods_list']))
                $order_info['goods_count'] = count($order_info['goods_list']);
            else
                $order_info['goods_count'] = 0;
        } else {
            $order_info['goods_count'] = count($order_info['goods_list']) + 1;
        }
        $order_info['state_desc'] = orderStateVendor($order_info);


        //取得其它订单类型的信息
        $model_order->getOrderExtendInfo($order_info);
        $data['order_info'] = $order_info;
        //Template::output('order_info',$order_info);
        //Template::output('left_show','order_view');

        //卖家发货信息
        if (!empty($order_info['extend_order_common']['daddress_id'])) {
            $daddress_info = Model('daddress')->getAddressInfo(array('address_id'=>$order_info['extend_order_common']['daddress_id']));
            //Template::output('daddress_info',$daddress_info);
        }

        //自提地址
        if($order_info['dian_id']>0){
            $dian = new Dian();
            $dian_info = $dian->getDianInfoById(null,$order_info['dian_id']);
            $dian_info['dian_phone_arr'] = explode(',',$dian_info['dian_phone']);
            $dian_info['operation_time_arr'] = explode(',',$dian_info['operation_time']);
            $dian_info['operation_time_arr'][0] = sprintf("%02d",$dian_info['operation_time_arr'][0]%1440/60).":".sprintf("%02d",$dian_info['operation_time_arr'][0]%60);
            $dian_info['operation_time_arr'][1] = sprintf("%02d",$dian_info['operation_time_arr'][1]%1440/60).":".sprintf("%02d",$dian_info['operation_time_arr'][1]%60);
            $t = $model_order->encode($order_info['order_sn'],$order_info['vid'].$order_info['dian_id']);
            $new='';
            $t=str_split($t);
            for($i=0;$i<count($t);$i++){
                if($i==4 || $i==8 || $i==12){
                    $new.=' ';
                }
                $new.=$t[$i];
            }
            $dian_info['hexiao_code'] = $new;
            $data['store_info'] = $dian_info;
            //Template::output('dian_info',$dian_info);
        }

        //订单变更日志
        $log_list	= $model_order->getOrderLogList(array('order_id'=>$order_info['order_id']));
        $data['log_list'] = $log_list;
        //Template::output('order_log',$log_list);

        //退款退货信息
        $model_refund = new Refund();
        $condition = array();
        $condition['order_id'] = $order_info['order_id'];
        $condition['seller_state'] = 2;
        $condition['admin_time'] = array('gt',0);
        $return_list = $model_refund->getReturnList($condition);
        $data['return_list'] = $return_list;
        //Template::output('return_list',$return_list);
//dd($order_info);die;
        //退款信息
        $refund_list = $model_refund->getRefundList($condition);
        $data['refund_list'] = $return_list;
        $data['error_code'] = 200;
        return json_encode($data);
        //Template::output('refund_list',$refund_list);
        //Template::showpage('member_order.show');
    }
}