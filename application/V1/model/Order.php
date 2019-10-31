<?php
namespace app\v1\model;

use Exception;
use think\Model;
use think\db;
class Order extends Model
{
    /**
     * 取得订单列表
     * @param array $condition
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

    /**
     * add by zhengyifan 2019-10-17
     * 取单条订单信息
     * @param array $condition
     * @param array $extend
     * @param string $fields
     * @param string $order
     * @param string $group
     */
    public function getOrderInfo($condition = array(), $extend = array(), $fields = '*', $order = '',$group = '')
    {
        $order_info = DB::name('order')->field($fields)->where($condition)->group($group)->order($order)->find();
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
            $order_goods_list = db::name('order_goods')->where(['order_id' => $order_info['order_id']])->select();

            foreach ($order_goods_list as $key => $value) {
                $model_goods = new Goods();
                $item_goods_info = $model_goods->getGoodsInfoByID($value['gid'],'goods_commonid,goods_serial');
                // 获取 多规格商品 多规格相关信息
                if ($value['has_spec']) {
                    $value['spec_num_arr'] = unserialize($value['spec_num']);
                    // 有规格 (获取规格信息)
                    $spec_array = $model_goods->getGoodsList(array('goods_commonid' => $item_goods_info['goods_commonid']), 'goods_spec,gid,vid,goods_image,color_id,goods_storage');
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

    /**
     * add by zhengyifan 2019-10-17
     * 订单信息扩展表
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getOrderCommonInfo($condition = array(), $field = '*') {
        return DB::name('order_common')->where($condition)->find();
    }

    /**
     * add by zhengyifan 2019-10-17
     * 获取订单支付表
     * @param array $condition
     * @return array
     */
    public function getOrderPayInfo($condition = array()) {
        return DB::name('order_pay')->where($condition)->find();
    }

    /**
     * add by zhengyifan 2019-10-17
     * 更新订单支付信息
     * @param $data
     * @param $condition
     * @return int|string
     */
    public function editOrderPay($data,$condition) {
        return DB::name('order_pay')->where($condition)->update($data);
    }

    /**
     * add by zhengyifan 2019-10-17
     * 添加订单日志
     * @param $data
     * @return int|string
     */
    public function addOrderLog($data) {
        $data['log_role'] = str_replace(array('buyer','seller','system','dian'),array('买家','商家','系统','门店'), $data['log_role']);
        $data['log_time'] = TIMESTAMP;
        return DB::name('order_log')->insert($data);
    }

    /**
     * add by zhengyifan 2019-10-17
     * 更改订单信息
     * @param $data
     * @param $condition
     * @return int|string
     */
    public function editOrder($data,$condition) {
//        if(Config('distribution') && !(Config("sld_spreader") && Config("spreader_isuse"))){
            if ($data['order_state'] == ORDER_STATE_PAY) {
                $order_info = $this->getOrderInfo($condition,array(),'order_id,buyer_id,order_amount,order_sn');
                $this->fanli($order_info);
            }else if($data['order_state']==ORDER_STATE_SUCCESS){
                $list = DB::name('fenxiao_log')->field('*')->where(array('order_id'=>$condition['order_id'],'status'=>0))->select();

                foreach($list as $key => $value){
                    $bs = 'rebate'.$value['description'];

                    $member_model = new User();
                    $member_info=$member_model->getMemberInfoByID($value['reciver_member_id']);
                    $points = new Points();
                    $points->savePointsLog($bs,array('pl_memberid'=>$value['reciver_member_id'],'pl_membername'=>$member_info['member_name'],'rebate_amount'=>$value['yongjin']));

                }
                DB::name('fenxiao_log')->where(array('order_id'=>$condition['order_id'],'status'=>0))->update(array('status'=>1));
            }
//        }

        // 推手系统开启
        if (Config('spreader_isuse') && Config('sld_spreader')) {
            if ($data['order_state'] == ORDER_STATE_PAY) {
                $order_info = $this->getOrderInfo($condition);
                $par['order_id'] = $order_info['order_id'];
                $par['order_amount'] = $order_info['order_amount'];
                // 发送请求 增加分享达成的已付款订单数量
                // 需要在 推手系统内检查该订单是否为 推手订单
                con_addons('spreader',$par,'add_up_order_num','api','mobile');
            }
        }
        return DB::name('order')->where($condition)->update($data);
    }

    /**
     * @param $order_info
     * @throws Exception
     */
    private function fanli($order_info)
    {
        $model_member = new User();
        $model_setting = new Setting();
        $order_id = $order_info['order_id'];

        $list_setting =$model_setting->getListSetting();
        $points_rebate_grade1 = $list_setting['points_rebate_grade1']/100;
        $points_rebate_grade2 = $list_setting['points_rebate_grade2']/100;
        $points_rebate_grade3 = $list_setting['points_rebate_grade3']/100;
        $points_rebate_set = $list_setting['points_rebate_set'];

        $order_goods_info = $this->getOrderInfo(['order_id' => $order_id],['order_goods']);
        $order_goods_list = $order_goods_info['extend_order_goods'];

        $commission_total = 0;
        foreach ($order_goods_list as $key => $value){
            $commission_total += $value['goods_pay_price'];
        }
        $commission_total = $commission_total * $points_rebate_set;

        if ($commission_total > 0){
            $member_info = $model_member->getMemberInfoByID($order_info['buyer_id']);
            $inviter_id = $member_info['inviter_id'];
            if ($inviter_id){
                $array['contribution_member_id'] = $order_info['buyer_id'];
                $array['contribution_member_name'] = $member_info['member_name'];
                $array['reciver_member_id'] = $inviter_id;
                $array['add_time'] = time();
                $array['yongjin'] = $commission_total * $points_rebate_grade1;
                $array['order_id'] = $order_info['order_id'];
                $array['order_total'] = $order_info['order_amount'];
                $array['order_sn'] = $order_info['order_sn'];
                $array['description'] = 1;
                $array['status'] = 0;

                $model_fenxiao = new Fenxiao();
                $result = $model_fenxiao->addRecord($array);
                if (!$result){
                    throw new Exception('一级返利失败');
                }
                
                $inviter2_id = $member_info['inviter2_id'];
                if ($inviter2_id){
                    $array['reciver_member_id'] = $inviter2_id;
                    $array['add_time'] = time();
                    $array['yongjin'] = $commission_total * $points_rebate_grade2;
                    $array['description'] = 2;

                    $result = $model_fenxiao->addRecord($array);
                    if (!$result){
                        throw new Exception('二级返利失败');
                    }
                    
                    $inviter3_id = $member_info['inviter3_id'];
                    if ($inviter3_id){
                        $array['reciver_member_id'] = $inviter3_id;
                        $array['add_time'] = time();
                        $array['yongjin'] = $commission_total * $points_rebate_grade3;
                        $array['description'] = 3;

                        $result = $model_fenxiao->addRecord($array);
                        if (!$result){
                            throw new Exception('三级返利失败');
                        }
                    }
                }
            }
        }
    }
}