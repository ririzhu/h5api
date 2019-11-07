<?php
/**
 * 统计记录
 *
 */
namespace app\v1\model;

use think\Model;
use think\Db;

class SsysStatisticsLog extends Model
{
	// 更新当天的统计记录
	public function saveStatisticsLog($data)
    {
		if (isset($data['share_num']) && intval($data['share_num']) > 0) {
			$insert_data['share_num'] =  intval($data['share_num']);
			$update_data['share_num'] =  array('inc',intval($data['share_num']));
		}
		if (isset($data['order_num']) && intval($data['order_num']) > 0) {
			$insert_data['order_num'] =  intval($data['order_num']);
			$update_data['order_num'] =  array('inc',intval($data['order_num']));
		}
		if (isset($data['order_amount']) && floatval($data['order_amount']) > 0) {
			$insert_data['order_amount'] =  floatval($data['order_amount']);
			$update_data['order_amount'] =  array('inc',floatval($data['order_amount']));
		}
		if (isset($data['yj_av_amount']) && floatval($data['yj_av_amount']) > 0) {
			$insert_data['yj_av_amount'] =  floatval($data['yj_av_amount']);
			$update_data['yj_av_amount'] =  array('inc',floatval($data['yj_av_amount']));
		}
		if (isset($data['yj_av_amount_minus']) && floatval($data['yj_av_amount_minus']) > 0) {
			$insert_data['yj_av_amount'] =  -floatval($data['yj_av_amount_minus']);
			$update_data['yj_av_amount'] =  array('dec',floatval(abs($data['yj_av_amount_minus'])));
		}
		if (isset($data['yj_freeze_amount']) && floatval($data['yj_freeze_amount']) > 0) {
			$insert_data['yj_freeze_amount'] =  floatval($data['yj_freeze_amount']);
			$update_data['yj_freeze_amount'] =  array('inc',floatval($data['yj_freeze_amount']));
		}
		if (isset($data['yj_freeze_amount_minus']) && floatval($data['yj_freeze_amount_minus']) > 0) {
			$insert_data['yj_freeze_amount'] =  -floatval($data['yj_freeze_amount_minus']);
			$update_data['yj_freeze_amount'] =  array('dec',floatval(abs($data['yj_freeze_amount_minus'])));
		}

        // 记录是否存在
        $condition['log_time'] = $log_time = strtotime(date('Y-m-d',time()));
        $has_flag = $this->hasLog($condition);

		if ($has_flag > 0) {
			$return = Db::name('ssys_statistics_log')->where($condition)->update($update_data);
		}else{
			$insert_data['log_time'] = $log_time;
			$return = Db::name('ssys_statistics_log')->insert($insert_data);
		}

		return $return;

	}

	// 是否存在记录
	public function hasLog($condition)
    {
		return Db::name('ssys_statistics_log')->where($condition)->count();
	}

	// 统计记录 列表
	public function getAllList($condition,$page=0)
    {
		return Db::name('ssys_statistics_log')->where($condition)->page($page)->select();
	}

}