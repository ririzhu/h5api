<?php
namespace app\v1\model;

use think\Model;
use think\db;
class Refund extends Model
{
    /**
     * 取得退单数量
     * @param unknown $condition
     */
    public function getRefundReturn($condition) {
        return DB::name('refund_return')->where($condition)->count();
    }


    /**
     * 取得退单数量
     * @param unknown $condition
     */
    public function getRefundReturnByDianId($condition) {
        foreach ($condition as $k=>$v){
            if($k='vid'){
                $condition['order.dian_id'] = $v;
                unset($condition[$k]);
            }else {
                $condition[$k] = 'refund_return.' . $v;
            }
        }
        return DB::name('order')->join('refund_return','order.order_id=refund_return.order_id')->where($condition)->count();
    }



    /**
     * 增加退款退货
     *
     * @param
     * @return int
     */
    public function addRefundReturn($refund_array, $order = array(), $goods = array()) {
        if (!empty($order) && is_array($order)) {
            $refund_array['order_id'] = $order['order_id'];
            $refund_array['order_sn'] = $order['order_sn'];
            $refund_array['vid'] = $order['vid'];
            $refund_array['store_name'] = $order['store_name'];
            $refund_array['buyer_id'] = $order['buyer_id'];
            $refund_array['buyer_name'] = $order['buyer_name'];
        }
        if (!empty($goods) && is_array($goods)) {
            $refund_array['gid'] = $goods['gid'];
            $refund_array['order_gid'] = $goods['rec_id'];
            $refund_array['order_goods_type'] = $goods['goods_type'];
            $refund_array['goods_name'] = $goods['goods_name'];
            $refund_array['commis_rate'] = $goods['commis_rate'];
            $refund_array['goods_image'] = $goods['goods_image'];
        }
        $refund_array['refund_sn'] = $this->getRefundsn($refund_array['vid']);
        if(count(db::name("refund_return")->where(['order_sn'=>$order['order_sn']])->find())=='0') {
            $refund_id = db::name('refund_return')->insert($refund_array);
            // 发送商家提醒
            $param = array();
            if (intval($refund_array['refund_type']) == 1) {    // 退款
                $param['code'] = 'refund';
            } else {    // 退货
                $param['code'] = 'return';
            }
            $param['vid'] = $order['vid'];
            $type = $refund_array['order_lock'] == 2 ? '售前' : '售后';
            $param['param'] = array(
                'type' => $type,
                'refund_sn' => $refund_array['refund_sn']
            );
        }else{
            $refund_id=false;
        }
        //QueueClient::push('sendStoreMsg', $param);
        return $refund_id;
    }

    /**
     * 订单锁定
     *
     * @param
     * @return bool
     */
    public function editOrderLock($order_id) {
        $order_id = intval($order_id);
        if ($order_id > 0) {
            $condition = array();
            $condition['order_id'] = $order_id;
            $data = array();
            $data['lock_state'] = array('inc','lock_state+1');
            $result = db::name('order')->where($condition)->update($data);
            return $result;
        }
        return false;
    }

    /**
     * 订单解锁
     *
     * @param
     * @return bool
     */
    public function editOrderUnlock($order_id) {
        $order_id = intval($order_id);
        if ($order_id > 0) {
            $condition = array();
            $condition['order_id'] = $order_id;
            $condition['lock_state'] = array('egt','1');
            $data = array();
            $data['lock_state'] = array('exp','lock_state-1');
            $data['delay_time'] = time();
            $result = DB::name('order')->where($condition)->update($data);
            return $result;
        }
        return false;
    }

    /**
     * 修改记录
     *
     * @param
     * @return bool
     */
    public function editRefundReturn($condition, $data) {
        if (empty($condition)) {
            return false;
        }
        if (is_array($data)) {
            $result = DB::name('refund_return')->where($condition)->update($data);
            return $result;
        } else {
            return false;
        }
    }

