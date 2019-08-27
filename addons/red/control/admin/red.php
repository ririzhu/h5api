<?php
/**
 * 优惠券管理列表
 *
 */
defined('DYMall') or exit('Access Invalid!');

class redCtl extends SystemCtl{

    const EXPORT_SIZE = 5000;

    public function __construct()
    {
        parent::__construct();
        //开启当前活动
        if ($_GET['red_open'] == 1){
            //更改数据库
            $model_setting = Model('setting');
            $result = $model_setting->updateSetting(array('red_isuse'=>1));
            if($result){
                echo json_encode(array('state'=>200,'msg'=>'操作成功'));die;
            }else{
                echo json_encode(array('state'=>255,'msg'=>'活动开启失败'));die;
            }
        }
        //检查当前活动功能是否开启
        if (C('promotion_allow') != 1){
            echo json_encode(array('state'=>265,'msg'=>'当前活动未开启'));die;
        }
    }

    //设置到期提醒 （天）
    public function set_notice(){
        if($_POST['red_notice']){
            $model_setting = Model('setting');
            $result = $model_setting->updateSetting(array('red_notice'=>intval($_POST['red_notice'])));
            if($result){
                echo json_encode(array('state'=>200,'msg'=>'操作成功'));die;
            }else{
                echo json_encode(array('state'=>255,'msg'=>'操作失败'));die;
            }
        }else{
            echo json_encode(array('option'=>'1,3,7','red_notice' => C('red_notice')));
        }
    }

    //列表
    public function red_list(){
        $red_model = M('red');

        $pageSize = intval($_GET['pageSize'] > 0 ? $_GET['pageSize'] : 10);
        $_GET['pn'] = intval($_GET['currentPage'] > 0 ? $_GET['currentPage'] : 1);

        $condition = $red_model->getRedCondition($_GET);

        $red_list = $red_model->getRedList($condition, $pageSize);
        foreach ($red_list as $k=>$v){
            $red_list[$k]['red_type_text'] = $red_model->redtype[$v['red_type']];
            $red_list[$k]['red_status_text'] = $v['redinfo_end']<TIMESTAMP ? '过期' : $red_model->redstatus[$v['red_status']];
            $red_list[$k]['redinfo_start_text'] = $v['redinfo_start'] ? date('Y-m-d H:i:s',$v['redinfo_start']) : '';
            $red_list[$k]['redinfo_end_text'] = $v['redinfo_end'] ? date('Y-m-d H:i:s',$v['redinfo_end']) : '';

            $red_list[$k]['red_receive_start_text'] = $v['red_receive_start'] ? date('Y-m-d H:i:s',$v['red_receive_start']) : '';
            $red_list[$k]['red_receive_end_text'] = $v['red_receive_end'] ? date('Y-m-d H:i:s',$v['red_receive_end']) : '';
            $red_list[$k]['red_create_text'] = $v['red_create'] ? date('Y-m-d H:i:s',$v['red_create']) : '';


        }
        echo json_encode(array('list' => $red_list, 'pagination' => array('current' => $_GET['pn'], 'pageSize' => $pageSize, 'total' => intval($red_model->gettotalnum())),'searchlist'=>$_GET));
    }
    /*
     * 等级列表
     */
    public function grade_list()
    {
        $grade_model = Model('grade');
        $grade_list = $grade_model->getlist([],'*','','grade_value asc');
        $grade_list = $grade_list?:[];
        echo json_encode(['status'=>200,'msg'=>$grade_list]);die;
    }

