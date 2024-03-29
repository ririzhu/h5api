<?php
namespace app\v1\model;

use app\v1\controller\Base;
use think\Model;

class Search extends Model
{

    //是否开启分面搜索
    private $_open_face = true;

    //全文搜索对象
    private $_xs_search;

    //全文搜索到的商品ID数组
    private $_indexer_ids = array();

    //全文搜索到的品牌数组
    private $_indexer_brands = array();

    //全文检索到的商品分类数组
    private $_indexer_cates = array();


    //全文搜索结果总数
    private $_indexer_count;

    //搜索结果中品牌分面信息
    private $_face_brand = array();

    //搜索结果中品牌分面信息
    private $_face_attr = array();

    /**
     * 从全文索引库搜索关键词
     * @param unknown $condition 条件
     * @param unknown $order 排序
     * @param number $pagesize 每页显示商品数
     * @return
     */
    public function getIndexerList($condition = array(), $order = array(), $pagesize = 24) {

        //全文搜索初始化
        $this->_createXS($pagesize);

        //设置搜索内容
        $this->_setQueryXS($condition,$order);

        //执行搜索
        $result = $this->_searchXS();

        if ($result) {
            return array($this->_indexer_ids, $this->_indexer_count);
        } else {
            return false;
        }

    }

    /**
     * 设置全文检索查询条件
     * @param unknown $condition
     * @param array $order
     */
    private function _setQueryXS($condition,$order) {
        if (isset($condition['keyword'])) {
            $this->_xs_search->setQuery(is_null($condition['keyword']) ? '':$condition['keyword']);
        }
        if (isset($condition['cate'])) {
            $this->_xs_search->addQueryString($condition['cate']['key'].':'.$condition['cate']['value']);
        }
        if (isset($condition['brand_id'])) {
            $this->_xs_search->addQueryString('brand_id'.':'.$condition['brand_id']);
        }
        if (isset($condition['vid'])) {
            $this->_xs_search->addQueryString('vid'.':'.$condition['vid']);
        }
        if (isset($condition['area_id'])) {
            $this->_xs_search->addQueryString('area_id'.':'.$condition['area_id']);
        }
        if (is_array($condition['attr_id'])) {
            foreach ($condition['attr_id'] as $attr_id) {
                $this->_xs_search->addQueryString('attr_id'.':'.$attr_id);
            }
        }
        $this->_xs_search->setSort($order['key'],$order['value']);
//         echo $this->_xs_search->getQuery();
    }

    /**
     * 创建全文搜索对象，并初始化基本参数
     * @param number $pagesize 每页显示商品数
     * @param string $appname 全文搜索INI配置文件名
     */
    private function _createXS($pagesize,$appname) {
        if (is_numeric($_GET['pn']) && $_GET['pn'] > 0) {
            $pn = intval($_GET['pn']);
            $start =  ($pn-1) * $pagesize;
        } else {
            $start = null;
        }
        require_once(BASE_DATA_PATH.'/api/xs/lib/XS.php');
        $obj_doc = new XSDocument();
        $obj_xs = new XS(C('fullindexer.appname'));
        $this->_xs_search = $obj_xs->search;
        $this->_xs_search->setCharset(CHARSET);
        $this->_xs_search->setLimit($pagesize,$start);
        //设置分面
        if ($this->_open_face) {
            $this->_xs_search->setFacets(array('brand_id','attr_id'));
        }
    }

