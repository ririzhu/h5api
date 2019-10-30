<?php
namespace app\v1\controller;

use app\v1\model\Favorites;
use app\v1\model\GoodsActivity;
use app\v1\model\VendorInfo;

class Vendor extends Base
{
    protected $store_info;
    public function __construct() {
        parent::__construct();
    }
    /**
     * 店铺首页
     */
    public function venderInfo(){
        $vid = input("vid");
        $memberId = input("member_id",0);
        $condition = array();
        $condition['vid'] = $vid;
        $model_store = new VendorInfo();
        $store_info = $model_store->getStoreOnlineInfoByID($vid);
        $store_info['store_label']="http://www.horizou.cn/data/upload/mall/common/06249945949889035.png";
        $data['store_info'] = $store_info;
        $model_goods = new \app\v1\model\Goods(); // 字段
        $fieldstr = "gid,goods_commonid,goods_name,goods_jingle,vid,store_name,goods_price,goods_marketprice,goods_storage,goods_image,goods_freight,goods_salenum,color_id,evaluation_good_star,evaluation_count";
        //得到最新12个商品列表
        $new_goods_list = $model_goods->getGoodsListByCommonidDistinct($condition, $fieldstr, 'gid desc', 0,12);
        //print_r($new_goods_list);die;
//		判断商品是否收藏
        $favorite_model = new Favorites();
        foreach ($new_goods_list as $key => $value) {
            $favorite_info = $favorite_model->getOneFavorites(array('fav_id'=>"$value[gid]",'fav_type'=>'goods','member_id'=>"$memberId"));
            if(empty($favorite_info)){
                $favorites_flag = 0;
            }else{
                $favorites_flag = 1;
            }
            $new_goods_list[$key]['favorites_flag'] = $favorites_flag;
        }
        // 商品多图
        if (!empty($new_goods_list)) {
            $goodsid_array = array();       // 商品id数组
            $commonid_array = array(); // 商品公共id数组
            $storeid_array = array();       // 店铺id数组
            foreach ($new_goods_list as $value) {
                $goodsid_array[] = $value['gid'];
                $commonid_array[] = $value['goods_commonid'];
                $storeid_array[] = $value['vid'];
            }
            $goodsid_array = array_unique($goodsid_array);
            $commonid_array = array_unique($commonid_array);
            $storeid_array = array_unique($storeid_array);
        }
        // 商品多图
        $goodsimage_more = $model_goods->getGoodsImageList('goods_commonid in('.arrayToString($commonid_array).')');
        foreach ($new_goods_list as $key => $value) {
            // 商品多图
            foreach ($goodsimage_more as $v) {
                if ($value['goods_commonid'] == $v['goods_commonid'] && $value['vid'] == $v['vid'] && $value['color_id'] == $v['color_id']) {
                    $new_goods_list[$key]['image'][] = $v;
                }
            }
        }
        $data['new_goods_list'] = $new_goods_list;
        //Template::output('new_goods_list',$new_goods_list);

        $condition['goods_commend'] = 1;
        //得到12个推荐商品列表
        $recommended_goods_list = $model_goods->getGoodsListByColorDistinct($condition, $fieldstr, 'gid desc', 12);
        //		判断商品是否收藏
        $favorite_model = Model('favorites');
        foreach ($recommended_goods_list as $key => $value) {
            $favorite_info = $favorite_model->getOneFavorites(array('fav_id'=>"$value[gid]",'fav_type'=>'goods','member_id'=>"{$_SESSION['member_id']}"));
            if(empty($favorite_info)){
                $favorites_flag = 0;
            }else{
                $favorites_flag = 1;
            }
            $recommended_goods_list[$key]['favorites_flag'] = $favorites_flag;
        }
// 商品多图
        if (!empty($recommended_goods_list)) {
            $goodsid_array = array();       // 商品id数组
            $commonid_array = array(); // 商品公共id数组
            $storeid_array = array();       // 店铺id数组
            foreach ($recommended_goods_list as $value) {
                $goodsid_array[] = $value['gid'];
                $commonid_array[] = $value['goods_commonid'];
                $storeid_array[] = $value['vid'];
            }
            $goodsid_array = array_unique($goodsid_array);
            $commonid_array = array_unique($commonid_array);
            $storeid_array = array_unique($storeid_array);
        }
        // 商品多图
        $goodsimage_more = Model('goods')->getGoodsImageList('goods_commonid in('.arrayToString( $commonid_array).')');
        foreach ($recommended_goods_list as $key => $value) {
            // 商品多图
            foreach ($goodsimage_more as $v) {
                if ($value['goods_commonid'] == $v['goods_commonid'] && $value['vid'] == $v['vid'] && $value['color_id'] == $v['color_id']) {
                    $recommended_goods_list[$key]['image'][] = $v;
                }
            }
        }
        $data['recommended_goods_list'] = $recommended_goods_list;
        //Template::output('recommended_goods_list',$recommended_goods_list);

        //幻灯片图片
        if($this->store_info['store_slide'] != '' && $this->store_info['store_slide'] != ',,,,'){
            //Template::output('store_slide', explode(',', $this->store_info['store_slide']));
            //Template::output('store_slide_url', explode(',', $this->store_info['store_slide_url']));
        }
        //Template::output('page','index');
        //Template::output('recommended_goods_list',$recommended_goods_list);

        //Template::showpage('index');
        return json_encode($data,true);
    }
    /**
     * 店铺商品搜索
     */
    public function searchGoods(){
        $condition = array();
        $page   =   input("page",0);
        $condition['vid'] = input('vid');
        $condition = " 1=1";
        if (trim(input('keyword')) != '') {
            $condition .= " and goods_name like'%".trim(input('keyword'))."%'";
        }

        // 排序
        $sort = input('sort') == 1 ? 'asc' : 'desc';
        switch (trim(input('key'))){
            case '1':
                $sort = 'gid '.$sort;
                break;
            case '2':
                $sort = 'goods_price '.$sort;
                break;
            case '3':
                //虚拟销量
                if(Config('virtual_sale')){
                    $sort = 'goods_salenum+virtual_sale '.$sort;
                }else{
                    $sort = 'goods_salenum '.$sort;
                }
                break;
            case '4':
                $sort = 'goods_collect '.$sort;
                break;
            case '5':
                $sort = 'goods_click '.$sort;
                break;
            default:
                $sort = 'gid desc';
                break;
        }

        //查询分类下的子分类
        if (intval(input('stc_id')) > 0){
            $condition['goods_stcids'] = array('like', '%,' . intval(input('stc_id')) . ',%');
        }

        $model_goods = new \app\v1\model\Goods();
        $fieldstr = "gid,goods_commonid,goods_name,goods_jingle,vid,store_name,goods_price,goods_marketprice,goods_storage,goods_image,goods_freight,goods_salenum,color_id,evaluation_good_star,evaluation_count";
        //虚拟销量
        if(Config('virtual_sale')){
            $fieldstr .= ',(goods_salenum+virtual_sale) as goods_salenum';
        }
        $recommended_goods_list = $model_goods->getGoodsListByColorDistinct($condition, $fieldstr, $sort, $page);

        // 获取最终价格
        $ga = new GoodsActivity();
        $recommended_goods_list = $ga->rebuild_goods_data($recommended_goods_list,'pc');
        if(count($recommended_goods_list)>0) {
            //loadfunc('search');
            //判断是否收藏
            $favorite_model = new Favorites();
            $memberId = input("member_id");
            foreach ($recommended_goods_list as $key => $value) {
                $favorite_info = $favorite_model->getOneFavorites(array('fav_id' => "$value[gid]", 'fav_type' => 'goods', 'member_id' => $memberId));
                if (empty($favorite_info)) {
                    $favorites_flag = 0;
                } else {
                    $favorites_flag = 1;
                }
                $recommended_goods_list[$key]['favorites_flag'] = $favorites_flag;
            }
            // 商品多图
            $commonid_array = array();
            if (!empty($recommended_goods_list)) {
                $goodsid_array = array();       // 商品id数组
                $commonid_array = array(); // 商品公共id数组
                $storeid_array = array();       // 店铺id数组
                foreach ($recommended_goods_list as $value) {
                    $goodsid_array[] = $value['gid'];
                    $commonid_array[] = $value['goods_commonid'];
                    $storeid_array[] = $value['vid'];
                }
                $goodsid_array = array_unique($goodsid_array);
                $commonid_array = array_unique($commonid_array);
                $storeid_array = array_unique($storeid_array);
            }
            // 商品多图
            $goodsimage_more = $model_goods->getGoodsImageList("  goods_commonid in (" . arrayToString($commonid_array) . ")");
            foreach ($recommended_goods_list as $key => $value) {
                // 商品多图
                foreach ($goodsimage_more as $v) {
                    if ($value['goods_commonid'] == $v['goods_commonid'] && $value['vid'] == $v['vid'] && $value['color_id'] == $v['color_id']) {
                        $recommended_goods_list[$key]['image'][] = $v;
                    }
                }
            }
        }

        $data['error_code'] = 200;
        $data['message'] = lang("操作成功");
        $data['list'] = $recommended_goods_list;
        return json_encode($data,true);
    }

}