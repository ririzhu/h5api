<?php
/**
 * 门店商品 管理
 *
 */
defined('DYMall') or exit('Access Invalid!');
class cashsys_orderModel extends Model {

    public function __construct(){
        parent::__construct('cashsys_order');
    }

    /**
     * 订单列表
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getOrderList($condition = array(), $field = '*', $page = 0, $group='', $limit = '', $order = '') {
        return $this->table('cashsys_order')->group($group)->where($condition)->field($field)->page($page)->order($order)->select();
    }

    /**
     * 订单商品信息
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getGoodsListByOrderId($orderId) {
    	$condition['order_id'] = $orderId;
        return $this->table('cashsys_order_goods')->field($fields)->where($condition)->limit($limit)->order($order)->group($group)->key($key)->page($page)->select();
    }

    /**
     * 添加订单日志
     */
    public function addOrderLog($data) {
        $data['log_role'] = str_replace(array('dian'),array('门店'), $data['log_role']);
        $data['log_time'] = TIMESTAMP;
        return $this->table('cashsys_order_log')->insert($data);
    }

    /**
     * 插入订单支付表信息
     * @param array $data
     * @return int 返回 insert_id
     */
    public function addOrderPay($data) {
        return $this->table('cashsys_order_pay')->insert($data);
    }


    /**
     * 插入订单表信息
     * @param array $data
     * @return int 返回 insert_id
     */
    public function addOrder($data) {
        return $this->table('cashsys_order')->insert($data);
    }
    
    /**
     * 插入订单扩展表信息
     * @param array $data
     * @return int 返回 insert_id
     */
    public function addOrderGoods($data) {
        return $this->table('cashsys_order_goods')->insertAll($data);
    }

    /**
     * 取单条订单信息
     *
     * @param unknown_type $condition
     * @param array $extend 追加返回那些表的信息,如array('order_common','order_goods','store')
     * @return unknown
     */
    public function getOrderInfo($condition = array(), $extend = array(), $fields = '*', $order = '',$group = '') {
        $order_info = $this->table('cashsys_order')->field($fields)->where($condition)->group($group)->order($order)->find();
        if (empty($order_info)) {
            return array();
        }

        //返回买家信息
        if (in_array('member',$extend)) {
            $order_info['extend_member'] = Model('member')->getMemberInfo(array('member_id'=>$order_info['buyer_id']));
        }

        //追加返回商品信息
        if (in_array('order_goods',$extend)) {
            //取商品列表
            $order_goods_list = $this->getOrderGoodsList(array('order_id'=>$order_info['order_id']));

            // 获取最终价格
            $order_goods_list = Model('goods_activity')->rebuild_goods_data($order_goods_list);

            foreach ($order_goods_list as $value) {
                $item_goods_info = Model('goods')->getGoodsInfoByID($value['gid'],'goods_commonid');
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
                $value['goods_image_url'] = cthumb($value['goods_image']);
                $order_info['extend_order_goods'][] = $value;
            }
        }

        return $order_info;
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
        return $this->table('cashsys_order_goods')->field($fields)->where($condition)->limit($limit)->order($order)->group($group)->key($key)->page($page)->select();
    }

    // 核销操作的收银员关联数据存储
    public function saveCasherActionData($data)
    {
        return $this->table('cashsys_order_extend')->insert($data);
    }

    // 核销操作的收银员关联数据查询
    public function getCasherActionData($condition)
    {
        return $this->table('cashsys_order_extend')->where($condition)->select();
    }
    

    /**
     * 更改订单信息
     *
     * @param unknown_type $data
     * @param unknown_type $condition
     */
    public function editOrder($data,$condition) {
        return $this->table('cashsys_order')->where($condition)->update($data);
    }
    
    /**
     * 取得订单商品表详细信息
     * @param unknown $condition
     * @param string $fields
     * @param string $order
     */
    public function getOrderGoodsInfo($condition = array(), $fields = '*', $order = '') {
        return $this->table('cashsys_order_goods')->where($condition)->field($fields)->order($order)->find();
    }

    // 删除 订单
    public function deleteOrder($condition)
    {
        return $this->table('cashsys_order')->where($condition)->delete();
    }

    
}