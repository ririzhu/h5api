<?php
/**
 * 支付
 *
 */



defined('DYMall') or exit('Access Invalid!');

class payCtl extends mobileMemberCtl {

    private $payment_code;
    private $payment_config;

    public function __construct() {
        if(!(C('promotion_allow')==1 && C('sld_pintuan_ladder') && C('pin_ladder_isuse'))){
            echo json_encode(['status'=>255,'msg'=>'当前活动尚未开启']);die;
        }
        parent::__construct();
        if($_GET['mod'] != 'payment_list' && !$_POST['payment_code']) {
            $payment_code = 'alipay';

            if(in_array($_GET['mod'], array('wx_app_pay', 'wx_app_pay3', 'wx_app_vr_pay', 'wx_app_vr_pay3'), true)) {
                $payment_code = 'wxpay';
            }
            else if (in_array($_GET['mod'],array('alipay_native_pay','alipay_native_vr_pay'),true)) {
                $payment_code = 'alipay_native';
            }
            else if (isset($_GET['payment_code'])) {
                $payment_code = $_GET['payment_code'];
            }

            $model_mb_payment = Model('mb_payment');
            $condition = array();
            $condition['payment_code'] = $payment_code;
            $mb_payment_info = $model_mb_payment->getMbPaymentOpenInfo($condition);
            if(!$mb_payment_info) {
                output_error('支付方式未开启');
            }

            $this->payment_code = $payment_code;
            $this->payment_config = $mb_payment_info['payment_config'];

        }
    }

    /**
     * 实物订单支付_app_余额支付
     */
    public function pay_new_app_predision() {
        @header("Content-type: text/html; charset=".CHARSET);
        $pay_sn = $_POST['pay_sn'];
        //验证支付密码
        if (!preg_match('/^\d{18}$/',$pay_sn)){
            echo json_encode(array('state'=>255,'msg'=>'支付单号错误'));die;
        }
        $logic_payment = Logic('payment');

        //取订单信息
        $result = $logic_payment->getRealOrderInfo($pay_sn, $this->member_info['member_id']);
        if(!$result['state']) {
            echo json_encode(array('state'=>255,'msg'=>$result['msg']));die;
        }

        //站内余额支付
        if ($_POST) {
            $result['data']['order_list'] = $this->_pd_pay($result['data']['order_list'],$_POST);


            if (empty($_POST['password'])) {
                echo json_encode(array('state'=>255,'msg'=>'支付密码不能为空'));die;
            }
            $model_member = Model('member');
            $buyer_info = $model_member->getMemberInfoByID($this->member_info['member_id']);
            if ($buyer_info['member_passwd'] == '' || $buyer_info['member_passwd'] != md5($_POST['password'])) {
                echo json_encode(array('state'=>255,'msg'=>'支付密码错误'));die;
            }

            if ($buyer_info['available_rc_balance'] == 0) {
                $_POST['rcb_pay'] = null;
            }
            if ($buyer_info['available_predeposit'] == 0) {
                $_POST['pd_pay'] = null;
            }


            try {
                $model_member->beginTransaction();
                $logic_buy_1 = Logic('buy_1');
                //使用充值卡支付
                if (!empty($post['rcb_pay'])) {
                    $order_list = $logic_buy_1->rcbPay($result['data']['order_list'], $_POST, $buyer_info);
                }

                //使用预存款支付
                if (!empty($post['pd_pay'])) {
                    $order_list = $logic_buy_1->pdPay($result['data']['order_list'], $_POST, $buyer_info);
                }

                //特殊订单站内支付处理
                $logic_buy_1->extendInPay($result['data']['order_list']);

                $model_member->commit();
            } catch (Exception $e) {
                $model_member->rollback();
                exit($e->getMessage());
            }

            $result['data']['order_list'] = $order_list;

        }

        //计算本次需要在线支付的订单总金额
        $pay_amount = 0;
        $pay_order_id_list = array();
        if (!empty($result['data']['order_list'])) {
            foreach ($result['data']['order_list'] as $order_info) {
                if ($order_info['order_state'] == ORDER_STATE_NEW) {
                    $pay_amount += $order_info['order_amount'] - $order_info['pd_amount'] - $order_info['rcb_amount'];
                    $pay_order_id_list[] = $order_info['order_id'];
                }
            }
        }

        if ($pay_amount == 0) {
            echo json_encode(array('state'=>200,'msg'=>'支付成功'));die;
        }
    }

    /**
     * 实物订单支付
     */
    public function pay_new() {
        @header("Content-type: text/html; charset=".CHARSET);
        $pay_sn = $_GET['pay_sn'];
//        if (!preg_match('/^\d{16}$/',$pay_sn)){
//            exit('支付单号错误');
//        }
        if(mb_strlen($pay_sn) != 16){
            exit('支付单号错误');
        }
        if (in_array($_GET['payment_code'],array('alipay','weixin','yinlian','wxpay_jsapi','predeposit'))) {
            $model_mb_payment = Model();
            $condition = array();
            $condition['payment_code'] = $_GET['payment_code'];
            $condition['payment_state'] = 1;
            $mb_payment_info = $model_mb_payment->table('mb_payment')->where($condition)->find();
            if(is_array($mb_payment_info) && !empty($mb_payment_info['payment_config'])){
                $mb_payment_info['payment_config'] = unserialize($mb_payment_info['payment_config']);
            }
            if(!$mb_payment_info) {
                exit('支付方式未开启');
            }
            $this->payment_code = $_GET['payment_code'];
            $this->payment_config = $mb_payment_info['payment_config'];
        } else {
            exit('支付方式提交错误');
        }

        $pay_info = $this->_get_real_order_info($pay_sn,$_GET);
        if(isset($pay_info['error'])) {
            exit($pay_info['error']);
        }

        //第三方API支付
        $this->_api_pay($pay_info['data'],$mb_payment_info);
    }

