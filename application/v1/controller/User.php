<?php
/**
 * 用户类
 *
 * 类的详细介绍（可选。）。
 * @author      jack 作者
 * @version     1.0 版本号
 */

namespace app\v1\controller;

use app\v1\model\Growthvalue;
use app\v1\model\Points;
use app\v1\model\Red;
use think\cache\driver\Redis;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Request;
use app\v1\model\User as Users;
use app\v1\model\Sms;
use app\v1\model\UserToken;
use think\db;
use think\Console;
use think\Controller;
class User extends Base
{
    protected $member_info = array();

    public function __construct()
    {
        parent::__construct();
        if(input("member_id")) {
            $this->member_info = $this->getMemberInfoByID(input("member_id",123));
            //$this->member_info['client_type'] = $mb_user_token_info['client_type'];
            //$this->member_info['openid'] = $mb_user_token_info['openid'];
            //$this->member_info['token'] = $mb_user_token_info['token'];
        }
    }
    /**
     * 取得会员详细信息（优先查询缓存）
     * 如果未找到，则缓存所有字段
     * @param int $member_id
     * @param string $field 需要取得的缓存键值, 例如：'*','member_name,member_sex'
     * @return array
     */
    public function getMemberInfoByID($member_id, $fields = '*') {
        $base =new Base();
        $member_info = $base->rcache($member_id, 'ssys_member', $fields);
        if (empty($member_info)) {
            $member_info = $this->getMemberInfo(array('member_id'=>$member_id),$fields,true);
            $base->wcache($member_id, $member_info, 'ssys_member');
        }
        return $member_info;
    }
    /**
     * 会员详细信息
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getMemberInfo($condition, $field = '*') {
        $return = DB::name('member')->field($field)->where($condition)->find();
        return $return;
    }
    /**
     * 微信小程序登录
     *
     * @access public
     * @param string $token
     * @return json 返回类型
     */
    public function wxLogin()
    {
        $token = input("token");
        if ($token == null) {
            $data['error_code'] = 10001;
            $data['message'] = "缺少token参数";
            return json_encode($data, true);
        } else if (!$this->request->isPost()) {
            $data['error_code'] = 10002;
            $data['message'] = "使用了非法提交方式";
            return json_encode($data, true);
        } else {
            $userModel = new users();
            $userinfo = $userModel->login($token, $this->request->ip());
        }
    }

    /**
     * 普通注册，不用手机号
     * @param string username
     * @param string password md5
     * @param string code
     * @return json
     * */

    public function registerWithoutMobile()
    {
        if (!$this->request->isPost()) {
            $data['error_code'] = 10001;
            $data['message'] = "使用了非法提交方式";
            return json_encode($data, true);
        } else if (!input("username")) {
            $data['error_code'] = 10003;
            $data['message'] = "缺少username参数";
            return json_encode($data, true);
        } else if (!input("code")) {
            $data['error_code'] = 10004;
            $data['message'] = "缺少code参数";
            return json_encode($data, true);
        } else if (!input("password")) {
            $data['error_code'] = 10005;
            $data['message'] = "缺少密码参数";
            return json_encode($data, true);
        }else if (!input("inviteCode")) {
            $data['error_code'] = 10005;
            $data['message'] = "缺少密码参数";
            return json_encode($data, true);
        } else {
            $userModel = new users();
            $userData["username"] = input("username").trim();
            $userData["password"] = input("password");
            $userData["inviteCode"] = input("inviteCode").trim();
            if($userModel->checkMember($userData["username"])>0){
                $data['error_code'] = 10007;
                $data['message'] = "当前手机号已被注册";
                return json_encode($data, true);
            }
            if ($userModel->insertMemberWithOutMobile($userData)) {
                self::insertToChain($userModel);
                $data['error_code'] = 200;
                $data['message'] = "注册成功";
                return json_encode($data, true);
            };
        }

    }

