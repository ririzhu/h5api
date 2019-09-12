<?php
namespace app\v1\model;
use think\addons\red\red;
use think\addons\red\red1;
use think\db;
use think\Model;
include(dirname(__FILE__)."/../../../addons/red/model/red.php");
require(dirname(__FILE__)."/../../../addons/red/control/mall/member_red.php");

/**
 * 下单业务模型
 *
 */
defined('DYMall') or exit('Access Invalid!');
class UserBuy extends Model {

    /**
     * 输出有货到付款时，在线支付和货到付款及每种支付下商品数量和详细列表
     * @param $buy_list 商品列表
     * @return 返回 以支付方式为下标分组的商品列表
     */
    public function getOfflineGoodsPay($buy_list) {
        //以支付方式为下标，存放购买商品
        $buy_goods_list = array();
        $vendor_model = new VendorInfo();
//        $offline_pay = Model('payment')->getPaymentOpenInfo(array('payment_code'=>'offline'));
        $offline_pay = Config('cash_on_delivery');
        if ($offline_pay) {
            //判断商品里面的店铺是否全是支持货到付款的店铺
            $Avid = array_unique(low_array_column($buy_list,'vid'));
            $vendor_condition = [
                'vid'=>['in',implode(',',$Avid)]
            ];
            $venddor_list = $vendor_model->getStoreList($vendor_condition,'','','cash_on_delivery');
            foreach($venddor_list as $k=>$v){
                if(empty($v['cash_on_delivery'])){
                    return $buy_goods_list;
                }
            }
//            $offline_store_id_array = array(DEFAULT_PLATFORM_STORE_ID);
            foreach ($buy_list as $value) {
//                if (in_array($value['vid'],$offline_store_id_array)) {
                $buy_goods_list['offline'][] = $value;
//                } else {
//                    $buy_goods_list['online'][] = $value;
//                }
            }
        }
        return $buy_goods_list;
    }

    /**
     * 计算每个店铺(所有店铺级优惠活动)总共优惠多少金额
     * @param array $store_goods_total 最初店铺商品总金额
     * @param array $store_final_goods_total 去除各种店铺级促销后，最终店铺商品总金额(不含运费)
     * @return array
     */
    public function getStorePromotionTotal($store_goods_total, $store_final_goods_total) {
        if (!is_array($store_goods_total) || !is_array($store_final_goods_total)) return array();
        $store_promotion_total = array();
        foreach ($store_goods_total as $vid => $goods_total) {
            $store_promotion_total[$vid] = abs($goods_total - $store_final_goods_total[$vid]);
        }
        return $store_promotion_total;
    }

    /**
     * 返回需要计算运费的店铺ID组成的数组 和 免运费店铺ID及免运费下限金额描述
     * @param array $store_goods_total 每个店铺的商品金额小计，以店铺ID为下标
     * @return array
     */
    public function getStoreFreightDescList($store_goods_total) {
        if (empty($store_goods_total) || !is_array($store_goods_total)) return array(array(),array());

        //定义返回数组
        $need_calc_sid_array = array();
        $cancel_calc_sid_array = array();

        //如果商品金额未达到免运费设置下线，则需要计算运费
        $ids = "";
        foreach(array_keys($store_goods_total) as $k=>$v){
            $ids .= $v.",";
        }
        $ids = substr($ids,0,strlen($ids)-1);
        $condition = array('vid' => array('in',$ids));
        $vendorModel = new VendorInfo();
        $store_list = $vendorModel->getStoreOnlineList($condition,null,'','vid,store_free_price');
        foreach ($store_list as $store_info) {
            $limit_price = floatval($store_info['store_free_price']);
            if ($limit_price == 0 || $limit_price > $store_goods_total[$store_info['vid']]) {
                //需要计算运费
                $need_calc_sid_array[] = $store_info['vid'];
            } else {
                //返回免运费金额下限
                $cancel_calc_sid_array[$store_info['vid']]['free_price'] = $limit_price;
                $cancel_calc_sid_array[$store_info['vid']]['desc'] = "满".$limit_price."免运费";
            }
        }
        return array($need_calc_sid_array,$cancel_calc_sid_array);
    }

    /**
     * 取得店铺运费(使用运费模板的商品运费不会计算，但会返回模板信息)
     * 先将免运费的店铺运费置0，然后算出店铺里没使用运费模板的商品运费之和 ，存到iscalced下标中
     * 然后再计算使用运费模板的信息(array(店铺ID=>array(运费模板ID=>购买数量))，放到nocalced下标里
     * @param array $buy_list 购买商品列表
     * @param array $free_freight_sid_list 免运费的店铺ID数组
     */
    public function getStoreFreightList($buy_list = array(), $free_freight_sid_list) {
        //定义返回数组
        $return = array();
        //先将免运费的店铺运费置0(格式:店铺ID=>0)
        $freight_list = array();
        if (!empty($free_freight_sid_list) && is_array($free_freight_sid_list)) {
            foreach ($free_freight_sid_list as $vid) {
                $freight_list[$vid] = 0;
            }
        }

        //然后算出店铺里没使用运费模板(优惠套装商品除外)的商品运费之和(格式:店铺ID=>运费)
        //定义数组，存放店铺优惠套装商品运费总额 vid=>运费
        $store_bl_goods_freight = array();
        foreach ($buy_list as $key => $goods_info) {
            //免运费店铺的商品不需要计算
            if (array_key_exists($goods_info['vid'], $freight_list)) {
                unset($buy_list[$key]);
            }
            //优惠套装商品运费另算
            if (intval($goods_info['bl_id'])) {
                unset($buy_list[$key]);
                $store_bl_goods_freight[$goods_info['vid']] = $goods_info['bl_id'];
                continue;
            }
            if (isset($goods_info['transport_id']) && !intval($goods_info['transport_id']) &&  !in_array($goods_info['vid'],$free_freight_sid_list)) {
                try{
                    if(isset($freight_list[$goods_info['vid']]))
                    $freight_list[$goods_info['vid']] += $goods_info['goods_freight'];
                }catch (Exception $e){

                }

                unset($buy_list[$key]);
            }
        }
        //计算优惠套装商品运费
        if (!empty($store_bl_goods_freight)) {
            $model_bl = new Pbundling();
            //foreach (array_unique($store_bl_goods_freight) as $vid => $bl_id) {
            foreach ($store_bl_goods_freight as $vid => $bl_id) {
                $bl_info = $model_bl->getBundlingInfo(array('bl_id'=>$bl_id));
                if (!empty($bl_info) && !empty($freight_list)) {
                    $freight_list[$vid] += $bl_info['bl_freight'];
                }
            }
        }

        $return['iscalced'] = $freight_list;

        //最后再计算使用运费模板的信息(店铺ID，运费模板ID，购买数量),使用使用相同运费模板的商品数量累加
        $freight_list = array();
        if(isset($goods_info['transport_id']) ) {
            foreach ($buy_list as $goods_info) {
                $freight_list[$goods_info['vid']][$goods_info['transport_id']] += $goods_info['goods_num'];
            }
        }
        $return['nocalced'] = $freight_list;

        return $return;
    }

    /**
     * 根据地区选择计算出所有店铺最终运费
     * @param array $freight_list 运费信息(店铺ID，运费，运费模板ID，购买数量)
     * @param int $city_id 市级ID
     * @return array 返回店铺ID=>运费
     */
    public function calcStoreFreight($freight_list, $city_id) {
        if (!is_array($freight_list) || empty($freight_list) || empty($city_id)) return;

        //免费和固定运费计算结果
        $return_list = $freight_list['iscalced'];

        //使用运费模板的信息(array(店铺ID=>array(运费模板ID=>购买数量))
        $nocalced_list = $freight_list['nocalced'];

        //然后计算使用运费运费模板的在该$city_id时的运费值
        if (!empty($nocalced_list) && is_array($nocalced_list)) {
            //如果有商品使用的运费模板，先计算这些商品的运费总金额
            $model_transport = Model('transport');
            foreach ($nocalced_list as $vid => $value) {
                if (is_array($value)) {
                    foreach ($value as $transport_id => $buy_num) {
                        $freight_total = $model_transport->calc_transport($transport_id,$buy_num, $city_id);
                        if (empty($return_list[$vid])) {
                            $return_list[$vid] = $freight_total;
                        } else {
                            $return_list[$vid] += $freight_total;
                        }
                    }
                }
            }
        }

        return $return_list;
    }

    /**
     * 取得店铺下商品分类佣金比例
     * @param array $goods_list
     * @return array 店铺ID=>array(分类ID=>佣金比例)
     */
    public function getStoreGcidCommisRateList($goods_list) {

        if (empty($goods_list) || !is_array($goods_list)) return array();

        //定义返回数组
        $store_gc_id_commis_rate = array();

        //取得每个店铺下有哪些商品分类
        //$store_gc_id_list = array();
        foreach ($goods_list as $goods) {
            if (isset($goods['gc_id'])&&!intval($goods['gc_id'])) continue;
            if (isset($store_gc_id_list[$goods['vid']]) && !in_array($goods['gc_id'],(array)$store_gc_id_list[$goods['vid']])) {
                if (in_array($goods['vid'],array(DEFAULT_PLATFORM_STORE_ID))) {
                    //平台店铺佣金为0
                    $store_gc_id_commis_rate[$goods['vid']][$goods['gc_id']] = 0;
                } else {
                    $store_gc_id_list[$goods['vid']][] = $goods['gc_id'];
                }
            }
        }

        if (empty($store_gc_id_list)) return array();

        $model_bind_class = Model('vendor_bind_category');
        $condition = array();
        foreach ($store_gc_id_list as $vid => $gc_id_list) {
            $condition['vid'] = $vid;
            $condition['class_1|class_2|class_3'] = array('in',$gc_id_list);
            $bind_list = $model_bind_class->getStoreBindClassList($condition);

            if (!empty($bind_list) && is_array($bind_list)) {
                foreach ($bind_list as $bind_info) {
                    if ($bind_info['vid'] != $vid) continue;
                    //如果class_1,2,3有一个字段值匹配，就有效
                    $bind_class = array($bind_info['class_3'],$bind_info['class_2'],$bind_info['class_1']);
                    foreach ($gc_id_list as $gc_id) {
                        if (in_array($gc_id,$bind_class)) {
                            $store_gc_id_commis_rate[$vid][$gc_id] = $bind_info['commis_rate'];
                        }
                    }
                }
            }
        }
        return $store_gc_id_commis_rate;

    }

    /**
     * 追加赠品到下单列表,并更新购买数量
     * @param array $store_cart_list 购买列表
     * @param array $store_premiums_list 赠品列表
     * @param array $store_mansong_rule_list 满退送规则
     */
    public function appendPremiumsToCartList($store_cart_list, $store_premiums_list = array(), $store_mansong_rule_list = array(), $member_id) {
        if (empty($store_cart_list)) return array();

        //取得每种商品的库存
        $goods_storage_quantity = $this->_getEachGoodsStorageQuantity($store_cart_list,$store_premiums_list);

        //取得每种商品的购买量
        $goods_buy_quantity = $this->_getEachGoodsBuyQuantity($store_cart_list);

        //本次购买后，余库存为0的，则后面不再送赠品
        $last_storage = array();
        foreach ($goods_buy_quantity as $gid => $quantity) {
            if(isset($goods_storage_quantity[$gid])) {
                $goods_storage_quantity[$gid] -= $quantity;
                if ($goods_storage_quantity[$gid] < 0) {
                    return array('error' => '抱歉，您购买的商品库存不足，请重购买');
                }
            }
        }
        //将赠品追加到购买列表
        if(is_array($store_premiums_list)) {
            foreach ($store_premiums_list as $vid => $goods_list) {
                foreach ($goods_list as $goods_info) {
                    //如果没有库存了，则不再送赠品
                    if (!intval($goods_storage_quantity[$gid])) {
                        $store_mansong_rule_list[$vid]['desc'] .= ' ( 抱歉，库存不足，系统未送赠品 )';
                        continue;
                    }
                    $new_data = array();
                    $new_data['buyer_id'] = $member_id;
                    $new_data['vid'] = $vid;
                    $new_data['store_name'] = $store_cart_list[$vid][0]['store_name'];
                    $new_data['gid'] = $goods_info['gid'];
                    $new_data['goods_name'] = $goods_info['goods_name'];
                    $new_data['goods_num'] = 1;
                    $new_data['goods_price'] = 0;
                    $new_data['goods_image'] = $goods_info['goods_image'];
                    $new_data['bl_id'] = 0;
                    $new_data['state'] = true;
                    $new_data['storage_state'] = true;
                    $new_data['gc_id'] = 0;
                    $new_data['transport_id'] = 0;
                    $new_data['goods_freight'] = 0;
                    $new_data['goods_vat'] = 0;
                    $new_data['goods_total'] = 0;
                    $new_data['ifzengpin'] = true;
                    $store_cart_list[$vid][] = $new_data;
                    $goods_buy_quantity[$goods_info['gid']] += 1;
                }
            }
        }
        return array($store_cart_list,$goods_buy_quantity,$store_mansong_rule_list);
    }

