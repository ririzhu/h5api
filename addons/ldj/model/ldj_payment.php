<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/10/15
 * Time: 22:05
 */
class ldj_paymentModel extends Model
{
    public function __construct()
    {
        parent::__construct('ldj_payment');
    }

    public function getpaymentlist($where)
    {
        return $this->table('ldj_payment')->where($where)->select();
    }

    // 获取微信支付的所有支付配置
    public function getWxpayAllConfig()
    {
        $model_payment = Model();
        $condition = array('payment_code'=>'wxpay','payment_state'=>1);
        $saoma_payment = $model_payment->table('ldj_payment')->where($condition)->find();
        if (!empty($saoma_payment)) {
            $payment_other_data['saoma'] = unserialize($saoma_payment['payment_config']);
        }

        // 移动端
        $mb_model_payment = Model();
        $mb_condition['payment_code'] = array('IN',array('mini_wxpay','weixin','wxpay_jsapi'));
        $mb_condition['payment_state'] = 1;
        $mb_payment_data = $mb_model_payment->table('ldj_payment')->where($mb_condition)->select();
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
    /*
     * 支付获取支付方式信息
     * $where
     */
    public function getMbPaymentOpenInfo($where)
    {
        $payment_info = $this->where($where)->find();
        //return $payment_info;
        if (!empty($payment_info['payment_config'])) {
            $payment_info['payment_config'] = unserialize($payment_info['payment_config']);
        }

        if (isset($payment_info['payment_config']) && !is_array($payment_info['payment_config'])) {
            $payment_info['payment_config'] = array();
        }

        return $payment_info;
    }
}