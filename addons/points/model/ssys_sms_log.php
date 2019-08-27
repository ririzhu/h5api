<?php
/**
 * 手机短信记录
 * @since      File available since Release v1.1
 */
defined('DYMall') or exit('Access Invalid!');

class ssys_sms_logModel extends Model{

    public function __construct() {
        parent::__construct();
    }

    /**
     * 增加短信记录
     *
     * @param
     * @return int
     */
    public function addSms($log_array) {
        $log_id = $this->table('ssys_sms_log')->insert($log_array);
        return $log_id;
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
        $result = $this->table('ssys_sms_log')->where($condition)->order('log_id desc')->find();
        return $result;
    }

    /**
     * 查询记录
     *
     * @param
     * @return array
     */
    public function getSmsList($condition = array(), $page = '', $limit = '', $order = 'log_id desc') {
        $result = $this->table('ssys_sms_log')->where($condition)->page($page)->limit($limit)->order($order)->select();
        return $result;
    }
}