    /**
     * 取得每种商品的库存
     * @param array $store_cart_list 购买列表
     * @param array $store_premiums_list 赠品列表
     * @return array 商品ID=>库存
     */
    private function _getEachGoodsStorageQuantity($store_cart_list, $store_premiums_list = array()) {
        if(empty($store_cart_list) || !is_array($store_cart_list)) return array();
        $goods_storage_quangity = array();
        foreach ($store_cart_list as $store_cart) {
            foreach ($store_cart as $cart_info) {
                if (!intval($cart_info['bl_id'])) {
                    //正常商品
                    $goods_storage_quangity[$cart_info['gid']] = $cart_info['goods_storage'];
                } elseif (!empty($cart_info['bl_goods_list']) && is_array($cart_info['bl_goods_list'])) {
                    //优惠套装
                    foreach ($cart_info['bl_goods_list'] as $goods_info) {
                        if(isset($goods_info["goods_storage"]))
                        $goods_storage_quangity[$goods_info['gid']] = $goods_info['goods_storage'];
                    }
                }
            }
        }
        //取得赠品商品的库存
        if (is_array($store_premiums_list)) {
            foreach ($store_premiums_list as $vid => $goods_list) {
                foreach($goods_list as $goods_info) {
                    if (!isset($goods_storage_quangity[$goods_info['gid']])) {
                        $goods_storage_quangity[$goods_info['gid']] = $goods_info['goods_storage'];
                    }
                }
            }
        }
        return $goods_storage_quangity;
    }

    /**
     * 取得每种商品的购买量
     * @param array $store_cart_list 购买列表
     * @return array 商品ID=>购买数量
     */
    private function _getEachGoodsBuyQuantity($store_cart_list) {
        if(empty($store_cart_list) || !is_array($store_cart_list)) return array();
        $goods_buy_quangity = array();
        foreach ($store_cart_list as $store_cart) {
            foreach ($store_cart as $cart_info) {
                if (!intval($cart_info['bl_id'])) {
                    //正常商品
                    if(!isset($goods_buy_quangity[$cart_info['gid']]))
                        $goods_buy_quangity[$cart_info['gid']]=0;
                    $goods_buy_quangity[$cart_info['gid']] += $cart_info['goods_num'];
                } elseif (!empty($cart_info['bl_goods_list']) && is_array($cart_info['bl_goods_list'])) {
                    //优惠套装
                    foreach ($cart_info['bl_goods_list'] as $goods_info) {
                        if(!isset($goods_buy_quangity[$goods_info['gid']])){
                            $goods_buy_quangity[$goods_info['gid']] =0;
                        }
                        $goods_buy_quangity[$goods_info['gid']] += $cart_info['goods_num'];
                    }
                }
            }
        }
        return $goods_buy_quangity;
    }

