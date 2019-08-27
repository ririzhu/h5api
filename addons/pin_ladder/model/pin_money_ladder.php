<?php

/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/11/17
 * Time: 16:25
 */
class pin_money_ladderModel extends Model{
    public function __construct() {
        parent::__construct('pin_money_ladder');
    }
    /*
     * 查询阶梯记录
     * $condition 条件
     * $field 字段
     */
    public function getlist($condition,$field)
    {
        return $this->table('pin_money_ladder')->where($condition)->field($field)->select();
    }
    /*
     * 获取一条记录
     * $condition 条件
     * $field 字段
     */
    public  function  getone($condition,$field)
    {
            return $this->table('pin_money_ladder')->where($condition)->field($field)->find();
    }
}