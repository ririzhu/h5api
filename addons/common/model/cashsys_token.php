<?php
/**
 * 客户端商家令牌model
 */

defined('DYMall') or exit('Access Invalid!');

class cashsys_tokenModel extends Model{
    public function __construct(){
        parent::__construct('cashsys_user_token');
    }

    /**
     * 查询
     *
     * @param array $condition 查询条件
     * @return array
     */
    public function getTokenInfo($condition) {
        return $this->where($condition)->find();
    }

    public function getTokenInfoByToken($token) {
        if(empty($token)) {
            return null;
        }
        return $this->getTokenInfo(array('token' => $token));
    }

    /**
     * 新增
     *
     * @param array $param 参数内容
     * @return bool 布尔类型的返回结果
     */
    public function addToken($param){
        return $this->insert($param);
    }

    /**
     * 删除
     *
     * @param int $condition 条件
     * @return bool 布尔类型的返回结果
     */
    public function delToken($condition){
        return $this->where($condition)->delete();
    }
}
