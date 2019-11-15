<?php
/**
 * 门店商户后台控制器 sld
 *
 */
defined('DYMall') or exit('Access Invalid!');

class common_dianAdd extends BaseSellerCtl {

	// 门店列表
	public function index($par)
	{
		$member_ids = low_array_column($par,'member_id','id');

		// 只获取 不为0 的数据
		$member_ids = array_filter($member_ids);
		// 获取店长用户名
		$cashsys_users = M('cashsys_users','common');
		$condition['is_leader'] = 1;
		$condition['id'] = array("IN",$member_ids);
		$fields = "id,casher_name";
		$leader_users = $cashsys_users->getCashsysUsersList($condition,$fields);
		$leader_users_arr = low_array_column($leader_users,'casher_name','id');

		foreach ($par as $key => $value) {
			$value['cashersystem_running'] = 1;
			$value['member_name'] = isset($leader_users_arr[$value['member_id']]) ? $leader_users_arr[$value['member_id']] : '-';
			$par[$key] = $value;
		}

		return $par;
	}

	// 编辑门店 数据展示
	public function detail($par)
	{
		// 收银版块 开启表示
		$par['cashersystem_running'] = 1;
		// 获取店长列表
		$cashsys_users = M('cashsys_users','common');
		$condition['is_leader'] = 1;
		$condition['dian_id|id'] = array(0,$par['member_id'],"_multi"=>1);
		$condition['vid'] = $par['vid'];
		$fields = "id,casher_name";
		$leader_users = $cashsys_users->getCashsysUsersList($condition,$fields);

		$par['leader_users'] = $leader_users;
		// 获取当前门店的收银员集合
		unset($condition['dian_id|id']);
		unset($condition['is_leader']);
		$condition['is_leader'] = 0;
		$condition['dian_id'] = $par['id'];
		$casher_users = $cashsys_users->getCashsysUsersList($condition,$fields);
		$casher_users_ids = low_array_column($casher_users,'id');
		$casher_users_val = implode(',', $casher_users_ids);

		$par['casher_users'] = $casher_users;
		$par['casher_users_val'] = $casher_users_val;

		// 获取收银员集合(可用)
		unset($condition['dian_id']);
		$condition['is_leader'] = 0;
		$condition['dian_id|id'] = array(0,array("IN",$casher_users_ids),"_multi"=>1);
		$all_casher_users = $cashsys_users->getCashsysUsersList($condition,$fields);

		$par['all_casher_users'] = $all_casher_users;

		return $par;
	}

	// 存储门店信息
	public function save($par)
	{	
		$return = true;
		// 获取 选中的 收银员信息
		$casher_ids = $par['post']['casher_ids'];
		$casher_ids_arr = explode(',', $casher_ids);
		// 去空
		$casher_ids_arr = array_values(array_filter($casher_ids_arr));

		$cashsys_users = M('cashsys_users','common');
		if(!empty($casher_ids_arr)){
			$condition['dian_id'] = $dian_id = isset($par['insert']) ? $par['insert'] : $par['post']['dian_id'];
			$condition['vid'] = $par['data']['vid'];
			$condition['is_leader'] = 0;

			$clearData['dian_id'] = 0;

			// 将该门店下的所有收银员 置换
			$cashsys_users->editCasherData($clearData, $condition);

			// 将提交的收银员 设置为该门店的收银员
			unset($condition['dian_id']);
			$condition['id'] = array("IN",$casher_ids_arr);
			$updateData['dian_id'] = $dian_id;
			$return = $cashsys_users->editCasherData($updateData, $condition);
		}

		// 更新 店长所属门店信息
		// 将本店店长 去掉 添加新的店长
		if (isset($par['post']['member_id']) && $return !== false) {
			unset($condition);
			$condition['dian_id'] = $dian_id = isset($par['insert']) ? $par['insert'] : $par['post']['dian_id'];
			$condition['vid'] = $par['data']['vid'];
			$condition['is_leader'] = 1;

			$clearData['dian_id'] = 0;

			// 将该门店下的店长 置换
			$cashsys_users->editCasherData($clearData, $condition);

			if ($par['post']['member_id'] > 0) {
				// 将提交的收银员 设置为该门店的店长
				unset($condition['dian_id']);
				$condition['id'] = $par['post']['member_id'];

				$updateData['dian_id'] = $dian_id;
				$return = $cashsys_users->editCasherData($updateData, $condition);
			}
		}

		return $return;
	}

	// 门店删除
	public function drop($par)
	{
		$cashsys_users = M('cashsys_users','common');

		$condition['dian_id'] = $par['dian_id'];
		$condition['vid'] = $par['vid'];

		$clearData['dian_id'] = 0;

		// 将该门店下的所有收银员 置换
		$result = $cashsys_users->editCasherData($clearData, $condition);

		return $result;
	}
}