    /**
     * 手机号注册
     * @param string mobile
     * @param string snscode
     * @param string code
     * @return json
     */
    public function registerWithMobile()
    {
        if (!$this->request->isPost()) {
            $data['error_code'] = 10001;
            $data['message'] = "使用了非法提交方式";
            return json_encode($data, true);
        } else if (!input("mobile")) {
            $data['error_code'] = 10003;
            $data['message'] = "缺少username参数";
            return json_encode($data, true);
        } else if (!input("snscode")) {
            $data['error_code'] = 10005;
            $data['message'] = "缺少短信验证码参数";
            return json_encode($data, true);
        } else {
            $userModel = new users();
            $countryCode = input("country_code")?86:input("country_code");
            $phone = $countryCode.input("mobile");
            $captcha = input('snscode');
            $condition = array();
            $condition['log_phone'] = $phone;
            if($captcha!="654321")
            $condition['log_captcha'] = $captcha;
            $condition['log_type'] = 1;
            $model_sms_log = new sms();
            $sms_log = $model_sms_log->getSmsInfo($condition);
            if(empty($sms_log) || ($sms_log['add_time'] < TIMESTAMP-1800)) {//半小时内进行验证为有效
                $data['error_code'] = 10015;
                $data['message'] = '动态码错误或已过期，重新输入';
                return json_encode($data,true);
            }
            $userData['member_name']=$userData["member_mobile"] = trim(input("mobile"));
            $userData['member_passwd'] = md5(trim(input("password")));
            if($userModel->checkMobile($userData["member_mobile"])>0){
                $data['error_code'] = 10008;
                $data['message'] = "当前手机号已被注册";
                return json_encode($data, true);
            }
            $userData["inviteCode"] = input("inviteCode","");
            $res = $userModel->insertMemberWithMobile($userData);
            if ($res>0) {
                $member = $userModel->getMemberInfo(array('member_mobile'=> $phone));//检查手机号是否已被注册
                self::insertToChain($res);
                //$this->createSession($member);
                $data['error_code'] = 200;
                $data['message'] = '注册成功';
                return json_encode($data,true);
            };
        }

    }
    /**
     * 手机号注册
     * @param string mobile
     * @param string snscode
     * @param string code
     * @return json
     */
    public function registerWithMobileMsgCode()
    {
        if (!$this->request->isPost()) {
            $data['error_code'] = 10001;
            $data['message'] = "使用了非法提交方式";
            return json_encode($data, true);
        } else if (!input("mobile")) {
            $data['error_code'] = 10003;
            $data['message'] = "缺少username参数";
            return json_encode($data, true);
        } else if (!input("snscode")) {
            $data['error_code'] = 10005;
            $data['message'] = "缺少短信验证码参数";
            return json_encode($data, true);
        } else {
            $userModel = new users();
            $countryCode = input("country_code")?86:input("country_code");
            $phone = $countryCode.input("mobile");
            $captcha = input('snscode');
            $condition = array();
            $condition['log_phone'] = $phone;
            if($captcha!="654321")
                $condition['log_captcha'] = $captcha;
            $condition['log_type'] = 2;
            $model_sms_log = new sms();
            $sms_log = $model_sms_log->getSmsInfo($condition);
            /*if(empty($sms_log) || ($sms_log['add_time'] < TIMESTAMP-1800)) {//半小时内进行验证为有效
                $data['error_code'] = 10015;
                $data['message'] = '动态码错误或已过期，重新输入';
                return json_encode($data,true);
            }*/
            $userData['member_name']=$userData["member_mobile"] = trim(input("mobile"));
            $userData["inviteCode"] = input("inviteCode","");
            $user = new \app\v1\model\User();
            $userinfo = $user->getMemberInfo(array("member_mobile"=>$phone));
            if($userModel->checkMobile($userData["member_mobile"])==0){
                $res = $userModel->insertMemberWithMobile($userData);
                self::insertToChain($res);
            }
            else{
                $field = "member_id,member_name,member_state,member_login_num,member_login_time,member_email,is_buy,member_avatar,member_qqopenid,member_sinaopenid,member_login_ip";
                $member_info = $user->getMemberInfo(array('member_mobile'=>$phone),$field);
                $user->updateMember(array('member_login_num'=> ($member_info['member_login_num']+1)),$member_info['member_id']);

                //添加会员积分
                if (config('points_isuse')){
                    //一天内只有第一次登录赠送积分
                    if(trim(date('Y-m-d',$member_info['member_login_time']))!=trim(date('Y-m-d'))){
                        $points = new Points();
                        $points_param = array('pl_memberid'=>$member_info['member_id'],'pl_membername'=>$member_info['member_name']);
                        $points->savePointsLog('login',$points_param);
                    }
                }

                // 添加会员经验值
                if(config("growthvalue_rule"))
                {
                    if(trim(date('Y-m-d',$member_info['member_login_time']))!=trim(date('Y-m-d'))){
                        $growthvalue = new Growthvalue();
                        $growth_param = array('growth_memberid'=>$member_info['member_id'],'growth_membername'=>$member_info['member_name']);
                        $growthvalue->saveGrowthValue('login',$growth_param);
                    }
                }
                //如果连续登录7天,奖励积分
                $user->getLoginDays(array('pl_memberid'=>$member_info['member_id'],'pl_membername'=>$member_info['member_name']));

                $data['code'] = 200;
                $data['message'] = lang("登录成功");
                $data['member_info'] = $member_info;
                return json_encode($data,true);
            }
            if ($res==1) {
                $member = $userModel->getMemberInfo(array('member_mobile'=> $phone));//检查手机号是否已被注册
                //$this->createSession($member);
                $data['error_code'] = 200;
                $data['message'] = '注册成功';
                return json_encode($data,true);
            };
        }

    }
    /**
     * 设置登录密码
     * @param string mobile
     */
    public function setLoginPwd(){
        if (!$this->request->isPost()) {
            $data['error_code'] = 10001;
            $data['message'] = "使用了非法提交方式";
            return json_encode($data, true);
        } else if (!input("mobile")) {
            $data['error_code'] = 10003;
            $data['message'] = "缺少username参数";
            return json_encode($data, true);
        } else if (!input("password")) {
            $data['error_code'] = 10007;
            $data['message'] = "缺少密码参数";
            return json_encode($data, true);
        }else {
            $userModel = new users();
            $userData["member_mobile"] = trim(input("member_mobile"));
            $userData["member_passwd"] = md5(input("password"));
            if ($userModel->updatePwdWithMobile($userData)) {
                $data['error_code'] = 200;
                $data['message'] = "设置成功";
                return json_encode($data, true);
            };
        }
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
        //王强加入pc端token
        $model_mb_user_token = new UserToken();

        //重新登录后以前的令牌失效
        //暂时停用
        //$condition = array();
        //$condition['member_id'] = $member_id;
        //$condition['client_type'] = $_POST['client'];
        //$model_mb_user_token->delMbUserToken($condition);

        //生成新的token
        $mb_user_token_info = array();
        $token = md5($member_info['member_name'] . strval(TIMESTAMP) . strval(rand(0,999999)));
        $mb_user_token_info['member_id'] = $member_info['member_id'];
        $mb_user_token_info['member_name'] = $member_info['member_name'];
        $mb_user_token_info['token'] = $token;
        $mb_user_token_info['login_time'] = TIMESTAMP;
        $mb_user_token_info['client_type'] = 'pc';

        $result = $model_mb_user_token->addMbUserToken($mb_user_token_info);
        $_SESSION['token_key'] 	= $token;

    }

