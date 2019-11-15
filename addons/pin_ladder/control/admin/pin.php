<?php
/**
 * 拼团管理
 *
 */
defined('DYMall') or exit('Access Invalid!');
class pinCtl extends SystemCtl
{

    public function __construct()
    {
        parent::__construct();
        Language::read('tuan');
        //如果是执行开启团购操作，直接返回
        if ($_GET['pin_open'] == 1) {
            //更改数据库
            $result = Model()->table('addons')->where(array('sld_key' => 'pin'))->update(array('sld_status' => 1));
            if ($result) {
                echo json_encode(array('state' => 200, 'msg' => '拼团功能开启成功'));
                die;
            } else {
                echo json_encode(array('state' => 255, 'msg' => '拼团功能开启失败'));
                die;
            }
        }
        //检查是否有团购功能
        $result = Model()->table('addons')->where(array('sld_key' => 'pin'))->find();
        if(empty($result)){
            echo json_encode(array('state' => 285, 'msg' => '您没有拼团功能的权限'));
            die;
        }else{
            if(!$result['sld_status']){
                echo json_encode(array('state' => 265, 'msg' => '拼团活动未开启'));die;
            }
        }
    }

    /*插件获取*/
    public function getAddInfo(){
        AddonsBase::get_addons_info('pin');
    }
    /*插件设置*/
    public function setAddInfo(){
        $val=$_GET['val'];
        AddonsBase::set_addons_info('pin',$val);
    }

    /**
     * 拼团列表
     *
     */
    public function getTuanList()
    {
        $tuan_model = M('pin');
        $search = array();
        $pageSize = intval($_GET['pageSize'] > 0 ? $_GET['pageSize'] : 10);
        $_GET['pn'] = intval($_GET['currentPage'] > 0 ? $_GET['currentPage'] : 1);

        $condition = array();
        if ($_GET['tuan_state']) { //如果状态不为空
            if ($_GET['tuan_state'] == 1) {
                $condition['sld_start_time'] = array('gt', TIMESTAMP);
            } elseif ($_GET['tuan_state'] == 2) {
                $condition['sld_start_time'] = array('lt', TIMESTAMP);
                $condition['sld_end_time'] = array('gt', TIMESTAMP);
            } elseif ($_GET['tuan_state'] == 3) {
                $condition['sld_end_time'] = array('lt', TIMESTAMP);
            }
            $search['tuan_state'] = $_GET['tuan_state'];  //拼团状态筛选
        }
        if ($_GET['tuan_name']) {  //产品名称筛选
            $search['goods_name'] = $_GET['tuan_name'];
            $condition['goods.goods_name'] = array('like', '%' . $_GET['tuan_name'] . '%');
        }
        if($_GET['type']) {  //产品分类筛选
            $search['type'] = $_GET['type'];  //拼团状态筛选
            $condition['pin_type.id'] = $_GET['type'];
        }
        if($_GET['s_name']) {  //店铺名称
            $search['s_name'] = $_GET['s_name'];  //店铺名称筛选
            $condition['vendor.store_name'] = array('like', '%' . $_GET['s_name'] . '%');
        }

        //拼团的分类
        $types = $tuan_model->getPinTypes(array('sld_parent_id'=>0));
        $tuan_list = $tuan_model->table('pin,goods_common,pin_type,vendor,pin_goods,goods')->join('left')
            ->on('pin.sld_goods_id=goods_common.goods_commonid,pin.sld_type=pin_type.id,pin.sld_vid=vendor.vid,pin_goods.sld_pin_id=pin.id,pin_goods.sld_gid=goods.gid')
            ->where($condition)
            ->order('sld_success_time desc')
            ->field('pin.*,pin_goods.sld_gid,vendor.store_name,goods_common.goods_name,pin_type.sld_typename,(select count(*) from bbc_pin_team where sld_pin_id=pin.id) as zong,(select count(*) from bbc_pin_team where sld_pin_id=pin.id and sld_tuan_status=1) as cheng,min(pin_goods.sld_pin_price) as min_pin_price,max(pin_goods.sld_pin_price) as max_pin_price,min(goods.goods_price) as min_price,max(goods.goods_price) as max_price,min(pin_goods.sld_stock) as min_stock,max(pin_goods.sld_stock) as max_stock')
            ->group('pin.id')
            ->page($pageSize)
            ->select();

        foreach ($tuan_list as $k => $tuan_info) {
            $tuan_list[$k]['sld_pic_url'] = gthumb($tuan_info['sld_pic']);
            $tuan_list[$k]['goods_url'] = urlShop('goods', 'index', array('gid' => $tuan_info['sld_gid']));
            $tuan_list[$k]['start_time_text'] = date('Y-m-d H:i:s', $tuan_info['sld_start_time']);
            $tuan_list[$k]['end_time_text'] = date('Y-m-d H:i:s', $tuan_info['sld_end_time']);
            if ($tuan_info['sld_status'] == 0) {
                $tuan_list[$k]['tuan_state_text'] = '禁用';
            } else {
                if ($tuan_info['sld_start_time'] < TIMESTAMP && $tuan_info['sld_end_time'] > TIMESTAMP) {
                    $tuan_list[$k]['tuan_state_text'] = '进行中';
                } elseif ($tuan_info['sld_end_time'] < TIMESTAMP) {
                    $tuan_list[$k]['tuan_state_text'] = '已结束';
                } else {
                    $tuan_list[$k]['tuan_state_text'] = '等待开始';
                }
            }
        }
        echo json_encode(array('list' => $tuan_list,'types'=>$types, 'pagination' => array('current' => $_GET['pn'], 'pageSize' => $pageSize, 'total' => intval($tuan_model->gettotalnum())), 'searchlist' => $search, 'tuan_state_array' => $tuan_model->getTuanStateArray()));

    }

