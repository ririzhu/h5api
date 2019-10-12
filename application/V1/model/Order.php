<?php
namespace app\v1\model;

use think\Model;
use think\db;
class Order extends Model
{
    /**
     * 取得订单列表
     * @param unknown $condition
     * @param string $pagesize
     * @param string $field
     * @param string $order
     * @param string $limit
     * @param unknown $extend 追加返回那些表的信息,如array('order_common','order_goods','store')
     * @param array 追加表的条件信息
     * @return Ambigous <multitype:boolean Ambigous <string, mixed> , unknown>
     */
    public function getOrderList($condition, $pagesize = '', $field = '*', $order = 'order_id desc', $limit = '', $extend = array()){
        //联查的主要目的就是筛符合城市分站的店铺，从而筛选订单
        if((isset($condition['vendor.province_id']) && $condition['vendor.province_id']>0)|| (isset($condition['vendor.city_id']) && $condition['vendor.city_id']>0)|| (isset($condition['vendor.area_id']) && $condition['vendor_area_id']>0)){
            $list = db::name('order,vendor')->join('left join')->on('order.vid=vendor.vid')->field($field)->where($condition)->page($pagesize)->order($order)->limit($limit)->select();
        }else{
            $list = db::name('order')->field($field)->where($condition)->page($pagesize)->order($order)->limit($limit)->select();
        }
        if (empty($list)) return array();
        $order_list = array();

        foreach ($list as $order) {
            $order['state_desc'] = orderState($order);
            //退款退货状态说明文字
            if($order['refund_state'] == 2){
                $state = db::name('refund_return')->where(['order_id'=>$order['order_id']])->field('refund_state')->find();
                if($state['refund_state'] == 3){
                    $order['state_desc'] = '退款退货完成';
                }
            }
            $order['payment_name'] = orderPaymentName($order['payment_code']);
            if (!empty($extend)) $order_list[$order['order_id']] = $order;
        }
        if (empty($order_list)) $order_list = $list;

        //追加返回订单扩展表信息
        if (in_array('order_common',$extend)) {
            $order_common_list = $this->getOrderCommonList(array('order_id'=>array('in',array_keys($order_list))));
            foreach ($order_common_list as $value) {
                $order_list[$value['order_id']]['extend_order_common'] = $value;
                $order_list[$value['order_id']]['extend_order_common']['reciver_info'] = @unserialize($value['reciver_info']);
                $order_list[$value['order_id']]['extend_order_common']['invoice_info'] = @unserialize($value['invoice_info']);
            }
        }
        //追加返回店铺信息
        if (in_array('store',$extend)) {
            $store_id_array = array();
            foreach ($order_list as $value) {
                if (!in_array($value['vid'],$store_id_array)) $store_id_array[] = $value['vid'];
            }
            $vendor = new VendorInfo();
            $store_list = $vendor->getStoreList(array('vid'=>array('in',$store_id_array)));
            $store_new_list = array();
            foreach ($store_list as $store) {
                $store_new_list[$store['vid']] = $store;
            }
            foreach ($order_list as $order_id => $order) {
                $order_list[$order_id]['extend_store'] = $store_new_list[$order['vid']];
            }
        }

        //追加返回买家信息
        if (in_array('member',$extend)) {
            $member_id_array = array();
            foreach ($order_list as $value) {
                if (!in_array($value['buyer_id'],$member_id_array)) $member_id_array[] = $value['buyer_id'];
            }
            $member = new User();
            $member_list = db::name('member')->where(array('member_id'=>array('in',$member_id_array)))->limit($pagesize)->key('member_id')->select();
            foreach ($order_list as $order_id => $order) {
                $order_list[$order_id]['extend_member'] = $member_list[$order['buyer_id']];
            }
        }

        //追加返回商品信息
        if (in_array('order_goods',$extend)) {
            //取商品列表
            $order_goods_list = $this->getOrderGoodsList(array('order_id'=>array('in',array_keys($order_list))));

            // // 获取最终价格
            // $order_goods_list = Model('goods_activity')->rebuild_goods_data($order_goods_list);

            foreach ($order_goods_list as $value) {
                $goods = new Goods();
                $item_goods_info = $goods->getGoodsInfoByID($value['gid'],'goods_commonid,gc_id_1');
                // 获取 多规格商品 多规格相关信息
                if ($value['has_spec']) {
                    $value['spec_num_arr'] = unserialize($value['spec_num']);
                    // 有规格 (获取规格信息)
                    $spec_array = $goods->getGoodsList(array('goods_commonid' => $item_goods_info['goods_commonid']), 'goods_spec,gid,vid,goods_image,color_id,goods_storage');
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

                $value['gc_id_1'] = $item_goods_info['gc_id_1'];

                $value['goods_image_url'] = cthumb($value['goods_image'], 240, $value['vid']);
                $order_list[$value['order_id']]['extend_order_goods'][] = $value;
            }
        }

        if(Config('sld_pintuan') && Config('pin_isuse')){
            $order_list=M('pin','pin')->order_list_state($order_list);
        }

        return $order_list;
    }
    /**
     * 取得订单商品表列表
     * @param unknown $condition
     * @param string $fields
     * @param string $limit
     * @param string $page
     * @param string $order
     * @param string $group
     * @param string $key
     */
    public function getOrderGoodsList($condition = array(), $fields = '*', $limit = null, $page = null, $order = 'rec_id desc', $group = null, $key = null) {
        $list = db::name('order_goods')->field($fields)->where($condition)->limit($limit)->order($order)->group($group)->force($key)->page($page)->select();
        //echo db::name("order_goods")->getLastSql();
        return $list;
    }
}