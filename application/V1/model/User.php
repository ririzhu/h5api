<?php

namespace app\V1\model;

use think\Db;
use think\Exception;
use think\Model;

class User extends Model
{
    protected $table = 'bbc_member';

    public function login($token, $ip)
    {
        $user = model("user");
        $userinfo = $user->where('wx_openid', $token)->find()->toArray();
        return $userinfo;
    }

    /**
    * 查询当前昵称有没有被注册
     * @param $username
     * @return float|string
     */
    public function checkMember($username)
    {
        $user = model("user");
        $count = $user->where('member_name', $username)->count();
        return $count;
    }

    /**查询手机号有没有被注册
     * @param $mobile
     * @return float|string
     */

    public function checkMobile($mobile)
    {
        $user = model("user");
        $count = $user->where('member_mobile', $mobile)->count();
        return $count;
    }
    /**
     * 用户注册，不用手机号
     * $param Array $userData
     */
    public function insertMemberWithOutMobile($userData)
    {
        if($userData['inviteCode']!=""){
            $inviteIds = $this->getInviteIds($userData["inviteCode"]);
            $userData['inviter2_id'] = $inviteIds["inviter_id"];
            $userData['inviter3_id'] = $inviteIds["inviter2_id"];
        }
        Db::startTrans();
        try {
            $res = DB::table("bbc_member")->insert(['member_name'=>$userData["username"],"member_passwd"=>$userData["password"],'inviter_id'=>$userData["inviter_id"],'inviter3_id'=>$userData["inviter3_id"],'inviter3_id'=>$userData["inviter3_id"]]);
            return DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
    /**
     * 获取推荐人的上级和上上级的id
     * $param int inviteid;
     **/
    public function getInviteIds($inviteId)
    {
       return $this->model("bbc_member")->where(["id"=>$inviteId])->field(["inviter_id","inviter2_id","inviter3_id"])->find()->toArray();
    }
    /**
     * 手机号注册
     * $param Array $userData
     */
    public function insertMemberWithMobile($userData)
    {
        if($userData['inviteCode']!=""){
            $inviteIds = $this->getInviteIds($userData["inviteCode"]);
            $userData['inviter2_id'] = $inviteIds["inviter_id"];
            $userData['inviter3_id'] = $inviteIds["inviter2_id"];
        }
        Db::startTrans();
        try {
            $res = DB::table("bbc_member")->insert(["member_mobile"=>$userData["member_mobile"],'inviter_id'=>$userData["inviter_id"],'inviter3_id'=>$userData["inviter3_id"],'inviter3_id'=>$userData["inviter3_id"]]);
            return DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
    /**
     * 更改密码
     * $param int inviteid;
     */
    public function updatePwdWithMobile($userData)
    {
        Db::startTrans();
        try {
            $res = DB::table("bbc_member")->where(["member_mobile"=>$userData['member_mobile']])->update($userData);
            return DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * 获取用户信息
     * @param array $array
     */
    public function getMemberInfo(array $array)
    {
    }
}