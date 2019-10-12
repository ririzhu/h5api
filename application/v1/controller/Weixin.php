<?php
namespace app\v1\controller;

use think\cache\driver\Redis;

class Weixin extends  Base
{
    /**
     * @return false|string
     *
     */
    public function config(){
        $goodsid=input("gid",1006);
        $ticket = $this->getTicket();
        $noncestr = "HorizouApiipAuoziroH";
        $timestrap = time();
        $url = "http://www.horizou.cn/index.php?app=goods&gid=$goodsid";
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
}