<?php
namespace app\V1\model;

use think\Model;
use think\Db;

class VendorInfo extends Model
{
    /**
     * 删除缓存自营店铺的ID
     */
    public function dropCachedOwnShopIds() {
        $this->ownShopIds = null;
        dkcache('own_shop_ids');
    }
    /**
     * 获取自营店铺的ID
     *
     * @param boolean $bind_all_gc = false 是否只获取绑定全部类目的自营店 默认否（即全部自营店）
     * @return array
     */
    public function getOwnShopIds($bind_all_gc = false) {
        $data = $this->ownShopIds;
        // 属性为空则取缓存
        if (!$data) {
            //$data = rkcache('own_shop_ids');
            $data = H('own_shop_ids')? H('own_shop_ids'):H('own_shop_ids',true);
            // 缓存为空则查库
            if (!$data) {
                $data = array();
                $all_own_shops = $this->table('vendor')->field('vid,bind_all_gc')->where(array(
                    'is_own_shop' => 1,
                ))->select();
                foreach ((array) $all_own_shops as $v) {
                    $data[$v['vid']] = (int) (bool) $v['bind_all_gc'];
                }
                // 写入缓存
                //               wkcache('own_shop_ids', $data);
            }
            // 写入属性
            $this->ownShopIds = $data;
        }
        return array_keys($bind_all_gc ? array_filter($data) : $data);
    }

    /**
     * 查询店铺列表
     *
     * @param array $condition 查询条件
     * @param int $page 分页数
     * @param string $order 排序
     * @param string $field 字段
     * @param string $limit 取多少条
     * @return array
     */
    public function getStoreList($condition, $page = null, $order = 'store_time desc', $field = '*', $limit = '') {
        $result = $this->field($field)->where($condition)->order($order)->limit($limit)->page($page)->select();
        return $result;
    }

    /**
     * 查询有效店铺列表
     *
     * @param array $condition 查询条件
     * @param int $page 分页数
     * @param string $order 排序
     * @param string $field 字段
     * @return array
     */
    public function getStoreOnlineList($condition, $page = null, $order = '', $field = '*') {
        $condition['store_state'] = 1;
        return $this->getStoreList($condition, $page, $order, $field);
    }

    /**
     * 店铺数量
     * @param array $condition
     * @return int
     */
    public function getStoreCount($condition) {
        return $this->where($condition)->count();
    }

    /**
     * 按店铺编号查询店铺的开店信息
     *
     * @param array $storeid_array 店铺编号
     * @return array
     */
    public function getStoreMemberIDList($storeid_array) {
        //$store_list = DB::table('vendor')->where(array('vid'=> array('in', $storeid_array)))->field('vid,member_id,store_domain')->key('vid')->select();
        $ids = "" ;
        foreach($storeid_array as $k=>$v){
            $ids .=$v .",";
        }
        $ids = substr($ids,0,strlen($ids)-1);
        $store_list = DB::table('bbc_vendor')->where(array('vid'=> array('in', $ids)))->field('vid,member_id,store_domain')->select();
        return json_encode($store_list,true);
    }

