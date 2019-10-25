<?php
namespace app\v1\model;

use Exception;
use think\Model;
use think\db;
class Payment extends Model
{
    /**
     * 开启状态标识
     * @var unknown
     */
    const STATE_OPEN = 1;

    public function __construct() {
        parent::__construct('bbc_payment');
    }

    /**
     * 读取单行信息
     *
     * @param
     * @return array 数组格式的返回结果
     */
    public function getPaymentInfo($condition = array()) {
        return $this->where($condition)->find();
    }

    /**
     * 读开启中的取单行信息
     *
     * @param
     * @return array 数组格式的返回结果
     */
    public function getPaymentOpenInfo($condition = array()) {
        $condition['payment_state'] = self::STATE_OPEN;
        return DB::name("payment")->where($condition)->find();
    }

    /**
     * 读取多行
     *
     * @param
     * @return array 数组格式的返回结果
     */
    public function getPaymentList($condition = array()){
        return $this->where($condition)->select();
    }

    /**
     * 读取开启中的支付方式
     *
     * @param
     * @return array 数组格式的返回结果
     */
    public function getPaymentOpenList($condition = array()){
        $condition['payment_state'] = self::STATE_OPEN;
        return DB::name("payment")->where($condition)->cache('payment_code')->select();
    }

    /**
     * 更新信息
     *
     * @param array $param 更新数据
     * @return bool 布尔类型的返回结果
     */
    public function editPayment($data, $condition){
        return $this->where($condition)->update($data);
    }

    /**
     * 读取支付方式信息by Condition
     *
     * @param
     * @return array 数组格式的返回结果
     */
    public function getRowByCondition($conditionfield,$conditionvalue){
        $param	= array();
        $param['table']	= 'payment';
        $param['field']	= $conditionfield;
        $param['value']	= $conditionvalue;
        $result	= Db::getRow($param);
        return $result;
    }

    /**
     * 购买商品
     */
    public function productBuy($pay_sn, $payment_code, $member_id) {
        $condition = array();
        $condition['payment_code'] = $payment_code;
        $payment_info = $this->getPaymentOpenInfo($condition);
        if(!$payment_info) {
            return array('error' => '系统不支持选定的支付方式');
        }

        //验证订单信息
        $model_order = new UserOrder();
        $order_pay_info = $model_order->getOrderPayInfo(array('pay_sn'=>$pay_sn,'buyer_id'=>$member_id));
        if(empty($order_pay_info)){
            return array('error' => '该订单不存在');
        }
        $order_pay_info['subject'] = '商品购买_'.$order_pay_info['pay_sn'];
        $order_pay_info['order_type'] = 'product_buy';

        //重新计算在线支付且处于待支付状态的订单总额
        $condition = array();
        $condition['pay_sn'] = $pay_sn;
        $condition['order_state'] = ORDER_STATE_NEW;
        $order_list = $model_order->getOrderList($condition,1,'bbc_order.order_id,bbc_order.order_sn,bbc_order.order_amount,bbc_order.pd_amount,bbc_order.pd_points,bbc_order.order_state,bbc_order.refund_state,bbc_order.payment_code','',10);
        if (empty($order_list)) {echo 2;
            return array('error' => '该订单不存在');
        }

        //计算本次需要在线支付的订单总金额
        $pay_amount = 0;
        foreach ($order_list as $k=>$order_info) {
            $pay_amount += sldPriceFormat(floatval($order_info['order_amount']) - floatval($order_info['pd_amount']));
        }

        //如果为空，说明已经都支付过了或已经取消或者是价格为0的商品订单，全部返回
        if (empty($pay_amount)) {
            return array('error' => '订单金额为0，不需要支付');
        }
        $order_pay_info['pay_amount'] = $pay_amount;

        return(array('order_pay_info' => $order_pay_info, 'payment_info' => $payment_info));

    }

