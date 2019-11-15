<?php
namespace app\v1\model;

use think\Model;

class Cache extends Model
{
    public function __construct(){
        parent::__construct();
    }

    public function call($method){
        if(substr($method, 0,3) == 'nav'){
            return $this->_nav();
        }
        $method = '_'.strtolower($method);
        if (method_exists($this,$method)){
            return $this->$method();
        }else{
            return false;
        }
    }

    /**
     * 基本设置
     *
     * @return array
     */
    private function _setting(){
        $list =$this->table('setting')->where(true)->select();

        $array = array();
        foreach ((array)$list as $v) {
            $array[$v['name']] = $v['value'];
        }
        unset($list);
        return $array;
    }

    private function _setting_lang(){
        $list = $this->table('setting_by_lang')->where(['lang'=>LANG_TYPE])->select();
        $list = low_array_column($list,'value','name');

        return $list;
    }

    /**
     * 基本设置
     *
     * @return array
     */
    private function _addons(){
        $list =$this->table('addons')->where(array('sld_status'=>1))->select();
        $array = array();
        foreach ((array)$list as $v) {
            $array[$v['sld_key']] = $v;
        }
        unset($list);
        return $array;
    }

    /**
     * 商品分类
     *
     * @return array
     */
    private function _goods_class(){
        $fields = 'gc_id,gc_name,type_id,gc_parent_id,gc_sort,gc_mobile_picture,is_mobile_display';
        $result = $this->table('goods_class')->field($fields)->order('gc_parent_id asc, gc_sort asc,gc_id asc')->limit(10000)->select();
        if (!is_array($result)) return null;

        $class_level = array();
        $result_copy = $result;
        $result_child = array();
        $result_childchild = array();
        $return_array = array();
        foreach ($result_copy as $k=>$v) {
            $return_array[$v['gc_id']] = $v;
            if (!$v['gc_parent_id']) {
                $class_level[1][$v['gc_id']] = $v;
                unset($result_copy[$k]);
            }
        }
        foreach ($result_copy as $k=>$v) {
            if (array_key_exists($v['gc_parent_id'],$class_level[1])){
                $result_child[$v['gc_parent_id']][] = $v['gc_id'];
                $class_level[2][$v['gc_id']] = $v;
                unset($result_copy[$k]);
            }
        }
        foreach ($result_copy as $k=>$v) {
            if (array_key_exists($v['gc_parent_id'],$class_level[2])){
                $result_child[$v['gc_parent_id']][] = $v['gc_id'];
                $parent_parent_gc_id = $class_level[2][$v['gc_parent_id']]['gc_parent_id'];
                $result_childchild[$parent_parent_gc_id][] = $v['gc_id'];
                $class_level[3][$v['gc_id']] = $v;
                unset($result_copy[$k]);
            }
        }

        foreach ($return_array as $k=>$v){
            if (array_key_exists($v['gc_id'],$class_level[1])) {
                $return_array[$k]['depth'] = 1;
            } elseif (array_key_exists($v['gc_id'],$class_level[2])) {
                $return_array[$k]['depth'] = 2;
            } elseif (array_key_exists($v['gc_id'],$class_level[3])) {
                $return_array[$k]['depth'] = 3;
            }
            if (array_key_exists($k, $result_child)){
                $return_array[$k]['child'] = implode(',', $result_child[$k]);
            }
            if (array_key_exists($k, $result_childchild)){
                $return_array[$k]['childchild'] = implode(',',$result_childchild[$k]);
            }
        }
        return $return_array;
    }
    //用户消息模板
    private function _member_msg_tpl(){
        $list = Model('member_msg_tpl')->getMemberMsgTplList(array());
        $array = array();
        foreach ((array)$list as $v) {
            $array[$v['mmt_code']] = $v;
        }
        unset($list);
        return $array;
    }
    //商户消息模板
    private function _vendor_msg_tpl(){
        $list = Model('vendor_msg_tpl')->getStoreMsgTplList(array());
        $array = array();
        foreach ((array)$list as $v) {
            $array[$v['smt_code']] = $v;
        }
        unset($list);
        return $array;
    }
    //门店消息模板
    private function _dian_msg_tpl(){
        $list = Model('dian_msg_tpl')->getStoreMsgTplList(array());
        $array = array();
        foreach ((array)$list as $v) {
            $array[$v['smt_code']] = $v;
        }
        unset($list);
        return $array;
    }
    /**
     * 咨询类型
     *
     * @return array
     */
    private function _consult_type(){
        $list = Model('consult_type')->getConsultTypeList(array());
        $array = array();
        foreach ((array)$list as $val) {
            $val['ct_introduce'] = html_entity_decode($val['ct_introduce']);
            $array[$val['ct_id']] = $val;
        }
        unset($list);
        return $array;
    }
    /**
     * 专题缓存信息
     *
     * @return array
     */
    private function _channel(){
        $channel_list = array();
        $condition = array();
        $condition['gc_id'] = array('gt',0);
        $condition['channel_show'] = 1;
        $list = $this->table('web_channel')->field('gc_id,channel_id')->where($condition)->limit(999)->order('update_time desc')->select();
        if (!empty($list) && is_array($list)){
            foreach ($list as $k => $v){
                $gc_id = $v['gc_id'];
                $channel_list[$gc_id] = $v['channel_id'];
            }
        }

        return $channel_list;
    }
    /**
     * 商品分类SEO
     *
     * @return array
     */
    private function _goods_class_seo(){

        $list = $this->table('goods_class')->field('gc_id,gc_title,gc_keywords,gc_description')->where(array('gc_keywords'=>array('neq','')))->limit(2000)->select();
        if (!is_array($list)) return null;
        $array = array();
        foreach ($list as $k=>$v) {
            if ($v['gc_title'] != '' || $v['gc_keywords'] != '' || $v['gc_description'] != ''){
                if ($v['gc_name'] != ''){
                    $array[$v['gc_id']]['name'] = $v['gc_name'];
                }
                if ($v['gc_title'] != ''){
                    $array[$v['gc_id']]['title'] = $v['gc_title'];
                }
                if ($v['gc_keywords'] != ''){
                    $array[$v['gc_id']]['key'] = $v['gc_keywords'];
                }
                if ($v['gc_description'] != ''){
                    $array[$v['gc_id']]['desc'] = $v['gc_description'];
                }
            }
        }
        return $array;
    }

