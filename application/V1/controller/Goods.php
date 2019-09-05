<?php
namespace app\V1\controller;

use app\V1\model\Area;
use app\V1\model\BrowserHistory;
use app\V1\model\Dian;
use app\V1\model\EvaluateGoods;
use app\V1\model\EvaluateStore;
use app\V1\model\Favorites;
use app\V1\model\FirstOrder;
use app\V1\model\GoodsActivity;
use app\V1\model\GoodsClass;
use app\V1\model\Grade;
use app\V1\model\MyGoods;
use app\V1\model\Seller;
use app\V1\model\Seo;
use app\V1\model\SnsGoods;
use app\V1\model\Transport;
use app\V1\model\VendorGlmb;
use app\V1\model\VendorInfo;
use app\V1\model\VendorLabel;
use app\V1\model\VendorNavigation;
use think\db;
class Goods extends  Base
{
    public function detail(){
        if(!input("gid") || !input("member_id")){
            $data['error_code']=10100;
            $data['message']=lang("缺少参数");
            return json_encode($data,true);
        }
        $memberId = input("member_id");
        $gid = input("gid");
        $is_supplier_close = 0;

        // 商品详细信息
        $model_goods = new \app\V1\model\Goods();
        //虚拟销量
        if(Config('virtual_sale')){
            $field = '*,(goods_salenum+virtual_sale) as goods_salenum';
        }else{
            $field = '*';
        }
        $model_grade = new Grade();


        $goods_detail = $model_goods->getGoodsDetail($gid, $field,$memberId);
        if(empty($goods_detail)){
            $data['error_code'] = 10101;
            $data['message'] = lang("商品没有找到");
            return json_encode($data,true);
        }

        $data['order_goods_info']=$goods_detail['order_goods_info'];
        //Template::output('order_goods_info', $goods_detail['order_goods_info']);
//        dd($goods_detail['types']);die;
        if(isset($goods_detail['types']))
        $data['types']=$goods_detail['types'];
        //Template::output('types', $goods_detail['types']);


        //首单优惠PC
        $firstModel = new FirstOrder();
        $goods_detail['first'] = $firstModel->getInfo($goods_detail['goods_info']['vid'],$goods_detail['goods_info']['goods_commonid']);

        //条件||pc去掉预售和阶梯团购活动的商品,以后可能会去掉
        //商品详情展示等级优惠列表
        //print_r($goods_detail);die;
        $goods_detail['goods_info']['duration'] = Sec2Time($goods_detail['goods_info']['duration']);
        $grade_list =array();
        $supplier_buy_flag="";
        if(
            Config('member_grade_open') &&
            (
                !(isset($goods_detail['goods_info']['promotion_type']) && !empty($goods_detail['goods_info']['promotion_type']))
                || in_array($goods_detail['goods_info']['promotion_type'],['pin_ladder_tuan','sld_presale'])
            )
        ){
            $vendor_info = model()->table('bbc_vendor')->where(['vid'=>$goods_detail['goods_info']['vid']])->find();
            if($vendor_info['grade_on_price']){
                $grade_list = $model_grade->getlist([],'*','','grade_value asc');
                $member_grade = $model_grade->getmembergrade($memberId);
                //查看当前等级
                if(Config('grade_setting') == 2){
                    $grade_list = [];
                    $grade_list[] =  $member_grade;
                }elseif(Config('grade_setting') == 3){
                    //查看比自己等级小的价格
                    array_walk($grade_list,function(&$v) use ($member_grade) {
                        if($v['grade_value'] > $member_grade['grade_value']){
                            $v = '';
                        }
                    });
                }
                $grade_list = array_filter($grade_list);
                foreach($grade_list as $k=>$v){
                    if($v['grade_discount'] > 0){
                        $grade_list[$k]['goods_price'] = $goods_detail['goods_info']['goods_price'] * $v['grade_discount']/100;
                    }else{
                        $grade_list[$k]['goods_price'] = $goods_detail['goods_info']['goods_price'];
                    }
                }
            }
        }
        $goods_detail['goods_info']['grade_info'] = $grade_list;
        if(!empty($goods_detail['goods_info']['promotion_type']) && in_array($goods_detail['goods_info']['promotion_type'],array('tuan','xianshi','phone_price','today_buy','pin_tuan','p_mbuy'))){
            $goods_detail['goods_info']['cart_xian']=0;
        }else{
            $goods_detail['goods_info']['cart_xian']=1;
        }

        $goods_info = $goods_detail['goods_info'];
        if ($goods_info['goods_type']) {
            $goods_info['sld_ladder_price_arr'] = unserialize($goods_info['sld_ladder_price']);
            ksort($goods_info['sld_ladder_price_arr']);
            $goods_info['sld_ladder_price_json'] = json_encode($goods_info['sld_ladder_price_arr']);
            // 获取 最小的数量
            $ladder_numbers = array_keys($goods_info['sld_ladder_price_arr']);
            $goods_info['min_number'] = $ladder_numbers[0];
            if (!Config('supplier_isuse') || !Config('sld_supplier_isuse')) {
                // 功能关闭
                $is_supplier_close = 1;
            }
            // 供应商用户 跳过权限校验
            if (isset($_SESSION['sld_is_supplier']) && $_SESSION['sld_is_supplier']) {
                $supplier_buy_flag = true;
            }else{
                $supplier_buy_flag = $this->checkSupplierRule($memberId,$_SESSION['vid'],'buy');
            }

            // 批发中心 搜索标示
            //Template::output('supplier_search',ture);
        }
        $data['is_supplier_close']=$is_supplier_close;
        //Template::output('is_supplier_close', $is_supplier_close);
        $data['supplier_buy_flag']=$supplier_buy_flag;
        //Template::output('supplier_buy_flag', $supplier_buy_flag);
        if (!empty($goods_info['video_url'])){
            $goods_info['video_url']=UPLOAD_SITE_URL . DS . ATTACH_STORE_video . DS .$goods_info['video_url'];
        }
        if (empty($goods_info)) {
            echo lang("商品没有找到");exit;
        }
        $goods_info=$this->getStoreInfo($goods_info['vid'],$goods_info);
        // 看了又看（同分类本店随机商品）
        $size = '3';
        $goods_rand_list = $model_goods->getGoodsGcStoreRandList($goods_info['gc_id_1'], $goods_info['vid'], $goods_info['gid'], $size);
        $goods_rand_list = array_slice($goods_rand_list,0,3);
        // 获取最终价格
        $goodsActivityModel = new GoodsActivity();
        $goods_rand_list = $goodsActivityModel->rebuild_goods_data($goods_rand_list,'pc');
        $data['goods_rand_list']=$goods_rand_list;
        //Template::output('goods_rand_list', $goods_rand_list);
        $data['spec_list']=$goods_detail['spec_list'];
        //Template::output('spec_list', $goods_detail['spec_list']);
        $data['spec_image']=$goods_detail['spec_image'];
        //Template::output('spec_image', $goods_detail['spec_image']);
        if(isset($goods_detail['goods_image']))
        $data['goods_image']=$goods_detail['goods_image'];
        //Template::output('goods_image', $goods_detail['goods_image']);
        $data['tuan_info']=$goods_detail['tuan_info'];
        //Template::output('tuan_info', $goods_detail['tuan_info']);
        $data['xianshi_info']=$goods_detail['xianshi_info'];
        //Template::output('xianshi_info', $goods_detail['xianshi_info']);
        $data['mansong_info']=$goods_detail['mansong_info'];
        //Template::output('mansong_info', $goods_detail['mansong_info']);
        if(isset($goods_detail['mobile_info']))
        $data['mobile_info']=$goods_detail['mobile_info'];
        //Template::output('mobile_info', $goods_detail['mobile_info']);

        // 浏览过的商品
        $browserHistory = new BrowserHistory();
        $viewed_goods = $browserHistory->getViewedGoodsList($memberId, 20);
        $data['viewed_goods']=$viewed_goods;
        //Template::output('viewed_goods', $viewed_goods);
        //聊天判断身份
        $vendoeModel = new VendorInfo();
        if($vendoeModel->table('bbc_vendor')->where(['vid'=>$goods_detail['goods_info']['vid'],'member_id'=>$memberId])->find()){
            //Template::output('is_vendor_manage', 1);
        }

        // 生成缓存的键值
        $hash_key = $goods_info['gid'];
        // 先查找$hash_key缓存
        $cachekey_arr = array (
            'likenum',
            'sharenum'
        );
        $base = new Base();
        if ($_cache = $base->rcache($hash_key, 'product')) {
            foreach ($_cache as $k => $v) {
                $goods_info[$k] = $v;
            }
        } else {
            // 查询SNS中该商品的信息
            $snsGoods = new SnsGoods();
            $snsgoodsinfo = $snsGoods->getSNSGoodsInfo(array('snsgoods_goodsid' => $goods_info['gid']), 'snsgoods_likenum,snsgoods_sharenum');
            $goods_info['likenum'] = $snsgoodsinfo['snsgoods_likenum'];
            $goods_info['sharenum'] = $snsgoodsinfo['snsgoods_sharenum'];

            $data = array();
            if (! empty ( $goods_info )) {
                foreach ( $goods_info as $k => $v ) {
                    if (in_array ( $k, $cachekey_arr )) {
                        $data [$k] = $v;
                    }
                }
            }
            // 缓存商品信息
            Base::wmemcache ( $hash_key, $data, 'product' );
        }

        // 检查是否为店主本人
        $store_self = false;
        if (!empty($_SESSION['vid'])) {
            if ($goods_info['vid'] == $_SESSION['vid']) {
                $store_self = true;
            }
        }
        //Template::output('store_self',$store_self );

        // 如果使用运费模板
        if ($goods_info['transport_id'] > 0) {
            // 取得三种运送方式默认运费
            $model_transport = new Transport();
            $transport = $model_transport->getExtendList(array('transport_id' => $goods_info['transport_id'], 'is_default' => 1));
            if (!empty($transport) && is_array($transport)) {
                foreach ($transport as $v) {
                    $goods_info[$v['type'] . "_price"] = $v['sprice'];
                }
            }
        }
        if(isset($goods_info['promotion_type']))
        $goods_info['goods_promotion_type'] = $goods_info['promotion_type'];
        if(isset($goods_info['promotion_type']) && $goods_info['promotion_type']&&$goods_info['promotion_type']!='pin'){
            $goods_info['goods_promotion_price'] = $goods_info['promotion_price'];
        }

        if(Config('sld_red') && Config('red_isuse')){
            $par['goods_info']=$goods_info;
            $par['member']=array('member_id'=>$_SESSION['member_id']);
            $goods_info = con_addons('red',$par);
        }


        //*******拼接老师名*********************************************************

        $model_member = new \app\V1\model\User();
        $tmp = $model_member->table('bbc_member')->field('member_name')->where(['member_id'=>$goods_info['teacher']])->find();
        $goods_info['teacher'] = $tmp['member_name'];

        //***************************************************************************
        $area = new Area();
        $area_info = DB::table('bbc_area')->where(['area_deep'=>['neq',3]])->select();
        foreach ($area_info as $v){
            if($v['area_id']==$goods_info['areaid_1']){
                $goods_info['areaid_1'] = $v['area_name'];
            }

            if($v['area_id']==$goods_info['areaid_2']){
                $goods_info['areaid_2'] = $v['area_name'];
            }
        }
        //Template::output('goods', $goods_info);


        // 关联版式
        $plateid_array = array();
        if (!empty($goods_info['plateid_top'])) {
            $plateid_array[] = $goods_info['plateid_top'];
        }
        if (!empty($goods_info['plateid_bottom'])) {
            $plateid_array[] = $goods_info['plateid_bottom'];
        }
        if (!empty($plateid_array)) {
            $vendorGlmb=new VendorGlmb();
            $plate_array = $vendorGlmb->getPlateList(array('plate_id' => array('in', $plateid_array), 'vid' => $goods_info['vid']));
            $plate_array = array_under_reset($plate_array, 'plate_position', 2);
            $data['plate_array']=$plate_array;
            //Template::output('plate_array', $plate_array);
        }

        //Template::output('vid', $goods_info ['vid']);

        // 批发商品 去掉门店获取数据
        if (!$goods_info['goods_type'] && Config('dian') && Config('dian_isuse')) {
            //获取有该商品的门店
            $dianModel = new Dian();
            $dians = $dianModel->getDiansByGid($gid);
            foreach ($dians as $k => $v) {
                $dians[$k]['dian_phone'] = explode(',', $v['dian_phone']);
            }
            $data['dians']=$dians;
            //Template::output('dians', $dians);
            //$data['dians']=$dians;
            //Template::output('dians_page', Model('dian')->showpage());
        }

        // 生成浏览过产品
        $cookievalue = $gid . '-' . $goods_info ['vid'];
        if (cookie('viewed_goods')) {
            $string_viewed_goods = decrypt(cookie('viewed_goods'), MD5_KEY);
            if (get_magic_quotes_gpConfig()) {
                $string_viewed_goods = stripslashes($string_viewed_goods); // 去除斜杠
            }
            $vg_ca = @unserialize($string_viewed_goods);
            $sign = true;
            if ( !empty($vg_ca) && is_array($vg_ca)) {
                foreach ($vg_ca as $vk => $vv) {
                    if ($vv == $cookievalue) {
                        $sign = false;
                    }
                }
            } else {
                $vg_ca = array();
            }

            if ($sign) {
                if (count($vg_ca) >= 6) {
                    $vg_ca[] = $cookievalue;
                    array_shift($vg_ca);
                } else {
                    $vg_ca[] = $cookievalue;
                }
            }
        } else {
            $vg_ca[] = $cookievalue;
        }
        $vg_ca = encrypt(serialize($vg_ca), MD5_KEY);
        //setBbcCookie('viewed_goods', $vg_ca);

        //优先得到推荐商品
        $goods_commend_list = $model_goods->getGoodsOnlineList(array('vid' => $goods_info['vid'], 'goods_commend' => 1), 'gid,goods_name,goods_jingle,goods_image,vid,goods_price', 0, 'gid', 12, 'goods_commonid');

        // 获取最终价格
        $goods_commend_list = $goodsActivityModel->rebuild_goods_data($goods_commend_list,'pc');
        $data['goods_commend'] =$goods_commend_list;
        //Template::output('goods_commend',$goods_commend_list);


        // 当前位置导航
        $goodsClass = new GoodsClass();
        $nav_link_list = $goodsClass->getGoodsClassNav($goods_info['gc_id'], 0);
        $nav_link_list[] = array('title' => $goods_info['goods_name']);
        $data['nav_link_list'] =$nav_link_list;
        //Template::output('nav_link_list', $nav_link_list );

        //评价信息
        $eg = new EvaluateGoods();
        $goods_evaluate_info = $eg->getEvaluateGoodsInfoByGoodsID($gid);
        $data['goods_evaluate_info'] =$goods_evaluate_info;
        //Template::output('goods_evaluate_info', $goods_evaluate_info);
        //判断是否为预售商品
        $data['ispresale']=DB::name("pre_goods")->join("bbc_presale",'bbc_pre_goods.pre_id = bbc_presale.pre_id')->where("gid=$gid and pre_start_time<=".TIMESTAMP." and pre_end_time>=pre_end_time and pre_status=1")->find();
        //判断商品是否收藏
        $favorite_model = new Favorites();
        $favorite_info = $favorite_model->getOneFavorites(array('fav_id'=>"$gid",'fav_type'=>'goods','member_id'=>"$memberId"));
        if(empty($favorite_info)){
            $favorites_flag = 0;
        }else{
            $favorites_flag = 1;
        }
        $data['favorites_flag']=$favorites_flag;

        $seo_param = array ();
        $seo_param['name'] = $goods_info['goods_name'];
        if(isset($goods_info['goods_keywords']))
        $seo_param['key'] = $goods_info['goods_keywords'];
        if(isset($goods_info['description'])) {
            $seo_param['description'] = $goods_info['goods_description'];
            $data['goods_info']['seo']=Model('seo')->type('product')->param($seo_param)->show();
        }
        $data['goods_info'] =$goods_info;
        return json_encode($data);
        //Template::showpage('goods');
    }
    protected function getStoreInfo($vid,$data=array()) {
        $model_store = new VendorInfo();
        $store_info = $model_store->getStoreOnlineInfoByID($vid);

        if(empty($store_info)) {
            echo lang('该供应商已关闭');
        }

        $data['storeinfo']=$this->outputStoreInfo($store_info);
        $nav = cache('nav')?:cache('nav',true);
        /*foreach ($nav as $k=>$v){
            if($v['lang']!=LANG_TYPE){
                unset($nav[$k]);
            }
        }*/



        $data['StoreNavigation']=$this->getStoreNavigation($vid);
        $data['Seo']=$this->outputSeoInfo($store_info);
        return $data;
    }
    protected function getStoreNavigation($vid) {
        $model_store_navigation = new VendorNavigation();
        return $store_navigation_list = $model_store_navigation->getStoreNavigationList(array('sn_vid' => $vid));
        //Template::output('store_navigation_list', $store_navigation_list);
    }

