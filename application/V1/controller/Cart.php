<?php
namespace app\V1\controller;
use app\V1\model\GoodsActivity;
use app\V1\model\Pbundling;
use app\V1\model\UserCart;
use app\V1\model\Goods;
use app\V1\model\VendorInfo;
class Cart extends Base
{
    /**
     * 购物车首页
     */
    public function index() {
        $model_cart	= new UserCart();
        if(!$this->request->isPost()){
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
                $data['message'] = "商品已经下架";
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
                            $data['message'] = "没满足批发的最低数量";
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
                        $data['message'] = "没满足批发的最低数量";
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
            $cart_list[$key]['bl_goods_list'] = $model_goods->getGoodsOnlineList(array('gid'=>array(in,$goods_id_array)));

            //如果其中有商品下架，删除
            if (count($cart_list[$key]['bl_goods_list']) != count($goods_id_array)) {
                $data['error_code'] = 10020;
                $data['message'] = "该优惠套装已经无效，建议您购买单个商品";
                $data['subtotal'] = 0;
                $model_cart->delCart('db',array('cart_id'=>$cart_id,'buyer_id'=>$memberId));
                exit(json_encode($return));
            }

            //如果有商品库存不足，更新购买数量到目前最大库存
            foreach ($cart_list[$key]['bl_goods_list'] as $goods_info) {
                if ($quantitys > $goods_info['goods_storage']) {
                    $data['error_code'] = 10019;
                    $data['message'] = "该优惠套装部分商品库存不足，<br/>建议您降低购买数量或购买库存足够的单个商品";
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
}