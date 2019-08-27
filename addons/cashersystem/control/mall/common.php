<?php
/**
 * 通用 接口
 *
 */
defined('DYMall') or exit('Access Invalid!');

class commonCtl{

    protected $casher_info = array();

	public function __construct()
	{
		
	}

	// 行业 列表(登录时选择)
	public function industryClassList()
	{
        $state = 200;
        $data = '';
        $message = 'success';

        $data = array(
        	array(
	        	"id" => 1,
	        	"name" => "餐饮行业"
        	),
        	array(
	        	"id" => 2,
	        	"name" => "零售行业"	
        	)
        );

        $return_last = array(
        		'state' => $state,
        		'data' => $data,
        		'msg' => $message,
        	);

        echo json_encode($return_last);
	}

	// 店铺分类 1级列表
	public function vendorCateList()
	{

        // 验证token 是否有效
        $this->checkToken();

        $state = 200;
        $data = '';
        $message = 'success';

        $dian_id = $this->casher_info['dian_id'];

        if(empty($dian_id)) {
        	$state = 255;
            $message = '参数错误';
        }

        $vid = 0;

        // 根据dian_id 获取vid
        $dian_info = Model('dian')->getDianInfoByID('',$dian_id);
        if (isset($dian_info['vid']) && $dian_info['vid'] > 0) {
        	$vid = $dian_info['vid'];
        }

        if ($vid) {
        	$state = 200;
	        // 实例化店铺商品分类模型
	        $data = $store_goods_class = Model('my_goods_class')->getTreeClassList ( array (
	                'vid' => $vid,
	                'stc_state' => '1'
	        ),1 );
        }

        $return_last = array(
        		'state' => $state,
        		'data' => $data,
        		'msg' => $message,
        	);

        echo json_encode($return_last);
	}

	// 获取支付方式
	public function getPaymentList()
	{
        $state = 200;
        $data = '';
        $message = 'success';

        $model_payment = M('cashsys_payment','cashersystem');
        $payment_condition['payment_state'] = 1;
        $data = $payment_list = $model_payment->getPaymentList($payment_condition);
        
        if(!empty($payment_list)){
            foreach ($payment_list as $key => $val){
                if($val['payment_config'] != ''){
                    $payment_list[$key]['payment_config'] = unserialize($val['payment_config']);
                }
            }
        }

        $return_last = array(
        		'state' => $state,
        		'data' => $data,
        		'msg' => $message,
        	);

        echo json_encode($return_last);

	}
    
    // 校验token
    public function checkToken()
    {
        $check_flag = true;
        // 校验token
        $token = $_REQUEST['token'];

        $model_cashsys_token = M('cashsys_token','cashersystem');
        $cashsys_token_info = $model_cashsys_token->getTokenInfoByToken($token);
        if (empty($cashsys_token_info)) {
            $check_flag = false;
        }

        $model_users = M('cashsys_users');
        $this->casher_info = $model_users->getCashsysUsersInfo(array('id'=>$cashsys_token_info['casher_id']));
        if(empty($this->casher_info)) {
            $check_flag = false;
        } else {
            $this->casher_info['token'] = $cashsys_token_info['token'];
        }

        if (!$check_flag) {
            $state = 255;
            $data = '';
            $message = Language::get('请登录');
            $return_last = array(
                    'state' => $state,
                    'data' => $data,
                    'msg' => $message,
                );

            echo json_encode($return_last);exit;
        }
    }



}