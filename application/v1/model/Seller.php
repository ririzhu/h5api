<?php
namespace app\v1\model;

use think\Model;
use think\db;
class Seller extends Model
{
    public function __construct(){
        parent::__construct('seller');
    }

    /**
     * 读取列表
     * @param array $condition
     *
     */
    public function getSellerList($condition, $page='', $order='', $field='*') {
        $result = db::table("bbc_seller")->field($field)->where($condition)->page($page)->order($order)->select();
        return $result;
    }

    /**
     * 读取单条记录
     * @param array $condition
     *
     */
    public function getSellerInfo($condition) {
//        $result = $this->where($condition)->find();
        $result = DB::name("seller")->where($condition)->find();
        return $result;
    }

    /*
     *  判断是否存在
     *  @param array $condition
     *
     */
    public function isSellerExist($condition) {
        $result = $this->getSellerInfo($condition);
        if(empty($result)) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /*
     * 增加
     * @param array $param
     * @return bool
     */
    public function addSeller($param){
        return $this->insert($param);
    }

    /*
     * 更新
     * @param array $update
     * @param array $condition
     * @return bool
     */
    public function editSeller($update, $condition){
        return $this->where($condition)->update($update);
    }

    /*
     * 删除
     * @param array $condition
     * @return bool
     */
    public function delSeller($condition){
        return $this->where($condition)->delete();
    }
}