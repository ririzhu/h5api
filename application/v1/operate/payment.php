<?php
/**
 * 支付行为
 *
 */
defined('DYMall') or exit('Access Invalid!');
class payment {

    /**
     * 取得实物订单所需支付金额等信息
     * @param int $pay_sn
     * @param int $member_id
     * @return array
     */
    public function getRealOrderInfo($pay_sn, $member_id = null) {
    
        //验证订单信息
        $model_order = Model('order');
        $condition = array();
        $condition['pay_sn'] = $pay_sn;
        if (!empty($member_id)) {
            $condition['buyer_id'] = $member_id;
        }
        $order_pay_info = $model_order->getOrderPayInfo($condition);

        if(empty($order_pay_info)){
            $model_pd = Model('predeposit');
            $order_pay_info = $model_pd->getPdRechargeInfo(array('pdr_sn'=>$pay_sn,'pdr_member_id'=>$member_id));
            if(empty($order_pay_info) || $order_pay_info['pdr_payment_state'] == 1){
                return callback(false,'该支付单不存在');
            }else{
                $order_pay_info['subject'] = '预存款充值_'.$order_pay_info['pdr_sn'];
                $order_pay_info['order_type'] = 'predeposit';
                $order_pay_info['pay_sn'] = $order_pay_info['pdr_sn'];
                $order_pay_info['api_pay_amount'] = $order_pay_info['pdr_amount'];
            }
        }else{

            $order_pay_info['subject'] = '实物订单_'.$order_pay_info['pay_sn'];
            $order_pay_info['order_type'] = 'real_order';

            $condition = array();
            $condition['pay_sn'] = $pay_sn;
            $order_list = $model_order->getOrderList($condition);
            //计算本次需要在线支付的订单总金额
            $pay_amount = 0;
            if (!empty($order_list)) {
                foreach ($order_list as $order_info) {
                    $pay_amount += sldPriceFormat(floatval($order_info['order_amount']) - floatval($order_info['pd_amount']));
                }            
            }

            $order_pay_info['api_pay_amount'] = $pay_amount;
            $order_pay_info['order_list'] = $order_list;
        }

        return callback(true,'',$order_pay_info);
    }

    /**
     * 取得虚拟订单所需支付金额等信息
     * @param int $order_sn
     * @param int $member_id
     * @return array
     */
    public function getVrOrderInfo($order_sn, $member_id = null) {
    
        //验证订单信息
        $model_order = Model('vr_order');
        $condition = array();
        $condition['order_sn'] = $order_sn;
        if (!empty($member_id)) {
            $condition['buyer_id'] = $member_id;
        }
        $order_info = $model_order->getOrderInfo($condition);
        if(empty($order_info)){
            return callback(false,'该订单不存在');
        }

        $order_info['subject'] = '虚拟订单_'.$order_sn;
        $order_info['order_type'] = 'vr_order';
        $order_info['pay_sn'] = $order_sn;

        //计算本次需要在线支付的订单总金额
        $pay_amount = sldPriceFormat(floatval($order_info['order_amount']) - floatval($order_info['pd_amount']));

        $order_info['api_pay_amount'] = $pay_amount;
    
        return callback(true,'',$order_info);
    }

    /**
     * 取得充值单所需支付金额等信息
     * @param int $pdr_sn
     * @param int $member_id
     * @return array
     */
    public function getPdOrderInfo($pdr_sn, $member_id = null) {

        $model_pd = Model('predeposit');
        $condition = array();
        $condition['pdr_sn'] = $pdr_sn;
        if (!empty($member_id)) {
            $condition['pdr_member_id'] = $member_id;
        }

        $order_info = $model_pd->getPdRechargeInfo($condition);
        if(empty($order_info)){
            return callback(false,'该订单不存在');
        }

        $order_info['subject'] = '预存款充值_'.$order_info['pdr_sn'];
        $order_info['order_type'] = 'pd_order';
        $order_info['pay_sn'] = $order_info['pdr_sn'];
        $order_info['api_pay_amount'] = $order_info['pdr_amount'];
        return callback(true,'',$order_info);
    }