    /**
     * 生成订单
     * @param array $input
     * @throws Exception
     * @return array array(支付单sn,订单列表)
     */
    public function createOrder($input, $member_id, $member_name, $member_email,$store_cart_list) {
//        dd($input);die;
        extract($input);
        //平台优惠卷计算总价格
        //$store_final_order_total = 0;
        //购物车列表以店铺ID分组显示
        $allordermoney = array_sum($store_final_order_total);
        //复制一份出来作为计算比例参考
        $store_final_order_total_bak = $store_final_order_total;
        //获取用户绑定的id
        $service_id = getservice($member_id);
        if($service_id>0){
            $service_time = getservice_time($member_id);
        }

        //平台优惠券作废时机是存订单的时候
        if($red || $vred) {
            $redModel = new \app\v1\model\Red();
            $red_re =$redModel-> use_red($member_id, array('red'=>$red,'vred'=>$vred), $store_cart_list,$store_final_order_total);
            if (is_array($red_re) && isset($red_re['error'])) {

            }else{
                $store_cart_list = $red_re[0];
                $store_final_order_total = $red_re[1];
                $vreds = $red_re[2];
            }
        }

        $model_order = new UserOrder();
        //存储生成的订单,函数会返回该数组
        $order_list = array();
        //每个店铺订单是货到付款还是线上支付,店铺ID=>付款方式[在线支付/货到付款]
        if(!empty($store_cart_list))
        $store_pay_type_list    = $this->_getStorePayTypeList_(array_keys($store_cart_list), $if_offpay, $pay_name);

        if(!isset($store_pay_type_list)){
            //throw new Exception('当前店铺不支持货到付款');
        }
        $pay_sn = $this->makePaySn($member_id);
        $order_pay = array();
        $order_pay['pay_sn'] = $pay_sn;
        $order_pay['buyer_id'] = $member_id;
        $order_pay_id = $model_order->addOrderPay($order_pay);
        if (!$order_pay_id) {
            throw new Exception('订单保存失败1');
        }
        //收货人信息
        $reciver_info = array();

        $wqi = 0;//定义一个循环索引
        //因为抵扣需要提前算出所有金额总计
        $wqzong=0;
        foreach ($store_final_order_total as $vid => $solo){
            $wqzong+=$solo;
        }

        //统计需要取出最后一条访客记录id
        $stat = new Stats();
        $last_sv_id = DB::table("bbc_stats_visitor")->where(array('uid'=>$member_id))->order('id desc')->find();
        if(!empty($store_cart_list))
        foreach ($store_cart_list as $vid => $goods_list) {

            //取得本店优惠额度(后面用来计算每件商品实际支付金额，结算需要)
            $promotion_total = !empty($store_promotion_total[$vid]) ? $store_promotion_total[$vid] : 0;

            //本店总的优惠比例,保留3位小数
            $should_goods_total = $store_final_order_total[$vid]-$store_freight_total[$vid]+$promotion_total;
            $promotion_rate = $should_goods_total ? abs($promotion_total/$should_goods_total) : 0;
            if ($promotion_rate <= 1) {
                $promotion_rate = floatval(substr($promotion_rate,0,5));
            } else {
                $promotion_rate = 0;
            }

            //每种商品的优惠金额累加保存入 $promotion_sum
            $promotion_sum = 0;

            $order = array();
            $order_common = array();
            $order_goods = array();

            $order['order_sn'] = $this->makeOrderSn($order_pay_id);
            $order['pay_sn'] = $pay_sn;
            $order['vid'] = $vid;
            $order['store_name'] = $goods_list[0]['store_name'];
            $order['buyer_id'] = $member_id;
            $order['buyer_name'] = $member_name;
            $order['buyer_email'] = $member_email;
            $order['add_time'] = TIMESTAMP;
            $order['payment_code'] = $store_pay_type_list[$vid];
            $order['order_state'] = $store_pay_type_list[$vid] == 'online' ? ORDER_STATE_NEW : ORDER_STATE_PAY;
            $order['order_amount'] = $store_final_order_total[$vid];
            $order['shipping_fee'] = $store_freight_total[$vid]?$store_freight_total[$vid]:0;
            $order['goods_amount'] = $order['order_amount'] - $order['shipping_fee'];
            $order['order_from'] = $order_from;
            $order['ziti'] = 0;
            if(isset($input['course_type']))
            $order['order_course_type'] = $input['course_type'];  //新增课程类型字段

            if(isset($pin_id) && $pin_id>0){
                $order['pin_id'] = $pin_id;
            }

            if(isset($pd_points) && $pd_points>0){
                if( (count($store_final_order_total) == $wqi+1) ){
                    $order['pd_points'] = $pd_points;
                }else{
                    $order['pd_points'] = floor($pd_points/$wqzong*$order['goods_amount']);
                    $pd_points =   $pd_points - $order['pd_points'] ;
                    $wqzong = $wqzong - $order['pd_points'];
                }
                //积分比率存入订单
                if($GLOBALS['setting_config']['points_purpose_rebate']>0){
                    $order['points_ratio'] = $GLOBALS['setting_config']['points_purpose_rebate'];
                }
                //积分与订单价格隔离开
                $order['order_amount'] = $order['order_amount'] - ($order['pd_points']/$GLOBALS['setting_config']['points_purpose_rebate']);
            }
            if($red>0){
                $order['red_id'] = $red;
                //平台红包需要按比率处理,结算时用得到
                $redinfo_money = $model_order->table('red_user,red_info')->join('left')->on('red_user.red_id=red_info.red_id')->where(['red_user.id'=>$red])->field('red_info.redinfo_money')->find()['redinfo_money'];
                $order['red_money'] = round(($store_final_order_total_bak[$vid]/$allordermoney)*$redinfo_money,2);
            }
            if(isset($vreds[$vid])&&$vreds[$vid]){
                $order['vred_id'] = $vreds[$vid];
            }

            if($service_id){
                //接入的客服id
                $order['service_id'] = $service_id;
                //接入客服的时间
                $order['service_time'] = $service_time;
            }

            //如果支付金额为零，直接变成已支付
            if($order['order_amount']==0){
                $order['order_state'] = ORDER_STATE_SUCCESS;
            }

            $order_id = $model_order->addOrder($order);

            if (!$order_id) {
                throw new Exception('订单保存失败2');
            }else{
                //插入统计信息
                Model('stats')->table('bbc_stats_relation')->insert(array('type'=>'order','sv_id'=>$last_sv_id["id"],'re_id'=>$order_id));
            }

            if(isset($order['pd_points']) && $order['pd_points']>0) {
                //扣除抵扣积分
                Model('points')->savePointsLog('purpose', array('pl_memberid' => $member_id, 'pl_membername' => $member_name, 'orderprice' => $order['goods_amount'], 'order_sn' => $order['order_sn'], 'order_id' => $order_id, 'pl_points' => -1 * $order['pd_points']), true);
            }

            $order['order_id'] = $order_id;
            $order_list[$order_id] = $order;

            $order_common['order_id'] = $order_id;
            $order_common['vid'] = $vid;
            if(isset($pay_message[$vid]))
            $order_common['order_message'] = $pay_message[$vid];


            $order_common['reciver_info']= '';
            $order_common['reciver_name'] = '';
            //会员等级折扣信息存入common表
            if(isset($goods_list[0]['grade_discount']))
            $order_common['grade_discount'] = $goods_list[0]['grade_discount'];
            //发票信息
            if(isset($invoice_info))
            $order_common['invoice_info'] = $this->_createInvoiceData($invoice_info);

            //保存促销信息
            if(isset($store_mansong_rule_list[$vid]) &&is_array($store_mansong_rule_list[$vid])) {
                $order_common['promotion_info'] = addslashes($store_mansong_rule_list[$vid]['desc']);
            }


            //取得省ID
            $order_common['reciver_province_id'] = 0;

            $order_id = $model_order->addOrderCommon($order_common);


            if (!$order_id) {
                throw new Exception('订单保存失败');
            }

            //生成order_goods订单商品数据
            // 获取最终价格
//         $goods_list = Model('goods_activity')->rebuild_goods_data($goods_list,'pc');
            $i = 0;
            foreach ($goods_list as $goods_info) {

                //根据goods_commonid获取goods_storage_alarm   goods_common表就可以
                // 提醒[库存报警]  库存预警值必须大于0  为0的话不报警
                if(isset($order['dian_id'])&&$order['dian_id']>0) {
                    $storeGoods = new StoreGoods();
                    if(isset($order['dian_id'])) {
                        $goods_common_new = $storeGoods->getGoodsInfo(array('dian_id' => $order['dian_id'], 'goods_id' => $goods_info['gid']), 'stock');
                        if ($goods_common_new['delete'] == 1 || $goods_common_new['stock'] < 1) {
                            throw new Exception('部分商品已经下架或库存不足，请重新选择');
                        }
                        if (10 >= ($goods_info['goods_storage'] - $goods_info['goods_num']) && $goods_common_new['goods_storage'] > 0) {
                            $param = array();
                            $param['goods_name'] = $goods_info['goods_name'];
                            $param['gid'] = $goods_info['gid'];
                            QueueClient::push('sendDianMsg', array('code' => 'dian_goods_storage_alarm', 'vid' => $goods_info['vid'], 'param' => $param));
                        }
                    }
                }else{
                    if (!$goods_info['state'] || !$goods_info['storage_state']) {
                        //throw new Exception('部分商品已经下架或库存不足，请重新选择');
                    }
                    $goodsModel =new Goods();
                    $goods_common_new = $goodsModel->getGoodsCommonInfo(array('goods_commonid'=>$goods_info['goods_commonid']),'goods_storage_alarm');
                    if ($goods_info['has_spec']) {
                        $spec_num_arr = unserialize($goods_info['spec_num']);
                        // 多规格批发商品
                        // 按照各个规格对应的商品进行校验 预警数字 按照每个规格的预警值进行比较 有一个预警则跳出循环 进行预警
                        $run_sku_flag = false;
                        if (is_array($goods_info['spec_data']) && !empty($goods_info['spec_data']) && is_array($spec_num_arr) ) {
                            foreach ($spec_num_arr as $key => $value) {
                                if ($goods_info['spec_data'][$key]['storage_alarm'] >= ($goods_info['spec_data'][$key]['storage'] - $value) && $goods_info['spec_data'][$key]['storage_alarm'] > 0) {
                                    $sku_gid = $goods_info['spec_data'][$key]['gid'];
                                    $run_sku_flag = true;
                                    break;
                                }
                            }
                        }
                        if ($run_sku_flag) {
                            $param = array();
                            $param['common_id'] = $goods_info['goods_commonid'];
                            $param['sku_id'] = $sku_gid;
                            QueueClient::push('sendStoreMsg', array('code' => 'goods_storage_alarm', 'vid' => $goods_info['vid'], 'param' => $param));
                        }

                    }else{
                        if(isset($goods_info['goods_storage']))
                        if ( empty($goods_info['goods_storage']) &&$goods_common_new['goods_storage_alarm'] >= ($goods_info['goods_storage'] - $goods_info['goods_num']) && $goods_common_new['goods_storage_alarm'] > 0) {
                            $param = array();
                            $param['common_id'] = $goods_info['goods_commonid'];
                            $param['sku_id'] = $goods_info['gid'];
                            QueueClient::push('sendStoreMsg', array('code' => 'goods_storage_alarm', 'vid' => $goods_info['vid'], 'param' => $param));
                        }
                    }
                }
                //查询老师id
                $model_goodscommon = "";//Model('bbc_goods_common');
                $g_common = DB::table('bbc_goods_common')->field('teacher')->where(['goods_commonid'=>$goods_info['goods_commonid']])->find();

                if (!intval($goods_info['bl_id'])) {
                    //如果不是优惠套装
                    $order_goods[$i]['order_id'] = $order_id;
                    $order_goods[$i]['teacher'] = $g_common['teacher'];//添加老师id
                    $order_goods[$i]['gid'] = $goods_info['gid'];
                    $order_goods[$i]['vid'] = $vid;
                    if(Config('distribution') && !(Config("sld_spreader") && Config("spreader_isuse"))){
                        $order_goods[$i]['goods_yongjin'] = $goods_info['fenxiao_yongjin'] * $goods_info['goods_num'];
                    }
                    $order_goods[$i]['goods_name'] = $goods_info['goods_name'];
                    //$order_goods[$i]['goods_price'] = $goods_info['show_price'] ? $goods_info['show_price'] : $goods_info['goods_price'];
                    $order_goods[$i]['goods_price'] = $goods_info['goods_price'];
                    $order_goods[$i]['goods_num'] = $goods_info['goods_num'];
                    $order_goods[$i]['goods_image'] = $goods_info['goods_image'];
                    $order_goods[$i]['buyer_id'] = $member_id;
                    if ($goods_info['goods_type']) {
                        $order_goods[$i]['goods_type'] = 6;
                        $order_goods[$i]['spec_num'] = $goods_info['spec_num'];
                        $order_goods[$i]['has_spec'] = $goods_info['has_spec'];
                    }else{
                        if (isset($goods_info['iftuan'])&&$goods_info['iftuan']) {
                            $order_goods[$i]['goods_type'] = 2;
                        }elseif (isset($goods_info['ifxianshi'])&&$goods_info['ifxianshi']) {
                            $order_goods[$i]['goods_type'] = 3;
                        }elseif (isset($goods_info['ifzengpin'])&&$goods_info['ifzengpin']) {
                            $order_goods[$i]['goods_type'] = 5;
                        }elseif (isset($goods_info['promotion_type']) && isset($goods_info['start_time']) && $goods_info['start_time'] && isset($goods_info['end_time']) && $goods_info['end_time'] && $goods_info['promotion_type'] == 'pin_tuan' && time() > $goods_info['start_time'] && time() < $goods_info['end_time'] ){
                            $order_goods[$i]['goods_type'] = 7;
                        }elseif (isset($goods_info['promotion_type']) && empty($goods_info['end_time'])){
                            $order_goods[$i]['goods_type'] = 8;
                        }else {
                            $order_goods[$i]['goods_type'] = 1;
                        }
                    }
                    if(isset($goods_info['promotions_id']))
                    $order_goods[$i]['promotions_id'] = $goods_info['promotions_id'] ? $goods_info['promotions_id'] : 0;
                    else
                        $order_goods[$i]['promotions_id'] = 0;
                    if(isset($store_gc_id_commis_rate_list[$vid][$goods_info['gc_id']]))
                    $order_goods[$i]['commis_rate'] = floatval($store_gc_id_commis_rate_list[$vid][$goods_info['gc_id']]);
                    //计算商品金额
                    if(isset($goods_info['show_price']))
                    $goods_total = ($goods_info['show_price']?:$goods_info['goods_price']) * $goods_info['goods_num'];
                    else
                        $goods_total = ($goods_info['goods_price']) * $goods_info['goods_num'];
                    //计算本件商品优惠金额
                    $promotion_value = floor($goods_total*($promotion_rate));
                    $order_goods[$i]['goods_pay_price'] = $goods_total - $promotion_value;



                    //课程有效期
                    if($goods_info['course_type']==2){

                        if($goods_info['is_free']){
                            $validity = 9999;
                        }else{
                            //获取规格
                            $spec = $goods_info['goods_spec'];
                            $spec = unserialize($spec);
                            foreach ($spec as $v){
                                $validity = $v;
                            }
                        }
                        $order_goods[$i]['validity'] = TIMESTAMP + $validity*86400;
                    }else{
                        $order_goods[$i]['validity'] = 0;
                    }

                    $order_goods[$i]['course_type'] = $goods_info['course_type'];


                    if(isset($goods_info['first']) && $goods_info['first']) {
                        //首单满减 商品支付金额
                        $order_goods[$i]['goods_pay_price'] -= $goods_info['first'];
                        $order_goods[$i]['first'] = $goods_info['first'];
                    }else{
                        $order_goods[$i]['first'] = 0;
                    }

                    $promotion_sum += $promotion_value;
                    $i++;

                } elseif (!empty($goods_info['bl_goods_list']) && is_array($goods_info['bl_goods_list'])) {

                    //优惠套装
                    foreach ($goods_info['bl_goods_list'] as $bl_goods_info) {
                        $order_goods[$i]['order_id'] = $order_id;
                        $order_goods[$i]['teacher'] = $g_common['teacher'];//添加老师id;
                        $order_goods[$i]['gid'] = $bl_goods_info['gid'];
                        if(isset($bl_goods_info['fenxiao_yongjin']))
                        $order_goods[$i]['goods_yongjin'] =$bl_goods_info['fenxiao_yongjin'];
                        $order_goods[$i]['vid'] = $vid;
                        $order_goods[$i]['goods_name'] = $bl_goods_info['goods_name'];
                        $order_goods[$i]['goods_price'] = $bl_goods_info['bl_goods_price'];
                        $order_goods[$i]['goods_num'] = $goods_info['goods_num'];
                        $order_goods[$i]['goods_image'] = $bl_goods_info['goods_image'];
                        $order_goods[$i]['buyer_id'] = $member_id;
                        $order_goods[$i]['goods_type'] = 4;
                        $order_goods[$i]['promotions_id'] = $bl_goods_info['bl_id'];
                        if(isset($goods_info['gc_id']))
                        $order_goods[$i]['commis_rate'] = floatval($store_gc_id_commis_rate_list[$vid][$goods_info['gc_id']]);

                        //计算商品实际支付金额(goods_price减去分摊优惠金额后的值)
                        $goods_total = $bl_goods_info['bl_goods_price'] * $goods_info['goods_num'];
                        //计算本件商品优惠金额
                        $promotion_value = floor($goods_total*($promotion_rate));
                        $order_goods[$i]['goods_pay_price'] = $goods_total - $promotion_value;
                        $promotion_sum += $promotion_value;
                        if($goods_info['first']) {
                            //首单满减 商品支付金额
                            $order_goods[$i]['goods_pay_price'] -= $goods_info['first'];
                            $order_goods[$i]['first'] = $goods_info['first'];
                        }else{
                            $order_goods[$i]['first'] = 0;
                        }
                        $i++;
                    }
                }
            }

            //将因舍出小数部分出现的差值补到最后一个商品的实际成交价中(商品goods_price=0时不给补，可能是赠品)
            if ($promotion_total > $promotion_sum) {
                $i--;
                for($i;$i>=0;$i--) {
                    if (floatval($order_goods[$i]['goods_price']) > 0) {
                        $order_goods[$i]['goods_pay_price'] -= $promotion_total - $promotion_sum;
                        break;
                    }
                }
            }
            //牵扯到退款退货积分带来的影响
            //在这里详细处理单个商品的实际支付金额
            if(isset($order['pd_points']) && $order['pd_points']>0){
                $pd_points = $order['pd_points'] / $GLOBALS['setting_config']['points_purpose_rebate'] ;
                $goodspayprice = low_array_column($order_goods,'goods_pay_price','gid');
                $aa = $this->Calculation($pd_points,$goodspayprice);
                array_walk($order_goods,function(&$v)use($aa){
                    if(isset($aa[$v['gid']]) && $aa[$v['gid']]>0){
                        $v['goods_pay_price'] = $v['goods_pay_price']-$aa[$v['gid']];
                    }
                });
            }
            foreach($order_goods as $k=>$v){
                $insert = $model_order->addOrderGoods($v);
            }
            //        dd($order_goods);
            if (!$insert) {
                print_r($insert);
            }else{
                $wqi++;
                if($goods_info['first']){
                    //添加首单满减记录
                    M('first','firstDiscount')->table('first_discount_log')->insert([
                        'member_id'=>$member_id,
                        'cid'=>$goods_info['goods_commonid'],
                        'order_id'=>$order_id,
                        'add_time'=>TIMESTAMP,
                        'vid'=>$goods_info['vid']
                    ]);
                }
            }
        }

//        exit('调试中');


        return array($pay_sn,$order_list);
    }

    /*
     * 比率计算积分
     */
    public function Calculation($a,$b)
    {
        $sum = array_sum($b);
        $count = count($b);
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
        return $c;
    }

    /**
     * 记录订单日志
     * @param array $order_list
     */
    public function addOrderLog($order_list = array()) {
        if (empty($order_list) || !is_array($order_list)) return;
        $model_order = new UserOrder();
        foreach ($order_list as $order_id => $order) {
            $data = array();
            $data['order_id'] = $order_id;
            $data['log_role'] = 'buyer';
            $data['log_msg'] = '提交了订单';
            $data['log_orderstate'] = $order['payment_code'] == 'offline' ? ORDER_STATE_PAY : ORDER_STATE_NEW;
            $model_order->addOrderLog($data);
        }
    }

