<?php
/**
 * 统计管理
 *
 */
defined('DYMall') or exit('Access Invalid!');
class ssys_statistics_logCtl extends SystemCtl{
	
	public function __construct(){
		parent::__construct();
	}

	public function getAllList(){

        $ssys_s_log = M('ssys_statistics_log');
        $condition  = array();
        $search = array();
        //分页检索条件(如果传值 按照传的值 否则默认10页)
        $pageSize = intval($_GET['pageSize'] > 0 ? $_GET['pageSize'] : 10);
        $_GET['pn'] = intval($_GET['currentPage'] > 0 ? $_GET['currentPage'] : 1);

        $if_start_time = preg_match('/^20\d{2}-\d{2}-\d{2}$/',$_GET['query_start_time']);
        $if_end_time = preg_match('/^20\d{2}-\d{2}-\d{2}$/',$_GET['query_end_time']);
        $start_unixtime = $if_start_time ? strtotime($_GET['query_start_time']) : null;
        $end_unixtime = $if_end_time ? strtotime($_GET['query_end_time']): null;
        if ($start_unixtime || $end_unixtime) {
            $search['query_start_time'] = $_GET['query_start_time'];
            $search['query_end_time'] = $_GET['query_end_time'];
            $condition['log_time'] = array('BETWEEN',array($start_unixtime,$end_unixtime));
        }

        // 获取今日统计记录
        $day_condition['log_time'] = strtotime(date("Y-m-d",time()));
        $today_log_list = $ssys_s_log->getAllList($day_condition);

        $today_log_info = array();
        if (!empty($today_log_list)) {
            $today_log_info = $today_log_list[0];
            $today_log_info['log_time_str'] = date('Y-m-d',$today_log_info['log_time']);
        }

        $data_array = $ssys_s_log->getAllList($condition, $this->page);

        foreach ($data_array as $key => $value) {
        	$value['log_time_str'] = date('Y-m-d',$value['log_time']);
        	$data_array[$key] = $value;
        }

        $page_count = $ssys_s_log->gettotalpage();

        echo json_encode(array('list' => $data_array, 'today_list'=>$today_log_info, 'pagination' => array('current' => $_GET['pn'], 'pageSize' => $pageSize, 'total' => intval($ssys_s_log->gettotalnum())),'searchlist'=>$search));
	}

}