    /**
     * 取得所使用支付方式信息
     * @param unknown $payment_code
     */
    public function getPaymentInfo($payment_code) {
        if (in_array($payment_code,array('offline','predeposit')) || empty($payment_code)) {
            return callback(false,'系统不支持选定的支付方式');
        }
        $model_payment = Model('payment');
        $condition = array();
        $condition['payment_code'] = $payment_code;
        $payment_info = $model_payment->getPaymentOpenInfo($condition);
        if(empty($payment_info)) {
            return callback(false,'系统不支持选定的支付方式');
        }

        $ijmys_file = BASE_PATH.DS.'api'.DS.'payment'.DS.$payment_info['payment_code'].DS.$payment_info['payment_code'].'.php';
        if(!file_exists($ijmys_file)){
            return callback(false,'系统不支持选定的支付方式');
        }
        require_once($ijmys_file);
        $payment_info['payment_config']	= unserialize($payment_info['payment_config']);

        return callback(true,'',$payment_info);
    }

    /**
     * 支付成功后修改实物订单状态
     */
    public function updateRealOrder($out_trade_no, $payment_code, $order_list, $trade_no) {
        $post['payment_code'] = $payment_code;
        $post['trade_no'] = $trade_no;
        return Logic('order')->changeOrderReceivePay($order_list, 'system', '系统', $post);
    }

    /**
     * 支付成功后修改虚拟订单状态
     */
    public function updateVrOrder($out_trade_no, $payment_code, $order_info, $trade_no) {
        $post['payment_code'] = $payment_code;
        $post['trade_no'] = $trade_no;
        return Logic('vr_order')->changeOrderStatePay($order_info, 'system', $post);
    }

    /**
     * 支付成功后修改充值订单状态
     * @param unknown $out_trade_no
     * @param unknown $trade_no
     * @param unknown $payment_info
     * @throws Exception
     * @return multitype:unknown
     */
    public function updatePdOrder($out_trade_no,$trade_no,$payment_info,$recharge_info) {

        $condition = array();
        $condition['pdr_sn'] = $recharge_info['pdr_sn'];
        $condition['pdr_payment_state'] = 0;
        $update = array();
        $update['pdr_payment_state'] = 1;
        $update['pdr_payment_time'] = TIMESTAMP;
        $update['pdr_payment_code'] = $payment_info['payment_code'];
        $update['pdr_payment_name'] = $payment_info['payment_name'];
        $update['pdr_trade_sn'] = $trade_no;

        $model_pd = Model('predeposit');
        try {
            $model_pd->beginTransaction();
			$pdnum=$model_pd->getPdRechargeCount(array('pdr_sn'=>$recharge_info['pdr_sn'],'pdr_payment_state'=>1));
			if (intval($pdnum)>0) {
                throw new Exception('订单已经处理');
            }
            //更改充值状态
            $state = $model_pd->editPdRecharge($update,$condition);
            if (!$state) {
                throw new Exception('更新充值状态失败');
            }
            //变更会员预存款
            $data = array();
            $data['member_id'] = $recharge_info['pdr_member_id'];
            $data['member_name'] = $recharge_info['pdr_member_name'];
            $data['amount'] = $recharge_info['pdr_amount'];
            $data['pdr_sn'] = $recharge_info['pdr_sn'];
            $model_pd->changePd('recharge',$data);
            $model_pd->commit();
            return callback(true);

        } catch (Exception $e) {
            $model_pd->rollback();
            return callback(false,$e->getMessage());
        }
    }

    /**
     * 支付回调业务处理程序
     *
     * @param string $passback_params   回传参数 用于判断订单类型
     * @param string $out_trade_no      pay_sn
     * @param float $total_amount       异步通知的订单总金额
     * @param array $extend_data        预留参数
     *
     *
     */
    public function notifyProcessing($passback_params,$out_trade_no,$total_amount,$extend_data=array())
    {
        switch ($passback_params) {
            case 'product_buy':
                // 实物订单业务处理
                return $this->productBuyProcessing($out_trade_no,$total_amount,$extend_data);
                break;
            case 'ldj_product_buy':
                // 实物订单业务处理
                return $this->ldj_productBuyProcessing($out_trade_no,$total_amount,$extend_data);
                break;
            case 'pin_product_buy':
                return $this->pin_productBuyProcessing($out_trade_no,$total_amount,$extend_data);
                break;
            case 'pre_product_buy':
                return $this->pre_productBuyProcessing($out_trade_no,$total_amount,$extend_data);
                break;
            case 'predeposit':
                // 充值业务处理
                return $this->predepositProcessing($out_trade_no,$total_amount,$extend_data);
                break;
        }
    }

