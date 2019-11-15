<?php
namespace app\v1\model;

use app\v1\controller\Base;
use think\Model;

class SendMemberMessage extends Model
{


    private $code = '';
    private $member_id = 0;
    private $member_info = array();
    private $mobile = '';
    private $email = '';
    private $link;
    private $system_type;

    /**
     * 设置 提醒发送
     *
     * @param mixed $key
     * @param mixed $value
     */
    public function set($key, $value)
    {
        $this->$key = $value;
    }

    public function send($param = array())
    {
        $base =new Base();
        $msg_tpl = $base->rkcache('member_msg_tpl', true);
        if (!isset($msg_tpl[$this->code]) || $this->member_id <= 0) {
            return false;
        }
        $tpl_info = $msg_tpl[$this->code];
        $mms = new MemberMsgSetting();
        $setting_info = $mms->getMemberMsgSettingInfo(array('mmt_code' => $this->code, 'member_id' => $this->member_id), 'is_receive');
        if (empty($setting_info) || $setting_info['is_receive']) {
            // 发送站内信
            if ($tpl_info['mmt_message_switch']) {
                $message = sldReplaceText($tpl_info['mmt_message_content'], $param);
                $data = array();
                $data['message'] = $message;
                $data['system_type'] = $this->system_type;
                $data['link'] = $this->link;

                $this->sendMessage($data);

            }
            // 发送短消息
            if ($tpl_info['mmt_short_switch']) {
                $this->getMemberInfo();
                if (!empty($this->mobile)) $this->member_info['member_mobile'] = $this->mobile;
                if ($this->member_info['member_mobile_bind'] && !empty($this->member_info['member_mobile']) && $tpl_info['mmt_short_number']) {
                    $param['site_name'] = Config('site_name');
                    $message = sldReplaceText($tpl_info['mmt_short_content'], $param);
                    $this->sendShort($this->member_info['member_mobile'], $message, $tpl_info['mmt_short_number']);
                }
            }
            // 发送邮件
            if ($tpl_info['mmt_mail_switch']) {
                $this->getMemberInfo();
                if (!empty($this->email)) $this->member_info['member_email'] = $this->email;
                if ($this->member_info['member_email_bind'] && !empty($this->member_info['member_email'])) {
                    $param['site_name'] = Config('site_name');
                    $param['mail_send_time'] = date('Y-m-d H:i:s');
                    $subject = sldReplaceText($tpl_info['mmt_mail_subject'], $param);
                    $message = sldReplaceText($tpl_info['mmt_mail_content'], $param);
                    $this->sendMail($this->member_info['member_email'], $subject, $message);
                }
            }

            //发送微信
            if ($tpl_info['mmt_weixin_switch']) {
                $this->getMemberInfo();
                if ($this->member_info['wx_openid'] && $tpl_info['mmt_weixin_number']) {
                    include_once BASE_ROOT_PATH . '/cmobile/control/jssdk.php';
                    $jssdk = new jssdkCtl();

                    $access_token = $jssdk->getAccessToken();
                    $send_url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=' . $access_token;
                    $wqpar['touser'] = $this->member_info['wx_openid'];
                    $wqpar['template_id'] = $tpl_info['mmt_weixin_number'];
                    $wqpar['url'] = $param['url'];
                    $wqpar['topcolor'] = $param['topcolor'] ? $param['topcolor'] : '#ffffff';
                    $dats = $param;
                    // unset($dats['url']);
                    foreach ($dats as $k => $v) {
                        $wqpar[data][$k] = array('value' => $v, 'color' => '#333333');
                    }
//                    print_r($wqpar);
                    $re = request_json($send_url, $wqpar);
                    write_log($re);
                }
            }
        }
    }

    /**
     * 会员详细信息
     */
    private function getMemberInfo()
    {
        if (empty($this->member_info)) {
            $member = new User();
            $this->member_info = $member->getMemberInfoByID($this->member_id);
        }
    }

    /**
     * 发送站内信
     * @param unknown $message
     */
    private function sendMessage($data)
    {
        //添加短消息
        $model_message = new Message();
        $insert_arr = array();
        $insert_arr['from_member_id'] = 0;
        $insert_arr['member_id'] = $this->member_id;
        $insert_arr['msg_content'] = $data['message'];
        $insert_arr['message_type'] = 1;
        $insert_arr['system_type'] = $data['system_type'];
        $insert_arr['link'] = $data['link'];
        $model_message->saveMessage($insert_arr);
    }

    /**
     * 发送短消息
     * @param unknown $number
     * @param unknown $message
     */
    private function sendShort($number, $message, $tpl_id)
    {
        $sms = new Sms();
        $sms->send($number, $message, $tpl_id);
    }

    /**
     * 发送邮件
     * @param unknown $number
     * @param unknown $subject
     * @param unknown $message
     */
    private function sendMail($number, $subject, $message)
    {
        // 计划任务代码
        $insert = array();
        $insert['mail'] = $number;
        $insert['subject'] = $subject;
        $insert['contnet'] = $message;
        // Model('mail_cron')->addMailCron($insert);
    }


}