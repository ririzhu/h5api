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
                if($message_array[$key]['message_type']==0) $message_array[$key]['message_type']="私信";
                if($message_array[$key]['message_type']==1) $message_array[$key]['message_type']="系统消息";
                if($message_array[$key]['message_type']==2) $message_array[$key]['message_type']="留言";
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
    /*
    **消息中心改
     */
    public function  messageType(){
        if(empty(input("member_id"))){
            $data['code'] = 10001;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }
        $member_id=input("member_id");
        $model_message  = new Message();
        $where=" to_member_id = ".$member_id." or to_member_id = 'all' ";
        $type=$model_message->message_type($where);
        if(!empty($type)){
            foreach ($type as &$v1) {
                if($v1['message_type']==0) $v1['message_type1']="私信";
                if($v1['message_type']==1) $v1['message_type1']="系统消息";
                if($v1['message_type']==2) $v1['message_type1']="留言";
                $where=" (to_member_id = ".$member_id." or to_member_id = 'all') and message_type = ".$v1['message_type'];
                $lastOne=$model_message->get_last_type_message($where);
                $v1['message_body']=$lastOne['message_body'];
                $v1['message_time']=$this->date_before($lastOne['message_time']);
                if(!empty($lastOne['from_member_id'])){
                    $where1=" member_id = ".$lastOne['from_member_id'];
                    $fromWho=$model_message->getMemberInfo($where1);
                    $v1['from_member_name']=$fromWho['member_name'];
                    if(empty($fromWho['member_avatar'])) $v1['from_member_avatar']="http://192.168.2.141/static/defualt_img/sld_pc_dian_topleft_logo.png";
                     else $v1['from_member_avatar']="http://192.168.2.141/data/upload/mall/avatar/".$fromWho['member_avatar'];
                }else{
                    $v1['from_member_name']="系统";
                    $v1['from_member_avatar']="http://192.168.2.141/static/defualt_img/sld_pc_dian_topleft_logo.png";                    
                }
            }           
        }else{
            $type=null;
        }
        $data['code']=200;
        $data['message']='请求成功';
        $data['data']=$type;
        return json($data);
    }
    public function messageTypeList(){
        if(empty(input("member_id"))||is_null(input('message_type'))){
            $data['code'] = 10001;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }
        $member_id=input("member_id");
        $message_type=input("message_type");
        $model_message  = new Message();
        $field=" message_id,to_member_id,message_title,message_body,message_time,read_member_id,system_type,from_member_id";
        $where=" (to_member_id = ".$member_id." or to_member_id = 'all') and message_type = ".$message_type;
        $list=$model_message->get_type_list($field,$where);
        if(!empty($list)){
            foreach ($list as $k2 => $v2) {
                if(strpos($v2['read_member_id'],",".input("member_id").",") === false){
                    $list[$k2]['is_read']=0;
                }else{
                    $list[$k2]['is_read']=1;
                }
                switch ($v2['system_type']) {
                    case '0':
                        $list[$k2]['system_type1']='系统通知';
                        break;
                    case '1':
                        $list[$k2]['system_type1']='发货提醒';
                        break;
                    case '2':
                        $list[$k2]['system_type1']='付款成功';
                        break;
                    case '3':
                        $list[$k2]['system_type1']='余额变动';
                        break;
                    case '4':
                        $list[$k2]['system_type1']='退货退款';
                        break;
                    case '5':
                        $list[$k2]['system_type1']='积分变化';
                        break;                   
                }                
                $list[$k2]['message_time'] = $this->date_before($v2['message_time']);              
            }
        }else{
            $list=null;
        }

        //标记为已读
        if(!empty($list)){
            $list_c=$list;
            foreach ($list_c as $k3 => $v3) {
                if(strpos($v3['read_member_id'],",".$member_id.",") === false){
                    $condition['message_id']=$v3['message_id'];
                    //更新状态
                    $updata['read_member_id']=$v3['read_member_id'];
                    $updata['read_member_id'].=",".$member_id.",";
                    $updataMessage=$model_message->updateMessage($condition,$updata);
                }                
            }            
        }

        $data['code']=200;
        $data['message']='请求成功';
        if($message_type==0) $data['message_type']="私信";
        if($message_type==1) $data['message_type']="系统消息";
        if($message_type==2) $data['message_type']="留言";
        $data['data']=$list;
        return json($data);
    }
}