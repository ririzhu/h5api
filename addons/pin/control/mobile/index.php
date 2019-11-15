<?php
/**
 * WAP首页
 *
 */


defined('DYMall') or exit('Access Invalid!');
class indexCtl extends mobileHomeCtl{

    public function __construct() {
        parent::__construct();
    }

    /**
     * 首页
     */
    public function index() {
        $types= M('pin')->getPinTypes(array('sld_parent_id'=>0,'sld_status'=>1));
        $data_new['types'] = $types;
        output_data($data_new);
    }
    public function data(){
        $rows=10;
        $tid = $_GET['tid']?intval($_GET['tid']):0;
        if($tid){
            $where['sld_type'] = $tid;
        }
        $where['sld_start_time'] = array('lt',TIMESTAMP);
        $where['sld_end_time'] = array('gt',TIMESTAMP);
        $where['sld_status'] = 1;
        $md=M('pin');
        $goods=$md->table('pin,goods,pin_goods')
            ->field('pin.*,goods.gid,goods.goods_name,goods.goods_price,pin_goods.sld_pin_price,(select count(*) from bbc_order where pin_id = pin.id and bbc_order.order_state>2) as sales')
            ->join('left')
            ->on('pin.sld_goods_id=goods.goods_commonid,pin.id=pin_goods.sld_pin_id')
            ->where($where)
            ->group('pin.id')
            ->page($rows)
            ->select();
        $page_count = $md->gettotalpage();

        foreach ($goods as $k=>$v){
            $goods[$k]['sheng'] = $v['goods_price']- $v['sld_pin_price'];
            $goods[$k]['sld_pin_price'] = floatval($v['sld_pin_price']);
            $goods[$k]['sld_pic'] = gthumb($v['sld_pic'],'max');
        }

        output_data(array('goods' => $goods), mobile_page($page_count));
    }
    /**
     * 首页(用于微信小程序)
     */
    public function index_xcx() {
        $model_mb_special = Model('cwap_home');
        $model_goods = Model('goods');
        //获取首页数据
        $data = $model_mb_special->getCwapHomeInfo();
        $data =unserialize($data['home_data']);
        //对数据重新排序
        $data_new = array();
//        print_r($data);die;
        $new_data = array();
        foreach ($data as $k => $v){
            if(isset($v['data']) && !empty($v['data'])){
                foreach ($v['data'] as $i_k => $i_v) {
                    if(isset($i_v['img'])){
                        $i_v['img'] = (strpos($i_v['img'],'http') !==false) ? $i_v['img'] : getMbSpecialImageUrl($i_v['img']);
                        $v['data'][$i_k] = $i_v;
                    }
                }
            }
            if($v['type'] == 'fuwenben'){
                $v['text'] = htmlspecialchars_decode($v['text']);
                $data_new[] = $v;
            }else if($v['type'] == 'tuijianshangpin') {
                //推荐商品模块如果没有添加商品就保存的话就直接不显示了
                if (!empty($v['data']['gid']) && is_array($v['data']['gid'])) {
                    foreach ($v['data']['gid'] as $key => $val) {
                        $goods_info = $model_goods->getGoodsOnlineInfoByID($val, 'gid,goods_name,goods_promotion_price,goods_price,goods_image');
                        if (!empty($goods_info)) {
                            $goods_info['goods_image'] = thumb($goods_info, 310);
                            $v['data']['goods_info'][] = $goods_info;
                        }
                    }

                    // 获取最终价格
                    $v['data']['goods_info'] = Model('goods_activity')->rebuild_goods_data($v['data']['goods_info']);

                    $data_new[] = $v;
                }
            }else if($v['type'] == 'dapei') {
                //推荐商品模块如果没有添加商品就保存的话就直接不显示了
                if (!empty($v['data']['gid']) && is_array($v['data']['gid'])) {
                    foreach ($v['data']['gid'] as $key => $val) {
                        $goods_info = $model_goods->getGoodsOnlineInfoByID($val, 'gid,goods_name,goods_promotion_price,goods_price,goods_image');
                        if (!empty($goods_info)) {
                            $goods_info['goods_image'] = thumb($goods_info, 310);
                            $v['data']['goods_info'][] = $goods_info;
                        }
                    }
                    
                    // 获取最终价格
                    $v['data']['goods_info'] = Model('goods_activity')->rebuild_goods_data($v['data']['goods_info']);

                    $data_new[] = $v;
                }
            }else if($v['type'] == 'fzkb'){
//                $v['text'] = round($v['text']/23,2);//把像素转化为rem 用于做适配
                $data_new[] = $v;
            }else if($v['type'] == 'lunbo'){
                $lunbo_data = array();
                foreach ($v['data'] as $lb_k => $lb_v){
                    $lunbo_data[] = $lb_v;
                }
                $v['data'] = $lunbo_data;
                $data_new[] = $v;
            }else{
                $data_new[] = $v;
            }

        }
        $this->_output_special($data_new, $_GET['type']);
    }
    /**
     * 获取首页title和搜索栏颜色
     */
    public function index_title() {
        $model_mb_special = Model('cwap_home');
        //获取首页数据
        $data = $model_mb_special->getCwapHomeInfo();
        //对数据重新排序
        $data_new = array();
        $data_new['title'] = $data['home_desc'];
        $data_new['sousuo_color'] = $data['home_sousuo_color'];
//        print_r($data);die;
        $this->_output_special($data_new, $_GET['type']);
    }
    /**
     * 获取专题页标题
     */
    public function topic_title() {
        $model_mb_special = Model('cwap_topic');
        //获取专题页数据
        $data = $model_mb_special->getTopicInfoByTopicID($_GET['topic_id']);

        //对数据重新排序
        $data_new = array();
        $data_new['title'] = $data['topic_desc'];
        $this->_output_special($data_new, $_GET['type']);
    }


