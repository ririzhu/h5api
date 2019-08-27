<?php
/**
 * WAP端个人中心
 *
 */
defined('DYMall') or exit('Access Invalid!');

class points_buyCtl extends mobileCtl
{

    protected $member_info = array();

    public function __construct()
    {
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
     * @api {post} index.php?app=points_buy&mod=confirm&sld_addons=points 积分商品下单接口
     * @apiVersion 0.1.0
     * @apiName confirm
     * @apiGroup PointsOrder
     * @apiDescription 积分商品下单接口
     * @apiExample 请求地址:
     * curl -i http://site2.slodon.cn/cmobile/index.php?app=points_buy&mod=confirm&sld_addons=points
     * @apiParam {String} key 会员登录key值
     * @apiParam {Number} ifcart 是否来自购物车0/1
     * @apiParam {String} cart_id 购物车数据:如果来自购物车则用"22|1,62|2",<购物车id|数量,购物车id|数量>格式,如果来自立即购买:则用"20|1",<商品id|数量>格式
     * @apiSuccess {Number} status 状态
     * @apiSuccess {String} datas 返回数据
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200
     *         "datas": {
     *                  "store_cart_list":商品列表,
     *                  "all_money":1000,
     *                  "all_num":5,
     *                  "address_info":用户默认收货地址
     *               }
     *      }
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "商品不存在"
     *     }
     *
     */
    public function confirm()
    {
        $model = M('points_goods');
        $model_points_cart = model();
//        $_POST['cart_id'] = '22|1,62|2';
//        $_POST['ifcart'] = 1;
        //来自购物车
        if($_POST['ifcart']){
//        if(1){
            $cartids = explode(',',$_POST['cart_id']);
            $cartdata = [];
            foreach($cartids as $k=>$v){
                $data = explode('|',$v);
               $cart_info =  $model_points_cart->table('points_cart')->where(['pcart_id'=>$data[0]])->find();
                //组装数据pgid=>number
                $cartdata[$cart_info['pgid']] = $cart_info['pgoods_choosenum'];
            }

        }else{
            //立即购买
            $data = explode('|',$_POST['cart_id']);
            $goods_gid = $data[0];
            $goods_num = $data[1];
            //组装数据
            $cartdata = [$goods_gid=>$goods_num];
        }


        $goods_list = [];
        foreach($cartdata as $gid=>$num){
            $goods_info = $model->getGoodsDetail(['pgid'=>$gid,'pgoods_show'=>1,'pgoods_state'=>0]);
            if($goods_info){
                $goods_info['number'] = $num;
                $goods_list[]  = $goods_info;
            }
        }


        if($goods_list){
            //统计总价格
            $all_money = 0;
            //统计总数量
            $all_num = 0;
            foreach($goods_list as $k=>$v)
            {
                $all_money += $v['pgoods_points']*$v['number'];
                $all_num += $v['number'];
                $goods_list[$k]['goods_image_url'] = pointprodThumb($v['pgoods_image']);
                $goods_list[$k]['url'] = POINTS_WAP_SITE_URL.'/cwap_goods_datail.html?gid='.$v['pgid'];
            }

            //输出用户默认收货地址
            $address_info = Model('address')->getDefaultAddressInfo(array('member_id'=>$this->member_info['member_id']));
            echo json_encode(['status'=>200,'datas'=>['store_cart_list'=>$goods_list,'all_money'=>$all_money,'all_num'=>$all_num,'address_info'=>$address_info]]);
            die;
        }else{
            echo json_encode(['status'=>255,'msg'=>'商品不存在']);
            die;
        }

    }


    /**
     * @api {post} index.php?app=points_buy&mod=submitorder&sld_addons=points 积分商品下单支付接口
     * @apiVersion 0.1.0
     * @apiName submitorder
     * @apiGroup PointsOrder
     * @apiDescription 积分商品下单支付接口
     * @apiExample 请求地址:
     * curl -i http://site2.slodon.cn/cmobile/index.php?app=points_buy&mod=submitorder&sld_addons=points
     * @apiParam {String} key 会员登录key值
     * @apiParam {String} cart_id 购物车数据:如果来自购物车则用"22|1,62|2",<购物车id|数量,购物车id|数量>格式,如果来自立即购买:则用"20|1",<商品id|数量>格式
     * @apiParam {Number} ifcart 是否来自购物车0/1
     * @apiParam {Number} address_id 会员收货地址id
     * @apiSuccess {Number} status 状态
     * @apiSuccess {String} msg 返回数据
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200
     *         "msg": "支付成功"
     *      }
     * @apiError {Number} status 状态255/266(255生成订单失败,266生成订单成功,但是余额不足)
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "支付失败"
     *      }
     *      /
     *      {
     *          "status":266,
     *          "msg": "余额不足"
     *      }
     *
     */
    public function submitorder()
    {
        $pointsordermodel = Model('pointorder');
        $cart_id = $_POST['cart_id'];
        $address_id = $_POST['address_id'];
        $ifcart = $_POST['ifcart'];
        //通过购物车组装商品数据
        $goods_data = $this->builddata($cart_id,$ifcart);
        if($goods_data['status'] == 255){
            echo json_encode(['status'=>255,'msg'=>$goods_data['msg']]);die;
        }
        $data = $goods_data['data'];
        try{
            //总金额
            $allmoney = 0;
            $pointsordermodel->begintransaction();
//            dd($data['goods_list']);die;
            //开始验证
            foreach($data['goods_list'] as $k=>$v){
                //限购
                if($v['pgoods_islimit'] && $v['pgoods_limitnum']<$v['number']){
                    throw new Exception('商品超出限制购买数量');
                }
                //库存
                if($v['pgoods_storage']<$v['number']){
                    throw new Exception('商品库存不足');
                }
                $allmoney += $v['pgoods_points'] * $v['number'];

            }


            //存订单表
            $pointsorderdata = [
                'point_ordersn'=>$pointsordermodel->point_snOrder(),
                'point_buyerid'=>$this->member_info['member_id'],
                'point_buyername'=>$this->member_info['member_name'],
                'point_buyeremail'=>$this->member_info['member_email'],
                'point_addtime'=>time(),
                'point_outsn'=>$pointsordermodel->point_outSnOrder(),
                'point_allpoint'=>$allmoney,
                'point_orderamount'=>0,
                'point_shippingcharge'=>0,
                'point_shippingfee'=>0,
                'point_ordermessage'=>'',
                'point_orderstate'=>10,
            ];
            $order_id = $pointsordermodel->addPointOrder($pointsorderdata);
            if(!$order_id){
                throw new Exception('生成订单失败');
            }


            //添加订单商品
            $pointprod_model = Model('pointprod');
            $pointcart_model = Model('pointcart');
            foreach($data['goods_list'] as $k=>$val){
                $order_goods_array	= array();
                $order_goods_array['point_orderid']		= $order_id;
                $order_goods_array['point_goodsid']		= $val['pgid'];
                $order_goods_array['point_goodsname']	= $val['pgoods_name'];
                $order_goods_array['point_goodspoints']	= $val['pgoods_points'];
                $order_goods_array['point_goodsnum']	= $val['number'];
                $order_goods_array['point_goodsimage']	= $val['pgoods_image'];
                $update_res = $pointsordermodel->addPointOrderProd($order_goods_array);
                if(!$update_res) {
                    throw new Exception('生成订单失败');
                }

                //更新积分礼品库存
                $pointprod_uparr = array();
                $pointprod_uparr['pgoods_salenum'] = array('exp','pgoods_salenum + '.$order_goods_array['point_goodsnum']);
                $pointprod_uparr['pgoods_storage'] = array('exp','pgoods_storage - '.$order_goods_array['point_goodsnum']);
                $update_res = $pointprod_model->updatePointgoods(array('pgid'=>$val['pgid']),$pointprod_uparr);
                if(!$update_res) {
                    throw new Exception('生成订单失败');
                }

                unset($pointprod_uparr);
                unset($order_goods_array);
                $del_condition['pgid'] = $val['pgid'];
                $pointcart_model->dropPointCart($del_condition);
            }

            //保存买家收货地址
            $address_model		= Model('address');
            if(intval($address_id) > 0) {
                $address_info = $address_model->getOneAddress(intval($address_id));
                //sql注入过滤转义
                if (!empty($address_info) && !get_magic_quotes_gpc()){
                    foreach ($address_info as $k=>$v){
                        $address_info[$k] = addslashes(trim($v));
                    }
                }
                //添加订单收货地址
                if (is_array($address_info) && count($address_info)>0){
                    $address_array		= array();
                    $address_array['point_orderid']		= $order_id;
                    $address_array['point_truename']	= $address_info['true_name'];
                    $address_array['point_areaid']		= $address_info['area_id'];
                    $address_array['point_areainfo']	= $address_info['area_info'];
                    $address_array['point_address']		= $address_info['address'];
                    $address_array['point_zipcode']		= $address_info['zip_code'];
                    $address_array['point_telphone']	= $address_info['tel_phone'];
                    $address_array['point_mobphone']	= $address_info['mob_phone'];
                    $res = $pointsordermodel->addPointOrderAddress($address_array);
                    if(!$res) {
                        throw new Exception('生成订单失败');
                    }
                }else{
                    throw new Exception('生成订单失败');
                }
            }else{
                throw new Exception('生成订单失败');
            }

            $pointsordermodel->commit();
            //支付
            if($this->member_info['member_points'] < $allmoney) {
                echo json_encode(['status'=>266,'msg'=>'积分余额不足']);die;
            }else{
                //扣除会员积分
                $points_model = Model('points');
                $insert_arr['pl_memberid'] = $this->member_info['member_id'];
                $insert_arr['pl_membername'] = $this->member_info['member_name'];
                $insert_arr['pl_points'] = -$allmoney;
                $insert_arr['point_ordersn'] = $pointsorderdata['point_ordersn'];
                $pay_res = $points_model->savePointsLog('pointorder',$insert_arr);
                if($pay_res) {
                    $pointsordermodel->table('points_order')->where(['point_orderid'=>$order_id])->update(['point_orderstate'=>20]);
                    echo json_encode(['status'=>200,'msg'=>'支付成功']);die;
                }
            }


        }catch(Exception $e){
            $pointsordermodel->rollback();
            echo json_encode(['status'=>255,'msg'=>$e->getMessage()]);die;

        }
    }
    /*
     * 通过积分组装积分订单数据
     */
    public function builddata($cart_id,$ifcart)
    {
        $model = M('points_goods');
        $model_points_cart = model();
        //来自购物车
        if($ifcart){
//        if(1){
            $cartids = explode(',',$cart_id);
            $cartdata = [];
            foreach($cartids as $k=>$v){
                $data = explode('|',$v);
                $cart_info =  $model_points_cart->table('points_cart')->where(['pcart_id'=>$data[0]])->find();
                //组装数据pgid=>number
                $cartdata[$cart_info['pgid']] = $cart_info['pgoods_choosenum'];
            }

        }else{
            //立即购买
            $data = explode('|',$cart_id);
            $goods_gid = $data[0];
            $goods_num = $data[1];
            //组装数据
            $cartdata = [$goods_gid=>$goods_num];
        }


        $goods_list = [];
        foreach($cartdata as $gid=>$num){
            $goods_info = $model->getGoodsDetail(['pgid'=>$gid,'pgoods_show'=>1,'pgoods_state'=>0]);
            if($goods_info){
                $goods_info['number'] = $num;
                $goods_list[]  = $goods_info;
            } else {
                return ['status'=>255,'msg'=>'商品已下架'];
            }
        }


        if($goods_list){
            return ['status'=>200,'data'=>['goods_list'=>$goods_list]];
        }
        return ['status'=>255,'msg'=>'商品不存在'];
    }


    /**
     * @api {post} index.php?app=points_buy&mod=address_list&sld_addons=points 下单选择地址列表
     * @apiVersion 0.1.0
     * @apiName address_list
     * @apiGroup PointsOrder
     * @apiDescription 用户下单选择地址列表
     * @apiExample 请求地址:
     * curl -i http://site2.slodon.cn/cmobile/index.php?app=points_buy&mod=address_list&sld_addons=points
     * @apiParam {String} key 会员登录key值
     * @apiSuccess {Number} code 状态
     * @apiSuccess {String} datas 返回数据
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "code":200
     *         "data": {
     *               "address_list":{
     *                                 0:{
     *                                      address:"北京市海淀区西小口东升科技园"
     *                                      address_id:"106"
     *                                      area_id:"37"
     *                                      area_info:"北京 北京市 东城区"
     *                                      city_id:"36"
     *                                      is_default:"1"
     *                                      member_id:"243"
     *                                      mob_phone:"13031159513"
     *                                      tel_phone:null
     *                                      true_name:"郭萧凯"
     *                                   }
     *                              }
     *               }
     *      }
     *
     */
    public function address_list() {
        $model_address = Model('address');
        $address_list = $model_address->getAddressList(array('member_id'=>$this->member_info['member_id']));
        output_data(array('address_list' => $address_list));
    }



    /**
     * @api {post} index.php?app=points_buy&mod=address_add&sld_addons=points 会员添加收货地址
     * @apiVersion 0.1.0
     * @apiName address_add
     * @apiGroup PointsOrder
     * @apiDescription 会员添加收货地址
     * @apiExample 请求地址:
     * curl -i http://site2.slodon.cn/cmobile/index.php?app=points_buy&mod=address_add&sld_addons=points
     * @apiParam {String} key 会员登录key值
     * @apiParam {String} true_name 真实姓名
     * @apiParam {String} mob_phone 手机号
     * @apiParam {String} address 地址
     * @apiParam {Number} city_id 市级ID
     * @apiParam {Number} area_id 地区ID
     * @apiParam {String} area_info 地区内容
     * @apiParam {Number} is_default 是否默认
     * @apiSuccess {Number} code 状态
     * @apiSuccess {String} data 返回数据
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "code":200
     *         "datas": {
     *                  "address_id":55
     *               }
     *      }
     * @apiError {Number} code 状态
     * @apiError {String} error 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "code":200,
     *          "datas": {
     *                      "error":"保存失败"
     *                  }
     *     }
     *
     */
    public function address_add() {
        $model_address = Model('address');
        $type=2;
        $address_info = $this->_address_valid($type);
        //判断如果设为默认地址，则需要把之前的所有默认地址置为0
        if($address_info['is_default'] == 1){
            //根据member_id和is_default==1查询数据
            $default_list = $model_address -> getAddressList(array('member_id'=>$this->member_info['member_id'],'is_default'=>1));
            //如果之前有数据 需要把之前的数据的is_default都置为0
            if(!empty($default_list)&&is_array($default_list)){
                $model_address -> beginTransaction();
                foreach ($default_list as $key => $val){
                    //根据address_id 编辑数据
                    $pre_result = $model_address -> editAddress(array('is_default'=>0), array('address_id' => $val['address_id']));
                    if(!$pre_result){
                        $model_address -> rollback();
                        output_error('保存失败');
                    }
                }
                //最多保存20个收货地址
                $count = $model_address->getAddressCount(array('member_id'=>$this->member_info['member_id']));
                if ($count >= 20) {
                    $model_address -> rollback();
                    output_error('最多允许添加20个有效地址');
                }
                $result = $model_address->addAddress($address_info);
                if($result) {
                    $model_address -> commit();
                    output_data(array('address_id' => $result));
                } else {
                    $model_address -> rollback();
                    output_error('保存失败');
                }
            }
        }
        //增加地址
        //最多保存20个收货地址
        $count = $model_address->getAddressCount(array('member_id'=>$this->member_info['member_id']));
        if ($count >= 20) {
            output_error('最多允许添加20个有效地址');
        }
        $result = $model_address->addAddress($address_info);
        if($result) {
            output_data(array('address_id' => $result));
        } else {
            output_error('保存失败');
        }
    }
    /**
     * 验证地址数据
     */
    private function _address_valid($type) {
        $obj_validate = new Validate();
        $obj_validate->validateparam = array(
            array("input"=>$_POST["true_name"],"require"=>"true","message"=>'姓名不能为空'),
            array("input"=>$_POST["area_info"],"require"=>"true","message"=>'地区不能为空'),
            array("input"=>$_POST["address"],"require"=>"true","message"=>'地址不能为空'),
            array("input"=>$_POST['tel_phone'].$_POST['mob_phone'],'require'=>'true','message'=>'联系方式不能为空')
        );
        $error = $obj_validate->validate();
        if ($error != ''){
            output_error($error);
        }
        $data = array();
        $data['member_id'] = $this->member_info['member_id'];
        $data['true_name'] = $_POST['true_name'];
        $data['area_id'] = intval($_POST['area_id']);
        $data['city_id'] = intval($_POST['city_id']);
        $data['area_info'] = $_POST['area_info'];
        $data['address'] = $_POST['address'];
        $data['tel_phone'] = $_POST['tel_phone'];
        $data['mob_phone'] = $_POST['mob_phone'];
        if($type==1){
            $data['is_default']=0;
        }
        if($type==2){
            $data['is_default']=$_POST['is_default'];
        }
        return $data;
    }
}