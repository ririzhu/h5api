<?php
/**
 * 队伍表
 *
 */

defined('DYMall') or exit('Access Invalid!');
class member_redCtl extends \app\v1\controller\Base
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index(){
        $this->red_list();
    }

    /**
     * 列表页
     *
     */
    public function red_list()
    {

        $model_red = M('red');


        if (isset($_GET['red_status']) && $_GET['red_status']!=='') { //使用状态筛选
            if($_GET['red_status']=='used'){  //使用过
                $condition['reduser_use'] = array( 'neq',0);
            }elseif($_GET['red_status']=='not_used'){  //未使用
                $condition['reduser_use'] = array( 'eq',0);
                $condition['redinfo_end'] = array( 'gt',TIMESTAMP);
            }elseif($_GET['red_status']=='expired'){  //过期
                $condition['redinfo_end'] = array( 'lt',TIMESTAMP);
            }
        }else{
            $condition['reduser_use'] = array( 'eq',0);
            $condition['redinfo_end'] = array( 'gt',TIMESTAMP);
        }
        $condition['reduser_uid'] = $_SESSION['member_id'];

        $red_list = $model_red->getRedUserList($condition,8);

        $red_list = $model_red->getUseInfo($red_list);
        
        $page_count = $model_red->gettotalpage();
        Template::output('list', $red_list);
        Template::output('show_page',$model_red->showpage(2)) ;

        $this->profile_menu('red_list');
        Template::output('menu_sign','myred');
        Template::output('menu_sign_url','index.php?app=red_list&sld_addons=red');
        Template::output('menu_sign1','member_red');
        Template::showpage('red.list');

    }

    //优惠券使用
    public function use_red(){
        $redinfo_id = $_GET['redinfo_id'];
        $model_red = M('red');
        $red_info = $model_red->getRedList(array('red_info.id'=>$redinfo_id));
        $gids = 0;
        $gc_ids = 0;
        if($red_info[0]['redinfo_type'] == 2){//是商品的时候
            $gids = $red_info[0]['redinfo_ids']?$red_info[0]['redinfo_ids']:0;
            $gc_ids = 0;
        }else if($red_info[0]['redinfo_type'] == 1){//是分类
            $gc_ids = $red_info[0]['redinfo_ids']?$red_info[0]['redinfo_ids']:0;
            $gids = 0;
        }
        $store_self = $red_info[0]['redinfo_self']?$red_info[0]['redinfo_self']:0;
        $red_vid = $red_info[0]['red_vid']?$red_info[0]['red_vid']:0;
        exit(json_encode(array('red_ids'=>$gids,'red_vid'=>$red_vid,'red_gc_id'=>$gc_ids,'store_self'=>$store_self)));
    }
    //优惠券转赠
    public function give_red(){
        Template::output('user_red',$_GET['user_red']);
        Template::showpage('give.red','null_layout');
    }
    //转赠
    public function edit_red_user(){
        $give_member_name = $_POST['give_member_name'];
        $user_red = $_POST['user_red'];
        $model_red = M('red');
        $model_member = Model('member');

        //查询用户领取的用户卷信息 red_user
        $user_red_info = $model_red->getUserRed(array('id'=>$user_red));
        if(!$user_red_info){
            exit(json_encode(array('state'=>255,'msg'=>'优惠券不存在')));
        }
        $red_id = $user_red_info['red_id'];

        //查询被转赠人的用户信息
        $member_info = $model_member->getMemberInfo(array('member_name'=>$give_member_name));
        if(!$member_info){
            exit(json_encode(array('state'=>255,'msg'=>'用户不存在')));
        }

        //查询被赠送人该优惠券的拥有数量
        $member_red = $model_red->getRedUserList(array('red_user.red_id'=>$red_id,'red_user.reduser_uid'=>$member_info['member_id']));
        //该用户已有该优惠券的数量
        $num = 0;
        if(!$member_red){
            $num = 0;
        }else{
            foreach ($member_red as $v){
                $num += 1;
            }
        }
        //查询该优惠券的信息
        $red_info = $model_red->getRedInfo(array('id'=>$red_id));
        //判断优惠券每人限领
        if($num >= $red_info['red_rach_max']){
            exit(json_encode(array('state'=>255,'msg'=>'该用户拥有优惠券已达最大限额')));
        }

        //修改领取优惠券表中的用户id
        $edit = $model_red->editUserRed(array('id'=>$user_red),array('reduser_uid'=>$member_info['member_id']));
        if($edit){
            exit(json_encode(array('state'=>200,'msg'=>'转赠成功')));
        }else{
            exit(json_encode(array('state'=>255,'msg'=>'转赠失败')));
        }
    }

    /**
     * 用户中心右边，小导航
     *
     * @param string	$menu_type	导航类型
     * @param string 	$menu_key	当前导航的menu_key
     * @param array 	$array		附加菜单
     * @return
     */
    private function profile_menu($menu_key='') {
        $menu_array = array(
            1=>array('menu_key'=>'red_list','menu_name'=>'我的优惠券','menu_url'=>'index.php?app=red_list&sld_addons=red'),
        );
        Template::output('member_menu',$menu_array);
        Template::output('menu_key',$menu_key);
    }

}