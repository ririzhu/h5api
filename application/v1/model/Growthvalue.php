<?php
/**
 * Created by DYMall.
 * User: test
 * Date: 2016/9/4
 * Time: 14:53
 */
namespace app\v1\model;

use think\Model;
use think\Db;

class Growthvalue extends Model {
    /**
     * 操作经验值
     * @param  string $stage 操作阶段 login(登录),comments(评论),order(下单)
     * @param  array $insertarr 该数组可能包含信息 array('growth_memberid'=>'会员编号','growth_membername'=>'会员名称','growth_points'=>'经验值','growth_desc'=>'描述','orderprice'=>'订单金额','order_sn'=>'订单编号','order_id'=>'订单序号');
     * @param  bool $if_repeat 是否可以重复记录的信息,true可以重复记录，false不可以重复记录，默认为true
     * @return bool
     */
    function saveGrowthValue($stage,$insertarr){
        if (!$insertarr['growth_memberid']){
            return false;
        }
        $growthvalue_rule = config("growthvalue_rule")?unserialize(config("growthvalue_rule")):array();
        //记录原因文字
        switch ($stage){
            case 'login':
                if (!isset($insertarr['growth_desc'])){
                    $insertarr['growth_desc'] = '会员登录';
                }
                $insertarr['growth_points'] = 0;
                if (intval($growthvalue_rule['growth_login']) > 0){
                    $insertarr['growth_points'] = intval($growthvalue_rule['growth_login']);
                }
                break;
            case 'comments':
                if (!$insertarr['growth_desc']){
                    $insertarr['growth_desc'] = '评论商品';
                }
                $insertarr['growth_points'] = 0;
                if (intval($growthvalue_rule['growth_comments']) > 0){
                    $insertarr['growth_points'] = intval($growthvalue_rule['growth_comments']);
                }
                break;
            case 'order':
                if (!$insertarr['growth_desc']){
                    $insertarr['growth_desc'] = '订单'.$insertarr['order_sn'].'购物消费';
                }
                $insertarr['growth_points'] = 0;
                $growthvalue_rule['growth_orderrate'] = floatval($growthvalue_rule['growth_orderrate']);
                if ($insertarr['orderprice'] && $growthvalue_rule['growth_orderrate'] > 0){
                    $insertarr['growth_points'] = @intval($insertarr['orderprice']/$growthvalue_rule['growth_orderrate']);
                    $growth_ordermax = intval($growthvalue_rule['growth_ordermax']);
                    if ($growth_ordermax > 0 && $insertarr['growth_points'] > $growth_ordermax){
                        $insertarr['growth_points'] = $growth_ordermax;
                    }
                }
                break;
        }
        //新增日志
        $value_array = array();
        $value_array['growth_memberid'] = $insertarr['growth_memberid'];
        $value_array['growth_membername'] = $insertarr['growth_membername'];
        $value_array['growth_points'] = $insertarr['growth_points'];
        $value_array['growth_addtime'] = time();
        $value_array['growth_desc'] = $insertarr['growth_desc'];
        $value_array['growth_stage'] = $stage;
        $result = false;
        if($value_array['growth_points'] != '0'){
            $result = self::addExppointsLog($value_array);
        }
        if ($result){
            //更新member内容
            $member = new User();
            $upmember_array = array();
            $upmember_array['member_growthvalue'] = array('exp','member_growthvalue + '.$insertarr['growth_points']);
//            $member->editMember(array('member_id'=>$insertarr['growth_memberid']),$upmember_array); //zhengyifan注释 2019-09-12
            $res = DB::name('member')->where('member_id',$insertarr['growth_memberid'])->setInc('member_growthvalue',$insertarr['growth_points']); //zhengyifan添加的 2019-09-12
            if ($res){
                return true;
            }else{
                return false;
            }
        }else {
            return false;
        }
    }
    /**
     * 添加经验值日志信息
     *
     * @param array $param 添加信息数组
     */
    public function addExppointsLog($param) {
        if(empty($param)) {
            return false;
        }
//        $result = $this->table('growthvalue_log')->insert($param);
        $result = DB::name('growthvalue_log')->insert($param);
        return $result;
    }

    /**
     * 经验值日志总条数
     *
     * @param array $where 条件数组
     * @param array $field   查询字段
     * @param array $group   分组
     */
    public function getExppointsLogCount($where, $field = '*', $group = '') {
        $count = $this->table('growthvalue_log')->field($field)->where($where)->group($group)->count();
        return $count;
    }

    /**
     * 经验值日志列表
     *
     * @param array $where 条件数组
     * @param mixed $page   分页
     * @param string $field   查询字段
     * @param int $limit   查询条数
     * @param string $order   查询条数
     */
    public function getGrowthValueList($where, $field = '*', $page = 0, $limit = 0,$order = '', $group = '') {
        if (is_array($page)){
            if ($page[1] > 0){
                return $this->table('growthvalue_log')->field($field)->where($where)->page($page[0],$page[1])->order($order)->group($group)->select();
            } else {
                return $this->table('growthvalue_log')->field($field)->where($where)->page($page[0])->order($order)->group($group)->select();
            }
        } else {
            return $this->table('growthvalue_log')->field($field)->where($where)->page($page)->order($order)->group($group)->select();
        }
    }


    public function getGrowthValueList_new($condition,$page=''){
        $param	= array();
        $param['table']	= 'growthvalue_log';
        $param['where']	= $this->getCondition($condition);
        $param['order']	= $condition['order'];
        return Db::select($param,$page);
    }

    /**
     * 获得阶段说明文字
     */
    public function getStage(){
        $stage_arr = array();
        $stage_arr['login'] = '会员登录';
        $stage_arr['comments'] = '商品评论';
        $stage_arr['order'] = '订单消费';
        return $stage_arr;
    }

    /**
     * 构造查询条件
     *
     * @param array $condition 查询条件数组
     * @return string
     */
    private function getCondition($condition){
        $conditionStr	= '1';
        if($condition['growth_memberid']>0){
            $conditionStr	.= " and growthvalue_log.growth_memberid = '{$condition['growth_memberid']}'";
        }
        if($condition['growth_membername'] != ''){
            $conditionStr	.= " and growthvalue_log.growth_membername like '%{$condition['growth_membername']}%'";
        }
        return $conditionStr;
    }
}
