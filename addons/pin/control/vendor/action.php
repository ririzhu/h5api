<?php
/**
 * 商家中心管理
 */
defined('DYMall') or exit('Access Invalid!');

class actionCtl extends BaseSellerCtl {

    public function __construct() {
        parent::__construct();

        //读取语言包
        Language::read('member_tuan');
        //检查团购功能是否开启
        if (!(C('sld_pintuan') && C('pin_isuse')) ){
            vendorMessage('拼团活动没有开启','index.php?app=vendorcenter','','error');
        }
    }

    /**
     * 默认显示团购列表
     **/
    public function index() {
        $this->pin_list();
    }

    /**
     * 列表
     **/
    public function pin_list() {
        $tuan_model = M('pin');

        $condition = array();
        $condition['sld_vid'] = $_SESSION['vid'];
        if($_GET['tuan_state']) { //状态筛选
            if($_GET['tuan_state']==1){
                $condition['sld_start_time'] = array('gt',TIMESTAMP);
            }elseif($_GET['tuan_state']==2){
                $condition['sld_start_time']    = array('lt',TIMESTAMP);
                $condition['sld_end_time']      = array('gt',TIMESTAMP);
                $condition['pin.sld_status'] = 1;
            }elseif($_GET['tuan_state']==3){
                $condition['sld_status'] = array('exp', " sld_start_time < ".TIMESTAMP."  and ( sld_end_time <  ".TIMESTAMP." or pin.sld_status = 0)");
            }
        }
        if($_GET['tuan_name']) {  //产品名称
            $condition['goods_name'] = array('like', '%' . $_GET['tuan_name'] . '%');
        }
        if($_GET['type']) {  //产品分类
            $condition['pin_type.id'] = $_GET['type'];
        }

        $tuan_list = $tuan_model->table('pin,pin_goods,goods_common,pin_type')->join('left')
            ->on('pin.id=pin_goods.sld_pin_id,pin.sld_goods_id=goods_common.goods_commonid,pin.sld_type=pin_type.id')
            ->where($condition)
            ->field('pin.id,pin.sld_type,pin.sld_pic,pin.sld_start_time,pin.sld_end_time,pin.sld_return_leader,pin.sld_max_buy,pin.sld_team_count,pin.sld_success_time,pin.sld_status,goods_common.goods_name,pin_type.sld_typename,pin_goods.sld_gid,(select count(*) from bbc_pin_team where sld_pin_id=pin.id) as zong,(select count(*) from bbc_pin_team left join bbc_pin_team_user on bbc_pin_team.id=bbc_pin_team_user.sld_team_id and bbc_pin_team.sld_leader_id=bbc_pin_team_user.sld_user_id left join bbc_order on bbc_pin_team_user.sld_order_id=bbc_order.order_id where bbc_order.order_state>1 and pin_id=pin.id) as youxiao,(select count(*) from bbc_pin_team where sld_pin_id=pin.id and sld_tuan_status=1) as cheng')
            ->page(20)
            ->select();

        foreach ($tuan_list as $k=>$tuan_info){
            $tuan_list[$k]['start_time_text'] = str_replace(' ','<br>',date('Y-m-d H:i', $tuan_info['sld_start_time']));
            $tuan_list[$k]['end_time_text'] = str_replace(' ','<br>',date('Y-m-d H:i', $tuan_info['sld_end_time']));
            if ($tuan_info['sld_start_time'] < TIMESTAMP && $tuan_info['sld_end_time'] > TIMESTAMP) {
                if($tuan_info['sld_status']==0) {
                    $tuan_list[$k]['tuan_state_text'] = '已结束';
                }else{
                    $tuan_list[$k]['tuan_state_text'] = '进行中';
                }
            } elseif ($tuan_info['sld_end_time'] < TIMESTAMP) {
                $tuan_list[$k]['tuan_state_text'] = '已结束';
            } else {
                $tuan_list[$k]['tuan_state_text'] = '等待开始';
            }
        }

        Template::output('group',$tuan_list);
        Template::output('show_page',$tuan_model->showpage());
        Template::output('tuan_state_array', $tuan_model->getTuanStateArray());

        //拼团的分类
        Template::output('types',$tuan_model->getPinTypes(array('sld_parent_id'=>0)));

        self::profile_menu();
        Template::showpage('pin.list');
    }

