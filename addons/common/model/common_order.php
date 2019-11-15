<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/10/12
 * Time: 14:48
 */
class common_orderModel extends Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /* 取一条订单信息(联到家)
     * $condition 条件
     * $field 字段
     * $ismore 是否取出多条
     * return array
     */
    public function order_info($condition,$field='*',$ismore=0)
    {
        $order_list = $this->table('ldj_order')->where($condition)->field($field)->select();
        if($ismore){
                return $order_list;
        }
        return $order_list[0];
    }
    /*
     * 取出一条收银订单表
     * $condition 条件
     * $field 字段
     * $ismore 是否取出多条
     * return array
     */
    public function cash_order_info($condition,$field='*',$ismore=0)
    {
        $order_list = $this->table('cashsys_order')->where($condition)->field($field)->select();
        if($ismore){
            return $order_list;
        }
        return $order_list[0];
    }
    /*
     * 取出一条商城订单表
     * $condition 条件
     * $field 字段
     * $ismore 是否取出多条
     * return array
     */
    public function shop_order_info($condition,$field='*',$ismore=0)
    {
        $order_list = $this->table('order')->where($condition)->field($field)->select();
        if($ismore){
            return $order_list;
        }
        return $order_list[0];
    }
    /*
     * 修改订单信息(联到家)
     * $condition 条件
     * $update 数据
     */
    public function editOrder($condition,$update)
    {
        return $this->table('ldj_order')->where($condition)->update($update);
    }
    /*
     * 统计联到家订单
     */
    //    public function statisticsOrder()
    //    {
    //
    //    }
}