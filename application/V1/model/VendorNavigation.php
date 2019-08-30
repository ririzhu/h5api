<?php
namespace app\V1\model;

use think\Model;
use think\db;
class VendorNavigation extends Model
{
    public function __construct(){
        parent::__construct('vendor_navigation');
    }

    /**
     * 读取列表
     * @param array $condition
     *
     */
    public function getStoreNavigationList($condition, $page='', $order='', $field='*') {
        $result = db::table('bbc_vendor_navigation')->field($field)->where($condition)->page($page)->order($order)->select();
        return $result;
    }

    /**
     * 读取单条记录
     * @param array $condition
     *
     */
    public function getStoreNavigationInfo($condition) {
        $result = $this->where($condition)->find();
        return $result;
    }

    /*
     * 增加
     * @param array $param
     * @return bool
     */
    public function addStoreNavigation($param){
        return $this->insert($param);
    }

    /*
     * 更新
     * @param array $update
     * @param array $condition
     * @return bool
     */
    public function editStoreNavigation($update, $condition){
        return $this->where($condition)->update($update);
    }

    /*
     * 删除
     * @param array $condition
     * @return bool
     */
    public function delStoreNavigation($condition){
        return $this->where($condition)->delete();
    }
}