<?php
namespace app\V1\model;

use Cache;
use Exception;
use think\Model;
use think\db;
use think\Queue;
class UserOrder extends Model
{
    /**
     * 取单条订单信息
     *
     * @param unknown_type $condition
     * @param array $extend 追加返回那些表的信息,如array('order_common','order_goods','store')
     * @return unknown
     */
    public function getOrderInfo($condition = array(), $extend = array(), $fields = '*', $order = '',$group = '') {
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
            $vendor = new VendorInfo();
            $order_info['extend_store'] = $vendor->getStoreInfo(array('vid'=>$order_info['vid']));
        }

        //返回买家信息
        if (in_array('member',$extend)) {
            $member = new User();
            $order_info['extend_member'] = $member->getMemberInfo(array('member_id'=>$order_info['buyer_id']));
        }

        //追加返回商品信息
        if (in_array('order_goods',$extend)) {
            //取商品列表
            $order_goods_list = $this->getOrderGoodsList(array('order_id'=>$order_info['order_id']));

            // // 获取最终价格
            // $order_goods_list = Model('goods_activity')->rebuild_goods_data($order_goods_list);

            foreach ($order_goods_list as $value) {
                $goods = new Goods();
                $item_goods_info = $goods->getGoodsInfoByID($value['gid'],'goods_commonid,goods_serial');
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
        return DB::name('order_common')->where($condition)->find();
    }

    public function getOrderPayInfo($condition = array()) {
        return DB::name('order_pay')->where($condition)->find();
    }

    /**
     * 取得支付单列表
     *
     * @param unknown_type $condition
     * @param unknown_type $pagesize
     * @param unknown_type $filed
     * @param unknown_type $order
     * @param string $key 以哪个字段作为下标,这里一般指pay_id
     * @return unknown
     */
    public function getOrderPayList($condition, $pagesize = '', $filed = '*', $order = '', $key = '') {
        $list = DB::name('order_pay')->field($filed)->where($condition)->order($order)->page($pagesize)->cache($key)->select();
        return $list;
    }

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
        if((isset($condition['goods.province_id']) && $condition['goods.province_id']>0)||(isset($condition['goods.city_id']) && $condition['goods.city_id']>0)||(isset($condition['goods.area_id']) && $condition['goods.area_id']>0)){
            $list = DB::name('order')->join('bbc_vendor','bbc_order.vid=vendor.vid')->join("order_goods","order.order_id=order_goods.order_id")->join("goods","goods.gid=order_goods.gid")->field($field.",bbc_vendor.*")->where($condition)->page($pagesize)->order($order)->limit($limit)->select();
        }else{
            $list = DB::name('order')->join("bbc_order_goods","bbc_order.order_id=bbc_order_goods.order_id")->join("bbc_goods","bbc_goods.gid=bbc_order_goods.gid")->field($field)->where($condition)->page($pagesize)->order($order)->limit($limit)->select();
        }
        if (empty($list)) return array();
        $order_list = array();
        //$model = model();
        //print_r($list);die;
        foreach ($list as $order) {
            $order['state_desc'] = orderState($order);
            //退款退货状态说明文字
            if($order['refund_state'] == 2){
                $state = $model->table('refund_return')->where(['order_id'=>$order['order_id']])->field('refund_state')->find();
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
            $order_common_list = $this->getOrderCommonList(array('order_id'=>array('in',arrayToString(array_keys($order_list)))));
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
            $vendor=new VendorInfo();
            $store_list = $vendor->getStoreList("vid in (".arrayToString($store_id_array).")");
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
            $member_list = DB::name('member')->where(array('member_id'=>array('in',$member_id_array)))->limit($pagesize)->cache('member_id')->select();
            foreach ($order_list as $order_id => $order) {
                $order_list[$order_id]['extend_member'] = $member_list[$order['buyer_id']];
            }
        }

        //追加返回商品信息
        if (in_array('order_goods',$extend)) {
            //取商品列表
            $ids = "";
            foreach($order_list as $k=>$v){
                $ids.=$k.",";
            };
            $ids = substr($ids,0,strlen($ids)-1);
            $order_goods_list = $this->getOrderGoodsList("order_id in($ids)");

            // // 获取最终价格
            // $order_goods_list = Model('goods_activity')->rebuild_goods_data($order_goods_list);

            foreach ($order_goods_list as $value) {
                $goodsModel = new Goods();
                $item_goods_info = $goodsModel->getGoodsInfoByID($value['gid'],'goods_commonid,gc_id_1');
                // 获取 多规格商品 多规格相关信息
                if ($value['has_spec']) {
                    $value['spec_num_arr'] = unserialize($value['spec_num']);
                    // 有规格 (获取规格信息)
                    $spec_array = $goodsModel->getGoodsList(array('goods_commonid' => $item_goods_info['goods_commonid']), 'goods_spec,gid,vid,goods_image,color_id,goods_storage');
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
    /*
     * 获取订单表的商品信息,排除活动和库存不足的商品
     * order_id 订单id
     */
    public function getOrderAgainGoodsList($order_id)
    {
        $goods_list = $this->table('order_goods')->where(['order_id'=>$order_id])->select();
        // 获取最终价格
        $goods_activity = Model('goods_activity');
//        $goods_info = $goods_activity->rebuild_goods_data($goods_list);
//        dd($goods_info);die;
        //检测商品库存
        $return_data = [];
        foreach($goods_list as $k=>$v){
            $goods_info = $this->table('goods')->where(['gid'=>$v['gid']])->find();
            $goods_info = $goods_activity->rebuild_goods_data($goods_info);
            //去除带活动的商品
            if(!empty($goods_info['promotion_type']) && intval($goods_info['promotions_id'])>0){
                continue;
            }
            if($goods_info['goods_state'] != 1 || $goods_info['goods_verify'] != 1){
                continue;
//                $return_data[] = ['gid'=>$v['gid'],'num'=>$v['goods_num'],'error'=>1];
            }
            if($goods_info['goods_storage'] >= $v['goods_num']){
                $return_data[] = ['gid'=>$v['gid'],'num'=>$v['goods_num'],'error'=>0];
            }elseif($goods_info['goods_storage'] < $v['goods_num'] && $goods_info['goods_storage'] >0){
                $return_data[] = ['gid'=>$v['gid'],'num'=>$goods_info['goods_storage'],'error'=>0];
            }else{
                continue;
//                $return_data[] = ['gid'=>$v['gid'],'num'=>$v['goods_num'],'error'=>1];
            }

        }
        return $return_data;
    }
    /**
     * 取得订单列表 专门给门店使用的
     * @param unknown $condition
     * @param string $pagesize
     * @param string $field
     * @param string $order
     * @param string $limit
     * @param unknown $extend 追加返回那些表的信息,如array('order_common','order_goods','store')
     * @param array 追加表的条件信息
     * @return Ambigous <multitype:boolean Ambigous <string, mixed> , unknown>
     */
    public function getOrderListForDian($condition, $pagesize = '', $field = '*', $order = '`order`.order_id desc', $limit = '', $extend = array()){
        $list = $this->table('order,order_common')->join('left')->on('order.order_id=order_common.order_id')->field($field)->where($condition)->page($pagesize)->order($order)->limit($limit)->select();
        if (empty($list)) return array();
        $order_list = array();
        foreach ($list as $order) {
            $order['state_desc'] = orderState($order);
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
            $store_list = Model('vendor')->getStoreList(array('vid'=>array('in',$store_id_array)));
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
            $member_list = Model()->table('member')->where(array('member_id'=>array('in',$member_id_array)))->limit($pagesize)->key('member_id')->select();
            foreach ($order_list as $order_id => $order) {
                $order_list[$order_id]['extend_member'] = $member_list[$order['buyer_id']];
            }
        }

        //追加返回商品信息
        if (in_array('order_goods',$extend)) {
            //取商品列表
            $order_goods_list = $this->getOrderGoodsList(array('order_id'=>array('in',array_keys($order_list))));
            foreach ($order_goods_list as $value) {
                $value['goods_image_url'] = cthumb($value['goods_image'], 240, $value['vid']);
                $order_list[$value['order_id']]['extend_order_goods'][] = $value;
            }
        }

        return $order_list;
    }

    /**
     * 待付款订单数量
     * @param unknown $condition
     */
    public function getOrderStateNewCount($condition = array()) {
        $condition['order_state'] = ORDER_STATE_NEW;
        $condition['delete_state']=0;
        return $this->getOrderCount($condition);
    }

    /**
     * 待发货订单数量
     * @param unknown $condition
     */
    public function getOrderStatePayCount($condition = array()) {
        $condition['order_state'] = ORDER_STATE_PAY;
        return $this->getOrderCount($condition);
    }

    /**
     * 待收货订单数量
     * @param unknown $condition
     */
    public function getOrderStateSendCount($condition = array()) {
        $condition['order_state'] = ORDER_STATE_SEND;
        return $this->getOrderCount($condition);
    }
    /**
     * 已完成订单数量
     * @param unknown $condition
     */
    public function getOrderStateSuccess($condition = array()) {
        $condition['order_state'] = ORDER_STATE_SUCCESS;
        return $this->getOrderCount($condition);
    }

    /**
     * 待评价订单数量
     * @param unknown $condition
     */
    public function getOrderStateEvalCount($condition = array()) {
        $condition['order_state'] = ORDER_STATE_SUCCESS;
        $condition['evaluation_state'] = 0;
        $condition['delete_state']=0;
//        $condition['finnshed_time'] = array('gt',TIMESTAMP - ORDER_EVALUATE_TIME);
        return $this->getOrderCount($condition);
    }

    /**
     * 取得订单数量
     * @param unknown $condition
     */
    public function getOrderCount($condition) {
        return DB::name('order')->where($condition)->count();
    }

    /**
     * 取得订单商品表详细信息
     * @param unknown $condition
     * @param string $fields
     * @param string $order
     */
    public function getOrderGoodsInfo($condition = array(), $fields = '*', $order = '') {
        return DB::name('order_goods')->where($condition)->field($fields)->order($order)->find();
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
        return DB::name('order_goods')->field($fields)->where($condition)->limit($limit)->order($order)->group($group)->cache($key)->page($page)->select();
    }

    /**
     * 取得订单扩展表列表
     * @param unknown $condition
     * @param string $fields
     * @param string $limit
     */
    public function getOrderCommonList($condition = array(), $fields = '*', $limit = null) {
        return DB::name('order_common')->field($fields)->where($condition)->limit($limit)->select();
    }

    /**
     * 插入订单支付表信息
     * @param array $data
     * @return int 返回 insert_id
     */
    public function addOrderPay($data) {
        return DB::table('bbc_order_pay')->insert($data);
    }

    /**
     * 插入订单表信息
     * @param array $data
     * @return int 返回 insert_id
     */
    public function addOrder($data) {
        return DB::table('bbc_order')->insertGetId($data);
    }

    /**
     * 插入订单扩展表信息
     * @param array $data
     * @return int 返回 insert_id
     */
    public function addOrderCommon($data) {
        $data['evaluation_time']=TIMESTAMP;
        $data['evalseller_time']=TIMESTAMP;
        $res = DB::table('bbc_order_common')->insert($data);
        if($res!=0)
        return true;
    }

    /**
     * 插入订单扩展表信息
     * @param array $data
     * @return int 返回 insert_id
     */
    public function addOrderGoods($data) {
        $data['teacher'] = "UCG";
        return DB::table('bbc_order_goods')->insertAll($data);
    }

    /**
     * 添加订单日志
     */
    public function addOrderLog($data) {
        $data['log_role'] = str_replace(array('buyer','seller','system','dian'),array('买家','商家','系统','门店'), $data['log_role']);
        $data['log_time'] = TIMESTAMP;
        return $this->table('bbc_order_log')->insert($data);
    }

    /**
     * 更改订单信息
     *
     * @param unknown_type $data
     * @param unknown_type $condition
     */
    public function editOrder($data,$condition) {
        if(Config('distribution') && !(Config("sld_spreader") && Config("spreader_isuse"))){
            if ($data['order_state'] == ORDER_STATE_PAY) {
                $order_info = $this->getOrderInfo($condition);
                $this->fanli($order_info);
            }else if($data['order_state']==ORDER_STATE_SUCCESS){
                $list = $this->table('fenxiao_log')->field('*')->where(array('order_id'=>$condition["order_id"],'status'=>0))->select();

                foreach($list as $value){
                    $bs = 'rebate'.$value['description'];

                    $member_model = new User();
                    $member_info=$member_model->getMemberInfoByID($value['reciver_member_id']);
                    $points = new Points();
                    $points->savePointsLog($bs,array('pl_memberid'=>$value['reciver_member_id'],'pl_membername'=>$member_info['member_name'],'rebate_amount'=>$value['yongjin']),true);


//                $model_pd = Model('predeposit');

//                    $data_pd = array();
//                    $data_pd['member_id'] = $value['reciver_member_id'];
//                    $data_pd['amount'] = $value['yongjin'];
//                    $data_pd['order_sn'] = $value['order_sn'];
//                    //根据用户reciver_member_id获取reciver_member_name
//                    $member_model = Model('member');
//                    $member_info=$member_model->getMemberInfoByID($value['reciver_member_id']);
//                    $data_pd['member_name'] = $member_info['member_name'];
//                    $model_pd->changePd('cash_rebate',$data_pd);
                }
                $this->table('bbc_fenxiao_log')->where(array('order_id'=>$condition[order_id],'status'=>0))->update(array('status'=>1));
            }
        }

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
        return $this->table('bbc_order')->where($condition)->update($data);
    }

    /**
     * 更改订单信息
     *
     * @param unknown_type $data
     * @param unknown_type $condition
     */
    public function editOrderCommon($data,$condition) {
        return $this->table('order_common')->where($condition)->update($data);
    }

    /**
     * 更改订单支付信息
     *
     * @param unknown_type $data
     * @param unknown_type $condition
     */
    public function editOrderPay($data,$condition) {
        return $this->table('order_pay')->where($condition)->update($data);
    }

    /**
     * 订单操作历史列表
     * @param unknown $order_id
     * @return Ambigous <multitype:, unknown>
     */
    public function getOrderLogList($condition) {
        return DB::name('order_log')->where($condition)->select();
    }

    /**
     * 返回是否允许某些操作
     * @param unknown $operate
     * @param unknown $order_info
     */
    public function getOrderOperateState($operate,$order_info)
    {
        if (!is_array($order_info) || empty($order_info)) return false;

        switch ($operate) {
            //买家取消订单
            case 'buyer_cancel':
                $state = ($order_info['order_state'] == ORDER_STATE_NEW) ||
                    ($order_info['payment_code'] == 'offline' && $order_info['order_state'] == ORDER_STATE_PAY);
                break;
            //买家退款取消订单
            case 'refund_cancel':
                $state = isset($order_info['refund']) && $order_info['refund'] == 1 && !intval($order_info['lock_state']);
                break;
            //商家取消订单
            case 'store_cancel':
                $state = ($order_info['order_state'] == ORDER_STATE_NEW) ||
                    ($order_info['payment_code'] == 'offline' &&
                        in_array($order_info['order_state'], array(ORDER_STATE_PAY, ORDER_STATE_SEND)));
                break;
            //平台取消订单
            case 'system_cancel':
                $state = ($order_info['order_state'] == ORDER_STATE_NEW) ||
                    ($order_info['payment_code'] == 'offline' && $order_info['order_state'] == ORDER_STATE_PAY);
                break;
            //平台收款
            case 'system_receive_pay':
                $state = $order_info['order_state'] == ORDER_STATE_NEW && $order_info['payment_code'] == 'online';
                break;
            //买家投诉（wap端）使用
            case 'complain':
                $state = in_array($order_info['order_state'], array(ORDER_STATE_PAY, ORDER_STATE_SEND)) ||
                    intval($order_info['finnshed_time']) > (TIMESTAMP - Config('complain_time_limit'));
                break;
            //买家投诉（pc端）使用
            case 'tousu':
                $state = in_array($order_info['order_state'], array(ORDER_STATE_PAY, ORDER_STATE_SEND)) ||
                    intval($order_info['finnshed_time']) > (TIMESTAMP - Config('complain_time_limit'));
                break;
            //调整运费
            case 'modify_price':
                $state = ($order_info['order_state'] == ORDER_STATE_NEW) ||
                    ($order_info['payment_code'] == 'offline' && $order_info['order_state'] == ORDER_STATE_PAY);
                $state = floatval($order_info['shipping_fee']) > 0 && $state;
                break;
            //调整商品费用
            case 'spay_price':
                $state = ($order_info['order_state'] == ORDER_STATE_NEW) ||
                    ($order_info['payment_code'] == 'offline' && $order_info['order_state'] == ORDER_STATE_PAY);
                $state = floatval($order_info['goods_amount']) > 0 && $state;
                break;
            //发货
            case 'send':
                $state = !$order_info['lock_state'] && $order_info['order_state'] == ORDER_STATE_PAY;
                break;
            //收货
            case 'receive':
                $state = !$order_info['lock_state'] && $order_info['order_state'] == ORDER_STATE_SEND;
                break;
            //门店自提完成
            case 'chain_receive':
                $state = !$order_info['lock_state'] && in_array($order_info['order_state'], array(ORDER_STATE_NEW, ORDER_STATE_PAY)) &&
                    $order_info['chain_code'];
                break;
            //评价
            case 'evaluation':
//    	        $state = !$order_info['lock_state'] && !intval($order_info['evaluation_state']) && $order_info['order_state'] == ORDER_STATE_SUCCESS &&
//    	         TIMESTAMP - intval($order_info['finnshed_time']) < ORDER_EVALUATE_TIME;
                $state = !$order_info['lock_state'] && !intval($order_info['evaluation_state']) && $order_info['order_state'] == ORDER_STATE_SUCCESS;
                break;
            //再次评价
            case 'evaluation_again':
                $state = !$order_info['lock_state'] && $order_info['evaluation_state'] && !$order_info['evaluation_again_state'] && $order_info['order_state'] == ORDER_STATE_SUCCESS;
                break;
            //锁定
            case 'lock':
                $state = intval($order_info['lock_state']) ? true : false;
                break;
            //快递跟踪
            case 'deliver':
                $state = !empty($order_info['shipping_code']) && in_array($order_info['order_state'], array(ORDER_STATE_SEND, ORDER_STATE_SUCCESS));
                break;
            //放入回收站
            case 'delete':
                $state = in_array($order_info['order_state'], array(ORDER_STATE_CANCEL, ORDER_STATE_SUCCESS)) && $order_info['delete_state'] == 0;
                break;
            //永久删除、从回收站还原
            case 'drop':
            case 'restore':
                $state = in_array($order_info['order_state'], array(ORDER_STATE_CANCEL, ORDER_STATE_SUCCESS)) && $order_info['delete_state'] == 1;
                break;
            //分享
            case 'share':
                $state = $order_info['order_state'] == ORDER_STATE_SUCCESS;
                break;
            //发货
            case 'store_send':
                $state = !$order_info['lock_state'] && $order_info['order_state'] == ORDER_STATE_PAY && !$order_info['chain_id'];
                break;
            //核销
            case 'hexiao' :
                $state = !$order_info['lock_state'] && $order_info['dian_id'] = $_SESSION['dian_id'] && $order_info['order_state'] == ORDER_STATE_SEND;
                break;
            //可以派单
            case 'pai':
                $state = !$order_info['lock_state'] && $order_info['order_state'] == ORDER_STATE_PAY && !$order_info['dian_id'] &&!$order_info['extend_order_common']['dian_id'];
                if ($state) {
                    $dian = new Dian();
                    $geshu = $dian->getDiansCountByAddress($order_info);
                    $state = $geshu>0;
                }
                break;
            //派单中
            case 'pai_ing':
                $state = !$order_info['dian_id'] && $order_info['extend_order_common']['pai_dian_id'];
                break;
        }
        return $state;
    }

    /**
     * 联查订单表订单商品表
     *
     * @param array $condition
     * @param string $field
     * @param number $page
     * @param string $order
     * @return array
     */
    public function getOrderAndOrderGoodsList($condition, $field = '*', $page = 0, $order = 'rec_id desc') {
        return $this->table('order_goods,order')->join('inner')->on('order_goods.order_id=order.order_id')->where($condition)->field($field)->page($page)->order($order)->select();
    }

    /**
     * 订单销售记录 订单状态为20、30、40时
     * @param unknown $condition
     * @param string $field
     * @param number $page
     * @param string $order
     */
    public function getOrderAndOrderGoodsSalesRecordList($condition, $field="*", $page = 0, $order = 'rec_id desc') {
        $condition['order_state'] = array('in', array(ORDER_STATE_PAY, ORDER_STATE_SEND, ORDER_STATE_SUCCESS));
        return $this->getOrderAndOrderGoodsList($condition, $field, $page, $order);
    }

    /**
     * 买家订单状态操作
     *
     */
    public function memberChangeState($state_type, $order_info, $member_id, $member_name, $extend_msg) {
        try {

            $this->beginTransaction();

            if ($state_type == 'order_cancel') {
                $this->_memberChangeStateOrderCancel($order_info, $member_id, $member_name, $extend_msg);
                $message = '成功取消了订单';
            } elseif ($state_type == 'order_receive') {
                $this->_memberChangeStateOrderReceive($order_info, $member_id, $member_name, $extend_msg);
                $message = '订单交易成功,您可以评价本次交易';
            }

            // 推手系统 订单状态更新
            if (C('spreader_isuse') && C('sld_spreader')) {
                $par['state_type'] = $state_type;
                $par['order_info'] = $order_info;
                $par['extend_msg'] = $extend_msg;
                // 发送请求 添加订单信息
                con_addons('spreader',$par,'update_order_status_speader','api');
            }

            $this->commit();
            return array('success' => $message);

        } catch (Exception $e) {
            $this->rollback();
            return array('error' => $message);
        }

    }


    /**
     * 回收站操作（放入回收站、还原、永久删除）
     * @param array $order_info
     * @param string $role 操作角色 buyer、seller、admin、system 分别代表买家、商家、管理员、系统
     * @param string $state_type 操作类型
     * @return array
     */
    public function changeOrderStateRecycle($order_info, $role, $state_type) {
        $order_id = $order_info['order_id'];
        $model_order = Model('order');
        //更新订单删除状态
        $state = str_replace(array('delete','drop','restore'), array(ORDER_DEL_STATE_DELETE,ORDER_DEL_STATE_DROP,ORDER_DEL_STATE_DEFAULT), $state_type);
        $update = $model_order->editOrder(array('delete_state'=>$state),array('order_id'=>$order_id));
        if (!$update) {
            return callback(false,'操作失败');
        } else {
            return callback(true,'操作成功');
        }
    }

    /**
     * 王强 拼团成功时取消其他订单
     * @param unknown $order_info
     */
    public function pinChangeStateOrderCancel($order_info, $member_id, $member_name, $extend_msg) {
        $order_id = $order_info['order_id'];
        $if_allow = $this->getOrderOperateState('buyer_cancel',$order_info);
        if (!$if_allow) {
            throw new Exception('非法访问');
        }

        $goods_list = $this->getOrderGoodsList(array('order_id'=>$order_id));
        $model_goods= Model('goods');
        $goods_buy_quantity=array();
        if(is_array($goods_list) and !empty($goods_list)) {
            foreach ($goods_list as $goods) {
                $goods_buy_quantity[$goods['gid']]=$goods['goods_num'];
            }
        }
        Model('buy')->updateGoodsStorageNum($goods_buy_quantity,$order_info['dian_id']);

        //解冻预存款
        $pd_amount = floatval($order_info['pd_amount']);
        if ($pd_amount > 0) {
            $model_pd = Model('predeposit');
            $data_pd = array();
            $data_pd['member_id'] = $member_id;
            $data_pd['member_name'] = $member_name;
            $data_pd['amount'] = $pd_amount;
            $data_pd['order_sn'] = $order_info['order_sn'];
            $model_pd->changePd('order_cancel',$data_pd);
        }

        //更新订单信息
        $update_order = array('order_state' => ORDER_STATE_CANCEL, 'pd_amount' => 0);
        $update = $this->editOrder($update_order,array('order_id'=>$order_id));
        if (!$update) {
            throw new Exception('保存失败');
        }

        //添加订单日志
        $data = array();
        $data['order_id'] = $order_id;
        $data['log_role'] = 'buyer';
        $data['log_msg'] = '拼团，取消重复订单';
        if ($extend_msg) {
            $data['log_msg'] .= ' ( '.$extend_msg.' )';
        }
        $data['log_orderstate'] = ORDER_STATE_CANCEL;
        $this->addOrderLog($data);
    }

    /**
     * 取消订单操作
     * @param unknown $order_info
     */
    private function _memberChangeStateOrderCancel($order_info, $member_id, $member_name, $extend_msg) {
        $order_id = $order_info['order_id'];
        $if_allow = $this->getOrderOperateState('buyer_cancel',$order_info);
        if (!$if_allow) {
            throw new Exception('非法访问');
        }

        $goods_list = $this->getOrderGoodsList(array('order_id'=>$order_id));
        $model_goods= Model('goods');
        $goods_buy_quantity=array();
        if(is_array($goods_list) and !empty($goods_list)) {
            foreach ($goods_list as $goods) {
                $goods_buy_quantity[$goods['gid']]=$goods['goods_num'];
                //如果有首单优惠，取消订单把优惠使用权还回来
                if($goods['first']){
                    //修改首单满减记录
                    M('first','firstDiscount')->table('first_discount_log')->where(['order_id'=>$order_id])->update(['state'=>0]);
                }
            }
        }
        Logic('queue')->cancelOrderUpdateStorage(array('id'=>$order_info['dian_id'],'data'=>$goods_buy_quantity));
//        Model('buy')->updateGoodsStorageNum($goods_buy_quantity,$order_info['dian_id'],true);

        //解冻预存款
        $pd_amount = floatval($order_info['pd_amount']);
        if ($pd_amount > 0) {
            $model_pd = Model('predeposit');
            $data_pd = array();
            $data_pd['member_id'] = $member_id;
            $data_pd['member_name'] = $member_name;
            $data_pd['amount'] = $pd_amount;
            $data_pd['order_sn'] = $order_info['order_sn'];
            $model_pd->changePd('order_cancel',$data_pd);
        }
        //退还积分
        $pd_point = intval($order_info['pd_points']);
        if ($pd_point > 0) {
            Model('points')->savePointsLog('returnpurpose', array('pl_memberid' => $order_info['buyer_id'], 'pl_membername' => $order_info['buyer_name'], 'orderprice' => $order_info['goods_amount'], 'order_sn' => $order_info['order_sn'], 'order_id' => $order_id, 'pl_points' => $pd_point), true);
        }
        if($order_info['red_id']>0){
            //退回优惠券 平台
            M('red')->table('red_user')->where(array('id'=>$order_info['red_id']))->update(array('reduser_use'=>0));
        }
        if($order_info['vred_id']>0){
            //退回优惠券 店铺
            M('red')->table('red_user')->where(array('id'=>$order_info['vred_id']))->update(array('reduser_use'=>0));
        }


        //更新订单信息
        $update_order = array('order_state' => ORDER_STATE_CANCEL, 'pd_amount' => 0);
        $update = $this->editOrder($update_order,array('order_id'=>$order_id));
        if (!$update) {
            throw new Exception('保存失败');
        }

        //添加订单日志
        $data = array();
        $data['order_id'] = $order_id;
        $data['log_role'] = 'buyer';
        $data['log_msg'] = '取消了订单';
        if ($extend_msg) {
            $data['log_msg'] .= ' ( '.$extend_msg.' )';
        }
        $data['log_orderstate'] = ORDER_STATE_CANCEL;
        $this->addOrderLog($data);
    }

    /**
     * 收货操作
     * @param unknown $order_info
     */
    private function _memberChangeStateOrderReceive($order_info, $member_id, $member_name, $extend_msg) {
        $order_id = $order_info['order_id'];

        $model_rebate=Model('vendor');
        $model_member = Model('member');
        $model_goods=Model('goods');
        //$model_order = Model('order');
        /////////////////start 三级返利//////////////////////////////////////////////
        $model_setting = Model('setting');
        $list_setting = $model_setting->getListSetting();
        $points_rebate_grade0=$list_setting['points_rebate_grade0']/100;//买家返利
        $points_rebate_grade1=$list_setting['points_rebate_grade1']/100;//一级返利
        $points_rebate_grade2=$list_setting['points_rebate_grade2']/100;//二级返利
        $points_rebate_grade3=$list_setting['points_rebate_grade3']/100;//三级返利
        $order_goods_info	= $this->getOrderInfo(array('order_id'=>$order_id),array('order_goods'));
        $order_goods_list=array();
        $order_goods_list=$order_goods_info['extend_order_goods'];
        $model_goods_rate=Model('vendor_bind_category');
        $store_gc_id_commis_rate=$model_goods_rate->getStoreGcidCommisRateList($order_goods_list);
        $i=0;
        $condition=array();
        $fanli_jine=0;
        $tgzonge=0;
        $rebate_amount_array=array();
        //array $goods_ids取得订单所有商品id
        $goods_ids=array();
        foreach($order_goods_list as $key=>$value){
            $goods_ids[$key]=$value['gid'];
        }
        foreach($order_goods_list as $rkey=>$rval){
            $condition['gid']=$rval['gid'];
            $goods_info=$model_goods->getGoodsInfo($condition);
//            foreach($store_gc_id_commis_rate[$order_info['vid']] as $bkey=>$bval){
//                if($rval['gc_id']==$bkey){
//                    $sigele_points_rebate_grade=($goods_info['goods_rebate']-$bval)/100;
//                    if($sigele_points_rebate_grade<=0){
//                        $sigele_points_rebate_grade=0;
//                    }
//                    $rebate_amount_array[$rval['gid']]=$sigele_points_rebate_grade*($rval['goods_pay_price']-$rval['refund_amount']);
//                }else{
//                    continue;
//                }
//            }
            //$rebate_amount_array[$rval['gid']]=$rval['goods_pay_price']*$goods_info['goods_rebate']/100;
            $rebate_amount_array[$rval['gid']]=$rval['goods_pay_price'];
        }
        //计算退款部分的返利比例
        $rebate_amount=array_sum($rebate_amount_array);
        $tmp_array=array();
        //返利回买家20%===============
        $zxt_fanli=$rebate_amount*$points_rebate_grade0;
        $tmp_array['rebate_time']=TIMESTAMP;
        $tmp_array['member_id']=$order_info['buyer_id'];
        $tmp_array['rebate_amount']=$zxt_fanli;
        $tmp_array['order_id']=$order_info['order_id'];
        /*******S******************************************************/
        $find_condition=array();
        $find_condition['member_id']=$tmp_array['member_id'];
        $find_condition['order_id']=$order_id;
        $if_exist=$model_rebate->get_rebate_single($find_condition);

        //确认收货时添加会员积分
        if (C('points_isuse') == 1){
            $points_model = Model('points');
            $points_model->savePointsLog('rebate0',array('pl_memberid'=>$member_id,'pl_membername'=>$member_name,'rebate_amount'=>$zxt_fanli,'order_sn'=>$order_info['order_sn'],'order_id'=>$order_info['order_id']),true);
        }
        if($if_exist){//判断该订单用户id是否存在，存在就更新，不存在就新增
            $tmp_array['rebate_amount']=$tmp_array['rebate_amount']+$if_exist['rebate_amount'];
            $result=$model_rebate->edit_rebate_data($find_condition,$tmp_array);
        }else{
            $result=$model_rebate->add_rebate_data($tmp_array);
        }
        /*******E******************************************************/
        if ($result<=0) {
            throw new Exception('返利买家失败');
        }




        //三级返利1级上线,买家推荐人===============
        $store_inviter_id1 = $model_member->table('member')->getfby_member_id($order_info['buyer_id'],'inviter_id');
        if(!empty($store_inviter_id1)){
            $tmp_array['member_id']=$store_inviter_id1;
            $zxt_fanli=$rebate_amount*$points_rebate_grade1;


            $tmp_array['rebate_time']=TIMESTAMP;
            $tmp_array['rebate_amount']=$zxt_fanli;
            $tmp_array['order_id']=$order_info['order_id'];
            /*******S******************************************************/
            $find_condition=array();
            $find_condition['member_id']=$tmp_array['member_id'];
            $find_condition['order_id']=$tmp_array['order_id'];
            $if_exist=$model_rebate->get_rebate_single($find_condition);
            //确认收货时添加会员积分
//            if (C('points_isuse') == 1){
//                $points_model = Model('points');
//                $points_model->savePointsLog('rebate1',array('pl_memberid'=>$store_inviter_id1,'pl_membername'=>$member_name,'rebate_amount'=>$zxt_fanli,'order_sn'=>$order_info['order_sn'],'order_id'=>$order_info['order_id']),true);
//            }
            if($if_exist){//判断该订单用户id是否存在，存在就更新，不存在就新增
                $tmp_array['rebate_amount']=$tmp_array['rebate_amount']+$if_exist['rebate_amount'];
                $result=$model_rebate->edit_rebate_data($find_condition,$tmp_array);
            }else{
                $result=$model_rebate->add_rebate_data($tmp_array);
            }
            /*******E******************************************************/
            if ($result<=0) {
                throw new Exception('返利一级失败');
            }


            $cash_rebate = $rebate_amount/100; //现金返利按照购买者上三级，没人返利百分之一现金。
            $pd_amount = floatval($order_info['goods_amount']);
            if ($pd_amount > 0) {
                $model_pd = Model('predeposit');
                $data_pd = array();
                $data_pd['member_id'] = $store_inviter_id1;
                $data_pd['member_name'] = $member_name;
                $data_pd['amount'] = $cash_rebate;
                $data_pd['order_sn'] = $order_info['order_sn'];
                $model_pd->changePd('cash_rebate',$data_pd);
            }



            //三级返利2级上线===============
            $store_inviter_id2 = $model_member->table('member')->getfby_member_id($store_inviter_id1,'inviter_id');
            if(!empty($store_inviter_id2)){
                $tmp_array['member_id']=$store_inviter_id2;
                $zxt_fanli=$rebate_amount*$points_rebate_grade2;
                $tmp_array['rebate_time']=TIMESTAMP;
                $tmp_array['rebate_amount']=$zxt_fanli;
                $tmp_array['order_id']=$order_info['order_id'];
                /*******S******************************************************/
                $find_condition=array();
                $find_condition['member_id']=$tmp_array['member_id'];
                $find_condition['order_id']=$tmp_array['order_id'];
                $if_exist=$model_rebate->get_rebate_single($find_condition);
                //确认收货时添加会员积分
//                if (C('points_isuse') == 1){
//                    $points_model = Model('points');
//                    $points_model->savePointsLog('rebate2',array('pl_memberid'=>$store_inviter_id2,'pl_membername'=>$member_name,'rebate_amount'=>$zxt_fanli,'order_sn'=>$order_info['order_sn'],'order_id'=>$order_info['order_id']),true);
//                }
                if($if_exist){//判断该订单用户id是否存在，存在就更新，不存在就新增
                    $tmp_array['rebate_amount']=$tmp_array['rebate_amount']+$if_exist['rebate_amount'];
                    $result=$model_rebate->edit_rebate_data($find_condition,$tmp_array);
                }else{
                    $result=$model_rebate->add_rebate_data($tmp_array);
                }
                /*******E******************************************************/
                if ($result<=0) {
                    throw new Exception('返利二级失败');
                }

                $cash_rebate = $rebate_amount/100; //现金返利按照购买者上三级，没人返利百分之一现金。
                $pd_amount = floatval($order_info['goods_amount']);
                if ($pd_amount > 0) {
                    $model_pd = Model('predeposit');
                    $data_pd = array();
                    $data_pd['member_id'] = $store_inviter_id2;
                    $data_pd['member_name'] = $member_name;
                    $data_pd['amount'] = $cash_rebate;
                    $data_pd['order_sn'] = $order_info['order_sn'];
                    $model_pd->changePd('cash_rebate',$data_pd);
                }






                //三级返利3级上线===============
                $store_inviter_id3 = $model_member->table('member')->getfby_member_id($store_inviter_id2,'inviter_id');
                if(!empty($store_inviter_id3)){
                    $tmp_array['member_id']=$store_inviter_id3;
                    $zxt_fanli=$rebate_amount*$points_rebate_grade3;
                    $tmp_array['rebate_time']=TIMESTAMP;
                    $tmp_array['rebate_amount']=$zxt_fanli;
                    $tmp_array['order_id']=$order_info['order_id'];
                    /*******S******************************************************/
                    $find_condition=array();
                    $find_condition['member_id']=$tmp_array['member_id'];
                    $find_condition['order_id']=$tmp_array['order_id'];
                    $if_exist=$model_rebate->get_rebate_single($find_condition);
                    //确认收货时添加会员积分
//                    if (C('points_isuse') == 1){
//                        $points_model = Model('points');
//                        $points_model->savePointsLog('rebate3',array('pl_memberid'=>$store_inviter_id3,'pl_membername'=>$member_name,'rebate_amount'=>$zxt_fanli,'order_sn'=>$order_info['order_sn'],'order_id'=>$order_info['order_id']),true);
//                    }
                    if($if_exist){//判断该订单用户id是否存在，存在就更新，不存在就新增
                        $tmp_array['rebate_amount']=$tmp_array['rebate_amount']+$if_exist['rebate_amount'];
                        $result=$model_rebate->edit_rebate_data($find_condition,$tmp_array);
                    }else{
                        $result=$model_rebate->add_rebate_data($tmp_array);
                    }
                    /*******E******************************************************/
                    if ($result<=0) {
                        throw new Exception('返利三级失败');
                    }

                    $cash_rebate = $rebate_amount/100; //现金返利按照购买者上三级，没人返利百分之一现金。
                    $pd_amount = floatval($order_info['goods_amount']);
                    if ($pd_amount > 0) {
                        $model_pd = Model('predeposit');
                        $data_pd = array();
                        $data_pd['member_id'] = $store_inviter_id3;
                        $data_pd['member_name'] = $member_name;
                        $data_pd['amount'] = $cash_rebate;
                        $data_pd['order_sn'] = $order_info['order_sn'];
                        $model_pd->changePd('cash_rebate',$data_pd);
                    }



                }
            }
        }
        //////////////////////////end 三级返利///////////////////////////////////////
        //更新订单状态
        $update_order = array();
        $update_order['finnshed_time'] = TIMESTAMP;
        $update_order['order_state'] = ORDER_STATE_SUCCESS;
        $update = $this->editOrder($update_order,array('order_id'=>$order_id));
        if (!$update) {
            throw new Exception('保存失败');
        }

        //发放优惠券：推荐
        M('red')->SendRedInvite($order_info['buyer_id']);

        //添加订单日志
        $data = array();
        $data['order_id'] = $order_id;
        $data['log_role'] = 'buyer';
        $data['log_msg'] = '签收了货物';
        if ($extend_msg) {
            $data['log_msg'] .= ' ( '.$extend_msg.' )';
        }
        $data['log_orderstate'] = ORDER_STATE_SUCCESS;
        $this->addOrderLog($data);

//	    //确认收货时添加会员积分
        if (C('points_isuse') == 1){
            $points_model = Model('points');
            $points_model->savePointsLog('order',array('pl_memberid'=>$member_id,'pl_membername'=>$member_name,'orderprice'=>$order_info['order_amount'],'order_sn'=>$order_info['order_sn'],'order_id'=>$order_info['order_id']),true);
        }
    }



    //*********************线下体验店返利**************************************

    /**
     * 线下体验店 订单状态操作
     *
     */
    public function offlinememberChangeState($state_type, $order_info, $member_id, $member_name, $extend_msg) {
        try {

            $this->beginTransaction();

            if ($state_type == 'order_cancel') {
                $this->_memberChangeStateOrderCancel($order_info, $member_id, $member_name, $extend_msg);
                $message = '成功取消了订单';
            } elseif ($state_type == 'order_receive') {
                $this->_offlinememberChangeStateOrderReceive($order_info, $member_id, $member_name, $extend_msg);
                $message = '订单交易成功,您可以评价本次交易';
            }

            $this->commit();
            return array('success' => $message);

        } catch (Exception $e) {
            $this->rollback();
            return array('error' => $message);
        }

    }

    /**
     * 线下体验店修改自动确认收货及返利操作
     * @param unknown $order_info
     */
    private function _offlinememberChangeStateOrderReceive($order_info, $member_id, $member_name, $extend_msg) {
        $order_id = $order_info['order_id'];

        $model_rebate=Model('vendor');
        $model_member = Model('member');
        //$model_goods=Model('goods');
        //$model_order = Model('order');
        /////////////////start 三级返利//////////////////////////////////////////////
        $model_setting = Model('setting');
        $list_setting = $model_setting->getListSetting();
        $points_rebate_grade0=$list_setting['points_rebate_grade0']/100;//买家返利
        $points_rebate_grade1=$list_setting['points_rebate_grade1']/100;//一级返利
        $points_rebate_grade2=$list_setting['points_rebate_grade2']/100;//二级返利
        $points_rebate_grade3=$list_setting['points_rebate_grade3']/100;//三级返利
        //$order_goods_info	= $this->getOrderInfo(array('order_id'=>$order_id),array('order_goods'));
        $order_goods_info	= $this->getOrderInfo(array('order_id'=>$order_id),array('order_goods'));

        $order_goods_list=array();
        $i=0;
        $condition=array();
        $fanli_jine=0;
        $tgzonge=0;
        $rebate_amount_array=array();

        //计算退款部分的返利比例
        $rebate_amount=$order_goods_info['order_amount'];
        $tmp_array=array();
        //返利回买家20%===============
        $zxt_fanli=$rebate_amount*$points_rebate_grade0;
        $tmp_array['rebate_time']=TIMESTAMP;
        $tmp_array['member_id']=$order_info['buyer_id'];
        $tmp_array['rebate_amount']=$zxt_fanli;
        $tmp_array['order_id']=$order_info['order_id'];
        /*******S******************************************************/
        $find_condition=array();
        $find_condition['member_id']=$tmp_array['member_id'];
        $find_condition['order_id']=$order_id;
        $if_exist=$model_rebate->get_rebate_single($find_condition);

        //确认收货时添加会员积分
        if (C('points_isuse') == 1){
            $points_model = Model('points');
            $points_model->savePointsLog('rebate0',array('pl_memberid'=>$member_id,'pl_membername'=>$member_name,'rebate_amount'=>$zxt_fanli,'order_sn'=>$order_info['order_sn'],'order_id'=>$order_info['order_id']),true);
        }
        if($if_exist){//判断该订单用户id是否存在，存在就更新，不存在就新增
            $tmp_array['rebate_amount']=$tmp_array['rebate_amount']+$if_exist['rebate_amount'];
            $result=$model_rebate->edit_rebate_data($find_condition,$tmp_array);
        }else{
            $result=$model_rebate->add_rebate_data($tmp_array);
        }
        /*******E******************************************************/
        if ($result<=0) {
            throw new Exception('返利买家失败');
        }




        //三级返利1级上线,买家推荐人===============
        $store_inviter_id1 = $model_member->table('member')->getfby_member_id($order_info['buyer_id'],'inviter_id');
        if(!empty($store_inviter_id1)){
            $tmp_array['member_id']=$store_inviter_id1;
            $zxt_fanli=$rebate_amount*$points_rebate_grade1;


            $tmp_array['rebate_time']=TIMESTAMP;
            $tmp_array['rebate_amount']=$zxt_fanli;
            $tmp_array['order_id']=$order_info['order_id'];
            /*******S******************************************************/
            $find_condition=array();
            $find_condition['member_id']=$tmp_array['member_id'];
            $find_condition['order_id']=$tmp_array['order_id'];
            $if_exist=$model_rebate->get_rebate_single($find_condition);
            //确认收货时添加会员积分
            if (C('points_isuse') == 1){
                $points_model = Model('points');
                $points_model->savePointsLog('rebate1',array('pl_memberid'=>$store_inviter_id1,'pl_membername'=>$member_name,'rebate_amount'=>$zxt_fanli,'order_sn'=>$order_info['order_sn'],'order_id'=>$order_info['order_id']),true);
            }
            if($if_exist){//判断该订单用户id是否存在，存在就更新，不存在就新增
                $tmp_array['rebate_amount']=$tmp_array['rebate_amount']+$if_exist['rebate_amount'];
                $result=$model_rebate->edit_rebate_data($find_condition,$tmp_array);
            }else{
                $result=$model_rebate->add_rebate_data($tmp_array);
            }
            /*******E******************************************************/
            if ($result<=0) {
                throw new Exception('返利一级失败');
            }


            $cash_rebate = $rebate_amount/100; //现金返利按照购买者上三级，没人返利百分之一现金。
            $pd_amount = floatval($order_info['goods_amount']);
            if ($pd_amount > 0) {
                $model_pd = Model('predeposit');
                $data_pd = array();
                $data_pd['member_id'] = $store_inviter_id1;
                $data_pd['member_name'] = $member_name;
                $data_pd['amount'] = $cash_rebate;
                $data_pd['order_sn'] = $order_info['order_sn'];
                $model_pd->changePd('cash_rebate',$data_pd);
            }



            //三级返利2级上线===============
            $store_inviter_id2 = $model_member->table('member')->getfby_member_id($store_inviter_id1,'inviter_id');
            if(!empty($store_inviter_id2)){
                $tmp_array['member_id']=$store_inviter_id2;
                $zxt_fanli=$rebate_amount*$points_rebate_grade2;
                $tmp_array['rebate_time']=TIMESTAMP;
                $tmp_array['rebate_amount']=$zxt_fanli;
                $tmp_array['order_id']=$order_info['order_id'];
                /*******S******************************************************/
                $find_condition=array();
                $find_condition['member_id']=$tmp_array['member_id'];
                $find_condition['order_id']=$tmp_array['order_id'];
                $if_exist=$model_rebate->get_rebate_single($find_condition);
                //确认收货时添加会员积分
                if (C('points_isuse') == 1){
                    $points_model = Model('points');
                    $points_model->savePointsLog('rebate2',array('pl_memberid'=>$store_inviter_id2,'pl_membername'=>$member_name,'rebate_amount'=>$zxt_fanli,'order_sn'=>$order_info['order_sn'],'order_id'=>$order_info['order_id']),true);
                }
                if($if_exist){//判断该订单用户id是否存在，存在就更新，不存在就新增
                    $tmp_array['rebate_amount']=$tmp_array['rebate_amount']+$if_exist['rebate_amount'];
                    $result=$model_rebate->edit_rebate_data($find_condition,$tmp_array);
                }else{
                    $result=$model_rebate->add_rebate_data($tmp_array);
                }
                /*******E******************************************************/
                if ($result<=0) {
                    throw new Exception('返利二级失败');
                }

                $cash_rebate = $rebate_amount/100; //现金返利按照购买者上三级，没人返利百分之一现金。
                $pd_amount = floatval($order_info['goods_amount']);
                if ($pd_amount > 0) {
                    $model_pd = Model('predeposit');
                    $data_pd = array();
                    $data_pd['member_id'] = $store_inviter_id2;
                    $data_pd['member_name'] = $member_name;
                    $data_pd['amount'] = $cash_rebate;
                    $data_pd['order_sn'] = $order_info['order_sn'];
                    $model_pd->changePd('cash_rebate',$data_pd);
                }






                //三级返利3级上线===============
                $store_inviter_id3 = $model_member->table('member')->getfby_member_id($store_inviter_id2,'inviter_id');
                if(!empty($store_inviter_id3)){
                    $tmp_array['member_id']=$store_inviter_id3;
                    $zxt_fanli=$rebate_amount*$points_rebate_grade3;
                    $tmp_array['rebate_time']=TIMESTAMP;
                    $tmp_array['rebate_amount']=$zxt_fanli;
                    $tmp_array['order_id']=$order_info['order_id'];
                    /*******S******************************************************/
                    $find_condition=array();
                    $find_condition['member_id']=$tmp_array['member_id'];
                    $find_condition['order_id']=$tmp_array['order_id'];
                    $if_exist=$model_rebate->get_rebate_single($find_condition);
                    //确认收货时添加会员积分
                    if (C('points_isuse') == 1){
                        $points_model = Model('points');
                        $points_model->savePointsLog('rebate3',array('pl_memberid'=>$store_inviter_id3,'pl_membername'=>$member_name,'rebate_amount'=>$zxt_fanli,'order_sn'=>$order_info['order_sn'],'order_id'=>$order_info['order_id']),true);
                    }
                    if($if_exist){//判断该订单用户id是否存在，存在就更新，不存在就新增
                        $tmp_array['rebate_amount']=$tmp_array['rebate_amount']+$if_exist['rebate_amount'];
                        $result=$model_rebate->edit_rebate_data($find_condition,$tmp_array);
                    }else{
                        $result=$model_rebate->add_rebate_data($tmp_array);
                    }
                    /*******E******************************************************/
                    if ($result<=0) {
                        throw new Exception('返利三级失败');
                    }

                    $cash_rebate = $rebate_amount/100; //现金返利按照购买者上三级，没人返利百分之一现金。
                    $pd_amount = floatval($order_info['goods_amount']);
                    if ($pd_amount > 0) {
                        $model_pd = Model('predeposit');
                        $data_pd = array();
                        $data_pd['member_id'] = $store_inviter_id3;
                        $data_pd['member_name'] = $member_name;
                        $data_pd['amount'] = $cash_rebate;
                        $data_pd['order_sn'] = $order_info['order_sn'];
                        $model_pd->changePd('cash_rebate',$data_pd);
                    }



                }
            }
        }
        //////////////////////////end 三级返利///////////////////////////////////////
        //更新订单状态
        $update_order = array();
        $update_order['finnshed_time'] = TIMESTAMP;
        $update_order['order_state'] = ORDER_STATE_SUCCESS;
        $update = $this->editOrder($update_order,array('order_id'=>$order_id));
        if (!$update) {
            throw new Exception('保存失败');
        }

        //发放优惠券：推荐
        M('red')->SendRedInvite($order_info['buyer_id']);

        //添加订单日志
        $data = array();
        $data['order_id'] = $order_id;
        $data['log_role'] = 'buyer';
        $data['log_msg'] = '签收了货物';
        if ($extend_msg) {
            $data['log_msg'] .= ' ( '.$extend_msg.' )';
        }
        $data['log_orderstate'] = ORDER_STATE_SUCCESS;
        $this->addOrderLog($data);

//	    //确认收货时添加会员积分
//	    if (C('points_isuse') == 1){
//	        $points_model = Model('points');
//	        $points_model->savePointsLog('order',array('pl_memberid'=>$member_id,'pl_membername'=>$member_name,'orderprice'=>$order_info['order_amount'],'order_sn'=>$order_info['order_sn'],'order_id'=>$order_info['order_id']),true);
//	    }
    }

    /**
     * 取得其它订单类型的信息
     * @param unknown $order_info
     */
    public function getOrderExtendInfo(& $order_info) {
        //取得预定订单数据
        if (isset($order_info['order_type']) && $order_info['order_type'] == 2) {
            $result = Logic('order_book')->getOrderBookInfo($order_info);
            //如果是未支付尾款
            if ($result['data']['if_buyer_repay']) {
                $result['data']['order_pay_state'] = false;
            }
            $order_info = $result['data'];
        }
    }
    //获取订单的支付时间
    public function getOrderAddtime($id){
        $condition['order_id']=$id;
        return $this->table('order')->field('payment_time')->where($condition)->find();
    }

//根据pay_sn获取订单信息
    public function getOrderDetialInfo($pay_sn){
        return $this->table('order')->field('*')->where(array('pay_sn'=>$pay_sn))->select();
    }
    //获取用户的积分获取日志
    public function getUserPointsInfo($member_id){
        return $this->table('points_log')->field('*')->where(array('pl_memberid'=>$member_id))->order('pl_addtime desc')->select();
    }
    //修改商品的订单状态 10-14新增
    public function editGoodOrderStatus($condition){
        return $this->table('order_goods')->where($condition)->update(array('comment_status'=>1));
    }
    //获取所有商品的评论状态值 根据订单号  10-14新增
    public function getGoodsCommentStatus($order_id){
        $condition=array();
        $condition['order_id']=$order_id;
        return $this->table('order_goods')->field('comment_status')->where($condition)->select();
    }
    //获取店铺评论的信息 根据订单号 10-14新增
    public function getCommentStore($order_id){
        $condition=array();
        $condition['seval_orderid']=$order_id;
        return $this->table('evaluate_store')->field('seval_desccredit,seval_servicecredit,seval_deliverycredit')->where($condition)->find();
    }
    //根據訂單號獲取訂單信息（從代付款裡面支付）
    public function getOrderDetialByOrder_id($order_id){
        return $this->table('order')->field('*')->where(array('order_id'=>$order_id))->select();
    }
    //根据订单号 获取订单商品信息
    public function getOrderGoodsInfos($condition = array(), $fields = '*', $order = '') {
        return $this->table('order_goods')->where($condition)->field($fields)->order($order)->select();
    }
    //支付成功之后修改订单状态
    public function changeOrderStatus($condition,$update){
        return $this->table('order')->where($condition)->update($update);
    }
    /**
     * 取得买卖家订单数量某个缓存
     * @param string $type $type 买/卖家标志，允许传入 buyer、store
     * @param int $id 买家ID、店铺ID
     * @param string $key 允许传入  NewCount、PayCount、SendCount、EvalCount、TakesCount，分别取相应数量缓存，只许传入一个
     * @return int
     */
    public function getOrderNumByID($type, $id, $key) {
        $cache_info = $this->getOrderNumCache($type, $id, $key);
        if (is_string($cache_info[$key])) {
            //从缓存中取得
            $count = $cache_info[$key];
        } else {
            //从数据库中取得
            $field = $type == 'buyer' ? 'buyer_id' : 'vid';
            $condition = array($field => $id);
            $func = 'getOrderState'.$key;
            $count = $this->$func($condition);
            $this->editOrderNumCache($type,$id,array($key => $count));
        }
        return $count;
    }
    /**
     * 取得(买/卖家)订单某个数量缓存
     * @param string $type 买/卖家标志，允许传入 buyer、store
     * @param int $id   买家ID、店铺ID
     * @param string $key 允许传入  NewCount、PayCount、SendCount、EvalCount、TakesCount，分别取相应数量缓存，只许传入一个
     * @return array
     */
    public function getOrderNumCache($type, $id, $key) {
        if (!C('cache_open')) return array();
        $type = 'ordercount'.$type;
        $ins = Cache::getInstance('cacheredis');
        $order_info = $ins->hget($id,$type,$key);
        return !is_array($order_info) ? array($key => $order_info) : $order_info;
    }
    /**
     * 设置(买/卖家)订单某个数量缓存
     * @param string $type 买/卖家标志，允许传入 buyer、store
     * @param int $id 买家ID、店铺ID
     * @param array $data
     */
    public function editOrderNumCache($type, $id, $data) {
        if (!C('cache_open') || empty($type) || !intval($id) || !is_array($data)) return ;
        $ins = Cache::getInstance('cacheredis');
        $type = 'ordercount'.$type;
        $ins->hset($id,$type,$data);
    }
    /**
     * 交易中的订单数量
     * @param unknown $condition
     */
    public function getOrderStateTradeCount($condition = array()) {
        $condition['order_state'] = array(array('neq',ORDER_STATE_CANCEL),array('neq',ORDER_STATE_SUCCESS),'and');
        return $this->getOrderCount($condition);
    }
    /**
     * 取得店铺订单列表
     *
     * @param int $vid 店铺编号
     * @param string $order_sn 订单sn
     * @param string $buyer_name 买家名称
     * @param string $state_type 订单状态
     * @param string $query_start_date 搜索订单起始时间
     * @param string $query_end_date 搜索订单结束时间
     * @param string $skip_off 跳过已关闭订单
     * @return array $order_list
     */
    public function getStoreOrderList($vid, $order_sn, $buyer_name, $state_type, $query_start_date, $query_end_date, $skip_off, $fields = '*', $extend = array(),$chain_id = null) {
        $condition = array();
        $condition['vid'] = $vid;
        if ($order_sn != '') {
            $condition['order_sn'] = array('like','%'.$order_sn.'%');;
        }
        if ($buyer_name != '') {
            $condition['buyer_name'] = $buyer_name;
        }
        if (isset($chain_id)) {
            $condition['chain_id'] = intval($chain_id);
        }
        if ($state_type != '') {
            switch($state_type){
                case 1:
                    $condition['order_state'] = ORDER_STATE_NEW;
                    break;
                case 256:
                    $condition['order_state'] = ORDER_STATE_PAY;
                    break;
                case 1024:
                    $condition['order_state'] = ORDER_STATE_SEND;
                    break;
                case 2048:
                    $condition['order_state'] = ORDER_STATE_SUCCESS;
                    break;
                case 4096:
                    $condition['order_state'] = ORDER_STATE_CANCEL;
                    break;
                case nocomment:
                    $condition['order_state'] = ORDER_STATE_SUCCESS;
                    break;
            }
        }else{
            $condition['order_state'] = 'store_order';
        }
        $if_start_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/',$query_start_date);
        $if_end_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/',$query_end_date);
        $start_unixtime = $if_start_date ? strtotime($query_start_date) : null;
        $end_unixtime = $if_end_date ? strtotime($query_end_date): null;
        if ($start_unixtime || $end_unixtime) {
            $condition['add_time'] = array('time',array($start_unixtime,$end_unixtime));
        }

        if ($skip_off == 1) {
            $condition['order_state'] = array('neq',ORDER_STATE_CANCEL);
        }

        if ($state_type == '1') {
            $condition['chain_code'] = 0;
        }
        if ($state_type == '256') {
            $condition['chain_code'] = 0;
        }
        if ($state_type == 'state_notakes') {
            $condition['order_state'] = array('in',array(ORDER_STATE_NEW,ORDER_STATE_PAY));
            $condition['chain_code'] = array('gt',0);
        }

        $order_list = $this->getOrderList($condition, 20, $fields, 'order_id desc','', $extend);

        //页面中显示那些操作
        foreach ($order_list as $key => $order_info) {

            //显示取消订单
            $order_info['if_store_cancel'] = $this->getOrderOperateState('store_cancel',$order_info);
            //显示调整费用
            $order_info['if_modify_price'] = $this->getOrderOperateState('modify_price',$order_info);
            //显示调整订单费用
            $order_info['if_spay_price'] = $this->getOrderOperateState('spay_price',$order_info);
            //显示发货
            $order_info['if_store_send'] = $this->getOrderOperateState('store_send',$order_info);
            //显示锁定中
            $order_info['if_lock'] = $this->getOrderOperateState('lock',$order_info);
            //显示物流跟踪
            $order_info['if_deliver'] = $this->getOrderOperateState('deliver',$order_info);
            //门店自提订单完成状态
            $order_info['if_chain_receive'] = $this->getOrderOperateState('chain_receive',$order_info);

            //查询消费者保障服务
            if (C('contract_allow') == 1) {
                $contract_item = Model('contract')->getContractItemByCache();
            }
            foreach ($order_info['extend_order_goods'] as $value) {
                $value['image_60_url'] = cthumb($value['goods_image'], 60, $value['vid']);
                $value['image_240_url'] = cthumb($value['goods_image'], 240, $value['vid']);
                $value['goods_type_cn'] = orderGoodsType($value['goods_type']);
                $value['goods_url'] = urlShop('goods','index',array('gid'=>$value['gid']));
                //处理消费者保障服务
                if (trim($value['goods_contractid']) && $contract_item) {
                    $goods_contractid_arr = explode(',',$value['goods_contractid']);
                    foreach ((array)$goods_contractid_arr as $gcti_v) {
                        $value['contractlist'][] = $contract_item[$gcti_v];
                    }
                }
                if ($value['goods_type'] == 5) {
                    $order_info['zengpin_list'][] = $value;
                } else {
                    $order_info['goods_list'][] = $value;
                }
            }

            if (empty($order_info['zengpin_list'])) {
                $order_info['goods_count'] = count($order_info['goods_list']);
            } else {
                $order_info['goods_count'] = count($order_info['goods_list']) + 1;
            }

            //取得其它订单类型的信息
            $this->getOrderExtendInfo($order_info);
            $order_list[$key] = $order_info;
        }

        return $order_list;
    }

    // 同步更新 order_goods 中的商品 goods_pay_price 金额 修改后的金额 按照原订单 商品价格所占比例进行调整
    public function update_order_goods_pay_price($order_info,$new_goods_amount){
        $order_goods_list = $this->getOrderGoodsList(array('order_id'=>$order_info['order_id']));
        $result = false;
        if ($order_info['goods_amount'] && $new_goods_amount >= 0) {
            $old_goods_amount = $order_info['goods_amount'];
            $need_update_data = array();
            $rec_id_arr = array();
            foreach ($order_goods_list as $key => $value) {
                $need_update_data_item = array();
                $item_goods_pay_price = $value['goods_pay_price'];
                $parent_number = ($item_goods_pay_price*1)/($old_goods_amount*1);
                $need_update_pay_price = $parent_number * ($new_goods_amount*1);
                $need_update_data_item['rec_id'] = $value['rec_id'];
                $rec_id_arr[] = $value['rec_id'];
                $need_update_data_item['goods_pay_price'] = sldPriceFormat($need_update_pay_price);

                $need_update_data[] = $need_update_data_item;
            }
            if (!empty($need_update_data)) {
                $sql = "UPDATE ".$this->getTableName('order_goods')." SET `goods_pay_price` = CASE `rec_id` ";
                foreach ($need_update_data as $nud_k => $nud_v) {
                    $sql .= sprintf("WHEN %d THEN %s ", $nud_v['rec_id'], $nud_v['goods_pay_price']); // 拼接SQL语句
                }
                $ids = implode(',', $rec_id_arr);
                $sql .= "END WHERE `rec_id` IN ($ids)";
                $result = $this->execute($sql);
            }
        }
        return $result;
    }




    //三级分销返还佣金，返还积分和返利可以二选一
    private function fanli($order_info){

        $model_member = Model('member');
        $model_goods=Model('goods');
        $model_order = Model('order');

        $order_id = $order_info['order_id'];

        $model_setting = Model('setting');
        $list_setting = $model_setting->getListSetting();
//        $points_rebate_grade0=$list_setting['points_rebate_grade0']/100;//买家返利
        $points_rebate_grade1=$list_setting['points_rebate_grade1']/100;//一级返利
        $points_rebate_grade2=$list_setting['points_rebate_grade2']/100;//二级返利
        $points_rebate_grade3=$list_setting['points_rebate_grade3']/100;//三级返利
        $points_rebate_set=$list_setting['points_rebate_set'];//返积分倍数

        //获取订单商品列表方便计算返利
        $order_goods_info = $model_order->getOrderInfo(array('order_id'=>$order_id),array('order_goods'));
        $order_goods_list  = $order_goods_info['extend_order_goods'];

        $commission_total = 0;
        foreach($order_goods_list as $goods){
//            $goods_info=$model_goods->getGoodsInfoByID($goods['gid']);
//            $commission_total = $commission_total + $goods['goods_yongjin'];
            $commission_total = $commission_total + $goods['goods_pay_price'];
        };
        $commission_total = $commission_total * $points_rebate_set;

        if($commission_total > 0){//只用总佣金额度大于0才进行分佣操作 写入fengxiao_log表
            //三级返利1级上线,买家推荐人===============
            $member_info1=$model_member->getMemberInfoByID($order_info['buyer_id']);
            $inviter_id1=$member_info1['inviter_id'];
            if(!empty($inviter_id1)) {
                $d_array['contribution_member_id'] = $order_info['buyer_id'];
                $d_array['contribution_member_name'] = $member_info1['member_name'];
                $d_array['reciver_member_id'] = $inviter_id1 ;
                $d_array['add_time'] = TIMESTAMP;
                $d_array['yongjin'] = $commission_total * $points_rebate_grade1;
                $d_array['order_id'] = $order_info['order_id'];
                $d_array['order_total'] = $order_info['order_amount'];
                $d_array['order_sn'] = $order_info['order_sn'];
                $d_array['description'] = 1;
                $d_array['status'] = 0;

                $model_fenxiao = Model('fenxiao');
                $result = $model_fenxiao -> addRecord($d_array);
                if ($result <= 0) {
                    throw new Exception('返利一级失败');
                }

                //三级返利2级上线,买家推荐人===============
                $member_info2=$model_member->getMemberInfoByID($inviter_id1);
                $inviter_id2=$member_info2['inviter_id'];
                if(!empty($inviter_id2)) {

                    $d_array = array();
                    $d_array['contribution_member_id'] = $order_info['buyer_id'] ;
                    $d_array['contribution_member_name'] = $member_info2['member_name'];
                    $d_array['reciver_member_id'] = $inviter_id2;
                    $d_array['add_time'] = TIMESTAMP;
                    $d_array['yongjin'] = $commission_total * $points_rebate_grade2;
                    $d_array['order_id'] = $order_info['order_id'];
                    $d_array['order_total'] = $order_info['order_amount'];
                    $d_array['order_sn'] = $order_info['order_sn'];
                    $d_array['description'] = 2;
                    $d_array['status'] = 0;

                    $result = $model_fenxiao->addRecord($d_array);
                    if ($result <= 0) {
                        throw new Exception('二级返利失败');
                    }

                    //三级返利3级上线,买家推荐人===============
                    $member_info3=$model_member->getMemberInfoByID($inviter_id2);
                    $inviter_id3=$member_info3['inviter_id'];
                    if(!empty($inviter_id3)) {

                        $d_array = array();
                        $d_array['contribution_member_id'] = $order_info['buyer_id'];
                        $d_array['contribution_member_name'] = $member_info3['member_name'];
                        $d_array['reciver_member_id'] = $inviter_id3;
                        $d_array['add_time'] = TIMESTAMP;
                        $d_array['yongjin'] = $commission_total * $points_rebate_grade3;
                        $d_array['order_id'] = $order_info['order_id'];
                        $d_array['order_total'] = $order_info['order_amount'];
                        $d_array['order_sn'] = $order_info['order_sn'];
                        $d_array['description'] = 3;
                        $d_array['status'] = 0;

                        $result = $model_fenxiao->addRecord($d_array);
                        if ($result <= 0) {
                            throw new Exception('三级返利失败');
                        }
                    }
                }
            }
        }

    }


    function encode($data = '', $key = 'sldkey')
    {
        $c=md5($key);
        $t=str_split($c);
        $keyy='';
        foreach ($t as $v) {
            $keyy .= ord($v);
        }
        $data=$data+substr($c,-4);
        $new='';
        $t=str_split($data);

        for($i=0;$i<count($t);$i++){
            $new .= ($t[$i] + $keyy[$i])%10;
        }


        return $new;
    }

    function decode($data = '', $key = 'sldkey')
    {
        $data=str_replace(' ','',$data);
        $c=md5($key);
        $t=str_split($c);
        $keyy='';
        foreach ($t as $v) {
            $keyy .= ord($v);
        }
        $new='';
        $t=str_split($data);
        for($i=0;$i<count($t);$i++){
            if($t[$i]>=$keyy[$i]){
                $a=$t[$i]-$keyy[$i];
            }else{
                $a=($t[$i]+10)-$keyy[$i];
            }
            $a= $t[$i]>=$keyy[$i]? $t[$i]-$keyy[$i] : ($t[$i]+10)-$keyy[$i];
            $new .= $a;
        }
        $new = $new-substr($c,-4);
        return $new;
    }
    /*
    * 比率计算积分,退款退货时用到
     * @param $goodsinfo order_goods表单条信息
    */
    function Calculation($goodsinfo)
    {
        $goods_list = $this->table('order_goods')->where(['order_id'=>$goodsinfo['order_id']])->select();
        $b = low_array_column($goods_list,'goods_pay_price','gid');
        $sum = array_sum($b);
        $count = count($b);
        $order_info = $this->table('order')->where(['order_id'=>$goodsinfo['order_id']])->field('pd_points,points_ratio')->find();
        $a = $order_info['pd_points'];
        $i = 0;
        $c = [];
        foreach($b as $k=>$v){
            if($count == $i+1){
                $c[$k] = $a;
            }else{
                $tem = floor($a*($v/$sum));
                $c[$k] = $tem;
                $a = $a-$tem;
            }
            $i++;
        }
        return $c[$goodsinfo['gid']]/ $order_info['points_ratio'];
    }

    //根据goods_commonid查找课程下的所有学生
    public function get_students_list($goods_commonid,$keyword=''){
        $model = Model('order_goods');

        $where = ['g_c.goods_commonid'=>$goods_commonid];
        if(!empty($keyword)){
            $where['m.member_name'] = array('like','%'.$keyword.'%');
        }

        $fields = 'o_g.rec_id,o_g.gid,o_g.buyer_id,o_g.teacher,o_g.s_comment_status,g.gid,g_c.goods_commonid,g_c.goods_name,g_c.goods_image,m.member_name,m.member_avatar';
        $list = $model->table('order_goods,goods,goods_common,member')->alias('o_g,g,g_c,m')->join('left')->on('o_g.gid=g.gid,g.goods_commonid=g_c.goods_commonid,o_g.buyer_id=m.member_id')->field($fields)->where($where)->select();

        return $list;
    }
    /**
     * 取消订单
     * @param array $order_info
     * @param string $role 操作角色 buyer、seller、admin、system 分别代表买家、商家、管理员、系统
     * @param string $user 操作人
     * @param string $msg 操作备注
     * @param boolean $if_update_account 是否变更账户金额
     * @param boolean $if_queue 是否使用队列
     * @return array
     */
    public function changeOrderStateCancel($order_info, $role, $user = '', $msg = '', $if_update_account = true, $if_quque = true) {
        try {
            $model_order = new UserOrder();
            Db::startTrans();
            $order_id = $order_info['order_id'];

            //库存销量变更
            $goods_list = $this->getOrderGoodsList(array('order_id'=>$order_id));
            $data = array();
            foreach ($goods_list as $goods) {
                if ($goods['has_spec']) {
                    $goodsModel = new Goods();
                    $item_goods_info = $goodsModel->getGoodsInfoByID($goods['gid'],'goods_commonid');

                    // 有规格 (获取规格信息)
                    $spec_array = $goodsModel->getGoodsList(array('goods_commonid' => $item_goods_info['goods_commonid']), 'goods_spec,gid');
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

                        $spec_list[$spec_sign]['gid'] = $s_value['gid'];
                    }

                    $item_spec_num = unserialize($goods['spec_num']);
                    foreach ($item_spec_num as $spec_key => $spec_value) {
                        $data[$spec_list[$spec_key]['gid']] = $spec_value;
                    }
                }else{
                    $data[$goods['gid']] = $goods['goods_num'];
                }

                //如果有首单优惠，取消订单把优惠使用权还回来
                if($goods['first']){
                    //修改首单满减记录
                    DB::name('first_discount_log')->where(['order_id'=>$order_id])->update(['state'=>0]);
                }
            }
            if ($if_quque) {
                Queue::push('cancelOrderUpdateStorage', array('id'=>$order_info['dian_id'],'data'=>$data));
            } else {
                $this->cancelOrderUpdateStorage(array('id'=>$order_info['dian_id'],'data'=>$data));
            }

            if ($if_update_account) {
                $model_pd = new Predeposit();
                //解冻充值卡
                $rcb_amount = 0;
                if(isset($order_info['rcb_amount']))
                $rcb_amount = floatval($order_info['rcb_amount']);
                if ($rcb_amount > 0) {
                    $data_pd = array();
                    $data_pd['member_id'] = $order_info['buyer_id'];
                    $data_pd['member_name'] = $order_info['buyer_name'];
                    $data_pd['amount'] = $rcb_amount;
                    $data_pd['order_sn'] = $order_info['order_sn'];
                    $model_pd->changeRcb('order_cancel',$data_pd);
                }

                //解冻预存款
                $pd_amount = floatval($order_info['pd_amount']);
                if ($pd_amount > 0) {
                    $data_pd = array();
                    $data_pd['member_id'] = $order_info['buyer_id'];
                    $data_pd['member_name'] = $order_info['buyer_name'];
                    $data_pd['amount'] = $pd_amount;
                    $data_pd['order_sn'] = $order_info['order_sn'];
                    $model_pd->changePd('order_cancel',$data_pd);
                }
                //退还积分
                $pd_point = intval($order_info['pd_points']);
                if ($pd_point > 0) {
                    $points = new Points();
                    $points->savePointsLog('returnpurpose', array('pl_memberid' => $order_info['buyer_id'], 'pl_membername' => $order_info['buyer_name'], 'orderprice' => $order_info['goods_amount'], 'order_sn' => $order_info['order_sn'], 'order_id' => $order_id, 'pl_points' => $pd_point), true);
                }

                if($order_info['red_id']>0){
                    //退回优惠券  平台
                    M('red')->table('red_user')->where(array('id'=>$order_info['red_id']))->update(array('reduser_use'=>0));
                }

                if($order_info['vred_id']>0){
                    //退回优惠券  店铺
                    M('red')->table('red_user')->where(array('id'=>$order_info['vred_id']))->update(array('reduser_use'=>0));
                }
            }

            //更新订单信息
            $update_order = array('order_state' => ORDER_STATE_CANCEL, 'pd_amount' => 0);
            $update = $model_order->editOrder($update_order,array('order_id'=>$order_id));
            if (!$update) {
                return false;
            }

            //添加订单日志
            $data = array();
            $data['order_id'] = $order_id;
            $data['log_role'] = $role;
            $data['log_msg'] = '取消了订单';
            $data['log_user'] = $user;
            if ($msg) {
                $data['log_msg'] .= ' ( '.$msg.' )';
            }
            $data['log_orderstate'] = ORDER_STATE_CANCEL;
            $model_order->addOrderLog($data);

            // 推手系统 订单状态更新
            if (Config('spreader_isuse') && Config('sld_spreader')) {
                $par['state_type'] = 'order_cancel';
                $par['order_info'] = $order_info;
                $par['extend_msg'] = '';
                // 发送请求 添加订单信息
                con_addons('spreader',$par,'update_order_status_speader','api','mobile');
            }

            $model_order->commit();

            return true;//callback(true,'操作成功');

        } catch (Exception $e) {
           // $this->rollback();
            return print_r($e->getMessage());
        }
    }
    /**
     * 取消订单变更库存销量 王强标记更新库存（加库存）
     * @param unknown $goods_buy_quantity
     * @param int     $dian_id  自提门店id
     */
    public function cancelOrderUpdateStorage($goods_buy_quantity) {
        $dian_id=$goods_buy_quantity['id'];
        $goods_buy_quantity = $goods_buy_quantity['data'];
        if($dian_id){
            $model_goods = new StoreGoods();
            foreach ($goods_buy_quantity as $gid => $quantity) {
                $data = array();
                $data['stock'] = array('inc', 'stock+' . $quantity);
                $data['sales'] = array('dec', 'sales-' . $quantity);
                $result = $model_goods->editGoods($data, array('goods_id' => $gid,'dian_id'=>$dian_id));
            }
        }else{
            $model_goods = new Goods();
            foreach ($goods_buy_quantity as $gid => $quantity) {
                $data = array();
                $data['goods_storage'] = array('inc','goods_storage+'.$quantity);
                $data['goods_salenum'] = array('dec','goods_salenum-'.$quantity);
                $result = $model_goods->editGoods($data, array("gid"=>$gid));
            }
        }

        if (!$result) {
            return callback(false,'变更商品库存与销量失败');
        } else {
            return callback(true);
        }
    }
}