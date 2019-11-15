<?php
/**
 * 手机专题
 *
 *
 */



defined('DYMall') or exit('Access Invalid!');
class cwap_homeCtl extends SystemCtl{
	public function __construct(){
		parent::__construct();
	}
    /**
     * 编辑首页
     */
    public function home_edit() {
        $model_cwap_home = M('ssys_cwap_home');
        $model_goods = Model('goods');
        $home_id = $_GET['id'];
        $home_info_list = $model_cwap_home->getHomeInfoByHomeID($home_id);
        $home_list_new = array();
        $home_list_new['special_id'] = $home_info_list['home_id'];
        $home_list_new['special_desc'] = $home_info_list['home_desc'];
        $home_list_new['sousuocolor'] = $home_info_list['home_sousuo_color'];
        $home_list_new['botnavcolor'] = $home_info_list['home_botnav_color'];
        file_put_contents('cwaphome_edit.txt',$home_info_list['home_data'],FILE_APPEND);
        file_put_contents('cwaphome_edit.txt','\n************'.date('Y-m-d H:i:s',time()).'************\n',FILE_APPEND);
        $use_fixed_search_type = false;

        foreach (unserialize($home_info_list['home_data']) as $key => $val){
            if(isset($val['data']) && !empty($val['data'])){
                foreach ($val['data'] as $i_k => $i_v) {
                    if(isset($i_v['img'])){
                        $i_v['img'] = (strpos($i_v['img'],'http') !==false) ? $i_v['img'] : getMbSpecialImageUrl($i_v['img']);
                        $val['data'][$i_k] = $i_v;
                    }
                }
            }
            if($val['type'] == 'nav'){
                $nav_data = array();
                $nav_data['type'] = $val['type'];
                $nav_data['style_set'] = $val['style_set'];
                $nav_data['icon_set'] = $val['icon_set'];
                $nav_data['slide'] = $val['slide'];
                foreach ( $val['data'] as $nav_k => $nav_v){
                    $nav_data['data'][] = $nav_v;
                }
                $home_list_new['item_data'][] = $nav_data;
            }else if($val['type'] == 'tupianzuhe'){
                $tpzh_data = array();
                $tpzh_data['type'] = $val['type'];
                $tpzh_data['sele_style'] = $val['sele_style'];
                foreach ( $val['data'] as $tpzh_k => $tpzh_v){
                    $tpzh_data['data'][] = $tpzh_v;
                }
                $home_list_new['item_data'][] = $tpzh_data;
            }else if($val['type'] == 'lunbo'){
                $lunbo_data = array();
                $lunbo_data['type'] = $val['type'];
                foreach ( $val['data'] as $lunbo_k => $lunbo_v){
                    $lunbo_data['data'][] = $lunbo_v;
                }
                $home_list_new['item_data'][] = $lunbo_data;
            }else{
                $home_list_new['item_data'][] = $val;
            }
        }
        
        Template::output('list', $home_list_new);

        Template::output('page', $model_cwap_home->showpage(2));
        Template::output('type', 'home');

        Template::output('current_id', $home_info_list['home_id']);
        $this->show_menu('index_edit');
        Template::showpage('cwap_page_edit');
    }


