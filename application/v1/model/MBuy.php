<?php
namespace app\V1\model;

use think\Model;
use think\db;
//手机专享
class MBuy extends Model
{
    const STATE1 = 1;       // 开启
    const STATE0 = 0;       // 关闭

    public function __construct() {
        parent::__construct();
    }

    /**
     * 手机专享套餐列表
     *
     * @param array $condition
     * @param string $field
     * @param int $page
     * @param string $order
     * @return array
     */
    public function getSoleQuotaList($condition, $field = '*', $page = 0, $order = 'mbuy_quota_id desc') {
        return $this->table('p_mbuy_quota')->field($field)->where($condition)->order($order)->page($page)->select();
    }

    /**
     * 手机专享套餐详细信息
     *
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getSoleQuotaInfo($condition, $field = '*') {
        return $this->table('p_mbuy_quota')->field($field)->where($condition)->find();
    }

    /**
     * 通过的套餐详细信息
     *
     * @param int $vid
     * @param string $field
     * @return array
     */
    public function getSoleQuotaInfoCurrent($vid) {
        $condition['vid'] = $vid;
        $condition['mbuy_quota_endtime'] = array('gt', TIMESTAMP);
        $condition['mbuy_state'] = self::STATE1;
        return $this->getSoleQuotaInfo($condition);
    }

    /**
     * 保存手机专享套餐
     *
     * @param array $insert
     * @param boolean $replace
     * @return boolean
     */
    public function addSoleQuota($insert, $replace = false) {
        return $this->table('p_mbuy_quota')->insert($insert, $replace);
    }

    /**
     * 编辑手机专享套餐
     * @param array $update
     * @param array $condition
     * @return array
     */
    public function editSoleQuota($update, $condition) {
        return $this->table('p_mbuy_quota')->where($condition)->update($update);
    }

    /**
     * 编辑手机专享套餐
     * @param array $update
     * @param array $condition
     * @return array
     */
    public function editSoleQuotaOpen($update, $condition) {
        $update['mbuy_state'] = self::STATE1;
        return $this->table('p_mbuy_quota')->where($condition)->update($update);
    }

    /**
     * 商品列表
     *
     * @param array $condition
     * @param string $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return array
     */
    public function getSoleGoodsList($condition, $field = '*', $page = 0, $limit = 0, $order = 'mbuy_gid asc') {
        return $this->table('bbc_p_mbuy_goods')->field($field)->where($condition)->limit($limit)->order($order)->page($page)->select();
    }

    /**
     * 获取手机专享商品详细信息
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getSoleGoodsInfo($condition, $field = '*') {
        return $this->table('bbc_p_mbuy_goods')->field($field)->where($condition)->find();
    }

    /**
     * 取得商品详细信息（优先查询缓存）
     * 如果未找到，则缓存所有字段
     * @param int $gid
     * @return array
     */
    public function getSoleGoodsInfoOpenByGoodsID($gid) {
        $goods_info = $this->_rGoodsSoleCache($gid);
        if (empty($goods_info)) {
            $goods_info = $this->getSoleGoodsInfo(array('gid'=>$gid, 'mbuy_state' => self::STATE1));
            $this->_wGoodsSoleCache($gid, $goods_info);
        }
        return $goods_info;
    }

    /**
     * 保存手机专享商品信息
     * @param array $insert
     * @return boolean
     */
    public function addSoleGoods($insert) {
        // 获取 goods_common_id
        $goods_info = Model('goods')->getGoodsInfoByID($insert['gid'],'goods_commonid');

        // 锁定商品
        $lock_condition = array();
        $lock_condition['goods_commonid'] = $goods_info['goods_commonid'];
        Model('goods')->editGoodsCommonLock($lock_condition);

        $return = $this->table('p_mbuy_goods')->insert($insert);

        //更新缓存
        $this->update_activity_cache();

        return $return;
    }

    /**
     * 更新手机专享商品信息
     */
    public function editSoleGoods($update, $condition) {
        $solegoods_list = $this->getSoleGoodsList($condition);
        if (empty($solegoods_list)) {
            return true;
        }
        $goodsid_array = array();
        foreach ($solegoods_list as $val) {
            $goodsid_array[] = $val['gid'];
        }
        $result = $this->table('p_mbuy_goods')->where(array('gid' => array('in', $goodsid_array)))->update($update);
        if ($result) {
            foreach ($goodsid_array as $val) {
                $this->_dGoodsSoleCache($val);
            }
        }

        //更新缓存
        $this->update_activity_cache();

        return $result;
    }

    /**
     * 更新套餐为关闭状态
     * @param array $condition
     * @return boolean
     */
    public function editSoleClose($condition) {
        $quota_list = $this->getSoleQuotaList($condition);
        if (empty($quota_list)) {
            return true;
        }
        $storeid_array = array();
        foreach ($quota_list as $val) {
            $storeid_array[] = $val['vid'];
        }
        $where = array('vid' => array('in', $storeid_array));
        $update = array('mbuy_state' => self::STATE0);
        $this->editSoleQuota($update, $where);
        $this->editSoleGoods($update, $where);
        return true;
    }

    /**
     * 删除手机专享商品
     *
     * @param unknown $condition
     * @return boolean
     */
    public function delSoleGoods($condition) {
        // 获取 goods_common_id
        $goods_info = Model('goods')->getGoodsInfoByID($condition['gid'],'goods_commonid');

        // 锁定商品
        $lock_condition = array();
        $lock_condition['goods_commonid'] = $goods_info['goods_commonid'];
        Model('goods')->editGoodsCommonUnlock($lock_condition);

        $return = $this->table('p_mbuy_goods')->where($condition)->delete();

        //更新缓存
        $this->update_activity_cache();

        return $return;
    }

    /**
     * 读取商品限时折扣缓存
     * @param int $gid
     * @return array/bool
     */
    private function _rGoodsSoleCache($gid) {
        return rcache($gid, 'goods_sole');
    }

    /**
     * 写入商品限时折扣缓存
     * @param int $gid
     * @param array $info
     * @return boolean
     */
    private function _wGoodsSoleCache($gid, $info) {
        return wcache($gid, $info, 'goods_sole');
    }

    /**
     * 删除商品限时折扣缓存
     * @param int $gid
     * @return boolean
     */
    private function _dGoodsSoleCache($gid) {
        return dcache($gid, 'goods_sole');
    }

    // 更新缓存
    public function update_activity_cache(){
        //文件缓存
        $p_mbuy_list=$this->getSoleGoodsList_gid();
        if(!empty($p_mbuy_list)){
            $activity_cache=Model('activity_cache');
            $activity_cache->Activity('p_mbuy',$p_mbuy_list);
        }
    }

    //手机端专享缓存
    public function getSoleGoodsList_gid($condition, $field = '*', $limit = 0, $order = 'mbuy_gid desc') {
        return DB::table('bbc_p_mbuy_goods')->field($field)->where($condition)->limit(1000)->order($order)->select();
    }
}