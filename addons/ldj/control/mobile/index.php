<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/10/9
 * Time: 15:34
 */
class indexCtl extends mobileHomeCtl {

    public function __construct(){
        parent::__construct();
        if(!C('sld_ldjsystem') || !C('ldj_isuse') || !C('dian') || !C('dian_isuse')){
            echo json_encode(['status'=>255,'msg'=>'当前模块未开启']);die;
        }
    }

    /**
     * @api {get} index.php?app=index&mod=index&sld_addons=ldj 联到家首页接口
     * @apiVersion 0.1.0
     * @apiName index
     * @apiGroup Index
     * @apiDescription 联到家首页
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/cmobile/index.php?app=index&mod=index&sld_addons=ldj
     * @apiParam {String} latitude 纬度
     * @apiParam {String} longitude 经度
     * @apiParam {Number} page 分页
     * @apiParam {Number} pn 当前页数
     * @apiParam {String} key 用户登录标识(非必须)
     * @apiSuccess {Number} status 返回状态
     * @apiSuccess {Json} data 返回数据信息
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         status:200
     *         data: {
     *                  dian_list:[
     *                          {
     *                               "id": "7",
     *                               "vid": "2",
     *                               "dian_name": "测试1",
     *                               "dian_logo": "http://ldj.55jimu.com/data/upload/mall/dian/2/",
     *                               "delivery_type": "上门自提",
     *                               "operation_time": "720,1320",
     *                               "distance": "0.1km",
     *                               "month_sale": "5万",
     *                               "cart_num": 5
     *                               },
     *                               {
     *                               "id": "21",
     *                               "vid": "22",
     *                               "dian_name": "辉家居",
     *                               "dian_logo": "http://ldj.55jimu.com/data/upload/mall/dian/22/22_05715088897770142.png",
     *                               "delivery_type": "上门自提,门店配送",
     *                               "operation_time": "480,1320",
     *                               "distance": "0.2km",
     *                               "month_sale": "5万",
     *                               "cart_num": 5
     *                           }
     *
     *                          ]
     *               }
     *         ismore:{
     *              hasmore:true,
     *              page_total:4
     *          }
     *      }
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          status:255,
     *          msg: "未找到店铺"
     *     }
     *
     */
    public function index()
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
        $cart_model = M('ldj_cart','ldj');
        $time = explode('-',date('H-i'));
        $times = ($time[0]*60) + $time[1];
        //条件
        $condition = [
            //状态开启
            'status'=>1,
            'ldj_status'=>1,
            //营业时间
            'operation_time'=>['exp','substring_index(operation_time,",",1) < '.$times.' and substring_index(operation_time,",",-1) > '.$times]
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

        $field = 'id,vid,dian_name,dian_logo,month_sales,delivery_type,ldj_delivery_order_MinPrice,ldj_delivery_order_Price,operation_time,ldj_delivery_order_MaxDistance,ROUND(6378.138 * 2 * ASIN(SQRT(POW( SIN( ( '.$longitude.' * PI() / 180 - dian_lat * PI() / 180 ) / 2 ), 2 ) + COS( '.$longitude.' * PI() / 180 ) * COS( dian_lat * PI() / 180 ) * POW( SIN( ( '.$latitude.' * PI() / 180 - dian_lng * PI( ) / 180 ) / 2 ), 2 ))) * 1000) AS distance';
        //距离限制
        $having = 'distance<ldj_delivery_order_MaxDistance*1000';
        $order = 'distance asc';
        $dian_list  = $dian_model->getDianList($condition,$field,$having,$page,$order);

        if($dian_list){
            //组装数据
            foreach($dian_list as $k=>$v){

                //配送方式
                $dian_list[$k]['delivery_type'] = strtr($v['delivery_type'],['kuaidi'=>'商家自送','shangmen'=>'到店自取','mendian'=>'商家自送']);
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
                $dian_list[$k]['freight_qisong'] = $v['ldj_delivery_order_MinPrice'];
                $dian_list[$k]['freight_jichu'] = $v['ldj_delivery_order_Price'];

            }
            $page_count = $dian_model->gettotalpage();

            echo json_encode(['status'=>200,'data'=>$dian_list,'ismore'=>mobile_page($page_count)]);die;
        }
        echo json_encode(['status'=>255,'msg'=>'未找到门店']);die;
    }
    /**
     * @api {get} index.php?app=index&mod=index_data_app&sld_addons=ldj 联到家首页装修接口
     * @apiVersion 0.1.0
     * @apiName index_data_app
     * @apiGroup Index
     * @apiDescription 联到家首页装修接口_APP
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/cmobile/index.php?app=index&mod=index_data_app&sld_addons=ldj
     *
     */
    public function index_data_app()
    {
        $model_mb_special = M('cwap_home','ldj');
        $model_goods = Model('goods');

        $shop_id = isset($_GET['shop_id']) ? $_GET['shop_id'] : 0;

        $condition['shop_id'] = $shop_id;

        // 城市分站
        $curSldCityId = intval($_GET['bid']) ? intval($_GET['bid']) : 0;
        $condition['city_id'] = $curSldCityId;

        //获取首页数据
        $data = $model_mb_special->getCwapHomeInfo($condition);

        $data =unserialize($data['home_data']);

        if ($shop_id == 0) {
            // 首页模版分步加载数据
            $limit_group_p = (isset($_GET['lp']) && intval($_GET['lp']) > 0) ? intval($_GET['lp']) : 0; // 第几组
            $has_more = 0; // 是否有下一组数据
            $limit = 0;//(isset($_GET['l']) && intval($_GET['l']) > 0) ? intval($_GET['l']) : 0; // 每组几条数据
            if (($data_count = count($data)) > $limit && $limit > 0) {
                $data_group = array_chunk($data,$limit);
                $data = $data_group[$limit_group_p];
                $next_group_index = $limit_group_p+1;
                if (isset($data_group[$next_group_index])) {
                    $has_more = 1;
                }
            }
        }
        //对数据重新排序
        $data_new = array();
//        print_r($data);die;
        $new_data = array();
        if ($data) {
            foreach ($data as $k => $v){
                if(isset($v['data']) && !empty($v['data'])){
                    foreach ($v['data'] as $i_k => $i_v) {
                        if(isset($i_v['img'])){
                            $i_v['img'] = (strpos($i_v['img'],'http') !==false) ? $i_v['img'] : getMbSpecialImageUrl($i_v['img']);
                            //url处理，用户app和小程序的url处理
                            if($i_v['url_type'] == 'url'){
                                $url_new = $this -> match_diy_url($i_v['url']);
                                $i_v['url_type_new'] = $url_new['url_type_new'];
                                $i_v['url_new'] = $url_new['url_new'];
                            }
                            $v['data'][$i_k] = $i_v;
                        }
                    }
                }
                if($v['type'] == 'fuwenben'){
                    $v['text'] = htmlspecialchars_decode($v['text']);
                    $data_new[] = $v;
                }else if($v['type'] == 'tuijianshangpin') {
                    //推荐商品模块如果没有添加商品就保存的话就直接不显示了
                    if (!empty($v['data']['gid']) && is_array($v['data']['gid'])) {
                        foreach ($v['data']['gid'] as $key => $val) {
                            $goods_info = $model_goods->getGoodsOnlineInfoByID($val, 'gid,goods_name,goods_promotion_price,goods_price,goods_image');
                            if (!empty($goods_info)) {
                                $goods_info['goods_image'] = thumb($goods_info, 310);
                                $v['data']['goods_info'][] = $goods_info;
                            }
                        }

                        // 获取最终价格
                        $v['data']['goods_info'] = Model('goods_activity')->rebuild_goods_data($v['data']['goods_info']);

                        $data_new[] = $v;
                    }
                }else if($v['type'] == 'dapei') {
                    //推荐商品模块如果没有添加商品就保存的话就直接不显示了
                    if (!empty($v['data']['gid']) && is_array($v['data']['gid'])) {
                        foreach ($v['data']['gid'] as $key => $val) {
                            $goods_info = $model_goods->getGoodsOnlineInfoByID($val, 'gid,goods_name,goods_promotion_price,goods_price,goods_image');
                            if (!empty($goods_info)) {
                                $goods_info['goods_image'] = thumb($goods_info, 310);
                                $v['data']['goods_info'][] = $goods_info;
                            }
                        }

                        // 获取最终价格
                        $v['data']['goods_info'] = Model('goods_activity')->rebuild_goods_data($v['data']['goods_info']);
                        list($width,$height)=getimagesize($v['dapei_img']);
                        $v['width'] = $width;
                        $v['height'] = $height;
                        $data_new[] = $v;
                    }
                }else if($v['type'] == 'fzkb'){
                    //                $v['text'] = round($v['text']/23,2);//把像素转化为rem 用于做适配
                    $data_new[] = $v;
                }else if($v['type'] == 'lunbo'){
                    $lunbo_data = array();
                    foreach ($v['data'] as $lb_k => $lb_v){
                        $lunbo_data[] = $lb_v;
                    }
                    $v['data'] = $lunbo_data;
                    list($width,$height)=getimagesize($lunbo_data[0]['img']);
                    $v['width'] = 750;
                    $v['height'] = 750*$height/$width;
                    $data_new[] = $v;

                }else if($v['type'] == 'tupianzuhe'){
                    $tupianzuhe_data = array();
                    $tupianzuhe_data['type'] = $v['type'];
                    $tupianzuhe_data['sele_style'] = $v['sele_style'];
                    $new_data = array();
                    foreach ($v['data'] as $lb_k => $lb_v){

                        if($v['sele_style'] == 0||$v['sele_style'] == 1||$v['sele_style'] == 2||$v['sele_style'] == 3){
                            list($width,$height)=getimagesize($lb_v['img']);
                            $lb_v['width'] = $width;
                            $lb_v['height'] = $height;
                        }
                        $new_data[] = $lb_v;
                    }
                    $tupianzuhe_data['data'] = $new_data;
                    $data_new[] = $tupianzuhe_data;
                }else if($v['type'] == 'huodong'){
                    $use_fixed_search_type = true;

                    $huodong_data = array();
                    $huodong_data['type'] = $v['type'];
                    $huodong_data['sele_style'] = $v['sele_style'];

                    switch ($huodong_data['sele_style']) {
                        case '1':
                            // 限时折扣
                            $model_xian = Model('p_xianshi_goods');
                            $xianCondition = array();
                            $xianCondition['state'] = $model_xian::XIANSHI_GOODS_STATE_NORMAL;
                            $xianCondition['start_time'] = array('lt', TIMESTAMP);
                            $xianCondition['end_time'] = array('gt', TIMESTAMP);
                            $xian_goods_list = $model_xian->getXianshiGoodsList($xianCondition);
                            $extend_data_list = array();
                            $goods_ids = array();
                            if (!empty($xian_goods_list)) {

                                foreach ($xian_goods_list as $key => $value) {
                                    $goods_ids[] = $value['gid'];
                                    $value['sld_end_time'] = date("Y-m-d H:i:s",$value['end_time']);
                                    $extend_data_list[$value['gid']] = $value;
                                }
                            }
                            break;
                        case '2':
                            // 团购
                            $model_tuan = Model('tuan');
                            $tuanCondition = array();
                            $tuan_goods_list = $model_tuan->getTuanOnlineList($tuanCondition,'','','gid,tuan_price,virtual_quantity,buy_quantity,tuan_discount,vid,end_time');
                            $extend_data_list = array();
                            $goods_ids = array();
                            foreach ($tuan_goods_list as $key => $value) {
                                $value['sld_end_time'] = (strtotime($value['end_time_text']) > time()) ? $value['end_time_text'] : '';
                                $value['buyed_quantity'] = $value['virtual_quantity'] + $value['buy_quantity'];
                                $goods_ids[] = $value['gid'];
                                $extend_data_list[$value['gid']] = $value;
                            }
                            break;

                        default:
                            // 拼团
                            // 获取拼团 类型的商品(bbc_goods) id

                            $allow_search_type = array(1);

                            $model_pin = M('pin');
                            $pinCondition = array();
                            $pin_goods_list = $model_pin->getPinList($pinCondition,0);
                            $extend_data_list = array();
                            $goods_ids = array();
                            foreach ($pin_goods_list as $key => $value) {
                                $goods_ids[] = $value['gid'];
                                $extend_data_list[$value['gid']] = $value;
                            }
                            break;
                    }

                    if (isset($v['data']) && is_array($v['data']) && !empty($v['data'])) {
                        foreach ( $v['data'] as $huodong_k => $huodong_v){
                            foreach ($huodong_v as $huodong_a_k => $huodong_a_v) {
                                if (is_array($huodong_a_v) && !empty($huodong_a_v)) {
                                    foreach ($huodong_a_v as $huodong_b_k => $huodong_b_v) {
                                        if(isset($huodong_b_v['gid'])){
                                            if (is_array($huodong_b_v['gid']) && !empty($huodong_b_v['gid'])) {
                                                foreach ($huodong_b_v['gid'] as $huodong_c_k => $huodong_c_v) {
                                                    // 获取 商品信息
                                                    $goods_info = $model_goods -> getGoodsOnlineInfoByID($huodong_c_v,'gid,goods_name,goods_promotion_price,goods_marketprice,goods_price,goods_image');
                                                    if(!empty($goods_info)){
                                                        $goods_info['goods_image'] = thumb($goods_info, 320);
                                                        $goods_info['extend_data'] = $extend_data_list[$huodong_c_v];
                                                        $huodong_v[$huodong_a_k][$huodong_b_k]['goods_info'][$huodong_c_k] = $goods_info;
                                                    }
                                                }
                                            }else{
                                                // 获取 商品信息
                                                $goods_info = $model_goods -> getGoodsOnlineInfoByID($huodong_b_v['gid'],'gid,goods_name,goods_promotion_price,goods_marketprice,goods_price,goods_image');
                                                if(!empty($goods_info)){
                                                    $goods_info['goods_image'] = thumb($goods_info, 320);
                                                    $goods_info['extend_data'] = $extend_data_list[$huodong_b_v['gid']];
                                                    $huodong_v[$huodong_a_k][$huodong_b_k]['goods_info'] = $goods_info;
                                                }
                                            }
                                        }
                                    }
                                }

                                // 获取最终价格
                                $huodong_v[$huodong_a_k][$huodong_b_k]['goods_info'] = Model('goods_activity')->rebuild_goods_data($huodong_v[$huodong_a_k][$huodong_b_k]['goods_info']);
                            }

                            $huodong_data['data'][$huodong_k] = $huodong_v;
                        }
                    }

                    $data_new[] = $huodong_data;
                }else{
                    $data_new[] = $v;
                }

            }
        }
        if ($shop_id == 0) {
            $site_name = C('site_name') ? C('site_name') : '';
            output_data(array('tmp_data'=>$data_new,'has_more'=>$has_more,'site_name'=>$site_name));
        }else{
//            output_data(array('tmp_data'=>$data_new,'has_more'=>$has_more,'site_name'=>$site_name));
//            $this->_output_special($data_new, $_GET['type']);
        }
    }
    /**
     * @api {get} index.php?app=index&mod=index_data&sld_addons=ldj 联到家首页装修接口
     * @apiVersion 0.1.0
     * @apiName index_data
     * @apiGroup Index
     * @apiDescription 联到家首页装修接口
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/cmobile/index.php?app=index&mod=index_data&sld_addons=ldj
     *
     */
    public function index_data()
    {
        $model_mb_special = M('cwap_home','ldj');
        $model_goods = Model('goods');

        $shop_id = isset($_GET['shop_id']) ? $_GET['shop_id'] : 0;

        $condition['shop_id'] = $shop_id;

        // 城市分站
        $curSldCityId = intval($_GET['bid']) ? intval($_GET['bid']) : 0;
            $condition['city_id'] = $curSldCityId;

        //获取首页数据
        $data = $model_mb_special->getCwapHomeInfo($condition);

        $data =unserialize($data['home_data']);

        if ($shop_id == 0) {
            // 首页模版分步加载数据
            $limit_group_p = (isset($_GET['lp']) && intval($_GET['lp']) > 0) ? intval($_GET['lp']) : 0; // 第几组
            $has_more = 0; // 是否有下一组数据
            $limit = 0;//(isset($_GET['l']) && intval($_GET['l']) > 0) ? intval($_GET['l']) : 0; // 每组几条数据
            if (($data_count = count($data)) > $limit && $limit > 0) {
                $data_group = array_chunk($data,$limit);
                $data = $data_group[$limit_group_p];
                $next_group_index = $limit_group_p+1;
                if (isset($data_group[$next_group_index])) {
                    $has_more = 1;
                }
            }
        }
        //对数据重新排序
        $data_new = array();
//        print_r($data);die;
        $new_data = array();
        if ($data) {
            foreach ($data as $k => $v){
                if(isset($v['data']) && !empty($v['data'])){
                    foreach ($v['data'] as $i_k => $i_v) {
                        if(isset($i_v['img'])){
                            $i_v['img'] = (strpos($i_v['img'],'http') !==false) ? $i_v['img'] : getMbSpecialImageUrl($i_v['img']);
                            //url处理，用户app和小程序的url处理
                            if($i_v['url_type'] == 'url'){
                                $url_new = $this -> match_diy_url($i_v['url']);
                                $i_v['url_type_new'] = $url_new['url_type_new'];
                                $i_v['url_new'] = $url_new['url_new'];
                            }
                            $v['data'][$i_k] = $i_v;
                        }
                    }
                }
                if($v['type'] == 'fuwenben'){
                    $v['text'] = htmlspecialchars_decode($v['text']);
                    $data_new[] = $v;
                }else if($v['type'] == 'tuijianshangpin') {
                    //推荐商品模块如果没有添加商品就保存的话就直接不显示了
                    if (!empty($v['data']['gid']) && is_array($v['data']['gid'])) {
                        foreach ($v['data']['gid'] as $key => $val) {
                            $goods_info = $model_goods->getGoodsOnlineInfoByID($val, 'gid,goods_name,goods_promotion_price,goods_price,goods_image');
                            if (!empty($goods_info)) {
                                $goods_info['goods_image'] = thumb($goods_info, 310);
                                $v['data']['goods_info'][] = $goods_info;
                            }
                        }

                        // 获取最终价格
                        $v['data']['goods_info'] = Model('goods_activity')->rebuild_goods_data($v['data']['goods_info']);

                        $data_new[] = $v;
                    }
                }else if($v['type'] == 'dapei') {
                    //推荐商品模块如果没有添加商品就保存的话就直接不显示了
                    if (!empty($v['data']['gid']) && is_array($v['data']['gid'])) {
                        foreach ($v['data']['gid'] as $key => $val) {
                            $goods_info = $model_goods->getGoodsOnlineInfoByID($val, 'gid,goods_name,goods_promotion_price,goods_price,goods_image');
                            if (!empty($goods_info)) {
                                $goods_info['goods_image'] = thumb($goods_info, 310);
                                $v['data']['goods_info'][] = $goods_info;
                            }
                        }

                        // 获取最终价格
                        $v['data']['goods_info'] = Model('goods_activity')->rebuild_goods_data($v['data']['goods_info']);

                        $data_new[] = $v;
                    }
                }else if($v['type'] == 'fzkb'){
                    //                $v['text'] = round($v['text']/23,2);//把像素转化为rem 用于做适配
                    $data_new[] = $v;
                }else if($v['type'] == 'lunbo'){
                    $lunbo_data = array();
                    foreach ($v['data'] as $lb_k => $lb_v){
                        $lunbo_data[] = $lb_v;
                    }
                    $v['data'] = $lunbo_data;
                    list($width,$height)=getimagesize($lunbo_data[0]['img']);
                    $v['width'] = 750;
                    $v['height'] = 750*$height/$width;
                    $data_new[] = $v;

                }else if($v['type'] == 'tupianzuhe'){
                    $tupianzuhe_data = array();
                    $tupianzuhe_data['type'] = $v['type'];
                    $tupianzuhe_data['sele_style'] = $v['sele_style'];
                    $new_data = array();
                    foreach ($v['data'] as $lb_k => $lb_v){
                        $new_data[] = $lb_v;
                    }
                    $tupianzuhe_data['data'] = $new_data;
                    $data_new[] = $tupianzuhe_data;
                }else if($v['type'] == 'huodong'){
                    $use_fixed_search_type = true;

                    $huodong_data = array();
                    $huodong_data['type'] = $v['type'];
                    $huodong_data['sele_style'] = $v['sele_style'];

                    switch ($huodong_data['sele_style']) {
                        case '1':
                            // 限时折扣
                            $model_xian = Model('p_xianshi_goods');
                            $xianCondition = array();
                            $xianCondition['state'] = $model_xian::XIANSHI_GOODS_STATE_NORMAL;
                            $xianCondition['start_time'] = array('lt', TIMESTAMP);
                            $xianCondition['end_time'] = array('gt', TIMESTAMP);
                            $xian_goods_list = $model_xian->getXianshiGoodsList($xianCondition);
                            $extend_data_list = array();
                            $goods_ids = array();
                            if (!empty($xian_goods_list)) {

                                foreach ($xian_goods_list as $key => $value) {
                                    $goods_ids[] = $value['gid'];
                                    $value['sld_end_time'] = date("Y-m-d H:i:s",$value['end_time']);
                                    $extend_data_list[$value['gid']] = $value;
                                }
                            }
                            break;
                        case '2':
                            // 团购
                            $model_tuan = Model('tuan');
                            $tuanCondition = array();
                            $tuan_goods_list = $model_tuan->getTuanOnlineList($tuanCondition,'','','gid,tuan_price,virtual_quantity,buy_quantity,tuan_discount,vid,end_time');
                            $extend_data_list = array();
                            $goods_ids = array();
                            foreach ($tuan_goods_list as $key => $value) {
                                $value['sld_end_time'] = (strtotime($value['end_time_text']) > time()) ? $value['end_time_text'] : '';
                                $value['buyed_quantity'] = $value['virtual_quantity'] + $value['buy_quantity'];
                                $goods_ids[] = $value['gid'];
                                $extend_data_list[$value['gid']] = $value;
                            }
                            break;

                        default:
                            // 拼团
                            // 获取拼团 类型的商品(bbc_goods) id

                            $allow_search_type = array(1);

                            $model_pin = M('pin');
                            $pinCondition = array();
                            $pin_goods_list = $model_pin->getPinList($pinCondition,0);
                            $extend_data_list = array();
                            $goods_ids = array();
                            foreach ($pin_goods_list as $key => $value) {
                                $goods_ids[] = $value['gid'];
                                $extend_data_list[$value['gid']] = $value;
                            }
                            break;
                    }

                    if (isset($v['data']) && is_array($v['data']) && !empty($v['data'])) {
                        foreach ( $v['data'] as $huodong_k => $huodong_v){
                            foreach ($huodong_v as $huodong_a_k => $huodong_a_v) {
                                if (is_array($huodong_a_v) && !empty($huodong_a_v)) {
                                    foreach ($huodong_a_v as $huodong_b_k => $huodong_b_v) {
                                        if(isset($huodong_b_v['gid'])){
                                            if (is_array($huodong_b_v['gid']) && !empty($huodong_b_v['gid'])) {
                                                foreach ($huodong_b_v['gid'] as $huodong_c_k => $huodong_c_v) {
                                                    // 获取 商品信息
                                                    $goods_info = $model_goods -> getGoodsOnlineInfoByID($huodong_c_v,'gid,goods_name,goods_promotion_price,goods_marketprice,goods_price,goods_image');
                                                    if(!empty($goods_info)){
                                                        $goods_info['goods_image'] = thumb($goods_info, 320);
                                                        $goods_info['extend_data'] = $extend_data_list[$huodong_c_v];
                                                        $huodong_v[$huodong_a_k][$huodong_b_k]['goods_info'][$huodong_c_k] = $goods_info;
                                                    }
                                                }
                                            }else{
                                                // 获取 商品信息
                                                $goods_info = $model_goods -> getGoodsOnlineInfoByID($huodong_b_v['gid'],'gid,goods_name,goods_promotion_price,goods_marketprice,goods_price,goods_image');
                                                if(!empty($goods_info)){
                                                    $goods_info['goods_image'] = thumb($goods_info, 320);
                                                    $goods_info['extend_data'] = $extend_data_list[$huodong_b_v['gid']];
                                                    $huodong_v[$huodong_a_k][$huodong_b_k]['goods_info'] = $goods_info;
                                                }
                                            }
                                        }
                                    }
                                }

                                // 获取最终价格
                                $huodong_v[$huodong_a_k][$huodong_b_k]['goods_info'] = Model('goods_activity')->rebuild_goods_data($huodong_v[$huodong_a_k][$huodong_b_k]['goods_info']);
                            }

                            $huodong_data['data'][$huodong_k] = $huodong_v;
                        }
                    }

                    $data_new[] = $huodong_data;
                }else{
                    $data_new[] = $v;
                }

            }
        }
        if ($shop_id == 0) {
            $site_name = C('site_name') ? C('site_name') : '';
            output_data(array('tmp_data'=>$data_new,'has_more'=>$has_more,'site_name'=>$site_name));
        }else{
//            output_data(array('tmp_data'=>$data_new,'has_more'=>$has_more,'site_name'=>$site_name));
//            $this->_output_special($data_new, $_GET['type']);
        }
    }
    /**
     * @api {get} index.php?app=index&mod=index_title&sld_addons=ldj 获取首页title和搜索栏颜色
     * @apiVersion 0.1.0
     * @apiName index_title
     * @apiGroup Index
     * @apiDescription 获取首页title和搜索栏颜色
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/cmobile/index.php?app=index&mod=index_title&sld_addons=ldj
     * @apiSuccess {Number} status 返回状态
     * @apiSuccess {Json} data 返回数据信息
     * @apiSuccessExample {json} 成功的例子:
     * {}
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *{}
     */
    /**
     * 获取首页title和搜索栏颜色
     */
    public function index_title() {
        $model_mb_special = M('cwap_home','ldj');
        //获取首页数据
        $curSldCityId = intval($_GET['bid']) ? intval($_GET['bid']) : 0;
        $condition['city_id'] = $curSldCityId;
        $data = $model_mb_special->getCwapHomeInfo($condition);
        //对数据重新排序
        $data_new = array();
        $data_new['title'] = $data['home_desc'];
        $data_new['sousuo_color'] = $data['home_sousuo_color'];
//        print_r($data);die;
        output_data($data_new);
    }
    /*
     根据URL路径匹配页面的跳转(商品/店铺/店铺列表/优惠券列表/商品列表/签到页面/专题列表)
     *
     */
    public function match_diy_url($url){
        $result = array();
        //$url = 'http://www.slodon.cn/cwap/cwap_product_detail.html?gid=1177';
        if(strstr($url,'index.php?app=goods')){
            //url转为商品详情页
            $arr = $this->parse_url_param(htmlspecialchars_decode($url));
            if($arr['gid'] > 0){
                $result['url_type_new'] = 'goods';
                $result['url_new'] = $arr['gid'];
            }
        }else if(strstr($url,'cwap_product_detail.html?gid')){
            //url转为商品详情页
            $arr = $this->parse_url_param($url);
            if($arr['gid'] > 0){
                $result['url_type_new'] = 'goods';
                $result['url_new'] = $arr['gid'];
            }
        }else if(strstr($url,'cwap_go_shop.html?vid')){
            //url转为店铺
            $arr = $this->parse_url_param($url);
            if($arr['vid'] > 0){
                $result['url_type_new'] = 'vendor';
                $result['url_new'] = $arr['vid'];
            }
        }else if(strstr($url,'cwap_subject.html?topic_id')){
            //url转为专题
            $arr = $this->parse_url_param($url);
            if($arr['topic_id'] > 0){
                $result['url_type_new'] = 'special';
                $result['url_new'] = $arr['topic_id'];
            }
        }else if(strstr($url,'cwap_shop_list.html')){
            //url转为店铺列表
            $result['url_type_new'] = 'vendorlist';
            $result['url_new'] = '';

        }else if(strstr($url,'cwap_product_list.html?gc_id')){
            //url转为商品列表（按分类）
            $arr = $this->parse_url_param($url);
            if($arr['gc_id'] > 0){
                $result['url_type_new'] = 'goodscat';
                $result['url_new'] = $arr['gc_id'];
            }
        }else if(strstr($url,'cwap_product_list.html?keyword')){
            //url转为商品列表（按关键词）
            $arr = $this->parse_url_param($url);
            if(isset($arr['keyword'])){
                $result['url_type_new'] = 'goodslist';
                $result['url_new'] = urldecode($arr['keyword']);
            }
        }else if(strstr($url,'cwap_user_points.html')){
            //url转为签到
            $result['url_type_new'] = 'sighlogin';
            $result['url_new'] = '';
        }else if(strstr($url,'red_get_list.html')){
            //url转为优惠券列表
            $result['url_type_new'] = 'voucherlist';
            $result['url_new'] = '';
        }else if(strstr($url,'cwap_pro_cat.html')){
            //url转为优惠券列表
            $result['url_type_new'] = 'fenlei';
            $result['url_new'] = '';
        }else if(strstr($url,'cwap_cart.html')){
            //url转为优惠券列表
            $result['url_type_new'] = 'cart';
            $result['url_new'] = '';
        }else if(strstr($url,'cwap_user.html')){
            //url转为优惠券列表
            $result['url_type_new'] = 'usercenter';
            $result['url_new'] = '';
        }else if(strstr($url,'pin_index.html')){
            //url拼团列表页
            $result['url_type_new'] = 'pin_index';
            $result['url_new'] = '';
        }else if(strstr($url,'cwap_tuan.html')){
            //url团购列表页
            $result['url_type_new'] = 'tuan_index';
            $result['url_new'] = '';
        }else if(strstr($url,'cwap_discount.html')){
            //url团购列表页
            $result['url_type_new'] = 'xianshi_index';
            $result['url_new'] = '';
        }else if(strstr($url,'points')){
            //url积分商城首页
            $result['url_type_new'] = 'points_shop';
            $result['url_new'] = '';
        }else if(strstr($url,'ldj')){
            //url联到家首页
            $result['url_type_new'] = 'ldj';
            $result['url_new'] = '';
        }else{
            //不满足以上条件则不跳转
            $result['url_type_new'] = '';
            $result['url_new'] = '';
        }
        return $result;
    }
    /**
     * 获取url中的各个参数
     * 类似于 pay_code=alipay&bank_code=ICBC-DEBIT
     * @param type $str
     * @return type
     */
    public function parse_url_param($str)
    {
        $data = array();
        $arr=array();
        $p=array();
        $arr=explode('?', $str);
        $p = explode('&', $arr[1]);
        foreach ($p as $val) {
            $tmp = explode('=', $val);
            $data[$tmp[0]] = $tmp[1];
        }
        return $data;
    }
    /*
     * 获取高德地图key值
     */
    public function getGaoDekey()
    {

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

            return $member_info;
        }
    }

}