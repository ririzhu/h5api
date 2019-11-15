<?php
/**
 * 我的订单
 */

defined('DYMall') or exit('Access Invalid!');
class userorderCtl extends mobileCtl {
    protected $member_info = array();
    public function __construct(){
        parent::__construct();
        $agent = $_SERVER['HTTP_USER_AGENT'];
        if (strpos($agent, "MicroMessenger") && $_GET["app"] == 'auto') {
            $this->appId = C('app_weixin_appid');
            $this->appSecret = C('app_weixin_secret');;
        } else {
            $model_mb_user_token = Model('mb_user_token');
            $key = $_POST['key'];
            if (empty($key)) {
                $key = $_GET['key'];
            }
            $mb_user_token_info = $model_mb_user_token->getMbUserTokenInfoByToken($key);
            if (empty($mb_user_token_info)) {
                output_error(Language::get('请登录'), array('login' => '0'));
            }

            $model_member = Model('member');
            $this->member_info = $model_member->getMemberInfoByID($mb_user_token_info['member_id']);
            if(empty($this->member_info)) {
                output_error(Language::get('请登录'), array('login' => '0'));
            } else {
                //消除密码
                unset($this->member_info['member_passwd']);

                $this->member_info['client_type'] = $mb_user_token_info['client_type'];
                $this->member_info['openid'] = $mb_user_token_info['openid'];
                $this->member_info['token'] = $mb_user_token_info['token'];
                //读取卖家信息
                $vendorinfo = Model('seller')->getSellerInfo(array('member_id'=>$this->member_info['member_id']));
                $this->member_info['vid'] = $vendorinfo['vid'];
            }
        }
    }


    /**
     * @api {get} index.php?app=userorder&mod=getmemberlist&sld_addons=points 用户订单列表
     * @apiVersion 0.1.0
     * @apiName getmemberlist
     * @apiGroup PointsOrder
     * @apiDescription 获取用户订单列表
     * @apiExample 请求地址:
     * curl -i http://site2.slodon.cn/cmobile/index.php?app=userorder&mod=getmemberlist&sld_addons=points
     * @apiParam {String} key 会员登录key值
     * @apiParam {String} s 订单状态:1:全部,10:待付款,20:待发货,30:待收货,40:已完成
     * @apiParam {Number} page 分页
     * @apiParam {Number} pn 请求第几页
     * @apiSuccess {Number} status 状态
     * @apiSuccess {String} data 返回数据
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200
     *         "data": {
     *                  "ishasmore":{hasmore: true, page_total: 4},
     *                  "list":订单数据/空
     *               }
     *      }
     */
    public function getmemberlist()
    {
        if(isset($_GET['s']) && !empty($_GET['s']) && $_GET['s'] != 1){
            $condition['point_orderstate'] = $_GET['s'];
        }
        $condition['point_buyerid'] = $this->member_info['member_id'];
        $page = $_GET['page'];
        $points_model = M('points_goods');
        $list = $points_model->memberorderlist($condition,'point_orderid desc',$page);
        $page_count = $points_model->gettotalpage();
        echo json_encode(['status'=>200,'data'=>['list'=>$list,'ishasmore'=>mobile_page($page_count)]]);die;
    }


