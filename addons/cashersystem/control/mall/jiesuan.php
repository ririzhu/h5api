<?php
/**
 * 结算管理
 *
 */
defined('DYMall') or exit('Access Invalid!');

class jiesuanCtl{

	protected $casher_info = array();

	public function __construct()
	{
		// 验证token 是否有效
		$this->checkToken();

		// 校验是否时店长
		$allow_arr = array();
		if (!in_array($_GET['mod'],$allow_arr)) {
			if ($this->casher_info['is_leader'] != 1) {
				$state = 255;
				$data = '';
				$message = '权限不足';
		        $return_last = array(
		        		'state' => $state,
		        		'data' => $data,
		        		'msg' => $message,
		        	);

		        echo json_encode($return_last);exit;
			}	
		}
	}

    // 获取 结算账号列表
    public function getJiesuanAccountList()
    {
        $pageSize = intval($_GET['pageSize'] > 0 ? $_GET['pageSize'] : 10);
        $_GET['pn'] = intval($_GET['currentPage'] > 0 ? $_GET['currentPage'] : 1);

        $state = 200;
        $data = '';
        $message = 'success';

        // 获取结算账号 列表
        $model_jiesuan_account = Model()->table('jiesuan_account');
        $condition['dian_id'] = $this->casher_info['dian_id'];

        $page_list = $model_jiesuan_account->where($condition)->page($pageSize)->select();
        if (!empty($page_list)) {
            // 结算账号 转账类型
            $j_type_name = array(1=>'银行卡',2=>'支付宝',3=>'微信');

            foreach ($page_list as $key => $value) {
                $value['j_type_name'] = isset($j_type_name[$value['j_type']]) ? $j_type_name[$value['j_type']] : '';
                $page_list[$key] = $value;
            }

            $data = array(
                    'list' => $page_list,
                    'pagination' => array(
                            'current' => $_GET['pn'],
                            'pageSize' => $pageSize,
                            'total' => intval($model_jiesuan_account->gettotalnum()),
                        ),
                );
        }else{
            $state = 255;
            $data = '';
            $message = Language::get('没有数据');
        }

        $return_last = array(
                'state' => $state,
                'data' => $data,
                'msg' => $message,
            );

        echo json_encode($return_last);
    }

    // 新增 结算账号
    public function addJiesuanAccount()
    {
        $accountData['j_type'] = intval($_POST['j_type']);
        $accountData['j_bank'] = trim($_POST['j_bank']);
        $accountData['j_name'] = trim($_POST['j_name']);
        $accountData['is_default'] = intval($_POST['is_default']) ? 1 : 0;

        $state = 200;
        $data = '';
        $message = 'success';

        $run_flag = true;

        if (is_array($accountData) && !empty($accountData)) {
            $model_jiesuan_account = Model()->table('jiesuan_account');

            // 校验 手机号格式
            $obj_validate = new Validate();
            $obj_validate->validateparam = array(
                array("input"=>$accountData['j_type'], "require"=>"true","message"=>"转账类型不能为空"),
                array("input"=>$accountData['j_bank'], "require"=>"true","message"=>"收款账号不能为空"),
                array("input"=>$accountData['j_name'], "require"=>"true","message"=>"收款人不能为空")
            );
            $error = $obj_validate->validate();
            if ($error) {
                $run_flag = false;
                $state = 255;
                $message = strip_tags($error);
            }

            if ($run_flag) {

                $accountData['dian_id'] = $this->casher_info['dian_id'];
                $accountData['is_dian'] = 1;

                if($accountData['is_default']==1){
                    $condition['dian_id'] = $this->casher_info['dian_id'];
                    $update = $model_jiesuan_account->where($condition)->update(array('is_default'=>0));
                }

                $saveflag = $model_jiesuan_account->insert($accountData);;
                if ($saveflag) {
                    $state = 200;
                    if ($send_msg) {
                        $message = $send_msg;
                    }else{
                        $message = Language::get('保存成功');
                    }
                }else{
                    $state = 255;
                    Language::get('保存失败');
                }
            }
            
        }else{
            $state = 255;
            $message = Language::get('保存失败');
        }

        $return_last = array(
                'state' => $state,
                'data' => $data,
                'msg' => $message,
            );

        echo json_encode($return_last);
    }

    // 编辑 结算账号
    public function updateJiesuanAccount()
    {
        $account_id = $_POST['id'];

        $accountData['j_type'] = intval($_POST['j_type']);
        $accountData['j_bank'] = trim($_POST['j_bank']);
        $accountData['j_name'] = trim($_POST['j_name']);
        $accountData['is_default'] = intval($_POST['is_default']) ? 1 : 0;

        $state = 200;
        $data = '';
        $message = 'success';

        $run_flag = true;

        if (is_array($accountData) && !empty($accountData) && $account_id) {
            $model_jiesuan_account = Model()->table('jiesuan_account');

            // 校验 手机号格式
            $obj_validate = new Validate();
            $obj_validate->validateparam = array(
                array("input"=>$accountData['j_type'], "require"=>"true","message"=>"转账类型不能为空"),
                array("input"=>$accountData['j_bank'], "require"=>"true","message"=>"收款账号不能为空"),
                array("input"=>$accountData['j_name'], "require"=>"true","message"=>"收款人不能为空")
            );
            $error = $obj_validate->validate();
            if ($error) {
                $run_flag = false;
                $state = 255;
                $message = strip_tags($error);
            }

            if ($run_flag) {

                $accountData['dian_id'] = $this->casher_info['dian_id'];
                $accountData['is_dian'] = 1;

                if($accountData['is_default']==1){
                    $condition['dian_id'] = $this->casher_info['dian_id'];
                    $update = $model_jiesuan_account->where($condition)->update(array('is_default'=>0));
                }


                $update_condition = array();
                $update_condition['id'] = $account_id;
                $update_condition['dian_id'] = $this->casher_info['dian_id'];
                $saveflag = $model_jiesuan_account->where($update_condition)->update($accountData);
                if ($saveflag) {
                    $state = 200;
                    if ($send_msg) {
                        $message = $send_msg;
                    }else{
                        $message = '编辑成功';
                    }
                }else{
                    $state = 255;
                    $message = '编辑失败';
                }
            }
            
        }else{
            $state = 255;
            $message = Language::get('保存失败');
        }

        $return_last = array(
                'state' => $state,
                'data' => $data,
                'msg' => $message,
            );

        echo json_encode($return_last);
    }

