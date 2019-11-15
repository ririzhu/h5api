<?php
/**
 * 前台登录 退出操作
 *
 *
 */

defined('DYMall') or exit('Access Invalid!');

class loginCtl extends mobileHomeCtl {

    public function __construct(){
        parent::__construct();
    }

   /**
     * 登录
     */
    public function index(){
        if(empty($_POST['username']) || empty($_POST['password']) || !in_array($_POST['client'], $this->client_type_array)) {
            output_error('登录失败');
        }

        $model_member = M('ssys_member');

        $array = array();
        $array['member_name']   = $_POST['username'];
        $array['member_passwd'] = md5($_POST['password']);
        $member_info = $model_member->getMemberInfo($array);
        //如果用户名匹配不上的话就需要用手机号去匹配
        if(empty($member_info)){
            $member_info = $model_member->getMemberInfo(array('member_mobile'=>$_POST['username'],'member_passwd'=>md5($_POST['password'])));
        }
        if(!empty($member_info)) {
            $token = $this->_get_token($member_info['member_id'], $member_info['member_name'], $_POST['client']);
            if($token) {
                $condition=array();
                $condition['member_id']=$member_info['member_id'];
                $condition['is_default']=1;

                $share_code = con_addons('spreader',$member_info['member_id'],'encode_spreader_code','api');

                output_data(array('username' => $member_info['member_name'], 'key' => $token,'member_id'=>$member_info['member_id'],'share_code'=>$share_code,'logo'=>UPLOAD_SITE_URL.DS.ATTACH_AVATAR."/".$member_info['member_avatar'],'status'=>'1'));
            } else {
                output_data(array('error'=>'登录失败','status'=>0));
            }
        } else {
            output_data(array('error'=>'用户名密码错误','status'=>0));
        }
    }

    /**
     * 登录生成token
     */
    private function _get_token($member_id, $member_name, $client) {
        $model_mb_user_token = M('ssys_mb_user_token');

        //生成新的token
        $mb_user_token_info = array();
        $token = md5($member_name . strval(TIMESTAMP) . strval(rand(0,999999)));
        $mb_user_token_info['member_id'] = $member_id;
        $mb_user_token_info['member_name'] = $member_name;
        $mb_user_token_info['token'] = $token;
        $mb_user_token_info['login_time'] = TIMESTAMP;
        $mb_user_token_info['client_type'] = $_POST['client']?$_POST['client']:$client;

        $result = $model_mb_user_token->addMbUserToken($mb_user_token_info);

        if($result) {
            return $token;
        } else {
            return null;
        }

    }

    /**
     * 注册
     */
    public function register()
    {
        $model_member = M('ssys_member');

        $register_info = array();
        $register_info['username'] = $_POST['username'];
        $register_info['password'] = $_POST['password'];
        $register_info['password_confirm'] = $_POST['password_confirm'];
        $register_info['email'] = $_POST['email'];
        //*************开始处理推荐人id
//      $invite_info=$model_member->getMemberInfo(array('member_name'=>base64_decode($_POST['inviteid'])));
//      $register_info['inviter_id']=$invite_info['member_id'];//inviteid推荐人id 处理一下得到member_id//member_name
        $member_info = $model_member->mobileRegister($register_info);
        if (!isset($member_info['error'])) {
            $token = $this->_get_token($member_info['member_id'], $member_info['member_name'], $_POST['client']);
            if ($token) {
                output_data(array('username' => $member_info['member_name'], 'key' => $token));
            } else {
                output_error('注册失败');
            }
        } else {
            output_error($member_info['error']);
        }
    }

