<?php
/**
 * 收银商品 接口
 *
 */
defined('DYMall') or exit('Access Invalid!');

class cashersystem_goodsCtl{

    protected $vendor_info = array();

	public function __construct()
	{
		$this->checkToken();
	}
	
	// 收银商品 列表
	public function goodsList()
	{

        $search = array();
		
        $search['dian_id'] = $dian_id = isset($_GET['dian_id']) ? intval($_GET['dian_id']) : 0;
        $search['cate_id'] = $cate_id = isset($_GET['cate_id']) ? intval($_GET['cate_id']) : 0;
        $search['search_val'] = $search_val = isset($_GET['search']) ? trim($_GET['search']) : '';
        $pageSize = intval($_GET['pageSize'] > 0 ? $_GET['pageSize'] : 10);
        $_GET['pn'] = intval($_GET['currentPage'] > 0 ? $_GET['currentPage'] : 1);

        $state = 200;
        $data = '';
        $message = 'success';

        // 获取 商品 列表
        $model_goods = M('cashsys_goods','cashersystem');
        if ($search_val) {
        	$condition['goods.gid|goods.goods_name|goods.goods_barcode'] = array("LIKE","%".$search_val."%");
        }
        if ($dian_id) {
            $condition['dian_goods.dian_id'] = $dian_id;
        }else{
            // 根据vid 获取 dian_id 集合
            $dian_condition['vid'] = $this->vendor_info['vid'];
//            $dian_list = Model('dian')->getDianList($dian_condition);
            $dian_list = Model()->table('dian')->where($dian_condition)->select();
            $dian_ids = low_array_column($dian_list,'id');
            $condition['dian_goods.dian_id'] = array("IN",$dian_ids);
        }
        if ($cate_id) {
            // select goods_id from ''
            $son_condition['goods_stcids_1'] = array('LIKE','%,'.$cate_id.',%');
            $cate_goods_ids = $model_goods->table('cashsys_goods_extend')->where($son_condition)->field('goods_id')->select();
            $cate_goods_ids = low_array_column($cate_goods_ids,'goods_id');
            $condition['goods.gid'] = array("IN", $cate_goods_ids);
        }
        $page_list = $model_goods->getGoodsList($condition,'*',$pageSize,'goods.gid');

        if (!empty($page_list)) {
            $model_dian = Model('dian');

            // 获取门店名称
            foreach ($page_list as $key => $value) {
                if ($value['dian_id']) {
                    $dian_info = $model_dian->getDianInfoByID($value['vid'],$value['dian_id']);
                    if (!empty($dian_info)) {
                        $value['dian_name'] = $dian_info['dian_name'];
                    }
                }
                // 获取 店铺分类一级分类名称
                if ($value['goods_stcids'] != ',0,' && $value['goods_stcids'] != '') {
                    $goods_stcids = $value['goods_stcids'];
                    $goods_stcids_arr = explode(',', $goods_stcids);
                    $goods_stcids_arr = array_filter($goods_stcids_arr);
                    // 获取一级分类名称
                    $my_condition['stc_id'] = array("IN",$goods_stcids_arr);
                    $my_condition['stc_state'] = 1;
                    $my_data = Model('my_goods_class')->where($my_condition)->select();
                    // 获取 父级分类ID
                    $parent_ids = low_array_column($my_data,'stc_parent_id','stc_id');

                    foreach ($parent_ids as $p_key => $p_value) {
                        if ($p_value == 0) {
                            $p_value = $p_key;
                        }
                        $parent_ids[$p_key] = $p_value;
                    }
                    // 去重
                    $parent_ids = array_flip($parent_ids);
                    $parent_ids = array_flip($parent_ids);
                    $parent_ids = array_values($parent_ids);

                    // 获取父级分类名称
                    unset($my_condition);
                    $my_condition['stc_id'] = array("IN",$parent_ids);
                    $my_condition['stc_state'] = 1;
                    $my_data = Model('my_goods_class')->where($my_condition)->field("stc_id,stc_name")->select();
                    $stc_names = low_array_column($my_data,'stc_name');
                    $stc_name = implode(',', $stc_names);
                    $value['stc_name'] = $stc_name;
                }

                $page_list[$key] = $value;
            }
            // 获取最终价格
            $page_list = Model('goods_activity')->rebuild_goods_data($page_list);

            $data = array(
                    'list' => $page_list,
                    'pagination' => array(
                            'current' => $_GET['pn'],
                            'pageSize' => $pageSize,
                            'total' => intval($model_goods->gettotalnum()),
                        ),
                    'searchlist' => $search
                );

        }else{
            $state = 255;
            $data = '';
            $message = Language::get('没有数据');
        }
        
        $return_last = array(
        		'state' => $state,
        		'data' => $data,
        		'msg' => $message,
        	);

        echo json_encode($return_last);

	}

