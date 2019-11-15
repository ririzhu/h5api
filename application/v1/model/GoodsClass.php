<?php
namespace app\v1\model;

use app\v1\controller\Base;
use think\Model;
use think\db;
$base =new Base();

class GoodsClass extends Model
{

    //表名；主键；要翻译的字段；中文描述（给后台看）；short固定
    public static $types = [
        'goods_class' => ['gc_id', 'gc_name', '产品分类', 'short'],
        'article_class' => ['acid', 'ac_name', '文章分类', 'short'],
        'grade' => ['id', 'grade_name', '等级名称', 'short'],
        'store_grade' => ['sg_id', 'sg_name,sg_description', '店铺等级、等级描述', 'short,short'],
        'help_type' => ['type_id', 'type_name', '帮助分类', 'short'],
        'pin_type' => ['id', 'sld_typename', '拼团分类', 'short'],
        'teacher_trade' => ['trade_id', 'trade_name', '老师行业', 'short'],
        'payment' => ['payment_id', 'payment_name', 'PC端支付名称', 'short'],
        'mb_payment' => ['payment_id', 'payment_name', '移动端支付名称', 'short'],
    ];
    /**
     * 缓存数据
     */
    protected $cachedData;
    /**
     * 缓存数据 原H('goods_class')形式
     */
    protected $gcForCacheModel;
    public function __construct(){
        parent::__construct('goods_class');
    }

    /**
     * 获取缓存数据
     *
     * @return array
     * array(
     *   'data' => array(
     *     // Id => 记录
     *   ),
     *   'parent' => array(
     *     // 子Id => 父Id
     *   ),
     *   'children' => array(
     *     // 父Id => 子Id数组
     *   ),
     *   'children2' => array(
     *     // 1级Id => 3级Id数组
     *   ),
     * )
     */
    public function getCache() {
        if ($this->cachedData) {
            return $this->cachedData;
        }
        $base =new Base();
        $data = $base->rkcache('gc_class');
        if (!$data) {
            $data = array();
            foreach ((array) $this->getGoodsClassList(array()) as $v) {
                $id = $v['gc_id'];
                $pid = $v['gc_parent_id'];
                $data['data'][$id] = $v;
                $data['parent'][$id] = $pid;
                $data['children'][$pid][] = $id;
            }
            foreach ((array) $data['children'][0] as $id) {
                foreach ((array) $data['children'][$id] as $cid) {
                    //print_r($data['children']);
                    foreach ((array) $data['children'][$cid] as $ccid) {
                        $data['children2'][$id][] = $ccid;
                    }
                }
            }
            $base->wkcache('gc_class', $data);
        }
        return $this->cachedData = $data;
    }
    /**
     * 从缓存获取全部分类 分类id作为数组的键
     */
    public function getGoodsClassIndexedListAll() {
        $data = $this->getCache();
        return (array) $data['data'];
    }
    /**
     * 删除缓存数据
     */
    public function dropCache() {
        $base = new Base();
        $this->cachedData = null;
        $this->gcForCacheModel = null;
        $base->dkcache('gc_class');
        $base->dkcache('all_categories');
    }
    /**
     * 类别列表
     *
     * @param  array   $condition  检索条件
     * @return array   返回二位数组
     */
    public function getGoodsClassList($condition, $field = '*') {
        $result = DB::name('goods_class')->field($field)->where($condition)->where("is_points=0")->order('gc_parent_id asc,gc_sort asc,gc_id asc')->limit(10000)->select();
        return $result;
    }

//    /**
//     * 从缓存获取分类 通过分类id数组
//     *
//     * @param array $ids 分类id数组
//     */
//    public function getGoodsClassListByIds($ids) {
//        $data = $this->getCache();
//        $ret = array();
//        foreach ((array) $ids as $i) {
//            if ($data['data'][$i]) {
//                $ret[] = $data['data'][$i];
//            }
//        }
//        return $ret;
//    }
    /**
     * 类别详细
     *
     * @param   array   $condition  条件
     * $param   string  $field  字段
     * @return  array   返回一维数组
     */
    public function getGoodsClassInfo($condition, $field = '*') {
        $result = $this->field($field)->where($condition)->find();
        return $result;
    }
    /**
     * 从缓存获取分类 通过分类id
     *
     * @param int $id 分类id
     */
    public function getGoodsClassInfoById($id) {
        $data = $this->getCache();
        return $data['data'][$id];
    }
    /**
     * 根据一级分类id取得所有三级分类
     */
    public function getChildClassByFirstId($id) {
        $data = $this->getCache();
        $result = array();
        if (!empty($data['children2'][$id])) {
            foreach ($data['children2'][$id] as $val) {
                $child = $data['data'][$val];
                $result[$child['gc_parent_id']]['class'][$child['gc_id']] = $child['gc_name'];
                $result[$child['gc_parent_id']]['name'] = $data['data'][$child['gc_parent_id']]['gc_name'];
            }
        }
        return $result;
    }

