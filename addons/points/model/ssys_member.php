<?php
/**
 * 会员管理
 *
 */
defined('DYMall') or exit('Access Invalid!');
class ssys_memberModel extends Model {

    public function __construct(){
        parent::__construct('ssys_member');
    }
    
    /**
     * 会员详细信息
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getMemberInfo($condition, $field = '*') {
        $return = $this->table('ssys_member')->field($field)->where($condition)->find();
        return $return;
    }

    /**
     * 会员列表
     * @param array $condition
     * @param string $field
     * @param number $page
     * @param string $order
     */
    public function getMemberList($condition = array(), $field = '*', $page = 0, $order = 'member_id desc') {
        return $this->table('ssys_member')->where($condition)->field($field)->page($page)->order($order)->select();
    }

    /**
     * 会员数量
     * @param array $condition
     * @return int
     */
    public function getMemberCount($condition) {
        return $this->table('ssys_member')->where($condition)->count();
    }

    /**
     * 取得会员详细信息（优先查询缓存）
     * 如果未找到，则缓存所有字段
     * @param int $member_id
     * @param string $field 需要取得的缓存键值, 例如：'*','member_name,member_sex'
     * @return array
     */
    public function getMemberInfoByID($member_id, $fields = '*') {
        $member_info = rcache($member_id, 'ssys_member', $fields);
        if (empty($member_info)) {
            $member_info = $this->getMemberInfo(array('member_id'=>$member_id),$fields,true);
            wcache($member_id, $member_info, 'ssys_member');
        }
        return $member_info;
    }
    /**
     * 登录时创建会话SESSION
     *
     * @param array $member_info 会员信息
     */
    public function createSession($member_info = array()) {
        if (empty($member_info) || !is_array($member_info)) return ;
		$_SESSION['is_login']	= '1';
		$_SESSION['member_id']	= $member_info['member_id'];
		$_SESSION['member_name']= $member_info['member_name'];
		$_SESSION['member_email']= $member_info['member_email'];
		$_SESSION['is_buy']		= $member_info['is_buy'];
		$_SESSION['avatar'] 	= $member_info['member_avatar'];
		$vendorinfo = Model('seller')->getSellerInfo(array('member_id'=>$_SESSION['member_id']));
		$_SESSION['vid'] = $vendorinfo['vid'];
		if (trim($member_info['member_qqopenid'])){
			$_SESSION['openid']		= $member_info['member_qqopenid'];
		}
		if (trim($member_info['member_sinaopenid'])){
			$_SESSION['slast_key']['uid'] = $member_info['member_sinaopenid'];
		}
		if(!empty($member_info['member_login_time'])) {//登录时间更新
    		$update_info	= array(
    		'member_login_time'=> time(),
    		'member_old_login_time'=> $member_info['member_login_time'],
    		'member_login_ip'=> getIp(),
    		'member_old_login_ip'=> $member_info['member_login_ip']);
    		$this->updateMember($update_info,$member_info['member_id']);
		}
        // 自动登录
        if ($member_info['auto_login'] == 1) {
            $this->auto_login();
        }
    }
    /**
     * 7天内自动登录
     */
    public function auto_login() {
        // 自动登录标记 保存7天
        setBbcCookie('auto_login', encrypt($_SESSION['member_id'], MD5_KEY), 7*24*60*60);
    }
    /**
     * 注册
     */
    public function register($register_info) {
		// 注册验证
		$obj_validate = new Validate();
		$obj_validate->validateparam = array(
		array("input"=>$register_info["username"],		"require"=>"true",		"message"=>'用户名不能为空'),
		array("input"=>$register_info["password"],		"require"=>"true",		"message"=>'密码不能为空'),
		array("input"=>$register_info["password_confirm"],"require"=>"true",	"validator"=>"Compare","operator"=>"==","to"=>$register_info["password"],"message"=>'密码与确认密码不相同'),
		);
		$error = $obj_validate->validate();
		if ($error != ''){
            return array('error' => $error);
		}

        // 验证用户名是否重复
		$check_member_name	= $this->infoMember(array('member_name'=>trim($register_info['username'])));
		if(is_array($check_member_name) and count($check_member_name) > 0) {
            return array('error' => '用户名已存在');
		}



		// 会员添加
		$member_info	= array();
		$member_info['member_name']		= $register_info['username'];
		$member_info['member_passwd']	= $register_info['password'];
        $member_info['inviter_id']		= $register_info['inviter_id'];
		$insert_id	= $this->addMember($member_info);
		if($insert_id) {
			//注册送积分，添加会员积分
			if ($GLOBALS['setting_config']['points_isuse'] == 1){
				$points_model = Model('points');
				$points_model->savePointsLog('regist',array('pl_memberid'=>$insert_id,'pl_membername'=>$register_info['username']),false);
            	//送一级上线积分
                $first_inviter_name = Model('member')->table('member')->getfby_member_id($member_info['inviter_id'],'member_name');
                $first_member = $this->getMemberInfo(array('member_id' => $member_info['inviter_id']));
                $points_model->savePointsLog('inviter',array('pl_memberid'=>$register_info['inviter_id'],'pl_membername'=>$first_inviter_name,'invited'=>'一级下线会员'.$member_info['member_name']));

				//送二级上线积分
				$second_member = $this->getMemberInfo(array('member_id'=> $first_member['inviter_id']));
				if(!empty($second_member)){
					$points_model->savePointsLog('inviter',array('pl_memberid'=>$second_member['member_id'],'pl_membername'=>$second_member['member_name'],'invited'=>'二级下线会员'.$member_info['member_name']));

					//送三级上线积分
					$third_member = $this->getMemberInfo(array('member_id'=> $second_member['inviter_id']));
					if(!empty($second_member)){
						$points_model->savePointsLog('inviter',array('pl_memberid'=>$third_member['member_id'],'pl_membername'=>$third_member['member_name'],'invited'=>'三级下线会员'.$member_info['member_name']));
					}
				}
			}

            // 添加默认相册
            // $insert['ac_name']      = '买家秀';
            // $insert['member_id']    = $insert_id;
            // $insert['ac_des']       = '买家秀默认相册';
            // $insert['ac_sort']      = 1;
            // $insert['is_default']   = 1;
            // $insert['upload_time']  = TIMESTAMP;
            // Model()->table('sns_albumclass')->insert($insert);

            $member_info['member_id'] = $insert_id;
            $member_info['is_buy'] = 1;

            return $member_info;
		} else {
            return array('error' => '注册失败');
		}

}
    /**
     * 手机号码注册
     */
    public function mobileRegister($register_info) {

        $obj_validate = new Validate();
        $obj_validate->validateparam = array(
            //array("input"=>$register_info["mobile"],		"require"=>"true",		"message"=>'123用户名不能为空'),
            //array("input"=>$register_info["password"],		"require"=>"true",		"message"=>'456密码不能为空'),
            array("input"=>$register_info["password_confirm"],"require"=>"true",	"validator"=>"Compare","operator"=>"==","to"=>$register_info["password"],"message"=>'密码与确认密码不相同'),
        );
        $error = $obj_validate->validate();
        if ($error != ''){
            return array('error' => $error);
        }

		// 验证用户名是否重复
		$check_member_name	= $this->getMemberInfo(array('member_name'=>trim($register_info['username'])));
		if(is_array($check_member_name) and count($check_member_name) > 0) {
			return array('error' => '用户名已存在');
		}
//
//		        // 验证邮箱是否重复
//        $check_member_email	= $this->getMemberInfo(array('member_email'=>trim($register_info['email'])));
//        if(is_array($check_member_email) and count($check_member_email)>0) {
//            return array('error' => '邮箱已存在');
//        }

        // 验证手机号是否重复
        $check_member_mobile	= $this->getMemberInfo(array('member_mobile'=>trim($register_info['mobile'])));
        if(is_array($check_member_mobile) and count($check_member_mobile) > 0) {
            return array('error' => Language::get('手机号已存在'));
        }


        // 会员添加
        $member_info	= array();
        $member_info['member_name']		= $register_info['username'];
        $member_info['member_mobile']	= $register_info['mobile'];
        $member_info['member_passwd']	= $register_info['password'];
//      $member_info['member_email']	= $register_info['email'];
        $member_info['inviter_id']		= $register_info['inviter_id'];
		$member_info['inviter2_id']		= $register_info['inviter2_id'];
		$member_info['inviter3_id']		= $register_info['inviter3_id'];

        $insert_id	= $this->addMember($member_info);
        if($insert_id) {
//            //添加会员积分
//            if ($GLOBALS['setting_config']['points_isuse'] == 1){
//                $points_model = Model('points');
//                $points_model->savePointsLog('regist',array('pl_memberid'=>$insert_id,'pl_membername'=>$register_info['username']),false);
//                //送一级上线积分
//                $inviter_name = Model('member')->table('member')->getfby_member_id($member_info['inviter_id'],'member_name');
//                $points_model->savePointsLog('inviter',array('pl_memberid'=>$register_info['inviter_id'],'pl_membername'=>$inviter_name,'invited'=>$member_info['member_name']));
//            }


			// if ($GLOBALS['setting_config']['points_isuse'] == 1) {
			// 	$points_model = Model('points');
			// 	$points_model->savePointsLog('regist', array('pl_memberid' => $insert_id, 'pl_membername' => $register_info['username']), false);
			// 	//送一级上线积分
			// 	$first_inviter_name = Model('member')->table('member')->getfby_member_id($member_info['inviter_id'], 'member_name');
   //              $first_member = $this->getMemberInfo(array('member_id' => $member_info['inviter_id']));
			// 	$points_model->savePointsLog('inviter', array('pl_memberid' => $register_info['inviter_id'], 'pl_membername' => $first_inviter_name, 'invited' => '一级下线会员' . $member_info['member_name']));

			// 	//送二级上线积分
			// 	$second_member = $this->getMemberInfo(array('member_id' => $first_member['inviter_id']));
			// 	if (!empty($second_member)) {
			// 		$points_model->savePointsLog('inviter', array('pl_memberid' => $second_member['member_id'], 'pl_membername' => $second_member['member_name'], 'invited' => '二级下线会员' . $member_info['member_name']));

			// 		//送三级上线积分
			// 		$third_member = $this->getMemberInfo(array('member_id' => $second_member['inviter_id']));
			// 		if (!empty($second_member)) {
			// 			$points_model->savePointsLog('inviter', array('pl_memberid' => $third_member['member_id'], 'pl_membername' => $third_member['member_name'], 'invited' => '三级下线会员' . $member_info['member_name']));
			// 		}
			// 	}
			// }
            // // 添加默认相册
            // $insert['ac_name']      = '买家秀';
            // $insert['member_id']    = $insert_id;
            // $insert['ac_des']       = '买家秀默认相册';
            // $insert['ac_sort']      = 1;
            // $insert['is_default']   = 1;
            // $insert['upload_time']  = TIMESTAMP;
            // Model()->table('sns_albumclass')->insert($insert);
            $member_info['member_id'] = $insert_id;
            $member_info['is_buy'] = 1;
            return $member_info;
        } else {
            return array('error' => '注册失败');
        }
    }
    /**
     * 手机号码注册_zhangjinfeng_2017.08.20
     */
    public function mobileRegister_wap($register_info) {

        $obj_validate = new Validate();
        $obj_validate->validateparam = array(
            //array("input"=>$register_info["mobile"],		"require"=>"true",		"message"=>'123用户名不能为空'),
            //array("input"=>$register_info["password"],		"require"=>"true",		"message"=>'456密码不能为空'),

        );
        $error = $obj_validate->validate();
        if ($error != ''){
            return array('error' => $error);
        }

        // 验证用户名是否重复
        $check_member_name	= $this->getMemberInfo(array('member_name'=>trim($register_info['username'])));
        if(is_array($check_member_name) and count($check_member_name) > 0) {
            return array('error' => '用户名已存在');
        }
//
//		        // 验证邮箱是否重复
//        $check_member_email	= $this->getMemberInfo(array('member_email'=>trim($register_info['email'])));
//        if(is_array($check_member_email) and count($check_member_email)>0) {
//            return array('error' => '邮箱已存在');
//        }

        // 验证手机号是否重复
        $check_member_mobile	= $this->getMemberInfo(array('member_mobile'=>trim($register_info['mobile'])));
        if(is_array($check_member_mobile) and count($check_member_mobile) > 0) {
            return array('error' => Language::get('名称已存在'));
        }


        // 会员添加
        $member_info	= array();
        $member_info['member_name']		= $register_info['username'];
        $member_info['member_mobile']	= $register_info['mobile'];
        $member_info['member_passwd']	= $register_info['password'];
        if($register_info['member_avatar']){
            $member_info['member_avatar']	= $register_info['member_avatar'];
        }
        if($register_info['wx_openid']){
            $member_info['wx_openid']	        = $register_info['wx_openid'];
            $member_info['wx_nickname']	        = $register_info['wx_nickname'];
            $member_info['wx_area']	            = $register_info['wx_province'].' '.$register_info['wx_city'];
        }
//      $member_info['member_email']	= $register_info['email'];
        $member_info['parent_id']		= $register_info['parent_id'];
        $member_info['parent2_id']		= $register_info['parent2_id'];
        $member_info['parent3_id']		= $register_info['parent3_id'];

        $insert_id	= $this->addMember($member_info);
        if($insert_id) {
            $member_info['member_id'] = $insert_id;
            $member_info['is_buy'] = 1;
            return $member_info;
        } else {
            return array('error' => '注册失败');
        }
    }

