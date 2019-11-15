<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/11/26
 * Time: 17:55
 */
class pre_presaleModel extends Model{
    public $presaleState = ['全部','等待开始', '进行中', '已结束'];
    public function __construct() {
        parent::__construct('presale');
    }
    /*
     * 获取列表
     * $condition 条件
     * $field 字段
     * $page 分页
     * $order 排序
     * return array
     */
    public function getlist($condition=[],$field='*',$page='',$order='pre_id desc')
    {
        return $this->table('presale')->where($condition)->field($field)->page($page)->order($order)->select();
    }
    /*
     * 获取单条记录
     * $condition 条件
     * $field 字段
     * return array
     */
    public function getone($condition=[],$field='*')
    {
        return $this->table('presale')->where($condition)->field($field)->find();
    }
    /*
     * 插入
     * $insert 数据
     * return id
     */
    public function add($insert)
    {
        return $this->table('presale')->insert($insert);
    }
    /*
     * 编辑
     * $condition 条件
     * $update 数据
     * return bool
     */
    public function edit($condition,$update)
    {
        return $this->table('presale')->where($condition)->update($update);
    }
    /*
     * 删除
     * $condition 条件
     * return bool
     */
    public function drop($condition)
    {
        return $this->table('presale')->where($condition)->delete();
    }
    /**
     * 查询团购数量
     * @param array $condition
     * @return int
     */
    public function getTuanCount($condition) {
        return $this->table('presale')->where($condition)->count();
    }
    public function get_state()
    {
        return $this->presaleState;
    }
    //预售缓存
    public function _getPreSaleListByGoodsid_gid() {
        $condition = array();
        $condition['presale.pre_status'] = 1;
//        $condition['sld_start_time'] = array('lt', TIMESTAMP);
        $condition['presale.pre_end_time'] = array('gt', TIMESTAMP);
        $xianshi_goods_list = $this->table('pre_goods,presale')->join('right')->on('pre_goods.pre_id=presale.pre_id')
            ->where($condition)->order('presale.pre_id desc')->select();

        return $xianshi_goods_list;
    }
}