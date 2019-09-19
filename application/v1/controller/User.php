<?php
/**
 * 用户类
 *
 * 类的详细介绍（可选。）。
 * @author      jack 作者
 * @version     1.0 版本号
 */

namespace app\v1\controller;

use app\v1\model\Points;
use think\Config;
use think\Request;
use app\v1\model\User as Users;
use app\v1\model\Sms;
use app\v1\model\UserToken;
use think\db;
class User extends Base
{
    protected $member_info = array();

    public function __construct()
    {
        parent::__construct();
        if(input("member_id")) {
            $this->member_info = $this->getMemberInfoByID(input("member_id"));
            //$this->member_info['client_type'] = $mb_user_token_info['client_type'];
            //$this->member_info['openid'] = $mb_user_token_info['openid'];
            //$this->member_info['token'] = $mb_user_token_info['token'];
        }
    }
    /**
     * 取得会员详细信息（优先查询缓存）
     * 如果未找到，则缓存所有字段
     * @param int $member_id
     * @param string $field 需要取得的缓存键值, 例如：'*','member_name,member_sex'
     * @return array
     */
    public function getMemberInfoByID($member_id, $fields = '*') {
        $base =new Base();
        $member_info = $base->rcache($member_id, 'ssys_member', $fields);
        if (empty($member_info)) {
            $member_info = $this->getMemberInfo(array('member_id'=>$member_id),$fields,true);
            $base->wcache($member_id, $member_info, 'ssys_member');
        }
        return $member_info;
    }
    /**
     * 会员详细信息
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getMemberInfo($condition, $field = '*') {
        $return = DB::name('member')->field($field)->where($condition)->find();
        return $return;
    }
    /**
     * 微信小程序登录
     *
     * @access public
     * @param string $token
     * @return json 返回类型
     */
    public function wxLogin()
    {
        $token = input("token");
        if ($token == null) {
            $data['error_code'] = 10001;
            $data['message'] = "缺少token参数";
            return json_encode($data, true);
        } else if (!$this->request->isPost()) {
            $data['error_code'] = 10002;
            $data['message'] = "使用了非法提交方式";
            return json_encode($data, true);
        } else {
            $userModel = new users();
            $userinfo = $userModel->login($token, $this->request->ip());
        }
    }

    /**
     * 普通注册，不用手机号
     * @param string username
     * @param string password md5
     * @param string code
     * @return json
     * */

    public function registerWithoutMobile()
    {
        if (!$this->request->isPost()) {
            $data['error_code'] = 10001;
            $data['message'] = "使用了非法提交方式";
            return json_encode($data, true);
        } else if (!input("username")) {
            $data['error_code'] = 10003;
            $data['message'] = "缺少username参数";
            return json_encode($data, true);
        } else if (!input("code")) {
            $data['error_code'] = 10004;
            $data['message'] = "缺少code参数";
            return json_encode($data, true);
        } else if (!input("password")) {
            $data['error_code'] = 10005;
            $data['message'] = "缺少密码参数";
            return json_encode($data, true);
        }else if (!input("inviteCode")) {
            $data['error_code'] = 10005;
            $data['message'] = "缺少密码参数";
            return json_encode($data, true);
        } else {
            $userModel = new users();
            $userData["username"] = input("username").trim();
            $userData["password"] = input("password");
            $userData["inviteCode"] = input("inviteCode").trim();
            if($userModel->checkMember($userData["username"])>0){
                $data['error_code'] = 10007;
                $data['message'] = "当前手机号已被注册";
                return json_encode($data, true);
            }
            if ($userModel->insertMemberWithOutMobile($userData)) {
                $data['error_code'] = 200;
                $data['message'] = "注册成功";
                return json_encode($data, true);
            };
        }

    }