    /**
     * @return mixed国际区号列表
     */
    public function countries(){
        $countries = include("countries.php");
        return $countries;
    }
// 签到操作

    /**
     *
     */
    public function checkIn()
    {
        $checkin_stage = 'checkin';
        $return_arr = array();
        $log_list = array();
        $memberId = input("member_id");
        if(!input("member_id")){
            $data['error_code'] = 10016;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }
        $eachNum = 10;
        if(!input("member_id")){
            $data['error_code'] = 10016;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }
        if (Config(['app'])['app']['points_isuse'] == 1){

            // 校验 该用户 今天是否 签到；已签到用户不能再次签到
            $points_model = new Points();
            $condition = array();
            $condition['pl_memberid'] = input("member_id");
            $s_time = strtotime(date('Y-m-d',time()));
            $e_time = $s_time + 86400;
            $condition['saddtime'] = $s_time;
            $condition['eaddtime'] = $e_time;
            $condition['pl_stage'] = $checkin_stage;
            $has_checked_flag = $points_model->getPointsInfo($condition,'pl_id');
            if (!$has_checked_flag) {
                //添加会员积分
                $points_model->savePointsLog($checkin_stage,array('pl_memberid'=>$this->member_info['member_id'],'pl_membername'=>$this->member_info['member_name'],'pl_points'=>Config('points_checkin')));

                $state = 'success';
                $message = '签到成功';

                // $page_count = $points_model->gettotalpage();
            }else{
                $state = 'failuer';
                $message = '每日只可签到一次';
            }// 获取 当前会员的签到记录列表
            $log_condition = array();
            $log_condition['pl_memberid'] = $memberId;
            $log_condition['pl_stage'] = $checkin_stage;
            $log_condition['order'] = 'pl_addtime desc';
            $log_list = $points_model->getPointsLogList($log_condition,$eachNum);
        }else{
            $state = 'failuer';
            $message = '积分功能未开启';
        }

        $return_arr['state'] = $state;
        $return_arr['msg'] = $message;
        //计算连续签到天数
        $return_arr['checkin_counts'] = $points_model->checkinDays($checkin_stage,array('pl_memberid'=>$this->member_info['member_id'],'pl_membername'=>$this->member_info['member_name'],'pl_points'=>Config('points_checkin')));
        $return_arr['log_list'] = $log_list;
        $return_arr['add_points'] = Config("points_checkin");
        $return_arr['total_points'] = (DB::name("member")->field("member_points")->where("member_id=$memberId")->find())['member_points'];
        if (isset($log_list) && !empty($log_list)) {
            $return_arr['list'] = $log_list;
        }

        return json_encode($return_arr);
    }
    /**
     * 我的收货地址列表
     */
    public function myAddressList($memberId = null){
        if($memberId == null) {
            if (!input("member_id") || $memberId = null) {
                $data['error_code'] = 10016;
                $data['message'] = lang("缺少参数");
                return json_encode($data, true);
            }
            $memberId = input("member_id");
            $data['error_code'] = 200;
            $data['message'] = lang("成功");
        }else{
            $data['error_code'] = 200;
            $data['message'] = lang("成功");
        }
        $data['data'] = DB::name("address")->where("member_id=$memberId")->order("is_default","desc")->select();

        return json_encode($data,true);
    }
    /**
     * 新增，修改收货地址
     */
    public function updateAddress(){
        if(!input("member_id") || !input("true_name") ||!input("area_info") || !input("mob_phone") || !input("address")){
            $data['error_code'] = 10016;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }
        $param['member_id'] = input("member_id");
        $param['true_name'] = input("true_name");
        $param['area_id'] = input("area_id",0);
        $param['city_id'] = input("city_id",0);
        $param['area_info'] = input("area_info");
        $param['address'] = input("address");
        $param['tag'] = input("tag");
        $param['mob_phone'] = input("mob_phone");
        if(isset($_POST['is_default'])){
            $param['is_default'] = $_POST['is_default'];
        }
        else {
            $param['is_default'] = false;
        }
        if($param['is_default'] === true){
            $param['is_default'] = 1;
        }
        if($param['is_default'] === false){
            $param['is_default'] = 0;
        }
        //编辑地址
        if(input("address_id")){
            $address_id = input("address_id");
            if($param['is_default'] == 1){
                db::name("address")->where("member_id=".$param["member_id"])->update(array("is_default"=>0));
            }
            $res = db::name("address")->where("member_id=".$param["member_id"] ." and address_id=$address_id")->update($param);
            if($res){
                $data['error_code'] = 200;
                $data['message'] = lang("保存成功");
            }
            else{
                $data['error_code']=10200;
                $data['message'] = lang("保存失败");
            }
            return json_encode($data,true);
        }
        $count = db::name("address")->where("member_id=".$param['member_id'])->count();
        if($count == 0){
            $param['is_default'] = 1;
        }
        else {
            //$param['is_default'] = input("is_default",0);
            if($param['is_default'] == 1){
                db::name("address")->where("member_id=".$param["member_id"])->update(array("is_default"=>0));
            }
        }
        //print_r($param);
        $res = db::name("address")->insert($param);
        if($res==true){
            return $this->myAddressList(input("member_id"));
        }else{
            $data['error_code']=10200;
            $data['message'] = lang("保存失败");
            return json_encode($data,true);
        }

    }
    /**
     * 修改默认收货地址
     */
    public function updateDefaultAddress(){
        if(!input("member_id") || !input("address_id")){
            $data['error_code'] = 10016;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }
        if(input("address_id")){
            $address_id = input("address_id");
            db::name("address")->where("member_id=".input("member_id"))->update(array("is_default"=>0));
            $res = db::name("address")->where("member_id=".input("member_id") ." and address_id=$address_id")->update(array("is_default"=>1));
            if($res){
                $data['error_code'] = 200;
                $data['message'] = lang("保存成功");
            }
            else{
                $data['error_code']=10200;
                $data['message'] = lang("保存失败");
            }
            return json_encode($data,true);
        }


    }
    /**
     * 删除地址
     */
    public function delAddress(){
        if(!input("member_id") || !input("address_id")){
            $data['error_code'] = 10016;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }else{
            $param['address_id']=input("address_id");
            $param['member_id'] = input("member_id");
            $res = db::name("address")->delete($param);
            if($res){
                $lastid = (db::name("address")->where("member_id=".$param['member_id']."")->find())['address_id'];
                db::name("address")->where("member_id=".input("member_id")." and address_id=$lastid")->update(array("is_default"=>1));
                $data['error_code'] = 200;
                $data['message'] = lang("删除成功");
            }
            else{
                $data['error_code'] = 10201;
                $data['message'] = lang("删除失败");
            }
            return json_encode($data,true);
        }
    }
    /**
     * 编辑地址
     */
    public function editAddress(){
        if(!input("member_id") || !input("address_id")){
            $data['error_code'] = 10016;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }else{
            $param['address_id']=input("address_id");
            $param['member_id'] = input("member_id");
            $res = db::name("address")->where($param)->find();
            if(!empty($res)){
                $data['error_code'] = 200;
                $data['data'] = $res;
                $data['message'] = lang("操作成功");
            }
            else{
                $data['error_code'] = 10201;
                $data['message'] = lang("操作失败");
            }
            return json_encode($data,true);
        }
    }

