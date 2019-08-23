<?php
namespace app\V1\controller;
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
        $data['normal_has_count'] = $normal_has_count;
        // 批发中心购物车是否有数据
        $supplier_has_count = $model_cart->checkCart(array('buyer_id'=>$member_id,'sld_is_supplier'=>1));
        $data['supplier_has_count'] = $supplier_has_count;
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
                if($cart['promotion_type'] == 'tuan'){
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
            //$free_freight_list = $model_cart->getFreeFreightActiveList(array_keys($store_cart_list));
            //$data['free_freight_list'] = $free_freight_list;
        }
        return json_encode($data,true);
    }
}