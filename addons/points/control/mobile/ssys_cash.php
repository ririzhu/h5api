<?php
/**
 * WAP 提现
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

class ssys_cashCtl extends ssys_mobileMemberCtl {

	public function __construct() {
        parent::__construct();
    }

    // 提现申请
    public function cash_apply(){
    	$cash_amount = floatval($_POST['cash_amount']);
    	// 验证 申请的金额 是否满足最低金额 且 不超过用户可提现金额
    	$member_max_amount = floatval($this->member_info['available_yongjin']);
    	$member_min_amount = C('ssys_min_cash_amount_once') ? C('ssys_min_cash_amount_once') : 0;

		$show_error = true;
		$msg = '';

    	if ($cash_amount >= $member_min_amount && $cash_amount <= $member_max_amount) {
    		try {
    			$ssys_yj = M('ssys_yj','spreader');
			    $ssys_yj->beginTransaction();
			    $pdc_sn = $ssys_yj->makeSn();
    			$data = array();
    			$data['pdc_sn'] = $pdc_sn;
    			$data['pdc_member_id'] = $this->member_info['member_id'];
    			$data['pdc_member_name'] = $this->member_info['member_name'];
    			$data['pdc_amount'] = $cash_amount;
    			// $data['pdc_bank_name'] = $_POST['pdc_bank_name'];
    			// $data['pdc_bank_no'] = $_POST['pdc_bank_no'];
    			// $data['pdc_bank_user'] = $_POST['pdc_bank_user'];
    			$data['pdc_add_time'] = time();
    			$data['pdc_payment_state'] = 0;
    			$insert = $ssys_yj->addPdCash($data);
    			if (!$insert) {
    			    throw new Exception('提现信息添加失败');
    			}
    			//冻结可用预存款
    			$data = array();
    			$data['member_id'] = $this->member_info['member_id'];
    			$data['member_name'] = $this->member_info['member_name'];
    			$data['amount'] = $cash_amount;
    			$data['order_sn'] = $pdc_sn;
    			$ssys_yj->changePd('cash_apply',$data);
    			$ssys_yj->commit();
    			$show_error = false;
    			$msg = '您的提现申请已成功提交，请等待系统处理';
			} catch (Exception $e) {
			    $ssys_yj->rollback();
			    $msg = $e->getMessage();
			}
    	}else{
    		$msg = '提现金额不合法，请填写正确的提现金额。';
    	}

    	if ($show_error) {
    		output_data(array('error'=>$msg));
    	}else{
    		output_data(array('msg'=>$msg));
    	}

    }

    // 提现记录
    public function pdcashlist(){
    	$ssys_yj = M('ssys_yj','spreader');

    	// 全部,成功,失败,进行中
    	// '','s','f','i'
    	$cash_state = $_REQUEST['cash_state'];

        $condition = array();
        switch ($cash_state) {
        	case 's':
        		$condition['pdc_payment_state'] = 1;
        		break;
        	case 'f':
        		$condition['pdc_payment_state'] = -1;
        		break;
        	case 'i':
        		$condition['pdc_payment_state'] = 0;
        		break;
        	default:
        		break;
        }
        $condition['pdc_member_id'] =  $this->member_info['member_id'];

        $cash_list = $ssys_yj->getPdCashList($condition,30,'*','pdc_id desc');
        foreach ($cash_list as $key=>$val){
            $cash_list[$key]['pdc_add_time'] = date('Y-m-d',$val['pdc_add_time']);
            switch ($val['pdc_payment_state']) {
            	case '0':
            		$cash_list[$key]['pdc_payment_state_desc'] = '正在审核';
            		break;
            	case '-1':
            		$cash_list[$key]['pdc_payment_state_desc'] = '提现失败';
            		break;
            	case '1':
            		$cash_list[$key]['pdc_payment_state_desc'] = '提现成功';
            		break;
            }
        }
        $page_count = $ssys_yj->gettotalpage();
        output_data(array('list' => $cash_list),mobile_page($page_count));
    }

}