	/**
	 * 注册商城会员
	 *
	 * @param	array $param 会员信息
	 * @return	array 数组格式的返回结果
	 */
	public function addMember($param) {
		if(empty($param)) {
			return false;
		}
		$member_info	= array();
        $member_info['member_id']           = $param['member_id'];
		$member_info['member_name']			= $param['member_name'];
		$member_info['member_passwd']		= md5(trim($param['member_passwd']));
		$member_info['member_time']			= time();
		$member_info['member_login_time'] 	= $member_info['member_time'];
		$member_info['member_old_login_time'] = $member_info['member_time'];
		$member_info['member_login_ip']		= getIp();
		$member_info['member_old_login_ip']	= $member_info['member_login_ip'];

		if(!empty($param['member_mobile'])){
            $member_info['member_mobile']		= $param['member_mobile'];
        }

        if(!empty($param['member_mobile_bind'])){
            $member_info['member_mobile_bind']		= $param['member_mobile_bind'];
        }

        if(!empty($param['member_email'])){
            $member_info['member_email']		= $param['member_email'];
            $member_info['member_email_bind']		= 1;
        }

		$member_info['member_truename']		= $param['member_truename'];
		$member_info['member_qq']			= $param['member_qq'];
		$member_info['member_sex']			= $param['member_sex'];
		$member_info['member_avatar']		= $param['member_avatar'];
		$member_info['member_qqopenid']		= $param['member_qqopenid'];
		$member_info['member_qqinfo']		= $param['member_qqinfo'];
		$member_info['member_sinaopenid']	= $param['member_sinaopenid'];
		$member_info['member_sinainfo']		= $param['member_sinainfo'];
        $member_info['parent_id']	        = $param['parent_id'];
		$member_info['parent2_id']	        = $param['parent2_id'];
		$member_info['parent3_id']	        = $param['parent3_id'];
        if($param['wx_openid']){
            $member_info['wx_openid']	        = $param['wx_openid'];
            $member_info['wx_nickname']         = $param['wx_nickname'];
            $member_info['wx_area']             = $param['wx_area'];
        }
        if ($param['weixin_unionid']) {
            $member_info['weixin_unionid'] = $param['weixin_unionid'];
            $member_info['weixin_info'] = $param['weixin_info'];
            $member_info['wx_openid'] = $param['wx_openid'];
            $member_info['wx_nickname'] = $param['wx_nickname'];
            $member_info['wx_area']             = $param['wx_area'];
        }

		$result	= Db::insert('ssys_member',$member_info);
		if($result) {
		    $inderid = Db::getLastId();
            // 添加默认相册
            // $insert['ac_name']      = '买家秀';
            // $insert['member_id']    = $inderid;
            // $insert['ac_des']       = '买家秀默认相册';
            // $insert['ac_sort']      = 1;
            // $insert['is_default']   = 1;
            // $insert['upload_time']  = TIMESTAMP;
            // Model()->table('sns_albumclass')->insert($insert);
            return $inderid;
		} else {
			return false;
		}
	}
	/**
	 * 获取会员信息
	 *
	 * @param	array $param 会员条件
	 * @param	string $field 显示字段
	 * @return	array 数组格式的返回结果
	 */
	public function infoMember($param, $field='*') {
		if (empty($param)) return false;

		//得到条件语句
		$condition_str	= $this->getCondition($param);
		$param	= array();
		$param['table']	= 'ssys_member';
		$param['where']	= $condition_str;
		$param['field']	= $field;
		$param['limit'] = 1;
		$member_list	= Db::select($param);
		$member_info	= $member_list[0];
		
		return $member_info;
	}