    /**
     * 支付宝app实物订单支付
     */
    public function pay_alipay_app() {
        @header("Content-type: text/html; charset=".CHARSET);
        $pay_sn = $_POST['pay_sn'];

        if (!preg_match('/^\d{18}$/',$pay_sn)){
            exit('支付单号错误');
        }
        if (in_array($_POST['payment_code'],array('alipay'))) {
            $model_mb_payment = Model('mb_payment');
            $condition = array();
            $condition['payment_code'] = $_POST['payment_code'];
            $mb_payment_info = $model_mb_payment->getMbPaymentOpenInfo($condition);
            if(!$mb_payment_info) {
//                exit('支付方式未开启');
            }

            $this->payment_code = $_POST['payment_code'];
            $this->payment_config = $mb_payment_info['payment_config'];
        }else {
            exit('支付方式提交错误');
        }
        $pay_info = $this->_get_real_order_info($pay_sn,$_POST);

        if(isset($pay_info['error'])) {
            exit($pay_info['error']);
        }
        //第三方API支付
        $this->_api_pay($pay_info['data'],$mb_payment_info,1);
        exit();

//        $param['order_amount'] = $pay_info['data']['api_pay_amount'];
        $param['order_amount'] = 0.01;
        $param['order_type'] = 'r';
        $param['subject'] = $pay_info['data']['subject'];
        $param['order_sn'] = $pay_info['data']['pay_sn'].'_'.$param['order_type'];;

        require(dirname(__FILE__).'/../api/payment/alipay_app/alipay_app.php');
        $alipay_app = new alipay_app();
        $result=$alipay_app->JXPay_Alipay_App_Pay($body = null, $subject = $param['subject'], $out_trade_no = $param['order_sn'], $timeout_express = null, $total_amount = $param['order_amount'], $goods_type = null, $passback_params = null, $promo_params = null, $extend_params = null, $enable_pay_channels = null, $disable_pay_channels = null, $store_id = null);
        if(!empty($result)){
            $alipay=explode('&',urldecode($result));
            output_data(array('status'=>1,'result'=>$result,'alipay'=>$alipay));
        }else{
            output_data(array('status'=>0));
        }
    }

    /**
     * 微信app实物订单支付
     */
    public function pay_weixin_app() {

        @header("Content-type: text/html; charset=".CHARSET);
        $pay_sn = $_POST['pay_sn'];

        if (!preg_match('/^\d{18}$/',$pay_sn)){
            exit('支付单号错误');
        }
       if (in_array($_POST['payment_code'],array('weixin'))) {
           $model_mb_payment = Model('mb_payment');
           $condition = array();
           $condition['payment_code'] = $_POST['payment_code'];
           $mb_payment_info = $model_mb_payment->getMbPaymentOpenInfo($condition);
           if(!$mb_payment_info) {
               exit('支付方式未开启');
           }

           $this->payment_code = $_POST['payment_code'];
           $this->payment_config = $mb_payment_info['payment_config'];
       }else {
           exit('支付方式提交错误');
       }
        $pay_info = $this->_get_real_order_info($pay_sn,$_POST);

        if(isset($pay_info['error'])) {
            exit($pay_info['error']);
        }
        //第三方API支付
        $this->_api_pay($pay_info['data'],$mb_payment_info,1);
        exit;

//        $param['order_amount'] = $pay_info['data']['api_pay_amount']*100;
        $param['order_amount'] = 1;
        $param['order_type'] = 'r';
        $param['subject'] = $pay_info['data']['subject'];
        $param['order_sn'] = $pay_info['data']['pay_sn'];

        require(dirname(__FILE__).'/../api/payment/weixin_app/weixin_app.php');
        $alipay_app = new weixin_app();
        $result=$alipay_app->JXPay_Wechat_App_Pay($device_info = null, $body = $param['subject'], $detail = null, $attach = null,$out_trade_no = $param['order_sn'],$fee_type = null, $total_fee = $param['order_amount'],$time_start = null, $time_expire = null, $goods_tag = null,$trade_type = null, $limit_pay = null, $scene_info = null);
        if(!empty($result)){
            $alipay=explode('&',urldecode($result));
            output_data(array('status'=>1,'result'=>$result));
        }else{
            output_data(array('status'=>0));
        }
    }

