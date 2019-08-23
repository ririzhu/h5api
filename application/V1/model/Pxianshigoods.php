<?php
namespace app\V1\model;

use think\Model;
use think\db;
class Pxianshigoods extends Model
{
    const XIANSHI_GOODS_STATE_CANCEL = 0;
    const XIANSHI_GOODS_STATE_NORMAL = 1;

    public function __construct(){
        parent::__construct('p_xianshi_goods');
    }

    /**
     * 读取限时折扣商品列表
     * @param array $condition 查询条件
     * @param int $page 分页数
     * @param string $order 排序
     * @param string $field 所需字段
     * @param int $limit 个数限制
     * @return array 限时折扣商品列表
     *
     */
    public function getXianshiGoodsList($condition, $page=null, $order='', $field='*', $limit = 0) {
        $xianshi_goods_list = DB::table("bbc_p_xianshi_goods")->field($field)->where($condition)->page($page)->order($order)->limit($limit)->select();
        if(!empty($xianshi_goods_list)) {
            for($i=0, $j=count($xianshi_goods_list); $i < $j; $i++) {
                $xianshi_goods_list[$i] = $this->getXianshiGoodsExtendInfo($xianshi_goods_list[$i]);
            }
        }
        return $xianshi_goods_list;
    }

    /**
     * 根据条件读取限制折扣商品信息
     * @param array $condition 查询条件
     * @return array 限时折扣商品信息
     *
     */
    public function getXianshiGoodsInfo($condition) {
        $result = $this->where($condition)->find();
        return $result;
    }

    /**
     * 根据限时折扣商品编号读取限制折扣商品信息
     * @param int $xianshi_goods_id
     * @return array 限时折扣商品信息
     *
     */
    public function getXianshiGoodsInfoByID($xianshi_goods_id, $vid = 0) {
        if(intval($xianshi_goods_id) <= 0) {
            return null;
        }

        $condition = array();
        $condition['xianshi_gid'] = $xianshi_goods_id;
        $xianshi_goods_info = $this->getXianshiGoodsInfo($condition);

        if($vid > 0 && $xianshi_goods_info['vid'] != $vid) {
            return null;
        } else {
            return $xianshi_goods_info;
        }
    }

    /*
     * 增加限时折扣商品
     * @param array $xianshi_goods_info
     * @return bool
     *
     */
    public function addXianshiGoods($xianshi_goods_info){
        // 获取 goods_common_id
        $goods_info = Model('goods')->getGoodsInfoByID($xianshi_goods_info['gid'],'goods_commonid');

        // 锁定商品
        $lock_condition = array();
        $lock_condition['goods_commonid'] = $goods_info['goods_commonid'];
        Model('goods')->editGoodsCommonLock($lock_condition);

        $xianshi_goods_info['state'] = self::XIANSHI_GOODS_STATE_NORMAL;
        $xianshi_goods_id = $this->insert($xianshi_goods_info);
        $xianshi_goods_info['xianshi_gid'] = $xianshi_goods_id;
        $xianshi_goods_info = $this->getXianshiGoodsExtendInfo($xianshi_goods_info);

        //更新缓存
        $this->update_activity_cache();

        return $xianshi_goods_info;
    }

    /*
	 * 更新
	 * @param array $update
	 * @param array $condition
	 * @return bool
     *
	 */
    public function editXianshiGoods($update, $condition){
        $return = $this->where($condition)->update($update);

        //更新缓存
        $this->update_activity_cache();

        return $return;
    }

    /*
     * 删除
     * @param array $condition
     * @return bool
     *
     */
    public function delXianshiGoods($condition){
        // 获取商品ID
        $xianshi_goods_list = $this->getXianshiGoodsList($condition);

        $goods_commonids = array();
        // 获取所有商品的 goods_commonid
        foreach ($xianshi_goods_list as $key => $value) {
            if ($value['goods_info'] && $value['goods_info']['goods_commonid']) {
                $goods_commonids[] = $value['goods_info']['goods_commonid'];
            }
        }

        if (!empty($goods_commonids)) {
            $goods_commonids = array_flip($goods_commonids);
            $goods_commonids = array_flip($goods_commonids);
            $goods_commonids = array_values($goods_commonids);

            $unlock_condition['goods_commonid'] = array("IN",$goods_commonids);
            Model('goods')->editGoodsCommonUnlock($unlock_condition);
        }

        $return = $this->where($condition)->delete();

        //更新缓存
        $this->update_activity_cache();

        return $return;
    }