    /**
     * @api {post} index.php?app=userorder&mod=rollbackorder&sld_addons=points 取消订单操作
     * @apiVersion 0.1.0
     * @apiName rollbackorder
     * @apiGroup PointsOrder
     * @apiDescription 取消订单操作
     * @apiExample 请求地址:
     * curl -i http://site2.slodon.cn/cmobile/index.php?app=userorder&mod=rollbackorder&sld_addons=points
     * @apiParam {String} key 会员登录key值
     * @apiParam {Number} order_id 订单编号
     * @apiSuccess {Number} status 状态
     * @apiSuccess {String} msg 信息提示
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200
     *         "msg":"取消成功"
     *      }
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "取消失败"
     *     }
     *
     */
    public function rollbackorder()
    {
        $order_id = intval($_POST['order_id']);
        if($order_id<1){
            echo json_encode(['status'=>255,'msg'=>'订单号错误~~']);die;
        }
        $pointorder_model = Model('pointorder');
        $condition_arr = array();
        $condition_arr['point_orderid'] = $order_id;
        $condition_arr['point_buyerid'] = $this->member_info['member_id'];
        //查询兑换信息
        $order_info = $pointorder_model->getPointOrderInfo($condition_arr,'simple','point_ordersn,point_buyerid,point_buyername,point_allpoint,point_orderstate');
        if (!is_array($order_info) || count($order_info)<=0){
            echo json_encode(['status'=>255,'msg'=>'订单不存在~~']);die;
        }
        //更新状态
        $state = $pointorder_model->updatePointOrder($condition_arr,array('point_orderstate'=>'2'));
        if($order_info['point_orderstate'] != 10){
            if ($state){
                //退还会员积分
                $points_model =Model('points');
                $insert_arr['pl_memberid'] 		= $order_info['point_buyerid'];
                $insert_arr['pl_membername'] 	= $order_info['point_buyername'];
                $insert_arr['pl_points'] 		= $order_info['point_allpoint'];
                $insert_arr['point_ordersn'] 	= $order_info['point_ordersn'];
                $insert_arr['pl_desc'] 			= '取消兑换礼品信息'.$order_info['point_ordersn'].'增加积分';
                $points_model->savePointsLog('pointorder',$insert_arr,true);
            }else {
                echo json_encode(['status'=>255,'msg'=>'取消失败']);die;
            }
        }
        //更改兑换礼品库存
        $prod_list = $pointorder_model->getPointOrderProdList(array('prod_orderid'=>$order_id),'','point_goodsid,point_goodsnum');
        if (is_array($prod_list) && count($prod_list)>0){
            $pointprod_model = Model('pointprod');
            foreach ($prod_list as $v){
                $update_arr = array();
                $update_arr['pgoods_storage'] = array('sign'=>'increase','value'=>$v['point_goodsnum']);
                $update_arr['pgoods_salenum'] = array('sign'=>'decrease','value'=>$v['point_goodsnum']);
                $pointprod_model->updatePointProd($update_arr,array('pgid'=>$v['point_goodsid']));
                unset($update_arr);
            }
            echo json_encode(['status'=>200,'msg'=>'取消成功']);die;
        }
        echo json_encode(['status'=>255,'msg'=>'取消失败']);die;

    }


    /**
     * @api {post} index.php?app=userorder&mod=gotopayorder&sld_addons=points 会员待付款状态支付订单
     * @apiVersion 0.1.0
     * @apiName gotopayorder
     * @apiGroup PointsOrder
     * @apiDescription 会员待付款状态支付订单
     * @apiExample 请求地址:
     * curl -i http://site2.slodon.cn/cmobile/index.php?app=userorder&mod=gotopayorder&sld_addons=points
     * @apiParam {String} key 会员登录key值
     * @apiParam {Number} order_id 订单id
     * @apiSuccess {Number} status 状态
     * @apiSuccess {String} msg 提示信息
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200
     *         "msg":"取消成功"
     *      }
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "取消失败"
     *     }
     *
     */
    public function gotopayorder()
    {
        $order_id = intval($_POST['order_id']);
        if($order_id<1){
            echo json_encode(['status'=>255,'msg'=>'订单号错误~~']);die;
        }
        $model = model();
        $order_info = $model->table('points_order')->where(['point_orderid'=>$order_id])->find();
        //支付
        if($this->member_info['member_points'] < $order_info['point_allpoint']) {
            echo json_encode(['status'=>255,'msg'=>'积分余额不足']);die;
        }else{
            //扣除会员积分
            $points_model = Model('points');
            $insert_arr['pl_memberid'] = $this->member_info['member_id'];
            $insert_arr['pl_membername'] = $this->member_info['member_name'];
            $insert_arr['pl_points'] = -$order_info['point_allpoint'];
            $insert_arr['point_ordersn'] = $order_info['point_ordersn'];
            $pay_res = $points_model->savePointsLog('pointorder',$insert_arr);
            if($pay_res) {
                $model->table('points_order')->where(['point_orderid'=>$order_id])->update(['point_orderstate'=>20]);
                echo json_encode(['status'=>200,'msg'=>'支付成功']);die;
            }
        }
    }