    // 删除 结算账号
    public function deleteJiesuanAccount()
    {
        $account_ids = $_POST['ids'];

        $state = 200;
        $data = '';
        $message = 'success';

        if (!empty($account_ids)) {
            // 删除数据
            $model_jiesuan_account = Model()->table('jiesuan_account');
            
            $condition['id'] = $account_ids;
            $condition['dian_id'] = $this->casher_info['dian_id'];

            $delete_flag = $model_jiesuan_account->where($condition)->delete();
            if ($delete_flag) {
                $state = 200;
                $message = Language::get('删除成功');
            }else{
                $state = 255;
                $message = Language::get('删除失败');
            }

        }else{
            $state = 255;
            $message = Language::get('选择要删除的数据');
        }
        
        $return_last = array(
                'state' => $state,
                'data' => $data,
                'msg' => $message,
            );

        echo json_encode($return_last);
    }

	// 结算账单 列表
	public function getJiesuanList()
	{
        $dian_id = $this->casher_info['dian_id'];
        $bill_state = isset($_GET['bill_state']) ? intval($_GET['bill_state']) : 0;
        $search_val = isset($_GET['search']) ? trim($_GET['search']) : '';
        $pageSize = intval($_GET['pageSize'] > 0 ? $_GET['pageSize'] : 10);
        $_GET['pn'] = intval($_GET['currentPage'] > 0 ? $_GET['currentPage'] : 1);

        $state = 200;
        $data = '';
        $message = 'success';

        $run_flag = true;

        $model_bill = Model('dian_bill');
        $condition = array();
        $condition['ob_vid'] = $dian_id;
        if (!empty($search_val)) {
            if (preg_match('/^\d+$/',$search_val)) {
                $condition['os_month'] = $search_val;
            }else{
                $run_flag = false;
                $state = 255;
                $message = '单号格式不正确';
            }
        }
        if (is_numeric($_GET['bill_state']) && $_GET['bill_state'] > 0) {
            $condition['ob_state'] = intval($bill_state);
        }

        if ($run_flag) {
            $bill_list = $model_bill->getOrderBillList($condition,'*',$pageSize,'os_month DESC,ob_id DESC,ob_state asc');

            if (!empty($bill_list)) {
                $bill_state = array(
                    1=>'已出账',
                    2=>'已确认',
                    3=>'已审核',
                    4=>'已结算'
                );
                foreach ($bill_list as $key => $value) {
                    $value['ob_start_date_str'] = date("Y-m-d",$value['ob_start_date']);
                    $value['ob_end_date_str'] = date("Y-m-d",$value['ob_end_date']);
                    $value['ob_state_str'] = isset($bill_state[$value['ob_state']]) ? $bill_state[$value['ob_state']] : '';

                    // 获取线下订单 总金额
                    $order_condition = array();
                    $order_condition['dian_id'] = $dian_id;
                    $order_condition['finnshed_time'] = array('between',"{$value['ob_start_date']},{$value['ob_end_date']}");
                    $fields = 'sum(order_amount) as order_amount';
                    $order_condition['order_state'] = array("IN",array(ORDER_STATE_PAY,ORDER_STATE_SUCCESS));
                    $c_order_info =  M('cashsys_order','cashersystem')->getOrderInfo($order_condition,array(),$fields);
                    $value['xianxia_order_amount_totals'] = floatval($c_order_info['order_amount']);
                    
                    $bill_list[$key] = $value;
                }
                $data = array(
                        'list' => $bill_list,
                        'pagination' => array(
                                'current' => $_GET['pn'],
                                'pageSize' => $pageSize,
                                'total' => intval($model_bill->gettotalnum()),
                            ),
                    );
            }else{
                $state = 255;
                $data = '';
                $message = Language::get('没有数据');
            }

        }
		
        $return_last = array(
        		'state' => $state,
        		'data' => $data,
        		'msg' => $message,
        	);

        echo json_encode($return_last);
	}

	// 确认出账单
	public function confirmBill()
	{
		$dian_id = $this->casher_info['dian_id'];
		$bill_id = isset($_POST['id']) ? trim($_POST['id']) : '';

        $state = 200;
        $data = '';
        $message = 'success';

        $run_flag = true;

        if (empty($bill_id)) {
	        $state = 255;
	        $message = '参数错误';
        	$run_flag = false;
        }

        if ($run_flag) {
        	$model_bill = Model('dian_bill');
			$condition = array();
			$condition['ob_id'] = $bill_id;
			$condition['ob_vid'] = $dian_id;
			$condition['ob_state'] = BILL_STATE_CREATE;
			$update = $model_bill->editOrderBill(array('ob_state'=>BILL_STATE_STORE_COFIRM),$condition);
			if (!$update) {
				$state = 255;
				$message = '操作失败';
			}else{
				$state = 200;
				$message = '操作成功';
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
			$message = '请登录';
	        $return_last = array(
	        		'state' => $state,
	        		'data' => $data,
	        		'msg' => $message,
	        	);

	        echo json_encode($return_last);exit;
		}
	}
}
