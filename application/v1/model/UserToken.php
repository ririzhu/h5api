<?php
namespace app\v1\model;

use think\Model;
use think\Db;

class UserToken extends Model
{public function __construct(){
    parent::__construct('mb_user_token');
}

    /**
     * 查询
     *
     * @param array $condition 查询条件
     * @return array
     */
    public function getMbUserTokenInfo($condition) {
        return $this->where($condition)->find();
    }

    public function getMbUserTokenInfoByToken($token) {
        if(empty($token)) {
            return null;
        }
        return $this->getMbUserTokenInfo(array('token' => $token));
    }
    public function updateMemberOpenId($token, $openId)
    {
        return $this->where(array(
            'token' => $token,
        ))->update(array(
            'openid' => $openId,
        ));
    }
    /**
     * 新增
     *
     * @param array $param 参数内容
     * @return bool 布尔类型的返回结果
     */
    public function addMbUserToken($param){
//        return $this->insert($param);
        return DB::name("mb_user_token")->insert($param);
    }

    /**
     * 删除
     *
     * @param int $condition 条件
     * @return bool 布尔类型的返回结果
     */
    public function delMbUserToken($condition){
        return $this->where($condition)->delete();
    }

}