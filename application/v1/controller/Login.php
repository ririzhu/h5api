<?php
namespace app\v1\controller;

use app\v1\model\Growthvalue;
use app\v1\model\Points;
use app\v1\model\User;

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
        $field = "member_id,member_name,member_state,member_login_num,member_login_time,member_email,is_buy,member_avatar,member_qqopenid,member_sinaopenid,member_login_ip";
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

        $member->createSession($member_info);
        //更新登录次数
        $member->updateMember(array('member_login_num'=> ($member_info['member_login_num']+1)),$member_info['member_id']);

        //添加会员积分
        if (config('points_isuse')){
            //一天内只有第一次登录赠送积分
            if(trim(date('Y-m-d',$member_info['member_login_time']))!=trim(date('Y-m-d'))){
                $points = new Points();
                $points_param = array('pl_memberid'=>$member_info['member_id'],'pl_membername'=>$member_info['member_name']);
                $points->savePointsLog('login',$points_param);
            }
        }

        // 添加会员经验值
        if(config("growthvalue_rule"))
        {
            if(trim(date('Y-m-d',$member_info['member_login_time']))!=trim(date('Y-m-d'))){
                $growthvalue = new Growthvalue();
                $growth_param = array('growth_memberid'=>$member_info['member_id'],'growth_membername'=>$member_info['member_name']);
                $growthvalue->saveGrowthValue('login',$growth_param);
            }
        }
        //如果连续登录7天,奖励积分
        $member->getLoginDays(array('pl_memberid'=>$member_info['member_id'],'pl_membername'=>$member_info['member_name']));

        $data['code'] = 0;
        $data['message'] = lang("登录成功");
        $data['member_info'] = $member_info;
        return json_encode($data,true);
	}

}
