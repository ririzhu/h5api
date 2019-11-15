<?php
namespace app\v1\controller;

use app\v1\api\payment\qpay\AppConfig;
use app\v1\api\payment\qpay\AppUtil;
use app\v1\model\Payment;

/**
 * 回调
 *
 */

class Notify extends Base
{
	public function allinpayNotify()
    {
        $params = array();
        foreach($_POST as $key=>$val) {
            $params[$key] = $val;
        }
        if (count($params) < 1){
            echo 'error';
            exit();
        }
        if ($params['retcode'] != 'SUCCESS'){
            echo 'error';
            exit();
        }
        if (!AppUtil::ValidSign($params,AppConfig::APPKEY)){
            echo 'Sign 错误';
            exit();
        }
        if (empty($params['orderid'])){
            echo 'error';
            exit();
        }
        $payment = new Payment();
        $process = $payment->notifyProcess($params['orderid']);
        if ($process['code'] == 200){
            echo 'success';
        }else{
            echo 'error';
        }
	}

}
