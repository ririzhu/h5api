<?php
namespace app\v1\model;

use think\Model;
use think\db;
class TousuGoods extends Model
{
    /*
     * 构造条件
     */
    private function getCondition($condition){
        $condition_str = '' ;
        if(!empty($condition['complain_id'])) {
            $condition_str.= " and  complain_id = '{$condition['complain_id']}'";
        }
        return $condition_str;
    }

    /*
     * 增加
     * @param array $param
     * @return bool
     */
    public function saveComplainGoods($param){
        //QueueClient::push('sendStoreMsg', array('code' => 'tousu', 'vid' => $param['vid'], 'param' => array('complain_id'=>$param['complain_id'])));

        return db::name("tousu_goods")->insert($param);

    }

    /*
     * 更新
     * @param array $update_array
     * @param array $where_array
     * @return bool
     */
    public function updateComplainGoods($update_array, $where_array){

        $where = $this->getCondition($where_array) ;
        return Db::update('tousu_goods',$update_array,$where) ;

    }

    /*
     * 删除
     * @param array $param
     * @return bool
     */
    public function dropComplainGoods($param){

        $where = $this->getCondition($param) ;
        return Db::delete('tousu_goods', $where) ;

    }

    /*
     *  获得列表
     *  @param array $condition
     *  @param obj $page 	//分页对象
     *  @return array
     */
    public function getComplainGoods($condition='',$page='') {

        $param = array() ;
        $param['table'] = 'tousu_goods' ;
        $param['where'] = $this->getCondition($condition);
        $param['order'] = $condition['order'] ? $condition['order']: ' complain_gid desc ';
        return Db::select($param,$page);
    }

    /*
     *   根据id获取投诉商品详细信息
     */
    public function getoneComplainGoods($complain_goods_id) {

        $param = array() ;
        $param['table'] = 'tousu_goods';
        $param['field'] = 'complain_goods_id' ;
        $param['value'] = intval($complain_goods_id);
        return Db::getRow($param) ;

    }
}