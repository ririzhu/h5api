<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/11/16
 * Time: 21:01
 */
class pin_categoryModel extends Model{
    public function __construct() {
        parent::__construct('pin_category');
    }
    /*
     * 获取阶梯团分类
     * $condition 条件
     * $field 字段
     * $page
     */
    public function getlist($condition=[],$field='*',$page='',$order='sort asc')
    {
        return $this->table('pin_category')->where($condition)->field($field)->page($page)->order($order)->select();
    }
    /*
     * 获取单条数据
     * $condition 条件
     * $field 字段
     */
    public function getone($condition,$field)
    {
        return $this->table('pin_category')->where($condition)->field($field)->find();
    }
    /*
     * 新增一条记录
     * $insert
     */
    public function save($insert)
    {
        return $this->table('pin_category')->insert($insert);
    }
    /*
     * 修改一条记录
     * $condition 条件
     * $update 数据
     */
    public function edit($condition,$update)
    {
        return $this->table('pin_category')->where($condition)->update($update);
    }
    /*
     * 删除一条记录
     * $condition
     */
    public function drop($condition)
    {
        return $this->table('pin_category')->where($condition)->delete();
    }
}