<?php


namespace app\v1\controller;


use app\v1\model\TousuSubject;

class Tousu extends Base
{
    /*
         * 我的投诉页面
         */
    public function index() {
        /*
         * 得到当前用户的投诉列表
         */
        $page = input("page",0);
        $model_complain = new \app\v1\model\Tousu();
        $condition = array();
        $condition['order']        = 'complain_state asc,complain_id desc';
        $condition['accuser_id'] = input("member_id");
        switch(intval($_GET['select_complain_state'])) {
            case 1:
                $condition['progressing'] = 'true';
                break;
            case 2:
                $condition['finish'] = 'true';
                break;
            default :
                $condition['state'] = '';
        }
        $list = $model_complain->getComplain($condition, $page) ;
        $data['list'] = $list;
        return json_encode($data,true);
    }

    /*
     * 新投诉
     */
    public function newtousu() {
        $order_id = input('order_id');
        //获取订单详细信息，并检查权限
        $order_info = $this->get_order_info($order_id);
        //检查是不是正在进行投诉
        if($this->check_complain_exist($order_id)) {
            $data['error_code']=10201;
            $data['message']=lang('您已经投诉了该订单请等待处理');//'您已经投诉了该订单请等待处理'
            return json_encode($data,true);
        }
        //检查订单状态是否可以投诉
        $complain_time_limit = intval($GLOBALS['setting_config']['complain_time_limit']);
        if(!empty($order_info['finnshed_time'])) {
            if((intval($order_info['finnshed_time'])+$complain_time_limit) < time()) {
                $data['error_code']=10201;
                $data['message']=lang('您的订单已经超出投诉时限');//'您的订单已经超出投诉时限'
                return json_encode($data,true);
            }
        }
        //列出订单商品列表
        $order_goods_list = $order_info['extend_order_goods'];
        //买家未付款不能投诉
        if(intval($order_info['order_state']) < ORDER_STATE_PAY) {
            $data['error_code']=10201;
            $data['message']=lang('参数错误');
            return json_encode($data,true);
        }

        //获取投诉类型
        $model_complain_subject = new TousuSubject();
        $param = array();
        $complain_subject_list = $model_complain_subject->getActiveComplainSubject($param);
        if(empty($complain_subject_list)) {
            $data['error_code']=10201;
            $data['message']=lang('投诉主题不存在请联系管理员');
            return json_encode($data,true);
        }
        $model_refund = new \app\v1\model\Refund();
        $order_list[$order_id] = $order_info;
        $order_list = $model_refund->getGoodsRefundList($order_list);
        if(intval($order_list[$order_id]['complain']) == 1) {//退款投诉
            $complain_subject = Model()->table('tousu_subject')->where(array('complain_subject_id'=> 1))->select();//投诉主题
            $complain_subject_list = array_merge($complain_subject, $complain_subject_list);
        }
        $data['error_code'] = 200;
        $data['order'] = $order_list;
        $data['complain_subject_list'] = $complain_subject_list;
        return json_encode($data,true);

    }

    /*
     * 处理投诉请求
     */
    public function processtousu() {
        $complain_id = intval($_GET['complain_id']);
        //获取投诉详细信息
        $complain_info = $this->get_complain_info($complain_id);
        //获取订单详细信息
        $order_info = $this->get_order_info($complain_info['order_id']);
        //获取投诉的商品列表
        $model_complain_goods = Model('tousu_goods');
        $param = array();
        $param['complain_id'] = $complain_id;
        $complain_goods_list = $model_complain_goods->getComplainGoods($param);
        $page_name = '';
        switch(intval($complain_info['complain_state'])) {
            case self::STATE_NEW:
                $page_name = 'tousu.info';
                break;
            case self::STATE_APPEAL:
                $page_name = 'tousu.info';
                break;
            case self::STATE_TALK:
                $page_name = 'tousu.talk';
                break;
            case self::STATE_HANDLE:
                $page_name = 'tousu.talk';
                break;
            case self::STATE_FINISH:
                $page_name = 'tousu.info';
                break;
            default:
                showMsg(Language::get('参数错误'),'','html','error');
        }
        Template::output('order_info',$order_info);
        Template::output('complain_info',$complain_info);
        Template::output('complain_goods_list',$complain_goods_list);
        Template::output('left_show','order_view');
        Template::showpage($page_name);
    }