    /**
     * 店铺购买列表 王强标记更新库存（减库存）
     * @param array $goods_buy_quantity 商品ID与购买数量数组
     * @param  int  $dian_id   门店的id  建对应门店的库存
     * @param  boolean  $is_cancel   是否是取消订单操作 （反向操作）
     * @throws Exception
     */
    public function updateGoodsStorageNum($goods_buy_quantity,$dian_id,$is_cancel=false) {
        if (empty($goods_buy_quantity) || !is_array($goods_buy_quantity)) return;
        if($dian_id){
            $model_goods = new StoreGoods();
            foreach ($goods_buy_quantity as $gid => $quantity) {
                $data = array();
                if ($is_cancel) {
                    $data['stock'] = array('exp', 'stock+' . $quantity);
                    $data['sales'] = array('exp', 'sales-' . $quantity);
                }else{
                    $data['stock'] = array('exp', 'stock-' . $quantity);
                    $data['sales'] = array('exp', 'sales+' . $quantity);
                }
                $result = $model_goods->editGoods($data, array('goods_id' => $gid,'dian_id'=>$dian_id));
                if (!$result) {};//throw new Exception('更新库存失败');
            }
        }else {
            $model_goods = new Goods();
            foreach ($goods_buy_quantity as $gid => $quantity) {
                $data = array();
                if ($is_cancel) {
                    $data['goods_storage'] = array('inc', 'goods_storage+' . $quantity);
                    $data['goods_salenum'] = array('dec', 'goods_salenum-' . $quantity);
                }else{
                    $data['goods_storage'] = array('dec', 'goods_storage-' . $quantity);
                    $data['goods_salenum'] = array('inc', 'goods_salenum+' . $quantity);
                }
                $result = $model_goods->editGoods($data, array('gid' => $gid));
                if (!$result){};// throw new Exception('更新库存失败');
            }
        }
    }

    /**
     * 更新使用的优惠券状态
     * @param $input_voucher_list
     * @throws Exception
     */
    public function updateVoucher($voucher_list) {
        if (empty($voucher_list) || !is_array($voucher_list)) return;
        $model_voucher = Model('quan');
        foreach ($voucher_list as $vid => $voucher_info) {
            $update = $model_voucher->editVoucher(array('voucher_state'=>2),array('voucher_id'=>$voucher_info['voucher_id']));
            if (!$update) throw new Exception('优惠券更新失败');
        }
    }

    /**
     * 更新团购信息
     * @param unknown $tuan_info
     * @throws Exception
     */
    public function updateTuan($tuan_info) {
        if (empty($tuan_info) || !is_array($tuan_info)) return;
        $tuan_model = Model('tuan');
        $data = array();
        $data['buyer_count'] = array('exp','buyer_count+1');
        $data['buy_quantity'] = array('exp','buy_quantity+'.$tuan_info['quantity']);
        $update = $tuan_model->editTuan($data,array('tuan_id'=>$tuan_info['tuan_id']));
        if (!$update) throw new Exception('团购信息更新失败');
    }

    /**
     * 预存款支付,依次循环每个订单
     * 如果预存款足够就单独支付了该订单，如果不足就暂时冻结，等API支付成功了再彻底扣除
     */
    public function pdPay($order_list, $input, $member_id, $member_name) {
        if (empty($input['pd_pay']) || empty($input['password'])) return 3;

        $model_payment = Model('payment');
        $pd_payment_info = $model_payment->getPaymentOpenInfo(array('payment_code'=>'predeposit'));
        if (empty($pd_payment_info)) return 4;

        $buyer_info = Model('member')->infoMember(array('member_id' => $member_id));

        if ($buyer_info['member_passwd'] != md5($input['password'])) return 1;
        $available_pd_amount = floatval($buyer_info['available_predeposit']);
        if ($available_pd_amount <= 0) return 2;

        $model_order = Model('order');
        $model_pd = Model('predeposit');
        foreach ($order_list as $order_info) {

            //货到付款的订单跳过
            if ($order_info['payment_code'] == 'offline') continue;

            $order_amount = floatval($order_info['order_amount']);


            $data_pd = array();
            $data_pd['member_id'] = $member_id;
            $data_pd['member_name'] = $member_name;
            $data_pd['amount'] = $order_amount;
            $data_pd['order_sn'] = $order_info['order_sn'];
//            dd($order_info);die;
            if ($available_pd_amount >= $order_amount) {
                //预存款立即支付，订单支付完成
                $model_pd->changePd('order_pay',$data_pd);

                //记录订单日志(已付款)
                $data = array();
                $data['order_id'] = $order_info['order_id'];
                $data['log_role'] = 'buyer';
                $data['log_msg'] = L('完成了付款');
                if($order_info['order_course_type'] == 1){
                    $data['log_orderstate'] = ORDER_STATE_SEND;
                }else{
                    $data['log_orderstate'] = ORDER_STATE_SUCCESS;
                }

                $insert = $model_order->addOrderLog($data);
                if (!$insert) {
                    throw new Exception('记录订单日志出现错误');
                }

                //订单状态 置为已支付
                $data_order = array();
                if($order_info['order_course_type'] == 1){
                    $data_order['order_state'] = ORDER_STATE_SEND;  //修改订单状态为已发货待确认的状态
                }else{
                    $data_order['order_state'] = ORDER_STATE_SUCCESS;
                }


                $data_order['payment_time'] = TIMESTAMP;
                $data_order['finnshed_time'] = TIMESTAMP;
                $data_order['payment_code'] = 'predeposit';
                $data_order['pd_amount'] = $order_info['order_amount'];
                $result = $model_order->editOrder($data_order,array('order_id'=>$order_info['order_id']));
                if (!$result) {

                    throw new Exception('订单更新失败');
                }

                //支付成功拼团处理
                if($order_info['pin_id']) {
                    $wqre=M('pin')->paidPin($order_info);
                    if(!$wqre['succ']){
                        throw new Exception($wqre['msg']);
                    }
                }

                // 发送商家提醒
                $param = array();
                $param['code'] = 'new_order';
                $param['vid'] = $order_info['vid'];
                $param['param'] = array(
                    'order_sn' => $order_info['order_sn']
                );
                QueueClient::push('sendStoreMsg', $param);

                //发送门店提醒
                if($order_info['dian_id']>0) {
                    $param = array();
                    $param['code'] = 'dian_new_order';
                    $param['vid'] = $order_info['dian_id'];
                    $param['param'] = array(
                        'order_sn' => $order_info['order_sn']
                    );
                    QueueClient::push('sendDianMsg', $param);
                }

                // 支付成功发送买家消息
                $param = array();
                $param['code'] = 'order_payment_success';
                $param['member_id'] = $order_info['buyer_id'];
                $param['param'] = array(
                    'order_sn' => $order_info['order_sn'],
                    'order_url' => urlShop('userorder', 'show_order', array('order_id' => $order_info['order_id'])),

                    'first' => '您有一笔订单支付成功',
                    'keyword1' => $order_info['order_sn'],
                    'keyword2' => date('Y年m月d日 H时i分',time()),
                    'keyword3' => sldPriceFormat($order_amount),
                    'remark' => '如有问题，请联系我们',

                    'url' => WAP_SITE_URL.'/cwap_order_detail.html?order_id='.$order_info['order_id']
                );
                $param['system_type']=2;
                QueueClient::push('sendMemberMsg', $param);
                //极光推送商户——新订单提醒
                //获取seller_name
//                $seller_info_jpush = Model('seller')->getSellerInfo(array('vid'=>$order_info['vid']));
//                $jpush = Logic('sld_jpush');
//                if($jpush->sld_check_jpush_isopen() ){
//                    $jpush->send_special_detail_url('您有一笔新订单，请及时查看',['type'=>'pay_success','order_id'=>$order_info['order_id'],'order_sn'=>$order_info['order_sn']],[$seller_info_jpush['seller_name']]);
//                }

            } else {
                //暂冻结预存款,后面还需要 API彻底完成支付
                if ($available_pd_amount > 0) {
                    $data_pd['amount'] = $available_pd_amount;
                    $model_pd->changePd('order_freeze',$data_pd);
                    //预存款支付金额保存到订单
                    $data_order = array();
                    $data_order['pd_amount'] = $available_pd_amount;
                    $result = $model_order->editOrder($data_order,array('order_id'=>$order_info['order_id']));
                    $available_pd_amount = 0;
                    if (!$result) {
                        throw new Exception('订单更新失败');
                    }
                }
            }
        }
        return 6;
    }

    /**
     * 整理发票信息
     * @param array $invoice_info 发票信息数组
     * @return string
     */
    private function _createInvoiceData($invoice_info){
        //发票信息
        $inv = array();
        if ($invoice_info['inv_state'] == 1) {
            $inv['类型'] = '普通发票 ';
            $inv['抬头'] = $invoice_info['inv_title_select'] == 'person' ? Language::get('个人') : $invoice_info['inv_title'];
            $inv['内容'] = $invoice_info['inv_content'];
        } elseif (!empty($invoice_info)) {
            $inv['单位名称'] = $invoice_info['inv_company'];
            $inv['纳税人识别号'] = $invoice_info['inv_code'];
            $inv['注册地址'] = $invoice_info['inv_reg_addr'];
            $inv['注册电话'] = $invoice_info['inv_reg_phone'];
            $inv['开户银行'] = $invoice_info['inv_reg_bname'];
            $inv['银行帐户'] = $invoice_info['inv_reg_baccount'];
            $inv['收票人姓名'] = $invoice_info['inv_rec_name'];
            $inv['收票人手机号'] = $invoice_info['inv_rec_mobphone'];
            $inv['收票人省份'] = $invoice_info['inv_rec_province'];
            $inv['送票地址'] = $invoice_info['inv_goto_addr'];
        }
        return !empty($inv) ? serialize($inv) : serialize(array());
    }

    /**
     * 计算本次下单中每个店铺订单是货到付款还是线上支付,店铺ID=>付款方式[online在线支付offline货到付款]
     * @param array $store_id_array 店铺ID数组
     * @param boolean $if_offpay 是否支持货到付款 true/false
     * @param string $pay_name 付款方式 online/offline
     * @return array
     */
    private function _getStorePayTypeList($store_id_array, $if_offpay, $pay_name) {
        $store_pay_type_list = array();
        if ($pay_name == 'online') {
            foreach ($store_id_array as $vid) {
                $store_pay_type_list[$vid] = 'online';
            }
        } else {
            $offline_pay = Model('payment')->getPaymentOpenInfo(array('payment_code'=>'offline'));
            if ($offline_pay) {
                //下单里包括平台自营商品并且平台已开启货到付款
                $offline_store_id_array = array(DEFAULT_PLATFORM_STORE_ID);
                foreach ($store_id_array as $vid) {
                    if (in_array($vid,$offline_store_id_array)) {
                        $store_pay_type_list[$vid] = 'offline';
                    } else {
                        $store_pay_type_list[$vid] = 'online';
                    }
                }
            }
        }
        return $store_pay_type_list;
    }
    /** _getStorePayTypeList更新方法,只供生成订单方法调用
     * 计算本次下单中每个店铺订单是货到付款还是线上支付,店铺ID=>付款方式[online在线支付offline货到付款],如果出现不支持货到付款的店铺但是选择了货到付款,则直接返回false
     * @param array $store_id_array 店铺ID数组
     * @param boolean $if_offpay 是否支持货到付款 true/false
     * @param string $pay_name 付款方式 online/offline
     * @return array
     */
    private function _getStorePayTypeList_($store_id_array, $if_offpay, $pay_name) {
        $store_pay_type_list = array();
        if ($pay_name == 'online') {
            foreach ($store_id_array as $vid) {
                $store_pay_type_list[$vid] = 'online';
            }
        } else {
            $offline_pay = C('cash_on_delivery');
            if(!$offline_pay){
                return false;
            }
            $Avid = model()->table('vendor')->where(['vid'=>['in',implode(',',$store_id_array)]])->key('vid')->field('vid,cash_on_delivery')->select();
            foreach ($store_id_array as $vid) {
                if ($Avid[$vid]['cash_on_delivery'] == 1) {
                    $store_pay_type_list[$vid] = 'offline';
                } else {
                    return false;
//                        $store_pay_type_list[$vid] = 'online';
                }
            }
        }
        return $store_pay_type_list;
    }
    /**
     * 生成支付单编号(两位随机 + 从2000-01-01 00:00:00 到现在的秒数+微秒+会员ID%1000)，该值会传给第三方支付接口
     * 长度 =2位 + 10位 + 3位 + 3位  = 18位
     * 1000个会员同一微秒提订单，重复机率为1/100
     * @return string
     */
    public function makePaySn($member_id) {
        return mt_rand(10,99)
            . sprintf('%010d',time() - 946656000)
            . sprintf('%03d', (float) microtime() * 1000)
            . sprintf('%03d', (int) $member_id % 1000);
    }