    /**
     * 实物订单支付(用于微信小程序)
     */
    public function pay_new_xcx() {
        @header("Content-type: text/html; charset=".CHARSET);
        $pay_sn = $_POST['pay_sn'];
        if (!preg_match('/^\d{18}$/',$pay_sn)){
            echo json_encode(array('state'=>250,'msg'=>'支付单号错误'));die;
        }
        $_POST['payment_code'] = $_POST['payment_code'] ? $_POST['payment_code'] : 'mini_wxpay';
        if ($_POST['payment_code'] == 'predeposit') {
            $_POST['no_redirect'] = true;
            $_POST['pd_pay'] = 1;
        }
        if (in_array($_POST['payment_code'],array('mini_wxpay','predeposit'))) {
           $model_mb_payment = Model('mb_payment');
           $condition = array();
           $condition['payment_code'] = $_POST['payment_code'];
           $mb_payment_info = $model_mb_payment->getMbPaymentOpenInfo($condition);
           if(!$mb_payment_info) {
               exit('支付方式未开启');
           }

           $this->payment_code = $_POST['payment_code'];
           $this->payment_config = $mb_payment_info['payment_config'];
        }else {
           exit('支付方式提交错误');
        }
        if ($_POST['payment_code'] == 'predeposit') {
            $pay_info = $this->_get_real_order_info($pay_sn,$_POST);
            if(isset($pay_info['error'])) {
                echo json_encode(array('state'=>250,'msg'=>$pay_info['error']));die;
            }else{
                echo json_encode(array('state'=>200,'msg'=>'支付成功'));die;
            }
        }else{
            $pay_info = $this->_get_real_order_info($pay_sn);
            if(isset($pay_info['error'])) {
                echo json_encode(array('state'=>250,'msg'=>$pay_info['error']));die;
            }

            //第三方API支付
           $this->_api_pay($pay_info['data'],$mb_payment_info);   
        }
       exit;
        $ijmys_file = BASE_PATH.DS.'api'.DS.'payment'.DS.'xcx_jsapi'.DS.'wxpay_jsapi'.'.php';
        if(!is_file($ijmys_file)){
            echo json_encode(array('state'=>250,'msg'=>'支付接口不存在'));die;
        }
        require($ijmys_file);

            $param['appId'] = C('xcx_appid');
            $param['appSecret'] = C('xcx_secret');
            $param['partnerId'] = C('xcx_partnerId');
            $param['apiKey'] = C('xcx_apiKey');
            $param['orderSn'] = $pay_info['data']['pay_sn'];
            $param['orderFee'] = (int) (100 * $pay_info['data']['api_pay_amount']);
            $param['orderInfo'] = C('site_name') . '商品订单' . $pay_info['data']['pay_sn'];
            $param['orderAttach'] = ($pay_info['data']['order_type'] == 'real_order' ? 'r' : 'v');
            $api = new wxpay_jsapi();
            $api->setConfigs($param);
        //根据用户id获取用户的openid
        $member_info = Model('member')->getMemberInfoByID($this->member_info['member_id'], $fields = 'wx_openid');
            try {
                $api->paymentHtml($this,$member_info['wx_openid']);
            } catch (Exception $ex) {
                if (C('debug')) {
                    header('Content-type: text/plain; charset=utf-8');
                    echo $ex, PHP_EOL;
                } else {
                    echo json_encode(array('state'=>250,'msg'=>$ex->getMessage()));
                    Template::output('msg', $ex->getMessage());
                    Template::showpage('payment_result');
                }
            }
            exit;
    }

    /**
     * 虚拟订单支付
     */
    public function vr_pay() {
        $order_sn = $_GET['pay_sn'];
    
        $model_mb_payment = Model('mb_payment');
        $logic_payment = Logic('payment');
    
        $condition = array();
        $condition['payment_code'] = $this->payment_code;
        $mb_payment_info = $model_mb_payment->getMbPaymentOpenInfo($condition);
        if(!$mb_payment_info) {
            output_error('系统不支持选定的支付方式');
        }
    
        //重新计算所需支付金额
        $result = $logic_payment->getVrOrderInfo($order_sn, $this->member_info['member_id']);
    
        if(!$result['state']) {
            output_error($result['msg']);
        }
    
        //第三方API支付
        $this->_api_pay($result['data'], $mb_payment_info);
    }

