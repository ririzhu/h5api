<?php
/**
 * 收银员/店长 接口
 *
 */
defined('DYMall') or exit('Access Invalid!');

class casherCtl{

    protected $vendor_info = array();

	public function __construct()
	{
		$this->checkToken();
	}
	
	// 收银员/店长 列表
	public function casherList()
	{

        $search = array();
		
        $type = intval($_GET['type']) > 0 ? 1 : 0;
        $search['search_val'] = $search_val = isset($_GET['search']) ? trim($_GET['search']) : '';
        $pageSize = intval($_GET['pageSize'] > 0 ? $_GET['pageSize'] : 10);
        $_GET['pn'] = intval($_GET['currentPage'] > 0 ? $_GET['currentPage'] : 1);

        $state = 200;
        $data = '';
        $message = 'success';

        // 获取收银员/店长 列表
        $model_casher = M('cashsys_users','cashersystem');
        $condition['is_leader'] = $type;
        $condition['vid'] = $this->vendor_info['vid'];
        if ($search_val) {
        	$condition['casher_name|casher_phone'] = array("LIKE","%".$search_val."%");
        }
        $page_list = $model_casher->getCashsysUsersList($condition,'*',$pageSize);

        if (!empty($page_list)) {
            $model_dian = Model('dian');

            // 获取门店名称
            foreach ($page_list as $key => $value) {
                if ($value['dian_id']) {
                    $dian_info = $model_dian->getDianInfoByID($value['vid'],$value['dian_id']);
                    if (!empty($dian_info)) {
                        $value['dian_name'] = $dian_info['dian_name'];
                    }
                }
                $page_list[$key] = $value;
            }

            $data = array(
                    'list' => $page_list,
                    'pagination' => array(
                            'current' => $_GET['pn'],
                            'pageSize' => $pageSize,
                            'total' => intval($model_casher->gettotalnum()),
                        ),
                    'searchlist' => $search
                );
        }else{
            $state = 200;
            $data = [];
            $message = Language::get(Language::get('没有数据'));
        }

        $return_last = array(
        		'state' => $state,
        		'data' => $data,
        		'msg' => $message,
        	);

        echo json_encode($return_last);

	}

	// 收银员/店长 新增
	public function saveCasherData()
	{
        $type = intval($_POST['type']) > 0 ? 1 : 0;

		$casherData = $_POST['casherData'];

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
            if ($run_flag) {
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
            if ($run_flag) {
                $check_condition = array();
                $check_condition['casher_phone'] = $casherData['casher_phone'];
                $has_data_info = $model_casher->getCashsysUsersInfo($check_condition,'id');
                if (!empty($has_data_info)) {
                    $run_flag = false;
                    $state = 255;
                    $message = '手机号已存在';
                }
            }

            if ($run_flag) {
                $send_pwd = $casherData['casher_pwd'];

                $casherData['is_leader'] = $type;
                $casherData['vid'] = $this->vendor_info['vid'];
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

	// 收银员/店长 更新
	public function updateCasherData()
	{

        $type = intval($_POST['type']) > 0 ? 1 : 0;

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
                    $message = '手机号已存在';
                }
            }

            if ($run_flag) {
                $condition['id'] = $casher_id;
                $condition['is_leader'] = $type;
                $condition['vid'] = $this->vendor_info['vid'];

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

	// 删除 收银员/店长
	public function deleteCashers()
	{
        $type = intval($_POST['type']) > 0 ? 1 : 0;
		$casher_ids = $_POST['ids'];

        $state = 200;
        $data = '';
        $message = 'success';

        if (is_array($casher_ids) && !empty($casher_ids)) {
			// 删除数据
			$model_casher = M('cashsys_users','cashersystem');
        	
        	$condition['id'] = array("IN",$casher_ids);
        	$condition['is_leader'] = $type;
        	$condition['vid'] = $this->vendor_info['vid'];

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
	public function createPwd($show_md5=true)
	{
		// 
		$char_fool = md5(time());
		$pwd_length = 6;

		$max_length = strlen($char_fool) - $pwd_length;
		$rand_start_index = rand(0,$max_length);

		$pwd = substr($char_fool, $rand_start_index, 6);

		$save_pwd = $show_md5 ? md5($pwd) : $pwd;

		return $save_pwd;

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

        $model_bwap_vendor_token = Model('bwap_vendor_token');
        $bwap_vendor_token_info = $model_bwap_vendor_token->getSellerTokenInfoByToken($token);

        if (empty($bwap_vendor_token_info)) {
            $check_flag = false;
        }

        $model_vendor = Model('vendor');
        $seller_info = model()->table('seller')->where(['seller_id'=>$bwap_vendor_token_info['seller_id']])->find();
        $this->vendor_info = $model_vendor->getStoreInfo(array('vid'=>$seller_info['vid']));
        if(empty($this->vendor_info)) {
            $check_flag = false;
        } else {
            $this->vendor_info['token'] = $bwap_vendor_token_info['token'];
        }

        if (!$check_flag) {
            $state = 275;
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