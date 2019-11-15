<?php
/**
 * 商家中心管理
 */
defined('DYMall') or exit('Access Invalid!');

class action_ladderCtl extends BaseSellerCtl {

    public function __construct() {
        parent::__construct();

        //读取语言包
        Language::read('member_tuan');
        //检查团购功能是否开启
        if (!(C('sld_pintuan_ladder') && C('pin_ladder_isuse')) ){
            vendorMessage('阶梯团购活动没有开启','index.php?app=vendorcenter','','error');
        }
    }

    /**
     * 默认显示团购列表
     **/
    public function index() {
        dkcache('pin_ladder_tuan_gid');
        rkcache('pin_ladder_tuan_gid',true);
        $this->pin_list();
    }

    /**
     * 列表
     **/
    public function pin_list() {
        $tuan_model = M('pin_ladder','pin_ladder');
        $category_model = M('pin_category','pin_ladder');
        $condition = array();
        $condition['sld_vid'] = $_SESSION['vid'];
        if($_GET['tuan_state']) { //状态筛选
            if($_GET['tuan_state']==1){
                $condition['sld_start_time'] = array('gt',TIMESTAMP);
            }elseif($_GET['tuan_state']==2){
                $condition['sld_start_time']    = array('lt',TIMESTAMP);
                $condition['sld_end_time']      = array('gt',TIMESTAMP);
                $condition['pin_ladder.sld_status'] = 1;
            }elseif($_GET['tuan_state']==3){
                $condition['sld_status'] = array('exp', " sld_start_time < ".TIMESTAMP."  and ( sld_end_time <  ".TIMESTAMP." or pin_ladder.sld_status = 0)");
            }
        }
        if($_GET['tuan_name']) {  //产品名称
            $condition['goods_name'] = array('like', '%' . $_GET['tuan_name'] . '%');
        }
        if($_GET['type']) {  //产品分类
            $condition['pin_category.id'] = $_GET['type'];
        }

        $tuan_list = $tuan_model->table('pin_ladder,pin_goods_ladder,goods_common,pin_category')->join('left')
            ->on('pin_ladder.id=pin_goods_ladder.sld_pin_id,pin_ladder.sld_goods_id=goods_common.goods_commonid,pin_ladder.sld_type=pin_category.id')
            ->where($condition)
            ->field('pin_ladder.id,pin_ladder.sld_type,pin_ladder.sld_pic,pin_ladder.sld_start_time,pin_ladder.sld_end_time,pin_ladder.sld_return_leader,pin_ladder.sld_max_buy,pin_ladder.sld_team_count,pin_ladder.sld_success_time,pin_ladder.sld_status,goods_common.goods_name,pin_category.class_name,pin_goods_ladder.sld_gid,(select count(*) from bbc_pin_team_user_ladder where sld_pin_id=pin_ladder.id) as zong,(select count(*) from bbc_pin_order where pin_id=pin_ladder.id  and order_state="30") as cheng')
            ->page(20)
            ->order('pin_ladder.id desc')
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
        Template::output('types',$category_model->getlist());

        self::profile_menu();
        Template::showpage('pin.list');
    }