    /**
     * 第三方在线支付接口
     *
     */
    private function _api_pay($order_pay_info, $mb_payment_info, $is_app = false) {
        $run_branch = false;
        switch ($mb_payment_info['payment_code']) {
            case 'alipay':
                $run_branch = true;
                $run_flag = true;
                // 获取配置
                $payment_config = $mb_payment_info['payment_config'];
                if (
                    (
                        empty($payment_config) || 
                        empty($payment_config['alipay_public_key']) || 
                        empty($payment_config['merchant_private_key']) || 
                        empty($payment_config['app_id']) || 
                        empty($payment_config['alipay_partner'])
                    ) && $run_flag
                ) {
                    $run_flag = false;
                    $state = 255;
                    $message = '当前支付未配置';
                }

                if ($run_flag) {
                    // 请求 支付宝支付
                    define('PAYMENT_ROOT', BASE_LIBRARY_PATH . DS .'api/payment');
                    require_once(PAYMENT_ROOT . DS . 'alipay' . DS . 'index.php');
                    // 可传入配置
                    $pay_config['alipay_public_key'] = $payment_config['alipay_public_key'];
                    $pay_config['merchant_private_key'] = $payment_config['merchant_private_key'];
                    $pay_config['app_id'] = $payment_config['app_id'];
                    $pay_config['seller_id'] = $payment_config['alipay_partner'];
                    $pay_Obj = new alipay($pay_config);

                    // 发起支付
                    $pay_data['order_type'] = $order_pay_info['order_type'] == 'real_order' ? 'product_buy' : $order_pay_info['order_type'];
                    $pay_data['subject'] = $order_pay_info['subject'];
                    $pay_data['body'] = $order_pay_info['pay_sn'];
                    $pay_data['pay_sn'] = $order_pay_info['pay_sn'];
                    $pay_data['out_trade_no'] = $order_pay_info['pay_sn'];//.'_'.$pay_data['order_type'];
                    $pay_data['total_amount'] = floatval($order_pay_info['api_pay_amount']);
                    
                    $pay_data['extend_params'] = $pay_data['order_type'];
                    
                    //product_buy商品购买,predeposit预存款充值
                    if ($is_app) {
                        $pay_return = $pay_Obj->appPay($pay_data);

                        if(!empty($pay_return)){
                            // $alipay=explode('&',urldecode($pay_return));
                            output_data(array('status'=>1,'result'=>$pay_return,'alipay'=>$pay_return));
                        }else{
                            output_data(array('status'=>0));
                        }
                    }else{
                        $pay_return = $pay_Obj->wapPay($pay_data);
                    }

                }

                if (!$run_flag) {
                    if ($is_app) {
                        output_data(array('status'=>0));
                    }else{
                        output_error($message);   
                    }
                }
                break;
            case 'wxpay_jsapi':
                $run_branch = true;
                $run_flag = true;
                $attach_extend = 'wxpay_jsapi';

                // 获取配置
                $payment_config = $mb_payment_info['payment_config'];

                if (
                    (
                        empty($payment_config) || 
                        empty($payment_config['appId']) || 
                        empty($payment_config['partnerId']) || 
                        empty($payment_config['apiKey']) || 
                        empty($payment_config['appSecret'])
                    ) && $run_flag
                ) {
                    $run_flag = false;
                    $state = 255;
                    $message = '当前支付未配置';
                }
                if ($run_flag) {
                    // 请求 微信支付
                    define('PAYMENT_ROOT', BASE_LIBRARY_PATH . DS .'api/payment');
                    require_once(PAYMENT_ROOT . DS . 'wxpay' . DS . 'index.php');
                    // 可传入配置
                    $pay_config['appId'] = $payment_config['appId'];
                    $pay_config['appMid'] = $payment_config['partnerId'];
                    $pay_config['appKey'] = $payment_config['apiKey'];
                    $pay_config['appSecret'] = $payment_config['appSecret'];
                    $all_wx_payment_config = Logic("payment")->getWxpayAllConfig();
                    $pay_config['other_config'] = $all_wx_payment_config;
                    $wxpay_Obj = new wxpay($pay_config);

                    // 微信支付

                    $pay_data['body'] = $order_pay_info['subject'] ? $order_pay_info['subject'] : C('site_name') . '商品订单' . $order_pay_info['pay_sn'];
                    $pay_data['pay_sn'] = $order_pay_info['pay_sn'].'-jsapi';
                    $pay_data['fee'] = (int) (100 * $order_pay_info['api_pay_amount']);

                    $pay_data['attach'] = $order_pay_info['order_type'] == 'real_order' ? 'product_buy'.'-'.$attach_extend : $order_pay_info['order_type'].'-'.$attach_extend;

                    //根据用户id获取用户的openid
                    $member_info = Model('member')->getMemberInfoByID($this->member_info['member_id'], $fields = 'wx_openid');
                    $pay_data['wx_openid'] = $member_info['wx_openid'];

                    $pay_return = $wxpay_Obj->jsapipay($pay_data);

                    echo $pay_return;
                }


                if (!$run_flag) {
                    output_error($message);
                }
                break;
            case 'mini_wxpay':
                $run_branch = true;
                $run_flag = true;

                $attach_extend = 'wxpay_jsapi';

                // 获取配置
                $payment_config = $mb_payment_info['payment_config'];

                if (
                    (
                        empty($payment_config) ||
                        empty($payment_config['appId']) ||
                        empty($payment_config['partnerId']) ||
                        empty($payment_config['apiKey']) ||
                        empty($payment_config['appSecret'])
                    ) && $run_flag
                ) {
                    $run_flag = false;
                    $state = 255;
                    $message = '当前支付未配置';
                }
                if ($run_flag) {
                    // 请求 微信支付
                    define('PAYMENT_ROOT', BASE_LIBRARY_PATH . DS .'api/payment');
                    require_once(PAYMENT_ROOT . DS . 'wxpay' . DS . 'index.php');
                    // 可传入配置
                    $pay_config['appId'] = $payment_config['appId'];
                    $pay_config['appMid'] = $payment_config['partnerId'];
                    $pay_config['appKey'] = $payment_config['apiKey'];
                    $pay_config['appSecret'] = $payment_config['appSecret'];
                    $all_wx_payment_config = Logic("payment")->getWxpayAllConfig();
                    $pay_config['other_config'] = $all_wx_payment_config;
                    $wxpay_Obj = new wxpay($pay_config);
                    // 微信支付
                    $pay_data['body'] = $order_pay_info['subject'] ? $order_pay_info['subject'] : C('site_name') . '商品订单' . $order_pay_info['pay_sn'];
                    $pay_data['pay_sn'] = $order_pay_info['pay_sn'].'-mini';
                    $pay_data['fee'] = (int) (100 * $order_pay_info['api_pay_amount']);

                    $pay_data['attach'] = $order_pay_info['order_type'] == 'real_order' ? 'product_buy'.'-'.$attach_extend : $order_pay_info['order_type'].'-'.$attach_extend;

                    //根据用户id获取用户的openid
                    $member_info = Model('member')->getMemberInfoByID($this->member_info['member_id'], $fields = 'wx_openid');
                    $pay_data['wx_openid'] = $member_info['wx_openid'];

                    $pay_return = $wxpay_Obj->minipay($pay_data);

                    if (is_array($pay_return) && isset($pay_return['code']) && $pay_return['code'] == 255) {
                        echo  json_encode(array('state'=>$pay_return['code'],'msg'=>$pay_return['message']));
                    }else{
                        echo  json_encode(array('state'=>200,'info'=>$pay_return));
                    }
                }


                if (!$run_flag) {
                    output_error($message);
                }
                break;
            case 'weixin':
                $run_branch = true;
                $run_flag = true;
                $attach_extend = 'weixin';
                // 获取配置
                $payment_config = $mb_payment_info['payment_config'];

                if (
                    (
                        empty($payment_config) ||
                        empty($payment_config['appId']) ||
                        empty($payment_config['partnerId']) ||
                        empty($payment_config['apiKey']) ||
                        empty($payment_config['appSecret'])
                    ) && $run_flag
                ) {
                    $run_flag = false;
                    $state = 255;
                    $message = '当前支付未配置';
                }
                if ($run_flag) {
                    // 请求 微信支付
                    define('PAYMENT_ROOT', BASE_LIBRARY_PATH . DS .'api/payment');
                    require_once(PAYMENT_ROOT . DS . 'wxpay' . DS . 'index.php');
                    // 可传入配置
                    $pay_config['appId'] = $payment_config['appId'];
                    $pay_config['appMid'] = $payment_config['partnerId'];
                    $pay_config['appKey'] = $payment_config['apiKey'];
                    $pay_config['appSecret'] = $payment_config['appSecret'];
                    $all_wx_payment_config = Logic("payment")->getWxpayAllConfig();
                    $pay_config['other_config'] = $all_wx_payment_config;
                    $wxpay_Obj = new wxpay($pay_config);

                    // 微信支付
                    $pay_data['body'] = $order_pay_info['subject'] ? $order_pay_info['subject'] : C('site_name') . '商品订单' . $order_pay_info['pay_sn'];
                    $pay_data['pay_sn'] = $order_pay_info['pay_sn'].'-weixin';
                    $pay_data['fee'] = (int) (100 * $order_pay_info['api_pay_amount']);

                    $pay_data['attach'] = $order_pay_info['order_type'] == 'real_order' ? 'product_buy'.'-'.$attach_extend : $order_pay_info['order_type'].'-'.$attach_extend;

                    //根据用户id获取用户的openid
                    $member_info = Model('member')->getMemberInfoByID($this->member_info['member_id'], $fields = 'wx_openid');
                    $pay_data['wx_openid'] = $member_info['wx_openid'];
                    $pay_return = $wxpay_Obj->apppay($pay_data);

                    if (is_array($pay_return) && isset($pay_return['code']) && $pay_return['code'] == 255) {
                        output_data(array('status'=>0));
                    }else{
                        output_data(array('status'=>1,'result'=>$pay_return));
                    }
                }

                if (!$run_flag) {
                    output_data(array('status'=>0));
                }
                break;

            default:
                $run_branch = false;
                break;
        }
        if (!$run_branch) {
            $ijmys_file = BASE_PATH.DS.'api'.DS.'payment'.DS.$this->payment_code.DS.$this->payment_code.'.php';
            if(!is_file($ijmys_file)){
                output_error('支付接口不存在');
            }
            require($ijmys_file);
            $param = $this->payment_config;

            // wxpay_jsapi
            if ($this->payment_code == 'wxpay_jsapi') {
                $param['orderSn'] = $order_pay_info['pay_sn'];
                $param['orderFee'] = (int) (100 * $order_pay_info['api_pay_amount']);
                $param['orderInfo'] = C('site_name') . '商品订单' . $order_pay_info['pay_sn'];
                $param['orderAttach'] = ($order_pay_info['order_type'] == 'real_order' ? 'r' : 'v');
                $api = new wxpay_jsapi();
                $api->setConfigs($param);
                try {
                    echo $api->paymentHtml($this);
                } catch (Exception $ex) {
                    if (C('debug')) {
                        header('Content-type: text/plain; charset=utf-8');
                        echo $ex, PHP_EOL;
                    } else {
                        Template::output('msg', $ex->getMessage());
                        Template::showpage('payment_result');
                    }
                }
                exit;
            }

            $param['order_sn'] = $order_pay_info['pay_sn'];
            $param['order_amount'] = $order_pay_info['api_pay_amount'];
            $param['order_type'] = ($order_pay_info['order_type'] == 'real_order' ? 'r' : 'v');
            $payment_api = new $this->payment_code();
            $return = $payment_api->submit($param);
            echo $return;
        }
        exit;
    }

