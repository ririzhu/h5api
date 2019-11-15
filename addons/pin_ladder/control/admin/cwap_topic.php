<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/11/21
 * Time: 11:33
 */
class cwap_topicCtl extends SystemCtl{
    public function __construct(){
        parent::__construct();
    }
    /**
     * 手机页面自定义页面内容保存_17.10.07
     */
    public function zdy_save() {
        $model_cwap_topic = M('cwap_topic','pin_ladder');
        $model_cwap_home = M('cwap_home','pin_ladder');
        $type = $_POST['page_type'];
        $data = array();
//        file_put_contents('cwaphome.txt',serialize($_POST['item_data']),FILE_APPEND);
//        file_put_contents('cwaphome.txt','\n************'.date('Y-m-d H:i:s',time()).'************\n',FILE_APPEND);
        if($type == 'home'){
            $data['home_id'] = $_POST['id'];
            $data['home_data'] = serialize($_POST['item_data']);
            $data['home_desc'] = $_POST['page_title'];
            $data['home_sousuo_color'] = $_POST['sousuocolor'];
            $data['home_botnav_color'] = $_POST['botnavcolor'];
            $result = $model_cwap_home -> editCwapHome_index($data);
            if($result) {
                $this->log('编辑手机首页' . '[ID:' . $_POST['id']. ']', 1);
                showMsg(L('保存成功'),'');
            } else {
                $this->log('编辑手机首页' . '[ID:' . $_POST['id']. ']', 0);
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
            }else if($val['type'] == 'huodong'){
                $huodong_data = array();
                $huodong_data['type'] = $val['type'];
                $huodong_data['sele_style'] = $val['sele_style'];

                $use_fixed_search_type = true;

                switch ($huodong_data['sele_style']) {
                    case '1':
                        // 限时折扣
                        $model_xian = Model('p_xianshi_goods');
                        $xianCondition = array();
                        $xianCondition['state'] = $model_xian::XIANSHI_GOODS_STATE_NORMAL;
                        $xianCondition['start_time'] = array('lt', TIMESTAMP);
                        $xianCondition['end_time'] = array('gt', TIMESTAMP);
                        $xian_goods_list = $model_xian->getXianshiGoodsList($xianCondition);
                        $extend_data_list = array();
                        $goods_ids = array();
                        if (!empty($xian_goods_list)) {

                            foreach ($xian_goods_list as $key => $value) {
                                $goods_ids[] = $value['gid'];
                                $value['sld_end_time'] = date("Y-m-d H:i:s",$value['end_time']);
                                $extend_data_list[$value['gid']] = $value;
                            }
                        }
                        break;
                    case '2':
                        // 团购
                        $model_tuan = Model('tuan');
                        $tuanCondition = array();
                        $tuan_goods_list = $model_tuan->getTuanOnlineList($tuanCondition,'','','gid,tuan_price,virtual_quantity,buy_quantity,tuan_discount,vid');
                        $extend_data_list = array();
                        $goods_ids = array();
                        foreach ($tuan_goods_list as $key => $value) {
                            $value['sld_end_time'] = (strtotime($value['end_time_text']) > time()) ? $value['end_time_text'] : '';
                            $value['buyed_quantity'] = $value['virtual_quantity'] + $value['buy_quantity'];
                            $goods_ids[] = $value['gid'];
                            $extend_data_list[$value['gid']] = $value;
                        }
                        break;

                    default:
                        // 拼团
                        // 获取拼团 类型的商品(bbc_goods) id

                        $allow_search_type = array(1);

                        $model_pin = M('pin');
                        $pinCondition = array();
                        $pin_goods_list = $model_pin->getPinList($pinCondition,0);
                        $extend_data_list = array();
                        $goods_ids = array();
                        foreach ($pin_goods_list as $key => $value) {
                            $goods_ids[] = $value['gid'];
                            $extend_data_list[$value['gid']] = $value;
                        }
                        break;
                }

                if (isset($val['data']) && is_array($val['data']) && !empty($val['data'])) {
                    foreach ( $val['data'] as $huodong_k => $huodong_v){
                        foreach ($huodong_v as $huodong_a_k => $huodong_a_v) {
                            if (is_array($huodong_a_v) && !empty($huodong_a_v)) {
                                foreach ($huodong_a_v as $huodong_b_k => $huodong_b_v) {
                                    if(isset($huodong_b_v['gid'])){
                                        if (is_array($huodong_b_v['gid']) && !empty($huodong_b_v['gid'])) {
                                            foreach ($huodong_b_v['gid'] as $huodong_c_k => $huodong_c_v) {
                                                // 获取 商品信息
                                                $goods_info = $model_goods -> getGoodsOnlineInfoByID($huodong_c_v,'gid,goods_name,goods_promotion_price,goods_marketprice,goods_price,goods_image');
                                                if(!empty($goods_info)){
                                                    $goods_info['goods_image'] = thumb($goods_info, 320);
                                                    $goods_info['extend_data'] = $extend_data_list[$huodong_c_v];
                                                    $huodong_v[$huodong_a_k][$huodong_b_k]['goods_info'][$huodong_c_k] = $goods_info;
                                                }
                                            }
                                        }else{
                                            // 获取 商品信息
                                            $goods_info = $model_goods -> getGoodsOnlineInfoByID($huodong_b_v['gid'],'gid,goods_name,goods_promotion_price,goods_marketprice,goods_price,goods_image');
                                            if(!empty($goods_info)){
                                                $goods_info['goods_image'] = thumb($goods_info, 320);
                                                $goods_info['extend_data'] = $extend_data_list[$huodong_b_v['gid']];
                                                $huodong_v[$huodong_a_k][$huodong_b_k]['goods_info'] = $goods_info;
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        // 获取最终价格
                        $huodong_v[$huodong_a_k][$huodong_b_k]['goods_info'] = Model('goods_activity')->rebuild_goods_data($huodong_v[$huodong_a_k][$huodong_b_k]['goods_info']);

                        $huodong_data['data'][$huodong_k] = $huodong_v;
                    }
                }
                // exit;
                $special_cwap_topic_new['item_data'][] = $huodong_data;
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
        $model_goods = Model('goods');

        $goods_activity_type = $_REQUEST['goods_activity_type'];
        $keyword = $_REQUEST['keyword'];

        $page = 10;

        $condition = array();

        $searchExtendCondition = array();
        switch ($goods_activity_type) {
            case '1':
                // 获取拼团 类型的商品(bbc_goods) id
                $model_pin = M('pin');
                $pinCondition = array();
                $pin_goods_list = $model_pin->getPinList($pinCondition,0);
                $extend_data_list = array();
                $goods_ids = array();
                foreach ($pin_goods_list as $key => $value) {
                    $goods_ids[] = $value['gid'];
                    $extend_data_list[$value['gid']] = $value;

                }
                $searchExtendCondition['gid'] = array("IN",$goods_ids);
                break;
            case '2':
                // 团购
                $model_tuan = Model('tuan');
                $tuanCondition = array();
                $tuan_goods_list = $model_tuan->getTuanOnlineList($tuanCondition,'','','gid,tuan_price,virtual_quantity,buy_quantity,tuan_discount,vid');
                $extend_data_list = array();
                $goods_ids = array();
                foreach ($tuan_goods_list as $key => $value) {
                    $value['sld_end_time'] = (strtotime($value['end_time_text']) > time()) ? $value['end_time_text'] : '';
                    $value['buyed_quantity'] = $value['virtual_quantity'] + $value['buy_quantity'];
                    $goods_ids[] = $value['gid'];
                    $extend_data_list[$value['gid']] = $value;
                }
                $searchExtendCondition['gid'] = array("IN",$goods_ids);
                break;
            case '3':
                // 限时折扣
                $model_xian = Model('p_xianshi_goods');
                $xianCondition = array();
                $xianCondition['state'] = $model_xian::XIANSHI_GOODS_STATE_NORMAL;
                $xianCondition['start_time'] = array('lt', TIMESTAMP);
                $xianCondition['end_time'] = array('gt', TIMESTAMP);
                $xian_goods_list = $model_xian->getXianshiGoodsList($xianCondition);
                $extend_data_list = array();
                $goods_ids = array();
                if (!empty($xian_goods_list)) {

                    foreach ($xian_goods_list as $key => $value) {
                        $goods_ids[] = $value['gid'];
                        $value['sld_end_time'] = date("Y-m-d H:i:s",$value['end_time']);
                        $extend_data_list[$value['gid']] = $value;
                    }
                }
                $searchExtendCondition['gid'] = array("IN",$goods_ids);
                break;
            case '4':
                // 手机专享
                $model_p_mbuy = Model('p_mbuy');
                $p_mbuyCondition = array();
                $p_mbuyCondition['mbuy_state'] = $model_p_mbuy::STATE1;
                $p_mbuyCondition['mbuy_quota_endtime'] = array('gt', TIMESTAMP);
                $p_mbuy_list = $model_p_mbuy->getSoleQuotaList($p_mbuyCondition);
                $vendor_ids = array();
                if (!empty($p_mbuy_list)) {
                    foreach ($p_mbuy_list as $key => $value) {
                        $vendor_ids[] = $value['vid'];
                    }
                }
                // 获取 所有自营店铺ID
                $model_vendor = Model('vendor');
                $vendorCondition = array(
                    'is_own_shop' => 1,
                    'sld_is_supplier' => 0,
                    'store_state' => 1,
                );
                $own_vendor_list = $model_vendor->where($vendorCondition)->select();
                if (!empty($own_vendor_list)) {
                    foreach ($own_vendor_list as $key => $value) {
                        $vendor_ids[] = $value['vid'];
                    }
                }
                $mbuyCondition['vid'] = array("IN",$vendor_ids);
                $mbuyCondition['mbuy_state'] = $model_p_mbuy::STATE1;

                $mbuy_goods_list = $model_p_mbuy->getSoleGoodsList($mbuyCondition,'gid');
                $goods_ids = array();
                foreach ($mbuy_goods_list as $key => $value) {
                    $goods_ids[] = $value['gid'];
                }
                $searchExtendCondition['gid'] = array("IN",$goods_ids);
                break;

            default:
                break;
        }

        if($keyword=='限时折扣'){
            $model_xianshi=Model('p_xianshi_goods');
//            $condition, $page=null, $order='', $field='*', $limit = 0
            $time=time();
            $condition['state']='1';
            $condition['start_time']=array('lt',$time);
            $condition['end_time']=array('gt',$time);
            $condition['goods_type'] = 0;
            $goods_list=$model_xianshi->getXianshiGoodsList($condition,$page,'','*',0);
            foreach($goods_list as $k=>$v){
                $goods_list[$k]['goods_promotion_price']=$goods_list[$k]['xianshi_price'];
            }
        }else{

            //如果绑定的城市分站id存在的话，需要增加城市分站的筛选条件
            $admininfo = $this->admin_info;
            if($admininfo['admin_sld_city_site_id']>0){
                $condition['province_id|city_id|area_id'] = $admininfo['admin_sld_city_site_id'];
            }
            $condition['goods_name'] = array('like', '%' . $keyword . '%');
            $condition['goods_type'] = 0;

            $condition = array_merge($condition,$searchExtendCondition);

            $goods_list = $model_goods->getGoodsOnlineList($condition, 'gid,goods_name,goods_promotion_price,goods_marketprice,goods_price,goods_image', $page);
        }

        // 扩展搜索结果
        switch ($goods_activity_type) {
            case '1':
                if ($goods_list && isset($extend_data_list) && !empty($extend_data_list)) {
                    foreach ($goods_list as $key => $value) {
                        if (isset($extend_data_list[$value['gid']]) && !empty($extend_data_list[$value['gid']])) {
                            $goods_list[$key]['extend_data'] = $extend_data_list[$value['gid']];
                        }
                    }
                }
                break;
            case '2':
                if ($goods_list && isset($extend_data_list) && !empty($extend_data_list)) {
                    foreach ($goods_list as $key => $value) {
                        if (isset($extend_data_list[$value['gid']]) && !empty($extend_data_list[$value['gid']])) {
                            $goods_list[$key]['extend_data'] = $extend_data_list[$value['gid']];
                        }
                    }
                }
                break;
            case '3':
                if ($goods_list && isset($extend_data_list) && !empty($extend_data_list)) {
                    foreach ($goods_list as $key => $value) {
                        if (isset($extend_data_list[$value['gid']]) && !empty($extend_data_list[$value['gid']])) {
                            $goods_list[$key]['extend_data'] = $extend_data_list[$value['gid']];
                        }
                    }
                }
                break;
            case '4':
                # code...
                break;
            default:
                break;
        }

        // 获取最终价格
        $goods_list = Model('goods_activity')->rebuild_goods_data($goods_list);

        $now_page = $model_goods->wq_pagecmd('obj');

        $now_page->set('page_url','./index.php?app=cwap_topic&mod=goods_list_zdy&goods_activity_type='.$goods_activity_type.'&keyword='.$keyword.'&pn=');

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