    /**
     * @api {post} index.php?app=userorder&mod=confirmation&sld_addons=points 会员确认收货
     * @apiVersion 0.1.0
     * @apiName confirmation
     * @apiGroup PointsOrder
     * @apiDescription 会员确认收货
     * @apiExample 请求地址:
     * curl -i http://site2.slodon.cn/cmobile/index.php?app=userorder&mod=confirmation&sld_addons=points
     * @apiParam {String} key 会员登录key值
     * @apiParam {Number} order_id 订单id
     * @apiSuccess {Number} status 状态
     * @apiSuccess {String} msg 提示信息
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200
     *         "msg":"取消成功"
     *      }
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "取消失败"
     *     }
     *
     */
    public function confirmation()
    {
        $order_id = intval($_POST['order_id']);
        if($order_id<1){
            echo json_encode(['status'=>255,'msg'=>'订单号错误~~']);die;
        }
        $pointorder_model = Model('pointorder');
        $condition_arr = array();
        $condition_arr['point_orderid'] = $order_id;
        $condition_arr['point_buyerid'] = $this->member_info['member_id'];
        $condition_arr['point_orderstate'] = '30';//待收货

        $state = $pointorder_model->updatePointOrder($condition_arr,array('point_orderstate'=>'40','point_finnshedtime'=>time()));
        if($state){
            echo json_encode(['status'=>200,'msg'=>'操作成功']);die;
        }
        echo json_encode(['status'=>255,'msg'=>'修改失败~~']);die;

    }


    /**
     * @api {post} index.php?app=userorder&mod=buyagainorder&sld_addons=points 会员再次购买商品
     * @apiVersion 0.1.0
     * @apiName buyagainorder
     * @apiGroup PointsOrder
     * @apiDescription 会员再次购买商品
     * @apiExample 请求地址:
     * curl -i http://site2.slodon.cn/cmobile/index.php?app=userorder&mod=buyagainorder&sld_addons=points
     * @apiParam {String} key 会员登录key值
     * @apiParam {Number} order_id 订单id
     * @apiSuccess {Number} status 状态
     * @apiSuccess {String} msg 提示信息
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200
     *         "msg":"成功"
     *      }
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "找到不到商品"
     *     }
     *
     */
    public function buyagainorder()
    {
        $order_id = intval($_POST['order_id']);
        if($order_id<1){
            echo json_encode(['status'=>255,'msg'=>'订单号错误~~']);die;
        }
        $model = model();
        $order_info = $model->table('points_ordergoods')->where(['point_orderid'=>$order_id])->select();
        if($order_info){
            foreach($order_info as $k=>$v){
                $goods_info = $model->table('points_goods')->where(['pgid'=>$v['point_goodsid']])->find();
                if($goods_info['pgoods_storage']>=1 && $goods_info['pgoods_show']!=0 && $goods_info['pgoods_state']!=1 && $goods_info['pgoods_storage']>=$v['point_goodsnum']){

                    if(!$model->table('points_cart')->where(['pgid'=>$goods_info['pgid'],'pmember_id'=>$this->member_info['member_id']])->find()){
                        $insert = [
                            'pmember_id'=>$this->member_info['member_id'],
                            'pgid'=>$goods_info['pgid'],
                            'pgoods_name'=>$goods_info['pgoods_name'],
                            'pgoods_points'=>$goods_info['pgoods_points'],
                            'pgoods_choosenum'=>$v['point_goodsnum'],
                            'pgoods_image'=>$goods_info['pgoods_image'],
                        ];
                        $model->table('points_cart')->insert($insert);
                    }

                }

            }
            echo json_encode(['status'=>200,'msg'=>'成功']);die;
        }
        echo json_encode(['status'=>255,'msg'=>'找到不到商品']);die;

    }

    /**
     * @api {post} index.php?app=userorder&mod=order_desc&sld_addons=points 订单详情页
     * @apiVersion 0.1.0
     * @apiName order_desc
     * @apiGroup PointsOrder
     * @apiDescription 订单详情页
     * @apiExample 请求地址:
     * curl -i http://site2.slodon.cn/cmobile/index.php?app=userorder&mod=order_desc&sld_addons=points
     * @apiParam {String} key 会员登录key值
     * @apiParam {Number} order_id 订单id
     * @apiSuccess {Number} status 状态
     * @apiSuccess {String} data 提示信息
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200
     *         "data":{
     *              address:用户收货地址,
     *              orderinfo:{
     *                      point_ordersn:"gift2018092848101102",
     *                      point_addtimes:"2018-09-28 11:18:48"(添加时间),
     *                      point_invalidtime:"2018-09-28 12:18:48"(未付款取消时间),
     *                                  .
     *                                  .
     *                                  .
     *                                  .
     *                      order_goods:{
     *                              商品列表...
     *                          }
     *                  }
     *          }
     *      }
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "订单不存在"
     *     }
     *
     */

