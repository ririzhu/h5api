<?php
namespace app\v1\controller;
use app\v1\model\GoodsActivity;
use app\v1\model\Pbundling;
use app\v1\model\Stats;
use app\v1\model\UserCart;
use app\v1\model\Goods;
use app\v1\model\VendorInfo;
use think\Request;
use think\Db;

class Cart extends Base
{
    /**
     * 购物车首页
     */
    public function index() {
        $model_cart	= new UserCart();
        if(!\request()->isPost()){
            $data['error_code'] = 10001;
            $data['message'] = '使用了非法提交方式';
            return json_encode($data,true);
        }
        if(!input('member_id')){
            $data['error_code'] = 10015;
            $data['message'] = '缺少必要参数';
            return json_encode($data,true);
        }
        $data['error_code'] = 200;
        $member_id = input("member_id");
        $cart_show_type = input("st") ? 1 : 0;
        $has_count = $model_cart->checkCart(array('buyer_id'=>$member_id));
        $data['has_count'] = $has_count ? true :false;
        // 普通购物车是否有数据
        $normal_has_count = $model_cart->checkCart(array('buyer_id'=>$member_id,'sld_is_supplier'=>0));
        $data['normal_has_count'] = count($normal_has_count);
        // 批发中心购物车是否有数据
        $supplier_has_count = $model_cart->checkCart(array('buyer_id'=>$member_id,'sld_is_supplier'=>1));
        $data['supplier_has_count'] = count($supplier_has_count);
        if ($supplier_has_count && !$normal_has_count) {
            $cart_show_type = 1;
        }elseif (!$supplier_has_count && $normal_has_count) {
            $cart_show_type = 0;
        }


        //取出购物车信息
        $cart_list	= $model_cart->listCart('db',array('buyer_id'=>$member_id,'sld_is_supplier'=>$cart_show_type));
        $data['cart_list'] = $cart_list;


        if ($cart_show_type) {
            // 批发商品 购物车

            //取商品最新的在售信息
            $cart_list = $model_cart->getOnlineCartList($cart_list);
            $new_cart_list = array();
            foreach ($cart_list as $key => $cart_item) {
                if ($cart_item['has_spec']) {
                    // 有规格 (获取规格信息)
                    $goods = new Goods();
                    $spec_array = $goods ->getGoodsList(array('goods_commonid' => $cart_item['goods_commonid']), 'goods_spec,gid,vid,goods_image,color_id,goods_storage');
                    $spec_list = array();       // 各规格商品地址，js使用
                    foreach ($spec_array as $s_key => $value) {
                        $s_array = unserialize($value['goods_spec']);

                        $tmp_array = array();
                        if (!empty($s_array) && is_array($s_array)) {
                            foreach ($s_array as $k => $v) {
                                $tmp_array[] = $k;
                            }
                        }
                        sort($tmp_array);
                        $spec_sign = implode('|', $tmp_array);

                        $spec_list[$spec_sign]['storage'] = $value['goods_storage'];
                        $spec_list[$spec_sign]['field_name'] = implode('/', $s_array);
                    }
                    $cart_item['spec_data'] = $spec_list;
                    $cart_item['spec_num_arr'] = unserialize($cart_item['spec_num']);
                }else{
                    $cart_item['spec_data'] = array();
                    $cart_item['spec_num_arr'] = array();
                }
                $cart_list[$key] = $cart_item;
            }
            //购物车商品以店铺ID分组显示,并计算商品小计,店铺小计与总价由JS计算得出
            $store_cart_list = array();
            foreach ($cart_list as $cart) {
                $cart['goods_total'] = sldPriceFormat($cart['goods_price'] * $cart['goods_num']);
                $store_cart_list[$cart['vid']][] = $cart;
            }

            //店铺信息
            $vendorModel = new VendorInfo();
            $store_list[] = $vendorModel->getStoreMemberIDList(array_keys($store_cart_list));
            $data['store_list'] = $store_list;
            //取得哪些店铺有满免运费活动
            $free_freight_list = $model_cart->getFreeFreightActiveList(array_keys($store_cart_list));
            $data['free_freight_list'] = $free_freight_list;
        }else{
            //取商品最新的在售信息
             $cart_list = $model_cart->getOnlineCartList($cart_list);

            //得到团购信息
            $cart_list = $model_cart->getTuanCartList($cart_list);

            //得到限时折扣信息
            $cart_list = $model_cart->getXianshiCartList($cart_list);

            //得到今日抢购信息
            $cart_list = $model_cart->getTobuyCartList($cart_list);

            //得到优惠套装状态,并取得组合套装商品列表
            $cart_list = $model_cart->getBundlingCartList($cart_list);
            //购物车商品以店铺ID分组显示,并计算商品小计,店铺小计与总价由JS计算得出
            $store_cart_list[] = array();
            foreach ($cart_list as $cart) {
                //团购商品的话 超出限购数量会按照原价去购买
                if(isset($cart['promotion_type']) && $cart['promotion_type'] == 'tuan'){
                    if($cart['goods_num']>$cart['upper_limit']){
                        $cart['goods_total'] = sldPriceFormat($cart['promotion_price'] * $cart['upper_limit']+$cart['goods_price'] * ($cart['goods_num']-$cart['upper_limit']));
                    }else{
                        $cart['goods_total'] = sldPriceFormat($cart['promotion_price'] * $cart['goods_num']);
                    }
                }else{
                    $cart['goods_total'] = sldPriceFormat($cart['goods_price'] * $cart['goods_num']);

                }
                $store_cart_list[$cart['vid']][] = $cart;
            }
            //店铺信息
            $vendorModel = new VendorInfo();
            $store_list = $vendorModel->getStoreMemberIDList(array_keys($store_cart_list));
            $data['store_list'] = $store_list;
            //取得店铺级活动 - 可用的满即送活动
            $mansong_rule_list = $model_cart->getMansongRuleList(array_keys($store_cart_list));
            $data['mansong_rule_list'] = $mansong_rule_list;
            //取得哪些店铺有满免运费活动
            $free_freight_list = $model_cart->getFreeFreightActiveList(array_keys($store_cart_list));
            $data['free_freight_list'] = $free_freight_list;
        }
        return json_encode($data,true);
    }


