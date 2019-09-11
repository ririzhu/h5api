<?php
/**
 * 虚拟订单行为
 *
 */
defined('DYMall') or exit('Access Invalid!');
class vr_order {

    /**
     * 取消订单
     * @param array $order_info
     * @param string $role 操作角色 buyer、seller、admin、system 分别代表买家、商家、管理员、系统
     * @param string $msg 操作备注
     * @param boolean $if_queue 是否使用队列
     * @return array
     */
    public function changeOrderStateCancel($order_info, $role, $msg, $if_queue = true) {

        try {

            $model_vr_order = Model('vr_order');
            $model_vr_order->beginTransaction();

            //库存、销量变更
            if ($if_queue) {
                QueueClient::push('cancelOrderUpdateStorage', array('id'=>$order_info['dian_id'],'data'=>array($order_info['gid'] => $order_info['goods_num'])));
            } else {
                Logic('queue')->cancelOrderUpdateStorage(array('id'=>$order_info['dian_id'],'data'=>array($order_info['gid'] => $order_info['goods_num'])));
            }

            $model_pd = Model('predeposit');

            //解冻充值卡
            $pd_amount = floatval($order_info['rcb_amount']);
            if ($pd_amount > 0) {
                $data_pd = array();
                $data_pd['member_id'] = $order_info['buyer_id'];
                $data_pd['member_name'] = $order_info['buyer_name'];
                $data_pd['amount'] = $pd_amount;
                $data_pd['order_sn'] = $order_info['order_sn'];
                $model_pd->changeRcb('order_cancel',$data_pd);
            }

            //解冻预存款
            $pd_amount = floatval($order_info['pd_amount']);
            if ($pd_amount > 0) {
                $data_pd = array();
                $data_pd['member_id'] = $order_info['buyer_id'];
                $data_pd['member_name'] = $order_info['buyer_name'];
                $data_pd['amount'] = $pd_amount;
                $data_pd['order_sn'] = $order_info['order_sn'];
                $model_pd->changePd('order_cancel',$data_pd);
            }
            //退还积分
            $pd_point = intval($order_info['pd_points']);
            if ($pd_point > 0) {
                Model('points')->savePointsLog('returnpurpose', array('pl_memberid' => $order_info['buyer_id'], 'pl_membername' => $order_info['buyer_name'], 'orderprice' => $order_info['goods_amount'], 'order_sn' => $order_info['order_sn'], 'order_id' => $order_info['order_id'], 'pl_points' => $pd_point), true);
            }

            //更新订单信息
            $update_order = array(
                    'order_state' => ORDER_STATE_CANCEL,
                    'pd_amount' => 0,
                    'close_time' => TIMESTAMP,
                    'close_reason' => $msg
            );
            $update = $model_vr_order->editOrder($update_order,array('order_id'=>$order_info['order_id']));
            if (!$update) {
                throw new Exception('保存失败');
            }

            $model_vr_order->commit();
            return callback(true,'更新成功');

        } catch (Exception $e) {
            $model_vr_order->rollback();
            return callback(false,$e->getMessage());
        }
    }