    /**
     * 取得店铺绑定的分类
     *
     * @param   number  $vid   店铺id
     * @param   number  $pid        父级分类id
     * @param   number  $deep       深度
     * @return  array   二维数组
     */
    public function getGoodsClass($vid, $pid = 0, $deep = 1) {
        // 读取商品分类
        $gc_list = $this->getGoodsClassList(array('gc_parent_id' => $pid), 'gc_id, gc_name, type_id');
        // 如果店铺ID不为商城店铺的话，读取绑定分类
        if (!checkPlatformStoreBindingAllGoodsClass()) {

            $gc_list = array_under_reset($gc_list, 'gc_id');
            $model_storebindclass = Model('vendor_bind_category');
            //$gcid_array = $model_storebindclass->getStoreBindClassList(array('vid' => $vid), '', "class_{$deep} asc", "distinct class_{$deep}");

            $gcid_array = $model_storebindclass->getStoreBindClassList(array(
                'vid' => $vid,
                'state' => array('in', array(1, 2)),
            ), '', "class_{$deep} asc", "distinct class_{$deep}");
            if (!empty($gcid_array)) {
                $tmp_gc_list = array();
                foreach ($gcid_array as $value) {
//                    if($value["class_{$deep}"] == 0)
//                    return $gc_list_o;
                    if (isset($gc_list[$value["class_{$deep}"]])) {
                        $tmp_gc_list[] = $gc_list[$value["class_{$deep}"]];
                    }
                }
                $gc_list = $tmp_gc_list;
            } else {
                return array();
            }
        }

        return $gc_list;
    }

    /**
     * 取得店铺绑定的分类_wap
     *
     * @param   number  $vid   店铺id
     * @param   number  $pid        父级分类id
     * @param   number  $deep       深度
     * @param   number  $is_all_class 是否自营店铺并绑定全部商品分类(接口使用)
     * @return  array   二维数组
     */
    public function getWapGoodsClass($vid, $pid = 0, $deep = 1, $group_id = null, $gc_limits = 1, $is_all_class = null) {
        // 读取商品分类
        $gc_list = $this->getGoodsClassListByParentId($pid);
        // 如果不是自营店铺或者自营店铺未绑定全部商品类目，读取绑定分类
        if($is_all_class == null) {
            $is_all_class = checkPlatformStoreBindingAllGoodsClass();
        }
        if (!$is_all_class) {
            $gc_list = array_under_reset($gc_list, 'gc_id');
            $model_storebindclass = Model('vendor_bind_category');
            $gcid_array = $model_storebindclass->getStoreBindClassList(array(
                'vid' => $vid,
                'state' => array('in', array(1, 2)),
            ), '', "class_{$deep} asc", "distinct class_{$deep}");

            if (!empty($gcid_array)) {
                $tmp_gc_list = array();
                foreach ($gcid_array as $value) {
                    if (isset($gc_list[$value["class_{$deep}"]])) {
                        $tmp_gc_list[] = $gc_list[$value["class_{$deep}"]];
                    }
                }
                $gc_list = $tmp_gc_list;
            } else {
                return array();
            }
        }
        //排除无权操作的数组
        if (!$gc_limits && $group_id) {
            $gc_list_group = Model('seller_group_bclass')->getSellerGroupBclasList(array('group_id'=>$group_id),'','','distinct class_'.$deep,'class_'.$deep);
            $temp = array();
            foreach ($gc_list as $k => $v) {
                if (array_key_exists($v['gc_id'],$gc_list_group)) {
                    $temp[] = $gc_list[$k];
                }
            }
            $gc_list = $temp;
        }
        return $gc_list;
    }

    /**
     * 删除商品分类
     * @param unknown $condition
     * @return boolean
     */
    public function delGoodsClass($condition) {
        return $this->where($condition)->delete();
    }

    /**
     * 删除商品分类
     *
     * @param array $gcids
     * @return boolean
     */
    public function delGoodsClassByGcIdString($gcids) {
        $gcids = explode(',', $gcids);
        if (empty($gcids)) {
            return false;
        }
        $goods_class = Cache('goods_class') ? Cache('goods_class') : Cache('goods_class', true);
        $gcid_array = array();
        foreach ($gcids as $gc_id) {
            $child = (!empty($goods_class[$gc_id]['child'])) ? explode(',', $goods_class[$gc_id]['child']) : array();
            $childchild = (!empty($goods_class[$gc_id]['childchild'])) ? explode(',', $goods_class[$gc_id]['childchild']) : array();
            $gcid_array = array_merge($gcid_array, array($gc_id), $child, $childchild);
        }
        // 删除商品分类
        $this->delGoodsClass(array('gc_id' => array('in', $gcid_array)));
        // 删除常用商品分类
        Model('goods_class_staple')->delStaple(array('gc_id_1|gc_id_2|gc_id_3' => array('in', $gcid_array)));
        // 删除分类tag表
        Model('goods_class_tag')->delGoodsClassTag(array('gc_id_1|gc_id_2|gc_id_3' => array('in', $gcid_array)));
        // 删除店铺绑定分类
        Model('vendor_bind_category')->delStoreBindClass(array('class_1|class_2|class_3' => array('in', $gcid_array)));
        // 商品下架
        Model('goods')->editProducesLockUp(array('goods_stateremark' => '商品分类被删除，需要重新选择分类'), array('gc_id' => array('in', $gcid_array)));
        return true;
    }

