<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/11/29
 * Time: 17:33
 */
class buyCtl extends mobileMemberCtl{
    private $buy_model;
    private $model;
    public function __construct() {
        parent::__construct();
        if(!(C('promotion_allow')==1 && C('sld_presale_system') && C('pin_presale_isuse'))){
            echo json_encode(['status'=>255,'msg'=>'当前活动尚未开启']);die;
        }
        $this->buy_model = M('pre_buy','presale');
        $this->model = model();
        //购买权限判定
        if(!$this->member_info['is_buy']){
            echo json_encode(['status'=>255,'msg'=>'您没有购买权限,请联系平台管理员']);die;
        }
    }
    /*
     * 检测会员下单定金能否提交
     *  $gid 商品gid
     * $pre_id 预售id
     * $number
     */
    public function testdeposit()
    {

    }

    /**
     * @api {get} index.php?app=buy&mod=confirm_deposit&sld_addons=presale 交定金页面
     * @apiVersion 0.1.0
     * @apiName confirm_deposit
     * @apiGroup Presale
     * @apiDescription 交定金页面
     * @apiExample 请求地址:
     * curl -i http://site7.55jimu.com/cmobile/index.php?app=buy&mod=confirm_deposit&sld_addons=presale
     * @apiParam {String} key 会员登录key
     * @apiParam {Number} gid 商品id
     * @apiParam {Number} pre_id 预售id
     * @apiParam {Number} num 数量
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} data 信息
     * @apiSuccessExample {json} 成功的例子:
{
    "status": 200,
    "data": {
        "goods_info": {
            "pre_id": "299",
            "pre_goods_commonid": "1440",
            "vid": "8",
            "pre_category": "2",
            "pre_pic": "8_05967546718027499.jpg",
            "pre_start_time": "1542679140",
            "pre_end_time": "1546704000",
            "pre_max_buy": "3",
            "pre_limit_time": "10",
            "pre_status": "1",
            "is_rollback": "0",
            "id": "885",
            "gid": "2044",
            "goods_price": "280.00",
            "goods_name": "11月规格测试 a c",
            "goods_image": "http://site7.55jimu.com/data/upload/mall/store/goods/8/8_05955244440880378_240.png",
            "pre_deposit_price": "1.00",
            "pre_sale_price": "2.00",
            "goods_stock": "900",
            "goods_number": 1,
            "goods_dingjin": 1,
            "goods_spec": {
                "380": "a",
                "382": "c"
            }
        },
        "address_info": {
            "address_id": "172",
            "member_id": "86",
            "true_name": "312",
            "area_id": "1496",
            "city_id": "103",
            "area_info": "内蒙古自治区 乌兰察布市 察哈尔右翼后旗",
            "address": "232",
            "tel_phone": null,
            "mob_phone": "13666666666",
            "is_default": "1"
        }
    }
}
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "活动已关闭"
     *      }
     *      /
     *  {
     *           "code": 200,
     *           "login": "0",
     *           "datas": {
     *               "error": "请登录"
     *           }
     *  }
     *
     */
    public function confirm_deposit()
    {
        $pre_id = intval($_GET['pre_id']);
        $gid = intval($_GET['gid']);
        $num = intval($_GET['num']);
        $member_id = $this->member_info['member_id'];
        try {
            if ($num < 1) {
                throw new Exception('数量不能小于0');
            }
            $testRes = $this->buy_model->testMemberPayDeposit($gid,$pre_id,$member_id,$num);
            if($testRes['status'] == 255){
                    throw new Exception($testRes['msg']);
            }
            //返回数据
            $goods_info = $this->model->table('goods')->where(['gid'=>$gid])->field('goods_spec')->find();
            $address_info = $this->model->table('address')->where(['is_default'=>1,'member_id'=>$member_id])->find();
            if(!$address_info){
                $address_info = [];
            }
            $pre_info = $this->model->table('presale,pre_goods')->join('left')->on('presale.pre_id=pre_goods.pre_id')->where([
                'presale.pre_id'=>$pre_id,
                'pre_goods.gid'=>$gid,
            ])->find();

            $pre_info['goods_image'] = cthumb($pre_info['goods_image']);
            $pre_info['goods_number'] = $num;
            $pre_info['goods_dingjin'] = $pre_info['pre_deposit_price'] * $num;
            $pre_info['goods_spec'] = unserialize($goods_info['goods_spec']);
            echo json_encode(['status'=>200,'data'=>[
                'goods_info'=>$pre_info,
                'address_info'=>$address_info,
            ]]);die;
        } catch (Exception $e) {
            echo json_encode(['status'=>255,'msg'=>$e->getMessage()]);die;
        }
    }
    /**
     * @api {post} index.php?app=buy&mod=submitorder&sld_addons=presale 交定金下单接口
     * @apiVersion 0.1.0
     * @apiName submitorder
     * @apiGroup Presale
     * @apiDescription 交定金下单接口
     * @apiExample 请求地址:
     * curl -i http://site7.55jimu.com/cmobile/index.php?app=buy&mod=submitorder&sld_addons=presale
     * @apiParam {String} key 会员登录key值
     * @apiParam {Number} gid 商品id
     * @apiParam {Number} number 商品购买数量
     * @apiParam {Number} pre_id 活动id
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
        $gid = intval($_POST['gid']);
        $number = intval($_POST['number']);
        $pre_id = intval($_POST['pre_id']);
        $address_id = intval($_POST['address_id']);
        $pre_model = M('pre_buy','presale');
        $buy_model = $this->buy_model;
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
            $res_pay = $pre_model->testMemberPayDeposit($gid,$pre_id,$this->member_info['member_id'],$number);
            if($res_pay['status'] == 255){
                throw new Exception($res_pay['msg']);
            }
            $data = $model->table('presale,pre_goods')->join('left')->on('presale.pre_id=pre_goods.pre_id')->where([
                'presale.pre_id'=>$pre_id,
                'pre_goods.gid'=>$gid,
            ])->find();
            //下单支付
            $buy_model->begintransaction();
            $param = [
                'buyer_id'=>$this->member_info['member_id'],
                'buyer_name'=>$this->member_info['member_name'],
                'vid'=>$goods_info['vid'],
                'store_name'=>$goods_info['store_name'],
                'pre_id'=>$pre_id,
                'gid'=>$gid,
                'goods_name'=>$goods_info['goods_name'],
                'goods_image'=>$goods_info['goods_image'],
                'goods_num'=>$number,
                'goods_price'=>$data['pre_deposit_price'],
                'goods_price_finish'=>$pre_model->getPrePrice($pre_id,$gid),
                'order_amount'=>$data['pre_deposit_price'] * $number,
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
     * @api {post} index.php?app=buy&mod=topay&sld_addons=presale 去支付
     * @apiVersion 0.1.0
     * @apiName topay
     * @apiGroup Presale
     * @apiDescription 去支付
     * @apiExample 请求地址:
     * curl -i http://site7.55jimu.com/cmobile/index.php?app=buy&mod=topay&sld_addons=presale
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
        $buy_model = M('pre_buy','presale');
        $model = model();
        $order_model = M('pre_order','presale');
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
            //再次检验活动状态
            if($order['order_state'] == 10){
                $test = $buy_model->testMemberPayDepositOrder($order['gid'],$order['pre_id'],$order['buyer_id']);
                if($test['status'] == 255){
                    throw new Exception($test['msg']);
                }
            }elseif($order['order_state'] == 20){
                //检测交付尾款待定
                $test = $buy_model->testMemberPayFinishOrder($order['gid'],$order['pre_id'],$order['buyer_id']);
                if($test['status'] == 255){
                    throw new Exception($test['msg']);
                }
            }else{
                throw new Exception('订单不允许支付');
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
                    'sld_addons'=>'presale',
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
     * @api {get} index.php?app=buy&mod=payment&sld_addons=presale 支付方式列表
     * @apiVersion 0.1.0
     * @apiName payment
     * @apiGroup Presale
     * @apiDescription 支付方式列表
     * @apiExample 请求地址:
     * curl -i http://site7.55jimu.com/cmobile/index.php?app=buy&mod=payment&sld_addons=presale
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
        $order_model = M('pre_order','presale');
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



}