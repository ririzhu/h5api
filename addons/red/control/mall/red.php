<?php
/**
 * 队伍表
 *
 */

defined('DYMall') or exit('Access Invalid!');
class red extends \app\V1\controller\Base
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index(){
        $this->red_get_list();
    }


    /**
     * 领券中心页
     *
     */
    public function red_get_list()
    {

        $model_red = M('red');
        $condition['red.red_type'] = array('neq','3');
        $condition['red_status'] = 1;
        $condition['red_front_show'] = 1;
        $condition['red_receive_start'] = array('lt',TIMESTAMP);
        $condition['red_receive_end'] = array('gt',TIMESTAMP);
        if(isset($_GET['red_id'])){
            $condition['red.id'] = $_GET['red_id'];
        }


        $red_list = $model_red->getRedLingList($_SESSION['member_id'],$condition,$_GET['page']);

        $red_list = $model_red->getUseInfo($red_list);


        //Template::output('list', $red_list);
        //Template::output('show_page',$model_red->showpage(2)) ;

        //Template::showpage('red.get.list');

    }

    /**
     * 领取优惠券
     *
     */
    public function send_red()
    {

        $red_id = $_GET['red_id'];

        if(!$_SESSION['member_id']){
            exit('请先登录再领取！');
        }

        $msg = M('red')->ling_red($_SESSION['member_id'],$red_id);

        exit($msg);

    }

    /**
     * 加载买家发票列表，最多显示10条
     *
     */
    public function loadred() {
        $model_buy = Model('buy');

        $condition = array();
        if ($model_buy->buyDecrypt($_GET['vat_hash'], $_SESSION['member_id']) == 'allow_vat') {
        } else {
            Template::output('vat_deny',true);
            $condition['inv_state'] = 1;
        }
        $condition['member_id'] = $_SESSION['member_id'];

        $model_inv = Model('invoice');
        //如果传入ID，先删除再查询
        if (intval($_GET['del_id']) > 0) {
            $model_inv->delInv(array('inv_id'=>intval($_GET['del_id']),'member_id'=>$_SESSION['member_id']));
        }
        $list = $model_inv->getInvList($condition,10);
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                if ($value['inv_state'] == 1) {
                    $list[$key]['content'] = '普通发票'.' '.$value['inv_title'].' '.$value['inv_content'].' '.$value['inv_code'];
                } else {
                    $list[$key]['content'] = '增值税发票'.' '.$value['inv_company'].' '.$value['inv_code'].' '.$value['inv_reg_addr'];
                }
            }
        }
        Template::output('inv_list',$list);
        Template::showpage('buy_red.load','null_layout');
    }

}