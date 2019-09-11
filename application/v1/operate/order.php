<?php
/**
 * 实物订单行为
 *
 */
defined('DYMall') or exit('Access Invalid!');
class order {

    /**
     * 取消订单
     * @param array $order_info
     * @param string $role 操作角色 buyer、seller、admin、system 分别代表买家、商家、管理员、系统
     * @param string $user 操作人
     * @param string $msg 操作备注
     * @param boolean $if_update_account 是否变更账户金额
     * @param boolean $if_queue 是否使用队列
     * @return array
     */
    public function changeOrderStateCancel($order_info, $role, $user = '', $msg = '', $if_update_account = true, $if_quque = true) {
        try {
            $model_order = Model('order');
            $model_order->beginTransaction();
            $order_id = $order_info['order_id'];

            //库存销量变更
            $goods_list = $model_order->getOrderGoodsList(array('order_id'=>$order_id));
            $data = array();
            foreach ($goods_list as $goods) {
                if ($goods['has_spec']) {
                    $item_goods_info = Model('goods')->getGoodsInfoByID($goods['gid'],'goods_commonid');

                    // 有规格 (获取规格信息)
                    $spec_array = Model('goods')->getGoodsList(array('goods_commonid' => $item_goods_info['goods_commonid']), 'goods_spec,gid');
                    $spec_list = array();       // 各规格商品地址，js使用
                    foreach ($spec_array as $s_key => $s_value) {
                        $s_array = unserialize($s_value['goods_spec']);
                        
                        $tmp_array = array();
                        if (!empty($s_array) && is_array($s_array)) {
                            foreach ($s_array as $k => $v) {
                                $tmp_array[] = $k;
                            }
                        }
                        sort($tmp_array);
                        $spec_sign = implode('|', $tmp_array);

                        $spec_list[$spec_sign]['gid'] = $s_value['gid'];
                    }

                    $item_spec_num = unserialize($goods['spec_num']);
                    foreach ($item_spec_num as $spec_key => $spec_value) {
                        $data[$spec_list[$spec_key]['gid']] = $spec_value;
                    }
                }else{
                    $data[$goods['gid']] = $goods['goods_num'];   
                }

                //如果有首单优惠，取消订单把优惠使用权还回来
                if($goods['first']){
                    //修改首单满减记录
                    M('first','firstDiscount')->table('first_discount_log')->where(['order_id'=>$order_id])->update(['state'=>0]);
                }
            }
            if ($if_quque) {
                QueueClient::push('cancelOrderUpdateStorage', array('id'=>$order_info['dian_id'],'data'=>$data));
            } else {
                Logic('queue')->cancelOrderUpdateStorage(array('id'=>$order_info['dian_id'],'data'=>$data));
            }

            if ($if_update_account) {
                $model_pd = Model('predeposit');
                //解冻充值卡
                $rcb_amount = floatval($order_info['rcb_amount']);
                if ($rcb_amount > 0) {
                    $data_pd = array();
                    $data_pd['member_id'] = $order_info['buyer_id'];
                    $data_pd['member_name'] = $order_info['buyer_name'];
                    $data_pd['amount'] = $rcb_amount;
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
                    Model('points')->savePointsLog('returnpurpose', array('pl_memberid' => $order_info['buyer_id'], 'pl_membername' => $order_info['buyer_name'], 'orderprice' => $order_info['goods_amount'], 'order_sn' => $order_info['order_sn'], 'order_id' => $order_id, 'pl_points' => $pd_point), true);
                }

                if($order_info['red_id']>0){
                    //退回优惠券  平台
                    M('red')->table('red_user')->where(array('id'=>$order_info['red_id']))->update(array('reduser_use'=>0));
                }

                if($order_info['vred_id']>0){
                    //退回优惠券  店铺
                    M('red')->table('red_user')->where(array('id'=>$order_info['vred_id']))->update(array('reduser_use'=>0));
                }
            }

            //更新订单信息
            $update_order = array('order_state' => ORDER_STATE_CANCEL, 'pd_amount' => 0);
            $update = $model_order->editOrder($update_order,array('order_id'=>$order_id));
            if (!$update) {
                throw new Exception('保存失败');
            }

            //添加订单日志
            $data = array();
            $data['order_id'] = $order_id;
            $data['log_role'] = $role;
            $data['log_msg'] = '取消了订单';
            $data['log_user'] = $user;
            if ($msg) {
                $data['log_msg'] .= ' ( '.$msg.' )';
            }
            $data['log_orderstate'] = ORDER_STATE_CANCEL;
            $model_order->addOrderLog($data);

            // 推手系统 订单状态更新
            if (C('spreader_isuse') && C('sld_spreader')) {
                $par['state_type'] = 'order_cancel';
                $par['order_info'] = $order_info;
                $par['extend_msg'] = '';
                // 发送请求 添加订单信息
                con_addons('spreader',$par,'update_order_status_speader','api','mobile');
            }
            
            $model_order->commit();

            return callback(true,'操作成功');

        } catch (Exception $e) {
            $this->rollback();
            return callback(false,'操作失败');
        }
    }