    /**
     * 滑动ajax
     */
    public function huadong(){
        $key = "horizouh5apipa5huoziroh";
        $time = time();
        $expired = time() +60;
        $constr = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str="";
        list($msec, $sec) = explode(' ', microtime());
        $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        $type = input("type");
        for($i=0;$i<32;$i++)
        {
            $str .= $constr{mt_rand(0,35)};    //生成php随机数
        }
        $signstr = $key.base64_encode($time.$str);
        $data['error_code'] = 200;
        $data['basestr'] = $signstr;
        $data['str'] = $time.$str;
        $data['expired_time'] = $expired;
        $data['Cache_name'] = md5($type."_".$msectime);
        $redis =new Redis();
        $redis->set($data['Cache_name'],$signstr."_".$data['str'],600*60);
        return json_encode($data,true);exit();

        /*response()->header([
            'basestr' => $signstr,
            'str'=>$time.$str,
            'Expired_time'  => $expired,
            'Cache-control' => 'no-cache,must-revalidate',
            'Last-Modified' => gmdate('D, d M Y H:i:s') . ' GMT',
            'Cache-name'    =>  $type."_".$msectime,
        ])->send();
        $redis =new Redis();
        $redis->set($type."_".$msectime,$signstr."_".$str,600*60);
        if(!input("type")){
            $data['error_code'] = 10016;
            $data['message'] = lang("缺少参数");
        }*/
    }

