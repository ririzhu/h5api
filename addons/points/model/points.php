<?php
/**
 * 手机积分商城积分变更日志
 *
 *
 *
 */
defined('DYMall') or exit('Access Invalid!');
class pointsModel extends Model
{
    public function __construct()
    {
        parent::__construct('points_log');
    }
    /*
     * 获取用户积分变更列表
     * @param array $condition 条件
     * @param string $field 字段
     * @param string $order 排序
     * @param int $page 分页
     * return array
     */
    public function getPointsLog($condition=[],$field='*',$order='',$page=10)
    {
        return $this->where($condition)->field($field)->order($order)->page($page)->select();
    }
}