    /**
     * 收货
     * @param array $order_info
     * @param string $role 操作角色 buyer、seller、admin、system 分别代表买家、商家、管理员、系统
     * @param string $user 操作人
     * @param string $msg 操作备注
     * @return array 店铺ID=>array(分类ID=>佣金比例)
     */
    public function changeOrderStateReceive($order_info, $role, $user = '', $msg = '') {
        try {
            $order_id = $order_info['order_id'];
            $model_order = Model('order');
            //更新订单状态
            $update_order = array();
            $update_order['finnshed_time'] = TIMESTAMP;
            $update_order['order_state'] = ORDER_STATE_SUCCESS;
            $update = $model_order->editOrder($update_order,array('order_id'=>$order_id));
            if (!$update) {
                throw new Exception('保存失败');
            }

            //发放优惠券：推荐
            M('red')->SendRedInvite($order_info['buyer_id']);

            //添加订单日志
            $data = array();
            $data['order_id'] = $order_id;
            $data['log_role'] = $role;
            $data['log_msg'] = $msg;
            $data['log_user'] = $user;
            $data['log_orderstate'] = ORDER_STATE_SUCCESS;
            $model_order->addOrderLog($data);

            if ($order_info['buyer_id'] > 0 && $order_info['order_amount'] > 0) {
                //添加会员积分
                if (C('points_isuse') == 1){
                    Model('points')->savePointsLog('order',array('pl_memberid'=>$order_info['buyer_id'],'pl_membername'=>$order_info['buyer_name'],'orderprice'=>$order_info['order_amount'],'order_sn'=>$order_info['order_sn'],'order_id'=>$order_info['order_id']),true);
                }
                //添加会员经验值
                Model('growthvalue')->saveGrowthValue('order',array('growth_memberid'=>$order_info['buyer_id'],'growth_membername'=>$order_info['buyer_name'],'orderprice'=>$order_info['order_amount'],'order_sn'=>$order_info['order_sn'],'order_id'=>$order_info['order_id']),true);
            }
            return callback(true,'操作成功');
        } catch (Exception $e) {
            return callback(false,'操作失败');
        }
    }

