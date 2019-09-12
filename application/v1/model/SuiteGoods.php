<?php
namespace app\v1\model;

use think\Model;
use think\db;
class SuiteGoods extends Model
{
    /**
     * 插入数据
     *
     * @param unknown $insert
     * @return boolean
     */
    public function addComboGoodsAll($insert) {
        $result = $this->insertAll($insert);
        if ($result) {
            foreach ((array)$insert as $v) {
                if ($v['gid']) $this->_dComboGoodsCache($v['gid']);
            }
        }
        return $result;
    }

    /**
     * 查询组合商品列表
     * @param unknown $condition
     */
    public function getComboGoodsList($condition, $field = '*', $page = null, $order = 'cg_id desc') {
        return DB::table("bbc_p_suite_goods")->field($field)->where($condition)->order($order)->page($page)->select();
    }

    /**
     * 删除推荐组合商品
     */
    public function delComboGoods($condition) {
        $list = $this->getComboGoodsList($condition, 'gid');
        if (empty($list)) {
            return true;
        }
        $result = $this->where($condition)->delete();
        if ($result) {
            foreach ($list as $v) {
                $this->_dComboGoodsCache($v['gid']);
            }
        }
        return $result;
    }

    /**
     * 根据商品id删除推荐组合
     * @param unknown $gid
     */
    public function delComboGoodsByGoodsId($gid) {
        $this->where(array('gid' => $gid))->delete();
        return $this->_dComboGoodsCache($gid);
    }

    public function getComboGoodsCacheByGoodsId($gid) {
        $array = $this->_rComboGoodsCache($gid);
        if (empty($array)) {
            $array = array();
            $arr = array();
            $gcombo_list = array();
            $combo_list = $this->getComboGoodsList(array('gid' => $gid));
            if (!empty($combo_list)) {
                $comboid_array= array();
                foreach ($combo_list as $val) {
                    $comboid_array[] = $val['combo_goodsid'];
                }
                $gcombo_list = Model('goods')->getGeneralGoodsList(array('gid' => array('in', $comboid_array)));
                $gcombo_list = array_under_reset($gcombo_list, 'gid');
                foreach ($combo_list as $val) {
                    if (empty($gcombo_list[$val['combo_goodsid']])) {
                        continue;
                    }
                    $array[$val['cg_class']][] = $gcombo_list[$val['combo_goodsid']];
                }
                // $i = 1;
                foreach ($array as $key => $val) {
                    $gids_arr = low_array_column($val,'gid');
                    sort($gids_arr);
                    $now_gids_str = implode(',', $gids_arr);
                    $arr[$now_gids_str]['name'] = $key;
                    $arr[$now_gids_str]['goods'] = $val;
                    // $i++;
                }
                $arr = array_values($arr);
            }
            $array = array('gcombo_list' => serialize($arr));
            $this->_wComboGoodsCache($gid, $array);
        }
        return $array;
    }

    /**
     * 读取商品推荐搭配缓存
     * @param int $gid
     * @return array
     */
    private function _rComboGoodsCache($gid) {
        return rcache($gid, 'goods_combo');
    }

    /**
     * 写入商品推荐搭配缓存
     * @param int $gid
     * @param array $array
     * @return boolean
     */
    private function _wComboGoodsCache($gid, $array) {
        return wcache($gid, $array, 'goods_combo', 60);
    }

    /**
     * 删除商品推荐搭配缓存
     * @param int $gid
     * @return boolean
     */
    private function _dComboGoodsCache($gid) {
        return dcache($gid, 'goods_combo');
    }
}