    /**
     * 购物车更新商品数量
     */
    public function update() {
        if(!input("cart_id") || !input("quantity") || !input("member_id")){
            $data['error_code']=10016;
            $data['message'] = "缺少参数";
            return json_encode($data);exit;
        }
        $cartId = input("cart_id");
        $quantitys = input("quantity");
        $memberId = input("member_id");
        $cart_id	= intval(abs($cartId));
        // $quantity	= intval(abs($_GET['quantity']));
        //$quantity = intval(abs($quantity));



        $model_cart = new UserCart();
        $model_goods= new Goods();

        //存放返回信息
        $return = array();

        $cart_info = $model_cart->getCartInfo(array('cart_id'=>$cart_id,'buyer_id'=>$memberId));
        if ($cart_info['bl_id'] == '0') {

            //普通商品
            $gid = intval($cart_info['gid']);
            $goods_info	= $model_goods->getGoodsOnlineInfo(array('gid'=>$gid));
            $ispresale = DB::name("pre_goods")->join("bbc_presale",'bbc_pre_goods.pre_id = bbc_presale.pre_id')->where("gid=$gid and pre_start_time<=".TIMESTAMP." and pre_end_time>=pre_end_time and pre_status=1")->find();
            if(!empty($ispresale)){
                $goods_info['goods_price'] = $ispresale['pre_sale_price'];
            }else
                $goods_info['goods_price'] = $goods_info['goods_price'];

            if($goods_info['course_type']!=1){
                $quantity = 1;
            }
            //会员等级价格--start
            $goodsActivity = new GoodsActivity();
            $goods_info = $goodsActivity->rebuild_goods_data($goods_info,'pc',['grade'=>1]);
            if(isset($goods_item['show_price']))
            $goods_info['goods_price'] = $goods_info['show_price'];
            //会员等级价格--end
            if(empty($goods_info)) {
                $data['error_code'] = 10017;
                $data['message'] = lang("商品已经下架");
                $model_cart->delCart('db',array('cart_id'=>$cart_id,'buyer_id'=>$memberId));
                exit(json_encode($return));
            }
            if ($cart_info['sld_is_supplier']) {
                // 校验是否 满足最低数量
                $goods_buy_num = is_array($quantity) ? array_sum($quantity) : $quantity;
                $sld_ladder_price = unserialize($goods_info['sld_ladder_price']);
                $sld_ladder_numbers = array_keys($sld_ladder_price);
                $min_ladder_numbers = min($sld_ladder_numbers);

                if (is_array($quantity)) {
                    // 多规格 商品
                    // 需要进行  多规格 分别的库存校验
                    // 查询所有规格商品
                    $spec_array = $model_goods->getGoodsList(array('goods_commonid' => $goods_info['goods_commonid']), 'goods_spec,gid,vid,goods_image,color_id,goods_storage,sld_ladder_price');

                    if ($goods_info['goods_type'] == 1) {
                        $spec_list = array();       // 各规格商品地址，js使用
                        $spec_list_mobile = array();       // 各规格商品地址，js使用
                        $spec_image = array();      // 各规格商品主图，规格颜色图片使用
                        foreach ($spec_array as $key => $value) {
                            $s_array = unserialize($value['goods_spec']);
                            $tmp_array = array();
                            if (!empty($s_array) && is_array($s_array)) {
                                foreach ($s_array as $k => $v) {
                                    $tmp_array[] = $k;
                                }
                            }
                            sort($tmp_array);
                            $spec_sign = implode('|', $tmp_array);

                            $spec_list[$spec_sign] = $value['goods_storage'];
                        }
                        // 多规格库存不足 返回当前规格的最大库存
                        $check_storage_flag = 0;
                        $new_quantity = array();
                        foreach ($quantity as $key => $value) {
                            if(intval($spec_list[$key]) < $value) {
                                $new_quantity[$key] = intval($spec_list[$key]);
                                $check_storage_flag = 1;
                            }else{
                                $new_quantity[$key] = intval($value);
                            }
                        }
                        // 计算当前数量的单价
                        $check_flag = true;
                        $before_number = 0;
                        $before_price = 0;
                        $count_i = 0;
                        $total_i = 0;
                        $sld_ladder_price = unserialize($goods_info['sld_ladder_price']);
                        ksort($sld_ladder_price);
                        $total_number = is_array($new_quantity) ? array_sum($new_quantity) : 0;
                        $total_i = count($sld_ladder_price);
                        foreach ($sld_ladder_price as $k => $item) {
                            if($check_flag){
                                if($before_number == 0 && $before_price == 0){
                                    $now_price = $item*1;
                                }else{
                                    if ($total_number*1 >= $k*1 && ($total_i-1) == $count_i) {
                                        // 最后一个
                                        $now_price = $item*1;
                                        $check_flag = false;
                                    }elseif($total_number*1 < $k*1 && $total_number*1>= $before_number*1){
                                        $now_price = $before_price*1;
                                        $check_flag = false;
                                    }
                                }
                                $before_number = $k*1;
                                $before_price = $item*1;
                            }
                            $count_i++;
                        }
                        // 带规格的 批发商品
                        if ($goods_buy_num < $min_ladder_numbers) {
                            // 不满足 最低购买数量
                            $data['error_code'] = 10018;
                            $data['message'] = lang("没满足批发的最低数量");
                            $data['goods_price'] = $cart_info['goods_price'];
                            $data['spec_num'] = unserialize($cart_info['spec_num']);
                            exit(json_encode($data));
                        }
                        // 获取 总价
                        if ($check_storage_flag) {
                            $data['error_code'] = 10019;
                            $data['message'] = "库存不足";
                            $data['goods_num'] = $total_number;
                            $data['goods_price'] = $now_price;
                            $data['spec_num'] = serialize($new_quantity);
                            $data['subtotal'] = $now_price * $total_number;
                            $model_cart->editCart(array('goods_num'=>$goods_info['goods_storage'],'spec_num'=>$return['spec_num'],'goods_price'=>$return['goods_price']),array('cart_id'=>$cart_id,'buyer_id'=>$memberId));
                            exit(json_encode($return));
                        }
                    }
                }else{
                    // 带规格的 批发商品
                    if ($goods_buy_num < $min_ladder_numbers) {
                        // 不满足 最低购买数量
                        $data['error_code'] =10020;
                        $data['message'] = lang("没满足批发的最低数量");
                        $data['goods_price'] = $return['goods_price'] = $cart_info['goods_price'];;
                        $data['goods_num'] = $cart_info['goods_num'];
                        exit(json_encode($return));
                    }
                    // 无规格 商品
                    if(intval($goods_info['goods_storage']) < $quantity) {

                        // 计算当前数量的单价
                        $check_flag = true;
                        $before_number = 0;
                        $before_price = 0;
                        $count_i = 0;
                        $total_i = 0;
                        $sld_ladder_price = unserialize($goods_info['sld_ladder_price']);
                        ksort($sld_ladder_price);
                        $total_number = $quantity;
                        $total_i = count($sld_ladder_price);
                        foreach ($sld_ladder_price as $k => $item) {
                            if($check_flag){
                                if($before_number == 0 && $before_price == 0){
                                    $now_price = $item*1;
                                }else{
                                    if ($total_number*1 >= $k*1 && ($total_i-1) == $count_i) {
                                        // 最后一个
                                        $now_price = $item*1;
                                        $check_flag = false;
                                    }elseif($total_number*1 < $k*1 && $total_number*1>= $before_number*1){
                                        $now_price = $before_price*1;
                                        $check_flag = false;
                                    }
                                }
                                $before_number = $k*1;
                                $before_price = $item*1;
                            }
                            $count_i++;
                        }

                        $data['error_code'] = 10019;
                        $data['message'] = "库存不足";
                        $data['goods_num'] = $goods_info['goods_storage'];
                        $data['goods_price'] = $now_price;
                        $data['subtotal'] = $now_price * $quantitys;
                        $model_cart->editCart(array('goods_num'=>$goods_info['goods_storage'],'goods_price'=>$return['goods_price']),array('cart_id'=>$cart_id,'buyer_id'=>$memberId));
                        exit(json_encode($data));
                    }
                }
            }else{

                //如果是在限时折扣中,json返回价格，重新计算
                $cartModel = new UserCart();
                $xianshi_info = $cartModel->getXianshiInfo($goods_info,$quantitys);
                if (!empty($xianshi_info)) {
                    $cart_info['goods_price'] = $xianshi_info['goods_price'];
                }
                //如果是团购，json返回价格和总价
                //得到团购信息
                $tuan_list = $cartModel->getTuanInfo($goods_info);
//                die;



                if(intval($goods_info['goods_storage']) < $quantitys) {
                    $data['error_code'] = 10019;
                    $data['message'] = "库存不足";
                    $data['goods_num'] = $goods_info['goods_storage'];
                    $data['goods_price'] = $cart_info['goods_price'];
                    $data['subtotal'] = $cart_info['goods_price'] * $quantitys;
                    $model_cart->editCart(array('goods_num'=>$goods_info['goods_storage']),array('cart_id'=>$cart_id,'buyer_id'=>$memberId));
                    exit(json_encode($return));
                }
            }

        } else {

            //优惠套装商品
            $model_bl = new Pbundling();
            $bl_goods_list = $model_bl->getBundlingGoodsList(array('bl_id'=>$cart_info['bl_id']));
            $goods_id_array = array();
            foreach ($bl_goods_list as $goods) {
                $goods_id_array[] = $goods['gid'];
            }
            $key = 0;
            $cart_list[$key]['bl_goods_list'] = $model_goods->getGoodsOnlineList(array('gid'=>array("in",arrayToString($goods_id_array))));

            //如果其中有商品下架，删除
            if (count($cart_list[$key]['bl_goods_list']) != count($goods_id_array)) {
                $data['error_code'] = 10020;
                $data['message'] = lang("该优惠套装已经无效，建议您购买单个商品");
                $data['subtotal'] = 0;
                $model_cart->delCart('db',array('cart_id'=>$cart_id,'buyer_id'=>$memberId));
                exit(json_encode($return,true));
            }

            //如果有商品库存不足，更新购买数量到目前最大库存
            foreach ($cart_list[$key]['bl_goods_list'] as $goods_info) {
                if ($quantitys > $goods_info['goods_storage']) {
                    $data['error_code'] = 10019;
                    $data['message'] = lang("该优惠套装部分商品库存不足，<br/>建议您降低购买数量或购买库存足够的单个商品");
                    $data['goods_num'] = $goods_info['goods_storage'];
                    $data['goods_price'] = $cart_info['goods_price'];
                    $data['subtotal'] = $cart_info['goods_price'] * $quantitys;
                    $model_cart->editCart(array('goods_num'=>$goods_info['goods_storage']),array('cart_id'=>$cart_id,'buyer_id'=>$memberId));
                    exit(json_encode($return));
                    break;
                }
            }
        }

        if ($cart_info['sld_is_supplier']) {
            // 批发商品

            // 计算当前数量的单价
            $check_flag = true;
            $before_number = 0;
            $before_price = 0;
            $count_i = 0;
            $total_i = 0;
            $sld_ladder_price = unserialize($goods_info['sld_ladder_price']);
            ksort($sld_ladder_price);
            $total_number = is_array($quantity) ? array_sum($quantity) : $quantity;
            $total_i = count($sld_ladder_price);
            foreach ($sld_ladder_price as $k => $item) {
                if($check_flag){
                    if($before_number == 0 && $before_price == 0){
                        $now_price = $item*1;
                    }else{
                        if ($total_number*1 >= $k*1 && ($total_i-1) == $count_i) {
                            // 最后一个
                            $now_price = $item*1;
                            $check_flag = false;
                        }elseif($total_number*1 < $k*1 && $total_number*1>= $before_number*1){
                            $now_price = $before_price*1;
                            $check_flag = false;
                        }
                    }
                    $before_number = $k*1;
                    $before_price = $item*1;
                }
                $count_i++;
            }

            if (is_array($quantity)) {
                // 多规格 商品
                $data = array();
                $data['goods_num'] = $total_number;
                $data['goods_price'] = $now_price;
                $data['spec_num'] = serialize($quantity);
                $update = $model_cart->editCart($data,array('cart_id'=>$cart_id,'buyer_id'=>$_SESSION['member_id']));
                if ($update) {
                    $return = array();
                    $data['error_code'] = 200;
                    $data['subtotal'] = $now_price * $total_number;
                    $data['goods_price'] = $now_price;
                    $data['goods_num'] = $total_number;
                    $data['message'] = "修改成功";
                    $data['spec_num'] = serialize($quantity);
                } else {
                    $data['error_code'] = 10021;
                    $data['message'] = "修改失败";
                }
            }else{
                $data = array();
                $data['goods_num'] = $quantity;
                $data['goods_price'] = $now_price;
                $update = $model_cart->editCart($data,array('cart_id'=>$cart_id,'buyer_id'=>$_SESSION['member_id']));
                if ($update) {
                    $return = array();
                    $data['error_code'] = 200;
                    $data['subtotal'] = $now_price * $quantity;
                    $data['goods_price'] = $now_price;
                    $data['goods_num'] = $quantity;
                    $data['message'] = "修改成功";
                } else {
                    $data['error_code'] = 100210;
                    $data['message'] = "修改失败";
                }
            }
        }else{
            $data = array();
            $data['goods_num'] = $quantitys;
            $data['goods_price'] = $cart_info['goods_price'];
            $update = $model_cart->editCart($data,array('cart_id'=>$cartId,'buyer_id'=>$memberId));
            if ($update) {
                $return = array();
                $data['error_code'] = 200;
                $data['subtotal'] = $cart_info['goods_price'] * $quantitys;
                $data['goods_price'] = $cart_info['goods_price'];
                $data['goods_num'] = $quantitys;
            } else {
                $data['error_code'] = 10021;
                $data['message'] = "修改失败";
            }
        }
        exit(json_encode($data));
    }

