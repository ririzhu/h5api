<?php
namespace app\v1\model;

use think\Db;
use think\Model;

class UserCart extends Model
{
    /**
     * 购物车商品总金额
     */
    private $cart_all_price = 0;

    /**
     * 购物车商品总数
     */
    private $cart_goods_num = 0;

    public function __construct() {
        parent::__construct('cart');
    }

    /**
     * 取属性值魔术方法
     *
     * @param string $name
     */
    public function __get($name) {
        return $this->$name;
    }

    /**
     * 检查购物车内商品是否存在
     *
     * @param
     */
    public function checkCart($condition = array()) {
        //return DB::table("bbc_cart")->where($condition)->find();
        return DB::table("bbc_cart")->where($condition)->select();
    }

    /**
     * 取得 单条购物车信息
     * @param unknown $condition
     * @param string $field
     */
    public function getCartInfo($condition = array(), $field = '*') {
        return DB::table("bbc_cart")->field($field)->where($condition)->find();
    }

    /**
     * 将商品添加到购物车中
     *
     * @param array	$data	商品数据信息
     * @param string $save_type 保存类型，可选值 db,cookie,cache
     * @param int $quantity 购物数量
     */
    public function addCart($data = array(), $save_type = '', $quantity = null) {
        $method = '_addCart'.ucfirst($save_type);
        $insert = $this->$method($data,$quantity);
        //更改购物车总商品数和总金额，传递数组参数只是给DB使用
        $this->getCartNum($save_type,array('buyer_id'=>$data['buyer_id']));
        return $insert;
    }

