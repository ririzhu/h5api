<?php
/**
 * 前台登录 退出操作
 */

defined('DYMall') or exit('Access Invalid!');

class login_mobileCtl extends mobileHomeCtl {

	public function __construct(){
		parent::__construct();
	}

	/**
	 * 登录
	 */
	public function index(){
        if(empty($_POST['username']) || empty($_POST['password']) || !in_array($_POST['client'], $this->client_type_array)) {
            output_error('登录失败');
        }

		$model_member = M('ssys_member');

        $array = array();
        $array['member_name']	= $_POST['username'];
        $array['member_passwd']	= md5($_POST['password']);
        $member_info = $model_member->getMemberInfo($array);

        if(!empty($member_info)) {
            $token = $this->_get_token($member_info['member_id'], $member_info['member_name'], $_POST['client']);
            if($token) {
                output_data(array('username' => $member_info['member_name'], 'key' => $token));
            } else {
                output_error('登录失败');
            }
        } else {
            output_error('用户名密码错误');
        }
    }
    /*
     * 登录生成token
     * 商城端
     */
    private function _get_token_shop($member_id, $member_name, $client) {
        $model_mb_user_token = Model('mb_user_token');

        //重新登录后以前的令牌失效
        //暂时停用
        //$condition = array();
        //$condition['member_id'] = $member_id;
        //$condition['client_type'] = $_POST['client'];
        //$model_mb_user_token->delMbUserToken($condition);

        //生成新的token
        $mb_user_token_info = array();
        $token = md5($member_name . strval(TIMESTAMP) . strval(rand(0,999999)));
        $mb_user_token_info['member_id'] = $member_id;
        $mb_user_token_info['member_name'] = $member_name;
        $mb_user_token_info['token'] = $token;
        $mb_user_token_info['login_time'] = TIMESTAMP;
        $mb_user_token_info['client_type'] = $_POST['client'];

        $result = $model_mb_user_token->addMbUserToken($mb_user_token_info);

        if($result) {
            return $token;
        } else {
            return null;
        }

    }
    /**
     * 登录生成token
     */
    private function _get_token($member_id, $member_name, $client) {
        $model_mb_user_token = M('ssys_mb_user_token');

        //重新登录后以前的令牌失效
        //暂时停用
        //$condition = array();
        //$condition['member_id'] = $member_id;
        //$condition['client_type'] = $_POST['client'];
        //$model_mb_user_token->delMbUserToken($condition);

        //生成新的token
        $mb_user_token_info = array();
        $token = md5($member_name . strval(TIMESTAMP) . strval(rand(0,999999)));
        $mb_user_token_info['member_id'] = $member_id;
        $mb_user_token_info['member_name'] = $member_name;
        $mb_user_token_info['token'] = $token;
        $mb_user_token_info['login_time'] = TIMESTAMP;
        $mb_user_token_info['client_type'] = $_POST['client'];

        $result = $model_mb_user_token->addMbUserToken($mb_user_token_info);

        if($result) {
            return $token;
        } else {
            return null;
        }

    }

	/**
	 * 注册
	 */
	public function register(){
		$model_member	= Model('member');

        $register_info = array();
        $register_info['username'] = $_POST['username'];
        $register_info['password'] = $_POST['password'];
        $register_info['password_confirm'] = $_POST['password_confirm'];
        //$register_info['email'] = $_POST['invitername'];
        $inviter_phone = $_POST['invitername'];
        if (strlen($inviter_phone) == 11)
        {
            $member = $model_member->getMemberInfo(array('member_mobile'=> $inviter_phone));//通过邀请人手机获得邀请人信息
            if(!empty($member))
            {
                $register_info['inviter_id'] = $member['member_id'];
            }
        }


        $member_info = $model_member->register($register_info);


        if(!isset($member_info['error'])) {
            $token = $this->_get_token($member_info['member_id'], $member_info['member_name'], $_POST['client']);
            if($token) {
                output_data(array('username' => $member_info['member_name'], 'key' => $token));
            } else {
                output_error('注册失败');
            }
        } else {
			output_error($member_info['error']);
        }

    }



