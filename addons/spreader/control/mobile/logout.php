<?php
/**
 * 注销
 *
 *
 *
 *
 */

defined('DYMall') or exit('Access Invalid!');

class ssys_mobileMemberCtl extends mobileCtl
{

    protected $member_info = array();

    public function __construct()
    {
        parent::__construct();
        $agent = $_SERVER['HTTP_USER_AGENT'];
        if (strpos($agent, "MicroMessenger") && $_GET["app"] == 'auto') {
            $this->appId = C('app_weixin_appid');
            $this->appSecret = C('app_weixin_secret');;
        } else {
            $model_mb_user_token = M('ssys_mb_user_token','spreader');
            $key = $_REQUEST['ssys_key'];
            $mb_user_token_info = $model_mb_user_token->getMbUserTokenInfoByToken($key);
            if (empty($mb_user_token_info)) {
                output_error('请登录', array('login' => '0'));
            }

            $model_member = M('ssys_member');
            $this->member_info = $model_member->getMemberInfoByID($mb_user_token_info['member_id']);
            if(empty($this->member_info)) {
                output_error('请登录', array('login' => '0'));
            } else {
                $this->member_info['client_type'] = $mb_user_token_info['client_type'];
                $this->member_info['openid'] = $mb_user_token_info['openid'];
                $this->member_info['token'] = $mb_user_token_info['token'];
            }
        }
    }
    public function getOpenId()
    {
        return $this->member_info['openid'];
    }

    public function setOpenId($openId)
    {
        $this->member_info['openid'] = $openId;
        M('ssys_mb_user_token')->updateMemberOpenId($this->member_info['token'], $openId);
    }
}

class logoutCtl extends ssys_mobileMemberCtl {

	public function __construct(){
		parent::__construct();
	}

    /**
     * 注销
     */
	public function index(){
        if(empty($_POST['username']) || !in_array($_POST['client'], $this->client_type_array)) {
            output_error('参数错误');
        }

        $model_mb_user_token = M('ssys_mb_user_token','spreader');

        if($this->member_info['member_name'] == $_POST['username']) {
            $condition = array();
            $condition['member_id'] = $this->member_info['member_id'];
            $condition['client_type'] = $_POST['client'];
            //账号退出的时候只删除当前的key
            $condition['token'] = $_POST['ssys_key'];
            $model_mb_user_token->delMbUserToken($condition);
            output_data('1');
        } else {
            output_error('参数错误');
        }
	}

}
