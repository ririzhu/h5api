<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/10/23
 * Time: 11:55
 */
class common_billModel extends Model
{
    public function __construct()
    {
        parent::__construct('dian_bill');
    }
    /*
     * 结算订单列表
     * $condition 条件
     * $field 字段
     * $page 分页
     * $order 排序
     */
    public function getbilllist($condition,$field='*',$page=10,$group='',$order='ob_id desc')
    {
        return $this->table('dian_bill')->where($condition)->field($field)->page($page)->group($group)->order($order)->select();
    }
    /*
     * 获取结算表详情
     */
    public function getBillDesc($condition,$field='*')
    {
        return $this->table('dian_bill')->where($condition)->field($field)->find();
    }
}