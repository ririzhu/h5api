<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/10/10
 * Time: 11:33
 */
class ldj_cartModel extends Model
{
    public function __construct()
    {
        parent::__construct('ldj_cart');
    }
    /*
     * 获取一条购物车信息
     * @param array $condition
     * return array
     */
    public function getGoodsCartInfo($condition)
    {
        return $this->table('ldj_cart')->where($condition)->find();
    }
    /*
     * 获取购物车列表
     * @param array $condition
     * return array
     */
    public function getVendorCartlist($condition)
    {
        return $this->table('ldj_cart')->where($condition)->select();
    }
    /*
     * 添加商品到购物车
     * @param array $insert
     * return int 被影响的行数
     */
    public function insertcart($insert)
    {
        return $this->table('ldj_cart')->insert($insert);
    }
    /*
     * 更新购物车
     * @param array $where
     * @param array $update
     * return int 被影响的行数
     */
    public function updatecart($where,$update)
    {
        return $this->table('ldj_cart')->where($where)->update($update);
    }
    /* 删除购物车
     * @param array $where 条件
     * return int 返回受影响的行数
     */
    public function deletecart($where)
    {
        return $this->table('ldj_cart')->where($where)->delete();
    }
    /*
     * 获取某一店铺的购物车信息
     * @param int $vendorid 店铺id
     * @param int memberid 会员id
     * return array
     */
    public function getVendorCartlistGoods($vendorid='',$memberid='')
    {
        $vid = $vendorid?$vendorid:($_GET['vid']?:$_POST['vid']);

        $cart_model = M('ldj_cart','ldj');
        $goods_model = M('ldj_goods','ldj');
        $dian_model = M('ldj_dian','ldj');

        $where = [
            'vid'=>$vid,
            'buyer_id'=>$memberid
        ];
        $cart_list = $cart_model->getVendorCartlist($where);
        if($cart_list){
            $all_money = 0;
            foreach($cart_list as $k=>$v)
            {
                //图片
                $cart_list[$k]['goods_image'] = cthumb($cart_list[$k]['goods_image']);
                //店铺配送方式
                $dian_info = $goods_model->table('dian')->where(['id'=>$vid])->find();
                $delivery_type = strtr($dian_info['delivery_type'],['kuaidi'=>'门店配送','shangmen'=>'上门自提','mendian'=>'门店配送']);
                $cart_list[$k]['delivery_type'] = array_unique(explode(',',$delivery_type));
                $goods_info = $goods_model->table('dian_goods')->where(['goods_id'=>$v['gid'],'dian_id'=>$vid])->find();
                if($goods_info['off'] || $goods_info['delete']){
                    $cart_list[$k]['error'] = 1;
                    $cart_list[$k]['errorinfo'] = '下架';
                    continue;
                }
                if($goods_info['stock']<1){
                    $cart_list[$k]['error'] = 1;
                    $cart_list[$k]['errorinfo'] = '库存不足';
                    continue;
                }
                $all_money += $v['goods_price']*$v['goods_num'];
                $cart_list[$k]['error'] = 0;
            }

            $return_data = ['list'=>$cart_list,'all_money'=>$all_money,'error'=>0];
            //判断购物车状态能否加入结算
            $dian_state = $dian_model->getDianInfo(['id'=>$vid],$field='status,operation_time,ldj_status');
            $time = explode('-',date('H-i'));
            $times = $time[0]*60+$time[1];
            $operation_time = explode(',',$dian_state['operation_time']);
            if(!$dian_state['status'] || ($times<$operation_time[0] || $times>$operation_time[1])){
                $return_data['error'] = 1;
                $return_data['errormsg'] = '门店休息了';
            }
            if(!$dian_state['ldj_status']){
                $return_data['error'] = 1;
                $return_data['errormsg'] = '门店已关闭';
            }
            return  $return_data;
        }
        return ['list'=>[],'all_money'=>0];
    }
}