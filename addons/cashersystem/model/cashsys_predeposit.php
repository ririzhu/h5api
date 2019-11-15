<?php
/**
 * 预存款 收银扩展
 *
 */
defined('DYMall') or exit('Access Invalid!');
class cashsys_predepositModel extends Model {

	// 扩展数据添加
	public function addPdRecharge($data)
	{
		return $this->table('cashsys_pd_recharge')->insert($data);
	}

}