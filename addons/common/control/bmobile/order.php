<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/10/20
 * Time: 17:08
 */
class orderCtl extends mobileSellerCtl
{
    private $vendor_info;
    public function __construct()
    {
//        parent::__construct();

            if(!((C('sld_cashersystem') && C('cashersystem_isuse')) || (C('sld_ldjsystem') && C('ldj_isuse')))){
                echo json_encode(['status'=>255,'msg'=>'当前模块未开启']);die;
            }
        $this->checkToken();
    }
    /**
     * @api {get} index.php?app=order&mod=cash_order&sld_addons=common  收银订单
     * @apiVersion 0.1.0
     * @apiName  cash_order
     * @apiGroup App
     * @apiDescription 收银订单
     * @apiExample 请求地址:
     * curl -i http://site2.slodon.cn/bmobile/index.php?app=order&mod= cash_order&sld_addons=common
     * @apiParam {String} key 店铺登录key值
     * @apiParam {Number} page 每页显示数量
     * @apiParam {Number} pn 第几页
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} data 订单信息
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200,
     *         "data": {
     *                          订单表,订单goods表信息
     *                  }
     *                "ismore": {
     *                                "hasmore": true,
     *                                "page_total": 55
     *                           }
     *      }
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "暂无相关记录"
     *      }
     *      /
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *
     */
    public function cash_order()
    {
        $dian_id = $this->vendor_info['id'];
//        $dian_id = 15;
        $page = $_GET['page']?:10;
        $model = model();
        //订单列表
        $order_list = $model->table('cashsys_order')->where(['dian_id'=> $dian_id])->page($page)->order('order_id desc')->select();
        $ismore = mobile_page($model->gettotalpage());
        if(!$order_list){
            echo json_encode(['status'=>200,'data'=>[]]);die;
        }
        $payment = $model->table('cashsys_payment')->where(1)->key('payment_code')->field('payment_id,payment_code,payment_name')->select();
        foreach($order_list as $k=>$v){
            $order_list[$k]['add_time'] = date('Y-m-d H:i',$v['add_time']);
            $order_list[$k]['payment_time'] = $v['payment_time']?date('Y-m-d H:i',$v['payment_time']):'';
            $order_list[$k]['order_state_str'] = $v['order_state']==10?'待付款':'已完成';
            $order_list[$k]['payment_code'] = $v['payment_code']!='offline'?$payment[$v['payment_code']]['payment_name']:'待付款';
            $goods_list = $model->table('cashsys_order_goods')->where(['order_id'=>$v['order_id']])->select();
            $order_list[$k]['goods_num'] = array_sum(low_array_column($goods_list,'goods_num'));
            array_walk($goods_list,function(&$v)use($model){
                    $v['goods_image'] = cthumb($v['goods_image']);
                $goods_info = $model->table('dian_goods')->where(['dian_id'=>$v['dian_id'],'goods_id'=>$v['gid']])->find();
                //商品状态
                $v['goods_info'] = '上架中';
                if($goods_info['off'] || $goods_info['delete']){
                    $v['goods_info'] = '已下架';
                }
                if($goods_info['stock'] <= 0){
                    $v['goods_info'] = '库存不足';
                }
            });
            $order_list[$k]['goods_list'] = $goods_list;
        }
        echo json_encode(['status'=>200,'data'=>$order_list,'ismore'=>$ismore]);die;
    }
    /**
     * @api {get} index.php?app=order&mod=shoporder&sld_addons=common 商城门店订单
     * @apiVersion 0.1.0
     * @apiName shoporder
     * @apiGroup App
     * @apiDescription 商城门店订单
     * @apiExample 请求地址:
     * curl -i http://site2.slodon.cn/bmobile/index.php?app=order&mod=shoporder&sld_addons=common
     * @apiParam {String} key 店铺登录key值
     * @apiParam {Number} type 订单类型:1核销订单 2派单订单
     * @apiParam {Number} page 每页显示数量
     * @apiParam {Number} pn 第几页
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} data 订单信息
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200,
     *         "data": {
     *                          订单表,订单common表,订单goods表信息
     *                  }
     *                "ismore": {
     *                                "hasmore": true,
     *                                "page_total": 55
     *                           }
     *      }
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "暂无相关记录"
     *      }
     *      /
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *
     */
    public function shoporder()
    {
        $page = intval($_GET['page'])?:10;
        $model_order = Model('order');
        $condition = array();
        $condition['order.vid'] = $this->vendor_info['vid'];
        $condition['order.order_state'] = ['in','20,30,40'];
        if($_GET['type'] == 1){
            $condition['order.dian_id'] = $this->vendor_info['id'];
        }elseif($_GET['type'] == 2){
            $condition['order_common.pai_dian_id'] = array('exp', '  FIND_IN_SET('.$this->vendor_info['id'].',order_common.pai_dian_id)  ');
        }
        $order_list = $model_order->table('order,order_common')->join('inner')->on('order.order_id=order_common.order_id')->where($condition)->field('order.order_id,order.order_sn,order.pay_sn,order.buyer_name,order.shipping_fee,order.finnshed_time,order.add_time,order.order_amount,order.order_state,order_common.order_message,order_common.reciver_info')->order('order.order_id desc')->page($page)->select();
        $ismore = mobile_page($model_order->gettotalpage());
        if(!$order_list){
            echo json_encode([
                'status'=>200,
                'data'=>[],
            ]);die;
        }
        foreach($order_list as $k=>$v){
            if($_GET['type'] == 1){
                $order_list[$k]['express_type'] = '到店自取';
                if($v['order_state']==20 || $v['order_state']==30){
                    $order_list[$k]['order_state_str'] = '未核销';
                }elseif($v['order_state']==40){
                    $order_list[$k]['order_state_str'] = '已核销';
                }
            }elseif($_GET['type'] == 2){
                $order_list[$k]['express_type'] = '门店配送';
                if($v['order_state']==20){
                    $order_list[$k]['order_state_str'] = '未派单';
                }elseif($v['order_state']==30 || $v['order_state']==40){
                    $order_list[$k]['order_state_str'] = '已派单';
                }
            }
            $order_list[$k]['add_time'] = date('m-d H:i',$v['add_time']);
            $order_list[$k]['finnshed_time'] = date('m-d H:i',$v['finnshed_time']);
            $order_list[$k]['reciver_info'] = unserialize($v['reciver_info']);
            $order_list[$k]['reciver_info']['address'] = html_entity_decode($order_list[$k]['reciver_info']['address']);
            $goods_list = $model_order->table('order_goods')->where(['order_id'=>$v['order_id']])->field('gid,goods_name,goods_price,goods_num,goods_image')->select();
            $order_list[$k]['goods_num'] = array_sum(low_array_column($goods_list,'goods_num'));
            array_walk($goods_list,function(&$v)use($model_order){
                    $v['goods_image'] = cthumb($v['goods_image']);
                    $goods_info = $model_order->table('goods')->where(['gid'=>$v['gid']])->find();
                    //商品状态
                        $v['goods_info'] = '上架中';

                    if($goods_info['goods_storage'] <= 0){
                        $v['goods_info'] = '库存不足';
                    }
                    if($goods_info['goods_state']!=1 || $goods_info['goods_verify']!=1){
                        $v['goods_info'] = '已下架';
                    }
                    if(!$goods_info){
                        $v['goods_info'] = '已删除';

                    }
            });
            $order_list[$k]['goods_list'] = $goods_list;
        }
        echo json_encode([
            'status'=>200,
            'data'=>$order_list,
            'ismore'=>$ismore
        ]);die;
    }
    /**
     * @api {get} index.php?app=order&mod=hexiao&sld_addons=common 核销订单/分为门店订单和联到家查询
     * @apiVersion 0.1.0
     * @apiName hexiao
     * @apiGroup App
     * @apiDescription 核销订单/分为门店订单和联到家查询
     * @apiExample 请求地址:
     * curl -i http://site2.slodon.cn/bmobile/index.php?app=order&mod=hexiao&sld_addons=common
     * @apiParam {String} key 店铺登录key值
     * @apiParam {String} chain_code 核销码16位
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} data 订单信息
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200,
     *         "data": {
     *                          订单表,订单common表,订单goods表信息
     *                  }
     *      }
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "核销码错误"
     *      }
     *      /
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *
     */
    public function hexiao()
    {

        $model_order = Model('order');
        $model_order_common = M('common_order','common');
        $chain_code = trim($_GET['chain_code']);

        try{
            //判断状态
            if(!is_numeric($chain_code) || strlen($chain_code) != 16){
                throw new Exception('核销码错误');
            }
            //先判断是不是门店订单
            $order_sn = $model_order->decode($chain_code,$this->vendor_info['vid'].$this->vendor_info['id']);
            $where = [];
            $where['ziti'] = 1;
            $where['dian_id'] = $this->vendor_info['id'];
            $where['order_state'] = array('gt',10);
            $where['order_sn'] = $order_sn;
            $order_data = $model_order->getOrderInfo($where,array('order_goods','order_common'));
            if($order_data){
                $order_data['member_order_type'] = 'm';
                $order_data['member_order_type_str'] = '门店订单';
                $order_data['chain_code'] = $model_order->encode($order_data['order_sn'],$this->vendor_info['vid'].$this->vendor_info['id']);
                $order_data['add_time'] = date('m-d H:i',$order_data['add_time']);
                $order_data['should_money'] = $order_data['goods_amount'] +$order_data['shipping_fee'];
                $order_data['all_goods_num'] = array_sum(low_array_column($order_data['extend_order_goods'],'goods_num'));
                $order_data['extend_order_common']['member_phone'] = $order_data['extend_order_common']['reciver_info']['phone'];
                $order_data['extend_order_common']['start_time'] = '';
                $order_data['extend_order_common']['end_time'] = '';
                $order_data['extend_order_common']['distance'] = '0';
                foreach ($order_data['extend_order_goods'] as $k=>$v){
                    $order_data['extend_order_goods'][$k]['goods_image_url'] = thumb($v);
                }
                echo json_encode(['status'=>200,'data'=>$order_data]);die;
            }else{
                if(!C('sld_ldjsystem') || !C('ldj_isuse')){
                    throw new Exception('暂无订单记录');
                }
                //判断是不是联到家订单
                $order_sn = $model_order->decode($chain_code,'ldj');
                $where = [];
                $where['order_sn'] =  $order_sn;
                $where['vid'] =  $this->vendor_info['id'];
                $where['express_type'] =  1;
                $where['order_state'] =  20;
                $where['chain_code'] =  $chain_code;
                $order_info = $model_order_common->order_info($where,$field='*');

                if(!$order_info){
                    throw new Exception('暂无订单记录');
                }
                $order_info['member_order_type'] = 'd';
                $order_info['member_order_type_str'] = 'O2O订单';
                $order_info['should_money'] = $order_info['goods_amount'] +$order_info['shipping_fee'] ;
                $order_info['add_time'] = date('m-d H:s',$order_info['add_time']);
                $order_common_info = $model_order_common->table('ldj_order_common')->where(['order_id'=>$order_info['order_id']])->find();

                $order_common_info['reciver_info'] = unserialize($order_common_info['reciver_info']);
                $order_common_info['start_time'] = date('m-d H:i',$order_common_info['start_time']);
                $order_common_info['end_time'] = date('m-d H:i',$order_common_info['end_time']);
                $order_common_info['distance'] = round($order_common_info['distance']/1000,1);
                $order_info['extend_order_common'] = $order_common_info;
                $order_goods_list = $model_order_common->table('ldj_order_goods')->where(['order_id'=>$order_info['order_id']])->select();
                $order_info['all_goods_num'] = array_sum(low_array_column($order_goods_list,'goods_num'));
                array_walk($order_goods_list,function(&$v){
                    $v['goods_image'] = cthumb($v['goods_image']);
                });
                $order_info['extend_order_goods'] = $order_goods_list;
                echo json_encode(['status'=>200,'data'=>$order_info]);die;
            }

        }catch(Exception $e){
            echo json_encode(['status'=>255,'msg'=>$e->getMessage()]);die;
        }

    }
    /**
     * @api {get} index.php?app=order&mod=hexiao_handle&sld_addons=common 核销订单/分为门店订单和联到家
     * @apiVersion 0.1.0
     * @apiName hexiao_handle
     * @apiGroup App
     * @apiDescription 核销订单/分为门店订单和联到家
     * @apiExample 请求地址:
     * curl -i http://site2.slodon.cn/bmobile/index.php?app=order&mod=hexiao_handle&sld_addons=common
     * @apiParam {String} key 店铺登录key值
     * @apiParam {String} type m:门店,d:O2O到家
     * @apiParam {String} order_sn 订单号
     * @apiSuccess {Number} status 状态
     * @apiSuccess {String} msg 订单信息
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200,
     *         "msg": "核销成功"
     *      }
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "核销失败"
     *      }
     *      /
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *
     */
    public function hexiao_handle()
    {
        $model_order = M('common_order','common');
        try{
            //如果门店订单
            if($_GET['type'] == 'm'){
                $model_order = Model('order');
                $condition = array();
                $condition['order_sn'] = $_GET['order_sn'];
                $condition['vid'] = $this->vendor_info['vid'];
                $condition['dian_id'] = $this->vendor_info['id'];
                $order_info	= $model_order->getOrderInfo($condition);
                $model_order = Model('order');
                //检测订单是否可以核销
                $state = !$order_info['lock_state'] && ($order_info['order_state'] == ORDER_STATE_PAY || $order_info['order_state'] == ORDER_STATE_SEND);
//                $if_allow = $model_order->getOrderOperateState('hexiao',$order_info);
                if (!$state) {
                    throw new Exception('无权操作');
                }

                $update_order = array();
                $update_order['finnshed_time'] = TIMESTAMP;
                $update_order['order_state'] = ORDER_STATE_SUCCESS;
                $update = $model_order->editOrder($update_order,array('order_id'=>$order_info['order_id']));
                if (!$update) {
                    throw new Exception('核销失败');
                }

                //发放优惠券：推荐
                M('red')->SendRedInvite($order_info['buyer_id']);

            }elseif($_GET['type'] == 'd') {
                //如果联到家订单
                $condition = [
                    'order_sn'=>$_GET['order_sn'],
                    'vid'=>$this->vendor_info['id'],
                    'express_type'=>1,
                ];
                $order_info = $model_order->table('ldj_order')->where($condition)->find();
                if(!$order_info){
                    throw new Exception('订单不存在');
                }
                if($order_info['order_state'] != 20){
                    throw new Exception('订单无权限核销');
                }
                $update = [
                    'finnshed_time'=>time(),
                    'order_state'=>40,
                    'is_check'=>1
                ];
                $res = $model_order->table('ldj_order')->where($condition)->update($update);
                if(!$res){
                    throw new Exception('核销失败');
                }
            }else{
                throw new Exception('核销失败');
            }

            //记录订单日志
            $data = array();
            $data['order_id'] = $order_info['order_id'];
            $data['log_role'] = 'dian';
            $data['log_user'] = $this->vendor_info['dian_name'];
            //修改了价格
            $data['log_msg'] = '核销了订单';
            Model('order')->addOrderLog($data);

            //记录门店操作
//            $log = [
//                'content'=>'核销订单，订单编号：'.$order_info['order_sn'],
//
//            ];
//            $this->recordSellerLog($log);

        }catch(Exception $e){
            echo json_encode(['status'=>255,'msg'=>$e->getMessage()]);die;
        }
        echo json_encode(['status'=>200,'msg'=>'核销成功']);die;
    }
    /**
     * @api {get} index.php?app=order&mod=ldjAndShopOrderList&sld_addons=common 订单列表(核销栏的订单列表)
     * @apiVersion 0.1.0
     * @apiName ldjAndShopOrderList
     * @apiGroup App
     * @apiDescription 订单列表(核销栏的订单列表)
     * @apiExample 请求地址:
     * curl -i http://site2.slodon.cn/bmobile/index.php?app=order&mod=ldjAndShopOrderList&sld_addons=common
     * @apiParam {String} key 店铺登录key值
     * @apiParam {Number} type 1待核销 2:已核销
     * @apiParam {Number} page 当前页显示多少个
     * @apiParam {Number} pn 第几页
     * @apiSuccess {Number} status 状态
     * @apiSuccess {String} data 订单信息
     * @apiSuccessExample {json} 成功的例子:
                    {
                    "status": 200,
                    "data": [
                                    {
                                            "order_id": "1737",
                                            "order_sn": "1000000000172901",
                                            "add_time": "1541758684",
                                            "order_amount": "140.00",
                                            "goods_amount": "144.00",
                                            "shipping_fee": "0.00",
                                            "type": "门店订单",
                                            "add_time_str": "2018-11-09 18:18",
                                            "extend": {
                                                    "order_id": "1737",
                                                    "reciver_info": "a:2:{s:7:\"address\";s:36:\"北京 北京市 东城区&nbsp;2222\";s:5:\"phone\";s:11:\"15468899659\";}",
                                                    "reciver_name": "rrr",
                                                    "member_phone": "15468899659",
                                                    "time": "",
                                                    "distance": "0"
                                            },
                                            "hexiao_code": "0798565698567254",
                                            "all_num": 1,
                                            "should_money": 144
                                    },
                                    {
                                            "order_id": "1715",
                                            "order_sn": "1000000000170601",
                                            "add_time": "1541670578",
                                            "order_amount": "144.00",
                                            "goods_amount": "144.00",
                                            "shipping_fee": "0.00",
                                            "type": "门店订单",
                                            "add_time_str": "2018-11-08 17:49",
                                            "extend": {
                                                    "order_id": "1715",
                                                    "reciver_info": "a:2:{s:7:\"address\";s:36:\"北京 北京市 东城区&nbsp;2222\";s:5:\"phone\";s:11:\"15468899659\";}",
                                                    "reciver_name": "rrr",
                                                    "member_phone": "15468899659",
                                                    "time": "",
                                                    "distance": "0"
                                            },
                                            "hexiao_code": "0798565698565954",
                                            "all_num": 1,
                                            "should_money": 144
                                    },
                                    {
                                            "order_id": "127",
                                            "order_sn": "1000000000013801",
                                            "add_time": "1541661602",
                                            "order_amount": "89.00",
                                            "goods_amount": "89.00",
                                            "shipping_fee": "0.00",
                                            "type": "O2O订单",
                                            "add_time_str": "2018-11-08 15:20",
                                            "extend": {
                                                    "order_id": "127",
                                                    "reciver_info": "a:2:{s:7:\"address\";s:12:\"&nbsp;&nbsp;\";s:5:\"phone\";s:0:\"\";}",
                                                    "distance": 0,
                                                    "start_time": "1541664000",
                                                    "end_time": "1541667600",
                                                    "member_phone": "15784687965",
                                                    "reciver_name": null,
                                                    "time": "2018-11-08 16:00 - 2018-11-08 17:00"
                                            },
                                            "hexiao_code": null,
                                            "all_num": 1,
                                            "should_money": 89
                                    }
                    ],
                    "ismore": 0
                    }
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "核销失败"
     *      }
     *      /
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *
     */
    public function ldjAndShopOrderList()
    {
        $type = intval($_GET['type']);
        $dian_id = $this->vendor_info['id'];
        $model = model();
        $order_model = model('order');
        $return_data = [];
        try{
            $type = $type==2?2:1;
            //先计算门店订单
            $condition = [
                'dian_id'=>$dian_id,
                'vid'=>$this->vendor_info['vid'],
                'ziti'=>1
            ];
            if($type == 1){
                $condition['order_state'] = ['in',ORDER_STATE_PAY.','.ORDER_STATE_SEND];
            }else{
                $condition['order_state'] = ORDER_STATE_SUCCESS;
            }
            $shop_order_list = $model->table('order')->where($condition)->field('order_id,order_sn,add_time,order_amount,goods_amount,shipping_fee')->page($_GET['page'])->select();
            $shop_page = mobile_page($model->gettotalpage());
            if($shop_order_list){
                foreach($shop_order_list as $k=>$v){
                    $shop_order_list[$k]['type'] = '门店订单';
                    $shop_order_list[$k]['add_time_str'] = date('m-d H:i',$v['add_time']);
                    $order_common = $model->table('order_common')->where(['order_id'=>$v['order_id']])->field('order_id,reciver_info,reciver_name')->find();

                    $order_common['member_phone'] = unserialize($order_common['reciver_info'])['phone'];
                    $order_common['time'] = '';
                    $order_common['distance'] = '0';
                    unset($order_common['reciver_info']);
                    $shop_order_list[$k]['extend'] = $order_common;
                    $order_goods_list = $model->table('order_goods')->where(['order_id'=>$v['order_id']])->field('order_id,gid,goods_name,goods_num,goods_price,goods_image')->select();
                    array_walk($order_goods_list,function(&$vv){
                        $vv['goods_image'] = cthumb($vv['goods_image']);
                    });
                    $shop_order_list[$k]['goods_list'] = $order_goods_list;
                    $shop_order_list[$k]['hexiao_code'] = $order_model->encode($v['order_sn'],$this->vendor_info['vid'].$dian_id);
                    $shop_order_list[$k]['all_num'] = array_sum(low_array_column($order_goods_list,'goods_num'));
                    $shop_order_list[$k]['should_money'] = $v['goods_amount'] +$v['shipping_fee'];
                }
            }else{
                $shop_order_list = [];
            }
            //联到家订单
            $condition = [
                'vid'=>$dian_id,
                'express_type'=>1
            ];
            if($type == 1){
                $condition['order_state'] = ['in',ORDER_STATE_PAY.','.ORDER_STATE_SEND];
            }else{
                $condition['order_state'] = ORDER_STATE_SUCCESS;
            }
            $ldj_order_list = $model->table('ldj_order')->where($condition)->field('order_id,order_sn,add_time,order_amount,goods_amount,shipping_fee')->page($_GET['page'])->select();
            $ldj_page = mobile_page($model->gettotalpage());
            if($ldj_order_list){
                foreach($ldj_order_list as $k=>$v){
                    $ldj_order_list[$k]['type'] = 'O2O订单';
                    $ldj_order_list[$k]['add_time_str'] = date('m-d H:i',$v['add_time']);
                    $order_goods_list = $model->table('ldj_order_goods')->where(['order_id'=>$v['order_id']])->field('order_id,gid,goods_name,goods_num,goods_price,goods_image')->select();

                    $order_common = $model->table('ldj_order_common')->where(['order_id'=>$v['order_id']])->field('order_id,reciver_info,distance,start_time,end_time,member_phone,reciver_name')->find();
                    $order_common['time'] = date('m-d H:i',$order_common['start_time']).' - '. date('m-d H:i',$order_common['end_time']);
                    $order_common['distance'] = round($order_common['distance']/1000,1);
                    unset($order_common['reciver_info']);
                    $ldj_order_list[$k]['extend'] = $order_common;
                    array_walk($order_goods_list,function(&$vv){
                        $vv['goods_image'] = cthumb($vv['goods_image']);
                    });

                    $ldj_order_list[$k]['goods_list'] = $order_goods_list;
                    $ldj_order_list[$k]['hexiao_code'] = $v['chain_code'];
                    $ldj_order_list[$k]['all_num'] = array_sum(low_array_column($order_goods_list,'goods_num'));
                    $ldj_order_list[$k]['should_money'] = $v['goods_amount'] +$v['shipping_fee'];
                }
            }else{
                $ldj_order_list = [];
            }
            if(!$ldj_order_list && !$shop_order_list){
                    throw new Exception('暂无相关数据');
            }
            if( $shop_order_list){
                foreach($shop_order_list as $k=>$v){
                        $return_data[] = $v;
                }
            }
            if($ldj_order_list){
                foreach($ldj_order_list as $k=>$v){
                    $return_data[] = $v;
                }
            }
            array_multisort(low_array_column($return_data,'add_time'),SORT_DESC,$return_data);
            $ismore = 0;
            if($ldj_page['hasmore'] ||  $shop_page['hasmore']){
                $ismore = 1;
            }
            echo json_encode(['status'=>200,'data'=>$return_data,'ismore'=>$ismore]);die;
        }catch(Exception $e){
                echo json_encode(['status'=>200,'data'=>[]]);die;
        }
    }
//    protected function recordSellerLog($content = '', $state = 1){
//        $vendorinfo = array();
//        $vendorinfo['log_content'] = $content['content'];
//        $vendorinfo['log_time'] = TIMESTAMP;
//        $vendorinfo['log_seller_id'] = $_SESSION['dian_seller_id'];
//        $vendorinfo['log_seller_name'] = $_SESSION['dian_seller_name'];
//        $vendorinfo['log_store_id'] = $_SESSION['dian_vid'];
//        $vendorinfo['log_seller_ip'] = getIp();
//        $vendorinfo['log_url'] = $_GET['app'].'&'.$_GET['mod'];
//        $vendorinfo['log_state'] = $state;
//        $model_vendor_log = Model('dian_log');
//        $model_vendor_log->addSellerLog($vendorinfo);
//    }