    private function _api_payy($order_pay_info, $payment_info) {
        $ijmys_file = BASE_PATH.DS.'api'.DS.'payment'.DS.$payment_info['payment_code'].DS.$payment_info['payment_code'].'.php';
        if(!file_exists($ijmys_file)){
            output_error('支付接口不存在');
        }
//        print_r($payment_info);die;
        $ijmys_files=BASE_PATH.DS.'api'.DS.'payment'.DS.$payment_info['payment_code'].DS.$payment_info['payment_code'].'.config'.'.php';
        require_once($ijmys_file);
        require_once($ijmys_files);
        $param = array();
//        $param = unserialize($payment_info['payment_config']);
        $param['order_sn'] = $order_pay_info['pay_sn'];
        $param['order_amount'] = $order_pay_info['pay_amount'];
//        $param['alipay_partner'] = '2088912147118792';
//        $param['service'] = ;
//        $param['partner'] = ;
        //$param['sign_type'] = 'MD5';
        $payment_api = new $payment_info['payment_code']($param);
        $return = $payment_api->submits($param,$alipay_config);
        return $return;
//        exit;
    }

    /**
     * 站内余额支付(充值卡、预存款支付) 实物订单
     *
     */
    private function _pd_pay($order_list, $post) {
        if (empty($post['password'])) {
            return $order_list;
        }
        $model_member = Model('member');
        $buyer_info = $model_member->getMemberInfoByID($this->member_info['member_id']);
        if ($buyer_info['member_passwd'] == '' || $buyer_info['member_passwd'] != md5($post['password'])) {
            return $order_list;
        }

        if ($buyer_info['available_rc_balance'] == 0) {
            $post['rcb_pay'] = null;
        }
        if ($buyer_info['available_predeposit'] == 0) {
            $post['pd_pay'] = null;
        }
        if (floatval($order_list[0]['rcb_amount']) > 0 || floatval($order_list[0]['pd_amount']) > 0) {
            return $order_list;
        }

        try {
            $model_member->beginTransaction();
            $logic_buy_1 = Logic('buy_1');
            //使用充值卡支付
            if (!empty($post['rcb_pay'])) {
                $order_list = $logic_buy_1->rcbPay($order_list, $post, $buyer_info);
            }

            //使用预存款支付
            if (!empty($post['pd_pay'])) {
                $order_list = $logic_buy_1->pdPay($order_list, $post, $buyer_info);
            }

            //特殊订单站内支付处理
            $logic_buy_1->extendInPay($order_list);

            $model_member->commit();
        } catch (Exception $e) {
            $model_member->rollback();
            exit($e->getMessage());
        }

        return $order_list;
    }
    //获取订单信息
    private function getRealOrderInfo($pay_sn, $member_id)
    {
        //验证订单信息
        $model_order = Model('order');
        $buy_model = M('ladder_buy','pin_ladder');
        $condition = array();
        $condition['order_sn'] = $pay_sn;
        if (!empty($member_id)) {
            $condition['buyer_id'] = $member_id;
        }
        $order_pay_info = $model_order->table('pin_order')->where($condition)->find();
        if(!empty($order_pay_info)){
            $order_pay_info['subject'] = '实物订单_'.$order_pay_info['order_sn'];
            $order_pay_info['order_type'] = 'pin_product_buy';
            //计算本次需要在线支付的订单总金额
            //判断是交定金还是交尾款
            if($order_pay_info['order_state'] == 10){
                $pay_amount = sldPriceFormat($order_pay_info['goods_price'] * $order_pay_info['goods_num']);
                $order_pay_info['pay_sn'] = $order_pay_info['order_sn'].'_1';
            }elseif($order_pay_info['order_state'] == 20){
                $pay_amount = sldPriceFormat($buy_model->getLadderPrice($order_pay_info['pin_id'],$order_pay_info['gid']) * $order_pay_info['goods_num']);
                $order_pay_info['pay_sn'] = $order_pay_info['order_sn'].'_2';
            }else{
                return callback(false,'订单不能支付',$order_pay_info);
            }
            $order_pay_info['api_pay_amount'] = $pay_amount;
        }else{
            return callback(false,'订单不存在',$order_pay_info);
        }

        return callback(true,'',$order_pay_info);
    }
    /**
     * 获取订单支付信息
     */
    private function _get_real_order_info($pay_sn,$rcb_pd_pay = array()) {
        $logic_payment = Logic('payment');

        //取订单信息
        $result = $this->getRealOrderInfo($pay_sn, $this->member_info['member_id']);
        if(!$result['state']) {
            return array('error' => $result['msg']);
        }
        
        if ($result['data']['order_type'] == 'real_order') {
            //站内余额支付
//            if ($rcb_pd_pay) {
//                $result['data']['order_list'] = $this->_pd_pay($result['data']['order_list'],$rcb_pd_pay);
//            }

            //计算本次需要在线支付的订单总金额
            $pay_amount = 0;
            $pay_order_id_list = array();
            if (!empty($result['data']['order_list'])) {
                foreach ($result['data']['order_list'] as $order_info) {
                    if ($order_info['order_state'] == ORDER_STATE_NEW) {
                        $pay_amount += $order_info['order_amount'] - $order_info['pd_amount'] - $order_info['rcb_amount'];
                        $pay_order_id_list[] = $order_info['order_id'];
                    }
                }
            }
            if ($pay_amount == 0) {
                if (isset($rcb_pd_pay['no_redirect']) && $rcb_pd_pay['no_redirect']) {
                    return 'OK';
                }else{
                    //跳转路径待定
                    redirect(C('ldj_wap_site_url').'/cwap_order_list.html');
                }
            }

            $result['data']['api_pay_amount'] = sldPriceFormat($pay_amount);
            //临时注释
            //$update = Model('order')->editOrder(array('api_pay_time'=>TIMESTAMP),array('order_id'=>array('in',$pay_order_id_list)));
            //if(!$update) {
            //       return array('error' => '更新订单信息发生错误，请重新支付');
            //    }

            //如果是开始支付尾款，则把支付单表重置了未支付状态，因为支付接口通知时需要判断这个状态
            if ($result['data']['if_buyer_repay']) {
                $update = Model('order')->editOrderPay(array('api_pay_state'=>0),array('pay_id'=>$result['data']['pay_id']));
                if (!$update) {
                    return array('error' => '订单支付失败');
                }
                $result['data']['api_pay_state'] = 0;
            }
        }

        return $result;
    }