    //添加优惠券
    public function red_add(){
        $red_model = M('red');
        //后台只取前四个类型
        $type_option = array_slice($red_model->redtype,0,4);

        if($_POST){
            //拼装red表数据
            $red_pars['red_title']          = $_POST['red_title'];
            $red_pars['red_type']           = $_POST['red_type'];
            $red_pars['red_front_show']     = $_POST['red_front_show'] ? $_POST['red_front_show'] : 0;
            $red_pars['red_create']         = TIMESTAMP;
            $red_pars['red_receive_start']  = $_POST['red_receive_start'] ? strtotime($_POST['red_receive_start']) : '';
            $red_pars['red_receive_end']    = $_POST['red_receive_end'] ? strtotime($_POST['red_receive_end']) : '';
            $red_pars['red_limit']          = $_POST['red_limit'];
            $red_pars['red_rach_max']       = $_POST['red_rach_max'];

            //如果是自动注册 加发放数量限制 发放时间
            if($red_pars['red_type']==2 || $red_pars['red_type']==4){
                if($red_model->where(array('red_type'=>$red_pars['red_type'],'red_status'=>1))->count()>0){
                    echo json_encode(array('state'=>255,'msg'=>$type_option[$red_pars['red']].'同时，只能添加一个。如想再添加请作废现有优惠券'));die;
                }
            }

            $red_model->beginTransaction();

            if($insertid=$red_model->addRed($red_pars)){
                foreach ($_POST['arr'] as $v) {

                    $redinfo_pars = array();
                    //拼装red_info表数据
                    $redinfo_pars['redinfo_money'] = floatval($v['redinfo_money']);


                    if($v['redinfo_full']!=0 && $redinfo_pars['redinfo_money'] > floatval($v['redinfo_full'])){
                        $red_model->rollback();
                        echo json_encode(array('state'=>255,'msg'=>"优惠券面值".$redinfo_pars['redinfo_money']."，不能大于最小订单金额限制：".floatval($v['redinfo_full'])));die;
                    }

                    $redinfo_pars['redinfo_start'] = $v['redinfo_start'] ? strtotime($v['redinfo_start']) : '';
                    $redinfo_pars['redinfo_end'] = $v['redinfo_start'] ? strtotime($v['redinfo_end']) : '';
                    $redinfo_pars['redinfo_type'] = intval($v['redinfo_type']);
                    $redinfo_pars['redinfo_ids'] = ( is_array($v['redinfo_ids']) && !empty($v['redinfo_ids']) ) ? implode(',', $v['redinfo_ids']) : '';
                    $redinfo_pars['redinfo_self'] = intval($v['redinfo_self']) ? intval($v['redinfo_self']) : 0;
                    $redinfo_pars['redinfo_store'] = intval($v['redinfo_store']) ? intval($v['redinfo_store']) : 0;
                    $redinfo_pars['redinfo_full'] = floatval($v['redinfo_full']);
                    $redinfo_pars['redinfo_together'] = floatval($v['redinfo_together']);
                    $redinfo_pars['red_id'] = $insertid;
                    $redinfo_pars['red_cid'] = $this->admin_info['admin_sld_city_site_id'];
                    $redinfo_pars['redinfo_create'] = TIMESTAMP;
                    $insertid2 = $red_model->addRedInfo($redinfo_pars);

                    if (!$insertid2) {
                        $red_model->rollback();
                        echo json_encode(array('state' => 255, 'msg' => '优惠券信息表添加失败1'));
                        die;
                    }
                }
                $this->log('添加优惠券，编号'.$insertid,null);
                echo json_encode(array('state' => 200, 'msg' => '优惠券添加成功'));
                $red_model->commit();
                die;
            }else{
                $red_model->rollback();
                echo json_encode(array('state'=>255,'msg'=>'优惠券表添加失败2'));die;
            }
        }else{
            echo json_encode(array('type_option'=>$type_option));
        }
    }

    //查看优惠券
    public function red_view(){
        $red_model = M('red');
        $red_data = $red_model->getRedInfo(array('id'=>$_GET['id']));

        $red_data['red_receive_end_text'] = $red_data['red_receive_end'] ? date("Y-m-d H:i",$red_data['red_receive_end']) : '';
        $red_data['red_receive_start_text'] = $red_data['red_receive_start'] ? date("Y-m-d H:i",$red_data['red_receive_start']) : '';
        if (isset($red_data['arr']) && !empty($red_data['arr'])) {
            foreach ($red_data['arr'] as $key => $value) {
                $red_data['arr'][$key]['redinfo_create_text'] = $value['redinfo_create'] ? date("Y-m-d H:i:s",$value['redinfo_create']) : '';
                $red_data['arr'][$key]['redinfo_start_text'] = $value['redinfo_start'] ? date("Y-m-d H:i:s",$value['redinfo_start']) : '';
                $red_data['arr'][$key]['redinfo_end_text'] = $value['redinfo_end'] ? date("Y-m-d H:i",$value['redinfo_end']) : '';
                $red_data['arr'][$key]['redinfo_ids'] = $value['redinfo_ids'] ? explode(',', $value['redinfo_ids']) : '';
            }
        }
        echo json_encode(array('data'=>$red_data));die;
    }