    /**
     * 订单编号生成规则，n(n>=1)个订单表对应一个支付表，
     * 生成订单编号(年取1位 + $pay_id取13位 + 第N个子订单取2位)
     * 1000个会员同一微秒提订单，重复机率为1/100
     * @param $pay_id 支付表自增ID
     * @return string
     */
    public function makeOrderSn($pay_id) {
        //记录生成子订单的个数，如果生成多个子订单，该值会累加
        static $num;
        if (empty($num)) {
            $num = 1;
        } else {
            $num ++;
        }
        return (date('y',time()) % 9+1) . sprintf('%013d', $pay_id) . sprintf('%02d', $num);
    }

    /**
     * 更新库存与销量 王强标记更新库存（该方法没被使用）
     *
     * @param array $buy_items 商品ID => 购买数量
     */
    public function editGoodsNum($buy_items) {
        $model = Model()->table('goods');
        foreach ($buy_items as $gid => $buy_num) {
            $data = array('goods_storage'=>array('exp','goods_storage-'.$buy_num),'goods_salenum'=>array('exp','goods_salenum+'.$buy_num));
            $result = $model->where(array('gid'=>$gid))->update($data);
            if (!$result) throw new Exception(L('系统异常,生成订单失败'));
        }
    }

    /**
     * 购买第一步
     *
     * @param array $cart_id 购物车
     * @param int $ifcart 是否为购物车
     * @param int $invalid_cart
     * @param int $member_id 会员编号
     * @param int $vid 店铺编号
     * @param int $is_supplier 是否时批发中心购物车
     * @param array $extends_data 扩展数据
     */
    public function buyStep1($cart_id, $ifcart, $invalid_cart, $member_id, $vid, $is_supplier=0,$extends_data=array(),$again=0) {
        $model_cart = new UserCart();

        $result = array();
        $store =new Store();
        $goodsModel = new Goods();
        // 是否允许使用优惠券
        $is_allow_show_red = true;
        //取得POST ID和购买数量
        $buy_items = $this->_parseItems($cart_id);
        if (count($buy_items) > 50) {
            return array('error' => '一次最多只可购买50种商品');
        }

        if ($ifcart) {

            //来源于购物车
            if(!$again){
                //取购物车列表
                $ids = "";
                foreach (array_keys($buy_items) as $k=>$v){
                    $ids .=$v.",";
                    trim($ids);
                }
                $ids =substr($ids,0,strlen($ids) -1 );
                //$condition = array('cart_id'=>array('in',$ids), 'buyer_id'=>$member_id);
                $condition = "cart_id in (".$ids.") and buyer_id = ".$member_id;
                $cart_list  = $model_cart->listCart('db', $condition);
            }else{
                //组装购物车数据
                $cart_list = [];
                foreach($cart_id as $k=>$v){
                    $goods_info = $model_cart->table('bbc_goods')->where(['gid'=>$v['gid']])->find();
                    if($v['error'] == 0){
                        $cart_list[$k]['cart_id'] = $goods_info['gid'];
                        $cart_list[$k]['buyer_id'] = $member_id;
                        $cart_list[$k]['vid'] = $goods_info['vid'];
                        $cart_list[$k]['store_name'] = $goods_info['store_name'];
                        $cart_list[$k]['gid'] = $goods_info['gid'];
                        $cart_list[$k]['goods_name'] = $goods_info['goods_name'];
                        $cart_list[$k]['goods_price'] = $goods_info['goods_price'];
                        $cart_list[$k]['goods_num'] = $v['num'];
                        $cart_list[$k]['goods_image'] = $goods_info['goods_image'];
                        $cart_list[$k]['bl_id'] = 0;
                        $cart_list[$k]['has_spec'] = 0;
                        $cart_list[$k]['spec_num'] = '';
                        $cart_list[$k]['sld_is_supplier'] = 0;
                    }
                }
                $cart_list = array_values($cart_list);
            }



            //取商品最新的在售信息
            $cart_list = $model_cart->getOnlineCartList($cart_list);
            if (!$is_supplier) {

                //得到团购信息_zhangjinfeng
                $cart_list = $model_cart->getTuanCartList($cart_list);

                //得到限时折扣信息
                $cart_list = $model_cart->getXianshiCartList($cart_list);

                //得到今日抢购信息_zhangjinfeng
                $cart_list = $model_cart->getTobuyCartList($cart_list);

                //得到优惠套装状态,并取得组合套装商品列表
                $cart_list = $model_cart->getBundlingCartList($cart_list);

                $cart_list = $model_cart->getZhuanxiangCartList($cart_list);

            }else{
                $new_cart_list = array();
                foreach ($cart_list as $key => $cart_item) {
                    if ($cart_item['has_spec']) {
                        // 有规格 (获取规格信息)
                        $goodsModel = new Goods();
                        $spec_array = $goodsModel->getGoodsList(array('goods_commonid' => $cart_item['goods_commonid']), 'goods_spec,gid,vid,goods_image,color_id,goods_storage');
                        $spec_list = array();       // 各规格商品地址，js使用
                        foreach ($spec_array as $s_key => $value) {
                            $s_array = unserialize($value['goods_spec']);

                            $tmp_array = array();
                            if (!empty($s_array) && is_array($s_array)) {
                                foreach ($s_array as $k => $v) {
                                    $tmp_array[] = $k;
                                }
                            }
                            sort($tmp_array);
                            $spec_sign = implode('|', $tmp_array);

                            $spec_list[$spec_sign]['storage'] = $value['goods_storage'];
                            $spec_list[$spec_sign]['field_name'] = implode('/', $s_array);
                        }
                        $cart_item['spec_data'] = $spec_list;
                        $cart_item['spec_num_arr'] = unserialize($cart_item['spec_num']);
                    }else{
                        $cart_item['spec_data'] = array();
                        $cart_item['spec_num_arr'] = array();
                    }
                    $cart_list[$key] = $cart_item;
                }
            }
            //到得商品列表
            $goods_list = $model_cart->getGoodsList($cart_list);


            //购物车列表以店铺ID分组显示
            $store_cart_list = $model_cart->getStoreCartList($cart_list);
            //print_r($store_cart_list);die;
            //根据产品列表
            $gids = array();
            if(count($store_cart_list)>1){
                $result['dian_list'] = array();
                $result['gid'] = 0;
                $gids[]=$v['gid'];
            }else{
                //单店铺商品
                foreach ($goods_list as $k=>$v){
                    $gids[]=$v['gid'];
                }
            }
            //取得门店列表
            $condition5['dian.delivery_type'] =array('like','%shangmen%');
            //联到家判断,去掉关掉的门店
            $condition5['dian.status'] = ['exp',' dian.cash_status=1 or  dian.ldj_status=1 '];

            $dian_list = $store->getDianCountByGid($gids,null,$condition5);
            if($dian_list) {
                $result['dian_list'] = $dian_list;
                $result['gid'] = join(',', $gids);
            }

            //标识来源于购物车
            $result['ifcart'] = 1;

        }
        else {

            //来源于直接购买 拼团只能是直接购买

            if (isset($extends_data['is_bundling'])) {
                // 优惠套装 数据生成
                // 根据套装ID 获取 优惠套装 商品数据
                $p_bundling_condition['bl_id'] = $extends_data['bl_id'];
                $pbModel = new Pbundling();
                $p_bundling_goods_data = $pbModel->getBundlingGoodsList($p_bundling_condition);

                $cart_list = array();

                foreach ($p_bundling_goods_data as $pb_key => $pb_value) {
                    $pd_condition['gid'] = $pb_value['gid'];
                    $goodsModel = new Goods();
                    $goods_item_info = $goodsModel->getGoodsOnlineInfo($pd_condition);
                    if (!empty($goods_item_info)) {
                        $cart['gid'] = $pb_value['gid'];
                        $cart['vid'] = $goods_item_info['vid'];
                        $cart['gc_id'] = $goods_item_info['gc_id'];
                        $cart['goods_name'] = $pb_value['goods_name'];
                        $cart['goods_price'] = $pb_value['bl_goods_price'];
                        $cart['store_name'] = $goods_item_info['store_name'];
                        $cart['goods_image'] = $pb_value['goods_image'];
                        $cart['transport_id'] = $goods_item_info['transport_id'];
                        $cart['goods_freight'] = $goods_item_info['goods_freight'];
                        $cart['goods_vat'] = $goods_item_info['goods_vat'];
                        $cart['bl_id'] = $pb_value['bl_id'];
                        $cart['goods_num'] = 1;

                        $cart_list[$pb_key] = $cart;
                    }
                }

                //得到优惠套装状态,并取得组合套装商品列表
                $cart_list = $model_cart->getBundlingCartList($cart_list);

                //到得商品列表
                $goods_list = $model_cart->getGoodsList($cart_list);


                //购物车列表以店铺ID分组显示
                $store_cart_list = $model_cart->getStoreCartList($cart_list);

                //根据产品列表
                if(count($store_cart_list)>1){
                    $result['dian_list'] = array();
                    $result['gid'] = 0;
                }else{
                    //单店铺商品
                    foreach ($goods_list as $k=>$v){
                        $gids[]=$v['gid'];
                    }
                }
                //取得门店列表
                $condition5['dian.delivery_type'] =array('like','%shangmen%');
                //联到家判断,去掉关掉的门店

                $condition5['dian.status'] = ['exp',' dian.cash_status=1 or  dian.ldj_status=1 '];
                $dian_list=$store->getDianCountByGid($gids,null,$condition5);
                if($dian_list) {
                    $result['dian_list'] = $dian_list;
                    $result['gid'] = join(',', $gids);
                }

            }
            elseif(isset($extends_data['suite'])){
                $suite_checked_goods = $extends_data['suite_checked'];

                // 要购买的商品ID 及 商品数量
                $goods_buy_num = array();
                $goods_tmp_arr = explode(',',$suite_checked_goods);
                foreach ($goods_tmp_arr as $key => $value) {
                    $goods_item_data = explode('|', $value);
                    $goods_buy_num[$key]['gid'] = $goods_item_data[0];
                    $goods_buy_num[$key]['goods_num'] = $goods_item_data[1];
                }

                // 获取商品价格 等相关信息
                foreach ($goods_buy_num as $ps_key => $ps_value) {
                    $pd_condition['gid'] = $ps_value['gid'];
                    $goods_item_info = $goodsModel->getGoodsOnlineInfo($pd_condition);
                    if (!empty($goods_item_info)) {
                        $cart['gid'] = $ps_value['gid'];
                        $cart['vid'] = $goods_item_info['vid'];
                        $cart['gc_id'] = $goods_item_info['gc_id'];
                        $cart['goods_name'] = $goods_item_info['goods_name'];
                        $cart['goods_price'] = $goods_item_info['goods_price'];
                        $cart['store_name'] = $goods_item_info['store_name'];
                        $cart['goods_image'] = $goods_item_info['goods_image'];
                        $cart['transport_id'] = $goods_item_info['transport_id'];
                        $cart['goods_freight'] = $goods_item_info['goods_freight'];
                        $cart['goods_vat'] = $goods_item_info['goods_vat'];
                        $cart['bl_id'] = 0;
                        $cart['goods_num'] = $ps_value['goods_num'];

                        $cart_list[$ps_key] = $cart;
                    }
                }

                $cart_list = $model_cart->getOnlineCartList($cart_list);

                //到得商品列表
                $goods_list = $model_cart->getGoodsList($cart_list);


                //购物车列表以店铺ID分组显示
                $store_cart_list = $model_cart->getStoreCartList($cart_list);

                //根据产品列表
                if(count($store_cart_list)>1){
                    $result['dian_list'] = array();
                    $result['gid'] = 0;
                }else{
                    //单店铺商品
                    foreach ($goods_list as $k=>$v){
                        $gids[]=$v['gid'];
                    }
                }
                //取得门店列表
                $condition5['dian.delivery_type'] =array('like','%shangmen%');
                $condition5['dian.status'] = ['exp',' dian.cash_status=1 or  dian.ldj_status=1 '];
                $dian_list=$store->getDianCountByGid($gids,null,$condition5);
                if($dian_list) {
                    $result['dian_list'] = $dian_list;
                    $result['gid'] = join(',', $gids);
                }

            }
            else{

                //取得购买的商品ID和购买数量,只有一个下标 ，只会循环一次
                foreach ($buy_items as $gid => $quantity) {break;}


                //取得商品最新在售信息
                $goods_info = $model_cart->getGoodsOnlineInfo($gid,intval($quantity));



                if(empty($goods_info)) {
                    return array('error' => '商品不存在');
                }


                //不能购买自己店铺的商品
                if ($goods_info['vid'] == $vid) {
                    return array('error' => '不能购买自己店铺的商品' );
                }

                if(isset($_REQUEST['pin']) && Config('sld_pintuan') && Config('pin_isuse')){  //拼团
                    $re = con_addons('pin',$goods_info);
                    $goods_info = $re['goods_info'];
                    $result['pin'] = $re['pin'];
                }

                // 获取最终价格
                $goodsActivity = new GoodsActivity();
                $goods_info = $goodsActivity->rebuild_goods_data($goods_info,$extends_data['from'],['grade'=>1]);
                // 不进行拼团购买
                if(!(isset($_REQUEST['pin']) && Config('sld_pintuan') && Config('pin_isuse'))) {
                    if (isset($goods_info['promotion_type']) && $goods_info['promotion_type'] == 'pin_tuan') {
                        unset($goods_info['promotion_type']);

                        $goods_info['show_price'] = $goods_info['goods_price'];
                    }
                }

                if (isset($extends_data['from']) && $extends_data['from'] == 'pc') {
                    // 检查是否 PC商城下单
                    $no_allow_pc_buy = array('p_mbuy','pin_tuan');
                    if (isset($goods_info['promotion_type']) && in_array($goods_info['promotion_type'],$no_allow_pc_buy)) {
                        unset($goods_info['promotion_type']);

                        $goods_info['show_price'] = $goods_info['goods_price'];
                    }
                }

                $goods_info['goods_spec'] = current(unserialize($goods_info['goods_spec']));

                //转成多维数组，方便纺一使用购物车方法与模板
                $store_cart_list = array();
                $goods_list = array();
                $goods_list[0] = $store_cart_list[$goods_info['vid']][0] = $goods_info;


                $result['gid'] = intval($goods_info['gid']);

            }
        }

        //商品金额计算(分别对每个商品/优惠套装小计、每个店铺小计)


        list($store_cart_list,$store_goods_total) = $model_cart->calcCartList($store_cart_list,$member_id);



        $result['store_cart_list'] = $store_cart_list;
        $result['store_goods_total'] = $store_goods_total;


        //取得店铺优惠 - 满即送(赠品列表，店铺满送规则列表)
        list($store_premiums_list,$store_mansong_rule_list) = $model_cart->getMansongRuleCartListByTotal($store_goods_total);
        $result['store_premiums_list'] = $store_premiums_list;
        $result['store_mansong_rule_list'] = $store_mansong_rule_list;

        //重新计算优惠后(满即送)的店铺实际商品总金额
        $store_goods_total = $model_cart->reCalcGoodsTotal($store_goods_total,$store_mansong_rule_list,'mansong');


        //返回需要计算运费的店铺ID数组 和 不需要计算运费(满免运费活动的)店铺ID及描述
        list($need_calc_sid_list,$cancel_calc_sid_list) = $this->getStoreFreightDescList($store_goods_total);
        $result['need_calc_sid_list'] = $need_calc_sid_list;
        $result['cancel_calc_sid_list'] = $cancel_calc_sid_list;


        //将商品ID、数量、运费模板、运费序列化，加密，输出到模板，选择地区AJAX计算运费时作为参数使用
        $freight_list = $this->getStoreFreightList($goods_list,array_keys($cancel_calc_sid_list));
        $result['freight_list'] = $this->buyEncrypt($freight_list, $member_id);

        //输出用户默认收货地址
        $addressModel = new Address();
        $result['address_info'] = $addressModel->getDefaultAddressInfo(array('member_id'=>$member_id));

        //输出有货到付款时，在线支付和货到付款及每种支付下商品数量和详细列表
        $pay_goods_list = $this->getOfflineGoodsPay($goods_list);
        if (!empty($pay_goods_list['offline'])) {
            $result['pay_goods_list'] = $pay_goods_list;
            $result['ifshow_offpay'] = true;
        } else {
            //如果所购商品只支持线上支付，支付方式不允许修改
            $result['deny_edit_payment'] = true;
        }

        //发票 :只有所有商品都支持增值税发票才提供增值税发票
        foreach ($goods_list as $goods) {
            if (isset($goods['goods_vat']) && !intval($goods['goods_vat'])) {
                $vat_deny = true;
                $result['vat_deny'] = $vat_deny;
                $result['vat_hash'] = $this->buyEncrypt($result['vat_deny'] ? 'deny_vat' : 'allow_vat', $member_id);
                break;
            }
        }
        //不提供增值税发票时抛出true(模板使用)
        //$result['vat_deny'] = $vat_deny;
        //$result['vat_hash'] = $this->buyEncrypt($result['vat_deny'] ? 'deny_vat' : 'allow_vat', $member_id);

        //输出默认使用的发票信息
        $invoiceModel = new Invoice();
        $inv_info = $invoiceModel->getDefaultInvInfo(array('member_id'=>$member_id));
        if ($inv_info['inv_state'] == '2' && !isset($vat_deny)) {
            $inv_info['content'] = '增值税发票'.' '.$inv_info['inv_company'].' '.$inv_info['inv_code'].' '.$inv_info['inv_reg_addr'];
        } elseif ($inv_info['inv_state'] == '2' && isset($vat_deny)) {
            $inv_info = array();
            $inv_info['content'] = '不需要发票';
        } elseif (!empty($inv_info)) {
            $inv_info['content'] = '普通发票'.' '.$inv_info['inv_title'].' '.$inv_info['inv_content'];
        } else {
            $inv_info = array();
            $inv_info['content'] = '不需要发票';
        }
        $result['inv_info'] = $inv_info;

        // 优惠券是否 允许使用
        $result['is_allow_show_red'] = $is_allow_show_red;

        //删除购物车中无效商品
        if ($ifcart) {
            if (is_array($invalid_cart)) {
                $cart_id_str = implode(',',$invalid_cart);
                if (preg_match_all('/^[\d,]+$/',$cart_id_str,$matches)) {
                    $model_cart->delCart('db',array('buyer_id'=>$member_id,'cart_id'=>array('in',$cart_id_str)));
                }
            }
        }

        //显示使用预存款支付及会员预存款
        $model_payment = new Payment();
        $pd_payment_info = $model_payment->getPaymentOpenInfo(array('payment_code'=>'predeposit'));
        $userModel = new User();
        if (!empty($pd_payment_info)) {
            $buyer_info = $userModel->infoMember(array('member_id' => $member_id));
            if (floatval($buyer_info['available_predeposit']) > 0) {
                $result['available_predeposit'] = $buyer_info['available_predeposit'];
            }
        }

        //积分抵扣
        if ($GLOBALS['setting_config']['points_max_use']!=0) {
            if(!$buyer_info){
                $buyer_info = $userModel->infoMember(array('member_id' => $member_id));
            }
            $result['member_points'] = $buyer_info['member_points'];
            $result['points_max_use'] = $GLOBALS['setting_config']['points_max_use'];
            $result['points_purpose_rebate'] = $GLOBALS['setting_config']['points_purpose_rebate'];
        }

        return $result;
    }