	/**
	 * 更新会员信息
	 *
	 * @param	array $param 更改信息
	 * @param	int $member_id 会员条件 id
	 * @return	array 数组格式的返回结果
	 */
	public function updateMember($param,$member_id) {
		if(empty($param)) {
			return false;
		}
		$update		= false;
		//得到条件语句
		$condition_str	= " member_id='{$member_id}' ";
		$update		= Db::update('ssys_member',$param,$condition_str);
		return $update;
	}
    /**
     * 编辑会员
     * @param array $condition
     * @param array $data
     */
    public function editMember($condition, $data) {
        $update = $this->table('ssys_member')->where($condition)->update($data);
        if ($update && $condition['member_id']) {
            dcache($condition['member_id'], 'member');
        }
        return $update;
    }
    /**
     * 插入扩展表信息
     * @param unknown $data
     * @return Ambigous <mixed, boolean, number, unknown, resource>
     */
    public function addMemberCommon($data) {
        return $this->table('ssys_member_common')->insert($data);
    }
    /**
     * 编辑会员扩展表
     * @param unknown $data
     * @param unknown $condition
     * @return Ambigous <mixed, boolean, number, unknown, resource>
     */
    public function editMemberCommon($data,$condition) {
        return $this->table('ssys_member_common')->where($condition)->update($data);
    }
	/**
	 * 会员登录检查
	 *
	 */
	public function checkloginMember() {
		if($_SESSION['is_login'] == '1') {
			@header("Location: index.php");
			exit();
		}
	}

