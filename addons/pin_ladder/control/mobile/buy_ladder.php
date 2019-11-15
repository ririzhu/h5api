<?php

/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/11/15
 * Time: 20:49
 */
class buy_ladderCtl extends mobileMemberCtl {
    private $order_state = [
        '0'=>'已取消',
        '10'=>'待付定金',
        '20'=>'待付尾款',
        '30'=>'已付尾款'
    ];
    private $shop_order_state = [
        '0'=>'已取消',
        '10'=>'未付款',
        '20'=>'待发货',
        '30'=>'待收货',
        '40'=>'已完成',
    ];
    public function __construct() {
        if(!(C('promotion_allow')==1 && C('sld_pintuan_ladder') && C('pin_ladder_isuse'))){
            echo json_encode(['status'=>255,'msg'=>'当前活动尚未开启']);die;
        }
        parent::__construct();
    }
    /**
     * @api {get} index.php?app=buy_ladder&mod=iwantpinladder&sld_addons=pin_ladder 我要拼团,交定金页面
     * @apiVersion 0.1.0
     * @apiName iwantpinladder
     * @apiGroup Ladder
     * @apiDescription 我要拼团,交定金页面
     * @apiExample 请求地址:
     * curl -i http://site7.55jimu.com/cmobile/index.php?app=buy_ladder&mod=iwantpinladder&sld_addons=pin_ladder
     * @apiParam {String} key 会员登录key值
     * @apiParam {Number} gid 商品id
     * @apiParam {Number} pin_id 拼团id
     * @apiParam {Number} number 商品购买数量
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} data 信息
     * @apiSuccessExample {json} 成功的例子:
{
    "status": 200,
    "data": {
        "goods_info": {
            "gid": "2048",
            "goods_name": "ceshi 红 5.0 xxl",
            "goods_price": "12.00",
            "goods_image": "http://site7.55jimu.com/data/upload/mall/store/goods/8/8_05923258395752599_240.jpg",
            "goods_spec": [
                "红",
                "5.0",
                "xxl"
            ],
            "sld_pin_id": "206",
            "sld_deposit_money": "100.00",
            "deposit_money_all": 200,
            "goods_num": 2
        },
        "address": {
            "address_id": "79",
            "member_id": "86",
            "true_name": "rrr",
            "area_id": "37",
            "city_id": "36",
            "area_info": "北京 北京市 东城区",
            "address": "2222",
            "tel_phone": null,
            "mob_phone": "15468899659",
            "is_default": "1"
        }
    }
}
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "请添加默认地址"
     *      }
     *   /
     *    {
     *          "code": 200,
     *          "login": "0",
     *          "datas": {
     *          "error": "请登录"
     *          }
     *    }
     *
     */
    public function iwantpinladder()
    {
        //购买权限判定
        if(!$this->member_info['is_buy']){
            echo json_encode(['status'=>255,'msg'=>'您没有购买权限,请联系平台管理员']);die;
        }
        $pin_model = M('pin_ladder','pin_ladder');
        $model = Model();
        //返回的数据
        $return_data = [];
        try {
            $gid = intval($_GET['gid']);
            $number = intval($_GET['number']);

            //检测商品状态
            $res = $pin_model->testGoodsState($_GET['pin_id'],$gid,$number);
            if($res['status'] == 255){
                throw new Exception($res['msg']);
            }
            //商品信息
            $return_data['goods_info'] = $res['data'];
            //用户地址
            $return_data['address'] = $model->table('address')->where(['member_id'=>$this->member_info['member_id'],'is_default'=>1])->find();
//            if(!$return_data['address']){
//                    throw new Exception('请添加默认地址');
//            }

        } catch (Exception $e) {
            echo json_encode(['status'=>255,'msg'=>$e->getMessage()]);die;
        }
        echo json_encode(['status'=>200,'data'=>$return_data]);die;
    }