    /**
     * 平台确认退款处理
     *
     * @param
     * @return bool
     */
    public function editOrderRefund($refund) {
        $refund_id = intval($refund['refund_id']);
        if ($refund_id > 0) {
            //Language::read('model_lang_index');
            $order_id = $refund['order_id'];//订单编号
            $field = 'order_id,buyer_id,buyer_name,vid,order_sn,order_amount,payment_code,order_state,refund_amount,pd_points,dian_id,pin_id';
            $order = $this->table('order')->field($field)->where(array('order_id'=> $order_id))->find();
            $predeposit_model = new Predeposit();
            $log_array = array();
            $log_array['member_id'] = $order['buyer_id'];
            $log_array['member_name'] = $order['buyer_name'];
            $log_array['amount'] = $refund['refund_amount'];
            $log_array['order_sn'] = $order['order_sn'];
            $state = $predeposit_model->changePd('refund', $log_array);//增加买家可用金额
            //增加一个退还的积分存储,用于结算时用

            if($order['pd_points'] > 0) {
                //开始计算应退多少钱
                $order_model = new UserOrder();
                $refund_info = $predeposit_model->table('refund_return')->where(['refund_id'=>$refund_id])->find();
                if($refund_info['order_gid'] != 0){
                    $goods = $predeposit_model->table('order_goods')->where(['order_id'=>$refund_info['order_id'],'gid'=>$refund_info['gid']])->find();
                    $order['pd_points'] = $order_model->Calculation($goods)*$order['points_ratio'] ;
                }
                $this->editRefundReturn(['refund_id'=>$refund_id],['pd_points'=>$order['pd_points']/$order['points_ratio']]);
                //扣除抵扣积分
                $points = new Points();
                $points->savePointsLog('returnpurpose', array('pl_memberid' => $order['buyer_id'], 'pl_membername' => $order['buyer_name'], 'orderprice' => $order['goods_amount'], 'order_sn' => $order['order_sn'], 'order_id' => $order_id, 'pl_points' => $order['pd_points']), true);
            }
            if($order['red_id']>0){
                //退回优惠券 平台
                M('red')->table('red_user')->where(array('id'=>$order['red_id']))->update(array('reduser_use'=>0));
            }

            if($order['vred_id']>0){
                //退回优惠券 店铺
                M('red')->table('red_user')->where(array('id'=>$order['vred_id']))->update(array('reduser_use'=>0));
            }

            $order_state = $order['order_state'];
            $model_trade = new trade;
            $order_paid = $model_trade->getOrderState('order_paid');//订单状态20:已付款
            if ($state && $order_state == $order_paid) {
                $log_array = array();
                $log_array['order_id'] = $order_id;
                $log_array['log_role'] = 'system';
                $log_array['log_time'] = time();
                $log_array['log_msg'] = '商品全部退款完成取消订单。';
                $state = $model_trade->editOrderCancel($order_id, $log_array);//已付款未发货时取消订单
            }
            $order_shipped = $model_trade->getOrderState('order_shipped');//订单状态30:已发货
            $order_completed = $model_trade->getOrderState('order_completed');//订单状态40:已收货
            if ($state && ($order_state == $order_shipped || $order_state == $order_completed)) {
                $log_array = array();
                $log_array['order_id'] = $order_id;
                $log_array['log_role'] = 'system';
                $log_array['log_time'] = time();
                $log_array['log_msg'] = '退货库存回退';
                // 库存回退
                $goods_buy_quantity[$refund['gid']] = $refund['goods_num'];
                Model('buy')->updateGoodsStorageNum($goods_buy_quantity,$order['dian_id'],true);
                Model('order')->addOrderLog($log_array);
            }
            if ($state) {
                $order_array = array();
                $order_amount = $order['order_amount'];//订单金额
                $refund_amount = $order['refund_amount']+$refund['refund_amount'];//退款金额
                $order_array['refund_state'] = ($order_amount-$refund_amount) > 0 ? 1:2;
                $order_array['refund_amount'] = sldPriceFormat($refund_amount);
                $order_array['delay_time'] = time();
                $state = $this->table('order')->where(array('order_id'=> $order_id))->update($order_array);//更新订单退款
            }
            if ($state && $refund['order_lock'] == '2') {
                $state = $this->editOrderUnlock($order_id);//订单解锁
            }

            // 推手系统 订单状态更新
            if (C('spreader_isuse') && C('sld_spreader')) {
                $par['state_type'] = 'refund';
                $order_model = Model('order');
                $condition['order_id'] = $order['order_id'];
                $order_info = $order_model->getOrderInfo($condition,array('order_goods'));
                $par['order_info'] = $order_info;
                $par['extend_msg'] = '';
                // 发送请求 添加订单信息
                con_addons('spreader',$par,'update_order_status_speader','api','mobile');
            }

            // 看看订单是不是团长待返利订单
            if (C('sld_pintuan') && $order['pin_id']) {
                M('pin')->out_fanli($order);
            }

            return $state;
        }
        return false;
    }

