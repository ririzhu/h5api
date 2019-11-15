<?php
/**
 * 门店会员 接口
 *
 */
defined('DYMall') or exit('Access Invalid!');

class cashersystem_memberCtl{

    protected $vendor_info = array();

	public function __construct()
	{
		$this->checkToken();
	}
    // 门店 列表
    public function dianList()
    {
        $pageSize = intval($_GET['pageSize'] > 0 ? $_GET['pageSize'] : 10);
        $_GET['pn'] = intval($_GET['currentPage'] > 0 ? $_GET['currentPage'] : 1);

        $state = 200;
        $data = '';
        $message = 'success';

        // 获取门店 列表
        $model_dian = Model('dian');
        $condition['vid'] = $this->vendor_info['vid'];
        $dian_list = $model_dian->getDianList($condition, $pageSize, 'add_time desc');

        if (!empty($dian_list)) {
            $data = array(
                    'list' => $dian_list,
                    'pagination' => array(
                            'current' => $_GET['pn'],
                            'pageSize' => $pageSize,
                            'total' => intval($model_dian->gettotalnum()),
                        )
                );
        }else{
            $state = 255;
            $data = ['list'=>[],'pagination'=>[]];
            $message = Language::get('没有数据');
        }

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

        $state = 200;
        $data = '';
        $message = 'success';

        $vid = $this->vendor_info['vid'];

        if ($vid) {
            $state = 200;
            // 实例化店铺商品分类模型
            $data = $store_goods_class = Model('my_goods_class')->getTreeClassList ( array (
                    'vid' => $vid,
                    'stc_state' => '1'
            ),1 );
        }

        if (!empty($data)) {
            
        }else{
            $state = 255;
            $data = [];
            $message = Language::get('没有数据');
        }

        $return_last = array(
                'state' => $state,
                'data' => $data,
                'msg' => $message,
            );

        echo json_encode($return_last);
    }
	
	// 门店会员 列表
	public function memberList()
	{
		
        $search['dian_id'] = $dian_id = isset($_GET['dian_id']) ? intval($_GET['dian_id']) : 0;
        $search['search_val'] = $search_val = isset($_GET['search']) ? trim($_GET['search']) : '';
        $pageSize = intval($_GET['pageSize'] > 0 ? $_GET['pageSize'] : 10);
        $_GET['pn'] = intval($_GET['currentPage'] > 0 ? $_GET['currentPage'] : 1);

        $state = 200;
        $data = '';
        $message = 'success';

        // 获取收银员/店长 列表
        $model_casher = M('cashsys_member','cashersystem');
        if ($search_val) {
        	$condition['member.member_name|member.member_mobile'] = array("LIKE","%".$search_val."%");
        }
        if ($dian_id) {
            $condition['cashsys_member_common.dian_id'] = $dian_id;
        }
        $condition['cashsys_member_common.vid'] = $this->vendor_info['vid'];
        $limit = ($_GET['pn']-1)*$pageSize.','.$pageSize;
        $page_list = $model_casher->getMemberList($condition,'*',$pageSize,$limit);
        $page_count = $model_casher->getMemberCount($condition);

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
                $value['member_time_str'] = date('Y-m-d H:i',$value['member_time']);
                $page_list[$key] = $value;
            }

            $data = array(
                    'list' => $page_list,
                    'pagination' => array(
                            'current' => $_GET['pn'],
                            'pageSize' => $pageSize,
                            'total' => intval($page_count),
                        ),
                    'searchlist' => $search
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

	// 门店会员 新增
	public function saveMemberData()
	{
        $memberData = $_POST['memberData'];

        $state = 200;
        $data = '';
        $message = 'success';

        $run_flag = true;

        if (is_array($memberData) && !empty($memberData)) {
            $model_member = M('cashsys_member','cashersystem');

            // 校验 手机号格式
            $obj_validate = new Validate();
            $obj_validate->validateparam = array(
                array("input"=>$memberData['member_mobile'], "require"=>"true","validator"=>"mobile","message"=>"手机号格式不正确")
            );
            
            $error = $obj_validate->validate();
            if ($error) {
                $run_flag = false;
                $state = 255;
                $message = strip_tags($error);
            }

            if ($run_flag) {
                $register_info['username'] = $memberData['username'];
                $register_info['mobile'] = $memberData['member_mobile'];
                $register_info['password'] = $register_info['password_confirm'] = $this->createPwd(false);
                if (isset($memberData['remark'])) {
                    $common_data['remark'] = $memberData['remark'];
                    $common_data['dian_id'] = $memberData['dian_id'];
                    $common_data['vid'] = $this->vendor_info['vid'];
                }

                $member_inserted_info = Model('member')->mobileRegister($register_info);
                if (isset($member_inserted_info['error'])) {
                    $state = 255;
                    $message = $member_inserted_info['error'];
                }else{
                    $common_data['member_id'] = $member_inserted_info['member_id'];
                    $saveflag = $model_member->saveMember($common_data);
                    if ($saveflag) {
                        // 发送验证短信
                        $send_msg = $this->sendPwdToPhone($memberData['member_mobile'],$register_info['password']);

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

	// 门店会员 更新
	public function updateMemberData()
	{
		$member_id = $_POST['id'];
		$memberData = $_POST['memberData'];

        $state = 200;
        $data = '';
        $message = 'success';

        $run_flag = true;

		if (is_array($memberData) && !empty($memberData) && $member_id) {
        	$model_member = M('cashsys_member','cashersystem');

            // 校验 手机号格式
            $obj_validate = new Validate();
            $obj_validate->validateparam = array(
                array("input"=>$memberData['member_mobile'], "require"=>"false","validator"=>"mobile","message"=>"手机号格式不正确")
            );
            
            $error = $obj_validate->validate();
            if ($error) {
                $run_flag = false;
                $state = 255;
                $message = strip_tags($error);
            }

            if ($run_flag) {
                $condition['member_id'] = $member_id;
                $condition['vid'] = $this->vendor_info['vid'];
                // 检查密码是否 有变化
                // $oldMemberInfo = $model_member->getMemberInfo($condition);
                $saveflag = $model_member->updateMember($memberData,$condition);
                if ($saveflag) {

                    $state = 200;
                    $message = Language::get('保存成功');
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

	// 获取 门店会员 详情
	public function getMemberInfo()
	{

		$casher_ids = $_GET['member_id'];

        $state = 200;
        $data = '';
        $message = 'success';

		// 删除数据
		$model_member = M('cashsys_member','cashersystem');
    	
    	$condition['member.member_id'] = array("IN",$casher_ids);
    	$condition['cashsys_member_common.vid'] = $this->vendor_info['vid'];

    	$data = $member_info = $model_member->getMemberInfo($condition);

        if (!empty($data)) {
            $model_dian = Model('dian');
            if ($data['dian_id']) {
                $dian_info = $model_dian->getDianInfoByID($data['vid'],$data['dian_id']);
                if (!empty($dian_info)) {
                    $data['dian_name'] = $dian_info['dian_name'];
                }
            }
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