    /**
     * 更改运费
     * @param array $order_info
     * @param string $role 操作角色 buyer、seller、admin、system 分别代表买家、商家、管理员、系统
     * @param string $user 操作人
     * @param float $price 运费
     * @return array
     */
    public function changeOrderShipPrice($order_info, $role, $user = '', $price) {
        try {

            $order_id = $order_info['order_id'];
            $model_order = Model('order');

            $data = array();
            $data['shipping_fee'] = abs(floatval($price));
            $data['order_amount'] = array('exp','goods_amount+'.$data['shipping_fee']);
            $update = $model_order->editOrder($data,array('order_id'=>$order_id));
            if (!$update) {
                throw new Exception('保存失败');
            }
            //记录订单日志
            $data = array();
            $data['order_id'] = $order_id;
            $data['log_role'] = $role;
            $data['log_user'] = $user;
            $data['log_msg'] = '修改了运费'.'( '.$price.' )';;
            $data['log_orderstate'] = $order_info['payment_code'] == 'offline' ? ORDER_STATE_PAY : ORDER_STATE_NEW;
            $model_order->addOrderLog($data);
            return callback(true,'操作成功');
        } catch (Exception $e) {
            return callback(false,'操作失败');
        }
    }
    /**
     * 更改运费
     * @param array $order_info
     * @param string $role 操作角色 buyer、seller、admin、system 分别代表买家、商家、管理员、系统
     * @param string $user 操作人
     * @param float $price 运费
     * @return array
     */
    public function changeOrderSpayPrice($order_info, $role, $user = '', $price) {
        try {

            $order_id = $order_info['order_id'];
            $model_order = Model('order');

            $data = array();
            $data['goods_amount'] = abs(floatval($price));
            $data['order_amount'] = array('exp','shipping_fee+'.$data['goods_amount']);
            $update = $model_order->editOrder($data,array('order_id'=>$order_id));
            if (!$update) {
                throw new Exception('保存失败');
            }
            //记录订单日志
            $data = array();
            $data['order_id'] = $order_id;
            $data['log_role'] = $role;
            $data['log_user'] = $user;
            $data['log_msg'] = '修改了运费'.'( '.$price.' )';;
            $data['log_orderstate'] = $order_info['payment_code'] == 'offline' ? ORDER_STATE_PAY : ORDER_STATE_NEW;
            $model_order->addOrderLog($data);
            return callback(true,'操作成功');
        } catch (Exception $e) {
            return callback(false,'操作失败');
        }
    }
    /**
     * 回收站操作（放入回收站、还原、永久删除）
     * @param array $order_info
     * @param string $role 操作角色 buyer、seller、admin、system 分别代表买家、商家、管理员、系统
     * @param string $state_type 操作类型
     * @return array
     */
    public function changeOrderStateRecycle($order_info, $role, $state_type) {
        $order_id = $order_info['order_id'];
        $model_order = Model('order');
        //更新订单删除状态
        $state = str_replace(array('delete','drop','restore'), array(ORDER_DEL_STATE_DELETE,ORDER_DEL_STATE_DROP,ORDER_DEL_STATE_DEFAULT), $state_type);
        $update = $model_order->editOrder(array('delete_state'=>$state),array('order_id'=>$order_id));
        if (!$update) {
            return callback(false,'操作失败');
        } else {
            return callback(true,'操作成功');
        }
    }