    /**
     * 通过app 手机号注册
     */
    public function mobileregister(){
//        output_data(array('msg'=>'注册未开放','state'=>'failuer'));
        $model_member	= M('ssys_member');

        $register_info = array();
        $register_info['mobile'] = $_POST['mobile'];
        $register_info['username'] = $_POST['mobile'];
        $register_info['password'] = $_POST['password'];
        $sms_vcode = $_POST['vcode'];

        if(strlen($sms_vcode)==6)
        {
            $condition['log_phone'] = $_POST['mobile'];
            $condition['log_captcha'] = $sms_vcode;
            $condition['log_type'] = 1;
            $model_sms_log = M('ssys_sms_log');
            $sms_log = $model_sms_log->getSmsInfo($condition);

            if(empty($sms_log) || ($sms_log['add_time'] < TIMESTAMP-1800)) {//半小时内进行验证为有效
                output_data(array('msg'=>'动态码错误或已过期，重新输入','state'=>'failuer'));
            }
        }else{
            output_data(array('msg'=>'请输入六位动态验证码','state'=>'failuer'));
        }
        $inviter_id = intval(base64_decode($_POST['inviteid']))/999999999;
        $register_info['inviter_id'] = $inviter_id;


        $member_info = $model_member->mobileRegister_wap($register_info);

        $par['ssys_member'] = $member_info;
        $par['register_info'] = $register_info;
        // 申请推手时 创建同商城用户
        $shop_register_result = con_addons('spreader',$par,'create_shop_member_register','api');
        if (!$shop_register_result) {
            $model_member->del($member_info['member_id']);
            $member_info['error'] = '商城系统用户关联失败';
        }

        if(!isset($member_info['error'])) {
            //推手登录令牌
            $token = $this->_get_token($member_info['member_id'], $member_info['member_name'], $_POST['client']);

            //商城会员登录令牌
            $mm_shop_info = model()->table('ssys_member,member')->join('left')->on('ssys_member.shop_member_id=member.member_id')->where(['ssys_member.member_id'=>$member_info['member_id']])->field('member.member_id as mmid,member.member_name as mmname')->find();
            if($mm_shop_info && $mm_shop_info['mmid']){
                $key_shop = $this->_get_token_shop($mm_shop_info['mmid'],$mm_shop_info['mmname'], $_POST['client']);
            }

            if($token) {
                //对会员进行初始状态检测
                M('ssys_order','spreader')->StatisticsMemberOrderMoney($member_info['member_id']);

                $share_code = con_addons('spreader',$member_info['member_id'],'encode_spreader_code','api');

                output_data(array('username' => $member_info['member_name'], 'key' => $token,'key_shop'=>$key_shop,'username_shop'=>$mm_shop_info['mmname'],'share_code'=>$share_code,'state'=>'true'));
            } else {
                output_data(array('msg'=>'注册失败','state'=>'failuer'));
            }
        } else {
            output_error($member_info['error']);
        }
    }


