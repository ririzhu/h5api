<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/10/9
 * Time: 20:49
 */
class ldj_goodsModel extends Model
{
    public function __construct()
    {
        parent::__construct();
    }
    /*
     * 门店内搜索商品
     * @param array $where 条件
     * @param string $goodsfield 字段
     * @param int $goodspage 分页
     * @param string $goodsorder 排序
     * @param string $limit 排序
     * @param string $group 排序
     * return array
     */
    public function getGoodsList($where,$goodsfield='*',$goodspage='',$goodsorder='',$limit='',$group='')
    {
        return $this->table('dian_goods,goods')->join('left')->on('dian_goods.goods_id=goods.gid')->where($where)->field($goodsfield)->page($goodspage)->order($goodsorder)->group($group)->limit($limit)->select();
    }
    /*
     * 获取门店商品状态
     * @param array $condition
     * return array
     */
    public function getDianGoods($condition)
    {
        return $this->table('dian_goods')->where($condition)->find();
    }
    /*
     * 修改门店商品
     */
    public function editDianGoods($condition,$update)
    {
        return $this->table('dian_goods')->where($condition)->update($update);
    }
}