    /**
     * 实物订单业务处理
     *
     * @param string $out_trade_no      pay_sn
     * @param float $total_amount       异步通知的订单总金额
     * @param array $extend_data        预留参数
     *
     *
     */
    public function productBuyProcessing($out_trade_no,$total_amount,$extend_data=array())
    {
        $check_flag = true;
        $code = 200;

        $model_order = Model('order');
        $model_payment = Model('payment');

        // 校验订单号 是否合法
        // 总金额 合法至 订单号合法

        //取得订单列表和API支付总金额
        $order_list = $model_order->getOrderList(array('pay_sn'=>$out_trade_no,'order_state'=>$extend_data['ORDER_STATE_NEW']));
        if (empty($order_list)){
            $check_flag = false;
            $code = 256;
        }
        // 校验 total_amount 是否合法（是否为该订单的支付金额）
        if ($check_flag) {
            $pay_amount = 0;
            foreach($order_list as $order_info) {
                $pay_amount += sldPriceFormat(floatval($order_info['order_amount']) - floatval($order_info['pd_amount']));
            }
            $check_flag = ($pay_amount*1 == $total_amount*1);
            $code = $check_flag ? 200 : 255;
        }

        if ($check_flag) {
            // 验证是否已经支付完成
            $order_pay_info = $model_order->getOrderPayInfo(array('pay_sn'=>$out_trade_no));
            if(!is_array($order_pay_info) || empty($order_pay_info)){
                $check_flag = false;
                $code = 255;
            }
            if (intval($order_pay_info['api_pay_state'])){
                // 已完成支付 不再进行业务处理 直接返回
                $check_flag = false;
                $code = 256;
            }
        }

        if ($check_flag && $code == 200) {
            // 订单检查通过 进行业务处理更新数据
            $result = $model_payment->updateProductBuy($out_trade_no, $extend_data['payment_code'], $order_list, $extend_data['trade_no']);
            if(!empty($result['error'])) {
                $check_flag = false;
                $code = 255;
            }else{
                // 支付成功发送店铺消息
                //根据pay_sn获取订单的信息
                $order_info_new = $model_order->getOrderInfo(array('pay_sn'=>$order_pay_info['pay_sn']));
                $param = array();
                $param['code'] = 'new_order';
                $param['vid'] = $order_info_new['vid'];
                $param['param'] = array(
                    'order_sn' => $order_info_new['order_sn']
                );
                QueueClient::push('sendStoreMsg', $param);
            }
        }

        $return_data = array(
                    'code' => $code,
                );

        return $return_data;
    }
    /*
     * 阶梯拼团回调
     */
    public function pin_productBuyProcessing($out_trade_no,$total_amount,$extend_data=array())
    {
        $check_flag = true;
        $code = 200;

        $model_order = Model('order');
        $model_payment = Model('payment');

        // 校验订单号 是否合法
        // 总金额 合法至 订单号合法
        $order_sn_info = explode('_',$out_trade_no);
        $order_sn = $order_sn_info[0];
        $order_type = end($order_sn_info);
        //取得订单列表和API支付总金额
        $order_list = $model_order->table('pin_order')->where(['order_sn'=>$order_sn])->find();
        if (empty($order_list)){
            $check_flag = false;
            $code = 256;
        }
        if($order_type == 1){
            // 校验 total_amount 是否合法（是否为该订单的支付金额）
            if ($check_flag) {
                $pay_amount = 0;
                $pay_amount += sldPriceFormat($order_list['goods_price'] * $order_list['goods_num']);
                $check_flag = ($pay_amount*1 == $total_amount*1);
                $code = $check_flag ? 200 : 255;
            }

            if ($check_flag) {
                // 验证是否已经支付完成
                if($order_list['order_state']==20 || $order_list['order_state']==30){
                    $check_flag = false;
                    $code = 256;
                }
            }
            if ($check_flag && $code == 200) {
                // 订单检查通过 进行业务处理更新数据
                $order_model = M('ladder_order','pin_ladder');
                $order_info = $model_order->table('pin_order')->where(['order_sn'=>$order_sn])->find();
                try {//修改订单状态
                    $order_model->begintransaction();
                    $update = [
                        'order_state' => 20,
                        'first_time' => time(),
                        'payment_code' =>$extend_data['payment_code'] ,
                    ];
                    $res_update = $order_model->editorder(['order_sn' => $order_sn], $update);
                    if (!$res_update) {
                        throw new  Exception('支付失败');
                    }
                    //插入阶梯团的队伍表
                    $insert = [
                        'sld_gid' => $order_info['gid'],
                        'sld_order_id' => $order_info['order_id'],
                        'sld_pin_id' => $order_info['pin_id'],
                        'sld_user_id' => $order_info['buyer_id'],
                        'sld_add_time' => time()
                    ];

                    $res_insert = $model_order->table('pin_team_user_ladder')->insert($insert);
                    if (!$res_insert) {
                        throw new Exception('支付失败');
                    }
                    $order_model->commit();
                } catch (Exception $e) {
                    $code = 255;
                    $order_model->rollback();
                }
            }
        }else if($order_type == 2){
            if ($check_flag) {
                $pay_amount = 0;
                $pay_amount += sldPriceFormat($order_list['order_amount']);
                $check_flag = ($pay_amount*1 == $total_amount*1);
                $code = $check_flag ? 200 : 255;
            }
            if ($check_flag) {
                // 验证是否已经支付完成
                if($order_list['order_state']==30){
                    $check_flag = false;
                    $code = 256;
                }
            }

            if ($check_flag && $code == 200) {
                // 订单检查通过 进行业务处理更新数据
                //由于逻辑过长,单独一个方法触发
                $res = M('ladder_buy','pin_ladder')->handleordertype_2($out_trade_no,$extend_data);
                if(!$res){
                    $code = 255;
                }
            }
        }else{
            $check_flag = false;
            $code = 255;
        }

        $return_data = array(
            'code' => $code,
        );

        return $return_data;
    }

