<?php
namespace app\v1\controller;

use app\v1\model\BrowserHistory;
use app\v1\model\Dian;
use app\v1\model\FirstOrder;
use app\v1\model\Grade;
use app\v1\model\Invoice;
use app\v1\model\Payment;
use app\v1\model\Red;
use app\v1\model\StoreInfo;
use app\v1\model\Transport;
use app\v1\model\UserBuy;
use app\v1\model\UserOrder;
use think\Lang;
use think\Model;
use think\db;
class Buy extends Base
{
    public function __construct() {
        parent::__construct();
        if(!input("member_id")){
            $data["error_code"] = 10016;
            $data['message'] = "缺少参数";
        }
    }

    /**
     * 购物车、直接购买第一步:选择收获地址和配置方式
     */
    public function confirm() {
        $model_buy = new UserBuy();

        $is_supplier = isset($_POST['is_supplier'] )? intval($_POST['is_supplier']) : 0;

        $extends_data = array();

        $extends_data['from'] = 'pc';
        if(isset($_POST['invalid_cart']))
        $invalid_cart= input("invalid_cart");
        else $invalid_cart = "";
        $result = $model_buy->buyStep1($_POST['cart_id'], $_POST['ifcart'], $invalid_cart, input("member_id"), null,$is_supplier,$extends_data);
        $memberId = input("member_id");

        if(!empty($result['error'])) {
            echo $result['error'];
            //showMsg($result['error'], '', 'html', 'error');
        }

        $returns['is_supplier']= $is_supplier;
        $returns['ifcart'] = input("ifcart");
        //商品金额计算(分别对每个商品/优惠套装小计、每个店铺小计)
        //平台优惠券
        //if(!Config('sld_red') && !Config('red_isuse')) {
            $red=con_addons('red', $result + ['member' => array('member_id'=>$memberId)],'confirm','buy');
            $returns['red'] = $red['red'];
            $returns['vred'] = $red['vred'];
        //}
        $newstorecartlist = array();
        $a = 0;
        foreach($result['store_cart_list'] as $k=>$v){
            $newstorecartlist["store_name$a"] = $v[0]["store_name"];
            $newstorecartlist["store_vid$a"] = $v[0]["vid"];
            $newstorecartlist["store_total$a"] = $result['store_goods_total'][$k];
            $newstorecartlist["goods$a"]=$v;
            $newstorecartlist["store_mansong_rule_list$a"] = $result['store_mansong_rule_list'][$k];
            $a++;
        }
        //print_r($newstorecartlist);die;
        $returns['store_cart_list'] = $newstorecartlist;//$result['store_cart_list'];
        $returns['store_goods_total']= $result['store_goods_total'];
        if ($is_supplier) {
            # code...
        }else{
            //取得店铺优惠 - 满即送(赠品列表，店铺满送规则列表)
            $returns['store_premiums_list']= $result['store_premiums_list'];
            $returns['store_mansong_rule_list']= $result['store_mansong_rule_list'];
            //返回店铺可用的优惠券
            if(isset($result['store_voucher_list']))
            $returns['store_voucher_list']= $result['store_voucher_list'];

            if((Config('sld_cashersystem') && Config('cashersystem_isuse')) || (Config('sld_ldjsystem') && Config('ldj_isuse'))){
                //输出自提点信息
                $returns['dian_list']= $result['dian_list'];
            }
        }
        //返回需要计算运费的店铺ID数组 和 不需要计算运费(满免运费活动的)店铺ID及描述
        $returns['need_calc_sid_list']= $result['need_calc_sid_list'];
        $returns['cancel_calc_sid_list']= $result['cancel_calc_sid_list'];

        //将商品ID、数量、运费模板、运费序列化，加密，输出到模板，选择地区AJAX计算运费时作为参数使用
        $returns['freight_hash']= $result['freight_list'];
        //输出用户默认收货地址
        $returns['address_info']= $result['address_info'];
        //输出有货到付款时，在线支付和货到付款及每种支付下商品数量和详细列表
        if(!empty($result['pay_goods_list'])) {
            $returns['pay_goods_list'] = $result['pay_goods_list'];
            $returns['ifshow_offpay'] = $result['ifshow_offpay'];
        }
        if(isset($result['deny_edit_payment']))
        $returns['deny_edit_payment']= $result['deny_edit_payment'];
        //不提供增值税发票时抛出true(模板使用)
        if(!empty($result['vat_deny'])) {
            $returns['vat_deny'] = $result['vat_deny'];
            //增值税发票哈希值(php验证使用)
            $returns['vat_hash'] = $result['vat_hash'];
        }
        //输出默认使用的发票信息
        $returns['inv_info']= $result['inv_info'];
        //显示使用预存款支付及会员预存款
        if(isset($result['available_predeposit']))
        $returns['available_pd_amount']= $result['available_predeposit'];
        if(isset($result['member_points']))
        $returns['member_points']= $result['member_points'];
        if(isset($result['points_max_use']))
        $returns['points_max_use']= $result['points_max_use'];
        if(isset($result['points_purpose_rebate']))
        $returns['points_purpose_rebate']= $result['points_purpose_rebate'];
        if(isset($result['gid']))
        $returns['gid']= $result['gid'];
        $returns['paymethod']= 'againpay';

        //标识 购买流程执行第几步
        $returns['buy_step']='step2';
        return json_encode($returns,true);
    }
    public function ajaxvred()
    {
        $vid = intval($_GET['vid']);
        //平台优惠券
        if(Config('sld_red') && Config('red_isuse')) {
            $model_red = M('red');
            $condition = [];
            //获得可用优惠券
            $condition['reduser_use'] = array( 'eq',0);
            $condition['redinfo_end'] = array( 'gt',TIMESTAMP);
            $condition['redinfo_start'] = array( 'lt',TIMESTAMP);
            $condition['reduser_uid'] = $_SESSION['member_id'];
            $condition['red_vid'] = $vid;
            $red_list = $model_red->getRedUserList($condition);
            if($red_list)
            {
                $red_list = $model_red->getUseInfo($red_list);
            }
            echo json_encode($red_list);
            die;
        }else{
            echo 0;
            die;
        }
    }
    /**
     * 购物车、直接购买第二步:保存订单入库，产生订单号，开始选择支付方式
     *
     */
    public function submitorder() {
        $model_buy = new UserBuy();
        //处理优惠券
        $_POST['red'] = $_POST['red_id'];
        $_POST['order_from'] = 1;

        $_POST['vred'] = array_filter($_POST['vred']);
        $memberId = input("member_id");
        $extends_data = array();

        $extends_data['from'] = 'pc';
        $memberId = input("member_id");
        $userModel = new \app\v1\model\User();
        $memberinfo = $userModel->getMemberInfo(array("member_id"=>$memberId));
        $result = $model_buy->buyStep2($_POST, $memberId, $memberinfo['member_name'], $memberinfo['member_email'],$extends_data,!input("ifcart"));

        if(!empty($result['error'])) {
            echo $result['error'];die;
            //showMsg($result['error'], '', 'html', 'error');
        }
        $return['error_code'] = 200;
        $return['message'] = "下单成功";
        $return['pay_sn'] = $result['pay_sn'];
        return json_encode($return,true);
        //转向到商城支付页面
        //$pay_url = 'index.php?app=buy&mod=pay&pay_sn='.$result['pay_sn'];
        //redirect($pay_url);
    }
    /**
     * 直接购买
     */
    public function buy(){
        if(!input("member_id") || !input("gid")){
            $data['error_code'] = 10016;
            $str = lang("参数错误",null,language);

            $data['message'] = $str;
            return json_encode($data,true);
        }
        $gid = input("gid");
        $memberId = input("member_id");
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

        $data['order_goods_info']=$goods_detail['order_goods_info'];
//        Template::output('order_goods_info', $goods_detail['order_goods_info']);
//        dd($goods_detail['types']);die;
//        $data['types']=$goods_detail['types'];
//        Template::output('types', $goods_detail['types']);


        //首单优惠PC
        $first = new FirstOrder();
        $goods_detail['first'] = $first->getInfo($goods_detail['goods_info']['vid'],$goods_detail['goods_info']['goods_commonid']);
        //条件||pc去掉预售和阶梯团购活动的商品,以后可能会去掉
        //商品详情展示等级优惠列表

        $goods_detail['goods_info']['duration'] = Sec2Time($goods_detail['goods_info']['duration']);

        if(
            Config('member_grade_open') &&
            (
                !(isset($goods_detail['goods_info']['promotion_type']) && !empty($goods_detail['goods_info']['promotion_type']))
                || in_array($goods_detail['goods_info']['promotion_type'],['pin_ladder_tuan','sld_presale'])
            )
        ){
            $vendor_info = db::table('vendor')->where(['vid'=>$goods_detail['goods_info']['vid']])->find();
            if($vendor_info['grade_on_price']){
                $grade_list = $model_grade->getlist([],'*','','grade_value asc');
                $member_grade = $model_grade->getmembergrade($_SESSION['member_id']);
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
            $goods_detail['goods_info']['grade_info'] = $grade_list;

        }
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
                $supplier_buy_flag = $this->checkSupplierRule($_SESSION['member_id'],$_SESSION['vid'],'buy');
            }

            // 批发中心 搜索标示
//            Template::output('supplier_search',ture);
        }
//        Template::output('is_supplier_close', $is_supplier_close);
//        Template::output('supplier_buy_flag', $supplier_buy_flag);
        if (!empty($goods_info['video_url'])){
            $goods_info['video_url']=UPLOAD_SITE_URL . DS . ATTACH_STORE_video . DS .$goods_info['video_url'];
        }
        if (empty($goods_info)) {
//            showMsg(L('商品已下架或不存在'), '', 'html', 'error');
        }
        $storeModel = new \app\v1\model\Store();
        $storeModel->getStoreInfo($goods_info['vid']);
        // 看了又看（同分类本店随机商品）
        $size = '3';
        $goods_rand_list = $model_goods->getGoodsGcStoreRandList($goods_info['gc_id_1'], $goods_info['vid'], $goods_info['gid'], $size);
        $goods_rand_list = array_slice($goods_rand_list,0,3);
        // 获取最终价格
        $goods_rand_list = Model('goods_activity')->rebuild_goods_data($goods_rand_list,'pc');
        $data['goods_rand_list'] = $goods_rand_list;
//        Template::output('goods_rand_list', $goods_rand_list);

//        Template::output('spec_list', $goods_detail['spec_list']);
//        Template::output('spec_image', $goods_detail['spec_image']);
//        Template::output('goods_image', $goods_detail['goods_image']);
//        Template::output('tuan_info', $goods_detail['tuan_info']);
//        Template::output('xianshi_info', $goods_detail['xianshi_info']);
//        Template::output('mansong_info', $goods_detail['mansong_info']);
//        Template::output('mobile_info', $goods_detail['mobile_info']);

