<?php
namespace app\v1\controller;

use app\v1\model\Area;
use app\v1\model\BrowserHistory;
use app\v1\model\Dian;
use app\v1\model\EvaluateGoods;
use app\v1\model\EvaluateStore;
use app\v1\model\Favorites;
use app\v1\model\FirstOrder;
use app\v1\model\GoodsActivity;
use app\v1\model\GoodsClass;
use app\v1\model\Grade;
use app\v1\model\MyGoods;
use app\v1\model\Search;
use app\v1\model\Seller;
use app\v1\model\Seo;
use app\v1\model\SnsGoods;
use app\v1\model\Transport;
use app\v1\model\VendorGlmb;
use app\v1\model\VendorInfo;
use app\v1\model\VendorLabel;
use app\v1\model\VendorNavigation;
use think\console\command\make\Model;
use think\db;
class Goods extends  Base
{
    //模型对象
    private $_model_search;
    const PAGESIZE = 24;
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
        $model_goods = new \app\v1\model\Goods();
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

        $model_member = new \app\v1\model\User();
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
                $condition .= " and geval_scores  <=5 and geval_scores >=3.5";
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
    /**
     * 获取分类列表
     */
    function categoryList(){
        $parentId = input("parent_id",0);
        $gc = new GoodsClass();
        $list = $gc->getGoodsClassListByParentId($parentId);
        $childrenList = array();
        $a = 0;
        foreach($list as $k=>$v){
            $childrens = $gc->getGoodsClassListByParentId($list[$k]['gc_id']);
            $c = 0;

            foreach($childrens as $kk=>$vv){
                $childrenList[$k][$a][$kk % 3 ]=$vv;
                if($c==2){
                    $a++;
                }
            }

            //foreach($list[$k]['children'] as $m=>$n){
                //$list[$k]['children'][$m]['children'][$c] = $gc->getGoodsClassListByParentId($list[$k]['children'][$m]['gc_id']);

            //}
        }
        $data['error_code'] = 200;
        $data['list']=$list;
        $data['children'] = $childrenList;
        return json_encode($data,true);
    }
    /**
     * 获取分类列表
     */
    function categoryListByCid(){
        $parentId = input("parent_id",0);
        $gc = new GoodsClass();
        $list = $gc->getGoodsClassListByParentId($parentId);
        $newlist = array();
        foreach ($list as $k=>$v){
            $newlist[$k]['name'] = $v['gc_name'];
            $newlist[$k]['gc_id'] = $v['gc_id'];
        }
        $data['error_code'] = 200;
        $data['list']=$newlist;
        return json_encode($data,true);
    }
    /**
     * 获取分类商品列表
     */
    public function goodslist() {
        if(input("gc_id")){

        }
        $page = input("page",0);
        $this->_model_search = new Search();
        $memberId = input("member_id");
        $conditionstr = "1=1 ";
        $searchtype = input("searchtype",0);
        $data['error_code'] = 200;
        $data['message'] = lang("操作成功");
        if($searchtype ==0 || $searchtype ==1 ) {
            //获取该城市的最后一级id
            $curSldCityId = Logic('city_site')->getUrlCityBindId($_SERVER['HTTP_HOST']);
            //优先从全文索引库里查找
            list($indexer_ids, $indexer_count) = $this->_indexer_search();
            $data_attr = $this->_get_attr_list(input("cid"), input("bid"), input("aid"), $curSldCityId);
            //处理排序
            $order = 'gid desc';
            if (in_array(input('key'), array('1', '2', '3'))) {
                $sequence = input('sort') == '1' ? 'asc' : 'desc';
                $order = str_replace(array('1', '2', '3'), array('goods_salenum', 'goods_click', 'goods_price'), input('key'));
                //虚拟销量
                if (Config('virtual_sale')) {
                    if ($order == 'goods_salenum') {
                        //$order = '(goods_salenum+virtual_sale) as goods_salenum';
                        $order = 'goods_salenum';
                    }
                }
                $order .= ' ' . $sequence;
            }
            $model_goods = new \app\v1\model\Goods();

            $condition = array();
            $tid = intval(input('tid'));

            $data['tid'] = $tid;

            if ($tid) {
                $condition['course_type'] = $tid;
                $conditionstr .= " and course_type=$tid";
            }
            if (!isset($data_attr['sign']) || $data_attr['sign'] === true) {
                // 字段
                $fields = "gid,goods_label,goods_commonid,goods_name,goods_jingle,gc_id,vid,store_name,goods_price,goods_marketprice,goods_storage,goods_image,goods_freight,goods_salenum,color_id,evaluation_good_star,evaluation_count,is_free";
                //虚拟销量
                if (Config('virtual_sale')) {
                    $fields .= ',(goods_salenum+virtual_sale) as goods_salenum';
                }
                // 只检索零售商品
                $condition['goods_type'] = 0;

                //执行正常搜索，重新查库
                if (isset($data_attr['gcid_array'])) {
                    $condition['gc_id'] = array('in', arrayToString($data_attr['gcid_array']));
                    $conditionstr .= " and gc_id in ( " . arrayToString($data_attr['gcid_array']) . ") ";
                }
                if (intval(input('b_id')) > 0) {
                    $condition['brand_id'] = intval(input('b_id'));
                }
                if (input('keyword') != '') {
                    $condition['goods_name'] = array('like', '%' . input('keyword') . '%');
                    $conditionstr .= " and goods_name like '%" . input('keyword') . "%'";
                }
                //如果搜索的一级地区id跟当前绑定的城市分站一级id一致，正常搜索，不一致的话，以信息

                if (intval(input('area_id')) > 0) {
                    $condition['areaid_1'] = intval(input('area_id'));
                }
                if (in_array(input('t'), array(1, 2))) {
                    if (input('t') == 1) {
                        $condition['is_own_shop'] = 1;
                        $conditionstr .= " and is_own_shop=1";
                    } else if (input('t') == 2) {
                        $condition['is_own_shop'] = 0;
                        $conditionstr .= " and is_own_shop=0";
                    }
                }
                if (isset($data_attr['goodsid_array'])) {
                    $condition['gid'] = array('in', $data_attr['goodsid_array']);
                    $conditionstr .= " and gid  in (" . arrayToString($data_attr['goodsid_array']) . ")";
                }


                //[start搜索优惠券的商品]
                //点击优惠券过来的 首先判断是否有指定商品
                if (input('red_gids')) {
                    $gids = explode(',', input('red_gids'));
                } else if (input('red_gc_id')) {//判断是否有指定分类
                    $gc_ids = explode(',', input('red_gc_id'));
                }
                //商品
                if (!empty($gids)) {
                    $condition['gid'] = array('in', arrayToString($gids));
                    $conditionstr .= " and gid in (" . arrayToString($gids) . ")";
                } else if (!empty($gc_ids) && input('store_self') == 1) {//自营店分类
                    $condition['gc_id_1'] = array('in', implode(",", $gc_ids));
                    //获取与所有的自营店
                    $model_vendor = new VendorInfo();
                    $shop_vids = $model_vendor->getStoreOnlineList(array('is_own_shop' => 1), '', '', 'vid');
                    $vids = array();
                    foreach ($shop_vids as $v) {
                        $vids[] = $v['vid'];
                    }
                    $condition['vid'] = array('in', implode(",", $vids));
                } else if (!empty($gc_ids) && input('store_self') != 1) {//所有店铺分类
                    $condition['gc_id_1'] = array('in', $gc_ids);
                    $conditionstr .= "gc_id_1 in (" . arrayToString($gc_ids) . ")";
                } else if (input('red_vid')) {//如果没有指定商品和分类 判断店铺优惠券
                    $condition['vid'] = input('red_vid');
                    $conditionstr .= " and vid=" . input("red_vid");
                } else if (input('store_self') == 1) {//自营店所有商品
                    //获取与所有的自营店
                    $model_vendor = new VendorInfo();
                    $shop_vids = $model_vendor->getStoreOnlineList(array('is_own_shop' => 1), '', '', 'vid');
                    $vids = array();
                    foreach ($shop_vids as $v) {
                        $vids[] = $v['vid'];
                    }
                    $condition['vid'] = array('in', arrayToString($vids));
                    $conditionstr .= " and vid in (" . arrayToString($vids) . ")";
                }

                //公开课时间筛选
                if ($tid == 1) {

                    if (input('con_time')) {
                        list($con_start, $con_end) = explode(' ', input('con_time'));

                        $con_start = strtotime($con_start);
                        $con_end = strtotime($con_end);

                        $condition['con_start'] = ['exp', "NOT ((con_end < $con_start) OR (con_start > $con_end))"];
                        $conditionstr .= " and not ((con_end<$con_start ) OR (con_start > $con_end))";
                    }
                }


                //价格筛选字段
                if (input('m_price')) {
                    list($m_min, $m_max) = explode('-', input('m_price'));

                    $condition['goods_price'] = ['exp', " (goods_price <= $m_max) and (goods_price > $m_min) "];
                    $conditionstr .= " and goods_price <=$m_max and goods_price >$m_min";
                }

                //评级筛选
                if (input('m_star')) {
                    $star = intval(input('m_star'));
                    $eva_data = DB::name("evaluate_goods")->field('geval_ordergoodsid as gcid,avg(geval_scores) as star')->group('geval_ordergoodsid')->having('star>' . $star . ' and star<' . ($star + 1))->select();
                    $stars = low_array_column($eva_data, 'gcid');
                    $condition['goods_commonid'] = ['in', join(',', $stars)];
                    if (!empty($stars))
                        $conditionstr .= " and goods_commonid in (0," . arrayToString($stars) . ") ";
                }

                //店铺类型
                if (input('m_type')) {
                    $m_type = intval(input('m_type'));

                    $condition['store_type'] = $m_type;
                    $conditionstr .= " and store_type=$m_type";
                }
                //[end搜索优惠券的商品]

                //按照商品的SPU展示
                $goods_list = $model_goods->getGoodsListByCommonidDistinct($conditionstr, $fields, $order, $page);
                // 商品多图
                if (!empty($goods_list)) {
                    $goodsid_array = array();       // 商品id数组
                    $commonid_array = array(); // 商品公共id数组
                    $storeid_array = array();       // 店铺id数组
                    foreach ($goods_list as $value) {
                        $goodsid_array[] = $value['gid'];
                        $commonid_array[] = $value['goods_commonid'];
                        $storeid_array[] = $value['vid'];
                    }
                    $goodsid_array = array_unique($goodsid_array);
                    $commonid_array = array_unique($commonid_array);
                    $storeid_array = array_unique($storeid_array);

                    // 商品多图
                    $goods = new \app\v1\model\Goods();
                    $goodsimage_more = $goods->getGoodsImageList(array('goods_commonid' => array('in', arrayToString($commonid_array))));

                    // 店铺
                    $vendor = new VendorInfo();
                    $store_list = json_decode($vendor->getStoreMemberIDList($storeid_array), true);
                    $favorite_model = new Favorites();
                    // $model_sole = Model('p_mbuy');
//                $tobuy_detail_model = Model('today_buy_detail');
//                $tobuy_time_model = Model('today_buy');
                    foreach ($goods_list as $key => $value) {
                        $favorite_info = $favorite_model->getOneFavorites(array('fav_id' => $value["gid"], 'fav_type' => 'goods', 'member_id' => $memberId));
                        if (empty($favorite_info)) {
                            $favorites_flag = 0;
                        } else {
                            $favorites_flag = 1;
                        }
                        $goods_list[$key]['favorites_flag'] = $favorites_flag;
                        // 商品多图
                        foreach ($goodsimage_more as $v) {
                            if ($value['goods_commonid'] == $v['goods_commonid'] && $value['vid'] == $v['vid'] && $value['color_id'] == $v['color_id']) {
                                $goods_list[$key]['image'][] = $v;
                            }
                        }
                        // 店铺的开店会员编号
                        $vid = $value['vid'];
                        if (isset($store_list[$vid])) {
                            $goods_list[$key]['member_id'] = $store_list[$vid]['member_id'];
                            $goods_list[$key]['store_domain'] = $store_list[$vid]['store_domain'];
                        }
                    }
                }

                // 获取最终价格
                $ga = new GoodsActivity();
                $goods_list = $ga->rebuild_goods_data($goods_list, 'pc');
                $data['goods_list'] = $goods_list;
                //Template::output('goods_list', $goods_list);
            }
            if (isset($data_attr['gc_name']))
                $data['class_name'] = $data_attr['gc_name'];
            //Template::output('class_name',  @$data_attr['gc_name']);
            //热卖推荐（销量的前4个）
            //虚拟库存
            if (Config('virtual_sale')) {
                $field = '*,(goods_salenum+virtual_sale) as goods_salenum';
                $order = "goods_salenum desc";
            } else {
                $field = '*';
                $order = 'goods_salenum desc';
            }
            $sld_hotsale_goods = $model_goods->getGoodsOnlineList($condition, $field, 0, $order, 4, 'goods_commonid');
            // 获取最终价格
            $ga = new GoodsActivity();
            $sld_hotsale_goods = $ga->rebuild_goods_data($sld_hotsale_goods, 'pc');

            $data['sld_hotsale_goods'] = $sld_hotsale_goods;
            //显示左侧分类
            if (intval(input('cid')) > 0) {
                $goods_class_array = $this->_model_search->getLeftCategory(array(input('cid')));
            } elseif (input('keyword') != '') {
                $goods_class_array = $this->_model_search->getTagCategory(input('keyword'));
            } else {
                $goods_class_array = array();
            }
            $data['goods_class_array'] = $goods_class_array;

            if (input('keyword') == '') {
                //不显示无商品的搜索项
                if (Config('fullindexer.open')) {
                    $data_attr['brand_array'] = $this->_model_search->delInvalidBrand($data_attr['brand_array']);
                    $data_attr['attr_array'] = $this->_model_search->delInvalidAttr($data_attr['attr_array']);
                }
            }

            //抛出搜索属性
            if (isset($data_attr['brand_array']))
                $data['brand_array'] = $data_attr['brand_array'];
            if (isset($data_attr['attr_array']))
                $data['attr_array'] = $data_attr['attr_array'];
            if (isset($data_attr['cate_array']))
                $data['cate_array'] = $data_attr['cate_array'];
            if (isset($data_attr['checked_brand']))
                $data['checked_brand'] = $data_attr['checked_brand'];
            if (isset($data_attr['checked_attr']))
                $data['checked_attr'] = $data_attr['checked_attr'];


            // SEO
            if (input('keyword') == '') {
                $seo_class_name = @$data_attr['gc_name'];
                if (is_numeric(input('cid')) && empty(input('keyword'))) {
                    $model_goods_class = new GoodsClass();
                    $seo_info = $model_goods_class->getKeyWords(input('cid'));
                    if (empty($seo_info[1])) {
                        $seo_info[1] = Config('site_name') . ' - ' . $seo_class_name;
                    }
                    // Model('seo')->type($seo_info)->param(array('name' => $seo_class_name))->show();
                } else if (input('keyword') != '') {
                    // Template::output('html_title', (empty($_GET['keyword']) ? '' : $_GET['keyword'] . ' - ') . C('site_name') . L('bbc_common_search'));
                }
            }


            //获得价格区间
            $m_price = ['max' => 3200, 'min' => 500];
            $ti = 0;
            for ($i = 0; $i < 2; $i++) {
                $v = ceil(($m_price['max'] - $ti) / (2 - $i));
                $m_price_arr[$i]['min'] = $ti;
                $m_price_arr[$i]['max'] = $ti + $v;
                $m_price_arr[$i]['val'] = $m_price_arr[$i]['min'] . '-' . $m_price_arr[$i]['max'];
                if ($m_price_arr[$i]['min'] == 0) {
                    $m_price_arr[$i]['txt'] = lang('货币符号') . ' ' . $m_price_arr[$i]['max'] . ' ' . lang('以下');
                } else {
                    $m_price_arr[$i]['txt'] = lang('货币符号') . ' ' . $m_price_arr[$i]['min'] . ' - ' . lang('货币符号') . ' ' . $m_price_arr[$i]['max'];
                }
                $ti += $v;
            }
            $m_price_arr[2]['min'] = $ti;
            $m_price_arr[2]['max'] = 99999;
            $m_price_arr[2]['val'] = $ti . '-99999';
            $m_price_arr[$i]['txt'] = lang('货币符号') . ' ' . $ti . ' ' . lang('以上');


            $data['m_price'] = $m_price_arr;


            // 得到自定义导航信息
            $nav_id = intval(input('nav_id')) ? intval(input('nav_id')) : 0;
            $data['index_sign'] = $nav_id;

            // 根据商品筛选条件 得出存在的一级地区
            if ($tid == 1) {
                unset($condition['areaid_1']);
                $area_list = $model_goods->name('goods_common')->alias('gc')->join('area a', 'gc.areaid_1=a.area_id')->where($condition)->group('a.area_id')->field('a.area_name,a.area_id')->cache('area_id')->select();
                $data['area_list'] = $area_list;

                //定义公开课的时间
                $today = date('Y-m-d');


                $ddate['today1'] = ['txt' => lang('近一个月'), 'val' => $today . ' ' . date('Y-m-d', strtotime('+1 month'))];
                $ddate['today3'] = ['txt' => lang('近三个月'), 'val' => $today . ' ' . date('Y-m-d', strtotime('+3 month'))];
                $ddate['today6'] = ['txt' => lang('近半年'), 'val' => $today . ' ' . date('Y-m-d', strtotime('+6 month'))];

                $data['con_time'] = $ddate;
                $data['con_time_now'] = explode(' ', input('con_time'));
                $data['m_price_arr'] = explode('-', input('m_price'));
            }

            //loadfunc('search');
        }
        if($searchtype == 0 || $searchtype == 2 ){
            $keyword = input("keyword");
            $conditions = "1=1 ";
            $conditions .= " and (store_name like '%$keyword%' or store_zy like '%$keyword%')";
            $curSldCityId = Logic('city_site')->getUrlCityBindId($_SERVER['HTTP_HOST']);
            //绑定的城市分站id大于0的情况下采取根据地址选择
            if($curSldCityId>0){
                $conditions .=" and (province_id=$curSldCityId or city_id=$curSldCityId or area_id = $curSldCityId)";
            }
            $conditions.=" and store_state = 1";
            $conditions.=" and sld_is_supplier = 0";
            $model_store = new VendorInfo();
            $order = 'store_sort asc';
            $store_list = db::name("vendor")->where($conditions)->order($order)->page(1)->select();
            //获取店铺商品数，推荐商品列表等信息
            $store_list = $model_store->getStoreSearchList($store_list);
            $data['store_list'] = $store_list;
        }

        // 浏览过的商品
        $goods = new \app\v1\model\Goods();
        $viewed_goods = $goods->getViewedGoodsList();
        $data['viewed_goods'] = $viewed_goods;
        //$data['goods_list'] = $goods_list;
        return json_encode($data,true);
        //$data['goodslist');

    }