    /**
     * 添加拼团页面
     **/
    public function add() {
        $model_pin = M('pin','pin_ladder');
        $category_model = M('pin_category','pin_ladder');
        $class_list =  $category_model->getlist();
        Template::output('class_list', $class_list);
        if(!$class_list){
            showDialog('没有拼团分类，不能创建拼团', urlAddons('index'));
        }

        if($_GET['id']){
            $pin_id = intval($_GET['id']);
            $condition['pin_ladder.id'] = $pin_id;
            $pin_info = $model_pin->table('pin_ladder,goods_common,pin_category')->join('left')
                ->on('pin_ladder.sld_goods_id=goods_common.goods_commonid,pin_ladder.sld_type=pin_category.id')
                ->where($condition)
                ->field('pin_ladder.*,goods_common.goods_name,pin_category.class_name as pid')
                ->find();

            $pin_info['start_time_text'] = date('Y-m-d H:i', $pin_info['sld_start_time']);
            $pin_info['end_time_text'] = date('Y-m-d H:i', $pin_info['sld_end_time']);
            $pin_goods = $model_pin->getGoodsListByPinId($pin_id);
            $muti_goods = $this->goods_info($pin_info['sld_goods_id'],0);
            foreach ($pin_goods as $k=>$v){
                $pin_goods[$k] = array_merge($v,$muti_goods[$k]);
            }
            $pin_info['goods_list'] = $pin_goods;
            foreach($pin_info['goods_list'] as $k=>$v) {
                $pin_info['goods_list'][$k]['ladder'] = $model_pin->table('pin_money_ladder')->where(['pin_goods_id'=>$v['id']])->select();
            }
            Template::output('pin_info', $pin_info);

        };
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
            $model_pin = M('pin','pin_ladder');
            $category_model = M('pin_category','pin_ladder');
            $class_list =  $category_model->getlist();
            Template::output('class_list', $class_list);
            if(!$class_list){
                showDialog('没有拼团分类，不能创建拼团', urlAddons('index'));
            }

            $pin_id = intval($_GET['id']);
            $condition['pin_ladder.id'] = $pin_id;
            $pin_info = $model_pin->table('pin_ladder,goods_common,pin_category')->join('left')
                ->on('pin_ladder.sld_goods_id=goods_common.goods_commonid,pin_ladder.sld_type=pin_category.id')
                ->where($condition)
                ->field('pin_ladder.*,goods_common.goods_name,pin_category.class_name as pid')
                ->find();
            $pin_info['start_time_text'] = date('Y-m-d H:i', $pin_info['sld_start_time']);
            $pin_info['end_time_text'] = date('Y-m-d H:i', $pin_info['sld_end_time']);
            $pin_goods = $model_pin->getGoodsListByPinId($pin_id);
            $muti_goods = $this->goods_info($pin_info['sld_goods_id'],0);
            foreach ($pin_goods as $k=>$v){
                $pin_goods[$k] = array_merge($v,$muti_goods[$k]);
            }
            $pin_info['goods_list'] = $pin_goods;
            foreach($pin_info['goods_list'] as $k=>$v) {
                $pin_info['goods_list'][$k]['ladder'] = $model_pin->table('pin_money_ladder')->where(['pin_goods_id'=>$v['id']])->select();
            }
            Template::output('pin_info', $pin_info);

        }else{
            showDialog('参数错误');
        }

        if($pin_info){
            $class_list2 =  $category_model->getlist();
            Template::output('class_list2', $class_list2);
        }