        // 浏览过的商品
        $gb = new BrowserHistory();
        $viewed_goods = $gb->getViewedGoodsList(input("member_id"), 20);
//        Template::output('viewed_goods', $viewed_goods);
        //聊天判断身份
        if(DB::name('vendor')->where(['vid'=>$goods_detail['goods_info']['vid'],'member_id'=>input("member_id")])->find()){
//            Template::output('is_vendor_manage', 1);
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
            $snsgoodsinfo = Model('sns_goods')->getSNSGoodsInfo(array('snsgoods_goodsid' => $goods_info['gid']), 'snsgoods_likenum,snsgoods_sharenum');
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
            //$base->wmemcache ( $hash_key, $data, 'product' );
        }

        // 检查是否为店主本人
        $store_self = false;
        if (!empty($_SESSION['vid'])) {
            if ($goods_info['vid'] == $_SESSION['vid']) {
                $store_self = true;
            }
        }
//        Template::output('store_self',$store_self );

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
        if(isset($goods_info['promotion_type'])) {
            $goods_info['goods_promotion_type'] = $goods_info['promotion_type'];
            if ($goods_info['promotion_type'] && $goods_info['promotion_type'] != 'pin') {
                $goods_info['goods_promotion_price'] = $goods_info['promotion_price'];
            }
        }
        if(Config('sld_red') && Config('red_isuse')){
            $par['goods_info']=$goods_info;
            $par['member']=array('member_id'=>input("member_id"));
            $goods_info = con_addons('red',$par);
        }


