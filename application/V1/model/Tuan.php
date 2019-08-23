<?php
namespace app\V1\model;

use think\Model;
use think\db;
class Tuan extends Model
{
    const TUAN_STATE_REVIEW = 10;
    const TUAN_STATE_NORMAL = 20;
    const TUAN_STATE_REVIEW_FAIL = 30;
    const TUAN_STATE_CANCEL = 31;
    const TUAN_STATE_CLOSE = 32;

    private $tuan_state_array = array(
        0 => '全部',
        self::TUAN_STATE_REVIEW => '审核中',
        self::TUAN_STATE_NORMAL => '正常',
        self::TUAN_STATE_CLOSE => '已结束',
        self::TUAN_STATE_REVIEW_FAIL => '审核失败',
        self::TUAN_STATE_CANCEL => '管理员关闭',
    );

    public function __construct() {
        parent::__construct('tuan');
    }

    /**
     * 读取团购列表
     * @param array $condition 查询条件
     * @param int $page 分页数
     * @param string $order 排序
     * @param string $field 所需字段
     * @return array 团购列表
     *
     */
    public function getTuanList($condition, $page = null, $order = 'state asc', $field = '*', $limit = 0) {
        $tuan_list = DB::table("bbc_tuan")->field($field)->where($condition)->page($page)->order($order)->limit($limit)->select();
        if(!empty($tuan_list)) {
            for($i =0, $j = count($tuan_list); $i < $j; $i++) {
                $tuan_list[$i] = $this->getTuanExtendInfo($tuan_list[$i]);
            }
        }
        return $tuan_list;
    }

    /**
     * 读取可用团购列表
     */
    public function getTuanAvailableList($condition) {
        $condition['state'] = array('in', array(self::TUAN_STATE_REVIEW, self::TUAN_STATE_NORMAL));
        return $this->getTuanList($condition);
    }

    /**
     * 查询团购数量
     * @param array $condition
     * @return int
     */
    public function getTuanCount($condition) {
        return $this->where($condition)->count();
    }

    /**
     * 读取当前可用的团购列表
     * @param array $condition 查询条件
     * @param int $page 分页数
     * @param string $order 排序
     * @param string $field 所需字段
     * @return array 团购列表
     *
     */
    public function getTuanOnlineList($condition, $page = null, $order = 'state asc', $field = '*') {
        $condition['state'] = self::TUAN_STATE_NORMAL;
        $condition['start_time'] = array('lt', TIMESTAMP);
        $condition['end_time'] = array('gt', TIMESTAMP);
        return $this->getTuanList($condition, $page, $order, $field);
    }

    /**
     * 读取即将开始的团购列表
     * @param array $condition 查询条件
     * @param int $page 分页数
     * @param string $order 排序
     * @param string $field 所需字段
     * @return array 团购列表
     *
     */
    public function getTuanSoonList($condition, $page = null, $order = 'state asc', $field = '*') {
        $condition['state'] = self::TUAN_STATE_NORMAL;
        $condition['start_time'] = array('gt', TIMESTAMP);
        return $this->getTuanList($condition, $page, $order, $field);
    }

    /**
     * 读取已经结束的团购列表
     * @param array $condition 查询条件
     * @param int $page 分页数
     * @param string $order 排序
     * @param string $field 所需字段
     * @return array 团购列表
     *
     */
    public function getTuanHistoryList($condition, $page = null, $order = 'state asc', $field = '*') {
        $condition['state'] = self::TUAN_STATE_CLOSE;
        return $this->getTuanList($condition, $page, $order, $field);
    }

    /**
     * 读取推荐团购列表
     * @param int $limit 要读取的数量
     */
    public function getTuanCommendedList($limit = 4) {
        $condition = array();
        $condition['state'] = self::TUAN_STATE_NORMAL;
        $condition['recommended'] = 1;
        $condition['start_time'] = array('lt', TIMESTAMP);
        $condition['end_time'] = array('gt', TIMESTAMP);
        return $this->getTuanList($condition, null, 'recommended desc', '*', $limit);
    }

    /**
     * 根据条件读取团购信息
     * @param array $condition 查询条件
     * @return array 团购信息
     *
     */
    public function getTuanInfo($condition) {
        $tuan_info = $this->where($condition)->find();
        if (!empty($tuan_info)) {
            $tuan_info = $this->getTuanExtendInfo($tuan_info);
        }
        return $tuan_info;
    }