    /**
     * 添加拼团页面
     **/
    public function add() {
        $model_pin = M('pin');

        $class_list = $model_pin->getPinTypes(array('sld_parent_id'=>0));
        Template::output('class_list', $class_list);
        if(!$class_list){
            showDialog('没有拼团分类，不能创建拼团', urlAddons('index'));
        }

        if($_GET['id']){
            $pin_id = intval($_GET['id']);
            $condition['pin.id'] = $pin_id;
            $pin_info = $model_pin->table('pin,goods_common,pin_type')->join('left')
                ->on('pin.sld_goods_id=goods_common.goods_commonid,pin.sld_type=pin_type.id')
                ->where($condition)
                ->field('pin.*,goods_common.goods_name,pin_type.sld_parent_id as pid')
                ->find();

            $pin_info['start_time_text'] = date('Y-m-d H:i', $pin_info['sld_start_time']);
            $pin_info['end_time_text'] = date('Y-m-d H:i', $pin_info['sld_end_time']);
            $pin_goods = $model_pin->getGoodsListByPinId($pin_id);
            $muti_goods = $this->goods_info($pin_info['sld_goods_id'],0);
            foreach ($pin_goods as $k=>$v){
                $pin_goods[$k] = array_merge($v,$muti_goods[$k]);
            }
            $pin_info['goods_list'] = $pin_goods;

            Template::output('pin_info', $pin_info);

        }

        if($pin_info){
            $class_list2 = $model_pin->getPinTypes(array('sld_parent_id'=>$pin_info['pid']));
            Template::output('class_list2', $class_list2);
        }

        self::profile_menu();
        Template::showpage('pin.add');

    }

    /**
     * 查看拼团
     **/
    public function view() {
        if($_GET['id']){
            $model_pin = M('pin');

            $class_list = $model_pin->getPinTypes(array('sld_parent_id'=>0));
            Template::output('class_list', $class_list);
            if(!$class_list){
                showDialog('没有拼团分类，不能创建拼团', urlAddons('index'));
            }
            $pin_id = intval($_GET['id']);
            $condition['pin.id'] = $pin_id;
            $pin_info = $model_pin->table('pin,goods_common,pin_type')->join('left')
                ->on('pin.sld_goods_id=goods_common.goods_commonid,pin.sld_type=pin_type.id')
                ->where($condition)
                ->field('pin.*,goods_common.goods_name,pin_type.sld_parent_id as pid')
                ->find();

            $pin_info['start_time_text'] = date('Y-m-d H:i', $pin_info['sld_start_time']);
            $pin_info['end_time_text'] = date('Y-m-d H:i', $pin_info['sld_end_time']);
            $pin_goods = $model_pin->getGoodsListByPinId($pin_id);
            $muti_goods = $this->goods_info($pin_info['sld_goods_id'],0);
            foreach ($pin_goods as $k=>$v){
                $pin_goods[$k] = array_merge($v,$muti_goods[$k]);
            }
            $pin_info['goods_list'] = $pin_goods;

            Template::output('pin_info', $pin_info);

        }else{
            showDialog('参数错误');
        }

        if($pin_info){
            $class_list2 = $model_pin->getPinTypes(array('sld_parent_id'=>$pin_info['pid']));
            Template::output('class_list2', $class_list2);
        }

        self::profile_menu();
        Template::showpage('pin.view');

    }

