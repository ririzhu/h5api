<?php
namespace app\v1\model;

use think\Model;

class TodayBuy
{
    /**
     * 活动列表
     *
     * @param array $condition 查询条件
     * @param obj $page 分页对象
     * @return array 二维数组
     */
    public function getList($condition,$page=''){
        $param	= array();
        $param['table']	= 'bbc_today_buy';
        $param['where']	= $this->getCondition($condition);
        $param['order']	= $condition['order'] ? $condition['order'] : 'today_buy_id';
        return Db::select($param,$page);
    }
    /**
     * 添加活动
     *
     * @param array $input
     * @return bool
     */
    public function add($input){
        return Db::insert('bbc_today_buy',$input);
    }
    /**
     * 添加抢购-时间点
     *
     * @param array $input
     * @return bool
     */
    public function add_time($input){
        return Db::insert('bbc_today_buy_time',$input);
    }
    /**
     * 查询抢购-时间点
     *
     * @param array $input
     * @return bool
     */
    public function getList_time($condition,$page=''){
        $param	= array();
        $param['table']	= 'bbc_today_buy_time';
        $param['where']	= $this->getCondition($condition);
        $param['order']	= $condition['order'] ? $condition['order'] : 'today_buy_time_id';
        return Db::select($param,$page);
    }
    /**
     * 更新活动
     *
     * @param array $input
     * @param int $id
     * @return bool
     */
    public function update($input,$id){
        return Db::update('bbc_today_buy',$input," today_buy_id='$id' ");
    }
    /**
     *今日抢购状态设置
     *
     * @param array $input
     * @param int $id
     * @return bool
     */
    public function update_time($input,$id){
        return Db::update('bbc_today_buy_time',$input," today_buy_time_id='$id' ");
    }
    /**
     * 删除活动
     *
     * @param string $id
     * @return bool
     */
    public function del($id){
        return Db::delete('bbc_today_buy','today_buy_id in('.$id.')');
    }
    /**
     * 删除today_buy_time里面的数据
     *
     * @param string $id
     * @return bool
     */
    public function del_time($id){
        return Db::delete('bbc_today_buy_time','today_buy_time_id in('.$id.')');
    }
    /**
     * 根据id查询一条活动
     *
     * @param int $id 活动id
     * @return array 一维数组
     */
    public function getOneById($id){
        return Db::getRow(array('table'=>'bbc_today_buy','field'=>'today_buy_id','value'=>$id));
    }
    /**
     * 根据当前日期查询一条活动
     *
     * @param int $date  抢购日期
     * @return array 一维数组
     */
    public function getOneBydate($date){
        return Db::getRow(array('table'=>'bbc_today_buy','field'=>'today_buy_date','value'=>$date));
    }
    /**
     * 根据抢购日期id查询所有的日期时间点
     *
     * @param int $time  抢购时间点
     * @return array 一维数组
     */
    public function getallBytime($condition,$page=''){
        $param	= array();
        $param['table']	= 'today_buy_time';
        $param['where']	= $this->getCondition($condition);
        $param['order']	= $condition['order'] ? $condition['order'] : 'today_buy_time_id';
        return Db::select($param,$page);
    }
    /**
     * 商户中心
     * 根据id查询一条活动
     *
     * @param int $id 活动id
     * @return array 一维数组
     */
    public function getOneById_time($id){
        return Db::getRow(array('table'=>'bbc_today_buy_time','field'=>'today_buy_time_id','value'=>$id));
    }
    /**
     * 根据条件
     *
     * @param array $condition 查询条件
     * @param obj $page 分页对象
     * @return array 二维数组
     */
    public function getJoinList($condition,$page=''){
        $param	= array();
        $param['table']	= 'huodong,huodong_detail';
        $param['join_type']	= empty($condition['join_type'])?'right join':$condition['join_type'];
        $param['join_on']	= array('huodong.hid=huodong_detail.hid');
        $param['where']	= $this->getCondition($condition);
        $param['order']	= $condition['order'];
        return Db::select($param,$page);
    }
    /**
     * 构造查询条件
     *
     * @param array $condition 条件数组
     * @return string
     */
    private function getCondition($condition){
        $conditionStr	= '';
        if($condition['today_buy_id'] != ''){
            $conditionStr	.= " and today_buy_id='{$condition['today_buy_id']}' ";
        }
        if($condition['today_buy_state'] != ''){
            $conditionStr	.= " and today_buy.today_buy_state = '{$condition['today_buy_state']}' ";
        }

        if($condition['today_buy_time_state'] != ''){
            $conditionStr	.= " and today_buy_time.today_buy_time_state = '{$condition['today_buy_time_state']}' ";
        }
        if($condition['today_buy_title'] != ''){
            $conditionStr	.= " and today_buy.today_buy_title like '%{$condition['today_buy_title']}%' ";
        }
        if($condition['today_buy_date'] != ''){
            $time = date("Y-m-d",$condition['today_buy_date']);
            $conditionStr	.= " and today_buy.today_buy_date = '$time' ";
        }
        //可删除的活动记录
        if ($condition['activity_enddate_greater_or'] != ''){
            $conditionStr	.= " or huodong.huodong_end_date < '{$condition['activity_enddate_greater_or']}'";
        }
        //某时间段内正在进行的活动
        if($condition['activity_daterange'] != ''){
            $conditionStr .= " and (huodong.huodong_end_date >= '{$condition['activity_daterange']['startdate']}' and huodong.huodong_start_date <= '{$condition['activity_daterange']['enddate']}')";
        }
        if($condition['opening']){//在有效期内、活动状态为开启
            $conditionStr	.= " and (huodong.huodong_start_date <=".time()." and huodong.huodong_end_date >= ".time()." and huodong.huodong_state =1)";
        }
        return $conditionStr;
    }
}