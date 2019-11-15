<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/11/26
 * Time: 11:09
 */
class presaleCtl extends BaseSellerCtl
{
    private $presale_model;
    private $model;
    private $cateList;
    private $preState;
    public function __construct()
    {
        $this->presale_model = M('pre_presale','presale');
        $this->model = Model();
        $this->cateList = M('pre_category','presale')->getlist();
        $this->preState = $this->presale_model->get_state();
        parent::__construct();
        //读取语言包
        Language::read('member_tuan');
        //检查团购功能是否开启
        if (!(C('sld_presale_system') && C('pin_presale_isuse')) ){
            vendorMessage('阶梯团购活动没有开启','index.php?app=vendorcenter','','error');
        }
    }
    /*
     * get
     * 预售首页列表
     * status 状态
     *
     */
    public function index()
    {
        $condition = [];
        $condition['presale.vid'] = $_SESSION['vid'];
        if(isset($_GET['status']) && !empty($_GET['status'])){
            switch(intval($_GET['status'])){
                case 1:
                    $condition['presale.pre_start_time'] = ['gt',time()];
                    break;
                case 2:
                    $condition['presale.pre_start_time'] = ['lt',time()];
                    $condition['presale.pre_end_time'] = ['gt',time()];
                    $condition['presale.pre_status'] = 1;
                    break;
                case 3:
                    $condition['presale.pre_end_time'] = ['lt',time()];
                    break;
            }
        }
        if(isset($_GET['cateid']) && !empty($_GET['cateid'])){
            $condition['presale.pre_category'] = intval($_GET['cateid']);
        }
        if(isset($_GET['goods_name']) && !empty($_GET['goods_name'])){
            $condition['pre_goods.goods_name'] = ['like','%'.trim($_GET['goods_name']).'%'];
        }
        $presaleList = $this->model->table('presale,pre_goods')->join('left')->on('presale.pre_id=pre_goods.pre_id')->where($condition)->order('presale.pre_id desc')->field([
            'presale.pre_id',
            'presale.pre_pic',
            'presale.pre_category',
            'presale.pre_start_time',
            'presale.pre_end_time',
            'presale.pre_status',
            'pre_goods.id',
            'pre_goods.goods_name',
            'pre_goods.gid',
        ])->page(10)->select();
       $gid = low_array_column($presaleList,'gid');
        foreach($presaleList as $k=>$v){
            $presaleList[$k]['zong'] = $this->model->table('pre_order')->where(['pre_id'=>$v['pre_id'],'gid'=>['in', $gid],'first_time'=>['gt',0]])->count();
            $presaleList[$k]['cheng'] = $this->model->table('pre_order')->where(['pre_id'=>$v['pre_id'],'order_state'=>30])->count();
            if($presaleList[$k]['zong'] <= 0 && (($v['pre_start_time'] > time() || $v['pre_end_time'] < time()) || $v['pre_status'] == 0)){
                $presaleList[$k]['del'] = 1;
            }
            if($presaleList[$k]['zong'] <= 0 && $v['pre_start_time'] > time()){
                $presaleList[$k]['edit'] = 1;
            }else{
                $presaleList[$k]['look'] = 1;
            }
            if($v['pre_start_time'] < time() && $v['pre_end_time'] > time() && $v['pre_status']!=0){
                $presaleList[$k]['stop'] = 1;
            }
            if($v['pre_start_time'] < time()){
                $presaleList[$k]['look_user'] = 1;
            }

        }
        Template::output('list',$presaleList);
        Template::output('show_page',$this->model->showpage());
        Template::output('cate',$this->cateList);
        Template::output('prestate',$this->preState);
        Template::showpage('presale.list');
    }
    /*
     * 查看活动详情
     */
    public function view()
    {
        $pre_id = intval($_GET['id']);
        $pre_info = $this->presale_model->getone(['pre_id'=>$pre_id]);
        if(!$pre_info){
                showMsg('活动不存在');
        }
        $pre_info['pre_start_time'] = date('Y-m-d H:i',$pre_info['pre_start_time']);
        $pre_info['pre_end_time'] = date('Y-m-d H:i',$pre_info['pre_end_time']);
        $pre_info['goods_list'] = $this->model->table('pre_goods')->where(['pre_id'=>$pre_id])->select();
//        dd($pre_info);
        Template::output('presale_info',$pre_info);
        Template::output('class_list',$this->cateList);
        Template::showpage('presale.view');
    }
    /*
     * 预售添加
     */
    public function add()
    {
        if(isset($_GET['id']) && !empty($_GET['id'])){
            //编辑页面
            $pre_id = intval($_GET['id']);
            $pre_info = $this->presale_model->getone(['pre_id'=>$pre_id]);
            if(!$pre_info){
                showMsg('活动不存在');
            }
            $pre_info['pre_start_time'] = date('Y-m-d H:i',$pre_info['pre_start_time']);
            $pre_info['pre_end_time'] = date('Y-m-d H:i',$pre_info['pre_end_time']);
            $pre_info['goods_list'] = $this->model->table('pre_goods')->where(['pre_id'=>$pre_id])->select();
//        dd($pre_info);
            Template::output('presale_info',$pre_info);
            Template::output('class_list',$this->cateList);
        }
        //添加页面
        Template::output('class_list', $this->cateList);
        Template::showpage('presale.add');
    }
    /*
     * 保存
     */
    public function save()
    {
        try{
            //验证分类
            $is_class = $this->model->table('pre_category')->where(['id'=>$_POST['class_id']])->find();
            if(!$is_class){
                throw new Exception('分类不存在');
            }
            //验证库存价格
            $goods_list = $this->model->table('goods')->where([
                'goods_commonid'=>$_POST['tuan_goods_id'],
                'goods_state'=>1,
                'goods_verify'=>1,
            ])->key('gid')->select();
            if(!$goods_list){
                throw new Exception('商品不存在');
            }
            $Agid = low_array_column($goods_list,'gid');
            $Agid_request = $_POST['gid'];
            sort($Agid,1);
            sort($Agid_request,1);
            if(serialize($Agid) != serialize($Agid_request)){
                throw new Exception('商品选择错误');
            }
            foreach($goods_list as $k=>$v){
                if($_POST['sld_ding_price'][$v['gid']] <= 0){
                    throw new Exception('定金不能小于0');
                }
                if($_POST['sld_ding_price'][$v['gid']] >= $_POST['sld_presale_price'][$v['gid']]){
                    throw new Exception('定金不能大于预售价');
                }
                if($_POST['sld_presale_price'][$v['gid']] <= 0){
                    throw new Exception('预售价不能小于0');
                }
                if($v['goods_price'] < $_POST['sld_presale_price'][$v['gid']]){
                        throw new Exception('预售价不能大于商品价格');
                }
                if($_POST['sld_stock'][$v['gid']] <= 0){
                    throw new Exception('预售库存不能小于0');
                }
                if(isset($_POST['id']) && !empty($_POST['id'])){
                    $storage = $this->model->table('pre_goods')->where(['pre_id'=>$_POST['id'],'gid'=>$v['gid']])->find();
                    if(($v['goods_storage']+$storage['goods_stock']) < $_POST['sld_stock'][$v['gid']]){
                        throw new Exception('预售库存不能大于商品库存');
                    }
                }else{
                    if($v['goods_storage'] < $_POST['sld_stock'][$v['gid']]){
                        throw new Exception('预售库存不能大于商品库存');
                    }
                }

            }
            //检查有没有重复活动商品
            $repeat = $this->check_goods_repeat($_POST['tuan_goods_id'],0,$_POST['id']);
            if(!$repeat){
                throw new Exception('活动已开始，不能编辑');
            }
            //检查上商品在不在其他活动里面
            $have_activity = Model('goods')->getOtherActivity($Agid_request,$_POST['tuan_goods_id'],strtotime($_POST['pre_start_time']),strtotime($_POST['pre_end_time']),'sld_presale');
            if($have_activity>0){
                throw new Exception('该商品已经参加其他优惠活动了！');
            }
            //拼装数据
            $data = [
                'pre_goods_commonid'=>$_POST['tuan_goods_id'],
                'vid'=>$_SESSION['vid'],
                'pre_category'=>$_POST['class_id'],
                'pre_pic'=>$_POST['tuan_image'],
                'pre_start_time'=>strtotime($_POST['pre_start_time']),
                'pre_end_time'=>strtotime($_POST['pre_end_time']),
                'pre_max_buy'=>$_POST['pre_max_buy'],
                'pre_limit_time'=>$_POST['pre_limit_time']
            ];
            //验证时间
            if($data['pre_start_time'] >= $data['pre_end_time']){
                throw new Exception('预售时间设置错误');
            }

            $this->model->begintransaction();
            if(isset($_POST['id']) && !empty($_POST['id'])){
                //编辑
                //如果有人参与不能编辑
                $pre_member_info = $this->model->table('pre_user')->where(['pre_id'=>$_POST['id'],'vid'=>$_SESSION['vid']])->find();
                if($pre_member_info){
                    throw new Exception('已经有人参与预售活动,不能编辑');
                }
                //编辑之前先清除之前的商品数据
                $presale_goods_list = $this->model->table('pre_goods')->where(['pre_id'=>$_POST['id']])->select();
                $presale_info = $this->presale_model->getone(['pre_id'=>$_POST['id']]);
                //解锁及库存
                foreach($presale_goods_list as $k=>$v){
                    //修改商城库存
                    $res = $this->model->table('goods')->where(['gid'=>$v['gid']])->update(['goods_storage'=>['exp','goods_storage + '.$v['goods_stock']]]);
                    if(!$res){
                        throw new Exception('编辑失败');
                    }
                }
                $res = Model('goods')->editGoodsCommonUnlock(['goods_commonid'=>$presale_info['pre_goods_commonid']]);
                if(!$res){
                    throw new Exception('编辑失败');
                }
                $res = $this->model->table('pre_goods')->where(['pre_id'=>$presale_info['pre_id']])->delete();
                if(!$res){
                    throw new Exception('编辑失败');
                }

                $pre_id = $this->presale_model->edit(['pre_id'=>$presale_info['pre_id'],'vid'=>$_SESSION['vid']],$data);
                if(!$pre_id){
                    throw new Exception('编辑失败');
                }
                //存入预售关联预售表
                foreach($Agid_request as $k=>$v){
                    $goods_data[] = [
                        'pre_id'=>$presale_info['pre_id'],
                        'gid'=>$v,
                        'goods_price'=>$goods_list[$v]['goods_price'],
                        'goods_name'=>$goods_list[$v]['goods_name'],
                        'goods_image'=>$goods_list[$v]['goods_image'],
                        'pre_deposit_price'=>$_POST['sld_ding_price'][$v],
                        'pre_sale_price'=>$_POST['sld_presale_price'][$v],
                        'goods_stock'=>$_POST['sld_stock'][$v],
                    ];
                    //修改商城库存
                    $res = $this->model->table('goods')->where(['gid'=>$v])->update(['goods_storage'=>['exp','goods_storage - '.$_POST['sld_stock'][$v]]]);
                    if(!$res){
                        throw new Exception('编辑失败');
                    }
                }
                //锁定商品
                $res = Model('goods')->editGoodsCommonLock(['goods_commonid'=>$_POST['tuan_goods_id']]);
                if(!$res){
                    throw new Exception('保存失败');
                }
                $result = $this->model->table('pre_goods')->insertAll($goods_data);
                if(!$result){
                    throw new Exception('添加失败');
                }
                $this->model->commit();
                //文件缓存
                dkcache('sld_presale');
                rkcache('sld_presale',true);
                showDialog('编辑成功', urlAddons('index'), 'succ');
            }else{
                //添加
                $pre_id = $this->presale_model->add($data);
                if(!$pre_id){
                    throw new Exception('添加失败');
                }
                //存入预售关联预售表
                foreach($Agid_request as $k=>$v){
                    $goods_data[] = [
                        'pre_id'=>$pre_id,
                        'gid'=>$v,
                        'goods_price'=>$goods_list[$v]['goods_price'],
                        'goods_name'=>$goods_list[$v]['goods_name'],
                        'goods_image'=>$goods_list[$v]['goods_image'],
                        'pre_deposit_price'=>$_POST['sld_ding_price'][$v],
                        'pre_sale_price'=>$_POST['sld_presale_price'][$v],
                        'goods_stock'=>$_POST['sld_stock'][$v],
                    ];
                    //修改商城库存
                    $res = $this->model->table('goods')->where(['gid'=>$v])->update(['goods_storage'=>['exp','goods_storage - '.$_POST['sld_stock'][$v]]]);
                    if(!$res){
                        throw new Exception('保存失败');
                    }
                }
                //锁定商品
                $res = Model('goods')->editGoodsCommonLock(['goods_commonid'=>$_POST['tuan_goods_id']]);
                if(!$res){
                    throw new Exception('保存失败');
                }
                $result = $this->model->table('pre_goods')->insertAll($goods_data);
                if(!$result){
                    throw new Exception('添加失败');
                }
                $this->model->commit();
                //文件缓存
                dkcache('sld_presale');
                rkcache('sld_presale',true);
                showDialog('发布成功', urlAddons('index'), 'succ');
            }
        } catch(Exception $e){
            $this->model->rollback();
            //文件缓存
            dkcache('sld_presale');
            rkcache('sld_presale',true);
            showDialog($e->getMessage());
            die;
        }
    }
    /*
     * 活动删除
     */
    public function delete()
    {
        $pre_id = intval($_GET['id']);
        try {
            $pre_info = $this->presale_model->getone(['pre_id' => $pre_id, 'vid' => $_SESSION['vid']]);
            if (!$pre_info) {
                throw new Exception('活动不存在');
            }
            $zong = $this->model->table('pre_user')->where(['pre_id'=>$pre_info['pre_id']])->count();
//            var_dump(($pre_info['pre_start_time'] < TIMESTAMP && $pre_info['pre_end_time'] > TIMESTAMP) , $pre_info['pre_status'] == 1);die;
            if(!(($pre_info['pre_start_time'] < TIMESTAMP && $pre_info['pre_end_time'] > TIMESTAMP) || $pre_info['pre_status'] == 0)){
                throw new Exception('活动进行中不能删除');
            }
            if($zong > 0){
                throw new Exception('活动已经有会员参与不能删除');
            }
            $this->model->begintransaction();
            //先判断回主商品库存再删除
            if($pre_info['pre_status'] == 1){
                $pre_goods_info = $this->model->table('pre_goods')->where(['pre_id'=>$pre_info['pre_id']])->select();
                foreach($pre_goods_info as $k=>$v) {
                    $res = $this->model->table('goods')->where(['gid'=>$v['gid']])->update(['goods_storage'=>['exp','goods_storage + '.$v['goods_stock']]]);
                    if(!$res){
                        throw new Exception('删除失败');
                    }
                }
                //解锁商品
                $res4 = Model('goods')->editGoodsCommonUnlock(['goods_commonid' => $pre_info['pre_goods_commonid']]);
                if (!$res4) {
                    throw new Exception('删除失败');
                }
            }
            $re1 = $this->model->table('pre_goods')->where(array('pre_id' => $pre_id))->delete();
            $re2 = $this->model->table('presale')->where(array('pre_id' => $pre_id))->delete();

            if ($re1 && $re2) {
                //文件缓存
                dkcache('sld_presale');
                rkcache('sld_presale', true);
                $this->model->commit();
                showDialog('删除成功', urlAddons('index'), 'succ');
            }else{
                throw new Exception('删除失败');
            }
        } catch (Exception $e) {
            $this->model->rollback();
            //文件缓存
            dkcache('sld_presale');
            rkcache('sld_presale',true);
            showDialog($e->getMessage());
        }
    }
    /*
     * 终止预售活动
     */
    public function stoppresale()
    {
        $pre_id = intval($_GET['id']);
        try{
            $this->model->begintransaction();
            $pre_info = $this->presale_model->getone(['pre_id'=>$pre_id,'vid'=>$_SESSION['vid']]);
            if(!$pre_info){
                throw new Exception('活动不存在');
            }
            //已经加入的会员直接退定金
            $orderList = $this->model->table('pre_order')->where(['order_state'=>20,'pre_id'=>$pre_id])->select();
            if($orderList){
                $model_pd = Model('predeposit');
                foreach($orderList as $k=>$v){
                    $res = $this->model->table('pre_order')->where(['order_state'=>20,'pre_id'=>$pre_id,'buyer_id'=>$v['buyer_id']])->update(['order_state'=>0]);
                    if(!$res){
                        throw new Exception('终止失败');
                    }
                    $data_pd = array();
                    $data_pd['member_id'] = $v['buyer_id'];
                    $data_pd['member_name'] = $v['buyer_name'];
                    $data_pd['amount'] = $v['goods_price'] * $v['goods_num'];
                    $data_pd['order_sn'] = $v['order_sn'];
                    $data_pd['lg_desc'] = '预售活动取消,订单号: '.$v['order_sn'];
                    $model_pd->changePd('huodong_cancel',$data_pd);
                }
            }
            //退回库存和解锁商品
            $pre_goods_info = $this->model->table('pre_goods')->where(['pre_id'=>$pre_info['pre_id']])->select();
            foreach($pre_goods_info as $k=>$v) {
                $res = $this->model->table('goods')->where(['gid'=>$v['gid']])->update(['goods_storage'=>['exp','goods_storage + '.$v['goods_stock']]]);
                if(!$res){
                    throw new Exception('终止失败');
                }
            }
            //解锁商品
            $res4 = Model('goods')->editGoodsCommonUnlock(['goods_commonid' => $pre_info['pre_goods_commonid']]);
            if (!$res4) {
                throw new Exception('终止失败');
            }
            //修改状态
            $return = $this->presale_model->edit(['pre_id'=>$pre_id],['pre_status'=>0]);
            if(!$return){
                throw new Exception('终止失败');
            }
            $this->model->commit();
            //文件缓存
            dkcache('sld_presale');
            rkcache('sld_presale',true);
            showDialog('终止成功', urlAddons('index'), 'succ');
        }catch(Exception $e){
            $this->model->rollback();
            //文件缓存
            dkcache('sld_presale');
            rkcache('sld_presale',true);
            showDialog($e->getMessage());
        }
    }
    /*
     * 参与活动的人
     */
    public function  team_list()
    {
        $pre_id = intval($_GET['id']);
        $tuan_model = M('pre_presale','presale');
        $model = model();
        $pin_info = $tuan_model->getone(array('id'=>$pre_id));
        $condition = ['pre_id'=>$pre_id];
//        $condition['order_state'] = ['neq',10];
        $condition['order_state'] = ['exp',' first_time > 0'];
        if(isset($_GET['tuan_state']) && !empty($_GET['tuan_state'])){
            if($_GET['tuan_state'] == 1){
//                $order_id = array_keys($model->table('pin_team_user_ladder')->where(['sld_pin_id'=>$pin_id])->key('sld_order_id')->select());
//                $condition['order_id'] = ['in',$order_id];
            }else{
                $condition['order_state'] = $_GET['tuan_state'];
            }
        }
        $list = $model->table('pre_order')->where($condition)->page(10)->order('order_id desc')->select();
        Template::output('group',$list);
        Template::output('show_page',$model->showpage());
        Template::showpage('pin.team_list');
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
        $goods_list = $model_goods->getGoodsOnlineList($condition, '*', 5,'gid desc',0,'goods_commonid');
        Template::output('goods_list', $goods_list);
        Template::output('show_page', $model_goods->showpage());
        Template::showpage('presale.goods', 'null_layout');
    }
    /*
     * 检查选中的商品是否可以使用
     */
    public function check_goods_repeat($gid=null,$ajax=1,$pin_id=0) {
        if(!$gid) {
            $gid = $_GET['gid'];
        }

        $tuan_model = $this->presale_model;

        $data = array();
        $data['result'] = true;

        //检查商品是否已经参加同时段活动
        $condition = array();
        if($pin_id>0){
            $condition['pre_id'] = array('neq',$pin_id);
        }
        $condition['pre_goods_commonid'] = $gid;
        $condition['pre_status'] = array('exp',' ( pre_status = 1 and pre_end_time >= '.TIMESTAMP.') ');
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
}