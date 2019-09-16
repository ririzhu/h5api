<?php
/**
 * 三级分销
 */
namespace app\v1\model;

use think\Model;
use think\Db;

class Fenxiao extends Model {

    /**
     * 添加佣金记录信息
     *
     * @param array $param 添加信息数组
     */
    public function addRecord($param) {
        if(empty($param)) {
            return false;
        }
        $result	= Db::insert('fenxiao_log',$param);
        return $result;
    }

    /**
     * 查询分销明细
     * @param $param
     * @param $field
     * @return array|\PDOStatement|string|\think\Collection
     */
    public function getCommissionInfo($param,$field = '*')
    {
        return DB::name('fenxiao_log')->field($field)->where($param)->select();
    }

    /**
     * @return 确认收货后修改佣金状态，将未结算状态修改为结算状态
     */
    public function editStatus($condition)
    {
        return $this->table('fenxiao_log')->where($condition)->update(array('status'=>1));
    }

    /**
     * @return 未结算佣金总金额
     * 获取所有status=0，reciver_member_id的佣金总金额。
     */
    public function getTotalFreeze($param)
    {
        $freeze_list = $this->table('fenxiao_log')->field('yongjin')->where($param)->select();
        return array_sum($freeze_list);

    }

}
