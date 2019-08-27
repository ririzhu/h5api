<?php
/**
 * 系统后台 推手系统 商品分类设置
 */
defined('DYMall') or exit('Access Invalid!');

class ssys_goods_catesCtl extends SystemCtl
{
	
	public function __construct()
	{
        parent::__construct();
	}

	// 推手设置 商品 分类 列表
	public function cate_list()
	{
        $pageSize = intval($_GET['pageSize'] > 0 ? $_GET['pageSize'] : 10);
        $_GET['pn'] = intval($_GET['currentPage'] > 0 ? $_GET['currentPage'] : 1);

        $condition = array(
        	);

        $ssys_goods_cates = M('ssys_goods_cates');

        $goods_cates_list = $ssys_goods_cates->getGoodsCatesList($condition,$pageSize);

        foreach ($goods_cates_list as $key => $val){
		    $goods_cates_list[$key]['create_time'] = date('Y-m-d H:i:s',$val['create_time']);
		    $goods_cates_list[$key]['update_time'] = date('Y-m-d H:i:s',$val['update_time']);
		}

        $return_last = array(
        		'list' => $goods_cates_list,
        		'pagination' => array(
        				'current' => $_GET['pn'],
        				'pageSize' => $pageSize,
        				'total' => intval($ssys_goods_cates->gettotalnum()),
        			),
        	);

        echo json_encode($return_last);
		
	}

	// 推手设置 商品 分类 详情
	public function cate_info()
	{
        $cate_id = intval($_GET['id']);

        $state = 255;
        $data = '';
        $message = L('操作失败');

        if ($cate_id) {
        	$condition = array(
        			'id' => $cate_id,
        		);
        	$ssys_goods_cates = M('ssys_goods_cates');
        	$cate_info = $ssys_goods_cates->getGoodsCatesInfo($condition);

        	if ($cate_info) {
        		$state = 200;
        		$data = $cate_info;
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

	// 推手设置 商品 分类添加
	public function cate_save()
	{
		$tplData = $_POST['tplData'];

        $state = 255;
        $message = L('操作失败');

        if (is_array($tplData) && !empty($tplData)) {
                $insertData['g_name'] = $tplData['g_name'];
        	$insertData['order_num'] = $tplData['order_num'] ? $tplData['order_num'] : 0;
        	$insertData['create_time'] = time();
        	$insertData['update_time'] = time();

        	$ssys_goods_cates = M('ssys_goods_cates');
        	$save_flag = $ssys_goods_cates->saveGoodsCateInfo($insertData);
        	if ($save_flag) {
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

	// 推手设置 商品 分类编辑
	public function cate_update()
	{
		$tplData = $_POST['tplData'];

		$cate_id = intval($_POST['id']);

        $state = 255;
        $message = L('操作失败');

        if (is_array($tplData) && !empty($tplData) && $cate_id) {
        	$tplData['update_time'] = time();
        	$ssys_goods_cates = M('ssys_goods_cates');
        	$condition = array(
        			'id' => $cate_id,
        		);

        	$update_flag = $ssys_goods_cates->updateGoodsCateInfo($condition,$tplData);
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

	// 推手设置 商品 分类删除
	public function cate_delete()
	{
		$cate_ids = $_POST['ids'];

        $state = 255;
        $message = L('操作失败');

        if (is_array($cate_ids) && !empty($cate_ids)) {
        	$ssys_goods_cates = M('ssys_goods_cates');

			$where = ' id IN ('.implode(',', $cate_ids).') ';

        	$delete_flag = $ssys_goods_cates->deleteGoodsCates($where);

        	if ($delete_flag) {
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


}