    /**
     * 取退款退货记录
     *
     * @param
     * @return array
     */
    public function getRefundReturnList($condition = array(), $page = '', $fields = '*', $limit = '') {
        //联查的主要目的就是筛符合城市分站的店铺，从而筛选订单
        if(isset($condition['vendor.province_id']) && $condition['vendor.province_id>0'] | isset($condition['vendor.city_id']) && $condition['vendor.city_id'>0]| isset($condition['vendor.city_id']) && $condition['vendor.area_id']>0){
            $result = DB::name('refund_return')->join('vendor','refund_return.vid=vendor.vid')->field($fields)->where($condition)->page($page)->limit($limit)->order('refund_id desc')->select();
        }else{
            $result = DB::name('refund_return')->field($fields)->where($condition)->page($page)->limit($limit)->order('refund_id desc')->select();
        }
        return $result;
    }

    /**
     * 取退款退货记录  通过门店id
     *
     * @param
     * @return array
     */
    public function getRefundReturnListByDianId($condition = array(), $page = '', $fields = '*', $limit = '') {
        foreach ($condition as $k=>$v){
            if($k='vid'){
                $condition['order.dian_id'] = $v;
                unset($condition[$k]);
            }else {
                $condition[$k] = 'refund_return.' . $v;
            }
        }
        $result = $this->table('order,refund_return')->join('right')->on('order.order_id=refund_return.order_id')->where($condition)->field($fields)->page($page)->limit($limit)->order('refund_id desc')->select();

        return $result;
    }

    /**
     * 取退款记录
     *
     * @param
     * @return array
     */
    public function getRefundList($condition = array(), $page = '',$limit="") {
        $condition['refund_type'] = '1';//类型:1为退款,2为退货
        $result = $this->getRefundReturnList($condition, $page,'*',$limit);
        return $result;
    }

    /**
     * 取退货记录
     *
     * @param
     * @return array
     */
    public function getReturnList($condition = array(), $page = '') {
        $condition['refund_type'] = '2';//类型:1为退款,2为退货
        $result = $this->getRefundReturnList($condition, $page);
        return $result;
    }

    /**
     * 退款退货申请编号
     *
     * @param
     * @return array
     */
    public function getRefundsn($vid) {
        $result = mt_rand(100,999).substr(100+$vid,-3).date('ymdHis');
        return $result;
    }

    /**
     * 取一条记录
     *
     * @param
     * @return array
     */
    public function getRefundReturnInfo($condition = array(), $fields = '*') {
        return $this->table('refund_return')->where($condition)->field($fields)->find();
    }

    /**
     * 根据订单取商品的退款退货状态
     *
     * @param
     * @return array
     */
    public function getGoodsRefundList($order_list = array(), $order_refund = 0) {
        $order_ids = array();//订单编号数组
        $order_ids = array_keys($order_list);
        $model_trade = new Trade();
        $condition = array();
        $condition['order_id'] = array('in', arrayToString($order_ids));
        $refund_list = DB::name('refund_return')->where($condition)->order('refund_id desc')->select();
        $refund_goods = array();//已经提交的退款退货商品
        if (!empty($refund_list) && is_array($refund_list)) {
            foreach ($refund_list as $key => $value) {
                $order_id = $value['order_id'];//订单编号
                $gid = $value['order_gid'];//订单商品表编号
                if (empty($refund_goods[$order_id][$gid])) {
                    $refund_goods[$order_id][$gid] = $value;
                    if ($order_refund > 0) {//订单下的退款退货所有记录
                        $order_list[$order_id]['refund_list'] = $refund_goods[$order_id];
                    }
                }
            }
        }
        if (!empty($order_list) && is_array($order_list)) {
            foreach ($order_list as $key => $value) {
                $order_id = $key;
                $goods_list = array();
                if(isset($value['extend_order_goods'])) {
                    $goods_list = $value['extend_order_goods'];//订单商品
                }
                $order_state = $value['order_state'];//订单状态
                $order_paid = $model_trade->getOrderState('order_paid');//订单状态20:已付款
                $payment_code = $value['payment_code'];//支付方式

                if ($order_state == $order_paid && $payment_code != 'offline') {//已付款未发货的非货到付款订单可以申请取消
                    $order_list[$order_id]['refund'] = '1';
                } elseif ($order_state > $order_paid && !empty($goods_list) && is_array($goods_list)) {//已发货后对商品操作

                    $refund = $this->getRefundState($value);//根据订单状态判断是否可以退款退货
                    foreach ($goods_list as $k => $v) {
                        $gid = $v['rec_id'];//订单商品表编号
                        if ($v['goods_pay_price'] > 0) {//实际支付额大于0的可以退款
                            $v['refund'] = $refund;
                        }
                        if (!empty($refund_goods[$order_id][$gid])) {
                            $seller_state = $refund_goods[$order_id][$gid]['seller_state'];//卖家处理状态:1为待审核,2为同意,3为不同意
                            if ($seller_state == 3) {
                                $order_list[$order_id]['extend_complain'][$gid] = '1';//不同意可以发起退款投诉
                            } else {
                                $v['refund'] = '0';//已经存在处理中或同意的商品不能再操作
                            }
                            $v['extend_refund'] = $refund_goods[$order_id][$gid];
                        }
                        $goods_list[$k] = $v;
                    }
                }
                $order_list[$order_id]['extend_order_goods'] = $goods_list;
            }
        }

        return $order_list;
    }