    /**
     * 手机号注册
     * @param string mobile
     * @param string snscode
     * @param string code
     * @return json
     */
    public function registerWithMobile()
    {
        if (!$this->request->isPost()) {
            $data['error_code'] = 10001;
            $data['message'] = "使用了非法提交方式";
            return json_encode($data, true);
        } else if (!input("mobile")) {
            $data['error_code'] = 10003;
            $data['message'] = "缺少username参数";
            return json_encode($data, true);
        } else if (!input("code")) {
            $data['error_code'] = 10004;
            $data['message'] = "缺少code参数";
            return json_encode($data, true);
        } else if (!input("snscode")) {
            $data['error_code'] = 10005;
            $data['message'] = "缺少短信验证码参数";
            return json_encode($data, true);
        } else {
            $userModel = new users();
            $countryCode = input("country_code")?86:input("country_code");
            $phone = $countryCode.input("mobile");
            $captcha = input('snscode');
            $condition = array();
            $condition['log_phone'] = $phone;
            $condition['log_captcha'] = $captcha;
            $condition['log_type'] = 2;
            $model_sms_log = new sms();
            $sms_log = $model_sms_log->getSmsInfo($condition);
            if(empty($sms_log) || ($sms_log['add_time'] < TIMESTAMP-1800)) {//半小时内进行验证为有效
                $data['error_code'] = 10015;
                $data['message'] = '动态码错误或已过期，重新输入';
                return json_encode($data,true);
            }
            $userData["member_mobile"] = trim(input("member_mobile"));
            if($userModel->checkMobile($userData["member_mobile"])>0){
                $data['error_code'] = 10008;
                $data['message'] = "当前手机号已被注册";
                return json_encode($data, true);
            }
            $userData["inviteCode"] = input("inviteCode");
            if ($userModel->insertMemberWithMobile($userData)) {
                $member = $userModel->getMemberInfo(array('member_mobile'=> $phone));//检查手机号是否已被注册
                $this->createSession($member);
                $data['error_code'] = 200;
                $data['message'] = '登录成功';
                return json_encode($data,true);
            };
        }

    }
    /**
     * 设置登录密码
     * @param string mobile
     */
    public function setLoginPwd(){
        if (!$this->request->isPost()) {
            $data['error_code'] = 10001;
            $data['message'] = "使用了非法提交方式";
            return json_encode($data, true);
        } else if (!input("mobile")) {
            $data['error_code'] = 10003;
            $data['message'] = "缺少username参数";
            return json_encode($data, true);
        } else if (!input("password")) {
            $data['error_code'] = 10007;
            $data['message'] = "缺少密码参数";
            return json_encode($data, true);
        }else {
            $userModel = new users();
            $userData["member_mobile"] = trim(input("member_mobile"));
            $userData["member_passwd"] = md5(input("password"));
            if ($userModel->updatePwdWithMobile($userData)) {
                $data['error_code'] = 200;
                $data['message'] = "设置成功";
                return json_encode($data, true);
            };
        }
    }
    /**
     * 登录时创建会话SESSION
     *
     * @param array $member_info 会员信息
     */
    public function createSession($member_info = array()) {
        if (empty($member_info) || !is_array($member_info)) return ;
        $_SESSION['is_login']	= '1';
        $_SESSION['member_id']	= $member_info['member_id'];
        $_SESSION['member_name']= $member_info['member_name'];
        $_SESSION['member_email']= $member_info['member_email'];
        $_SESSION['is_buy']		= $member_info['is_buy'];
        $_SESSION['avatar'] 	= $member_info['member_avatar'];
        $vendorinfo = Model('seller')->getSellerInfo(array('member_id'=>$_SESSION['member_id']));
        $_SESSION['vid'] = $vendorinfo['vid'];
        if (trim($member_info['member_qqopenid'])){
            $_SESSION['openid']		= $member_info['member_qqopenid'];
        }
        if (trim($member_info['member_sinaopenid'])){
            $_SESSION['slast_key']['uid'] = $member_info['member_sinaopenid'];
        }
        if(!empty($member_info['member_login_time'])) {//登录时间更新
            $update_info	= array(
                'member_login_time'=> time(),
                'member_old_login_time'=> $member_info['member_login_time'],
                'member_login_ip'=> getIp(),
                'member_old_login_ip'=> $member_info['member_login_ip']);
            $this->updateMember($update_info,$member_info['member_id']);
        }
        // 自动登录
        if ($member_info['auto_login'] == 1) {
            $this->auto_login();
        }
        //王强加入pc端token
        $model_mb_user_token = new UserToken();

        //重新登录后以前的令牌失效
        //暂时停用
        //$condition = array();
        //$condition['member_id'] = $member_id;
        //$condition['client_type'] = $_POST['client'];
        //$model_mb_user_token->delMbUserToken($condition);

        //生成新的token
        $mb_user_token_info = array();
        $token = md5($member_info['member_name'] . strval(TIMESTAMP) . strval(rand(0,999999)));
        $mb_user_token_info['member_id'] = $member_info['member_id'];
        $mb_user_token_info['member_name'] = $member_info['member_name'];
        $mb_user_token_info['token'] = $token;
        $mb_user_token_info['login_time'] = TIMESTAMP;
        $mb_user_token_info['client_type'] = 'pc';

        $result = $model_mb_user_token->addMbUserToken($mb_user_token_info);
        $_SESSION['token_key'] 	= $token;

    }

    /**
     * @return mixed国际区号列表
     */
    public function countries(){
        $countries = include("countries.php");
        return $countries;
    }
// 签到操作

