<?php
namespace app\v1\model;

use app\v1\controller\Base;
use think\Model;
use think\db;
class Goods extends Model
{
    public function __construct(){
        parent::__construct('goods');
    }
    protected $table_name = '';
    protected $options = array();
    const STATE1 = 1;       // 出售中
    const STATE0 = 0;       // 下架
    const STATE10 = 10;     // 违规
    const VERIFY1 = 1;      // 审核通过
    const VERIFY0 = 0;      // 审核失败
    const VERIFY10 = 10;    // 等待审核

    public $course_type = [1=>'公开课',2=>'在线课',3=>'教材'];

    /**
     * 新增商品数据
     *
     * @param array $insert 数据
     * @param string $table 表名
     */
    public function addGoods($insert, $table = "goods") {
        return $this->table($table)->insert($insert);
    }

    /**
     * 新增多条商品数据
     *
     * @param unknown $insert
     */
    public function addGoodsAll($insert, $table = 'goods') {
        return $this->table($table)->insertAll($insert);
    }

    /**
     * 商品SKU列表
     *
     * @param array $condition 条件
     * @param string $field 字段
     * @param string $group 分组
     * @param string $order 排序
     * @param int $limit 限制
     * @param int $page 分页
     * @param boolean $lock 是否锁定
     * @return array 二维数组
     */
    public function getGoodsList($condition, $field = '*', $group = '',$order = '', $limit = 100, $page = 0, $lock = false, $count = 100) {
        //$condition = $this->_getRecursiveClass($condition);
        //$result = DB::table('bbc_goods')->field($field)->where($condition)->group($group)->order($order)->limit($limit)->page($page, $count)->lock($lock)->select();
        $result = DB::table('bbc_goods')->field($field)->where($condition)->group($group)->order($order)->limit($limit)->page($page)->lock($lock)->select();
        foreach($result as $k=>$v){
            $result[$k]['goods_image']="http://www.horizou.cn/data/upload/mall/store/goods/".$result[$k]['goods_image'];
        }
       //echo DB::table("bbc_goods")->getLastSql();die;
        return $result;
    }

    /**
     * 出售中的商品SKU列表（只显示不同颜色的商品，前台商品索引，店铺也商品列表等使用）
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param number $page
     * @return array
     */
    public function getGoodsListByColorDistinct($condition, $field = '*', $order = 'gid asc', $page = 0) {
        $condition['goods_state']   = self::STATE1;
        $condition['goods_verify']  = self::VERIFY1;
        $condition = $this->_getRecursiveClass($condition);
        $field = "CONCAT(goods_commonid,',',color_id) as jmys_distinct ," . $field;
        $count = $this->getGoodsOnlineCount($condition,"distinct CONCAT(goods_commonid,',',color_id)");
        $goods_list = array();
        if ($count != 0) {
            $goods_list = $this->getGoodsOnlineList($condition, $field, $page, $order, 0, 'jmys_distinct', false, $count);
        }
        return $goods_list;
    }
    /**
     * 出售中的商品SPU列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param number $page
     * @return array
     */
    public function getGoodsListByCommonidDistinct($condition, $field = '*', $order = null, $page = 0,$limit=0) {
        if(!$order){
            $order = 'gid asc';
        }
        if(is_array($condition)) {
            $condition['goods_state'] = true;
            $condition['goods_verify'] = "" . self::VERIFY1 . "";
            $condition = $this->_getRecursiveClass($condition);
        }else{
            $condition.=" and goods_state = true";
            $condition.=" and goods_verify =" . self::VERIFY1 . "";
        }
        $field = "goods_commonid as jmys_distinct ," . $field;
        $count = $this->getGoodsOnlineCount($condition,"distinct goods_commonid");
        $goods_list = array();
        if ($count != 0) {
            $goods_list = $this->getGoodsOnlineList($condition, $field, $page, $order, $limit, 'jmys_distinct', false, $count);
        }
        return $goods_list;
    }
    /**
     * 普通列表，即不包括虚拟商品、F码商品、预售商品、预定商品
     *
     * @param array $condition 条件
     * @param string $field 字段
     * @param string $group 分组
     * @param string $order 排序
     * @param int $limit 限制
     * @param int $page 分页
     * @param boolean $lock 是否锁定
     * @return array
     */
    public function getGeneralGoodsList($condition, $field = '*', $page = 0, $order = 'gid desc') {
        $condition['is_virtual']    = 0;
        $condition['is_fcode']      = 0;
        $condition['is_presell']    = 0;
        $condition['is_book']       = 0;
        return $this->getGoodsList($condition, $field, '', $order, 0, $page, 0);
    }

    /**
     * 出售中普通列表，即不包括虚拟商品、F码商品、预售商品、预定商品
     *
     * @param array $condition 条件
     * @param string $field 字段
     * @param string $group 分组
     * @param string $order 排序
     * @param int $limit 限制
     * @param int $page 分页
     * @param boolean $lock 是否锁定
     * @return array
     */
    public function getGeneralGoodsOnlineList($condition, $field = '*', $page = 0, $order = 'gid desc') {
        $condition['goods_state']   = self::STATE1;
        $condition['goods_verify']  = self::VERIFY1;
        return $this->getGeneralGoodsList($condition, $field, $page, $order);
    }

    /**
     * 在售商品SKU列表
     *
     * @param array $condition 条件
     * @param string $field 字段
     * @param string $group 分组
     * @param string $order 排序
     * @param int $limit 限制
     * @param int $page 分页
     * @param boolean $lock 是否锁定
     * @return array
     */
    public function getGoodsOnlineList($condition, $field = '*', $page = 0, $order = 'gid desc', $limit = 50, $group = '', $lock = false, $count = 0) {
        if(is_array($condition)) {
            $condition['goods_state'] = self::STATE1;
            $condition['goods_verify'] = self::VERIFY1;
        }else{
            $condition.="  and goods_state=".self::STATE1." and goods_verify=".self::VERIFY1;
        }
        //echo $lock;die;
        //if(APP_ID=='mall' || APP_ID=='cmobile'){
         //   $condition['sites'] = ['exp',"FIND_IN_SET('".LANG_TYPE."',sites)"];
        //}
        return $this->getGoodsList($condition, $field, $group, $order, $limit, $page, $lock, $count);
    }

    /**
     * 商品SUK列表 goods_show = 1 为出售中，goods_show = 0为未出售（仓库中，违规，等待审核）
     *
     * @param unknown $condition
     * @param string $field
     * @return multitype:
     */
    public function getGoodsAsGoodsShowList($condition, $field = '*') {
        $field = $this->_asGoodsShow($field);
        return $this->getGoodsList($condition, $field);
    }

    /**
     * 商品列表 卖家中心使用
     *
     * @param array $condition 条件
     * @param array $field 字段
     * @param string $page 分页
     * @param string $order 排序
     * @return array
     */
    public function getGoodsCommonList($condition, $field = '*', $page = 10, $order = 'goods_commonid desc') {
        $condition = $this->_getRecursiveClass($condition);
        return DB::table('bbc_goods_common')->field($field)->where($condition)->order($order)->page($page)->select();
    }

    /**
     * 出售中的商品列表 卖家中心使用
     *
     * @param array $condition 条件
     * @param array $field 字段
     * @param string $page 分页
     * @param string $order 排序
     * @return array
     */
    public function getGoodsCommonOnlineList($condition, $field = '*', $page = 10, $order = "goods_commonid desc") {
        $condition['goods_state']   = self::STATE1;
        $condition['goods_verify']  = self::VERIFY1;
        return $this->getGoodsCommonList($condition, $field, $page, $order);
    }

    /**
     * 仓库中的商品列表 卖家中心使用
     *
     * @param array $condition 条件
     * @param array $field 字段
     * @param string $page 分页
     * @param string $order 排序
     * @return array
     */
    public function getGoodsCommonOfflineList($condition, $field = '*', $page = 10, $order = "goods_commonid desc") {
        $condition['goods_state']   = self::STATE0;
        $condition['goods_verify']  = self::VERIFY1;
        return $this->getGoodsCommonList($condition, $field, $page, $order);
    }

    /**
     * 违规的商品列表 卖家中心使用
     *
     * @param array $condition 条件
     * @param array $field 字段
     * @param string $page 分页
     * @param string $order 排序
     * @return array
     */
    public function getGoodsCommonLockUpList($condition, $field = '*', $page = 10, $order = "goods_commonid desc") {
        $condition['goods_state']   = self::STATE10;
        $condition['goods_verify']  = self::VERIFY1;
        return $this->getGoodsCommonList($condition, $field, $page, $order);
    }

    /**
     * 等待审核或审核失败的商品列表 卖家中心使用
     *
     * @param array $condition 条件
     * @param array $field 字段
     * @param string $page 分页
     * @param string $order 排序
     * @return array
     */
    public function getGoodsCommonWaitVerifyList($condition, $field = '*', $page = 10, $order = "goods_commonid desc") {
        if (!isset($condition['goods_verify'])) {
            $condition['goods_verify']  = array('neq', self::VERIFY1);
        }
        return $this->getGoodsCommonList($condition, $field, $page, $order);
    }

    /**
     * 公共商品列表，goods_show = 1 为出售中，goods_show = 0为未出售（仓库中，违规，等待审核）
     */
    public function getGoodsCommonAsGoodsShowList($condition, $field = '*') {
        return $this->getGoodsCommonList($condition, $field);
    }

    /**
     * 查询商品SUK及其店铺信息
     *
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getGoodsStoreList($condition, $field = '*') {
        $condition = $this->_getRecursiveClass($condition);
        return DB::table('bbc_goods')->alias("goods")->field($field)->join('bbc_vendor vendor','goods.vid = vendor.vid')->where($condition)->select();
    }

    /**
     * 计算商品库存
     *
     * @param array $goods_list
     * @return array|boolean
     */
    public function calculateStorage($goods_list) {
        // 计算库存
        if (!empty($goods_list)) {
            $goodsid_array = array();
            foreach ($goods_list as $value) {
                $goodscommonid_array[] = $value['goods_commonid'];
            }
            $goods_storage = $this->getGoodsList(array('goods_commonid' => array('in', $goodscommonid_array)), 'goods_storage,goods_commonid,gid,goods_storage_alarm', '', '', false);
            $storage_array = array();
            foreach ($goods_storage as $val) {
                if ($val['goods_storage_alarm'] != 0 && $val['goods_storage'] <= $val['goods_storage_alarm']) {
                    $storage_array[$val['goods_commonid']]['alarm'] = true;
                }
                $storage_array[$val['goods_commonid']]['sum'] += $val['goods_storage'];
                $storage_array[$val['goods_commonid']]['gid'] = $val['gid'];
            }
            return $storage_array;
        } else {
            return false;
        }
    }

    /**
     * 更新商品SUK数据
     *
     * @param array $update 更新数据
     * @param array $condition 条件
     * @return boolean
     */
    public function editGoods($update, $condition) {
        return $this->table('bbc_goods')->where($condition)->update($update);
    }


    /**
     * 更新商品数据
     * @param array $update 更新数据
     * @param array $condition 条件
     * @return boolean
     */
    public function editGoodsCommon($update, $condition) {
        return $this->table('goods_common')->where($condition)->update($update);
    }

    /**
     * 更新商品数据
     * @param array $update 更新数据
     * @param array $condition 条件
     * @return boolean
     */
    public function editGoodsCommonNoLock($update, $condition) {
        $condition['goods_lock'] = 0;
        return $this->table('goods_common')->where($condition)->update($update);
    }

    /**
     * 锁定商品
     * @param unknown $condition
     * @return boolean
     */
    public function editGoodsCommonLock($condition) {
        $update = array('goods_lock' => 1);
        return $this->table('goods_common')->where($condition)->update($update);
    }

    /**
     * 解锁商品
     * @param unknown $condition
     * @return boolean
     */
    public function editGoodsCommonUnlock($condition) {
        $update = array('goods_lock' => 0);
        return $this->table('goods_common')->where($condition)->update($update);
    }

    /**
     * 更新商品信息
     *
     * @param array $condition
     * @param array $update1
     * @param array $update2
     * @return boolean
     */
    public function editProduces($condition, $update1, $update2 = array()) {
        $update2 = empty($update2) ? $update1 : $update2;
        $return1 = $this->editGoodsCommon($update1, $condition);
        $return2 = $this->editGoods($update2, $condition);
        if ($return1 && $return2) {
            //如果审核不通过的话就发送通知
            if(!empty($update2)&&$update2['goods_verify']==0){
                $commonlist = $this->getGoodsCommonList($condition, 'goods_commonid,vid,goods_verifyremark', 0);
                foreach ($commonlist as $val) {
                    $param = array();
                    $param['common_id'] = $val['goods_commonid'];
                    $param['remark']= $val['goods_verifyremark'];
                    QueueClient::push('sendStoreMsg', array('code' => 'goods_verify', 'vid' => $val['vid'], 'param' => $param));
                }
            }
            return true;
        } else {

            return false;
        }
    }