    /**
     * 根据订单判断投诉订单商品是否可退款
     *
     * @param
     * @return array
     */
    public function getComplainRefundList($order) {
        $list = array();
        $refund_list = array();//已退或处理中商品
        $refund_goods = array();//可退商品
        if (!empty($order) && is_array($order)) {
            $order_id = $order['order_id'];
            $order_list[$order_id] = $order;
            $order_list = $this->getGoodsRefundList($order_list);
            $order = $order_list[$order_id];
            $goods_list = $order['extend_order_goods'];
            $order_amount = $order['order_amount'];//订单金额
            $order_refund_amount = $order['refund_amount'];//订单退款金额
            foreach ($goods_list as $k => $v) {
                $gid = $v['rec_id'];//订单商品表编号
                $v['refund_state'] = 3;
                if (!empty($v['extend_refund'])) {
                    $v['refund_state'] = $v['extend_refund']['seller_state'];//卖家处理状态为3,不同意时能退款
                }
                if ($v['refund_state'] > 2) {//可退商品
                    $goods_pay_price = $v['goods_pay_price'];//商品实际成交价
                    if ($order_amount < ($goods_pay_price + $order_refund_amount)) {
                        $goods_pay_price = $order_amount - $order_refund_amount;
                        $v['goods_pay_price'] = $goods_pay_price;
                    }
                    $v['goods_refund'] = $v['goods_pay_price'];
                    $refund_goods[$gid] = $v;
                } else {//已经存在处理中或同意的商品不能再退款
                    $refund_list[$gid] = $v;
                }
            }
        }
        $list = array(
            'refund' => $refund_list,
            'goods' => $refund_goods
        );
        return $list;
    }

    /**
     * 根据订单状态判断是否可以退款退货
     *
     * @param
     * @return array
     */
    public function getRefundState($order) {
        $refund = '0';//默认不允许退款退货
        $order_state = $order['order_state'];//订单状态
        $model_trade = Model('trade');
        $order_shipped = $model_trade->getOrderState('order_shipped');//30:已发货
        $order_completed = $model_trade->getOrderState('order_completed');//40:已收货
        switch ($order_state) {
            case $order_shipped:
                $payment_code = $order['payment_code'];//支付方式
                if ($order['refund_state'] == 0 || $order['refund_state'] ==1) {//货到付款订单在没确认收货前不能退款退货
                    $refund = '1';
                }
                break;
            case $order_completed:
                $order_refund = $model_trade->getMaxDay('order_refund');//15:收货完成后可以申请退款退货
                if($order['delay_time']){
                    $delay_time = $order['delay_time']+60*60*24*$order_refund;
                }else{
                    $delay_time = $order['finnshed_time']+60*60*24*$order_refund;
                }
                if ($delay_time > time() && ($order['refund_state'] == 0 || $order['refund_state'] == 1)) {
                    $refund = '1';
                }
                break;
            default:
                $refund = '0';
                break;
        }
        return $refund;
    }

    /**
     * 向模板页面输出退款退货状态
     *
     * @param
     * @return array
     */
    public function getRefundStateArray($type = 'all') {
        //Language::read('refund');
        $state_array = array(
            '1' => '待审核',
            '2' => '同意退款',
            '3' => '不同意'
        );//卖家处理状态:1为待审核,2为同意,3为不同意
        $data['state_arrya'] = $state_array;
        $admin_array = array(
            '1' => '处理中',
            '2' => '待处理',
            '3' => '已完成'
        );//确认状态:1为买家或卖家处理中,2为待平台管理员处理,3为退款退货已完成
        $data['admin_array'] = $state_array;

        $state_data = array(
            'seller' => $state_array,
            'admin' => $admin_array
        );
        if ($type == 'all') return $state_data;//返回所有
        return $state_data[$type];
    }

    /**
     * 退货退款数量
     *
     * @param array $condition
     * @return int
     */
    public function getRefundReturnCount($condition) {
        return $this->table('refund_return')->where($condition)->count();
    }
}