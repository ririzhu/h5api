<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/10/8
 * Time: 15:48
 */
class ldj_memberModel extends Model
{
    public function __construct()
    {
        parent::__construct('ldj_member');
    }
    /*
     * 获取单个会员信息
     *
     * @param array $condition 查询条件
     * @return array $member_info 会员信息
     */
    public function getMemberInfo($condition)
    {
        return $this->table('ldj_member')->where($condition)->find();
    }
}