    /**
     * 从缓存获取分类 通过分类id数组
     *
     * @param array $ids 分类id数组
     */
    public function getGoodsClassListByIds($ids) {
        $base = new Base();
        $data = $this->H('goods_class') ? $this->H('goods_class') : $this->H('goods_class', true);
        $ret = array();
        foreach ((array) $ids as $i) {
            if ($data['data'][$i]) {
                $ret[] = $data['data'][$i];
            }
        }
        return $ret;
    }


    /*
     * 从缓存获取分类 通过上级分类id
     *
     * @param int $pid 上级分类id 若传0则返回1级分类
     */
    public function getGoodsClassListByParentId($pid) {

        //$data = Cache('goods_class') ? Cache('goods_class') : Cache('goods_class', true);
        $data = array();
        foreach ((array) $this->getGoodsClassList(array()) as $v) {
            $id = $v['gc_id'];
            $ppid = $v['gc_parent_id'];
            $data['data'][$id] = $v;
            $data['parent'][$id] = $ppid;
            $data['children'][$ppid][] = $id;
        }
        $ret = array();//print_r($data);die;
        /*foreach ((array) $data['children'][$pid] as $i) {
            if ($data['data'][$i]) {//print_r($data['data'][$id]);die;
                if($data['data'][$i]['gc_parent_id']==$pid)
                $ret[] = $data['data'][$i];//echo $pid;
            }
        }*/
        if(isset($data['children'][$pid]))
        foreach ($data['children'][$pid] as $i) {

            if (isset($data['data'][$i])) {
                $ret[] = $data['data'][$i];
            }
        }
        return $ret;
    }
    /*
         *  获取所有的一级分类
         *
         *
         */
    public function getGoodsOneClassListAll() {
        $class_list = $this->getGoodsClassList(array('gc_parent_id'=>0),'gc_id,gc_name,gc_sld_pc_picture');
        return $class_list;
    }
    /*
     *  获取所有显示的一级分类，只用于微信小程序
     *
     *
     */
    public function getGoodsOneClassList() {
        $class_list = $this->getGoodsClassList(array('gc_parent_id'=>0,'is_mobile_display'=>1),'gc_id,gc_name');
        return $class_list;
    }
    /*
     * 根据一级分类id获取对应的二三级分类
    */
    public function getGoodsClassListByPid($pid){
        $second_list = $this->getGoodsClassList(array('gc_parent_id'=>$pid,'is_mobile_display'=>1),'gc_id,gc_name');
        if(!empty($second_list)&&is_array($second_list)){
            foreach ($second_list as $k => $v){
                $third_class = $this->getGoodsClassList(array('gc_parent_id'=>$v['gc_id'],'is_mobile_display'=>1),'gc_id,gc_name,gc_mobile_picture');
                //并根据gc_id获取三级分类的图片
                if(!empty($third_class)&&is_array($third_class)){
                    foreach ($third_class as $key => $val){
                        if($val['gc_mobile_picture']){
                            $third_class[$key]['image'] = UPLOAD_SITE_URL.DS.'mall'.DS.ATTACH_MOBILE.DS.'category'.DS.$val['gc_mobile_picture'];
                        }else{
                            $third_class[$key]['image'] = WAP_SITE_URL.'/images/default_wap_cat.png';
                        }
                    }
                }
                $second_list[$k]['third_class'] = $third_class;
            }
        }
        return $second_list;
    }