    /**
     * 发货
     * @param array $order_info
     * @param string $role 操作角色 buyer、seller、admin、system 分别代表买家、商家、管理员、系统
     * @param string $user 操作人
     * @return array
     */
    public function changeOrderSend($order_info, $role, $user = '', $post = array()) {
        $order_id = $order_info['order_id'];
        $model_order = Model('order');
        try {
            $model_order->beginTransaction();
            $data = array();
            $data['reciver_name'] = $order_info['extend_order_common']['reciver_name'];
            $data['reciver_info'] = $order_info['extend_order_common']['reciver_info'];
            $data['deliver_explain'] = $order_info['extend_order_common']['deliver_explain'];
            $data['daddress_id'] = intval($post['daddress_id']);
            $data['shipping_express_id'] = intval($post['shipping_express_id']);
            $data['shipping_time'] = TIMESTAMP;

            $condition = array();
            $condition['order_id'] = $order_id;
            $condition['vid'] = $order_info['vid'];
            $update = $model_order->editOrderCommon($data,$condition);
            if (!$update) {
                throw new Exception('操作失败');
            }

            $data = array();
            $data['shipping_code']  = $post['shipping_code'];
            $data['order_state'] = ORDER_STATE_SEND;
            $data['delay_time'] = TIMESTAMP;
            $update = $model_order->editOrder($data,$condition);
            if (!$update) {
                throw new Exception('操作失败');
            }
            $model_order->commit();
        } catch (Exception $e) {
            $model_order->rollback();
            return callback(false,$e->getMessage());
        }

        //更新表发货信息
        if ($post['shipping_express_id'] && $order_info['extend_order_common']['reciver_info']['dlyp']) {
            $data = array();
            $data['shipping_code'] = $post['shipping_code'];
            $data['order_sn'] = $order_info['order_sn'];
            $express_info = Model('express')->getExpressInfo(intval($post['shipping_express_id']));
            $data['express_code'] = $express_info['e_code'];
            $data['express_name'] = $express_info['e_name'];
            Model('delivery_order')->editDeliveryOrder($data,array('order_id' => $order_info['order_id']));
        }

        //添加订单日志
        $data = array();
        $data['order_id'] = intval($_GET['order_id']);
        $data['log_role'] = 'seller';
        $data['log_user'] = $_SESSION['member_name'];
        $data['log_msg'] = '发出了货物 ( 编辑了发货信息 )';
        $data['log_orderstate'] = ORDER_STATE_SEND;
        $model_order->addOrderLog($data);
        // 获取当前物流信息
        $shipping_express_id = $data['shipping_express_id'];
        $shipping_code = $data['shipping_code'];
        $now_express_info = array();
        $express_list  = ($h = H('express')) ? $h : H('express',true);
        if (!empty($express_list) && !empty($express_list[$shipping_express_id])) {
            $now_express_info = $express_list[$shipping_express_id];
        }
        $now_express_info['shipping_code'] = $shipping_code;

        // 发送买家消息
        $param = array();
        $param['code'] = 'order_deliver_success';
        $param['member_id'] = $order_info['buyer_id'];
        $param['param'] = array(
            'order_sn' => $order_info['order_sn'],
            'order_url' => urlShop('userorder', 'show_order', array('order_id' => $order_id)),

            'first' => '亲，宝贝已经启程了，好想快点来到你身边',
            'keyword1' => $order_info['order_sn'],
            'keyword2' => (isset($now_express_info['e_name']) && $now_express_info['e_name']) ? $now_express_info['e_name'] : '无',
            'keyword3' => $now_express_info['shipping_code'] ? $now_express_info['shipping_code'] : '-',
            'remark' => '点击查看详情',
                        
            'url' => WAP_SITE_URL.'/cwap_order_detail.html?order_id='.$order_id
        );
        $param['system_type']=1;
        $param['link'] = WAP_SITE_URL.'/cwap_order_detail.html?order_id='.$order_id;
        QueueClient::push('sendMemberMsg', $param);

        return callback(true,'操作成功');
    }