        //*******拼接老师名*********************************************************

        $tmp = db::name('member')->field('member_name')->where(['member_id'=>$goods_info['teacher']])->find();
        $goods_info['teacher'] = $tmp['member_name'];

        //***************************************************************************

        $area_info = db::name('area')->where(['area_deep'=>['neq',3]])->select();
        foreach ($area_info as $v){
            if($v['area_id']==$goods_info['areaid_1']){
                $goods_info['areaid_1'] = $v['area_name'];
            }

            if($v['area_id']==$goods_info['areaid_2']){
                $goods_info['areaid_2'] = $v['area_name'];
            }
        }
        $data['goods_info'] = $goods_info;
 //       Template::output('goods', $goods_info);


        // 关联版式
        $plateid_array = array();
        if (!empty($goods_info['plateid_top'])) {
            $plateid_array[] = $goods_info['plateid_top'];
        }
        if (!empty($goods_info['plateid_bottom'])) {
            $plateid_array[] = $goods_info['plateid_bottom'];
        }
        if (!empty($plateid_array)) {
            $plate_array = Model('vendor_glmb')->getPlateList(array('plate_id' => array('in', $plateid_array), 'vid' => $goods_info['vid']));
            $plate_array = array_under_reset($plate_array, 'plate_position', 2);
//            Template::output('plate_array', $plate_array);
        }

//        Template::output('vid', $goods_info ['vid']);

