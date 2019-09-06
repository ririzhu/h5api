<?php
/**
 * 消息推送
 *
 */
defined('DYMall') or exit('Access Invalid!');

class sld_jpush {

    private $push = '';

    public function __construct(){
        //初始化jpush
//        require_once(BASE_LIBRARY_PATH . '/api/jpush/examples/config.php');
        require_once(BASE_LIBRARY_PATH . '/api/jpush/src/JPush/AdminClient.php');
        require_once(BASE_LIBRARY_PATH . '/api/jpush/src/JPush/Client.php');
        require_once(BASE_LIBRARY_PATH . '/api/jpush/src/JPush/Config.php');
        require_once(BASE_LIBRARY_PATH . '/api/jpush/src/JPush/DevicePayload.php');
        require_once(BASE_LIBRARY_PATH . '/api/jpush/src/JPush/Http.php');
        require_once(BASE_LIBRARY_PATH . '/api/jpush/src/JPush/PushPayload.php');
        require_once(BASE_LIBRARY_PATH . '/api/jpush/src/JPush/ReportPayload.php');
        require_once(BASE_LIBRARY_PATH . '/api/jpush/src/JPush/SchedulePayload.php');
        require_once(BASE_LIBRARY_PATH . '/api/jpush/src/JPush/version.php');
        require_once(BASE_LIBRARY_PATH . '/api/jpush/src/JPush/Exceptions/JPushException.php');
        require_once(BASE_LIBRARY_PATH . '/api/jpush/src/JPush/Exceptions/APIConnectionException.php');
        require_once(BASE_LIBRARY_PATH . '/api/jpush/src/JPush/Exceptions/APIRequestException.php');
        require_once(BASE_LIBRARY_PATH . '/api/jpush/src/JPush/Exceptions/ServiceNotAvaliable.php');

        $app_key = C('jpush_appkey');
        $master_secret = C('jpush_secret');
        error_reporting(E_ALL);
        $client = new \JPush\Client($app_key, $master_secret);
        $this->push = $client->push();

    }

    //检查是否开启消息推送
    public function sld_check_jpush_isopen(){
        return C('jpush_open');
    }


    //简单推送一条消息，一条文字
    public function send_simple_message(){
        $push_payload = $this->push->setPlatform('all')
            ->addAllAudience()
            ->setNotificationAlert('Hi, JPush');
        $response = $push_payload->send();
    }



    /*
     * 推送一条消息，文字+url连接，方便跳转别的页面
     * 针对所有用户
     * $content 推送的具体内容
     * $url 将要跳转的页面名称
     * */
    public function send_detail_url($content='',$url=''){
        $push_payload = $this->push->setPlatform('all')
            ->addAllAudience()
            ->androidNotification($content,[
                'extras'=>['url'=>$url],
            ]);
        $response = $push_payload->send();
    }

    /*
     * 推送一条消息，文字+url连接，方便跳转别的页面
     * 针对特定别名的用户
     * $content 推送的具体内容
     * $url 将要跳转的页面名称
     * */
    public function send_special_detail_url($content='',$extras='',$alias=[]){
        $push_payload = $this->push->setPlatform('all')
            ->addAlias($alias)
            ->androidNotification($content,[
                'extras'=>$extras,
            ]);
        $response = $push_payload->send();
    }

    /*
     * 推送一条消息，文字+url连接，方便跳转别的页面
     * 针对设备id
     * $content 推送的具体内容
     * $url 将要跳转的页面名称
     * */
    public function send_registrationId_detail($content='',$url='',$RegistrationId){
        $push_payload = $this->push->setPlatform('all')
            ->addRegistrationId($RegistrationId)
            ->androidNotification($content,[
                'extras'=>['url'=>$url],
            ]);
        $response = $push_payload->send();
    }

    /*
     * 推送一条消息，文字+url连接，方便跳转别的页面
     * 针对用户标签
     * $content 推送的具体内容
     * $url 将要跳转的页面名称
     * */
    public function send_tag_detail($content='',$url='',$tag){
        $push_payload = $this->push->setPlatform('all')
            ->addTag($tag)
            ->androidNotification($content,[
                'extras'=>['url'=>$url],
            ]);
        $response = $push_payload->send();
    }

}