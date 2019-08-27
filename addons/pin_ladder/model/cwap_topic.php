<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/11/21
 * Time: 11:34
 */
/**
 * 手机专题模型
 *
 *
 *
 */

class cwap_topicModel extends Model{

    //专题项目不可用状态
    const SPECIAL_ITEM_UNUSABLE = 0;
    //专题项目可用状态
    const SPECIAL_ITEM_USABLE = 1;
    //首页特殊专题编号
    const INDEX_SPECIAL_ID = 0;

    public function __construct() {
        parent::__construct('cwap_topic');
    }

    /**
     * 读取专题列表
     * @param array $condition
     *
     */
    public function getCwapTopicList($condition, $page='', $order='topic_id desc', $field='*') {
        $list = $this->table('cwap_topic')->field($field)->where($condition)->page($page)->order($order)->select();
        return $list;
    }


    /*
         * 更新首页——2017.10.20
         * @param array $update
         * @param array $condition
         * @return bool
         *
         */
    public function editCwapTopic($update, $topic_id) {
        $topic_id = intval($topic_id);
        if($topic_id <= 0) {
            return false;
        }
        $condition = array();
        $condition['topic_id'] = $topic_id;
        $result = $this->table('cwap_topic')->where($condition)->update($update);
        if($result) {
            return $topic_id;
        } else {
            return false;
        }
    }

    /*
     * 删除专题
     * @param int $special_id
     * @return bool
     *
     */
    public function delCwapTopicByID($topic_id) {
        $topic_id = intval($topic_id);
        if($topic_id <= 0) {
            return false;
        }

        $condition = array();
        $condition['topic_id'] = $topic_id;

        return $this->table('cwap_topic')->where($condition)->delete();
    }



    /**
     * 专题可用项目列表（用于前台显示仅显示可用项目）
     * @param int $special_id
     *
     */
    public function getMbSpecialItemUsableListByID($special_id) {
        $prefix = 'mb_special';

        $item_list = rcache($special_id, $prefix);
        //缓存有效
        if(!empty($item_list)) {
            return unserialize($item_list['special']);
        }

        //缓存无效查库并缓存
        $condition = array();
        $condition['special_id'] = $special_id;
        $condition['item_usable'] = self::SPECIAL_ITEM_USABLE;
        $item_list = $this->_getMbSpecialItemList($condition);
        if(!empty($item_list)) {
            $new_item_list = array();
            foreach ($item_list as $value) {
                //处理图片
                $item_data = $this->_formatMbSpecialData($value['item_data'], $value['item_type']);
                $arr=array();
                if($value['item_type']=='home5'){
                    for($i=1;$i<9;$i++){
                        $mm=array();
                        $mm['rectangle_image']=$item_data['rectangle'.$i.'_image'];
                        $mm['rectangle_type']=$item_data['rectangle'.$i.'_type'];
                        $mm['rectangle_data']=$item_data['rectangle'.$i.'_data'];
                        array_push($arr,$mm);
                    }
                    $items=array();
                    $items['title']=$item_data['title'];
                    $items['items']=$arr;
                    $item_data=$items;
                }

                $new_item_list[] = array($value['item_type'] => $item_data);
            }
            $item_list = $new_item_list;
        }
        $cache = array('special' => serialize($item_list));
        wcache($special_id, $cache, $prefix);
        return $item_list;
    }

    /**
     * 首页专题
     */
    public function getMbSpecialIndex() {
        return $this->getMbSpecialItemUsableListByID(self::INDEX_SPECIAL_ID);
    }
    /**
     * wap首页获取数据
     */
    public function getMbSpecialInfo() {
        return $this->getMbSpecialItemUsableListByID(self::INDEX_SPECIAL_ID);
    }