    //团队列表
    public function team_list(){
        $tuan_model = M('pin');
        $pin_id = $_GET['id'];
        $pin_info = $tuan_model->getTuanInfo(array('id'=>$pin_id));

        $condition = array();
        $condition['sld_pin_id'] = $pin_id;
        if($_GET['tuan_state']!=null && $_GET['tuan_state']!='') { //如果状态不为空
            $condition['sld_tuan_status'] = $_GET['tuan_state'];
        }
        $condition['sld_pin_id'] = $pin_id;
        $condition['order.order_state'] = array('gt',1);
        $teams = $tuan_model->table('pin_team,pin_team_user,member,order')->join('left')
            ->on('pin_team.id=pin_team_user.sld_team_id,pin_team.sld_leader_id=member.member_id,pin_team_user.sld_order_id=order.order_id')
            ->where($condition)
            ->group('pin_team.id')
            ->field('pin_team.id,sld_tuan_status,pin_team_user.sld_add_time,count(pin_team_user.id) as ren,member_name')
            ->page(10)
            ->order('sld_tuan_status = 0 desc,sld_add_time desc')
            ->select();

        $ids = array();
        foreach ($teams as $v){
            if(!in_array($v['id'],$ids)){
                $ids[]=$v['id'];
            }
        }

        $condition['sld_team_id'] = array('in',$ids);
        $user_list = $tuan_model->table('pin_team,pin_team_user,member,order')->join('left')
            ->on('pin_team.id=pin_team_user.sld_team_id,pin_team_user.sld_user_id=member.member_id,pin_team_user.sld_order_id=order.order_id')
            ->where($condition)
            ->field('pin_team.id,sld_team_id,member_name,member_id,pin_team_user.sld_add_time,member_avatar')
            ->order('sld_add_time asc')
            ->select();
        foreach ($user_list as $k=>$v){
            $v['time'] = date('m-d H:i',$v['sld_add_time']);
            $v['member_avatar'] = $v['member_avatar']?UPLOAD_SITE_URL.DS.ATTACH_AVATAR.DS.$v['member_avatar']:getCimg('default_user_portrait');
            $new[$v['id']][] = $v;
        }
        foreach ($teams as $k=>$tuan_info){
            $teams[$k]['users'] = $new[$tuan_info['id']];
            $teams[$k]['start_time_text'] = str_replace(' ','<br>',date('Y-m-d H:i', $tuan_info['sld_add_time']));
            if($tuan_info['sld_tuan_status']==1){
                $teams[$k]['state'] = '已成功';
            }elseif($tuan_info['sld_tuan_status']==2){
                $teams[$k]['state'] = '已失败';
            }else{
                $teams[$k]['state'] = '进行中';
            }
            if($tuan_info['sld_start_time'] < TIMESTAMP && $tuan_info['sld_end_time'] > TIMESTAMP) {
                $teams[$k]['tuan_state_text'] = '进行中';
            } elseif ($tuan_info['sld_end_time'] < TIMESTAMP) {
                $teams[$k]['tuan_state_text'] = '已结束';
            } else {
                $teams[$k]['tuan_state_text'] = '等待开始';
            }
            if($tuan_info['sld_tuan_status']==0) {
                $teams[$k]['sheng'] = formatDateTime($tuan_info['sld_add_time'] + $pin_info['sld_success_time'] * 3600);
            }else{
                $teams[$k]['sheng'] = '-';
            }
        }


        Template::output('group',$teams);
        Template::output('show_page',$tuan_model->showpage());
        Template::output('tuan_state_array', $tuan_model->getTuanStateArray());

        self::profile_menu();
        Template::showpage('pin.team_list');
    }

