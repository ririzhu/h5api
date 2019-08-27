<?php
/**
 * 网站设置
 */
defined('DYMall') or exit('Access Invalid!');
class cashersystem_operationAdd extends SystemCtl{
	public function __construct(){
		parent::__construct();
	}

	/**
	 * 商城管理——活动管理——活动开关
	 */
	public function setActivity($par)
	{
		Language::read('cashsys_setting','cashersystem');
		$model_setting = M('cashsys_setting','cashersystem');

		$list_setting = $model_setting->getListSetting();
        $setting_item['field_code'] = 'cashersystem_isuse';
        $setting_item['field_val'] = isset($list_setting['cashersystem_isuse']) ? intval($list_setting['cashersystem_isuse']) : 0;
        $setting_item['field_name'] = Language::get('cashersystem_isuse');
        $setting_item['field_notice'] = Language::get('cashersystem_isuse_notice');

        array_push($par, $setting_item);

        return $par;
	}

	/**
	 * 商城管理——活动管理——活动开关保存
	 */
	public function saveActivity($par)
	{
		$model_setting = M('cashsys_setting','cashersystem');
		$result = false;

		if(C('dian') && (C('dian_isuse') || $par['dian_isuse'] == true)){
			$update_array['cashersystem_isuse'] = $par['cashersystem_isuse']=='true'?1:0;

			$result = $model_setting->updateSetting($update_array);
		}
		if ($result) {
			// 开启收银模块后 会将当前所有店铺的门店管理员清空
			$update_data = array(
				'member_id' => '',
			);
			$update_condition = array(
				1=>1
			);
			Model('dian')->editStore($update_data,$update_condition);
		}

		return $result;
	}
	

}