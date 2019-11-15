<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/10/8
 * Time: 15:27
 */
class ldj_loginModel extends Model
{
    public function __construct()
    {
        parent::__construct('ldj_mb_user_token');
    }
    /**
     * 新增会员登录令牌
     *
     * @param array $param 参数内容
     * @return bool 返回结果
     */
    public function addMbUserToken($param){
        return $this->insert($param);
    }
}