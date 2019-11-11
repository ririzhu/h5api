<?php
namespace app\v1\controller;

use app\v1\model\Predeposit;
include("../extend/pay/alipay/AopSdk.php");
include("../extend/pay/allinpay/Allinpay.php");
class Payment extends Base
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(){

        //购买商品和预存款充值分流
        if ($_POST['order_type'] == 'product_buy') {
            $this->_product_buy();
        } elseif ($_POST['order_type'] == 'pd_rechange') {
            $this->_pd_rechange();
        }
    }

    /**
     * 商品购买
     */
    private function _product_buy(){
        $pay_sn = $_POST['pay_sn'];
        $payment_code = $_POST['payment_code'];
        $url = 'index.php?app=userorder';

        $valid = !preg_match('/^\d{18}$/',$pay_sn) || !preg_match('/^[a-z]{1,20}$/',$payment_code) || in_array($payment_code,array('offline','predeposit'));
        if($valid || !input("member_id")){
            lang('参数错误');
        }

        $model_payment = new \app\v1\model\Payment();
        $result = $model_payment->productBuy($pay_sn, $payment_code, input("member_id"));
        if(!empty($result['error'])) {
            //showMsg($result['error'], $url, 'html', 'error');
        }
        //第三方API支付
        $this->_api_pay($result['order_pay_info'], $result['payment_info']);
    }

    /**
     * 预存款充值
     */
    private function _pd_rechange(){
        //Language::read('home_payment_index');
        //$url = 'index.php?app=chongzhi';
        //pay_sn:充值单号
        $pay_sn = $_POST['pdr_sn'];
        $payment_code = $_POST['payment_code'];
        if(!preg_match('/^\d{18}$/',$pay_sn) || !preg_match('/^[a-z]{1,20}$/',$payment_code)){
            lang('参数错误');
        }

        //取支付方式信息
        $model_payment = new \app\v1\model\Payment();
        $condition = array();
        $condition['payment_code'] = $payment_code;
        $payment_info = $model_payment->getPaymentOpenInfo($condition);
        if(!$payment_info || in_array($payment_info['payment_code'],array('offline','predeposit'))) {
            lang('系统不支持选定的支付方式');
        }
        $model_pd = new Predeposit();
        $order_info = $model_pd->getPdRechargeInfo(array('pdr_sn'=>$pay_sn,'pdr_member_id'=>$_SESSION['member_id']));
        $order_info['subject'] = '预存款充值_'.$order_info['pdr_sn'];
        $order_info['order_type'] = 'predeposit';
        $order_info['pay_sn'] = $order_info['pdr_sn'];
        $order_info['pay_amount'] = $order_info['pdr_amount'];
        if(empty($order_info) || $order_info['pdr_payment_state'] == 1){
            lang('该订单不存在');
        }

        //其它第三方在线通用支付入口
        $this->_api_pay($order_info,$payment_info);
    }

    /**
     * 第三方在线支付接口
     *
     */
    private function _api_pay($order_info, $payment_info) {
        $run_branch = false;
        switch ($payment_info['payment_code']) {
            case 'alipay':
                $run_branch = true;
                $run_flag = true;

                // 获取配置
                $payment_config = unserialize($payment_info['payment_config']);
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
                    // 可传入配置
                    $pay_config['alipay_public_key'] = $payment_config['alipay_public_key'];
                    $pay_config['merchant_private_key'] = $payment_config['merchant_private_key'];
                    $pay_config['app_id'] = $payment_config['app_id'];
                    $pay_config['seller_id'] = $payment_config['alipay_partner'];
                    $pay_Obj = $aop = new \AopClient();
                    $aop->gatewayUrl = "https://openapi.alipay.com/gateway.do";
                    $aop->appId = $pay_config['app_id'];
                    $aop->alipayPublicKey = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAsSah6GBhd7JM7zaot3OlaGm2W38xTsywQpyh3XjijOnjlbxyoSvXRMix4/VEKqhBlRRRh7L+f1Csqn2oZB6H19juie0ER93l/zjpEs+bMcfB3QE5qeV62RTCswo7LEGY8XQZx1DysN1U/O5nvgG5yzIuVGk+08LTYaISqV1s5pD3/kFk/rrbIRmUVpr3leb/sRMpyGIxM+tslV/oRvsjW59RR4nkkPVWHVXddQ15VgRSmBPDPPF2ALDFQt7KTDaE9nKJ6IlnVhDenKANT1FTj7KRBJLRFZyEN8YkuQssu4C4naUQrdCFFUEIkyTeHeUbtWhlY9Depp9BX1LzxYzykQIDAQAB";
                    $aop->alipayrsaPublicKey = "MIIEpAIBAAKCAQEAtoG+oYDc1yQSniD5UqDzmxq42EyPlsVbJ+HtMhcA2Kz2Fotn GzcnsRtJ0WzCBz4wDSPwW6qgJGaHqfg2SiIkIL3U5Z55LzhlIW2wB4DvNj1vaZ6v YSMTxNaPjWWbfLAGtMm4gFcSDRJqilZCzLuBE9IUid6sHkJBbPTyoWBD/76JQfSD 1McydWv80spYEvBJO6CoHFOrDPZyOy8ms3gzX2Wi0USzfoHvoDX6CCJCYx5hCBJB CGLqftpZ2Oj1gBhez0MXIgJ/QJeWVZDPgI5HTXhvp6DHCZM8ytSYvLYCwzBVF5Uz QyreK9iUSbkUcRIbat7rv8KK9prrPYVoZeKtLwIDAQABAoIBAQCvnJam5s0ZQv+b IpVlmbPuOj9E+h5scEivTqijOxk4ZO2Cce7rdB4AQAa+ukzVFVS9QCimu77OZ84A kfMDUGi9DIyPyfZHppdBHD92aO1EnsbWtbeB23PJQr/syalcyDAbw8KB08Ztx5u2 sxwhzgZ84PecViP+FbjIs8XH3E5yBPAoJoKDCyPdaHhQSGQZe6jxRjLC9XODVH7d 3bgCT95nCYzyezE8cDAHVieKZ65AUSWwSSs+us8iZGRdgHqX5IvFHDd6Uy6ZjMOs OlUWH5JgXGzciPQjDt1pNYWpjlSniLDrq58JLu95VcEr977HrP9UZkhvS6lKCTSh XpJK7NJRAoGBANlLDuy7vvhWK/769PXyETkEp+JzL4zLcXGtK5nfrvm2Jc9ewPBj pVHleh+B0aKw1mCWufUT8IJxF2CixnfvS55xnP+yQBvs8le0IdHeVdvBkDiexkcb OSbBzmG9m8LCSSzMdEi62E11KOyYKXY6VRwbYA0i2JWFOn87djjqOVZHAoGBANcE XikbFF6bcE9CNA8Q9GxLQb8j8i36wNO+a/h8mnfnF8Y+2RyK6X286sdRDXa9UENO WA0MXqMPBJfdFbb0Qiz019jid7mPn8BMDYcMSCk604bMCK7BBdgeV1/FObPgSoV8 wzweSXHhUZjWM/RDPAh7yGzqGh1uSAA9l4sgKZ3ZAoGAecqNMflFX7IE9OS6ekPU jW3jn5RKOZMqIbobLyLl0wbaCHImmFZxqgaCPbioxJRzhC3XStuDOcmjfcGelkik zMkHY3YIYt6bMrc/IX+KBiNm76VmoyJKFUQZpkT9UdtN4nMyVjWL2VZqurnKu36U h618V8CJPr0u/XNZnysBOi8CgYBDqTU0PDhBuSozVsLpBs3Tki8DRf18qI6rUx3I 2PUGzCq4EKjjiXcGQT+kLwZMmjA6rdmZaY4SQ7SPUVv28ZAtc3LE5icEtoRvz77m A2Bl0QQlQ+lrjIQZSRr3oSmSR/9LWEJblbBI7L1vmeBJeirXBJTCaTyEjGKN6NPa TOmrqQKBgQCeiwzKLjzp/i27nH7d+ZxH6hNaQwc7Ufz4Uo1APEu0UvzO1we4kUCE 6PYWj5nTCsBhvyZQdcziAS290TDNvpZPrQZawidD1LN+hFUtAqBj9wQLB0yCdNU9 xCHwq/b3zBk+W4nNAJEh5qVXJNQlEAidcC1Knf/FnmYcnZ7QQl9sDw==";
                    $aop->apiVersion = '1.0';
                    $aop->signType = 'RSA2';
                    $aop->postCharset='UTF-8';
                    $aop->format='json';
                    $request = new \AlipayTradeAppPayRequest();// AlipayTradeAppPayRequest();
                    $bizcontent = json_encode($order_info,true);
                    $request->setNotifyUrl("http://xcx.com");
                    $request->setBizContent($bizcontent);
                    $result = $aop->sdkExecute($request);
                    echo ($result);


                }

                if (!$run_flag) {
                    showMsg($message,'','html','error');
                }
                break;
            case 'wxpay':
                $run_branch = true;
                $run_flag = true;

                // 获取配置
                $payment_config = unserialize($payment_info['payment_config']);
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

                if (!extension_loaded('curl')  && $run_flag) {
                    $run_flag = false;
                    $state = 255;
                    $message = '系统curl扩展未加载，请检查系统配置';
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
                    // 二维码支付
                    $pay_data['body'] = $order_info['pay_sn'].'订单';
                    $pay_data['pay_sn'] = $order_info['pay_sn'].'-saoma';
                    $pay_data['fee'] = floatval($order_info['pay_amount']) * 100;

                    $pay_data['attach'] = $order_info['order_type'];
                    $pay_return = $wxpay_Obj->qrcodepay($pay_data);

                    if ($pay_return['result_code'] == 'SUCCESS' && $pay_return['return_code'] == 'SUCCESS') {
                        Template::setDir('buy');
                        Template::setLayout('buy_layout');
                        if (array_key_exists('order_list', $order_info)) {
                            Template::output('order_list',$order_info['order_list']);
                            Template::output('args','buyer_id='.$_SESSION['member_id'].'&pay_id='.$order_info['pay_id']);
                        }else if($order_info['order_type']=='predeposit'){
                            $order_info['order_sn'] = $order_info['pdr_sn'];
                            $order_info['order_amount'] = $order_info['pay_amount'];

                            Template::output('order_list',array($order_info));
                            Template::output('order_type','predeposit');
                            Template::output('args','buyer_id='.$_SESSION['member_id'].'&pdr_id='.$order_info['pdr_id']);
                        }else {
                            Template::output('order_list',array($order_info));
                            Template::output('args','buyer_id='.$_SESSION['member_id'].'&pay_sn='.$order_info['pay_sn']);
                        }
                        Template::output('api_pay_amount',$order_info['pay_amount']);
                        Template::output('pay_url',base64_encode(encrypt($pay_return['code_url'],MD5_KEY)));
                        Template::output('nav_list', rkcache('nav',true));
                        Template::showpage('payment.wxpay');
                    }else{
                        $run_flag = false;
                        $state = 255;
                        $message = $pay_return['err_code_des'] ? $pay_return['err_code_des'] : $pay_return['return_msg'];
                    }
                }

                if (!$run_flag) {
                    showMsg($message,'','html','error');
                }

                break;
            case 'allinpay-alipay':
                    $allinpay = new \allinpay();
                    $allinpay->pay();
                break;
            default:
                $run_branch = false;
                break;
        }
        if (!$run_branch) {
            $ijmys_file = BASE_MALL_PATH.DS.'api'.DS.'payment'.DS.$payment_info['payment_code'].DS.$payment_info['payment_code'].'.php';
            if(!file_exists($ijmys_file)){
                showMsg(Language::get('指定的支付接口不存在'),'','html','error');
            }
            require_once($ijmys_file);
            $payment_info['payment_config'] = unserialize($payment_info['payment_config']);
            $payment_api = new $payment_info['payment_code']($payment_info,$order_info);
            if($payment_info['payment_code'] == 'chinabank') {
                $payment_api->submit();
            } elseif ($payment_info['payment_code'] == 'wxpay') {
                if (!extension_loaded('curl')) {
                    showMsg(Language::get('系统curl扩展未加载，请检查系统配置'), '', 'html', 'error');
                }
                Template::setDir('buy');
                Template::setLayout('buy_layout');
                if (array_key_exists('order_list', $order_info)) {
                    Template::output('order_list',$order_info['order_list']);
                    Template::output('args','buyer_id='.$_SESSION['member_id'].'&pay_id='.$order_info['pay_id']);
                }else if($order_info['order_type']=='predeposit'){
                    Template::output('order_list',$order_info);
                    Template::output('order_type','predeposit');
                    Template::output('args','buyer_id='.$_SESSION['member_id'].'&pdr_id='.$order_info['pdr_id']);
                }else {
                    Template::output('order_list',array($order_info));
                    Template::output('args','buyer_id='.$_SESSION['member_id'].'&pay_sn='.$order_info['pay_sn']);
                    //                Template::output('args','buyer_id='.$_SESSION['member_id'].'&order_id='.$order_info['pay_id']);
                }
                Template::output('api_pay_amount',$order_info['pay_amount']);
                Template::output('pay_url',base64_encode(encrypt($payment_api->get_payurl(),MD5_KEY)));
                Template::output('nav_list', rkcache('nav',true));
                Template::showpage('payment.wxpay');
            }else {
                @header("Location: ".$payment_api->get_payurl());
            }
        }
        exit;
    }

    /**
     * 通知处理(支付宝异步通知和网银在线自动对账)
     *
     */
    public function notify(){
        $payment_code = (isset($_GET['payment_code']) && $_GET['payment_code']) ? $_GET['payment_code'] : '';

        if ($payment_code) {

            //取得支付方式信息
            $paymentModel = new \app\v1\model\Payment();
            $payment_info = $paymentModel->getPaymentOpenInfo(array('payment_code'=>$payment_code));

            switch ($payment_code) {
                case 'alipay':
                    $success    = str_replace(array('alipay','chinabank'), array('success','ok'), $payment_code);
                    $fail       = str_replace(array('alipay','chinabank'), array('fail','error'), $payment_code);

                    $out_trade_no_str = (isset($_POST['out_trade_no']) && $_POST['out_trade_no']) ? trim($_POST['out_trade_no']) : '';
                    $out_trade_no = ($out_trade_no_str && strpos($out_trade_no_str, '-')) ? substr($out_trade_no_str,0,strpos($out_trade_no_str, '-')) : $out_trade_no_str;
                    $passback_params = (isset($_POST['passback_params']) && $_POST['passback_params']) ? $_POST['passback_params'] : '';
                    $seller_id = $_POST['seller_id'];
                    $app_id = $_POST['app_id'];
                    $trade_status = $_POST['trade_status'];
                    $notify_id = $_POST['notify_id'];
                    $total_amount = $_POST['total_amount'];
                    $extend_data = $_POST;
                    // 扩展数据 新订单 状态值
                    $extend_data['ORDER_STATE_NEW'] = ORDER_STATE_NEW;
                    $extend_data['payment_code'] = $payment_code;
                    $extend_data['payment_name'] = $payment_info['payment_name'];

                    $allow_passback_params = array('predeposit','product_buy');

                    //参数判断
                    if ( empty($out_trade_no) && !preg_match('/^\d{18}$/',$out_trade_no) ){
                        exit($fail);
                    }
                    if ( $passback_params && !in_array($passback_params,$allow_passback_params) ) {
                        exit($fail);
                    }

                    // 校验 seller_id/seller_email 是否合法
                    if(!is_array($payment_info) or empty($payment_info)) exit($fail);
                    $payment_config = unserialize($payment_info['payment_config']);
                    if ( !(isset($payment_config['alipay_partner']) && $payment_config['alipay_partner']) || $payment_config['alipay_partner'] != $seller_id ) {
                        exit($fail);
                    }

                    // 校验app_id 是否合法
                    if ( !(isset($payment_config['app_id']) && $payment_config['app_id']) || $payment_config['app_id'] != $app_id ) {
                        exit($fail);
                    }

                    define('PAYMENT_ROOT', BASE_LIBRARY_PATH . DS .'api/payment');
                    require_once(PAYMENT_ROOT . DS . 'alipay' . DS . 'index.php');
                    // 可传入配置
                    $pay_config['alipay_public_key'] = $payment_config['alipay_public_key'];
                    $pay_config['merchant_private_key'] = $payment_config['merchant_private_key'];
                    $pay_config['app_id'] = $payment_config['app_id'];
                    $pay_config['seller_id'] = $payment_config['alipay_partner'];
                    $pay_Obj = new alipay($pay_config);

                    //对进入的参数进行远程数据判断
                    $verify = $pay_Obj->notify_verify($notify_id);
                    if (!$verify) {
                        exit($fail);
                    }

                    // 校验订单号 是否合法 （放至业务处理程序内进行校验）

                    // 校验 total_amount 是否合法（是否为该订单的支付金额，放置业务处理程序内进行校验）

                    // 状态TRADE_SUCCESS的通知触发条件是商户签约的产品支持退款功能的前提下，买家付款成功；
                    // 交易状态TRADE_FINISHED的通知触发条件是商户签约的产品不支持退款功能的前提下，买家付款成功；或者，商户签约的产品支持退款功能的前提下，交易已经成功并且已经超过可退款期限。
                    if ($trade_status == 'TRADE_SUCCESS' || $trade_status == 'TRADE_FINISHED') {
                        $processing_data = Logic("payment")->notifyProcessing($passback_params,$out_trade_no,$total_amount,$extend_data);
                        if (isset($processing_data['code']) && $processing_data['code']) {
                            switch ($processing_data['code']) {
                                case '200':
                                    exit($success);
                                    break;

                                case '255':
                                    exit($fail);
                                    break;

                                case '256':
                                    exit($success);
                                    break;

                                default:
                                    exit($fail);
                                    break;
                            }
                        }else{
                            exit($fail);
                        }
                    }else{
                        // 异步回调 未付款成功 其他情况
                        exit($fail);
                    }
                    break;

                case 'wxpay':
                    $success = '<xml>
                                <return_code>SUCCESS</return_code>
                                <return_msg>OK</return_msg>
                                </xml>';
                    $fail = '<xml>
                                <return_code>FAIL</return_code>
                                <return_msg>失败</return_msg>
                                </xml>';

                    $payment_config = unserialize($payment_info['payment_config']);

                    // 获取微信回调的数据
                    $notifiedData = file_get_contents('php://input');

                    //XML格式转换
                    libxml_disable_entity_loader(true);
                    $xmlObj = json_decode(json_encode(simplexml_load_string($notifiedData, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

                    // 当支付通知返回支付成功时
                    if ($xmlObj['return_code'] == "SUCCESS" && $xmlObj['result_code'] == "SUCCESS") {

                        //获取返回的所以参数
                        //这里是要把微信返给我们的所有值，先删除sign的值，其他值 按ASCII从小到大排序，md5加密+‘key’；

                        foreach( $xmlObj as $k=>$v) {
                            if($k == 'sign') {
                                $xmlSign = $xmlObj[$k];
                                unset($xmlObj[$k]);
                            };
                        }
                        ksort($xmlObj);

                        $sign = http_build_query($xmlObj);
                        //md5处理
                        // $sign = md5($sign.'&key='.$payment_config['apiKey']);
                        //
                        $sign = hash_hmac("sha256",$sign.'&key='.$payment_config['apiKey'] ,$payment_config['apiKey']);
                        //转大写
                        $sign = strtoupper($sign);

                        //验签名。默认支持MD5
                        //验证加密后的32位值和 微信返回的sign 是否一致！！！
                        if ( $sign === $xmlSign) {
                            $allow_attach = array('predeposit','product_buy');

                            $attach = $xmlObj['attach'];
                            $out_trade_no_str = $xmlObj['out_trade_no'];
                            $out_trade_no = ($out_trade_no_str && strpos($out_trade_no_str, '-')) ? substr($out_trade_no_str,0,strpos($out_trade_no_str, '-')) : $out_trade_no_str;
                            $total_amount = floatval($xmlObj['total_fee']/100);
                            $extend_data = $xmlObj;
                            $extend_data['ORDER_STATE_NEW'] = ORDER_STATE_NEW;
                            $extend_data['payment_code'] = 'wx_saoma';//$payment_code;
                            $extend_data['payment_name'] = $payment_info['payment_name'];

                            //参数判断
                            if ( empty($out_trade_no) && !preg_match('/^\d{18}$/',$out_trade_no) ){
                                return false;
                            }
                            if ( $attach && !in_array($attach,$allow_attach) ) {
                                return false;
                            }

                            $processing_data = Logic("payment")->notifyProcessing($attach,$out_trade_no,$total_amount,$extend_data);

                            if (isset($processing_data['code']) && $processing_data['code']) {
                                switch ($processing_data['code']) {
                                    case '200':
                                        exit($success);
                                        break;

                                    case '255':
                                        exit($fail);
                                        break;

                                    case '256':
                                        exit($success);
                                        break;

                                    default:
                                        exit($fail);
                                        break;
                                }
                            }else{
                                exit($fail);
                            }

                        }else{
                            exit($fail);
                        }

                    }

                    break;

                default:
                    # code...
                    break;
            }
        }
    }

    /**
     * 支付接口返回
     *
     */
    public function returns(){
        if ($_GET['extra_common_param'] == 'product_buy') {
            $url = MALL_SITE_URL."/index.php?app=userorder";
        } else {
            $url = MALL_SITE_URL."/index.php?app=chongzhi";
        }

        showMsg(Language::get('回调展示关闭业务处理'),$url,'','html','error');

        // Language::read('home_payment_index');
        // if ($_GET['extra_common_param'] == 'product_buy') {
        //     $url = MALL_SITE_URL."/index.php?app=userorder";
        // } else {
        //     $url = MALL_SITE_URL."/index.php?app=chongzhi";
        // }

        // $out_trade_no = $_GET['out_trade_no'];
        // //对外部交易编号进行非空判断
        // if(!preg_match('/^\d{18}$/',$out_trade_no)) {
        //     showMsg(Language::get('参数错误'),$url,'','html','error');
        // }
        // if (!in_array($_GET['extra_common_param'],array('predeposit','product_buy'))) {
        //     showMsg(Language::get('参数错误'),$url,'','html','error');
        // }

        // $condition = array();
        // if ($_GET['extra_common_param'] == 'product_buy') {
        //     //取得订单信息
        //     $model_order = Model('order');
        //     $condition['pay_sn'] = $out_trade_no;
        //     $order_pay_info	= $model_order->getOrderPayInfo($condition);
        //     //对订单信息进行非空判断
        //     if(empty($order_pay_info)) {
        //         showMsg('返回的交易号不存',$url,'html','error');
        //     }
        //     if (intval($order_pay_info['api_pay_state'])) {
        //         //状态为已支付的情况下需要发送卖家的订单通知消息
        //         // 支付成功发送店铺消息
        //         //根据pay_sn获取订单的信息
        //         $order_info_new = $model_order -> getOrderInfo(array('pay_sn'=>$order_pay_info['pay_sn']));
        //         $param = array();
        //         $param['code'] = 'new_order';
        //         $param['vid'] = $order_info_new['vid'];
        //         $param['param'] = array(
        //             'order_sn' => $order_info_new['order_sn']
        //         );
        //         QueueClient::push('sendStoreMsg', $param);
        //         //发送门店提醒
        //         if($order_info_new['dian_id']>0){
        //             $param = array();
        //             $param['code'] = 'dian_new_order';
        //             $param['vid'] = $order_info_new['dian_id'];
        //             $param['param'] = array(
        //                 'order_sn' => $order_info_new['order_sn']
        //             );
        //             QueueClient::push('sendDianMsg', $param);
        //         }

        //         //如果是门店订单 通知门店
        //         if($order_info_new['dian_id']>0) {
        //             $param = array();
        //             $param['code'] = 'dian_new_order';
        //             $param['vid'] = $order_info_new['dian_id'];
        //             $param['param'] = array(
        //                 'order_sn' => $order_info_new['order_sn']
        //             );
        //             QueueClient::push('sendDianMsg', $param);
        //         }
        //         showMsg(Language::get('订单支付成功'),$url);
        //     }

        //     //取得订单列表和API支付总金额
        //     $order_list = $model_order->getOrderList(array('pay_sn'=>$out_trade_no,'order_state'=>ORDER_STATE_NEW));
        //     if (empty($order_list)) {
        //         showMsg(Language::get('订单支付成功'),$url);
        //     }
        //     $pay_amount = $api_pay_amount = 0;
        //     foreach($order_list as $order_info) {
        //         $api_pay_amount += sldPriceFormat(floatval($order_info['order_amount']) - floatval($order_info['pd_amount']));
        //         $pay_amount += floatval($order_info['order_amount']);
        //     }
        //     $order_pay_info['pay_amount'] = $api_pay_amount;

        // } elseif ($_GET['extra_common_param'] == 'predeposit') {
        //     $model_pd = Model('predeposit');
        //     $condition['pdr_sn'] = $out_trade_no;
        //     $order_pay_info = $model_pd->getPdRechargeInfo($condition);
        //     //对订单信息进行非空判断
        //     if(empty($order_pay_info)) {
        //         showMsg('返回的交易号不存',$url,'html','error');
        //     }
        //     if (intval($order_pay_info['pdr_payment_state'])) {
        //         showMsg(Language::get('充值成功'),$url);
        //     }
        // }

        // //取得支付接口信息
        // $payment_code = $_GET['payment_code'];
        // unset($_GET['payment_code']);
        // $model_payment = Model('payment');
        // $condition = array();
        // $condition['payment_code'] = $payment_code;
        // $payment_info = $model_payment->getPaymentOpenInfo($condition);
        // if(!is_array($payment_info) || empty($payment_info)) {
        //     showMsg(Language::get('缺少支付方式'),$url,'html','error');
        // }
        // $payment_info['payment_config']	= unserialize($payment_info['payment_config']);
        // $ijmys_file = BASE_MALL_PATH.DS.'api'.DS.'payment'.DS.$payment_info['payment_code'].DS.$payment_info['payment_code'].'.php';
        // if(!file_exists($ijmys_file)) {
        //     showMsg(Language::get('指定的支付接口不存在'),$url,'html','error');
        // }
        // require_once($ijmys_file);
        // $payment_api = new $payment_info['payment_code']($payment_info,$order_pay_info);

        // //返回参数判断
        // $verify = $payment_api->return_verify();
        // if(!$verify) {
        //     showMsg(Language::get('验证失败'),$url,'html','error');
        // }
        // $order_type = $payment_api->order_type;
        // if (!in_array($order_type,array('product_buy','predeposit'))) {
        //     showMsg(Language::get('验证失败'),$url,'html','error');
        // }

        // //取得支付结果
        // $pay_result	= $payment_api->getPayResult($_GET);
        // if (!$pay_result) {
        //     showMsg('非常抱歉，您的订单支付没有成功，请您后尝试',$url,'html','error');
        // }
        // //支付成功后处理
        // if ($order_type == 'predeposit') {
        //     $this->_updatePredeposit($payment_info['payment_code']);
        // } elseif ($order_type == 'product_buy') {
        //     $this->_updateProduct_buy($payment_info['payment_code'],$order_list,$pay_amount);
        // }

    }

    /**
     * 预存款充值在线支付成功后，更新数据表
     *
     */
    private function _updatePredeposit($payment_code) {
        $url	= MALL_SITE_URL."/index.php?app=chongzhi&mod=index";

        //取得记录信息
        $model_pd = Model('predeposit');
        $condition = array();
        $condition['pdr_sn'] = $_GET['out_trade_no'];
        $condition['pdr_payment_state'] = 0;
        $recharge_info = $model_pd->getPdRechargeInfo($condition);
        if (!is_array($recharge_info) || empty($recharge_info)){
            showMsg(Language::get('充值失败'),$url,'html','error');
        }

        //取支付方式信息
        $model_payment = Model('payment');
        $condition = array();
        $condition['payment_code'] = $payment_code;
        $payment_info = $model_payment->getPaymentOpenInfo($condition);
        if(!$payment_info || $payment_info['payment_code'] == 'offline') {
            showMsg(L('系统不支持选定的支付方式:'),'','html','error');
        }

        $condition = array();
        $condition['pdr_sn'] = $recharge_info['pdr_sn'];
        $condition['pdr_payment_state'] = 0;
        $update = array();
        $update['pdr_payment_state'] = 1;
        $update['pdr_payment_time'] = TIMESTAMP;
        $update['pdr_payment_code'] = $payment_code;
        $update['pdr_payment_name'] = $payment_info['payment_name'];
        $update['pdr_trade_sn'] = $_GET['trade_no'];

        try {
            $model_pd->beginTransaction();
            //更改充值状态
            $state = $model_pd->editPdRecharge($update,$condition);
            if (!$state) {
                throw Exception(Language::get('充值失败'));
            }
            //变更会员预存款
            $data = array();
            $data['member_id'] = $recharge_info['pdr_member_id'];
            $data['member_name'] = $recharge_info['pdr_member_name'];
            $data['amount'] = $recharge_info['pdr_amount'];
            $data['pdr_sn'] = $recharge_info['pdr_sn'];
            $model_pd->changePd('recharge',$data);
            $model_pd->commit();
        } catch (Exception $e) {
            $model_pd->rollback();
            showMsg($e->getMessage(),$url,'html','error');
        }

        //财付通需要输出反馈
        if ($payment_code == 'tenpay'){
            $url = MALL_SITE_URL."/index.php?app=payment&mod=payment_success&predeposit=1";
            showMsg(Language::get('充值成功，正在前往我的充值列表'),$url,'tenpay');
        } else {
            showMsg(Language::get('充值成功，正在前往我的充值列表'),$url);
        }
    }

    /**
     * 购买商品在线支付成功后，更新数据表(财付通异步也使用return,不能使用SESSION)
     */
    private function _updateProduct_buy($payment_code,$order_list,$pay_amount) {
        $url = MALL_SITE_URL."/index.php?app=userorder";
        $out_trade_no = $_GET['out_trade_no'];

        if ($_GET['trade_no'] != '') {
            $trade_no = $_GET['trade_no'];
        }

        $model_payment = Model('payment');
        $result = $model_payment->updateProductBuy($out_trade_no, $payment_code, $order_list, $trade_no);
        if(!empty($result['error'])) {
            showMsg($result['error'], $url, 'html', 'error');
        }


        //财付通需要输出反馈
        if ($payment_code == 'tenpay'){
            $url = MALL_SITE_URL."/index.php?app=payment&mod=payment_success";
            showMsg(Language::get('订单支付成功，正在前往我的订单'),$url,'tenpay');
        } else {
            redirect(MALL_SITE_URL.'/index.php?app=buy&mod=pay_ok&pay_sn='.$out_trade_no.'&pay_amount='.sldPriceFormat($pay_amount));
        }
    }

    /**
     * 支付成功
     *
     */
    public function payment_success(){
        Language::read('home_payment_index');
        if ($_GET['predeposit']) {
            $url = MALL_SITE_URL."/index.php?app=chongzhi";
            $lang = Language::get('充值成功，正在前往我的充值列表');
        } else {
            $url = MALL_SITE_URL."/index.php?app=userorder";
            $lang = Language::get('订单支付成功，正在前往我的订单');
        }
        showMsg($lang,$url);
    }
    /**
     * 接收微信请求，接收productid和用户的openid等参数，执行（【统一下单API】返回prepay_id交易会话标识
     */
    public function wxpay_return() {
        $result = Logic('payment')->getPaymentInfo('wxpay');
        if (!$result['state']) {
            Log::record('wxpay not found','RUN');

        }
        new wxpay($result['data'],array());
        require_once BASE_MALL_PATH.'/api/payment/wxpay/native_notify.php';
    }
    /**
     * 微信支付成功，更新订单状态
     */
    public function wxpay_notify() {
        $condition['payment_code'] = 'wxpay';
        $result = Model('payment')->getPaymentOpenInfo($condition);
        $result['payment_config']	= unserialize($result['payment_config']);
        if (!$result['payment_state']) {
            Log::record('wxpay not found','RUN');
        }
        require_once BASE_MALL_PATH.'/api/payment/wxpay/wxpay.php';
        new wxpay($result,array());
        require_once BASE_MALL_PATH.'/api/payment/wxpay/notify.php';
    }

    /**
     * 二维码显示(微信扫码支付)
     */
    public function qrcode() {
        $data = base64_decode($_GET['data']);
        $data = decrypt($data,MD5_KEY,30);
        require_once(BASE_STATIC_PATH.DS.'phpqrcode'.DS.'phpqrcode.php');
        QRcode::png($data);
    }

    public function query_state() {
        if ($_GET['pay_sn'] && intval($_GET['pay_sn']) > 0) {
            $info = Model('order')->getOrderPayInfo(array('pay_sn'=>$_GET['pay_sn'],'buyer_id'=>intval($_GET['buyer_id'])));
            exit(json_encode(array('state'=>($info['api_pay_state'] == '1'),'pay_sn'=>$info['pay_sn'],'type'=>'r')));
        } elseif (intval($_GET['order_id']) > 0) {
            $info = Model('order')->getOrderInfo(array('order_id'=>intval($_GET['order_id']),'buyer_id'=>intval($_GET['buyer_id'])));
            exit(json_encode(array('state'=>($info['order_state'] == '20'),'pay_sn'=>$info['order_sn'],'type'=>'v')));
        } else if ($_GET['pdr_id']) {
            $info = Model('predeposit')->getPdRechargeInfo(array('pdr_id'=>intval($_GET['pdr_id']),'pdr_member_id'=>intval($_GET['buyer_id'])));
            exit(json_encode(array('state'=>($info['pdr_payment_state'] == '1'),'pay_sn'=>$info['pdr_sn'],'type'=>'p')));
        }
    }
    public function h5Sign(){
        $param['cusid'] = TLCUID;
        $param['appid'] = TLAPPID;
        $param['version'] = 12;
        $param['trxamt'] = input('trxamt');
        $param['reqsn'] = input('reqsn');
        $param['charset'] = input('charset');
        $param['returl'] = "http://www.baidu.com";
        $param['notify_url'] = "http://www.baidu.com";
        $param['body'] = input('body');
        $param['remark'] = input('remark');
        $param['randomstr'] =input('randomstr');
        $param['validtime'] = input('validtime');
        //$param['limit_pay'] = TLAPPID;
        //$param['asinfo'] = TLCUID;
        $param['sign'] = self::SignArray($param,15202156609);
        echo $param['sign'];
    }
}