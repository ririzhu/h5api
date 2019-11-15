<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/11/26
 * Time: 21:25
 */
class pre_categoryModel extends Model{

    public function __construct() {
        parent::__construct('pre_category');
    }
    /*
     * 获取列表
     * $condition 条件
     * $field 字段
     * $page 分页
     * $order 排序
     * return array
     */
    public function getlist($condition=[],$field='*',$page='',$order='sort asc')
    {
        return $this->table('pre_category')->where($condition)->field($field)->key('id')->page($page)->order($order)->select();
    }
    /*
     * 获取单条记录
     * $condition 条件
     * $field 字段
     * return array
     */
    public function getone($condition=[],$field='*')
    {
        return $this->table('pre_category')->where($condition)->field($field)->find();
    }
    /*
     * 插入
     * $insert 数据
     * return id
     */
    public function add($insert)
    {
        return $this->table('pre_category')->insert($insert);
    }
    /*
     * 编辑
     * $condition 条件
     * $update 数据
     * return bool
     */
    public function edit($condition,$update)
    {
        return $this->table('pre_category')->where($condition)->update($update);
    }
    /*
     * 删除
     * $condition 条件
     * return bool
     */
    public function drop($condition)
    {
        return $this->table('pre_category')->where($condition)->delete();
    }
}