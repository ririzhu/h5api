<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/10/26
 * Time: 15:39
 */
class ldj_addressModel extends Model
{
    public function __construct()
    {
        parent::__construct('ldj_address');
    }
    /*
     * 查询地址库列表
     */
    public function querylist($where=[],$field='*'){
        return $this->table('area')->where($where)->field($field)->select();
    }
    /*
     * 会员地址列表
     */
    public function memberaddresslist($where=[],$field='*')
    {
        return $this->table('ldj_address')->where($where)->field($field)->select();
    }
    /*
      * 会员单条地址信息
      */
    public function findmemberaddress($where)
    {
        return $this->table('ldj_address')->where($where)->find();
    }
    /*
     * 会员添加地址
     */
    public function add($insert)
    {
        return $this->table('ldj_address')->insert($insert);
    }
    /*
     * 会员修改地址
     */
    public function editaddress($where,$update)
    {
        return $this->table('ldj_address')->where($where)->update($update);
    }
    /*
     * 删除会员地址
     */
    public function dropaddress($where)
    {
        return $this->table('ldj_address')->where($where)->delete();
    }
}