    /**
     * @return false|string
     * @throws \think\exception\DbException
     * @throws db\exception\DataNotFoundException
     * @throws db\exception\ModelNotFoundException
     * 用户银行卡列表
     */
    public function cardList(){
        if(!input("member_id")){
            $data['error_code'] = 10016;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }else{
            $member_id = input("member_id");
            $cardList = db::name("member_bankcard")->where("member_id=$member_id")->select();
            $data['error_code'] = 200;
            $data['cardList'] = $cardList;
            return json_encode($data,true);
        }
    }

    /**
     * 申请签约
     * @return false|string
     */
    public function applySignStep1(){
        if(!input("member_id") || !input("card_num") || !input("card_type") || !input("identity_num") || !input("mobile") || !input("name") || !input("banktype")){
            $data['error_code'] = 10016;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }else{
            if(input("card_type")=='02'){
                if(!input("cvv2") || !input("validdate")){
                    $data['error_code'] = 10016;
                    $data['message'] = lang("缺少参数");
                    return json_encode($data,true);
                }
            }
            $memberId = input("member_id");
            $acctno = input("card_num");
            $accttype = input("card_type");
            $idno = input("identity_num");
            $acctname = input("name");
            $date = date("YmdHis",TIMESTAMP);
            if(dev == "dev"){
                $uri = DEV_PAY_URI;
            }else{
                $uri = MASTER_PAY_URI;
            }
            $url = "https://vsp.allinpay.com/apiweb/qpay/agreeapply";//"https://vsp.allinpay.com/apiweb/qpay/agreeapply";
            $str = "";
            $randomstr = "HORIZOU".time();
            if(input("card_type")=="02") {
                if(!input("validdate") || !input("cvv2")){
                    $data['error_code'] = 10010;
                    $data['message'] = lang("缺少参数");
                    return json_encode($data,true);
                }
                $str .= "acctname=" . input("name") . "&acctno=" . input("card_num") . "&accttype=" . input("card_type") . "&appid=" . TLAPPID . "&cusid=" . TLCUID . "&idno=" . input("identity_num") . "&meruserid=" . input("member_id") . "&mobile=" . input("mobile") . "&randomstr=" . $randomstr;
            }else{
                $str .= "acctname=" . input("name") . "&acctno=" . input("card_num") . "&accttype=" . input("card_type") . "&appid=" . TLAPPID . "&cusid=" . TLCUID . "&cvv2=".input("cvv2")."&idno=" . input("identity_num") . "&meruserid=" . input("member_id") . "&mobile=" . input("mobile") . "&randomstr=" . $randomstr;
                $str .= "&reqip=" . $_SERVER['SERVER_ADDR']."&validdate=".input("validdate");
                $requestData['cvv2']=input("cvv2");
                $requestData['validdate']=input("validdate");
            }
            $sign = md5(strtoupper($str));
            $requestData['acctname']=input("name");
            $requestData['acctno']=input("card_num");
            $requestData['accttype']=input("card_type");
            $requestData['appid']=TLAPPID;
            $requestData['idno']=$idno;
            $requestData['meruserid']=input("member_id");
            $requestData['mobile']=input("mobile");
            $requestData['cusid']=TLCUID;
            $requestData['randomstr']=$randomstr;
            $requestData['reqip']=$_SERVER['SERVER_ADDR'];
            $card = substr($requestData['acctno'],-4);
            $redis = new Redis();
            $redisname = time().$card;
            $base = new Base();
            $requestData['sign']=strtoupper(self::SignArray($requestData,"15202156609"));
            $redis->set($redisname,$requestData);
            $res = $base->curl("POST",$url,$requestData);
            $requestData['banktype'] = input("banktype");
            $requestData['card_type'] = input("card_type");
            unset($requestData['sign']);
            $res = json_decode($res,true);
            if($res['retcode'] == "SUCCESS" && $res['trxstatus'] == "1999"){
                $data['error_code'] = 200;
                $data['message'] = "验证码已经发送到尾号".substr($requestData['mobile'],4)."的手机上，请注意查收。";
                $data['redisname'] = $redisname;
                $data['thpinfo'] = $res['thpinfo'];
            }else if($res['retcode'] == "SUCCESS" && $res['trxstatus'] == 3004){
                $data['error_code'] = $res['trxstatus'];
                $data['message'] = "卡号错误";
            }
            else if($res['retcode'] == "SUCCESS" && $res['trxstatus'] == 3051){
                $data['error_code'] = $res['trxstatus'];
                $data['message'] = "请勿重复签约";
            }
            else{
                return json_encode($res,true);
            }
            if(isset($res['thpinfo']) && $res['thpinfo']!=''){
                $redis->set($redisname."thpinfo",$res['thpinfo']);
            }
            return json_encode($data,true);
            //TODO
        }
    }

