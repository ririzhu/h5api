<?php
namespace app\v1\model;

use Exception;
use QueueClient;
use think\Model;
use think\db;
use think\model\concern\TimeStamp;

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
        if(!empty($tuan_list))
        return $tuan_list[0];
        else{
            return null;
        }
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
        /*$condition = array();
        $condition['state'] = self::TUAN_STATE_NORMAL;
        $condition['start_time'] = array('lt', TIMESTAMP);
        $condition['end_time'] = array('gt', TIMESTAMP);
        $condition['goods_commonid'] = array('in', $goods_commonid_string);*/
        $condition = "state = ". self::TUAN_STATE_NORMAL ." and end_time<".TIMESTAMP;
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
    public function getTuanOnlineList_gid($condition = null, $page = null, $order = 'state asc', $field = '*') {
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




    //清理拼团队伍超时 拼团失败
    function team_timeout($id=null){
        $where='';
        if($id){
            $where=' and pin_team.id='.$id;
        }
        //查询所有超时队伍的订单
        $orders = M('pin')->table('pin,pin_team,pin_team_user,order,goods,pin_goods')
            ->join('left')
            ->on('pin_team.sld_pin_id = pin.id,pin_team.id=pin_team_user.sld_team_id,pin_team_user.sld_order_id=order.order_id,pin_team_user.sld_gid=goods.gid,pin_team_user.sld_gid=pin_goods.sld_gid')
            ->where("( pin_team.sld_add_time + sld_success_time * 3600 ) <= ".TIMESTAMP." and sld_tuan_status =0 and order.order_state in ('20') and order.pin_id>0".$where)
            ->field('`order`.*,pin_team.id as team_id,goods.goods_name,pin_goods.sld_pin_price')
            ->select();
        if(count($orders)<1){
            return 0;
        }

        $model_order = Model('order');
        $logic_order = Logic('order');
        $ids=array();
        foreach ($orders as $k=>$v){

            $v['pd_amount'] = $v['order_amount'];


            if(!in_array($v['team_id'],$ids)){
                $ids[]=$v['team_id'];
            }

            if($v['lock_state']==1) { //如果有退款的 处理成同意
                $model_refund = Model('refund_return');
                $condition['order_id'] = $v['order_id'];
                $refund_array = array();
                $refund_array['seller_time'] = $refund_array['admin_time'] = time();
                $refund_array['seller_state'] = 2;
                $refund_array['seller_message'] = $refund_array['admin_message'] = '参团超时失败，自动处理';
                $refund_array['refund_state'] = '3';//状态:1为处理中,2为待管理员处理,3为已完成
                $state = $model_refund->editRefundReturn($condition, $refund_array);
                $model_refund->editOrderUnlock($v['order_id']);//订单解锁
            }
            $result = $logic_order->changeOrderStateCancel($v, 'system', '系统', '拼团超时');

            $param = array();
            $param['member_id'] = $v['buyer_id'];
            $param['code'] = 'pin_team_no';
            $param['param'] = array(
                'url' => WAP_SITE_URL.'/pin_detail.html?id='.$v['team_id'],
                'first' => '您好，您参加的拼团由于团已过期，拼团失败。',
                'keyword1' => $v['goods_name'], //商品名称
                'keyword2' => $v['sld_pin_price'], //价格
                'keyword3' => $v['sld_pin_price'], //价格
                'remark' => '点击查看拼团详情', // 点击查看
            );
            QueueClient::push('sendMemberMsg', $param);


        }

        $ids= join(',',$ids);
        $re=M('pin')->table('pin_team')->where(array('id'=>array('in',$ids)))
            ->update(array('sld_tuan_status'=>'2'));
        return $re;

    }

    //付款成功后对 拼团成功操作
    public function paidPin($order_info){


        $db = $this;
        if(empty($order_info['pin_id'])){
            return array('succ'=>1);
        }
        //最后一个人 成团
        $where['pin_team.sld_pin_id'] = $order_info['pin_id'];
        $where['sld_order_id'] = $order_info['order_id'];
//        $pin_info = $db->table('pin')->where(array('id'=>$order_info['pin_id']))->find();

        $sheng = $db->table('pin_team,pin,pin_team_user,member,goods,pin_goods')
            ->join('left')
            ->field('member.*,pin.sld_success_time,pin_goods.sld_gid,sld_team_id,pin.sld_team_count,pin_team.sld_add_time,pin.sld_team_count-(select count(*) from bbc_pin_team_user p left join bbc_order o on p.sld_order_id=o.order_id where p.sld_team_id = pin_team.id and o.order_state >2 and o.buyer_id <> pin_team.sld_leader_id ) as sheng,pin.sld_return_leader,sld_pin_price,sld_leader_id,goods.goods_name')
            ->on('pin_team.sld_pin_id=pin.id, pin_team.id=pin_team_user.sld_team_id, pin_team.sld_leader_id=member.member_id,goods.gid=pin_team_user.sld_gid,pin_goods.sld_gid=pin_team_user.sld_gid,pin_team_user.sld_order_id=order.order_id')
            ->where($where)
            ->order('pin_team_user.id asc')
            ->find();

        $param = array();
        $param['member_id'] = $order_info['buyer_id'];
        if ( $sheng['sld_leader_id'] == $order_info['buyer_id'] ) {  //开团的话
            //更新一下团队开始时间
            $sheng['sld_add_time'] = TIMESTAMP;
            $this->table('pin_team')->where(array('id'=>$sheng['sld_team_id']))->update(array('sld_add_time'=>TIMESTAMP));

            //开团微信提醒
            $key5 = $sheng['sld_success_time']>1?$sheng['sld_success_time'].'小时':($sheng['sld_success_time']/60).'分钟';
            $key5.=date(' m月d日H:i',$sheng['sld_add_time'] + 3600 * $sheng['sld_success_time']).'    截止';
            $param['code'] = 'pin_lead_team';
            $param['param'] = array(
                'url' => WAP_SITE_URL.'/pin_detail.html?id='.$sheng['sld_team_id'],
                'first' => '恭喜您开团成功，请等待成团。',
                'keyword1' => $sheng['goods_name'], //产品名称
                'keyword2' => '￥'.$sheng['sld_pin_price'], //拼价
                'keyword3' => $sheng['sld_team_count'], //总人数
                'keyword4' => $sheng['sld_return_leader']>0?'团长返利'.$sheng['sld_return_leader'].'元':'正常拼团', //团长
                'keyword5' => $key5, //截止时间
                'remark' => '点击查看拼团详情', // 点击查看
            );
        } else {
            $sheng['sheng'] = $sheng['sheng'] - 1;
            //参团微信提醒
            $param['code'] = 'pin_join_team';
            $param['param'] = array(
                'url' => WAP_SITE_URL.'/pin_detail.html?id='.$sheng['sld_team_id'],
                'first' => '恭喜您参团成功，请等待成团。',
                'keyword1' => $sheng['goods_name'], //产品名称
                'keyword2' => '￥'.$sheng['sld_pin_price'], //拼价
                'keyword3' => $sheng['wx_nickname']?$sheng['wx_nickname']:$sheng['member_name'], //团长
                'keyword4' => $sheng['sld_team_count'], //总人数
                'keyword5' => date('m月d日 H:i',$sheng['sld_add_time'] + 3600 * $sheng['sld_success_time']), //截止时间
                'remark' => '点击查看拼团详情', // 点击查看
            );
        }
        QueueClient::push('sendMemberMsg', $param);

//        if($sheng['sheng']<=0 && $order_info['order_state']=='10'){
//            return array('succ'=>0,'msg'=>'人数已满，参团失败');
//        }

        if($sheng['sheng']<=1){
            //成团提醒
            $members = [];
            if($sheng['sheng']==1) {
                $members  = $this->table('pin_team_user,order,member,goods,pin_goods')->where(array('sld_team_id'=>$sheng['sld_team_id'],'order_state'=>'20','pin_id'=>$order_info['pin_id']))->join('left')
                    ->on('pin_team_user.sld_order_id=order.order_id,order.buyer_id=member.member_id,pin_team_user.sld_gid=goods.gid,pin_team_user.sld_gid=pin_goods.sld_gid')
                    ->field('order.order_id,member.member_id,order.order_sn,goods.goods_name,pin_goods.sld_pin_price,order.order_sn')
                    ->select();
            }

            $members[] = array(
                'order'=>$order_info['order_id'],
                'sld_team_id'=>$sheng['sld_team_id'],
                'member_id'=>$order_info['buyer_id'],
                'goods_name'=>$sheng['goods_name'],
                'sld_pin_price'=>$sheng['sld_pin_price'],
                'order_sn'=>$order_info['order_sn']
            );



            $this->full_team_user($members,$sheng);
        }


        //减活动库存
        $stock=$this->table('pin_goods')->where(array('sld_gid'=>$sheng['sld_gid'],'sld_pin_id'=>$order_info['pin_id']))->find();
        if($stock<1){
            return array('succ'=>0,'msg'=>'库存不足');
        }

        if(!$this->table('pin_goods')->where(array('sld_gid'=>$sheng['sld_gid'],'sld_pin_id'=>$order_info['pin_id']))->update(array('sld_stock'=>array('exp', 'sld_stock - 1')))){
            return array('succ'=>0,'msg'=>'更改库存失败');
        }

        //取消这个拼团活动没付款的订单
        $model_order = Model('order');
        $delete_orders = $this->table('order')->where(array('buyer_id'=>$order_info['buyer_id'],'pin_id'=>$order_info['pin_id'],'order_state'=>'10','order_id'=>array('neq',$order_info['order_id'])))->select();
        foreach ($delete_orders as $k=>$v){
            $model_order->pinChangeStateOrderCancel($v,$order_info['buyer_id'],$order_info['buyer_name']);
        }
        return array('succ'=>1);

    }

    //适配拼团 订单状态显示
    public function order_list_state($arr){

        $order_ids = array();
        foreach ($arr as $kk=>$vv){
            if($vv['pin_id']>0) {
                $order_ids[] = $vv['order_id'];
            }
        }
        if(count($order_ids)<1){
            return $arr;
        }
        $where['order_id'] = array('in',join(',',$order_ids));
        $team_list = M('pin')->table('order,pin_team_user,pin_team,pin')
            ->join('left')
            ->on('order.order_id=pin_team_user.sld_order_id, pin_team_user.sld_team_id=pin_team.id, pin_team.sld_pin_id=pin.id')
            ->where($where)
            ->field('pin_team_user.*,pin_team.*,pin.*')
            ->group('order.order_id')
            ->select();
        foreach ($team_list as $k=>$v){
            if($v['id']){
                $temp[$v['sld_order_id']] = $v;
            }

        }
        $team_list = $temp;

        foreach ($arr as $kk=>$vv) {
            if($team_list[$vv['order_id']]) {
                $arr[$kk]['if_pin'] = 1;
                $arr[$kk]['pin'] = $team_list[$vv['order_id']];
                if ($vv['state_desc'] == '<span style="color:#F30">待发货</span>') {
                    if ($team_list[$vv['order_id']]['sld_tuan_status'] == 1) {
                        $arr[$kk]['state_desc'] = '拼团成功，待发货';
                    } else {
                        $arr[$kk]['state_desc'] = '付款成功，待成团';
                    }
                }
                if ($vv['state_desc'] == '<span style="color:#999">已取消</span>') {
                    if ($team_list[$vv['order_id']]['sld_tuan_status'] == 2) {
                        $arr[$kk]['state_desc'] = '拼团失败，已退款';
                    } else {
                        $arr[$kk]['state_desc'] = '未付款，已取消';
                    }
                }
            }
        }

        return $arr;
    }

    //成团后的操作
    public function full_team_user($team_user_list,$sheng){

        foreach ($team_user_list as $v){
            $param = array();
            $param['member_id'] = $v['member_id'];
            $param['code'] = 'pin_team_ok';
            $param['param'] = array(
                'url' => WAP_SITE_URL.'/cwap_order_detail.html?order_id='.$v['order_id'],
                'first' => '你拼团的商品【 '.$v['goods_name'].' 】已拼团成功，我们会尽快为您安排发货！',
                'keyword1' => $v['sld_pin_price'], //拼团价格
                'keyword2' => $v['order_sn'], //订单号
                'remark' => '点击查看订单详情', // 点击查看
            );
            QueueClient::push('sendMemberMsg', $param);
        }




        $where2['id'] = $sheng['sld_team_id'];

        $where3 = array('sld_user_id'=>$sheng['sld_leader_id'],'sld_team_id'=>$sheng['sld_team_id']);


        if(!$sheng['order_sn']){
            $leader = $this->table('pin_team_user,order')->join('left')->on('pin_team_user.sld_order_id=order.order_id')
                ->field('sld_fanli,order_sn')
                ->where($where3)->find();
            $sheng['sld_fanli'] = $leader['sld_fanli'];
            $sheng['order_sn'] = $leader['order_sn'];
        }

        if(!$this->table('pin_team')->where($where2)->update(array('sld_tuan_status'=>1))){
            return array('succ'=>0,'msg'=>'修改团队状态失败');
        }


        if($sheng['sld_return_leader']>0 && $sheng['sld_fanli'] == 1){ //团长返利

            $model_pd = Model('predeposit');
            $data_pd = array();
            $data_pd['member_id'] = $sheng['sld_leader_id'];
            $data_pd['member_name'] = $sheng['member_name'];
            $data_pd['amount'] = $sheng['sld_return_leader'];
            $data_pd['order_sn'] = $sheng['order_sn'];
            $model_pd->changePd('return_leader',$data_pd);

            $this->table('pin_team_user')->where($where3)->update(['sld_fanli'=>2]);
        }
    }



    public function getPinList($where=null,$page=16){
        $where['sld_start_time'] = array('lt',TIMESTAMP);
        $where['sld_end_time'] = array('gt',TIMESTAMP);
        $where['pin.sld_status'] = 1;
//        $where['goods.gid'] = array('neq','');
        $goods=$this->table('pin,pin_goods,goods,pin_type')
            ->field('pin.*,goods.gid,goods.goods_name,goods.goods_image,goods.goods_price,pin_goods.sld_pin_price,pin_type.id as tid,pin_type.sld_typename as tname,goods.vid,(select count(*) from bbc_order where pin_id = pin.id and bbc_order.order_state>2) as sales')
            ->join('inner')
            ->on('pin.id=pin_goods.sld_pin_id,pin_goods.sld_gid=goods.gid,pin.sld_type=pin_type.id')
            ->where($where)
            ->group('pin.id')
            ->page($page)
            ->order('sales desc')
            ->select();
        foreach ($goods as $k=>$v){
            $goods[$k]['sheng'] = $v['goods_price']- $v['sld_pin_price'];
            $goods[$k]['sld_pic'] = gthumb($v['sld_pic'],'max');
            $goods[$k]['goods_image'] = thumb($v,350);
            $goods[$k]['sld_end_time'] = date('Y/m/d H:i:s',$v['sld_end_time']);
            $goods[$k]['end_time'] = $v['sld_end_time'];
        }

        return $goods;
    }



    /**
     * 读取团购商品列表
     * @param int $page 拼团活动id
     * @return array 拼团商品列表
     *
     */
    public function getGoodsListByPinId($id) {
        $condition['sld_pin_id'] = $id;
        $tuan_list = $this->table('pin_goods')->where($condition)->select();
        return $tuan_list;
    }


    /**
     * 读取团购分类
     */
    public function getPinTypes($condition=array(),$order='sld_sort asc') {
        if(!$condition) {
            $condition['sld_status'] = 1;
        }
        return $this->table('pin_type')->where($condition)->order($order)->select();
    }






    //拼团缓存
    public function _getTuanListByGoodsid_gid() {
        $condition = array();
        $condition['bbc_pin.sld_status'] = 1;
//        $condition['sld_start_time'] = array('lt', TIMESTAMP);
       // $condition['bbc_pin.sld_end_time'] = " <".TIMESTAMP;

        $xianshi_goods_list = DB::table('bbc_pin_goods')->join("bbc_pin",' bbc_pin_goods.sld_pin_id=bbc_pin.id')
            ->where("bbc_pin.sld_status = 1 and bbc_pin.sld_end_time <".TIMESTAMP)->order('bbc_pin.id')->select();

        return $xianshi_goods_list;
    }




    /*
     * 增加
     * @param array $param
     * @return bool
     *
     */
    public function addPinGoods($insert){

        // 获取商品ID
        $goods_commonids = array();
        $gids = low_array_column($insert,'sld_gid');

        // 获取所有商品的 goods_commonid
        $goods_data = Model('goods')->getGoodsList(array('gid'=>array("IN",$gids)),'goods_commonid');

        if (!empty($goods_data)) {
            $goods_commonids = low_array_column($goods_data,'goods_commonid');
        }

        if (!empty($goods_commonids)) {
            $goods_commonids = array_flip($goods_commonids);
            $goods_commonids = array_flip($goods_commonids);
            $goods_commonids = array_values($goods_commonids);

            $lock_condition['goods_commonid'] = array("IN",$goods_commonids);
            Model('goods')->editGoodsCommonLock($lock_condition);
        }

        // 发布团购锁定商品
        $result= $this->table('pin_goods')->insertAll($insert);

        //文件缓存
        dkcache('pin_tuan_gid');
        rkcache('pin_tuan_gid',true);
        return $result;

    }

    /**
     * 过期拼团终止，解锁对应商品
     */
    public function editExpirePinTuan()
    {
        $condition['sld_end_time'] = array('lt', TIMESTAMP);

        $condition['sld_status'] = 1;

        $pin_data = $this->table('pin')->where($condition)->field('id')->select();

        $pin_ids = !empty($pin_data) ? low_array_column($pin_data,'id') : array();
        // 获取商品ID
        $pin_goods_condition['sld_pin_id'] = array("IN",$pin_ids);
        $pin_goods_list = $this->table('pin_goods')->where($pin_goods_condition)->field('sld_gid')->select();

        $gids = !empty($pin_goods_list) ? low_array_column($pin_goods_list,'sld_gid') : array();

        $update = array();
        $update = array('sld_status'=>0);
        $this->table('pin')->where($condition)->update($update);

        // 过期活动 的商品 解除商品锁定
        $goods_commonids = array();
        // 获取所有商品的 goods_commonid
        $goods_data = Model('goods')->getGoodsList(array('gid'=>array("IN",$gids)),'goods_commonid');

        if (!empty($goods_data)) {
            $goods_commonids = low_array_column($goods_data,'goods_commonid');
        }

        if (!empty($goods_commonids)) {
            $goods_commonids = array_flip($goods_commonids);
            $goods_commonids = array_flip($goods_commonids);
            $goods_commonids = array_values($goods_commonids);

            $unlock_condition['goods_commonid'] = array("IN",$goods_commonids);
            Model('goods')->editGoodsCommonUnlock($unlock_condition);
        }
        // 重新生成缓存
        dkcache('pin_tuan_gid');
        rkcache('pin_tuan_gid',true);

        return true;
    }



    // 定时任务 订单 冻结转可用
    public function pin_freeze_to_av(){
        // 获取 系统配置 设置的 结算周期（确认收货后多长时间 冻结转可用）
        $model = Model('order');
        $days = Model('trade')->getMaxDay('order_refund');
        $rechange_day_num = $days;
        $rechang_day_time = time() - 60*60*24*$rechange_day_num;

        // 查询 待处理订单 （完成状态，且完成时间 已满足 冻结转可用 周期）
        $order_condition['o.order_state'] = ORDER_STATE_SUCCESS;
        $order_condition['o.finnshed_time'] = array('lt',$rechang_day_time);
        $order_condition['pu.sld_fanli'] = 2;
        $order_condition['p.sld_return_leader'] = array('gt',0);
        $wait_order_list = $model->table('pin_team_user,pin_team,pin,order')->alias('pu,pt,p,o')->join('left')
            ->on('pu.sld_team_id=pt.id,pt.sld_pin_id=p.id,pu.sld_order_id=o.order_id')->where($order_condition)->
            field('p.sld_return_leader,pu.id,o.order_sn,pu.sld_user_id,o.buyer_name')->group('pu.id')->select();

        try {

            $this->beginTransaction();

            foreach ($wait_order_list as $k => $v) {
                $model_pd = Model('predeposit');
                $data_pd = array();
                $data_pd['member_id'] = $v['sld_user_id'];
                $data_pd['member_name'] = $v['buyer_name'];
                $data_pd['amount'] = $v['sld_return_leader'];
                $data_pd['order_sn'] = $v['order_sn'];
                $model_pd->changePd('return_leader2', $data_pd);
            }

//      返利之后改状态
            $lids = low_array_column($wait_order_list, 'id');


            if (count($lids) > 0) {
                $this->table('pin_team_user')->where(array('id' => ['in', join(',', $lids)]))->update(['sld_fanli' => 3]);

            }
            $this->commit();
        }catch (Exception $e){
            $this->rollback();
        }


    }

    public function out_fanli($order){
        $pin = $this->table('pin_team_user,pin_team,pin')->alias('pu,pt,p')->join('left')
            ->on('pu.sld_team_id=pt.id,pt.sld_pin_id=p.id')
            ->where(['pu.sld_order_id'=>$order['order_id']])
            ->field('p.sld_return_leader,pu.id,pu.sld_user_id')
            ->find();

        if($pin) {

            $re = $this->table('pin_team_user')->where(array('id' => $pin['id'],'sld_fanli'=>2))->update(['sld_fanli' => 0]);

            $model_pd = Model('predeposit');
            $data_pd = array();
            $data_pd['member_id'] = $pin['sld_user_id'];
            $data_pd['member_name'] = $order['buyer_name'];
            $data_pd['amount'] = $pin['sld_return_leader'];
            $data_pd['order_sn'] = $order['order_sn'];
            $model_pd->changePd('return_leader3', $data_pd);
        }
    }

}