    /**
     * 获取限时折扣商品扩展信息
     * @param array $xianshi_info
     * @return array 扩展限时折扣信息
     *
     */
    public function getXianshiGoodsExtendInfo($xianshi_info) {
        $xianshi_info['goods_url'] = urlShop('goods', 'index', array('gid' => $xianshi_info['gid']));
        $xianshi_info['image_url'] = cthumb($xianshi_info['goods_image'], 60, $xianshi_info['vid']);
        $xianshi_info['xianshi_price'] = sldPriceFormat($xianshi_info['xianshi_price']);
        $xianshi_info['xianshi_discount'] = number_format($xianshi_info['xianshi_price'] / $xianshi_info['goods_price'] * 10, 1).'折';

        // 商品库存 进行进度
        $xianshi_info['goods_info'] = $goods_info = Model('goods')->getGoodsInfo(array('gid'=>$xianshi_info['gid']));
        $xianshi_info['goods_salenum'] = $goods_info['goods_salenum'];
        $xianshi_info['goods_storage'] =$goods_info['goods_storage'];
        return $xianshi_info;
    }

    /**
     * 获取推荐限时折扣商品
     * @param int $count 推荐数量
     * @return array 推荐限时活动列表
     *
     */
    public function getXianshiGoodsCommendList($count = 4) {
        $condition = array();
        $condition['state'] = self::XIANSHI_GOODS_STATE_NORMAL;
        $condition['start_time'] = array('lt', TIMESTAMP);
        $condition['end_time'] = array('gt', TIMESTAMP);
        $xianshi_list = $this->getXianshiGoodsList($condition, null, 'xianshi_recommend desc', '*', $count);
        return $xianshi_list;
    }

    /**
     * 根据商品编号查询是否有可用限时折扣活动，如果有返回限时折扣活动，没有返回null
     * @param int $gid
     * @return array $xianshi_info
     *
     */
    public function getXianshiGoodsInfoByGoodsID($gid) {
        $xianshi_goods_list = $this->_getXianshiGoodsListByGoods($gid);
        if(!empty($xianshi_goods_list)){
            return $xianshi_goods_list[0];
        }
        else{
            return null;
        }

    }

    /**
     * 根据商品编号查询是否有可用限时折扣活动，如果有返回限时折扣活动，没有返回null
     * @param string $goods_string 商品编号字符串，例：'1,22,33'
     * @return array $xianshi_goods_list
     *
     */
    public function getXianshiGoodsListByGoodsString($goods_string) {
        $xianshi_goods_list = $this->_getXianshiGoodsListByGoods($goods_string);
        $xianshi_goods_list = array_under_reset($xianshi_goods_list, 'gid');
        return $xianshi_goods_list;
    }

    /**
     * 根据商品编号查询是否有可用限时折扣活动，如果有返回限时折扣活动，没有返回null
     * @param string $goods_id_string
     * @return array $xianshi_info
     *
     */
    private function _getXianshiGoodsListByGoods($goods_id_string) {
        $condition = array();
        $condition['state'] = self::XIANSHI_GOODS_STATE_NORMAL;
        $condition['start_time'] = array('lt', TIMESTAMP);
        $condition['end_time'] = array('gt', TIMESTAMP);
        $condition['gid'] = array('in', $goods_id_string);
        $xianshi_goods_list = $this->getXianshiGoodsList($condition, null, 'xianshi_gid desc', '*');
        return $xianshi_goods_list;
    }

    // 更新缓存
    public function update_activity_cache(){
        //文件缓存
        $xianshi_list=$this->getXianshiGoodsList_gid();
        if(!empty($xianshi_list)){
            $activity_cache=Model('activity_cache');
            $activity_cache->Activity('xianshi',$xianshi_list);
        }
    }

    //缓存限时折扣
    public function getXianshiGoodsList_gid($condition,$order='end_time asc', $field='*', $limit = 10000) {
        $condition['start_time']=array('lt', TIMESTAMP);
        $condition['end_time']=array('gt', TIMESTAMP);
        $condition['state']=1;

        $xianshi_goods_list = $this->field($field)->where($condition)->order($order)->limit($limit)->select();

        if(!empty($xianshi_goods_list)) {
            for($i=0, $j=count($xianshi_goods_list); $i < $j; $i++) {
                $xianshi_goods_list[$i] = $this->getXianshiGoodsExtendInfo($xianshi_goods_list[$i]);
            }
        }
        return $xianshi_goods_list;
    }
}