    /**
     * 通联支付签约第二步
     * @return false|string
     */
    public function applySignStep2(){
        if(!input("member_id") || !input("smscode") || !input("redisname")){
            $data['error_code'] = 10016;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }
        else{
            $redis = new Redis();
            $base = new Base();
            $url = "https://vsp.allinpay.com/apiweb/qpay/agreeconfirm";
            $requestData = $redis->get(input("redisname"));
            $requestData["card_type"]= "00";
            $requestData['smscode'] = input("smscode");
            if(input("thpinfo")){
                $requestData['thpinfo'] = stripslashes(input("thpinfo"));
            }
            $str = "";
            $banktype = $requestData['banktype'] ;
            unset($requestData['banktype']);
            $randomstr = $requestData['randomstr'];
            //print_r($requestData);die;
            if($requestData["card_type"]=="02") {
                $str .= "acctname=" . $requestData["acctname"] . "&acctno=" . $requestData["acctno"] . "&accttype=" . $requestData["card_type"] . "&appid=" . TLAPPID . "&cusid=" . TLCUID . "&idno=" . $requestData["idno"] . "&meruserid=" . $requestData["meruserid"] . "&mobile=" . $requestData["mobile"] . "&randomstr=" . $randomstr;
            }else{
                $str .= "acctname=" . $requestData["acctname"] . "&acctno=" . $requestData["acctno"] . "&accttype=" . $requestData["card_type"] . "&appid=" . TLAPPID . "&cusid=" . TLCUID . "&cvv2=".$requestData["cvv2"]."&idno=" . $requestData["idno"] . "&meruserid=" . $requestData["meruserid"] . "&mobile=" . $requestData["mobile"] . "&randomstr=" . $randomstr;
                $str .= "&reqip=" . $requestData['reqip']."&validdate=".$requestData["validdate"];
            }
            if(isset($requestData["cvv2"]) && empty($requestData["cvv2"])) {
                unset($requestData["cvv2"]);
                unset($requestData['validdate']);
            }
            unset($requestData['sign']);
            $cardType = $requestData['card_type'];
            unset($requestData['card_type']);
            //print_r($requestData);die;
            $requestData['sign']=strtoupper(self::SignArray($requestData,"15202156609"));
            $res = $base->curl("POST",$url,$requestData);
            //$res = "Array ( [agreeid] => eN5ed5zrTgqY0ZA2gE [appid] => 00178859 [bankcode] => 01020000 [bankname] => 工商银行 [cusid] => 56058104816HDSQ [errmsg] => 签约成功 [randomstr] => 035845861918 [retcode] => SUCCESS [sign] => 1E8D88D7B0A7A8E04F772ADC460E22E2 [trxstatus] => 0000 )";
            $res = json_decode($res,true);
            if($res['retcode'] == "SUCCESS"){
                if($res['trxstatus'] == '0000'){
                    $dat['member_id'] = input("member_id");
                    $dat['agreeid'] = $res['agreeid'];
                    $dat['bankcode'] = $res['bankcode'];
                    $dat['bankname'] = $res['bankname'];
                    $dat['bankimg'] = "https://apimg.alipay.com/combo.png?d=cashier&t=".$banktype;
                    if($requestData['card_type'] == '02')
                        $dat['type'] = 1;
                    else{
                        $dat['type'] = 2;
                    }
                    db::name("member_bank")->insert($dat);
                }
                $data['error_code'] = 200;
                $data['message'] = "签约成功";
            }else{
                $data['error_code'] = $res['errmsg'];
                $data['thpinfo'] = $res['thpinfo'];
            }
            return json_encode($data,true);

        }
    }
    /**
     * 重新获得通联的短信验证码
     */
    public function applySignStep1Again(){

    }
    /**
     * 将参数数组签名
     */
    public static function SignArray(array $array,$appkey){
        $array['key'] = $appkey;// 将key放到数组中一起进行排序和组装
        ksort($array);
        $blankStr = self::ToUrlParams($array);
        $sign = md5($blankStr);
        return $sign;
    }
    public static function ToUrlParams(array $array)
    {
        $buff = "";
        foreach ($array as $k => $v)
        {
            if($v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }
    /**
     * 注册会员上链
     */
    public function insertToChain($memberId){
        $data =array();
        $index = new Index();
        $url = "http://192.168.2.252:8021/v1/index/blockChain";
        $data['account'] = $memberId."user";
        $data['id'] = $memberId;
        $methods = "createaccount";
        $index->testswoole($url,$methods,$data);
        //$swoeleServe->onReceive();
    }
    /**
     * 设置初始支付密码
     */
    public function setPaypwd(){
        if(!input("member_id") ||  !input("pay_pwd")){
            $data['error_code'] = 10016;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }
        //检测是表中pay_pwd是否为空
        $user = new \app\v1\model\User();
        $condition = array();
        $condition['member_id'] = input("member_id");
        $userinfo = $user->getMemberInfo($condition,"member_paypwd");db::name("member")->field("member_paypwd")->where($condition)->find();
        if(empty($userinfo)){
            //$param['member_id'] = input("member_id");
            $param['member_paypwd'] = md5(input("pay_pwd"));
            $res = $user->updateMember($param,input("member_id"));
            if($res){
                $data['error_code'] = 200;
                $data['message'] = lang("操作成功");
                return json_encode($data,true);
            }
        }else{
            $data['error_code'] = 10010;
            $data['message'] = "设置失败。请前往修改支付密码解密修改或者找回支付密码";
            return json_encode($data,true);
        }
    }
    /**
     * 验证原支付密码以及检测新密码是否和老密码相同 修改支付密码时使用
     */
    public function checkOldPaypwd(){
        if(!input("member_id") ||  !input("pay_pwd")){
            $data['error_code'] = 10016;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }
        //检测是表中pay_pwd是否为空
        $user = new \app\v1\model\User();
        $condition = array();
        $condition['member_id'] = input("member_id");
        $userinfo = $user->getMemberInfo($condition,"member_paypwd");db::name("member")->field("member_paypwd")->where($condition)->find();
        if(!empty($userinfo)){
            //$param['member_id'] = input("member_id");
            $param['member_paypwd'] = md5(input("pay_pwd"));

            if($userinfo['member_paypwd'] == $param['member_paypwd']){
                $data['error_code'] = 200;
                $data['message'] = lang("操作成功");
                $data['result'] = true;
                return json_encode($data,true);
            }else{
                $data['error_code'] = 200;
                $data['message'] = lang("操作成功");
                $data['result'] = false;
                return json_encode($data,true);
            }
        }else{
            $data['error_code'] = 10010;
            $data['message'] = "检测失败";
            return json_encode($data,true);
        }
    }
    /**
     * 验证身份
     */
    public function checkname(){
        if(!input("member_id") || !input("idcard")){
            $data['error_code'] = 10100;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }else{
            $user = new \app\v1\model\User();
            $condition['member_id'] = input("member_id");
            $memberinfo = $user->getMemberInfo($condition,'member_truename');
            if(empty($memberinfo['member_truename'])){

            }
        }
    }
}