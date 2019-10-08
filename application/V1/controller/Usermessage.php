<?php
namespace app\v1\controller;

use app\v1\model\Message;
use think\console\command\make\Model;
use think\db;

class Usermessage extends  Base
{
	public function __construct(){
		parent::__construct();
	}

    /**
     * 消息中心
     */
    public function systemmsg(){
    	if(!input("member_id")){
            $data['code'] = 10001;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }

        //$model_message  = Model('message');
        $model_message  = new Message();
        $page=!empty(input('page'))?input('page'):1;
        $condition=array();
        //$condition['to_member_id']=$this->member_info['member_id'];
        $condition['to_member_id']=input("member_id");
        //$condition['message_type']=1;
        //$condition['from_member_id']=0;

        $field=" message_id,to_member_id,message_title,message_body,message_time,read_member_id,system_type,message_type,from_member_id";
        //$message_array  = $model_message->listMessage($condition);
        $message_array  = $model_message->messageList($condition,$field,$page);
        if(!empty($message_array)){
            // 过滤掉 其中的a标签及a标签内容
            foreach ($message_array as $key => $value) {
                //$message_array[$key]['message_body'] = preg_replace("/<a[^>]*>(.*?)<\/a>/is", "", $value['message_body']);
                $message_array[$key]['message_body'] = html_entity_decode(preg_replace("/<a[^>]*>(.*?)<\/a>/is", "", $value['message_body']));
                //$message_array[$key]['message_time_str'] = date('Y-m-d H:i:s',$value['message_time']);
                $message_array[$key]['message_time_str'] = $this->date_before($value['message_time']);
                //是否已读
                if(strpos($value['read_member_id'],",".input("member_id").",") === false){
                    $message_array[$key]['is_read']=0;
                }else{
                    $message_array[$key]['is_read']=1;
                }
/*                switch ($value['system_type']) {
                	case '1':
                		$message_array[$key]['message_title']='发货提醒';
                		break;
                	case '2':
                		$message_array[$key]['message_title']='付款成功';
                		break;
                	case '3':
                		$message_array[$key]['message_title']='余额变动';
                		break;
                	case '4':
                		$message_array[$key]['message_title']='退货退款';
                		break;               	
                	default:
                		$message_array[$key]['message_title']='系统信息';
                		break;
                }*/
            }
			$data['code'] = 200;
			$data['message'] = '请求成功';
			$data['message_array'] = $message_array;
        }else{
        	$data['code'] = 200;
			$data['message'] = '请求成功';
			$data['message_array'] = [];
        }
        echo json_encode($data,true);
    }
    /*
    **查看单条消息
     */
    public function messageInfo(){
        if(!input("message_id")||!input("member_id")){
            $data['code'] = 10001;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }
        $model_message  = new Message();
        $condition=array();
        $condition['message_id']=input('message_id');
        $message=$model_message->getOne($condition);
        if(empty($message)){
            $data['code']=10002;
            $data['message']='数据错误';
        }
        if(strpos($message['read_member_id'],",".input("member_id").",") === false){
            //更新状态
            $updata['read_member_id']=$message['read_member_id'];
            $updata['read_member_id'].=",".input("member_id").",";
            $updataMessage=$model_message->updateMessage($condition,$updata);
        }
        $message['message_time_str'] = $this->date_before($message['message_time']);
        $data['code']=200;
        $data['message']='请求成功';
        $data['mesInfo']=$message;
        return json_encode($data,true);
    }
}