    /**
     * 处理专题数据，拼接图片URL
     */
    private function _formatMbSpecialData($item_data, $item_type) {
        switch ($item_type) {
            case 'home1':
                $item_data['image'] = getMbSpecialImageUrl($item_data['image']);
                break;
            case 'home2':
            case 'home4':
                $item_data['square_image'] = getMbSpecialImageUrl($item_data['square_image']);
                $item_data['rectangle1_image'] = getMbSpecialImageUrl($item_data['rectangle1_image']);
                $item_data['rectangle2_image'] = getMbSpecialImageUrl($item_data['rectangle2_image']);
                break;
            case 'home5':
                $item_data['rectangle1_image'] = getMbSpecialImageUrl($item_data['rectangle1_image']);
                $item_data['rectangle2_image'] = getMbSpecialImageUrl($item_data['rectangle2_image']);
                $item_data['rectangle3_image'] = getMbSpecialImageUrl($item_data['rectangle3_image']);
                $item_data['rectangle4_image'] = getMbSpecialImageUrl($item_data['rectangle4_image']);
                $item_data['rectangle5_image'] = getMbSpecialImageUrl($item_data['rectangle5_image']);
                $item_data['rectangle6_image'] = getMbSpecialImageUrl($item_data['rectangle6_image']);
                $item_data['rectangle7_image'] = getMbSpecialImageUrl($item_data['rectangle7_image']);
                $item_data['rectangle8_image'] = getMbSpecialImageUrl($item_data['rectangle8_image']);
            case 'goods':
                $new_item = array();
                foreach ((array) $item_data['item'] as $value) {
                    $value['goods_image'] = cthumb($value['goods_image']);
                    $new_item[] = $value;
                }
                $item_data['item'] = $new_item;
                break;
            default:
                $new_item = array();
                foreach ((array) $item_data['item'] as $key => $value) {
                    $value['image'] = getMbSpecialImageUrl($value['image']);
                    $new_item[] = $value;
                }
                $item_data['item'] = $new_item;
        }
        return $item_data;
    }

    /**
     * 查询专题项目列表
     */
    private function _getMbSpecialItemList($condition, $order = 'item_sort asc') {
        $item_list = $this->table('cwap_topic')->where($condition)->order($order)->select();
        foreach ($item_list as $key => $value) {
            $item_list[$key]['item_data'] = $this->_initMbSpecialItemData($value['item_data'], $value['item_type']);
            if($value['item_usable'] == self::SPECIAL_ITEM_USABLE) {
                $item_list[$key]['usable_class'] = 'usable';
                $item_list[$key]['usable_text'] = '禁用';
            } else {
                $item_list[$key]['usable_class'] = 'unusable';
                $item_list[$key]['usable_text'] = '启用';
            }
        }
        return $item_list;
    }

