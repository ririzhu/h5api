<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/11/12
 * Time: 20:52
 */
class common_accountModel extends Model {

    public function __construct()
    {
        parent::__construct('jiesuan_account');
    }
    /*
     * 查询账号列表
     * $condition 条件
     * return array
     */
    public function getAccountList($condition)
    {
        return $this->table('jiesuan_account')->where($condition)->select();
    }
    /*
     * 查询一条记录
     * $condition
     * return array
     */
    public function getAccountInfo($condition)
    {
        return $this->table('jiesuan_account')->where($condition)->find();
    }
    /*
     * 增加一条记录
     * $insert
     * return bool
     */
    public function  addAccountInfo($insert)
    {
        return $this->table('jiesuan_account')->insert($insert);
    }
    /*
     * 修改账号信息
     * $condition
     * $update
     * return bool
     */
    public function editAccountInfo($condition,$update)
    {
        return $this->table('jiesuan_account')->where($condition)->update($update);
    }
    /*
     * 删除账号信息
     * $condition
     * return bool
     */
    public function deleteAccountInfo($condition)
    {
        return $this->table('jiesuan_account')->where($condition)->delete();
    }

}