    /**
     * 执行全文搜索
     */
    private function _searchXS(){
        try {

            $goods_class = H('goods_class') ? H('goods_class') : H('goods_class', true);

            $docs = $this->_xs_search->search();
            $count = $this->_xs_search->getLastCount();
            $goods_ids = array();
            $brands = array();
            $cates = array();
            foreach ($docs as $k => $doc) {
                $goods_ids[] = $doc->gid;
//                 if ($doc->brand_id > 0) {
//                     $brands[$doc->brand_id]['brand_id'] = $doc->brand_id;
//                     $brands[$doc->brand_id]['brand_name'] = $doc->brand_name;
//                 }
//                 if ($doc->gc_id > 0) {
//                     $cates[$doc->gc_id]['gc_id'] = $doc->gc_id;
//                     $cates[$doc->gc_id]['gc_name'] = $goods_class[$doc->gc_id]['gc_name'];
//                 }
            }
            $this->_indexer_ids = $goods_ids;
            $this->_indexer_count = $count;
            $this->_indexer_brands = $brands;
            $this->_indexer_cates = $cates;

            //读取分面结果
            if ($this->_open_face) {
                $this->_face_brand = $this->_xs_search->getFacets('brand_id');
                $this->_face_attr = $this->_xs_search->getFacets('attr_id');
                $this->_parseFaceAttr($this->_face_attr);
            }
        } catch (XSException $e) {
            if (C('debug')) {
                showMsg($e->getMessage(),'','html','error');
            } else {
                Log::record($e->getMessage()."\r\n".$sql,Log::ERR);
                return false;
            }
        }
        return true;
    }

    /**
     * 处理属性多面信息
     */
    private function _parseFaceAttr($face_attr = array()) {
        if (!is_array($face_attr)) return;
        $new_attr = array();
        foreach ($face_attr as $k => $v) {
            $new_attr = array_merge($new_attr,explode('_',$k));
        }
        $this->_face_attr = $new_attr;
    }

    /**
     * 删除没有商品的品牌(不显示)
     * @param unknown $brand_array
     * @return unknown|multitype:
     */
    public function delInvalidBrand($brand_array = array()) {
        if (!$this->_open_face) return $brand_array;
        if (is_array($brand_array) && is_array($this->_face_brand)) {
            foreach ($brand_array as $k => $v) {
                if (!isset($this->_face_brand[$k])) {
                    unset($brand_array[$k]);
                }
            }
        }
        return $brand_array;
    }

    /**
     * 删除没有商品的属性(不显示)
     * @param unknown $brand_array
     * @return unknown|multitype:
     */
    public function delInvalidAttr($attr_array = array()) {
        if (!$this->_open_face) return $attr_array;
        if (is_array($attr_array) && is_array($this->_face_attr)) {
            foreach ($attr_array as $key => $value) {
                if (is_array($value['value'])) {
                    foreach ($value['value'] as $k => $v) {
                        if (!in_array($k,$this->_face_attr)) {
                            unset($attr_array[$key]['value'][$k]);
                        }
                    }
                }
            }
        }
        return $attr_array;
    }

    public function __get($key) {
        return $this->$key;
    }

