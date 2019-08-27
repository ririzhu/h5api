<?php
/**
 * 会员 接口
 *
 */
defined('DYMall') or exit('Access Invalid!');

class memberCtl{

	protected $casher_info = array();

	public function __construct()
	{
		// 验证token 是否有效
		$this->checkToken();
	}

	// 获取会员信息
	public function getMemberInfo()
	{

        $search = trim($_GET['search']) ? trim($_GET['search']) : '';
        $dian_id = $this->casher_info['dian_id'];

        $state = 200;
        $data = '';
        $message = 'success';

        $model_member = M('cashsys_member','cashersystem');
        $condition['member.member_name|member.member_mobile'] = array('like', '%' . $search . '%');;
//        $condition['member.member_name|member.member_mobile'] = $search;
        // $condition['cashsys_member_common.dian_id'] = $dian_id;
        $member_info = $model_member->getMemberInfo($condition,'member.member_id,member.is_buy,member.member_name,member.member_mobile,member.available_predeposit,member.member_points');

        if (!empty($member_info)) {
            if(!$member_info['is_buy']){
                $state = 255;
                $data = '';
                $message = '会员已经被禁止购买,请联系管理员';
            }else{
                $state = 200;
                $data = $member_info;
            }
        }else{
            $state = 255;
            $data = '';
            $message = Language::get('没有数据');
        }

        $return_last = array(
        		'state' => $state,
        		'data' => $data,
        		'msg' => $message,
        	);

        echo json_encode($return_last);
	}

    // 新增 会员
    public function saveMemberInfo()
    {
        $memberData['member_mobile'] = $_POST['member_mobile'];
        $memberData['username'] = $_POST['username'] ? $_POST['username'] : $memberData['member_mobile'] ;

        $run_flag = true;

        $state = 200;
        $data = '';
        $message = 'success';

        if (!$memberData['member_mobile']) {
            $state = 255;
            $message = '参数不全';
            $run_flag = false;
        }
        
        // 校验 手机号格式
        $obj_validate = new Validate();
        $obj_validate->validateparam = array(
            array("input"=>$memberData['member_mobile'], "require"=>"true","validator"=>"mobile","message"=>"手机号格式不正确")
        );
        
        $error = $obj_validate->validate();
        if ($error) {
            $run_flag = false;
            $state = 255;
            $message = strip_tags($error);
        }

        if (is_array($memberData) && !empty($memberData) && $run_flag == true) {
            $model_member = M('cashsys_member','cashersystem');

            $register_info['username'] = $memberData['username'];
            $register_info['mobile'] = $memberData['member_mobile'];
            $register_info['password'] = $register_info['password_confirm'] = $this->createPwd(false);
            
            $common_data['dian_id'] = $this->casher_info['dian_id'];
            $common_data['vid'] = $this->casher_info['vid'];

            $member_inserted_info = Model('member')->mobileRegister($register_info);
            if (isset($member_inserted_info['error'])) {
                $state = 255;
                $message = $member_inserted_info['error'];
            }else{
                $common_data['member_id'] = $member_inserted_info['member_id'];
                $saveflag = $model_member->saveMember($common_data);
                if ($saveflag) {
                    // 发送验证短信
                    $send_msg = $this->sendPwdToPhone($memberData['member_mobile'],$register_info['password']);

                    $state = 200;
                    if ($send_msg) {
                        $message = $send_msg;
                    }else{
                        $message = Language::get('保存成功');
                    }
                }else{
                    $state = 255;
                    Language::get('保存失败');
                }   
            }
        }

        $return_last = array(
                'state' => $state,
                'data' => $data,
                'msg' => $message,
            );

        echo json_encode($return_last);
    }

