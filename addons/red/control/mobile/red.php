<?php
/**
 * 队伍表
 *
 */


defined('DYMall') or exit('Access Invalid!');
class redCtl extends mobileHomeCtl
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 列表页
     *
     */
    public function red_list()
    {
        $key = $_GET['key'];
        if (!empty($key)) {
            $member_info = array();
            $model_mb_user_token = Model('mb_user_token');
            //判断是否收藏
            $member_info = $model_mb_user_token->getMbUserTokenInfoByToken($key);
        } else {
            return array('error' => '请重新登陆');
        }

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
        }
        $condition['reduser_uid'] = $member_info['member_id'];

        $red_list = $model_red->getRedUserList($condition,$_GET['page']);

        foreach ($red_list as $k=>$v){
            if($_GET['red_status']=='used'){
                $red_list[$k]['sheng'] = '';
            }
        }

        $red_list = $model_red->getUseInfo($red_list);

        $page_count = $model_red->gettotalpage();


        output_data(array('red' => $red_list?$red_list:[]), mobile_page($page_count));
    }

    /**
     * 领券中心页
     *
     */
    public function red_get_list()
    {
        $key = $_GET['key'];
        if (!empty($key)) {
            $member_info = array();
            $model_mb_user_token = Model('mb_user_token');
            //判断是否收藏
            $member_info = $model_mb_user_token->getMbUserTokenInfoByToken($key);
        } else {
            $member_info['member_id'] = 0;
        }

        $model_red = M('red');
//        $condition['red.red_type'] = array('neq','3');
        $condition['red_status'] = 1;
        $condition['red_front_show'] = 1;
        $condition['red_receive_start'] = array('lt',TIMESTAMP);
        $condition['red_receive_end'] = array('gt',TIMESTAMP);

        $red_list = $model_red->getRedLingList($member_info['member_id'],$condition,$_GET['page']);

        $red_list = $model_red->getUseInfo($red_list);

        $page_count = $model_red->gettotalpage();


        output_data(array('red' => $red_list?$red_list:[]), mobile_page($page_count));
    }

    /**
     * 领取优惠券
     *
     */
    public function send_red()
    {
        $key = $_GET['key'];
        if (!empty($key)) {
            $member_info = array();
            $model_mb_user_token = Model('mb_user_token');
            //判断是否收藏
            $member_info = $model_mb_user_token->getMbUserTokenInfoByToken($key);
        } else {
            output_data(array('msg'=>'请登陆再领取'));
        }
        $red_id = $_GET['red_id'];

        $msg = M('red')->ling_red($member_info['member_id'],$red_id);

        output_data($msg);

    }
}