    /**
     * 根据团购编号读取团购信息
     * @param array $tuan_id 团购活动编号
     * @param int $vid 如果提供店铺编号，判断是否为该店铺活动，如果不是返回null
     * @return array 团购信息
     *
     */
    public function getTuanInfoByID($tuan_id, $vid = 0) {
        if(intval($tuan_id) <= 0) {
            return null;
        }

        $condition = array();
        $condition['tuan_id'] = $tuan_id;
        $tuan_info = $this->getTuanInfo($condition);

        if($vid > 0 && $tuan_info['vid'] != $vid) {
            return null;
        } else {
            return $tuan_info;
        }
    }

    /**
     * 根据商品编号查询是否有可用团购活动，如果有返回团购信息，没有返回null
     * @param int $gid
     * @return array $tuan_info
     *
     */
    public function getTuanInfoByGoodsCommonID($goods_commonid) {
        $tuan_list = $this->_getTuanListByGoodsCommon($goods_commonid);
        return $tuan_list[0];
    }
    /**
     * 根据商品id查询是否有可用团购活动，如果有返回团购信息，没有返回null_zhangjinfeng
     * @param int $gid
     * @return array $tuan_info
     *
     */
    public function getTuanInfoByGoodsID_new($gid) {
        $tuan_list = $this->_getTuanListByGoodsid_new($gid);
        return $tuan_list;//[0];
    }

    /**
     * 根据商品编号查询是否有可用团购活动，如果有返回团购活动，没有返回null
     * @param string $goods_string 商品编号字符串，例：'1,22,33'
     * @return array $tuan_list
     *
     */
    public function getTuanListByGoodsCommonIDString($goods_commonid_string) {
        $tuan_list = $this->_getTuanListByGoodsCommon($goods_commonid_string);
        $tuan_list = array_under_reset($tuan_list, 'goods_commonid');
        return $tuan_list;
    }

    /**
     * 根据商品编号查询是否有可用团购活动，如果有返回团购活动，没有返回null
     * @param string $goods_id_string
     * @return array $tuan_list
     *
     */
    private function _getTuanListByGoodsCommon($goods_commonid_string) {
        $condition = array();
        $condition['state'] = self::TUAN_STATE_NORMAL;
        $condition['start_time'] = array('lt', TIMESTAMP);
        $condition['end_time'] = array('gt', TIMESTAMP);
        $condition['goods_commonid'] = array('in', $goods_commonid_string);
        $xianshi_goods_list = $this->getTuanList($condition, null, 'tuan_id desc', '*');
        return $xianshi_goods_list;
    }
    /**
     * 根据商品id是否有可用团购活动，如果有返回团购活动，没有返回null_zhangjinfeng
     * @param string $goods_id_string
     * @return array $tuan_list
     *
     */
    private function _getTuanListByGoodsid_new($gid) {
        $condition = array();
        $condition['state'] = self::TUAN_STATE_NORMAL;
        $condition['start_time'] = array('lt', TIMESTAMP);
        $condition['end_time'] = array('gt', TIMESTAMP);
        $condition['gid'] = array('in', $gid);
        $xianshi_goods_list = $this->getTuanList($condition, null, 'tuan_id desc', '*');
        return $xianshi_goods_list;
    }

    /**
     * 团购状态数组
     */
    public function getTuanStateArray() {
        return $this->tuan_state_array;
    }


    /*
     * 增加
     * @param array $param
     * @return bool
     *
     */
    public function addTuan($param){
        // 发布团购锁定商品
        $this->_lockGoods($param['goods_commonid']);

        $param['state'] = self::TUAN_STATE_REVIEW;
        $param['recommended'] = 0;
        $return = $this->insert($param);

        return $return;
    }

    /**
     * 锁定商品
     */
    private function _lockGoods($goods_commonid) {
        $condition = array();
        $condition['goods_commonid'] = $goods_commonid;

        $model_goods = Model('goods');
        $model_goods->editGoodsCommonLock($condition);
    }

    /**
     * 解锁商品
     */
    private function _unlockGoods($goods_commonid) {
        $condition = array();
        $condition['goods_commonid'] = $goods_commonid;
        $condition['end_time'] = array('gt', TIMESTAMP);
        $condition['state'] = array('in', array(self::TUAN_STATE_REVIEW, self::TUAN_STATE_NORMAL));
        $tuan_list = $this->getTuanList($condition);

        if(!empty($tuan_list)) {
            $model_goods = Model('goods');
            $model_goods->editGoodsCommonUnlock(array('goods_commonid' => $goods_commonid));
        }
    }

    /*
	 * 更新
	 * @param array $update
	 * @param array $condition
	 * @return bool
     *
	 */
    public function editTuan($update, $condition) {
        $return = $this->where($condition)->update($update);

        //更新缓存
        $this->update_activity_cache();

        return $return;
    }