    /*
     * 保存用户提交的投诉
     */
    public function savetousu() {
        //获取输入的投诉信息
        $input = array();
        $input['order_id'] = input('input_order_id');
        //检查是不是正在进行投诉
        if($this->check_complain_exist($input['order_id'])) {
            $data['error_code']=10201;
            $data['message']=lang('您已经投诉了该订单请等待处理');
            return json_encode($data,true);
        }
        list($input['complain_subject_id'],$input['complain_subject_content']) = explode(',',trim(input('input_complain_subject')));
        $input['complain_content'] = trim(input('input_complain_content'));
        //验证输入的信息
        $obj_validate = new Validate();
        $obj_validate->validateparam = array(
            array("input"=>$input['complain_content'], "require"=>"true","validator"=>"Length","min"=>"1","max"=>"255","message"=>Language::get('投诉内容不能为空且必须小于100个字符')),
        );
        $error = $obj_validate->validate();
        if ($error != ''){
            
            showValidateError($error);
        }
        //获取有问题的商品
        $checked_goods = $_POST['input_goods_check'];
        $goods_problem = $_POST['input_goods_problem'];
        if(empty($checked_goods)) {
            showDialog(Language::get('参数错误'),'','error');
        }
        $order_info = $this->get_order_info($input['order_id']);
        $input['accuser_id'] = $order_info['buyer_id'];
        $input['accuser_name'] = $order_info['buyer_name'];
        $input['accused_id'] = $order_info['vid'];
        $input['accused_name'] = $order_info['store_name'];
        //上传图片
        $complain_pic = array();
        $complain_pic[1] = 'input_complain_pic1';
        $complain_pic[2] = 'input_complain_pic2';
        $complain_pic[3] = 'input_complain_pic3';
        $pic_name = $this->upload_pic($complain_pic);
        $input['complain_pic1'] = $pic_name[1];
        $input['complain_pic2'] = $pic_name[2];
        $input['complain_pic3'] = $pic_name[3];
        $input['complain_datetime'] = time();
        $input['complain_state'] = self::STATE_NEW;
        $input['complain_active'] = self::STATE_UNACTIVE;
        //保存投诉信息
        $model_complain = Model('tousu');
        $complain_id = $model_complain->saveComplain($input);
        //保存被投诉的商品详细信息
        $model_complain_goods = Model('tousu_goods');
        $order_goods_list = $order_info['extend_order_goods'];
        foreach($order_goods_list as $goods) {
            $order_goods_id = $goods['rec_id'];
            if (array_key_exists($order_goods_id,$checked_goods)) {//验证提交的商品属于订单
                $input_checked_goods['complain_id'] = $complain_id;
                $input_checked_goods['order_gid'] = $order_goods_id;
                $input_checked_goods['order_goods_type'] = $goods['goods_type'];
                $input_checked_goods['gid'] = $goods['gid'];
                $input_checked_goods['goods_name'] = $goods['goods_name'];
                $input_checked_goods['vid'] = $goods['vid'];
                $input_checked_goods['goods_price'] = $goods['goods_price'];
                $input_checked_goods['goods_num'] = $goods['goods_num'];
                $input_checked_goods['goods_image'] = $goods['goods_image'];
                $input_checked_goods['complain_message'] = $goods_problem[$order_goods_id];
                $model_complain_goods->saveComplainGoods($input_checked_goods);
            }
        }
        //商品被投诉发送商户消息

        showDialog(Language::get('投诉提交成功,请等待系统审核'),'index.php?app=tousu','succ');
    }
    /*
     * 检查投诉是否已经存在
     */
    private function check_complain_exist($order_id) {
        $model_complain = Model('tousu');
        $param = array();
        $param['order_id'] = $order_id;
        $param['accuser_id'] = $_SESSION['member_id'];
        $param['progressing'] = 'ture';
        return $model_complain->isExist($param);
    }
    /*
     * 保存用户提交的补充证据
     */
    public function addpic() {
        $complain_id = intval($_GET['complain_id']);
        //获取投诉详细信息
        $complain_info = $this->get_complain_info($complain_id);
        if (chksubmit()){
            $where_array = array();
            $where_array['complain_id'] = $complain_id;
            //获取输入的投诉信息
            $input = array();
            $complain_pic = array();
            $complain_pic[1] = 'input_complain_pic1';
            $complain_pic[2] = 'input_complain_pic2';
            $complain_pic[3] = 'input_complain_pic3';
            $pic_name = $this->upload_pic($complain_pic);
            $input['complain_pic1'] = $pic_name[1];
            $input['complain_pic2'] = $pic_name[2];
            $input['complain_pic3'] = $pic_name[3];
            //保存投诉信息
            $model_complain = Model('tousu');
            $model_complain->updateComplain($input,$where_array);
            showDialog(Language::get('保存成功'),'reload','succ','CUR_DIALOG.close();');
        }
        Template::output('complain_info',$complain_info);
        Template::showpage('complain_add_pic','null_layout');
    }

    /*
     * 取消用户提交的投诉
     */
    public function complain_cancel() {
        $complain_id = intval($_GET['complain_id']);
        $complain_info = $this->get_complain_info($complain_id);
        if(intval($complain_info['complain_state']) === 10) {
            $pics = array();
            if(!empty($complain_info['complain_pic1'])) $pics[] = $complain_info['complain_pic1'];
            if(!empty($complain_info['complain_pic2'])) $pics[] = $complain_info['complain_pic2'];
            if(!empty($complain_info['complain_pic3'])) $pics[] = $complain_info['complain_pic3'];
            if(!empty($pics)) {//删除图片
                foreach($pics as $pic) {
                    $pic = BASE_UPLOAD_PATH.DS.ATTACH_PATH.DS.'complain'.DS.$pic;
                    if(file_exists($pic)) {
                        delete_file($pic);
                    }
                }
            }
            $model_complain = Model('tousu');
            $model_complain->dropComplain(array('complain_id' => $complain_id));
            $model_complain_goods = Model('tousu_goods');
            $model_complain_goods->dropComplainGoods(array('complain_id' => $complain_id));
            showDialog(Language::get('投诉取消成功'),'reload','succ');
        } else {
            showDialog(Language::get('投诉取消失败'),'','error');
        }
    }
}