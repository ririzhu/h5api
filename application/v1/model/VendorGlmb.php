<?php
namespace app\v1\model;

use think\Model;

class VendorGlmb extends Model
{
    public function __construct(){
        parent::__construct('vendor_glmb');
    }

    /**
     * 版式列表
     * @param array $condition
     * @param string $field
     * @param int $page
     * @return array
     */
    public function getPlateList($condition, $field = '*', $page = 0) {
        return $this->field($field)->where($condition)->page($page)->select();
    }

    /**
     * 版式详细信息
     * @param array $condition
     * @return array
     */
    public function getPlateInfo($condition) {
        return $this->where($condition)->find();
    }

    /**
     * 添加版式
     * @param unknown $insert
     * @return boolean
     */
    public function addPlate($insert) {
        return $this->insert($insert);
    }

    /**
     * 更新版式
     * @param array $update
     * @param array $condition
     * @return boolean
     */
    public function editPlate($update, $condition) {
        return $this->where($condition)->update($update);
    }

    /**
     * 删除版式
     * @param array $condition
     * @return boolean
     */
    public function delPlate($condition) {
        return $this->where($condition)->delete();
    }
}