    private function _indexer_search()
    {
        if (!Config('fullindexer.open')) return array(null,0);

        $condition = array();

        //拼接条件
        if (intval(input('cid')) > 0) {
            $cid = intval(input('cid'));
            $model = new \app\v1\model\Goods();
            $goods_class = $model->H('goods_class') ? $model->H('goods_class') : $model->H('goods_class', true);
            $depth = $goods_class[$cid]['depth'];
            $cate_field = 'cate_'.$depth;
            $condition['cate']['key'] = $cate_field;
            $condition['cate']['value'] = $cid;
        }
        if ($_GET['keyword'] != '') {
            $condition['keyword'] = $_GET['keyword'];
        }
        if (intval($_GET['b_id']) > 0) {
            $condition['brand_id'] = intval($_GET['b_id']);
        }
        if (preg_match('/^[\d_]+$/',$_GET['a_id'])) {
            $attr_ids = explode('_',$_GET['a_id']);
            if (is_array($attr_ids)){
                foreach ($attr_ids as $v) {
                    if (intval($v) > 0) {
                        $condition['attr_id'][] = intval($v);
                    }
                }
            }
        }
        if (in_array($_GET['t'],array('1','2'))) {
            $condition['vid'] = $_GET['t'];
        }
        if (intval($_GET['area_id']) > 0) {
            $condition['area_id'] = intval($_GET['area_id']);
        }

        //拼接排序(销量,浏览量,价格)
        $order = array();
        $order['key'] = 'gid';
        $order['value'] = false;
        if (in_array(input('key'),array('1','2','3'))) {
            $order['value'] = input('sort') == '1' ? true : false;
            $order['key'] = str_replace(array('1','2','3'), array('goods_salenum','goods_click','goods_price'), $_GET['key']);
        }

        //取得商品主键等信息
        $this->_model_search = new Search();
        $result = $this->_model_search->getIndexerList($condition,$order,self::PAGESIZE);
        if ($result !== false) {
            list($indexer_ids,$indexer_count) = $result;
            //如果全文搜索发生错误，后面会再执行数据库搜索
        } else {
            $indexer_ids = null;
            $indexer_count = 0;
        }

        return array($indexer_ids,$indexer_count);
    }


    /**
     * 取得商品属性
     */
    private function _get_attr_list($cid,$bid,$aid,$areaid) {
        if (intval($cid) > 0) {
            $search = new search();
            $data = $search->getAttrLists($cid,$bid,$aid,$areaid);
        } else {
            $data = array();
        }
        return $data;
    }
}