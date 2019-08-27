<?php
/**
 * 统计记录
 *
 */
defined('DYMall') or exit('Access Invalid!');
class ssys_statistics_logModel extends Model {

	// 更新当天的统计记录
	public function save_statistics_log($data){
		$return = false;

		// 记录是否存在
		$condition['log_time'] = $log_time = strtotime(date('Y-m-d',time()));
		$has_flag = $this->has_log($condition);

		if (isset($data['share_num']) && intval($data['share_num']) > 0) {
			$insert_data['share_num'] =  intval($data['share_num']);
			$update_data['share_num'] =  array('exp','share_num+'.intval($data['share_num']));
		}
		if (isset($data['order_num']) && intval($data['order_num']) > 0) {
			$insert_data['order_num'] =  intval($data['order_num']);
			$update_data['order_num'] =  array('exp','order_num+'.intval($data['order_num']));
		}
		if (isset($data['order_amount']) && floatval($data['order_amount']) > 0) {
			$insert_data['order_amount'] =  floatval($data['order_amount']);
			$update_data['order_amount'] =  array('exp','order_amount+'.floatval($data['order_amount']));
		}
		if (isset($data['yj_av_amount']) && floatval($data['yj_av_amount']) > 0) {
			$insert_data['yj_av_amount'] =  floatval($data['yj_av_amount']);
			$update_data['yj_av_amount'] =  array('exp','yj_av_amount+'.floatval($data['yj_av_amount']));
		}
		if (isset($data['yj_av_amount_minus']) && floatval($data['yj_av_amount_minus']) > 0) {
			$insert_data['yj_av_amount'] =  -floatval($data['yj_av_amount_minus']);
			$update_data['yj_av_amount'] =  array('exp','yj_av_amount-'.floatval(abs($data['yj_av_amount_minus'])));
		}
		if (isset($data['yj_freeze_amount']) && floatval($data['yj_freeze_amount']) > 0) {
			$insert_data['yj_freeze_amount'] =  floatval($data['yj_freeze_amount']);
			$update_data['yj_freeze_amount'] =  array('exp','yj_freeze_amount+'.floatval($data['yj_freeze_amount']));
		}
		if (isset($data['yj_freeze_amount_minus']) && floatval($data['yj_freeze_amount_minus']) > 0) {
			$insert_data['yj_freeze_amount'] =  -floatval($data['yj_freeze_amount_minus']);
			$update_data['yj_freeze_amount'] =  array('exp','yj_freeze_amount-'.floatval(abs($data['yj_freeze_amount_minus'])));
		}

		if ($has_flag > 0) {
			$return = $this->table('ssys_statistics_log')->where($condition)->update($update_data);
		}else{
			$insert_data['log_time'] = $log_time;
			$return = $this->table('ssys_statistics_log')->insert($insert_data);
		}

		return $return;

	}

	// 是否存在记录
	public function has_log($condition){
		return $this->table('ssys_statistics_log')->where($condition)->count();
	}

	// 统计记录 列表
	public function getAllList($condition,$page=0){
		return $this->table('ssys_statistics_log')->where($condition)->page($page)->select();
	}

}