    /**
     * 可用支付参数列表
     */
    public function payment_list() {

        $model_mb_payment = Model('mb_payment');

        $payment_list = $model_mb_payment->getMbPaymentOpenList();


        $payment_array = array();
        if(!empty($payment_list)) {
            foreach ($payment_list as $value) {
                $payment_array[] = $value['payment_code'];
            }
        }

        output_data(array('payment_list' => $payment_array));
    }

    /*
     * 微信支付
     *
     */
    public function wx_app_pay3(){
        $pay_sn = $_POST['pay_sn'];
        //$model_mb_payment = Model('mb_payment');
        $model_payment = Model('payment');

        $payment_code = 'alipay';
        //$payment_code = 'wxpay';
        $result = $model_payment->productBuy($pay_sn, $payment_code, $this->member_info['member_id']);

        if(!empty($result['error'])) {
            output_error($result['error']);
        }

        $payment_info['payment_code'] = 'wxpay';
        $ijmys_file = BASE_PATH.DS.'api'.DS.'payment'.DS.$payment_info['payment_code'].DS.$payment_info['payment_code'].'.php';

        if(!file_exists($ijmys_file)){
            output_error('支付接口不存在');
        }
        require_once($ijmys_file);

        //output_data(array('test' => $result['payment_info']));

        $payresult = $this->_api_wxv3_unifiedpay($result['order_pay_info'], $result['payment_info']);

        //$payresult['timestamp'] = time();
        //$payresult['package'] ='com.dymall.mall';

        //output_data(array('wxpay' => $result['order_pay_info']));
        //output_data(array('wxpay' => $this->member_info));
       output_data(array('wxpay' => $payresult));

    }