    // 会员充值
    public function chongzhi()
    {
        $member_id = $_POST['member_id'];
        $money = $_POST['money'];
        $payment_code = $_POST['payment_code'];
        $payment_name = $_POST['payment_name'];
        $auth_code = $_POST['auth_code'] ? $_POST['auth_code'] :'';

        $run_flag = true;

        $state = 200;
        $data = '';
        $message = 'success';

        if (!$member_id && $money && $payment_code && $payment_name) {
            $state = 255;
            $message = '参数不全';
            $run_flag = false;
        }

        if ($run_flag) {
            // 根据memberID  获取会员信息
            $model_member = M('cashsys_member');
            $condition['member.member_id'] = $member_id;
            $member_info = $model_member->getMemberInfo($condition,'member.member_name');

            if (!empty($member_info)) {
                // 创建 充值订单
                $model_pdr = Model('predeposit');

                try {
                    $model_pdr->beginTransaction();

                    $pd_data = array();
                    $pd_data['pdr_sn'] = $pay_sn = $model_pdr->makeSn();
                    $pd_data['pdr_member_id'] = $member_id;
                    $pd_data['pdr_member_name'] = $member_info['member_name'];
                    $pd_data['pdr_amount'] = $money;
                    $pd_data['pdr_payment_code'] = $payment_code;
                    $pd_data['pdr_payment_name'] = $payment_name;
                    $pd_data['pdr_add_time'] = TIMESTAMP;
                    $insert = $model_pdr->addPdRecharge($pd_data);
                    if ($insert) {
                        $cashsys_data['pdr_sn'] = $pay_sn;
                        $cashsys_data['casher_id'] = $this->casher_info['id'];
                        $cashsys_data['vid'] = $this->casher_info['vid'];
                        $cashsys_data['dian_id'] = $this->casher_info['dian_id'];
                        // 添加 收银相关扩展数据
                        M('cashsys_predeposit')->addPdRecharge($cashsys_data);
                    }

                    // 发起支付
                    $pd_data['auth_code'] = $auth_code;
                    $this->pay($pd_data);

                    $model_pdr->commit();

                    $state = 200;
                    $message = 'success';

                } catch (Exception $e) {
                    $model_pdr->rollback();
                    $state = 255;
                    $message = $e->getMessage();
                }

            }else{
                $state = 255;
                $message = '未找到相关会员信息';
                $run_flag = false;
            }
        }

        $return_last = array(
                'state' => $state,
                'data' => $data,
                'msg' => $message,
            );

        echo json_encode($return_last);
    }

