<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/10/9
 * Time: 19:36
 */
class goodsCtl extends mobileHomeCtl {

    public function __construct(){
        parent::__construct();
        if(!C('sld_ldjsystem') || !C('ldj_isuse') || !C('dian') || !C('dian_isuse')){
            echo json_encode(['status'=>255,'msg'=>'当前模块未开启']);die;
        }
    }
    /**
     * @api {get} index.php?app=goods&mod=goods_list&sld_addons=ldj 联到家商品搜索接口
     * @apiVersion 0.1.0
     * @apiName goods_list
     * @apiGroup Goods
     * @apiDescription 联到家商品搜索接口,全局搜索和店铺搜索
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/cmobile/index.php?app=goods&mod=goods_list&sld_addons=ldj
     * @apiParam {Number} type 类型(1:全局搜索,2:店内搜索)
     * @apiParam {Number} vid 门店id(type为2时必填)
     * @apiParam {String} keyworld 关键字
     * @apiParam {String} latitude 纬度(type为1时必填)
     * @apiParam {String} longitude 经度(type为1时必填)
     * @apiParam {Number} page 分页
     * @apiParam {Number} pn 当前页数
     * @apiParam {String} key 用户登录标识(type为2时必填)
     * @apiSuccess {Number} status 返回状态
     * @apiSuccess {Json} data 返回数据信息
     * @apiSuccess {Json} ismore 分页信息
     * @apiSuccessExample {json} 成功的例子:
     *
     *      type为1时:
     *
     *      {
     *         status:200
     *         data: {
     *                  dian_list:[
     *                          {
     *                               "id": "7",
     *                               "vid": "2",
     *                               "dian_name": "测试1",
     *                               "dian_logo": "http://ldj.55jimu.com/data/upload/mall/dian/2/",
     *                               "delivery_type": ['上门自提','门店配送'],
     *                               "operation_time": "720,1320",
     *                               "distance": "82m",
     *                               "month_sale": "5万",
     *                               "cart_num": 5,
     *                                "goods_list":{
     *                                                   "gid": "493",
     *                                                   "goods_name": "英国伦敦+剑桥+曼彻斯特+约克+利物浦10日8晚自由行·英格兰深度游 温莎城堡巴斯巨石阵",
     *                                                   "goods_image": "http://ldj.55jimu.com/data/upload/mall/store/goods/7/7_05709704086902601_240.jpg",
     *                                                   "goods_price": "12222.00",
     *                                                   "dian_id": "7",
     *                                                   "month_sales": "10万+"
     *                                           }
     *                               }
     *                          ]
     *               }
     *         ismore:{
     *              hasmore:true,
     *              page_total:4
     *          }
     *      }
     *
     *      type为2时:
     *
     *              {
                        "status": 200,
                        //goods列表
                        "goods_list": {
                                "list": [

                                                {
                                                "gid": "1519",
                                                "goods_name": "测试商品0039",
                                                "goods_image": "8_05798950295489962.jpg",
                                                "goods_price": "450.00",
                                                "dian_id": "26",
                                                "month_sales": "0",
                                                "cart_num": 0
                                                },
                                                {
                                                "gid": "1520",
                                                "goods_name": "商品测试0040",
                                                "goods_image": "8_05798950626710813.jpg",
                                                "goods_price": "450.00",
                                                "dian_id": "26",
                                                "month_sales": "0",
                                                "cart_num": 0
                                                }
                                        ],
                                //分页信息
                                "ismore": {
                                                "hasmore": true,
                                                "page_total": 2
                                            }
                                },
                        //订单列表
                        "cart_list": {
                                "list": [],
                                "all_money": 0
                        }
     *              }
     *
     *
     *
     *
     *
     *
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *
              type为1时:
                {
                    status:255,
                    msg: "未找到相关产品"
                }

              type为2时:

               {
                    status:266,(会出现请登录提示)
                    msg:"请登录"
               }

               {
                    status:255,
                    "msg":'未找到相关数据'
               }
     *
     */
    public function goods_list()
    {
        if($_GET['type'] == 1){
            //全部搜索
            $this->search_all();

        }elseif($_GET['type'] == 2){
            //店内搜索
            $this->search_dian();

        }else{
            echo json_encode(['status'=>255,'msg'=>'未找到相关产品']);die;
        }
    }
    /*
     * 全部搜索产品
     */
    private function search_all()
    {
        //经度
        $latitude = trim($_GET['latitude']);
        //纬度
        $longitude = trim($_GET['longitude']);
        //分页
        $page = $_GET['page'];

        //会员是否登录
        $member_info = $this->getMemberInfo($_GET['key']);

        $dian_model = M('ldj_dian','ldj');
        $goods_model = M('ldj_goods','ldj');
        $cart_model = M('ldj_cart','ldj');
        $time = explode('-',date('H-i'));
        $times = ($time[0]*60) + $time[1];
        //条件
        $condition = [
            //状态开启
            'status'=>1,
            'ldj_status'=>1,
            //营业时间
            'operation_time'=>['exp','substring_index(operation_time,",",1) < '.$times.' and substring_index(operation_time,",",-1) > '.$times],
            //关键字
            'dian_name'=>['like','%'.trim($_GET['keyworld']).'%']
        ];

        //如果传入经纬度失败
        if(!$longitude || !$latitude){
            //如果没位置信息 则根据ip定位
            $url = 'http://restapi.amap.com/v3/ip';
            $post_data['key'] = C('gaode_serverkey');
            $post_data['ip'] = getIp();
            $re = request_post($url, false, $post_data);
            if($re) {
                $re = json_decode($re,true);
                if ($re['rectangle']) {
                    $rect = explode(';', $re['rectangle']);
                    foreach ($rect as $k => $v) {
                        $rect[$k] = explode(',', $v);
                    }
                    $longitude = $rect[0][0] + ($rect[1][0] - $rect[0][0]);
                    $latitude = $rect[0][1] + ($rect[1][1] - $rect[0][1]);
                }
            }
        }

        $field = 'id,vid,dian_name,dian_logo,month_sales,delivery_type,ldj_delivery_order_MinPrice,ldj_delivery_order_Price,operation_time,ROUND(6378.138 * 2 * ASIN(SQRT(POW( SIN( ( '.$longitude.' * PI() / 180 - dian_lat * PI() / 180 ) / 2 ), 2 ) + COS( '.$longitude.' * PI() / 180 ) * COS( dian_lat * PI() / 180 ) * POW( SIN( ( '.$latitude.' * PI() / 180 - dian_lng * PI( ) / 180 ) / 2 ), 2 ))) * 1000) AS distance';

        $order = 'distance asc';
        //门店带有关键字的
        $dian_list  = $dian_model->getDianList($condition,$field,'',$page,$order);

        //商品带有关键字的门店
        $where = [
            'goods.goods_name'=>['like','%'.trim($_GET['keyworld']).'%'],
            'dian_goods.stock'=>['gt',0],
            'dian_goods.off'=>0,
            'dian_goods.delete'=>0,
        ];
        $goodsfield = 'goods.gid,goods.goods_name,goods.goods_image,goods.goods_price,dian_goods.dian_id,dian_goods.month_sales';
        $goodspage = $page;
        $goodsorder = 'dian_goods.month_sales desc';
        $limit = '';
        $group = '';
        $goods_list_2 = $goods_model->getGoodsList($where,$goodsfield,$goodspage,$goodsorder,$limit,'dian_goods.dian_id');

        //合并分页
        $page_count_dian = mobile_page($dian_model->gettotalpage());
        $page_count_goods = mobile_page($dian_model->gettotalpage());
        if($page_count_goods['hasmore'] || $page_count_dian['hasmore']){
            $page_count = $page_count_goods['page_total']>=$page_count_dian['page_total']?$page_count_goods:$page_count_dian;

        }else{
            $page_count = $page_count_goods;
        }


        unset($condition['dian_name']);
        foreach($goods_list_2 as $k=>$v){

            $condition['id'] = $v['dian_id'];

            $dian_info = $dian_model->getDianList($condition,$field,'','',$order);

            if($dian_info){
                $dian_list[] = $dian_info[0];
            }
        }
        //距离总排序
        array_multisort(low_array_column($dian_list,'distance'),SORT_ASC,SORT_NUMERIC,$dian_list);

        if($dian_list){
            //组装数据
            foreach($dian_list as $k=>$v){

                //配送方式
                $dian_list[$k]['delivery_type'] = strtr($v['delivery_type'],['kuaidi'=>'门店配送','shangmen'=>'上门自提','mendian'=>'门店配送']);
                $dian_list[$k]['delivery_type'] = array_unique(explode(',',$dian_list[$k]['delivery_type']));

                //距离
                $dian_list[$k]['distance'] = $v['distance']>1000?round($v['distance']/1000,1).'km':round($v['distance'],1).'m';

                //logo
                $dian_list[$k]['dian_logo'] = UPLOAD_SITE_URL.DS.ATTACH_PATH.DS.'dian'.DS.$v['vid'].DS.$v['dian_logo'];

                //月销量
                if($v['month_sales']>10000){
                    $dian_list[$k]['month_sale'] = number_format($v['month_sales']/10000,1).'万';
                }

                //店铺购物车数量
                if($member_info) {
                    $dian_list[$k]['cart_num'] = count($cart_model->getVendorCartlist(['buyer_id'=>$member_info['member_id'],'vid'=>$v['id']]));
                }else{
                    $dian_list[$k]['cart_num'] = 0;
                }

                //店内的商品
                $where = [
                    'dian_goods.dian_id'=>$v['id'],
                    'goods.goods_name'=>['like','%'.trim($_GET['keyworld']).'%'],
                    'dian_goods.stock'=>['gt',0],
                    'dian_goods.off'=>0,
                    'dian_goods.delete'=>0,
                ];
                $goodsfield = 'goods.gid,goods.goods_name,goods.goods_image,goods.goods_price,dian_goods.dian_id,dian_goods.month_sales';
                $goodspage = '';
                $goodsorder = 'dian_goods.month_sales desc';
                $limit = '';
                $goods_list = $goods_model->getGoodsList($where,$goodsfield,$goodspage,$goodsorder,$limit,$group);


                if(count($goods_list) >= 2){

                    $dian_list[$k]['goods_list'] = $goods_list;

                }elseif(count($goods_list) == 1){

                        unset($where['goods.goods_name']);
                        //补上最多销售的一个商品
                        $limit = 1;
                        $goods_list_1 = $goods_model->getGoodsList($where,$goodsfield,$goodspage,$goodsorder,$limit,$group);
                    $goods_list[] = $goods_list_1[0];
                    $dian_list[$k]['goods_list'] = $goods_list;

                }else{

                    unset($where['goods.goods_name']);
                    //取销量最高的几个
                    $limit = '';
                    $dian_list[$k]['goods_list'] = $goods_model->getGoodsList($where,$goodsfield,$goodspage,$goodsorder,$limit,$group);
                }
                //商品图片
                array_walk($dian_list[$k]['goods_list'],function(&$vv){
                    //销量
                    if($vv['month_sales']>10000){
                        $vv['month_sales'] = number_format($vv['month_sales']/10000,1).'万';
                    }
                    $vv['goods_image'] = cthumb($vv['goods_image']);
                });



                $dian_list[$k]['freight'] = '起送 ¥'.$v['ldj_delivery_order_MinPrice'].'元 基础运费¥'.$v['ldj_delivery_order_Price'].'元';


            }

            echo json_encode(['status'=>200,'data'=>$dian_list,'ismore'=>$page_count]);die;
        }
        echo json_encode(['status'=>255,'msg'=>'未找到相关数据']);die;
    }
    /*
     * 店内搜索产品
     */
    private function search_dian()
    {
        //用户标识
        $key = $_GET['key'];
        $member_info = $this->getMemberInfo($key);
//        if(!$member_info){
//            echo json_encode(['status'=>266,'msg'=>Language::get('请登录')]);die;
//        }
        //分页
        $page = $_GET['page'];

        //店铺id
        $vid = $_GET['vid'];

        //关键字
        $keyworld = trim($_GET['keyworld']);

        $goods_model = M('ldj_goods','ldj');
        $cart_model = M('ldj_cart','ldj');
        //店内的商品
        $where = [
            'dian_goods.dian_id'=>$vid,
            'goods.goods_name'=>['like','%'.$keyworld.'%'],
            'dian_goods.stock'=>['gt',0],
            'dian_goods.off'=>0,
            'dian_goods.delete'=>0,
        ];
        $goodsfield = 'goods.gid,goods.goods_name,goods.goods_image,goods.goods_price,dian_goods.dian_id,dian_goods.month_sales';
        $goodsorder = 'dian_goods.month_sales desc';
        $limit = '';
        $goods_list = $goods_model->getGoodsList($where,$goodsfield,$page,$goodsorder,$limit,'');
        $page_count_goods = mobile_page($goods_model->gettotalpage());

        if($goods_list){
            foreach($goods_list as $k=>$v){
                //销量
                if($v['month_sales']>10000){
                    $goods_list[$k]['month_sale'] = number_format($v['month_sales']/10000,1).'万';
                }
                //统计商品购物车数量
                $goodscartnum = $cart_model->getGoodsCartInfo(['buyer_id'=>$member_info['member_id'],'vid'=>$vid,'gid'=>$v['gid']])['goods_num'];
                $goods_list[$k]['cart_num'] = $goodscartnum>0?$goodscartnum:0;

                //商品图片
                $goods_list[$k]['goods_image'] = cthumb($v['goods_image']);

            }
            $cart_list = $cart_model->getVendorCartlistGoods($vid,$member_info['member_id']);
            echo json_encode(['status'=>200,'goods_list'=>['list'=>$goods_list,'ismore'=>$page_count_goods],'cart_list'=>$cart_list]);die;
        }
        echo json_encode(['status'=>255,'msg'=>'暂无相关产品']);die;
    }
    /*
     * 会员是否登录
     */
    public function getMemberInfo($key)
    {
        $model_mb_user_token = Model('mb_user_token');
        $key = trim($key);
        $mb_user_token_info = $model_mb_user_token->getMbUserTokenInfoByToken($key);
        if (empty($mb_user_token_info)) {
            return false;
        }

        $model_member = Model('member');
        $member_info = $model_member->getMemberInfoByID($mb_user_token_info['member_id']);
        if(empty($member_info)) {
            return false;
        } else {
            unset($member_info['member_passwd']);
            //读取卖家信息
            return $member_info;
        }
    }