    /*
     * 订单详情
     */
    public function order_desc()
    {
        $order_id = intval($_POST['order_id']);
        if($order_id<1) {
            echo json_encode(['status'=>255,'msg'=>'订单不存在']);die;
        }
        $condition = [];
        $condition['point_orderid'] = $order_id;
        $condition['point_buyerid'] = $this->member_info['member_id'];
        $points_model = M('points_goods');
        $list = $points_model->memberorderlist($condition)[0];
        //添加时间
        $list['point_addtimes'] = date('Y-m-d H:i:s',$list['point_addtime']);
        //过期时间
        $invalid = ORDER_AUTO_CANCEL_TIME * 3600;
        $list['point_invalidtime'] = date('Y-m-d H:i:s',$list['point_addtime']+$invalid);
        //发货时间
        $list['point_shippingtimes'] = date('Y-m-d H:i:s',$list['point_shippingtime']);
        $address = $points_model->table('points_orderaddress')->where(['point_orderid'=> $list['point_orderid']])->find();
        if($list){
            echo json_encode(['status'=>200,'data'=>['orderinfo'=>$list,'address'=>$address]]);die;
        }
        echo json_encode(['status'=>255,'msg'=>'订单不存在']);die;
    }

    /**
     * @api {post} index.php?app=userorder&mod=cartlist&sld_addons=points 购物车列表
     * @apiVersion 0.1.0
     * @apiName cartlist
     * @apiGroup PointsCart
     * @apiDescription 购物车列表
     * @apiExample 请求地址:
     * curl -i http://site2.slodon.cn/cmobile/index.php?app=userorder&mod=cartlist&sld_addons=points
     * @apiParam {String} key 会员登录key值
     * @apiSuccess {Number} status 状态
     * @apiSuccess {String} data 返回数据
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200
     *         "data":数据
     *      }
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "当前购物车暂无数据"
     *     }
     *
     */

    public function cartlist()
    {
        $condition['pmember_id'] = $this->member_info['member_id'];
        $model = model();
        $cart_list = $model->table('points_cart')->where($condition)->select();
        array_walk($cart_list,function(&$v){
            $v['image'] = pointprodThumb($v['pgoods_image']);
        });
        if($cart_list){
            echo json_encode(['status'=>200,'data'=>$cart_list]);die;
        }
        echo json_encode(['status'=>255,'msg'=>'当前购物车暂无数据']);die;

    }

    /**
     * @api {post} index.php?app=userorder&mod=handlecart&sld_addons=points 购物车加减商品
     * @apiVersion 0.1.0
     * @apiName handlecart
     * @apiGroup PointsCart
     * @apiDescription 购物车加减商品(不能手动输入,每次操作默认为1)
     * @apiExample 请求地址:
     * curl -i http://site2.slodon.cn/cmobile/index.php?app=userorder&mod=handlecart&sld_addons=points
     * @apiParam {String} key 会员登录key值
     * @apiParam {String} type add(增加)/desc(减少)
     * @apiParam {Number} cartid 购物车id
     * @apiSuccess {Number} status 状态
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200
     *      }
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "商品已下架"
     *     }
     *
     */

    public  function handlecart()
    {
        $cartid = $_POST['cartid'];
        $type = $_POST['type'];
        $condition['pmember_id'] = $this->member_info['member_id'];
        $condition['pcart_id'] = $cartid;
        $model = model();
        $cart_info = $model->table('points_cart')->where($condition)->find();
        $gid = $cart_info['pgid'];
        $goods_info = $model->table('points_goods')->where(['pgid'=>$gid])->find();
        if(!$goods_info){
            echo json_encode(['status'=>255,'msg'=>'商品不存在']);die;
        }
        if($goods_info['pgoods_show']==0 || $goods_info['pgoods_state']==1) {
            echo json_encode(['status'=>255,'msg'=>'商品已下架']);die;
        }


        $update = [];
        if($type == 'add'){
            if($goods_info['pgoods_storage']<=$cart_info['pgoods_choosenum']){
                echo json_encode(['status'=>255,'msg'=>'商品库存不足']);die;
            }
            if($goods_info['pgoods_islimit']){
//                dd($cart_info['pgoods_choosenum']);
//                dd($cart_info);
//                die;
                if($cart_info['pgoods_choosenum'] >= $goods_info['pgoods_limitnum']){
                    echo json_encode(['status'=>255,'msg'=>'超过限购数量']);die;
                }
            }
            $update = [
                'pgoods_choosenum'=>['exp','pgoods_choosenum + 1']
            ];
        }else{

            if($cart_info['pgoods_choosenum'] != 1){
                $update = [
                    'pgoods_choosenum'=>['exp','pgoods_choosenum - 1']
                ];
            }else{
                echo json_encode(['status'=>255,'msg'=>'商品购买数量最少为1']);die;
            }

        }

        $res = $model->table('points_cart')->where($condition)->update($update);
        if($res){
//            dd($model->getlastsql());die;
            echo json_encode(['status'=>200]);die;
        }
        echo json_encode(['status'=>255,'msg'=>'修改失败~~']);die;
    }