    /**
     * Mobile平台获取全部商品分类
     *
     * @param   number  $update_all   更新
     * @return  array   数组
     */
    public function get_mobile_all_category($update_all = 0,$child_field_name = '') {
        if ($child_field_name) {
            $file_name = BASE_DATA_PATH.'/cache/index/mobile-mb-category.php';
        }else{
            $file_name = BASE_DATA_PATH.'/cache/index/mb-category.php';
        }
        if (!file_exists($file_name) || $update_all == 1) {//文件不存在时更新或者强制更新时执行
            $class_list = $this->getGoodsClassList(array(), 'gc_id, gc_name, gc_parent_id, gc_sort');
            $gc_list = array();
            $class1_deep = array();//第1级关联第3级数组
            $class2_ids = array();//第2级关联第1级ID数组
            $type_ids = array();//第2级分类关联类型
            if (is_array($class_list) && !empty($class_list)) {
                foreach ($class_list as $key => $value) {
                    $p_id = $value['gc_parent_id'];//父级ID
                    $gc_id = $value['gc_id'];
                    $sort = $value['gc_sort'];
                    if ($p_id == 0) {//第1级分类
                        $gc_list[$gc_id] = $value;
                    } elseif (array_key_exists($p_id,$gc_list)) {//第2级
                        $class2_ids[$gc_id] = $p_id;
                        //$type_ids[] = $value['type_id'];
                        if ($child_field_name) {
                            $gc_list[$p_id][$child_field_name][$gc_id] = $value;
                        }else{
                            $gc_list[$p_id]['class2'][$gc_id] = $value;
                        }
                    } elseif (array_key_exists($p_id,$class2_ids)) {//第3级
                        $parent_id = $class2_ids[$p_id];//取第1级ID
                        if ($child_field_name) {
                            $gc_list[$parent_id][$child_field_name][$p_id][$child_field_name][$gc_id] = $value;
                        }else{
                            $gc_list[$parent_id]['class2'][$p_id]['class3'][$gc_id] = $value;
                        }
                        $class1_deep[$parent_id][$sort][] = $value;
                    }
                }
                if ($child_field_name) {
                    $gc_list = array_values($gc_list);
                    foreach ($gc_list as $key => $value) {
                        if (isset($value[$child_field_name]) && !empty($value[$child_field_name])) {
                            $value[$child_field_name] = array_values($value[$child_field_name]);
                            foreach ($value[$child_field_name] as $v_key => $v_value) {
                                if (isset($v_value[$child_field_name]) && !empty($v_value[$child_field_name])) {
                                    $v_value[$child_field_name] = array_values($v_value[$child_field_name]);
                                }
                                $value[$child_field_name][$v_key] = $v_value;
                            }
                        }
                        $gc_list[$key] = $value;
                    }
                }
                return $gc_list;
                //$type_brands = $this->get_type_brands($type_ids);//类型关联品牌
                foreach ($gc_list as $key => $value) {
                    $gc_id = $value['gc_id'];
                    $gc_list[$gc_id]['pic'] = UPLOAD_SITE_URL.'/'.ATTACH_COMMON.'/category-pic-'.$gc_id.'.jpg';
                    $class3s = $class1_deep[$gc_id];

                    if (is_array($class3s) && !empty($class3s)) {//取关联的第3级
                        $class3_n = 0;//已经找到的第3级分类个数
                        ksort($class3s);//排序取到分类
                        foreach ($class3s as $k3 => $v3) {
                            if ($class3_n >= 5) {//最多取5个
                                break;
                            }
                            foreach ($v3 as $k => $v) {
                                if ($class3_n >= 5) {
                                    break;
                                }
                                if (is_array($v) && !empty($v)) {
                                    $p_id = $v['gc_parent_id'];
                                    $gc_id = $v['gc_id'];
                                    $parent_id = $class2_ids[$p_id];//取第1级ID
                                    $gc_list[$parent_id]['class3'][$gc_id] = $v;
                                    $class3_n += 1;
                                }
                            }
                        }
                    }
                    $class2s = $value['class2'];
                    if (is_array($class2s) && !empty($class2s)) {//第2级关联品牌
                        foreach ($class2s as $k2 => $v2) {
                            $p_id = $v2['gc_parent_id'];
                            $gc_id = $v2['gc_id'];
                            $type_id = $v2['type_id'];
                            //$gc_list[$p_id]['class2'][$gc_id]['brands'] = $type_brands[$type_id];
                        }
                    }
                }
                //F('mbcategory', $gc_list, 'cache/index');
            }
        } else {
            $gc_list = include $file_name;
        }
        return $gc_list;
    }