    /**
     * @api {post} index.php?app=buy_ladder&mod=submitorder&sld_addons=pin_ladder 交定金下单接口
     * @apiVersion 0.1.0
     * @apiName submitorder
     * @apiGroup Ladder
     * @apiDescription 交定金下单接口
     * @apiExample 请求地址:
     * curl -i http://site7.55jimu.com/cmobile/index.php?app=buy_ladder&mod=submitorder&sld_addons=pin_ladder
     * @apiParam {String} key 会员登录key值
     * @apiParam {Number} gid 商品id
     * @apiParam {Number} number 商品购买数量
     * @apiParam {Number} pin_id 阶梯拼团id
     * @apiParam {Number} address_id 用户地址id
     * @apiParam {Number} member_message 卖家留言
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} msg 信息
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *          "status":200,
     *          "data": {
     *                      order_sn:1000056058000
     *                 }
     *      }
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "地址不存在"
     *      }
     *   /
     *    {
     *          "code": 200,
     *          "login": "0",
     *          "datas": {
     *          "error": "请登录"
     *          }
     *    }
     *
     */
    public function submitorder()
    {
        //购买权限判定
        if(!$this->member_info['is_buy']){
            echo json_encode(['status'=>255,'msg'=>'您没有购买权限,请联系平台管理员']);die;
        }
        $gid = intval($_POST['gid']);
        $number = intval($_POST['number']);
        $pin_id = intval($_POST['pin_id']);
        $address_id = intval($_POST['address_id']);
        $pin_model = M('pin_ladder','pin_ladder');
        $buy_model = M('ladder_buy','pin_ladder');
        $model = model();
        try{
            //取店铺信息
            $goods_info = $model->table('goods')->where(['gid'=>$gid])->find();
            //检测地址
            $address_info = $model->table('address')->where(['address_id'=>$address_id,'member_id'=>$this->member_info['member_id']])->find();
            if(!$address_info){
                    throw new Exception('地址不存在');
            }
            //检测用户能否购买
            $res_pay = $pin_model->testMemberBuyLadderGoods($pin_id,$gid,$this->member_info['member_id']);
            if(!$res_pay){
                    throw new Exception('不能重复团购');
            }
            //检测商品状态
            $res_array = $pin_model->testGoodsState($pin_id,$gid,$number);
            if($res_array['status'] == 255){
                throw new Exception($res_array['msg']);
            }
            $data = $res_array['data'];
            //下单支付
            $buy_model->begintransaction();
            $param = [
                'buyer_id'=>$this->member_info['member_id'],
                'buyer_name'=>$this->member_info['member_name'],
                'vid'=>$goods_info['vid'],
                'store_name'=>$goods_info['store_name'],
                'pin_id'=>$pin_id,
                'gid'=>$gid,
                'goods_name'=>$goods_info['goods_name'],
                'goods_image'=>$goods_info['goods_image'],
                'goods_num'=>$number,
                'goods_price'=>$data['sld_deposit_money'],
                'order_amount'=>$data['sld_deposit_money'] * $number,
                'order_state'=>10,
                'member_message'=>trim($_POST['member_message']),
                'address_id'=>$address_id,
                'true_name'=>$address_info['true_name'],
                'address_info'=>serialize(['address'=>$address_info['area_info'].' '.$address_info['address'],'phone'=>$address_info['mob_phone']]),
            ];
            $order_sn = $buy_model->buy_step2($param);
//            dd($extent);die;
            $buy_model->commit();
            echo json_encode(['status'=>200,'data'=>['order_sn'=>$order_sn]]);die;
        }catch(Exception $e){
            $buy_model->rollback();
            echo json_encode(['status'=>255,'msg'=>$e->getMessage()]);die;
        }
    }
    /**
     * @api {get} index.php?app=buy_ladder&mod=payment&sld_addons=pin_ladder 支付方式列表
     * @apiVersion 0.1.0
     * @apiName payment
     * @apiGroup Ladder
     * @apiDescription 支付方式列表
     * @apiExample 请求地址:
     * curl -i http://site7.55jimu.com/cmobile/index.php?app=buy_ladder&mod=payment&sld_addons=pin_ladder
     * @apiParam {String} key 会员登录key值
     * @apiParam {Number} order_sn 订单号
     * @apiParam {String} client_type 客户端类型:app,xcx,h5_weixin,h5_brower,
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} data 信息
     * @apiSuccessExample {json} 成功的例子:
{
    "status": 200,
    "data": {
        "payment": [
            {
                "payment_id": "2",
                "payment_code": "wxpay_jsapi",
                "payment_name": "微信支付",
                "payment_state": "1"
            },
            {
                "payment_id": "4",
                "payment_code": "predeposit",
                "payment_name": "余额支付",
                "payment_state": "1"
            }
        ]
    }
}
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "暂无支付方式"
     *      }
     *   /
     *    {
     *          "code": 200,
     *          "login": "0",
     *          "datas": {
     *          "error": "请登录"
     *          }
     *    }
     *
     */
    public function payment()
    {
        $order_sn  = trim($_GET['order_sn']);
        $client_type = $_GET['client_type'];
        $order_model = M('ladder_order','pin_ladder');
        $payment_model = Model('mb_payment');
        $condition = ['order_sn'=>$order_sn,'buyer_id'=>$this->member_info['member_id']];
        $order = $order_model->getone($condition);
        if(!$order){
            echo json_encode(['status'=>255,'msg'=>'订单不存在']);die;
        }
        if($order['order_state'] == 30){
            echo json_encode(['status'=>255,'msg'=>'订单已经支付']);die;
        }
        //支付信息
        $payment_info = $payment_model->getPaymentList($client_type,'product_buy');
        if($payment_info['state'] == 255){
            echo json_encode(['status'=>255,'msg'=>'暂无支付方式']);die;
        }
        $return_data['payment'] = $payment_info['data'];
        echo json_encode(['status'=>200,'data'=>$return_data]);die;
    }

