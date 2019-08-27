<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/11/29
 * Time: 21:43
 */
class pre_orderModel extends Model
{
    public function __construct()
    {
        parent::__construct('pre_order');
    }

    /*
     * 获取列表
     * $condition 条件
     * $field 字段
     * $page 分页
     * $order 排序
     * return array
     */
    public function getlist($condition = [], $field = '*', $page = '', $order = '')
    {
        return $this->table('pre_order')->where($condition)->field($field)->page($page)->order($order)->select();
    }

    /*
     * 获取单条记录
     * $condition 条件
     * $field 字段
     * return array
     */
    public function getone($condition = [], $field = '*')
    {
        return $this->table('pre_order')->where($condition)->field($field)->find();
    }

    /*
     * 插入
     * $insert 数据
     * return id
     */
    public function add($insert)
    {
        return $this->table('pre_order')->insert($insert);
    }

    /*
     * 编辑
     * $condition 条件
     * $update 数据
     * return bool
     */
    public function edit($condition, $update)
    {
        return $this->table('pre_order')->where($condition)->update($update);
    }

    /*
     * 删除
     * $condition 条件
     * return bool
     */
    public function drop($condition)
    {
        return $this->table('pre_order')->where($condition)->delete();
    }
}