    /**
     * @api {post} index.php?app=userorder&mod=addcart&sld_addons=points 添加商品到购物车
     * @apiVersion 0.1.0
     * @apiName addcart
     * @apiGroup PointsCart
     * @apiDescription 添加商品到购物车
     * @apiExample 请求地址:
     * curl -i http://site2.slodon.cn/cmobile/index.php?app=userorder&mod=addcart&sld_addons=points
     * @apiParam {String} key 会员登录key值
     * @apiParam {Number} num 商品数量
     * @apiParam {Number} gid 积分商品id
     * @apiSuccess {Number} status 状态
     * @apiSuccess {String} msg 提示信息
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200,
     *         "msg":"添加成功",
     *      }
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "商品库存不足"
     *     }
     *
     */
    public function addcart()
    {
        $model = model();
        $res = $model->table('points_goods')->where(['pgid'=>intval($_POST['gid'])])->find();
        if(!$res){
            echo json_encode(['status'=>255,'msg'=>'商品未找到']);die;
        }
        if($res['pgoods_storage']<intval($_POST['num'])){
            echo json_encode(['status'=>255,'msg'=>'商品库存不足']);die;
        }
        if($res['pgoods_islimit']>0 && $res['pgoods_limitnum']<intval($_POST['num'])){
            echo json_encode(['status'=>255,'msg'=>'商品超过限购数量']);die;
        }
        $ishascart = $model->table('points_cart')->where(['pgid'=>intval($_POST['gid']),'pmember_id'=>$this->member_info['member_id']])->find();
        if($ishascart){
            if($res['pgoods_storage']<($ishascart['pgoods_choosenum']+intval($_POST['num']))){
                echo json_encode(['status'=>255,'msg'=>'商品库存不足']);die;
            }
            if($res['pgoods_islimit']>0 && $res['pgoods_limitnum']<($ishascart['pgoods_choosenum']+intval($_POST['num']))){
                echo json_encode(['status'=>255,'msg'=>'商品超过限购数量']);die;
            }
            $r1 = $model->table('points_cart')->where(['pgid'=>intval($_POST['gid']),'pmember_id'=>$this->member_info['member_id']])->update(['pgoods_choosenum'=>['exp','pgoods_choosenum +'.intval($_POST['num'])]]);
            if($r1){
                echo json_encode(['status'=>200,'msg'=>'添加成功']);die;
            }
            echo json_encode(['status'=>255,'msg'=>'添加失败']);die;
        }
        $insert = [
            'pmember_id'=>$this->member_info['member_id'],
            'pgid'=>$res['pgid'],
            'pgoods_name'=>$res['pgoods_name'],
            'pgoods_points'=>$res['pgoods_points'],
            'pgoods_choosenum'=>intval($_POST['num']),
            'pgoods_image'=>$res['pgoods_image'],
        ];
        $r = $model->table('points_cart')->insert($insert);
        if($r){
            echo json_encode(['status'=>200,'msg'=>'添加成功']);die;
        }
        echo json_encode(['status'=>255,'msg'=>'添加失败']);die;
    }


    /**
     * @api {post} index.php?app=userorder&mod=delcart&sld_addons=points 删除购物车商品
     * @apiVersion 0.1.0
     * @apiName delcart
     * @apiGroup PointsCart
     * @apiDescription 删除购物车商品
     * @apiExample 请求地址:
     * curl -i http://site2.slodon.cn/cmobile/index.php?app=userorder&mod=delcart&sld_addons=points
     * @apiParam {String} key 会员登录key值
     * @apiParam {String} cartid 积分商品id集合类似于:1,2,3,4
     * @apiSuccess {Number} status 状态
     * @apiSuccess {String} msg 提示信息
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200,
     *         "msg":"删除成功",
     *      }
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "删除失败"
     *     }
     *
     */
    public function delcart()
    {
        $cartids = $_POST['cartid'];
        $condition['pmember_id'] = $this->member_info['member_id'];
        $condition['pcart_id'] = ['in',$cartids];
        $model = model();
        $res = $model->table('points_cart')->where($condition)->delete();
        if($res)
        {
            echo json_encode(['status'=>200,'msg'=>"删除成功"]);die;
        }
        echo json_encode(['status'=>255,'msg'=>"删除失败"]);die;
    }
}