    /**
	 * 检查会员是否允许举报商品
	 *
	 */
	public function isMemberAllowInform($member_id) {
        
        $condition = array();
        $condition['member_id'] = $member_id; 
        $member_info = $this->infoMember($condition,'inform_allow');
        if(intval($member_info['inform_allow']) === 1) {
            return true;
        }
        else {
            return false;
        }
	}


	/**
	 * 将条件数组组合为SQL语句的条件部分
	 *
	 * @param	array $conditon_array
	 * @return	string
	 */
	private function getCondition($conditon_array){
		$condition_sql = '';
		if($conditon_array['member_id'] != '') {
			$condition_sql	.= " and member_id= '" .intval($conditon_array['member_id']). "'";
		}
		if($conditon_array['member_name'] != '') {
			$condition_sql	.= " and member_name='".$conditon_array['member_name']."'";
		}
		if($conditon_array['member_passwd'] != '') {
			$condition_sql	.= " and member_passwd='".$conditon_array['member_passwd']."'";
		}
		//是否允许举报
		if($conditon_array['inform_allow'] != '') {
			$condition_sql	.= " and inform_allow='{$conditon_array['inform_allow']}'";
		}
		//是否允许购买
		if($conditon_array['is_buy'] != '') {
			$condition_sql	.= " and is_buy='{$conditon_array['is_buy']}'";
		}
		//是否允许发言
		if($conditon_array['is_allowtalk'] != '') {
			$condition_sql	.= " and is_allowtalk='{$conditon_array['is_allowtalk']}'";
		}
		//是否允许登录
		if($conditon_array['member_state'] != '') {
			$condition_sql	.= " and member_state='{$conditon_array['member_state']}'";
		}
		if($conditon_array['friend_list'] != '') {
			$condition_sql	.= " and member_name IN (".$conditon_array['friend_list'].")";
		}
		if($conditon_array['member_email'] != '') {
			$condition_sql	.= " and member_email='".$conditon_array['member_email']."'";
		}
		if($conditon_array['no_member_id'] != '') {
			$condition_sql	.= " and member_id != '".$conditon_array['no_member_id']."'";
		}
		if($conditon_array['like_member_name'] != '') {
			$condition_sql	.= " and member_name like '%".$conditon_array['like_member_name']."%'";
		}
		if($conditon_array['like_member_email'] != '') {
			$condition_sql	.= " and member_email like '%".$conditon_array['like_member_email']."%'";
		}
		if($conditon_array['like_member_truename'] != '') {
			$condition_sql	.= " and member_truename like '%".$conditon_array['like_member_truename']."%'";
		}
		if($conditon_array['in_member_id'] != '') {
			$condition_sql	.= " and member_id IN (".$conditon_array['in_member_id'].")";
		}
		if($conditon_array['in_member_name'] != '') {
			$condition_sql	.= " and member_name IN (".$conditon_array['in_member_name'].")";
		}
		if($conditon_array['member_qqopenid'] != '') {
			$condition_sql	.= " and member_qqopenid = '{$conditon_array['member_qqopenid']}'";
		}
		if($conditon_array['member_sinaopenid'] != '') {
			$condition_sql	.= " and member_sinaopenid = '{$conditon_array['member_sinaopenid']}'";
		}

		if($conditon_array['inviter_id'] != '') {
			$condition_sql	.= " and inviter_id = '{$conditon_array['inviter_id']}'";
		}

		if($conditon_array['inviter2_id'] != '') {
			$condition_sql	.= " and inviter2_id = '{$conditon_array['inviter2_id']}'";
		}

		if($conditon_array['inviter3_id'] != '') {
			$condition_sql	.= " and inviter3_id = '{$conditon_array['inviter3_id']}'";
		}

		
		return $condition_sql;
	}
	
// 	/**
// 	 * 会员列表
// 	 *
// 	 * @param array $condition 检索条件
// 	 * @param obj $obj_page 分页对象
// 	 * @return array 数组类型的返回结果
// 	 */
// 	public function getMemberList($condition,$obj_page='',$field='*'){
// 		$condition_str = $this->getCondition($condition);
// 		$param = array();
// 		$param['table'] = 'member';
// 		$param['where'] = $condition_str;
// 		$param['order'] = $condition['order'] ? $condition['order'] : 'member_id desc';
// 		$param['field'] = $field;
// 		$param['limit'] = $condition['limit'];
// 		$member_list = Db::select($param,$obj_page);
// 		return $member_list;
// 	}
	