    /**
     * 查询店铺信息
     *
     * @param array $condition 查询条件
     * @return array
     */
    public function getStoreInfo($condition) {
        $store_info = $this->where($condition)->find();
        $member_model = Model('member');
        if(!empty($store_info)) {
            if(!empty($store_info['store_presales'])){
                $store_info['store_presales'] = unserialize($store_info['store_presales']);
                foreach ($store_info['store_presales'] as $key => $val){
                    $member_info = $member_model -> getMemberInfoByID($val['num'],'member_name,member_avatar');
                    $store_info['store_presales'][$key]['member_name'] = $member_info['member_name'];
                    $store_info['store_presales'][$key]['member_avatar'] = $member_info['member_avatar']?UPLOAD_SITE_URL.DS.ATTACH_AVATAR.DS.$member_info['member_avatar']:UPLOAD_SITE_URL.'/'.ATTACH_COMMON.DS.C('default_user_portrait');
                }
            }
            if(!empty($store_info['store_aftersales'])) {
                $store_info['store_aftersales'] = unserialize($store_info['store_aftersales']);
                //获取店铺客服的用户名和头像
                if(count($store_info['store_aftersales'])>0) {
                    foreach ($store_info['store_aftersales'] as $key => $val) {
                        $member_info = $member_model->getMemberInfoByID($val['num'], 'member_name,member_avatar');
                        $store_info['store_aftersales'][$key]['member_name'] = $member_info['member_name'];
                        $store_info['store_aftersales'][$key]['member_avatar'] = $member_info['member_avatar'] ? UPLOAD_SITE_URL . DS . ATTACH_AVATAR . DS . $member_info['member_avatar'] : UPLOAD_SITE_URL . '/' . ATTACH_COMMON . DS . C('default_user_portrait');
                    }
                }
            }

            //商品数
            $model_goods = Model('goods');
            $store_info['goods_count'] = $model_goods->getGoodsOnlineCount(array('vid' => $store_info['vid']));

            //店铺评价
            $model_evaluate_store = Model('evaluate_store');
            $store_evaluate_info = $model_evaluate_store->getEvaluateStoreInfoByStoreID($store_info['vid'], $store_info['sc_id']);

            $store_info = array_merge($store_info, $store_evaluate_info);
        }

        //zz 加入店铺标签***************
        $model = Model('vendor_label');
        $label_name = $model->table('vendor_label')->field('label_name')->where(['id'=>$store_info['label_id']])->one();
        $store_info['label_name'] = $label_name;
        return $store_info;
    }

    /**
     * 通过店铺编号查询店铺信息
     *
     * @param int $vid 店铺编号
     * @return array
     */
    public function getStoreInfoByID($vid) {
        $store_info = rcache($vid, 'store_info');
        if(empty($store_info)) {
            $store_info = $this->getStoreInfo(array('vid' => $vid));
            wmemcache($vid, $store_info, 'store_info');
        }


        if(LANG_TYPE!='zh_cn'){
            foreach ($store_info['store_credit'] as $k=>$v){
                $store_info['store_credit'][$k]['text'] = Language::get($v['text']);
            }
        }

        return $store_info;
    }

    public function getStoreOnlineInfoByID($vid) {
        $store_info = $this->getStoreInfoByID($vid);
        if(empty($store_info) || $store_info['store_state'] == '0') {
            return null;
        } else {
            return $store_info;
        }
    }

    public function getStoreIDString($condition) {
        $condition['store_state'] = 1;
        $store_list = $this->getStoreList($condition);
        $store_id_string = '';
        foreach ($store_list as $value) {
            $store_id_string .= $value['vid'].',';
        }
        return $store_id_string;
    }

    /*
     * 添加店铺
     *
     * @param array $param 店铺信息
     * @return bool
     */
    public function addStore($param){
        return $this->insert($param);
    }

    /*
     * 编辑店铺
     *
     * @param array $update 更新信息
     * @param array $condition 条件
     * @return bool
     */
    public function editStore($update, $condition){
        //清空缓存
        $store_list = $this->getStoreList($condition);
        foreach ($store_list as $value) {
            wmemcache($value['vid'], array(), 'store_info');
        }

        return $this->where($condition)->update($update);
    }

    /*
     * 删除店铺
     *
     * @param array $condition 条件
     * @return bool
     */
    public function delStore($condition){
        $store_info = $this->getStoreInfo($condition);
        //删除店铺相关图片
        delete_file(BASE_UPLOAD_PATH.DS.ATTACH_STORE.DS.$store_info['store_label']);
        delete_file(BASE_UPLOAD_PATH.DS.ATTACH_STORE.DS.$store_info['store_banner']);
        if($store_info['store_slide'] != ''){
            foreach(explode(',', $store_info['store_slide']) as $val){
                delete_file(BASE_UPLOAD_PATH.DS.ATTACH_SLIDE.DS.$val);
            }
        }

        //清空缓存
        wmemcache($store_info['vid'], array(), 'store_info');

        return $this->where($condition)->delete();
    }