    /**
     * 商城主要专题SEO
     *
     * @return array
     */
    private function _seo(){
        $list =$this->table('seo')->where(true)->select();
        if (!is_array($list)) return null;
        $array = array();
        foreach ($list as $key=>$value){
            $array[$value['type']] = $value;
        }
        return $array;
    }

    /**
     * 快递公司
     *
     * @return array
     */
    private function _express(){
        $fields = 'id,e_name,e_code,e_letter,e_order,e_url';
        $list = $this->table('express')->field($fields)->order('e_order,e_letter')->where(array('e_state'=>1))->select();
        if (!is_array($list)) return null;
        $array = array();
        foreach ($list as $k=>$v) {
            $array[$v['id']] = $v;
        }
        return $array;
    }

    /**
     * 自定义导航
     *
     * @return array
     */
    private function _nav(){
        $condition = array(
            'nav_is_close' => 0,
        );
        $list = $this->table('navigation')->where($condition)->order('nav_sort')->select();
        if (!is_array($list)) return null;
        return $list;
    }

    /**
     * 团购地区、分类、价格区间
     *
     * @return array
     */
    private function _tuan(){
        $area = $this->table('tuan_area')->where('area_parent_id=0')->order('area_sort')->select();
        if (!is_array($area)) $area = array();

        $category = $this->table('tuan_category')->where('class_parent_id=0')->order('sort')->select();
        if (!is_array($category)) $category = array();

        $price = $this->table('tuan_price_range')->order('range_start')->select();
        if (!is_array($price)) $price = array();

        return array('area'=>$area,'category'=>$category,'price'=>$price);
    }

