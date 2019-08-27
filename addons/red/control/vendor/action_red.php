<?php
/**
 * 商家优惠券
 */
defined('DYMall') or exit('Access Invalid!');

class action_redCtl extends BaseSellerCtl
{

    public function __construct()
    {
        parent::__construct();

        //检查优惠券功能是否开启
        if (!(C('sld_red') && C('red_isuse'))) {
            vendorMessage('优惠券活动没有开启', 'index.php?app=vendorcenter', '', 'error');
        }
    }

    /**
     * 默认显示团购列表
     **/
    public function index() {
        $this->red_list();
    }

    //列表
    public function red_list(){
        $red_model = M('red');

        $condition = $red_model->getRedCondition($_GET);

        $red_list = $red_model->getRedList($condition,10);

        foreach ($red_list as $k=>$v){
            $red_list[$k]['red_type_text'] = $red_model->redtype[$v['red_type']];
            $red_list[$k]['red_status_text'] = $red_model->redstatus[$v['red_status']];
            $red_list[$k]['redinfo_start_text'] = date('Y-m-d',$v['redinfo_start']);
            $red_list[$k]['redinfo_end_text'] = date('Y-m-d',$v['redinfo_end']);
        }

        Template::output('list',$red_list);
        Template::output('redtype',$red_model->redtype);
        Template::output('redstatus',$red_model->redstatus);
        Template::output('list',$red_list);
        Template::output('show_page',$red_model->showpage());

        self::profile_menu();
        Template::showpage('red.list');
    }

    //添加优惠券
    public function red_add(){
        $red_model = M('red');
        //后台只取前四个类型
        $type_option = array_slice($red_model->redtype,4,1);

        if($_POST){
            //拼装red表数据
            $red_pars['red_vid']            = $_SESSION['vid'];
            $red_pars['red_title']          = $_POST['red_title'];
            $red_pars['red_type']           = $_POST['red_type']?intval($_POST['red_type']):'0';
            $red_pars['red_front_show']     = 1;
            $red_pars['red_create']         = TIMESTAMP;
            $red_pars['red_receive_start']  = $_POST['red_receive_start'] ? strtotime($_POST['red_receive_start']) : '';
            $red_pars['red_receive_end']    = $_POST['red_receive_end'] ? strtotime($_POST['red_receive_end']) : '';
            $red_pars['red_limit']          = $_POST['red_limit'];
            $red_pars['red_rach_max']       = $_POST['red_rach_max'];
            if($insertid=$red_model->addRed($red_pars)){
                $redinfo_pars = array();
                //拼装red_info表数据
                $redinfo_pars['red_vid'] = $_SESSION['vid'];
                $redinfo_pars['redinfo_money'] = floatval($_POST['redinfo_money']);
                $redinfo_pars['redinfo_start'] = $_POST['redinfo_start'] ? strtotime($_POST['redinfo_start']) : '';
                $redinfo_pars['redinfo_end'] = $_POST['redinfo_start'] ? strtotime($_POST['redinfo_end']) : '';
                $redinfo_pars['redinfo_type'] = intval($_POST['redinfo_type']);
                $redinfo_pars['redinfo_ids'] = join(',',$_POST['redinfo_ids']);
                $redinfo_pars['redinfo_self'] = 0;
                $redinfo_pars['redinfo_store'] = 1;
                $redinfo_pars['redinfo_full'] = floatval($_POST['redinfo_full']);
                $redinfo_pars['redinfo_together'] = floatval($_POST['redinfo_together']);
                $redinfo_pars['red_id'] = $insertid;
                $redinfo_pars['redinfo_create'] = TIMESTAMP;
                $insertid2 = $red_model->addRedInfo($redinfo_pars);

                if (!$insertid2) {
                    showDialog('优惠券信息表添加失败');
                }
                $this->recordSellerLog('商户添加成功，编号'.$insertid);
                showDialog('优惠券添加成功', urlAddons('index'), 'succ');
            }else{
                showDialog('优惠券表添加失败');
            }
        }else{
            Template::output('type_option',$type_option);
            $all_cate = Model('goods_class')->getGoodsClass($_SESSION['vid']);
            Template::output('all_cate',$all_cate);
        }

        self::profile_menu();
        Template::showpage('red.add');
    }

