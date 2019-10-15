<?php
namespace app\v1\controller;
use app\v1\model\Sms as Smslog;
use app\v1\model\User;
use think\cache\driver\Redis;
use think\Request;
use \Yunpian\Sdk\YunpianClient;
class Sms extends Base
{
    /**
     * 获取短信验证码
     * TODO
     */
    public  function getSmsMessage(){
        //TODO
        $type = input("type");//1注册，2登录，3找回密码
        $mobile = input("mobile");
        $sms = new Smslog();
        $condition = array();
        $condition['log_ip'] = getIp();
        $condition['log_type'] = $type;
        $sms_log = $sms->getSmsInfo($condition);
        $redis = new Redis();
        if(!input('Cache_name')){
            //return json_encode(array("error_code"=>2));
        }else if( !input('basestr')){
            //return json_encode(array("error_code"=>1));
        }else{
            $basestr = input("basestr");
            $name = input("Cache_name");
            $redisvalue = $redis->get($name);
            if($redisvalue!=$basestr."_".input("str")){
                $data['error_code']=10016;
                $data['message']="请求header参数错误";
                //return json_encode($data,true);
            }else{
                //$redis->clear($name);
            }
        }
        //$redis->set($type."_".$msectime,$signstr."_".$str,600*60);
        if(!empty($sms_log) && ($sms_log['add_time'] > TIMESTAMP-60)) {//同一IP十分钟内只能发一条短信
            $data['error_code'] = 10008;
            $data['message'] = '同一IP地址一分钟内，请勿多次获取动态码！';
            return json_encode($data,true);
            //  $state = Language::get('同一IP地址十分钟内，请勿多次获取动态码！');
        } else {
            $state = 'true';
            $log_array = array();
            $model_member = new User();
            $member = $model_member->getMemberInfo(array('member_mobile'=> $mobile));
            $captcha = rand(100000, 999999);
            $log_msg = str_replace('#code#',$captcha,"【海云舟】感谢您选择海云舟，您的注册验证码是#code#。海云舟，您的培训服务交易平台。");
            $log_msg = str_replace('#add#',Config('site_name'),$log_msg);
            switch ($type) {
                case '1':
                    if(Config('sms_register') != 1) {
                        $data['error_code'] = 10009;
                        $data['message'] = '系统没有开启手机注册功能！';
                        return json_encode($data,true);
                        // $state = Language::get('系统没有开启手机注册功能');
                    }
                    if(!empty($member)) {//检查手机号是否已被注册
                        $data['error_code'] = 10008;
                        $data['message'] = '当前手机号已被注册，请更换其他号码。';
                        return json_encode($data,true);
                        // $state = Language::get('当前手机号已被注册，请更换其他号码。');
                    }
                    break;
                case '2':
                    if(Config('sms_login') != 1) {
                        $data['error_code'] = 10010;
                        $data['message'] = '系统没有开启手机登录功能';
                        return json_encode($data,true);
                        //  $state = Language::get('系统没有开启手机登录功能');
                    }
                    if(empty($member)) {//检查手机号是否已绑定会员
                        $data['error_code'] = 10011;
                        $data['message'] = '当前手机号未注册，请检查号码是否正确。';
                        return json_encode($data,true);
                        // $state = Language::get('当前手机号未注册，请检查号码是否正确。');
                    }
                    $log_array['member_id'] = $member['member_id'];
                    $log_array['member_name'] = $member['member_name'];
                    break;
                case '3':
                    if(Config('sms_password') != 1) {
                        $data['error_code'] = 10012;
                        $data['message'] = '系统没有开启手机找回密码功能。';
                        return json_encode($data,true);
                        // $state = Language::get('系统没有开启手机找回密码功能');
                    }
                    if(empty($member)) {//检查手机号是否已绑定会员
                        $data['error_code'] = 10013;
                        $data['message'] = '当前手机号未注册，请检查号码是否正确。';
                        return json_encode($data,true);
                        //  $state = Language::get('当前手机号未注册，请检查号码是否正确。');
                    }
                    $log_array['member_id'] = $member['member_id'];
                    $log_array['member_name'] = $member['member_name'];
                    break;
                default:
                    //$state = Language::get('参数错误');
                    break;
            }
            if($state == 'true'){
                $sms = new Smslog();
                //$result = $sms->send($mobile,$log_msg,Config('mobile_tplid'));
                $clnt = YunpianClient::create("0acb46c3756b6154d65cf177083e320e");

                $param = [YunpianClient::MOBILE => $mobile,YunpianClient::TEXT => $log_msg];
                $r = $clnt->sms()->single_send($param);
//var_dump($r);
                if($r->isSucc()){
                    //$r->data()
                }
                if($r->isSucc()){
                    $countryCode = input("country_code")?86:input("country_code");
                    $mobile = $countryCode.$mobile;
                    $log_array['log_phone'] = $mobile;
                    $log_array['log_captcha'] = $captcha;
                    $log_array['log_ip'] = getIp();
                    $log_array['log_msg'] = $log_msg;
                    $log_array['log_type'] = $type;
                    $log_array['add_time'] = time();
                    $sms->addSms($log_array);
                    $data['error_code'] = 200;
                    $data['message'] = '短信验证码发送成功。';
                    return json_encode($data,true);
                } else {
                    print_r($r);die;
                    $data['error_code'] = 10014;
                    $data['message'] = '短信验证码发送失败。';
                    return json_encode($data,true);
                    // $state = Language::get('手机短信发送失败');
                }
            }
        }
    }
}