    /**
     * 检查专题项目是否存在
     * @param array $condition
     *
     */
    public function isMbSpecialItemExist($condition) {
        $item_list = $this->table('cwap_topic')->where($condition)->select();
        if($item_list) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取项目详细信息
     * @param int $item_id
     *
     */
    public function getMbSpecialItemInfoByID($item_id) {
        $item_id = intval($item_id);
        if($item_id <= 0) {
            return false;
        }

        $condition = array();
        $condition['item_id'] = $item_id;
        $item_info = $this->table('cwap_topic')->where($condition)->find();
        $item_info['item_data'] = $this->_initMbSpecialItemData($item_info['item_data'], $item_info['item_type']);

        return $item_info;
    }

    /**
     * 整理项目内容
     *
     */
    private function _initMbSpecialItemData($item_data, $item_type) {
        if(!empty($item_data)) {
            $item_data = unserialize($item_data);
            if($item_type == 'goods') {
                $item_data = $this->_initMbSpecialItemGoodsData($item_data, $item_type);
            }
        } else {
            $item_data = $this->_initMbSpecialItemNullData($item_type);
        }

        return $item_data;
    }

    /**
     * 处理goods类型内容
     */
    private function _initMbSpecialItemGoodsData($item_data, $item_type) {
        $goods_id_string = '';
        if(!empty($item_data['item'])) {
            foreach ($item_data['item'] as $value) {
                $goods_id_string .= $value . ',';
            }
            $goods_id_string = rtrim($goods_id_string, ',');

            //查询商品信息
            $condition['gid'] = array('in', $goods_id_string);
            $model_goods = Model('goods');
            $goods_list = $model_goods->getGoodsList($condition, 'gid,goods_name,goods_promotion_price,goods_marketprice,goods_price,goods_image,vid');
            //获取商品的促销类型和促销价格
            foreach($goods_list as $kk=>$vv){
                $promotion=$model_goods->getGoodsProType($vv['gid']);
                $goods_list[$kk]['promotion_type']=$promotion['promotion_type'];
                $goods_list[$kk]['promotion_price']=$promotion['promotion_price'];
            }
            $goods_list = array_under_reset($goods_list, 'gid');
            //整理商品数据
            $new_goods_list = array();
            foreach ($item_data['item'] as $value) {
                if(!empty($goods_list[$value])) {
                    $new_goods_list[] = $goods_list[$value];
                }
            }
            $item_data['item'] = $new_goods_list;
        }
        return $item_data;
    }

    /**
     * 初始化空项目内容
     */
    private function _initMbSpecialItemNullData($item_type) {
        $item_data = array();
        switch ($item_type) {
            case 'home1':
                $item_data = array(
                    'title' => '',
                    'image' => '',
                    'type' => '',
                    'data' => '',
                );
                break;
            case 'home2':
            case 'home4':
                $item_data= array(
                    'title' => '',
                    'square_image' => '',
                    'square_type' => '',
                    'square_data' => '',
                    'rectangle1_image' => '',
                    'rectangle1_type' => '',
                    'rectangle1_data' => '',
                    'rectangle2_image' => '',
                    'rectangle2_type' => '',
                    'rectangle2_data' => '',
                );
                break;
            default:
        }
        return $item_data;
    }

    /*
     * 增加专题项目
     * @param array $param
     * @return array $item_info
     *
     */
    public function addMbSpecialItem($param,$pre_item_id) {
        //根据pre_item_id查找到前一条的item_sort  基础上加1就可以了
        $item_info = $this->getMbSpecialItemInfoByID($pre_item_id);
        if(empty($item_info)){
            $param['item_sort'] = 0;
        }else{
            $param['item_sort'] = $item_info['item_sort'];
        }
        $param['item_usable'] = self::SPECIAL_ITEM_UNUSABLE;
        $result = $this->table('cwap_topic')->insert($param);
        //删除缓存
        if($result) {
            //删除缓存
            $this->_delMbSpecialCache($param['special_id']);
            $param['item_id'] = $result;
            return $param;
        } else {
            return false;
        }
    }
    /*
     * 新增专题页面
     * @param array $param
     * @return array $item_info
     *
     */
    public function addTopic($param) {
        $result = $this->table('cwap_topic')->insert($param);
        if($result) {
            return $result;
        } else {
            return false;
        }
    }
    /*
     * 增加专题项目
     * @param array $param
     * @return array $item_info
     *
     */
    public function addMbSpecialItem_new($param) {
        $item_info = $this->getItemInfoBySpecialID($param['special_id']);

        if(empty($item_info)){
            $result = $this->table('cwap_topic')->insert($param);
        }else{
            if(isset($param['item_data'])){
                $update = array();
                $update['item_data'] = $param['item_data'];
            }else{
                $update['item_data'] = '';
            }
            $result = $this->table('cwap_topic')->where(array('special_id'=>$param['special_id']))->update($update);
        }
        //删除缓存
        if($result) {
            //删除缓存
            $this->_delMbSpecialCache($param['special_id']);
            $param['item_id'] = $result;
            return $param;
        } else {
            return false;
        }
    }
    /*
     * 编辑专题页面
     * @param array $param
     * @return array $item_info
     *
     */
    public function editCwapTopic_index($param) {
        $item_info = $this->getTopicInfoByTopicID($param['topic_id']);

        if(empty($item_info)){
            return false;
        }else{
            $update = array();
            $update['topic_desc'] = $param['topic_desc'];
            if(isset($param['topic_data'])){
                $update['topic_data'] = $param['topic_data'];
            }else{
                $update['topic_data'] = '';
            }
            $result = $this->table('cwap_topic')->where(array('topic_id'=>$param['topic_id']))->update($update);
        }
        //删除缓存
        if($result) {
            $param['topic_id'] = $result;
            return $param;
        } else {
            return false;
        }
    }
    /**
     * 获取项目详细信息  根据special_id
     * @param int $special_id
     *
     */
    public function getTopicInfoByTopicID($topic_id) {
        $topic_id = intval($topic_id);
        if($topic_id < 0) {
            return false;
        }
        $condition = array();
        $condition['topic_id'] = $topic_id;
        $item_info = $this->table('cwap_topic')->where($condition)->find();

        return $item_info;
    }

    /**
     * 编辑专题项目
     * @param array $update
     * @param int $item_id
     * @param int $special_id
     * @return bool
     *
     */
    public function editMbSpecialItemByID($update, $item_id, $special_id) {
        if(isset($update['item_data'])) {
            $update['item_data'] = serialize($update['item_data']);
        }
        $condition = array();
        $condition['item_id'] = $item_id;

        //删除缓存
        $this->_delMbSpecialCache($special_id);

        return $this->table('cwap_topic')->where($condition)->update($update);
    }

    /**
     * 编辑专题项目启用状态
     * @param string usable-启用/unsable-不启用
     * @param int $item_id
     * @param int $special_id
     *
     */
    public function editMbSpecialItemUsableByID($usable, $item_id, $special_id) {
        $update = array();
        if($usable == 'usable') {
            $update['item_usable'] = self::SPECIAL_ITEM_USABLE;
        } else {
            $update['item_usable'] = self::SPECIAL_ITEM_UNUSABLE;
        }
        return $this->editMbSpecialItemByID($update, $item_id, $special_id);
    }

    /*
     * 删除
     * @param array $condition
     * @return bool
     *
     */
    public function delMbSpecialItem($condition, $special_id) {
        //删除缓存
        $this->_delMbSpecialCache($special_id);

        return $this->table('cwap_topic')->where($condition)->delete();
    }

    /**
     * 获取专题URL地址
     * @param int $special_id
     *
     */
    public function getMbSpecialHtmlUrl($special_id) {
        return UPLOAD_SITE_URL . DS . ATTACH_MOBILE . DS . 'special_html' . DS . md5('special' . $special_id) . '.html';
    }

    /**
     * 获取专题静态文件路径
     * @param int $special_id
     *
     */
    public function getMbSpecialHtmlPath($special_id) {
        return BASE_UPLOAD_PATH . DS . ATTACH_MOBILE . DS . 'special_html' . DS . md5('special' . $special_id) . '.html';
    }

    /**
     * 获取专题模块类型列表
     * @return array
     *
     */
    public function getMbSpecialModuleList() {
        $module_list = array();
        $module_list['adv_list'] = array('name' => 'adv_list' , 'desc' => '广告条版块');
        $module_list['home1'] = array('name' => 'home1' , 'desc' => '模型版块布局A');
        $module_list['home2'] = array('name' => 'home2' , 'desc' => '模型版块布局B');
        $module_list['home3'] = array('name' => 'home3' , 'desc' => '模型版块布局C');
        $module_list['home4'] = array('name' => 'home4' , 'desc' => '模型版块布局D');
        $module_list['goods'] = array('name' => 'goods' , 'desc' => '商品版块');
        $module_list['home5'] = array('name' => 'home5' , 'desc' => '模型版块布局E');
        return $module_list;
    }

    /**
     * 清理缓存
     */
    private function _delMbSpecialCache($special_id) {
        //清理缓存
        dcache($special_id, 'mb_special');

        //删除静态文件
        $html_path = $this->getMbSpecialHtmlPath($special_id);
        if(is_file($html_path)) {
            delete_file($html_path);
        }
    }
    //获取app广告列表
    // public function getAdList($sepical_id){
    //     $condition['item_type']='adv_list';
    //     $item_list = $this->table('cwap_topic')->where($condition)->order($order)->select();
    //     return $item_list;

    // }
    //

    /*
     * 删除专题(多条)
     * @param array $special_ids
     * @return bool
     *
     */
    public function delCwapTopicByIDs($condition) {
        $result = $this->table('cwap_topic')->where($condition)->delete();
        return $result;
    }


    // 获取 模板信息
    public function getTemplateInfo($condition)
    {
        $result = $this->table('cwap_topic')->where($condition)->field('topic_id,topic_desc,activity_type')->find();
        return $result;
    }

}