    /**
     * 购物车删除单个商品，未登录前使用goods_id，此时cart_id可能为0，登录后使用cart_id
     */
    public function del() {
        if(!input("cart_id") || !input("is_supplier") || !input("member_id")|| !input("gid")|| !input("ismini")){
            $data['error_code']=10016;
            $data['message'] = "缺少参数";
            return json_encode($data);exit;
        }
        $memberId = input("member_id");
        $cart_id = intval(input("cart_id"));
        $gid = intval(input("gid"));
        $is_supplier = isset($_POST["is_supplier"]) ? intval(input("is_supplier")) : 0;
        $ismini = strip_tags(trim($_POST['ismini']));
        if($cart_id < 0 || $gid < 0) return ;
        $model_cart	= new UserCart();
        $data = array();
        $delete	= $model_cart->delCart('db',array('cart_id'=>$cart_id,'buyer_id'=>$memberId,'is_supplier'=>$is_supplier),['ismini'=>$ismini]);
            if($delete) {
                $data['error_code'] = 200;
                $data['quantity'] = $model_cart->cart_goods_num;
                $data['amount'] = $model_cart->cart_all_price;
            } else {
                $data['error_code'] = 10022;
                $data["message"] = "删除失败";
            }


        exit(json_encode($data,true));
    }
    /**
     * 加入购物车，登录后存入购物车表
     * 登录前，如果开启缓存，存入缓存，否则存入COOKIE，由于COOKIE长度限制，最多保存5个商品
     * 未登录不能将优惠套装商品加入购物车，登录前保存的信息以goods_id为下标
     *
     */
    public function add() {
        if(!input("quantity") || !input("member_id") ||!input("token")){
            $data['error_code']=10016;
            $data['message'] = "缺少参数";
            return json_encode($data);exit;
        }
        $token = input("token");
        $model_goods = new Goods();
        $model_cart = new UserCart();
        $goodsActivityModel = new GoodsActivity();
        if (is_numeric(input('gid'))) {
            //商品加入购物车(默认)
            $gid = intval(input('gid'));
            $quantity = intval(input('quantity'));

            if ($gid <= 0) return ;
            $goods_info	= $model_goods->getGoodsOnlineInfo(array('gid'=>$gid));
            //会员等级价格--start
            $goods_info = $goodsActivityModel->rebuild_goods_data($goods_info,'pc',['grade'=>1]);
            $ispresale = DB::name("pre_goods")->join("bbc_presale",'bbc_pre_goods.pre_id = bbc_presale.pre_id')->where("gid=$gid and pre_start_time<=".TIMESTAMP." and pre_end_time>=pre_end_time and pre_status=1")->find();
            if(!empty($ispresale)){
                $goods_info['goods_price'] = $ispresale['pre_sale_price'];
            }else
            $goods_info['goods_price'] = $goods_info['goods_price'];
            //会员等级价格--end

            //判断是不是在限时折扣中，如果是返回折扣信息
            $xianshi_info = $model_cart->getXianshiInfo($goods_info,$quantity);
            if (!empty($xianshi_info) && $goods_info['goods_price']>$xianshi_info['goods_price']) {
                $goods_info = $xianshi_info;
            }

            $this->_check_goods($goods_info,$quantity);

        } elseif (is_numeric(input("bl_id"))) {

            //优惠套装加入购物车(单套)

            $bl_id = intval(input("bl_id"));
            if ($bl_id <= 0) return ;
            $model_bl = new Pbundling();
            $bl_info = $model_bl->getBundlingInfo(array('bl_id'=>$bl_id));
            if (empty($bl_info) || $bl_info['bl_state'] == '0') {
                $data['error_code'] = 10022;
                $data['message'] = "该优惠套装已不存在，建议您单独购买";
            }

            //检查每个商品是否符合条件,并重新计算套装总价
            $bl_goods_list = $model_bl->getBundlingGoodsList(array('bl_id'=>$bl_id));
            $goods_id_array = array();
            $bl_amount = 0;
            foreach ($bl_goods_list as $goods) {
                $goods_id_array[] = $goods['gid'];
                $bl_amount += $goods['bl_goods_price'];
            }
            $model_goods = new Goods();
            $ids = "";
            foreach($goods_id_array as $k=>$v){
                $ids .=$v.",";
            }
            $ids = substr($ids,0,strlen($ids) -1);
            $goods_list = $model_goods->getGoodsOnlineList(['gid'=>array('IN',$ids)]);
            foreach ($goods_list as $goods) {
                $this->_check_goods($goods,1);
            }

            //优惠套装作为一条记录插入购物车，图片取套装内的第一个商品图
            $goods_info    = array();
            $goods_info['vid']	= $bl_info['vid'];
            $goods_info['gid']	= $goods_list[0]['gid'];
            $goods_info['goods_name'] = $bl_info['bl_name'];
            $goods_info['goods_price'] = $bl_amount;
            $goods_info['goods_num']   = 1;
            $goods_info['goods_image'] = $goods_list[0]['goods_image'];
            $goods_info['store_name'] = $bl_info['store_name'];
            $goods_info['bl_id'] = $bl_id;
            $quantity = 1;
        } elseif (is_numeric(input('supplier_goods_id'))) {
            // 批发商品
            $has_spec = intval(input('has_spec')) ? intval(input('has_spec')) : 0;
            $supplier_goods_id = intval(input('supplier_goods_id'));

            if ($supplier_goods_id <= 0) return ;
            $goods_info	= $model_goods->getGoodsOnlineInfo(array('gid'=>$supplier_goods_id));
            $goods_common_info = $model_goods->getGoodsCommonInfo(array('goods_commonid'=>$goods_info['goods_commonid']),array('goods_name'));
            // 更正商品名
            $goods_info['goods_name'] = $goods_common_info['goods_name'];

            $quantity = input('spec_quantities');

            $this->_check_goods($goods_info,$quantity);

        }

        //已登录状态，存入数据库,未登录时，优先存入缓存，否则存入COOKIE
        $save_type = 'db';
        $goods_info['buyer_id'] = input("member_id");

        $model_cart	= new UserCart();


        $insert = $model_cart->addCart($goods_info,$save_type,$quantity);
        if (!$insert['error'] && $insert) {
            //购物车商品种数记入cookie
            $data = array('state'=>'true', 'num' => $model_cart->cart_goods_num, 'amount' => sldPriceFormat($model_cart->cart_all_price),'goods_num'=>$quantity,'goods_price'=>$goods_info['goods_price'],'subtotal'=>$goods_info['goods_price']*$quantity);
            //加购统计记录
            $statModel = new Stats();
            $statModel->put_goods_stats(1,$goods_info['gid'],'cart',$token,$quantity,input("member_id"));
        } else {
            $data = array('state'=>'false','msg'=>$insert['error']);
        }
        exit(json_encode($data));
    }