    /**
     * 取得商品分类详细信息
     * 格式  分类id@规格id_规格id@品牌id_品牌id@属性id_属性id
     *
     * @param array $param 需要的参数内容
     * @return array 数组类型的返回结果
     */
    public function getAttrLists($cid,$brand_id,$attr_id,$area_id){
        if(intval($area_id) > 0){
            $area_id = intval($area_id);
        }

        //由规格型号组合取得商品ID
        $param = array();

        $param['gc_id']		= $cid;
        if($brand_id != '' && intval($brand_id) !== 0){
            $param['brand_id']	= explode('_', $brand_id);
        }
        if($attr_id != '' && intval($attr_id) !== 0){
            $param['attr_id']	= explode('_', $attr_id);
        }
        if($area_id > 0){
            $param['area_id']	= $area_id;
        }
        //生成缓存的键值
        $hash_key	= md5($cid.'_'.$brand_id.'_'.$attr_id);

        //先查找$hash_key缓存
        $base = new Base();
        $data = $base->rcache($hash_key,'search_p');
        if (empty($data)) {
            $model_type = new Types();

            // 初始化统计数据为0
            $count = 0;

            // 获取当前的分类内容
            $class_array = $this->H('bbc_goods_class') ? $this->H('bbc_goods_class') : $this->H('bbc_goods_class', true);
            $data = $class_array[$param['gc_id']];
            $child = (!empty($data['child'])) ? explode(',', $data['child']) : array();
            $childchild = (!empty($data['childchild'])) ? explode(',', $data['childchild']) : array();
            //if(isset($data['gcid_array']))
            $data['gcid_array'] = array_merge(array($param['gc_id']), $child, $childchild);
            //print_r($data);die;
            if (!empty($data) && isset($data['gcid_array']) && count($data['gcid_array']) == 1) {
                // 根据属性查找商品
                if (isset($param['attr_id']) && is_array($param['attr_id'])) {
                    // 商品id数组
                    $goodsid_array = array();
                    $data['sign'] = false;
                    foreach ($param['attr_id'] as $val) {
                        $where = array();
                        $where['attr_value_id'] = $val;
                        if ($data['sign']) {
                            $where['gid'] = array('in', $goodsid_array);
                        }
                        $goodsattrindex_list = Model('goods_attr_index')->getGoodsAttrIndexList($where, 'gid');
                        if (!empty($goodsattrindex_list)) {
                            $data['sign'] = true;
                            $tpl_goodsid_array = array();
                            foreach ($goodsattrindex_list as $val) {
                                $tpl_goodsid_array[] = $val['gid'];
                            }
                            $goodsid_array = $tpl_goodsid_array;
                        } else {
                            $data['goodsid_array'] = $goodsid_array = array();
                            $data['sign'] = false;
                            break;
                        }
                    }
                    if ($data['sign']) {
                        $data['goodsid_array'] = $goodsid_array;
                    }
                }
                $data['brand_array'] = array();
                $param['brand_id'] = array();
                    //品牌列表
                $typebrand_list = $model_type->getTypeBrandList(array('type_id' => $data['type_id']), 'brand_id');
                if (!empty($typebrand_list)) {
                    $brandid_array = array();
                    foreach ($typebrand_list as $val) {
                        $brandid_array[] = $val['brand_id'];
                    }
                    if(!empty($brandid_array)) {
                        $brand_array = Model('brand')->getBrandPassList("brand_id in (" . arrayToString($brandid_array) . ")", 'brand_id,brand_name');
                        $data['brand_array'] = array_under_reset($brand_array, 'brand_id');
                    }
                }
                // 被选中的品牌
                if(is_array($param['brand_id']) && !empty($data['brand_array'])){
                    $checked_brand = array();
                    foreach ($param['brand_id'] as $s){
                        if(isset($data['brand_array'][$s])){
                            $checked_brand[$s]['brand_name'] = $data['brand_array'][$s]['brand_name'];
                        }
                    }
                    $data['checked_brand'] = $checked_brand;
                }

                //属性列表
                $model_attribute =new Attribute();
                $attribute_list = $model_attribute->getAttributeShowList(array('type_id' => $data['type_id']), 'attr_id,attr_name');
                $attributevalue_list = $model_attribute->getAttributeValueList(array('type_id' => $data['type_id']), 'attr_value_id,attr_value_name,attr_id');
                $attributevalue_list = array_under_reset($attributevalue_list, 'attr_id', 2);
                $attr_array = array();
                if (!empty($attribute_list)) {
                    foreach ($attribute_list as $val) {
                        $attr_array[$val['attr_id']]['name'] = $val['attr_name'];
                        $tpl_array = array_under_reset($attributevalue_list[$val['attr_id']], 'attr_value_id');
                        $attr_array[$val['attr_id']]['value'] = $tpl_array;
                    }
                }
                $data['attr_array'] = $attr_array;

                //被选中的属性
                if(isset($param['attr_id']) && is_array($param['attr_id']) && !empty($data['attr_array'])){
                    $checked_attr = array();
                    foreach ($param['attr_id'] as $s){
                        foreach ($data['attr_array'] as $k=>$d){
                            if(isset($d['value'][$s])){
                                $checked_attr[$k]['attr_name']		= $d['name'];
                                $checked_attr[$k]['attr_value_id']	= $s;
                                $checked_attr[$k]['attr_value_name']= $d['value'][$s]['attr_value_name'];
                            }
                        }
                    }
                    $data['checked_attr'] = $checked_attr;
                }

                //缓存规格组合结果
                //$base->wmemcache($hash_key,$data,'search_p');
            }
        }
        return $data;
    }

