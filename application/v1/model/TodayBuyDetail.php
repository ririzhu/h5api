<?php
namespace app\V1\model;

use think\Model;
use think\db;
class TodayBuyDetail
{
    /**
     * 添加
     *
     * @param array $input
     * @return bool
     */
    public function add($input){
        return Db::insert('today_buy_detail',$input);
    }
    /**
     * 更新
     *
     * @param array $input 更新内容
     * @param string $id 活动内容id
     * @return bool
     */
    public function update($input,$id){
        return Db::update('today_buy_detail',$input,'today_buy_detail_id in('.$id.')');
    }
    /**
     * 根据条件更新
     *
     * @param array $input 更新内容
     * @param array $condition 更新条件
     * @return bool
     */
    public function updateList($input,$condition){
        return Db::update('today_buy_detail',$input,$this->getCondition($condition));
    }
    /**
     * 删除
     *
     * @param string $id
     * @return bool
     */
    public function del($id){
        return Db::delete('today_buy_detail','today_buy_time_id in('.$id.')');
    }
    /**
     * 根据条件删除
     *
     * @param array $condition 条件数组
     * @return bool
     */
    public function delList($condition){
        return Db::delete('today_buy_detail',$this->getCondition($condition));
    }
    /**
     * 根据条件查询活动内容信息
     *
     * @param array $condition 查询条件数组
     * @param obj $page	分页对象
     * @return array 二维数组
     */
    public function getList($condition,$page=''){
        $param	= array();
        $param['table']	= 'bbc_today_buy_detail';
        $param['where']	= $this->getCondition($condition);
        if(isset($condition['order']))
        $param['order']	= $condition['order'];
        return Db::table("bbc_today_buy_detail")->select($param,$page);
    }




    /**
     * 根据条件查询活动商品内容信息
     *
     * @param array $condition 查询条件数组
     * @param obj $page	分页对象
     * @return array 二维数组
     */
    public function getGoodsJoinList($condition,$page=''){
        $param	= array();
        $param['table']	= 'today_buy_detail,goods';
        $param['join_type']	= 'inner join';
        $param['field']	= 'today_buy_detail.*,goods.*';
        $param['join_on']	= array('today_buy_detail.item_id=goods.gid');
        $param['where']	= $this->getCondition($condition);
        $param['order']	= $condition['order'];
        return Db::select($param,$page);
    }
    /**
     * 查询活动商品信息
     *
     * @param array $condition 查询条件数组
     * @param obj $page	分页对象
     * @return array 二维数组
     */
    public function getGoodsList($condition,$page=''){
        $param	= array();
        $param['table']	= 'huodong_detail,goods';
        $param['join_type']	= 'inner join';
        $param['field']	= 'huodong_detail.huodong_detail_sort,goods.gid,goods.vid,goods.goods_name,goods.goods_price,goods.goods_image';
        $param['join_on']	= array('huodong_detail.item_id=goods.gid');
        $param['where']	= $this->getCondition($condition);
        $param['order']	= $condition['order'];
        return Db::select($param,$page);
    }
    /**
     * 构造查询条件
     *
     * @param array $condition 查询条件数组
     * @return string
     */
    private function getCondition($condition){
        $conditionStr	= '';
        if(isset($condition['today_buy_time_id']) && $condition['today_buy_time_id']>0){
            $conditionStr	.= " and today_buy_detail.today_buy_time_id = '{$condition['today_buy_time_id']}'";
        }
        if (isset($condition['today_buy_detail_id_in'])){
            if ($condition['today_buy_detail_id_in'] == ''){
                $conditionStr	.= " and today_buy_detail_id in ('')";
            }else{
                $conditionStr	.= " and today_buy_detail_id in ({$condition['today_buy_detail_id_in']})";
            }
        }
        if(isset($condition['today_buy_detail_state_in'])){
            if ($condition['today_buy_detail_state_in'] == ''){
                $conditionStr	.= " and today_buy_detail_state in ('')";
            }else{
                $conditionStr	.= " and today_buy_detail_state in ({$condition['today_buy_detail_state_in']})";
            }
        }
        if($condition['today_buy_detail_state'] != ''){
            $conditionStr	.= " and today_buy_detail.today_buy_detail_state='".$condition['today_buy_detail_state']."'";
        }
        if(isset($condition['gc_id']) && $condition['gc_id'] != ''){
            $conditionStr	.= " and goods.gc_id='{$condition['gc_id']}'";
        }
        if(isset($condition['brand_id']) && $condition['brand_id'] != ''){
            $conditionStr	.= " and goods.brand_id='{$condition['brand_id']}' ";
        }
        if(isset($condition['name']) && $condition['name'] != ''){
            $conditionStr	.= " and goods.goods_name like '%{$condition['name']}%'";
        }
        if(intval($condition['item_id'])>0){
            $conditionStr	.= " and today_buy_detail.item_id='".intval($condition['item_id'])."'";
        }
        if(isset($condition['item_name']) && $condition['item_name'] != ''){
            $conditionStr	.= " and today_buy_detail.item_name like '%{$condition['item_name']}%'";
        }
        if(isset($condition['vid']) && intval($condition['vid'])>0){
            $conditionStr	.= " and today_buy_detail.vid='".intval($condition['vid'])."'";
        }
        if(isset($condition['store_name']) && $condition['store_name'] != ''){
            $conditionStr	.= " and today_buy_detail.store_name like '%{$condition['store_name']}%'";
        }
        /*if ($condition_array['goods_show'] != '') {
            $condition_sql	.= " and goods.goods_show= '{$condition_array['goods_show']}'";
        }*/
        return $conditionStr;
    }
}