    /**
     * 新增首页  选择模板
     */
    public function template_sele_edit() {
        $model_cwap_home = Model('cwap_home');
        $model_goods = Model('goods');
        $id = $_GET['id'];
        $home_info_list = $model_cwap_home->getTempInfoByID($id);
        $home_list_new = array();
        $home_list_new['special_id'] = $home_info_list['template_id'];
        $home_list_new['special_desc'] = $home_info_list['template_desc'];
        $home_list_new['sousuocolor'] = $home_info_list['sousuo_color'];
        $home_list_new['botnavcolor'] = $home_info_list['botnav_color'];
        foreach (unserialize($home_info_list['template_data']) as $key => $val){
            if(isset($val['data']) && !empty($val['data'])){
                foreach ($val['data'] as $i_k => $i_v) {
                    if(isset($i_v['img'])){
                        $i_v['img'] = (strpos($i_v['img'],'http') !==false) ? $i_v['img'] : getMbSpecialImageUrl($i_v['img']);
                        $val['data'][$i_k] = $i_v;
                    }
                }
            }
            if($val['type'] == 'tuijianshangpin'){
                //推荐商品如果没有商品的话这个数据就在前台展示了
                if(!empty($val['data']['gid'])&&is_array($val['data']['gid'])){
                    //根据gid获取商品的pic  name  price
                    foreach ($val['data']['gid'] as $k => $v){
                        $goods_info = $model_goods -> getGoodsOnlineInfoByID($v,'gid,goods_name,goods_promotion_price,goods_price,goods_image');
                        if(!empty($goods_info)){
                            $goods_info['goods_image'] = thumb($goods_info, 320);
                            $val['data']['goods_info'][] = $goods_info;
                        }
                    }

                    // 获取最终价格
                    $val['data']['goods_info'] = Model('goods_activity')->rebuild_goods_data($val['data']['goods_info']);

                    $home_list_new['item_data'][] = $val;
                }
            }else if($val['type'] == 'nav'){
                $nav_data = array();
                $nav_data['type'] = $val['type'];
                $nav_data['style_set'] = $val['style_set'];
                $nav_data['icon_set'] = $val['icon_set'];
                $nav_data['slide'] = $val['slide'];
                foreach ( $val['data'] as $nav_k => $nav_v){
                    $nav_data['data'][] = $nav_v;
                }
                $home_list_new['item_data'][] = $nav_data;
            }else if($val['type'] == 'dapei'){
                if(!empty($val['data']['gid'])&&is_array($val['data']['gid'])){
                    //根据gid获取商品的pic  name  price
                    foreach ($val['data']['gid'] as $k => $v){
                        $goods_info = $model_goods -> getGoodsOnlineInfoByID($v,'gid,goods_name,goods_promotion_price,goods_price,goods_image');
                        if(!empty($goods_info)){
                            $goods_info['goods_image'] = thumb($goods_info, 320);
                            $val['data']['goods_info'][] = $goods_info;
                        }
                    }
                    
                    // 获取最终价格
                    $val['data']['goods_info'] = Model('goods_activity')->rebuild_goods_data($val['data']['goods_info']);

                    $home_list_new['item_data'][] = $val;
                }else{
                    $home_list_new['item_data'][] = array();
                }
            }else if($val['type'] == 'tupianzuhe'){
                $tpzh_data = array();
                $tpzh_data['type'] = $val['type'];
                $tpzh_data['sele_style'] = $val['sele_style'];
                foreach ( $val['data'] as $tpzh_k => $tpzh_v){
                    $tpzh_data['data'][] = $tpzh_v;
                }
                $home_list_new['item_data'][] = $tpzh_data;
            }else if($val['type'] == 'lunbo'){
                $lunbo_data = array();
                $lunbo_data['type'] = $val['type'];
                foreach ( $val['data'] as $lunbo_k => $lunbo_v){
                    $lunbo_data['data'][] = $lunbo_v;
                }
                $home_list_new['item_data'][] = $lunbo_data;
            }else{
                $home_list_new['item_data'][] = $val;
            }
        }
        Template::output('list', $home_list_new);

        Template::output('page', $model_cwap_home->showpage(2));
        Template::output('type', 'home');

        Template::output('current_id', $id);
        $this->show_menu('index_edit');
        Template::showpage('cwap_page_edit');
    }



    /**
     * 手机页面自定义页面内容保存_17.10.07
     */
    public function zdy_save() {
        $model_mb_special = Model('mb_special');
        $data = array();
        $data['special_id'] = $_POST['special_id']?$_POST['special_id']:0;
        $data['item_data'] = serialize($_POST['item_data']);
        $result = $model_mb_special -> addMbSpecialItem_new($data);
        if($result) {
            $this->log('添加或更新手机专题' . '[ID:' . $result. ']', 1);
            showMsg(L('保存成功'), urlAdmin('mb_special', 'index_edit'));
        } else {
            $this->log('添加或更新手机专题' . '[ID:' . $result. ']', 0);
            showMsg(L('保存失败'), urlAdmin('mb_special', 'index_edit'));
        }
    }