    /**
     * 商品TAG
     *
     * @return array
     */
    private function _class_tag(){
        $field = 'gc_tag_id,gc_tag_name,gc_tag_value,gc_id,type_id';
        $list = $this->table('goods_class_tag')->field($field)->where(true)->select();
        if (!is_array($list)) return null;
        return $list;
    }

    /**
     * 店铺分类
     *
     * @return array
     */
    private function _vendor_category(){
        $list = $this->table('vendor_category')->where(true)->order('sc_parent_id,sc_sort')->select();
        $tmp = array();
        if (is_array($list)){
            foreach ($list as $key => $value) {
                $tmp[$value['sc_id']]['sc_name'] = $value['sc_name'];
                $tmp[$value['sc_id']]['sc_parent_id'] = $value['sc_parent_id'];
                foreach ($list as $k => $v) {
                    if ($v['sc_parent_id'] == $value['sc_id']){
                        $tmp[$value['sc_id']]['child'][] = $v['sc_id'];
                    }
                }
                unset($list[$key]);
            }
        }
        return $tmp;
    }

    /**
     * 店铺等级
     *
     * @return array
     */
    private function _store_grade(){
        $list =$this->table('store_grade')->where(array('sld_is_supplier'=>0))->select();
        $array = array();
        foreach ((array)$list as $v) {
            $array[$v['sg_id']] = $v;
        }
        unset($list);
        return $array;
    }

    /**
     * 供应商等级
     *
     * @return array
     */
    private function _supplier_grade(){
        $list =$this->table('store_grade')->where(array('sld_is_supplier'=>1))->select();
        $array = array();
        foreach ((array)$list as $v) {
            $array[$v['sg_id']] = $v;
        }
        unset($list);
        return $array;
    }

    /**
     * Circle Member Level
     *
     * @return array
     */
    private function _circle_level(){
        $list = $this->table('circle_mldefault')->where(true)->select();

        if (!is_array($list)) return null;
        $array = array();
        foreach ($list as $val){
            $array[$val['mld_id']] = $val;
        }
        return $array;
    }

    /**
     * 递归取某个分类下的所有子类ID组成的字符串,以逗号隔开
     *
     * @param string $goodsclass 商品分类
     * @param int $gc_id	待查找子类的ID
     * @param string $child 存放被查出来的子类ID
     */
    private function get_child(&$goodsclass,$gc_id,&$child){
        foreach ($goodsclass as $k=>$v) {
            if ($v['gc_parent_id'] == $gc_id){
                $child[] = $v['gc_id'];
                $this->get_child($goodsclass,$v['gc_id'],$child);
            }
        }
    }