	// 获取 商品所在门店
	public function getGoodsInfo()
	{

		$gid = $_GET['gid'];

        $state = 200;
        $data = '';
        $message = 'success';

		// 删除数据
		$model_goods = M('cashsys_goods','cashersystem');
    	
    	$condition['goods_id'] = $gid;
    	// $condition['vid'] = $this->vendor_info['vid'];

    	$data = $goods_info = $model_goods->getCashsysGoodsInfo($condition);

        if (!empty($data)) {
            $mall_goods_base_info = Model('goods')->getGoodsInfoByID($gid);
            if ($mall_goods_base_info['goods_name']) {
                $data['goods_name'] = $mall_goods_base_info['goods_name'];
                $data['goods_stcids'] = $mall_goods_base_info['goods_stcids'];
                // 获取 店铺分类一级分类名称
                if ($data['goods_stcids'] != ',0,' && $data['goods_stcids'] != '') {
                    $goods_stcids = $data['goods_stcids'];
                    $goods_stcids_arr = explode(',', $goods_stcids);
                    $goods_stcids_arr = array_filter($goods_stcids_arr);
                    // 获取一级分类名称
                    $my_condition['stc_id'] = array("IN",$goods_stcids_arr);
                    $my_condition['stc_state'] = 1;
                    $my_data = Model('my_goods_class')->where($my_condition)->select();
                    // 获取 父级分类ID
                    $parent_ids = low_array_column($my_data,'stc_parent_id','stc_id');

                    foreach ($parent_ids as $p_key => $p_value) {
                        if ($p_value == 0) {
                            $p_value = $p_key;
                        }
                        $parent_ids[$p_key] = $p_value;
                    }
                    // 去重
                    $parent_ids = array_flip($parent_ids);
                    $parent_ids = array_flip($parent_ids);
                    $parent_ids = array_values($parent_ids);

                    // 获取父级分类名称
                    unset($my_condition);
                    $my_condition['stc_id'] = array("IN",$parent_ids);
                    $my_condition['stc_state'] = 1;
                    $my_data = Model('my_goods_class')->where($my_condition)->field("stc_id,stc_name")->select();
                    $stc_names = low_array_column($my_data,'stc_name');
                    $stc_name = implode(',', $stc_names);
                    $data['stc_name'] = $stc_name;
                }
            }

            $dian_list = $model_goods->getDianListByGid($gid);
            $dian_name_arr = low_array_column($dian_list,'dian_name','id');
            $dian_name_arr = array_values($dian_name_arr);

            $data['dian_name_str'] = implode(',', $dian_name_arr);
            
        }else{
            $state = 255;
            $data = '';
            $message = Language::get('没有数据');
        }

        $return_last = array(
        		'state' => $state,
        		'data' => $data,
        		'msg' => $message,
        	);

        echo json_encode($return_last);
	}

	// 校验token
    public function checkToken()
    {
        $check_flag = true;
        // 校验token
        $token = $_REQUEST['token'];

        $model_bwap_vendor_token = Model('bwap_vendor_token');
        $bwap_vendor_token_info = $model_bwap_vendor_token->getSellerTokenInfoByToken($token);
        if (empty($bwap_vendor_token_info)) {
            $check_flag = false;
        }

        $model_vendor = Model('vendor');
        $seller_info = model()->table('seller')->where(['seller_id'=>$bwap_vendor_token_info['seller_id']])->find();
        $this->vendor_info = $model_vendor->getStoreInfo(array('vid'=>$seller_info['vid']));
        if(empty($this->vendor_info)) {
            $check_flag = false;
        } else {
            $this->vendor_info['token'] = $bwap_vendor_token_info['token'];
        }

        if (!$check_flag) {
            $state = 275;
            $data = '';
            $message = '请登录';
            $return_last = array(
                    'state' => $state,
                    'data' => $data,
                    'msg' => $message,
                );

            echo json_encode($return_last);exit;
        }
    }

}