        self::profile_menu();
        Template::showpage('pin.view');

    }

    //团队列表
    public function team_list(){
        $tuan_model = M('pin','pin_ladder');
        $model = model();
        $pin_id = $_GET['id'];
        $pin_info = $tuan_model->getTuanInfo(array('id'=>$pin_id));
        $condition = ['pin_id'=>$pin_id];
        $condition['order_state'] = ['neq',10];
        if(isset($_GET['tuan_state']) && !empty($_GET['tuan_state'])){
                if($_GET['tuan_state'] == 1){
                    $order_id = array_keys($model->table('pin_team_user_ladder')->where(['sld_pin_id'=>$pin_id])->key('sld_order_id')->select());
                    $condition['order_id'] = ['in',$order_id];
                }else{
                    $condition['order_state'] = $_GET['tuan_state'];
                }
        }
        $list = $model->table('pin_order')->where($condition)->page(10)->order('order_id desc')->select();
        Template::output('group',$list);
        Template::output('show_page',$model->showpage());
        self::profile_menu();
        Template::showpage('pin.team_list');
    }

    //手动成团
    public function setSuccess(){
        $id=intval($_GET['id']);
        $tuan_model = M('pin','pin_ladder');
        $re = $tuan_model->table('pin_team_ladder')->where(array('id'=>$id))->update(array('sld_tuan_status'=>1));
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
        $class_list = M('pin','pin_ladder')->getPinTypes(array('sld_parent_id'=>$parent_id));
        exit(json_encode($class_list));
    }

    //删除活动
    public function delete(){
        $id=intval($_GET['id']);
        $tuan_model = M('pin','pin_ladder');
        $model = Model();
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
        try {
            $tuan_model->begintransaction();
            //先判断回主商品库存再删除
            if($pin_info['sld_status'] == 1){
                $ladder_goods_info = $model->table('pin_goods_ladder')->where(['sld_pin_id'=>$pin_info['id']])->select();
                foreach($ladder_goods_info as $k=>$v) {
                    $res = $model->table('goods')->where(['gid'=>$v['sld_gid']])->update(['goods_storage'=>['exp','goods_storage + '.$v['sld_stock']]]);
                    if(!$res){
                            throw new Exception('删除失败');
                    }
                }
            }
            $re1 = $tuan_model->table('pin_goods_ladder')->where(array('sld_pin_id' => $id))->delete();
            $re2 = $tuan_model->table('pin_ladder')->where(array('id' => $id))->delete();
            $re3 = $tuan_model->table('pin_money_ladder')->where(array('pin_id' => $id))->delete();

            if ($re1 && $re2 && $re3) {
                //解锁商品
                $res4 = Model('goods')->editGoodsCommonUnlock(['goods_commonid'=>$pin_info['sld_goods_id']]);
                if(!$res4){
                    throw new Exception('删除失败');
                }
                $this->recordSellerLog('删除拼团活动：' . $id);
                //文件缓存
                dkcache('pin_ladder_tuan_gid');
                rkcache('pin_ladder_tuan_gid',true);
                $tuan_model->commit();
                showDialog('删除成功', urlAddons('index'), 'succ');
            } else {
                throw new Exception('删除失败');
            }
        } catch (Exception $e) {
            $tuan_model->rollback();
            showDialog($e->getMessage());
        }
    }

    /**
     * 拼团保存
     **/
    public function save() {
        $model = model();
        $id= intval($_POST['id']);
        $repeat = $this->check_goods_repeat($_POST['tuan_goods_id'],0,$id);
        if(!$repeat){
            showDialog('活动已开始，不能编辑');
        }

        if($id) {
            if ($_POST['start_time'] > TIMESTAMP) {
                $zong = M('pin','pin_ladder')->table('pin_team_ladder')->where(array('sld_pin_id'=>$id))->count();
                if($zong>0) {
                    showDialog('活动有人参与了，不能编辑', urlAddons('view') . '&id=' . $id);
                }
            }
        }

        //获取提交的数据
        $gid = $_POST['gid'];

        $have_activity = Model('goods')->getOtherActivity($gid,$_POST['tuan_goods_id'],strtotime($_POST['start_time']),strtotime($_POST['end_time']),'pin_ladder');

        if($have_activity>0){
            showDialog('该商品同已经参加其他优惠活动了！', urlAddons('view') . '&id=' . $id);
        }
        //组装产品数据
        $goods = array();

        $where_old['gid'] = array('in',$gid);
        $db = Model('goods');
        $old_goods = $db->table('goods')->where($where_old)->key('gid')->field('gid,goods_price,goods_storage')->select();
//        dd($_POST);
//        dd($gid);die;
        //主商品库存
        $aaa_storage = [];
        foreach ($gid as $k=>$v){
            $sstr = "第".($k+1)."个产品";
            if($_POST['gid'][$k]<1){
                showDialog($sstr.Language::get('参数错误'));
            }
//            if($old_goods[intval($_POST['gid'][$k])]['goods_price']<=$_POST['sld_pin_price'][$v]){
//                showDialog($sstr.'价格并没有优惠');
//            }
            if(floatval($_POST['sld_pin_price'][$v]) <= 0){
                showDialog($sstr.'定金不能小于0！');

            }
            //库存检测
            if($_POST['sld_stock'][$v] <=0 ){
                showDialog('库存不能小于0');
            }
            if($_POST['sld_pin_price'][$v] > $old_goods[$v]['goods_price']){
                showDialog('第'.($k+1).'件商品定金不能大于商品价格');
            }
            if($_POST['sld_pin_price'][$v] > end($_POST['ladder'][$v]['money'])){
                showDialog('第'.($k+1).'件商品定金不能大于阶梯价');
            }
            if($id){
                //如果是编辑
                //活动库存
                $goods_info = $model->table('pin_goods_ladder,pin_ladder')->join('left')->on('pin_goods_ladder.sld_pin_id=pin_ladder.id')->where(['pin_ladder.id'=>$id])->field('pin_goods_ladder.sld_gid,pin_goods_ladder.sld_stock')->key('sld_gid')->select();

                foreach($goods_info as $kk=>$vv){
                    $goods_storage = $model->table('goods')->where(['gid'=>$vv['sld_gid']])->field('goods_storage')->find();
                    $all_storage = $vv['sld_stock'] + $goods_storage['goods_storage'];
                    if($_POST['sld_stock'][$v] > $all_storage){
                        showDialog('第'.($k+1).'件商品库存不能大于主库存');
                    }else{
                        $aaa_storage[$v] = $all_storage;
                    }
                }
            }else{
                //如果是新增
                if($_POST['sld_stock'][$v] > $old_goods[$v]['goods_storage']){
                    showDialog('第'.($k+1).'件商品库存不能大于主库存');
                }
            }

            $tmp['sld_gid']                 = intval($_POST['gid'][$k]);                //gid
            $tmp['sld_pin_price']       = floatval($_POST['sld_pin_price'][$v]);    //拼价
            $tmp['sld_stock']           = intval($_POST['sld_stock'][$v]);          //库存
            $goods[] = $tmp;
        }
        $tuan_model = M('pin','pin_ladder');
        $model_goods = Model('goods');
        //开始存pin表
        $param = array();
        $param['sld_goods_id']  = $_POST['tuan_goods_id'];
        $param['sld_vid']       = $_SESSION['vid'];
        $param['sld_type']      = intval($_POST['class_id']);
        $param['sld_pic']      = $_POST['tuan_image'];
        $param['sld_start_time'] = strtotime($_POST['start_time']);
        $param['sld_end_time'] = strtotime($_POST['end_time']);
        $param['sld_max_buy'] = intval($_POST['sld_max_buy']);
        $param['is_tui'] = intval($_POST['is_tui']);
        $param['sld_success_time'] = floatval($_POST['sld_success_time']);
        $param['sld_status'] = intval($_POST['sld_status']);
        //检查时间冲突
        $where['sld_status'] = 1;
        $where['sld_start_time'] = array('exp', "(sld_start_time <= ".$param['sld_start_time']." or sld_end_time >=".$param['sld_end_time']." )");
        $where['sld_goods_id'] = $param['sld_goods_id'];
        //编辑时去掉自己
        if($id > 0){
            $where['pin_ladder.id'] = ['neq',$id];
        }
        $repeat_time = M('pin','ladder')->table('pin_ladder,pin_goods_ladder')->join('left')
            ->on('pin_ladder.id=pin_goods_ladder.sld_pin_id')
            ->field('pin_ladder.id')
            ->where($where)
            ->select();
        if(count($repeat_time)>0){
            foreach ($repeat_time as $v){
                $rep_ids[]=$v['id'];
            }
            $rep_ids=join('、',$rep_ids);
            showDialog('所选产品与活动'.$rep_ids.'时间有冲突');
        }
        //进行数据库操作
        try {
            $tuan_model->begintransaction();
            if ($id > 0) {
                unset($param['sld_vid']);
                //解锁之前的订单
                $pin_info = $model->table('pin_ladder')->where(['id'=>$id])->find();
                $res = $model->table('goods_common')->where(['goods_commonid'=>$pin_info['sld_goods_id']])->update(['goods_lock'=>0]);
                if(!$res){
                        throw new Exception('编辑失败');
                }
                $re = $tuan_model->editTuan($param, array('id' => $id, 'sld_vid' => $_SESSION['vid']));
                if ($re) {
                    $res1 = $model->table('pin_goods_ladder')->where(array('sld_pin_id' => $id))->delete();
                    $res2 = $model->table('pin_money_ladder')->where(array('pin_id' => $id))->delete();
                    if(!$res1 || !$res2){
                        throw new Exception('编辑失败');
                    }
                    foreach ($goods as $k => $v) {
                        $goods[$k]['sld_pin_id'] = $id;
                    }
//                    dd($aaa_storage);
//                    dd($goods);die;
                    //商品主库存与活动库存做同步
                    foreach($goods as $k=>$v){
                        $res = $model->table('goods')->where(['gid'=>$v['sld_gid']])->update(['goods_storage'=>$aaa_storage[$v['sld_gid']] - $v['sld_stock']]);
                        if(!$res){
                            throw new Exception('保存失败');
                        }
                    }
                    $goods_res = $tuan_model->addPinGoods($goods);
                    if(!$goods_res){
                        throw new Exception('发布失败');
                    }
                    //阶梯价格存储

                    foreach($goods as $k=>$v){
                        $condition = [
                            'sld_pin_id'=>$v['sld_pin_id'],
                            'sld_gid'=>$v['sld_gid']
                        ];
                        $ladder_goods_info = $tuan_model->getladdergoods($condition,'*',1);

                        //验证阶梯顺序
                        $ANumberBat = $_POST['ladder'][$ladder_goods_info['sld_gid']]['number'];
                        sort($ANumberBat,1);
                        if(serialize($_POST['ladder'][$ladder_goods_info['sld_gid']]['number']) != serialize($ANumberBat)){
                            throw new Exception('阶梯人数设置顺序错误');
                        }
                        foreach($_POST['ladder'][$ladder_goods_info['sld_gid']]['number'] as $kkk=>$vvv){
                            if($vvv<=0 || $_POST['ladder'][$ladder_goods_info['sld_gid']]['money'][$kkk]<=0){
                                throw new Exception('阶梯不能为空');
                            }
                            $insertgoodsladder[] = [
                                'pin_goods_id'=>$ladder_goods_info['id'],
                                'pin_id'=>$ladder_goods_info['sld_pin_id'],
                                'gid'=>$ladder_goods_info['sld_gid'],
                                'people_num'=>$vvv,
                                'pay_money'=>$_POST['ladder'][$ladder_goods_info['sld_gid']]['money'][$kkk],
                            ];
                        }
                    }
                    $res = $tuan_model->insertladderall($insertgoodsladder);
                    if(!$res){
                        throw new Exception('编辑失败');
                    }
                    $this->recordSellerLog('编辑拼团活动：' . $id . '，商品编码：' . $_POST['tuan_goods_id']);
                    $tuan_model->commit();
                    //文件缓存
                    dkcache('pin_ladder_tuan_gid');
                    rkcache('pin_ladder_tuan_gid',true);
                    showDialog('编辑成功', urlAddons('index'), 'succ');
                } else {
                    throw new Exception('编辑失败');
                }
            } else {
                $pin_id = $tuan_model->addTuan($param);
                if ($pin_id) {
                    foreach ($goods as $k => $v) {
                        $goods[$k]['sld_pin_id'] = $pin_id;
                    }
//                    dd($goods);die;
                    //商品主库存与活动库存做同步
                    foreach($goods as $k=>$v){
                        $res = $model->table('goods')->where(['gid'=>$v['sld_gid']])->update(['goods_storage'=>['exp','goods_storage - '.$v['sld_stock']]]);
                        if(!$res){
                            throw new Exception('保存失败');
                        }
                    }

                    $goods_res = $tuan_model->addPinGoods($goods);
                    if(!$goods_res){
                        throw new Exception('发布失败');
                    }
                    //阶梯价格存储
                    foreach($goods as $k=>$v){
                        $condition = [
                            'sld_pin_id'=>$v['sld_pin_id'],
                            'sld_gid'=>$v['sld_gid']
                        ];
                        $ladder_goods_info = $tuan_model->getladdergoods($condition,'*',1);
                        //验证阶梯顺序
                        $ANumberBat = $_POST['ladder'][$ladder_goods_info['sld_gid']]['number'];
                        sort($ANumberBat,1);
                        if(serialize($_POST['ladder'][$ladder_goods_info['sld_gid']]['number']) != serialize($ANumberBat)){
                            throw new Exception('阶梯人数设置顺序错误');
                        }
                        foreach($_POST['ladder'][$ladder_goods_info['sld_gid']]['number'] as $kkk=>$vvv){
                            if($vvv<=0 || $_POST['ladder'][$ladder_goods_info['sld_gid']]['money'][$kkk]<=0){
                                throw new Exception('阶梯不能为空');
                            }
                            $insertgoodsladder[] = [
                                'pin_goods_id'=>$ladder_goods_info['id'],
                                'pin_id'=>$ladder_goods_info['sld_pin_id'],
                                'gid'=>$ladder_goods_info['sld_gid'],
                                'people_num'=>$vvv,
                                'pay_money'=>$_POST['ladder'][$ladder_goods_info['sld_gid']]['money'][$kkk],
                            ];
                        }
                    }
                    $res = $tuan_model->insertladderall($insertgoodsladder);
                    if(!$res){
                        throw new Exception('发布失败');
                    }
                    $this->recordSellerLog('发布阶梯团活动：' . $pin_id . '，商品编码：' . $_POST['tuan_goods_id']);
                    $tuan_model->commit();
                    //文件缓存
                    dkcache('pin_ladder_tuan_gid');
                    rkcache('pin_ladder_tuan_gid',true);

                    showDialog('发布成功', urlAddons('index'), 'succ');
                } else {
                    throw new Exception('发布失败');
                }
            }
        } catch (Exception $e) {
            $tuan_model->rollback();
            showDialog($e->getMessage());
        }
    }

    /**
     * 停止活动
     */
    public function stoppin() {
        $pin_id = $_GET['id'];
        $model_pin = M('pin','pin_ladder');
        $model = model();
        $pin_info =  $model_pin->getTuanInfoByID($pin_id);
        try {
            $model_pin->begintransaction();
            //已经加入的会员直接退定金
            $orderList = $model->table('pin_order')->where(['order_state'=>20,'pin_id'=>$pin_id])->select();
            if($orderList){
                $model_pd = Model('predeposit');
                foreach($orderList as $k=>$v){
                    $res = $model->table('pin_order')->where(['order_state'=>20,'pin_id'=>$pin_id,'buyer_id'=>$v['buyer_id']])->update(['order_state'=>0]);
                    if(!$res){
                        throw new Exception('终止失败');
                    }
                    $data_pd = array();
                    $data_pd['member_id'] = $v['buyer_id'];
                    $data_pd['member_name'] = $v['buyer_name'];
                    $data_pd['amount'] = $v['goods_price'] * $v['goods_num'];
                    $data_pd['order_sn'] = $v['order_sn'];
                    $data_pd['lg_desc'] = '阶梯团购取消,订单号: '.$v['order_sn'];
                    $model_pd->changePd('huodong_cancel',$data_pd);
                }
            }
            //先判断回主商品库存再删除
                $ladder_goods_info = $model->table('pin_goods_ladder')->where(['sld_pin_id' => $pin_info['id']])->select();
                foreach ($ladder_goods_info as $k => $v) {
                    $res = $model->table('goods')->where(['gid' => $v['sld_gid']])->update(['goods_storage' => ['exp', 'goods_storage + ' . $v['sld_stock']]]);
                    if (!$res) {
                        throw new Exception('终止失败');
                    }
                }

            $return = $model_pin->table('pin_ladder')->where(array('id' => $pin_id))->update(array('sld_status' => 0));

            if ($return) {
                // 解锁 该拼团 下的商品
                // 获取商品ID
                $goods_commonids = array();
                $goods_data = $model_pin->getGoodsListByPinId($pin_id);
                $gids = low_array_column($goods_data, 'sld_gid');

                // 获取所有商品的 goods_commonid
                $goods_data = Model('goods')->getGoodsList(array('gid' => array("IN", $gids)), 'goods_commonid');

                if (!empty($goods_data)) {
                    $goods_commonids = low_array_column($goods_data, 'goods_commonid');
                }

                if (!empty($goods_commonids)) {
                    $goods_commonids = array_flip($goods_commonids);
                    $goods_commonids = array_flip($goods_commonids);
                    $goods_commonids = array_values($goods_commonids);
                    $unlock_condition['goods_commonid'] = array("IN", $goods_commonids);
                    $res = Model('goods')->editGoodsCommonUnlock($unlock_condition);
                }

                //文件缓存
                dkcache('pin_ladder_tuan_gid');
                rkcache('pin_ladder_tuan_gid', true);

                // 添加操作日志
                $this->recordSellerLog('停止拼团活动：' . $pin_id);

                $model_pin->commit();
                showDialog('操作成功', 'reload', 'succ');
            } else {
                throw new Exception('操作失败');
            }
        } catch (Exception $e) {
            $model_pin->rollback();
            //文件缓存
            dkcache('pin_ladder_tuan_gid');
            rkcache('pin_ladder_tuan_gid', true);
            showDialog($e->getMessage(), '', 'error');
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

        $tuan_model = M('pin','pin_ladder');

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
        //文件缓存
//        dkcache('pin_ladder_tuan_gid');
//        rkcache('pin_ladder_tuan_gid',true);
        $model_goods = Model('goods');
        $condition = array();
        $condition['vid'] = $_SESSION['vid'];
        $condition['goods_name'] = array('like', '%'.$_GET['goods_name'].'%');

        // 获取其他活动已经添加的商品ID
        $activity_gids = Model('goods_activity')->get_gids_other_activiting($_SESSION['vid']);
        // 过滤掉 其他活动的商品ID
        $condition['gid'] = array("NOT IN", array_filter($activity_gids));
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
        $menu_array[0] = array('index'=>'阶梯团列表');
        $menu_array[1] = array(
            'add'=>'添加团购',
            'edit'=>'编辑团购',
            'team_list'=>'组团列表'
        );
        AddonsBase::get_proMenu($menu_array);
    }
}