    /*
     * 预售回调
     */
    public function pre_productBuyProcessing($out_trade_no,$total_amount,$extend_data=array())
    {
        $check_flag = true;
        $code = 200;

        $model_order = Model('order');
        $model_payment = Model('payment');

        // 校验订单号 是否合法
        // 总金额 合法至 订单号合法
        $order_sn_info = explode('_',$out_trade_no);
        $order_sn = $order_sn_info[0];
        $order_type = end($order_sn_info);
        //取得订单列表和API支付总金额
        $order_list = $model_order->table('pre_order')->where(['order_sn'=>$order_sn])->find();
        if (empty($order_list)){
            $check_flag = false;
            $code = 256;
        }
        //定金
        if($order_type == 1){
            // 校验 total_amount 是否合法（是否为该订单的支付金额）
            if ($check_flag) {
                $pay_amount = 0;
                $pay_amount += sldPriceFormat($order_list['goods_price'] * $order_list['goods_num']);
                $check_flag = ($pay_amount*1 == $total_amount*1);
                $code = $check_flag ? 200 : 255;
            }

            if ($check_flag) {
                // 验证是否已经支付完成
                if($order_list['order_state']==20 || $order_list['order_state']==30){
                    $check_flag = false;
                    $code = 256;
                }
            }
            if ($check_flag && $code == 200) {
                // 订单检查通过 进行业务处理更新数据
                $order_model = M('pre_order','presale');
                $order_info = $order_list;
                try {
                    //修改订单状态
                    $order_model->begintransaction();
                    $update = [
                        'order_state' => 20,
                        'first_time' => time(),
                        'payment_code' =>$extend_data['payment_code'] ,
                    ];
                    $res_update = $order_model->edit(['order_sn' => $order_sn], $update);
                    if (!$res_update) {
                        throw new  Exception('支付失败');
                    }
                    //取消这个活动未付款的订单
                    M('pre_buy','presale')->pre_cancelorder(['buyer_id'=>$order_info['buyer_id'],'order_state'=>10,'pre_id'=>$order_info['pre_id'],'gid'=>$order_info['gid']]);
                    $order_model->commit();
                } catch (Exception $e) {
                    $code = 255;
                    $order_model->rollback();
                }
            }
        }else if($order_type == 2){
            //尾款
            if ($check_flag) {
                $pay_amount = 0;
                $pay_amount += sldPriceFormat($order_list['goods_price_finish'] * $order_list['goods_num']);
                $check_flag = ($pay_amount*1 == $total_amount*1);
                $code = $check_flag ? 200 : 255;
            }
            if ($check_flag) {
                // 验证是否已经支付完成
                if($order_list['order_state']==30){
                    $check_flag = false;
                    $code = 256;
                }
            }

            if ($check_flag && $code == 200) {
                // 订单检查通过 进行业务处理更新数据
                //由于逻辑过长,单独一个方法触发
                $res = M('pre_buy','presale')->handleordertype_2($out_trade_no,$extend_data);
                if(!$res){
                    $code = 255;
                }
            }
        }else{
            $check_flag = false;
            $code = 255;
        }

        $return_data = array(
            'code' => $code,
        );

        return $return_data;
    }