    //派发优惠券
    public function red_send(){
        $_POST['id'] = intval($_GET['id']) ? intval($_GET['id']) : intval($_POST['id']);
        $_POST['grade'] = isset($_GET['grade']) ? intval($_GET['grade']) : intval($_POST['grade']);
        $_POST['is_repeat'] = $_GET['is_repeat'] ? $_GET['is_repeat'] : $_POST['is_repeat'];
        $_POST['ids'] = $_GET['ids'] ? $_GET['ids'] : $_POST['ids'];
        if($_POST){
            $red_model = M('red');
            $id = intval($_POST['id']);
            //判断能否派发
            if( $red = $red_model->getCanSend($id)){
                //是否给发过的用户发优惠券
                $is_repeat = intval($_POST['is_repeat'])==1?$red['id']:null;
                if($_POST['grade']){
                    $grade = intval($_POST['grade']);
                    //根据等级获取用户id
                    //取出所有等级列表,确认查找范围
                    $grade_model = Model('grade');
                    $model = Model();
                    $start = 0;
                    $end = 0;
                    $grade_list = $grade_model->getlist([],'*','','grade_value asc');
                    foreach($grade_list as $k=>$v){
                        if($v['id'] == $grade){
                            $end = $v['grade_value'];
                            if(isset($grade_list[$k-1])){
                                $start = $grade_list[$k-1]['grade_value'];
                            }else{
                                $start = 0;
                            }
                        }
                    }
                    //如果只有一个等级的情况
                    if(count($grade_list) == 1 && $grade == $grade_list[0]['id']){
                        $member_id = $model->table('member')->where(1)->limit(false)->field('member_id')->key('member_id')->select();
                    }else{
                    //如果取的是最后一个等级的情况
                        if($grade == end($grade_list)['id']){
                            array_pop($grade_list);
                            $member_id = $model->table('member')->where([
                                'member_growthvalue'=>['gt',end($grade_list)['grade_value']]
                            ])->field('member_id')->key('member_id')->select();
                        }else{
                            //取的中间的等级
                            $member_id = $model->table('member')->where([
                                'member_growthvalue'=>['exp',"member_growthvalue >{$start} and member_growthvalue <= {$end}"]
                            ])->field('member_id')->key('member_id')->select();
                        }
                    }

//                    $user_ids = $red_model->getUserIdsByGrade($grade,$is_repeat,$id);
                    $user_ids = implode(',',array_keys($member_id));
                }else {
                    //通过会员id发送
                    $user_ids = $_POST['ids'];
                }
                //把该优惠券每人限领多少取出来
                $red_info = $red_model->getRedInfo(array('id'=>$id));

                //会员id全重  为真：排除已领取
                if($is_repeat) {
                    $limit = 1;
                }else{
                    $limit = $red_info['red_rach_max'];
                }

                //过滤超过数量的会员id
                $condition['u.member_id'] = array('in',$user_ids);
                $ids = $red_model->table('red_user,member')->join('right')->alias('r,u')->on('r.reduser_uid=u.member_id and r.red_id='.$id)
                    ->where($condition)->field('member_id,count( r.reduser_uid ) AS yiling')->group('u.member_id')->having('yiling < '.$limit)->key('member_id')->select();

                $user_ids = array_keys($ids);

                if(count($user_ids)>$red_info['red_limit']){
                    echo json_encode(array('state'=>265,'msg'=>"派发失败，要派发的用户(".count($user_ids).")超过发放数量限制(".$red_info['red_limit'].")"));die;
                }

                if(count($user_ids) == 0 ){
                    echo json_encode(array('state'=>265,'msg'=>'派发失败，无用户'));die;
                }
                $re = $red_model->SendRed($user_ids,$red);
                if($re){
                    $this->log('派发优惠券成功，'.count($user_ids).'条',null);
                    echo json_encode(array('state'=>200,'msg'=>'发送成功,本次发放'.count($user_ids).'个会员'));die;
                }else{
                    echo json_encode(array('state'=>265,'msg'=>'派发失败，数据库插入错误'));die;
                }
            }else{
                echo json_encode(array('state'=>265,'msg'=>'派发失败，优惠券不存在'));die;
            }
        }
    }

