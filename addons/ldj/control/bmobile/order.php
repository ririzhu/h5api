<?php

/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/10/18
 * Time: 19:34
 */
class orderCtl extends mobileHomeCtl {
    private $dian_info;
    private $order_state = ['0'=>'已取消','10'=>'未付款','20'=>'已付款','30'=>'已发货','40'=>'已完成'];
    private $express_type = ['1'=>'门店自取','2'=>'商家配送','3'=>'达达快递'];
    public function __construct(){
        parent::__construct();
            if(!((C('sld_cashersystem') && C('cashersystem_isuse')) || (C('sld_ldjsystem') && C('ldj_isuse')))){
                echo json_encode(['status'=>255,'msg'=>'当前模块未开启']);die;
            }
        //登录信息
        if($_POST['key']){
            $key = trim($_POST['key']);
        }else{
            $key = trim($_GET['key']);
        }
        if(!$key){
            echo json_encode(['status'=>266,'msg'=>Language::get('请登录')]);die;
        }

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
                echo json_encode(['status'=>266,'msg'=>Language::get('请登录')]);die;
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
            $this->dian_info =  $dian_info;



//        else{
//            //否则检测门店管理员账号
//            $token_model = Model();
//            $token_info = $token_model ->table('dian_user_token')->where(['token'=>$key])->find();
//            if(!$token_info){
//                echo json_encode(['status'=>266,'msg'=>Language::get('请登录')]);die;
//            }
//            $dian_info = $token_model ->table('dian')->where(['member_id'=>$token_info['member_id']])->find();
//            if(!$dian_info){
//                echo json_encode(['status'=>266,'msg'=>Language::get('请登录')]);die;
//            }
//            if(!$dian_info['ldj_status']){
//                echo json_encode(['status'=>255,'msg'=>'O2O到家功能已关闭']);die;
//            }
//            $this->dian_info = $dian_info;
//        }
    }
    /**
     * @api {get} index.php?app=order&mod=order_list&sld_addons=ldj 联到家订单列表
     * @apiVersion 0.1.0
     * @apiName order_list
     * @apiGroup Bmobile
     * @apiDescription 联到家订单列表
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/bmobile/index.php?app=order&mod=order_list&sld_addons=ldj
     * @apiParam {String} key 门店登录key值
     * @apiParam {String} order_state 订单类型:0:已取消,20:待配货,30:待送达,40:已完成
     * @apiParam {Number} page 分页
     * @apiParam {Number} pn 第几页
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} data 订单列表
     * @apiSuccess {Json} ismore 分页信息
     * @apiSuccess {Json} stateNumber 各状态总条数
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200
     *         "data": [
     *                          {
     *                                      数据...
     *                          }
     *                      ],
     *         "ismore": {
     *                          hasmore:true,
     *                          page_total:20
     *                      }
     *         "stateNumber": {
     *                                   "cancelorder": "0",
     *                                   "payorder": "5",
     *                                   "sendorder": "0",
     *                                   "okorder": "0"
     *                           }
     *      }
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "暂无相关数据"
     *      }
     *     /
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *
     */
    public function order_list()
    {
        $order_model = M('ldj_order','ldj');
        $goods_model = M('ldj_goods','ldj');
        $dian_id = $this->dian_info['id'];
        $page = intval($_GET['page']);
        $return_data = [];
        try{
            $condition = [
                'vid'=>$dian_id,
            ];
            if(isset($_GET['order_state'])){
                $condition['order_state'] = intval($_GET['order_state']);
                if($_GET['order_state'] == 20){
                    $condition['express_type'] = ['neq',1];
                }
            }
            $order_list = $order_model->order_list($condition,$field='*',$page);
            $ismore = mobile_page($order_model->gettotalpage());
            foreach($order_list as $k=>$v)
            {
                //订单信息
                $return_data[$k]['order_id'] = $v['order_id'];
                $return_data[$k]['order_sn'] = $v['order_sn'];
                $return_data[$k]['buyer_id'] = $v['buyer_id'];
                $return_data[$k]['buyer_name'] = $v['buyer_name'];
                $return_data[$k]['add_time'] = date('Y-m-d H:i',$v['add_time']);
                $return_data[$k]['express_type'] = $v['express_type'];
                $return_data[$k]['express_type_str'] = $this->express_type[$v['express_type']];
                $return_data[$k]['order_amount'] = $v['order_amount'];
                $return_data[$k]['shipping_fee'] = $v['shipping_fee'];
                $return_data[$k]['order_state'] = $v['order_state'];
                $return_data[$k]['order_state_str'] = $this->order_state[$v['order_state']];
                $return_data[$k]['order_type'] = 'O2O到家';
                $return_data[$k]['finnshed_time'] = $v['finnshed_time']?date('Y-m-d H:i:s',$v['finnshed_time']):'';

                //order配置信息
                $order_common = $order_model->table('ldj_order_common')->where(['order_id'=>$v['order_id']])->field('member_phone,start_time,end_time,reciver_info,order_message,time_type,distance')->find();
                $return_data[$k]['order_message'] = $order_common['order_message'];
                $return_data[$k]['reciver_info'] = unserialize($order_common['reciver_info']);
                $return_data[$k]['reciver_info']['address'] = html_entity_decode($return_data[$k]['reciver_info']['address']);
                $return_data[$k]['member_phone'] = $order_common['member_phone'];
                $return_data[$k]['start_time'] = date('Y-m-d H:i',$order_common['start_time']);
                $return_data[$k]['end_time'] = date('Y-m-d H:i',$order_common['end_time']);
                if($order_common['distance'] >= 1000){
                    $return_data[$k]['distance'] = intval(($order_common['distance']/1000)*10)/10;
                }else{
                    $return_data[$k]['distance'] = round($order_common['distance']/1000,1);
                }

                //订单商品信息
                $order_goods_list = $order_model->table('ldj_order_goods')->where(['order_id'=>$v['order_id']])->field('gid,goods_name,goods_price,goods_num,goods_image')->select();

                foreach($order_goods_list as $kk=>$vv){
                    $goods_info = $goods_model->getGoodsList(['dian_goods.goods_id'=>$vv['gid'],'dian_goods.dian_id'=>$dian_id],$goodsfield='dian_goods.stock,dian_goods.off,dian_goods.delete,goods.goods_serial')[0];
                    //商品状态
                    $order_goods_list[$kk]['goods_info'] = '上架中';
                    if($goods_info['off'] || $goods_info['delete']){
                        $order_goods_list[$kk]['goods_info'] = '已下架';
                    }
                    if($goods_info['stock'] <= 0){
                        $order_goods_list[$kk]['goods_info'] = '库存不足';
                    }
                    $order_goods_list[$kk]['stock'] = $goods_info['stock'];
                    //商品图像
                    $order_goods_list[$kk]['goods_image'] = cthumb($vv['goods_image']);
                }

                $return_data[$k]['goods_num'] = array_sum(low_array_column($order_goods_list,'goods_num'));
                $return_data[$k]['goods_list'] = $order_goods_list;
            }
            echo json_encode(['status'=>200,'data'=>$return_data,'ismore'=>$ismore]);die;
        }catch(Exception $e){
            echo json_encode(['status'=>255,'data'=>[],'msg'=>$e->getMessage()]);die;
        }
    }
    /**
     * @api {get} index.php?app=order&mod=cancelorder&sld_addons=ldj 联到家订单取消
     * @apiVersion 0.1.0
     * @apiName cancelorder
     * @apiGroup Bmobile
     * @apiDescription 联到家订单取消
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/bmobile/index.php?app=order&mod=cancelorder&sld_addons=ldj
     * @apiParam {String} key 门店登录key值
     * @apiParam {Number} order_id 订单id
     * @apiSuccess {Number} status 状态
     * @apiSuccess {String} msg 返回说明信息
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200
     *         "msg": "操作成功"
     *      }
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "发货订单不能取消"
     *      }
     *     /
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *
     */
    public function cancelorder()
    {
        $dian_id = $this->dian_info['id'];
        $order_id = intval($_GET['order_id']);
        $order_model = M('ldj_order','ldj');
        $condition = [
                'order_id'=>$order_id,
                'vid'=>$dian_id
        ];
        $order_info = $order_model->order_info($condition,$field='*');
        if(!$order_info){
            echo json_encode(['status'=>255,'msg'=>'订单不存在']);die;
        }
        if($order_info['order_state'] == 30 || $order_info['order_state'] == 40){
            echo json_encode(['status'=>255,'msg'=>'发货订单不能取消']);die;
        }
        $type = 1;
        $this->editOrder($order_info,$type);
        echo json_encode(['status'=>200,'msg'=>'操作成功']);die;
    }
    /**
     * @api {get} index.php?app=order&mod=sendorder&sld_addons=ldj 联到家订单发货
     * @apiVersion 0.1.0
     * @apiName sendorder
     * @apiGroup Bmobile
     * @apiDescription 联到家订单发货
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/bmobile/index.php?app=order&mod=sendorder&sld_addons=ldj
     * @apiParam {String} key 门店登录key值
     * @apiParam {Number} order_id 订单id
     * @apiSuccess {Number} status 状态
     * @apiSuccess {String} msg 返回说明信息
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200
     *         "msg": "操作成功"
     *      }
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "发货订单不能取消"
     *      }
     *     /
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *
     */
    public function sendorder()
    {
        $dian_id = $this->dian_info['id'];
        $order_id = intval($_GET['order_id']);
        $order_model = M('ldj_order','ldj');
        $condition = [
            'order_id'=>$order_id,
            'vid'=>$dian_id
        ];
        $order_info = $order_model->order_info($condition,$field='*');
        if(!$order_info){
            echo json_encode(['status'=>255,'msg'=>'订单不存在']);die;
        }
        if($order_info['order_state'] != 20 || $order_info['lock_state'] || $order_info['express_type'] == 1){
            echo json_encode(['status'=>255,'msg'=>'订单没有发货权限']);die;
        }
        $type = 2;
        $this->editOrder($order_info,$type);
        echo json_encode(['status'=>200,'msg'=>'操作成功']);die;
    }
    private function editOrder($order_info,$type)
    {
        $order_model = M('ldj_order','ldj');
        try{
            $order_model->begintransaction();
            //取消订单
            if($type == 1){
                $order_model->cancel_order($order_info);
            }elseif($type == 2){
                //发货
                $update = [
                    'order_state'=>30,
                ];
                $condition = [
                    'order_id'=>$order_info['order_id'],
                    'vid'=>$order_info['vid'],
                ];
                $res = $order_model->editOrder($condition,$update);
                if(!$res){
                    throw new Exception('操作失败');
                }

            }elseif($type == 3){
                //发货
                $update = [
                    'order_state'=>40,
                    'finnshed_time'=>time()
                ];
                $condition = [
                    'order_id'=>$order_info['order_id'],
                    'vid'=>$order_info['vid'],
                ];
                $res = $order_model->editOrder($condition,$update);
                if(!$res){
                    throw new Exception('操作失败');
                }
            }
            $order_model->commit();
        }catch(Exception $e){
            $order_model->rollback();
            echo json_encode(['status'=>255,'msg'=>$e->getMessage()]);die;
        }
    }
    /**
     * @api {get} index.php?app=order&mod=confirmorder&sld_addons=ldj 联到家确认订单已送达
     * @apiVersion 0.1.0
     * @apiName confirmorder
     * @apiGroup Bmobile
     * @apiDescription 联到家确认订单已送达
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/bmobile/index.php?app=order&mod= confirmorder&sld_addons=ldj
     * @apiParam {String} key 门店登录key值
     * @apiParam {Number} order_id 订单id
     * @apiSuccess {Number} status 状态
     * @apiSuccess {String} msg 返回说明信息
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200
     *         "msg": "操作成功"
     *      }
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "发货订单不能取消"
     *      }
     *     /
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *
     */
    public function confirmorder()
    {
        $dian_id = $this->dian_info['id'];
        $order_id = intval($_GET['order_id']);
        $order_model = M('ldj_order','ldj');
        $condition = [
            'order_id'=>$order_id,
            'vid'=>$dian_id
        ];
        $order_info = $order_model->order_info($condition,$field='*');
        if(!$order_info){
            echo json_encode(['status'=>255,'msg'=>'订单不存在']);die;
        }
        if($order_info['order_state'] != 30 || $order_info['lock_state'] || $order_info['express_type'] == 1){
            echo json_encode(['status'=>255,'msg'=>'订单没有操作权限']);die;
        }
        $type = 3;
        $this->editOrder($order_info,$type);
        echo json_encode(['status'=>200,'msg'=>'操作成功']);die;
    }
    /**
     * @api {get} index.php?app=order&mod=getOrderStateNum&sld_addons=ldj 获取订单各种状态的数量
     * @apiVersion 0.1.0
     * @apiName getOrderStateNum
     * @apiGroup Bmobile
     * @apiDescription 获取订单各种状态的数量
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/bmobile/index.php?app=order&mod=getOrderStateNum&sld_addons=ldj
     * @apiParam {String} key 门店登录key值
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} data 各状态总条数
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200
     *         "data":{
     *                                   "cancelorder": "0",
     *                                   "payorder": "5",
     *                                   "sendorder": "0",
     *                                   "okorder": "0"
     *                          }
     *      }
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *
     */
    public function getOrderStateNum()
    {
        $model = Model();
        $dian_id = $this->dian_info['id'];
        $return_data = [];
        $return_data['cancelorder'] = $model->table('ldj_order')->where(['vid'=>$dian_id,'order_state'=>0])->count();
        //只统计门店发货的订单
        $return_data['payorder'] = $model->table('ldj_order')->where(['vid'=>$dian_id,'order_state'=>20,'express_type'=>['neq',1]])->count();
        $return_data['sendorder'] = $model->table('ldj_order')->where(['vid'=>$dian_id,'order_state'=>30])->count();
        $return_data['okorder'] = $model->table('ldj_order')->where(['vid'=>$dian_id,'order_state'=>40])->count();
        echo json_encode(['status'=>200,'data'=>$return_data]);die;
    }
}