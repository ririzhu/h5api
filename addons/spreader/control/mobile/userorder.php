<?php
/**
 * 我的订单
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
                output_error(Language::get('请登录'), array('login' => '0'));
            }

            $model_member = M('ssys_member');
            $this->member_info = $model_member->getMemberInfoByID($mb_user_token_info['member_id']);
            if(empty($this->member_info)) {
                output_error(Language::get('请登录'), array('login' => '0'));
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

class userorderCtl extends ssys_mobileMemberCtl {

    public function __construct(){
        parent::__construct();
    }

    /**
     * 订单列表
     */
    public function order_list() {

    	$model_order = M('ssys_order');

        $condition = array();
        $condition['member_id'] = $this->member_info['member_id'];
        if (isset($_REQUEST['yj_state'])) {
            $condition['yj_status'] = $_REQUEST['yj_state'];
        }
        $condition['delete_state'] = 0;

        $order_list = array();

        $order_list_array = $model_order->getSpreaderOrderInfos($condition, '*', $this->page);
		$order_sn_list = low_array_column($order_list_array, 'order_sn');

		$rebuild_order_list_array = $this->get_order_list_by_api($order_sn_list);
		$rebuild_order_list_array = low_array_column($rebuild_order_list_array,NULL, 'order_id');

		foreach ($order_list_array as $key => $value) {
			$item_order_info = array();

			$item_order_info = $value;
			$rebuild_order_item_info = $rebuild_order_list_array[$value['order_id']];
			$item_order_info['state_desc'] = $rebuild_order_item_info['state_desc'];
			$item_order_info['order_state'] = $rebuild_order_item_info['order_state'];
			$item_order_info['order_amount'] = $rebuild_order_item_info['order_amount'];
			switch ($item_order_info['yj_status']) {
				case '-1':
					$item_order_info['yj_status_desc'] = '失效';
					break;
				case '0':
					$item_order_info['yj_status_desc'] = '冻结';
					break;
				case '1':
					$item_order_info['yj_status_desc'] = '已结算';
					break;
			}
			$item_order_info['add_time_str'] = date('Y-m-d H:i:s',$item_order_info['add_time']);

			$order_list[] = $item_order_info;
		}
        
        $page_count = $model_order->gettotalpage();

        output_data(array('order_group_list' => $order_list), mobile_page($page_count));

    }

    // 获取商城系统订单信息
    public function get_order_list_by_api($order_sn_array){
    	$model_order = Model('order');
    	$condition['order_sn'] = array("IN",$order_sn_array);
    	$shop_order_list = $model_order->getOrderList($condition, '', 'order_id,order_state,order_amount', '','',array('order_goods'));
    	
    	return $shop_order_list;
    }

}