	/**
	 * 删除会员
	 *
	 * @param int $id 记录ID
	 * @return array $rs_row 返回数组形式的查询结果
	 */
	public function del($id){
		if (intval($id) > 0){
			$where = " member_id = '". intval($id) ."'";
			$result = Db::delete('ssys_member',$where);
			return $result;
		}else {
			return false;
		}
	}
	/**
	 * 查询会员总数
	 */
	public function countMember($condition){
		//得到条件语句
		$condition_str	= $this->getCondition($condition);
		$count = Db::getCount('ssys_member',$condition_str);
		return $count;
	}

    /**
     * 获得会员等级
     * @param bool $show_progress 是否计算其当前等级进度
     * @param int $exppoints  会员经验值
     * @param array $cur_level 会员当前等级
     */
    public function getMemberGradeArr($show_progress = false,$exppoints = 0,$cur_level = ''){
        $member_grade = C('member_grade')?unserialize(C('member_grade')):array();
        //处理会员等级进度
        if ($member_grade && $show_progress){
            $is_max = false;
            if ($cur_level === ''){
                $cur_gradearr = $this->getOneMemberGrade($exppoints, false, $member_grade);
                $cur_level = $cur_gradearr['level'];
            }
            foreach ($member_grade as $k=>$v){
                if ($cur_level == $v['level']){
                    $v['is_cur'] = true;
                }
                $member_grade[$k] = $v;
            }
        }
        return $member_grade;
    }


