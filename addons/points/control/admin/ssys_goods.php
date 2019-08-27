<?php
/**
 * 系统后台 推手系统 商品设置
 */
defined('DYMall') or exit('Access Invalid!');

class ssys_goodsCtl extends SystemCtl
{
	
	public function __construct()
	{
        parent::__construct();
	}

	// 商品列表
	public function goods_list()
	{
        $pageSize = intval($_GET['pageSize'] > 0 ? $_GET['pageSize'] : 10);
        $_GET['pn'] = intval($_GET['currentPage'] > 0 ? $_GET['currentPage'] : 1);

        $return_array = array();
        $condition = array(
        	);

        $ssys_goods = M('ssys_goods');

        $extend_data = $gids = $ssys_goods->get_spreader_goods_list($condition,"*",$pageSize);

        $total_num = $ssys_goods->gettotalnum();

    	$gids = low_array_column($gids, 'gid');

    	$extend_data = low_array_column($extend_data,NULL,'gid');
    	$condition['gid'] = array("IN",$gids);
    	$goods_list = $this->shop_api_get_goods_SKU($condition,'gid,goods_commonid,goods_name,vid,goods_price,goods_image,goods_jingle','',0);

		//成为推手购买条件商品加上标识
		array_walk($goods_list,function(&$v) use ($extend_data){
				$v['is_buy_condition'] = $extend_data[$v['gid']]['is_buy_condition']?:0;
		});

        $cate_name_list = M('ssys_goods_cates')->getGoodsCatesList();
        $cate_name_list = low_array_column($cate_name_list,'g_name', 'id');

    	// 重组 数组 将佣金 加入数组中
    	if (!empty($goods_list) && !empty($extend_data)) {
	    	foreach ($goods_list as $key => $value) {
	    		if (isset($extend_data[$value['gid']])) {
				    $extend_data[$value['gid']]['add_time'] = date('Y-m-d H:i:s',$extend_data[$value['gid']]['add_time']);
				    $extend_data[$value['gid']]['update_time'] = date('Y-m-d H:i:s',$extend_data[$value['gid']]['update_time']);
                    $now_cate_id = $extend_data[$value['gid']]['cate_id'];
                    $extend_data[$value['gid']]['g_name'] = isset($cate_name_list[$now_cate_id]) ? $cate_name_list[$now_cate_id] : '未绑定';

	    			$value = array_merge($value,$extend_data[$value['gid']]);
	    			$return_array[$value['id']] = $value;
	    		}
	    	}
    	}

        krsort($return_array);
        $return_array = array_values($return_array);

        $return_last = array(
        		'list' => $return_array,
        		'pagination' => array(
        				'current' => $_GET['pn'],
        				'pageSize' => $pageSize,
        				'total' => intval($total_num),
        			),
        	);

        echo json_encode($return_last);
		
	}

	/*
	 * 修改成为推手购买商品标识
	 * @param int gid 商品gid
	 * @param int is_buy_condition 是否是被选中的条件商品 1是 0否
	 * return json [state=>200(成功)/255(失败),msg=>消息说明]
	 */
	public function edit_becomets_condition_goods()
	{
		$is_buy_condition = intval($_POST['is_buy_condition']);
		$gid = intval($_POST['gid']);
		$model = model();
		$res = $model->table('ssys_goods')->where(['gid'=>$gid])->update(['is_buy_condition'=>$is_buy_condition]);
		if($res){
			echo json_encode(['state'=>200,'msg'=>'操作成功']);die;
		}
		echo json_encode(['state'=>255,'msg'=>'操作失败']);die;
	}

	// 商品 详情
	public function goods_info()
	{

        $g_id = intval($_GET['id']);

        $state = 255;
        $data = '';
        $message = L('操作失败');

        $return_array = array();

        if ($g_id) {
        	$condition = array(
        			'id' => $g_id,
        		);
        	$ssys_goods = M('ssys_goods');
        	$extend_data = $goods_info = $ssys_goods->get_spreader_goods_info($condition);

        	if ($goods_info) {
		    	$api_condition['gid'] = $goods_info['gid'];
		    	$goods_api_info = $this->shop_api_get_goods_SKU($api_condition,'gid,goods_commonid,goods_name,vid,goods_price,goods_image,goods_jingle','',1);

		    	// 重组 数组 将佣金 加入数组中
		    	if (!empty($goods_api_info) && !empty($extend_data)) {
		    		
		    		if (isset($extend_data)) {
					    $extend_data['add_time'] = date('Y-m-d H:i:s',$extend_data['add_time']);
					    $extend_data['update_time'] = date('Y-m-d H:i:s',$extend_data['update_time']);

		    			$goods_api_info = array_merge($goods_api_info[0],$extend_data);
		    			$return_array = $goods_api_info;
		    		}
		    	}

        		$state = 200;
        		$data = $return_array;
        		$message = L('操作成功');
        	}
        }else{
        	$message = L('参数错误');
        }

        $return_last = array(
        		'state' => $state,
        		'data' => $data,
        		'msg' => $message,
        	);

        echo json_encode($return_last);

	}

	// 商品 编辑
	public function goods_update()
	{
		$tplData = $_POST['tplData'];

		$g_id = intval($_POST['id']);

        $state = 255;
        $message = L('操作失败');

        if (is_array($tplData) && !empty($tplData) && $g_id) {
        	$ssys_goods = M('ssys_goods');
        	$condition = array(
        			'id' => $g_id,
        		);

        	$update_flag = $ssys_goods->update_ssys_goods($tplData,$condition);
        	if ($update_flag) {
        		$state = 200;
        		$message = L('操作成功');
        	}
        }else{
        	$message = L('参数错误');
        }
        
        $return_last = array(
        		'state' => $state,
        		'msg' => $message,
        	);

        echo json_encode($return_last);

	}

    // 商户系统 对应方法
    public function shop_api_get_goods_SKU($condition,$fields="*",$order="gid asc",$pageSize=10)
    {
    	$goods_model = Model('goods');
        $goods_list = $goods_model->getGoodsOnlineList($condition,$fields,$order,$pageSize);

        // 获取最终价格
        $goods_list = Model('goods_activity')->rebuild_goods_data($goods_list);

        foreach ($goods_list as $key => $value) {
        	$value['goods_image'] = cthumb($value['goods_image'], 240,$value['vid']);
        	$goods_list[$key] = $value;
        }

        return $goods_list;
    }


}
