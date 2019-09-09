<?php
namespace app\V1\controller;

use app\V1\model\User;

class Login extends Base {
    /**
     * 用户登录
     * @return false|string
     */
	public function index(){
        if(!input("member_name") || !input("member_password")){
            $data['code']=1;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }
        $member_name = input("member_name");
        $member_password = input("member_password");
        $member = new User();
        $field = 'member_id,member_name,member_state';
        $member_info = $member->getMemberInfo(array('member_name'=>$member_name,'member_passwd'=>md5($member_password)),$field);
        if (empty($member_info)){
            $data['code']=2;
            $data['message'] = lang("用户名或者密码错误");
            return json_encode($data,true);
        }
        if ($member_info['member_state'] == 0){
            $data['code']=3;
            $data['message'] = lang("账号被停用");
            return json_encode($data,true);
        }
        $data['code'] = 0;
        $data['message'] = lang("登录成功");
        $data['member_info'] = $member_info;
        return json_encode($data,true);
	}

}
