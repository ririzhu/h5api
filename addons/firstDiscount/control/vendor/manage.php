<?php
/**
 * 商家中心管理
 */
defined('DYMall') or exit('Access Invalid!');

class manageCtl extends BaseSellerCtl {
    function index(){

        $model = Model('goods');
        $where['g.vid'] = $_SESSION['vid'];
        $where['g.goods_state'] = 1;
        $where['g.goods_verify'] = 1;
        if($_GET['tuan_name']){
            $where['g.goods_name'] = ['like','%'.$_GET['tuan_name'].'%'];
        }

        $list = $model->table('goods_common,first_discount,goods')->alias('gc,fd,g')->join('left')
            ->on('gc.goods_commonid=fd.cid,gc.goods_commonid=g.goods_commonid')->field('gc.*,fd.reduction,g.gid')->group('gc.goods_commonid')->page('20')
            ->where($where)->order('g.goods_commonid desc')->select();

        Template::output('list',$list);
        Template::output('show_page',$model->showpage());

        Template::output('money',$model->table('first_discount')->where(['vid'=>$_SESSION['vid'],'cid'=>0])->field('reduction')->one());

        self::profile_menu('index');
        Template::showpage('manage.list');

    }

    function toggle(){
        $cid = $_GET['cid'];

        $model = Model('goods')->table('first_discount');

        $where = ['vid'=>$_SESSION['vid'],'cid'=>$cid];

        $have = $model->where($where)->find();

        if($have){
            $model->where($where)->delete();
            exit('0');
        }else{
            $par = $where;
            $par['reduction'] = floatval($_GET['money']);
            $model->insert($par);
            exit('1');
        }
    }

    function set(){
        $cid = 0;

        $model = Model('goods')->table('first_discount');

        $where = ['vid'=>$_SESSION['vid'],'cid'=>$cid];

        $have = $model->where($where)->find();

        if($have){
            unset($where['cid']);
            $re = $model->where($where)->update(['reduction'=>floatval($_GET['money'])]);
        }else{
            $par = $where;
            $par['reduction'] = floatval($_GET['money']);
            $par['vid'] = $_SESSION['vid'];
            $re = $model->insert($par);
        }


        echo intval($re);
    }


    function iframe(){



        Template::showpage('manage.iframe');
    }

    /**
     * 用户中心右边，小导航
     *
     * @param string 	$menu_key	当前导航的menu_key
     * @param array 	$array		附加菜单
     * @return
     */
    private function profile_menu() {
        $menu_array[0] = array('index'=>'首单优惠');
        $menu_array[1] = array(
            'log'=>'参与列表'
        );
        AddonsBase::get_proMenu($menu_array);
    }
}