    //优惠券发放列表
    public function red_user_list(){
        $red_model = M('red');

        $pageSize = intval($_GET['pageSize'] > 0 ? $_GET['pageSize'] : 10);
        $_GET['pn'] = intval($_GET['currentPage'] > 0 ? $_GET['currentPage'] : 1);

        $condition = array();
        $condition['red.id'] = $_GET['id'];
        $condition['red.red_cid'] = $this->admin_info['admin_sld_city_site_id'];
        if ($_GET['red_status']) { //活动状态不筛选
            $condition['red_status'] = $_GET['red_status'];
        }
        if ($_GET['red_title']) {  //活动名称筛选
            $condition['red_title'] = array('like', '%' . $_GET['red_title'] . '%');
        }

        $red_list = $red_model->getRedUserList($condition, $pageSize);
        foreach ($red_list as $k=>$v){
            $red_list[$k]['red_type_text'] = $red_model->redtype[$v['red_type']];
            $red_list[$k]['red_status_text'] = $red_model->redstatus[$v['red_status']];
            $red_list[$k]['reduser_used_text'] = $red_model->redused[$v['reduser_use']==='0'];
            $red_list[$k]['redinfo_start_text'] = date('Y-m-d H:i:s',$v['redinfo_start']);
            $red_list[$k]['redinfo_end_text'] = date('Y-m-d H:i:s',$v['redinfo_end']);
            $red_list[$k]['reduser_use_text'] = $v['reduser_use']!=='0'?date('Y-m-d H:i:s',$v['reduser_use']):'--';
        }
        echo json_encode(array('list' => $red_list, 'pagination' => array('current' => $_GET['pn'], 'pageSize' => $pageSize, 'total' => intval($red_model->gettotalnum())),'searchlist'=>$_GET));

    }

    //作废优惠券
    public function red_abolish(){
        $red_model = M('red');
        $red_id = $_POST['id'];
        $condition['id'] = array('in', $red_id);

        $update['red_status'] = 0;

        $result = $red_model->table('red')->where($condition)->update($update);
        if($result) {
            $this->log('作废优惠券，编号'.$red_id,null);
            echo json_encode(array('state'=>200,'msg'=>L('操作成功')));
        } else {
            echo json_encode(array('state'=>255,'msg'=>L('操作失败')));
        }    }

    //删除优惠券
    public function red_delete(){
        $red_model = M('red');
        $red_id = $_POST['id'];
        $condition['id'] = array('in', $red_id);

        $update['red_status'] = 0;
        $update['red_delete'] = 1;

        $result = $red_model->table('red')->where($condition)->update($update);

        if($result) {
            $this->log('删除优惠券，编号'.$red_id,null);
            echo json_encode(array('state'=>200,'msg'=>L('操作成功')));
        } else {
            echo json_encode(array('state'=>255,'msg'=>L('操作失败')));
        }
    }

    //判断 优惠券类型是否可选择
    public function red_canType(){
        $type= $_GET['type'];
        //如果是自动注册 加发放数量限制 发放时间
        if($type==2 || $type==4){
            if(M('red')->table('red')->where(array('red_type'=>$type,'red_status'=>1))->count()>0){
                echo json_encode(array('state'=>255,'msg'=>'不可以选择'));die;
            }else{
                echo json_encode(array('state'=>200,'msg'=>'可选择'));die;
            }
        }
    }

