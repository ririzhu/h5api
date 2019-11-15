<?php
/**
 * 手机专题
 *
 *
 */



defined('DYMall') or exit('Access Invalid!');
class cwap_topicCtl extends SystemCtl{
	public function __construct(){
		parent::__construct();
	}
    /**
     * 手机页面自定义页面内容保存_17.10.07
     */
    public function zdy_save() {

        $model_cwap_topic = Model('cwap_topic');
        $model_cwap_home = M('points_cwap_home');
        $type = $_POST['page_type'];
        $data = array();
        file_put_contents('cwaphome.txt',serialize($_POST['item_data']),FILE_APPEND);
        file_put_contents('cwaphome.txt','\n************'.date('Y-m-d H:i:s',time()).'************\n',FILE_APPEND);
        if($type == 'home'){
            $data['home_id'] = $_POST['id'];
            $data['home_data'] = serialize($_POST['item_data']);
            $data['home_desc'] = $_POST['page_title'];
            $data['home_sousuo_color'] = $_POST['sousuocolor'];
            $data['home_botnav_color'] = $_POST['botnavcolor'];
            $result = $model_cwap_home -> editCwapHome_index($data);
            if($result) {

                $this->log('编辑积分商城首页' . '[ID:' . $_POST['id']. ']', 1);
                showMsg(L('保存成功'),'');
            } else {

                $this->log('编辑积分商城首页' . '[ID:' . $_POST['id']. ']', 0);
                showMsg(L('保存失败'),'');
            }
        }else if($type == 'topic'){
            $data['topic_id'] = $_POST['id'];
            $data['topic_data'] = serialize($_POST['item_data']);
            $data['topic_desc'] = $_POST['page_title'];
            $result = $model_cwap_topic -> editCwapTopic_index($data);
            if($result) {
                $this->log('编辑手机专题页' . '[ID:' . $_POST['id']. ']', 1);
                showMsg(L('保存成功'),'');
            } else {
                $this->log('编辑手机专题页' . '[ID:' . $_POST['id']. ']', 0);
                showMsg(L('保存失败'),'');
            }
        }else if($type == 'add_home'){
            //新建首页
            $data['home_data'] = serialize($_POST['item_data']);
            $data['home_state'] = 1;
            //根据模板id获取模板描述
            $template_info = $model_cwap_home->getTempInfoByID($_POST['id']);
            $data['home_desc'] = $_POST['page_title'];
            $data['home_sousuo_color'] = $_POST['sousuocolor'];
            $data['home_botnav_color'] = $_POST['botnavcolor'];
            $result = $model_cwap_home -> addHome($data);
            if($result) {
                $this->log('新增手机首页' . '[ID:' . $result. ']', 1);
                showMsg(L('保存成功'),'');
            } else {
                $this->log('新增手机首页', 0);
                showMsg(L('保存失败'),'');
            }
        }else if($type == 'add_topic'){
            //新建专题页
            $data['topic_data'] = serialize($_POST['item_data']);
            $data['topic_desc'] = $_POST['page_title'];
            $result = $model_cwap_topic -> addTopic($data);
            if($result) {
                $this->log('新增手机专题页' . '[ID:' . $result. ']', 1);
                showMsg(L('保存成功'),'');
            } else {
                $this->log('新增手机专题页', 0);
                showMsg(L('保存失败'),'');
            }

        }
    }


    /**
     * 专题列表
     */
    public function topic_list() {
        $model_cwap_topic = Model('cwap_topic');

        $mb_topic_list = $model_cwap_topic->getCwapTopicList($array, 10);

        Template::output('list', $mb_topic_list);
        Template::output('page', $model_cwap_topic->showpage(2));

        $this->show_menu('topic_list');
        Template::showpage('cwap_topic_list');
    }