    /**
     * 收到货款 王强标记购买成功总方法
     * @param array $order_info
     * @param string $role 操作角色 buyer、seller、admin、system 分别代表买家、商家、管理员、系统
     * @param string $user 操作人
     * @return array
     */
    public function changeOrderReceivePay($order_list, $role, $user = '', $post = array()) {
        $model_order = Model('order');

        try {
            $model_order->beginTransaction();

            $data = array();
            $data['api_pay_state'] = 1;
            $update = $model_order->editOrderPay($data,array('pay_sn'=>$order_list[0]['pay_sn']));
            //$update = $model_order->editOrderPay($data,array('pay_sn'=>$order_info['pay_sn']));
            if (!$update) {
                throw new Exception('更新支付单状态失败');
            }

            $model_pd = Model('predeposit');
            foreach($order_list as $order_info) {
                $order_id = $order_info['order_id'];
                if ($order_info['order_state'] != ORDER_STATE_NEW) continue;
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
            $update_order['payment_time'] = ($post['payment_time'] ? strtotime($post['payment_time']) : TIMESTAMP);
            $update_order['payment_code'] = $post['payment_code'];
            $update = $model_order->editOrder($update_order,array('pay_sn'=>$order_info['pay_sn'],'order_state'=>ORDER_STATE_NEW));

            if (!$update) {
                throw new Exception('操作失败');
            }

                //支付成功拼团处理
                if ($order_info['pin_id']) {
                    //取消本活动的其他的未付款订单
                    $wqre = M('pin')->paidPin($order_info);
                    if (!$wqre['succ']) {
                        throw new Exception($wqre['msg']);
                    }
        }

            //防止重复发送消息
            if ($order_info['order_state'] != ORDER_STATE_NEW) continue;
            // 支付成功发送买家消息
            $param = array();
            $param['code'] = 'order_payment_success';
            $param['member_id'] = $order_info['buyer_id'];
            $param['param'] = array(
                    'order_sn' => $order_info['order_sn'],
                    'order_url' => urlShop('userorder', 'show_order', array('order_id' => $order_info['order_id'])),

                    'first' => '您有一笔订单支付成功',
                    'keyword1' => $order_info['order_sn'],
                    'keyword2' => date('Y年m月d日 H时i分',time()),
                    'keyword3' => sldPriceFormat($order_info['order_amount']),
                    'remark' => '如有问题，请联系我们',

                    'url' => WAP_SITE_URL.'/cwap_order_detail.html?order_id='.$order_info['order_id']
            );
            $param['system_type']=2;
            $param['link']=WAP_SITE_URL.'/cwap_order_detail.html?order_id='.$order_info['order_id'];
            QueueClient::push('sendMemberMsg', $param);

            //极光推送商户——新订单提醒
            //获取seller_name
//            $seller_info_jpush = Model('seller')->getSellerInfo(array('vid'=>$order_info['vid']));
//            $jpush = Logic('sld_jpush');
//            if($jpush->sld_check_jpush_isopen() ){
//                $jpush->send_special_detail_url('您有一笔新订单，请及时查看',['type'=>'pay_success','order_id'=>$order_info['order_id'],'order_sn'=>$order_info['order_sn']],[$seller_info_jpush['seller_name']]);
//            }

            // 支付成功发送店铺消息
            $params = array();
            $params['code'] = 'new_order';
            $params['vid'] = $order_info['vid'];
            $params['param'] = array(
                    'order_sn' => $order_info['order_sn']
            );
            QueueClient::push('sendStoreMsg', $params);
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

            //添加订单日志
            $data = array();
            $data['order_id'] = $order_id;
            $data['log_role'] = $role;
            $data['log_user'] = $user;
            $data['log_msg'] = '收到了货款 ( 支付平台交易号 : '.$post['trade_no'].' )';
            if($order_info['dian_id']>0) { //门店修改待自提
                $data['log_orderstate'] = ORDER_STATE_SEND;
            }else{
                $data['log_orderstate'] = ORDER_STATE_PAY;
            }
            $model_order->addOrderLog($data);            
        }
            $model_order->commit();
        } catch (Exception $e) {
            $model_order->rollback();
            return callback(false,$e->getMessage());
        }

        return callback(true,'操作成功');
    }