    //查看优惠券
    public function red_view(){
        $red_model = M('red');
        $info = $red_model->getRedInfo(array('id'=>$_GET['id']));
        $info['red_receive_start_text'] = date('Y-m-d',$info['red_receive_start']);
        $info['red_receive_end_text'] = date('Y-m-d',$info['red_receive_end']);
        $info['info'] = $info['arr'][0];
        $info['info']['redinfo_start_text'] = date('Y-m-d',$info['info']['redinfo_start']);
        $info['info']['redinfo_end_text'] = date('Y-m-d',$info['info']['redinfo_end']);

        //查找分类
        if($info['info']['redinfo_type']) {
            $cate_str = array();
            $ids = explode(',',$info['info']['redinfo_ids']);
            $all_cate = Model('goods_class')->getGoodsClass($_SESSION['vid']);
            foreach ($all_cate as $k=>$v){
                if(in_array($v['gc_id'],$ids)){
                    $cate_str[] = $v['gc_name'];
                }
            }
            $info['cate_str'] = '指定分类：'.join('、',$cate_str);
        }

        Template::output('info',$info);
        self::profile_menu();
        Template::showpage('red.view');
    }

    //优惠券发放列表
    public function red_user_list(){
        $red_model = M('red');

        $pageSize = intval($_GET['pageSize'] > 0 ? $_GET['pageSize'] : 10);
        $_GET['pn'] = intval($_GET['currentPage'] > 0 ? $_GET['currentPage'] : 1);

        $condition = array();
        $condition['red.id'] = $_GET['id'];
//        $condition['red.red_vid'] = $_SESSION['vid'];
        if ($_GET['red_status']==1) { //活动状态不筛选
            $condition['reduser_use'] = 0;
        }elseif($_GET['red_status']==='0'){
            $condition['reduser_use'] = array('neq',0);
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
            $red_list[$k]['reduser_use_text'] = $v['reduser_use']!=='0'?date('Y-m-d H:i:s',$v['reduser_use_text']):0;
        }

        Template::output('list',$red_list);
        Template::output('redtype',$red_model->redtype);
        Template::output('redstatus',$red_model->redused);
        Template::output('list',$red_list);
        Template::output('show_page',$red_model->showpage());

        self::profile_menu();
        Template::showpage('red.user.list');

    }

    //作废优惠券
    public function red_abolish()
    {
        $red_model = M('red');
        $red_id = $_GET['id'];
        $condition['id'] = array('in', $red_id);

        $update['red_status'] = 0;

        $result = $red_model->table('red')->where($condition)->update($update);
        if ($result) {
            $this->recordSellerLog('作废优惠券，编号' . $red_id);
            showDialog('操作成功', urlAddons('index'), 'succ');
        } else {
            showDialog('操作失败');
        }
    }

    //删除优惠券
    public function red_delete(){
        $red_model = M('red');
        $red_id = $_GET['id'];
        $condition['id'] = array('in', $red_id);

        $update['red_status'] = 0;
        $update['red_delete'] = 1;

        $result = $red_model->table('red')->where($condition)->update($update);
        if($result) {
            $this->recordSellerLog('删除优惠券，编号' . $red_id);
            showDialog('操作成功', urlAddons('index'), 'succ');
        } else {
            showDialog('操作失败');
        }
    }
    /**
     * 优惠券
     */
    public function bundling_add_goods() {
        /**
         * 实例化模型
         */
        $model_goods =Model('goods');

        // where条件
        $where = array ();
        $where['vid'] = $_SESSION['vid'];
        if (intval($_GET['stc_id']) > 0) {
            $where['goods_stcids'] = array('like', '%,' . intval($_GET['stc_id']) . ',%');
        }
        if (trim($_GET['keyword']) != '') {
            $where['goods_name'] = array('like', '%' . trim($_GET['keyword']) . '%');
        }

        $goods_list = $model_goods->getGoodsOnlineList($where, '*', 8);
        Template::output('show_page', $model_goods->showpage());
        Template::output('goods_list', $goods_list);

        /**
         * 商品分类
         */
        $store_goods_class = Model('my_goods_class')->getClassTree(array('vid' => $_SESSION['vid'], 'stc_state' => '1'));
        Template::output('innercategory', $store_goods_class);

        Template::showpage('p_suite.add_goods', 'null_layout');
    }

    /**
     * 用户中心右边，小导航
     *
     * @param string 	$menu_key	当前导航的menu_key
     * @param array 	$array		附加菜单
     * @return
     */
    private function profile_menu() {
        $menu_array[0] = array('index'=>'优惠券列表');
        $menu_array[1] = array(
            'red_add'=>'添加优惠券',
            'red_view'=>'查看优惠券',
            'red_user_list'=>'发放列表'
        );
        AddonsBase::get_proMenu($menu_array);
    }
}