    /**
     * 首页列表
     */
    public function home_list() {
        $model_cwap_home = Model('cwap_home');

        $cwap_home_list = $model_cwap_home->getWapHomeList($array, 10);

        Template::output('list', $cwap_home_list);
        Template::output('page', $model_cwap_home->showpage(2));

        $this->show_menu('home_list');
        Template::showpage('cwap_home_list');
    }

    /**
     * 商城内容手机模板-2017.10.21
     */
    public function template_list() {
        $model_cwap_home = Model('cwap_home');
        $condition = array();
        $condition['template_type'] = $_GET['type'];
        $cwap_template_list = $model_cwap_home->getWapTemplateList($condition, 10);
        Template::output('list', $cwap_template_list);
        Template::output('page', $model_cwap_home->showpage(null,'show_total'));//自己新建了一个分页模板
        Template::showpage('cwap_template_list', 'null_layout');
    }

    /**
     * 编辑首页描述
     */
    public function update_home_desc() {
        $model_cwap_home = Model('cwap_home');

        $param = array();
        $param['home_desc'] = $_GET['value'];
        $result = $model_cwap_home->editCwapHome($param, $_GET['id']);

        $data = array();
        if($result) {
            $this->log('保存手机首页' . '[ID:' . $result. ']', 1);
            $data['result'] = true;
        } else {
            $this->log('保存手机首页' . '[ID:' . $result. ']', 0);
            $data['result'] = false;
            $data['message'] = '保存失败';
        }
        echo json_encode($data);die;
    }
    /**
     * 设为首页功能
     */
    public function setting_home() {
        $model_cwap = Model('cwap_home');
        $result = $model_cwap->setCwapHome($_GET['home_id']);
        $data = array();
        if($result) {
            $this->log('设置手机首页' . '[ID:' . $result. ']', 1);
            $data['result'] = true;
            $data['message'] = '设置成功';
        } else {
            $this->log('设置手机首页' . '[ID:' . $result. ']', 0);
            $data['result'] = false;
            $data['message'] = '设置失败，请稍后重试';
        }
        echo json_encode($data);die;
    }
    /**
     * 删除首页
     */
    public function home_del() {
        $model_cwap_home = Model('cwap_home');

        $result = $model_cwap_home->delCwapHomeByID($_POST['home_id']);

        if($result) {
            $this->log('删除手机首页' . '[ID:' . $_POST['home_id'] . ']', 1);
            showMsg(L('删除成功'), urlAdmin('mb_special', 'home_list'));
        } else {
            $this->log('删除手机首页' . '[ID:' . $_POST['home_id'] . ']', 0);
            showMsg(L('删除失败'), urlAdmin('mb_special', 'home_list'));
        }
    }

    /**
     * 页面内导航菜单
     * @param string 	$menu_key	当前导航的menu_key
     * @param array 	$array		附加菜单
     * @return
     */
    private function show_menu($menu_key='') {
        $menu_array = array();
        if($menu_key == 'index_edit') {
            $menu_array[] = array('menu_key'=>'index_edit', 'menu_name'=>'编辑', 'menu_url'=>'javascript:;');
        } else if($menu_key == 'home_list'){
            $menu_array[] = array('menu_key'=>'home_list', 'menu_name'=>'首页', 'menu_url'=>'javascript:;');
        }else {
            $menu_array[] = array('menu_key'=>'special_list','menu_name'=>'专题列表', 'menu_url'=>urlAdmin('mb_special', 'special_list'));
        }
        if($menu_key == 'special_item_list') {
            $menu_array[] = array('menu_key'=>'special_item_list', 'menu_name'=>'编辑专题', 'menu_url'=>'javascript:;');
        }
        if($menu_key == 'index_edit') {
            Template::output('item_title', '首页编辑');
        } else {
            Template::output('item_title', '专题设置');
        }
        Template::output('menu', $menu_array);
        Template::output('menu_key', $menu_key);
    }
}

