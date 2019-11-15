<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/10/12
 * Time: 14:48
 */
class ldj_orderModel extends Model
{
    public function __construct()
    {
        parent::__construct('ldj_order');

    }
    /*
     * 下单第一步
     */
    public function buystep1($cartids,$member_id,$vid)
    {
        $cart_model = M('ldj_cart','ldj');
        $return_data = [];
        //取出购物车数据
        $condition = [
            'cart_id'=>['in',$cartids],
            'buyer_id'=>$member_id,
            'vid'=>$vid
        ];

        $cart_list = $cart_model->getVendorCartlist($condition);
        if(!$cart_list){
            return ['status'=>255,'msg'=>'购物车为空'];
        }
        //检测有没有商品状态
        $error = $this->testCartGoodsInfo($cart_list);
        if($error['status'] == 255){
            return $error;
        }
        //获取商品实际信息
        $return_data['cart_list'] = $this->getGoodsInfoByCart($cart_list);
        //计算总价格
        $all_money = 0;
        foreach($return_data['cart_list'] as $k=>$v){
            $all_money += $v['goods_price']*$v['goods_num'];
        }
        $return_data['goods_all_price'] = $all_money;
        //检测店铺
        $dian_info = $this->testVendorinfo($vid);
        if($dian_info['status'] == 255) {
            return $dian_info;
        }

        if($all_money < $dian_info['ldj_delivery_order_MinPrice']){
            $return_data['error_cart_state'] = 1;
            $return_data['error_cart_msg'] = '差'.($dian_info['ldj_delivery_order_MinPrice']-$all_money).'元起送';
        }
        //录入店铺信息
        $return_data['dian_info']['dian_id'] = $dian_info['id'];
        $return_data['dian_info']['dian_name'] = $dian_info['dian_name'];
        $return_data['dian_info']['dian_lng'] = $dian_info['dian_lng'];
        $return_data['dian_info']['dian_lat'] = $dian_info['dian_lat'];
        $dian = explode(',',$dian_info['delivery_type']);
        $return_data['dian_info']['kuaidi'] = 0;
        $return_data['dian_info']['shangmen'] = 0;
        if(in_array('kuaidi',$dian) || in_array('mendian',$dian)){
            $return_data['dian_info']['kuaidi'] = 1;
        }
        if(in_array('shangmen',$dian)){
            $return_data['dian_info']['shangmen'] = 1;
        }


        //预计送达时间
        $return_data['estimatedTime'] = $this->estimatedTime($dian_info);

        //基础运费
        $freight_money = $dian_info['ldj_delivery_order_Price'];
        //输出用户默认收货地址
//        $address_info = Model('address')->getDefaultAddressInfo(array('member_id'=>$member_id));
        $address_info = M('ldj_address','ldj')->findmemberaddress(['member_id' => $member_id,'is_default'=>1]);
        if($address_info){
            $return_data['address'] = $address_info;
            //获取城市名
//            $city = Model()->table('area')->where(['area_id'=>$address_info['city_id']])->find();
            //发起高德地图经纬度查询接口
//            $location_info = request_post('https://restapi.amap.com/v3/geocode/geo',false,['key'=>C('gaode_serverkey'),'address'=>$address_info['address'],'city'=>$city['area_name']]);
//            $location_info = json_decode($location_info,1);
            if(!empty($address_info['lng']) && !empty($address_info['lat'])){
//                $location = explode(',',$location_info['geocodes'][0]['location']);
                $distance = _distance($address_info['lng'],$address_info['lat'],$dian_info['dian_lng'],$dian_info['dian_lat']);
                //判断地址距离是否合格
                if($distance <= $dian_info['ldj_delivery_order_MinDistance']*1000){
                    $return_data['freight_money'] = $freight_money;
                }elseif($distance > $dian_info['ldj_delivery_order_MinDistance']*1000 && $distance <= $dian_info['ldj_delivery_order_MaxDistance']*1000){
                    //计算额外累加的运费
                    if($dian_info['ldj_delivery_order_PerPrice']>0){
                        $freight_money += ceil(($distance/1000)-$dian_info['ldj_delivery_order_MinDistance'])*$dian_info['ldj_delivery_order_PerPrice'];
                    }
                    $return_data['freight_money'] = $freight_money;
                }else{
                    //超出配送范围
                    $return_data['freight_money'] = $freight_money;
                    $return_data['error_area_state'] = 1;
                    $return_data['error_area_msg'] = '当前位置不支持配送';
                }
            }else{
                //未获取到位置信息
                $return_data['freight_money'] = $freight_money;
                $return_data['error_area_state'] = 1;
                $return_data['error_area_msg'] = '未获取到详细地址信息';
            }


        }else{
            //会员未设置默认地址
            $return_data['freight_money'] = $freight_money;
            $return_data['error_area_state'] = 1;
            $return_data['error_area_msg'] = '请添加收货地址';
        }

        return $return_data;

    }
    /*
     * 通过购物车获取商品状态
     * @param array cart_list
     * return ['status'=>255,'msg'=>'库存不足']
     */
    public function testCartGoodsInfo($cart_list)
    {
        $goods_model = M('ldj_goods','ldj');
        foreach($cart_list as $k=>$v){
            $goods_info = $goods_model->getDianGoods(['goods_id'=>$v['gid'],'dian_id'=>$v['vid']]);
            if(!$goods_info){
                return ['status'=>255,'msg'=>$v['goods_name'].'商品已下架!'];
            }
            if($goods_info['stock'] < $v['goods_num']){
                return ['status'=>255,'msg'=>$v['goods_name'].'商品库存不足!'];
            }
            if($goods_info['off'] || $goods_info['delete']){
                return ['status'=>255,'msg'=>$v['goods_name'].'商品已下架!'];
            }
        }
        return ['status'=>200];
    }
    /*
     * 通过购物车获取商品实际信息
     * @param array cart_list
     * return array
     */
    public function getGoodsInfoByCart($cart_list)
    {
        foreach($cart_list as $k=>$v){
            $goods_Info = $this->table('goods')->where(['gid'=>$v['gid']])->field('goods_name,gc_id,vid,goods_price,goods_image')->find();
            $cart_list[$k]['goods_name'] = $goods_Info['goods_name'];
            $cart_list[$k]['goods_price'] = $goods_Info['goods_price'];
            $cart_list[$k]['image'] = $goods_Info['goods_image'];
            $cart_list[$k]['gc_id'] = $goods_Info['gc_id'];
            $cart_list[$k]['vendor_id'] = $goods_Info['vid'];
            $cart_list[$k]['goods_image'] = cthumb($goods_Info['goods_image']);
        }
        return $cart_list;
    }
    /*
     * 检测店铺信息
     * @param int $vid 店铺id
     * return array
     */
    public function testVendorinfo($vid)
    {
        $dian_model = M('ldj_dian','ldj');
        $dian_info = $dian_model->getDianInfo(['id'=>$vid],$field='*');

        $time = explode('-',date('H-i'));
        $times = $time[0]*60+$time[1];
        $operation_time = explode(',',$dian_info['operation_time']);
        if(!$dian_info['ldj_status']){
            return ['status'=>255,'msg'=>'门店已关闭'];
        }
        if(!$dian_info['status']){
            return ['status'=>255,'msg'=>'暂停营业'];
        }
        if($times<$operation_time[0] || $times>$operation_time[1]){
            return ['status'=>255,'msg'=>'休息中'];
        }
        return $dian_info;
    }
    /*
     * 通过店铺获取送达时间列表今天/明天
     * @param array $dian_info 店铺信息
     * return array
     */
    public function estimatedTime($dian_info)
    {
        $Atime = [];
        $time = time();
        $businessTime = explode(',',$dian_info['operation_time']);
        $dian_start_hour = floor($businessTime[0]/60);
        $dian_last_hour = floor($businessTime[1]/60);
        $hour = date('H',$time)+1;
        if($hour<$dian_start_hour){
            $hour = $dian_start_hour;
        }
        //取今天可以选择的时间列表
        $Atime['first_day'] = [];
        for($hour;$hour<$dian_last_hour;$hour++){
            if(($dian_last_hour-$hour) == 1){
                $Atime['first_day'][] = sprintf("%'02d",$hour).':'.'00'.'-'.sprintf("%'02d",floor($businessTime[1]/60)).':'.sprintf("%'02d",$businessTime[1]%60);
            }else{
                $Atime['first_day'][] = sprintf("%'02d",$hour).':'.'00'.'-'.sprintf("%'02d",$hour+1).':'.'00';
            }
        }
        //取明天可以选择的时间列表
        $Atime['sencond_day'] = [];
        for($dian_start_hour;$dian_start_hour<$dian_last_hour;$dian_start_hour++){
            if(($dian_last_hour-$dian_start_hour) == 1){
                $Atime['sencond_day'][] = sprintf("%'02d",$dian_start_hour).':'.'00'.'-'.sprintf("%'02d",floor($businessTime[1]/60)).':'.sprintf("%'02d",$businessTime[1]%60);
            }else{
                $Atime['sencond_day'][] = sprintf("%'02d",$dian_start_hour).':'.'00'.'-'.sprintf("%'02d",$dian_start_hour+1).':'.'00';
            }
        }

        return $Atime;
    }
    /*
     * 下单第二步入库
     */
    public function buystep2($post,$member_id, $member_name, $member_email)
    {

        $cart_model = M('ldj_cart', 'ldj');
        //验证收货地址
        $input_address_id = intval($post['address_id']);
        if ($post['express_type'] == 1) {
            if ($input_address_id <= 0) {
                return array('status' => 255, 'msg' => '请选择收货地址');
            } else {
//                $address_info = Model('address')->getAddressInfo(array('address_id' => $input_address_id));
                $address_info = M('ldj_address','ldj')->findmemberaddress(['address_id' => $input_address_id]);
                if ($address_info['member_id'] != $member_id) {
                    return array('status' => 255, 'msg' => '请选择收货地址');
                }
            }
        }

        $ceateorderdata['order_message'] = $post['order_message'];
        $ceateorderdata['address_info'] = $address_info;
        //取店铺信息
        $dian_info = $this->testVendorinfo($post['vid']);
        if ($dian_info['status'] == 255) {
            return $dian_info;
        }
        $ceateorderdata['dian_info'] = $dian_info;
        //取商品信息
        //取出购物车数据
        $condition = [
            'cart_id' => ['in', $post['cart_id']],
            'buyer_id' => $member_id,
            'vid' => $post['vid']
        ];
        $cart_list = $cart_model->getVendorCartlist($condition);
        if (!$cart_list) {
            return ['status' => 255, 'msg' => '购物车为空'];
        }
        //检测有没有商品异常状态
        $error = $this->testCartGoodsInfo($cart_list);
        if ($error['status'] == 255) {
            return $error;
        }
        //获取商品实际信息
        $goods_list = $this->getGoodsInfoByCart($cart_list);
        $ceateorderdata['goods_list'] = $goods_list;
        //计算总价格
        $all_money = 0;
        foreach ($goods_list as $k => $v) {
            $all_money += $v['goods_price'] * $v['goods_num'];
        }
        //验证起送价
        if ($all_money < $dian_info['ldj_delivery_order_MinPrice']) {
            return ['status' => 255, 'msg' => '差' . ($dian_info['ldj_delivery_order_MinPrice'] - $all_money) . '元起送'];
        }

        $ceateorderdata['all_money'] = $all_money;

        //计算运费
        //基础运费
        if($post['express_type'] != 2){
        $freight_money = $dian_info['ldj_delivery_order_Price'];
        //获取城市名
//        $city = Model()->table('area')->where(['area_id' => $address_info['city_id']])->find();
        //发起高德地图经纬度查询接口
//        $location_info = request_post('https://restapi.amap.com/v3/geocode/geo', false, ['key' => C('gaode_serverkey'), 'address' => $address_info['address'], 'city' => $city['area_name']]);
//        $location_info = json_decode($location_info, 1);
//            dd($address_info);die;
        if ($address_info['lng'] && $address_info['lat'] ) {
//            $location = explode(',', $location_info['geocodes'][0]['location']);
            $distance = _distance($address_info['lng'], $address_info['lat'], $dian_info['dian_lng'], $dian_info['dian_lat']);
            //判断地址距离是否合格
            if ($distance > $dian_info['ldj_delivery_order_MinDistance'] * 1000 && $distance <= $dian_info['ldj_delivery_order_MaxDistance'] * 1000) {
                //计算额外累加的运费
                if ($dian_info['ldj_delivery_order_PerPrice'] > 0) {
                    $freight_money += ceil(($distance / 1000) - $dian_info['ldj_delivery_order_MinDistance'])  * $dian_info['ldj_delivery_order_PerPrice'];
                }
            } elseif ($distance > $dian_info['ldj_delivery_order_MaxDistance'] * 1000) {
                //超出配送范围
                return ['status' => 255, 'msg' => '当前位置不支持配送'];
            }
        } else {
            //未获取到位置信息
            return ['status' => 255, 'msg' => '未获取到当前位置的详细地址信息'];
        }
    }else{
            $freight_money = 0;
        }
        $ceateorderdata['freight_money'] = $freight_money;
        $ceateorderdata['distance'] = $distance?:0;

        //送货类型处理
        if($post['express_type'] == 1){
            if(!in_array('kuaidi',explode(',',$dian_info['delivery_type'])) && !in_array('mendian',explode(',',$dian_info['delivery_type']))){
                return ['status'=>255,'msg'=>'当前门店不支持送货上门'];
            }
        }else if($post['express_type'] == 2){
            if(!in_array('shangmen',explode(',',$dian_info['delivery_type']))){
                return ['status'=>255,'msg'=>'当前门店不支持上门自提'];
            }
            if(empty($post['member_phone'])){
                return ['status'=>255,'msg'=>'请填写预留电话'];
            }else{
                if(!preg_match('/^1[3,5,6,7,8,9]{1}\d{9}$/',$post['member_phone'])){
                    return ['status'=>255,'msg'=>'预留电话格式错误'];
                }
            }
            //如果自提运费置为0
            $ceateorderdata['freight_money'] = 0;
        }else{
            return ['status'=>255,'msg'=>'请选择送货方式'];
        }

        $ceateorderdata['express_type'] = $post['express_type'];
        $ceateorderdata['member_phone'] = $post['member_phone'];

        //时间处理
        if(!empty($post['time_section'])){
            $dian_time = explode(',',$dian_info['operation_time']);
            $time_section = explode('-',$post['time_section']);
            $time = explode(':',$time_section[0]);
            $times = intval($time[0]*60) + intval($time[1]);
            if($times>$dian_time[1] || $times<$dian_time[0]){
                return ['status'=>255,'msg'=>'请选择店铺营业时间'];
            }
            $time = explode(':',$time_section[1]);
            $times = intval($time[0]*60) + intval($time[1]);
            if($times>$dian_time[1] || $times<$dian_time[0]){
                return ['status'=>255,'msg'=>'请选择店铺营业时间'];
            }
            //时间录入
            if($post['time_type'] == 2){
                $ceateorderdata['time_type'] = 2;
                $ceateorderdata['start_time'] = strtotime(date('Y-m-d',strtotime('+1 day')).' '.$time_section[0]);
                $ceateorderdata['end_time'] = strtotime(date('Y-m-d',strtotime('+1 day')).' '.$time_section[1]);
            }else{
                $ceateorderdata['time_type'] = 1;
                $ceateorderdata['start_time'] = strtotime(date('Y-m-d').' '.$time_section[0]);
                $ceateorderdata['end_time'] = strtotime(date('Y-m-d').' '.$time_section[1]);
            }
        }else{
            return ['status'=>255,'msg'=>'请选择时间'];
        }
        //开始下单
        try{
            $this->beginTransaction();

            $order_res = $this->ceateOrder($ceateorderdata,$member_id, $member_name, $member_email);
            //记录订单日志
//            $this->addOrderLog($order_res['order']);

            //变更库存和销量
            $this->updateGoodsStorageNum($goods_list,$post['vid'],1);
            //删除购物车

            $this->commit();
            return $order_res;
        }catch(Exception $e){

            $this->rollback();
            return ['status'=>255,'msg'=>$e->getMessage()];
        }


    }
    /*
     * 更新商品销量和库存
     * @param goods_list 商品列表
     * @param vid 店铺id
     * @param isdeletecart 是否删除购物车
     */
    public function updateGoodsStorageNum($goods_list,$vid,$isdeletecart=0)
    {
        $dian_model = M('ldj_dian','ldj');
        $goods_model = M('ldj_goods','ldj');
        $cart_model = M('ldj_cart','ldj');
        $num = 0;
        foreach($goods_list as $k=>$v){
            $goods_condition = [
                'dian_id'=>$v['vid'],
                'goods_id'=>$v['gid'],
                'stock'=>['egt',$v['goods_num']],
            ];
            $goods_update = [
                'stock'=>['exp','stock - 1'],
                'sales'=>['exp','sales + 1'],
                'month_sales'=>['exp','month_sales + 1'],
            ];
            $res = $goods_model->editDianGoods($goods_condition,$goods_update);
            if(!$res){
                throw new Exception('订单保存失败2');
            }
            if($isdeletecart){
                $cart_model->deletecart(['cart_id'=>$v['cart_id']]);
            }
            $num += $v['goods_num'];
        }
        $dian_condition = [
            'id'=>$vid
        ];
        $dian_update = [
            'month_sales'=>['exp','month_sales + '.$num]
        ];
        $res = $dian_model->updateDian($dian_condition,$dian_update);
        if(!$res){
            throw new Exception('订单保存失败3');
        }

    }
    /*
     * 下单
     */
    private function ceateOrder($data,$member_id, $member_name, $member_email)
    {
        //地址
        //收货人信息
        $reciver_info = array();
        $reciver_info['address'] = $data['address_info']['area_info'].'&nbsp;'.$data['address_info']['address'].'&nbsp;'.$data['address_info']['address_precose'];
        $reciver_info['phone'] = $data['address_info']['mob_phone'].($data['address_info']['tel_phone'] ? ','.$data['address_info']['tel_phone'] : null);
        $reciver_info = serialize($reciver_info);
        $reciver_name = $data['address_info']['true_name'];


        //生成支付单号
        $pay_sn = $this->makePaySn($member_id);
        $order_pay = array();
        $order_pay['pay_sn'] = $pay_sn;
        $order_pay['buyer_id'] = $member_id;
        $order_pay_id = $this->addOrderPay($order_pay);
        if (!$order_pay_id) {
            throw new Exception('订单保存失败');
        }

        //生成订单表
        //计算总价格
        $order_amount = $data['all_money'] + $data['freight_money'];
        $order = [
            'order_sn'=>$this->makeOrderSn($order_pay_id),
            'pay_sn'=>$pay_sn,
            'vid'=>$data['dian_info']['id'],
            'store_name'=>$data['dian_info']['dian_name'],
            'buyer_id'=>$member_id,
            'buyer_name'=>$member_name,
            'buyer_email'=>$member_email,
            'add_time'=>time(),
            'goods_amount'=>$data['all_money'],
            'order_amount'=>$order_amount,
            'shipping_fee'=>$data['freight_money'],
            'order_state'=>10,
            'order_from'=>0
        ];
        if($data['express_type'] == 2){
            $order['express_type'] = 1;
        }
        //如果订单金额为0直接置为已支付
        if($order['order_amount']==0){
            $order['order_state'] = 20;
        }
        $order_id = $this->addorder($order);
        if (!$order_id) {
            throw new Exception('订单保存失败');
        }
        //生成提货码
        if($order['express_type'] == 1){

            $chain_code = model('order')->encode($order['order_sn'],'ldj');

            $res = $this->editOrder(['order_id'=>$order_id],['chain_code'=>$chain_code]);
            if(!$res){
                throw new Exception('订单保存失败');
            }
        }
        //生成order_common信息
        $order_common = [
            'order_id'=>$order_id,
            'vid'=>$data['dian_info']['id'],
            'order_message'=>$data['order_message'],
            'reciver_name'=>$reciver_name,
            'reciver_info'=>$reciver_info,
            'time_type'=>$data['time_type'],
            'start_time'=>$data['start_time'],
            'end_time'=>$data['end_time'],
            'distance'=>round($data['distance'],2)
        ];
        require_once(BASE_LIBRARY_PATH.'/area/area.php');
        $order_common['reciver_province_id'] = intval($area_array[$data['address_info']['city_id']]['area_parent_id']);
        if($data['express_type'] == 2){
            $order_common['member_phone'] = $data['member_phone'];
        }
        $order_common_id = $this->addorder_common($order_common);
        if(!$order_common_id){
            throw new Exception('订单保存失败');
        }
//        dd($data['goods_list']);die;
        $commis_rate = $this->getStoreGcidCommisRateList($data['goods_list']);
        //生成order_goods表信息
        foreach($data['goods_list'] as $k=>$v){
            $order_goods = [
                'order_id'=>$order_id,
                'gid'=>$v['gid'],
                'goods_name'=>$v['goods_name'],
                'goods_price'=>$v['goods_price'],
                'goods_num'=>$v['goods_num'],
                'goods_pay_price'=>$v['goods_price']*$v['goods_num'],
                'vid'=>$data['dian_info']['id'],
                'buyer_id'=>$member_id,
                'goods_image'=>$v['image'],
                'commis_rate'=>$commis_rate[$v['vendor_id']][$v['gc_id']]?:0,
            ];
//            dd($order_goods);die;
            $rec_id = $this->addorder_goods($order_goods);
            if(!$rec_id){
                throw new Exception('订单保存失败1');
            }

        }
        return $pay_sn;
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
        $store_gc_id_list = array();
        foreach ($goods_list as $goods) {
            if (!intval($goods['gc_id'])) continue;
            if (!in_array($goods['gc_id'],(array)$store_gc_id_list[$goods['vendor_id']])) {
                if (in_array($goods['vendor_id'],array(DEFAULT_PLATFORM_STORE_ID))) {
                    //平台店铺佣金为0
                    $store_gc_id_commis_rate[$goods['vendor_id']][$goods['gc_id']] = 0;
                } else {
                    $store_gc_id_list[$goods['vendor_id']][] = $goods['gc_id'];
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
    /*
     * 插入订单表
     */
    public function addorder($data){
        return $this->table('ldj_order')->insert($data);
    }
    /*
     * 插入order_common
     */
    public function addorder_common($order_common){
        return $this->table('ldj_order_common')->insert($order_common);
    }
    public function addorder_goods($order_goods)
    {
        return $this->table('ldj_order_goods')->insert($order_goods);
    }
    /**
     * 生成支付单编号(两位随机 + 从2000-01-01 00:00:00 到现在的秒数+微秒+会员ID%1000)，该值会传给第三方支付接口
     * 长度 =2位 + 10位 + 3位 + 3位  = 18位
     * 1000个会员同一微秒提订单，重复机率为1/100
     * @return string
     */
    private function makePaySn($member_id) {
        return mt_rand(10,99)
        . sprintf('%010d',time() - 946656000)
        . sprintf('%03d', (float) microtime() * 1000)
        . sprintf('%03d', (int) $member_id % 1000);
    }
    //插入支付订单表
    private function addOrderPay($order_pay){
        return $this->table('ldj_order_pay')->insert($order_pay);
    }
    /**
     * 订单编号生成规则，n(n>=1)个订单表对应一个支付表，
     * 生成订单编号(年取1位 + $pay_id取13位 + 第N个子订单取2位)
     * 1000个会员同一微秒提订单，重复机率为1/100
     * @param $pay_id 支付表自增ID
     * @return string
     */
    private function makeOrderSn($pay_id) {
        //记录生成子订单的个数，如果生成多个子订单，该值会累加
        static $num;
        if (empty($num)) {
            $num = 1;
        } else {
            $num ++;
        }
        return (date('y',time()) % 9+1) . sprintf('%013d', $pay_id) . sprintf('%02d', $num);
    }
    /*
     * 取消订单
     * $order_info 订单信息
     * return
     */
    public function cancel_order($order_info)
    {
        //修改状态
        $res = $this->table('ldj_order')->where(['order_id'=>$order_info['order_id']])->update(['order_state'=>0]);
        if(!$res){
            throw new Exception('操作失败');
        }
        $goods_list = $this->table('ldj_order_goods')->where(['order_id'=>$order_info['order_id']])->select();

        //修改库存
        foreach($goods_list as $k=>$v){
            $res = $this->table('dian_goods')->where(['dian_id'=>$v['vid'],'goods_id'=>$v['gid']])->update(['stock'=>['exp','stock + '.$v['goods_num']]]);
            if(!$res){
                throw new Exception('操作失败');
            }
        }
        //是否需要退款
        if($order_info['order_state'] > 10 && $order_info['order_amount']>0){
                $model_pd = Model('predeposit');
                $data_pd = array();
                $data_pd['member_id'] = $order_info['buyer_id'];
                $data_pd['member_name'] = $order_info['buyer_name'];
                $data_pd['amount'] = $order_info['order_amount'];
                $data_pd['order_sn'] = $order_info['order_sn'];
                $model_pd->changePd('order_cancel',$data_pd);
        }

    }
    /* 取订单列表
     * $condition 条件
     * return array
     */
    public function order_list($condition,$field='*',$page='10',$order='order_id desc')
    {
        return $this->table('ldj_order')->where($condition)->field($field)->order($order)->page($page)->select();
    }
    /* 取一条订单信息
     * $condition 条件
     * return array
     */
    public function order_info($condition,$field='*')
    {
        return $this->table('ldj_order')->where($condition)->field($field)->find();
    }
    /*
     * 修改订单信息
     * $condition 条件
     * $update 数据
     */
    public function editOrder($condition,$update)
    {
        return $this->table('ldj_order')->where($condition)->update($update);
    }

}