    /**
     * 获取商品销售排行
     *
     * @param int $vid 店铺编号
     * @param int $limit 数量
     * @return array	商品信息
     */
    public function getHotSalesList($vid, $limit = 5,$page=0) {
        $prefix = 'store_hot_sales_list_' . $limit;
        $hot_sales_list = array();
        if(empty($hot_sales_list)) {
            $model_goods = Model('goods');
            //虚拟销量
            if(C('virtual_sale')){
                $field = 'gid,goods_name,goods_image,goods_salenum,evaluation_good_star,evaluation_count,goods_price,goods_salenum+virtual_sale as goods_salenum';
                $order = 'goods_salenum+virtual_sale desc';
            }else{
                $field = 'gid,goods_name,goods_image,goods_salenum,evaluation_good_star,evaluation_count,goods_price';
                $order = 'goods_salenum desc';
            }
            $hot_sales_list = $model_goods->getGoodsListByCommonidDistinct(array('vid' => $vid,'goods_type' => 0),$field , $order,$limit);

            // 获取最终价格
            $hot_sales_list = Model('goods_activity')->rebuild_goods_data($hot_sales_list,'pc');

            wmemcache($vid, $hot_sales_list, $prefix);
        }
        return $hot_sales_list;
    }

    /**
     * 获取商品收藏排行
     *
     * @param int $vid 店铺编号
     * @param int $limit 数量
     * @return array	商品信息
     */
    public function getHotCollectList($vid, $limit = 5) {
        $prefix = 'store_collect_sales_list_' . $limit;
        $hot_collect_list = rcache($vid, $prefix);
        if(empty($hot_collect_list)) {
            $model_goods = Model('goods');
            $hot_collect_list = $model_goods->getGoodsOnlineList(array('vid' => $vid), '*', 0, 'goods_collect desc', $limit);

            // 获取最终价格
            $hot_collect_list = Model('goods_activity')->rebuild_goods_data($hot_collect_list,'pc');

            wmemcache($vid, $hot_collect_list, $prefix);
        }
        return $hot_collect_list;
    }

