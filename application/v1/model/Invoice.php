<?php
namespace app\v1\model;

use think\Model;
use think\db;
class Invoice extends Model
{
    public function __construct() {
        parent::__construct('invoice');
    }

    /**
     * 取得买家默认发票
     *
     * @param array $condition
     */
    public function getDefaultInvInfo($condition = array()) {
        return DB::name("invoice")->where($condition)->order('inv_state asc')->find();
    }

    /**
     * 取得单条发票信息
     *
     * @param array $condition
     */
    public function getInvInfo($condition = array()) {
        return $this->where($condition)->find();
    }

    /**
     * 取得发票列表
     *
     * @param unknown_type $condition
     * @return unknown
     */
    public function getInvList($condition, $limit = '') {
        return $this->where($condition)->limit($limit)->select();
    }

    /**
     * 删除发票信息
     *
     * @param unknown_type $condition
     * @return unknown
     */
    public function delInv($condition) {
        return $this->where($condition)->delete();
    }

    /**
     * 新增发票信息
     *
     * @param unknown_type $data
     * @return unknown
     */
    public function addInv($data) {
        return $this->insert($data);
    }

    public $invoice_content_list = array(
        '明细',
        '酒',
        '食品',
        '饮料',
        '玩具',
        '日用品',
        '装修材料',
        '化妆品',
        '办公用品',
        '学生用品',
        '家居用品',
        '饰品',
        '服装',
        '箱包',
        '精品',
        '家电',
        '劳防用品',
        '耗材',
        '电脑配件'
    );
}