    protected function outputSeoInfo($store_info) {
        $seo_param = array();
        $seo_param['shopname'] = $store_info['store_name'];
        $seo_param['key']  = $store_info['store_keywords'];
        $seo_param['description'] = $store_info['store_description'];
        $seo = new Seo();
        return $seo->type('shop')->param($seo_param)->show();
    }
    /**
     * 检查店铺开启状态
     *
     * @param int $vid 店铺编号
     * @param string $msg 警告信息
     */
    protected function outputStoreInfo($store_info){
        $model_store = new VendorInfo();
        $model_seller = new Seller();

        //店铺分类
        $goodsclass_model = new MyGoods();
        $goods_class_list = $goodsclass_model->getShowTreeList($store_info['vid']);
        //Template::output('goods_class_list', $goods_class_list);

        //热销排行
        $hot_sales = $model_store->getHotSalesList($store_info['vid'], 5);
        $data['hot_sales']=$hot_sales;
        //Template::output('hot_sales', $hot_sales);

        //收藏排行
        $hot_collect = $model_store->getHotCollectList($store_info['vid'], 5);
        $data['hot_collect']=$hot_collect;
        //Template::output('hot_collect', $hot_collect);

        //卖家列表
        $seller_list = $model_seller->getSellerList(array('vid' => $store_info['vid'], 'is_admin' => '0'), null, 'seller_id asc');

        if ($store_info['store_label']) {
            $store_info['store_label_new'] = UPLOAD_SITE_URL . DS . ATTACH_STORE . DS . $store_info['store_label'];
        } else {
            $store_info['store_label_new'] = '';
        }

        //zz 加入店铺标签***************
        $model = new VendorLabel();
        $label_name = $model->table('bbc_vendor_label')->field('label_name')->where(['id'=>$store_info['label_id']])->find();
        $store_info['label_name'] = $label_name;
        $data['seller_list']=$seller_list;
        $data['store_info']=$store_info;
        return $data;
        //*********************

        //Template::output('seller_list', $seller_list);
        //Template::output('store_info', $store_info);
        //Template::output('page_title', $store_info['store_name']);
//        if (function_exists('getChat')) {
////            getChat('');//调用一次向页面抛出变量
//        }
    }
    public function getComments() {
        $condition = array();
        $condition="geval_goodsid =".input("gid");
        $type = input("type");
        $page = input("page")?input("page"):1;
        $hasImg = input("has_img");
        switch ($type) {
            case '1':
                $condition .= " and geval_scores in (5,4)";
                //Template::output('type', '1');
                break;
            case '2':
                $condition .= " and geval_scores in (3,2)";
                //Template::output('type', '2');
                break;
            case '3':
                $condition .=" and geval_scores =1";
                //Template::output('type', '3');
                break;
        }
        if($hasImg==1){
            $condition .=" and geval_image <>''";
        }if($hasImg==0){
            $condition .="";
        }

        //查询商品评分信息
        $model_evaluate_goods = new EvaluateGoods();
        $goodsevallist = $model_evaluate_goods->getEvaluateGoodsList($condition, $page);
        return json_encode($goodsevallist);
        //Template::output('goodsevallist',$goodsevallist);
        //Template::output('show_page',$model_evaluate_goods->showpage('5'));
    }
}