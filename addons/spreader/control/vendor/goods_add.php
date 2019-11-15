<?php
/**
 * 商品管理
 */
defined('DYMall') or exit ('Access Invalid!');
class spreader_goods_addAdd extends BaseSellerCtl {

    public function __construct()
    {
        parent::__construct();
    }

    // 编辑/添加商品 存储 分销推广商品 相关数据
    public function save_goods($par)
    {
    	if ($par['commonid']) {

	    	$ssys_goods = M("ssys_goods",'spreader');

	    	// 获取 分销推广商品 现有数据
	    	$ssys_condition['goods_commonid'] = $par['commonid'];
	    	$ssys_condition['from_flag'] = $par['from_flag'];
	    	$goods_spreader_gids = $ssys_goods->get_spreader_goods_list($ssys_condition,'gid');
			$goods_spreader_gids = low_array_column($goods_spreader_gids, 'gid');

			$spec_gids_yj = array();
			if (isset($par['spec'])) {
				foreach ($par['spec'] as $spec_k => $spec_v) {
					if ($spec_v['gid'] == '') {
						// 新增规格 需要匹配出 一添加的当前规格的gid
						// 根据common_id 及 规格 序列化后的值进行查找定位gid
				    	$goods_model = Model('goods');
				    	$condition['goods_commonid'] = $par['commonid'];
				    	$condition['goods_spec'] = serialize($spec_v['sp_value']);
				    	$now_spec_goods_info = $goods_model->getGoodsInfo($condition,'gid');
						if (!empty($now_spec_goods_info) && $now_spec_goods_info['gid']) {
							$now_item_gid = $now_spec_goods_info['gid'];
						}
						unset($now_spec_goods_info);
						unset($condition);
					}else{
						$now_item_gid = $spec_v['gid'];
					}
					if ($now_item_gid) {
						$spec_gids_yj[$now_item_gid] = $spec_v['yj_amount'];
					}
				}
			}
    		
	    	if ($par['is_spreader_goods'] > 0 && ($par['spreader_goods_yj_amount'] > 0 || !empty($spec_gids_yj))) {

	    		$save_data = array();
	    		$action_gids = array();
	    		$need_clear_gids = array();

	    		$update_common['yj_amount'] = floatval($par['spreader_goods_yj_amount']);
	    		$update_common['goods_commonid'] = $par['commonid'];
	    		$update_common['from_flag'] = $par['from_flag'];
	    		$update_common['share_link_base'] = $par['share_link_base'];

	    		// 根据 common_id  获取 所有gid
		    	$goods_model = Model('goods');
		    	$condition['goods_commonid'] = $update_common['goods_commonid'];
		    	$goods_ids = $goods_model->getGoodsList($condition,'gid');
				$goods_ids = low_array_column($goods_ids, 'gid');

		    	if (!empty($goods_ids)) {
	    			foreach ($goods_ids as $key => $value) {
	    				$update_item = array();
	    				unset($condition);
				    	// 验证更新 还是 新增
				    	$condition['gid'] = $value;
				    	$condition['goods_commonid'] = $update_common['goods_commonid'];
				    	$goods_spreader_info = $ssys_goods->get_spreader_goods_info($condition,'gid');

				    	// 编辑或删除 均需要的数据
			    		$update_item['yj_amount'] = isset($spec_gids_yj[$value]) ? $spec_gids_yj[$value] : $update_common['yj_amount'];
	    				$update_item['from_flag'] = $update_common['from_flag'];
	    				$update_item['share_link'] = str_replace("[GID]", $value, $update_common['share_link_base']);

	    				if ($update_item['yj_amount'] > 0) {
					    	if (empty($goods_spreader_info)) {
					    		// 添加
			    				$update_item['gid'] = $value;
			    				$update_item['goods_commonid'] = $update_common['goods_commonid'];

					    		$ssys_goods->insert_ssys_goods($update_item);
					    		$goods_spreader_gids[] = $value;
					    	}else{
					    		// 编辑
					    		$update_condition = array();
			    				$update_condition['gid'] = $value;
			    				$update_condition['goods_commonid'] = $update_common['goods_commonid'];
					    		$ssys_goods->update_ssys_goods($update_item,$update_condition);
					    	}
					    	
					    	// 记录 已操作的gid 为之后 删除操作做准备
					    	$action_gids[] = $value;
					    }
	    			}

	    			// 清除 ssys_goods 多余的 商品信息 数据
	    			$need_clear_gids = array_diff($goods_spreader_gids, $action_gids);

		    	}else{
		    		$need_clear_gids = $goods_spreader_gids;
		    	}
	    	}else{
	    		$need_clear_gids = $goods_spreader_gids;
	    	}

	    	// 清除 多余的 分销推广商品信息
			if (!empty($need_clear_gids)) {
    			$delete_condition['gid'] = array("IN",$need_clear_gids);
    			$delete_condition['goods_commonid'] = $par['commonid'];
    			$delete_condition['from_flag'] = $par['from_flag'];
    			$ssys_goods->delete_ssys_goods($delete_condition);
			}
    	}
    	
    }

}