    /**
     * 前台头部的商品分类
     *
     * @param   number  $update_all   更新
     * @return  array   数组
     */
    public function get_all_category($update_all = 0) {
        $file_name = BASE_DATA_PATH.'/cache/index/category.php';
        if (!file_exists($file_name) || $update_all == 1) {//文件不存在时更新或者强制更新时执行
            $class_list = $this->getGoodsClassList(array(), 'gc_id, gc_name, type_id, gc_parent_id, gc_sort,gc_sld_pc_picture');
            $gc_list = array();
            $class1_deep = array();//第1级关联第3级数组
            $class2_ids = array();//第2级关联第1级ID数组
            $type_ids = array();//第2级分类关联类型
            if (is_array($class_list) && !empty($class_list)) {
                foreach ($class_list as $key => $value) {
                    $p_id = $value['gc_parent_id'];//父级ID
                    $gc_id = $value['gc_id'];
                    $sort = $value['gc_sort'];
                    if ($p_id == 0) {//第1级分类
                        //$gc_list[$gc_id] = $value;
                        $nav_info = $this->_getGoodsClassNavById($gc_id);
                        $gc_list[$gc_id] = array_merge($value, $nav_info);
                    } elseif (array_key_exists($p_id,$gc_list)) {//第2级
                        $class2_ids[$gc_id] = $p_id;
                        $type_ids[] = $value['type_id'];
                        $gc_list[$p_id]['class2'][$gc_id] = $value;
                    } elseif (array_key_exists($p_id,$class2_ids)) {//第3级
                        $parent_id = $class2_ids[$p_id];//取第1级ID
                        $gc_list[$parent_id]['class2'][$p_id]['class3'][$gc_id] = $value;
                        $class1_deep[$parent_id][$sort][] = $value;
                    }
                }
                $type_brands = $this->get_type_brands($type_ids);//类型关联品牌
                foreach ($gc_list as $key => $value) {
                    $gc_id = $value['gc_id'];
                    $gc_list[$gc_id]['pic'] = UPLOAD_SITE_URL.'/'.ATTACH_COMMON.'/category-pic-'.$gc_id.'.jpg';
                    if(isset($class1_deep[$gc_id])) {
                        $class3s = $class1_deep[$gc_id];

                        if (is_array($class3s) && !empty($class3s)) {//取关联的第3级
                            $class3_n = 0;//已经找到的第3级分类个数
                            ksort($class3s);//排序取到分类
                            foreach ($class3s as $k3 => $v3) {
                                if ($class3_n >= 5) {//最多取5个
                                    break;
                                }
                                foreach ($v3 as $k => $v) {
                                    if ($class3_n >= 5) {
                                        break;
                                    }
                                    if (is_array($v) && !empty($v)) {
                                        $p_id = $v['gc_parent_id'];
                                        $gc_id = $v['gc_id'];
                                        $parent_id = $class2_ids[$p_id];//取第1级ID
                                        $gc_list[$parent_id]['class3'][$gc_id] = $v;
                                        $class3_n += 1;
                                    }
                                }
                            }
                        }
                    }
                    $class2s = $value['class2'];
                    if (is_array($class2s) && !empty($class2s)) {//第2级关联品牌
                        foreach ($class2s as $k2 => $v2) {
                            $p_id = $v2['gc_parent_id'];
                            $gc_id = $v2['gc_id'];
                            $type_id = $v2['type_id'];
                            if(isset($type_brands[$type_id]))
                            $gc_list[$p_id]['class2'][$gc_id]['brands'] = $type_brands[$type_id];
                        }
                    }
                }
                //F('category', $gc_list, 'cache');
            }
        } else {
            $gc_list = include $file_name;
        }
        $lang_type=LANG_TYPE;

        return $gc_list;
    }
    private function _getGoodsClassNavById($gc_id) {
        $model_class_nav = new GoodsClassNav();
        $model_brand = new Brand();
        $nav_info = $model_class_nav->getGoodsClassNavInfoByGcId($gc_id);
        if (empty($nav_info)) {
            return array();
        }
        $nav_info['cn_pic'] = UPLOAD_SITE_URL. '/' . ATTACH_GOODS_CLASS . '/' . $nav_info['cn_pic'];
        $nav_info['cn_adv1'] = UPLOAD_SITE_URL. '/' . ATTACH_GOODS_CLASS . '/' . $nav_info['cn_adv1'];
        $nav_info['cn_adv2'] = UPLOAD_SITE_URL. '/' . ATTACH_GOODS_CLASS . '/' . $nav_info['cn_adv2'];
        if ($nav_info['cn_brandids'] != '') {
            $nav_info['cn_brands'] = $model_brand->getBrandList(array('brand_id' => array('in', $nav_info['cn_brandids'])));
            unset($nav_info['cn_brandids']);
        }
        if ($nav_info['cn_classids'] != '') {
            $nav_info['cn_classs'] = $this->getGoodsClassList(array('gc_id' => array('in', $nav_info['cn_classids'])));
            unset($nav_info['cn_classids']);
        }
        if ($nav_info['cn_alias'] != '') {
            $nav_info['gc_name'] = $nav_info['cn_alias'];
            unset($nav_info['cn_alias']);
        }

        return $nav_info;
    }

    /**
     * 类型关联品牌
     *
     * @param   array  $type_ids   类型
     * @return  array   数组
     */
    public function get_type_brands($type_ids = array()) {
        $brands = array();//品牌
        $type_brands = array();//类型关联品牌
        if (is_array($type_ids) && !empty($type_ids)) {
            $type_ids = array_unique($type_ids);
            $type_list = DB::name('type_brand')->where(array('type_id'=>array('in',implode(",",$type_ids))))->limit(10000)->select();
            if (is_array($type_list) && !empty($type_list)) {
                $brand_list = DB::name('brand')->field('brand_id,brand_name,brand_pic')->where(array('brand_apply'=>1))->limit(10000)->select();
                if (is_array($brand_list) && !empty($brand_list)) {
                    foreach ($brand_list as $key => $value) {
                        $brand_id = $value['brand_id'];
                        $brands[$brand_id] = $value;
                    }
                    foreach ($type_list as $key => $value) {
                        $type_id = $value['type_id'];
                        $brand_id = $value['brand_id'];
                        $brand = $brands[$brand_id];
                        if (is_array($brand) && !empty($brand)) {
                            $type_brands[$type_id][$brand_id] = $brand;
                        }
                    }
                }
            }

        }
        return $type_brands;
    }