    // 支付
    public function pay($order)
    {
        $model_pd = Model('predeposit');

        $pay_sn = $order['pdr_sn'];
        $payment_code = $order['pdr_payment_code'];
        if(!preg_match('/^\d{18}$/',$pay_sn) || !preg_match('/^[a-z]{1,20}$/',$payment_code)){
            // 信息错误
            throw new Exception('非法请求');
        }

        //取支付方式信息
        $model_payment = M('cashsys_payment','cashersystem');
        $condition = array();
        $condition['payment_code'] = $payment_code;
        $payment_info = $model_payment->getPaymentOpenInfo($condition);
        if(!$payment_info || in_array($payment_info['payment_code'],array('offline','predeposit'))) {
            // 支付方式不支持
            throw new Exception('支付方式不支持');
        }
        if ($payment_code == 'cash') {
            // 现金支付
            $condition = array();
            $condition['pdr_sn'] = $pay_sn;
            $condition['pdr_payment_state'] = 0;
            $recharge_info = $model_pd->getPdRechargeInfo($condition);
            if (!empty($recharge_info)) {
                $condition = array();
                $condition['pdr_sn'] = $recharge_info['pdr_sn'];
                $condition['pdr_payment_state'] = 0;
                $update = array();
                $update['pdr_payment_state'] = 1;
                $update['pdr_payment_time'] = TIMESTAMP;
                $update['pdr_payment_code'] = $payment_info['payment_code'];
                $update['pdr_payment_name'] = $payment_info['payment_name'];
                $update['pdr_trade_sn'] = '';

                try {
                    $model_pd->beginTransaction();
                    //更改充值状态
                    $state = $model_pd->editPdRecharge($update,$condition);
                    if (!$state) {
                        throw new Exception('非法请求');
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
                    throw new Exception($e->getMessage());
                }
            }else{
                throw new Exception('非法请求');
            }
        }else{
            $model_pd = Model('predeposit');
            $order_info = $model_pd->getPdRechargeInfo(array('pdr_sn'=>$pay_sn));
            $order_info['subject'] = '门店预存款充值_'.$order_info['pdr_sn'];
            $order_info['order_type'] = 'predeposit';
            $order_info['pay_sn'] = $order_info['pdr_sn'];
            $order_info['pay_amount'] = $order_info['pdr_amount'];
            //其它第三方在线通用支付入口
            $payment_info['auth_code'] = $order['auth_code'];
            $this->_api_pay($order_info,$payment_info);
        }
    }

    // 第三方支付
    public function _api_pay($order_info,$payment_info)
    {
        $auth_code = $payment_info['auth_code'];
        $run_flag = true;
        // 获取配置
        $payment_config = unserialize($payment_info['payment_config']);
        switch ($payment_info['payment_code']) {
            case 'alipay':
                // 支付宝支付
                // 校验用户授权码 是否存在
                if (empty($auth_code)) {
                    $run_flag = false;
                    throw new Exception('未获取授权码');
                }
                if (
                    (
                        empty($payment_config) || 
                        empty($payment_config['alipay_public_key']) || 
                        empty($payment_config['merchant_private_key']) || 
                        empty($payment_config['app_id'])
                    ) && $run_flag
                ) {

                    $run_flag = false;
                    $state = 255;
                    throw new Exception('当前支付未配置');
                }
                if ($run_flag) {
                    // 请求 支付宝支付
                    define('PAYMENT_ROOT', BASE_LIBRARY_PATH . DS .'api/payment');
                    require_once(PAYMENT_ROOT . DS . 'alipay' . DS . 'index.php');
                    // 可传入配置
                    $pay_config['alipay_public_key'] = $payment_config['alipay_public_key'];
                    $pay_config['merchant_private_key'] = $payment_config['merchant_private_key'];
                    $pay_config['app_id'] = $payment_config['app_id'];
                    $pay_Obj = new alipay($pay_config);
                    // 刷卡支付
                    $pay_data['auth_code'] = $auth_code;
                    $pay_data['body'] = $order_info['subject'];
                    $pay_data['pay_sn'] = $order_info['pay_sn'];
                    $pay_data['fee'] = floatval($order_info['pay_amount']);
                    $pay_return = $pay_Obj->micropay($pay_data);
                    if ($pay_return) {
                        // 发起支付 等待
                        // 存储 第三方日志
                        if ($pay_return->code == '10000') {
                            $run_flag = true;
                        }else{
                            $run_flag = false;
                            $state = 255;
                            $message = $pay_return->sub_msg;
                            throw new Exception($message);
                        }
                    }else{
                        // 扫码支付出现问题
                        $run_flag = false;
                        $state = 255;
                        throw new Exception('支付出现问题，请重新支付');
                    }
                    if ($run_flag) {
                        if (function_exists('spl_autoload_register')) {
                            spl_autoload_unregister(array('LtAutoloader', "loadClass"));
                            spl_autoload_register(array('Base', 'autoload'));
                        }
                        $model_pd = Model('predeposit');
                        $condition = array();
                        $condition['pdr_sn'] = $order_info['pay_sn'];
                        $condition['pdr_payment_state'] = 0;
                        $update = array();
                        $update['pdr_payment_state'] = 1;
                        $update['pdr_payment_time'] = TIMESTAMP;
                        $update['pdr_payment_code'] = $payment_info['payment_code'];
                        $update['pdr_payment_name'] = $payment_info['payment_name'];
                        $update['pdr_trade_sn'] = $pay_return->trade_no;

                        try {
                            $model_pd->beginTransaction();
                            //更改充值状态
                            $state = $model_pd->editPdRecharge($update,$condition);
                            if (!$state) {
                                throw new Exception('非法请求');
                            }
                            //变更会员预存款
                            $data = array();
                            $data['member_id'] = $order_info['pdr_member_id'];
                            $data['member_name'] = $order_info['pdr_member_name'];
                            $data['amount'] = $order_info['pdr_amount'];
                            $data['pdr_sn'] = $order_info['pdr_sn'];
                            $model_pd->changePd('recharge',$data);
                            $model_pd->commit();
                        } catch (Exception $e) {
                            $model_pd->rollback();
                            throw new Exception($e->getMessage());
                        }
                    }
                }

                break;
            case 'wxpay':
                // 微信支付
                // 校验用户授权码 是否存在
                if (empty($auth_code)) {
                    $run_flag = false;
                    throw new Exception('未获取授权码');
                }
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
                    throw new Exception('当前支付未配置');
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
                    $wxpay_Obj = new wxpay($pay_config);
                    // 刷卡支付
                    $pay_data['auth_code'] = $auth_code;
                    $pay_data['body'] = $order_info['subject'];
                    $pay_data['pay_sn'] = $order_info['pay_sn'];
                    $pay_data['fee'] = floatval($order_info['pay_amount']) * 100;
                    $pay_return = $wxpay_Obj->micropay($pay_data);
                    if ($pay_return) {
                        // 发起支付 等待
                        // 存储 第三方日志

                        $run_flag = true;
                    }else{
                        // 扫码支付出现问题
                        $run_flag = false;
                        $state = 255;
                        throw new Exception('支付出现问题，请重新支付');
                    }
                    if ($run_flag) {
                        $model_pd = Model('predeposit');
                        $condition = array();
                        $condition['pdr_sn'] = $order_info['pay_sn'];
                        $condition['pdr_payment_state'] = 0;
                        $update = array();
                        $update['pdr_payment_state'] = 1;
                        $update['pdr_payment_time'] = TIMESTAMP;
                        $update['pdr_payment_code'] = $payment_info['payment_code'];
                        $update['pdr_payment_name'] = $payment_info['payment_name'];
                        $update['pdr_trade_sn'] = $pay_return['transaction_id'];

                        try {
                            $model_pd->beginTransaction();
                            //更改充值状态
                            $state = $model_pd->editPdRecharge($update,$condition);
                            if (!$state) {
                                throw new Exception('非法请求');
                            }
                            //变更会员预存款
                            $data = array();
                            $data['member_id'] = $order_info['pdr_member_id'];
                            $data['member_name'] = $order_info['pdr_member_name'];
                            $data['amount'] = $order_info['pdr_amount'];
                            $data['pdr_sn'] = $order_info['pdr_sn'];
                            $model_pd->changePd('recharge',$data);
                            $model_pd->commit();
                        } catch (Exception $e) {
                            $model_pd->rollback();
                            throw new Exception($e->getMessage());
                        }
                    }
                }
                break;
            case 'bank':
                throw new Exception('开发中');
                break;
        }
    }