    //手动成团
    public function setSuccess(){
        $id=intval($_GET['id']);
        $tuan_model = M('pin');
        $re = $tuan_model->table('pin_team')->where(array('id'=>$id))->update(array('sld_tuan_status'=>1));


        //成团提醒
        $where['pin_team.id'] = $id;
        $sheng = $tuan_model->table('pin_team,pin,pin_team_user,member,goods,pin_goods,order')
            ->join('left')
            ->field('member.*,pin.sld_success_time,pin_goods.sld_gid,sld_team_id,pin.sld_team_count,pin_team.sld_add_time,pin.sld_team_count-(select count(*) from bbc_pin_team_user p left join bbc_order o on p.sld_order_id=o.order_id where p.sld_team_id = pin_team.id and o.order_state >2 and o.buyer_id <> pin_team.sld_leader_id ) as sheng,pin.sld_return_leader,sld_pin_price,sld_leader_id,sld_fanli,goods.goods_name,pin_team_user.id as ltid,order.order_sn')
            ->on('pin_team.sld_pin_id=pin.id, pin_team.id=pin_team_user.sld_team_id, pin_team.sld_leader_id=member.member_id,goods.gid=pin_team_user.sld_gid,pin_goods.sld_gid=pin_team_user.sld_gid,pin_team_user.sld_order_id=order.order_id')
            ->where($where)
            ->order('pin_team_user.id asc')
            ->find();


        $sheng['sheng'] = 1;

        $members  = M('pin')->table('pin_team_user,order,member,goods,pin_goods')->where(array('sld_team_id'=>$id,'order_state'=>'20'))->join('left')
            ->on('pin_team_user.sld_order_id=order.order_id,order.buyer_id=member.member_id,pin_team_user.sld_gid=goods.gid,pin_team_user.sld_gid=pin_goods.sld_gid')
            ->field('order.order_id,member.member_id,goods.goods_name,pin_goods.sld_pin_price,order.order_sn')
            ->select();

        M('pin')->full_team_user($members,$sheng);


        if($re){
            $this->recordSellerLog('手动成团：'.$id);
            showDialog('成团成功', urlAddons('team_list').'&id='.$_GET['pin_id'], 'succ');
        }else{
            showDialog('操作失败');
        }
    }


    public function edit() {
        $this->add();
    }

    /*ajax获取分类*/
    public function getTypes(){
        $parent_id = $_GET['parent_id']?$_GET['parent_id']:0;
        $class_list = M('pin')->getPinTypes(array('sld_parent_id'=>$parent_id));
        exit(json_encode($class_list));
    }

    //删除活动
    public function delete(){
        $id=intval($_GET['id']);
        $tuan_model = M('pin');
        $pin_info = $tuan_model->getTuanInfoByID($id);
        if(!$pin_info){
            showDialog('活动不存在，请刷新');
        }
        if($pin_info['sld_vid']!=$_SESSION['vid']){
            showDialog('不是你的活动');
        }
        if($pin_info['sld_start_time'] < TIMESTAMP && $pin_info['sld_end_time'] > TIMESTAMP && $pin_info['sld_status'] == 1){
            showDialog('活动正在进行中不能删除');
        }
        $re = $tuan_model->table('pin_goods')->where(array('sld_pin_id'=>$id))->delete();
        $re = $tuan_model->table('pin')->where(array('id'=>$id))->delete();
        if($re){
            $this->recordSellerLog('删除拼团活动：'.$id);
            showDialog('删除成功', urlAddons('index'), 'succ');
        }else{
            showDialog('删除失败');
        }
    }