    /**
     * 更新未锁定商品信息
     *
     * @param array $condition
     * @param array $update1
     * @param array $update2
     * @return boolean
     */
    public function editProducesNoLock($condition, $update1, $update2 = array()) {
        $update2 = empty($update2) ? $update1 : $update2;
        $condition['goods_lock'] = 0;
        $common_array = $this->getGoodsCommonList($condition);
        $common_array = array_under_reset($common_array, 'goods_commonid');
        $commonid_array = array_keys($common_array);
        $where = array();
        $where['goods_commonid'] = array('in', $commonid_array);
        $return1 = $this->editGoodsCommon($update1, $where);
        $return2 = $this->editGoods($update2, $where);
        if ($return1 && $return2) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 商品下架
     * @param array $condition 条件
     * @return boolean
     */
    public function editProducesOffline($condition){
        $update = array('goods_state' => self::STATE0);
        return $this->editProducesNoLock($condition, $update);
    }

    /**
     * 商品上架
     * @param array $condition 条件
     * @return boolean
     */
    public function editProducesOnline($condition){
        $update = array('goods_state' => self::STATE1);
        // 禁售商品、审核失败商品不能上架。
        $condition['goods_state'] = self::STATE0;
        $condition['goods_verify'] = array('neq', self::VERIFY0);
        return $this->editProduces($condition, $update);
    }

    /**
     * 违规下架
     *
     * @param array $update
     * @param array $condition
     * @return boolean
     */
    public function editProducesLockUp($update, $condition) {
        $update_param['goods_state'] = self::STATE10;
        $update = array_merge($update, $update_param);
        $return = $this->editProduces($condition, $update, $update_param);
        if ($return) {
            // 商品违规下架发送店铺消息
            $common_list = $this->getGoodsCommonList($condition, 'goods_commonid,vid,goods_stateremark', 0);
            foreach ($common_list as $val) {
                $param = array();
                $param['remark'] = $val['goods_stateremark'];
                $param['common_id'] = $val['goods_commonid'];
                QueueClient::push('sendStoreMsg', array('code' => 'goods_violation', 'vid' => $val['vid'], 'param' => $param));
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取单条商品SKU信息
     *
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getGoodsInfo($condition, $field = '*') {
        $re=  DB::table('bbc_goods')->field($field)->where($condition)->find();
        return $re;
    }

    /**
     * 查询出售中的商品详细信息及其促销信息
     * @param int $gid
     * @return array
     */
    public function getGoodsOnlineInfoAndPromotionById($gid) {
        $goods_info = $this->getGoodsInfoAndPromotionById($gid);
        if (empty($goods_info) || $goods_info['goods_state'] != self::STATE1 || $goods_info['goods_verify'] != self::VERIFY1) {
            return array();
        }
        return $goods_info;
    }
    /**
     * 查询商品详细信息及其促销信息
     * @param int $gid
     * @return array
     */
    public function getGoodsInfoAndPromotionById($gid) {
        $goods_info = $this->getGoodsInfoByID($gid);
        if (empty($goods_info)) {
            return array();
        }
        // 手机专享
        if (Config('promotion_allow') && APP_ID == 'mobile') {
            $goods_info['mbuy_info'] = Model('p_mbuy')->getSoleGoodsInfoOpenByGoodsID($goods_info['gid']);
        }

        //抢购
        if (Config('tuan_allow')) {
            $goods_info['tuan_info'] = Model('tuan')->getTuanInfoByGoodsCommonID($goods_info['goods_commonid']);
        }
        //限时折扣
        if (Config('promotion_allow') && empty($goods_info['tuan_info'])) {
            $goods_info['xianshi_info'] = Model('p_xianshi_goods')->getXianshiGoodsInfoByGoodsID($goods_info['gid']);
        }
        return $goods_info;
    }
    /**
     * 获取单条商品SKU信息
     *
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getGoodsOnlineInfo($condition, $field = '*') {
        $condition['goods_state']   = self::STATE1;
        $condition['goods_verify']  = self::VERIFY1;
        $goods_info =  DB::table('bbc_goods')->field($field)->where($condition)->find();


        return $goods_info;

    }

    /**
     * 获取单条商品SKU信息，goods_show = 1 为出售中，goods_show = 0为未出售（仓库中，违规，等待审核）
     *
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getGoodsAsGoodsShowInfo($condition, $field = '*') {
        $field = $this->_asGoodsShow($field);
        return $this->getGoodsInfo($condition, $field);
    }


    /**
     * 获取单条商品信息
     *
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getGoodsCommonInfo($condition, $field = '*') {
        return DB::table('bbc_goods_common')->field($field)->where($condition)->find();
    }

    /**
     * 取得商品详细信息（优先查询缓存）
     * 如果未找到，则缓存所有字段
     * @param int $goods_commonid
     * @param string $fields 需要取得的缓存键值, 例如：'*','goods_name,store_name'
     * @return array
     */
    public function getGoodsCommonInfoByID($goods_commonid, $fields = '*') {
        $common_info = $this->_rGoodsCommonCache($goods_commonid, $fields);
        if (empty($common_info)) {
            $common_info = $this->getGoodsCommonInfo(array('goods_commonid'=>$goods_commonid));
            $this->_wGoodsCommonCache($goods_commonid, $common_info);
        }
        return $common_info;
    }


    /**
     * 获取单条商品信息，goods_show = 1 为出售中，goods_show = 0为未出售（仓库中，违规，等待审核）
     *
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getGoodeCommonAsGoodsShowInfo($condition, $field = '*') {
        $field = $this->_asGoodsShow($field);
        return $this->getGoodsCommonInfo($condition, $field);
    }

    /**
     * 获取单条商品信息
     *
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getGoodsDetail($gid,$field='*',$member_id=null)
    {
        if ($gid <= 0) {
            return null;
        }
        $result1 = $this->getGoodsAsGoodsShowInfo(array('gid' => $gid),$field);

        if (empty($result1)) {
            return null;
        }
        $result2 = $this->getGoodeCommonAsGoodsShowInfo(array('goods_commonid' => $result1['goods_commonid']));

        if($result2['course_type']==2) {

        }




        $goods_info = array_merge($result2, $result1);
        $goods_info['spec_value'] = unserialize($goods_info['spec_value']);
        $goods_info['spec_name'] = unserialize($goods_info['spec_name']);
        $goods_info['goods_spec'] = unserialize($goods_info['goods_spec']);
        $goods_info['goods_attr'] = unserialize($goods_info['goods_attr']);
        if($goods_info['duration']){
            $goods_info['duration'] = Sec2Time($goods_info['duration']);
        }


        // 查询所有规格商品
        $spec_array = $this->getGoodsList(array('goods_commonid' => $goods_info['goods_commonid']), 'goods_spec,gid,vid,goods_image,color_id,goods_storage');
        if ($goods_info['goods_type'] == 1) {
            $spec_list = array();       // 各规格商品地址，js使用
            $spec_list_mobile = array();       // 各规格商品地址，js使用
            $spec_image = array();      // 各规格商品主图，规格颜色图片使用
            $spec_new = array();
            foreach ($spec_array as $key => $value) {
                $s_array = unserialize($value['goods_spec']);
                $tmp_array = array();
                $i=0;
                $count = count($s_array);
                $last_s = array();
                if (!empty($s_array) && is_array($s_array)) {
                    foreach ($s_array as $k => $v) {
                        if ($i == $count-1) {
                            $last_s[] = $k;
                        }else{
                            $tmp_array[] = $k;
                        }
                        $i++;
                    }
                }
                sort($tmp_array);
                sort($last_s);
                $spec_sign = implode('|', $tmp_array);
                // 给最后一行的类型 赋值 库存
                foreach ($last_s as $l_s_key => $l_s_value) {
                    $spec_new[$spec_sign][$l_s_value] = $value['goods_storage'];
                }
                $tpl_spec = array();
                $tpl_spec['sign'] = $spec_sign;
                $spec_list[] = $tpl_spec;
                $spec_list_mobile[$spec_sign] = $value['gid'];
                $spec_image[$value['color_id']] = thumb($value, 60);
            }
            foreach ($spec_list as $key => $value) {
                $spec_list[$key]['data'] = $spec_new[$value['sign']];
            }
            $spec_list = json_encode($spec_list);
        }else{
            $spec_list = array();       // 各规格商品地址，js使用
            $spec_list_mobile = array();       // 各规格商品地址，js使用
            $spec_image = array();      // 各规格商品主图，规格颜色图片使用
            foreach ($spec_array as $key => $value) {
                $s_array = unserialize($value['goods_spec']);
                $tmp_array = array();
                if (!empty($s_array) && is_array($s_array)) {
                    foreach ($s_array as $k => $v) {
                        $tmp_array[] = $k;
                    }
                }
                sort($tmp_array);
                $spec_sign = implode('|', $tmp_array);
                $tpl_spec = array();
                $tpl_spec['sign'] = $spec_sign;
                $tpl_spec['url'] = urlShop('goods', 'index', array('gid' => $value['gid']));
                $spec_list[] = $tpl_spec;
                $spec_list_mobile[$spec_sign] = $value['gid'];
                $spec_image[$value['color_id']] = thumb($value, 60);
            }
            $spec_list = json_encode($spec_list);
        }
        // slodon_放大镜开始
        $image_more = $this->getGoodsImageByKey($goods_info['goods_commonid'] . '|' . $goods_info['color_id']);
        /*$goods_image = array();
        $goods_image_mobile = array();
        if (!empty($image_more)) {
            foreach ($image_more as $val) {
                $goods_image[] = array(cthumb($val['goods_image'], 60, $goods_info['vid']), cthumb($val['goods_image'], 350, $goods_info['vid']), cthumb($val['goods_image'], 1280, $goods_info['vid']));
                $goods_image_mobile[] = cthumb($val['goods_image'], 350, $goods_info['vid']);
            }
        } else {
            $goods_image[] = "{ title : '', levelA : '" . thumb($goods_info, 60) . "', levelB : '" . thumb($goods_info, 350) . "', levelC : '" . thumb($goods_info, 350) . "', levelD : '" . thumb($goods_info, 1280) . "'}";
            $goods_image_mobile[] = thumb($goods_info, 350);
        }*/
        // slodon_放大镜结束

        // 批发商品 无促销活动
        if ($goods_info['goods_type']!=1) {
            //团购
            if (Config('tuan_allow')) {
                $tuan_info = Model('tuan')->getTuanInfoByGoodsCommonID($goods_info['goods_commonid']);
                if (!empty($tuan_info)) {
                    $goods_info['promotion_type'] = 'tuan';
                    $goods_info['remark'] = $tuan_info['remark'];
                    $goods_info['promotion_price'] = $tuan_info['tuan_price'];
                    $goods_info['down_price'] = sldPriceFormat($goods_info['goods_price'] - $tuan_info['tuan_price']);
                    $goods_info['upper_limit'] = $tuan_info['upper_limit'];
                }
            }

            //限时折扣
            if (Config('promotion_allow') && empty($tuan_info)) {
                $pxianshiModel = new Pxianshigoods();
                $xianshi_info = $pxianshiModel->getXianshiGoodsInfoByGoodsID($gid);
                if (!empty($xianshi_info)) {
                    $goods_info['promotion_type'] = 'xianshi';
                    $goods_info['remark'] = $xianshi_info['xianshi_title'];
                    $goods_info['promotion_price'] = $xianshi_info['xianshi_price'];
                    $goods_info['down_price'] = sldPriceFormat($goods_info['goods_price'] - $xianshi_info['xianshi_price']);
                    $goods_info['lower_limit'] = $xianshi_info['lower_limit'];
                }
            }
            //拼团活动
            if (Config('sld_pintuan') && Config('pin_isuse')) {
                $pin_info = M('pin')->getTuanInfoByGoodsID_new($gid);
                if (!empty($pin_info)) {
                    $goods_info['promotion_type'] = 'pin';
                    $goods_info['remark'] = $goods_info['goods_name'];
                    $goods_info['promotion_price'] = $pin_info['sld_pin_price'];
                    $goods_info['down_price'] = sldPriceFormat($goods_info['goods_price'] - $pin_info['sld_pin_price']);
                    $goods_info['lower_limit'] = $pin_info['sld_max_buy'];
                }
            }
            //满即送
            $pmansongModel = new Favorable();
            $mansong_info = $pmansongModel->getMansongInfoByStoreID($goods_info['vid']);
            //手机专享
            $model_sole = new MBuy();
            $solegoods_info = $model_sole->getSoleGoodsInfo(array('vid' => $goods_info['vid'], 'gid' => $goods_info['gid']));
            if (!empty($solegoods_info)) {
                $mobile_info = $solegoods_info;
            }

            // 获取最终价格
            $goodsActivityModel = new GoodsActivity();
            $goods_info = $goodsActivityModel->rebuild_goods_data($goods_info,'pc');
        }
        // 商品受关注次数加1
        $_times = cookie('tm_visit_product');
        if (empty($_times)) {
            $this->editGoods(array('goods_click' => array('inc', 'goods_click + 1')), array('gid' => $gid));
            //setBbcCookie('tm_visit_product', 1);
            $goods_info['goods_click'] = intval($goods_info['goods_click']) + 1;
        }

        $result = array();
        $result['goods_info'] = $goods_info;
        $result['spec_list'] = $spec_list;
        $result['spec_list_mobile'] = $spec_list_mobile;
        $result['spec_image'] = $spec_image;
        if(isset($goods_image))
        $result['goods_image'] = $goods_image;
        if(isset($goods_image_mobile))
        $result['goods_image_mobile'] = $goods_image_mobile;
        // 批发商品 无促销活动
        if ($goods_info['goods_type']!=1) {
            $result['tuan_info'] = $tuan_info;
            $result['xianshi_info'] = $xianshi_info;
            $result['mansong_info'] = $mansong_info;
            if(isset($mobile_info))
            $result['mobile_info'] = $mobile_info;
            if(isset($pin_info))
            $result['pin_info'] = $pin_info;
        }

        $where = ['og.buyer_id' => $member_id,'og.gid'=>$gid];
        $where['o.order_state'] = 40;

        if ($goods_info['course_type']) {
            //如果是在线课的话 查看同commonid
            $gids            = $this->unit_gids($goods_info['goods_commonid']);
            $ids = "";
            foreach($gids as $k=>$v){
                $ids .= $v['gid'].",";
            }
            $ids = substr($ids,0,strlen($ids)-1);
            $where['og.gid'] = ['in', $gids];
        } else {
            $where['og.gid'] = $gid;
            $ids=$gid;
        }
        $where = "og.buyer_id=".$member_id." and og.gid in($ids) and o.order_state=40";
        $field = "og.rec_id,og.buyer_id,og.validity,m.member_name as teacher_name";

        $order_goods_info = DB::table('bbc_order')->alias("o")->join("bbc_order_goods og","o.order_id=og.order_id")->join("bbc_member m","og.teacher=m.member_id")->field($field)->order('og.validity desc')->where($where)->find();
        if(isset($order_goods_info['rec_id'])&& !empty($order_goods_info['rec_id'])){
            $result['goods_info']['is_virtual'] = 1;
        }

        if ($order_goods_info['course_type'] == 2 && $order_goods_info['validity'] < TIMESTAMP) {
            $order_goods_info['rec_id'] = null;
            $result['goods_info']['is_virtual'] = 1;
        }

        if($goods_info['course_type']==2) {
            if ($order_goods_info['validity'] < TIMESTAMP) {
                $order_goods_info = [];
            } else {

                if ($order_goods_info['validity']) {
                    $order_goods_info['validity'] = date('Y-m-d H:i:s', $order_goods_info['validity']);
                }


                $result['goods_info']['is_virtual'] = 1;

            }
        }


        $result['order_goods_info'] = $order_goods_info;
        if(isset($order_goods_info['rec_id'])){
            $order_goods_info = intval($order_goods_info['rec_id'] > 0);
            $types            = ['bs' => $goods_info['course_type'], 'txt' => lang('立即购买')];
    //        var_dump($types);die;


            if($goods_info['course_type']==1 || $goods_info['course_type']==2 || $goods_info['course_type']==3 ){
                switch ($goods_info['course_type']) {
                    case 1:
                        $types['txt'] = !empty($order_goods_info) ? lang('已报名') : lang('立即报名');
                        break;
                    case 2:
                        $types['txt'] = !empty($order_goods_info) ? lang('立即观看') : lang('立即购买');
                        break;
                    case 3:
                        $types['txt'] = !empty($order_goods_info) ? lang('立即阅读') : lang('立即购买');
                        break;
                    default:
                        $types['txt'] = lang('立即购买');
                        break;
                }

            }
            $result['types'] = $types;
        }








        return $result;
    }

    /**
     * 判断商品 在时间段内是否有其他活动
     *
     * @param int gid
     * @param int commonid
     * @param string $start  开始时间戳
     * @param string $end  结束时间戳
     * @param string $nokey  不判断哪个活动  tuan xianshi pin zhuanxiang
     * @return boolean
     */
    public function getOtherActivity($gid,$commonid,$start,$end,$nokey=null){

        $gidstr = ' = '.$gid;
        if(is_array($gid)){
            $gidarr=array();
            foreach ($gid as $v){
                $gidarr[] = $v;
            }
            $gidstr = ' in ('.join(',',$gidarr).')';
        }
        //团购
        if (Config('tuan_allow') && $nokey!='tuan') {
            $where = " NOT ((start_time < $start) OR (end_time > $end)) and gid $gidstr and goods_commonid = $commonid";
            if($tuan_info = Model('tuan')->where($where)->count()>0){
                return $tuan_info;
            }
        }

        //限时折扣
        if (Config('promotion_allow' && $nokey!='xianshi')) {
            $where = " NOT ((start_time < $start) OR (end_time > $end)) and gid $gidstr ";
            if($xianshi_info = Model('p_xianshi_goods')->where($where)->count()>0){
                return $xianshi_info;
            }
        }
        //拼团活动
        if (Config('sld_pintuan') && Config('pin_isuse') && $nokey!='pin') {
            $where = " NOT ((sld_start_time < $start) OR (sld_end_time > $end)) and sld_goods_id = $commonid ";
            if($pin_info = M('pin')->table('pin')->where($where)->count()){
                return $pin_info;
            }
        }
        //阶梯拼团
        if (Config('sld_pintuan_ladder') && Config('pin_ladder_isuse') && $nokey!='pin_ladder') {
            $where = " NOT ((sld_start_time < $start) OR (sld_end_time > $end)) and sld_goods_id = $commonid ";
            if($pin_info = $this->table('pin_ladder')->where($where)->count()){
                return $pin_info;
            }
        }

        //预售
        if (Config('pin_presale_isuse') && Config('sld_presale_system') && $nokey!='sld_presale') {
            $where = " NOT ((pre_start_time < $start) OR (pre_end_time > $end)) and pre_goods_commonid = $commonid ";

            if($pin_info = $this->table('presale')->where($where)->count()){
                return $pin_info;
            }
        }

        //手机专享
        if ( $nokey!='zhuanxiang') {
            $where = "  gid $gidstr ";
            if ($solegoods_info = Model('p_mbuy')->table('p_mbuy_goods')->where($where)->count() > 0) {
                return $solegoods_info;
            }
        }

        return 0;
    }

    /**
     * 获得商品SKU某字段的和
     *
     * @param array $condition
     * @param string $field
     * @return boolean
     */
    public function getGoodsSum($condition, $field) {
        return $this->table('goods')->where($condition)->sum($field);
    }

    /**
     * 获得商品SKU数量
     *
     * @param array $condition
     * @param string $field
     * @return int
     */
    public function getGoodsCount($condition) {
        return $this->table('goods')->where($condition)->count();
    }

    /**
     * 获得出售中商品SKU数量
     *
     * @param array $condition
     * @param string $field
     * @return int
     */
    public function getGoodsOnlineCount($condition, $field = '*', $group = '') {
        if(is_array($condition)) {
            $condition['goods_state'] = true;
            $condition['goods_verify'] = true;
        }else{
            $condition.=" and goods_state=1 and goods_verify=1 ";
        }
        //echo $condition;
        $result=DB::name('goods')->where($condition)->group($group)->select();
        return count($result);
    }
    /**
     * 获得商品数量
     *
     * @param array $condition
     * @param string $field
     * @return int
     */
    public function getGoodsCommonCount($condition) {
        return $this->table('goods_common')->where($condition)->count();
    }

    /**
     * 出售中的商品数量
     *
     * @param array $condition
     * @return int
     */
    public function getGoodsCommonOnlineCount($condition) {
        $condition['goods_state']   = self::STATE1;
        $condition['goods_verify']  = self::VERIFY1;
        return $this->getGoodsCommonCount($condition);
    }

    /**
     * 仓库中的商品数量
     *
     * @param array $condition
     * @return int
     */
    public function getGoodsCommonOfflineCount($condition) {
        $condition['goods_state']   = self::STATE0;
        $condition['goods_verify']  = self::VERIFY1;
        return $this->getGoodsCommonCount($condition);
    }

    /**
     * 等待审核的商品数量
     *
     * @param array $condition
     * @return int
     */
    public function getGoodsCommonWaitVerifyCount($condition) {
        $condition['goods_verify']  = self::VERIFY10;
        return $this->getGoodsCommonCount($condition);
    }

    /**
     * 审核是被的商品数量
     *
     * @param array $condition
     * @return int
     */
    public function getGoodsCommonVerifyFailCount($condition) {
        $condition['goods_verify']  = self::VERIFY0;
        return $this->getGoodsCommonCount($condition);
    }

    /**
     * 违规下架的商品数量
     *
     * @param array $condition
     * @return int
     */
    public function getGoodsCommonLockUpCount($condition) {
        $condition['goods_state']   = self::STATE10;
        $condition['goods_verify']  = self::VERIFY1;
        return $this->getGoodsCommonCount($condition);
    }

    /**
     * 商品图片列表
     *
     * @param array $condition
     * @param array $order
     * @param string $field
     * @return array
     */
    public function getGoodsImageList($condition, $field = '*', $order = 'is_default desc,goods_image_sort asc') {
        $this->cls();
        return DB::table('bbc_goods_images')->field($field)->where($condition)->order($order)->select();
    }
    /**
     * 清空MODEL中的options、table_name属性
     *
     */
    public function cls(){
        $this->options = array();
        $this->table_name = '';
        return $this;
    }
    /**
     * 获取指定分类指定店铺下的随机商品列表
     *
     * @param int $gcId 一级分类ID
     * @param int $storeId 店铺ID
     * @param int $notEqualGoodsId 此商品ID除外
     * @param int $size 列表最大长度
     *
     * @return array|null
     */
    public function getGoodsGcStoreRandList($gcId, $storeId, $notEqualGoodsId = 0, $size = 4) {
        $condition = array(
            'vid' => (int) $storeId,
            'gc_id_1' => (int) $gcId,
        );

        if ($notEqualGoodsId > 0) {
            $condition['gid'] = array('neq', (int) $notEqualGoodsId);
        }

        $condition['goods_type'] = 0;
        //return $this->getGoodsOnlineList($condition, '*', 0, 'rand()', $size,'goods_commonid');
        return $this->getGoodsOnlineList($condition, '*', 0, '', $size,'goods_commonid');
    }


    /**
     * 浏览过的商品
     *
     * @return array
     */
    public function getViewedGoodsList() {
        //取浏览过产品的cookie(最大四组)
        $viewed_goods = array();
        $cookie_i = 0;

        if(cookie('viewed_goods')){
            $string_viewed_goods = decrypt(cookie('viewed_goods'),MD5_KEY);
            if (get_magic_quotes_gpc()) $string_viewed_goods = stripslashes($string_viewed_goods);//去除斜杠
            $cookie_array = array_reverse(unserialize($string_viewed_goods));
            $goodsid_array = array();
            foreach ((array)$cookie_array as $k=>$v){
                $info = explode("-", $v);
                if (is_numeric($info[0])){
                    $goodsid_array[] = intval($info[0]);
                }
            }
            //虚拟库存
            if(C('virtual_sale')){
                $field = 'gid, goods_name, goods_price, goods_image, vid,goods_salenum+virtual_sale as goods_salenum,is_free';
            }else{
                $field = 'gid, goods_name, goods_price, goods_image, vid,goods_salenum,is_free';
            }
            $viewed_list    = $this->getGoodsList(array('gid' => array('in', $goodsid_array)),  $field,'goods_commonid');

            // 获取最终价格
            $viewed_list = Model('goods_activity')->rebuild_goods_data($viewed_list,'pc');

            foreach ((array)$viewed_list as $val){
                $viewed_goods[] = array(
                    "gid"      => $val['gid'],
                    "goods_name"    => $val['goods_name'],
                    "goods_image"   => $val['goods_image'],
                    "goods_price"   => $val['show_price'],
                    "vid"      => $val['vid'],
                    "is_free"      => $val['is_free'],
                    "goods_salenum"      => $val['goods_salenum']
                );
            }
        }

        return $viewed_goods;
    }

    /**
     * 删除商品SKU信息
     *
     * @param array $condition
     * @return boolean
     */
    public function delGoods($condition) {
        $goods_list = $this->getGoodsList($condition, 'gid,vid');
        if (!empty($goods_list)) {
            foreach ($goods_list as $val) {
                delete_file(BASE_UPLOAD_PATH.DS.ATTACH_STORE.DS.$goods_list['vid'].DS.$goods_list['gid'].'.png');
            }
        }
        return $this->table('goods')->where($condition)->delete();
    }

    /**
     * 删除商品图片表信息
     *
     * @param array $condition
     * @return boolean
     */
    public function delGoodsImages($condition) {
        return $this->table('goods_images')->where($condition)->delete();
    }

    /**
     * 商品删除及相关信息
     *
     * @param   array $condition 列表条件
     * @return boolean
     */
    public function delGoodsAll($condition) {
        $goods_list = $this->getGoodsList($condition, 'gid,goods_commonid,vid');
        if (empty($goods_list)) {
            return false;
        }
        $goodsid_array = array();
        $commonid_array = array();
        foreach ($goods_list as $val) {
            $goodsid_array[] = $val['gid'];
            $commonid_array[] = $val['goods_commonid'];
            // 删除二维码
            unlink(BASE_UPLOAD_PATH.DS.ATTACH_STORE.DS.$val['vid'].DS.$val['gid'].'.png');
        }
        $commonid_array = array_unique($commonid_array);

        // 删除商品表数据
        $this->delGoods(array('gid' => array('in', $goodsid_array)));
        // 删除商品公共表数据
        $this->table('goods_common')->where(array('goods_commonid' => array('in', $commonid_array)))->delete();
        // 删除商品图片表数据
        $this->delGoodsImages(array('goods_commonid' => array('in', $commonid_array)));
        // 删除属性关联表数据
        $this->table('goods_attr_index')->where(array('gid' => array('in', $goodsid_array)))->delete();
        // 删除买家收藏表数据
        $this->table('favorites')->where(array('fav_id' => array('in', $goodsid_array), 'fav_type' => 'goods'))->delete();
        // 删除优惠套装商品
        Model('p_bundling')->delBundlingGoods(array('gid' => array('in', $goodsid_array)));
        // 优惠套餐活动下架
        Model('p_bundling')->editBundlingCloseByGoodsIds(array('gid' => array('in', $goodsid_array)));
        // 推荐展位商品
        Model('p_booth')->delBoothGoods(array('gid' => array('in', $goodsid_array)));

        return true;
    }

    /**
     * 删除未锁定商品
     * @param unknown $condition
     */
    public function delGoodsNoLock($condition) {
        $condition['goods_lock'] = 0;
        $common_array = $this->getGoodsCommonList($condition, 'goods_commonid');
        $common_array = array_under_reset($common_array, 'goods_commonid');
        $commonid_array = array_keys($common_array);
        return $this->delGoodsAll(array('goods_commonid' => array('in', $commonid_array)));
    }

    /**
     * goods_show = 1 为出售中，goods_show = 0为未出售（仓库中，违规，等待审核）
     *
     * @param string $field
     * @return string
     */
    private function _asGoodsShow($field) {
        if($field){
            return $field.',(goods_state=' . self::STATE1 . ' && goods_verify=' . self::VERIFY1 . ') as goods_show';
        }else{
            return $field;
        }

    }

    /**
     * 获得商品子分类的ID
     * @param array $condition
     * @return array
     */
    private function _getRecursiveClass($condition){
        if (isset($condition['gc_id']) && !is_array($condition['gc_id'])) {
            $gc_list = $this->H('goods_class') ? $this->H('goods_class') : $this->H('goods_class', true);
            if (!empty($gc_list[$condition['gc_id']])) {
                $gc_id[] = $condition['gc_id'];
                $gcchild_id = empty($gc_list[$condition['gc_id']]['child']) ? array() : explode(',', $gc_list[$condition['gc_id']]['child']);
                $gcchildchild_id = empty($gc_list[$condition['gc_id']]['childchild']) ? array() : explode(',', $gc_list[$condition['gc_id']]['childchild']);
                $gc_id = array_merge($gc_id, $gcchild_id, $gcchildchild_id);
                $condition['gc_id'] = array('in', $gc_id);
            }
        }
        return $condition;
    }

    public function getkeyGoods($condition,$page,$rows){
        if($condition['hot']){
            $data=$this->getGoodsOnlineList('', $field = '*', $page, $order ='goods_addtime desc,goods_salenum desc', $rows, $group = '', $lock = false, $count = 0);
            return $data;
        }
        if($condition['discount']){
            // $data=$this->getGoodsOnlineList(array('goods_promotion_type'=>'0'), $field = '*',$page,'gid desc',500, $page,false,$rows);
            // $page_count = $this->gettotalpage();
            // $b=mobile_page($page_count)
            $data=$this->table('goods')->field('*')->where(array('goods_promotion_type'=>'0'))->group($page)->order('gid desc')->limit($rows,$page)->lock($lock)->select();
            return $data;
        }
    }


    /**
     * 由ID取得在售单个虚拟商品信息
     * @param unknown $gid
     * @param string $field 需要取得的缓存键值, 例如：'*','goods_name,store_name'
     * @return array
     */
    public function getVirtualGoodsOnlineInfoByID($gid) {
        $goods_info = $this->getGoodsInfoByID($gid,'*');
        return $goods_info['is_virtual'] == 1 && $goods_info['virtual_indate'] >= TIMESTAMP ? $goods_info : array();
    }

    /**
     * 取得商品详细信息（优先查询缓存）（在售）
     * 如果未找到，则缓存所有字段
     * @param int $gid
     * @param string $field 需要取得的缓存键值, 例如：'*','goods_name,store_name'
     * @return array
     */
    public function getGoodsOnlineInfoByID($gid, $field = '*') {
        if ($field != '*') {
            $field .= ',goods_state,goods_verify';
        }
        $goods_info = $this->getGoodsInfoByID($gid,trim($field,','));
        if ($goods_info['goods_state'] != self::STATE1 || $goods_info['goods_verify'] != self::VERIFY1) {
            $goods_info = array();
        }
        return $goods_info;
    }
    /**
     * 取得商品详细信息（优先查询缓存）
     * 如果未找到，则缓存所有字段
     * @param int $gid
     * @param string $fields 需要取得的缓存键值, 例如：'*','goods_name,store_name'
     * @return array
     */
    public function getGoodsInfoByID($gid, $fields = '*') {
        $goods_info = $this->_rGoodsCache($gid, $fields);
        if (empty($goods_info)) {
            $goods_info = $this->getGoodsInfo(array('gid'=>$gid), $fields);
            $this->_wGoodsCache($gid, $goods_info);
        }
        return $goods_info;
    }
    /**
     * 验证是否为普通商品
     * @param array $goods 商品数组
     * @return boolean
     */
    public function checkIsGeneral($goods) {
        if ($goods['is_virtual'] == 1 || $goods['is_fcode'] == 1 || $goods['is_presell'] == 1 || $goods['is_book'] == 1) {
            return false;
        }
        return true;
    }
    public function checkOnline($goods) {
        if ($goods['goods_state'] == 1 && $goods['goods_verify'] == 1) {
            return true;
        }
        return false;
    }
    /**
     * 验证是否允许送赠品
     * @param unknown $goods
     * @return boolean
     */
    public function checkGoodsIfAllowGift($goods) {
        if ($goods['is_virtual'] == 1) {
            return false;
        }
        return true;
    }
    /**
     * 获得商品规格数组
     * @param unknown $common_id
     */
    public function getGoodsSpecListByCommonId($common_id) {
        $spec_list = $this->_rGoodsSpecCache($common_id);
        if (empty($spec_list)) {
            $spec_array = $this->getGoodsList(array('goods_commonid' => $common_id), 'goods_spec,gid,vid,goods_image,color_id');
            $spec_list['spec'] = serialize($spec_array);
            $this->_wGoodsSpecCache($common_id, $spec_list);
        }
        $spec_array = unserialize($spec_list['spec']);
        return $spec_array;
    }
    /**
     * 获得商品图片数组
     * @param int $gid
     * @param array $condition
     */
    public function getGoodsImageByKey($key) {
        $image_list = $this->_rGoodsImageCache($key);
        if (empty($image_list)) {
            $array = explode('|', $key);
            list($common_id, $color_id) = $array;
            $image_more = $this->getGoodsImageList(array('goods_commonid' => $common_id, 'color_id' => $color_id), 'goods_image');
            $image_list['image'] = serialize($image_more);
            $this->_wGoodsImageCache($key, $image_list);
        }
        $image_more = unserialize($image_list['image']);
        return $image_more;
    }
    /**
     * 读取商品缓存
     * @param int $gid
     * @param string $fields
     * @return array
     */
    private function _rGoodsCache($gid, $fields) {
        $base =new Base();return $base->rcache($gid, 'goods', $fields);
    }
    /**
     * 写入商品缓存
     * @param int $gid
     * @param array $goods_info
     * @return boolean
     */
    private function _wGoodsCache($gid, $goods_info) {
        $base =new Base();
        return $base->wcache($gid, $goods_info, 'goods');
    }
    /**
     * 删除商品缓存
     * @param int $gid
     * @return boolean
     */
    private function _dGoodsCache($gid) {
        $base =new Base();
        return $base->dcache($gid, 'goods');
    }
    /**
     * 读取商品公共缓存
     * @param int $goods_commonid
     * @param string $fields
     * @return array
     */
    private function _rGoodsCommonCache($goods_commonid, $fields) {
        $base =new Base();return $base->rcache($goods_commonid, 'goods_common', $fields);
    }
    /**
     * 写入商品公共缓存
     * @param int $goods_commonid
     * @param array $common_info
     * @return boolean
     */
    private function _wGoodsCommonCache($goods_commonid, $common_info) {
        $base =new Base();$base =new Base();return $base->wcache($goods_commonid, $common_info, 'goods_common');
    }
    /**
     * 删除商品公共缓存
     * @param int $goods_commonid
     * @return boolean
     */
    private function _dGoodsCommonCache($goods_commonid) {
        $base =new Base();$base =new Base();return $base->dcache($goods_commonid, 'goods_common');
    }
    /**
     * 读取商品规格缓存
     * @param int $goods_commonid
     * @param string $fields
     * @return array
     */
    private function _rGoodsSpecCache($goods_commonid) {
        $base =new Base();return $base->$base->rcache($goods_commonid, 'goods_spec');
    }
    /**
     * 写入商品规格缓存
     * @param int $goods_commonid
     * @param array $spec_list
     * @return boolean
     */
    private function _wGoodsSpecCache($goods_commonid, $spec_list) {
        $base =new Base();$base =new Base();return $base->wcache($goods_commonid, $spec_list, 'goods_spec');
    }
    /**
     * 删除商品规格缓存
     * @param int $goods_commonid
     * @return boolean
     */
    private function _dGoodsSpecCache($goods_commonid) {
        $base =new Base();$base =new Base();return $base->$base->dcache($goods_commonid, 'goods_spec');
    }
    /**
     * 读取商品图片缓存
     * @param int $key ($goods_commonid .'|'. $color_id)
     * @param string $fields
     * @return array
     */
    private function _rGoodsImageCache($key) {
        $base =new Base();return $base->rcache($key, 'goods_image');
    }
    /**
     * 写入商品图片缓存
     * @param int $key ($goods_commonid .'|'. $color_id)
     * @param array $image_list
     * @return boolean
     */
    private function _wGoodsImageCache($key, $image_list) {
        $base =new Base();$base =new Base();return $base->wcache($key, $image_list, 'goods_image');
    }
    /**
     * 删除商品图片缓存
     * @param int $key ($goods_commonid .'|'. $color_id)
     * @return boolean
     */
    private function _dGoodsImageCache($key) {
        $base =new Base();
        return $base->dcache($key, 'goods_image');
    }
    /**
     * 获取单条商品信息
     *
     * @param int $gid
     * @return array
     */
    public function getGoodsDetail_new($gid) {
        if($gid <= 0) {
            return null;
        }
        $result1 = $this->getGoodsInfoAndPromotionById($gid);
        if (empty($result1)) {
            return null;
        }
        if ($result1['goods_body'] == '') unset($result1['goods_body']);
        if ($result1['mobile_body'] == '') unset($result1['mobile_body']);
        $result2 = $this->getGoodsCommonInfoByID($result1['goods_commonid']);
        $goods_info = array_merge($result2, $result1);
        $goods_info['spec_value'] = unserialize($goods_info['spec_value']);
        $goods_info['spec_name'] = unserialize($goods_info['spec_name']);
        $goods_info['goods_spec'] = unserialize($goods_info['goods_spec']);
        $goods_info['goods_attr'] = unserialize($goods_info['goods_attr']);
        $goods_info['goods_custom'] = unserialize($goods_info['goods_custom']);
        // 手机商品描述
        if ($goods_info['mobile_body'] != '') {
            $mobile_body_array = unserialize($goods_info['mobile_body']);
            $mobile_body = '';
            if (is_array($mobile_body_array)) {
                foreach ($mobile_body_array as $val) {
                    switch ($val['type']) {
                        case 'text':
                            $mobile_body .= '<div>' . $val['value'] . '</div>';
                            break;
                        case 'image':
                            $mobile_body .= '<img src="' . $val['value'] . '">';
                            break;
                    }
                }
            }
            $goods_info['mobile_body'] = $mobile_body;
        }
        // 查询所有规格商品
        $spec_array = $this->getGoodsSpecListByCommonId($goods_info['goods_commonid']);
        $spec_list = array();       // 各规格商品地址，js使用
        $spec_list_mobile = array();       // 各规格商品地址，js使用
        $spec_image = array();      // 各规格商品主图，规格颜色图片使用
        foreach ($spec_array as $key => $value) {
            $s_array = unserialize($value['goods_spec']);
            $tmp_array = array();
            if (!empty($s_array) && is_array($s_array)) {
                foreach ($s_array as $k => $v) {
                    $tmp_array[] = $k;
                }
            }
            sort($tmp_array);
            $spec_sign = implode('|', $tmp_array);
            $tpl_spec = array();
            $tpl_spec['sign'] = $spec_sign;
            $tpl_spec['url'] = urlShop('goods', 'index', array('gid' => $value['gid']));
            $spec_list[] = $tpl_spec;
            $spec_list_mobile[$spec_sign] = $value['gid'];
            $spec_image[$value['color_id']] = thumb($value, 60);
        }
        $spec_list = json_encode($spec_list);
        // 商品多图
        //  $image_more = $this->getGoodsImageByKey($goods_info['goods_commonid'] . '|' . $goods_info['color_id']);
        //  $goods_image = array();
        //  $goods_image_mobile = array();
        //  if (!empty($image_more)) {
        //   array_splice($image_more, 5);
        //   foreach ($image_more as $val) {
        //    $goods_image[] = "{ title : '', levelA : '".cthumb($val['goods_image'], 60, $goods_info['vid'])."', levelB : '".cthumb($val['goods_image'], 360, $goods_info['vid'])."', levelC : '".cthumb($val['goods_image'], 360, $goods_info['vid'])."', levelD : '".cthumb($val['goods_image'], 1280, $goods_info['vid'])."'}";
        //         $goods_image_mobile[] = cthumb($val['goods_image'], 360, $goods_info['vid']);
        //    }
        // } else {
        //     $goods_image[] = "{ title : '', levelA : '".thumb($goods_info, 60)."', levelB : '".thumb($goods_info, 360)."', levelC : '".thumb($goods_info, 360)."', levelD : '".thumb($goods_info, 1280)."'}";
        //$goods_image_mobile[] = thumb($goods_info, 360);
        // }
        // 新版放大镜开始
        $image_more = $this->getGoodsImageByKey($goods_info['goods_commonid'] . '|' . $goods_info['color_id']);
        $goods_image = array();
        $goods_image_mobile = array();
        if (!empty($image_more)) {
            foreach ($image_more as $val) {
                $goods_image[] = array(cthumb($val['goods_image'], 60, $goods_info['vid']),cthumb($val['goods_image'], 360, $goods_info['vid']),cthumb($val['goods_image'], 1280, $goods_info['vid']));
                $goods_image_mobile[] = cthumb($val['goods_image'], 360, $goods_info['vid']);
            }
        } else {
            $goods_image[] = "{ title : '', levelA : '".thumb($goods_info, 60)."', levelB : '".thumb($goods_info, 360)."', levelC : '".thumb($goods_info, 360)."', levelD : '".thumb($goods_info, 1280)."'}";
            $goods_image_mobile[] = thumb($goods_info, 360);
        }
        print_r($goods_info);die;
        // 新版结束
        if ($goods_info['is_book'] != '1') {
            //限时折扣
            if (!empty($goods_info['xianshi_info'])) {
                $goods_info['promotion_type'] = 'xianshi';
                $goods_info['title'] = $goods_info['xianshi_info']['xianshi_title'];
                $goods_info['remark'] = $goods_info['xianshi_info']['xianshi_title'];
                $goods_info['promotion_price'] = $goods_info['xianshi_info']['xianshi_price'];
                $goods_info['down_price'] = sldPriceFormat($goods_info['goods_price'] - $goods_info['xianshi_info']['xianshi_price']);
                $goods_info['lower_limit'] = $goods_info['xianshi_info']['lower_limit'];
                $goods_info['explain'] = $goods_info['xianshi_info']['xianshi_explain'];
                unset($goods_info['xianshi_info']);
            }
            //团购
            if (!empty($goods_info['tuan_info'])) {
                $goods_info['promotion_type'] = 'tuan';
                $goods_info['title'] = '团购';
                $goods_info['remark'] = $goods_info['tuan_info']['remark'];
                $goods_info['promotion_price'] = $goods_info['tuan_info']['tuan_price'];
                $goods_info['down_price'] = sldPriceFormat($goods_info['goods_price'] - $goods_info['tuan_info']['tuan_price']);
                $goods_info['upper_limit'] = $goods_info['tuan_info']['upper_limit'];
                unset($goods_info['tuan_info']);
            }
            // 手机专享
            if (!empty($goods_info['mbuy_info'])) {
                $goods_info['promotion_type'] = 'sole';
                $goods_info['title'] = '手机专享';
                $goods_info['promotion_price'] = $goods_info['mbuy_info']['mbuy_price'];
                unset($goods_info['mbuy_info']);
            }
            // 加价购
            if (!empty($goods_info['jjg_info'])) {
                $jjgFirstLevel = $goods_info['jjg_info']['firstLevel'];
                if ($jjgFirstLevel && $jjgFirstLevel['mincost'] > 0) {
                    $goods_info['jjg_explain'] = sprintf(
                        '购满<em>&yen;%.2f</em>，再加<em>&yen;%.2f</em>，可换购商品',
                        $jjgFirstLevel['mincost'],
                        $jjgFirstLevel['plus']
                    );
                }
            }
            // 验证是否允许送赠品
            if ($this->checkGoodsIfAllowGift($goods_info)) {
                $gift_array = Model('goods_gift')->getGoodsGiftListByGoodsId($gid);
                if (!empty($gift_array)) {
                    $goods_info['have_gift'] = 'gift';
                }
            }
            //满即送
            $mansong_info = ($goods_info['is_virtual'] == 1) ? array() : Model('p_mansong')->getMansongInfoByStoreID($goods_info['vid']);
        }
        // 加入购物车按钮
        $goods_info['cart'] = true;
        //虚拟、F码、预售不显示加入购物车，不显示门店
        if ($goods_info['is_virtual'] == 1 || $goods_info['is_fcode'] == 1 || $goods_info['is_presell'] == 1 || $goods_info['is_book'] == 1) {
            $goods_info['is_chain'] = 0;
            $goods_info['cart'] = false;
        }
        // 立即购买按钮
        $goods_info['buynow'] = true;
        // 加价购不显示立即购买按钮
        if (!empty($goods_info['jjg_info'])) {
            $goods_info['buynow'] = false;
        }
        // 立即购买文字显示
        $goods_info['buynow_text'] = '立即购买';
        if ($goods_info['is_presell'] == 1) {
            $goods_info['buynow_text'] = '预售购买';
        } elseif ($goods_info['is_book'] == 1) {
            $goods_info['buynow_text'] = '支付定金';
        } elseif ($goods_info['is_fcode'] == 1) {
            $goods_info['buynow_text'] = 'F码购买';
        }
        // 商品受关注次数加1
        $goods_info['goods_click'] = intval($goods_info['goods_click']) + 1;
        if (Config('cache_open')) {
            $base =new Base();
            $this->_wGoodsCache($gid, array('goods_click' => $goods_info['goods_click']));
            $base->wcache('updateRedisDate', array($gid => $goods_info['goods_click']), 'goodsClick');
        } else {
            $this->editGoodsById(array('goods_click' => array('exp', 'goods_click + 1')), $gid);
        }
        $result = array();
        $result['goods_info'] = $goods_info;
        $result['spec_list'] = $spec_list;
        $result['spec_list_mobile'] = $spec_list_mobile;
        $result['spec_image'] = $spec_image;
        $result['goods_image'] = $goods_image;
        $result['goods_image_mobile'] = $goods_image_mobile;
        $result['mansong_info'] = $mansong_info;
        $result['gift_array'] = $gift_array;
        return $result;
    }
    /**
     * 处理商品消费者保障服务信息
     */
    public function getGoodsContract($goods_list, $contract_item = array()){
        if (!$goods_list) {
            return array();
        }
        //查询消费者保障服务
        if (Config('contract_allow') == 1) {
            if (!$contract_item) {
                $contract_item = Model('contract')->getContractItemByCache();
            }
        }
        if (!$contract_item) {
            return $goods_list;
        }
        foreach ($goods_list as $k=>$v) {
            $v['contractlist'] = array();
            foreach($contract_item as $citem_k=>$citem_v){
                if($v["contract_$citem_k"] == 1){
                    $v['contractlist'][$citem_k] = $citem_v;
                }
            }
            $goods_list[$k] = $v;
        }
        return $goods_list;
    }
    //我的界面的推荐商品
    public function getRecGoods($page = 0,$condition,$field = '*',$order = 'gid desc',$limit = 0) {
        $condition['goods_state']   = self::STATE1;
        $condition['goods_verify']  = self::VERIFY1;
        $condition['goods_type'] = 0;
        return $this->table('goods')->field('*')->where($condition)->order($order)->page(10)->select();
    }
    //获取店铺的上新商品
    public function getNewGoods($vid=null,$page){
        $condition['goods_state']   = self::STATE1;
        $condition['goods_verify']  = self::VERIFY1;
        $condition['vid']      = $vid;
        $condition['goods_edittime']=array('gt',time()-300*24*3600);
        $condition['goods_type'] = 0;
        $result = $this->table('goods')->field('*')->where($condition)->group('goods_commonid')->order('gid desc')->page($page)->limit(10)->select();
        return $result;
    }
    //获取热销产品
    public function getCartRecGoods(){
        $condition['goods_state']   = self::STATE1;
        $condition['goods_verify']  = self::VERIFY1;
        $condition['goods_type'] = 0;
        return $this->table('goods')->field('*')->where($condition)->order('goods_salenum desc')->page(6)->select();
    }
    //获取店铺上新商品的数量
    public function getNewGoodsNum($vid=null){
        $condition['goods_state']   = self::STATE1;
        $condition['goods_verify']  = self::VERIFY1;
        $condition['vid']      = $vid;
        $condition['goods_edittime']=array('gt',time()-300*24*3600);
        $condition['goods_type'] = 0;
        return $this->table('goods')->field('count(*) as count')->where($condition)->group('goods_commonid')->order('gid desc')->find();

    }
// //wap端推荐店铺推荐商品
    public function getWapGoods($vid=null){
        $condition['goods_state']   = self::STATE1;
        $condition['goods_verify']  = self::VERIFY1;
        $condition['vid']      = $vid;
        $condition['goods_commend']=1;
        $condition['goods_type'] = 0;
        return $this->table('goods')->field('*')->where($condition)->order('gid desc')->select();
    }
    //wap商品收藏排行榜
    public function getStorageGoods($vid=null){
        $condition['goods_state']   = self::STATE1;
        $condition['goods_verify']  = self::VERIFY1;
        $condition['vid']      = $vid;
        $condition['goods_type'] = 0;
        return $this->table('goods')->field('*')->where($condition)->order('goods_collect desc,goods_salenum desc')->page(3)->select();
    }
    //根据商品id获取商品库存
    public function getStorageByGoodsId($gid=null){
        return  $this->table('goods')->field('goods_storage')->where(array('gid'=>$gid))->find();
    }
    /**
     * 发送店铺消息
     * @param string $code
     * @param int $vid
     * @param array $param
     */
    private function _sendStoreMsg($code, $vid, $param) {
        QueueClient::push('sendStoreMsg', array('code' => $code, 'vid' => $vid, 'param' => $param));
    }
    /**
     * 获取单条商品信息
     *app端（刘志远）
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getGoodsDetails($gid)
    {
        if ($gid <= 0) {
            return null;
        }
        $result1 = $this->getGoodsAsGoodsShowInfo(array('gid' => $gid));
        if (empty($result1)) {
            return null;
        }
        $result2 = $this->getGoodeCommonAsGoodsShowInfo(array('goods_commonid' => $result1['goods_commonid']));
        $goods_info = array_merge($result2, $result1);
        $goods_info['spec_value'] = unserialize($goods_info['spec_value']);
        $goods_info['spec_name'] = unserialize($goods_info['spec_name']);
        $goods_info['goods_spec'] = unserialize($goods_info['goods_spec']);
        $goods_info['goods_attr'] = unserialize($goods_info['goods_attr']);
        $goods_info['promotion_type']='';
        $goods_info['promotion_price']='';

        //商品标签
        if(!empty($goods_info['goods_label'])){
            $label=explode(',',$goods_info['goods_label']);
            if(!empty($label)){
                $label_arr=array();
                $model_label=Model('goods_label');
                $label_list=$model_label->getGoodsLabelAll();
                $i=0;
                foreach ($label_list as $key=>$value){
                    if(in_array($value['id'],$label)){
                        $label_arr[$i]['label_name']=$value['label_name'];
                        $label_arr[$i]['label_desc']=$value['label_desc'];
                        $label_arr[$i]['label_id']=$value['id'];
                        $label_arr[$i]['img']=$value['img'];
                        $i++;
                    }

                }
                $goods_info['goods_label']=$label_arr;
            }

        }

        // 查询所有规格商品
        $spec_array = $this->getGoodsList(array('goods_commonid' => $goods_info['goods_commonid']), 'goods_spec,gid,vid,goods_image,color_id');
        $spec_list = array();       // 各规格商品地址，js使用
        $spec_list_mobile = array();       // 各规格商品地址，js使用
        $spec_image = array();      // 各规格商品主图，规格颜色图片使用
        foreach ($spec_array as $key => $value) {
            $s_array = unserialize($value['goods_spec']);
            $tmp_array = array();
            if (!empty($s_array) && is_array($s_array)) {
                foreach ($s_array as $k => $v) {
                    $tmp_array[] = $k;
                }
            }
            sort($tmp_array);
            $spec_sign = implode('|', $tmp_array);
            $tpl_spec = array();
            $tpl_spec['sign'] = $spec_sign;
            $tpl_spec['url'] = urlShop('goods', 'index', array('gid' => $value['gid']));
            $spec_list[] = $tpl_spec;
            $spec_list_mobile[$spec_sign] = $value['gid'];
            $spec_image[$value['color_id']] = thumb($value, 60);
        }
        $spec_list = json_encode($spec_list);

//        // 商品多图
//        $image_more = $this->getGoodsImageList(array('goods_commonid' => $goods_info['goods_commonid'], 'color_id' => $goods_info['color_id']), 'goods_image');
//        $goods_image = array();
//        $goods_image_mobile = array();
//        if (!empty($image_more)) {
//            foreach ($image_more as $val) {
//                $goods_image[] = "{ title : '', levelA : '".cthumb($val['goods_image'], 60, $goods_info['vid'])."', levelB : '".cthumb($val['goods_image'], 350, $goods_info['vid'])."', levelC : '".cthumb($val['goods_image'], 350, $goods_info['vid'])."', levelD : '".cthumb($val['goods_image'], 1280, $goods_info['vid'])."'}";
//                $goods_image_mobile[] = cthumb($val['goods_image'], 360, $goods_info['vid']);
//            }
//        } else {
//            $goods_image[] = "{ title : '', levelA : '".thumb($goods_info, 60)."', levelB : '".thumb($goods_info, 350)."', levelC : '".thumb($goods_info, 350)."', levelD : '".thumb($goods_info, 1280)."'}";
//            $goods_image_mobile[] = thumb($goods_info, 360);
//        }
        // 新版放大镜开始
        $image_more = $this->getGoodsImageList(array('goods_commonid' => $goods_info['goods_commonid'], 'color_id' => $goods_info['color_id']), 'goods_image');
        $goods_image = array();
        $goods_image_mobile = array();
        if (!empty($image_more)) {
            foreach ($image_more as $val) {
                $goods_image[] = array(cthumb($val['goods_image'], 60, $goods_info['vid']), cthumb($val['goods_image'], 350, $goods_info['vid']), cthumb($val['goods_image'], 1280, $goods_info['vid']));
//                $goods_image_mobile[] = cthumb($val['goods_image'], 350, $goods_info['vid']);
                $goods_image_mobile[] = OriginImage($val['goods_image'],  $goods_info['vid']);
            }
        } else {
            $goods_image[] = "{ title : '', levelA : '" . thumb($goods_info, 60) . "', levelB : '" . thumb($goods_info, 350) . "', levelC : '" . thumb($goods_info, 350) . "', levelD : '" . thumb($goods_info, 1280) . "'}";
//            $goods_image_mobile[] = thumb($goods_info, 350);
            $goods_image_mobile[] = OriginImage($goods_info['goods_image'],  $goods_info['vid']);
        }
        // 新版结束
        //是否具有手机专享价_刘志远
        $model_sole = Model('p_mbuy');
        $solegoods_info = $model_sole->getSoleGoodsInfo(array('vid' => $goods_info['vid'], 'gid' => $goods_info['gid']));
        if(!empty($solegoods_info)){
            $goods_info['promotion_type'] = 'phone_price';
//            $goods_info['remark'] = $tuan_info['remark'];
            $goods_info['promotion_price'] = $solegoods_info['mbuy_price'];
//            $goods_info['down_price'] = sldPriceFormat($goods_info['goods_price'] - $tuan_info['tuan_price']);
//            $goods_info['upper_limit'] = $tuan_info['upper_limit'];
        }
        //团购
        if (empty($solegoods_info)&&C('tuan_allow')) {
            $tuan_info = Model('tuan')->getTuanInfoByGoodsCommonID($goods_info['goods_commonid']);
            if (!empty($tuan_info)) {
                $goods_info['promotion_type'] = 'tuan';
                $goods_info['remark'] = $tuan_info['remark'];
                $goods_info['promotion_price'] = $tuan_info['tuan_price'];
                $goods_info['down_price'] = sldPriceFormat($goods_info['goods_price'] - $tuan_info['tuan_price']);
                $goods_info['upper_limit'] = $tuan_info['upper_limit'];
            }
        }

        //限时折扣
        if (Config('promotion_allow') && empty($tuan_info)&&empty($solegoods_info)) {
            $xianshi_info = Model('p_xianshi_goods')->getXianshiGoodsInfoByGoodsID($gid);
            if (!empty($xianshi_info)) {
                $goods_info['promotion_type'] = 'xianshi';
                $goods_info['remark'] = $xianshi_info['xianshi_title'];
                $goods_info['promotion_price'] = $xianshi_info['xianshi_price'];
                $goods_info['down_price'] = sldPriceFormat($goods_info['goods_price'] - $xianshi_info['xianshi_price']);
                $goods_info['lower_limit'] = $xianshi_info['lower_limit'];
            }
        }

        //满即送
        $mansong_info = Model('p_mansong')->getMansongInfoByStoreID($goods_info['vid']);
        $mobile_info = $solegoods_info;
        //判断是否参与今日抢购活动_张金凤if(!($goods_info['promotion_price']*1)){
        if(!$goods_info['promotion_price']){
            $tobuy_detail_model = Model('today_buy_detail');
            $tobuy_detail_info = $tobuy_detail_model->getList(array('item_id' => $gid, 'today_buy_detail_state' => "1"));
            if (!empty($tobuy_detail_info)) {
                $today_buy_time_id = $tobuy_detail_info[0]['today_buy_time_id'];
                $tobuy_time_model = Model('today_buy');
                $tobuy_time_info = $tobuy_time_model->getOneById_time($today_buy_time_id);
                if ($tobuy_time_info && $tobuy_time_info['today_buy_time_state'] == 1) {
                    $today_buy_id = $tobuy_time_info['today_buy_id'];
                    $today_buy_time = $tobuy_time_info['today_buy_time'];
                    //根据today_buy_id获取today_buy_date
                    $today_buy_info = $tobuy_time_model->getList(array('today_buy_id' => $today_buy_id, 'today_buy_state' => "1"));
                    if (!empty($today_buy_info)) {
                        $today_date = date("Y-m-d");
                        if ($today_date == $today_buy_info[0]['today_buy_date']) {
                            $time = $today_buy_info[0]['today_buy_date']." ".$today_buy_time;
                            $tobuy_time = strtotime($time);
                            //当前时间大于时间点的情况下才可以算作参与活动了
                            if (time()-$tobuy_time>0||time()-$tobuy_time==0) {
                                $goods_info['promotion_type'] = 'today_buy';
                                $goods_info['promotion_price'] = $tobuy_detail_info[0]['today_buy_price'];
                            }

                        }
                    }
                }
            }
        }


        // 商品受关注次数加1
        $_times = cookie('tm_visit_product');
        if (empty($_times)) {
            $this->editGoods(array('goods_click' => array('exp', 'goods_click + 1')), array('gid' => $gid));
            setBbcCookie('tm_visit_product', 1);
            $goods_info['goods_click'] = intval($goods_info['goods_click']) + 1;
        }

        $result = array();
        $result['goods_info'] = $goods_info;
        $result['spec_list'] = $spec_list;
        $result['spec_list_mobile'] = $spec_list_mobile;
        $result['spec_image'] = $spec_image;
        $result['goods_image'] = $goods_image;
        $result['goods_image_mobile'] = $goods_image_mobile;
        $result['tuan_info'] = $tuan_info;
        $result['xianshi_info'] = $xianshi_info;
        $result['mansong_info'] = $mansong_info;
        $result['mobile_info'] = $mobile_info;
        return $result;
    }

    //根据商品id获取商品的所属促销类型和价格(刘志远)
    public function getGoodsProType($gid=null){
        if ($gid <= 0) {
            return null;
        }
        $result1 = $this->getGoodsAsGoodsShowInfo(array('gid' => $gid));
        if (empty($result1)) {
            return null;
        }
        $result2 = $this->getGoodeCommonAsGoodsShowInfo(array('goods_commonid' => $result1['goods_commonid']));
        $goods_info = array_merge($result2, $result1);
        //是否具有手机专享价_刘志远
        $goods_info['promotion_type']='';
        $goods_info['promotion_price']='';
        $model_sole = Model('p_mbuy');
        $solegoods_info = $model_sole->getSoleGoodsInfo(array('vid' => $goods_info['vid'], 'gid' => $goods_info['gid']));
        if(!empty($solegoods_info)){
            $goods_info['promotion_type'] = 'phone_price';
//            $goods_info['remark'] = $tuan_info['remark'];
            $goods_info['promotion_price'] = $solegoods_info['mbuy_price'];
//            $goods_info['down_price'] = sldPriceFormat($goods_info['goods_price'] - $tuan_info['tuan_price']);
//            $goods_info['upper_limit'] = $tuan_info['upper_limit'];
        }
        //团购
        if (empty($solegoods_info)&&C('tuan_allow')) {
            $tuan_info = Model('tuan')->getTuanInfoByGoodsCommonID($goods_info['goods_commonid']);
            if (!empty($tuan_info)) {
                $goods_info['promotion_type'] = 'tuan';
                $goods_info['remark'] = $tuan_info['remark'];
                $goods_info['promotion_price'] = $tuan_info['tuan_price'];
                $goods_info['down_price'] = sldPriceFormat($goods_info['goods_price'] - $tuan_info['tuan_price']);
                $goods_info['upper_limit'] = $tuan_info['upper_limit'];
            }
        }

        //限时折扣
        if (C('promotion_allow') && empty($tuan_info)&&empty($solegoods_info)) {
            $xianshi_info = Model('p_xianshi_goods')->getXianshiGoodsInfoByGoodsID($gid);
            if (!empty($xianshi_info)) {
                $goods_info['promotion_type'] = 'xianshi';
                $goods_info['remark'] = $xianshi_info['xianshi_title'];
                $goods_info['promotion_price'] = $xianshi_info['xianshi_price'];
                $goods_info['down_price'] = sldPriceFormat($goods_info['goods_price'] - $xianshi_info['xianshi_price']);
                $goods_info['lower_limit'] = $xianshi_info['lower_limit'];
            }
        }

        //满即送
        $mansong_info = Model('p_mansong')->getMansongInfoByStoreID($goods_info['vid']);
        $mobile_info = $solegoods_info;
        //判断是否参与今日抢购活动_张金凤if(!($goods_info['promotion_price']*1)){
        if(!$goods_info['promotion_price']){
            $tobuy_detail_model = Model('today_buy_detail');
            $tobuy_detail_info = $tobuy_detail_model->getList(array('item_id' => $gid, 'today_buy_detail_state' => "1"));
            if (!empty($tobuy_detail_info)) {
                $today_buy_time_id = $tobuy_detail_info[0]['today_buy_time_id'];
                $tobuy_time_model = Model('today_buy');
                $tobuy_time_info = $tobuy_time_model->getOneById_time($today_buy_time_id);
                if ($tobuy_time_info && $tobuy_time_info['today_buy_time_state'] == 1) {
                    $today_buy_id = $tobuy_time_info['today_buy_id'];
                    $today_buy_time = $tobuy_time_info['today_buy_time'];
                    //根据today_buy_id获取today_buy_date
                    $today_buy_info = $tobuy_time_model->getList(array('today_buy_id' => $today_buy_id, 'today_buy_state' => "1"));
                    if (!empty($today_buy_info)) {
                        $today_date = date("Y-m-d");
                        if ($today_date == $today_buy_info[0]['today_buy_date']) {
                            $time = $today_buy_info[0]['today_buy_date']." ".$today_buy_time;
                            $tobuy_time = strtotime($time);
                            //当前时间大于时间点的情况下才可以算作参与活动了
                            if (time()-$tobuy_time>0||time()-$tobuy_time==0) {
                                $goods_info['promotion_type'] = 'today_buy';
                                $goods_info['promotion_price'] = $tobuy_detail_info[0]['today_buy_price'];
                            }

                        }
                    }
                }
            }
        }
        $promotion=array();
        $promotion['promotion_type']=$goods_info['promotion_type'];
        $promotion['promotion_price']=$goods_info['promotion_price'];
        return $promotion;


    }
    public function saveGoods($param, $vid, $store_name, $store_state, $seller_id, $seller_name, $bind_all_gc) {
        // 验证参数
        $error = $this->_validParam($param);

        if ($error != '') {
            return callback(false, $error);
        }
        $gc_id = intval($param['cid']);
        // 验证商品分类是否存在且商品分类是否为最后一级
        $data = Model('goods_class')->getGoodsClassForCacheModel();
        if (!isset($data[$gc_id]) || isset($data[$gc_id]['child']) || isset($data[$gc_id]['childchild'])) {
            return callback(false, '您选择的分类不存在，或没有选择到最后一级，请重新选择分类。');
        }

        // 三方店铺验证是否绑定了该分类
        //根据vid获取店铺信息
        $store_info = Model('vendor')->getStoreInfoByID($vid);
        if(!((bool)$store_info['is_own_shop']&&(bool) $store_info['bind_all_gc'])){
            $where = array();
            $where['class_1|class_2|class_3'] = $gc_id;
            $where['vid'] = $vid;
            $rs = Model('vendor_bind_category')->getStoreBindClassInfo($where);
            if (empty($rs)) {
                return callback(false, '您的店铺没有绑定该分类，请重新选择分类。');
            }
        }

        // 根据参数初始化通用商品数据
        $common_array = $this->_initCommonGoodsByParam($param, $vid, $store_name, $store_state);
        // 生成通用商品返回通用商品编号
        $common_id = $this->addGoodsCommon($common_array);
        if (!$common_id) {
            return callback(false, '商品添加失败');
        }

        // 商品多图保存
        if(!empty($param['image_all'])) {
            $this->_imageAll($common_id, $vid, $param['image_all'], $common_array['goods_image']);
        }
        // 生成商品返回商品ID(SKU)数组
        $goodsid_array = $this->_addGoods($param, $common_id, $common_array);
        // 生成商品二维码
        if (!empty($goodsid_array)) {
            QueueClient::push('createGoodsQRCode', array('vid' => $vid, 'goodsid_array' => $goodsid_array));
        }
        // 商品加入上架队列
        if (isset($param['starttime'])) {
            $selltime = strtotime($param['starttime']) + intval($param['starttime_H'])*3600 + intval($param['starttime_i'])*60;
            if ($selltime > TIMESTAMP) {
                Model('cron')->addCron(array('exetime' => $selltime, 'exeid' => $common_id, 'type' => 1), true);
            }
        }
        //商品加入消费者保障服务更新队列
        Model('cron')->addCron(array('exetime' => TIMESTAMP, 'exeid' => $common_id, 'type' => 9), true);

        // 记录日志
        $this->_recordLog('添加商品，SPU:'.$common_id, $seller_id, $seller_name, $vid);

        return callback(true, '', $common_id);
    }
    /**
     * 编辑商品图
     */
    public function editSaveImage($img, $common_id, $vid, $seller_id, $seller_name) {

        if ($common_id <= 0 || empty($_POST['img'])) {
            return callback(false, '参数错误');
        }
        // 删除原有图片信息
        $this->delGoodsImages_new(array('goods_commonid' => $common_id, 'vid' => $vid));
        // 保存
        $insert_array = array();
        foreach ($_POST['img'] as $key => $value) {
            $k = 0;
            foreach ($value as $v) {
                if ($v['name'] == '') {
                    continue;
                }
                // 商品默认主图
                $update_array = array();        // 更新商品主图
                $update_where = array();
                $update_array['goods_image']    = $v['name'];
                $update_where['goods_commonid'] = $common_id;
                $update_where['vid']       = $vid;
                $update_where['color_id']       = $key;
                if ($k == 0 || $v['default'] == 1) {
                    $k++;
                    $update_array['goods_image']    = $v['name'];
                    $update_where['goods_commonid'] = $common_id;
                    $update_where['vid']       = $vid;
                    $update_where['color_id']       = $key;
                    // 更新商品主图
                    $this->editGoods($update_array, $update_where);
                }
                $tmp_insert = array();
                $tmp_insert['goods_commonid']   = $common_id;
                $tmp_insert['vid']         = $vid;
                $tmp_insert['color_id']         = $key;
                $tmp_insert['goods_image']      = $v['name'];
                $tmp_insert['goods_image_sort'] = ($v['default'] == 1) ? 0 : $v['sort'];
                $tmp_insert['is_default']       = $v['default'];
                $insert_array[] = $tmp_insert;
            }
        }
        $rs = $this->addGoodsImagesAll($insert_array);
        if ($rs) {
            $this->_recordLog('商品图片编辑，SPU:'.$common_id, $seller_id, $seller_name, $vid);
            return callback(true);
        } else {
            return callback(false, '商品图片编辑失败');
        }
    }
    /**
     * 验证参数
     */
    private function _validParam($param) {
        $obj_validate = new Validate();
        $obj_validate->validateparam = array(
            array (
                "input" => $param["g_name"],
                "require" => "true",
                "message" => L('商品名称不能为空')
            ),
            array (
                "input" => $param["g_price"],
                "require" => "true",
                "validator" => "Double",
                "message" => L('store_goods_index_goods_price_null')
            )
        );

        return $obj_validate->validate();
    }
    /**
     * 根据参数初始化通用商品数据
     */
    private function _initCommonGoodsByParam($param, $vid, $store_name, $store_state) {

        // 分类信息
        $goods_class = Model('goods_class')->getGoodsClassLineForTag(intval($param['cid']));

        $common_array = array();
        $common_array['goods_name']         = $param['g_name'];
        $common_array['goods_jingle']       = $param['g_jingle'];
        $common_array['gc_id']              = intval($param['cid']);
        $common_array['gc_id_1']            = intval($goods_class['gc_id_1']);
        $common_array['gc_id_2']            = intval($goods_class['gc_id_2']);
        $common_array['gc_id_3']            = intval($goods_class['gc_id_3']);
        $common_array['gc_name']            = $param['cname'];
        $common_array['brand_id']           = $param['b_id'];
        $common_array['brand_name']         = $param['b_name'];
        $common_array['type_id']            = intval($param['type_id']);
        $common_array['goods_image']        = $param['image_path'];
        $common_array['goods_price']        = floatval($param['g_price']);
        $common_array['goods_marketprice']  = floatval($param['g_marketprice']);
        $common_array['goods_costprice']    = floatval($param['g_costprice']);
        $common_array['goods_discount']     = floatval($param['g_discount']);
        $common_array['goods_serial']       = $param['g_serial'];
        $common_array['goods_storage_alarm']= intval($param['g_alarm']);
        $common_array['goods_barcode']      = $param['g_barcode'];
        $common_array['goods_attr']         = serialize($param['attr']);
        $common_array['goods_body']         = $param['g_body'];
        $common_array['goods_commend']      = intval($param['g_commend']);
        $common_array['goods_state']        = ($store_state != 1) ? 0 : intval($param['g_state']);            // 店铺关闭时，商品下架
        $common_array['goods_addtime']      = TIMESTAMP;
        $common_array['goods_selltime']     = strtotime($param['starttime']) + intval($param['starttime_H'])*3600 + intval($param['starttime_i'])*60;
        $common_array['goods_verify']       = (Config('goods_verify') == 1) ? 10 : 1;
        $common_array['vid']           = $vid;
        $common_array['store_name']         = $store_name;
        $common_array['spec_name']          = is_array($param['spec']) ? serialize($param['sp_name']) : serialize(null);
        $common_array['spec_value']         = is_array($param['spec']) ? serialize($param['sp_val']) : serialize(null);
        $common_array['goods_vat']          = intval($param['g_vat']);
        $common_array['areaid_1']           = intval($param['province_id']);
        $common_array['areaid_2']           = intval($param['city_id']);
        $common_array['transport_id']       = ($param['freight'] == '0') ? '0' : intval($param['transport_id']); // 运费模板
        $common_array['transport_title']    = $param['transport_title'];
        $common_array['goods_freight']      = floatval($param['g_freight']);
        $common_array['goods_stcids']       = $this->_getStoreClassArray($param['sgcate_id'], $vid);
        $common_array['plateid_top']        = intval($param['plate_top']) > 0 ? intval($param['plate_top']) : 0;
        $common_array['plateid_bottom']     = intval($param['plate_bottom']) > 0 ? intval($param['plate_bottom']) : 0;
        if(Config('distribution')){
            $common_array['fenxiao_yongjin']     = floatval($param['fenxiao_yongjin']);
        }
        return $common_array;
    }
    /**
     * 新增商品公共数据
     *
     * @param array $insert 数据
     * @param string $table 表名
     */
    public function addGoodsCommon($insert) {
        return $this->table('goods_common')->insert($insert);
    }
    private function _imageAll($common_id, $vid, $image_all, $image_main) {

        $image_array = explode(',', $image_all);

        $insert_array = array();
        foreach ($image_array as $value) {
            if(!empty($value)) {
                $tmp_insert = array();
                $tmp_insert['goods_commonid']   = $common_id;
                $tmp_insert['vid']         = $vid;
                $tmp_insert['color_id']         = 0;
                $tmp_insert['goods_image']      = $value;
                $tmp_insert['goods_image_sort'] = 0;
                if($value == $image_main) {
                    $tmp_insert['is_default'] = 1 ;
                } else {
                    $tmp_insert['is_default'] = 0;
                }
                $insert_array[] = $tmp_insert;
            }
        }
        $this->addGoodsImagesAll($insert_array);
    }
    /**
     * 新增多条商品数据
     *
     * @param unknown $insert
     */
    public function addGoodsImagesAll($insert) {
        $result = $this->table('goods_images')->insertAll($insert);
        if ($result) {
            foreach ($insert as $val) {
                $this->_dGoodsImageCache($val['goods_commonid'] . '|' . $val['color_id']);
            }
        }
        return $result;
    }

    /*
     *修改商品图片
     *
     */
    public function eidtGoodsImages($condition,$data) {
        $result = $this->table('goods_images')->where($condition)->update($data);
        return $result;
    }

    /**
     * 生成商品返回商品ID(SKU)数组
     */
    private function _addGoods($param, $common_id, $common_array) {
        $goodsid_array = array();
        $model_type = Model('type');

        // 商品规格
        if (is_array($param['spec'])) {
            foreach ($param['spec'] as $value) {
                $goods = $this->_initGoodsByCommonGoods($common_id, $common_array);
                $goods['goods_name']        = $common_array['goods_name'] . ' ' . implode(' ', $value['sp_value']);
                $goods['goods_price']       = $value['price'];
                $goods['goods_promotion_price']=$value['price'];
                $goods['goods_marketprice'] = $value['marketprice'] == 0 ? $common_array['goods_marketprice'] : $value['marketprice'];
                $goods['goods_serial']      = $value['sku'];
                $goods['goods_storage_alarm']= intval($value['alarm']);
                $goods['goods_spec']        = serialize($value['sp_value']);
                $goods['goods_storage']     = $value['stock'];
                $goods['goods_barcode']     = $value['barcode'];
                $goods['color_id']          = intval($value['color']);
                $gid = $this->addGoods($goods);
                $model_type->addGoodsType($gid, $common_id, array('cid' => $param['cid'], 'type_id' => $param['type_id'], 'attr' => $param['attr']));

                $goodsid_array[] = $gid;
            }
        } else {
            $goods = $this->_initGoodsByCommonGoods($common_id, $common_array);
            $goods['goods_name']        = $common_array['goods_name'];
            $goods['goods_image']        = $common_array['goods_image'];
            $goods['goods_price']       = $common_array['goods_price'];
            $goods['goods_promotion_price']=$common_array['goods_price'];
            $goods['goods_marketprice'] = $common_array['goods_marketprice'];
            $goods['goods_serial']      = $common_array['goods_serial'];
            $goods['goods_storage_alarm']= $common_array['goods_storage_alarm'];
            $goods['goods_spec']        = serialize(null);
            $goods['goods_storage']     = intval($param['g_storage']);
            $goods['goods_barcode']     = $common_array['goods_barcode'];
            $goods['color_id']          = 0;
            $gid = $this->addGoods($goods);
            $model_type->addGoodsType($gid, $common_id, array('cid' => $param['cid'], 'type_id' => $param['type_id'], 'attr' => $param['attr']));

            $goodsid_array[] = $gid;
        }

        return $goodsid_array;
    }
    /**
     * 记录日志
     *
     * @param $content 日志内容
     * @param $state 1成功 0失败
     */
    private function _recordLog($content = '', $seller_id, $seller_name, $vid, $state = 1) {
        $log = array();
        $log['log_content'] = $content;
        $log['log_time'] = TIMESTAMP;
        $log['log_seller_id'] = $seller_id;
        $log['log_seller_name'] = $seller_name;
        $log['log_store_id'] = $vid;
        $log['log_seller_ip'] = getIp();
        $log['log_url'] = 'goodsLogic&saveGoods';
        $log['log_state'] = $state;
        $model_vendor_log = Model('vendor_log');
        $model_vendor_log->addSellerLog($log);
    }
    /**
     * 根据通用商品数据初始化商品数据
     */
    private function _initGoodsByCommonGoods($common_id, $common_array) {
        $goods = array();
        $goods['goods_commonid']    = $common_id;
        $goods['goods_jingle']      = $common_array['goods_jingle'];
        $goods['vid']          = $common_array['vid'];
        $goods['store_name']        = $common_array['store_name'];
        $goods['gc_id']             = $common_array['gc_id'];
        $goods['gc_id_1']           = $common_array['gc_id_1'];
        $goods['gc_id_2']           = $common_array['gc_id_2'];
        $goods['gc_id_3']           = $common_array['gc_id_3'];
        $goods['brand_id']          = $common_array['brand_id'];
        $goods['goods_spec']         = $common_array['spec_name'];
        $goods['goods_image']       = $common_array['goods_image'];
        $goods['goods_state']       = $common_array['goods_state'];
        $goods['goods_verify']      = $common_array['goods_verify'];
        $goods['goods_addtime']     = TIMESTAMP;
        $goods['goods_edittime']    = TIMESTAMP;
        $goods['areaid_1']          = $common_array['areaid_1'];
        $goods['areaid_2']          = $common_array['areaid_2'];
        $goods['transport_id']      = $common_array['transport_id'];
        $goods['goods_freight']     = $common_array['goods_freight'];
        $goods['goods_vat']         = $common_array['goods_vat'];
        $goods['goods_commend']     = $common_array['goods_commend'];
        $goods['goods_stcids']      = $common_array['goods_stcids'];
        if(Config('distribution')){
            $goods['fenxiao_yongjin']     = $common_array['fenxiao_yongjin'];
        }
        return $goods;
    }
    /**
     * 删除商品图片表信息
     *
     * @param array $condition
     * @return boolean
     */
    public function delGoodsImages_new($condition) {
        $image_list = $this->getGoodsImageList($condition, 'goods_commonid,color_id');
        if (empty($image_list)) {
            return true;
        }
        $result = $this->table('goods_images')->where($condition)->delete();
        if ($result) {
            foreach ($image_list as $val) {
                $this->_dGoodsImageCache($val['goods_commonid'] . '|' . $val['color_id']);
            }
        }
        return $result;
    }
    /**
     * 序列化保存手机端商品描述数据
     */
    private function _getMobileBody($mobile_body) {
        if ($mobile_body != '') {
            $mobile_body = str_replace('&quot;', '"', $mobile_body);
            $mobile_body = json_decode($mobile_body, true);
            if (!empty($mobile_body)) {
                return serialize($mobile_body);
            }
        }
        return '';
    }
    /**
     * 查询店铺商品分类
     */
    private function _getStoreClassArray($sgcate_id, $vid) {
        $goods_stcids_arr = array();
        if (!empty($sgcate_id)){
            $sgcate_id_arr = array();
            foreach ($sgcate_id as $k=>$v){
                $sgcate_id_arr[] = intval($v);
            }
            $sgcate_id_arr = array_unique($sgcate_id_arr);
            $store_goods_class = Model('vendor_innercategory')->getStoreGoodsClassList(array('vid' => $vid, 'stc_id' => array('in', $sgcate_id_arr), 'stc_state' => '1'));
            if (!empty($store_goods_class)){
                foreach ($store_goods_class as $k=>$v){
                    if ($v['stc_id'] > 0){
                        $goods_stcids_arr[] = $v['stc_id'];
                    }
                    if ($v['stc_parent_id'] > 0){
                        $goods_stcids_arr[] = $v['stc_parent_id'];
                    }
                }
                $goods_stcids_arr = array_unique($goods_stcids_arr);
                sort($goods_stcids_arr);
            }
        }
        if (empty($goods_stcids_arr)){
            return '';
        } else {
            return ','.implode(',',$goods_stcids_arr).',';// 首尾需要加,
        }
    }
    public function updateGoods($param, $vid, $store_name, $store_state, $seller_id, $seller_name, $bind_all_gc) {
        $common_id = intval($param['commonid']);
        if ($common_id <= 0) {
            return callback(false, '商品编辑失败');
        }
        // 验证参数
        $error = $this->_validParam($param);
        if ($error != '') {
            return callback(false, $error);
        }

        $gc_id = intval($param['cid']);
        // 验证商品分类是否存在且商品分类是否为最后一级
        $data = Model('goods_class')->getGoodsClassForCacheModel();
        if (!isset($data[$gc_id]) || isset($data[$gc_id]['child']) || isset($data[$gc_id]['childchild'])) {
            return callback(false, '您选择的分类不存在，或没有选择到最后一级，请重新选择分类。');
        }


        // 三方店铺验证是否绑定了该分类
        //根据vid获取店铺信息
        $store_info = Model('vendor')->getStoreInfoByID($vid);
        if(!((bool)$store_info['is_own_shop']&&(bool) $store_info['bind_all_gc'])){
            $where = array();
            $where['class_1|class_2|class_3'] = $gc_id;
            $where['vid'] = $vid;
            $rs = Model('vendor_bind_category')->getStoreBindClassInfo($where);
            if (empty($rs)) {
                return callback(false, '您的店铺没有绑定该分类，请重新选择分类。');
            }else{
                return callback(false, '您的店铺没有绑定该分类，请重新选择分类。');
            }
        }

        // 根据参数初始化通用商品数据
        $common_array = $this->_initCommonGoodsByParam($param, $vid, $store_name, $store_state);
        // 接口不标记字段
        if (APP_ID == 'bmobile') {
            unset($common_array['brand_id']);
            unset($common_array['brand_name']);
            unset($common_array['mobile_body']);
            unset($common_array['plateid_top']);
            unset($common_array['plateid_bottom']);
            unset($common_array['sup_id']);
        }
        // 更新商品数据
        extract($this->_editGoods($param, $common_id, $common_array, $vid));
        // 清理商品数据
        $this->delGoods(array('gid' => array('not in', $goodsid_array), 'goods_commonid' => $common_id, 'vid' => $vid));
        // 清理商品图片表
        $this->delGoodsImages(array('goods_commonid' => $common_id, 'color_id' => array('not in', $colorid_array)));
        // 更新商品默认主图
        $default_image_list = $this->getGoodsImageList(array('goods_commonid' => $common_id, 'is_default' => 1), 'color_id ,goods_image');
        if (!empty($default_image_list)) {
            foreach ($default_image_list as $val) {
                $this->editGoods(array('goods_image' => $val['goods_image']), array('goods_commonid' => $common_id, 'color_id' => $val['color_id']));
            }
        }
        // 商品加入上架队列
        if (isset($param['starttime'])) {
            $selltime = strtotime($param['starttime']) + intval($param['starttime_H'])*3600 + intval($param['starttime_i'])*60;
            if ($selltime > TIMESTAMP) {
                Model('cron')->addCron(array('exetime' => $selltime, 'exeid' => $common_id, 'type' => 1), true);
            }
        }
        // 更新商品促销价格
        QueueClient::push('updateGoodsPromotionPriceByGoodsCommonId', $common_id);

        $return = $this->editGoodsCommon($common_array, array('goods_commonid' => $common_id, 'vid' => $vid));
        if (!$return) {
            return callback(false, '商品编辑失败');
        }

        // 生成商品二维码
        if (!empty($goodsid_array)) {
            QueueClient::push('createGoodsQRCode', array('vid' => $vid, 'goodsid_array' => $goodsid_array));
        }

        // 记录日志
        $this->_recordLog('编辑商品，SPU:'.$common_id, $seller_id, $seller_name, $vid);

        return callback(true, '', $common_id);
    }
    private function _editGoods($param, $common_id, $common_array, $vid) {
        $goodsid_array = array();
        $colorid_array = array();

        $model_type = Model('type');
        $model_type->delGoodsAttr(array('goods_commonid' => $common_id));
        if (is_array($param['spec'])) {
            foreach ($param['spec'] as $value) {
                $goods = $this->_initGoodsByCommonGoods($common_id, $common_array);
                $goods_info = $this->getGoodsInfo(array('gid' => $value['gid'], 'goods_commonid' => $common_id, 'vid' => $vid), 'gid');
                if (!empty($goods_info)) {
                    $gid = $goods_info['gid'];
                    $goods['goods_name']        = $common_array['goods_name'] . ' ' . implode(' ', $value['sp_value']);
                    $goods['goods_price']       = $value['price'];
                    $goods['goods_marketprice'] = $value['marketprice'] == 0 ? $common_array['goods_marketprice'] : $value['marketprice'];
                    $goods['goods_serial']      = $value['sku'];
                    $goods['goods_storage_alarm']= intval($value['alarm']);
                    $goods['goods_spec']        = serialize($value['sp_value']);
                    $goods['goods_storage']     = $value['stock'];
                    $goods['goods_barcode']     = $value['barcode'];
                    $goods['color_id']          = intval($value['color']);
                    unset($goods['goods_image']);
                    unset($goods['goods_addtime']);
                    $this->editGoodsById($goods, $gid);
                } else {
                    $goods['goods_name']        = $common_array['goods_name'] . ' ' . implode(' ', $value['sp_value']);
                    $goods['goods_price']       = $value['price'];
                    $goods['goods_promotion_price']=$value['price'];
                    $goods['goods_marketprice'] = $value['marketprice'] == 0 ? $common_array['goods_marketprice'] : $value['marketprice'];
                    $goods['goods_serial']      = $value['sku'];
                    $goods['goods_storage_alarm']= intval($value['alarm']);
                    $goods['goods_spec']        = serialize($value['sp_value']);
                    $goods['goods_storage']     = $value['stock'];
                    $goods['goods_barcode']     = $value['barcode'];
                    $goods['color_id']          = intval($value['color']);
                    $rs = $gid = $this->addGoods($goods);
                }
                $goodsid_array[] = intval($gid);
                $colorid_array[] = intval($value['color']);
                $model_type->addGoodsType($gid, $common_id, array('cid' => $param['cid'], 'type_id' => $param['type_id'], 'attr' => $param['attr']));
            }
        } else {
            if (C('dbdriver') == 'mysql') {
                $goods_spec_field_name = 'goods_spec';
            } else {
                $goods_spec_field_name = 'to_char(goods_spec)';
            }
            $goods = $this->_initGoodsByCommonGoods($common_id, $common_array);
            $goods_info = $this->getGoodsInfo(array('goods_commonid' => $common_id, 'vid' => $vid), 'gid');
            if (!empty($goods_info)) {
                $gid = $goods_info['gid'];
                $goods['goods_name']        = $common_array['goods_name'];
                $goods['goods_price']       = $common_array['goods_price'];
                $goods['goods_marketprice'] = $common_array['goods_marketprice'];
                $goods['goods_serial']      = $common_array['goods_serial'];
                $goods['goods_storage_alarm']= $common_array['goods_storage_alarm'];
                $goods['goods_spec']        = serialize(null);
                $goods['goods_storage']     = intval($param['g_storage']);
                $goods['goods_barcode']     = $common_array['goods_barcode'];
                $goods['color_id']          = 0;
                if ($common_array['is_virtual'] == 1) {
                    $goods['have_gift']    = 0;
                    Model('goods_gift')->delGoodsGift(array('gid' => $gid));
                    Model('goods_fcode')->delGoodsFCode(array('gid' => $gid));
                }
                unset($goods['goods_image']);
                unset($goods['goods_addtime']);
                $this->editGoodsById($goods, $gid);
            } else {
                $goods['goods_name']        = $common_array['goods_name'];
                $goods['goods_price']       = $common_array['goods_price'];
                $goods['goods_promotion_price']=$common_array['goods_price'];
                $goods['goods_marketprice'] = $common_array['goods_marketprice'];
                $goods['goods_serial']      = $common_array['goods_serial'];
                $goods['goods_storage_alarm']= $common_array['goods_storage_alarm'];
                $goods['goods_spec']        = serialize(null);
                $goods['goods_storage']     = intval($param['g_storage']);
                $goods['goods_barcode']     = $common_array['goods_barcode'];
                $goods['color_id']          = 0;
                $gid = $this->addGoods($goods);
            }
            $goodsid_array[] = intval($gid);
            $colorid_array[] = 0;
            $model_type->addGoodsType($gid, $common_id, array('cid' => $param['cid'], 'type_id' => $param['type_id'], 'attr' => $param['attr']));
        }
        return array('goodsid_array' => $goodsid_array, 'colorid_array' =>  array_unique($colorid_array));
    }
    /**
     * 更新商品SUK数据
     * @param array $update
     * @param int|array $goodsid_array
     * @return boolean|unknown
     */
    public function editGoodsById($update, $goodsid_array, $updateXS = false) {
        if (empty($goodsid_array)) {
            return true;
        }
        $condition['gid'] = array('in', $goodsid_array);
        $update['goods_edittime'] = TIMESTAMP;
        $result = $this->table('goods')->where($condition)->update($update);
        if ($result) {
            foreach ((array)$goodsid_array as $value) {
                $this->_dGoodsCache($value);
            }
            if (C('fullindexer.open') && $updateXS) {
                QueueClient::push('updateXS', $goodsid_array);
            }
        }
        return $result;
    }
    /**
     * 更新商品促销价 (需要验证团购和限时折扣是否进行)
     *
     * @param array $update 更新数据
     * @param array $condition 条件
     * @return boolean
     */
    public function editGoodsPromotionPrice($condition) {
        $goods_list = $this->getGoodsList($condition, '*');
        $goods_array = array();
        foreach ($goods_list as $val) {
            $goods_array[$val['goods_commonid']][$val['gid']] = $val;
        }
        $tuan_model = Model('tuan');
        $model_xianshigoods = Model('p_xianshi_goods');
        foreach ($goods_array as $key => $val) {
            // 验证预定商品是否进行
            foreach ($val as $k => $v) {
                if ($v['is_book'] == '1') {
                    if ($v['book_down_time'] > time()) {
                        // 更新价格
                        $this->editGoodsById(array('goods_promotion_price' => ($v['book_down_payment'] + $v['book_final_payment']), 'goods_promotion_type' => 0), $k);
                    } else {
                        $this->editGoodsById(array('is_book' => 0, 'book_down_payment' => 0, 'book_final_payment' => 0, 'book_down_time' => 0), $k);
                    }
                }
            }
            // 查询团购是否进行
            $tuan = $tuan_model->getTuanOnlineInfo(array('goods_commonid' => $key));
            if (!empty($tuan)) {
                // 更新价格
                $this->editGoods(array('goods_promotion_price' => $tuan['tuan_price'], 'goods_promotion_type' => 1), array('goods_commonid' => $key, 'is_book' => 0));
                continue;
            }
            foreach ($val as $k => $v) {
                if ($v['is_book'] == '1') {
                    continue;
                }
                // 查询限时折扣是否进行
                $xianshigoods = $model_xianshigoods->getXianshiGoodsInfo(array('gid' => $k, 'start_time' => array('lt', TIMESTAMP), 'end_time' => array('gt', TIMESTAMP)));
                if (!empty($xianshigoods)) {
                    // 更新价格
                    $this->editGoodsById(array('goods_promotion_price' => $xianshigoods['xianshi_price'], 'goods_promotion_type' => 2), $k);
                    continue;
                }

                // 没有促销使用原价
                $this->editGoodsById(array('goods_promotion_price' => array('exp', 'goods_price'), 'goods_promotion_type' => 0), $k);
            }
        }
        return true;
    }
    public function goodsDrop($commonid_array, $vid, $seller_id, $seller_name) {
        $return = $this->delGoodsNoLock(array('goods_commonid' => array('in', $commonid_array), 'vid' => $vid));
        if ($return) {
            // 添加操作日志
            $this->_recordLog('删除商品，SPU：'.implode(',', $commonid_array), $seller_id, $seller_name, $vid);
            return callback(true);
        } else {
            return callback(false, '商品删除失败');
        }

    }
    /**
     * 商品下架
     * @param unknown $commonid_array
     * @param unknown $vid
     * @param unknown $seller_id
     * @param unknown $seller_name
     * @return multitype:unknown
     */
    public function goodsUnShow($commonid_array, $vid, $seller_id, $seller_name) {
        $where = array();
        $where['goods_commonid'] = array('in', $commonid_array);
        $where['vid'] = $vid;
        $return = $this->editProducesOffline($where);
        if ($return) {
            // 更新优惠套餐状态关闭
            $goods_list = $this->getGoodsList($where, 'gid');
            if (!empty($goods_list)) {
                $goodsid_array = array();
                foreach ($goods_list as $val) {
                    $goodsid_array[] = $val['gid'];
                }
                Model('p_bundling')->editBundlingCloseByGoodsIds(array('gid' => array('in', $goodsid_array)));
            }
            //添加操作日志
            $this->_recordLog('商品下架，SPU:'.implode(',', $commonid_array), $seller_id, $seller_name, $vid);
            return callback(true);
        } else {
            return callback(false, '商品下架失败');
        }
    }
    /**
     * 商品上架
     * @param unknown $commonid_array
     * @param unknown $vid
     * @param unknown $seller_id
     * @param unknown $seller_name
     * @return multitype:unknown
     */
    public function goodsShow($commonid_array, $vid, $seller_id, $seller_name) {
        $return = $this->editProducesOnline(array('goods_commonid' => array('in', $commonid_array), 'vid' => $vid));
        if ($return) {
            // 添加操作日志
            $this->_recordLog('商品上架，SPU:'.implode(',', $commonid_array), $seller_id, $seller_name, $vid);
            return callback(true);
        } else {
            return callback(false, '商品上架失败');
        }
    }
    /**
     * 查询出售中的商品列表及其促销信息
     * @param array $goodsid_array
     * @return array
     */
    public function getGoodsOnlineListAndPromotionByIdArray($goodsid_array) {
        if (empty($goodsid_array) || !is_array($goodsid_array)) return array();

        $goods_list = array();
        foreach ($goodsid_array as $gid) {
            $goods_info = $this->getGoodsOnlineInfoAndPromotionById($gid);
            if (!empty($goods_info)) $goods_list[] = $goods_info;
        }

        return $goods_list;
    }

    //在线课 传入gid,member_id获取该商品购买过的gid
    public function unit_gids($cid){
        return  db::table('bbc_goods')->where(['goods_commonid'=>$cid])->field('gid')->select();
    }

    //读取视频
    public function read_video($gid,$member_id=null,$video_id=null){

        //先查产品信息
        $goods_info = $this->table('goods,goods_common')->alias('g,gc')->join('left')->on('g.goods_commonid=gc.goods_commonid')->where(['g.gid'=>$gid])->field('g.gid,gc.goods_commonid,gc.goods_image,gc.videos,g.goods_name,g.evaluation_good_star,(select count(*) ccc from bbc_evaluate_goods where geval_ordergoodsid=g.gid) as ccc,g.store_name,g.vid,g.gc_id_1,g.is_free,g.course_type')->find();




        if($goods_info['is_free']!=1){
            $where = ['og.buyer_id' => $member_id,'og.gid'=>$gid];
            $where['o.order_state'] = 40;

            if ($goods_info['course_type']) {
                //如果是在线课的话 查看同commonid
                $gids            = $this->unit_gids($goods_info['goods_commonid']);
                $where['og.gid'] = ['in', join(',', $gids)];
            } else {
                $where['og.gid'] = $gid;
            }

            $field = "og.rec_id,og.buyer_id,og.validity,m.member_name as teacher_name";
            $order_goods_info = $this->table('order,order_goods,member')->alias('o,og,m')->join('left join')->on('o.order_id=og.order_id,og.teacher=m.member_id')->field($field)->order('og.validity desc')->where($where)->find();
            if ($order_goods_info['course_type'] == 2 && $order_goods_info['validity'] < TIMESTAMP) {
                $order_goods_info['rec_id'] = null;
            }else{
                return $goods_info;
            }
        }else{
            if($goods_info['teacher']) {
                $field              = "member_name as teacher_name";
                $where              = [];
                $where['member_id'] = $goods_info['teacher'];
                $order_goods_info   = $this->table('member')->field($field)->where($where)->find();
            }else{
                $order_goods_info = [];
            }
        }


        $goods_info = array_merge($goods_info,$order_goods_info);



        return $goods_info;
    }


    //根据老师id查询老师的所有课程
    public function get_lesson_list($member_id,$page = ''){
        $model_goods_common = Model('goods_common');
        $where = ['teacher'=>$member_id];
        $fields = 'goods_commonid,goods_name,goods_image,goods_addtime';
        $lesson_list = $model_goods_common->table('goods_common')->field($fields)->where($where)->page($page)->order('goods_addtime desc')->select();
        return $lesson_list;
    }

    public function get_video($gid,$member_id,$v_id=null){
        if(empty($member_id)){
            return ['error'=>1,'msg'=>Language::get('您登录后观看')];
            exit;
        }

        $goods = Model('goods')->where(['gid'=>$gid])->field('goods_commonid')->find();

        if(!($goods)){
            return ['error'=>1,'msg'=>Language::get('商品没有找到')];
            exit;
        }

        //课程信息
        $where = ['og.buyer_id' => $member_id,'g.goods_commonid'=>$goods['goods_commonid']];
        $field = "og.buyer_id,og.validity,gc.goods_commonid,gc.goods_image,gc.videos,g.goods_name,g.evaluation_good_star,m.member_name as teacher_name,gc.duration,og.order_id,count(eva.geval_id) as ccc,g.store_name";
        $model = Model();
        $data = $model->table('order_goods,goods,goods_common,member,evaluate_goods')->alias('og,g,gc,m,eva')->join('left join')->on('og.gid=g.gid,g.goods_commonid=gc.goods_commonid,og.teacher=m.member_id,eva.geval_goodsid=g.gid')->field($field)->where($where)->find();

        if (empty($data)) {
            return ['error'=>1,'msg'=>Language::get('您没有购买此课程')];
            exit;
        }

        //查询用户视频最后观看记录

        $condition = ['buyer_id' => $member_id, 'goods_commonid' => $goods['goods_commonid']];

        if($v_id){
            $condition['video_id'] = $v_id;
        }


        $video_log = $model->table('video_log')->where($condition)->order('alter_time desc')->find();

        if(!empty($video_log)){
            //查询用户课程 全部视频
            $videos = $model->table('goods_video')->where(['id' => ['in', $data['videos']]])->key('id')->select();
            if(!$v_id) {
                $v_id = $video_log['video_id'];
            }
        }else{
            //查询用户课程 全部视频
            $videos = $model->table('goods_video')->where(['id' => ['in', $data['videos']]])->key('id')->select();
            if(!$v_id) {
                $v_id = $video_log['video_id'];
            }
            if(!$v_id){
                foreach ($videos as $v){
                    $v_id = $v['id'];
                    break;
                }
            }
        }

        $this_video = $videos[$v_id];
        $this_video['dur'] = Sec2Time($this_video['dur']);
        $data['duration'] = Sec2Time($data['duration']);

        if(!empty($video_log)){
            $data['video_log'] = $video_log;
        }

        // 看了又看（同分类本店随机商品）
        $model_goods = Model('goods');
        $size = '6';
        $goods_rand_list = $model_goods->getGoodsGcStoreRandList($data['gc_id_1'], $data['vid'], $data['gid'], $size);
        $goods_rand_list = array_slice($goods_rand_list,0,$size);
        // 获取最终价格
        $goods_rand_list = Model('goods_activity')->rebuild_goods_data($goods_rand_list,'pc');

        foreach ($videos as &$v){
            $v['dur'] = Sec2Time($v['dur']);
            if(LANG_TYPE!='zh_cn'){
                $v['txt'] = $v['entxt'];
            }
        }

        $result['data'] = $data;
        $result['videos'] = $videos;
        $result['this_video'] = $this_video;
        $result['video_log'] = $video_log;
        $result['this_video'] = $this_video;
        $result['goods_rand_list'] = $goods_rand_list;

        return ['error'=>0,'data'=>$result];
    }

    /**
     * add by zhengyifan 2019-09-09
     * 获取商品信息
     * @param $condition
     * @param string $field
     * @param string $order
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getGoods($condition, $field = '*', $order = '', $page = 0, $limit = 100) {
        return DB::name('goods')
            ->where($condition)
            ->order($order)
            ->page($page)
            ->limit($limit)
            ->column($field,'gid');
    }


}