    /*
	 * 审核成功
	 * @param int $tuan_id
	 * @return bool
     *
	 */
    public function reviewPassTuan($tuan_id) {
        $condition = array();
        $condition['tuan_id'] = $tuan_id;

        $update = array();
        $update['state'] = self::TUAN_STATE_NORMAL;

        //更新缓存
        $this->update_activity_cache();

        return $this->editTuan($update, $condition);
    }

    /*
	 * 审核失败
	 * @param int $tuan_id
	 * @return bool
     *
	 */
    public function reviewFailTuan($tuan_id) {
        // 商品解锁
        $tuan_info = $this->getTuanInfoByID($tuan_id);
        $this->_unlockGoods($tuan_info['goods_commonid']);

        $condition = array();
        $condition['tuan_id'] = $tuan_id;

        $update = array();
        $update['state'] = self::TUAN_STATE_REVIEW_FAIL;

        return $this->editTuan($update, $condition);
    }

    /*
     * 取消
     * @param int $tuan_id
     * @return bool
     *
     */
    public function cancelTuan($tuan_id) {
        // 商品解锁
        $tuan_info = $this->getTuanInfoByID($tuan_id);
        $this->_unlockGoods($tuan_info['goods_commonid']);

        $condition = array();
        $condition['tuan_id'] = $tuan_id;

        $update = array();
        $update['state'] = self::TUAN_STATE_CANCEL;

        return $this->editTuan($update, $condition);
    }

    /**
     * 过期团购修改状态，解锁对应商品
     */
    public function editExpireTuan() {
        $condition = array();
        $condition['end_time'] = array('lt', TIMESTAMP);
        $condition['state'] = array('in', array(self::TUAN_STATE_REVIEW, self::TUAN_STATE_NORMAL));

        $expire_tuan_list = $this->getTuanList($condition, null);
        $tuan_id_string = '';
        if(!empty($expire_tuan_list)) {
            foreach ($expire_tuan_list as $value) {
                $tuan_id_string .= $value['tuan_id'].',';
                $this->_unlockGoods($value['goods_commonid']);
            }
        }

        if($tuan_id_string != '') {
            $updata = array();
            $update['state'] = self::TUAN_STATE_CLOSE;
            $condition = array();
            $condition['tuan_id'] = array('in', rtrim($tuan_id_string, ','));
            $this->editTuan($update, $condition);
        }
    }

    /*
     * 删除团购活动
     * @param array $condition
     * @return bool
     *
     */
    public function delTuan($condition){
        $tuan_list = $this->getTuanList($condition);

        if(!empty($tuan_list)) {
            foreach ($tuan_list as $value) {
                // 商品解锁
                $this->_unlockGoods($value['goods_commonid']);

                list($base_name, $ext) = explode('.', $value['tuan_image']);
                list($vid) = explode('_', $base_name);
                $path = BASE_UPLOAD_PATH.DS.ATTACH_TUAN.DS.$vid.DS;
                delete_file($path.$base_name.'.'.$ext);
                delete_file($path.$base_name.'_small.'.$ext);
                delete_file($path.$base_name.'_mid.'.$ext);
                delete_file($path.$base_name.'_max.'.$ext);

                if(!empty($value['tuan_image1'])) {
                    list($base_name, $ext) = explode('.', $value['tuan_image1']);
                    delete_file($path.$base_name.'.'.$ext);
                    delete_file($path.$base_name.'_small.'.$ext);
                    delete_file($path.$base_name.'_mid.'.$ext);
                    delete_file($path.$base_name.'_max.'.$ext);
                }
            }
        }
        $return = $this->where($condition)->delete();

        //更新缓存
        $this->update_activity_cache();

        return $return;
    }

