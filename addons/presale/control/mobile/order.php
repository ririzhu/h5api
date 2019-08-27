<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/11/30
 * Time: 11:50
 */
class orderCtl extends mobileMemberCtl{
    private $order_model;
    private $model;
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
        if(!(C('promotion_allow')==1 && C('sld_presale_system') && C('pin_presale_isuse'))){
            echo json_encode(['status'=>255,'msg'=>'当前活动尚未开启']);die;
        }
        $this->order_model = M('pre_order','presale');
        $this->model = model();
        parent::__construct();
    }
    /**
     * @api {get} index.php?app=order&mod=pre_order_list&sld_addons=presale 预售订单列表
     * @apiVersion 0.1.0
     * @apiName pre_order_list
     * @apiGroup Presale
     * @apiDescription 预售订单列表
     * @apiExample 请求地址:
     * curl -i http://site7.55jimu.com/cmobile/index.php?app=order&mod=pre_order_list&sld_addons=presale
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
                "order_id": "66",
                "vid": "8",
                "store_name": "商联达家居店",
                "goods_name": "11月规格测试 a d",
                "goods_image": "http://site7.55jimu.com/data/upload/mall/store/goods/8/8_05955244440880378_240.png",
                "goods_price": "1.00",
                "goods_num": "3",
                "order_state": "10",
                "gid": "2045",
                "shop_order_state": null,
                "shop_order_id": null,
                "pre_id": "299",
                "goods_spec": {
                    "380": "a",
                    "383": "d"
                },
                "order_state_str": "请在2019-09-12 00:00前支付定金",
                "order_send_str": "",
                "ding": 1,
                "finish": 0
            },
            {
                "order_id": "65",
                "vid": "8",
                "store_name": "商联达家居店",
                "goods_name": "11月规格测试 a c",
                "goods_image": "http://site7.55jimu.com/data/upload/mall/store/goods/8/8_05955244440880378_240.png",
                "goods_price": "1.00",
                "goods_num": "3",
                "order_state": "20",
                "gid": "2044",
                "shop_order_state": null,
                "shop_order_id": null,
                "pre_id": "299",
                "goods_spec": {
                    "380": "a",
                    "382": "c"
                },
                "order_state_str": "2019-09-12 00:00开始支付尾款",
                "order_send_str": "",
                "ding": 0,
                "finish": 0
            },
            {
                "order_id": "64",
                "vid": "8",
                "store_name": "商联达家居店",
                "goods_name": "11月规格测试 a c",
                "goods_image": "http://site7.55jimu.com/data/upload/mall/store/goods/8/8_05955244440880378_240.png",
                "goods_price": "1.00",
                "goods_num": "3",
                "order_state": "0",
                "gid": "2044",
                "shop_order_state": null,
                "shop_order_id": null,
                "pre_id": "299",
                "goods_spec": {
                    "380": "a",
                    "382": "c"
                },
                "order_state_str": "支付超时,订单失效",
                "order_send_str": "",
                "ding": 0,
                "finish": 0
            },
            {
                "order_id": "63",
                "vid": "8",
                "store_name": "商联达家居店",
                "goods_name": "11月规格测试 a c",
                "goods_image": "http://site7.55jimu.com/data/upload/mall/store/goods/8/8_05955244440880378_240.png",
                "goods_price": "1.00",
                "goods_num": "3",
                "order_state": "0",
                "gid": "2044",
                "shop_order_state": null,
                "shop_order_id": null,
                "pre_id": "299",
                "goods_spec": {
                    "380": "a",
                    "382": "c"
                },
                "order_state_str": "支付超时,订单失效",
                "order_send_str": "",
                "ding": 0,
                "finish": 0
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
    public function pre_order_list()
    {
        $type = trim($_GET['type']);
        $page = intval($_GET['page'])?:10;
        $order_model = M('pre_order','presale');
        $model = $this->model;
        try{
            $condition = [
//                'pre_order.order_state'=>['neq',10],
                'pre_order.buyer_id'=>$this->member_info['member_id']
            ];
            if($type){
                $type_array = explode('_',$type);
                $order_type = $type_array[0];
                $order_state = $type_array[1];
                if($order_type == 1){
                    if($order_state == 30){
                        $condition['pre_order.order_state'] = 20;
                        $condition['presale.order_state'] = ['exp','(presale.pre_end_time + presale.pre_limit_time*3600) > '.time().' and presale.pre_end_time < '.time()];
                    }else{
                        $condition['pre_order.order_state'] = $order_state;
                    }
                }elseif($order_type == 2){
                    $condition['order.order_state'] = $order_state;
                }
            }
            $field1 = ['order_id','order_sn','vid','store_name','goods_name','goods_image','goods_price','goods_num','order_state','gid','goods_price_finish'];
            $table1 = 'pre_order';
            $field2 = ['order_state as shop_order_state','order_id as shop_order_id'];
            $table2 = '`order`';
            $field1 = array_map(function($v) use ($table1){
                return $table1.'.'.$v;
            },$field1);
            $field2 = array_map(function($v) use ($table2){
                return $table2.'.'.$v;
            },$field2);
            $field = array_merge($field1,$field2);
            array_push($field,'presale.pre_id');
            $order_list = $model->table('pre_order,order,presale')->join('left')->on('pre_order.order_id=`order`.pre_order_id,pre_order.pre_id=presale.pre_id')->where($condition)->field($field)->page($page)->order('pre_order.order_id desc')->select();
            $ismore = mobile_page($model->gettotalpage());
            foreach($order_list as $k=>$v){
                //预售信息
                $presale_info = $this->model->table('presale,pre_goods')->join('left')->on('presale.pre_id=pre_goods.pre_id')->where(['presale.pre_id'=>$v['pre_id'],'pre_goods.gid'=>$v['gid']])->find();

                //商品信息
                $goods_info = $this->model->table('goods')->where(['gid'=>$v['gid']])->field('goods_spec')->find();
                //定金与尾款
                $order_list[$k]['ding_price'] = $v['goods_price'] * $v['goods_num'];
                $order_list[$k]['wei_price'] = $v['goods_price_finish'] * $v['goods_num'];
                $order_list[$k]['goods_spec'] = unserialize($goods_info['goods_spec']);
                $order_list[$k]['goods_image'] = cthumb($v['goods_image']);
                $order_list[$k]['order_state_str'] = $this->order_state[$v['order_state']];
                $order_list[$k]['order_send_str'] = '';
                if($v['shop_order_state']){
                    if($v['shop_order_state'] == ORDER_STATE_SEND){
                        $order_common = $this->model->table('order_common')->where(['order_id'=>$v['shop_order_id']])->find();
                        $order_list[$k]['order_send_str'] = $order_common['shipping_time']?'发货时间 '.date('Y年m月d日',$order_common['shipping_time']):'';
                    }
                    $order_list[$k]['order_state_str'] = $this->shop_order_state[$v['shop_order_state']];
                }
                if($v['order_state'] == 20){
                    $order_list[$k]['ding'] = 0;
                    $order_list[$k]['finish'] = 0;
                    if(($presale_info['pre_end_time']+($presale_info['pre_limit_time']*3600)) > time() && time() > $presale_info['pre_end_time']){
                        $order_list[$k]['finish'] = 1;
                    }elseif($presale_info['pre_end_time'] > time() && $presale_info['pre_start_time'] < time()){
                        $order_list[$k]['order_state_str'] = date('Y-m-d H:i',$presale_info['pre_end_time']).'开始支付尾款';
                    }
                }
                if($v['order_state'] == 10){
                    $order_list[$k]['ding'] = 1;
                    $order_list[$k]['finish'] = 0;
                    $order_list[$k]['order_state_str'] = '请在'.date('Y-m-d H:i',$presale_info['pre_end_time']).'前支付定金';
                }
                if($v['order_state'] == 30){
                    $order_list[$k]['ding'] = 0;
                    $order_list[$k]['finish'] = 0;
                }
                if($v['order_state'] == 0){
                    $order_list[$k]['ding'] = 0;
                    $order_list[$k]['finish'] = 0;
                    $order_list[$k]['order_state_str'] = '支付超时,订单失效';
                }
            }

            echo json_encode(['status'=>200,'data'=>['list'=>$order_list,'ismore'=>$ismore]]);die;
        }catch(Exception $e){
            echo json_encode(['status'=>255,'msg'=>$e->getMessage()]);
        }
    }
    /**
     * @api {get} index.php?app=order&mod=pre_order_list&sld_addons=presale 预售订单详情
     * @apiVersion 0.1.0
     * @apiName order_desc
     * @apiGroup Presale
     * @apiDescription 预售订单详情
     * @apiExample 请求地址:
     * curl -i http://site7.55jimu.com/cmobile/index.php?app=order&mod=order_desc&sld_addons=presale
     * @apiParam {String} key 会员登录key值
     * @apiParam {Number} order_id 订单id
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} data 信息
     * @apiSuccessExample {json} 成功的例子:
{
    "status": 200,
    "data": {
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
            "is_default": "0"
        },
        "order_sn": "10000000000069ys",
        "order_state": "20",
        "add_time": "2018.11.30 22:38:13",
        "vid": "8",
        "store_name": "商联达家居店",
        "gid": "2045",
        "goods_name": "11月规格测试 a d",
        "goods_image": "http://site7.55jimu.com/data/upload/mall/store/goods/8/8_05955244440880378_240.png",
        "goods_num": "3",
        "goods_spec": {
            "380": "a",
            "383": "d"
        },
        "send_str": "",
        "ding_price": 3,
        "wei_price": 6,
        "finish": 0,
        "ding": 0,
        "state_1": "已完成",
        "state_str_1": "已付商品定金",
        "state_2": "未开始",
        "state_str_2": "2019年09月12日00:00开始支付尾款"
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
    public function  order_desc()
    {
        $order_id = intval($_GET['order_id']);
        $return_data = [];
        try {
            $order_info = $this->model->table('pre_order')->where(['order_id' => $order_id,'buyer_id'=>$this->member_info['member_id']])->find();
            if (!$order_info) {
                throw new Exception('订单不存在');
            }
            $goods_info = $this->model->table('goods')->where(['gid' => $order_info['gid']])->field('goods_spec')->find();
            $pre_info = $this->model->table('presale')->where(['pre_id' => $order_info['pre_id']])->find();
            if($order_info['order_state'] == 30){
                $shop_order = $this->model->table('order,order_common')->join('left')->on('order.order_id=order_common.order_id')->where(['order.pre_order_id' => $order_id])->field('order_common.shipping_time')->find();
            }
            $return_data['address'] =$this->model->table('address')->where(['address_id'=>$order_info['address_id'],'member_id'=>$order_info['buyer_id']])->find();
            $return_data['order_sn'] = $order_info['order_sn'];
            $return_data['order_state'] = $order_info['order_state'];
            $return_data['add_time'] = date('Y.m.d H:i:s',$order_info['add_time']);
            $return_data['vid'] = $order_info['vid'];
            $return_data['store_name'] = $order_info['store_name'];
            $return_data['gid'] = $order_info['gid'];
            $return_data['goods_name'] = $order_info['goods_name'];
            $return_data['goods_image'] = cthumb($order_info['goods_image']);
            $return_data['goods_num'] = $order_info['goods_num'];
            $return_data['goods_spec'] = unserialize($goods_info['goods_spec']);
            $return_data['send_str'] = $shop_order['shipping_time']?'发货时间 '.date('Y年m月d日',$shop_order['shipping_time']):'';
            $return_data['ding_price'] = $order_info['goods_price'] * $order_info['goods_num'];
            $return_data['wei_price'] = $order_info['goods_price_finish'] * $order_info['goods_num'];
            $return_data['finish'] = 0;
            $return_data['ding'] = 0;
            if($order_info['order_state'] == 0){
                if($order_info['first_time']){
                    $return_data['state_1'] = '已完成';
                    $return_data['state_str_1'] = '已付商品定金';
                    $return_data['state_2'] = '付款超时';
                    $return_data['state_str_2'] = date('Y-m-d H:i',$pre_info['pre_end_time']) .' - '. date('Y-m-d H:i',($pre_info['pre_end_time'] + ($pre_info['pre_limit_time']*3600))) .' 支付尾款,支付已经结束';
                }else{
                    $return_data['state_1'] = '未支付';
                    $return_data['state_str_1'] = '定金支付超时,订单自动取消';
                    $return_data['state_2'] = '未支付';
                    $return_data['state_str_2'] = '未支付定金';
                }
            }
            if($order_info['order_state'] == 10){
                if($pre_info['pre_start_time'] < time() && $pre_info['pre_end_time'] > time()){
                    $return_data['state_1'] = '待支付';
                    $return_data['state_str_1'] = '请在'.date('Y-m-d H:i',$pre_info['pre_end_time']). '前支付定金';
                    $return_data['state_2'] = '未支付';
                    $return_data['state_str_2'] = '您还未支付定金';
                    $return_data['ding'] = 1;
                }
                if($pre_info['pre_end_time'] < time()){
                    $return_data['state_1'] = '未完成';
                    $return_data['state_str_1'] = date('Y-m-d H:i',$pre_info['pre_end_time']) .' - '. date('Y-m-d H:i',($pre_info['pre_end_time'] + ($pre_info['pre_limit_time']*3600))) .' 支付定金,支付已经结束';
                    $return_data['state_2'] = '未支付';
                    $return_data['state_str_2'] = '您还未支付定金';
                }

            }
            if($order_info['order_state'] == 20){
                if($pre_info['pre_start_time'] < time() && $pre_info['pre_end_time'] > time()){
                    $return_data['state_1'] = '已完成';
                    $return_data['state_str_1'] = '已付商品定金';
                    $return_data['state_2'] = '未开始';
                    $return_data['state_str_2'] = date('Y年m月d日H:i',$pre_info['pre_end_time']).'开始支付尾款';
                }
                if($pre_info['pre_end_time'] < time() && time() < ($pre_info['pre_end_time'] + ($pre_info['pre_limit_time']*3600))){
                    $return_data['state_1'] = '已完成';
                    $return_data['state_str_1'] = '已付商品定金';
                    $return_data['state_2'] = '待支付';
                    $return_data['state_str_2'] = date('Y年m月d日 H:i',$pre_info['pre_end_time']+($pre_info['pre_limit_time']*3600)).'前支付尾款';
                    $return_data['finish'] = 1;
                }
                if(time() > ($pre_info['pre_end_time'] + ($pre_info['pre_limit_time']*3600))){
                    $return_data['state_1'] = '已完成';
                    $return_data['state_str_1'] = '已付商品定金';
                    $return_data['state_2'] = '未完成';
                    $return_data['state_str_2'] = date('Y年m月d日 H:i',$pre_info['pre_end_time']+($pre_info['pre_limit_time']*3600)).'前支付尾款,支付超时';
                }
            }
            if($order_info['order_state'] == 30){
                $return_data['state_1'] = '已完成';
                $return_data['state_str_1'] = '已付商品定金';
                $return_data['state_2'] = '已完成';
                $return_data['state_str_2'] = '已付商品尾款';
            }
            echo json_encode(['status'=>200,'data'=>$return_data]);
            die;
        } catch (Exception $e) {
            echo json_encode(['status'=>255,'msg'=>$e->getMessage()]);
            die;
        }

    }

}