        // 批发商品 去掉门店获取数据
        if (!$goods_info['goods_type'] && Config('dian') && Config('dian_isuse')) {
            //获取有该商品的门店
            $dian = new Dian();
            $dians = $dian->getDiansByGid($gid);
            foreach ($dians as $k => $v) {
                $dians[$k]['dian_phone'] = explode(',', $v['dian_phone']);
            }
//            Template::output('dians', $dians);
//            Template::output('dians_page', Model('dian')->showpage());
        }

        // 生成浏览过产品
        $cookievalue = $gid . '-' . $goods_info ['vid'];
        if (cookie('viewed_goods')) {
            $string_viewed_goods = decrypt(cookie('viewed_goods'), MD5_KEY);
            if (get_magic_quotes_gpc()) {
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
 //       setBbcCookie('viewed_goods', $vg_ca);

        //优先得到推荐商品
        $goods_commend_list = $model_goods->getGoodsOnlineList(array('vid' => $goods_info['vid'], 'goods_commend' => 1), 'gid,goods_name,goods_jingle,goods_image,vid,goods_price', 0, 'gid', 12, 'goods_commonid');
        //$goods_commend_list = $model_goods->getGoodsOnlineList(array('vid' => $goods_info['vid'], 'goods_commend' => 1), 'gid,goods_name,goods_jingle,goods_image,vid,goods_price', 0, 'rand()', 12, 'goods_commonid');

        // 获取最终价格
        $goods_commend_list = Model('goods_activity')->rebuild_goods_data($goods_commend_list,'pc');

//        Template::output('goods_commend',$goods_commend_list);


        // 当前位置导航
        //$nav_link_list = Model('goods_class')->getGoodsClassNav($goods_info['gc_id'], 0);
        //$nav_link_list[] = array('title' => $goods_info['goods_name']);
//        Template::output('nav_link_list', $nav_link_list );

        //评价信息
        //$goods_evaluate_info = Model('evaluate_goods')->getEvaluateGoodsInfoByGoodsID($gid);
//        Template::output('goods_evaluate_info', $goods_evaluate_info);

        //判断商品是否收藏
        //$favorite_model = Model('favorites');
        //$favorite_info = $favorite_model->getOneFavorites(array('fav_id'=>"$gid",'fav_type'=>'goods','member_id'=>"{$_SESSION['member_id']}"));
        //if(empty($favorite_info)){
            $favorites_flag = 0;
        //}else{
            $favorites_flag = 1;
        //}
//        Template::output('favorites_flag', $favorites_flag);

        //$seo_param = array ();
        //$seo_param['name'] = $goods_info['goods_name'];
        //$seo_param['key'] = $goods_info['goods_keywords'];
        //$seo_param['description'] = $goods_info['goods_description'];
        //Model('seo')->type('product')->param($seo_param)->show();

//        Template::showpage('goods');
        return json_encode($data,true);
    }

    /**
     * 下单时支付页面
     */
    public function pay() {
        $pay_sn	= $_GET['pay_sn'];
        $memberId = input("member_id");
        if (!preg_match('/^\d{18}$/',$pay_sn)){
            lang('该订单不存在');
        }

        //查询支付单信息
        $model_order= new UserOrder();
        $pay_info = $model_order->getOrderPayInfo(array('pay_sn'=>$pay_sn,'buyer_id'=>$memberId));
        if(empty($pay_info)){
            lang('该订单不存在');
        }
        $returns['pay_info']=$pay_info;
        $page = input("page",0);
        //取子订单列表
        $condition = "pay_sn=$pay_sn";
        $condition .=" and order_state in ('".ORDER_STATE_NEW."','".ORDER_STATE_PAY."','".ORDER_STATE_SEND."','".ORDER_STATE_SUCCESS."')";
        $order_list = $model_order->getOrderList($condition,$page,'order_id,order_state,payment_code,order_amount,pd_amount,order_sn,dian_id,pd_points,red_id,vred_id,refund_state',"",10);
        if (empty($order_list)) {
            $data['message']=lang('未找到需要支付的订单');
            return json_encode($data);
        }
        //重新计算在线支付金额
        $pay_amount_online = 0;
        $pay_amount_offline = 0;
        //订单总支付金额(不包含货到付款)
        $pay_amount = 0;
        foreach ($order_list as $key => $order_info) {
            //计算相关支付金额
            if ($order_info['payment_code'] != 'offline') {
                if ($order_info['order_state'] == ORDER_STATE_NEW) {
                    $pay_amount_online += sldPriceFormat(floatval($order_info['order_amount'])-floatval($order_info['pd_amount']));
                }
                $pay_amount += floatval($order_info['order_amount']);
            } else {
                $pay_amount_offline += floatval($order_info['order_amount']);
            }

            //显示支付方式与支付结果
            if ($order_info['payment_code'] == 'offline') {
                $order_list[$key]['payment_state'] = '货到付款';
            } else {
                $order_list[$key]['payment_state'] = '在线支付';
                if (floatval($order_info['pd_amount']) > 0) {
                    if ($order_info['order_state'] == ORDER_STATE_PAY) {
                        $order_list[$key]['payment_state'] .= " ( 已使用预存款完全支付，支付金额 ￥ {$order_info['pd_amount']} )";
                    } else {
                        $order_list[$key]['payment_state'] .= " ( 已使用预存款部分支付，支付金额 ￥ {$order_info['pd_amount']} )";
                    }
                }
            }
        }
        $returns['order_list']=$order_list;
        $pd_amount=0;
        //如果线上线下支付金额都为0，转到支付成功页
        if (empty($pay_amount_online) && empty($pay_amount_offline)) {
            //redirect('index.php?app=buy&mod=pay_ok&pay_sn='.$pay_sn.'&pay_amount='.sldPriceFormat($pay_amount));
        }

        //输入订单描述
        if (empty($pay_amount_online)) {
            $order_remind = '下单成功，我们会尽快为您发货，请保持电话畅通！';
        } elseif (empty($pay_amount_offline)) {
            $order_remind = '请您及时付款，以便订单尽快处理！';
        } else {
            $order_remind = '部分商品需要在线支付，请尽快付款！';
        }
        $returns['order_remind']=$order_remind;
        $returns['pay_amount_online']=sldPriceFormat($pay_amount_online);
        $returns['pd_amount']=sldPriceFormat($pd_amount);

        //显示支付接口列表
        if ($pay_amount_online > 0) {
            $model_payment = new Payment();
            $condition = array();
            $payment_list = $model_payment->getPaymentOpenList($condition);
            if (!empty($payment_list)) {
                unset($payment_list['predeposit']);
                unset($payment_list['offline']);
            }
            if (empty($payment_list)) {
                lang('暂未找到合适的支付方式');
            }
            $returns['payment_list']=$payment_list;
        }

        //标识 购买流程执行第几步
        $returns['buy_step']='step3';
        return json_encode($returns);
    }

    /**
     * 预存款充值下单时支付页面
     */
    public function pd_pay() {
        $pay_sn	= $_GET['pay_sn'];
        if (!preg_match('/^\d{18}$/',$pay_sn)){
            showMsg(Language::get('参数错误'),'index.php?app=chongzhi','html','error');
        }

        //查询支付单信息
        $model_order= Model('predeposit');
        $pd_info = $model_order->getPdRechargeInfo(array('pdr_sn'=>$pay_sn,'pdr_member_id'=>$_SESSION['member_id']));
        if(empty($pd_info)){
            showMsg(Language::get('参数错误'),'','html','error');
        }
        if (intval($pd_info['pdr_payment_state'])) {
            showMsg(Language::get('您的订单已经支付，请勿重复支付'),'index.php?app=chongzhi','html','error');
        }
        $returns['pdr_info']=$pd_info;

        //显示支付接口列表
        $model_payment = Model('payment');
        $condition = array();
        $condition['payment_code'] = array('not in',array('offline','predeposit'));
        $condition['payment_state'] = 1;
        $payment_list = $model_payment->getPaymentList($condition);
        $returns['payment_list']=$payment_list;

        //标识 购买流程执行第几步
        $returns['buy_step']='step3';
        return json_encode($returns,true);
        //Template::showpage('balancepay');
    }

    /**
     * 支付成功页面
     */
    public function pay_ok() {
        $pay_sn	= $_GET['pay_sn'];
        if (!preg_match('/^\d{18}$/',$pay_sn)){
            showMsg(Language::get('该订单不存在'),'index.php?app=userorder','html','error');
        }

        //查询支付单信息
        $model_order= new UserOrder();
        $pay_info = $model_order->getOrderPayInfo(array('pay_sn'=>$pay_sn,'buyer_id'=>$_SESSION['member_id']));
        if(empty($pay_info)){
            land('该订单不存在');
        }
        $returns['pay_info']=$pay_info;

        $returns['buy_step']='step4';
        return json_encode($returns,true);

    }

    /**
     * 加载买家收货地址
     *
     */
    public function load_addr() {
        $model_addr = Model('address');
        //如果传入ID，先删除再查询
        if (!empty($_GET['id']) && intval($_GET['id']) > 0) {
            $model_addr->delAddress(array('address_id'=>intval($_GET['id']),'member_id'=>$_SESSION['member_id']));
        }
        $list = $model_addr->getAddressList(array('member_id'=>$_SESSION['member_id']));
        $returns['address_list']=$list;
        return json_encode($returns);
        //Template::showpage('buy_address.load','null_layout');
    }

    /**
     * 选择不同地区时，异步处理并返回每个店铺总运费以及本地区是否能使用货到付款
     * 如果店铺统一设置了满免运费规则，则运费模板无效
     * 如果店铺未设置满免规则，且使用运费模板，按运费模板计算，如果其中有商品使用相同的运费模板，则两种商品数量相加后再应用该运费模板计算（即作为一种商品算运费）
     * 如果未找到运费模板，按免运费处理
     * 如果没有使用运费模板，商品运费按快递价格计算，运费不随购买数量增加
     */
    public function change_addr() {
        $model_buy = Model('buy');

        $data = $model_buy->changeAddr($_POST['freight_hash'], $_POST['city_id'], $_POST['area_id'], $_SESSION['member_id']);
        if(!empty($data)) {
            exit(json_encode($data));
        } else {
            exit();
        }
    }

    /**
     * 添加新的收货地址
     *
     */
    public function add_addr(){
        $model_addr = Model('address');
        if (chksubmit()){
            //验证表单信息
            $obj_validate = new Validate();
            $obj_validate->validateparam = array(
                array("input"=>$_POST["true_name"],"require"=>"true","message"=>Language::get('请填写收货人姓名')),
                array("input"=>$_POST["area_id"],"require"=>"true","validator"=>"Number","message"=>Language::get('请选择所在地区')),
                array("input"=>$_POST["address"],"require"=>"true","message"=>Language::get('请填写收货人详细地址'))
            );
            $error = $obj_validate->validate();
            if ($error != ''){
                $error = strtoupper(CHARSET) == 'GBK' ? Language::getUTF8($error) : $error;
                exit(json_encode(array('state'=>false,'msg'=>$error)));
            }
            $data = array();
            $data['member_id'] = $_SESSION['member_id'];
            $data['true_name'] = $_POST['true_name'];
            $data['area_id'] = intval($_POST['area_id']);
            $data['city_id'] = intval($_POST['city_id']);
            $data['area_info'] = $_POST['area_info'];
            $data['address'] = $_POST['address'];
            $data['tel_phone'] = $_POST['tel_phone'];
            $data['mob_phone'] = $_POST['mob_phone'];
            //转码
            $data = strtoupper(CHARSET) == 'GBK' ? Language::getGBK($data) : $data;
            $insert_id = $model_addr->addAddress($data);
            if ($insert_id){
                exit(json_encode(array('state'=>true,'addr_id'=>$insert_id)));
            }else {
                exit(json_encode(array('state'=>false,'msg'=>Language::get('新地址添加失败','UTF-8'))));
            }
        } else {
            Template::showpage('buy_address.add','null_layout');
        }
    }

    /**
     * 加载买家发票列表，最多显示10条
     *
     */
    public function loadinvoice() {
        $model_buy = new UserBuy();
        $returns['error_code'] = 200;
        $memberId = input("member_id");
        $condition = array();
        $vat_hash = input("vat_hash");
        if ($model_buy->buyDecrypt($vat_hash, $memberId) == 'allow_vat') {
        } else {
            $returns['vat_deny']=true;
            $condition['inv_state'] = 1;
        }
        $condition['member_id'] = $memberId;
        $delid = input("del_id",0);
        $model_inv = new Invoice();
        //如果传入ID，先删除再查询
        if (intval($delid) > 0) {
            $model_inv->delInv(array('inv_id'=>intval($_GET['del_id']),'member_id'=>$memberId));
        }
        $list = $model_inv->getInvList($condition,10);
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                if ($value['inv_state'] == 1) {
                    $list[$key]['content'] = lang('普通发票').' '.$value['inv_title'].' '.$value['inv_content'].' '.$value['inv_code'];
                } else {
                    $list[$key]['content'] = lang('增值税发票').' '.$value['inv_company'].' '.$value['inv_code'].' '.$value['inv_reg_addr'];
                }
            }
        }
        $ic = new Invoice();
        $invoice_content_list = $ic->invoice_content_list;