    /**
     * 添加数据库购物车
     *
     * @param unknown_type $goods_info
     * @param unknown_type $quantity
     * @return unknown
     */
    private function _addCartDb($goods_info = array(),$quantity) {
        //验证购物车商品是否已经存在
        $condition = array();
	    $condition['gid'] = $goods_info['gid'];
        //$condition['goods_commonid'] = $goods_info['goods_commonid'];
        $condition['buyer_id'] = $goods_info['buyer_id'];
        if (isset($goods_info['bl_id'])) {
            $condition['bl_id'] = $goods_info['bl_id'];
        } else {
            $condition['bl_id'] = 0;
        }


        // 阶梯价格
        $now_price = 0;
        if (isset($goods_info['sld_ladder_price'])) {
            $sld_ladder_price = unserialize($goods_info['sld_ladder_price']);
            ksort($sld_ladder_price);
            // 计算单价
            $check_flag = true;
            $before_number = 0;
            $before_price = 0;
            $count_i = 0;
            $total_i = 0;
            $total_number = (is_array($quantity) && !empty($quantity)) ? array_sum($quantity) : $quantity;
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

        }


        $check_cart	= $this->checkCart($condition);


        if (!empty($check_cart)){

            if (isset($check_cart['sld_is_supplier'])) {
                $old_spec_num = unserialize($check_cart['spec_num']);
                $old_num=$check_cart['goods_num'];
                $new_num = $old_num+array_sum($quantity);
                $new_spec_num = array_merge_recursive($quantity,$old_spec_num);
                foreach ($new_spec_num as $key => $value) {
                    if (is_array($value)) {
                        $new_spec_num[$key] = array_sum($value);
                    }
                }
                // 计算单价
                $check_flag = true;
                $before_number = 0;
                $before_price = 0;
                $count_i = 0;
                $total_i = 0;
                $total_number = $new_num;
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
                            }elseif ($total_number*1 < $k*1 && $total_number*1>= $before_number*1){
                                $now_price = $before_price*1;
                                $check_flag = false;
                            }
                        }
                        $before_number = $k*1;
                        $before_price = $item*1;
                    }
                    $count_i++;
                }
                $new_spec_num = serialize($new_spec_num);
                return $this->editCart(array('goods_num'=>$new_num,'goods_price'=>$now_price,'spec_num'=>$new_spec_num),array('buyer_id'=>$goods_info['buyer_id'],'gid'=>$goods_info['gid']));
            }else{
                if(isset($check_cart['goods_num']))
                $old_num=$check_cart['goods_num'];
//                if($goods_info['course_type']==2){
//                    $new_num=1;
//                }else{
//                    $new_num=$old_num+$quantity;
//                }

                $new_num=1;

                //执行修改购物车数量操作
                //检测购物车是否超过库存
                $goods_storage = db::table('bbc_goods')->where(['gid'=>$goods_info['gid']])->field('goods_storage')->find();


                if($goods_storage['goods_storage'] < $new_num){
                    return ['error'=>'超过商品库存'];
                }

                //执行修改购物车列表的数量操作
                if(isset($goods_info['goods_commonid'])){
                    return $this->editCart(array('goods_num'=>$new_num,'goods_price'=>$goods_info['goods_price'],'gid'=>$goods_info['gid']),array('buyer_id'=>$goods_info['buyer_id'],'goods_commonid'=>$goods_info['goods_commonid']));
                }else {
                    return $this->editCart(array('goods_num' => $new_num, 'goods_price' => $goods_info['goods_price'], 'gid' => $goods_info['gid']), array('buyer_id' => $goods_info['buyer_id']));
                }
            }
        }

        $array    = array();
        $array['buyer_id']	= $goods_info['buyer_id'];
        $array['vid']	= $goods_info['vid'];
        $array['gid']	= $goods_info['gid'];
        $array['goods_name'] = $goods_info['goods_name'];
        if(isset($goods_info['sld_ladder_price']))
        $array['goods_price'] = $goods_info['sld_ladder_price'] ? $now_price : $goods_info['goods_price'];
        else
            $array['goods_price'] = $now_price ? $now_price : $goods_info['goods_price'];
        $array['goods_num']   = (is_array($quantity) && !empty($quantity)) ? array_sum($quantity) : $quantity ;
        $array['goods_image'] = $goods_info['goods_image'];
        $array['store_name'] = $goods_info['store_name'];
        $array['sld_is_supplier'] = (isset($goods_info['goods_type']) && $goods_info['goods_type'] == 1) ? 1 : 0;
        if(isset($goods_info['goods_commonid']))
        $array['goods_commonid'] = $goods_info['goods_commonid'];
        else
            $array['goods_commonid'] = 0;
        // 批发商品处理
        if(isset($goods_info['goods_spec']))
        $goods_spec = unserialize($goods_info['goods_spec']) ? unserialize($goods_info['goods_spec']) : array();
        else
            $goods_spec = array();
        if(is_array($goods_spec) && !empty($goods_spec)){
            $array['has_spec'] =1;
        }else{
            $array['has_spec'] = 0;
        }
        $array['spec_num'] = (is_array($quantity) && !empty($quantity)) ? serialize($quantity) : '' ;
        $array['bl_id'] = isset($goods_info['bl_id']) ? $goods_info['bl_id'] : 0;
        return DB::table("bbc_cart")->insert($array);
    }

    /**
     * 添加到缓存购物车
     *
     * @param unknown_type $goods_info
     * @param unknown_type $quantity
     * @return unknown
     */
    private function _addCartCache($goods_info = array(), $quantity = null) {
        $obj_cache = Cache::getInstance(C('cache.type'));
        $cart_array = $obj_cache->get($_COOKIE['PHPSESSID'],'cart_');
        $cart_array = @unserialize($cart_array);
        $cart_array = !is_array($cart_array) ? array() : $cart_array;
        if (count($cart_array) >= 5) return true;
        if (in_array($goods_info['gid'],array_keys($cart_array))) return true;

        // 阶梯价格
        $now_price = 0;
        if ($goods_info['sld_ladder_price']) {
            $sld_ladder_price = unserialize($goods_info['sld_ladder_price']);
            ksort($sld_ladder_price);
            // 计算单价
            $check_flag = true;
            $before_number = 0;
            $before_price = 0;
            $count_i = 0;
            $total_i = 0;
            $total_number = (is_array($quantity) && !empty($quantity)) ? array_sum($quantity) : $quantity;
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
                    $before_number = k*1;
                    $before_price = item*1;
                }
                $count_i++;
            }

        }

        $cart_array[$goods_info['gid']] = array(
            'vid' => $goods_info['vid'],
            'gid' => $goods_info['gid'],
            'goods_name' => $goods_info['goods_name'],
            'goods_price' => $goods_info['sld_ladder_price'] ? $now_price : $goods_info['goods_price'],
            'goods_image' => $goods_info['goods_image'],
            'goods_num' => (is_array($quantity) && !empty($quantity)) ? array_sum($quantity) : $quantity
        );
        $obj_cache->set($_COOKIE['PHPSESSID'], serialize($cart_array), 'cart_', 24*3600);
        return true;
    }

    /**
     * 添加到cookie购物车,最多保存5个商品
     *
     * @param unknown_type $goods_info
     * @param unknown_type $quantity
     * @return unknown
     */
    private function _addCartCookie($goods_info = array(), $quantity = null) {
        //去除斜杠
        $cart_str = get_magic_quotes_gpc() ? stripslashes(cookie('cart')) : cookie('cart');
        $cart_str = base64_decode(decrypt($cart_str));
        $cart_array = @unserialize($cart_str);
        $cart_array = !is_array($cart_array) ? array() : $cart_array;
        if (count($cart_array) >= 5) return false;

        if (in_array($goods_info['gid'],array_keys($cart_array))) return true;

        // 阶梯价格
        $now_price = 0;
        if ($goods_info['sld_ladder_price']) {
            $sld_ladder_price = unserialize($goods_info['sld_ladder_price']);
            ksort($sld_ladder_price);
            // 计算单价
            $check_flag = true;
            $before_number = 0;
            $before_price = 0;
            $count_i = 0;
            $total_i = 0;
            $total_number = (is_array($quantity) && !empty($quantity)) ? array_sum($quantity) : $quantity;
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
                    $before_number = k*1;
                    $before_price = item*1;
                }
                $count_i++;
            }

        }

        $cart_array[$goods_info['gid']] = array(
            'vid' => $goods_info['vid'],
            'gid' => $goods_info['gid'],
            'goods_name' => $goods_info['goods_name'],
            'goods_price' => $goods_info['sld_ladder_price'] ? $now_price : $goods_info['goods_price'],
            'goods_image' => $goods_info['goods_image'],
            'goods_num' => (is_array($quantity) && !empty($quantity)) ? array_sum($quantity) : $quantity
        );
        setBbcCookie('cart',encrypt(base64_encode(serialize($cart_array))),24*3600);
        return true;
    }

    /**
     * 更新购物车
     *
     * @param	array	$param 商品信息
     */
    public function editCart($data,$condition) {
        $result	= DB::table("bbc_cart")->where($condition)->update($data);
        if ($result) {
            $this->getCartNum('db',array('buyer_id'=>$condition['buyer_id']));
        }
        return $result;
    }

    /**
     * 购物车列表
     *
     * @param string $type 存储类型 db,cache,cookie
     * @param unknown_type $condition
     */
    public function listCart($type, $condition = array()) {
        if ($type == 'db') {
            $cart_list = DB::table("bbc_cart")->where($condition)->order('vid,cart_id desc')->select();
        }
        // $cart_list = is_array($cart_list) ? $cart_list : array();
        //顺便设置购物车商品数和总金额
        $this->cart_goods_num =  count($cart_list);
        $cart_all_price = 0;
        if(is_array($cart_list)) {
            foreach ($cart_list as $val) {
                $cart_all_price	+= $val['goods_price'] * $val['goods_num'];
            }
        }
        $this->cart_all_price = sldPriceFormat($cart_all_price);
        return $cart_list;
    }

    /**
     * 删除购物车商品
     *
     * @param string $type 存储类型 db,cache,cookie
     * @param unknown_type $condition
     * @param array $extend 额外参数
     * return array
     */
    public function delCart($type, $condition = array(),$extend=[]) {
        $condition['sld_is_supplier'] = isset($condition['is_supplier']) ? $condition['is_supplier'] : 0;
        unset($condition['is_supplier']);
        if ($type == 'db') {
            $result =  DB::table("bbc_cart")->where($condition)->delete();
        } elseif ($type == 'cache') {
            $obj_cache = Cache::getInstance(Config('cache.type'));
            $cart_array = $obj_cache->get($_COOKIE['PHPSESSID'],'cart_');
            $cart_array = @unserialize($cart_array);
            if (!is_array($cart_array)) return true;
            if (key_exists($condition['gid'],$cart_array)) {
                unset($cart_array[$condition['gid']]);
                $obj_cache = Cache::getInstance(Config('cache.type'));
                $obj_cache->set($_COOKIE['PHPSESSID'], serialize($cart_array), 'cart_', 24*3600);
                $result = true;
            }
        } elseif ($type == 'cookie') {
            $cart_str = get_magic_quotes_gpc() ? stripslashes(cookie('cart')) : cookie('cart');
            $cart_str = base64_decode(decrypt($cart_str));
            $cart_array = @unserialize($cart_str);
            if (key_exists($condition['gid'],(array)$cart_array)) {
                unset($cart_array[$condition['gid']]);
            }
            $result = true;
        }
        //重新计算购物车商品数和总金额
        //if (count($result)>0) {
        if ($result>0) {
            if($extend['ismini'] == 'mini'){
                $this->getCartNum($type,array('buyer_id'=>$condition['buyer_id']));
            }else{
                $this->getCartNum($type,array('buyer_id'=>$condition['buyer_id'],'sld_is_supplier'=>$condition['sld_is_supplier']));
            }
        }
        return $result;
    }

    /**
     * 清空购物车
     *
     * @param string $type 存储类型 db,cache,cookie
     * @param unknown_type $condition
     */
    public function clearCart($type, $condition = array()) {
        if ($type == 'cache') {
            $obj_cache = Cache::getInstance(C('cache.type'));
            $obj_cache->rm($_COOKIE['PHPSESSID'],'cart_');
        } elseif ($type == 'cookie') {
            setBbcCookie('cart','',-3600);
        } else if ($type == 'db') {
            //数据库暂无浅清空操作
        }
    }

    /**
     * 计算购物车总商品数和总金额
     * @param string $type 购物车信息保存类型 db,cookie,cache
     * @param array $condition 只有登录后操作购物车表时才会用到该参数
     */
    public function getCartNum($type, $condition = array()) {
        if ($type == 'db') {
            $cart_all_price = 0;
            $cart_goods	= $this->listCart('db',$condition);
            $this->cart_goods_num = count($cart_goods);
            if(!empty($cart_goods) && is_array($cart_goods)) {
                foreach ($cart_goods as $val) {
                    $cart_all_price	+= $val['goods_price'] * $val['goods_num'];
                }
            }
            $this->cart_all_price = sldPriceFormat($cart_all_price);

        } elseif ($type == 'cache') {
            $obj_cache = Cache::getInstance(Config('cache.type'));
            $cart_array = $obj_cache->get($_COOKIE['PHPSESSID'],'cart_');
            $cart_array = @unserialize($cart_array);
            $cart_array = !is_array($cart_array) ? array() : $cart_array;
            $this->cart_goods_num = count($cart_array);
            $cart_all_price = 0;
            if (!empty($cart_array)){
                foreach ($cart_array as $v){
                    $cart_all_price += floatval($v['goods_price'])*intval($v['goods_num']);
                }
            }
            $this->cart_all_price = $cart_all_price;

        } elseif ($type == 'cookie') {
            $cart_str = get_magic_quotes_gpc() ? stripslashes(cookie('cart')) : cookie('cart');
            $cart_str = base64_decode(decrypt($cart_str));
            $cart_array = @unserialize($cart_str);
            $cart_array = !is_array($cart_array) ? array() : $cart_array;
            $this->cart_goods_num = count($cart_array);
            $cart_all_price = 0;
            foreach ($cart_array as $v){
                $cart_all_price += floatval($v['goods_price'])*intval($v['goods_num']);
            }
            $this->cart_all_price = $cart_all_price;
        }
        return $this->cart_goods_num;
    }

    /**
     * 直接购买/加入购物车时，判断商品是不是限时折扣中，如果购买数量若>=规定的下限，按折扣价格计算,否则按原价计算
     * @param unknown $buy_goods_list
     * @param number $quantity 购买数量
     * @return array,如果该商品未正在进行限时折扣，返回空数组
     */
    public function getXianshiInfo($buy_goods_info, $quantity) {
        if (!Config('promotion_allow') || empty($buy_goods_info) || !is_array($buy_goods_info)) return $buy_goods_info;
        //定义返回数组
        $PModel = new Pxianshigoods();
        $xianshi_info = $PModel->getXianshiGoodsInfoByGoodsID($buy_goods_info['gid']);
        if (!empty($xianshi_info)) {
            if ($quantity >= $xianshi_info['lower_limit']) {
                $buy_goods_info['goods_price'] = $xianshi_info['xianshi_price'];
                $buy_goods_info['promotions_id'] = $xianshi_info['xianshi_id'];
                $buy_goods_info['ifxianshi'] = true;
            }
        }
        return $buy_goods_info;
    }

    /**
     * 直接购买时，判断商品是不是正在团购中，如果是，按团购价格计算，购买数量若超过团购规定的上限，则按团购上限计算
     * @param unknown $buy_goods_info
     * @return array,如果该商品未正在进行团购，返回空数组
     */
    public function getTuanInfo($buy_goods_info = array()) {
//	    print_r($buy_goods_info);die;
        if (!Config('tuan_allow') || empty($buy_goods_info) || !is_array($buy_goods_info)) return $buy_goods_info;
        $tuan = new Tuan();
        $tuan_info = $tuan->getTuanInfoByGoodsCommonID($buy_goods_info['goods_commonid']);
//        print_r($tuan_info);die;
        if (!empty($tuan_info)) {
            $buy_goods_info['goods_price'] = $tuan_info['tuan_price'];
            if ($tuan_info['upper_limit'] && $buy_goods_info['goods_num'] > $tuan_info['upper_limit']) {
                $buy_goods_info['goods_num'] = $tuan_info['upper_limit'];
            }
            $buy_goods_info['promotions_id'] = $buy_goods_info['tuan_id'] = $tuan_info['tuan_id'];
            $buy_goods_info['iftuan'] = true;
        }

        return $buy_goods_info;
    }

    /**
     * 直接购买时返回最新的在售商品信息（需要在售）
     *
     * @param int $gid 所购商品ID
     * @param int $quantity 购买数量
     * @return array
     */
    public function getGoodsOnlineInfo($gid,$quantity) {
        //取目前在售商品
        $goods = new Goods();
        $goods_info = $goods->getGoodsOnlineInfo(array('gid'=>$gid));

        if($goods_info['is_free']){
            $goods_info['goods_price'] = 0;
        }

        if(empty($goods_info)){
            return null;
        }
        $new_array = array();
        $new_array['goods_num'] = $quantity;
        $new_array['gid'] = $gid;
        $new_array['goods_commonid'] = $goods_info['goods_commonid'];
        $new_array['gc_id'] = $goods_info['gc_id'];
        $new_array['gc_id_1'] = $goods_info['gc_id_1'];
        $new_array['vid'] = $goods_info['vid'];
        $new_array['goods_name'] = $goods_info['goods_name'];
        $new_array['goods_price'] = $goods_info['goods_price'];
        $new_array['store_name'] = $goods_info['store_name'];
        $new_array['goods_image'] = $goods_info['goods_image'];
        $new_array['transport_id'] = $goods_info['transport_id'];
        $new_array['goods_freight'] = $goods_info['goods_freight'];
        $new_array['goods_vat'] = $goods_info['goods_vat'];
        $new_array['goods_storage'] = $goods_info['goods_storage'];
        $new_array['fenxiao_yongjin'] = $goods_info['fenxiao_yongjin'];
        $new_array['goods_spec'] = $goods_info['goods_spec'];
        $new_array['state'] = true;
        $new_array['is_free'] = $goods_info['is_free'];
        $new_array['course_type'] = $goods_info['course_type'];
        $new_array['storage_state'] = intval($goods_info['goods_storage']) < intval($quantity) ? false : true;

        //填充必要下标，方便后面统一使用购物车方法与模板
        //cart_id=gid,优惠套装目前只能进购物车,不能立即购买
        $new_array['cart_id'] = $gid;
        $new_array['bl_id'] = 0;
        return $new_array;
    }

    /**
     * 直接批发时返回最新的在售商品信息（需要在售）
     *
     * @param int $gid 所购商品ID
     * @param int $quantity 购买数量
     * @return array
     */
    public function getGoodsSupplierOnlineInfo($gid,$quantity) {
        //取目前在售商品
        $goods_info = Model('goods')->getGoodsOnlineInfo(array('gid'=>$gid));
        if(empty($goods_info)){
            return null;
        }
        // 有规格 (获取规格信息)
        $spec_array = Model('goods')->getGoodsList(array('goods_commonid' => $goods_info['goods_commonid']), 'goods_spec,gid,vid,goods_image,color_id,goods_storage');
        $spec_list = array();       // 各规格商品地址，js使用
        if (is_array($spec_array) && !empty($spec_array)) {
            foreach ($spec_array as $s_key => $value) {
                if ($value['goods_spec'] && $value['goods_spec'] != 'N;') {
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
                    $spec_list[$spec_sign]['gid'] = $value['gid'];
                    $spec_list[$spec_sign]['field_name'] = implode('/', $s_array);
                }
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
            $spec_num_string_arr = array();
            foreach ($quantity as $key => $value) {
                $spec_num_string_arr[] = $key.'|<=>|'.$value;
            }
            $spec_num_string = implode('[<=>]',$spec_num_string_arr);
        }
        $new_array = array();
        $new_array['goods_num'] = is_array($quantity) ? array_sum($quantity) : $quantity;
        $new_array['gid'] = $gid;
        $new_array['goods_commonid'] = $goods_info['goods_commonid'];
        $new_array['gc_id'] = $goods_info['gc_id'];
        $new_array['gc_id_1'] = $goods_info['gc_id_1'];
        $new_array['vid'] = $goods_info['vid'];
        $new_array['goods_name'] = $goods_info['goods_name'];
        $new_array['goods_type'] = $goods_info['goods_type'];
        $new_array['goods_price'] = $now_price;
        $new_array['store_name'] = $goods_info['store_name'];
        $new_array['goods_image'] = $goods_info['goods_image'];
        $new_array['transport_id'] = $goods_info['transport_id'];
        $new_array['goods_freight'] = $goods_info['goods_freight'];
        $new_array['goods_vat'] = $goods_info['goods_vat'];
        $new_array['goods_storage'] = $goods_info['goods_storage'];
        $new_array['state'] = true;
        $new_array['has_spec'] = is_array($quantity) ? 1 : 0;
        $new_array['spec_num'] = is_array($quantity) ? serialize($quantity) : '';
        $new_array['spec_num_arr'] = is_array($quantity) ? $quantity : '';
        $new_array['spec_num_string'] = is_array($quantity) ? $spec_num_string : $quantity;
        $new_array['sld_is_supplier'] = 1;
        $new_array['sld_ladder_price'] = $goods_info['sld_ladder_price'];
        $new_array['spec_data'] = $spec_list;
        if (is_array($quantity)) {
            $has_storage_error = false;
            foreach ($quantity as $key => $value) {
                if(intval($spec_list[$key]['storage']) < $value) {
                    $has_storage_error = true;
                    break;
                }
            }
            $new_array['storage_state'] = $has_storage_error ? false : true;
        }else{
            $new_array['storage_state'] = intval($goods_info['goods_storage']) < intval($quantity) ? false : true;
        }

        //填充必要下标，方便后面统一使用购物车方法与模板
        //cart_id=gid,优惠套装目前只能进购物车,不能立即购买
        $new_array['cart_id'] = $gid;
        $new_array['bl_id'] = 0;
        return $new_array;
    }

    /**
     * 取商品最新的在售信息
     * @param unknown $cart_list
     * @return array
     */
    public function getOnlineCartList($cart_list) {
        if (empty($cart_list) || !is_array($cart_list)) return $cart_list;
        //验证商品是否有效
        $goods_id_array = array();
        $ids = '';
        foreach ($cart_list as $key => $cart_info) {
            if (!intval($cart_info['bl_id'])) {
                $goods_id_array[] = $cart_info['gid'];
                $ids .= $cart_info['gid'] .",";
            }
        }
        $ids = substr($ids,0,strlen($ids)-1);
        $model_goods = new Goods();
        $goods_online_list = $model_goods->getGoodsOnlineList(array('gid'=>array("in",$ids)));
        //会员等级价格--start
        $goodsActivity = new GoodsActivity();
        $goods_online_list = $goodsActivity->rebuild_goods_data($goods_online_list,'',['grade'=>1]);
        //会员等级价格--end
        $goods_online_array = array();
        foreach ($goods_online_list as $goods) {
            $goods_online_array[$goods['gid']] = $goods;
        }
        foreach ((array)$cart_list as $key => $cart_info) {
            if (intval($cart_info['bl_id'])) continue;
            $cart_list[$key]['state'] = true;
            $cart_list[$key]['storage_state'] = true;
            if (in_array($cart_info['gid'],array_keys($goods_online_array))) {
                $goods_online_info = $goods_online_array[$cart_info['gid']];
                $cart_list[$key]['goods_name'] = $goods_online_info['goods_name'];
                $cart_list[$key]['gc_id'] = $goods_online_info['gc_id'];
                $cart_list[$key]['gc_id_1'] = $goods_online_info['gc_id_1'];
                $cart_list[$key]['goods_commonid'] = $goods_online_info['goods_commonid'];
                $cart_list[$key]['goods_image'] = $goods_online_info['goods_image'];
                $cart_list[$key]['goods_price'] = $goods_online_info['show_price'];
                $cart_list[$key]['transport_id'] = $goods_online_info['transport_id'];
                $cart_list[$key]['goods_freight'] = $goods_online_info['goods_freight'];
                $cart_list[$key]['fenxiao_yongjin'] = $goods_online_info['fenxiao_yongjin'];
                $cart_list[$key]['goods_vat'] = $goods_online_info['goods_vat'];
                $cart_list[$key]['goods_storage'] = $goods_online_info['goods_storage'];
                $cart_list[$key]['sld_ladder_price'] = $goods_online_info['sld_ladder_price'];
                $cart_list[$key]['goods_type'] = $goods_online_info['goods_type'];
                $cart_list[$key]['goods_spec'] = $goods_online_info['goods_spec'];
                $cart_list[$key]['is_free'] = $goods_online_info['is_free'];
                $cart_list[$key]['course_type'] = $goods_online_info['course_type'];
                //会员等级折扣
                if(isset($goods_online_info['grade_discount'])) {
                    $cart_list[$key]['grade_discount'] = $goods_online_info['grade_discount'];
                }
                $cart_list[$key]['goods_storage_alarm'] = $goods_online_info['goods_storage_alarm'];
                // 批发商品 多规格 处理
                if ($goods_online_info['goods_type'] == 1) {
                    if ($goods_online_info['sld_ladder_price']) {
                        // 批发商品
                        // 计算当前数量的单价
                        $check_flag = true;
                        $before_number = 0;
                        $before_price = 0;
                        $count_i = 0;
                        $sld_ladder_price = unserialize($goods_online_info['sld_ladder_price']);
                        ksort($sld_ladder_price);
                        $total_number = $cart_list[$key]['goods_num'];
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
                        $cart_list[$key]['goods_price'] = sldPriceFormat($now_price);
                    }
                    if ($goods_online_info['goods_spec'] != 'N;') {
                        // 多规格 校验库存
                        $supplier_goods_storage = 0;
                        // 有规格 (获取规格信息)
                        $goodsModel = new Goods();
                        $spec_array = $goodsModel>getGoodsList(array('goods_commonid' => $goods_online_info['goods_commonid']), 'goods_spec,gid,vid,goods_image,color_id,goods_storage,goods_storage_alarm');
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
                            $spec_list[$spec_sign]['storage_alarm'] = $value['goods_storage_alarm'];
                            $spec_list[$spec_sign]['gid'] = $value['gid'];

                            $supplier_goods_storage += $value['goods_storage'];
                        }

                        $cart_list[$key]['spec_data'] = $spec_list;

                        if ($cart_info['goods_num'] > $supplier_goods_storage) {
                            $cart_list[$key]['storage_state'] = false;
                        }
                    }else{
                        if ($cart_info['goods_num'] > $goods_online_info['goods_storage']) {
                            $cart_list[$key]['storage_state'] = false;
                        }
                    }
                }else{

                    //普通商品

                    if ($goods_online_info['goods_spec'] != 'N;') {
                        // 多规格 校验库存
                        $supplier_goods_storage = 0;
                        // 有规格 (获取规格信息)
                        $goodsModel = new Goods();
                        $spec_array = $goodsModel->getGoodsList(array('goods_commonid' => $goods_online_info['goods_commonid']), 'goods_spec,gid,vid,goods_image,color_id,goods_storage,goods_storage_alarm');
                        $spec_list = array();       // 各规格商品地址，js使用

                        //当前规格
                        $this_spec = $goods_online_info['goods_spec'];
                        $this_spec = unserialize($this_spec);

                        foreach ($this_spec as $v){
                            $this_spec = $v;
                            break;
                        }



                        foreach ($spec_array as $s_key => $value) {
                            $s_array = unserialize($value['goods_spec']);

                            $s_value = '';
                            $tmp_array = array();
                            if (!empty($s_array) && is_array($s_array)) {
                                foreach ($s_array as $k => $v) {
                                    $tmp_array[] = $k;
                                    $s_value = $v;
                                }
                            }
                            sort($tmp_array);
                            $spec_sign = implode('|', $tmp_array);

                            $spec_list[$spec_sign]['storage'] = $value['goods_storage'];
                            $spec_list[$spec_sign]['storage_alarm'] = $value['goods_storage_alarm'];
                            $spec_list[$spec_sign]['gid'] = $value['gid'];
                            $spec_list[$spec_sign]['s_value'] = $s_value;
                            if($this_spec==$s_value){
                                $spec_list[$spec_sign]['current'] = 1;
                            }

                            $supplier_goods_storage += $value['goods_storage'];
                        }

                        $cart_list[$key]['spec_data'] = $spec_list;

                        if ($cart_info['goods_num'] > $supplier_goods_storage) {
                            $cart_list[$key]['storage_state'] = false;
                        }
                    }


                    if ($cart_info['goods_num'] > $goods_online_info['goods_storage']) {
                        $cart_list[$key]['storage_state'] = false;
                    }
                }
            } else {
                //如果商品下架
                $cart_list[$key]['state'] = false;
                $cart_list[$key]['storage_state'] = false;
            }
        }

        return $cart_list;
    }

    /**
     * 批量判断购物车内的商品是不是在团购中，如果购买数量若>=规定的下限，按折扣价格计算,否则按原价计算_zhangjinfeng
     * 并标识该商品为团购商品
     * @param unknown $cart_list
     * @return array
     */
    public function getTuanCartList($cart_list) {
        if (!Config('promotion_allow') || empty($cart_list) || !is_array($cart_list)) return $cart_list;
        //团购
        if (Config('tuan_allow')) {
            foreach ($cart_list as $key => $cart_info) {
                $tuan_info = Model('tuan')->getTuanInfoByGoodsID_new($cart_info['gid']);
                if (!empty($tuan_info)) {
                    $cart_list[$key]['promotion_type'] = 'tuan';
                    $cart_list[$key]['promotion_price'] = $tuan_info['tuan_price'];
                    $cart_list[$key]['down_price'] = sldPriceFormat($cart_info['goods_price'] - $tuan_info['tuan_price']);
                    $cart_list[$key]['upper_limit'] = $tuan_info['upper_limit'];
                    /*数量不满足的话，则按照普通价格走，否则按照团购价*/
                    if($cart_info['goods_num']>$tuan_info['upper_limit']||$cart_info['goods_num']==$tuan_info['upper_limit']){
                        $cart_list[$key]['goods_price'] = $cart_info['goods_price'];
                    }
                }
            }
        }
        return $cart_list;
    }



    /**
     * 批量判断购物车内的商品是不是限时折扣中，如果购买数量若>=规定的下限，按折扣价格计算,否则按原价计算
     * 并标识该商品为限时商品
     * @param unknown $cart_list
     * @return array
     */
    public function getXianshiCartList($cart_list) {
        if (!Config('promotion_allow') || empty($cart_list) || !is_array($cart_list)) return $cart_list;
        $model_xianshi = new Pxianshigoods();
        $model_goods = new Goods();
        foreach ($cart_list as $key => $cart_info) {
            if (intval($cart_info['bl_id'])) continue;
            //如果该商品参与了别的促销活动，则不进行限时折扣的判断
            if(isset($cart_info['promotion_price']))continue;
            $xianshi_info = $model_xianshi->getXianshiGoodsInfoByGoodsID($cart_info['gid']);
            if (!empty($xianshi_info)) {
                if ($cart_info['goods_num'] >= $xianshi_info['lower_limit']) {
                    $cart_list[$key]['goods_price'] = $xianshi_info['xianshi_price'];
                    $cart_list[$key]['promotions_id'] = $xianshi_info['xianshi_id'];
                    $cart_list[$key]['promotion_type'] = 'xianshi';
                    $cart_list[$key]['promotion_price'] = $xianshi_info['xianshi_price'];
                    $cart_list[$key]['ifxianshi'] = true;
                }
                $cart_list[$key]['xianshi_info']['lower_limit'] = $xianshi_info['lower_limit'];
                $cart_list[$key]['xianshi_info']['xianshi_price'] = $xianshi_info['xianshi_price'];
                $cart_list[$key]['xianshi_info']['down_price'] = sldPriceFormat($cart_info['goods_price'] - $xianshi_info['xianshi_price']);
            }
        }
        return $cart_list;
    }




    /**
     * 批量判断购物车内的商品是不是在今日抢购中_张金凤（new）
     * @param unknown $cart_list
     * @return array
     */
    public function getTobuyCartList($cart_list) {
        if ( empty($cart_list) || !is_array($cart_list)) return $cart_list;
        $tobuy_detail_model = Model('today_buy_detail');
        $tobuy_time_model = Model('today_buy');
        foreach ($cart_list as $key => $cart_info) {
            if (intval($cart_info['bl_id'])) continue;
            //如果该商品参与了别的促销活动，则不进行近日抢购的判断
            if(isset($cart_info['promotion_price']))continue;
            $tobuy_detail_info = $tobuy_detail_model->getList(array('item_id' => $cart_info['gid'], 'today_buy_detail_state' => "1",'today_buy_date'=>"date('Y-m-d')"));
            if (!empty($tobuy_detail_info)) {
                $today_buy_time_id = $tobuy_detail_info[0]['today_buy_time_id'];

                $tobuy_time_info = $tobuy_time_model->getOneById_time($today_buy_time_id);
                if ($tobuy_time_info && $tobuy_time_info['today_buy_time_state'] == 1) {
                    $today_buy_time = $tobuy_time_info['today_buy_time'];
                    $today_date = date("Y-m-d");
                    $time = $today_date." ". $today_buy_time;
                    $tobuy_time = strtotime($time);
                    //当前时间大于时间点的情况下才可以算作参与活动了
                    if (time() - $tobuy_time > 0 || time() - $tobuy_time == 0) {
                        $cart_list[$key]['tobuy_info']['tobuy_price'] = $tobuy_detail_info[0]['today_buy_price'];
                        $cart_list[$key]['goods_price'] = $tobuy_detail_info[0]['today_buy_price'];
                        $cart_list[$key]['promotion_type'] = 'tobuy';
                        $cart_list[$key]['promotion_price'] = $tobuy_detail_info[0]['today_buy_price'];
                        $cart_list[$key]['down_price'] = sldPriceFormat($cart_info['goods_price'] - $tobuy_detail_info[0]['today_buy_price']);
                    }
                }
            }
        }
        return $cart_list;
    }



    /**
     * 取得购买车内组合销售信息以及包含的商品及有效状态
     * @param unknown $cart_list
     * @return array
     */
    public function getBundlingCartList($cart_list) {
        if (!Config('promotion_allow') || empty($cart_list) || !is_array($cart_list)) return $cart_list;
        $model_bl = new Pbundling();
        $model_goods = new Goods();
        foreach ($cart_list as $key => $cart_info) {
            if (!intval($cart_info['bl_id'])) continue;
            $cart_list[$key]['state'] = true;
            $cart_list[$key]['storage_state'] = true;
            $bl_info = $model_bl->getBundlingInfo(array('bl_id'=>$cart_info['bl_id']));

            //标志优惠套装是否处于有效状态
            if (empty($bl_info) || !intval($bl_info['bl_state'])) {
                $cart_list[$key]['state'] = false;
            }

            //取得优惠套装商品列表
            $cart_list[$key]['bl_goods_list'] = $model_bl->getBundlingGoodsList(array('bl_id'=>$cart_info['bl_id']));

            //取最新在售商品信息
            $goods_id_array = array();
            $ids = "";
            foreach ($cart_list[$key]['bl_goods_list'] as $goods_info) {
                $goods_id_array[] = $goods_info['gid'];
                $ids .=$goods_info['gid'].",";
            }
            $ids = substr($ids,0,strlen($ids) - 1);
            $goods_list = $model_goods->getGoodsOnlineList(array('gid'=>array("in",$ids)));
            $goods_online_list = array();
            foreach ($goods_list as $goods_info) {
                $goods_online_list[$goods_info['gid']] = $goods_info;
            }
            unset($goods_list);

            //使用最新的商品名称、图片,如果一旦有商品下架，则整个套装置置为无效状态
            foreach ($cart_list[$key]['bl_goods_list'] as $k => $goods_info) {
                if (array_key_exists($goods_info['gid'],$goods_online_list)) {
                    $goods_online_info = $goods_online_list[$goods_info['gid']];
                    //如果库存不足，标识false
                    if ($cart_info['goods_num'] > $goods_online_info['goods_storage']) {
                        $cart_list[$key]['storage_state'] = false;
                    }
                    $cart_list[$key]['bl_goods_list'][$k]['gc_id'] = $goods_online_info['gc_id'];
                    $cart_list[$key]['bl_goods_list'][$k]['goods_name'] = $goods_online_info['goods_name'];
                    $cart_list[$key]['bl_goods_list'][$k]['goods_image'] = $goods_online_info['goods_image'];
                    $cart_list[$key]['bl_goods_list'][$k]['goods_storage'] = $goods_online_info['goods_storage'];
                } else {
                    //商品已经下架
                    $cart_list[$key]['state'] = false;
                    $cart_list[$key]['storage_state'] = false;
                }
            }
        }
        return $cart_list;
    }

    /**
     * 从购物车数组中得到商品列表
     * @param unknown $cart_list
     */
    public function getGoodsList($cart_list) {
        if (empty($cart_list) || !is_array($cart_list)) return $cart_list;
        $goods_list = array();
        $i = 0;
        foreach ($cart_list as $key => $cart) {
            //if (!$cart['state'] || !$cart['storage_state']) continue;
            //购买数量
            $quantity = $cart['goods_num'];
            if (!intval($cart['bl_id'])) {
                //如果是普通商品
                $goods_list[$i]['goods_num'] = $quantity;
                $goods_list[$i]['gid'] = $cart['gid'];
                $goods_list[$i]['vid'] = $cart['vid'];
                if(isset($cart['gc_id']))
                $goods_list[$i]['gc_id'] = $cart['gc_id'];
                $goods_list[$i]['goods_name'] = $cart['goods_name'];
                $ispresale = DB::name("pre_goods")->join("bbc_presale",'bbc_pre_goods.pre_id = bbc_presale.pre_id')->where("gid=".$cart["gid"]." and pre_start_time<=".TIMESTAMP." and pre_end_time>=pre_end_time and pre_status=1")->find();
                if(!empty($ispresale)){
                    $goods_info['goods_price'] = $ispresale['pre_sale_price'];
                }else{
                    $goods_info['goods_price'] = $cart['goods_price'];
                }

                $goods_list[$i]['store_name'] = $cart['store_name'];
                $goods_list[$i]['goods_image'] = $cart['goods_image'];
                if(isset($cart['transport_id']))
                $goods_list[$i]['transport_id'] = $cart['transport_id'];
                if(isset($cart['goods_freight']))
                $goods_list[$i]['goods_freight'] = $cart['goods_freight'];
                if(isset($cart['goods_vat']))
                $goods_list[$i]['goods_vat'] = $cart['goods_vat'];
                $goods_list[$i]['bl_id'] = 0;
                $i++;
            } else {
                //如果是优惠套装商品
                foreach ($cart['bl_goods_list'] as $bl_goods) {
                    $goods_list[$i]['goods_num'] = $quantity;
                    $goods_list[$i]['gid'] = $bl_goods['gid'];
                    $goods_list[$i]['vid'] = $cart['vid'];
                    if(isset($bl_goods['gc_id']))
                    $goods_list[$i]['gc_id'] = $bl_goods['gc_id'];
                    $goods_list[$i]['goods_name'] = $bl_goods['goods_name'];
                    if(isset($bl_goods['goods_price']))
                    $goods_list[$i]['goods_price'] = $bl_goods['goods_price'];
                    if(isset($bl_goods['store_name']))
                    $goods_list[$i]['store_name'] = $bl_goods['store_name'];
                    $goods_list[$i]['goods_image'] = $bl_goods['goods_image'];
                    if(isset($bl_goods['transport_id']))
                    $goods_list[$i]['transport_id'] = $bl_goods['transport_id'];
                    if(isset($bl_goods['goods_freight']))
                    $goods_list[$i]['goods_freight'] = $bl_goods['goods_freight'];
                    if(isset($bl_goods['goods_vat']))
                    $goods_list[$i]['goods_vat'] = $bl_goods['goods_vat'];
                    $goods_list[$i]['bl_id'] = $cart['bl_id'];
                    $i++;
                }
            }
        }
        return $goods_list;
    }

    /**
     * 将下单商品列表转换为以店铺ID为下标的数组
     *
     * @param array $cart_list
     * @return array
     */
    public function getStoreCartList($cart_list) {
        if (empty($cart_list) || !is_array($cart_list)) return $cart_list;
        $new_array = array();
        foreach ($cart_list as $cart) {
            $new_array[$cart['vid']][] = $cart;
        }
        return $new_array;
    }

    /**
     * 商品金额计算(分别对每个商品/优惠套装小计、每个店铺小计)
     * @param unknown $store_cart_list 以店铺ID分组的购物车商品信息
     * @return array
     */
    public function calcCartList($store_cart_list,$member_id) {
        if (empty($store_cart_list) || !is_array($store_cart_list)) return array($store_cart_list,array(),0);


        //如果该商品有首单优惠
        $firstModel = new FirstOrder();
        if($_store_cart_list = $firstModel->handle_buy_list($store_cart_list,$member_id)){
            $store_cart_list = $_store_cart_list;
        }


        //存放每个店铺的商品总金额
        $store_goods_total = array();
        //存放本次下单所有店铺商品总金额
        $order_goods_total = 0;
        $store_goods_total =array();
        $store_goods_total[1]=0;//print_r($store_cart_list);die;
        foreach ($store_cart_list as $vid => $store_cart) {
            ;$tmp_amount = 0;$store_goods_total[$vid] = 0;
            foreach ($store_cart as $key => $cart_info) {
                $store_cart[$key]['goods_total'] = isset($cart_info['show_price']) ? sldPriceFormat($cart_info['show_price'] * $cart_info['goods_num']) : sldPriceFormat($cart_info['goods_price'] * $cart_info['goods_num']);
                if(isset($cart_info['first']) && $cart_info['first']>0){
                    $tmp_amount+=$cart_info['first'];
                }
                $store_cart[$key]['goods_image_url'] = cthumb($store_cart[$key]['goods_image']);
                $tmp_amount += $store_cart[$key]['goods_total'];
            }
            $store_cart_list[$vid] = $store_cart;
            $store_goods_total[$vid] += sldPriceFormat($tmp_amount);

        }

        return array($store_cart_list,$store_goods_total);
    }

    /**
     * 取得店铺级活动 - 每个店铺可用的满即送活动规则列表
     * @param unknown $store_id_array 店铺ID数组
     */
    public function getMansongRuleList($store_id_array) {
        if (!Config('promotion_allow') || empty($store_id_array) || !is_array($store_id_array)) return array();
        $model_mansong = new Favorable();
        $mansong_rule_list = array();

        foreach ($store_id_array as $vid) {
            $store_mansong_rule = $model_mansong->getMansongInfoByStoreID($vid);
            if (!empty($store_mansong_rule['rules']) && is_array($store_mansong_rule['rules'])) {
                foreach ($store_mansong_rule['rules'] as $rule_info) {
                    //如果减金额 或 有赠品(在售且有库存)
                    if (!empty($rule_info['discount']) || (!empty($rule_info['mansong_goods_name']) && !empty($rule_info['goods_storage']))) {
                        $mansong_rule_list[$vid][] = $this->_parseMansongRuleDesc($rule_info);
                    }
                }
            }
        }
        return $mansong_rule_list;
    }

    /**
     * 取得店铺级优惠 - 跟据商品金额返回每个店铺当前符合的一条活动规则，如果有赠品，则自动追加到购买列表，价格为0
     * @param unknown $store_goods_total 每个店铺的商品金额小计，以店铺ID为下标
     * @return array($premiums_list,$mansong_rule_list) 分别为赠品列表[下标自增]，店铺满送规则列表[店铺ID为下标]
     */
    public function getMansongRuleCartListByTotal($store_goods_total) {
        if (!Config('promotion_allow') || empty($store_goods_total) || !is_array($store_goods_total)) return array(array(),array());

        $model_mansong = new Favorable();
        $model_goods = new Goods();

        //定义赠品数组，下标为店铺ID
        $premiums_list = array();
        //定义满送活动数组，下标为店铺ID
        $mansong_rule_list = array();

        foreach ($store_goods_total as $vid => $goods_total) {
            $rule_info = $model_mansong->getMansongRuleByStoreID($vid,$goods_total);
            if (is_array($rule_info) && !empty($rule_info)) {
                //即不减金额，也找不到促销商品时(已下架),此规则无效
                if (empty($rule_info['discount']) && empty($rule_info['mansong_goods_name'])) {
                    continue;
                }
                $rule_info['desc'] = $this->_parseMansongRuleDesc($rule_info);
                $rule_info['discount'] = sldPriceFormat($rule_info['discount']);
                $mansong_rule_list[$vid] = $rule_info;
                //如果赠品在售,有库存,则追加到购买列表
                if (!empty($rule_info['mansong_goods_name']) && !empty($rule_info['goods_storage'])) {
                    $data = array();
                    $data['gid'] = $rule_info['gid'];
                    $data['goods_name'] = $rule_info['mansong_goods_name'];
                    $data['goods_num'] = 1;
                    $data['goods_price'] = 0.00;
                    $data['goods_image'] = $rule_info['goods_image'];
                    $data['goods_image_url'] = cthumb($rule_info['goods_image']);
                    $data['goods_storage'] = $rule_info['goods_storage'];
                    $premiums_list[$vid][] = $data;
                }
            }
        }
        return array($premiums_list,$mansong_rule_list);
    }

    /**
     * 拼装单条满即送规则页面描述信息
     * @param array $rule_info 满即送单条规则信息
     * @return string
     */
    function _parseMansongRuleDesc($rule_info) {
        if (empty($rule_info) || !is_array($rule_info)) return;
        $discount_desc = !empty($rule_info['discount']) ? '减'.$rule_info['discount'] : '';
        $goods_desc = (!empty($rule_info['mansong_goods_name']) && !empty($rule_info['goods_storage'])) ?
            " 送<a href='".urlShop('goods','index',array('gid'=>$rule_info['gid']))."' title='{$rule_info['mansong_goods_name']}' target='_blank'>[赠品]</a>" : '';
        return sprintf('满%s%s%s',$rule_info['price'],$discount_desc,$goods_desc);
    }

    /**
     * 重新计算每个店铺最终商品总金额(最初计算金额减去各种优惠/加运费)
     * @param array $store_goods_total 店铺商品总金额
     * @param array $preferential_array 店铺优惠活动内容
     * @param string $preferential_type 优惠类型，目前只有一个 'mansong'
     * @return array 返回扣除优惠后的店铺商品总金额
     */
    public function reCalcGoodsTotal($store_goods_total, $preferential_array, $preferential_type) {
        $deny = empty($store_goods_total) || !is_array($store_goods_total) || empty($preferential_array) || !is_array($preferential_array);
        if ($deny) return $store_goods_total;

        switch ($preferential_type) {
            case 'mansong':
                if (!Config('promotion_allow')) return $store_goods_total;
                foreach ($preferential_array as $vid => $rule_info) {
                    if (is_array($rule_info) && $rule_info['discount'] > 0) {
                        $store_goods_total[$vid] -= $rule_info['discount'];
                    }
                }
                break;

            case 'voucher':
                if (!C('voucher_allow')) return $store_goods_total;
                foreach ($preferential_array as $vid => $voucher_info) {
                    $store_goods_total[$vid] -= $voucher_info['quan_price'];
                }
                break;

            case 'freight':
                foreach ($preferential_array as $vid => $freight_total) {
                    $store_goods_total[$vid] += $freight_total;
                }
                break;
        }
        return $store_goods_total;
    }

    /**
     * 取得哪些店铺有满免运费活动
     * @param array $store_id_array 店铺ID数组
     * @return array
     */
    public function getFreeFreightActiveList($store_id_array) {
        if (empty($store_id_array) || !is_array($store_id_array)) return array();

        //定义返回数组
        $store_free_freight_active = array();

        //如果商品金额未达到免运费设置下线，则需要计算运费
        $ids = "";
        foreach ($store_id_array as $k=>$v)
        {
            $ids .= $v .",";
        }
        $ids = substr($ids,0,strlen($ids)-1);
        $condition['vid'] = array('in',$ids);
        $vendorModel = new VendorInfo();
        $store_list = $vendorModel->getStoreOnlineList($condition,null,'','vid,store_free_price');
        foreach ($store_list as $store_info) {
            $limit_price = floatval($store_info['store_free_price']);
            if ($limit_price > 0) {
                $store_free_freight_active[$store_info['vid']] = sprintf('满%s免运费',$limit_price);
            }
        }
        return $store_free_freight_active;
    }

    /**
     * 验证传过来的优惠券是否可用有效，如果无效，直接删除
     * @param array $input_voucher_list 优惠券列表
     * @param array $store_goods_total (店铺ID=>商品总金额)
     * @return array
     */
    public function reParseVoucherList($input_voucher_list = array(), $store_goods_total = array(), $member_id) {
        if (empty($input_voucher_list) || !is_array($input_voucher_list)) return array();
        $store_voucher_list = $this->getStoreAvailableVoucherList($store_goods_total, $member_id);
        foreach ($input_voucher_list as $vid => $voucher) {
            $tmp = $store_voucher_list[$vid];
            if (is_array($tmp) && isset($tmp[$voucher['voucher_t_id']])) {
                $input_voucher_list[$vid]['voucher_id'] = $tmp[$voucher['voucher_t_id']]['voucher_id'];
                $input_voucher_list[$vid]['voucher_code'] = $tmp[$voucher['voucher_t_id']]['voucher_code'];
            } else {
                unset($input_voucher_list[$vid]);
            }
        }
        return $input_voucher_list;
    }

    /**
     * 取得店铺可用的优惠券
     * @param array $store_goods_total array(店铺ID=>商品总金额)
     * @return array
     */
    public function getStoreAvailableVoucherList($store_goods_total, $member_id) {
        if (!C('voucher_allow')) return $store_goods_total;
        $voucher_list = array();
        $model_voucher = Model('quan');
        foreach ($store_goods_total as $vid => $goods_total) {
            $condition = array();
            $condition['voucher_vid'] = $vid;
            $condition['voucher_owner_id'] = $member_id;
            $voucher_list[$vid] = $model_voucher->getCurrentAvailableVoucher($condition,$goods_total);
        }
        return $voucher_list;
    }
    //根据用户id获取购物车数量(该方法 只有cmobile 使用)
    public function getCartNums($member_id=null){
        // 过滤掉批发商品
        $num=$this->table('cart')->field('sum(goods_num) as num')->where(array('buyer_id'=>$member_id,'sld_is_supplier'=>0))->find();
        if(!$num['num']){
            $num['num']=0;
        }
        return $num;

    }

    /**
     * 批量判断购物车内的商品是不是手机专享中
     * 直接修改价格
     * @param array $cart_list
     */
    public function getZhuanxiangCartList(& $cart_list) {

        foreach ($cart_list as $key => $cart_info) {
            //判断是不是在手机专享，如果是返回折扣信息
            $mbuy = new MBuy();
            $solegoods_info = $mbuy->table('bbc_p_mbuy_goods')->where(['gid'=>$cart_info['gid']])->find();
            if (!empty($solegoods_info)) {
                $cart_list[$key]['goods_price'] = $solegoods_info['mbuy_price'];
            }
        }

        return $cart_list;
    }
}