    /**
     * 拼团保存
     **/
    public function save() {
        $id= intval($_POST['id']);

        $repeat = $this->check_goods_repeat($_POST['tuan_goods_id'],0,$id);
        if(!$repeat){
            showDialog('活动已开始，不能编辑');
        }

        if($id) {
            if ($_POST['start_time'] > TIMESTAMP) {
                $zong = M('pin')->table('pin_team')->where(array('sld_pin_id'=>$id))->count();
                if($zong>0) {
                    showDialog('活动有人参与了，不能编辑', urlAddons('view') . '&id=' . $id);
                }
            }
        }

        //获取提交的数据
        $gid = $_POST['gid'];

        $have_activity = Model('goods')->getOtherActivity($gid,$_POST['tuan_goods_id'],strtotime($_POST['start_time']),strtotime($_POST['end_time']),pin);

        if($have_activity>0){
            showDialog('该商品同已经参加其他优惠活动了！', urlAddons('view') . '&id=' . $id);
        }
        //组装产品数据
        $goods = array();

        $where_old['gid'] = array('in',$gid);
        $db = Model('goods');
        $old_goods = $db->table('goods')->where($where_old)->key('gid')->field('gid,goods_price')->select();

        foreach ($gid as $k=>$v){
            $sstr = "第".($k+1)."个产品";
            if($_POST['gid'][$k]<1){
                showDialog($sstr.Language::get('参数错误'));
            }
            if($old_goods[intval($_POST['gid'][$k])]['goods_price']<=$_POST['sld_pin_price'][$k]){
                showDialog($sstr.'价格并没有优惠');
            }
            if(floatval($_POST['sld_pin_price'][$k])==0){
                showDialog($sstr.'拼团价格设置错误！');
            }
            $tmp['sld_gid']                 = intval($_POST['gid'][$k]);                //gid
            $tmp['sld_pin_price']       = floatval($_POST['sld_pin_price'][$k]);    //拼价
            $tmp['sld_stock']           = intval($_POST['sld_stock'][$k]);          //库存
            $goods[] = $tmp;
        }

        $tuan_model = M('pin');
        $model_goods = Model('goods');

        //开始存pin表
        $param = array();
        $param['sld_goods_id']  = $_POST['tuan_goods_id'];
        $param['sld_vid']       = $_SESSION['vid'];
        $param['sld_type']      = intval($_POST['class_id']);
        $param['sld_pic']      = $_POST['tuan_image'];
        $param['sld_start_time'] = strtotime($_POST['start_time']);
        $param['sld_end_time'] = strtotime($_POST['end_time']);
        $param['sld_return_leader'] = floatval($_POST['sld_return_leader']);
        $param['sld_max_buy'] = intval($_POST['sld_max_buy']);
        $param['sld_team_count'] = intval($_POST['sld_team_count']);
        $param['sld_success_time'] = floatval($_POST['sld_success_time']);
        $param['sld_status'] = intval($_POST['sld_status']);

        //检查时间冲突
        $where['sld_status'] = 1;
        $where['sld_start_time'] = array('exp', "(sld_start_time <= ".$param['sld_start_time']." or sld_end_time >=".$param['sld_end_time']." )");
        $where['sld_goods_id'] = $param['sld_goods_id'];
        $repeat_time = M('pin')->table('pin,pin_goods')->join('left')
            ->on('pin.id=pin_goods.sld_pin_id')
            ->field('pin.id')
            ->where($where)
            ->select();
        if(count($repeat_time)>0){
            foreach ($repeat_time as $v){
                $rep_ids[]=$v['id'];
            }
            $rep_ids=join('、',$rep_ids);
            showDialog('所选产品与活动'.$rep_ids.'时间有冲突');
        }

        if($id>0){
            unset($param['sld_vid']);
            $re=$tuan_model->editTuan($param,array('id'=>$id,'sld_vid'=>$_SESSION['vid']));
            if ($re) {
                $tuan_model->table('pin_goods')->where(array('sld_pin_id'=>$id))->delete();
                foreach ($goods as $k => $v) {
                    $goods[$k]['sld_pin_id'] = $id;
                }
                $tuan_model->addPinGoods($goods);
                $this->recordSellerLog('编辑拼团活动：'.$id.'，商品编码：' . $_POST['tuan_goods_id']);
                showDialog('编辑成功', urlAddons('index'), 'succ');
            } else {
                showDialog('编辑失败');
            }
        }else {
            $pin_id = $tuan_model->addTuan($param);
            if ($pin_id) {
                foreach ($goods as $k => $v) {
                    $goods[$k]['sld_pin_id'] = $pin_id;
                }
                $tuan_model->addPinGoods($goods);
                $this->recordSellerLog('发布拼团活动：'.$pin_id.'，商品编码：' . $_POST['tuan_goods_id']);
                showDialog('发布成功', urlAddons('index'), 'succ');
            } else {
                showDialog('发布失败');
            }
        }
    }