    /**
     * 编辑专题描述 
     */
    public function update_topic_desc() {
        $model_cwap_topic = Model('cwap_topic');

        $param = array();
        $param['topic_desc'] = $_GET['value'];
        $result = $model_cwap_topic->editCwapTopic($param, $_GET['id']);

        $data = array();
        if($result) {
            $this->log('保存手机专题' . '[ID:' . $_GET['value']. ']', 1);
            $data['result'] = true;
        } else {
            $this->log('保存手机专题' . '[ID:' . $_GET['value']. ']', 0);
            $data['result'] = false;
            $data['message'] = '保存失败';
        }
        echo json_encode($data);die;
    }
    /**
     * 编辑首页描述
     */
    public function update_home_desc() {
        $model_mb_special = Model('cwap_topic');

        $param = array();
        $param['home_desc'] = $_GET['value'];
        $result = $model_mb_special->editCwapHome($param, $_GET['id']);

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
        $model_cwap = Model('cwap_topic');
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
     * 删除专题
     */
    public function topic_del() {
        $model_cwap_topic = Model('cwap_topic');
        $result = $model_cwap_topic->delCwapTopicByID($_POST['topic_id']);
        if($result) {
            $this->log('删除手机专题' . '[ID:' . $_POST['topic_id'] . ']', 1);
            showMsg(L('删除成功'), urlAdmin('cwap_topic', 'topic_list'));
        } else {
            $this->log('删除手机专题' . '[ID:' . $_POST['topic_id'] . ']', 0);
            showMsg(L('删除失败'), urlAdmin('cwap_topic', 'topic_list'));
        }
    }

    /**
     * 编辑专题页
     */
    public function topic_edit() {
        $model_cwap_topic = Model('cwap_topic');
        $model_goods = Model('goods');
        $special_topic_list = $model_cwap_topic->getTopicInfoByTopicID($_GET['id']);
        $special_cwap_topic_new = array();
        $special_cwap_topic_new['special_id'] = $special_topic_list['topic_id'];
        $special_cwap_topic_new['special_desc'] = $special_topic_list['topic_desc'];
        foreach (unserialize($special_topic_list['topic_data']) as $key => $val){
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

                    $special_cwap_topic_new['item_data'][] = $val;
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
                $special_cwap_topic_new['item_data'][] = $nav_data;
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

                    $special_cwap_topic_new['item_data'][] = $val;
                }else{
                    $special_cwap_topic_new['item_data'][] = array();
                }
            }else if($val['type'] == 'tupianzuhe'){
                $tpzh_data = array();
                $tpzh_data['type'] = $val['type'];
                $tpzh_data['sele_style'] = $val['sele_style'];
                foreach ( $val['data'] as $tpzh_k => $tpzh_v){
                    $tpzh_data['data'][] = $tpzh_v;
                }
                $special_cwap_topic_new['item_data'][] = $tpzh_data;
            }else if($val['type'] == 'lunbo'){
                $lunbo_data = array();
                $lunbo_data['type'] = $val['type'];
                foreach ( $val['data'] as $lunbo_k => $lunbo_v){
                    $lunbo_data['data'][] = $lunbo_v;
                }
                $special_cwap_topic_new['item_data'][] = $lunbo_data;
            }else{
                $special_cwap_topic_new['item_data'][] = $val;
            }
        }

        // 商品检索 活动类型 列表
        $searchExtendFields = array();
        $searchExtendFields[0] = "选择参与活动类型";
        $searchExtendFields[4] = "手机专享";
        $is_allow_pin = Model()->table('addons')->where(array('sld_key' => 'pin'))->find();
        if ($is_allow_pin) {
            $searchExtendFields[1] = "拼团";
        }
        if (C('tuan_allow')) {
            $searchExtendFields[2] = "团购";
        }
        if (C('promotion_allow')) {
            $searchExtendFields[3] = "限时折扣";
        }
        Template::output('searchExtendFields', $searchExtendFields);

        Template::output('list', $special_cwap_topic_new);

        Template::output('page', $model_cwap_topic->showpage(2));

        Template::output('current_id', $_GET['id']);