    /**
     * wap 及 app手机号码注册 - 发送验证码
     */
    public function send_sms_mobile() {
        //发送手机短信
        $msg = '发送失败';
        $phone = $_POST['mobile'];

        if (strlen($phone) == 11){

            $log_type = $_POST['type'];//短信类型:1为注册,2为登录,3为找回密码
            $model_sms_log = M('ssys_sms_log','spreader');
            $condition = array();
//            $condition['log_ip'] = getIp();
            $condition['log_type'] = $log_type;
            $sms_log = $model_sms_log->getSmsInfo($condition);
//            if(!empty($sms_log) && ($sms_log['add_time'] > TIMESTAMP-600)) {//同一IP十分钟内只能发一条短信
//                $msg = '同一IP地址十分钟内，请勿多次获取动态码！';
//                //output_error($state);
//                output_data(array('state'=>false,'msg'=>$msg));
//            }
            if(false){

            }
            else
            {
                $state = 'true';
                $log_array = array();
                $model_member = M('ssys_member','spreader');
                $member = $model_member->getMemberInfo(array('member_mobile'=> $phone));
                $captcha = rand(100000, 999999);
                $log_msg = str_replace('#code#',$captcha,C('ssys_mobile_memo'));
                $log_msg = str_replace('#app#',C('ssys_mobile_signature'),$log_msg);
                switch ($log_type) {
                    case '1':
                        if(C('ssys_sms_register') != 1) {
                            $msg = '系统没有开启手机注册功能';
                            $state = 'failuer';
                        }
                        if(!empty($member)) {//检查手机号是否已被注册
                            $msg = '当前手机号已被注册，请更换其他号码。';
                            $state = 'failuer';
                        }
                        break;
                    case '2':
                        if(C('ssys_sms_login') != 1) {
                            $msg = '系统没有开启手机登录功能';
                            $state = 'failuer';
                        }
                        if(empty($member)) {//检查手机号是否已绑定会员
                            $msg = '当前手机号未注册，请检查号码是否正确。';
                            $state = 'failuer';
                        }
                        $log_array['member_id'] = $member['member_id'];
                        $log_array['member_name'] = $member['member_name'];
                        break;
                    case '3':
                        if(C('ssys_sms_password') != 1) {
                            $msg = '系统没有开启手机找回密码功能';
                            $state = 'failuer';
                        }
                        if(empty($member)) {//检查手机号是否已绑定会员
                            $msg = '当前手机号未注册，请检查号码是否正确。';
                            $state = 'failuer';
                        }
                        $log_array['member_id'] = $member['member_id'];
                        $log_array['member_name'] = $member['member_name'];
                        break;
                    default:
                        $msg = '参数错误';
                        $state = 'failuer';
                        break;
                }
                if($state == 'true'){
                    $sms = new Sms();
                    $result = $sms->send($phone,$log_msg,C('ssys_mobile_tplid'));
                    if($result){
                        $log_array['log_phone'] = $phone;
                        $log_array['log_captcha'] = $captcha;
                        $log_array['log_ip'] = getIp();
                        $log_array['log_msg'] = $log_msg;
                        $log_array['log_type'] = $log_type;
                        $log_array['add_time'] = time();
                        $model_sms_log->addSms($log_array);
                        $msg = '手机短信发送成功';
                        output_data(array('state' =>$state,'msg'=>$msg));
                    } else {
                        $msg = '手机短信发送失败';
                        output_data(array('state'=>failuer,'msg'=>$msg));
                    }
                }
                else
                {
                    //output_error($state);
                    output_data(array('state'=>failuer,'msg'=>$msg));
                }
            }
        }
        else
        {
            $msg = '请正确填写手机号码';
            //output_error($state);
            output_data(array('state'=>failuer,'msg'=>$msg));
        }
    }

    /**
     * 验证注册动态码
     */
    public function check_captcha(){
        $state = '验证失败';
        $phone = $_GET['phone'];
        $captcha = $_GET['sms_captcha'];
        if (strlen($phone) == 11 && strlen($captcha) == 6){
            $state = 'true';
            $condition = array();
            $condition['log_phone'] = $phone;
            $condition['log_captcha'] = $captcha;
            $condition['log_type'] = 1;
            $model_sms_log = Model('sms_log');
            $sms_log = $model_sms_log->getSmsInfo($condition);
            if(empty($sms_log) || ($sms_log['add_time'] < TIMESTAMP-1800)) {//半小时内进行验证为有效
                $state = '动态码错误或已过期，重新输入';
            }
        }
        exit($state);
    }