    /**
     * 购物车、直接购买第二步:保存订单入库，产生订单号，开始选择支付方式
     *
     */
    public function buyStep2($post, $member_id, $member_name, $member_email,$extends_data=array(),$again=0) {
        $model_cart = new UserCart();
        $goodsModel = new Goods();
        if(!$again){
            //取得商品ID和购买数量
            $input_buy_items = $this->_parseItems($post['cart_id']);
        }

        //是否开增值税发票
        $input_if_vat = $this->buyDecrypt($post['vat_hash'], $member_id);
        if (!in_array($input_if_vat,array('allow_vat','deny_vat'))) {
            return array('error' => '订单保存出现异常，请重试');
        }
        $input_if_vat = ($input_if_vat == 'allow_vat') ? true : false;
        //是否支持货到付款
//        $input_if_offpay = $this->buyDecrypt($post['offpay_hash'], $member_id);
//        if (!in_array($input_if_offpay,array('allow_offpay','deny_offpay'))) {
//            return array('error' => '订单保存出现异常，请重试');
//        }
//        $input_if_offpay = ($input_if_offpay == 'allow_offpay') ? true : false;
        if(Config('cash_on_delivery')){
            $input_if_offpay = true;
        }else{
            $input_if_offpay = false;
        }
        if(!$input_if_offpay && $post['pay_name'] == 'offline'){
            return array('error' => '付款方式错误，请重新选择1');
        }
        //付款方式:在线支付/货到付款(online/offline)
        if (!in_array($post['pay_name'],array('online','offline'))) {
            return array('error' => '付款方式错误，请重新选择2');
        }
        $input_pay_name = $post['pay_name'];
        //验证发票信息
        if (!empty($post['invoice_id'])) {
            $input_invoice_id = intval($post['invoice_id']);
            if ($input_invoice_id > 0) {
                $invoiceModel = new Invoice();
                $input_invoice_info = $invoiceModel->getinvInfo(array('inv_id'=>$input_invoice_id));
                if ($input_invoice_info['member_id'] != $member_id) {
                    return array('error' => '请正确填写发票信息');
                }
            }
        }


        if ($post['ifcart']) {

            //取购物车列表

            $ids = "";
            foreach($input_buy_items as $k=>$v){
                $ids .= $k.",";
            }
            $ids = substr($ids,0,strlen($ids)-1);
            $condition = " cart_id in (".$ids.") and buyer_id=".$member_id;
            $cart_list  = $model_cart->listCart('db',$condition);
            //取商品最新的在售信息
            $cart_list = $model_cart->getOnlineCartList($cart_list);

            if (isset($post['is_supplier'])) {
                // 批发商品
                $goods_buy_quantity = array();
                foreach ($cart_list as $key => $value) {
                    if ($value['has_spec']) {
                        $item_spec_num = unserialize($value['spec_num']);
                        foreach ($item_spec_num as $spec_key => $spec_value) {
                            $goods_buy_quantity[$value['spec_data'][$spec_key]['gid']] = $spec_value;
                        }
                    }else{
                        $goods_buy_quantity[$value['gid']] = $value['goods_num'];
                    }
                }
            }else{
                //得到限时折扣信息
                $cart_list = $model_cart->getXianshiCartList($cart_list);

                //得到优惠套装状态,并取得组合套装商品列表
                $cart_list = $model_cart->getBundlingCartList($cart_list);

            }
            //到得商品列表
            $goods_list = $model_cart->getGoodsList($cart_list);
            //购物车列表以店铺ID分组显示
            $store_cart_list = $model_cart->getStoreCartList($cart_list);
        } else {
            if ($extends_data['is_bundling']) {
                // 优惠套装 数据生成
                // 根据套装ID 获取 优惠套装 商品数据
                $p_bundling_condition['bl_id'] = $p_bundling_goods_condition['bl_id'] = $extends_data['bl_id'];
                $p_bundling_condition['bl_state'] = 1;
                $pbModel = new Pbundling();
                $p_bundling_data = $pbModel->getBundlingInfo($p_bundling_condition);
                $p_bundling_goods_data = $pbModel->getBundlingGoodsList($p_bundling_goods_condition);

                $cart_list = array();

                foreach ($p_bundling_goods_data as $pb_key => $pb_value) {
                    if ($pd_key == 0) {
                        $pd_condition['gid'] = $pb_value['gid'];

                        $goods_item_info = $goodsModel->getGoodsOnlineInfo($pd_condition);
                        if (!empty($goods_item_info)) {
                            $cart['gid'] = $pb_value['gid'];
                            $cart['vid'] = $goods_item_info['vid'];
                            $cart['gc_id'] = $goods_item_info['gc_id'];
                            $cart['goods_name'] = $p_bundling_data['goods_name'];
                            $cart['goods_price'] = $p_bundling_data['bl_discount_price'];
                            $cart['store_name'] = $goods_item_info['store_name'];
                            $cart['goods_image'] = $pb_value['goods_image'];
                            $cart['transport_id'] = $goods_item_info['transport_id'];
                            $cart['goods_freight'] = $goods_item_info['goods_freight'];
                            $cart['fenxiao_yongjin'] = $goods_item_info['fenxiao_yongjin'];
                            $cart['goods_vat'] = $goods_item_info['goods_vat'];
                            $cart['bl_id'] = $pb_value['bl_id'];
                            $cart['goods_num'] = 1;

                            $cart_list[$pb_key] = $cart;
                        }
                    }
                }

                //得到优惠套装状态,并取得组合套装商品列表
                $cart_list = $model_cart->getBundlingCartList(array($cart_list[0]));

                //到得商品列表
                $goods_list = $model_cart->getGoodsList($cart_list);

                //购物车列表以店铺ID分组显示
                $store_cart_list = $model_cart->getStoreCartList($cart_list);


            } elseif($extends_data['suite']){
                $suite_checked_goods = $extends_data['suite_checked'];

                // 要购买的商品ID 及 商品数量
                $goods_buy_num = array();
                $goods_tmp_arr = explode(',',$suite_checked_goods);
                foreach ($goods_tmp_arr as $key => $value) {
                    $goods_item_data = explode('|', $value);
                    $goods_buy_num[$key]['gid'] = $goods_item_data[0];
                    $goods_buy_num[$key]['goods_num'] = $goods_item_data[1];
                }

                // 获取商品价格 等相关信息
                foreach ($goods_buy_num as $ps_key => $ps_value) {
                    $pd_condition['gid'] = $ps_value['gid'];
                    $goods_item_info = $goodsModel->getGoodsOnlineInfo($pd_condition);
                    if (!empty($goods_item_info)) {
                        $cart['gid'] = $ps_value['gid'];
                        $cart['vid'] = $goods_item_info['vid'];
                        $cart['gc_id'] = $goods_item_info['gc_id'];
                        $cart['goods_name'] = $goods_item_info['goods_name'];
                        $cart['goods_price'] = $goods_item_info['goods_price'];
                        $cart['store_name'] = $goods_item_info['store_name'];
                        $cart['goods_image'] = $goods_item_info['goods_image'];
                        $cart['transport_id'] = $goods_item_info['transport_id'];
                        $cart['goods_freight'] = $goods_item_info['goods_freight'];
                        $cart['fenxiao_yongjin'] = $goods_item_info['fenxiao_yongjin'];
                        $cart['goods_vat'] = $goods_item_info['goods_vat'];
                        $cart['bl_id'] = 0;
                        $cart['goods_num'] = $ps_value['goods_num'];

                        $cart_list[$ps_key] = $cart;
                    }
                }

                //取商品最新的在售信息
                $cart_list = $model_cart->getOnlineCartList($cart_list);
                //得到优惠套装状态,并取得组合套装商品列表
                $cart_list = $model_cart->getBundlingCartList($cart_list);

                //到得商品列表
                $goods_list = $model_cart->getGoodsList($cart_list);

                //购物车列表以店铺ID分组显示
                $store_cart_list = $model_cart->getStoreCartList($cart_list);

            }
            else{
                //来源于直接购买  拼团只能是直接购买
                //取得购买的商品ID和购买数量,只有有一个下标 ，只会循环一次

                foreach ($input_buy_items as $gid => $quantity) {break;}


                //取得商品最新在售信息
                $goods_info = $model_cart->getGoodsOnlineInfo($gid,$quantity);
                if(empty($goods_info)) {
                    return array('error' => '商品不存在');
                }
                //判断是不是正在团购中，如果是则按团购价格计算，购买数量若超过团购规定的上限，则按团购上限计算
                $goods_info = $model_cart->getTuanInfo($goods_info);

                if($_REQUEST['pin'] && Config('sld_pintuan') && Config('pin_isuse')){  //拼团
                    $re = con_addons('pin',$goods_info);
                    if($re['error']) {
                        return array('error' => $re['error']);
                    }
                    $goods_info = $re['goods_info'];
                    $pin_info = $re['pin'];
                    if($pin_info['haspin']){  //如果有这个拼团订单了 直接处理老订单
                        return array('pay_sn' => $pin_info['haspin']);
                    }

                    if($pin_info['sld_stock']<1){
                        return array('error' => '拼团商品库存不足');
                    }
                }else{
                    //如果未进行团购，则再判断是否限时折扣中
                    if (!$goods_info['iftuan']) {
                        // $goods_info = $model_cart->getXianshiInfo($goods_info,$quantity);
                    } else {
                        //这里记录一下团购数量，订单完成后需要更新一下团购表信息
                        $tuan_info = array();
                        $tuan_info['tuan_id'] = $goods_info['tuan_id'];
                        $tuan_info['quantity'] = $quantity;
                    }
                }

                // 获取最终价格
                $goodsActivityModel = new GoodsActivity();
                $goods_info = $goodsActivityModel->rebuild_goods_data($goods_info,$extends_data['from'],['grade'=>1]);


                // 不进行拼团购买
                if(!($_REQUEST['pin'] && Config('sld_pintuan') && Config('pin_isuse'))) {
                    if ($goods_info['promotion_type'] && $goods_info['promotion_type'] == 'pin_tuan') {
                        unset($goods_info['promotion_type']);

                        $goods_info['show_price'] = $goods_info['goods_price'];
                    }
                }

                if (isset($extends_data['from']) && $extends_data['from'] == 'pc') {
                    // 检查是否 PC商城下单
                    $no_allow_pc_buy = array('p_mbuy','pin_tuan');
                    if ($goods_info['promotion_type'] && in_array($goods_info['promotion_type'],$no_allow_pc_buy)) {
                        unset($goods_info['promotion_type']);

                        $goods_info['show_price'] = $goods_info['goods_price'];
                    }
                }

                //转成多维数组，方便纺一使用购物车方法与模板
                $store_cart_list = array();
                $goods_list = array();
                $goods_list[0] = $store_cart_list[$goods_info['vid']][0] = $goods_info;
            }
        }


        //商品金额计算(分别对每个商品/优惠套装小计、每个店铺小计)
        list($store_cart_list,$store_goods_total) = $model_cart->calcCartList($store_cart_list,$member_id);


        //取得店铺优惠 - 满即送(赠品列表，店铺满送规则列表)
        list($store_premiums_list,$store_mansong_rule_list) = $model_cart->getMansongRuleCartListByTotal($store_goods_total);

        //重新计算店铺扣除满即送后商品实际支付金额
        $store_final_goods_total = $model_cart->reCalcGoodsTotal($store_goods_total,$store_mansong_rule_list,'mansong');


        //计算每个店铺(所有店铺级优惠活动)总共优惠多少
        $store_promotion_total = $this->getStorePromotionTotal($store_goods_total, $store_final_goods_total);

        //计算每个店铺运费
        $store_freight_total = 0;
        //计算店铺最终订单实际支付金额(加上运费)
        $store_final_order_total = $model_cart->reCalcGoodsTotal($store_final_goods_total,$store_freight_total,'freight');

        //计算店铺分类佣金
        $store_gc_id_commis_rate_list = $this->getStoreGcidCommisRateList($goods_list);


        //将赠品追加到购买列表(如果库存不足，则不送赠品)
        $append_premiums_to_cart_list = $this->appendPremiumsToCartList($store_cart_list,$store_premiums_list,$store_mansong_rule_list,$member_id);
        if(!empty($append_premiums_to_cart_list['error'])) {
            return array('error' => $append_premiums_to_cart_list['error']);
        } else {
            @list($store_cart_list,$goods_buy_quantity,$store_mansong_rule_list) = $append_premiums_to_cart_list;
        }

        $input = array();
        //使用积分抵扣
        if(isset($post['oints_pay']) && $post['points_pay'] == 1 && $post['use_points']>0){
            //不允许使用
            if($GLOBALS['setting_config']['points_max_use']==0){
                return array('error' => '平台不允许使用积分抵扣');
            }

            $zong = 0;
            foreach ($store_final_order_total as $v){
                $zong+=$v;
            }
            //不允许使用
            if($zong==0){
                return array('error' => '付款金额为零，不需要使用积分抵扣');
            }

            if($post['use_points'] > $GLOBALS['setting_config']['points_max_use'] * $zong / 100 * $GLOBALS['setting_config']['points_purpose_rebate']){
                return array('error' => '使用积分抵扣超出限额');
            }

            $input['pd_points'] = $post['use_points'];
        }

        //整理已经得出的固定数据，准备下单

        $input['pay_name'] = $input_pay_name;
        $input['if_offpay'] = $input_if_offpay;
        $input['if_vat'] = $input_if_vat;
        if(isset($post['pay_message']))
        $input['pay_message'] = $post['pay_message'];
        $input['address_info'] = 0;
        if(isset($input_invoice_info))
        $input['invoice_info'] = $input_invoice_info;
        $input['voucher_list'] = 0;
        $input['store_goods_total'] = $store_goods_total;
        $input['store_final_order_total'] = $store_final_order_total;
        $input['store_freight_total'] = $store_freight_total;
        $input['store_promotion_total'] = $store_promotion_total;
        $input['store_gc_id_commis_rate_list'] = $store_gc_id_commis_rate_list;
        $input['store_mansong_rule_list'] = $store_mansong_rule_list;
        $input['store_cart_list'] = $store_cart_list;
        $input['input_city_id'] = 0        ;
        $input['order_from'] = $post['order_from'];
        if(isset($goods_info))
        $input['course_type'] = $goods_info['course_type'];
        if($_POST['dian_id']>0) {
            $input['dian_id'] = intval($_POST['dian_id']);
            $input['ziti'] = 1;
        }
        if(isset($_REQUEST['pin'])){
            $input['pin_id'] = $pin_info['id'];
        }
        $input['red'] = $post['red'];
        $input['vred'] = $post['vred'];

        try {
            //开始事务
            DB::startTrans();
            //生成订单
            list($pay_sn,$order_list) = $this->createOrder($input, $member_id, $member_name, $member_email,$store_cart_list);


            //拼团
            if(Config('sld_pintuan') && Config('pin_isuse') && $pin_info){
                $input['pin'] = $pin_info?$pin_info['id']:0;
                //更新拼团团队表
                foreach ($order_list as $v){
                    $pin_par['order'] = $v;
                }
                $pin_par['pin_info'] = $pin_info;
                $pin_par['team_id'] = $_REQUEST['team_id'];
                $team_re=con_addons('pin',$pin_par,'insertTeam'); //往团队表里插入信息
                if($team_re['succ']!=1){
                    return array('error' => $team_re['msg']);
                }
            }

            //记录订单日志
            $this->addOrderLog($order_list);

            //变更库存和销量
            //$this->updateGoodsStorageNum($goods_buy_quantity,$input['dian_id']);
            $this->updateGoodsStorageNum($goods_buy_quantity,$_POST['dian_id']);

            //更新使用的优惠券状态
            $this->updateVoucher($input_voucher_list=array());
            //更新团购购买人数和数量
            if(isset($tuan_info))
            $this->updateTuan($tuan_info);
            //使用预存款支付
            //$this->pdPay($order_list, $post, $member_id, $member_name);

            //提交事务
            Db::commit();

        }catch (Exception $e){

            //回滚事务
            $model_cart->rollback();
            return array('error' => $e->getMessage());
        }
        if(!$again){
            //删除购物车中的商品
            if ($post['ifcart']) {
                $ids = "";print_r($input_buy_items);
                foreach($input_buy_items as $k=>$v){
                    $ids .=$k.",";
                }
                $ids = substr($ids,0,strlen($ids)-1);
                $model_cart->delCart('db',array('buyer_id'=>$member_id,'cart_id'=>array('in',$ids)));
                echo DB::table("bbc_cart")->getLastSql();die;
            }
        }


        //下单完成后，需要更新销量统计
        $this->_complateOrder($goods_list);
        //

        return array('pay_sn' => $pay_sn);
    }