    /**
     * 拼团详情
     **/
    public function add() {
        $model_pin = M('pin');
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

        }else{
            echo json_encode(array('state'=>255,'msg'=>'id不存在'));exit();
        }

        $class_list = $model_pin->getPinTypes(array('sld_parent_id'=>0));

//        if($pin_info){  二级分类
//            $class_list2 = $model_pin->getPinTypes(array('sld_parent_id'=>$pin_info['pid']));
//            Template::output('class_list2', $class_list2);
//        }

        echo json_encode(array('state'=>'200','info' => $pin_info, 'class_list' => $class_list ));
    }


    /**
     * 团队列表
     * id  拼团活动的id
     * tuan_state 团队状态
     *
     **/
    public function team_list(){
        $tuan_model = M('pin');
        $pageSize = intval($_GET['pageSize'] > 0 ? $_GET['pageSize'] : 10);
        $pin_id = $_GET['id'];
        $pin_info = $tuan_model->getTuanInfo(array('id'=>$pin_id));

        $condition = array();
        $condition['sld_pin_id'] = $pin_id;
        if($_GET['tuan_state']!=null && $_GET['tuan_state']!='') { //如果状态不为空
            $condition['sld_tuan_status'] = $_GET['tuan_state'];
        }
        $condition['sld_pin_id'] = $pin_id;
        $condition['order.order_state'] = array('neq',1);
        $teams = $tuan_model->table('pin_team,pin_team_user,member,order')->join('left')
            ->on('pin_team.id=pin_team_user.sld_team_id,pin_team.sld_leader_id=member.member_id,pin_team_user.sld_order_id=order.order_id')
            ->where($condition)
            ->group('pin_team.id')
            ->field('pin_team.id,sld_tuan_status,pin_team_user.sld_add_time,count(pin_team_user.id) as ren,member_name')
            ->page($pageSize)
            ->order('sld_tuan_status = 0 desc,sld_add_time desc')
            ->select();

        $ids = array();
        foreach ($teams as $v){
            if(!in_array($v['id'],$ids)){
                $ids[]=$v['id'];
            }
        }

        $condition['sld_team_id'] = array('in',$ids);

        $user_list = Model()->table('pin_team,pin_team_user,member,order')->join('left')
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

        echo json_encode(array('list' => $teams, 'pagination' => array('current' => $_GET['pn'], 'pageSize' => $pageSize, 'total' => intval($tuan_model->gettotalnum())), 'searchlist' => $_GET, 'tuan_state_array' => $tuan_model->getTuanStateArray()));
    }

    //手动成团
    public function setSuccess(){
        $id=intval($_GET['id']);
        $tuan_model = M('pin');
        $re = $tuan_model->table('pin_team')->where(array('id'=>$id))->update(array('sld_tuan_status'=>1));
        if($re){
            $this->log('手动成团：'.$id,1);
            echo json_encode(array('state'=>200,'msg'=>'成团成功'));
        }else{
            echo json_encode(array('state'=>255,'msg'=>'操作失败'));
        }
    }

    /*ajax获取分类*/
    public function getTypes(){
        $parent_id = $_GET['parent_id']?$_GET['parent_id']:0;
        $class_list = M('pin')->getPinTypes(array('sld_parent_id'=>$parent_id));
        exit(json_encode($class_list));
    }

    //禁用活动
    public function stop(){
        $id=intval($_GET['id']); //活动id
        $tuan_model = M('pin');
        $pin_info = $tuan_model->getTuanInfoByID($id);
        if(!$pin_info){
            echo json_encode(array('state'=>255,'msg'=>'活动不存在'));exit();
        }

        $re = $tuan_model->table('pin')->where(array('id'=>$id))->update(array('sld_status'=>0));
        if($re){
            $this->log('停止拼团活动：'.$id,1);
            echo json_encode(array('state'=>200,'msg'=>'操作成功'));
        }else{
            echo json_encode(array('state'=>255,'msg'=>'活动不存在'));
        }
    }

    //开启活动
    public function open(){
        $id=intval($_GET['id']); //活动id
        $tuan_model = M('pin');
        $pin_info = $tuan_model->getTuanInfoByID($id);
        if(!$pin_info){
            echo json_encode(array('state'=>255,'msg'=>'活动不存在'));exit();
        }

        $re = $tuan_model->table('pin')->where(array('id'=>$id))->update(array('sld_status'=>1));
        if($re){
            $this->log('开启拼团活动：'.$id,1);
            echo json_encode(array('state'=>200,'msg'=>'操作成功'));
        }else{
            echo json_encode(array('state'=>255,'msg'=>'活动不存在'));
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


}