    /**
     * 购买订单支付成功后修改订单状态
     */
    public function updateProductBuy($out_trade_no, $payment_code, $order_list, $trade_no) {
        try {
            $model_order = new UserOrder();
            $model_pd = new Predeposit();
            $model_order->beginTransaction();


            $condition = array();
            $condition['pay_sn'] = $out_trade_no;
            $condition['payment_code'] = 'wxtiyan';
            $order_info	= $model_order->getOrderInfo($condition);

            if(!empty($order_info))
            {
                $data['payment_code'] = 'wxtiyan';
            }

            $data = array();
            $data['api_pay_state'] = 1;
            $update = $model_order->editOrderPay($data,array('pay_sn'=>$out_trade_no));
            if (!$update) {
                throw new Exception('更新订单状态失败');
            }

            $data = array();
            $data['order_state']	= ORDER_STATE_SUCCESS;
            $data['payment_time']	= TIMESTAMP;
            $data['payment_code']   = $payment_code;

            if(!empty($order_info))
            {
                $data['payment_code'] = 'wxtiyan';
            }

            $update = $model_order->editOrder($data,array('pay_sn'=>$out_trade_no,'order_state'=>ORDER_STATE_NEW));
            if (!$update) {
                throw new Exception('更新订单状态失败');
            }

            if(!empty($order_info))
            {
                $data['payment_code'] = 'wxtiyan';
                $member_id = $order_info['buyer_id'];
                $member_name = $order_info['buyer_name'];

                $param =array();
                $param['order_id'] = $order_info['order_id'];
                $param['vid'] = $order_info['vid'];
                $model_buy = Model('buy');
                $model_buy->payStep3($param, $member_id, $member_name, '');
            }
            foreach($order_list as $order_info) {
                //如果有预存款支付的，彻底扣除冻结的预存款
                $pd_amount = floatval($order_info['pd_amount']);
                if ($pd_amount > 0) {
                    $data_pd = array();
                    $data_pd['member_id'] = $order_info['buyer_id'];
                    $data_pd['member_name'] = $order_info['buyer_name'];
                    $data_pd['amount'] = $order_info['pd_amount'];
                    $data_pd['order_sn'] = $order_info['order_sn'];
                    $model_pd->changePd('order_comb_pay',$data_pd);
                }
                //记录订单日志
                $data = array();
                $data['order_id'] = $order_info['order_id'];
                $data['log_role'] = 'buyer';
                $data['log_msg'] = L('完成了付款').' ( 支付平台交易号 : '.$trade_no.' )';
                $data['log_orderstate'] = ORDER_STATE_SUCCESS;
                $insert = $model_order->addOrderLog($data);
                if (!$insert) {
                    throw new Exception('记录订单日志出现错误');
                }
            }
            $model_order->commit();
            return array('success' => true);
        } catch (Exception $e) {
            $model_order->rollback();
            return array('error' => $e->getMessage());
        }

    }
    //根据订单号获取订单信息
    public function getOrderInfo($order_sn){
        return $this->table('order')->field('*')->where(array('order_sn'=>$order_sn,'order_state'=>10))->find();
    }

    /**
     * add by zhengyifan 2019-10-17
     * 支付回调业务处理程序
     * @param $out_trade_no
     * @return mixed
     */
    public function notifyProcess($out_trade_no)
    {
        $order = new Order();

        $condition = [
            'pay_sn' => $out_trade_no,
            'order_state' => ORDER_STATE_NEW,
        ];
        $order_list = $order->getOrderList($condition);
        if (empty($order_list)){
            $data['code'] = 256;
            $data['message'] = '订单不存在或已支付';
            return $data;
        }

        $pay_condition = [
            'pay_sn' => $out_trade_no,
        ];
        $order_pay_info =$order->getOrderPayInfo($pay_condition);
        if (empty($order_pay_info)){
            $data['code'] = 256;
            $data['message'] = '订单不存在';
            return $data;
        }
        if ($order_pay_info['api_pay_state'] == 1){
            $data['code'] = 256;
            $data['message'] = '订单已支付';
            return $data;
        }

        $result = $this->updateBuy($out_trade_no,$order_list);
        if (isset($result['error'])){
            $data['code'] = 256;
            $data['message'] = $result['error'];
            return $data;
        }
        $data['code'] = 200;
        $data['message'] = '成功';
        return $data;
    }

    /**
     * add by zhengyifan 2019-10-17
     * 支付成功修改订单状态
     * @param $out_trade_no
     * @param $order_list
     * @return array
     */
    public function updateBuy($out_trade_no,$order_list)
    {
        try{
            $order = new Order();

            DB::startTrans();

            $order_pay_data = [
                'api_pay_state' => 1,
            ];
            $order_pay_condition = [
                'pay_sn' => $out_trade_no,
            ];
            $update =$order->editOrderPay($order_pay_data,$order_pay_condition);
            if (!$update){
                throw new Exception('更新订单状态失败');
            }

            $order_data = [
                'order_state' => ORDER_STATE_SUCCESS,
                'payment_time' => time(),
            ];
            $order_condition = [
                'pay_sn' => $out_trade_no,
                'order_state' => ORDER_STATE_NEW,
            ];
            $order_update = $order->editOrder($order_data,$order_condition);
            if (!$order_update){
                throw new Exception('更新订单状态失败');
            }

            foreach ($order_list as $key => $val){
                $log_data = [
                    'order_id' => $val['order_id'],
                    'log_role' => 'buyer',
                    'log_msg' => '完成了付款(支付平台交易号：'.$out_trade_no,
                    'log_orderstate' => ORDER_STATE_SUCCESS,
                ];
                $insert = $order->addOrderLog($log_data);
                if (!$insert) {
                    throw new Exception('记录订单日志出现错误');
                }
            }
            DB::commit();
            return ['success' => true];
        }catch (Exception $e){
            DB::rollback();
            return ['error' => $e->getMessage()];
        }
    }
}