    /*
     * 订单搜索
     * key 商户登录key
     * type 订单类型: 1线上订单,2线下订单
     * order_id 订单id
     */
     public function order_search()
     {
         $order_id = trim($_GET['order_id']);
         $condition = [
             'order_sn'=>$order_id
         ];
         $model = M('common_order','common');
         $model_ = model();
         $return_data = [];
         if($_GET['type'] == 1){
             //联到家订单
             $ldj_order = $model->order_info($condition,'*',1);
             if($ldj_order){
                 //订单common信息
                 $model_->table('ldj_order_common')->where([''=>$ldj_order['']])->find();
                 $model_->table('ldj_order_goods')->where([''])->select();
             }
             //商城订单
             $shop_order = $model->shop_order_info($condition,'*',1);
             if($shop_order){

             }
         }elseif($_GET['type'] == 2){
             //收银订单
             $cash_order = $model->cash_order_info($condition,'*',1);
             if($cash_order){

             }
         }else{

         }


     }
    /**
     * @api {get} index.php?app=order&mod=pai&sld_addons=common 门店派单
     * @apiVersion 0.1.0
     * @apiName pai
     * @apiGroup App
     * @apiDescription 门店派单
     * @apiExample 请求地址:
     * curl -i http://site2.slodon.cn/bmobile/index.php?app=order&mod=pai&sld_addons=common
     * @apiParam {String} key 店铺登录key值
     * @apiParam {String} order_id 订单id
     * @apiSuccess {Number} status 状态
     * @apiSuccess {String} msg 订单信息
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200,
     *         "msg": "操作成功"
     *      }
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "操作失败"
     *      }
     *      /
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *
     */
    public function pai()
    {
        $this->checkToken();
        $order_id = intval($_GET['order_id']);
        if ($order_id <= 0){
            echo json_encode(['status'=>255,'msg'=>'订单编号获取失败']);die;
        }
        $model_order = Model('order');
        $condition = array();
        $condition['order_id'] = $order_id;
        $condition['vid'] = $this->vendor_info['vid'];
        $order_info = $model_order->getOrderInfo($condition,array('order_common','order_goods'));

        $if_allow_send = intval($order_info['lock_state']) || !in_array($order_info['order_state'],array(ORDER_STATE_PAY,ORDER_STATE_SEND));
        if ($if_allow_send) {
            echo json_encode(['status'=>255,'msg'=>'无权限操作该订单']);die;
        }
        if ($order_info['dian_id']>0 && $order_info['dian_id'] != $this->vendor_info['id']) {
            echo json_encode(['status'=>255,'msg'=>'该订单已被其他门店处理']);die;
        }
        $if_allow_pai = in_array($this->vendor_info['id'], explode(',',$order_info['extend_order_common']['pai_dian_id']));
        if (!$if_allow_pai || $order_info['dian_id']!=0) {
            echo json_encode(['status'=>255,'msg'=>'无权限操作该订单']);die;
        }

            try {
                $model_order->beginTransaction();
                $data = array();
//                $data['reciver_name'] = $_POST['reciver_name'];
//                $data['reciver_info'] = serialize(array('address' => $_POST['reciver_address'],'phone' => $_POST['reciver_phone']));
//                $data['shipping_express_id'] = intval($_POST['shipping_express_id']);
                $data['shipping_time'] = TIMESTAMP;
                $condition = array();
                $condition['order_id'] = $order_id;
                $condition['vid'] = $this->vendor_info['vid'];
                $update = $model_order->editOrderCommon($data,$condition);
                if (!$update) {
                    throw new Exception('操作失败');
                }
                $data = array();
//                $data['shipping_code']  = $_POST['shipping_code'];
                $data['order_state'] = ORDER_STATE_SEND;
                $data['delay_time'] = TIMESTAMP;
                $data['dian_id'] = $this->vendor_info['id'];
                $data['ziti'] = 0;
                $update = $model_order->editOrder($data,$condition);

                if (!$update) {
                    throw new Exception('操作失败');
                }

                //减门店库存
                $goods_buy_quantity=array();
                if(is_array($order_info['extend_order_goods']) and !empty($order_info['extend_order_goods'])) {
                    foreach ($order_info['extend_order_goods'] as $goods) {
                        $goods_buy_quantity[$goods['gid']]=$goods['goods_num'];
                    }
                }
                Model('buy')->updateGoodsStorageNum($goods_buy_quantity,$this->vendor_info['id']);
                //加店铺库存
                QueueClient::push('cancelOrderUpdateStorage', array('id'=>null,'data'=>$goods_buy_quantity));


                $model_order->commit();

                $dian_info = Model('dian')->getDianInfoById($this->vendor_info['vid'],$this->vendor_info['id']);

                // 获取当前物流信息
                $shipping_express_id = '';
                $shipping_code = '';
                $now_express_info = array();
                $express_list  = ($h = H('express')) ? $h : H('express',true);
                if (!empty($express_list) && !empty($express_list[$shipping_express_id])) {
                    $now_express_info = $express_list[$shipping_express_id];
                }
                $now_express_info['shipping_code'] = $shipping_code;

                // 发送买家消息
                $param = array();
                $param['code'] = 'order_deliver_success';
                $param['member_id'] = $order_info['buyer_id'];
                $param['param'] = array(
                    'order_sn' => $order_info['order_sn'],
                    'order_url' => urlShop('userorder', 'show_order', array('order_id' => $order_id)),

                    'first' => '亲，宝贝已经启程了，好想快点来到你身边',
                    'keyword1' => $order_info['order_sn'],
                    'keyword2' => (isset($now_express_info['e_name']) && $now_express_info['e_name']) ? $now_express_info['e_name'] : '无',
                    'keyword3' => $now_express_info['shipping_code'] ? $now_express_info['shipping_code'] : '-',
                    'remark' => '点击查看详情',
                                
                    'url' => WAP_SITE_URL.'/cwap_order_detail.html?order_id='.$order_id
                );
                $param['system_type']=1;
                QueueClient::push('sendMemberMsg', $param);

                // 给店铺发消息
                $param = array();
                $param['code'] = 'dian_shipping';
                $params['vid'] = $order_info['vid'];
                $param['param'] = array(
                    'order_sn' => $order_info['order_sn'],
                    'dian_name' => $dian_info['dian_name'],
                );
                QueueClient::push('sendStoreMsg', $param);

                //发货的时候执行推送操作
//				if($_POST['shipping_code']){
//					//根据订单号 获取订单详情
//					$msg='您的订单'.$order_info['order_sn'].'已经发货';
//					$member_id='11';
//					$res=$jpush->deliverInfo($msg,$order_id,$member_id);
//
//				}
            } catch (Exception $e) {
                $model_order->rollback();
                echo json_encode(['status'=>255,'msg'=>$e->getMessage()]);
            }
            //添加订单日志
            $data = array();
            $data['order_id'] = intval($_GET['order_id']);
            $data['log_role'] = 'dian';
            $data['log_user'] = $this->vendor_info['casher_name'];
            $data['log_msg'] = '发出了货物';
            $model_order->addOrderLog($data);
            echo json_encode(['status'=>200,'msg'=>'操作成功']);die;
    }
    // 校验token
    public function checkToken()
    {
        //登录信息
        if($_POST['key']){
            $key = trim($_POST['key']);
        }else{
            $key = trim($_GET['key']);
        }
        if(!$key){
            echo json_encode(['status'=>266,'msg'=>Language::get('请登录')]);die;
        }
        //如果收银开启
            $token_model = M('cashsys_token','common');
            $token_info = $token_model->getTokenInfoByToken($key);
            if(!$token_info){
                echo json_encode(['status'=>266,'msg'=>Language::get('请登录')]);die;
            }
            $cash_info = $token_model->table('cashsys_users')->where(['id'=>$token_info['casher_id']])->find();
            if(!$cash_info){
                echo json_encode(['status'=>266,'msg'=>Language::get('请登录')]);die;
            }
            $dian_info = $token_model->table('dian')->where(['id'=>$cash_info['dian_id']])->find();
            if(!$dian_info){
                echo json_encode(['status'=>266,'msg'=>'门店不存在']);die;
            }
            if($cash_info['is_leader']){
                if(! (
                    (C('sld_cashersystem') && C('cashersystem_isuse') && $dian_info['cash_status'])
                    ||
                    (C('sld_ldjsystem') && C('ldj_isuse') && $dian_info['ldj_status'])
                )){
                    echo json_encode(['status'=>255,'msg'=>'该功能已关闭']);die;
                }
            }else{
                if(! (C('sld_cashersystem') && C('cashersystem_isuse') && $dian_info['cash_status'])){
                    echo json_encode(['status'=>255,'msg'=>'该功能已关闭']);die;
                }
            }
            $this->vendor_info =  $dian_info;
            $this->vendor_info['casher_name'] =  $cash_info['casher_name'];

    }
}