    /**
     * 线下支付第一步
     *
     * @param array $cart_id 购物车
     * @param int $ifcart 是否为购物车
     * @param int $invalid_cart
     * @param int $member_id 会员编号
     * @param int $vid 店铺编号
     */
    public function payStep1($vid, $member_id, $member_store_id) {
        //不能自己付款自己商店的商品

        if ($vid == $member_store_id) {
            return array('error' => '不能购买自己店铺的商品' );
        }
    }

    /**
     * 购物车、直接购买第二步:保存订单入库，产生订单号，开始选择支付方式
     *
     */
    public function payStep2($post, $member_id, $member_name, $member_email) {

        //付款方式:在线支付/货到付款(online/offline)
        if (!in_array($post['pay_name'],array('online','offline'))) {
            return array('error' => '付款方式错误，请重新选择');
        }
        $input_pay_name = $post['pay_name'];

        $model_store = Model('vendor');
        $store_info = $model_store->getStoreInfoByID($post['vid']);

        //整理已经得出的固定数据，准备下单
        $input = array();
        $input['pay_name'] = $input_pay_name;
        $input['pay_message'] = $post['pay_message'];
        $input['pay_name'] = $input_pay_name;
        $input['if_offpay'] = '';
        $input['if_vat'] = '';
        $input['pay_message'] = $post['pay_message'];
        $input['address_info'] = '线下店支付';
        $input['invoice_info'] = '无';
        $input['voucher_list'] = '无';
        $input['store_goods_total'] = '';
        $input['store_final_order_total'] = '';
        $input['store_freight_total'] = '';
        $input['store_promotion_total'] = '';
        $input['store_cart_list'] = '';
        $input['input_city_id'] = '线下店支付';

        $store_cart_list = '';
        $goods_list = '';

        try {

            $model_order = Model('order');
            $order_list = array();

            $condition = array();
            //$condition['order_id'] = $post['order_id'];//$order['order_sn'];
            $condition['vid'] = $post['vid'];
            $condition['buyer_id'] = $member_id;
            $condition['payment_code'] = 'tiyan';
            $condition['order_state'] = '10';
            $order_item = $model_order->getOrderInfo($condition);

            if(!empty($order_item))
            {
                return array(array('pay_sn' => $order_item['pay_sn'],'order_id'=>$order_item['order_id']));
            }

            $pay_sn = $this->makePaySn($member_id);
            $order_pay = array();
            $order_pay['pay_sn'] = $pay_sn;
            $order_pay['buyer_id'] = $member_id;
            $order_pay_id = $model_order->addOrderPay($order_pay);
            if (!$order_pay_id) {
                throw new Exception('订单保存失败');
            }




            $order = array();
            $order_common = array();
            $order_goods = array();

            $order['order_sn'] = $this->makeOrderSn($order_pay_id);
            $order['pay_sn'] = $pay_sn;
            $order['vid'] = $post['vid'];
            $order['store_name'] = $store_info['store_name'];//'线下体验店';
            $order['buyer_id'] = $member_id;
            $order['buyer_name'] = $member_name;
            $order['buyer_email'] = $member_email;
            $order['add_time'] = TIMESTAMP;
            $order['payment_code'] = 'wxtiyan';
            $order['order_state'] = ORDER_STATE_NEW;//$store_pay_type_list[$vid] == 'online' ? ORDER_STATE_NEW : ORDER_STATE_PAY;
            $order['order_amount'] = $post['cash_total'];;
            $order['shipping_fee'] = '';
            $order['goods_amount'] = $post['cash_total'];
            $order['order_from'] = $post['order_from'];
            $order_id = $model_order->addOrder($order);
            if (!$order_id) {
                throw new Exception('订单保存失败');
            }

            //生成订单
            // list($pay_sn,$order_list) = $this->createOrder($input, $member_id, $member_name, $member_email);

            //记录订单日志
            // $this->addOrderLog($order_list);

            //使用预存款支付
            //$this->pdPay($order_list, $post, $member_id, $member_name);

        }catch (Exception $e){
            return array('error' => $e->getMessage());
        }


        //return array('pay_sn' => $order_info);


        return array(array('pay_sn' => $pay_sn,'order_id'=>$order_id));
    }


