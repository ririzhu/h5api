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
            print_r($userinfo);
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
        }else{
            $userModel =new users();
            $userData["username"] = input("username");
            $userData["password"] = input("password");
            $userData["inviteCode"] = input("inviteCode");
            $userModel->insertMemberWithOutMobile($userData);
        }

    }
}