    private function _api_wxv3_unifiedpay($order_pay_info, $payment_info)
    {
        $payment_info['payment_code'] = 'wxpay';
        $payment_api = new $payment_info['payment_code'];

        $param = array();
        $param['body'] = $order_pay_info['subject']; //商品或支付单简要描述
        $param['out_trade_no'] = $order_pay_info['pay_sn'];   //商户系统内部的订单号,32个字符内
        //strval(100*floatval($param['total_fee']))
        $param['total_fee'] = $order_pay_info['pay_amount'];   //订单总金额，只能为整数,单位为分
        $param['spbill_create_ip'] = $this->member_info['member_login_ip']; //APP和网页支付提交用户端ip

 //       return $param;

//        $param['notify_url'] = 'test';

        $return = $payment_api->submit($param);
        //$return = $payment_api->submit();
        return $return;
    }
    public function paysingle() {
//        $pay_sn = $_GET['pay_sn'];
//        $model_mb_payment = Model('mb_payment');
//        //$logic_payment = Logic('payment');
//        $model_payment = Model('payment');
//
//
//        $condition = array();
//        $condition['payment_code'] = $this->payment_code;
//
//
//        $payment_code = 'alipay';
//        $mb_payment_info = $model_mb_payment->getMbPaymentOpenInfo($condition);
//        //print_r($mb_payment_info);die;
//        if(!$mb_payment_info) {
//            output_error('系统不支持选定的支付方式');
//        }
//
//        //重新计算所需支付金额
//        //$result = $logic_payment->getRealOrderInfo($pay_sn, $this->member_info['member_id']);
//        $result = $model_payment->productBuy($pay_sn, $payment_code, $this->member_info['member_id']);
//
//        //print_r($this);die;
//        if(!$result['state']) {
//            output_error($result['msg']);
//        }
//
//        //第三方API支付
//        //$this->_api_pay($result['data'], $mb_payment_info);
//
//        $this->_api_payy($result['order_pay_info'], $result['payment_info']);

        $order_sn = $_POST['order_sn'];
        $model_mb_payment = Model('mb_payment');
        $payment_code = 'alipay';
        $condition = array();
        $condition['payment_code'] = $this->payment_code;

        $model_payment = Model('payment');
        $result = $model_payment->getOrderInfo($order_sn);
        if(!empty($result['error'])) {
            output_error($result['error']);
        }
        $order_pay_info=array();
        $order_pay_info['pay_sn']=$result['order_sn'];
        $payment_info=array();
        $payment_info['pay_amount']=$result['order_amount'];
        $payment_info['payment_code']=$payment_code;
        //print_r($result);die;
        //print_r($result);die;
        //第三方API支付
        $data=$this->_api_payy($order_pay_info, $payment_info);
        output_data(array('pay_info'=>$data));
    }
    //支付宝充值
    public function rechargeop(){
        $pdr_amount=$_POST['pdr_amount'];
        $pdr_sn=time().$this->member_info['member_id'];
        $payment_code = 'alipay';
        $condition['payment_code'] = $this->payment_code;
        $order_pay_info=array();
        $order_pay_info['pay_sn']=$pdr_sn;
        $payment_info=array();
        $payment_info['pay_amount']=$pdr_amount;
        $payment_info['payment_code']=$payment_code;
        //print_r($result);die;
        //print_r($result);die;
        //第三方API支付
        $data=$this->_api_payy($order_pay_info, $payment_info);
        //执行数据库插入操作
        $pdr=Model('predeposit');
        $field=array('pdr_sn'=>$pdr_sn,'pdr_member_id'=>$this->member_info['member_id'],
            'pdr_member_name'=>$this->member_info['member_name'],'pdr_amount'=>$pdr_amount,'pdr_add_time'=>time());
        $pdr->addPdRecharge($field);
        output_data(array('pay_info'=>$data));
    }
    /**
     * 获取支付参数
     */
    private function _get_wx_pay_info($pay_param) {
        $access_token = $this->_get_wx_access_token();
        if(empty($access_token)) {
            return array('error' => '支付失败code:1001');
        }

        $package = $this->_get_wx_package($pay_param);

        $noncestr = md5($package + TIMESTAMP);
        $timestamp = TIMESTAMP;
        $traceid = $this->member_info['member_id'];

        // 获取预支付app_signature
        $param = array();
        $param['appid'] = $this->payment_config['wxpay_appid'];
        $param['noncestr'] = $noncestr;
        $param['package'] = $package;
        $param['timestamp'] = $timestamp;
        $param['traceid'] = $traceid;
        $app_signature = $this->_get_wx_signature($param);

        // 获取预支付编号
        $param['sign_method'] = 'sha1';
        $param['app_signature'] = $app_signature;
        $post_data = json_encode($param);
        $prepay_result = http_postdata('https://api.weixin.qq.com/pay/genprepay?access_token=' . $access_token, $post_data);
        $prepay_result = json_decode($prepay_result, true);
        if($prepay_result['errcode']) {
            return array('error' => '支付失败code:1002');
        }
        $prepayid = $prepay_result['prepayid'];

        // 生成正式支付参数
        $data = array();
        $data['appid'] = $this->payment_config['wxpay_appid'];
        $data['noncestr'] = $noncestr;
        $data['package'] = 'Sign=WXPay';
        $data['partnerid'] = $this->payment_config['wxpay_partnerid'];
        $data['prepayid'] = $prepayid;
        $data['timestamp'] = $timestamp;
        $sign = $this->_get_wx_signature($data);
        $data['sign'] = $sign;
        return $data;
    }