    /**
     * 获取店铺列表页附加信息
     *
     * @param array $store_array 店铺数组
     * @return array $store_array 包含近期销量和8个推荐商品的店铺数组
     */
    public function getStoreSearchList($store_array) {
        $store_array_new = array();
        if(!empty($store_array)){
            $model = Model();
            $no_cache_store = array();
            foreach ($store_array as $value) {
                //$store_search_info = rcache($value['vid'],'store_search_info');
                //print_r($store_array);exit();
                //if($store_search_info !== FALSE) {
                //	$store_array_new[$value['vid']] = $store_search_info;
                //} else {
                //	$no_cache_store[$value['vid']] = $value;
                //}
                $no_cache_store[$value['vid']] = $value;
            }
            if(!empty($no_cache_store)) {
                //获取店铺商品数
                $no_cache_store = $this->getStoreInfoBasic($no_cache_store);
                //获取店铺近期销量
                $no_cache_store = $this->getGoodsCountJq($no_cache_store);
                //获取店铺推荐商品
                $no_cache_store = $this->getGoodsListBySales($no_cache_store);
                //写入缓存
                foreach ($no_cache_store as $value) {
                    wcache($value['vid'],$value,'store_search_info');
                }
                $store_array_new = array_merge($store_array_new,$no_cache_store);
            }
        }
        return $store_array_new;
    }
    /**
     * 获得店铺标志、信用、商品数量、店铺评分等信息
     *
     * @param	array $param 店铺数组
     * @return	array 数组格式的返回结果
     */
    public function getStoreInfoBasic($list,$day = 0){
        $list_new = array();
        if (!empty($list) && is_array($list)){
            foreach ($list as $key=>$value) {
                if(!empty($value)) {
                    $value['store_logo'] = getStoreLogo($value['store_logo']);
                    //店铺评价
                    $model_evaluate_store = Model('evaluate_store');
                    $store_evaluate_info = $model_evaluate_store->getEvaluateStoreInfoByStoreID($value['vid'], $value['sc_id']);
                    $value = array_merge($value, $store_evaluate_info);
                    if(!empty($value['store_presales'])) $value['store_presales'] = unserialize($value['store_presales']);
                    if(!empty($value['store_aftersales'])) $value['store_aftersales'] = unserialize($value['store_aftersales']);
                    $list_new[$value['vid']] = $value;
                    $list_new[$value['vid']]['goods_count'] = 0;
                }
            }
            //全部商品数直接读取缓存
            if($day > 0) {
                $store_id_string = implode(',',array_keys($list_new));
                //指定天数直接查询数据库
                $condition = array();
                $condition['goods_show'] = '1';
                $condition['vid'] = array('in',$store_id_string);
                $condition['goods_add_time'] = array('gt',strtotime("-{$day} day"));
                $model = Model();
                $goods_count_array = $model->table('goods')->field('vid,count(*) as goods_count')->where($condition)->group('vid')->select();
                if (!empty($goods_count_array)){
                    foreach ($goods_count_array as $value){
                        $list_new[$value['vid']]['goods_count'] = $value['goods_count'];
                    }
                }
            } else {
                $list_new = $this->getGoodsCountByStoreArray($list_new);
            }
        }
        return $list_new;
    }
    /**
     * 获取店铺商品数
     *
     * @param array $store_array 店铺数组
     * @return array $store_array 包含商品数goods_count的店铺数组
     */
    public function getGoodsCountByStoreArray($store_array) {
        $store_array_new = array();
        $model = Model();
        $no_cache_store = '';
        foreach ($store_array as $value) {
            $goods_count = rcache($value['vid'],'store_goods_count');
            if(!empty($goods_count)&&$goods_count !== FALSE) {
                //有缓存的直接赋值
                $value['goods_count'] = $goods_count;
            } else {
                //没有缓存记录store_id，统计从数据库读取
                $no_cache_store .= $value['vid'].',';
                $value['goods_count'] = '0';
            }
            $store_array_new[$value['vid']] = $value;
        }
        if(!empty($no_cache_store)) {
            //从数据库读取店铺商品数赋值并缓存
            $no_cache_store = rtrim($no_cache_store,',');
            $condition = array();
            $condition['goods_state'] = '1';
            $condition['vid'] = array('in',$no_cache_store);
            $goods_count_array = $model->table('goods')->field('vid,count(*) as goods_count')->where($condition)->group('vid')->select();
            if (!empty($goods_count_array)){
                foreach ($goods_count_array as $value){
                    $store_array_new[$value['vid']]['goods_count'] = $value['goods_count'];
                    wcache($value['vid'],$value['goods_count'],'store_goods_count');
                }
            }
        }
        return $store_array_new;
    }
    //获取近期销量
    private function getGoodsCountJq($store_array) {
        $model = Model();
        $order_count_array = $model->table('order')->field('vid,count(*) as order_count')->where(array('vid'=>array('in',implode(',',array_keys($store_array))),'add_time'=>array('gt',TIMESTAMP-3600*24*90)))->group('vid')->select();
        foreach ((array)$order_count_array as $value) {
            $store_array[$value['vid']]['num_sales_jq'] = $value['order_count'];
        }
        return $store_array;
    }
    //获取店铺8个销量最高商品
    private function getGoodsListBySales($store_array) {
        $model = Model();
        $field = 'gid,vid,goods_name,goods_image,goods_price,goods_salenum';
        //虚拟销量
        if(C('virtual_sale')){
            $field .= ',goods_salenum+virtual_sale as goods_salenum';
            $order = 'goods_salenum+virtual_sale desc';
        }else{
            $order = 'goods_salenum desc';
        }
        foreach ($store_array as $value) {
            if ($value['sld_is_supplier'] == 1) {
                $field .= ',goods_type';
            }
            $search_list_goods = $model->table('goods')->field($field)->where(array('vid'=>$value['vid'],'goods_state'=>1))->order($order)->limit(4)->select();

            // 获取最终价格
            $search_list_goods = Model('goods_activity')->rebuild_goods_data($search_list_goods,'pc');

            $store_array[$value['vid']]['search_list_goods'] = $search_list_goods;
        }
        return $store_array;
    }
    public function add_rebate_data($param){
        return $this->table('rebate_temp')->insert($param);
    }
    public function get_rebate_data($condition){//获得返利
        return $this->table('rebate_temp')->where($condition)->select();
    }
    public function get_rebate_single($condition){//获得返利单条
        return $this->table('rebate_temp')->where($condition)->find();
    }
    public function edit_rebate_data($condition,$data){
        return $this->table('rebate_temp')->where($condition)->update($data);
    }
    public function del_rebate_data($condition){//删除返利
        return $this->table('rebate_temp')->where($condition)->delete();
    }
    public function getStoreName($vid){
        $condition['vid']=$vid;
        return $this->table('vendor')->field('store_name')->where($condition)->find();

    }
    /*
     * 根据用户id查找店铺名字
     */
    public function getStoreNameBymid($member_id){
        $condition['member_id']=$member_id;
        return $this->table('vendor')->field('store_name')->where($condition)->find();

    }
    //
    public function getStoreDongTaiNum($vid=null){
        $condition=array();
        $condition['strace_storeid']=$vid;
        $condition['strace_state']=1;
        //        $Num['goodsNum']=$this->table('favorites')->field('count(*) as count')->where(array('member_id'=>$member_id,'fav_type'=>'goods'))->find();
        return $this->table('store_sns_tracelog')->field('strace_id')->where($condition)->group('strace_time')->select();

    }
    /*
    * 店铺首页二维码
    */
    public function QRCode($data)
    {
        $vid = intval($data['vid'])?:$_SESSION['vid'];
        // 生成店铺首页二维码
        if(!$vid){
            return false;
        }
        require_once(BASE_STATIC_PATH.DS.'phpqrcode'.DS.'index.php');
        $PhpQRCode = new PhpQRCode();
        $dir = BASE_UPLOAD_PATH.DS.'shop/vendorqr'.DS;
        $PhpQRCode->set('is_qiniu_or_oss',0);
        $PhpQRCode->set('matrixPointSize',8);
        $PhpQRCode->set('pngTempDir',BASE_UPLOAD_PATH.DS.'shop/vendorqr'.DS);
        $PhpQRCode->set('date',C('main_url').DS.'cwap/cwap_go_shop.html?vid='.$vid);
        $filename = $vid . '.png';
        $PhpQRCode->set('pngTempName', $vid . '.png');
        $res = $PhpQRCode->init();
        if(!$res){
            return false;
        }

        $logo = UPLOAD_SITE_URL.'/'.ATTACH_STORE.'/'.$data['store_label'];
        $QR = $dir.$filename;
        //生成二维码图片
        if (@fopen( $logo, 'r' )) {
            $QR = imagecreatefromstring(file_get_contents($QR));
            $logo = imagecreatefromstring(file_get_contents($logo));
//            header("Content-type: image/png");
            $logo_width = imagesx($logo);//logo图片宽度
            $logo_height = imagesy($logo);//logo图片高度
            $im = @imagecreatetruecolor($logo_width+200, $logo_height+200);
            $background = imagecolorallocate($im, 255, 255, 255);
            imagefill($im,0,0,$background);
            imagecopy($im, $logo,100,100,0,0,$logo_width,$logo_height);
            $logo = $im;
//            imagepng ($im);
//            imagedestroy($im);
//            die;



            $QR_width = imagesx($QR);//二维码图片宽度
            $QR_height = imagesy($QR);//二维码图片高度
            $logo_width = imagesx($logo);//logo图片宽度
            $logo_height = imagesy($logo);//logo图片高度
            $logo_qr_width = $QR_width / 3;
            $scale = $logo_width/$logo_qr_width;
            $logo_qr_height = $logo_height/$scale;
            $from_width = ($QR_width - $logo_qr_width) / 2;

            //重新组合图片并调整大小
            imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,
                $logo_qr_height, $logo_width, $logo_height);
        }
        //输出图片  带logo图片
        imagepng($QR, $dir.$filename);

//        if(QINIU_ENABLE){
//            $re = qiniu_uploaded_file('data/upload/shop/vendorqr/'.$filename,$dir.$filename);
//        }else if(OSS_ENABLE){
//            $re = new_uploaded_file('data/upload/shop/vendorqr/'.$filename,$dir.$filename);
//        }
        return $res;
    }
}