    //微信小程序根据code生成openid
    public function xcx_login(){
        $code = $_GET['code'];
        //获取openid
        $ch = curl_init();
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.C('xcx_appid').'&secret='.C('xcx_secret').'&js_code='.$code.'&grant_type=authorization_code';
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $response = curl_exec($ch);
        $response = json_decode($response,true);
        curl_close($ch);
        $_SESSION['xcx_openid']=$response['openid'];
//        $_SESSION['session_key']=$response['session_key'];
        //根据openid查是否有该用户
        $model_member = M('ssys_member');
        $member_info = $model_member -> getMemberInfo(array('wx_openid'=>$response['openid']));
        //用户的话 直接生成token，没有的话不做处理
        $return = array();
        if(!empty($member_info)){
            $token = $this->_get_token($member_info['member_id'], $member_info['member_name'],'wx_xcx');
            if($token){
                $return['state'] = '200';
                $return['token'] = $token;
                $return['member_id'] = $member_info['member_id'];
                $return['member_name'] = $member_info['member_name'];
            }else{
                $return['state'] = '250';
                $return['error'] = 'token生成错误';
            }
        }else{
            $return['state'] = '255';
        }
        echo json_encode(array('reinfo'=>$return));
    }
    //微信公众号根据code生成openid
    public function gzh_login(){
        $code = $_GET['code'];
        //获取openid
        $ch = curl_init();
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.C('gzh_appid').'&secret='.C('gzh_secret').'&code='.$code.'&grant_type=authorization_code';
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $response = curl_exec($ch);
        $response = json_decode($response,true);
        curl_close($ch);
        //获取微信用户信息
        $ch = curl_init();
        $url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$response['access_token'].'&openid='.$response['openid'].'&lang=zh_CN';
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $wx_userinfo = curl_exec($ch);
        $wx_userinfo = json_decode($wx_userinfo,true);

        curl_close($ch);
        $_SESSION['gzh_openid']=$wx_userinfo['openid'];
        $_SESSION['gzh_nickname']=$wx_userinfo['nickname'];
        $_SESSION['gzh_avatar']= $headimgurl =$wx_userinfo['headimgurl'];
        $_SESSION['gzh_sex']=$wx_userinfo['sex'];
        $_SESSION['gzh_province'] = $wx_userinfo['province'];
        $_SESSION['gzh_city'] = $wx_userinfo['city'];
        //根据openid查是否有该用户
        $model_member = M('ssys_member');
        $member_info = $model_member -> getMemberInfo(array('wx_openid'=>$response['openid']));
        //有用户的话 直接生成token并跳转到个人中心页面，没有的话直接跳转补充信息页面
        $return = array();
        if(!empty($member_info)){
            
            // 添加微信头像
            if($_SESSION['gzh_avatar'] && !$member_info['member_avatar']){
                $time = time();
                $avatar = @copy($headimgurl,BASE_UPLOAD_PATH.'/'.ATTACH_AVATAR."/avatar_".$time.".jpg");
                if($avatar) {
                    if(QINIU_ENABLE){
                        $re=qiniu_uploaded_file('data/upload'.'/'.ATTACH_AVATAR."/avatar_".$time.".jpg",BASE_UPLOAD_PATH.'/'.ATTACH_AVATAR."/avatar_".$time.".jpg");
                    }else if(OSS_ENABLE){
                        $re=new_uploaded_file('data/upload'.'/'.ATTACH_AVATAR."/avatar_".$time.".jpg",BASE_UPLOAD_PATH.'/'.ATTACH_AVATAR."/avatar_".$time.".jpg");
                    }
                    $update_member_data['member_avatar'] = "avatar_".$time.".jpg";
                    $model_member->updateMember($update_member_data,$member_info['member_id']);
                }
            }

            $token = $this->_get_token($member_info['member_id'], $member_info['member_name'],'wx_xcx');
            if($token){
                $return['state'] = '200';
                $return['token'] = $token;
                $return['member_id'] = $member_info['member_id'];
                $return['member_name'] = $member_info['member_name'];
                $return['ssys_share_code'] = $share_code = con_addons('spreader',$member_info['member_id'],'encode_spreader_code','api');;
                header('Location: '.SPREADER_WAP_SITE_URL.'/cwap_user.html?ssys_key='.$token.'&ssys_share_code='.$share_code);die;
            }else{
                $return['state'] = '250';
                $return['error'] = 'token生成错误';
                header('Location: '.SPREADER_WAP_SITE_URL.'/cwap_complete_account.html');die;
            }
        }else{
            $return['state'] = '255';
            header('Location: '.SPREADER_WAP_SITE_URL.'/cwap_complete_account.html');die;
        }
    }
    /**
     * 通过微信公众号注册用户
     */
    public function gzh_register(){
        $model_member   = M('ssys_member');
        $register_info = array();
        $register_info['mobile'] = $_POST['mobile'];
        $register_info['username'] = $_POST['mobile'];
        $register_info['password'] = $_POST['password'];
        //处理微信头像问题
        $headimgurl = $_SESSION['gzh_avatar'];//用户头像，最后一个数值代表正方形头像大小（有0、46、64、96、132数值可选，0代表640*640正方形头像）

//        $headimgurl = substr($headimgurl, 0, -1).'132';  把头像处理成132宽度的

        if($headimgurl){
            $time = time();
            $avatar = @copy($headimgurl,BASE_UPLOAD_PATH.'/'.ATTACH_AVATAR."/avatar_".$time.".jpg");
            if($avatar) {
                if(QINIU_ENABLE){
                    $re=qiniu_uploaded_file('data/upload'.'/'.ATTACH_AVATAR."/avatar_".$time.".jpg",BASE_UPLOAD_PATH.'/'.ATTACH_AVATAR."/avatar_".$time.".jpg");
                }else if(OSS_ENABLE){
                    $re=new_uploaded_file('data/upload'.'/'.ATTACH_AVATAR."/avatar_".$time.".jpg",BASE_UPLOAD_PATH.'/'.ATTACH_AVATAR."/avatar_".$time.".jpg");
                }
                $register_info['member_avatar'] = "avatar_".$time.".jpg";
            }else{
                $register_info['member_avatar'] = '';
            }
        }

        if($_SESSION['gzh_openid']){
            $register_info['wx_nickname'] = $_SESSION['gzh_nickname'];
            $register_info['wx_openid'] = $_SESSION['gzh_openid'];
            $register_info['wx_province'] = $_SESSION['gzh_province'];
            $register_info['wx_city'] = $_SESSION['gzh_city'];
        }
        $sms_vcode = $_POST['vcode'];
        if(strlen($sms_vcode)==6)
        {
            $condition['log_phone'] = $_POST['mobile'];
            $condition['log_captcha'] = $sms_vcode;
            $condition['log_type'] = 1;
            $model_sms_log = M('ssys_sms_log');
            $sms_log = $model_sms_log->getSmsInfo($condition);

            if(empty($sms_log) || ($sms_log['add_time'] < TIMESTAMP-1800)) {//半小时内进行验证为有效
                output_data(array('msg'=>'动态码错误或已过期，重新输入','state'=>'failuer'));
            }
        }else{
            output_data(array('msg'=>'请输入六位动态验证码','state'=>'failuer'));
        }
        $inviter_id = intval(base64_decode($_POST['inviteid']))/999999999;
        $register_info['inviter_id'] = $inviter_id;


        $member_info = $model_member->mobileRegister_wap($register_info);
        
        $par['ssys_member'] = $member_info;
        $par['register_info'] = $register_info;
        // 申请推手时 创建同商城用户
        $shop_register_result = con_addons('spreader',$par,'create_shop_member_register','api');
        if (!$shop_register_result) {
            $model_member->del($member_info['member_id']);
            $member_info['error'] = '商城系统用户关联失败';
        }

        if(!isset($member_info['error'])) {
            $token = $this->_get_token($member_info['member_id'], $member_info['member_name'], $_POST['client']);
            if($token) {

                $share_code = con_addons('spreader',$member_info['member_id'],'encode_spreader_code','api');

                output_data(array('username' => $member_info['member_name'], 'key' => $token,'share_code'=>$share_code,'state'=>'true'));
            } else {
                output_data(array('msg'=>'注册失败','state'=>'failuer'));
            }
        } else {
            output_data(array('msg'=>$member_info['error'],'state'=>'failuer'));
        }
    }
    //微信公众号绑定已有账号
    public function gzh_bind_account(){
        if(empty($_POST['username']) || empty($_POST['password']) || !in_array($_POST['client'], $this->client_type_array)) {
            output_error('登录失败');
        }

        $model_member = M('ssys_member');

        $array = array();
        $array['member_name']   = $_POST['username'];
        $array['member_passwd'] = md5($_POST['password']);
        $member_info = $model_member->getMemberInfo($array);
        //如果用户名匹配不上的话就需要用手机号去匹配
        if(empty($member_info)){
            $member_info = $model_member->getMemberInfo(array('member_mobile'=>$_POST['username'],'member_passwd'=>md5($_POST['password'])));
        }
        if(!empty($member_info)) {
            if(!empty($member_info['wx_openid'])&&($member_info['wx_openid']==$_SESSION['gzh_openid'])){
                output_data(array('error'=>'该账号已被别人绑定','status'=>255));die;
            }
            $token = $this->_get_token($member_info['member_id'], $member_info['member_name'], $_POST['client']);
            if($token) {
                //更新用户的openid和头像问题
                $data_new = array();

                $headimgurl = $_SESSION['gzh_avatar'];//用户头像，最后一个数值代表正方形头像大小（有0、46、64、96、132数值可选，0代表640*640正方形头像）
                // $headimgurl = substr($headimgurl, 0, -1).'132';
                if(empty($member_info['member_avatar'])){
                    if($headimgurl){
                        $time = time();
                        $avatar = @copy($headimgurl,BASE_UPLOAD_PATH.'/'.ATTACH_AVATAR."/avatar_".$time.".jpg");
                        if($avatar) {
                            if(QINIU_ENABLE){
                                $re=qiniu_uploaded_file('data/upload'.'/'.ATTACH_AVATAR."/avatar_".$time.".jpg",BASE_UPLOAD_PATH.'/'.ATTACH_AVATAR."/avatar_".$time.".jpg");
                            }else if(OSS_ENABLE){
                                $re=new_uploaded_file('data/upload'.'/'.ATTACH_AVATAR."/avatar_".$time.".jpg",BASE_UPLOAD_PATH.'/'.ATTACH_AVATAR."/avatar_".$time.".jpg");
                            }
                            $data_new['member_avatar'] = "avatar_".$time.".jpg";
                        }else{
                            $data_new['member_avatar'] = '';
                        }
                    }
                }
                if($_SESSION['gzh_openid']){
                    $data_new['wx_openid'] = $_SESSION['gzh_openid'];
                    $data_new['wx_nickname'] = $_SESSION['gzh_nickname'];
                    $data_new['wx_area'] = $_SESSION['gzh_province'].' '.$_SESSION['gzh_city'];
                }
                //更新用户的信息
                $model_member->editMember(array('member_id'=>$member_info['member_id']),$data_new);
                $condition=array();
                $condition['member_id']=$member_info['member_id'];
                $condition['is_default']=1;

                $share_code = con_addons('spreader',$member_info['member_id'],'encode_spreader_code','api');
                
                output_data(array('username' => $member_info['member_name'], 'key' => $token,'member_id'=>$member_info['member_id'],'share_code'=>$share_code,
                    'logo'=>UPLOAD_SITE_URL.DS.ATTACH_AVATAR."/".$member_info['member_avatar'],'status'=>'1'));
                header('Location: '.SPREADER_WAP_SITE_URL.'/cwap_user.html?ssys_key='.$token);die;
            } else {
                output_data(array('error'=>'绑定失败','status'=>0));
            }
        } else {
            output_data(array('error'=>'用户名密码错误','status'=>0));
        }
    }
    //微信小程序绑定已有账号
    public function gzh_bind_account_xcx(){
        if(empty($_GET['username']) || empty($_GET['password']) || !in_array($_GET['client'], $this->client_type_array)) {
            output_data(array('error'=>'绑定失败','status'=>255));die;
        }

        $model_member = M('ssys_member');

        $array = array();
        $array['member_name']   = $_GET['username'];
        $array['member_passwd'] = md5($_GET['password']);
        $member_info = $model_member->getMemberInfo($array);
        //如果用户名匹配不上的话就需要用手机号去匹配
        if(empty($member_info)){
            $member_info = $model_member->getMemberInfo(array('member_mobile'=>$_GET['username'],'member_passwd'=>md5($_GET['password'])));
        }
        $code = $_GET['code'];
        //获取openid
        $ch = curl_init();
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.C('xcx_appid').'&secret='.C('xcx_secret').'&js_code='.$code.'&grant_type=authorization_code';
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $response = curl_exec($ch);
        $response = json_decode($response,true);
        curl_close($ch);
        $openid=$response['openid'];
        if(!empty($member_info)) {
            if(!empty($member_info['wx_openid'])&&($member_info['wx_openid']!=$openid)){
                output_data(array('error'=>'该账号已被别人绑定','status'=>255));die;
            }
            $token = $this->_get_token($member_info['member_id'], $member_info['member_name'], $_GET['client']);
            if($token) {
                //更新用户的openid
                $data_new = array();
                if($openid){
                    $data_new['wx_openid'] = $openid;
                }else{
                    output_data(array('error'=>'openid获取失败','status'=>255));die;
                }
                //更新用户的信息
                $model_member->editMember(array('member_id'=>$member_info['member_id']),$data_new);
                output_data(array('key'=>$token,'status'=>200));die;
            } else {
                output_data(array('error'=>'绑定失败','status'=>255));die;
            }
        } else {
            output_data(array('error'=>'用户名密码错误','status'=>255));die;
        }
    }
    /**
     * 通过微信小程序注册用户
     */
    public function gzh_register_xcx(){
        $model_member   = M('ssys_member');
        $register_info = array();
        $register_info['mobile'] = $_GET['mobile'];
        $register_info['username'] = $_GET['mobile'];
        $register_info['password'] = $_GET['password'];
        
        $code = $_GET['code'];
        //获取openid
        $ch = curl_init();
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.C('xcx_appid').'&secret='.C('xcx_secret').'&js_code='.$code.'&grant_type=authorization_code';
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $response = curl_exec($ch);
        $response = json_decode($response,true);
        curl_close($ch);
        $_SESSION['xcx_openid']=$response['openid'];
        //处理微信头像问题
        // $_SESSION['xcx_openid'] = 'oWNIP0S56rYqpegtjMzN4yCWWiYU';
        if($_SESSION['xcx_openid']){
            $register_info['wx_openid'] = $_SESSION['xcx_openid'];
        }
        $sms_vcode = $_GET['smscode'];
        if(strlen($sms_vcode)==6)
        {
            $condition['log_phone'] = $_GET['mobile'];
            $condition['log_captcha'] = $sms_vcode;
            $condition['log_type'] = 1;
            $model_sms_log = M('ssys_sms_log');
            $sms_log = $model_sms_log->getSmsInfo($condition);

            if(empty($sms_log) || ($sms_log['add_time'] < TIMESTAMP-1800)) {//半小时内进行验证为有效
                output_data(array('msg'=>'动态码错误或已过期，重新输入','state'=>'failuer'));
            }
        }else{
            output_data(array('msg'=>'请输入六位动态验证码','state'=>'failuer'));
        }

        $member_info = $model_member->mobileRegister_wap($register_info);

        if(!isset($member_info['error'])) {
            $token = $this->_get_token($member_info['member_id'], $member_info['member_name'], $_GET['client']);
            if($token) {
                output_data(array('username' => $member_info['member_name'], 'key' => $token,'state'=>'true'));
            } else {
                output_data(array('msg'=>'注册失败','state'=>'failuer'));
            }
        } else {
            output_data(array('msg'=>$member_info['error'],'state'=>'failuer'));
        }
    }
}