    /**
     * 类别列表
     *
     * @param array $condition 检索条件
     * @return array 数组结构的返回结果
     */
    public function getClassList($condition ,$field='*',$lang='zh_cn'){
        $condition_str = $this->_condition($condition);
        $param = array();
        $param['table'] = 'goods_class';
        $param['field'] = $field;
        $param['where'] = $condition_str;
        $param['order'] = $condition['order'] ? $condition['order'] : 'gc_parent_id asc,gc_sort asc,gc_id asc';
        $result = Db::select($param);


        return $result;
    }

    /**
     * 构造检索条件
     *
     * @param int $id 记录ID
     * @return string 字符串类型的返回结果
     */
    private function _condition($condition){
        $condition_str = '';

        if (!is_null($condition['gc_parent_id'])){
            $condition_str .= " and gc_parent_id = '". intval($condition['gc_parent_id']) ."'";
        }
        if (!is_null($condition['no_gc_id'])){
            $condition_str .= " and gc_id != '". intval($condition['no_gc_id']) ."'";
        }
        if ($condition['in_gc_id'] != ''){
            $condition_str .= " and gc_id in (". $condition['in_gc_id'] .")";
        }
        if ($condition['gc_name'] != ''){
            $condition_str .= " and gc_name = '". $condition['gc_name'] ."'";
        }
        if (isset($condition['un_type_name'])) {
            $condition_str .= " and type_name <> ''";
        }
        if ($condition['un_type_id'] != '') {
            $condition_str .= " and type_id <> '". $condition['un_type_id'] ."'";
        }
        if ($condition['in_type_id'] != '') {
            $condition_str .= " and type_id in (".$condition['in_type_id'].")";
        }

        return $condition_str;
    }

    /**
     * 取单个分类的内容
     *
     * @param int $id 分类ID
     * @return array 数组类型的返回结果
     */
    public function getOneGoodsClass($id) {
        if (intval ( $id ) > 0) {
            $result = $this->where(array('gc_id' => $id))->find();
            return $result;
        } else {
            return false;
        }
    }

    /**
     * 新增
     *
     * @param array $param 参数内容
     * @return bool 布尔类型的返回结果
     */
    public function add($param){
        if (empty($param)){
            return false;
        }
        if (is_array($param)){
            $tmp = array();
            foreach ($param as $k => $v){
                $tmp[$k] = $v;
            }
            $result = Db::insert('goods_class',$tmp);
            return $result;
        }else {
            return false;
        }
    }

    /**
     * 更新信息
     *
     * @param array $param 更新数据
     * @return bool 布尔类型的返回结果
     */
    public function goodsClassUpdate($param){
        if (empty($param)){
            return false;
        }
        if (is_array($param)){
            $tmp = array();
            foreach ($param as $k => $v){
                $tmp[$k] = $v;
            }
            $where = " gc_id = '". $param['gc_id'] ."'";
            $result = Db::update('goods_class',$tmp,$where);
            return $result;
        }else {
            return false;
        }
    }

    /**
     * 更新信息
     *
     * @param array $param 更新数据
     * @return bool 布尔类型的返回结果
     */
    public function updateWhere($param, $condition){
        if (empty($param)){
            return false;
        }
        if (is_array($param)){
            $tmp = array();
            foreach ($param as $k => $v){
                $tmp[$k] = $v;
            }
            $where = $this->_condition($condition);
            $result = Db::update('goods_class',$tmp,$where);
            return $result;
        }else {
            return false;
        }
    }

    /**
     * 删除分类
     *
     * @param int $id 记录ID
     * @return bool 布尔类型的返回结果
     */
    public function del($id){
        if (intval($id) > 0){
            $where = " gc_id = '". intval($id) ."'";
            $result = Db::delete('goods_class',$where);
            return $result;
        }else {
            return false;
        }
    }

    /**
     * 取分类列表，最多为三级
     *
     * @param int $show_deep 显示深度
     * @param array $condition 检索条件
     * @return array 数组类型的返回结果
     */
    public function getTreeClassList($show_deep='3',$condition=array(),$lang='zh_cn'){
        $class_list = $this->getClassList($condition,null,$lang);
        $goods_class = array();//分类数组
        if(is_array($class_list) && !empty($class_list)) {
            $show_deep = intval($show_deep);
            if ($show_deep == 1){//只显示第一级时用循环给分类加上深度deep号码
                foreach ($class_list as $val) {
                    if($val['gc_parent_id'] == 0) {
                        $val['deep'] = 1;
                        $goods_class[] = $val;
                    } else {
                        break;//父类编号不为0时退出循环
                    }
                }
            } else {//显示第二和三级时用递归
                $goods_class = $this->_getTreeClassList($show_deep,$class_list,$lang);
            }
        }
        return $goods_class;
    }

