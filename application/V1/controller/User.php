<?php
/**
 * 用户类
 *
 * 类的详细介绍（可选。）。
 * @author      jack 作者
 * @version     1.0 版本号
 */

namespace app\V1\controller;

use think\Request;
use app\V1\model\User as Users;
use app\V1\model\Sms;
use app\V1\model\UserToken;
class User extends Base
{
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
            $phone = input("mobile");
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


}