    /**
     * 检查商品是否符合加入购物车条件
     * @param unknown $goods
     * @param number $quantity
     */
    private function _check_goods($goods_info, $quantity) {
        /*if(empty($quantity)) {
            exit(json_encode(array('msg'=>Language::get('参数错误','UTF-8'))));
        }
        if(empty($goods_info)) {
            exit(json_encode(array('msg'=>Language::get('该商品不存在','UTF-8'))));
        }
        if ($goods_info['vid'] == $_SESSION['vid']) {
            exit(json_encode(array('msg'=>Language::get('不能购买自己店铺的商品','UTF-8'))));
        }
        if(intval($goods_info['goods_storage']) < 1) {
            exit(json_encode(array('msg'=>Language::get('商品库存不足，提醒店家补货','UTF-8'))));
        }*/
        // 校验批发商品购买的数量是否超出了最大库存
        if (isset($goods_info['goods_type']) && $goods_info['goods_type']) {

            // 校验是否 满足最低数量
            $goods_buy_num = is_array($quantity) ? array_sum($quantity) : $quantity;
            $sld_ladder_price = unserialize($goods_info['sld_ladder_price']);
            $sld_ladder_numbers = array_keys($sld_ladder_price);
            $min_ladder_numbers = min($sld_ladder_numbers);

            if ($goods_buy_num < $min_ladder_numbers) {
                // 不满足 最低购买数量
                exit(json_encode(array('msg'=>Language::get('没满足批发的最低数量','UTF-8'))));
            }

            if (is_array($quantity)) {
                // 多规格 商品
                // 需要进行  多规格 分别的库存校验
                // 查询所有规格商品
                $goodsModel = new Goods();
                $spec_array = $goodsModel->getGoodsList(array('goods_commonid' => $goods_info['goods_commonid']), 'goods_spec,gid,vid,goods_image,color_id,goods_storage');
                if ($goods_info['goods_type'] == 1) {
                    $spec_list = array();       // 各规格商品地址，js使用
                    $spec_list_mobile = array();       // 各规格商品地址，js使用
                    $spec_image = array();      // 各规格商品主图，规格颜色图片使用
                    foreach ($spec_array as $key => $value) {
                        $s_array = unserialize($value['goods_spec']);
                        $tmp_array = array();
                        if (!empty($s_array) && is_array($s_array)) {
                            foreach ($s_array as $k => $v) {
                                $tmp_array[] = $k;
                            }
                        }
                        sort($tmp_array);
                        $spec_sign = implode('|', $tmp_array);

                        $spec_list[$spec_sign] = $value['goods_storage'];
                    }
                    foreach ($quantity as $key => $value) {
                        if(intval($spec_list[$key]) < $value) {
                            exit(json_encode(array('msg'=>Language::get('库存不足','UTF-8'))));
                        }
                    }
                }
            }else{
                // 无规格 商品
                if(intval($goods_info['goods_storage']) < $quantity) {
                    exit(json_encode(array('msg'=>Language::get('库存不足','UTF-8'))));
                }
            }
        }else{
            if(intval($goods_info['goods_storage']) < $quantity) {
                exit(json_encode(array('msg'=>lang('库存不足','UTF-8'))));
            }
        }
    }


}