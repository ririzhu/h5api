<?php
namespace app\v1\controller;

use app\v1\model\Dian;
use app\v1\model\EvaluateGoods;
use app\v1\model\EvaluateStore;
use app\v1\model\GoodsActivity;
use app\v1\model\Refund;
use app\v1\model\UserOrder;
use app\v1\model\VendorInfo;
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
        $condition = "";
        $condition = "bbc_order.buyer_id=". $memberId;

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
            $condition.= " and bbc_order_goods.gid=".input("key")." or bbc_order.order_id like '%".input("key")."%' or bbc_order_goods.goods_name like '%".input("key")."%'";
        }
        $sdate = input("sdate",null);
        $edate = input("edate",null);
        $if_start_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/',$sdate);
        $if_end_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/',$edate);
        $start_unixtime = $if_start_date ? strtotime($sdate) : null;
        $end_unixtime = $if_end_date ? strtotime($edate): null;
        if ($start_unixtime || $end_unixtime) {
            $condition.= " and add_time >$start_unixtime and add_time<=$end_unixtime";
        }
        if(input("s")) {
            if (input('s') != '') {
                switch (input('s')) {
                    case 1:
                        $condition.=" and order_state=".ORDER_STATE_NEW;
                        break;
                    case 256:
                        $condition.=" and order_state=".ORDER_STATE_PAY;
                        break;
                    case 1024:
                        $condition.=" and order_state=".ORDER_STATE_SEND;
                        break;
                    case 2048:
                        $condition.=" and order_state=".ORDER_STATE_SUCCESS;
                        break;
                    case 4096:
                        $condition.=" and order_state=".ORDER_STATE_CANCEL;
                        break;
                    case "nocomment":
                        $condition.=" and order_state=".ORDER_STATE_SUCCESS;
                        break;
                }
            }
            if (input('s') == '1') {
                $condition.= " and chain_code=0";
            }
            if (input('s') == 'nocomment') {
                $condition.=" and evaluation_state=0";
                $condition.=" and order_state = ".ORDER_STATE_SUCCESS;
                $condition.=" and finnshed_time >".TIMESTAMP - ORDER_EVALUATE_TIME;
            }
        }
        //回收站
        if (input('recycle')) {
            $condition.=" and delete_state=1";
        } else {
            $condition.=" and delete_state= 0";
        }
         $order_list = $model_order->getOrderList($condition, $page, "bbc_order.*,bbc_order_goods.goods_name,bbc_order_goods.gid,bbc_order_goods.goods_image,bbc_order_goods.goods_num", 'order_id desc',10, array('order_common','order_goods','store'));

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
            $order_list[$order_id]['goods_image'] = "http://192.168.2.252:9999/data/upload/mall/store/goods/1/".$order['goods_image'];



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
        //print_r($order_pay_list);die;
        if(!empty($order_pay_list)){
            $kkk = 0;
            foreach ($order_group_list as $pay_sn => $pay_info) {
                if(isset($order_pay_list[$kkk])) {
                    $order_group_list[$kkk]['pay_info'] = $order_pay_list[$kkk];
                    $kkk++;
                }
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
            $model_member = new \app\v1\model\User();
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
    /**
     * 取消订单
     */
    public function orderCancel() {
        /*if (!chksubmit()) {
            Template::output('order_info', $order_info);
            Template::showpage('member_order.cancel','null_layout');
            exit();
        } else {
            */
        if(empty(input("member_id"))){
            return;
        }
        $state_type = input("s",'');
        $post = input();
        $order_id   = intval(input('order_id'));

        $model_order = new UserOrder();

        $condition = array();
        $condition['order_id'] = $order_id;
        $condition['buyer_id'] = input("member_id");
        $order_info = $model_order->getOrderInfo($condition);

        //取得其它订单类型的信息
        $model_order->getOrderExtendInfo($order_info);
            $model_order = new UserOrder();
            //$logic_order = Logic('order');
            $if_allow = $model_order->getOrderOperateState('buyer_cancel',$order_info);
            if (!$if_allow) {
                $data['error_code'] =10102;
                $data['message'] = lang('无权操作');
            }
            $msg = "1111";//$post['state_info1'] != '' ? $post['state_info1'] : $post['state_info'];
        $user = new \app\v1\model\User();
            $member = $user->getMemberInfo("member_name")["member_name"];
            $result = $model_order->changeOrderStateCancel($order_info,'buyer', $member, $msg);
            if($result == true){
                $data['message'] = \lang("取消了订单");
            }
        if($result == true){
            $data['error_code'] =200;
            $data['message'] = \lang("取消订单失败");
        }
            return json_encode($data);
        //}
    }
    /**
     * 回收站
     */
    private function _order_recycle($order_info, $get) {
        $model_order = Model('order');
        $logic_order = Logic('order');
        $state_type = str_replace(array('order_delete','order_drop','order_restore'), array('delete','drop','restore'), $_GET['s']);
        $if_allow = $model_order->getOrderOperateState($state_type,$order_info);
        if (!$if_allow) {
            return callback(false,language::get('无权操作'));
        }
        return $logic_order->changeOrderStateRecycle($order_info,'buyer',$state_type);
    }
    /**
     * 订单添加评价
     */
    public function addComment(){
        $order_id = intval($_GET['order_id']);
        if (!$order_id){
            showMsg(Language::get('参数错误'),'index.php?app=userorder','html','error');
        }

        $model_order = new UserOrder();
        $model_store = new VendorInfo();
        $model_evaluate_goods = new EvaluateGoods();
        $model_evaluate_store = new EvaluateStore();
        //$model_evaluate_teacher = new EvaluateTeacher();

        //获取订单信息
        //订单为'已收货'状态，并且未评论
        $order_info = $model_order->getOrderInfo(array('order_id' => $order_id));
        $order_info['evaluate_able'] = $model_order->getOrderOperateState('evaluation',$order_info);
        if (empty($order_info) || !$order_info['evaluate_able']){
            showMsg(Language::get('订单信息错误'),'index.php?app=userorder','html','error');
        }

        //查询店铺信息
        $store_info = $model_store->getStoreInfoByID($order_info['vid']);
        if(empty($store_info)){
            showMsg(Language::get('店铺信息错误'),'index.php?app=userorder','html','error');
        }

        //获取订单商品
        $field = "o.*,m.member_id,m.member_name,g.gc_id_1,g.course_type";
        $order_goods = $model_order->table('order_goods,member,goods')->alias('o,m,g')->join('left join')->on('o.teacher=m.member_id,o.gid=g.gid')->field($field)->where(array('o.order_id'=>$order_id))->select();
        //******************************************************************************************************


        // // 获取最终价格
        // $order_goods = Model('goods_activity')->rebuild_goods_data($order_goods,'pc');

        if(empty($order_goods)){
            showMsg(Language::get('订单信息错误'),'index.php?app=userorder','html','error');
        }
        //判断是否为页面
        if (!$_POST){
            for ($i = 0, $j = count($order_goods); $i < $j; $i++) {
                $order_goods[$i]['goods_image_url'] = cthumb($order_goods[$i]['goods_image'], 60, $store_info['vid']);
            }
            //处理积分、经验值计算说明文字
            $ruleexplain = '';
            $exppoints_rule = C("exppoints_rule")?unserialize(C("exppoints_rule")):array();
            $exppoints_rule['exp_comments'] = intval($exppoints_rule['exp_comments']);
            $points_comments = intval(C('points_comments'));
            if ($exppoints_rule['exp_comments'] > 0 || $points_comments > 0){
                $ruleexplain .= Language::get('评价完成将获得');
                if ($exppoints_rule['exp_comments'] > 0){
                    $ruleexplain .= (Language::get(' “').$exppoints_rule['exp_comments'].Language::get('经验值”'));
                }
                if ($points_comments > 0){
                    $ruleexplain .= (Language::get(' “').$points_comments.Language::get('积分”'));
                }
                $ruleexplain .= Language::get('。');
            }
            Template::output('ruleexplain', $ruleexplain);

            $model_sns_alumb = Model('sns_album');
            $ac_id = $model_sns_alumb->getSnsAlbumClassDefault($_SESSION['member_id']);
            Template::output('acid', $ac_id);
            //不显示左菜单
            Template::output('left_show','order_view');
            Template::output('order_info',$order_info);
            Template::output('order_goods',$order_goods);
            Template::output('store_info',$store_info);
            Template::output('menu_sign','evaluateadd');
            Template::showpage('evaluation.add');
        }else {
            $evaluate_goods_array = array();
            $evaluate_teacher_array = array();
            foreach ($order_goods as $value){
                //如果未评分，默认为5分
                $evaluate_score = intval($_POST['goods'][$value['gid']]['score']);
                if($evaluate_score <= 0 || $evaluate_score > 5) {
                    $evaluate_score = 5;
                }
                //默认评语
                $evaluate_comment = $_POST['goods'][$value['gid']]['comment'];
                if(empty($evaluate_comment)) {
                    $evaluate_comment = Language::get('不错哦');
                }

                //老师评分
                $evaluate_t_score = intval($_POST['goods'][$value['gid']]['t_score']);
                if($evaluate_t_score <= 0 || $evaluate_t_score > 5) {
                    $evaluate_t_score = 5;
                }
                //默认评语
                $evaluate_t_comment = $_POST['goods'][$value['gid']]['t_comment'];
                if(empty($evaluate_t_comment)) {
                    $evaluate_t_comment = Language::get('不错哦');
                }

                $geval_image = '';
                if (isset($_POST['goods'][$value['rec_id']]['evaluate_image']) && is_array($_POST['goods'][$value['rec_id']]['evaluate_image'])) {
                    foreach ($_POST['goods'][$value['rec_id']]['evaluate_image'] as $val) {
                        if(!empty($val)) {
                            $geval_image .= $val . ',';
                        }
                    }
                }
                $geval_image = rtrim($geval_image, ',');

                $evaluate_goods_info = array();
                $evaluate_goods_info['geval_orderid'] = $order_id;
                $evaluate_goods_info['geval_orderno'] = $order_info['order_sn'];
                $evaluate_goods_info['geval_ordergoodsid'] = $value['rec_id'];
                $evaluate_goods_info['geval_goodsid'] = $value['gid'];
                $evaluate_goods_info['geval_goodsname'] = $value['goods_name'];
                $evaluate_goods_info['geval_goodsprice'] = $value['goods_price'];
                $evaluate_goods_info['geval_scores'] = $evaluate_score;
                $evaluate_goods_info['geval_content'] = $evaluate_comment;
                $evaluate_goods_info['geval_isanonymous'] = $_POST['anony']?1:0;
                $evaluate_goods_info['geval_addtime'] = TIMESTAMP;
                $evaluate_goods_info['geval_storeid'] = $store_info['vid'];
                $evaluate_goods_info['geval_storename'] = $store_info['store_name'];
                $evaluate_goods_info['geval_frommemberid'] = $_SESSION['member_id'];
                $evaluate_goods_info['geval_frommembername'] = $_SESSION['member_name'];
                $evaluate_goods_info['geval_image'] = $geval_image;
                $evaluate_goods_array[] = $evaluate_goods_info;

                //添加老师评价****************************************************************************************
                $evaluate_teacher_info = array();
                $evaluate_teacher_info['teval_orderid'] = $order_id;
                $evaluate_teacher_info['teval_orderno'] = $order_info['order_sn'];
                $evaluate_teacher_info['teval_ordergoodsid'] = $value['rec_id'];
                $evaluate_teacher_info['teval_member_id'] = $_POST['goods'][$value['gid']]['member_id'];
                $evaluate_teacher_info['teval_teacher_name'] = $_POST['goods'][$value['gid']]['member_name'];
                $evaluate_teacher_info['teval_goods_name'] = $value['goods_name'];
                $evaluate_teacher_info['teval_goodsprice'] = $value['goods_price'];
                $evaluate_teacher_info['teval_scores'] = $evaluate_t_score;
                $evaluate_teacher_info['teval_content'] = $evaluate_t_comment;
                $evaluate_teacher_info['teval_addtime'] = TIMESTAMP;
                $evaluate_teacher_info['teval_storeid'] = $store_info['vid'];
                $evaluate_teacher_info['teval_storename'] = $store_info['store_name'];
                $evaluate_teacher_info['teval_frommemberid'] = $_SESSION['member_id'];
                $evaluate_teacher_info['teval_frommembername'] = $_SESSION['member_name'];
                $evaluate_teacher_info['teval_image'] = $geval_image;
                $evaluate_teacher_array[] = $evaluate_teacher_info;
                //***************************************************************************************************

            }
            $model_evaluate_goods->addEvaluateGoodsArray($evaluate_goods_array);

            //保存老师评价
            $model_evaluate_teacher->insertAll($evaluate_teacher_array);
//            $res = $model_evaluate_teacher->getLastsql();

            $store_desccredit = intval($_POST['store_desccredit']);
            if($store_desccredit <= 0 || $store_desccredit > 5) {
                $store_desccredit= 5;
            }
            $store_servicecredit = intval($_POST['store_servicecredit']);
            if($store_servicecredit <= 0 || $store_servicecredit > 5) {
                $store_servicecredit = 5;
            }
            $store_deliverycredit = intval($_POST['store_deliverycredit']);
            if($store_deliverycredit <= 0 || $store_deliverycredit > 5) {
                $store_deliverycredit = 5;
            }

            if($order_goods[0]['course_type'] == 1){
                //添加店铺评价
                $evaluate_store_info = array();
                $evaluate_store_info['seval_orderid'] = $order_id;
                $evaluate_store_info['seval_orderno'] = $order_info['order_sn'];
                $evaluate_store_info['seval_addtime'] = time();
                $evaluate_store_info['seval_storeid'] = $store_info['vid'];
                $evaluate_store_info['seval_storename'] = $store_info['store_name'];
                $evaluate_store_info['seval_memberid'] = $_SESSION['member_id'];
                $evaluate_store_info['seval_membername'] = $_SESSION['member_name'];
                $evaluate_store_info['seval_desccredit'] = $store_desccredit;
                $evaluate_store_info['seval_servicecredit'] = $store_servicecredit;
                $evaluate_store_info['seval_deliverycredit'] = $store_deliverycredit;
                $model_evaluate_store->addEvaluateStore($evaluate_store_info);
            }

            //更新订单信息并记录订单日志
            $state = $model_order->editOrder(array('evaluation_state'=>1), array('order_id' => $order_id));
            $model_order->editOrderCommon(array('evaluation_time'=>TIMESTAMP), array('order_id' => $order_id));
            if ($state){
                $data = array();
                $data['order_id'] = $order_id;
                $data['log_role'] = 'buyer';
                $data['log_msg'] = L('评价了交易');
                $model_order->addOrderLog($data);
            }

            //添加会员积分
            if ($GLOBALS['setting_config']['points_isuse'] == 1){
                $points_model = Model('points');
                $points_model->savePointsLog('comments',array('pl_memberid'=>$_SESSION['member_id'],'pl_membername'=>$_SESSION['member_name']));
            }


            showDialog(Language::get('评价成功'),'index.php?app=userorder', 'succ');
        }
    }
}