    // 支付完成后修改订单状态，修改成完成订单
    public function payStep3($post, $member_id, $member_name, $member_email)
    {
        //修改订单状态***************************
        $model_order = Model('order');
        $data = array();
        $condition1 = array();
        $condition1['order_id'] = $post['order_id'];//$order_id;//$order['order_sn'];
        $condition1['vid'] = $post['vid'];
        $condition['buyer_id'] = $member_id;

        $data['reciver_name'] = '';
        $data['reciver_info'] = '';
        $data['deliver_explain'] = '';
        $data['daddress_id'] = '';
        $data['shipping_express_id'] = '';
        $data['shipping_time'] = TIMESTAMP;

        try {

            $model_order->beginTransaction();

            $update = $model_order->editOrderCommon($data, $condition1);
            if (!$update) {
                throw new Exception('保存失败');
            }

            $data = array();
            //$data['order_state'] = ORDER_STATE_SEND;
            $data['delay_time'] = TIMESTAMP;
            $data['shipping_code'] = '';
            $data['payment_code'] = 'wxpay';


            $update = $model_order->editOrder($data, $condition1);
            if (!$update) {
                throw new Exception(L('保存失败'));
            }
            $model_order->commit();
        }catch (Exception $e) {
            $model_order->rollback();
            throw new Exception(L('保存失败'));
        }





        $condition = array();
        $condition['order_id'] = $post['order_id'];//$order['order_sn'];
        // $condition['vid'] = $post['vid'];
        //$condition['buyer_id'] = $member_id;
        $order_info = $model_order->getOrderInfo($condition);

        $state_type = 'order_receive';
        //sleep(2000);
//        return array('pay_sn' => $order_info);

//        if($order_info['order_state'] == ORDER_STATE_PAY)
//        {

        $data = array();
        //$data['order_state'] = ORDER_STATE_SEND;
        $data['delay_time'] = TIMESTAMP;
        $data['shipping_code']  = '';
        $data['payment_code'] = 'wxtiyan';
        $update = $model_order->editOrder($data,$condition1);
        $result = $model_order->offlinememberChangeState($state_type, $order_info, $member_id, $member_name, '线下支付');
//        }
        //$result = $model_order->offlinememberChangeState($state_type, $order_info, $member_id, $member_name, '线下支付');

        //return array('pay_sn' => $order_info);



    }



    /**
     * 加密
     * @param array/string $string
     * @param int $member_id
     * @return mixed arrray/string
     */
    public function buyEncrypt($string, $member_id) {
        $buy_key = sha1(md5($member_id.'&'.MD5_KEY));
        if (is_array($string)) {
            $string = serialize($string);
        } else {
            $string = strval($string);
        }
        return encrypt(base64_encode($string), $buy_key);
    }

    /**
     * 解密
     * @param string $string
     * @param int $member_id
     * @param number $ttl
     */
    public function buyDecrypt($string, $member_id, $ttl = 0) {
        $buy_key = sha1(md5($member_id.'&'.MD5_KEY));
        if (empty($string)) return;
        $string = base64_decode(trim(decrypt(strval($string), $buy_key, $ttl)));
        return $string;
    }

    /**
     * 得到所购买的id和数量
     *
     */
    private function _parseItems($cart_id) {
        //存放所购商品ID和数量组成的键值对
        $buy_items = array();
        if (is_array($cart_id)) {
            foreach ($cart_id as $value) {
                $value = htmlspecialchars_decode($value);
                if(strpos($value,'{<||>}') !== false){
                    $supplier_cart = explode('{<||>}', $value);
                    $spec_quantity = array();
                    if (strpos($supplier_cart[1],'|<=>|') === false) {
                        $spec_quantity = $supplier_cart[1];
                    }else{
                        $spec_quantity_arr = explode('[<=>]', $supplier_cart[1]);
                        if (is_array($spec_quantity_arr) && !empty($spec_quantity_arr)) {
                            foreach ($spec_quantity_arr as $k => $item) {
                                $spec_quantity_arr_item = explode('|<=>|', $item);
                                $spec_quantity[$spec_quantity_arr_item[0]] = $spec_quantity_arr_item[1];
                            }
                        }
                    }
                    $buy_items[$supplier_cart[0]] = $spec_quantity;
                }else{
                    if (preg_match_all('/^(\d{1,10})\|(\d{1,6})/', $value, $match)) {
                        $buy_items[$match[1][0]] = $match[2][0];
                    }
                }
            }
        }
        return $buy_items;
    }

    /**
     * 下单完成后，更新销量统计
     *
     */
    private function _complateOrder($goods_list = array()) {
        if (empty($goods_list) || !is_array($goods_list)) return;
        foreach ($goods_list as $goods_info) {
            //更新销量统计
            $date = date('Ymd',time());
            $sale_date_array = DB::table('bbc_salenum')->where(array('date'=>$date,'gid'=>$goods_info['gid']))->find();
            if(is_array($sale_date_array) && !empty($sale_date_array)){
                $update_param = array();
                $update_param['table'] = 'salenum';
                $update_param['field'] = 'salenum';
                $update_param['value'] = $goods_info['goods_num'];
                $update_param['where'] = "WHERE date = '".$date."' AND gid = '".$goods_info['gid']."'";
                $this->updatestat($update_param);
            }else{
                DB::table('bbc_salenum')->insert(array('date'=>$date,'salenum'=>$goods_info['goods_num'],'vid'=>$goods_info['vid'],'gid'=>$goods_info['gid']));
            }
        }
    }

    /**
     * 选择不同地区时，异步处理并返回每个店铺总运费以及本地区是否能使用货到付款
     * 如果店铺统一设置了满免运费规则，则运费模板无效
     * 如果店铺未设置满免规则，且使用运费模板，按运费模板计算，如果其中有商品使用相同的运费模板，则两种商品数量相加后再应用该运费模板计算（即作为一种商品算运费）
     * 如果未找到运费模板，按免运费处理
     * 如果没有使用运费模板，商品运费按快递价格计算，运费不随购买数量增加
     */
    public function changeAddr($freight_hash, $city_id, $area_id, $member_id) {
        //$city_id计算运费模板,$area_id计算货到付款
        $city_id = intval($city_id);
        $area_id = intval($area_id);
        if ($city_id <= 0 || $area_id <= 0) return null;

        //将hash解密，得到运费信息(店铺ID，运费,运费模板ID,购买数量),hash内容有效期为1小时
        $freight_list = $this->buyDecrypt($freight_hash, $member_id);
        //算运费
        $store_freight_list = $this->calcStoreFreight($freight_list, $city_id);
        $data = array();
        $data['state'] = empty($store_freight_list) ? 'fail' : 'success';
        $data['content'] = $store_freight_list;

        //是否能使用货到付款(只有包含平台店铺的商品才会判断)
        $if_include_platform_store = array_key_exists(DEFAULT_PLATFORM_STORE_ID,$freight_list['iscalced']) || array_key_exists(DEFAULT_PLATFORM_STORE_ID,$freight_list['nocalced']);
        if ($if_include_platform_store) {
            $allow_offpay = Model('offpay_area')->checkSupportOffpay($area_id,DEFAULT_PLATFORM_STORE_ID);
        }
        //JS验证使用
        $data['allow_offpay'] = $allow_offpay ? '1' : '0';
        //PHP验证使用
        $data['offpay_hash'] = $this->buyEncrypt($allow_offpay ? 'allow_offpay' : 'deny_offpay', $member_id);

        return $data;
    }
    public function updatestat($param){
        if (empty($param)){
            return false;
        }
        //$result = Db::update($param['table'],array($param['field']=>array('sign'=>'increase','value'=>$param['value'])),$param['where']);
        //return $result;
    }

}