    private function check_sms($para){
        $state = '验证失败';
        $phone = $_GET['phone'];
        $captcha = $_GET['sms_captcha'];
        if (strlen($phone) == 11 && strlen($captcha) == 6){
            $state = 'true';
            $condition = array();
            $condition['log_phone'] = $phone;
            $condition['log_captcha'] = $captcha;
            $condition['log_type'] = 1;
            $model_sms_log = Model('sms_log');
            $sms_log = $model_sms_log->getSmsInfo($condition);
            if(empty($sms_log) || ($sms_log['add_time'] < TIMESTAMP-1800)) {//半小时内进行验证为有效
                $state = '动态码错误或已过期，重新输入';
            }
        }
        exit($state);
    }
    private  function update_member_inviter()
    {
        $model_member	= Model('member');
        //$register_info = array();
        for($i=0;$i<6149;$i=$i+1)
       // $i = 6149;
        {
            $register_info = array();
            $member = $model_member->getMemberInfo(array('member_id'=>$i));
            if(!empty($member))
            {
                $member_inviter = $model_member->getMemberInfo(array('member_id'=>$member['inviter_id']));
                //$register_info['inviter_id'] = $member_inviter['member_id'];

                if(!empty($member_inviter)&&($member_inviter['inviter_id']!=0))
                {
                    $member_inviter2 = $model_member->getMemberInfo(array('member_id'=>$member_inviter['inviter_id']));
                    if(!empty($member_inviter2))
                    {
                        $register_info['inviter2_id'] = $member_inviter2['member_id'];

                        $member_inviter3 = $model_member->getMemberInfo(array('member_id'=>$member_inviter2['inviter_id']));
                        if(!empty($member_inviter3))
                        {
                            $register_info['inviter3_id'] = $member_inviter3['member_id'];
                        }
                    }
                }
            }
            //print_r($register_info);
            $model_member->updateMember($register_info,$i);
        }
    }
     //忘记密码
    public function forgetPassword(){
        $sms_vcode=$_POST['code'];//验证码
        if(strlen($sms_vcode)==6)
        {
            $condition['log_phone'] = $_POST['mobile'];
            $condition['log_captcha'] = $sms_vcode;
            $condition['log_type'] = 3;
            $model_sms_log = Model('sms_log');
            $sms_log = $model_sms_log->getSmsInfo($condition);

            if(empty($sms_log) || ($sms_log['add_time'] < TIMESTAMP-1800)) {//半小时内进行验证为有效
                output_data(array('msg'=>'动态码错误或已过期，重新输入','state'=>'failuer'));
            }
        }else{
            output_data(array('msg'=>'请输入六位动态验证码','state'=>'failuer'));
        }
        $password=$_POST['password'];
        $member=Model('member');
        $data=$member->getUserPassword_wap($password,$_POST['mobile']);
        if($data){
            output_data(array('info'=>'重置密码成功','state'=>'success'));
        }

    }
    /**
     * AJAX验证
     *
     */
    public function check(){
        if (checkSeccode(getSldhash(),$_POST['picyanzheng'])){
            return true;
        }else{
            return false;
        }
    }

    //忘记密码cwap端
    public function cwapforgetPassword(){
        $sms_vcode=$_POST['code'];//验证码
        if(strlen($sms_vcode)==6)
        {
            $condition['log_phone'] = $_POST['mobile'];
            $condition['log_captcha'] = $sms_vcode;
            $condition['log_type'] = 3;
            $model_sms_log = M('ssys_sms_log');
            $sms_log = $model_sms_log->getSmsInfo($condition);

            if(empty($sms_log) || ($sms_log['add_time'] < TIMESTAMP-1800)) {//半小时内进行验证为有效
                output_data(array('msg'=>'动态码错误或已过期，重新输入','state'=>'failuer'));
            }else{
                echo json_encode(1);
                die;
            }
        }else{
            output_data(array('msg'=>'请输入六位动态验证码','state'=>'failuer'));
        }


    }

    //修改密码
    public  function editpass()
    {
        $password=$_POST['password'];
        if(empty($_POST['mobile'])||empty($_POST['code']) ) {
            output_data(array('msg'=>'请输入完整的信息','state'=>'failuer'));
        }

        $member=M('ssys_member');
        $sms_vcode=$_POST['code'];//验证码
        if(strlen($sms_vcode)==6)
        {
            $condition['log_phone'] = $_POST['mobile'];
            $condition['log_captcha'] = $sms_vcode;
            $condition['log_type'] = 3;
            $model_sms_log = M('ssys_sms_log');
            $sms_log = $model_sms_log->getSmsInfo($condition);

            if(empty($sms_log) || ($sms_log['add_time'] < TIMESTAMP-1800)) {//半小时内进行验证为有效
                output_data(array('msg'=>'动态码错误或已过期，请重新操作忘记密码步骤','state'=>'failuer'));
            }
        }else{
            output_data(array('msg'=>'前面验证码输入有误或者过期，请重新操作','state'=>'failuer'));
        }
        $data=$member->getUserPassword_wap($password,$_POST['mobile']);
        if($data){
            // 同步更新 商城用户 密码
            Model('member')->getUserPassword_wap($password,$_POST['mobile']);
            output_data(array('info'=>'重置密码成功','state'=>'success'));
        }
    }



}