    /**
     * 获取团购扩展信息
     */
    public function getTuanExtendInfo($tuan_info) {
        $tuan_info['tuan_url'] = urlShop('tuan', 'tuandetail', array('tuan_id' => $tuan_info['tuan_id']));
        $tuan_info['goods_url'] = urlShop('goods', 'index', array('gid' => $tuan_info['gid']));
        $tuan_info['start_time_text'] = date('Y-m-d H:i', $tuan_info['start_time']);
        $tuan_info['end_time_text'] = date('Y-m-d H:i', $tuan_info['end_time']);
        //根据店铺vid获取店铺logo
        $vendor_model = new VendorInfo();
        $sld_ven_info = $vendor_model -> getStoreInfoByID($tuan_info['vid']);
        $sld_ven_logo = $sld_ven_info['store_label'];
        $tuan_info['store_label'] = $sld_ven_logo;
        //根据gid获取商品图片
        $goods_model = new Goods();
        $sld_goods_info = $goods_model -> getGoodsOnlineInfoByID($tuan_info['gid'],'*');
        $tuan_info['goods_img_url'] = thumb($sld_goods_info, 240);
        //计算已售百分比（虚拟售出数量+已售数量/（虚拟售出数量+已售数量+商品现有库存））取整
        $tuan_info['salenum_percent'] = ceil(($tuan_info['virtual_quantity']+$tuan_info['buy_quantity'])/($tuan_info['virtual_quantity']+$tuan_info['buy_quantity']+$sld_goods_info['goods_storage'])*100);
        if(empty($tuan_info['tuan_image1'])) {
            $tuan_info['tuan_image1'] = $tuan_info['tuan_image'];
        }
        if($tuan_info['start_time'] > TIMESTAMP && $tuan_info['state'] == self::TUAN_STATE_NORMAL) {
            $tuan_info['tuan_state_text'] = '正常(未开始)';
        } elseif ($tuan_info['end_time'] < TIMESTAMP && $tuan_info['state'] == self::TUAN_STATE_NORMAL) {
            $tuan_info['tuan_state_text'] = '已结束';
            $model_goods = new Goods();
            $model_goods->editGoodsCommonUnlock(array('goods_commonid' => $tuan_info['goods_commonid']));
//            $this->_unlockGoods($tuan_info['goods_commonid']);
        } else {
            $tuan_info['tuan_state_text'] = $this->tuan_state_array[$tuan_info['state']];
        }

        if($tuan_info['state'] == self::TUAN_STATE_REVIEW) {
            $tuan_info['reviewable'] = true;
        } else {
            $tuan_info['reviewable'] = false;
        }

        if($tuan_info['state'] == self::TUAN_STATE_NORMAL) {
            $tuan_info['cancelable'] = true;
        } else {
            $tuan_info['cancelable'] = false;
        }

        switch ($tuan_info['state']) {
            case self::TUAN_STATE_REVIEW:
                $tuan_info['state_flag'] = 'not-verify';
                $tuan_info['button_text'] = '未审核';
                break;
            case self::TUAN_STATE_REVIEW_FAIL:
            case self::TUAN_STATE_CANCEL:
            case self::TUAN_STATE_CLOSE:
                $tuan_info['state_flag'] = 'close';
                $tuan_info['button_text'] = '已结束';
                break;
            case self::TUAN_STATE_NORMAL:
                if($tuan_info['start_time'] > TIMESTAMP) {
                    $tuan_info['state_flag'] = 'not-start';
                    $tuan_info['button_text'] = '未开始';
                    $tuan_info['count_down_text'] = '距团购开始';
                    $tuan_info['count_down'] = $tuan_info['start_time'] - TIMESTAMP;
                } elseif ($tuan_info['end_time'] < TIMESTAMP) {
                    $tuan_info['state_flag'] = 'close';
                    $tuan_info['button_text'] = '已结束';
                } else {
                    $tuan_info['state_flag'] = 'buy-now';
                    $tuan_info['button_text'] = '我要团';
                    $tuan_info['count_down_text'] = '距团购结束';
                    $tuan_info['count_down'] = $tuan_info['end_time'] - TIMESTAMP;
                }
                break;
        }
        return $tuan_info;
    }
    /**
     * 根据条件读取团购信息
     * @param array $condition 查询条件
     * @param int $page 分页数
     * @param string $order 排序
     * @param string $field 所需字段
     * @return array 团购列表
     *
     */
    public function getTuanOnlineInfo($condition) {
        $condition['state'] = self::TUAN_STATE_NORMAL;
        $condition['start_time'] = array('lt', TIMESTAMP);
        $condition['end_time'] = array('gt', TIMESTAMP);
        $tuan_info = $this->where($condition)->find();
        return $tuan_info;
    }

    // 更新缓存
    public function update_activity_cache(){
        //文件缓存
        $tuan_list=$this->getTuanOnlineList_gid();
        if(!empty($tuan_list)){
            $activity_cache=Model('activity_cache');
            $activity_cache->Activity('tuan',$tuan_list);
        }
    }

    //缓存团购信息
    public function getTuanOnlineList_gid($condition, $page = null, $order = 'state asc', $field = '*') {
        $condition['state'] = self::TUAN_STATE_NORMAL;
//        $condition['start_time'] = array('lt', TIMESTAMP);
        $condition['end_time'] = array('gt', TIMESTAMP);

        return $this->getTuanList_gid($condition, $page, $order, $field);
    }

    //团购缓存
    public function getTuanList_gid($condition, $page = null, $order = 'end_time asc', $field = '*', $limit = 10000) {
        $tuan_list = $this->field($field)->where($condition)->order($order)->limit($limit)->select();

        if (!empty($tuan_list)) {

            for ($i = 0, $j = count($tuan_list); $i < $j; $i++) {
                $tuan_list[$i] = $this->getTuanExtendInfo($tuan_list[$i]);

            }

        }
        return $tuan_list;
    }
}