    // 获取 团购商品 集合
    private function _tuan_gid(){
        $type = 'tuan';
        $last_goods_list = array();
        $goods_list = array();
        $tuan_model = new Tuan();

        $goods_list = $tuan_model->getTuanOnlineList_gid();

        if (is_array($goods_list) && !empty($goods_list)) {
            $last_goods_list = $this->rebuildCacheGoodsData($type,$goods_list);
        }

        // 存储缓存文件 的生成时间
        // 获取 缓存文件 的最新更新时间
        $last_new_time = Model('cache_time')->getNewCacheTime($type);
        if ($last_new_time) {
            $last_data['create_time'] = $last_new_time;
        }else{
            $last_data['create_time'] = time();
            // 更新缓存最新时间到 数据库表
            $ctMdodel = new CacheTime();
            $ctMdodel->saveNewCacheTime($type);
        }
        $last_data['data'] = $last_goods_list;

        return $last_data;
    }
    // 获取 限时折扣 集合
    private function _xianshi_gid(){
        $type = 'xianshi';
        $last_goods_list = array();
        $goods_list = array();
        $xianshi_model = new Pxianshigoods();

        $goods_list = $xianshi_model->getXianshiGoodsList_gid(null);

        if (is_array($goods_list) && !empty($goods_list)) {
            $last_goods_list = $this->rebuildCacheGoodsData($type,$goods_list);
        }

        // 存储缓存文件 的生成时间
        // 获取 缓存文件 的最新更新时间
        $last_new_time = Model('cache_time')->getNewCacheTime($type);
        if ($last_new_time) {
            $last_data['create_time'] = $last_new_time;
        }else{
            $last_data['create_time'] = time();
            // 更新缓存最新时间到 数据库表
            Model('cache_time')->saveNewCacheTime($type);
        }
        $last_data['data'] = $last_goods_list;

        return $last_data;
    }
    // 获取 手机专享   集合
    private function _p_mbuy_gid(){
        $type = 'p_mbuy';
        $last_goods_list = array();
        $goods_list = array();
        $p_mbuy_model = new MBuy();
        $goods_list = $p_mbuy_model->getSoleGoodsList_gid(null);

        if (is_array($goods_list) && !empty($goods_list)) {
            $last_goods_list = $this->rebuildCacheGoodsData($type,$goods_list);
        }

        // 存储缓存文件 的生成时间
        // 获取 缓存文件 的最新更新时间
        $ctModel = new CacheTime();
        $last_new_time = $ctModel->getNewCacheTime($type);
        if ($last_new_time) {
            $last_data['create_time'] = $last_new_time;
        }else{
            $last_data['create_time'] = time();
            // 更新缓存最新时间到 数据库表
            $ctModel->saveNewCacheTime($type);
        }
        $last_data['data'] = $last_goods_list;

        return $last_data;
    }
    // 获取 拼团   集合
    private function _pin_tuan_gid(){
        /*$type = 'pin_tuan';
        $last_goods_list = array();
        $goods_list = array();
        $pin_tuan_model = M('pin');

        $goods_list = $pin_tuan_model->_getTuanListByGoodsid_gid();

        if (is_array($goods_list) && !empty($goods_list)) {
            $last_goods_list = $this->rebuildCacheGoodsData($type,$goods_list);
        }

        // 存储缓存文件 的生成时间
        // 获取 缓存文件 的最新更新时间
        $last_new_time = Model('cache_time')->getNewCacheTime($type);
        if ($last_new_time) {
            $last_data['create_time'] = $last_new_time;
        }else{
            $last_data['create_time'] = time();
            // 更新缓存最新时间到 数据库表
            Model('cache_time')->saveNewCacheTime($type);
        }
        $last_data['data'] = $last_goods_list;*/

        return $last_data=array();
    }
    // 获取阶梯拼团   集合
    private function _pin_ladder_tuan_gid(){
        $type = 'pin_ladder_tuan';
        $last_goods_list = array();
        $goods_list = array();
        $pin_tuan_model = new Tuan();

        $goods_list = $pin_tuan_model->_getTuanListByGoodsid_gid();

        if (is_array($goods_list) && !empty($goods_list)) {
            $last_goods_list = $this->rebuildCacheGoodsData($type,$goods_list,time());
        }

        // 存储缓存文件 的生成时间
        // 获取 缓存文件 的最新更新时间
        $ctModel = new CacheTime();
        $last_new_time = $ctModel->getNewCacheTime($type);
        if ($last_new_time) {
            $last_data['create_time'] = $last_new_time;
        }else{
            $last_data['create_time'] = time();
            // 更新缓存最新时间到 数据库表
            $ctModel->saveNewCacheTime($type);
        }
        $last_data['data'] = $last_goods_list;

        return $last_data;
    }
    // 获取预售   集合
    private function _sld_presale(){
        /*$type = 'sld_presale';
        $last_goods_list = array();
        $goods_list = array();
        $pin_tuan_model = M('pre_presale','presale');

        $goods_list = $pin_tuan_model->_getPreSaleListByGoodsid_gid();

        if (is_array($goods_list) && !empty($goods_list)) {
            $last_goods_list = $this->rebuildCacheGoodsData($type,$goods_list);
        }

        // 存储缓存文件 的生成时间
        // 获取 缓存文件 的最新更新时间
        $last_new_time = Model('cache_time')->getNewCacheTime($type);
        if ($last_new_time) {
            $last_data['create_time'] = $last_new_time;
        }else{
            $last_data['create_time'] = time();
            // 更新缓存最新时间到 数据库表
            Model('cache_time')->saveNewCacheTime($type);
        }
        $last_data['data'] = $last_goods_list;*/

        return $last_data=array();
    }
    // 重构 需要存储的缓存信息
    private function rebuildCacheGoodsData($type,$data,$last_new_time){
        switch ($type) {
            case 'tuan':
                $new_activity = array();

                foreach ($data as $value) {

                    $item_value = array(
                        'start_time' => $value['start_time'],
                        'end_time' => $value['end_time'],
                        'gid' => $value['gid'],
                        'promotion_type' => $type,
                        'promotion_price' => $value['tuan_price'] * 1,
                    );

                    $new_activity[$value['gid']][] = $item_value;
                }
                break;
            case 'xianshi':
                $new_activity = array();
                foreach ($data as $value) {

                    $item_value = array(
                        'start_time' => $value['start_time'],
                        'end_time' => $value['end_time'],
                        'gid' => $value['gid'],
                        'promotion_type' => $type,
                        'promotion_price' => $value['xianshi_price'] * 1,
                    );


                    $new_activity[$value['gid']][] = $item_value;
                }
                break;
            case 'p_mbuy':
                $new_activity = array();
                foreach ($data as $value) {

                    $item_value = array(
                        'gid' => $value['gid'],
                        'promotion_type' => $type,
                        'promotion_price' => $value['mbuy_price'] * 1,
                    );

                    $new_activity[$value['gid']][] = $item_value;

                }
                break;
            case 'pin_tuan':
                $new_activity = array();

                foreach ($data as $value) {

                    $item_value = array(
                        'start_time' => $value['sld_start_time'],
                        'end_time' => $value['sld_end_time'],
                        'gid' => $value['sld_gid'],
                        'promotion_type' => $type,
                        'promotion_price' => $value['sld_pin_price'] * 1,
                    );

                    $new_activity[$value['sld_gid']][] = $item_value;
                }
                break;
            case 'pin_ladder_tuan':
                $new_activity = array();
                foreach ($data as $value) {
                    $item_value = array(
                        'start_time' => $value['sld_start_time'],
                        'end_time' => $value['sld_end_time'],
                        'gid' => $value['sld_gid'],
                        'promotion_type' => $type,
                        'promotion_price' => $value['sld_pin_price'] * 1,
                    );
                    $new_activity[$value['sld_gid']][] = $item_value;
                }
                break;
            case 'sld_presale':
                $new_activity = array();
                foreach ($data as $value) {
                    $item_value = array(
                        'start_time' => $value['pre_start_time'],
                        'end_time' => $value['pre_end_time'],
                        'gid' => $value['gid'],
                        'promotion_type' => $type,
                        'promotion_price' => $value['pre_sale_price'] * 1,
                    );
                    $new_activity[$value['gid']][] = $item_value;
                }
                break;

        }

        return $new_activity;
    }


    /**
     * 推手系统 基本设置
     *
     * @return array
     */
    private function _ssys_setting(){
        $list =$this->table('ssys_setting')->where(true)->select();
        $array = array();
        foreach ((array)$list as $v) {
            $array[$v['name']] = $v['value'];
        }
        unset($list);
        return $array;
    }

    /**
     * 收银系统 基本设置
     *
     * @return array
     */
    private function _cashsys_setting(){
        $list =$this->table('cashsys_setting')->where(true)->select();
        $array = array();
        foreach ((array)$list as $v) {
            $array[$v['name']] = $v['value'];
        }
        unset($list);
        return $array;
    }
    /**
     * 联到家系统 基本设置
     *
     * @return array
     */
    private function _ldj_setting(){
        $list = $this->table('ldj_setting')->where(true)->select();
        $array = array();
        foreach ((array)$list as $v) {
            $array[$v['name']] = $v['value'];
        }
        unset($list);
        return $array;
    }
}