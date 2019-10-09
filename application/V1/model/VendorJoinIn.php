<?php
namespace app\v1\model;

use think\Model;
use think\db;
class VendorJoinIn extends Model
{


    /**
     * 读取列表
     * @param array $condition
     *
     */
    public function getList($condition,$page='',$order='',$field='*'){
        $result = $this->table('vendor_joinin')->field($field)->where($condition)->page($page)->order($order)->select();
        return $result;
    }

    /**
     * 店铺入住数量
     * @param unknown $condition
     */
    public function getStoreJoininCount($condition) {
        return  $this->where($condition)->count();
    }

    /**
     * 读取单条记录
     * @param array $condition
     *
     */
    public function getOne($condition){
        $result = db::name("vendor_joinin")->where($condition)->find();
        //echo db::name("vendor_joinin")->getLastSql();
        return $result;
    }

    /*
     *  判断是否存在
     *  @param array $condition
     *
     */
    public function isExist($condition) {
        $result = $this->getOne($condition);
        if(empty($result)) {
            return FALSE;
        }
        else {
            return TRUE;
        }
    }




    /*
     * 更新
     * @param array $update
     * @param array $condition
     * @return bool
     */
    public function modify($update, $condition){
        return db::name("vendor_joinin")->where($condition)->update($update);
    }


    /**
     * 编辑
     * @param array $condition
     * @param array $update
     * @return bool
     */
    public function editStoreJoinin($condition, $update) {
        return $this->where($condition)->save($update);
    }
}