<?php
/**
 * 收银员 接口
 *
 */
defined('DYMall') or exit('Access Invalid!');

class casherCtl{

	protected $casher_info = array();

	public function __construct()
	{
		// 验证token 是否有效
		$this->checkToken();

		// 校验是否时店长
		$allow_arr = array("getcashersaledatatoday");
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

	// 收银员的当日销售概况
	public function getCasherSaleDataToday()
	{
		$casher_id = $this->casher_info['id'];

        $state = 200;
        $data = '';
        $message = 'success';

    	// 获取销售概况
    	$start_time = strtotime(date("Y-m-d",time()));
    	$end_time = $start_time + 86400;
    	// 收银员 门店总单据数 = 核销单数 + 线下单数
    	// 获取核销单数
    	$model_order = M('cashsys_order','cashersystem');
    	$he_condition['casher_id'] = $casher_id;
    	$he_condition['add_time'] = array("BETWEEN",array($start_time,$end_time));
    	$he_order_data = $model_order->getCasherActionData($he_condition);
    	
    	$data['order']['he_order_num'] = $he_order_num = count($he_order_data);

    	// 获取线下单数
    	$xia_condition['casher_id'] = $casher_id;
    	$xia_condition['order_state'] = 20;
    	$xia_condition['add_time'] = array("BETWEEN",array($start_time,$end_time));
    	$xia_order_data = $model_order->getOrderList($xia_condition);
    	// 线下订单 总金额
    	$xia_money_data = low_array_column($xia_order_data,'order_amount');
    	$xia_money = !empty($xia_money_data) ? array_sum($xia_money_data) : '0.00';

    	// 获取当前开放的支付方式
    	$model_payment = M('cashsys_payment','cashersystem');
    	$payment_condition['payment_state'] = 1;
        $payment_list = $model_payment->getPaymentList($payment_condition);
        $payment_list = low_array_column($payment_list,'payment_name','payment_code');

    	$payment_money_data = array();
    	$show_payment_money_list = array();
    	foreach ($xia_order_data as $key => $value) {
    		$old_money = isset($payment_money_data[$value['payment_code']]) ? $payment_money_data[$value['payment_code']] : 0;
    		$payment_money_data[$value['payment_code']] = $old_money + $value['order_amount'];
    	}
    	foreach ($payment_list as $p_key => $p_value) {
    		$item_val['payment_name'] = $p_value;
    		$item_val['money'] = isset($payment_money_data[$p_key]) ? $payment_money_data[$p_key] : 0;
    		$show_payment_money_list[] = $item_val;
    	}

    	$data['order']['xia_order_num'] = $xia_order_num = count($xia_order_data);

    	$data['order']['all_order_num'] = $all_order_num = $he_order_num + $xia_order_num;

    	$data['order']['all_money'] = floatval($xia_money);
    	$data['order']['show_payment_money_list'] = $show_payment_money_list;

        $return_last = array(
        		'state' => $state,
        		'data' => $data,
        		'msg' => $message,
        	);

        echo json_encode($return_last);

	}

    // 门店销售概况
    public function getCasherSaleData()
    {
        // 时间区间
        $date_type = $_GET['type'] ? $_GET['type'] : 1;

        switch ($date_type) {
            case '1':
                // 当前天
                $start_time = strtotime(date("Y-m-d",time()));
                $end_time = $start_time + 86400;
                break;

            case '2':
                // 当前周

                //当前日期
                $sdefaultDate = date("Y-m-d");
                //$first =1 表示每周星期一为开始日期 0表示每周日为开始日期
                $first=1;
                //获取当前周的第几天 周日是 0 周一到周六是 1 - 6
                $w=date('w',strtotime($sdefaultDate));
                //获取本周开始日期，如果$w是0，则表示周日，减去 6 天
                $week_start=date('Y-m-d',strtotime("$sdefaultDate -".($w ? $w - $first : 6).' days'));
                //本周结束日期
                $week_end=date('Y-m-d',strtotime("$week_start +7 days"));

                $start_time = strtotime($week_start);
                $end_time = strtotime($week_end);
                break;

            case '3':
                // 当前月
                $year = date("Y",time());
                $mouth = date("m",time());

                // 获取下一月的月份
                $next_year = $year;
                $next_mouth = $mouth + 1;
                if ($next_mouth > 12) {
                    $next_year = $year + 1;
                    $next_mouth = 1;
                }else{
                    $next_year = $year;
                }

                $start_time_str = $year.'-'.$mouth.'-01';
                $end_time_str = $next_year.'-'.$next_mouth.'-01';

                $start_time = strtotime($start_time_str);
                $end_time = strtotime($end_time_str);
                break;
        }

        $state = 200;
        $data = '';
        $message = 'success';

        // 获取当前门店的所有收银员
        $model_casher = M('cashsys_users','cashersystem');
        $condition['is_leader'] = 0;
        $condition['vid'] = $this->casher_info['vid'];
        $condition['dian_id'] = $this->casher_info['dian_id'];

        $casher_list = $model_casher->getCashsysUsersList($condition,'id');
        $casher_ids = low_array_column($casher_list,'id');

        // 获取销售概况
        // 收银员 门店总单据数 = 核销单数 + 线下单数
        // 获取核销单数
        $model_order = M('cashsys_order','cashersystem');
        $he_condition['casher_id'] = array("IN",$casher_ids);
        $he_condition['add_time'] = array("BETWEEN",array($start_time,$end_time));
        $he_order_data = $model_order->getCasherActionData($he_condition);
        
        $data['order']['he_order_num'] = $he_order_num = count($he_order_data);

        // 获取线下单数
        $xia_condition['casher_id'] = array("IN",$casher_ids);
        $xia_condition['order_state'] = 20;
        $xia_condition['add_time'] = array("BETWEEN",array($start_time,$end_time));
        $xia_order_data = $model_order->getOrderList($xia_condition);
        // 线下订单 总金额
        $xia_money_data = low_array_column($xia_order_data,'order_amount');
        $xia_money = !empty($xia_money_data) ? array_sum($xia_money_data) : '0.00';

        // 获取当前开放的支付方式
        $model_payment = M('cashsys_payment','cashersystem');
        $payment_condition['payment_state'] = 1;
        $payment_list = $model_payment->getPaymentList($payment_condition);
        $payment_list = low_array_column($payment_list,'payment_name','payment_code');

        $payment_money_data = array();
        $show_payment_money_list = array();
        foreach ($xia_order_data as $key => $value) {
            $old_money = isset($payment_money_data[$value['payment_code']]) ? $payment_money_data[$value['payment_code']] : 0;
            $payment_money_data[$value['payment_code']] = $old_money + $value['order_amount'];
        }
        foreach ($payment_list as $p_key => $p_value) {
            $item_val['payment_name'] = $p_value;
            $item_val['money'] = isset($payment_money_data[$p_key]) ? $payment_money_data[$p_key] : 0;
            $show_payment_money_list[] = $item_val;
        }

        $data['order']['xia_order_num'] = $xia_order_num = count($xia_order_data);

        $data['order']['all_order_num'] = $all_order_num = $he_order_num + $xia_order_num;

        $data['order']['all_money'] = floatval($xia_money);
        $data['order']['show_payment_money_list'] = $show_payment_money_list;

        $return_last = array(
                'state' => $state,
                'data' => $data,
                'msg' => $message,
            );

        echo json_encode($return_last);
    }

	// 收银员 列表
	public function casherList()
	{
		
        $type = 0;
        $search_val = isset($_GET['search']) ? trim($_GET['search']) : '';
        $pageSize = intval($_GET['pageSize'] > 0 ? $_GET['pageSize'] : 10);
        $_GET['pn'] = intval($_GET['currentPage'] > 0 ? $_GET['currentPage'] : 1);

        $state = 200;
        $data = '';
        $message = 'success';

        // 获取收银员/店长 列表
        $model_casher = M('cashsys_users','cashersystem');
        $condition['is_leader'] = $type;
        $condition['vid'] = $this->casher_info['vid'];
        $condition['dian_id'] = $this->casher_info['dian_id'];
        if ($search_val) {
        	$condition['casher_name|casher_phone'] = array("LIKE","%".$search_val."%");
        }
        $page_list = $model_casher->getCashsysUsersList($condition,'*',$pageSize);
        if (!empty($page_list)) {
            $data = array(
                    'list' => $page_list,
                    'pagination' => array(
                            'current' => $_GET['pn'],
                            'pageSize' => $pageSize,
                            'total' => intval($model_casher->gettotalnum()),
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

	// 收银员详情（收银员的销售概况）
	public function getCasherInfo()
	{
		$casher_id = $_GET['casher_id'];

        $state = 200;
        $data = '';
        $message = 'success';

        $model_casher = M('cashsys_users','cashersystem');
    	$condition['is_leader'] = 0;
    	$condition['vid'] = $this->casher_info['vid'];
    	$condition['dian_id'] = $this->casher_info['dian_id'];
    	$condition['id'] = $casher_id;

    	// 获取收银员基本信息
    	$cashInfo = $model_casher->getCashsysUsersInfo($condition);

        if (!empty($cashInfo)) {
            // 获取门店名称
            $model_dian = Model('dian');
            if ($cashInfo['dian_id']) {
                $dian_info = $model_dian->getDianInfoByID($cashInfo['vid'],$cashInfo['dian_id']);
                if (!empty($dian_info)) {
                    $cashInfo['dian_name'] = $dian_info['dian_name'];
                }
            }
            $data['cash_info'] = $cashInfo;

            // 获取销售概况
            // 收银员 门店总单据数 = 核销单数 + 线下单数
            // 获取核销单数
            $model_order = M('cashsys_order','cashersystem');
            $he_condition['casher_id'] = $casher_id;
            $he_order_data = $model_order->getCasherActionData($he_condition);
            
            $data['order']['he_order_num'] = $he_order_num = count($he_order_data);

            // 获取线下单数
            $xia_condition['casher_id'] = $casher_id;
            $xia_condition['order_state'] = 20;
            $xia_order_data = $model_order->getOrderList($xia_condition);
            // 线下订单 总金额
            $xia_money_data = low_array_column($xia_order_data,'order_amount');
            $xia_money = !empty($xia_money_data) ? array_sum($xia_money_data) : '0.00';

            // 获取当前开放的支付方式
            $model_payment = M('cashsys_payment','cashersystem');
            $payment_condition['payment_state'] = 1;
            $payment_list = $model_payment->getPaymentList($payment_condition);
            $payment_list = low_array_column($payment_list,'payment_name','payment_code');

            $payment_money_data = array();
            $show_payment_money_list = array();
            foreach ($xia_order_data as $key => $value) {
                $old_money = isset($payment_money_data[$value['payment_code']]) ? $payment_money_data[$value['payment_code']] : 0;
                $payment_money_data[$value['payment_code']] = $old_money + $value['order_amount'];
            }
            foreach ($payment_list as $p_key => $p_value) {
                $item_val['payment_name'] = $p_value;
                $item_val['money'] = isset($payment_money_data[$p_key]) ? $payment_money_data[$p_key] : 0;
                $show_payment_money_list[] = $item_val;
            }

            $data['order']['xia_order_num'] = $xia_order_num = count($xia_order_data);

            $data['order']['all_order_num'] = $all_order_num = $he_order_num + $xia_order_num;

            $data['order']['all_money'] = floatval($xia_money);
            $data['order']['show_payment_money_list'] = $show_payment_money_list;

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

	// 收银员 更新
	public function updateCasherData()
	{

        $type = 0;

		$casher_id = $_POST['id'];
		$casherData = $_POST['casherData'];

        $state = 200;
        $data = '';
        $message = 'success';

        $run_flag = true;

		if (is_array($casherData) && !empty($casherData) && $casher_id) {
        	$model_casher = M('cashsys_users','cashersystem');

            // 校验 名称是否 唯一
            if ($run_flag && $casherData['casher_name']) {
                $check_condition = array();
                $check_condition['id'] = array("neq", $casher_id);
                $check_condition['casher_name'] = $casherData['casher_name'];
                $has_data_info = $model_casher->getCashsysUsersInfo($check_condition,'id');
                if (!empty($has_data_info)) {
                    $run_flag = false;
                    $state = 255;
                    $message = Language::get('名称已存在');
                }
            }
            // 校验 手机是否 唯一
            if ($run_flag && $casherData['casher_phone']) {
                $check_condition = array();
                $check_condition['id'] = array("neq", $casher_id);
                $check_condition['casher_phone'] = $casherData['casher_phone'];
                $has_data_info = $model_casher->getCashsysUsersInfo($check_condition,'id');
                if (!empty($has_data_info)) {
                    $run_flag = false;
                    $state = 255;
                    $message = Language::get('名称已存在');
                }
            }

            if ($run_flag) {
            	$condition['id'] = $casher_id;
            	$condition['is_leader'] = $type;
            	$condition['vid'] = $this->casher_info['vid'];
            	$condition['dian_id'] = $this->casher_info['dian_id'];

            	// 检查密码是否 有变化
            	$oldCasherInfo = $model_casher->getCashsysUsersInfo($condition);

            	// 是否将密码发送
            	$send_pwd_short_msg_flag = false;
            	if (isset($casherData['casher_pwd']) && $casherData['casher_pwd']) {
    	        	$send_pwd = $casherData['casher_pwd'];
    	        	$casherData['casher_pwd'] = md5($send_pwd);

    	        	if (!empty($oldCasherInfo) && isset($oldCasherInfo['casher_pwd'])) {
    	        		if ($oldCasherInfo['casher_pwd'] == $casherData['casher_pwd']) {
    	        			// 未更改密码 不需要发送
    	        		}
    	        	}
            	}

            	$saveflag = $model_casher->editCasherData($casherData,$condition);
            	if ($saveflag) {
                    $send_msg = '';
            		if ($send_pwd_short_msg_flag) {
    		        	// 发送密码 给手机号
    		        	$send_msg = $this->sendPwdToPhone($oldCasherInfo['casher_phone'],$send_pwd);
            		}

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

	// 收银员 新增
	public function saveCasherData()
	{
        $type = 0;

		$casherData['casher_name'] = trim($_POST['casher_name']);
		$casherData['casher_phone'] = trim($_POST['casher_phone']);
		$casherData['casher_pwd'] = trim($_POST['casher_pwd']);

        $state = 200;
        $data = '';
        $message = 'success';

        $run_flag = true;

		if (is_array($casherData) && !empty($casherData)) {
        	$model_casher = M('cashsys_users','cashersystem');

            // 校验 手机号格式
            $obj_validate = new Validate();
            $obj_validate->validateparam = array(
                array("input"=>$casherData['casher_name'], "require"=>"true","message"=>"收银员名称不能为空"),
                array("input"=>$casherData['casher_phone'], "require"=>"true","validator"=>"mobile","message"=>"手机号格式不正确")
            );
            $error = $obj_validate->validate();
            if ($error) {
                $run_flag = false;
                $state = 255;
                $message = strip_tags($error);
            }

            // 校验 名称是否 唯一
            if ($run_flag && $casherData['casher_name']) {
                $check_condition = array();
                $check_condition['casher_name'] = $casherData['casher_name'];
                $has_data_info = $model_casher->getCashsysUsersInfo($check_condition,'id');
                if (!empty($has_data_info)) {
                    $run_flag = false;
                    $state = 255;
                    $message = Language::get('名称已存在');
                }
            }
            // 校验 手机是否 唯一
            if ($run_flag && $casherData['casher_phone']) {
                $check_condition = array();
                $check_condition['casher_phone'] = $casherData['casher_phone'];
                $has_data_info = $model_casher->getCashsysUsersInfo($check_condition,'id');
                if (!empty($has_data_info)) {
                    $run_flag = false;
                    $state = 255;
                    $message = Language::get('名称已存在');
                }
            }

            if ($run_flag) {
                $send_pwd = $casherData['casher_pwd'];

                $casherData['is_leader'] = 0;
                $casherData['vid'] = $this->casher_info['vid'];
                $casherData['dian_id'] = $this->casher_info['dian_id'];
                $casherData['add_time'] = time();
                $casherData['casher_pwd'] = md5($send_pwd);

                $saveflag = $model_casher->addCasherData($casherData);
                if ($saveflag) {
                    // 发送密码 给手机号
                    $send_msg = $this->sendPwdToPhone($casherData['casher_phone'],$send_pwd);

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

	// 删除 收银员
	public function deleteCashers()
	{
        $type = 0;
		$casher_ids = $_POST['ids'];

        $state = 200;
        $data = '';
        $message = 'success';

        if (!empty($casher_ids)) {
			// 删除数据
			$model_casher = M('cashsys_users','cashersystem');
        	
        	$condition['id'] = $casher_ids;
        	$condition['is_leader'] = $type;
        	$condition['vid'] = $this->casher_info['vid'];
        	$condition['dian_id'] = $this->casher_info['dian_id'];

        	$delete_flag = $model_casher->deleteCasherData($condition);
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

	// 给指定手机号发送短信
	public function sendPwdToPhone($phone,$pwd)
	{
        $log_msg = str_replace('#code#',$pwd,C('cashsys_mobile_memo'));
        $log_msg = str_replace('#app#',C('cashsys_mobile_signature'),$log_msg);
        $sms = new Sms();
        $result = $sms->send($phone,$log_msg,C('cashsys_mobile_tplid'));
        if($result){
            $log_array['log_phone'] = $phone;
            $log_array['log_captcha'] = $pwd;
            $log_array['log_ip'] = getIp();
            $log_array['log_msg'] = $log_msg;
            $log_array['log_type'] = 4;
            $log_array['add_time'] = time();
            $model_sms_log = Model('sms_log');
            $model_sms_log->addSms($log_array);
            $msg = '';
            // output_data(array('state' =>$state,'msg'=>$msg));
        } else {
            $msg = Language::get('密码发送失败');
        }

        return $msg;
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