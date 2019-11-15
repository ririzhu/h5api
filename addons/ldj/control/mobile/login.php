<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/10/8
 * Time: 14:21
 */

class loginCtl extends mobileHomeCtl {
    private $member_info;
    public function __construct(){
        parent::__construct();
        if(!C('sld_ldjsystem') || !C('ldj_isuse') || !C('dian') || !C('dian_isuse')){
            echo json_encode(['status'=>255,'msg'=>'当前模块未开启']);die;
        }
        $model_mb_user_token = Model('mb_user_token');
        if($_POST['key']){
            $key = trim($_POST['key']);
        }else{
            $key = trim($_GET['key']);
        }
        if(!$key){
            echo json_encode(['status'=>266,'msg'=>Language::get('请登录')]);die;
        }
        $mb_user_token_info = $model_mb_user_token->getMbUserTokenInfoByToken($key);
        if (empty($mb_user_token_info)) {
            echo json_encode(['status'=>266,'msg'=>Language::get('请登录')]);die;
        }

        $model_member = Model('member');
        $member_info = $model_member->getMemberInfoByID($mb_user_token_info['member_id']);
        if(empty($member_info)) {
            echo json_encode(['status'=>266,'msg'=>Language::get('请登录')]);die;
        } else {
            unset($member_info['member_passwd']);
            //读取卖家信息
            $this->member_info = $member_info;
        }
    }
    /**
     * @api {post} index.php?app=login&mod=usercenter&sld_addons=ldj 会员中心
     * @apiVersion 0.1.0
     * @apiName usercenter
     * @apiGroup Member
     * @apiDescription 会员中心
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/cmobile/index.php?app=login&mod=usercenter&sld_addons=ldj
     * @apiParam {String} key 会员登录key值
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} data 会员信息
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200
     *         "data": {
     *                         会员数据...
     *                  }
     *      }
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *
     */
    public function usercenter()
    {
        $return_data = [
            'member_id'=>$this->member_info['member_id'],
            'member_name'=>$this->member_info['member_name'],
            'member_mobile'=>$this->member_info['member_mobile'],
            'member_avatar'=>getMemberAvatar($this->member_info['member_avatar']),
            'available_predeposit'=>$this->member_info['available_predeposit']>0?$this->member_info['available_predeposit']:0,
        ];
        echo json_encode(['status'=>255,'data'=>$return_data]);die;
    }
    public function login()
    {
        if(empty($_POST['username']) || empty($_POST['password']) || !in_array($_POST['client'], $this->client_type_array)) {
            echo json_encode(['status'=>255,'msg'=>'登录失败']);die;
        }

        $data = [
            'username'=>trim($_POST['username']),
            'password'=>trim($_POST['password']),
            'client'=>trim($_POST['client']),
        ];

        if($_POST['loginType'] == 1){
            //手机号验证码登录
            $this->phone_login($data);

        }elseif($_POST['loginType'] == 2){
            //商城账号登录
            $this->shop_login($data);

        }else{
            echo json_encode(['status'=>255,'msg'=>'登录失败']);die;
        }


    }
    /*
     * 手机验证码登录
     */
    private function phone_login($data)
    {

    }
    /*
     * 商城会员登录
     */
    private function shop_login($data)
    {
        $member_model = M('ldj_member');

        $condition = [
            ''
        ];

        $member_info = $member_model->getMemberInfo($condition);

        if(empty($member_info)){
            echo json_encode(['status'=>255,'msg'=>'账号密码错误!']);die;
        }

        //新增登录令牌
        $key = $this->_get_token($member_info['member_id'],$member_info['member_name'],$data['client']);
        if(empty($key)){
            echo json_encode(['status'=>255,'msg'=>'登录失败!']);die;
        }

        echo json_encode(['status'=>200,'member_info'=>$member_info,'key'=>$key]);die;

    }
    /*
     * 生成登录token
     */
    private function _get_token($member_id, $member_name, $client) {
        $model_mb_user_token = M('ldj_login');

        //生成新的token
        $mb_user_token_info = array();
        $token = md5($member_name . strval(TIMESTAMP) . strval(rand(0,999999)));
        $mb_user_token_info['member_id'] = $member_id;
        $mb_user_token_info['member_name'] = $member_name;
        $mb_user_token_info['token'] = $token;
        $mb_user_token_info['login_time'] = TIMESTAMP;
        $mb_user_token_info['client_type'] = $_POST['client']?$_POST['client']:$client;

        $result = $model_mb_user_token->addMbUserToken($mb_user_token_info);

        if($result) {
            return $token;
        } else {
            return null;
        }

    }

    public function send_sms_mobile() {
        //发送手机短信
        $msg = '发送失败';
        $phone = $_POST['mobile'];

        if (strlen($phone) == 11){

            $log_type = $_POST['type'];//短信类型:1为注册,2为登录,3为找回密码
            $model_sms_log = M('ldj_sms_log');
            $condition = array();
            $condition['log_ip'] = getIp();
            $condition['log_type'] = $log_type;
            $sms_log = $model_sms_log->getSmsInfo($condition);
            if(!empty($sms_log) && ($sms_log['add_time'] > TIMESTAMP-600)) {//同一IP十分钟内只能发一条短信
                $msg = '同一IP地址十分钟内，请勿多次获取动态码！';
                //output_error($state);
                echo json_encode(['status'=>255,'msg'=>$msg]);die;
            } else {
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
                        output_data(array('state'=>'failuer','msg'=>$msg));
                    }
                }
                else
                {
                    //output_error($state);
                    output_data(array('state'=>'failuer','msg'=>$msg));
                }
            }
        }
        else
        {
            $msg = '请正确填写手机号码';
            //output_error($state);
            output_data(array('state'=>'failuer','msg'=>$msg));
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
            $model_sms_log = M('ldj_sms_log');
            $sms_log = $model_sms_log->getSmsInfo($condition);
            if(empty($sms_log) || ($sms_log['add_time'] < TIMESTAMP-1800)) {//半小时内进行验证为有效
                $state = '动态码错误或已过期，重新输入';
            }
        }
        exit($state);
    }
}