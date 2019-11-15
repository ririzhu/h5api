<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/11/19
 * Time: 22:15
 */
class ladder_orderModel extends Model{

    private $tuan_state_array = array(
        0 => '全部',
        1 => '等待开始',
        2 => '进行中',
        3 => '已结束',
    );

    public function __construct() {
        parent::__construct('pin_order');
    }
    /*
        * 订单列表
        * $condition 条件
        * $field 字段
        * $page
        */
    public function getlist($condition=[],$field='*',$page='',$order='order_id desc')
    {
        return $this->table('pin_order')->where($condition)->field($field)->page($page)->order($order)->select();
    }
    /*
     * 获取单条数据
     * $condition 条件
     * $field 字段
     */
    public function getone($condition,$field)
    {
        return $this->table('pin_order')->where($condition)->field($field)->find();
    }
    /*
     * 修改订单信息
     * $condition 条件
     * $update 数据
     */
    public function editorder($condition,$update)
    {
        return $this->table('pin_order')->where($condition)->update($update);
    }
    /**
     * 取单条订单信息
     *
     * @param unknown_type $condition
     * @param array $extend 追加返回那些表的信息,如array('order_common','order_goods','store')
     * @return unknown
     */
    public function getOrderInfo($condition = array(), $extend = array(), $fields = '*', $order = '',$group = '') {

        $order_info = $this->table('order')->field($fields)->where($condition)->group($group)->order($order)->find();
        if (empty($order_info)) {
            return array();
        }

        $order_info['state_desc'] = orderState($order_info);
        $order_info['payment_name'] = orderPaymentName($order_info['payment_code']);

        //追加返回订单扩展表信息
        if (in_array('order_common',$extend)) {
            $order_info['extend_order_common'] = $this->getOrderCommonInfo(array('order_id'=>$order_info['order_id']));
            $order_info['extend_order_common']['reciver_info'] = unserialize($order_info['extend_order_common']['reciver_info']);
            $order_info['extend_order_common']['invoice_info'] = unserialize($order_info['extend_order_common']['invoice_info']);
        }

        //追加返回店铺信息
        if (in_array('store',$extend)) {
            $order_info['extend_store'] = Model('vendor')->getStoreInfo(array('vid'=>$order_info['vid']));
        }

        //返回买家信息
        if (in_array('member',$extend)) {
            $order_info['extend_member'] = Model('member')->getMemberInfo(array('member_id'=>$order_info['buyer_id']));
        }

        //追加返回商品信息
        if (in_array('order_goods',$extend)) {
            //取商品列表
            $order_goods_list = $this->getOrderGoodsList(array('order_id'=>$order_info['order_id']));

            // // 获取最终价格
            // $order_goods_list = Model('goods_activity')->rebuild_goods_data($order_goods_list);

            foreach ($order_goods_list as $value) {
                $item_goods_info = Model('goods')->getGoodsInfoByID($value['gid'],'goods_commonid,goods_serial');
                // 获取 多规格商品 多规格相关信息
                if ($value['has_spec']) {
                    $value['spec_num_arr'] = unserialize($value['spec_num']);
                    // 有规格 (获取规格信息)
                    $spec_array = Model('goods')->getGoodsList(array('goods_commonid' => $item_goods_info['goods_commonid']), 'goods_spec,gid,vid,goods_image,color_id,goods_storage');
                    $spec_list = array();       // 各规格商品地址，js使用
                    foreach ($spec_array as $s_key => $s_value) {
                        $s_array = unserialize($s_value['goods_spec']);

                        $tmp_array = array();
                        if (!empty($s_array) && is_array($s_array)) {
                            foreach ($s_array as $k => $v) {
                                $tmp_array[] = $k;
                            }
                        }
                        sort($tmp_array);
                        $spec_sign = implode('|', $tmp_array);

                        $spec_list[$spec_sign]['storage'] = $s_value['goods_storage'];
                        $spec_list[$spec_sign]['field_name'] = implode('/', $s_array);
                    }
                    $value['spec_data'] = $spec_list;
                }
                if (isset($value['show_price']) && $value['show_price'] > 0) {
                    $value['goods_pay_price'] = $value['show_price'];
                }
                $value['goods_serial'] = $item_goods_info['goods_serial'];
                $value['goods_image_url'] = cthumb($value['goods_image']);
                $order_info['extend_order_goods'][] = $value;
            }
        }

        return $order_info;
    }
    public function getOrderCommonInfo($condition = array(), $field = '*') {
        return $this->table('order_common')->where($condition)->find();
    }
}