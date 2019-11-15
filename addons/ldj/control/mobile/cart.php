<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/10/10
 * Time: 18:26
 */
class cartCtl extends mobileHomeCtl {
    private $member_info;
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
     * @api {post} index.php?app=cart&mod=editcart&sld_addons=ldj 加减购物车接口
     * @apiVersion 0.1.0
     * @apiName editcart
     * @apiGroup Cart
     * @apiDescription 加减购物车接口
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/cmobile/index.php?app=cart&mod=editcart&sld_addons=ldj
     * @apiParam {String} key 会员登录key值
     * @apiParam {Number} gid 商品gid
     * @apiParam {Number} dian_id 店铺id
     * @apiParam {Number} quantity 数量
     * @apiSuccess {Number} status 状态
     * @apiSuccess {String} msg 返回说明信息
     * @apiSuccess {Json} cart_list 返回购物车数据
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200
     *         "msg": "操作成功",
     *         "cart_list": {
     *                          list:{购物车列表},
     *                          all_money:100
     *                      }
     *      }
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "商品已下架"
     *      }
     *     /
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *
     */
    public function editcart(){

        $gid = intval($_POST['gid']);
        $dian_id = intval($_POST['dian_id']);

        $quantity = intval($_POST['quantity']);

        $cart_model = M('ldj_cart','ldj');
        $goods_model = M('ldj_goods','ldj');
//        $dian_model = M('ldj_dian','ldj');
//        $dian_state = $dian_model->getDianInfo(['id'=>$dian_id],$field='status,operation_time');
        //判断门店状态能否加入购物车
//        $time = explode('-',date('H-i'));
//        $times = $time[0]*60+$time[1];
//        $operation_time = explode(',',$dian_state['operation_time']);
//        if(!$dian_state['status'] || ($times<$operation_time[0] || $times>$operation_time[1])){
//            echo json_encode(['status'=>255,'msg'=>'门店休息了']);die;
//        }
        $condition = [
            'gid'=>$gid,
            'vid'=>$dian_id,
            'buyer_id'=>$this->member_info['member_id']
        ];
        $ishascart = $cart_model->getGoodsCartInfo($condition);
        $goods_state = $goods_model->getDianGoods(['goods_id'=>$gid,'dian_id'=>$dian_id]);
        $goods_info = $goods_model->table('goods')->where(['gid'=>$gid])->find();
        $dian_info = $goods_model->table('dian')->where(['id'=>$dian_id])->find();
        if(!$dian_info['status'] || !$dian_info['ldj_status']){
            echo json_encode(['status'=>255,'msg'=>'门店已关闭']);die;
        }
        if(!$goods_state || !$goods_info){
            echo json_encode(['status'=>255,'msg'=>'商品不存在']);die;
        }
        if($goods_state['off'] || $goods_state['delete']){
            echo json_encode(['status'=>255,'msg'=>'失效']);die;
        }
        if($goods_state['stock'] < $quantity){
            echo json_encode(['status'=>255,'msg'=>'库存不足']);die;
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
                echo json_encode(['status'=>255,'msg'=>'操作失败']);die;
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
                echo json_encode(['status'=>255,'msg'=>'操作失败']);die;
            }
        }
        $cart_list = $cart_model->getVendorCartlistGoods($dian_info['id'],$this->member_info['member_id']);
        echo json_encode(['status'=>200,'msg'=>'操作成功','cart_list'=>$cart_list]);die;
    }
    /**
     * @api {post} index.php?app=cart&mod=deletecart&sld_addons=ldj 删除购物车
     * @apiVersion 0.1.0
     * @apiName deletecart
     * @apiGroup Cart
     * @apiDescription 删除购物车
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/cmobile/index.php?app=cart&mod=deletecart&sld_addons=ldj
     * @apiParam {String} key 会员登录key值
     * @apiParam {Number} type 1:通过购物车id删除,2:通过店铺id删除购物车
     * @apiParam {Array} cart_ids type1时必填,购物车id数组,数组形式[1,2,3]
     * @apiParam {Number} vid type2时必填,店铺id
     * @apiSuccess {Number} status 状态
     * @apiSuccess {String} msg 返回说明信息
     * @apiSuccess {Json} cart_list 返回购物车数据
     * @apiSuccessExample {json} 成功的例子:
     *
     *    type:1
     *      {
     *         "status":200,
     *         "msg": "操作成功",
     *         "cart_list": {
     *                          list:{购物车列表},
     *                          all_money:100
     *                      }
     *      }
     *
     *    type:2
     *       {
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
     *     /
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *
     */
    public function deletecart()
    {
        $cart_model = M('ldj_cart','ldj');
        if($_POST['type'] == 1) {
            //通过购物车id删除购物车
            $cart_ids = $_POST['cart_ids'];
            if(is_string($cart_ids)){
                $cart_ids = explode(',',$cart_ids);
            }
            if(!is_array($cart_ids) || count($cart_ids)<1){
                echo json_encode(['status'=>255,'msg'=>'操作失败']);die;
            }
            $where = [
                'cart_id'=>['in',$cart_ids],
                'buyer_id'=>$this->member_info['member_id']
            ];
            $cart_info = $cart_model->getGoodsCartInfo($where);
            if($cart_info){
                $res = $cart_model->deletecart($where);
                if(!$res){
                    echo json_encode(['status'=>255,'msg'=>'操作失败']);die;
                }
                $cart_list = $cart_model->getVendorCartlistGoods($cart_info['vid'],$this->member_info['member_id']);
                echo json_encode(['status'=>200,'msg'=>'操作成功','cart_list'=>$cart_list]);die;
            }
            echo json_encode(['status'=>255,'msg'=>'操作失败']);die;
        }elseif($_POST['type'] == 2){
            //通过店铺id删除购物车
            $vid = $_POST['vid'];
            if(!$vid){
                echo json_encode(['status'=>255,'msg'=>'操作失败']);die;
            }
            $where = [
                'vid'=>$vid,
                'buyer_id'=>$this->member_info['member_id']
            ];
            $res = $cart_model->deletecart($where);
            if(!$res){
                echo json_encode(['status'=>255,'msg'=>'操作失败']);die;
            }
            echo json_encode(['status'=>200,'msg'=>'操作成功']);die;
        }else{
            echo json_encode(['status'=>255,'msg'=>'操作失败']);die;
        }


    }
    /**
     * @api {post} index.php?app=cart&mod=getAllCartList&sld_addons=ldj 获取用户所有购物车数据
     * @apiVersion 0.1.0
     * @apiName getAllCartList
     * @apiGroup Cart
     * @apiDescription 获取用户所有购物车数据
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/cmobile/index.php?app=cart&mod=getAllCartList&sld_addons=ldj
     * @apiParam {String} key 会员登录key值
     * @apiParam {String} latitude 纬度
     * @apiParam {String} longitude 经度
     * @apiSuccess {Number} status 状态
     * @apiSuccess {String} msg 返回说明信息
     * @apiSuccess {Json} cart_list 返回购物车数据
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200,
     *         "data": {
     *                     vid:7,
     *                     store_name:"测试1",
     *                     distance:"82.474629113516",
     *                     error:0/1,(0没有异常状态)
     *                     errormsg:"不在配送范围内",
     *                     cart_list:{
     *                              "all_money": 39690
     *                              list:{
     *                                      "cart_id": "15",
     *                                      "buyer_id": "243",
     *                                      "vid": "7",
     *                                      "store_name": "测试1",
     *                                      "gid": "152",
     *                                      "goods_name": "沁格家具 简约时尚蓝白相间大床 经典板式双人床 独特创意十字条纹",
     *                                      "goods_price": "2333.00",
     *                                      "goods_num": "3",
     *                                      "goods_image": "http://ldj.55jimu.com/data/upload/mall/store/goods/8/8_05707408406603196_240.jpg",
     *                                      "error": 1,
     *                                      "error": "下架"
     *                                   }
     *
     *                                  },
     *                  }
     *      }
     *
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "购物车为空"
     *      }
     *     /
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *
     */
    public function getAllCartList()
    {
        $latitude = $_POST['latitude'];
        $longitude = $_POST['longitude'];
        $cart_model = M('ldj_cart','ldj');
        $returndata = [];
        $vid_group = $cart_model->table('ldj_cart')->where(['buyer_id'=>$this->member_info['member_id']])->group('vid')->select();

        foreach($vid_group as $k=>$v)
        {
            $returndata[$k]['vid'] = $v['vid'];
            $returndata[$k]['store_name'] = $v['store_name'];
            $vendorcartlist = $cart_model->getVendorCartlistGoods($v['vid'],$this->member_info['member_id']);

            $returndata[$k]['cart_list'] = $vendorcartlist;

            //距离,开关,营业时间判断
            $dian_info = $cart_model->table('dian')->where(['id'=>$v['vid']])->find();
            $returndata[$k]['distance'] = _distance($latitude,$longitude,$dian_info['dian_lng'],$dian_info['dian_lat']);
            $returndata[$k]['error'] = 0;
            if($returndata[$k]['distance'] > ($dian_info['ldj_delivery_order_MaxDistance']*1000)){
                $returndata[$k]['error'] = 1;
                $returndata[$k]['errormsg'] = '不在配送范围内';
            }
            $time = explode('-',date('H-i'));
            $times = $time[0]*60+$time[1];
            $operation_time = explode(',',$dian_info['operation_time']);
            if($times < $operation_time[0] || $times > $operation_time[1]){
                $returndata[$k]['error'] = 1;
                $returndata[$k]['errormsg'] = '休息中';
            }
            if($dian_info['ldj_status'] != 1){
                $returndata[$k]['error'] = 1;
                $returndata[$k]['errormsg'] = '关闭中';
            }

        }
        if(!$returndata){
            echo json_encode(['status'=>255,'msg'=>'购物车为空']);die;
        }
        //更新查看状态
        $cart_model->table('ldj_cart')->where(['buyer_id'=>$this->member_info['member_id'],'look_status'=>0])->update(['look_status'=>1]);
        echo json_encode(['status'=>200,'data'=>$returndata]);die;
    }
    /*
     * 获取购物车查看状态
     * key 会员登录信息
     */
    public function getCartLookStatus()
    {
        $cart_model = M('ldj_cart','ldj');
        $res = $cart_model->table('ldj_cart')->where([
            'buyer_id'=>$this->member_info['member_id'],
            'look_status'=>0,
        ])->find();
        if($res){
            echo 1;die;
        }else{
            echo 0;die;
        }
    }
}