        foreach ($invoice_content_list as &$v){
            $v = lang($v);
        }
//        dd($invoice_content_list);die;

        $returns['inv_list']=$list;
        $returns['invoice_content_list']=$invoice_content_list;
        return json_encode($returns,true);
    }

    /**
     * 新增发票信息  怀疑没地方使用
     *
     */
    public function addinvoice(){
        $model_inv = new Invoice();
        if(!input("member_id")){
            $data['error_code'] = 10016;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }
        if (chksubmit()){
            //如果是增值税发票验证表单信息
            if ($_POST['invoice_type'] == 2) {
                if (empty($_POST['inv_company']) || empty($_POST['inv_code']) || empty($_POST['inv_reg_addr'])) {
                    exit(json_encode(array('state'=>false,'msg'=>lang('保存失败','UTF-8'))));
                }
            }
            $data = array();
            if ($_POST['invoice_type'] == 1) {
                $data['inv_state'] = 1;
                $data['inv_title'] = input('inv_title');
                $data['inv_content'] = input('inv_content');
                $data['inv_reg_phone'] = input('inv_reg_phone',"");
            } else {
                $data['inv_state'] = 2;
                $data['inv_company'] = input('inv_company',"");
                $data['inv_code'] = input('inv_code',"");
                $data['inv_reg_addr'] = input('inv_reg_addr',"");
                $data['inv_reg_phone'] = input('inv_reg_phone',"");
                $data['inv_reg_bname'] = input('inv_reg_bname',"");
                $data['inv_reg_baccount'] = input('inv_reg_baccount',"");
                $data['inv_rec_name'] = input('inv_rec_name',"");
                $data['inv_rec_mobphone'] = input('inv_rec_mobphone',"");
                $data['inv_rec_province'] = input('area_info',"");
                $data['inv_goto_addr'] = input('inv_goto_addr',"");
            }
            $data['member_id'] = input("member_id");
            //转码
            $data = strtoupper(CHARSET) == 'GBK' ? getGBK($data) : $data;
            $insert_id = $model_inv->addInv($data);
            if ($insert_id) {
                $data['error_code'] = 200;
                $data['id'] = $insert_id;
                exit(json_encode($data,true));
            } else {
                $data['error_code'] = 10001;
                exit(json_encode($data,true));
            }
        } else {
            //Template::showpage('buy_address.add','null_layout');
        }
    }


    /**
     * AJAX验证登录密码
     */
    public function check_pay_pwd(){
        if (empty($_GET['password'])) exit('0');
        $buyer_info	= Model('member')->infoMember(array('member_id' => $_SESSION['member_id']));
        echo $buyer_info['member_passwd'] === md5($_GET['password']) ? '1' : '0';
    }
    /*
     * 再来一单控制器
     */
    public function buy_again()
    {
        $order_id = intval($_GET['order_id']);
        $order_model = Model('order');
        try {
            if ($order_id < 1) {
                throw new Exception(Language::get('下单错误'));
            }
            //取可下单的列表
            $goods_list = $order_model->getOrderAgainGoodsList($order_id);
            if(empty($goods_list)){
                throw new Exception(Language::get('商品不存在'));
            }
            $this->confirm_again($goods_list);
        } catch (Exception $e) {
            showMsg($e->getMessage());
        }
    }
    /*
   * 再来一单confirm页
   */
    public function confirm_again($goods_list) {
        $model_buy = Model('buy');

        $is_supplier = $_POST['is_supplier'] ? intval($_POST['is_supplier']) : 0;

        $extends_data = array();

        $extends_data['from'] = 'pc';

        $result = $model_buy->buyStep1($goods_list, 1, '', $_SESSION['member_id'], $_SESSION['vid'],$is_supplier,$extends_data,1);

        if(!empty($result['error'])) {
            showMsg($result['error'], '', 'html', 'error');
        }

        $returns['is_supplier']= $is_supplier;
        $returns['ifcart']= 1;
        //商品金额计算(分别对每个商品/优惠套装小计、每个店铺小计)
        //平台优惠券

        if(C('red_isuse')) {
            $red=con_addons('red', $result + ['member' => array('member_id'=>$_SESSION['member_id'])],'confirm','buy');
            $returns['red']= $red['red'];
            $returns['vred']= $red['vred'];
        }
        $returns['store_cart_list']= $result['store_cart_list'];
        $returns['store_goods_total']= $result['store_goods_total'];
        if ($is_supplier) {
            # code...
        }else{
            //取得店铺优惠 - 满即送(赠品列表，店铺满送规则列表)
            $returns['store_premiums_list']= $result['store_premiums_list'];
            $returns['store_mansong_rule_list']= $result['store_mansong_rule_list'];
            //返回店铺可用的优惠券
            $returns['store_voucher_list']= $result['store_voucher_list'];

            if((Config('sld_cashersystem') && Config('cashersystem_isuse')) || (Config('sld_ldjsystem') && Config('ldj_isuse'))){
                //输出自提点信息
                $returns['dian_list']= $result['dian_list'];
            }
        }
        //返回需要计算运费的店铺ID数组 和 不需要计算运费(满免运费活动的)店铺ID及描述
        $returns['need_calc_sid_list']= $result['need_calc_sid_list'];
        $returns['cancel_calc_sid_list']= $result['cancel_calc_sid_list'];

        //将商品ID、数量、运费模板、运费序列化，加密，输出到模板，选择地区AJAX计算运费时作为参数使用
        $returns['freight_hash']= $result['freight_list'];
        //输出用户默认收货地址
        $returns['address_info']= $result['address_info'];
        //输出有货到付款时，在线支付和货到付款及每种支付下商品数量和详细列表
        $returns['pay_goods_list']= $result['pay_goods_list'];
        $returns['ifshow_offpay']= $result['ifshow_offpay'];
        $returns['deny_edit_payment']= $result['deny_edit_payment'];
        //不提供增值税发票时抛出true(模板使用)
        $returns['vat_deny']= $result['vat_deny'];
        //增值税发票哈希值(php验证使用)
        $returns['vat_hash']= $result['vat_hash'];
        //输出默认使用的发票信息
        $returns['inv_info']= $result['inv_info'];
        //显示使用预存款支付及会员预存款
        $returns['available_pd_amount']= $result['available_predeposit'];
        $returns['member_points']= $result['member_points'];
        $returns['points_max_use']= $result['points_max_use'];
        $returns['points_purpose_rebate']= $result['points_purpose_rebate'];
        $returns['gid']= $result['gid'];

        //标识 购买流程执行第几步
        $returns['buy_step']='step2';
        return json_encode($returns);
    }
    /**
     * 再来一单保存订单入库，产生订单号，开始选择支付方式
     *
     */
    public function submitorder_again() {
        $model_buy = Model('buy');
        //处理优惠券
        $_POST['red'] = $_POST['red_id'];
        $_POST['order_from'] = 1;

        $_POST['vred'] = array_filter($_POST['vred']);

        $extends_data = array();

        $extends_data['from'] = 'pc';

        $result = $model_buy->buyStep2($_POST, $_SESSION['member_id'], $_SESSION['member_name'], $_SESSION['member_email'],$extends_data,1);

        if(!empty($result['error'])) {
            showMsg($result['error'], '', 'html', 'error');
        }

        //转向到商城支付页面
        $pay_url = 'index.php?app=buy&mod=pay&pay_sn='.$result['pay_sn'];
        redirect($pay_url);
    }
    /**
     * 用优惠券，积分，重新计算价格。ajax
     * @param memberId
     * @param redId
     * @param score
     * @param cartId[]
     */
    public function calPrice(){
        if(!input("member_id") || !input("cart_id")){
            $data['error_code']=10016;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }
        //先计算使用优惠券后的价格
        $redId = input("red_id",0);
        $score = input("score",0);
        $cart_id = input("cart_id");
        $model_buy = new UserBuy();
        $result = $model_buy->buyStep1($_POST['cart_id'], $_POST['ifcart'], 0, input("member_id"), null,null,null);
        $memberId = input("member_id");
        $returns['is_supplier']= 0;
        $returns['ifcart'] = input("ifcart");
        //商品金额计算(分别对每个商品/优惠套装小计、每个店铺小计)
        //平台优惠券
        //if(!Config('sld_red') && !Config('red_isuse')) {
        $redModel = new Red();
        $condition ="bbc_red_user.id= $redId";
        $condition .=" and bbc_red_user.reduser_uid = $memberId";
        $condition .=" and bbc_red_user.redinfo_start<".TIMESTAMP."";
        $condition .=" and bbc_red_user.redinfo_end>".TIMESTAMP."";
        $red = $redModel->getUserRedById($condition,null,"bbc_red.*");
        $redMinprice = $red['min_money'];
        $redMaxprice = $red['max_money'];
        //}

        $returns['store_cart_list'] = $result['store_cart_list'];
        $returns['store_goods_total']= $result['store_goods_total'];
        //if ($is_supplier) {
            # code...
        //}else{
            //取得店铺优惠 - 满即送(赠品列表，店铺满送规则列表)
            $returns['store_premiums_list']= $result['store_premiums_list'];
            $returns['store_mansong_rule_list']= $result['store_mansong_rule_list'];
            //返回店铺可用的优惠券
            if(isset($result['store_voucher_list']))
                $returns['store_voucher_list']= $result['store_voucher_list'];

            if((Config('sld_cashersystem') && Config('cashersystem_isuse')) || (Config('sld_ldjsystem') && Config('ldj_isuse'))){
                //输出自提点信息
                $returns['dian_list']= $result['dian_list'];
            }
        //}
        //计算优惠券后的价格；
        $total = $result['total'] - $redMaxprice;
            if($total<=0){
                $total = 0;
            }else{
                $points_max_use = $result['points_max_use'];
                if($score!=0) {
                    $score = $score>$points_max_use?$points_max_use:$score;
                    $total = $total - number_format(floatval($total > $score ? $score : $total) / 100, 2);
                }
            }
            $result = array();
            $result['price'] = $total;
        return json_encode($result,true);
    }
}