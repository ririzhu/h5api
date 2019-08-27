<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/10/8
 * Time: 17:15
 */
class ldj_sms_logModel extends Model
{
    public function __construct()
    {
        parent::__construct('ldj_sms_log');
    }
    /**
     * 查询单条记录
     *
     * @param
     * @return array
     */
    public function getSmsInfo($condition) {
        if (empty($condition)) {
            return false;
        }
        $result = $this->where($condition)->order('log_id desc')->find();
        return $result;
    }
}