    /**
     * 停止活动
     */
    public function stoppin() {
        $pin_id = $_GET['id'];
        $model_pin = M('pin');
        $return = $model_pin->table('pin')->where(array('id'=>$pin_id))->update(array('sld_status'=>0));
        if ($return) {
            // 解锁 该拼团 下的商品
            // 获取商品ID
            $goods_commonids = array();
            $goods_data = $model_pin->getGoodsListByPinId($pin_id);
            $gids = low_array_column($goods_data,'sld_gid');

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
            
            //文件缓存
            dkcache('pin_tuan_gid');
            rkcache('pin_tuan_gid',true);

            // 添加操作日志
            $this->recordSellerLog('停止拼团活动：'.$pin_id);
            showDialog('操作成功', 'reload', 'succ');
        } else {
            showDialog('操作失败', '', 'error');
        }
    }

    /**
     * ajax获取商品列表
     */
    public function goods_info($common_id=null,$ajax=1) {
        if(!$common_id) {
            $common_id = $_GET['goods_commonid'];
        }
        if ($common_id <= 0) {
            echo 'false';exit();
        }
        $model_goods = Model('goods');
        $goodscommon_list = $model_goods->getGoodsCommonInfo(array('vid' => $_SESSION['vid'], 'goods_commonid' => $common_id), 'spec_name');
        if (empty($goodscommon_list)) {
            echo 'false';exit();
        }
        $goods_list = $model_goods->getGoodsList(array('vid' => $_SESSION['vid'], 'goods_commonid' => $common_id), 'gid,goods_name,goods_spec,vid,goods_price,goods_serial,goods_storage_alarm,goods_storage,goods_image');
        if (empty($goods_list)) {
            echo 'false';exit();
        }

        $spec_name = array_values((array)unserialize($goodscommon_list['spec_name']));
        foreach ($goods_list as $key => $val) {
            $goods_spec = array_values((array)unserialize($val['goods_spec']));
            $spec_array = array();
            foreach ($goods_spec as $k => $v) {
                $spec_array[] = '<div class="goods_spec">' . $spec_name[$k] . ":" . '<em title="' . $v . '">' . $v .'</em>' . '</div>';
            }
            $goods_list[$key]['goods_name'] = $val['goods_name'];
            $goods_list[$key]['gid']       = $val['gid'];
            $goods_list[$key]['goods_serial']   = $val['goods_serial'];
            $goods_list[$key]['goods_image'] = thumb($val, '60');
            $goods_list[$key]['goods_spec'] = implode('', $spec_array);
            $goods_list[$key]['alarm'] = ( $val['goods_storage_alarm'] != 0 && $val['goods_storage'] <= $val['goods_storage_alarm']) ? 'style="color:red;"' : '';
            $goods_list[$key]['url'] = urlShop('goods', 'index', array('gid' => $val['gid']));
        }

        /**
         * 转码
         */
        if (strtoupper(CHARSET) == 'GBK') {
            Language::getUTF8($goods_list);
        }
        if($ajax) {
            echo json_encode($goods_list);
            exit();
        }else{
            return $goods_list;
        }
    }