    /**
     * @api {post} index.php?app=buy_ladder&mod=topay&sld_addons=pin_ladder 去支付
     * @apiVersion 0.1.0
     * @apiName topay
     * @apiGroup Ladder
     * @apiDescription 去支付
     * @apiExample 请求地址:
     * curl -i http://site7.55jimu.com/cmobile/index.php?app=buy_ladder&mod=topay&sld_addons=pin_ladder
     * @apiParam {String} key 会员登录key值
     * @apiParam {Number} order_sn 订单号
     * @apiParam {String} payment 支付代码名称
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} data 信息
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *          "status":200,
     *          "msg":"支付成功"
     *      }
     * /
     *     {
     *          "status":300,
     *          "url_param":{
     *                      app:"pay",
     *                      mod:"pay_new",
     *                      sld_addons:"pin_ladder",
     *                      order_sn:"100005645465465",
     *                      payment_code:"wxpay_jsapi",
     *                      key:"erw5fdsafjsdf4fsd",
     *              }
     *      }
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "暂无支付方式"
     *      }
     *   /
     *    {
     *          "code": 200,
     *          "login": "0",
     *          "datas": {
     *          "error": "请登录"
     *          }
     *    }
     *
     */
    public function topay()
    {
        //购买权限判定
        if(!$this->member_info['is_buy']){
            echo json_encode(['status'=>255,'msg'=>'您没有购买权限,请联系平台管理员']);die;
        }
        $buy_model = M('ladder_buy','pin_ladder');
        $model = model();
        $order_model = M('ladder_order','pin_ladder');
        $payment = trim($_POST['payment']);
        $order_sn = $_POST['order_sn'];
        try{
            $buy_model->begintransaction();
            $condition = ['order_sn'=>$order_sn,'buyer_id'=>$this->member_info['member_id']];
            $order = $order_model->getone($condition);
            if(!$order){
                throw new Exception('订单不存在');
            }
            if($order['order_state'] == 30){
                throw new Exception('订单已经支付');
            }
            //检测支付方式
            $res_payment = $model->table('mb_payment')->where(['payment_code'=>$payment,'payment_state'=>1])->find();
            if(!$res_payment){
                throw new Exception('当前支付方式未开启');
            }
            if($payment == 'predeposit'){
                //预存款支付
                $buy_model->pd_pay($order_sn,$this->member_info['member_id']);
                $buy_model->commit();
                echo json_encode(['status'=>200,'msg'=>'支付成功']);die;
            }else{
                //第三方支付
                $return = [
                    'app'=>'pay',
                    'mod'=>'pay_new',
                    'sld_addons'=>'pin_ladder',
                    'pay_sn'=>$order_sn,
                    'payment_code'=>$payment,
                    'key'=>$_POST['key']
                ];
                echo json_encode(['status'=>300,'url_param'=>$return]);die;
            }
        }catch(Exception $e){
            $buy_model->rollback();
            echo json_encode(['status'=>255,'msg'=>$e->getMessage()]);die;
        }

    }
    /**
     * @api {get} index.php?app=buy_ladder&mod=pin_order_list&sld_addons=pin_ladder 拼团订单列表
     * @apiVersion 0.1.0
     * @apiName pin_order_list
     * @apiGroup Ladder
     * @apiDescription 拼团订单列表
     * @apiExample 请求地址:
     * curl -i http://site7.55jimu.com/cmobile/index.php?app=buy_ladder&mod=pin_order_list&sld_addons=pin_ladder
     * @apiParam {String} key 会员登录key值
     * @apiParam {Number} type 订单状态:1_20已付定金;1_30待付尾款;1_0参团失败;2_20待发货;2_30已发货;2_40已完成
     * @apiParam {Number} page 每页显示多少个
     * @apiParam {Number} pn 当前页数
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} data 信息
     * @apiSuccessExample {json} 成功的例子:
{
    "status": 200,
    "data": {
        "list": [
            {
                "order_id": "13",
                "vid": "8",
                "store_name": "商联达家居店",
                "goods_name": "ceshi 蓝 5.0 xxl",
                "goods_image": "http://site7.55jimu.com/data/upload/mall/store/goods/8/8_05923258395752599_240.jpg",
                "goods_price": "200.00",
                "goods_num": "2",
                "order_state": "20",
                "order_state_str": "待付尾款"
            },
            {
                "order_id": "12",
                "vid": "8",
                "store_name": "商联达家居店",
                "goods_name": "",
                "goods_image": "http://site7.55jimu.com/data/upload/mall/common/05933657783724333_240.png",
                "goods_price": "100.00",
                "goods_num": "2",
                "order_state": "20",
                "order_state_str": "待付尾款"
            },
            {
                "order_id": "11",
                "vid": "8",
                "store_name": "商联达家居店",
                "goods_name": "",
                "goods_image": "http://site7.55jimu.com/data/upload/mall/common/05933657783724333_240.png",
                "goods_price": "100.00",
                "goods_num": "2",
                "order_state": "20",
                "order_state_str": "待付尾款"
            }
        ],
        "ismore": {
            "hasmore": false,
            "page_total": 1
        }
    }
}
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "获取失败"
     *      }
     *   /
     *    {
     *          "code": 200,
     *          "login": "0",
     *          "datas": {
     *          "error": "请登录"
     *          }
     *    }
     *
     */
    public function pin_order_list()
    {
        $type = trim($_GET['type']);
        $page = intval($_GET['page'])?:10;
        $order_model = M('ladder_order','pin_ladder');
        $model = model();
        try{
            $condition = [
                'pin_order.order_state'=>['neq',10],
                'pin_order.buyer_id'=>$this->member_info['member_id']
            ];
            if($type){
                $type_array = explode('_',$type);
                $order_type = $type_array[0];
                $order_state = $type_array[1];
                if($order_type == 1){
                    if($order_state == 30){
                        $condition['pin_order.order_state'] = 20;
                        $condition['pin_ladder.order_state'] = ['exp','(pin_ladder.sld_end_time + pin_ladder.sld_success_time*3600) > '.time().' and pin_ladder.sld_end_time < '.time()];
                    }else{
                        $condition['pin_order.order_state'] = $order_state;
                    }
                }elseif($order_type == 2){
                    $condition['order.order_state'] = $order_state;
                }
            }
            $field1 = ['order_id','vid','store_name','goods_name','goods_image','goods_price','goods_num','order_state'];
            $table1 = 'pin_order';
            $field2 = ['order_state as shop_order_state'];
            $table2 = '`order`';
            $field1 = array_map(function($v) use ($table1){
                return $table1.'.'.$v;
            },$field1);
            $field2 = array_map(function($v) use ($table2){
                return $table2.'.'.$v;
            },$field2);
            $field = array_merge($field1,$field2);
            $order_list = $model->table('pin_order,order,pin_ladder')->join('left')->on('pin_order.order_id=`order`.pin_order_id,pin_order.pin_id=pin_ladder.id')->where($condition)->field($field)->page($page)->order('pin_order.order_id desc')->select();
            $ismore = mobile_page($model->gettotalpage());
            foreach($order_list as $k=>$v){
                    $order_list[$k]['goods_image'] = cthumb($v['goods_image']);
                    $order_list[$k]['order_state_str'] = $this->order_state[$v['order_state']];
                    if($v['shop_order_state']){
                        $order_list[$k]['order_state_str'] = $this->shop_order_state[$v['shop_order_state']];
                    }
            }
            echo json_encode(['status'=>200,'data'=>['list'=>$order_list,'ismore'=>$ismore]]);die;
        }catch(Exception $e){
            echo json_encode(['status'=>255,'msg'=>$e->getMessage()]);
        }
    }


