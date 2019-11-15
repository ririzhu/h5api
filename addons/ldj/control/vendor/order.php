<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/10/19
 * Time: 22:00
 */
class orderCtl{
    private $vendor_info;
    private $order_state = ['0'=>'已取消','10'=>'未付款','20'=>'已付款','30'=>'已发货','40'=>'已完成'];
    private $express_type = ['1'=>'门店自取','2'=>'门店配送','3'=>'达达快递'];
    public function __construct(){
        $this->checkToken();
    }
    /**
     * @api {get} index.php?app=order&mod=order_list&sld_addons=ldj 订单列表
     * @apiVersion 0.1.0
     * @apiName order_list
     * @apiGroup Admin
     * @apiDescription 订单列表
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/vendor/index.php?app=order&mod=order_list&sld_addons=ldj
     * @apiParam {Number} pageSize 当前显示页数
     * @apiParam {Number} currentPage 第几页
     * @apiParam {String} order_sn 订单号 搜索条件 可选
     * @apiParam {String} dian_name 店铺名称id 搜索条件 可选
     * @apiParam {String} start_time 开始时间 搜索条件 可选 格式:2018-10-20
     * @apiParam {String} end_time 结束时间 搜索条件 可选 格式:2018-10-20
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} data 订单列表
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200,
     *           "data": [
     *                           {
     *                               "order_id": "20",
     *                               "add_time": "1539766698",
     *                               "store_name": "肯德基徐家汇店",
     *                               "buyer_name": "13031159513",
     *                               "goods_amount": "1320.00",
     *                               "order_amount": "1320.00",
     *                               "shipping_fee": "0.00",
     *                               "payment_code": "alipay",
     *                               "order_state": "已付款",
     *                               "payment_name": "支付宝"
     *                           },
     *                           {
     *                               "order_id": "21",
     *                               "add_time": "1539766840",
     *                               "store_name": "肯德基徐家汇店",
     *                               "buyer_name": "13031159513",
     *                               "goods_amount": "1320.00",
     *                               "order_amount": "1320.00",
     *                               "shipping_fee": "0.00",
     *                               "payment_code": "alipay",
     *                               "order_state": "已付款",
     *                               "payment_name": "支付宝"
     *                           },
     *                           {
     *                               "order_id": "24",
     *                               "add_time": "1539864429",
     *                               "store_name": "肯德基徐家汇店",
     *                               "buyer_name": "13031159513",
     *                               "goods_amount": "130.00",
     *                               "order_amount": "130.00",
     *                               "shipping_fee": "0.00",
     *                               "payment_code": "",
     *                               "order_state": "未付款",
     *                               "payment_name": null
     *                           }
     *                   ],
     *           "pagination": {
     *                   "current": 3,
     *                   "pageSize": 5,
     *                   "total": "13"
     *               }
     *
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "暂无相关数据"
     *      }
     *
     */
    public function order_list()
    {
        //分页检索条件(如果传值 按照传的值 否则默认10页)
        $pageSize = intval($_GET['pageSize'] > 0 ? $_GET['pageSize'] : 10);
        $_GET['pn'] = intval($_GET['currentPage'] > 0 ? $_GET['currentPage'] : 1);
        $order_model = M('ldj_order','ldj');
        $condition = [];
        $search = [];
        if(isset($_GET['order_sn']) && !empty($_GET['order_sn'])){
            $search['order_sn'] = trim($_GET['order_sn']);
            $condition['order_sn'] = trim($_GET['order_sn']);
        }
        if(isset($_GET['dian_name']) && !empty($_GET['dian_name'])){
            $search['vid'] = intval($_GET['dian_name']);
            $condition['vid'] = intval($_GET['dian_name']);
        }
//        if(isset($_GET['start_time']) && !empty($_GET['start_time'])){
//            $condition['add_time'] = ['gt',strtotime(trim($_GET['start_time']))];
//        }
//        if(isset($_GET['end_time']) && !empty($_GET['end_time'])){
//            $condition['add_time'] = ['lt',strtotime(trim($_GET['end_time']))];
//        }
        if(isset($_GET['start_time']) && !empty($_GET['start_time']) && isset($_GET['end_time']) && !empty($_GET['end_time'])){
            $search['start_time'] = trim($_GET['start_time']);
            $search['end_time'] = trim($_GET['end_time']);
            $condition['add_time'] = ['time',[strtotime(trim($_GET['start_time'])),strtotime(trim($_GET['end_time']))]];
        }
//        $order_list = $order_model->order_list($condition,'order_id,add_time,store_name,store_name,buyer_name,goods_amount,order_amount,shipping_fee,payment_code,order_state',$pageSize);
        $allpage = $order_model->table('ldj_order')->where($condition)->limit(false)->count();
        $order_list = $order_model->table('ldj_order')->where($condition)->field('order_id,order_sn,add_time,store_name,store_name,buyer_name,goods_amount,order_amount,shipping_fee,payment_code,order_state')->limit($pageSize*($_GET['pn']-1).','.$pageSize)->order('order_id desc')->select();
        if(!$order_list){
            echo json_encode([
                'status'=>200,
                'msg'=>'暂无相关数据',
                'data'=>[
                    'list'=>[],
                    'pagination'=>[
                        'current' => $_GET['pn'],
                        'pageSize' =>$pageSize,
                        'total' => $allpage
                    ],
                    'searchlist'=>$search
                ]
            ]);die;
        }
        $payment =  $order_model->table('ldj_payment')->where(1)->field('payment_code,payment_name')->key('payment_code')->select();
        array_walk($order_list,function(&$v)use($payment){
            $v['add_time_str'] =date('Y-m-d H:i:s',$v['add_time']);
            if(empty($v['payment_code'])){
                $v['payment_name'] = '——';
            }else{
                $v['payment_name'] = $payment[$v['payment_code']]['payment_name'];
            }
            $v['order_state'] = $this->order_state[$v['order_state']];

        });
        echo json_encode([
            'status'=>200,
            'data'=>[
                'list'=>$order_list,
                'pagination'=>[
                    'current' => $_GET['pn'],
                    'pageSize' =>$pageSize,
                    'total' => $allpage
                ],
                'searchlist'=>$search
            ]
        ]);die;
//        echo json_encode(array('data' =>$order_list, 'pagination' => array()));die;
    }
    /**
     * @api {get} index.php?app=order&mod=order_desc&sld_addons=ldj 订单详情
     * @apiVersion 0.1.0
     * @apiName order_desc
     * @apiGroup Admin
     * @apiDescription 订单详情
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/vendor/index.php?app=order&mod=order_desc&sld_addons=ldj
     * @apiParam {Number} order_id 订单id
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} data 门店信息
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200,
     *         "data": {
                            "order_id": "3",
                            "buyer_name": "13031159513",
                            "order_sn": "1000000000001401",
                            "add_time": "2018-10-15 21:22:45",
                            "payment_code": "alipay",
                            "finnshed_time": "",
                            "express_type": "门店自取",
                            "goods_amount": "2340.00",
                            "order_amount": "2365.00",
                            "shipping_fee": "25.00",
                            "payname": "支付宝",
                            "real_name": "郭萧凯",
                            "order_message": "下次还会买",
                            "member_phone": "13238866960",
                            "reciver_info": {
                                    "address": "北京 北京市 东城区&nbsp;北京市海淀区西小口东升科技园",
                                    "phone": "13031159513"
                                },
                            "goods_list": [
                                    {
                                        "rec_id": "1",
                                        "order_id": "3",
                                        "gid": "1516",
                                        "goods_name": "商品测试0036",
                                        "goods_price": "120.00",
                                        "goods_yongjin": "0.00",
                                        "goods_num": "3",
                                        "goods_image": "http://ldj.55jimu.com/data/upload/mall/store/goods/8/8_05798949344031244_240.jpg",
                                        "goods_pay_price": "360.00",
                                        "vid": "26",
                                        "buyer_id": "243",
                                        "commis_rate": "0"
                                    },
                                    {
                                        "rec_id": "2",
                                        "order_id": "3",
                                        "gid": "1514",
                                        "goods_name": "商品测试0034",
                                        "goods_price": "220.00",
                                        "goods_yongjin": "0.00",
                                        "goods_num": "3",
                                        "goods_image": "http://ldj.55jimu.com/data/upload/mall/store/goods/8/8_05798948660770670_240.jpg",
                                        "goods_pay_price": "660.00",
                                        "vid": "26",
                                        "buyer_id": "243",
                                        "commis_rate": "0"
                                    },
                                    {
                                        "rec_id": "3",
                                        "order_id": "3",
                                        "gid": "1515",
                                        "goods_name": "商品测试0035",
                                        "goods_price": "440.00",
                                        "goods_yongjin": "0.00",
                                        "goods_num": "3",
                                        "goods_image": "http://ldj.55jimu.com/data/upload/mall/store/goods/8/8_05798948980385436_240.jpg",
                                        "goods_pay_price": "1320.00",
                                        "vid": "26",
                                        "buyer_id": "243",
                                        "commis_rate": "0"
                                    }
                                ]
     *                 }
     *
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "找不到订单信息"
     *      }
     *
     */
    public function order_desc()
    {
        $order_id = intval($_GET['order_id']);
        $order_model = M('ldj_order','ldj');
        $payment =  $order_model->table('ldj_payment')->where(1)->field('payment_code,payment_name')->key('payment_code')->select();
        $condition = [
            'order_id'=>$order_id
        ];
        $order_info = $order_model->order_info($condition,$field='order_id,buyer_name,order_sn,add_time,payment_code,finnshed_time,express_type,goods_amount,order_amount,shipping_fee,order_state,pay_sn,store_name');
        if(!$order_info){
            echo json_encode(['status'=>255,'msg'=>'找不到订单信息']);die;
        }
        $order_info['add_time'] = date('Y-m-d H:i:s',$order_info['add_time']);
        $order_info['finnshed_time'] = $order_info['finnshed_time']?date('Y-m-d H:i:s',$order_info['finnshed_time']):'';
        $order_info['payname'] = $payment[$order_info['payment_code']]['payment_name'];
        $order_info['express_type'] = $this->express_type[$order_info['express_type']];
        $order_info['order_state'] = $this->order_state[$order_info['order_state']];
//        $order_info['payment_code'] = $order_info['payment_code']?:'——';
        //订单common表信息
        $order_common_info = $order_model->table('ldj_order_common')->where(['order_id'=>$order_info['order_id']])->find();
        $order_info['real_name'] = $order_common_info['reciver_name'];
        $order_info['order_message'] = $order_common_info['order_message'];
        $order_info['member_phone'] = $order_common_info['member_phone']?:unserialize($order_common_info['reciver_info'])['phone'];
        $order_info['reciver_info'] = unserialize($order_common_info['reciver_info']);
        //商品信息
        $order_goods = $order_model->table('ldj_order_goods')->where(['order_id'=>$order_info['order_id']])->select();
        array_walk($order_goods,function(&$v){
            $v['goods_image'] = cthumb($v['goods_image']);
        });
        $order_info['goods_list'] = $order_goods;
        echo json_encode(['status'=>200,'data'=>$order_info]);die;
    }
    // 校验token
    public function checkToken()
    {
        $check_flag = true;
        // 校验token
        $token = $_REQUEST['token'];

        $model_bwap_vendor_token = Model('bwap_vendor_token');
        $bwap_vendor_token_info = $model_bwap_vendor_token->getSellerTokenInfoByToken($token);
        if (empty($bwap_vendor_token_info)) {
            $check_flag = false;
        }

        $model_vendor = Model('vendor');
        $this->vendor_info = $model_vendor->getStoreInfo(array('member_id'=>$bwap_vendor_token_info['seller_id']));
        if(empty($this->vendor_info)) {
            $check_flag = false;
        } else {
            $this->vendor_info['token'] = $bwap_vendor_token_info['token'];
        }

        if (!$check_flag) {
            $state = 266;
            $data = '';
            $message = Language::get('请登录');
            $return_last = array(
                'state' => $state,
                'data' => $data,
                'msg' => $message,
            );

            echo json_encode($return_last);exit;
        }
    }
}