    /**
     *
     */
    public function checkIn()
    {
        $checkin_stage = 'checkin';
        $return_arr = array();
        $log_list = array();
        $memberId = input("member_id");

        $eachNum = 10;
        if (Config(['app'])['app']['points_isuse'] == 1){

            // 校验 该用户 今天是否 签到；已签到用户不能再次签到
            $points_model = new Points();
            $condition = array();
            $condition['pl_memberid'] = input("member_id");
            $s_time = strtotime(date('Y-m-d',time()));
            $e_time = $s_time + 86400;
            $condition['saddtime'] = $s_time;
            $condition['eaddtime'] = $e_time;
            $condition['pl_stage'] = $checkin_stage;
            $has_checked_flag = $points_model->getPointsInfo($condition,'pl_id');
            if (!$has_checked_flag) {
                //添加会员积分
                $points_model->savePointsLog($checkin_stage,array('pl_memberid'=>$this->member_info['member_id'],'pl_membername'=>$this->member_info['member_name'],'pl_points'=>Config('points_checkin')));

                $state = 'success';
                $message = '签到成功';

                // $page_count = $points_model->gettotalpage();
            }else{
                $state = 'failuer';
                $message = '每日只可签到一次';
            }// 获取 当前会员的签到记录列表
            $log_condition = array();
            $log_condition['pl_memberid'] = $this->member_info['member_id'];
            $log_condition['pl_stage'] = $checkin_stage;
            $log_condition['order'] = 'pl_addtime desc';
            $log_list = $points_model->getPointsLogList($log_condition,$eachNum);
        }else{
            $state = 'failuer';
            $message = '积分功能未开启';
        }

        $return_arr['state'] = $state;
        $return_arr['msg'] = $message;
        //计算连续签到天数
        $return_arr['checkin_counts'] = $points_model->checkinDays($checkin_stage,array('pl_memberid'=>$this->member_info['member_id'],'pl_membername'=>$this->member_info['member_name'],'pl_points'=>Config('points_checkin')));
        $return_arr['log_list'] = $log_list;
        if (isset($log_list) && !empty($log_list)) {
            $return_arr['list'] = $log_list;
        }

        return json_encode($return_arr);
    }
    /**
     * 我的收货地址列表
     */
    public function myAddressList($memberId = null){
        if($memberId == null) {
            if (!input("member_id") || $memberId = null) {
                $data['error_code'] = 10016;
                $data['message'] = lang("缺少参数");
                return json_encode($data, true);
            }
            $memberId = input("member_id");
            $data['error_code'] = 200;
            $data['message'] = lang("成功");
        }else{
            $data['error_code'] = 200;
            $data['message'] = lang("保存成功");
        }
        $data['data'] = DB::name("address")->where("member_id=$memberId")->order("is_default","desc")->select();

        return json_encode($data,true);
    }
    /**
     * 新增，修改收货地址
     */
    public function updateAddress(){
        if(!input("member_id") || !input("true_name") || !input("area_id") || !input("city_id") || !input("area_info") || !input("mob_phone") || !input("address")){
            $data['error_code'] = 10016;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }
        $param['member_id'] = input("member_id");
        $param['true_name'] = input("true_name");
        $param['area_id'] = input("area_id");
        $param['city_id'] = input("city_id");
        $param['area_info'] = input("area_info");
        $param['address'] = input("address");
        $param['mob_phone'] = input("mob_phone");
        //编辑地址
        if(input("address_id")){
            $address_id = input("address_id");
            $res = db::name("address")->where("member_id=".$param["member_id"] ." and address_id=$address_id")->update($param);
            if($res){
                $data['error_code'] = 200;
                $data['message'] = lang("保存成功");
            }
            else{
                $data['error_code']=10200;
                $data['message'] = lang("保存失败");
            }
            return json_encode($data,true);
        }
        $count = db::name("address")->where("member_id=".$param['member_id'])->count();
        if($count == 0){
            $param['is_default'] = 1;
        }
        else {
            $param['is_default'] = input("is_default");
            if($param['is_default'] == 1){
                db::name("address")->where("member_id=".$param["member_id"])->update(array("is_default"=>0));
            }
        }
        //print_r($param);
        $res = db::name("address")->insert($param);
        if($res==true){
            return $this->myAddressList(input("member_id"));
        }else{
            $data['error_code']=10200;
            $data['message'] = lang("保存失败");
            return json_encode($data,true);
        }

    }
    /**
     * 删除地址
     */
    public function delAddress(){
        if(!input("member_id") || !input("address_id")){
            $data['error_code'] = 10016;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }else{
            $param['address_id']=input("address_id");
            $param['member_id'] = input("member_id");
            $res = db::name("address")->delete($param);
            if($res){
                $data['error_code'] = 200;
                $data['message'] = lang("删除成功");
            }
            else{
                $data['error_code'] = 10201;
                $data['message'] = lang("删除失败");
            }
            return json_encode($data,true);
        }
    }
}