        $this->show_menu('index_edit');
        Template::showpage('cwap_page_edit');
    }


    /**
     * 专题项目添加
     */
    public function special_item_add() {
        $model_mb_special = Model('cwap_topic');
        $param = array();
        $param['special_id'] = $_POST['special_id'];
        $param['item_type'] = $_POST['item_type'];
        $pre_item_id = empty($_POST['pre_item_id'])?0:$_POST['pre_item_id'];
        //广告只能添加一个
        if($param['item_type'] == 'adv_list') {
            $result = $model_mb_special->isMbSpecialItemExist($param);
            if($result) {
                echo json_encode(array('error' => '广告条板块只能添加一个'));die;
            }
        }
        $item_info = $model_mb_special->addMbSpecialItem($param,$pre_item_id);
        if($item_info) {
            echo json_encode($item_info);die;
        } else {
            echo json_encode(array('error' => '添加失败'));die;
        }
    }

    /**
     * 专题项目删除
     */
    public function special_item_del() {
        $model_mb_special = Model('cwap_topic');

        $condition = array();
        $condition['item_id'] = $_POST['item_id'];

        $result = $model_mb_special->delMbSpecialItem($condition, $_POST['special_id']);
        if($result) {
            echo json_encode(array('message' => '删除成功'));die;
        } else {
            echo json_encode(array('error' => '删除失败'));die;
        }
    }

    /**
     * 专题项目编辑
     */
    public function special_item_edit() {
        $model_mb_special = Model('cwap_topic');

        $item_info = $model_mb_special->getMbSpecialItemInfoByID($_GET['item_id']);
        Template::output('item_info', $item_info);

        if($item_info['special_id'] == 0) {
            $this->show_menu('index_edit');
        } else {
            $this->show_menu('special_item_list');
        }
        Template::showpage('mb_special_item.edit');
    }

    /**
     * 专题项目保存
     */
    public function special_item_save() {
        $model_mb_special = Model('cwap_topic');
        $result = $model_mb_special->editMbSpecialItemByID(array('item_data' => $_POST['item_data']), $_POST['item_id'], $_POST['special_id']);
        if($result) {
            if($_POST['special_id'] == $model_mb_special::INDEX_SPECIAL_ID) {
                showMsg(L('保存成功'), urlAdmin('mb_special', 'index_edit'));
            } else {
                showMsg(L('保存成功'), urlAdmin('mb_special', 'special_edit', array('special_id' => $_POST['special_id'])));
            }
        } else {
            showMsg(L('保存成功'), '');
        }
    }

    /**
     * 图片上传
     */
    public function topic_image_upload() {
        $data = array();
        if(!empty($_FILES['special_image']['name'])) {
            $prefix = 's' . time();
            $upload	= new UploadFile();
            $upload->set('default_dir', ATTACH_MOBILE . DS . 'special' . DS . $prefix);
            $upload->set('fprefix', $prefix);
            $upload->set('allow_type', array('gif', 'jpg', 'jpeg', 'png'));

            $result = $upload->upfile('special_image');
            if(!$result) {
                $data['error'] = $upload->error;
            }
            $data['image_name'] = $upload->file_name;
            $data['image_url'] = getMbSpecialImageUrl($data['image_name']);
        }
        echo json_encode($data);
    }

    /**
     * 商品列表
     */
    public function goods_list() {
        $model_goods = Model('goods');
        if($_POST['keyword']=='限时折扣'){
            $model_xianshi=Model('p_xianshi_goods');
//            $condition, $page=null, $order='', $field='*', $limit = 0
            $time=time();
            $condition['state']='1';
            $condition['start_time']=array('lt',$time);
            $condition['end_time']=array('gt',$time);
            $condition['goods_type'] = 0;
            $goods_list=$model_xianshi->getXianshiGoodsList($condition,0,'','*',0);
            foreach($goods_list as $k=>$v){
                $goods_list[$k]['goods_promotion_price']=$goods_list[$k]['xianshi_price'];
            }
        }else{
            $condition = array();
            $condition['goods_name'] = array('like', '%' . $_POST['keyword'] . '%');
            $condition['goods_type'] = 0;
            $goods_list = $model_goods->getGoodsOnlineList($condition, 'gid,goods_name,goods_promotion_price,goods_price,goods_image', 10);
        }
        Template::output('goods_list', $goods_list);
        Template::output('show_page', $model_goods->showpage(null,'show_total'));//自己新建了一个分页模板
        Template::showpage('mb_special_widget.goods', 'null_layout');
    }
    /**
     * 商品列表_手机端自定义用
     */
    public function goods_list_zdy() {

        $model_goods = Model('pointprod');
//        $model_goods = Model('goods');

        $page = 10;

        $condition = array();
        if(isset($_POST['keyword']) && !empty($_POST['keyword'])){
            $condition['pgoods_name'] = array('like', '%' . $_POST['keyword'] . '%');
        }
        $condition['pgoods_show'] = 1;
        $condition['pgoods_state'] = 0;
        ;
        $goods_list = $model_goods->cwap_GetPointsGoodsList($condition, 'pgid,pgoods_name,pgoods_price,pgoods_points,pgoods_image', $page,'pgoods_sort asc');
        array_walk($goods_list,function(&$v){
            $v['pgoods_image'] = pointprodThumb($v['pgoods_image']);
        });
        // 获取最终价格
//        $goods_list = Model('goods_activity')->rebuild_goods_data($goods_list);
//        dd($goods_list);
        Template::output('goods_list', $goods_list);
        Template::output('show_page', $model_goods->showpage(null,'show_total'));//自己新建了一个分页模板
        Template::showpage('mb_special_widget_zdy', 'null_layout');
    }

    /**
     * 更新项目排序
     */
    public function update_item_sort() {
        $item_id_string = $_POST['item_id_string'];
        $special_id = $_POST['special_id'];
        if(!empty($item_id_string)) {
            $model_mb_special = Model('cwap_topic');
            $item_id_array = explode(',', $item_id_string);
            $index = 0;
            foreach ($item_id_array as $item_id) {
                $result = $model_mb_special->editMbSpecialItemByID(array('item_sort' => $index), $item_id, $special_id);
                $index++;
            }
        }
        $data = array();
        $data['message'] = '操作成功';
        echo json_encode($data);
    }

    /**
     * 更新项目启用状态
     */
    public function update_item_usable() {
        $model_mb_special = Model('cwap_topic');
        $result = $model_mb_special->editMbSpecialItemUsableByID($_POST['usable'], $_POST['item_id'], $_POST['special_id']);
        $data = array();
        if($result) {
            $data['message'] = '操作成功';
        } else {
            $data['error'] = '操作失败';
        }
        echo json_encode($data);
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
            $menu_array[] = array('menu_key'=>'topic_list','menu_name'=>'专题列表', 'menu_url'=>'javascript:;');
        }
        if($menu_key == 'special_item_list') {
            $menu_array[] = array('menu_key'=>'topic_edit_info', 'menu_name'=>'编辑专题', 'menu_url'=>'javascript:;');
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