    public function addInviteRate($order_info){
        $model_order = Model('order');
        $invite_info=Model('member')->table('member')->where(array('member_id'=>$order_info['buyer_id']))->find();
        $invite_money=0;
        //取得佣金金额
        $field = 'SUM(ROUND(goods_num*invite_rates)) as commis_amount';
        $order_goods_condition['order_id'] = $order_info['order_id'];
        $order_goods_condition['buyer_id'] = $order_info['buyer_id'];
        $order_goods_info = $model_order->getOrderGoodsInfo($order_goods_condition,$field);
        $commis_rate_totals_array[] = $order_goods_info['commis_amount'];
        $commis_amount_sum=floatval(array_sum($commis_rate_totals_array));

        if($commis_amount_sum>0)
        {
            $invite_money=$commis_amount_sum;
            $invite_money2 = ceil($commis_amount_sum *0.01);
            $invite_money3 = ceil($commis_amount_sum *0.01);
        }
        //检测是否货到付款方式
        $is_offline=($order_info['payment_code']=="offline");
        $model_member = Model('member');
        //取得一级推荐会员
        $invite_one_id = $model_member->table('member')->getfby_member_id($invite_info['member_id'],'invite_one');
        $invite_one_name = $model_member->table('member')->getfby_member_id($invite_one_id,'member_name');
        //取得二级推荐会员
        $invite_two_id = $model_member->table('member')->getfby_member_id($invite_info['member_id'],'invite_two');
        $invite_two_name = $model_member->table('member')->getfby_member_id($invite_two_id,'member_name');
        //取得三级推荐会员
        $invite_three_id = $model_member->table('member')->getfby_member_id($invite_info['member_id'],'invite_three');
        $invite_three_name = $model_member->table('member')->getfby_member_id($invite_three_id,'member_name');

        if($invite_money>0&&$is_offline==false){

            //变更会员预存款
            $model_pd = Model('predeposit');
            if($invite_one_id!=0){
                $data = array();
                $data['invite_member_id'] = $order_info['buyer_id'];
                $data['member_id'] = $invite_one_id;
                $data['member_name'] = $invite_one_name;
                $data['amount'] = $invite_money;
                $data['order_sn'] = $order_info['order_sn'];
                $model_pd->changePd('order_invite',$data);}

            if($invite_two_id!=0){
                $data_pd = array();
                $data_pd['invite_member_id'] = $order_info['buyer_id'];
                $data_pd['member_id'] = $invite_two_id;
                $data_pd['member_name'] = $invite_two_name;
                $data_pd['amount'] = $invite_money2;
                $data_pd['order_sn'] = $order_info['order_sn'];
                $model_pd->changePd('order_invite',$data_pd);}

            if($invite_three_id!=0){
                $datas = array();
                $datas['invite_member_id'] = $order_info['buyer_id'];
                $datas['member_id'] = $invite_three_id;
                $datas['member_name'] = $invite_three_name;
                $datas['amount'] = $invite_money3;
                $datas['order_sn'] = $order_info['order_sn'];
                $model_pd->changePd('order_invite',$datas);}
        }
    }
    /**写入卖家预存款账号**/
    public function addStoreMony($order_info){
        $model_order = Model('order');
        $store_info=Model('vendor')->table('vendor')->where(array('vid'=>$order_info['vid']))->find();
        $vendorinfo=Model('member')->table('member')->where(array('member_id'=>$store_info['member_id']))->find();
        $refund=Model('refund_return')->table('refund_return')->where(array('order_id'=>$order_info['order_id'],'refund_state'=>3))->find();
        $seller_money=0;
        if($refund){
            $seller_money=$order_info['order_amount']-$refund['refund_amount'];
        }else{
            $seller_money=$order_info['order_amount'];
        }
        //取得拥金金额
        $field = 'SUM(ROUND(goods_pay_price*commis_rate/100,2)) as commis_amount';
        $order_goods_condition['order_id'] = $order_info['order_id'];
        $order_goods_condition['buyer_id'] = $order_info['buyer_id'];
        $order_goods_info = $model_order->getOrderGoodsInfo($order_goods_condition,$field);
        $commis_rate_totals_array[] = $order_goods_info['commis_amount'];
        $commis_amount_sum=floatval(array_sum($commis_rate_totals_array));

        if($commis_amount_sum>0)
        {
            $seller_money=$seller_money-$commis_amount_sum;
        }
        //检测是否货到付款方式
        $is_offline=($order_info['payment_code']=="offline");
        if($seller_money>0&&$is_offline==false)
        {
            //变更会员预存款
            $model_pd = Model('predeposit');
            $data = array();
            $data['msg']="";
            if($commis_amount_sum>0)
            {
                $data['msg']=$commis_amount_sum;
            }
            $data['member_id'] = $store_info['member_id'];
            $data['member_name'] = $store_info['member_name'];
            $data['amount'] = $seller_money;
            $data['pdr_sn'] = $order_info['order_sn'];
            $model_pd->changePd('seller_money',$data);
        }
    }