    //获取首页分页的商品数据
    public function getGoodsByPage(){
        $page=$_GET['page'];
        $model_mb_special = Model('mb_special');
        $rows=$_GET['rows'];
        $total=$page*$rows;
        $totals=($page+1)*$rows;
        $data = $model_mb_special->getMbSpecialItemUsableListByID($_GET['special_id']);
        foreach($data as $k=>$v){
            if($k==4){
                $length=count($v['goods']['item']);
                $arr=array();
                if($totals<=$length){
                    //从截取数组长度
                    for($i=$total;$i<$totals;$i++){
                        array_push($arr,$v['goods']['item'][$i]);
                    }
                    $hasmore=1;
                }
                if($total>$length){
                    $hasmore=0;
                }
                if($total<$length && $length<=$totals){
                    $arr=array();
                    for($i=$total;$i<$length;$i++){
                        array_push($arr,$v['goods']['item'][$i]);
                    }
                    $hasmore=0;
                }
            }
        }
        output_data(array('goods_info'=>array_values($arr),'hasmore'=>$hasmore));
    }
    /**
     * 默认搜索词列表
     */
    public function search_key_list() {
        //热门搜索
        $list = @explode(',',C('hot_search'));
        if (!$list || !is_array($list)) {
            $list = array();
        }

        //历史搜索
        if (cookie('his_sh') != '') {
            $his_search_list = explode('~', cookie('his_sh'));
        }

        $data['list'] = $list;
        $data['his_list'] = is_array($his_search_list) ? $his_search_list : array();
        output_data($data);
    }
    /**
     * 默认搜索词列表(用于微信小程序)
     */
    public function search_key_list_xcx() {
        //热门搜索
        $list = @explode(',',C('hot_search'));
        if (!$list || !is_array($list)) {
            $list = array();
        }
        echo json_encode(array('hot_list'=>$list));
    }

    /**
     * 热门搜索列表
     */
    public function search_hot_info() {
        //热门搜索
        if (C('rec_search') != '') {
            $rec_search_list = @unserialize(C('rec_search'));
            $rec_value = array();
            foreach($rec_search_list as $v){
                $rec_value[] = $v['value'];
            }

        }
        output_data(array('hot_info'=>$result ? $rec_value : array()));
    }

    /**
     * 高级搜索
     */
    public function search_adv() {
        $area_list = Model('area')->getAreaList(array('area_deep'=>1),'area_id,area_name');
        if (C('contract_allow') == 1) {
            $contract_list = Model('contract')->getContractItemByCache();
            $_tmp = array();$i = 0;
            foreach ($contract_list as $k => $v) {
                $_tmp[$i]['id'] = $v['cti_id'];
                $_tmp[$i]['name'] = $v['cti_name'];
                $i++;
            }
        }
        output_data(array('area_list'=>$area_list ? $area_list : array(),'contract_list'=>$_tmp));
    }
}