    /**
     * 获取微信access_token
     */
    private function _get_wx_access_token() {
        // 尝试读取缓存的access_token
        $access_token = rkcache('wx_access_token');
        if($access_token) {
            $access_token = unserialize($access_token);
            // 如果access_token未过期直接返回缓存的access_token
            if($access_token['time'] > TIMESTAMP) {
                return $access_token['token'];
            }
        }

        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=%s&secret=%s';
        $url = sprintf($url, $this->payment_config['wxpay_appid'], $this->payment_config['wxpay_appsecret']);
        $re = http_get($url);
        $result = json_decode($re, true);
        if($result['errcode']) {
            return '';
        }

        // 缓存获取的access_token
        $access_token = array();
        $access_token['token'] = $result['access_token'];
        $access_token['time'] = TIMESTAMP + $result['expires_in'];
        wkcache('wx_access_token', serialize($access_token));

        return $result['access_token'];
    }

    /**
     * 获取package
     */
    private function _get_wx_package($param) {
        $array = array();
        $array['bank_type'] = 'WX';
        $array['body'] = $param['subject'];
        $array['fee_type'] = 1;
        $array['input_charset'] = 'UTF-8';
        $array['notify_url'] = MOBILE_SITE_URL . '/api/payment/wxpay/notify_url.php';
        $array['out_trade_no'] = $param['pay_sn'];
        $array['partner'] = $this->payment_config['wxpay_partnerid'];
        $array['total_fee'] = $param['amount'];
        $array['spbill_create_ip'] = get_server_ip();

        ksort($array);

        $string = '';
        $string_encode = '';
        foreach ($array as $key => $val) {
            $string .= $key . '=' . $val . '&';
            $string_encode .= $key . '=' . urlencode($val). '&';
        }

        $stringSignTemp = $string . 'key=' . $this->payment_config['wxpay_partnerkey'];
        $signValue = md5($stringSignTemp);
        $signValue = strtoupper($signValue);

        $wx_package = $string_encode . 'sign=' . $signValue;
        return $wx_package;
    }

    /**
     * 获取微信支付签名
     */
    private function _get_wx_signature($param) {
        $param['appkey'] = $this->payment_config['wxpay_appkey'];

        $string = '';

        ksort($param);
        foreach ($param as $key => $value) {
            $string .= $key . '=' . $value . '&';
        }
        $string = rtrim($string, '&');

        $sign = sha1($string);

        return $sign;
    }



    /**
     * 获取支付参数
     */
    private function _get_wx_pay_info3($pay_param) {
        $noncestr = md5(rand());

        $param = array();
        $param['appid'] = $this->payment_config['wxpay_appid'];
        $param['mch_id'] = $this->payment_config['wxpay_partnerid'];
        $param['nonce_str'] = $noncestr;
        $param['body'] = $pay_param['subject'];
        $param['out_trade_no'] = $pay_param['pay_sn'];
        $param['total_fee'] = $pay_param['amount'];
        $param['spbill_create_ip'] = get_server_ip();
        $param['notify_url'] = MOBILE_SITE_URL . '/api/payment/wxpay3/notify_url.php';
        $param['trade_type'] = 'APP';

        $sign = $this->_get_wx_pay_sign3($param);
        $param['sign'] = $sign;

        $post_data = '<xml>';
        foreach ($param as $key => $value) {
            $post_data .= '<' . $key .'>' . $value . '</' . $key . '>';
        }
        $post_data .= '</xml>';

        $prepay_result = http_postdata('https://api.mch.weixin.qq.com/pay/unifiedorder', $post_data);
        $prepay_result = simplexml_load_string($prepay_result);
        if($prepay_result->return_code != 'SUCCESS') {
            return array('error' => '支付失败code:1002');
        }

        // 生成正式支付参数
        $data = array();
        $data['appid'] = $this->payment_config['wxpay_appid'];
        $data['noncestr'] = $noncestr;
        //微信修改接口参数，否则IOS报解析失败
        //$data['package'] = 'prepay_id=' . $prepay_result->prepay_id;
        $data['package'] = 'Sign=WXPay';
        $data['partnerid'] = $this->payment_config['wxpay_partnerid'];
        $data['prepayid'] = (string)$prepay_result->prepay_id;
        $data['timestamp'] = TIMESTAMP;
        $sign = $this->_get_wx_pay_sign3($data);
        $data['sign'] = $sign;
        return $data;
    }

    private function _get_wx_pay_sign3($param) {
        ksort($param);
        foreach ($param as $key => $val) {
            $string .= $key . '=' . $val . '&';
        }
        $string .= 'key=' . $this->payment_config['wxpay_partnerkey'];
        return strtoupper(md5($string));
    }

    /**
     * 取得支付宝移动支付 订单信息 实物订单
     */
    public function alipay_native_payOp() {
        $pay_sn = $_POST['pay_sn'];
        if (!preg_match('/^\d+$/',$pay_sn)){
            output_error('支付单号错误');
        }
        $pay_info = $this->_get_real_order_info($pay_sn);
        if(isset($pay_info['error'])) {
            output_error($pay_info['error']);
        }

        $slds_file = BASE_PATH.DS.'api'.DS.'payment'.DS.$this->payment_code.DS.$this->payment_code.'.php';
        if(!is_file($slds_file)){
            exit('支付接口不存在');
        }
        require($slds_file);
        $pay_info['data']['order_type'] = 'r';
        $payment_api = new $this->payment_code();
        $payment_api->init($this->payment_config,$pay_info['data']);

        output_data($payment_api->param);
    }
}