    //三级分销返还积分，返还积分和返利可以二选一
    private function fan_point($order_info){

        $model_fanli=Model('vendor');
        $model_member = Model('member');
        $model_goods=Model('goods');
        $model_order = Model('order');

        $order_id = $order_info['order_id'];

        $model_setting = Model('setting');
        $list_setting = $model_setting->getListSetting();
        $points_rebate_grade0=$list_setting['points_rebate_grade0']/100;//买家返利
        $points_rebate_grade1=$list_setting['points_rebate_grade1']/100;//一级返利
        $points_rebate_grade2=$list_setting['points_rebate_grade2']/100;//二级返利
        $points_rebate_grade3=$list_setting['points_rebate_grade3']/100;//三级返利


        $order_goodslist=array();

        //获取订单商品列表方便计算返利
        $order_goods_info = $model_order->getOrderInfo(array('order_id'=>$order_id),array('order_goods'));
        $order_goodslist  = $order_goods_info['extend_order_goods'];

        $model_goodsrate=Model('vendor_bind_category');
        $store_gc_id_commis_rate=$model_goodsrate->getStoreGcidCommisRateList($order_goodslist);


        $i=0;   $fanli_jine=0; $tgzonge=0;

        $condition=array();
        $fanli_jine_arr=array();

        //array $goods_id取得订单所有商品id
        $goods_id=array();
        foreach($order_goodslist as $getkey=>$getvalue){
            $goods_id[$getkey]=$getvalue['gid'];
        }
        foreach($order_goodslist as $rkey=>$rval){
            $condition['gid']=$rval['gid'];
            $goods_info=$model_goods->getGoodsInfo($condition);
            foreach($store_gc_id_commis_rate[$order_info['vid']] as $bkey=>$bval){
                if($rval['gc_id']==$bkey){
                    $sigele_points_rebate_grade=($goods_info['goods_rebate']-$bval)/100;
                    if($sigele_points_rebate_grade<=0){
                        $sigele_points_rebate_grade=0;
                    }
                    $fanli_jine_arr[$rval['gid']]=$sigele_points_rebate_grade*($rval['goods_pay_price']-$rval['refund_amount']);
                }else{
                    continue;
                }
            }
        }

        //计算退款部分的返利比例
        $fanli_jine=array_sum($fanli_jine_arr);
        $linshiarr=array();

        //返利回买家20%===============
        $zxt_fanli=$fanli_jine*$points_rebate_grade0;
        $d_array['fanli_time']=TIMESTAMP;
        $d_array['member_id']=$order_info['buyer_id'];
        $d_array['fanli_jine']=$zxt_fanli;
        $d_array['order_id']=$order_info['order_id'];

        /*******S******************************************************/
        $findconditon=array();
        $findconditon['member_id']=$d_array['member_id'];
        $findconditon['order_id']=$order_id;
        $ifexit=$model_fanli->getfanlionly($findconditon);
        if($ifexit){//判断该订单用户id是否存在，存在就更新，不存在就新增
            $d_array['fanli_jine']=$d_array['fanli_jine']+$ifexit['fanli_jine'];
            $result=$model_fanli->editfanli($findconditon,$d_array);
        }else{
            $result=$model_fanli->addlinshidata($d_array);
        }
        /*******E******************************************************/

        if ($result<=0) {
            throw new Exception('返利买家失败');
        }


        //三级返利1级,买家推荐人===============
        $store_inviter_ida = $model_member->table('member')->getfby_member_id($order_info['buyer_id'],'inviter_id');
        if(!empty($store_inviter_ida)){
            $d_array['member_id']=$store_inviter_ida;
            $zxt_fanli=$fanli_jine*$points_rebate_grade1;
            $d_array['fanli_time']=TIMESTAMP;
            $d_array['fanli_jine']=$zxt_fanli;
            $d_array['order_id']=$order_info['order_id'];
            /*******S******************************************************/
            $findconditon=array();
            $findconditon['member_id']=$d_array['member_id'];
            $findconditon['order_id']=$d_array['order_id'];
            $ifexit=$model_fanli->getfanlionly($findconditon);
            if($ifexit){//判断该订单用户id是否存在，存在就更新，不存在就新增
                $d_array['fanli_jine']=$d_array['fanli_jine']+$ifexit['fanli_jine'];
                $result=$model_fanli->editfanli($findconditon,$d_array);
            }else{
                $result=$model_fanli->addlinshidata($d_array);
            }
            /*******E******************************************************/
            if ($result<=0) {
                throw new Exception('返利一级失败');
            }

            //三级返利2级===============
            $store_inviter_idb = $model_member->table('member')->getfby_member_id($store_inviter_ida,'inviter_id');
            if(!empty($store_inviter_idb)){
                $d_array['member_id']=$store_inviter_idb;
                $zxt_fanli=$fanli_jine*$points_rebate_grade2;
                $d_array['fanli_time']=TIMESTAMP;
                $d_array['fanli_jine']=$zxt_fanli;
                $d_array['order_id']=$order_info['order_id'];
                /*******S******************************************************/
                $findconditon=array();
                $findconditon['member_id']=$d_array['member_id'];
                $findconditon['order_id']=$d_array['order_id'];
                $ifexit=$model_fanli->getfanlionly($findconditon);
                if($ifexit){//判断该订单用户id是否存在，存在就更新，不存在就新增
                    $d_array['fanli_jine']=$d_array['fanli_jine']+$ifexit['fanli_jine'];
                    $result=$model_fanli->editfanli($findconditon,$d_array);
                }else{
                    $result=$model_fanli->addlinshidata($d_array);
                }
                /*******E******************************************************/
                if ($result<=0) {
                    throw new Exception('返利二级失败');
                }

                //三级返利3级===============
                $store_inviter_idc = $model_member->table('member')->getfby_member_id($store_inviter_idb,'inviter_id');
                if(!empty($store_inviter_idc)){
                    $d_array['member_id']=$store_inviter_idc;
                    $zxt_fanli=$fanli_jine*$points_rebate_grade3;
                    $d_array['fanli_time']=TIMESTAMP;
                    $d_array['fanli_jine']=$zxt_fanli;
                    $d_array['order_id']=$order_info['order_id'];
                    /*******S******************************************************/
                    $findconditon=array();
                    $findconditon['member_id']=$d_array['member_id'];
                    $findconditon['order_id']=$d_array['order_id'];
                    $ifexit=$model_fanli->getfanlionly($findconditon);
                    if($ifexit){//判断该订单用户id是否存在，存在就更新，不存在就新增
                        $d_array['fanli_jine']=$d_array['fanli_jine']+$ifexit['fanli_jine'];
                        $result=$model_fanli->editfanli($findconditon,$d_array);
                    }else{
                        $result=$model_fanli->addlinshidata($d_array);
                    }
//        /*******E******************************************************/
                    if ($result<=0) {
                        throw new Exception('返利三级失败');
                    }
                }
            }
        }
        //更新订单状态
        $update_order = array();
        $update_order['finnshed_time'] = TIMESTAMP;
        $update_order['order_state'] = ORDER_STATE_SUCCESS;
        $update = $model_order->editOrder($update_order,array('order_id'=>$order_id));
        if (!$update) {
            throw new Exception('保存失败');
        }

        //发放优惠券：推荐
        M('red')->SendRedInvite($order_info['buyer_id']);

        //添加订单日志
        $data = array();
        $data['order_id'] = $order_id;
        $data['log_role'] = 'buyer';
        $data['log_msg'] = '签收了货物';
        $data['log_user'] = $user;
        if ($msg) {
            $data['log_msg'] .= ' ( '.$msg.' )';
        }
        $data['log_orderstate'] = ORDER_STATE_SUCCESS;
        $model_order->addOrderLog($data);

        //添加会员积分
        if (C('points_isuse') == 1){
            Model('points')->savePointsLog('order',array('pl_memberid'=>$order_info['buyer_id'],'pl_membername'=>$order_info['buyer_name'],'orderprice'=>$order_info['order_amount'],'order_sn'=>$order_info['order_sn'],'order_id'=>$order_info['order_id']),true);
        }
        //添加会员经验值
        Model('growthvalue')->saveGrowthValue('order',array('exp_memberid'=>$order_info['buyer_id'],'exp_membername'=>$order_info['buyer_name'],'orderprice'=>$order_info['order_amount'],'order_sn'=>$order_info['order_sn'],'order_id'=>$order_info['order_id']),true);
        //邀请人获得返利积分
        $model_member = Model('member');
        $inviter_id = $model_member->table('member')->getfby_member_id($member_id,'inviter_id');
        $inviter_name = $model_member->table('member')->getfby_member_id($inviter_id,'member_name');
        $rebate_amount = ceil(0.01 * $order_info['order_amount'] * $GLOBALS['setting_config']['points_rebate']);
        Model('points')->savePointsLog('rebate',array('pl_memberid'=>$inviter_id,'pl_membername'=>$inviter_name,'rebate_amount'=>$rebate_amount),true);

        return callback(true,'操作成功');

    }


}