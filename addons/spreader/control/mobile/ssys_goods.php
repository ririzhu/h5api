<?php
/**
 * WAP 分销推广商品
 *
 */


defined('DYMall') or exit('Access Invalid!');
class ssys_goodsCtl extends mobileHomeCtl{

	public function __construct() {
        parent::__construct();
    }

    // 首页 数据展示
    public function index()
    {
    	$pageSize = 14;
    	$condition = array();
    	$return_array = array();

    	$goods_list = $this->getHotSalesList($condition,$pageSize);

    	$return_array['list'] = $goods_list;

    	output_data($return_array);
    }

    /**
     * 获取所有商品分类
     */
    public function cates_list() {

        // 获取所有 可展示的一级标签
        // 倒序
        $ssys_goods_cates = M('ssys_goods_cates');

        $condition = array();

        $tag_list = array();

        $tag_list = $ssys_goods_cates->getGoodsCatesList($condition,0);
        
        $data_new['types'] = $tag_list;
        output_data($data_new);
    }

    // 更多商品 数据展示
    public function more_goods_list()
    {

        $tid = $_GET['t'] ? $_GET['t'] : 0;

        if ($tid == 0) {
            // 获取 第一个分类
            $first_goods_cates = M('ssys_goods_cates')->getGoodsCatesList('',1);
            if (!empty($first_goods_cates)) {
                $tid = $first_goods_cates[0]['id'];
            }
        }

        if ($tid > 0) {
            $this->get_cate_goods_list($tid,$_GET);
        }

    }

    // 分类的商品列表
    public function get_cate_goods_list($tid,$a)
    {
        $cate_id = $tid;
        $rows = $a['pn'] * $a['page'];

        $member_key=$a['ssys_key'];

        $ssys_goods = M('ssys_goods');

        $return_array = array();

        $condition['cate_id'] = $cate_id;

        $extend_data = $gids = $ssys_goods->get_spreader_goods_list($condition,"*");

        $gids = low_array_column($gids, 'gid');
        $extend_data = low_array_column($extend_data,NULL,'gid');

        unset($condition);
        $condition['gid'] = array("IN",$gids);
        $goods_api_list = $this->shop_api_get_hot_goods($condition,'gid,goods_name,vid,goods_price,goods_image,goods_jingle','',$a['page']);

        $goods_list = $goods_api_list['goods_list'];
        $page_count = $goods_api_list['page_count'];

        $bili = unserialize(C('ssys_yj_percent'));

        // 重组 数组 将佣金 加入数组中
        if (!empty($goods_list) && !empty($extend_data)) {
            foreach ($goods_list as $key => $value) {
                if (isset($extend_data[$value['gid']])) {
                    $value = array_merge($value,$extend_data[$value['gid']]);
                    $value['yj_amount'] *= $bili[0]/100;
                    $return_array[] = $value;
                }
            }
        }

        $return_data['goods'] = $return_array;

        output_data($return_data, mobile_page($page_count));

    }

    // 销量最高的 的分销推广商品
    public function getHotSalesList($condition,$pageSize=14)
    {
    	$return_array = array();

    	// 获取当前分销推广商品 ID 组合
    	$ssys_goods_model = M('ssys_goods');
    	$ssys_condition = array();
    	$extend_data = $gids = $ssys_goods_model->get_spreader_goods_list(array(),'gid,goods_commonid,yj_amount,cate_id,share_link');
    	$gids = low_array_column($gids, 'gid');
    	$extend_data = low_array_column($extend_data,NULL,'gid');

    	$condition['gid'] = array("IN",$gids);
    	$goods_api_list = $this->shop_api_get_hot_goods($condition,'gid,goods_name,vid,goods_price,goods_image,goods_jingle','goods_salenum desc',$pageSize);

        $goods_list = $goods_api_list['goods_list'];

        $bili = unserialize(C('ssys_yj_percent'));

    	// 重组 数组 将佣金 加入数组中
    	if (!empty($goods_list) && !empty($extend_data)) {
	    	foreach ($goods_list as $key => $value) {
	    		if (isset($extend_data[$value['gid']])) {
	    			$value = array_merge($value,$extend_data[$value['gid']]);
	    			$value['yj_amount'] *= $bili[0]/100;
	    			$return_array[] = $value;
	    		}
	    	}
    	}

    	return $return_array;
    }

    // 商户系统 对应方法
    public function shop_api_get_hot_goods($condition,$fields="*",$order="gid asc",$pageSize=10)
    {
        $return_array = array();

    	$goods_model = Model('goods');
        $goods_list = $goods_model->getGoodsListByCommonidDistinct($condition,$fields,$order,$pageSize);
        $page_count = $goods_model->gettotalpage();

        $goodscommon_ids = low_array_column($goods_list,'jmys_distinct');
        $common_condition['goods_commonid'] = array("IN",$goodscommon_ids);
        $goods_common_names = $goods_model->table('goods_common')->field('goods_commonid,goods_name')->group('')->where($common_condition)->page(0)->order('')->select();
        $goods_common_names = low_array_column($goods_common_names,'goods_name','goods_commonid');

        // 获取最终价格
        $goods_list = Model('goods_activity')->rebuild_goods_data($goods_list);

        foreach ($goods_list as $key => $value) {
        	$value['goods_image'] = cthumb($value['goods_image'], 240,$value['vid']);
            // 重组商品名称 展示 goods_common 的商品名称
            $value['goods_name'] = isset($goods_common_names[$value['jmys_distinct']]) ? $goods_common_names[$value['jmys_distinct']] : $value['goods_name'];
        	$goods_list[$key] = $value;
        }

        $return_array['goods_list'] = $goods_list;
        $return_array['page_count'] = $page_count;

        return $return_array;
    }

}