    /**
     * 从TAG中查找分类
     */
    public function getTagCategory($keyword = '') {
        if ($keyword != '') {
            // 跟据class_tag缓存搜索出与keyword相关的分类
            $tag_list = ($tag = $this->H('bbc_class_tag')) ? $tag : $this->H('bbc_class_tag', true);
            $data = array();
            if (!empty($tag_list) && is_array($tag_list)) {
                foreach($tag_list as $key => $val) {
                    $tag_value = str_replace(',', '==DYMall==', $val['gc_tag_value']);
                    if (strpos($tag_value, $keyword)) {
                        $data[] = $val['gc_id'];
                    }
                }
            }
        }
        return $this->getLeftCategory($data, 1);
    }

    /**
     * 获取父级分类，递归调用
     */
    private function _getParentCategory($gc_id, $goods_class, $data) {
        array_unshift($data, $gc_id);
        if ($goods_class[$gc_id]['gc_parent_id'] != 0) {
            return $this->_getParentCategory($goods_class[$gc_id]['gc_parent_id'], $goods_class, $data);
        } else {
            return $data;
        }
    }

    /**
     * 显示左侧商品分类
     * @param array $param 分类id
     * @sign int $sign 0为取得最后一级的同级分类，1为不取得
     */
    public function getLeftCategory($param, $sign = 0) {
        $data = array();
        if (!empty($param)) {
            $goods_class = $this->H('bbc_goods_class') ? $this->H('bbc_goods_class') : $this->H('bbc_goods_class', true);
            foreach ($param as $val) {
                $data[] = $this->_getParentCategory($val, $goods_class, array());
            }
        }
        $tpl_data = array();
        $gc = new GoodsClass();
        $gc_list = $gc->get_all_category();
        foreach ($data as $value) {
            //$tpl_data[$val[0]][$val[1]][$val[2]] = $val[2];
            if (!empty($gc_list[$value[0]])){   // 一级
                $tpl_data[$value[0]]['gc_id'] = $gc_list[$value[0]]['gc_id'];
                $tpl_data[$value[0]]['gc_name'] = $gc_list[$value[0]]['gc_name'];
                if (isset($value[1]) && isset($gc_list[$value[0]]['class2'][$value[1]])) { // 二级
                    $tpl_data[$value[0]]['class2'][$value[1]]['gc_id'] = $gc_list[$value[0]]['class2'][$value[1]]['gc_id'];
                    $tpl_data[$value[0]]['class2'][$value[1]]['gc_name'] = $gc_list[$value[0]]['class2'][$value[1]]['gc_name'];
                    if (isset($value[2])&&!empty($gc_list[$value[0]]['class2'][$value[1]]['class3'][$value[2]])) {    // 三级
                        $tpl_data[$value[0]]['class2'][$value[1]]['class3'][$value[2]]['gc_id'] = $gc_list[$value[0]]['class2'][$value[1]]['class3'][$value[2]]['gc_id'];
                        $tpl_data[$value[0]]['class2'][$value[1]]['class3'][$value[2]]['gc_name'] = $gc_list[$value[0]]['class2'][$value[1]]['class3'][$value[2]]['gc_name'];
                        if (!$sign) {   // 取得全部三级分类
                            foreach ($gc_list[$value[0]]['class2'][$value[1]]['class3'] as $val) {
                                $tpl_data[$value[0]]['class2'][$value[1]]['class3'][$val['gc_id']]['gc_id'] = $val['gc_id'];
                                $tpl_data[$value[0]]['class2'][$value[1]]['class3'][$val['gc_id']]['gc_name'] = $val['gc_name'];
                                if ($value[2] == $val['gc_id']) {
                                    $tpl_data[$value[0]]['class2'][$value[1]]['class3'][$val['gc_id']]['default'] = 1;
                                }
                            }
                        }
                    } else {    // 取得全部二级分类
                        if (!$sign) {   // 取得同级分类
                            if (!empty($gc_list[$value[0]]['class2'])) {
                                foreach ($gc_list[$value[0]]['class2'] as $gc2) {
                                    $tpl_data[$value[0]]['class2'][$gc2['gc_id']]['gc_id'] = $gc2['gc_id'];
                                    $tpl_data[$value[0]]['class2'][$gc2['gc_id']]['gc_name'] = $gc2['gc_name'];
                                    if (!empty($gc2['class3'])) {
                                        foreach ($gc2['class3'] as $gc3) {
                                            $tpl_data[$value[0]]['class2'][$gc2['gc_id']]['class3'][$gc3['gc_id']]['gc_id'] = $gc3['gc_id'];
                                            $tpl_data[$value[0]]['class2'][$gc2['gc_id']]['class3'][$gc3['gc_id']]['gc_name'] = $gc3['gc_name'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                } else {    // 取得全部一级分类
                    if (!$sign) {   // 取得同级分类
                        if (!empty($gc_list)) {
                            foreach ($gc_list as $gc1) {
                                $tpl_data[$gc1['gc_id']]['gc_id'] = $gc1['gc_id'];
                                $tpl_data[$gc1['gc_id']]['gc_name'] = $gc1['gc_name'];
                                if (!empty($gc1['class2'])) {
                                    foreach ($gc1['class2'] as $gc2) {
                                        $tpl_data[$gc1['gc_id']]['class2'][$gc2['gc_id']]['gc_id'] = $gc2['gc_id'];
                                        $tpl_data[$gc1['gc_id']]['class2'][$gc2['gc_id']]['gc_name'] = $gc2['gc_name'];
                                        if (!empty($gc2['class3'])) {
                                            foreach ($gc2['class3'] as $gc3) {
                                                $tpl_data[$gc1['gc_id']]['class2'][$gc2['gc_id']]['class3'][$gc3['gc_id']]['gc_id'] = $gc3['gc_id'];
                                                $tpl_data[$gc1['gc_id']]['class2'][$gc2['gc_id']]['class3'][$gc3['gc_id']]['gc_name'] = $gc3['gc_name'];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $tpl_data;
    }
    /**
     * 全文搜索
     * @return array 商品主键，搜索结果总数
     */
    public function indexerSearch($get = array(),$pagesize) {
        if (!C('fullindexer.open')) return array(null,0);
        $condition = array();
        //拼接条件
        if (intval($get['cid']) > 0) {
            $cid = intval($get['cid']);
        } elseif (intval($get['gc_id']) > 0) {
            $cid = intval($get['gc_id']);
        }
        if ($cid) {
            $goods_class = Model('goods_class')->getGoodsClassForCacheModel();
            $depth = $goods_class[$cid]['depth'];
            $cate_field = 'cate_'.$depth;
            $condition['cate']['key'] = $cate_field;
            $condition['cate']['value'] = $cid;
        }
        if ($get['keyword'] != '') {
            $condition['keyword'] = $get['keyword'];
        }
        if (intval($get['b_id']) > 0) {
            $condition['brand_id'] = intval($get['b_id']);
        }
        if (preg_match('/^[\d_]+$/',$get['a_id'])) {
            $attr_ids = explode('_',$get['a_id']);
            if (is_array($attr_ids)){
                foreach ($attr_ids as $v) {
                    if (intval($v) > 0) {
                        $condition['attr_id'][] = intval($v);
                    }
                }
            }
        }
        if ($get['type'] == 1) {
            $condition['vid'] = 1;
        }
        if (intval($get['area_id']) > 0) {
            $condition['area_id'] = intval($get['area_id']);
        }
        if ($get['gift'] == 1) {
            $condition['have_gift'] = 1;
        }
        //拼接排序(销量,浏览量,价格)
        $order = array();
        $order = array('vid' => false,'gid' => false);
        if (in_array($get['key'],array('1','2','3'))) {
            $order = array(str_replace(array('1','2','3'), array('goods_salenum','goods_click','goods_price'), $get['key'])
            => $get['order'] == '1' ? true : false
            );
        }
        // 过滤 批发商品
        $condition['goods_type'] = (isset($get['goods_type']) && intval($get['goods_type']) >0) ? intval($get['goods_type']) : 0;
        //取得商品主键等信息
        $result = $this->getIndexerList($condition,$order,$pagesize);
        if ($result !== false) {
            list($indexer_ids,$indexer_count) = $result;
            //如果全文搜索发生错误，后面会再执行数据库搜索
        } else {
            $indexer_ids = null;
            $indexer_count = 0;
        }
        return array($indexer_ids,$indexer_count);
    }
}