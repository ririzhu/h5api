<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/10/11
 * Time: 16:10
 */
class dianCtl extends mobileHomeCtl {
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
        $mb_user_token_info = $model_mb_user_token->getMbUserTokenInfoByToken($key);

        $model_member = Model('member');
        $member_info = $model_member->getMemberInfoByID($mb_user_token_info['member_id']);
        if(empty($member_info)) {
//            echo json_encode(['status'=>266,'msg'=>Language::get('请登录')]);die;
        } else {
            unset($member_info['member_passwd']);
            //读取卖家信息
            $this->member_info = $member_info;
        }
    }
    /**
     * @api {get} index.php?app=dian&mod=index&sld_addons=ldj 店铺首页信息
     * @apiVersion 0.1.0
     * @apiName index
     * @apiGroup Dian
     * @apiDescription 店铺首页信息以及栏目,购物车
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/cmobile/index.php?app=dian&mod=index&sld_addons=ldj
     * @apiParam {String} key 会员登录key值
     * @apiParam {Number} dian_id 店铺id
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} dian_info 门店信息
     * @apiSuccess {Json} stcids_list 栏目分类列表
     * @apiSuccess {Json} cart_list 返回购物车数据
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200,
     *         "dian_info": {
                                "id": "15",
                                "dian_name": "肯德基徐家汇店",
                                "dian_logo": "http://ldj.55jimu.com/data/upload/mall/dian//8_05714905263846781.jpg",
                                "error": 0,//门店是否有异常状态
                                "freight": "门店配送,基础运费5元",
                                "notice": "联到家商城开业啦~~"
     *                      },
     *         "stcids_list": {
                                    "4": {
                                            "stc_id": "4",
                                            "stc_name": "卧室",
                                            "stc_parent_id": "0",
                                            "stc_state": "1",
                                            "vid": "8",
                                            "stc_sort": "0"
                                    },
                                    "11": {
                                            "stc_id": "11",
                                            "stc_name": "书桌",
                                            "stc_parent_id": "0",
                                            "stc_state": "1",
                                            "vid": "8",
                                            "stc_sort": "0"
                                    },
                                    "23": {
                                            "stc_id": "23",
                                            "stc_name": "彩妆",
                                            "stc_parent_id": "0",
                                            "stc_state": "1",
                                            "vid": "8",
                                            "stc_sort": "1"
                                    }
     *                      }
     *         "cart_list": {
     *                          list:[
                                            {
                                                    "cart_id": "5",
                                                    "buyer_id": "243",
                                                    "vid": "15",
                                                    "store_name": "肯德基徐家汇店",
                                                    "gid": "1668",
                                                    "goods_name": "产品组合0961",
                                                    "goods_price": "189.00",
                                                    "goods_num": "3",
                                                    "goods_image": "http://ldj.55jimu.com/data/upload/mall/store/goods/8/8_05895494874151855_240.jpg",
                                                    "error": 0
                                            }
     *                               ],
     *                          all_money:100
     *                      }
     *      }
     *
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *
     */
    public function index()
    {
        $dian_id = intval($_GET['dian_id']);
        $dian_model = M('ldj_dian','ldj');
        $cart_model = M('ldj_cart','ldj');

        $dian_info = $dian_model->getDianInfo(['id'=>$dian_id],$field='id,vid,dian_name,dian_logo,status,ldj_status,operation_time,ldj_delivery_order_Price,ldj_notice');
        //检测门店所属店铺是否开启
        $vendor_info = $dian_model->table('vendor')->where(['vid'=>$dian_info['vid']])->field('store_state')->find();
        //店铺log
        $dian_info['dian_logo'] = UPLOAD_SITE_URL.DS.ATTACH_PATH.DS.'dian'.DS.$dian_info['vid'].DS.$dian_info['dian_logo'];
        //门店营业状态判断
        $dian_info['error'] = 0;
        $time = explode('-',date('H-i'));
        $times = $time[0]*60+$time[1];
        $operation_time = explode(',',$dian_info['operation_time']);
        if($times<$operation_time[0] || $times>$operation_time[1]){
            $dian_info['error'] = 1;
            $dian_info['errormsg'] = '休息中';
        }
        if(!$dian_info['status'] || !$dian_info['ldj_status'] || $vendor_info['store_state'] != 1){
            $dian_info['error'] = 1;
            $dian_info['errormsg'] = '暂停营业';
        }
        //配送运费
        $dian_info['freight'] = '门店配送,基础运费'.$dian_info['ldj_delivery_order_Price'].'元';
        //查询店铺分类
        $stcids_list = $dian_model->getVendorCate($dian_id);
        //购物车列表
        $cart_list = $cart_model->getVendorCartlistGoods($dian_id,$this->member_info['member_id']);

        echo json_encode(['status'=>200,'dian_info'=>$dian_info,'stcids_list'=>$stcids_list,'cart_list'=>$cart_list]);die;

    }
    /**
     * @api {get} index.php?app=dian&mod=search_goods&sld_addons=ldj 门店首页通过栏目搜索商品
     * @apiVersion 0.1.0
     * @apiName search_goods
     * @apiGroup Dian
     * @apiDescription 门店首页通过栏目搜索商品
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/cmobile/index.php?app=dian&mod=search_goods&sld_addons=ldj
     * @apiParam {String} key 会员登录key值
     * @apiParam {Number} dian_id 门店id
     * @apiParam {String} stcid 搜索条件 (店铺分类id,数字表示店铺分类搜索),('recommend'表示搜索推荐商品)
     * @apiParam {String} order 排序 ('p'价格排序),('s'销量排序)
     * @apiParam {String} ordertype 排序规格 ('asc'升序),('desc'降序)
     * @apiParam {Number} page 分页
     * @apiParam {Number} pn 当前页数
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} goods_list 商品列表
     * @apiSuccess {Json} cart_list 返回购物车数据
     * @apiSuccessExample {json} 成功的例子:
     *
     *      {
     *         "status":200,
     *         "goods_list": {
     *                          list:{
                                            {
                                                    "gid": "1517",
                                                    "goods_name": "商品测试0037",
                                                    "goods_image": "http://ldj.55jimu.com/data/upload/mall/store/goods/8/8_05798949663940836_240.jpg",
                                                    "goods_price": "1220.00",
                                                    "dian_id": "15",
                                                    "month_sales": "0",
                                                    "cart_num": 0
                                            },
                                            {
                                                    "gid": "1606",
                                                    "goods_name": "意大利红酒 oversize 33",
                                                    "goods_image": "http://ldj.55jimu.com/data/upload/mall/store/goods/8/8_05884476876793652_240.jpg",
                                                    "goods_price": "500.00",
                                                    "dian_id": "15",
                                                    "month_sales": "0",
                                                    "cart_num": 0
                                            },
     *                                }
     *                         ismore:{
                                            "hasmore": true,
                                            "page_total": 2
     *                                }
     *                         "count_list": "18"
     *                       },
     *         "cart_list": {
     *                          list:{
                                            {
                                                    "cart_id": "5",
                                                    "buyer_id": "243",
                                                    "vid": "15",
                                                    "store_name": "肯德基徐家汇店",
                                                    "gid": "1668",
                                                    "goods_name": "产品组合0961",
                                                    "goods_price": "189.00",
                                                    "goods_num": "3",
                                                    "goods_image": "http://ldj.55jimu.com/data/upload/mall/store/goods/8/8_05895494874151855_240.jpg",
                                                    "error": 0
                                            }
     *                               },
     *                          all_money:100
     *                      }
     *      }
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "暂无相关产品"
     *      }
     *     /
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *
     */
    public function search_goods()
    {
        //店铺id
        $vid = $_GET['dian_id'];
        //栏目id
        $stcid = $_GET['stcid'];

        $dian_model = M('ldj_dian','ldj');

        $where = [
            'dian_goods.dian_id'=>$vid,
            'dian_goods.stock'=>['gt',0],
            'dian_goods.off'=>0,
            'dian_goods.delete'=>0,
            'goods.gid'=>['gt',0],
        ];

        if(is_numeric($stcid) && $stcid > 0){
            //通过栏目搜索
            $gids = $dian_model->table('ldj_goods_extend')->where(['goods_stcids_1'=>['exp','FIND_IN_SET('.$stcid.',goods_stcids_1)']])->field('goods_id')->select();
            $gids = low_array_column($gids,'goods_id');
            $where['dian_goods.goods_id'] = ['in',implode(',',$gids)];
        }elseif($stcid == 'recommend'){
            //取推荐
            $where['goods.goods_commend'] = 1;
        }elseif($stcid == 'all'){
            //取全部
        }
        $this->search_dian($where);
    }
    /*
     * 店内搜索产品
     */
    private function search_dian($where)
    {
        //分页
        $page = $_GET['page'];
        //店铺id
        $vid = $_GET['dian_id'];

        $goods_model = M('ldj_goods','ldj');
        $cart_model = M('ldj_cart','ldj');

        //店内的商品

        $goodsfield = 'goods.gid,goods.goods_name,goods.goods_image,goods.goods_price,dian_goods.dian_id,dian_goods.month_sales';
        if(trim($_GET['order']) == 's'){

            //销量排序
            $goodsorder = 'dian_goods.month_sales '.$_GET['ordertype'];

        }elseif(trim($_GET['order']) == 'p'){

            //价格排序
            $goodsorder = 'goods.goods_price '.$_GET['ordertype'];

        }else{

            //默认时间排序
            $goodsorder = 'dian_goods.id '.$_GET['ordertype'];
        }

        $limit = '';
        $goods_list = $goods_model->getGoodsList($where,$goodsfield,$page,$goodsorder,$limit,'');
        $count_list = $goods_model->gettotalnum();
        $page_count_goods = mobile_page($goods_model->gettotalpage());

        if($goods_list){
            foreach($goods_list as $k=>$v){
                //销量
                if($v['month_sales']>10000){
                    $goods_list[$k]['month_sale'] = number_format($v['month_sales']/10000,1).'万';
                }
                //统计商品购物车数量
                $goodscartnum = $cart_model->getGoodsCartInfo(['buyer_id'=>$this->member_info['member_id'],'vid'=>$vid,'gid'=>$v['gid']])['goods_num'];
                $goods_list[$k]['cart_num'] = $goodscartnum>0?$goodscartnum:0;

                //商品图片
                $goods_list[$k]['goods_image'] = cthumb($v['goods_image']);

            }
            $cart_list = $cart_model->getVendorCartlistGoods($vid,$this->member_info['member_id']);
            echo json_encode(['status'=>200,'goods_list'=>['list'=>$goods_list,'ismore'=>$page_count_goods,'count_list'=>$count_list],'cart_list'=>$cart_list]);die;
        }
        echo json_encode(['status'=>255,'msg'=>'暂无相关产品']);die;
    }


    /**
     * @api {post} index.php?app=dian&mod=dian_info_function&sld_addons=ldj 店铺详情信息
     * @apiVersion 0.1.0
     * @apiName dian_info_function
     * @apiGroup Dian
     * @apiDescription 店铺详情信息
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/cmobile/index.php?app=dian&mod=dian_info_function&sld_addons=ldj
     * @apiParam {String} key 会员登录key值
     * @apiParam {Number} dian_id 店铺id
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} data 门店信息
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200,
     *         "data": {
                                    "id": "15",
                                    "vid": "8",
                                    "dian_name": "肯德基徐家汇店",
                                    "dian_logo": "http://ldj.55jimu.com/data/upload/mall/dian/8/8_05714905263846781.jpg",
                                    "month_sales": "0",
                                    "dian_phone": "15921035735",
                                    "dian_address": "上海徐汇区枫林路街道宛平南路100号建科大厦",
                                    "operation_time": "480,1320",
                                    "freight": "门店配送,基础运费5元",
                                    "notice": "联到家商城开业啦~~",
                                    "product_name": "大型超市",
                                    "businessTime": "08:00-22:00"
     *                      }
     *
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *
     */
    public function dian_info_function()
    {

        $dian_id = intval($_POST['dian_id']);
        $dian_model = M('ldj_dian','ldj');
        $condition = [
            'id'=>$dian_id
        ];
        $dian_info = $dian_model->getDianInfo($condition,$field='id,vid,dian_name,dian_logo,month_sales,dian_phone,dian_address,operation_time,ldj_delivery_order_Price,ldj_notice');
        //店铺log
        $dian_info['dian_logo'] = UPLOAD_SITE_URL.DS.ATTACH_PATH.DS.'dian'.DS.$dian_info['vid'].DS.$dian_info['dian_logo'];
        //店铺联系方式
        $dian_info['dian_phone'] = explode(',',$dian_info['dian_phone'])[0];
        //配送运费
        $dian_info['freight'] = '门店配送,基础运费'.$dian_info['ldj_delivery_order_Price'].'元';

        //营业时间
        $businessTime = explode(',',$dian_info['operation_time']);
        $businessStr = '';
        $businessStr .= sprintf("%'02d",floor($businessTime[0]/60)).':'.sprintf("%'02d",$businessTime[0]%60);
        $businessStr .= '-';
        $businessStr .= sprintf("%'02d",floor($businessTime[1]/60)).':'.sprintf("%'02d",$businessTime[1]%60);
        $dian_info['businessTime'] = $businessStr;
        echo json_encode(['status'=>200,'data'=>$dian_info]);die;
    }
    /*
     * 检查登录
     */
    public function checklogin()
    {
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
}