    // 给指定手机号发送短信
    public function createPwd($show_md5=true)
    {
        // 
        $char_fool = md5(time());
        $pwd_length = 6;

        $max_length = strlen($char_fool) - $pwd_length;
        $rand_start_index = rand(0,$max_length);

        $pwd = substr($char_fool, $rand_start_index, 6);

        $save_pwd = $show_md5 ? md5($pwd) : $pwd;

        return $save_pwd;

    }

    // 给指定手机号发送短信
    public function sendPwdToPhone($phone,$pwd)
    {
        $log_msg = str_replace('#code#',$pwd,C('cashsys_mobile_memo'));
        $log_msg = str_replace('#app#',C('cashsys_mobile_signature'),$log_msg);
        $sms = new Sms();
        $result = $sms->send($phone,$log_msg,C('cashsys_mobile_tplid'));
        if($result){
            $log_array['log_phone'] = $phone;
            $log_array['log_captcha'] = $pwd;
            $log_array['log_ip'] = getIp();
            $log_array['log_msg'] = $log_msg;
            $log_array['log_type'] = 4;
            $log_array['add_time'] = time();
            $model_sms_log = Model('sms_log');
            $model_sms_log->addSms($log_array);
            $msg = '';
            // output_data(array('state' =>$state,'msg'=>$msg));
        } else {
            $msg = Language::get('密码发送失败');
        }

        return $msg;
    }

	// 校验token
	public function checkToken()
	{
		$check_flag = true;
		// 校验token
        $token = $_REQUEST['token'];

        $model_cashsys_token = M('cashsys_token','cashersystem');
        $cashsys_token_info = $model_cashsys_token->getTokenInfoByToken($token);
        if (empty($cashsys_token_info)) {
        	$check_flag = false;
        }

        $model_users = M('cashsys_users');
		$this->casher_info = $model_users->getCashsysUsersInfo(array('id'=>$cashsys_token_info['casher_id']));
        if(empty($this->casher_info)) {
        	$check_flag = false;
        } else {
            $this->casher_info['token'] = $cashsys_token_info['token'];
        }

		if (!$check_flag) {
			$state = 255;
			$data = '';
			$message = Language::get('请登录');
	        $return_last = array(
	        		'state' => $state,
	        		'data' => $data,
	        		'msg' => $message,
	        	);

	        echo json_encode($return_last);exit;
		}
	}

}