    /**
     * 递归 整理分类
     *
     * @param int $show_deep 显示深度
     * @param array $class_list 类别内容集合
     * @param int $deep 深度
     * @param int $parent_id 父类编号
     * @param int $i 上次循环编号
     * @return array $show_class 返回数组形式的查询结果
     */
    private function _getTreeClassList($show_deep,$class_list,$deep=1,$parent_id=0,$i=0){
        static $show_class = array();//树状的平行数组
        if(is_array($class_list) && !empty($class_list)) {
            $size = count($class_list);
            if($i == 0) $show_class = array();//从0开始时清空数组，防止多次调用后出现重复
            for ($i;$i < $size;$i++) {//$i为上次循环到的分类编号，避免重新从第一条开始
                $val = $class_list[$i];
                $gc_id = $val['gc_id'];
                $gc_parent_id	= $val['gc_parent_id'];
                if($gc_parent_id == $parent_id) {
                    $val['deep'] = $deep;
                    $show_class[] = $val;
                    if($deep < $show_deep && $deep < 3) {//本次深度小于显示深度时执行，避免取出的数据无用
                        $this->_getTreeClassList($show_deep,$class_list,$deep+1,$gc_id,$i+1);
                    }
                }
                if($gc_parent_id > $parent_id) break;//当前分类的父编号大于本次递归的时退出循环
            }
        }
        return $show_class;
    }

    /**
     * 取指定分类ID下的所有子类
     *
     * @param int/array $parent_id 父ID 可以单一可以为数组
     * @return array $rs_row 返回数组形式的查询结果
     */
    public function getChildClass($parent_id){
        $condition = array('order'=>'gc_parent_id asc,gc_sort asc,gc_id asc');
        $all_class = $this->getClassList($condition);
        if (is_array($all_class)){
            if (!is_array($parent_id)){
                $parent_id = array($parent_id);
            }
            $result = array();
            foreach ($all_class as $k => $v){
                $gc_id	= $v['gc_id'];//返回的结果包括父类
                $gc_parent_id	= $v['gc_parent_id'];
                if (in_array($gc_id,$parent_id) || in_array($gc_parent_id,$parent_id)){
                    $parent_id[] = $v['gc_id'];
                    $result[] = $v;
                }
            }
            return $result;
        }else {
            return false;
        }
    }

    /**
     * 取指定分类ID的导航链接
     *
     * @param int $id 父类ID/子类ID
     * @param int $sign 1、0 1为最后一级不加超链接，0为加超链接
     * @return array $nav_link 返回数组形式类别导航连接
     */
    public function getGoodsClassNav($id = 0, $sign = 1,$find_top = 0) {
        if (intval ( $id ) > 0) {
            $data = array_filter($this->H('bbc_goods_class'));
            //print_r($data);die;

            //$data = $this->magicLang($data,'bbc_goods_class');

            // 当前分类不加超链接
            if ($sign == 1) {
                //print_r($data[$id]);
                $nav_link [] = array(
                    'title' => $data[$id]['gc_name'],
                    'cid' =>$data[$id]['gc_id'],
                    'depth'=>$data[$id]['depth']
                );
            } else {
                $nav_link [] = array(
                    'title' => $data[$id]['gc_name'],
                    'link' => urlShop('goodslist', 'index', array('cid' => $data[$id]['gc_id'])),
                    'cid' => $data[$id]['gc_id'],
                    'depth'=>$data[$id]['depth']
                );
            }

            // 最多循环4层
            for($i = 1; $i < 5; $i ++) {
                if ($data[$id]['gc_parent_id'] == '0') {
                    break;
                }
                $id = $data[$id]['gc_parent_id'];
                $nav_link[] = array(
                    'title' => $data[$id]['gc_name'],
                    'link' => urlShop('goodslist', 'index', array('cid' => $data[$id]['gc_id'])),
                    'cid' => $data[$id]['gc_id'],
                    'depth'=>$data[$id]['depth']
                );
            }
        } else {
            // 加上 首页 商品分类导航
            $nav_link[] = array('title' => lang('搜索结果'));
        }
        $top_cid = 0;

        /*foreach ($nav_link as $v){
            if($v['depth']==1){
                $top_cid = $v['cid'];
            }
        }*/


        // 首页导航
        $nav_link[] = array('title' => lang('homepage'), 'link' => MALL_URL);

        krsort ( $nav_link );
        foreach($nav_link as &$value){
            $lang_type=LANG_TYPE;

        }
        if($find_top){
            return [$nav_link,$top_cid];
        }else {
            return $nav_link;
        }
    }