    /**
     * @api {get} index.php?app=goods&mod=goods_detail&sld_addons=ldj 商品详情接口
     * @apiVersion 0.1.0
     * @apiName goods_detail
     * @apiGroup Goods
     * @apiDescription 商品详情接口
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/cmobile/index.php?app=goods&mod=goods_detail&sld_addons=ldj
     * @apiParam {String} key 会员登录key值
     * @apiParam {Number} gid 商品gid
     * @apiParam {Number} vid 门店ID
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} goods_info 返回商品数据
     * @apiSuccess {Json} cart_info 返回购物车数据
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200
     *         "goods_info": {
     *                  商品信息...
     *               }
     *          "cart_info": {
     *                          list:{购物车列表},
     *                          all_money:100
     *                      }
     *      }
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "内容不存在"
     *      }
     *     /
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *
     */
    public function goods_detail()
    {

        $gid = intval($_GET['gid']);
        $vid = intval($_GET['vid']);
        $goods_model = M('ldj_goods','ldj');
        $cart_model = M('ldj_cart','ldj');
        $where = [
            'dian_goods.goods_id'=>$gid,
            'dian_goods.dian_id'=>$vid,
        ];
        $field = 'dian_goods.*,goods.goods_name,goods.goods_commonid,goods.goods_price,goods.goods_image';
        $goods_info = $goods_model->getGoodsList($where,$field)[0];
        if(!$goods_info){
            echo  json_encode(['status'=>255,'商品不存在']);die;
        }
        if($goods_info['stock']<1){
            echo  json_encode(['status'=>255,'库存不足']);die;
        }
        unset($goods_info['stock']);
        if($goods_info['off'] || $goods_info['delete']){
            echo  json_encode(['status'=>255,'商品已下架']);die;
        }
        unset($goods_info['off']);
        unset($goods_info['delete']);
        //取商品的图片和商品的body
        $goods_body = $goods_model->table('goods_common')->where(['goods_commonid'=>$goods_info['goods_commonid']])->field('goods_body')->find()['goods_body'];
        $goods_image = $goods_model->table('goods_images')->where(['goods_commonid'=>$goods_info['goods_commonid']])->field('goods_image')->select();

        $goods_info['body'] = $goods_body;
        $goods_info['goods_image'] = cthumb($goods_info['goods_image'],350);
        $goods_info['goods_images'] = array_map(function($v){
            return cthumb($v['goods_image'],350);
        },$goods_image);

        //商家信息
        $dian_info = $goods_model->table('dian')->where(['id'=>$goods_info['dian_id']])->field('id,dian_name,dian_phone')->find();
        $goods_info['dian_name'] = $dian_info['dian_name'];
        $goods_info['dian_phone'] = explode(',',$dian_info['dian_phone'])[0];

        //购物车信息
        $key = trim($_GET['key']);
        $member_info = $this->getMemberInfo($key);
        $cart_list = $cart_model->getVendorCartlistGoods($goods_info['dian_id'],$member_info['member_id']);
        echo json_encode(['status'=>200,'goods_info'=>$goods_info,'cart_info'=>$cart_list]);die;
    }



}