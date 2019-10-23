<?php
namespace app\v1\controller;

use app\v1\model\Points;
use think\cache\driver\Redis;
use think\db;
class Weixin extends  Base
{
    /**
     * @return false|string
     *
     */
    public function config(){
        $url=input("url",10016);
        $ticket = $this->getTicket();
        $noncestr = "HorizouApiipAuoziroH";
        $timestrap = time();
        $string = "jsapi_ticket=$ticket&noncestr=$noncestr&timestamp=$timestrap&url=$url";
        $signature = sha1($string);
        $data['ticket'] = $ticket;
        $data['error_code'] = 200;
        $data['appId'] = WXAPPID;
        $data['timestamp'] = $timestrap;
        $data['nonceStr'] = $noncestr;
        $data['signature'] = $signature;
        return json_encode($data,true);
    }
    /**
     * 获取accesstoken
     */
    private function getAccessToken(){
        $redis = new Redis();
        if(!$redis->has("access_token")) {
            $json = file_get_contents("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . WXAPPID . "&secret=" . WXAPPSECRET . "");
            $access_token = json_decode($json,true)['access_token'];
            $redis->set("access_token",$access_token,7000);
        }else{
            $access_token = $redis->get("access_token");
        }
        return $access_token;
    }
    /**
     * 获取ticket
     */
    private function getTicket(){
        $redis = new Redis();
        if(!$redis->has("ticket")) {
            $json = file_get_contents("https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=".$this->getAccessToken()."&type=jsapi");
            $ticket = json_decode($json,true)['ticket'];
            $redis->set("ticket",$ticket,7000);
        }else{
            $ticket = $redis->get("ticket");
        }
        return $ticket;
    }

    /**
     * @return false|string
     * 分享后增加积分
     */
    public function addPoints(){
        $gid = input("gid");
        $aid = input("aid");
        if($gid&&$aid){
            $gid=$aid;
        }else{
            if(!input("gid") && !input("aid")){
                $data['error_code'] = 200;
                $data['message'] = lang("缺少参数");
                return json_encode($data,true);
            }
        }
        $memberid = input("member_id");
        $type = input("type",1);//1,商品 2,文章
        if($type==1){
            $points_type = "points_goods_share";
            $checkin_stage = 'goods_share';

        }else{
            $points_type = "points_article_share";
            $checkin_stage = 'article_share';
        }
        //$checkin_stage = 'share';
        $eachNum = 10;
        if (Config(['app'])['app']['points_isuse'] == 1){

            // 校验 该用户 今天是否 签到；已签到用户不能再次签到
            $points_model = new Points();
            $condition = array();
            $condition['pl_memberid'] = input("member_id");
            $s_time = strtotime(date('Y-m-d',time()));
            $e_time = $s_time + 86400;
            $condition['pl_stage'] = $checkin_stage;
            $has_checked_flag = $points_model->getPointsInfo($condition,'pl_id');
            $count = (db::name("points_log")->where("(pl_stage='goods_share' or pl_stage='article_share' ) and pl_member_id=$memberid and saddtime>=$s_time and eaddtime<=$e_time")->count());
            if($count>=5){
                $data['error_code'] = 200;
                $data['message'] = '每日只可分享5次';
                return json_encode($data,true);
            }
            else {
                //添加会员积分
                $points_model->savePointsLog($checkin_stage,array('pl_memberid'=>$this->member_info['member_id'],'pl_membername'=>$this->member_info['member_name'],'pl_points'=>Config($points_type)));
                $data['error_code'] = 200;
                $data['message'] = '分享成功';
                return json_encode($data,true);

                // $page_count = $points_model->gettotalpage();
            }
        }else{
            $state = 'failuer';
            $message = '积分功能未开启';
        }
    }
}