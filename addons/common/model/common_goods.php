<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/10/24
 * Time: 15:29
 */
class common_goodsModel extends Model {

    public function __construct(){
        parent::__construct('dian_goods');
    }

    /**
     * 商品扩展详情
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getCashsysGoodsInfo($condition, $field = '*') {
        return $this->table('dian_goods')->field($field)->where($condition)->find();
    }

    /**
     * 商品列表
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getGoodsList($condition = array(), $field = '*', $page = 0, $group='', $order = '') {
        return $this->table('goods,dian_goods')->on('goods.gid = dian_goods.goods_id')->group($group)->where($condition)->field($field)->page($page)->order($order)->select();
    }

    /**
     * 所在门店信息
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getDianListByGid($gid) {
        $condition['dian_goods.goods_id'] = $gid;
        return $this->table('dian_goods,dian')->on('dian.id = dian_goods.dian_id')->where($condition)->select();
    }

    /**
     * 更新商品SUK数据
     *
     * @param array $update 更新数据
     * @param array $condition 条件
     * @return boolean
     */
    public function editGoods($update, $condition) {
        return $this->table('dian_goods')->where($condition)->update($update);
    }

    // 存储本店分类 一级分类集合
    public function saveGoodsExtend($data){
        return $this->table('cashsys_goods_extend')->insert($data);
    }

    // 清除本店分类 一级分类集合
    public function clearGoodsExtend($condition){
        return $this->table('cashsys_goods_extend')->where($condition)->delete();
    }

}