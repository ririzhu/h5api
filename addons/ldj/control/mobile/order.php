<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/10/12
 * Time: 14:22
 */
class orderCtl extends mobileHomeCtl {
    private $member_info;
    private $order_state = ['0'=>'已取消','10'=>'未付款','20'=>'已付款','30'=>'已配货','40'=>'已完成'];
    private $express_type = ['1'=>'门店自取','2'=>'门店配送','3'=>'达达快递'];
    public function __construct(){
        parent::__construct();
        if(!C('sld_ldjsystem') || !C('ldj_isuse') || !C('dian') || !C('dian_isuse')){
            echo json_encode(['status'=>255,'msg'=>'当前模块未开启']);die;
        }
        $model_mb_user_token = Model('mb_user_token');
        if($_POST['key']){
            $key = trim($_POST['key']);
        }else{
            $key = trim($_GET['key']);
        }
        if(!$key){
            echo json_encode(['status'=>266,'msg'=>Language::get('请登录')]);die;
        }
        $mb_user_token_info = $model_mb_user_token->getMbUserTokenInfoByToken($key);
        if (empty($mb_user_token_info)) {
            echo json_encode(['status'=>266,'msg'=>Language::get('请登录')]);die;
        }

        $model_member = Model('member');
        $member_info = $model_member->getMemberInfoByID($mb_user_token_info['member_id']);
        if(empty($member_info)) {
            echo json_encode(['status'=>266,'msg'=>Language::get('请登录')]);die;
        } else {
            unset($member_info['member_passwd']);
            //读取卖家信息
            $this->member_info = $member_info;
        }
    }
    /**
     * @api {post} index.php?app=order&mod=confirm&sld_addons=ldj 订单确认页接口
     * @apiVersion 0.1.0
     * @apiName confirm
     * @apiGroup Order
     * @apiDescription 订单确认页接口
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/cmobile/index.php?app=order&mod=confirm&sld_addons=ldj
     * @apiParam {String} key 会员登录key值
     * @apiParam {Number} type 下单类型:1购物车下单,2直接门店结算
     * @apiParam {String} cart_id 购物车id,格式逗号分隔:如1,5,12
     * @apiParam {Number} dian_id 店铺id(type为2时必填)
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} data 下单页数据
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200,
     *         "data": {
                                "cart_list": [
                                        {
                                                "cart_id": "7",
                                                "buyer_id": "243",
                                                "vid": "26",
                                                "store_name": "江飞的小店",
                                                "gid": "1516",
                                                "goods_name": "商品测试0036",
                                                "goods_price": "120.00",
                                                "goods_num": "3",
                                                "goods_image": "http://ldj.55jimu.com/data/upload/mall/store/goods/8/8_05798949344031244_240.jpg"
                                        },
                                        {
                                                "cart_id": "10",
                                                "buyer_id": "243",
                                                "vid": "26",
                                                "store_name": "江飞的小店",
                                                "gid": "1514",
                                                "goods_name": "商品测试0034",
                                                "goods_price": "220.00",
                                                "goods_num": "3",
                                                "goods_image": "http://ldj.55jimu.com/data/upload/mall/store/goods/8/8_05798948660770670_240.jpg"
                                        },
                                        {
                                                "cart_id": "12",
                                                "buyer_id": "243",
                                                "vid": "26",
                                                "store_name": "江飞的小店",
                                                "gid": "1515",
                                                "goods_name": "商品测试0035",
                                                "goods_price": "440.00",
                                                "goods_num": "3",
                                                "goods_image": "http://ldj.55jimu.com/data/upload/mall/store/goods/8/8_05798948980385436_240.jpg"
                                        }
                                ],
                                "goods_all_price": 2340,//商品总价格
                                "dian_info": //门店信息
                                        {
                                                    "dian_id": "26",
                                                    "dian_name": "江飞的小店",
                                                    "kuaidi": 1,//是否支持快递送门
                                                    "shangmen": 1//是否支持上门取货
                                        },
                                "estimatedTime": {//时间选择
                                            "first_day": [],
                                            "sencond_day": [
                                                    "08:00-09:00",
                                                    "09:00-10:00",
                                                    "10:00-11:00",
                                                    "11:00-12:00",
                                                    "12:00-13:00",
                                                    "13:00-14:00",
                                                    "14:00-15:00",
                                                    "15:00-16:00",
                                                    "16:00-17:00",
                                                    "17:00-18:45"
                                            ]
                                },
                                "address": {//会员地址
                                        "address_id": "106",
                                        "member_id": "243",
                                        "true_name": "郭萧凯",
                                        "area_id": "37",
                                        "city_id": "36",
                                        "area_info": "北京 北京市 东城区",
                                        "address": "北京市海淀区西小口东升科技园",
                                        "tel_phone": null,
                                        "mob_phone": "13031159513",
                                        "is_default": "1"
                                },
                                "freight_money": "5.00",//根据地址计算运费
                                "error_area_state": 1,//地址异常状态,为1时不可下单
                                "error_area_msg": "当前位置不支持配送"
                                "error_cart_state" = 1;//购物车异常状态,为1时不可下单
                                "error_cart_msg" = '差3元起送';
     *                 }
     *
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "不能同时结算两个门店的商品"
     *      }
     *      /
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *
     */
    public function confirm()
    {
        $order_model = M('ldj_order','ldj');
        $cart_model = M('ldj_cart','ldj');
        $dian_model = M('ldj_dian','ldj');
        //购买权限判定
        if(!$this->member_info['is_buy']){
            echo json_encode(['status'=>255,'msg'=>'您没有购买权限,请联系平台管理员']);die;
        }

        if($_POST['type'] == 1){

            $cartids = trim($_POST['cart_id'],',');
            if(!$cartids){
                echo json_encode(['status'=>255,'msg'=>'购物车无数据']);die;
            }
            //验证购物车是否来自同一个店铺
            $isvid = $cart_model->table('ldj_cart')->where(['cart_id'=>['in',$cartids]])->group('vid')->select();
            if(count($isvid) != 1){
                echo json_encode(['status'=>255,'msg'=>'不能同时结算两个门店的商品']);die;
            }
            $confirm_data = $order_model->buystep1($cartids,$this->member_info['member_id'],$isvid[0]['vid']);

        }elseif($_POST['type'] == 2){
            $vid = intval($_POST['dian_id']);
            //组装购物车数据
            $cart_list = $cart_model->getVendorCartlist(['vid'=>$vid]);
            if(!$cart_list){
                echo json_encode(['status'=>255,'msg'=>'购物车无数据']);die;
            }
            $cart_id = implode(',',low_array_column($cart_list,'cart_id'));
            $confirm_data = $order_model->buystep1($cart_id,$this->member_info['member_id'],$vid);
        }else{
            echo json_encode(['status'=>255,'msg'=>'下单失败~~']);die;
        }

        if($confirm_data['status'] == 255){
            echo json_encode($confirm_data);die;
        }
        echo json_encode(['status'=>200,'data'=>$confirm_data]);die;
    }
    /**
     * @api {post} index.php?app=order&mod=editAreaFreight&sld_addons=ldj 会员修改地址获取运费
     * @apiVersion 0.1.0
     * @apiName editAreaFreight
     * @apiGroup Order
     * @apiDescription 会员修改地址获取运费
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/cmobile/index.php?app=order&mod=editAreaFreight&sld_addons=ldj
     * @apiParam {String} key 会员登录key值
     * @apiParam {Number} dian_id 店铺id
     * @apiParam {Number} address_id 会员地址id
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} data 门店信息
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200,
     *         "freight_money": 12
     *
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "未获取到详细地址信息"
     *      }
     *      /
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *
     */
    public function editAreaFreight()
    {
        $dian_id = intval($_POST['dian_id']);
        $address_id = intval($_POST['address_id']);
        $order_model = M('ldj_order','ldj');
        $return_data = [];
        $dian_info = $order_model->testVendorinfo($dian_id);
        if($dian_info['status'] == 255) {
            echo json_encode($dian_info);die;
        }
        $freight_money = $dian_info['ldj_delivery_order_Price'];
        $address_info = $order_model->table('ldj_address')->where(['address_id'=>$address_id])->find();
        if($address_info){
            //获取城市名
//            $city = Model()->table('area')->where(['area_id'=>$address_info['city_id']])->find();
//
//            //发起高德地图经纬度查询接口
//            $location_info = request_post('https://restapi.amap.com/v3/geocode/geo',false,['key'=>C('gaode_serverkey'),'address'=>$address_info['address'],'city'=>$city['area_name']]);
//            $location_info = json_decode($location_info,1);
            if(!empty($address_info['lng']) && !empty($address_info['lat'])){
//                $location = explode(',',$location_info['geocodes'][0]['location']);
                $distance = _distance($address_info['lng'],$address_info['lat'],$dian_info['dian_lng'],$dian_info['dian_lat']);
                //判断地址距离是否合格
                if($distance <= $dian_info['ldj_delivery_order_MinDistance']*1000){
                    $return_data['freight_money'] = $freight_money;
                }elseif($distance > $dian_info['ldj_delivery_order_MinDistance']*1000 && $distance <= $dian_info['ldj_delivery_order_MaxDistance']*1000){
                    //计算额外累加的运费,未设置累加运费不做计算
                    if($dian_info['ldj_delivery_order_PerPrice']>0){

                        $freight_money += ceil(($distance/1000)-$dian_info['ldj_delivery_order_MinDistance'])*$dian_info['ldj_delivery_order_PerPrice'];
                    }
                    $return_data['freight_money'] = $freight_money;
                }else{
                    //超出配送范围
                    echo json_encode(['status'=>255,'msg'=>'当前位置不支持配送']);die;
                }
            }else{

                //未获取到位置信息
                echo json_encode(['status'=>255,'msg'=>'未获取到详细地址信息']);die;
            }
        }else{
            //会员未设置默认地址
            echo json_encode(['status'=>255,'msg'=>'请添加收货地址']);die;
        }

        echo json_encode(['status'=>200,'freight_money'=>$return_data['freight_money']]);die;

    }
    /**
     * @api {post} index.php?app=order&mod=createorder&sld_addons=ldj 生成订单接口
     * @apiVersion 0.1.0
     * @apiName createorder
     * @apiGroup Order
     * @apiDescription 会员生成订单接口
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/cmobile/index.php?app=order&mod=createorder&sld_addons=ldj
     * @apiParam {String} key 会员登录key值
     * @apiParam {Number} dian_id 店铺id
     * @apiParam {String} cart_id 购物车id,格式逗号分隔:如1,5,12
     * @apiParam {Number} address_id 会员地址id,express_type为1时必填
     * @apiParam {Number} express_type 订单类型 1:商家自送 2:到店自取
     * @apiParam {Number} time_type:送货(自提)时间 1今天 2明天
     * @apiParam {String} time_section:时间区间字符串如:11:00-12:00
     * @apiParam {String} member_phone:预留电话,express_type为2时必填
     * @apiParam {String} order_message:订单留言
     * @apiSuccess {Number} status 状态
     * @apiSuccess {String} pay_sn 门店信息
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200,
     *         "pay_sn": 170592953765005243
     *
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "未获取到详细地址信息"
     *      }
     *      /
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *
     */
    public function createorder()
    {
        $order_model = M('ldj_order','ldj');
        $cart_id = trim($_POST['cart_id'],',');
        $param = [
            'cart_id'=>$cart_id,
            'vid'=>intval($_POST['dian_id']),
            'address_id'=>intval($_POST['address_id']),
            'express_type'=>intval($_POST['express_type']),
            'time_type'=>intval($_POST['time_type']),
            'time_section'=>trim($_POST['time_section']),
            'member_phone'=>trim($_POST['member_phone']),
            'order_message'=>trim($_POST['order_message']),
        ];
        $order = $order_model->buystep2($param,$this->member_info['member_id'],$this->member_info['member_name'],$this->member_info['member_email']);
        if($order['status'] == 255){
            echo json_encode($order);die;
        }
        echo json_encode(['status'=>200,'pay_sn'=>$order]);die;
    }
    /**
     * @api {get} index.php?app=order&mod=pay_confirm&sld_addons=ldj 支付页面
     * @apiVersion 0.1.0
     * @apiName pay_confirm
     * @apiGroup Order
     * @apiDescription 支付页面
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/cmobile/index.php?app=order&mod=pay_confirm&sld_addons=ldj
     * @apiParam {String} key 会员登录key值
     * @apiParam {String} pay_sn 支付单号
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} data 订单信息
     * @apiSuccess {Json} paymentlist 支付方式列表
     * @apiSuccess {Json} member_info 会员信息
     * @apiSuccessExample {json} 成功的例子:
            {
                status:100,
                msg:"已经支付成功"
            }
            /
            {
                "status": 200,
                "data": {
                        "order_sn": "1000000000002401",
                        "store_name": "江飞的小店",
                        "order_amount": "2365.00",
                        "add_time": "1539610381",
                        "add_time_str": "2018-10-15 21:33:01"
                },
                "paymentlist": [
                        {
                                "payment_id": "1",
                                "payment_code": "alipay",
                                "payment_name": "支付宝",
                                "payment_state": "1"
                        },
                        {
                                "payment_id": "2",
                                "payment_code": "wxpay",
                                "payment_name": "微信",
                                "payment_state": "1"
                        },
                        {
                                "payment_id": "3",
                                "payment_code": "predeposit",
                                "payment_name": "商城余额",
                                "payment_state": "1"
                        }
                    ],
                "member_info": {
                        "member_id": "243",
                        "member_name": "13031159513",
                        "member_truename": "",
                        "member_avatar": "",
                        "member_sex": "0",
                        "member_birthday": null,
                        "member_paypwd": null,
                        "member_email": "",
                        "member_email_bind": "0",
                        "member_mobile": "13031159513",
                        "member_mobile_bind": "0",
                        "member_qq": "",
                        "member_ww": null,
                        "member_login_num": "15",
                        "member_time": "1536723989",
                        "member_login_time": "1538030268",
                        "member_old_login_time": "1537970507",
                        "member_login_ip": "210.12.69.66",
                        "member_old_login_ip": "210.12.69.66",
                        "member_qqopenid": "",
                        "member_qqinfo": "",
                        "member_sinaopenid": "",
                        "member_sinainfo": "",
                        "weixin_unionid": null,
                        "weixin_info": null,
                        "member_points": "4586",
                        "available_yongjin": "0.00",
                        "freeze_yongjin": "0.00",
                        "available_predeposit": "799310.00",
                        "freeze_predeposit": "0.00",
                        "available_rc_balance": "0.00",
                        "inform_allow": "1",
                        "is_buy": "1",
                        "is_allowtalk": "1",
                        "member_state": "1",
                        "member_credit": "0",
                        "member_snsvisitnum": "0",
                        "member_areaid": null,
                        "member_cityid": null,
                        "member_provinceid": null,
                        "member_areainfo": null,
                        "member_privacy": null,
                        "member_growthvalue": "520",
                        "union_points": "0",
                        "inviter3_id": "0",
                        "inviter2_id": "0",
                        "inviter_id": "0",
                        "parent_member_id": "0",
                        "wx_openid": null,
                        "wx_nickname": null,
                        "wx_area": null
                    }
            }
     *
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "暂未开通支付方式"
     *      }
     *      /
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *
     */
    public function pay_confirm()
    {
        //购买权限判定
        if(!$this->member_info['is_buy']){
            echo json_encode(['status'=>255,'msg'=>'您没有购买权限,请联系平台管理员']);die;
        }
        $order_model = M('ldj_order','ldj');

        $payment_model = M('ldj_payment','ldj');

//        $payment_list = $payment_model->getpaymentlist(['payment_state'=>1]);
        $is_weixin = $_GET['is_weixin'];

        if($is_weixin){
            $client = 'h5_weixin';
        }else{
            $client = 'h5_brower';
        }
        if(isset($_GET['client']) && !empty($_GET['client'])){
            $client = $_GET['client'];
        }
//        $client = 'h5_brower';
        $payment_list = $this->getPaymentList($client,'product_buy');
//        dd($payment_list);die;
//        foreach($payment_list as $k=>$v){
//            unset($payment_list[$k]['payment_config']);
//        }
        if(!$payment_list){
            echo json_encode(['status'=>255,'暂未开通支付方式']);die;
        }

        $pay_sn = trim($_GET['pay_sn']);
        $order_info = $order_model->order_info(['pay_sn'=>$pay_sn]);
        if(!$order_info){
            echo json_encode(['status'=>255,'msg'=>'订单不存在']);die;
        }
        if($order_info['order_state'] == 20){
            echo json_encode(['status'=>100,'msg'=>'您的订单已支付']);die;
        }

        $return_data = [
            'order_sn'=>$order_info['order_sn'],
            'store_name'=>$order_info['store_name'],
            'order_amount'=>$order_info['order_amount'],
            'add_time'=>$order_info['add_time'],
            'add_time_str'=>date('Y-m-d H:i:s',$order_info['add_time']),
            'surplus_time' => (C('order_cancel_time')*60)-(time()-$order_info['add_time']),
        ];

        echo json_encode(['status'=>200,'data'=>$return_data,'paymentlist'=>$payment_list,'member_info'=>$this->member_info]);die;
    }
    /**
     * @api {get} index.php?app=order&mod=pay&sld_addons=ldj 支付接口
     * @apiVersion 0.1.0
     * @apiName pay
     * @apiGroup Order
     * @apiDescription 支付接口
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/cmobile/index.php?app=order&mod=pay&sld_addons=ldj
     * @apiParam {String} key 会员登录key值
     * @apiParam {String} pay_sn 支付单号
     * @apiParam {String} pay_type 支付类型 如:predeposit/wxpay_jsapi/alipay
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} data 信息
     * @apiSuccessExample {json} 成功的例子:
     *       {
     *         status:100,
     *         msg:"已经支付成功"
     *       }
     *       /
     *       {//预存款支付成功
     *         status:200,
     *         pay_sn:170592953765005243
     *       }
     *       /
     *       {//第三方支付
     *         status:300,
     *         url:'http://ldj.55jimu.com/cmobile/index.php?app=pay&mod=pay_new&sld_addons=ldj&key=c69b4c6a9267418a3061128ba8044670&pay_sn=240593110840555243&payment_code=alipay'//此链接返回后直接跳转到这个链接,第三方支付
     *       }
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "订单不存在"
     *      }
     *      /
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *
     */
    public function pay()
    {
        $pay_type = trim($_GET['pay_type']);
        $pay_sn = trim($_GET['pay_sn']);
        $payment_model = M('ldj_payment','ldj');
        $order_model = M('ldj_order','ldj');
        $model_pd = Model('predeposit');
        $payment_list = $payment_model->getpaymentlist(['payment_state'=>1]);
        $Apayment = array_map(function($v){
            return $v['payment_code'];
        },$payment_list);
        if(!in_array($pay_type,$Apayment)){
            echo json_encode(['status'=>255,'msg'=>'支付方式未开启']);die;
        }
        $order_info = $order_model->order_info(['pay_sn'=>$pay_sn,'buyer_id'=>$this->member_info['member_id']]);
        if(!$order_info){
            echo json_encode(['status'=>255,'msg'=>'订单不存在']);die;
        }
        if($order_info['order_state'] > 10){
            echo json_encode(['status'=>100,'msg'=>'订单已完成']);die;
        }
        try{
            $order_model->begintransaction();
            //预存款支付
            if($pay_type == 'predeposit') {
                if($order_info['order_amount'] > $this->member_info['available_predeposit']){
                    echo json_encode(['status'=>255,'msg'=>'预存款余额不足']);die;
                }
                $data_pd = array();
                $data_pd['member_id'] = $this->member_info['member_id'];
                $data_pd['member_name'] = $this->member_info['member_name'];
                $data_pd['amount'] = $order_info['order_amount'];
                $data_pd['order_sn'] = $order_info['order_sn'];
                $model_pd->changePd('order_pay',$data_pd);
                //订单状态 置为已支付
                $data_order = array();
                $data_order['order_state'] = ORDER_STATE_SUCCESS;
                $data_order['payment_time'] = time();
                $data_order['finnshed_time'] = time();
                $data_order['payment_code'] = 'predeposit';
                $data_order['pd_amount'] = $order_info['order_amount'];
                $result = $order_model->editOrder(array('order_id'=>$order_info['order_id']),$data_order);
                $result1 = $order_model->table('ldj_order_pay')->where(array('pay_sn'=>$pay_sn))->update(['api_pay_state'=>1]);
                if(!$result || !$result1){
                    throw new Exception('支付失败');
                }
            }else{
                //第三支付待定
                $url = C('mobile_site_url').'/index.php?app=pay&mod=pay_new&sld_addons=ldj&key='.trim($_GET['key']).'&pay_sn='.$pay_sn.'&payment_code='.$pay_type;
                echo json_encode(['status'=>300,'url'=>$url]);die;
                $paymentList = $this->getPaymentList('h5_weixin','product_buy');
                dd($paymentList);
                die;
            }


            $order_model->commit();
            echo json_encode(['status'=>200,'pay_sn'=>$pay_sn]);die;
        }catch(Exception $e){

            $order_model->rollback();
            echo json_encode(['status'=>255,'msg'=>$e->getMessage()]);die;
        }

    }
    /*
     * 获取支付方式
     * client // app xcx h5_weixin h5_brower
     * paytype product_buy/predeposit
     */
    public function getPaymentList($client,$paytype)
    {
        // app xcx h5_weixin h5_brower
//        $client = $_GET['client'] ? trim($_GET['client']) : '';
//        $paytype = $_GET['paytype'] ? trim($_GET['paytype']) : '';

        $state = 200;
        $data = '';
        $message = '操作成功';

        if ($client && $paytype) {
            $model_payment = Model('mb_payment');
            $payment_condition['payment_state'] = 1;

            // 不允许展示的支付方式代码
            $not_allow_show_codes = array();
            switch ($paytype) {
                case 'product_buy':
                    $item_payment_code = array();
                    $not_allow_show_codes = array_merge($not_allow_show_codes, $item_payment_code);
                    break;
                case 'predeposit':
                    $item_payment_code = array('predeposit');
                    $not_allow_show_codes = array_merge($not_allow_show_codes, $item_payment_code);
                    break;
            }
            switch ($client) {
                case 'app':
                    $item_payment_code = array('mini_wxpay','wxpay_jsapi');
                    $not_allow_show_codes = array_merge($not_allow_show_codes, $item_payment_code);
                    break;
                case 'xcx':
                    $item_payment_code = array('weixin','wxpay_jsapi','alipay');
                    $not_allow_show_codes = array_merge($not_allow_show_codes, $item_payment_code);
                    break;
                case 'h5_weixin':
                    $item_payment_code = array('weixin','mini_wxpay','alipay');
                    $not_allow_show_codes = array_merge($not_allow_show_codes, $item_payment_code);
                    break;
                case 'h5_brower':
                    $item_payment_code = array('weixin','mini_wxpay','wxpay_jsapi');
                    $not_allow_show_codes = array_merge($not_allow_show_codes, $item_payment_code);
                    break;
            }
            $payment_condition['payment_code'] = array("NOT IN",$not_allow_show_codes);
            $payment_list = $model_payment->table('ldj_payment')->where($payment_condition)->select();
        }else{
            $payment_list = array();
        }
        if(!empty($payment_list)){
            foreach ($payment_list as $key => $val){
                unset($payment_list[$key]['payment_config']);
                // 小程序支付及微信APP支付
                $weixin_payment_codes = array('mini_wxpay','weixin');
                if (in_array($val['payment_code'],$weixin_payment_codes)) {
                    $payment_list[$key]['payment_name'] = '微信支付';
                }
            }
            $data = $payment_list;
        }else{
            $state = 255;
            $message = Language::get('没有数据');
        }

        $return_last = array(
            'state' => $state,
            'data' => $data,
            'msg' => $message,
        );
        return $return_last;
//        echo json_encode($return_last);

    }
    /**
     * @api {get} index.php?app=order&mod=pay_ok&sld_addons=ldj 支付成功页面
     * @apiVersion 0.1.0
     * @apiName pay_ok
     * @apiGroup Order
     * @apiDescription 支付成功页面
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/cmobile/index.php?app=order&mod=pay_ok&sld_addons=ldj
     * @apiParam {String} key 会员登录key值
     * @apiParam {String} pay_sn 支付单号
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} data 信息
     * @apiSuccessExample {json} 成功的例子:
     *       {
     *         status:200,
     *         data:{
     *                  数据...
     *              }
     *       }
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "订单不存在"
     *      }
     *      /
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *
     */
    public function pay_ok()
    {
        $pay_sn = trim($_GET['pay_sn']);
        $order_model = M('ldj_order','ldj');
        $payment_model = M('ldj_payment','ldj');
        $order_info = $order_model->order_info(['pay_sn'=>$pay_sn,'buyer_id'=>$this->member_info['member_id']]);
        $payment_list = $payment_model->getpaymentlist(['payment_state'=>1]);
        $payment_name = '';
        foreach($payment_list as $k=>$v){
            if($v['payment_code'] == $order_info['payment_code']){
                $payment_name = $v['payment_name'];
                break;
            }
        }
        if(!$order_info){
            echo json_encode(['status'=>255,'msg'=>'订单不存在']);die;
        }
        if($order_info['order_state']==0 || $order_info['order_state']==1){
            echo json_encode(['status'=>255,'msg'=>'支付失败']);die;
        }
        $data = [
            'order_amount'=>$order_info['order_amount'],
            'dian_id'=>$order_info['store_name'],
            'add_time'=>date('Y-m-d H:i:s',$order_info['add_time']),
            'payment_name'=>$payment_name,
            'order_sn'=>$order_info['order_sn'],
        ];
        echo json_encode(['status'=>200,'data'=>$data]);die;
    }
    /**
     * @api {get} index.php?app=order&mod=order_list&sld_addons=ldj 订单列表
     * @apiVersion 0.1.0
     * @apiName order_list
     * @apiGroup Order
     * @apiDescription 订单列表
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/cmobile/index.php?app=order&mod=order_list&sld_addons=ldj
     * @apiParam {String} key 会员登录key值
     * @apiParam {Number} page 分页
     * @apiParam {Number} pn 每页显示页数
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} data 信息
     * @apiSuccess {Json} ismore 是否还有更多
     * @apiSuccessExample {json} 成功的例子:
     *       {
     *         status:200,
     *         data:{
     *                  数据...
     *              }
     *         ismore:{
     *                  "hasmore": true,
     *                  "page_total": 2
     *                }
     *       }
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "订单不存在"
     *      }
     *      /
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *
     */
    public function order_list()
    {
        $order_model = M('ldj_order','ldj');
        $page = intval($_GET['page']);
        $condition = [
            'buyer_id'=>$this->member_info['member_id'],
        ];
        $field = 'order_id,order_sn,pay_sn,store_name,vid,add_time,order_amount,order_state,express_type,chain_code';
        $order_list = $order_model->order_list($condition,$field,$page);
        $ismore = mobile_page($order_model->gettotalpage());
        foreach($order_list as $k=>$v){
            $order_goods = $order_model->table('ldj_order_goods')->where(['order_id'=>$v['order_id'],'buyer_id'=>$this->member_info['member_id']])->field('goods_image,goods_num,gid,vid')->limit(4)->select();


            array_walk($order_goods,function(&$vv)use($order_model){
                $vv['goods_image'] = cthumb($vv['goods_image'],240);
                $goods_info = $order_model->table('dian_goods')->where(['dian_id'=>$vv['vid'],'goods_id'=>$vv['gid']])->find();

                //商品状态
                $vv['goods_error'] = 0;
                $vv['goods_error_str'] = '上架中';
                if($goods_info['stock'] <= 0){
                    $vv['goods_error'] = 1;
                    $vv['goods_error_str'] = '库存不足';
                }
                if($goods_info['off'] || $goods_info['delete']){
                    $vv['goods_error'] = 1;
                    $vv['goods_error_str'] = '已下架';
                }
            });

            $order_list[$k]['goods_list'] = $order_goods;
            $order_list[$k]['goods_num'] = array_sum(low_array_column($order_goods,'goods_num'));
            $order_list[$k]['surplus_time'] = (C('order_cancel_time')*60)-(time()-$v['add_time']);
            $order_list[$k]['express_type_str'] = $this->express_type[$v['express_type']];


            $order_list[$k]['order_state_str'] = $this->order_state[$v['order_state']];
            if($v['express_type'] == 1 && $v['order_state'] == 20){
                $order_list[$k]['order_state_str'] = '等待自取';
            }
        }
        echo json_encode(['status'=>200,'data'=>$order_list,'ismore'=>$ismore]);die;
    }
    /**
     * @api {get} index.php?app=order&mod=buy_again&sld_addons=ldj 再次购买
     * @apiVersion 0.1.0
     * @apiName buy_again
     * @apiGroup Order
     * @apiDescription 再次购买
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/cmobile/index.php?app=order&mod=buy_again&sld_addons=ldj
     * @apiParam {String} key 会员登录key值
     * @apiParam {Number} order_id 订单id
     * @apiSuccess {Number} status 状态
     * @apiSuccessExample {json} 成功的例子:
     *       {
     *         status:200
     *       }
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "订单不存在"
     *      }
     *      /
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *
     */
    public function buy_again()
    {
        $order_model = M('ldj_order','ldj');
        $order_info = $order_model->order_info(['order_id'=>$_GET['order_id']]);
        if(!$order_info){
            echo json_encode(['status'=>255,'msg'=>'订单不存在']);die;
        }
        $order_goods = $order_model->table('ldj_order_goods')->where(['order_id'=>$order_info['order_id']])->select();

        try{
            $order_model->begintransaction();
            foreach($order_goods as $k=>$v){
                $res = $this->insertcart($v['gid'],$v['vid'],$v['goods_num']);
                if($res['status'] == 255){
                    throw new Exception($res['msg']);
                }
            }
            $order_model->commit();
            echo json_encode(['status'=>200]);die;
        }catch(Exception $e){
            $order_model->rollback();
            echo json_encode(['status'=>255,'msg'=>$e->getMessage()]);die;
        }



    }
    /*
     * 加入购物车
     */
    private function insertcart($gid,$dian_id,$quantity)
    {

        $cart_model = M('ldj_cart','ldj');
        $goods_model = M('ldj_goods','ldj');
        $dian_model = M('ldj_dian','ldj');
        $dian_state = $dian_model->getDianInfo(['id'=>$dian_id],$field='status,operation_time');
        //判断门店状态能否加入购物车
        $time = explode('-',date('H-i'));
        $times = $time[0]*60+$time[1];
        $operation_time = explode(',',$dian_state['operation_time']);
        if(!$dian_state['status']){
            return ['status'=>255,'msg'=>'门店休息了'];
        }
        $condition = [
            'gid'=>$gid,
            'vid'=>$dian_id,
            'buyer_id'=>$this->member_info['member_id']
        ];
        $ishascart = $cart_model->getGoodsCartInfo($condition);
        $goods_state = $goods_model->getDianGoods(['goods_id'=>$gid,'dian_id'=>$dian_id]);
        $goods_info = $goods_model->table('goods')->where(['gid'=>$gid])->find();
        $dian_info = $goods_model->table('dian')->where(['id'=>$dian_id])->find();

        if(!$goods_state || !$goods_info){
            return ['status'=>255,'msg'=>'订单内存在失效商品'];
        }
        if($goods_state['off'] || $goods_state['delete']){
            return ['status'=>255,'msg'=>'订单内存在失效商品'];
        }
        if($goods_state['stock'] < $quantity){
            return ['status'=>255,'msg'=>'订单内存在失效商品'];
        }
        if($ishascart){
            //如果购物车有数据
            //如果数量为0表示删除这条购物车
            if($quantity <= 0){
                $res = $cart_model->deletecart($condition);
            }else{
                $res = $cart_model->updatecart($condition,['goods_num'=>$quantity]);
            }
            if(!$res){
                return ['status'=>255,'msg'=>'操作失败'];
            }
        }else{
            //如果购物车没数据
            $insertdata = [
                'buyer_id'=>$this->member_info['member_id'],
                'vid'=>$dian_info['id'],
                'store_name'=>$dian_info['dian_name'],
                'gid'=>$gid,
                'goods_name'=>$goods_info['goods_name'],
                'goods_price'=>$goods_info['goods_price'],
                'goods_num'=>$quantity,
                'goods_image'=>$goods_info['goods_image']
            ];
            $res = $cart_model->insertcart($insertdata);
            if(!$res){
                return ['status'=>255,'msg'=>'操作失败'];
            }
        }

        return ['status'=>200];
    }
    /**
     * @api {get} index.php?app=order&mod=order_desc&sld_addons=ldj 订单详情
     * @apiVersion 0.1.0
     * @apiName order_desc
     * @apiGroup Order
     * @apiDescription 订单详情
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/cmobile/index.php?app=order&mod=order_desc&sld_addons=ldj
     * @apiParam {String} key 会员登录key值
     * @apiParam {Number} order_id 订单id
     * @apiSuccess {Number} status 状态
     * @apiSuccessExample {json} 成功的例子:
     *       {
     *         status:200
     *         data:{数据}
     *       }
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "订单不存在"
     *      }
     *      /
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *
     */
    public function order_desc()
    {
        $order_id = intval($_GET['order_id']);
        $order_model = M('ldj_order','ldj');
        $dian_model = M('ldj_dian','ldj');
        $condition = [
            'buyer_id'=>$this->member_info['member_id'],
            'order_id'=>$order_id,
        ];
        $field = 'order_id,order_sn,pay_sn,vid,store_name,add_time,order_amount,shipping_fee,goods_amount,order_state,express_type,chain_code,finnshed_time';
        $order_info = $order_model->order_info($condition,$field);
        if(!$order_info){
            echo json_encode(['status'=>255,'msg'=>'订单不存在']);die;
        }
            $order_common = $order_model->table('ldj_order_common')->where(['order_id'=>$order_info['order_id']])->find();
            $dian_info = $dian_model->getDianInfo(['id'=>$order_info['vid']],'*');
            $order_goods = $order_model->table('ldj_order_goods')->where(['order_id'=>$order_info['order_id'],'buyer_id'=>$this->member_info['member_id']])->field('goods_name,gid,goods_image,goods_price,goods_num,vid')->select();
            array_walk($order_goods,function(&$vv)use($order_model){

                $vv['goods_image'] = cthumb($vv['goods_image'],240);
                $goods_info = $order_model->table('dian_goods')->where(['dian_id'=>$vv['vid'],'goods_id'=>$vv['gid']])->find();
                //商品状态
                $vv['goods_error'] = 0;
                $vv['goods_error_str'] = '上架中';
                if($goods_info['stock'] <= 0){
                    $vv['goods_error'] = 1;
                    $vv['goods_error_str'] = '库存不足';
                }
                if($goods_info['off'] || $goods_info['delete']){
                    $vv['goods_error'] = 1;
                    $vv['goods_error_str'] = '已下架';
                }
            });

            $order_info['dian_logo'] = UPLOAD_SITE_URL.DS.ATTACH_PATH.DS.'dian'.DS.$dian_info['vid'].DS.$dian_info['dian_logo'];
            $order_info['member_phone'] = $order_common['member_phone'];
            $order_info['reciver_info'] = unserialize($order_common['reciver_info']);
            $order_info['start_time'] = date('Y-m-d H:i:s',$order_common['start_time']);
            $order_info['end_time'] = date('Y-m-d H:i:s',$order_common['end_time']);
            $order_info['order_message'] = $order_common['order_message'];
            $order_info['dian_phone'] = explode(',',$dian_info['dian_phone'])[0];
            $order_info['dian_lng'] = $dian_info['dian_lng'];
            $order_info['dian_lat'] = $dian_info['dian_lat'];
            $order_info['site_phone'] = C('site_phone');
            $order_info['goods_list'] = $order_goods;
            $order_info['goods_num'] = array_sum(low_array_column($order_goods,'goods_num'));
            $order_info['add_time_str'] = date('Y-m-d H:i:s',$order_info['add_time']);
            if($order_info['finnshed_time']){
                $order_info['finnshed_time'] = date('Y-m-d H:i:s',$order_info['finnshed_time']);
            }
        if($order_info['order_state'] == 10){
            $order_info['surplus_time'] = (C('order_cancel_time')*60)-(time()-$order_info['add_time']);
        }
            $order_info['express_type_str'] = $this->express_type[$order_info['express_type']];

            $order_info['order_state_str'] = $this->order_state[$order_info['order_state']];
            if($order_info['express_type'] == 1 && $order_info['order_state'] == 20){
                $order_info['order_state_str'] = '等待自取';
            }

        echo json_encode(['status'=>200,'data'=>$order_info]);die;
    }
    /**
     * @api {get} index.php?app=order&mod=order_cancel&sld_addons=ldj 取消订单
     * @apiVersion 0.1.0
     * @apiName order_cancel
     * @apiGroup Order
     * @apiDescription 取消订单
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/cmobile/index.php?app=order&mod=order_cancel&sld_addons=ldj
     * @apiParam {String} key 会员登录key值
     * @apiParam {Number} order_id 订单id
     * @apiSuccess {Number} status 状态
     * @apiSuccessExample {json} 成功的例子:
     *       {
     *         status:200
     *         'msg':'操作成功'
     *       }
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "订单不存在"
     *      }
     *      /
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *
     */
    public function order_cancel()
    {
        $order_id = intval($_GET['order_id']);
        $order_model = M('ldj_order','ldj');
        $time = C('member_stop_cancel_time');
        $condition = [
            'buyer_id'=>$this->member_info['member_id'],
            'order_id'=>$order_id,
        ];
        $order_info = $order_model->order_info($condition);
        if(!$order_info){
            echo json_encode(['status'=>255,'msg'=>'订单不存在']);die;
        }
        if($time>0){
            if($order_info['express_type']==1 && $order_info['order_state']==20 && ((time()-$order_info['payment_time'])>($time*3600))){
                echo json_encode(['status'=>255,'msg'=>'自提超过'.$time.'小时不允许取消订单']);die;
            }
        }
        if($order_info['order_state'] == 30 || $order_info['order_state'] == 50){
            echo json_encode(['status'=>255,'msg'=>'订单不能退款']);die;
        }
//        dd($order_info);
        try{
            $order_model->begintransaction();
            $order_model->cancel_order($order_info);

            $order_model->commit();
//            dd($order_info);die;
            echo json_encode(['status'=>200,'msg'=>'操作成功']);die;
        }catch(Exception $e){
            $order_model->rollback();
            echo json_encode(['status'=>255,'msg'=>$e->getMessage()]);die;
        }
    }
}