    public function ldj_productBuyProcessing($out_trade_no,$total_amount,$extend_data=array())
    {
        $check_flag = true;
        $code = 200;

        $model_order = Model('order');
        $model_payment = Model('payment');

        // 校验订单号 是否合法
        // 总金额 合法至 订单号合法

        //取得订单列表和API支付总金额
        $order_list = $model_order->table('ldj_order')->where(['pay_sn'=>$out_trade_no,'order_state'=>$extend_data['ORDER_STATE_NEW']])->select();

        if (empty($order_list)){
            $check_flag = false;
            $code = 256;
        }
        // 校验 total_amount 是否合法（是否为该订单的支付金额）
        if ($check_flag) {
            $pay_amount = 0;
            foreach($order_list as $order_info) {
                $pay_amount += sldPriceFormat(floatval($order_info['order_amount']) - floatval($order_info['pd_amount']));
            }
            $check_flag = ($pay_amount*1 == $total_amount*1);
            $code = $check_flag ? 200 : 255;
        }
        if ($check_flag) {
            // 验证是否已经支付完成
            $order_pay_info = $model_order->table('ldj_order_pay')->where(array('pay_sn'=>$out_trade_no))->find();
            if(!is_array($order_pay_info) || empty($order_pay_info)){
                $check_flag = false;
                $code = 255;
            }
            if (intval($order_pay_info['api_pay_state'])){
                // 已完成支付 不再进行业务处理 直接返回
                $check_flag = false;
                $code = 256;
            }
        }
        if ($check_flag && $code == 200) {
            // 订单检查通过 进行业务处理更新数据
            $order_model = M('ldj_order','ldj');
            try{
                $order_model->begintransaction();
                //订单状态 置为已支付
                $data_order = array();
                $data_order['order_state'] = ORDER_STATE_SUCCESS;
                $data_order['payment_time'] = time();
                $data_order['finnshed_time'] = time();
                $data_order['payment_code'] = $extend_data['payment_code'];
                $result = $order_model->editOrder(array('pay_sn'=>$out_trade_no),$data_order);
                $result1 = $order_model->table('ldj_order_pay')->where(array('pay_sn'=>$out_trade_no))->update(['api_pay_state'=>1]);
                if($result && $result1){

                    $order_model->commit();
                }else{
                    $order_model->rollback();
                    throw new Exception('修改失败');
                }

            }catch(Exception $e){
                $order_model->rollback();
                $code = 255;
            }
//            $result = $model_payment->updateProductBuy($out_trade_no, $extend_data['payment_code'], $order_list, $extend_data['trade_no']);
            if(!$result) {
                $check_flag = false;
                $code = 255;
            }else{
                // 支付成功发送店铺消息
                //根据pay_sn获取订单的信息
//                $order_info_new = $model_order->getOrderInfo(array('pay_sn'=>$order_pay_info['pay_sn']));
//                $param = array();
//                $param['code'] = 'new_order';
//                $param['vid'] = $order_info_new['vid'];
//                $param['param'] = array(
//                    'order_sn' => $order_info_new['order_sn']
//                );
//                QueueClient::push('sendStoreMsg', $param);
//                //发送门店提醒
//                if($order_info_new['dian_id']>0){
//                    $param = array();
//                    $param['code'] = 'dian_new_order';
//                    $param['vid'] = $order_info_new['dian_id'];
//                    $param['param'] = array(
//                        'order_sn' => $order_info_new['order_sn']
//                    );
//                    QueueClient::push('sendDianMsg', $param);
//                }
//
//                //如果是门店订单 通知门店
//                if($order_info_new['dian_id']>0) {
//                    $param = array();
//                    $param['code'] = 'dian_new_order';
//                    $param['vid'] = $order_info_new['dian_id'];
//                    $param['param'] = array(
//                        'order_sn' => $order_info_new['order_sn']
//                    );
//                    QueueClient::push('sendDianMsg', $param);
//                }
            }
        }
        $return_data = array(
            'code' => $code,
        );

        return $return_data;
    }
    /**
     * 充值业务处理
     *
     * @param string $out_trade_no      pay_sn
     * @param float $total_amount       异步通知的订单总金额
     * @param array $extend_data        预留参数
     *
     *
     */
    public function predepositProcessing($out_trade_no,$total_amount,$extend_data=array())
    {
        $check_flag = true;
        $code = 200;

        $model_pd = Model('predeposit');
        
        // 校验订单号 是否合法
        // 总金额 合法至 订单号合法

        //预存款充值
        $order_pay_info = $model_pd->getPdRechargeInfo(array('pdr_sn'=>$out_trade_no));
        if (!is_array($order_pay_info) || empty($order_pay_info)){
            $check_flag = false;
            $code = 255;
        }
        if ($check_falg && intval($order_pay_info['pdr_payment_state'])){
            $check_flag = false;
            $code = 256;
        }

        if ($check_flag && $code == 200) {
            //预存款充值
            $condition = array();
            $condition['pdr_sn'] = $out_trade_no;
            $condition['pdr_payment_state'] = 0;
            $recharge_info = $model_pd->getPdRechargeInfo($condition);
            if (!$recharge_info) {
                $check_flag = false;
                $code = 255;
            }
            if ($check_flag && $code == 200) {
                
                $condition = array();
                $condition['pdr_sn'] = $recharge_info['pdr_sn'];
                $condition['pdr_payment_state'] = 0;
                $update = array();
                $update['pdr_payment_state'] = 1;
                $update['pdr_payment_time'] = TIMESTAMP;
                $update['pdr_payment_code'] = $extend_data['payment_code'];
                $update['pdr_payment_name'] = $extend_data['payment_name'];
                $update['pdr_trade_sn'] = $extend_data['trade_no'];

                try {
                    $model_pd->beginTransaction();
                    //更改充值状态
                    $state = $model_pd->editPdRecharge($update,$condition);
                    if (!$state) {
                        throw new Exception(255);
                    }
                    //变更会员预存款
                    $data = array();
                    $data['member_id'] = $recharge_info['pdr_member_id'];
                    $data['member_name'] = $recharge_info['pdr_member_name'];
                    $data['amount'] = $recharge_info['pdr_amount'];
                    $data['pdr_sn'] = $recharge_info['pdr_sn'];
                    $model_pd->changePd('recharge',$data);
                    $model_pd->commit();
                    
                    $check_flag = true;
                    $code = 200;
                } catch (Exception $e) {
                    $model_pd->rollback();
                    $check_flag = false;
                    $code = 255;
                }
            }
        }

        $return_data = array(
                    'code' => $code,
                );

        return $return_data;

    }
    
    // 获取微信支付的所有支付配置
    public function getWxpayAllConfig()
    {
        $model_payment = Model('payment');
        $condition = array('payment_code'=>'wxpay');
        $saoma_payment = $model_payment->getPaymentOpenInfo($condition);
        if (!empty($saoma_payment)) {
            $payment_other_data['saoma'] = unserialize($saoma_payment['payment_config']);
        }

        // 移动端
        $mb_model_payment = Model('mb_payment');
        $mb_condition['payment_code'] = array('IN',array('mini_wxpay','weixin','wxpay_jsapi'));
        $mb_payment_data = $mb_model_payment->getMbPaymentOpenList($mb_condition);
        foreach ($mb_payment_data as $key => $value) {
            switch ($value['payment_code']) {
                case 'mini_wxpay':
                    $payment_other_data['mini'] = unserialize($value['payment_config']);
                    break;
                case 'wxpay_jsapi':
                    $payment_other_data['jspai'] = unserialize($value['payment_config']);
                    break;
                default:
                    $payment_other_data[$value['payment_code']] = unserialize($value['payment_config']);
                    break;
            }
        }

        return $payment_other_data;
    }

}