    /**
     * 导出中奖记录
     *
     */
    public function sldSendRedExcelExport(){
        $red_model = M('red');

        $pageSize = intval($_GET['pageSize'] > 0 ? $_GET['pageSize'] : 10);
        $_GET['pn'] = intval($_GET['currentPage'] > 0 ? $_GET['currentPage'] : 1);

        $condition = array();
        $condition['red.id'] = $_GET['id'];
        $condition['red.red_cid'] = $this->admin_info['admin_sld_city_site_id'];
        if ($_GET['red_status']) { //活动状态不筛选
            $condition['red_status'] = $_GET['red_status'];
        }
        if ($_GET['red_title']) {  //活动名称筛选
            $condition['red_title'] = array('like', '%' . $_GET['red_title'] . '%');
        }

        $red_list = $red_model->getRedUserList($condition, $pageSize);
        foreach ($red_list as $k=>$v){
            $red_list[$k]['red_type_text'] = $red_model->redtype[$v['red_type']];
            $red_list[$k]['red_status_text'] = $red_model->redstatus[$v['red_status']];
            $red_list[$k]['reduser_used_text'] = $red_model->redused[$v['reduser_use']==='0'];
            $red_list[$k]['redinfo_start_text'] = date('Y-m-d H:i:s',$v['redinfo_start']);
            $red_list[$k]['redinfo_end_text'] = date('Y-m-d H:i:s',$v['redinfo_end']);
            $red_list[$k]['reduser_use_text'] = $v['reduser_use']!=='0'?date('Y-m-d H:i:s',$v['reduser_use']):'--';
        }

        if (!is_numeric($_GET['pn'])){
            $count = count($red_list);
            $array = array();
            if ($count > self::EXPORT_SIZE ){	//显示下载链接
                $page = ceil($count/self::EXPORT_SIZE);
                for ($i=1;$i<=$page;$i++){
                    $limit1 = ($i-1)*self::EXPORT_SIZE + 1;
                    $limit2 = $i*self::EXPORT_SIZE > $count ? $count : $i*self::EXPORT_SIZE;
                    $array[$i] = $limit1.' ~ '.$limit2 ;
                }
                Template::output('list',$array);
                Template::output('murl','index.php?app=draw&mod=red_user_list&addons=draw&id='.$_GET['id']);
                Template::showpage('export.excel');
            }else{	//如果数量小，直接下载
                $this->createExcel($red_list);
            }
        }else{	//下载
            $this->createExcel($red_list);
        }
    }

    /**
     * 生成导出预存款充值excel
     *
     * @param array $data
     */
    private function createExcel($data = array()){
        Language::read('export');
        import('libraries.excel');
        $excel_obj = new Excel();
        $excel_data = array();
        //设置样式
        $excel_obj->setStyle(array('id'=>'s_title','Font'=>array('FontName'=>'宋体','Size'=>'12','Bold'=>'1')));
        //header
        $excel_data[0][] = array('styleid'=>'s_title','data'=>'优惠券名称');
        $excel_data[0][] = array('styleid'=>'s_title','data'=>'优惠券金额');
        $excel_data[0][] = array('styleid'=>'s_title','data'=>'会员名称');
        $excel_data[0][] = array('styleid'=>'s_title','data'=>'领取时间');
        $excel_data[0][] = array('styleid'=>'s_title','data'=>'使用时间');
        $excel_data[0][] = array('styleid'=>'s_title','data'=>'优惠券类型');
        $excel_data[0][] = array('styleid'=>'s_title','data'=>'使用状态');

        foreach ((array)$data as $k=>$v){
            $tmp = array();
            $tmp[] = array('data'=>$v['red_title']);
            $tmp[] = array('data'=>$v['redinfo_money']);
            $tmp[] = array('data'=>$v['member_name']);
            $tmp[] = array('data'=>$v['reduser_get_text']);
            $tmp[] = array('data'=>$v['reduser_use_text']);
            $tmp[] = array('data'=>$v['red_type_text']);
            $tmp[] = array('data'=>$v['reduser_used_text']);
            $excel_data[] = $tmp;
        }
        $excel_data = $excel_obj->charset($excel_data,CHARSET);
        $excel_obj->addArray($excel_data);
        $excel_obj->addWorksheet($excel_obj->charset(L('exp_yc_yckcz'),CHARSET));
        $excel_obj->generateXML($excel_obj->charset('优惠券发放记录',CHARSET).$_GET['pn'].'-'.date('Y-m-d-H',time()));
    }
}