    /**
     * 取指定分类ID的所有父级分类
     *
     * @param int $id 父类ID/子类ID
     * @return array $nav_link 返回数组形式类别导航连接
     */
    public function getGoodsClassLineForTag($id = 0) {
        if (intval($id)> 0) {
            $gc_line = array();
            /**
             * 取当前类别信息
             */
            $class = $this->getOneGoodsClass(intval($id));
            /**
             * 是否是子类
             */
            if ($class['gc_parent_id'] != 0) {
                $parent_1 = $this->getOneGoodsClass($class['gc_parent_id']);
                if ($parent_1['gc_parent_id'] != 0) {
                    $parent_2 = $this->getOneGoodsClass($parent_1['gc_parent_id']);
                    $gc_line['gc_id'] = $parent_2['gc_id'];
                    $gc_line['type_id'] = $parent_2['type_id'];
                    $gc_line['gc_id_1'] = $parent_2 ['gc_id'];
                    $gc_line['gc_tag_name'] = trim($parent_2['gc_name']) . ' >';
                    $gc_line['gc_tag_value'] = trim($parent_2['gc_name']) . ',';
                }
                $gc_line['gc_id'] = $parent_1['gc_id'];
                $gc_line['type_id'] = $parent_1['type_id'];
                if (!isset($gc_line['gc_id_1'])) {
                    $gc_line['gc_id_1'] = $parent_1['gc_id'];
                } else {
                    $gc_line['gc_id_2'] = $parent_1['gc_id'];
                }
                $gc_line['gc_tag_name'] .= trim($parent_1['gc_name']) . ' >';
                $gc_line['gc_tag_value'] .= trim($parent_1['gc_name']) . ',';
            }
            $gc_line['gc_id'] = $class['gc_id'];
            $gc_line['type_id'] = $class['type_id'];
            if (!isset($gc_line['gc_id_1'])) {
                $gc_line['gc_id_1'] = $class['gc_id'];
            } else if (!isset($gc_line['gc_id_2'])) {
                $gc_line['gc_id_2'] = $class['gc_id'];
            } else {
                $gc_line['gc_id_3'] = $class['gc_id'];
            }
            $gc_line['gc_tag_name'] .= trim($class['gc_name']) . ' >';
            $gc_line['gc_tag_value'] .= trim($class['gc_name']) . ',';
        }
        $gc_line['gc_tag_name'] = trim($gc_line['gc_tag_name'], ' >');
        $gc_line['gc_tag_value'] = trim($gc_line['gc_tag_value'], ',');
        return $gc_line;
    }

    /**
     * 取得分类关键词，方便SEO
     *
     */
    public function getKeyWords($gc_id = null){
        if (empty($gc_id)) return false;
        $keywrods = ($seo_info = $this->H('bbc_goods_class_seo')) ? $seo_info : $this->H('bbc_goods_class_seo',true);
        $seo_title = $keywrods[$gc_id]['title'];
        $seo_key = '';
        $seo_desc = '';
        if (intval($gc_id )> 0){
            if (isset($keywrods[$gc_id])){
                $seo_key .= $keywrods[$gc_id]['key'].',';
                $seo_desc .= $keywrods[$gc_id]['desc'].',';
            }
            $goods_class = $this->H('bbc_goods_class') ? $this->H('bbc_goods_class') : $this->H('bbc_goods_class', true);
            if(($gc_id = $goods_class[$gc_id]['gc_parent_id']) > 0){
                if (isset($keywrods[$gc_id])){
                    $seo_key .= $keywrods[$gc_id]['key'].',';
                    $seo_desc .= $keywrods[$gc_id]['desc'].',';
                }
            }
            if($gc_id >0)
            if(($gc_id = $goods_class[$gc_id]['gc_parent_id']) > 0){
                if (isset($keywrods[$gc_id])){
                    $seo_key .= $keywrods[$gc_id]['key'].',';
                    $seo_desc .= $keywrods[$gc_id]['desc'].',';
                }
            }
        }
        return array(1=>$seo_title,2=>trim($seo_key,','),3=>trim($seo_desc,','));
    }
    /**
     * 返回缓存数据 原H('goods_class')形式
     */
    public function getGoodsClassForCacheModel() {

        if ($this->gcForCacheModel)
            return $this->gcForCacheModel;

        $data = $this->getCache();

        $r = $data['data'];
        $p = $data['parent'];
        $c = $data['children'];
        $c2 = $data['children2'];

        $r = (array) $r;

        foreach ($r as $k => & $v) {
            if ((string) $p[$k] == '0') {
                $v['depth'] = 1;
                if ($data['children'][$k]) {
                    $v['child'] = implode(',', $c[$k]);
                }
                if ($data['children2'][$k]) {
                    $v['childchild'] = implode(',', $c2[$k]);
                }
            } else if ((string) $p[$p[$k]] == '0') {
                $v['depth'] = 2;
                if ($data['children'][$k]) {
                    $v['child'] = implode(',', $c[$k]);
                }
            } else if ((string) $p[$p[$p[$k]]] == '0') {
                $v['depth'] = 3;
            }
        }

        return $this->gcForCacheModel = $r;
    }

}