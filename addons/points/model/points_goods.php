<?php
/**
 * 手机积分商品
 *
 *
 *
 */
defined('DYMall') or exit('Access Invalid!');
class points_goodsModel extends Model
{
    public function __construct()
    {
        parent::__construct('points_goods');
    }
    /*
     * 获取商品详情信息
     * @param array $condition 条件
     * @param string $field 字段
     * return array 商品详情信息
     */
    public function getGoodsDetail($condition,$field='*')
    {
        return $this->where($condition)->field($field)->find();
    }
    /*
     * 修改积分商品表
     */
    public function updatePointsGoods($condition,$update)
    {
        return $this->where($condition)->update($update);
    }
    /*
     * 获取积分商品列表
     */
    public function getPointsGoodslist($condition,$field='*',$order='',$page='',$limit='')
    {
        return $this->where($condition)->field($field)->order($order)->page($page)->limit($limit)->select();
    }

    /*
     * 会员订单列表,联合订单商品表
     */
    public function memberorderlist($condition=[],$order='',$page=10)
    {
        $order_list = $this->table('points_order')->where($condition)->order($order)->page($page)->select();
        if(!$order_list){
            return false;
        }

        foreach($order_list as $k=>$v){
            $goods_num = 0;
            $order_list[$k]['order_state'] = $this->getorderstate($v['point_orderstate']);
            $order_list[$k]['order_goods'] = $this->table('points_ordergoods')->where(['point_orderid'=>$v['point_orderid']])->select();
            array_walk($order_list[$k]['order_goods'],function(&$v) use (&$goods_num){
                $goods_num += $v['point_goodsnum'];
                $v['image'] = pointprodThumb($v['point_goodsimage']);
            });
            $order_list[$k]['goods_number'] = $goods_num;
        }
        return $order_list;

    }

    /*
     * 取订单状态
     */
    public function getorderstate($state)
    {
        $stateArray = [
            '10'=>'待支付',
            '11'=>'已付款',
            '20'=>'待发货',
            '30'=>'待收货',
            '40'=>'已完成',
            '2'=>'已取消',
            '50'=>'已完成'
        ];
        return $stateArray[$state];
    }
}