    /**
     * 支付订单
     * @param array $order_info
     * @param string $role 操作角色 buyer、seller、admin、system 分别代表买家、商家、管理员、系统
     * @param string $post
     * @return array
     */
    public function changeOrderStatePay($order_info, $role, $post) {
        try {

            $model_vr_order = Model('vr_order');
            $model_vr_order->beginTransaction();

            $model_pd = Model('predeposit');
            //下单，支付被冻结的充值卡
            $rcb_amount = floatval($order_info['rcb_amount']);
            if ($rcb_amount > 0) {
                $data_pd = array();
                $data_pd['member_id'] = $order_info['buyer_id'];
                $data_pd['member_name'] = $order_info['buyer_name'];
                $data_pd['amount'] = $rcb_amount;
                $data_pd['order_sn'] = $order_info['order_sn'];
                $model_pd->changeRcb('order_comb_pay',$data_pd);
            }

            //下单，支付被冻结的预存款
            $pd_amount = floatval($order_info['pd_amount']);
            if ($pd_amount > 0) {
                $data_pd = array();
                $data_pd['member_id'] = $order_info['buyer_id'];
                $data_pd['member_name'] = $order_info['buyer_name'];
                $data_pd['amount'] = $pd_amount;
                $data_pd['order_sn'] = $order_info['order_sn'];
                $model_pd->changePd('order_comb_pay',$data_pd);
            }

            //更新订单状态
            $update_order = array();
            if($order_info['dian_id']>0) { //门店修改待自提
                $update_order['order_state'] = ORDER_STATE_SEND;
            }else{
                $update_order['order_state'] = ORDER_STATE_PAY;
            }
            $update_order['payment_time'] = $post['payment_time'] ? strtotime($post['payment_time']) : TIMESTAMP;
            $update_order['payment_code'] = $post['payment_code'];
            $update_order['trade_no'] = $post['trade_no'];
            $update = $model_vr_order->editOrder($update_order,array('order_id'=>$order_info['order_id']));
            if (!$update) {
                throw new Exception(L('保存失败'));
            }
            //支付成功拼团处理
            if($order_info['pin_id']) {
                //取消本活动的其他的未付款订单
                $wqre=M('pin')->paidPin($order_info);
                if(!$wqre['succ']){
                    throw new Exception($wqre['msg']);
                }
            }

            //发放兑换码
            $insert = $model_vr_order->addOrderCode($order_info);
            if (!$insert) {
                throw new Exception('兑换码发送失败');
            }

            // 支付成功发送买家消息
            $param = array();
            $param['code'] = 'order_payment_success';
            $param['member_id'] = $order_info['buyer_id'];
            $param['param'] = array(
                    'order_sn' => $order_info['order_sn'],
                    'order_url' => urlShop('member_vr_order', 'show_order', array('order_id' => $order_info['order_id'])),

                    'first' => '您有一笔订单支付成功',
                    'keyword1' => $order_info['order_sn'],
                    'keyword2' => date('Y年m月d日 H时i分',time()),
                    'keyword3' => sldPriceFormat($order_info['order_amount']),
                    'remark' => '如有问题，请联系我们',
                        
                    'url' => WAP_SITE_URL.'/cwap_order_detail.html?order_id='.$order_info['order_id']
            );
            $param['system_type']=2;
            QueueClient::push('sendMemberMsg', $param);

            //极光推送商户——新订单提醒
            //获取seller_name
//            $seller_info_jpush = Model('seller')->getSellerInfo(array('vid'=>$order_info['vid']));
//            $jpush = Logic('sld_jpush');
//            if($jpush->sld_check_jpush_isopen() ){
//                $jpush->send_special_detail_url('您有一笔新订单，请及时查看',['type'=>'pay_success','order_id'=>$order_info['order_id'],'order_sn'=>$order_info['order_sn']],[$seller_info_jpush['seller_name']]);
//            }

            // 支付成功发送店铺消息
            $param = array();
            $param['code'] = 'new_order';
            $param['vid'] = $order_info['vid'];
            $param['param'] = array(
                    'order_sn' => $order_info['order_sn']
            );
            QueueClient::push('sendStoreMsg', $param);
            //发送门店提醒
            if($order_info['dian_id']>0){
                $params = array();
                $params['code'] = 'dian_new_order';
                $params['vid'] = $order_info['dian_id'];
                $params['param'] = array(
                    'order_sn' => $order_info['order_sn']
                );
                QueueClient::push('sendDianMsg', $params);
            }


            
            $model_vr_order->commit();
            return callback(true,'更新成功');

        } catch (Exception $e) {
            $model_vr_order->rollback();
            return callback(false,$e->getMessage());
        }
    }

    /**
     * 完成订单
     * @param int $order_id
     * @return array
     */
    public function changeOrderStateSuccess($order_id) {
        $model_vr_order = Model('vr_order');
        $condition = array();
        $condition['vr_state'] = 0;
        $condition['refund_lock'] = array('in',array(0,1));
        $condition['order_id'] = $order_id;
        $condition['vr_indate'] = array('gt',TIMESTAMP);
        $order_code_info = $model_vr_order->getOrderCodeInfo($condition,'*',true);
        if (empty($order_code_info)) {
            $update = $model_vr_order->editOrder(array('order_state' => ORDER_STATE_SUCCESS,'finnshed_time' => TIMESTAMP), array('order_id' => $order_id));
            if (!$update) {
                callback(false,'更新失败');
            }
        }

        $order_info = $model_vr_order->getOrderInfo(array('order_id'=>$order_id));
        //添加会员积分
        if (C('points_isuse') == 1){
            Model('points')->savePointsLog('order',array('pl_memberid'=>$order_info['buyer_id'],'pl_membername'=>$order_info['buyer_name'],'orderprice'=>$order_info['order_amount'],'order_sn'=>$order_info['order_sn'],'order_id'=>$order_info['order_id']),true);
        }

        //添加会员经验值
        Model('growthvalue')->saveGrowthValue('order',array('growth_memberid'=>$order_info['buyer_id'],'growth_membername'=>$order_info['buyer_name'],'orderprice'=>$order_info['order_amount'],'order_sn'=>$order_info['order_sn'],'order_id'=>$order_info['order_id']),true);
    
        return callback(true,'更新成功');
    }
}