    /**
     * @api {get} index.php?app=buy_ladder&mod=order_desc&sld_addons=pin_ladder 拼团订单详情
     * @apiVersion 0.1.0
     * @apiName order_desc
     * @apiGroup Ladder
     * @apiDescription 拼团订单详情
     * @apiExample 请求地址:
     * curl -i http://site7.55jimu.com/cmobile/index.php?app=buy_ladder&mod=order_desc&sld_addons=pin_ladder
     * @apiParam {String} key 会员登录key值
     * @apiParam {Number} order_id 订单id
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} data 信息
     * @apiSuccessExample {json} 成功的例子:
{
    "status": 200,
    "data": {
        "ladder_price": [
            {
                "id": "23",
                "pin_goods_id": "243",
                "pin_id": "206",
                "gid": "2048",
                "people_num": "1",
                "pay_money": "1.00"
            },
            {
                "id": "24",
                "pin_goods_id": "244",
                "pin_id": "206",
                "gid": "2049",
                "people_num": "1",
                "pay_money": "1.00"
            }
        ],
        "guding": {
            "address_info": {
                "address": "北京 北京市 东城区 2222",
                "phone": "15468899659"
            },
            "true_name": "gxk",
            "order_sn": "10000000000013jt",
            "order_state": "30",
            "add_time": "2018-11-20 10:23:53",
            "finish_time": "",
            "send_time": "",
            "vid": "8",
            "store_name": "商联达家居店",
            "goods_image": "http://site7.55jimu.com/data/upload/mall/store/goods/8/8_05923258395752599_240.jpg",
            "goods_name": "ceshi 蓝 5.0 xxl",
            "goods_spec": {
                "389": "5.0",
                "390": "xxl",
                "391": "蓝"
            },
            "goods_danmai_price": "12.00"
        },
        "change": {
            "dangqian_price": "1.00",
            "yijing_pin_num": 1,
            "order_state_str": "已付尾款",
            "jieduan_1_price": 400,
            "jieduan_1_str": "已完成",
            "jieduan_2_price": 0,
            "jieduan_2_str": "已完成",
            "ding_dan_he_ji": 400
        }
    }
}
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "订单不存在"
     *      }
     *   /
     *    {
     *          "code": 200,
     *          "login": "0",
     *          "datas": {
     *          "error": "请登录"
     *          }
     *    }
     *
     */
    public function order_desc()
    {
        $order_id = intval($_GET['order_id']);
        $order_model = M('ladder_order','pin_ladder');
        $buy_model = M('ladder_buy','pin_ladder');
        $model = model();
        $return_data = [];
        try{
            $condition = [
                'order_id'=>$order_id,
                'buyer_id'=>$this->member_info['member_id']
            ];
            $order_info = $order_model->getone($condition);
            if(!$order_info){
                throw new Exception('订单不存在');
            }
            $shop_order_info = $order_model->getOrderInfo(['pin_order_id'=>$order_info['order_id']],array('order_common'));
            $pin_info = $model->table('pin_ladder')->where(['id'=>$order_info['pin_id']])->find();
            $goods_info = $model->table('goods')->where(['gid'=>$order_info['gid']])->field('goods_price,goods_spec')->find();
            $ladder_price = $model->table('pin_team_user_ladder')->where(['sld_pin_id'=>$order_info['pin_id'],'sld_gid'=>$order_info['gid']])->select();
            //固定数值
            $guding = [];
            $guding['address_info'] =  unserialize($order_info['address_info']);
            $guding['true_name'] =  $order_info['true_name'];
            $guding['gid'] =  $order_info['gid'];
            $guding['member_message'] =  $order_info['member_message'];
            $guding['order_sn'] =  $order_info['order_sn'];
            $guding['order_state'] =  $order_info['order_state'];
            $guding['add_time'] =  date('Y-m-d H:i:s',$order_info['add_time']);
            $guding['finish_time'] =  $order_info['finished_time']?date('Y-m-d H:i:s',$order_info['finished_time']):'';
            $guding['send_time'] =  $shop_order_info['extend_order_common']['shipping_time']?date('Y-m-d H:i:s',$shop_order_info['extend_order_common']['shipping_time']):'';
            $guding['vid'] =  $order_info['vid'];
            $guding['store_name'] =  $order_info['store_name'];
            $guding['goods_image'] =  cthumb($order_info['goods_image']);
            $guding['goods_name'] =  $order_info['goods_name'];
            $guding['goods_spec'] =  unserialize($goods_info['goods_spec']);
            $guding['goods_danmai_price'] =  $goods_info['goods_price'];
            $guding['goods_dingjin_price'] =  $order_info['goods_price'];
            $guding['goods_num'] =  $order_info['goods_num'];
            //阶梯价
            $return_data['ladder_price'] = $model->table('pin_money_ladder')->where(['pin_id'=>$order_info['pin_id']])->order('people_num asc')->select();
            //变化的东西
            $change = [];
            $price = $buy_model->getLadderPrice($order_info['pin_id'],$order_info['gid']);
            $change['dangqian_price'] = $price?:0;
            $change['yijing_pin_num'] = count($ladder_price) ;
            $change['order_state_str'] = $this->order_state[$order_info['order_state']];
            $change['dao_ji_shi'] = 0;
            if($order_info['order_state'] == 20){
                $change['jieduan_1_price'] = $order_info['goods_price'] * $order_info['goods_num'];
                $change['jieduan_1_str'] = '已完成';
                $change['jieduan_2_price'] = $price * $order_info['goods_num'];
                $change['dao_ji_shi'] = $pin_info['sld_end_time'] - time();
                $change['jieduan_2_str'] = date('m月d日 H:i:s',$pin_info['sld_end_time'] + ($pin_info['sld_success_time']*3600)) .'前支付尾款';
                $change['ding_dan_he_ji'] = $change['jieduan_1_price'] + $change['jieduan_2_price'];
            }elseif($order_info['order_state'] == 30){
                $change['order_state_str'] = $this->order_state[$order_info['order_state']];
                $change['jieduan_1_price'] = $order_info['goods_price'] * $order_info['goods_num'];
                $change['jieduan_1_str'] = '已完成';
                $change['jieduan_2_price'] = $order_info['goods_price_finish'] * $order_info['goods_num'];
                $change['jieduan_2_str'] = '已完成';
                $change['ding_dan_he_ji'] = $change['jieduan_1_price'] + $change['jieduan_2_price'];
            }elseif($order_info['order_state'] == 0){
                $change['jieduan_1_price'] = $order_info['goods_price'] * $order_info['goods_num'];
                $change['jieduan_1_str'] = '已取消';
            }
            if(time() > $pin_info['sld_end_time'] && time() <  ($pin_info['sld_end_time']+(floatval($pin_info['sld_success_time'])*60*60)) && $order_info['order_state'] == 20 && $return_data['ladder_price'][0]['people_num'] <= $change['yijing_pin_num']){
                $change['shi_fou_ke_yi_fu_wei_kuan'] = 1;
            }else{
                $change['shi_fou_ke_yi_fu_wei_kuan'] = 0;
            }
            $return_data['guding'] = $guding;
            $return_data['change'] =$change;
//            dd($return_data);die;
            echo json_encode(['status'=>200,'data'=>$return_data]);die;
        }catch(Exception $e){
            echo json_encode(['status'=>255,'msg'=>$e->getMessage()]);die;
        }
    }
    /**
     * @api {post} index.php?app=buy_ladder&mod=buy_finish&sld_addons=pin_ladder 提交尾款接口
     * @apiVersion 0.1.0
     * @apiName buy_finish
     * @apiGroup Ladder
     * @apiDescription 提交尾款接口
     * @apiExample 请求地址:
     * curl -i http://site7.55jimu.com/cmobile/index.php?app=buy_ladder&mod=buy_finish&sld_addons=pin_ladder
     * @apiParam {String} key 会员登录key值
     * @apiParam {Number} order_sn 订单号
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} data 信息
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *          "status":200,
     *          "data":{
     *              order_sn:102000056456465
     *          }
     *      }
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "订单不存在"
     *      }
     *   /
     *    {
     *          "code": 200,
     *          "login": "0",
     *          "datas": {
     *          "error": "请登录"
     *          }
     *    }
     *
     */
    public function buy_finish()
    {
        $order_sn = trim($_POST['order_sn']);
        $order_model = M('ladder_order','pin_ladder');
        $pin_model = M('pin_ladder','pin_ladder');
        $buy_model = M('ladder_buy','pin_ladder');
        $model = model();
        try{
            $condition = [
                'order_sn'=>$order_sn,
                'buyer_id'=>$this->member_info['member_id'],
            ];
            $order_info = $order_model->getone($condition);
            if(!$order_info){
                    throw new  Exception('订单不存在');
            }
            if($order_info['order_state'] == 30){
                throw new  Exception('订单已经支付');
            }
            //检测商品状态
            $res = $pin_model->testFinishGoodsState($order_info['pin_id'],$order_info['gid'],$order_info['goods_num']);
            if($res['status'] == 255){
                throw new Exception($res['msg']);
            }
            //开始计算价格
            $update = [];
            $update['goods_price_finish'] = $buy_model->getLadderPrice($order_info['pin_id'],$order_info['gid']);
            $update['order_amount'] = $update['goods_price_finish'] * $order_info['goods_num'];
            $res = $order_model->editorder( $condition,$update);
            if(!$res){
                throw new Exception('操作失败');
            }
            echo json_encode(['status'=>200,'data'=>['order_sn'=>$order_sn]]);die;
        }catch(Exception $e){
            echo json_encode(['status'=>255,'msg'=>$e->getMessage()]);die;
        }
    }
}