    /**
     * 获得某一会员等级
     * @param int $growthvalue
     * @param bool $show_progress 是否计算其当前等级进度
     * @param array $member_grade 会员等级
     */
    public function getOneMemberGrade($growthvalue,$show_progress = false,$member_grade = array()){
        if (!$member_grade){
            $member_grade = C('member_grade')?unserialize(C('member_grade')):array();
        }
        if (empty($member_grade)){//如果会员等级设置为空
            $grade_arr['level'] = -1;
            $grade_arr['level_name'] = '暂无等级';
            return $grade_arr;
        }

        $growthvalue = intval($growthvalue);

        $grade_arr = array();
        if ($member_grade){
            foreach ($member_grade as $k=>$v){
                if($growthvalue >= $v['growthvalue']){
                    $grade_arr = $v;
                }
            }
        }
        //计算提升进度
        if ($show_progress == true){
            if (intval($grade_arr['level']) >= (count($member_grade) - 1)){//如果已达到顶级会员
                $grade_arr['downgrade'] = $grade_arr['level'] - 1;//下一级会员等级
                $grade_arr['downgrade_name'] = $member_grade[$grade_arr['downgrade']]['level_name'];
                $grade_arr['downgrade_exppoints'] = $member_grade[$grade_arr['downgrade']]['exppoints'];
                $grade_arr['upgrade'] = $grade_arr['level'];//上一级会员等级
                $grade_arr['upgrade_name'] = $member_grade[$grade_arr['upgrade']]['level_name'];
                $grade_arr['upgrade_exppoints'] = $member_grade[$grade_arr['upgrade']]['exppoints'];
                $grade_arr['less_exppoints'] = 0;
                $grade_arr['exppoints_rate'] = 100;
            } else {
                $grade_arr['downgrade'] = $grade_arr['level'];//下一级会员等级
                $grade_arr['downgrade_name'] = $member_grade[$grade_arr['downgrade']]['level_name'];
                $grade_arr['downgrade_exppoints'] = $member_grade[$grade_arr['downgrade']]['growthvalue'];
                $grade_arr['upgrade'] = $member_grade[$grade_arr['level']+1]['level'];//上一级会员等级
                $grade_arr['upgrade_name'] = $member_grade[$grade_arr['upgrade']]['level_name'];
                $grade_arr['upgrade_exppoints'] = $member_grade[$grade_arr['upgrade']]['growthvalue'];
                $grade_arr['less_exppoints'] = $grade_arr['upgrade_exppoints'] - $growthvalue;
                $grade_arr['exppoints_rate'] = round(($growthvalue - $member_grade[$grade_arr['level']]['exppoints'])/($grade_arr['upgrade_exppoints'] - $member_grade[$grade_arr['level']]['exppoints'])*100,2);
            }
        }
        return $grade_arr;
    }
    //修改用户密码
	public function getUserPassword($password=null,$user=null){
		$password=md5($password);
		$condition=array();
		$condtion['member_name']=$user;
		$update=array();
		$update=array('member_passwd'=>$password);
		return $this->table('ssys_member')->where($condtion)->update($update);
	}
    //修改用户密码_张金凤——2017.08.20
    public function getUserPassword_wap($password=null,$tel=null){
        $password=md5($password);
        $condition=array();
        $condtion['member_mobile']=$tel;
        $update=array();
        $update=array('member_passwd'=>$password);
        return $this->table('ssys_member')->where($condtion)->update($update);
    }
	//修改用户的预存款
	public function eidtPdrAmount($condition,$update){
		return $this->table('ssys_member')->where($condition)->update($update);
	}

	public function isMemberExist($member_id){
        return $this->table('ssys_member')->where($member_id);
    }

    // ------------------推手与商城用户关系（分销推广上下级）-------------------------------------------

    public function get_member_nexus_select($condition,$field="*",$page=0)
    {
        return $this->table('ssys_member_nexus')->where($condition)->field($field)->page($page)->select();
    }

    public function get_member_nexus_count($condition)
    {
        return $this->table('ssys_member_nexus')->where($condition)->count();
    }

    public function get_member_nexus_find($condition,$field="*")
    {
        return $this->table('ssys_member_nexus')->where($condition)->field($field)->find();
    }

    public function save_member_nexus_data($data)
    {
        $data['add_time'] = time();
        return $this->table('ssys_member_nexus')->insert($data);
    }

    // 获取当前推手 处于用户的 几级关系
    public function get_member_spreader_nexus_level($spreader_member_id)
    {
        // 检查 该推手 是否存在 上级
    }

    // 执行member insert 语句
    public function insert_member_data($member_info){

        if (!empty($member_info)) {
            $result = Db::insert('ssys_member',$member_info);
            if($result) {
                $inderid = Db::getLastId();
                return $inderid;
            } else {
                return false;
            }
        }else{
            return false;
        }
    }



}