    public function check_goods_repeat($gid=null,$ajax=1,$pin_id=0) {
        if(!$gid) {
            $gid = $_GET['gid'];
        }

        $tuan_model = M('pin');

        $data = array();
        $data['result'] = true;

        //检查商品是否已经参加同时段活动
        $condition = array();
        if($pin_id>0){
            $condition['sld_pin_id'] = array('neq',$pin_id);
        }
        $condition['sld_goods_id'] = $gid;
        $condition['sld_status'] = array('exp',' ( sld_status = 1 and sld_start_time <= '.TIMESTAMP.') ');
        $tuan_list = $tuan_model->getTuanCount($condition);
        if($ajax){
            if($tuan_list<1) {
                $data['result'] = false;
                echo json_encode($data);
                die;
            }else{
                echo json_encode($data);
                die;
            }
        }else{
            return $tuan_list<1;
        }
    }

    /**
     * 上传图片
     **/
    public function image_upload() {
        if(!empty($_POST['old_tuan_image'])) {
            $this->_image_del($_POST['old_tuan_image']);
        }
        $this->_image_upload('tuan_image');
    }

    private function _image_upload($file) {
        $data = array();
        $data['result'] = true;
        if(!empty($_FILES[$file]['name'])) {
            $upload	= new UploadFile();
            $uploaddir = ATTACH_PATH.DS.'tuan'.DS.$_SESSION['vid'].DS;
            $upload->set('default_dir', $uploaddir);
            $upload->set('thumb_width',	'730,365,73');
            $upload->set('thumb_height', '340,170,34');
            $upload->set('thumb_ext', '_max,_mid,_small');
            $upload->set('fprefix', $_SESSION['vid']);
            $result = $upload->upfile($file);
            if($result) {
                $data['file_name'] = $upload->file_name;
                $data['origin_file_name'] = $_FILES[$file]['name'];
                $data['file_url'] = gthumb($upload->file_name, 'mid');
            } else {
                $data['result'] = false;
                $data['message'] = $upload->error;
            }
        } else {
            $data['result'] = false;
        }
        echo json_encode($data);die;
    }

    /**
     * 图片删除
     */
    private function _image_del($image_name) {
        list($base_name, $ext) = explode(".", $image_name);
        $base_name = str_replace('/', '', $base_name);
        $base_name = str_replace('.', '', $base_name);
        list($vid) = explode('_', $base_name);
        $image_path = BASE_UPLOAD_PATH.DS.ATTACH_TUAN.DS.$vid.DS;
        $image = $image_path.$base_name.'.'.$ext;
        $image_small = $image_path.$base_name.'_small.'.$ext;
        $image_mid = $image_path.$base_name.'_mid.'.$ext;
        $image_max = $image_path.$base_name.'_max.'.$ext;
        delete_file($image);
        delete_file($image_small);
        delete_file($image_mid);
        delete_file($image_max);
    }

    /**
     * 选择活动商品
     **/
    public function search_goods() {
        $model_goods = Model('goods');
        $condition = array();
        $condition['vid'] = $_SESSION['vid'];
        $condition['goods_name'] = array('like', '%'.$_GET['goods_name'].'%');

        // 获取其他活动已经添加的商品ID
        $activity_gids = Model('goods_activity')->get_gids_other_activiting($_SESSION['vid']);
        // 过滤掉 其他活动的商品ID
        $condition['gid'] = array("NOT IN", $activity_gids);
        
        $goods_list = $model_goods->getGoodsOnlineList($condition, '*', 5);

        Template::output('goods_list', $goods_list);
        Template::output('show_page', $model_goods->showpage());
        Template::showpage('pin.goods', 'null_layout');
    }

    /**
     * 用户中心右边，小导航
     *
     * @param string 	$menu_key	当前导航的menu_key
     * @param array 	$array		附加菜单
     * @return
     */
    private function profile_menu() {
        $menu_array[0] = array('index'=>'拼团列表');
        $menu_array[1] = array(
            'add'=>'添加拼团',
            'edit'=>'编辑拼团',
            'team_list'=>'组团列表'
        );
        AddonsBase::get_proMenu($menu_array);
    }
}
