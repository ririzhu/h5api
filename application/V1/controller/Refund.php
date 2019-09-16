<?php
namespace app\v1\controller;
use app\v1\model\Red;
use app\v1\model\Trade;
use think\console\command\make\Model;
use think\Queue;

/**
 * Class Refund
 * @package app\v1\controller
 * 退款退货
 */
class Refund extends  Base
{
    public function __construct(){
        parent::__construct();
        $model_refund = new \app\v1\model\Refund();
        $model_refund->getRefundStateArray();
    }
    /**
     * 添加订单商品部分退款
     *
     */
    public function addRefund(){
        $model_order = new \app\v1\model\Order();
        $model_refund = new \app\v1\model\Refund();
        $model_goods= new \app\v1\model\Goods();
        $order_id = intval(input('order_id'));
        $gid = intval(input('gid'));
        $condition = array();
        $condition['buyer_id'] = input("member_id");
        $condition['order_id'] = $order_id;
        $order_list = $model_order->getOrderList($condition);
        $order = $order_list[0];
        $order_id = $order['order_id'];

        $condition = array();
        $condition['order_id'] = $order_id;
        $condition['rec_id'] = $gid;//订单商品表编号
        $goods_info=$model_goods->getGoodsInfoByID($gid);//根据商品id获取商品的信息
        $yongjin = $goods_info['fenxiao_yongjin'];//商品的佣金
        $goods_list = $model_order->getOrderGoodsList($condition);

        // // 获取最终价格
        // $goods_list = Model('goods_activity')->rebuild_goods_data($goods_list,'pc');

        $goods = $goods_list[0];
        $goods_pay_price = $goods['goods_pay_price'];//商品实际成交价
        $order_amount = $order['order_amount'];//订单金额
        $order_refund_amount = $order['refund_amount'];//订单退款金额
        if($order['payment_code'] == 'offline' && $order['order_state'] == 30){
            $goods['goods_pay_price'] = 0;
        }else{
            if ($order_amount < ($goods_pay_price + $order_refund_amount)) {
                $goods_pay_price = $order_amount - $order_refund_amount;
                $goods['goods_pay_price'] = $goods_pay_price;
            }
        }

//		else{
//            $goods['goods_pay_price'] = $goods['goods_pay_price']-$yongjin;
//        }
        //Template::output('goods',$goods);

        $gid = $goods['rec_id'];
        $condition = array();
        $condition['buyer_id'] = $order['buyer_id'];
        $condition['order_id'] = $order['order_id'];
        $condition['order_goods_id'] = $gid;
        $condition['seller_state'] = array('lt','3');
        $refund_list = $model_refund->getRefundReturnList($condition);
        $refund = array();
        if (!empty($refund_list) && is_array($refund_list)) {
            $refund = $refund_list[0];
        }
        if (chksubmit() && $gid > 0){
            $refund_state = $model_refund->getRefundState($order);//根据订单状态判断是否可以退款退货
            if ($refund['refund_id'] > 0 || $refund_state != 1) {//检查订单状态,防止页面刷新不及时造成数据错误
                //showDialog(Language::get('参数错误'),'reload','error','CUR_DIALOG.close();');
            }
            $refund_array = array();
            $refund_amount = floatval($_POST['refund_amount']);//退款金额
            if (($refund_amount < 0) || ($refund_amount > $goods_pay_price)) {
                $refund_amount = $goods_pay_price;
            }
            $goods_num = intval($_POST['goods_num']);//退货数量
            if (($goods_num < 0) || ($goods_num > $goods['goods_num'])) {
                $goods_num = 1;
            }
            $model_trade = new Trade();
            $order_shipped = $model_trade->getOrderState('order_shipped');//订单状态30:已发货
            if ($order['order_state'] == $order_shipped) {
                $refund_array['order_lock'] = '2';//锁定类型:1为不用锁定,2为需要锁定
            }
            $refund_array['refund_type'] = input('refund_type');//类型:1为退款,2为退货
            $refund_array['return_type'] = '2';//退货类型:1为不用退货,2为需要退货
            if ($refund_array['refund_type'] != '2') {
                $refund_array['refund_type'] = '1';
                $refund_array['return_type'] = '1';
            }
            $refund_array['seller_state'] = '1';//状态:1为待审核,2为同意,3为不同意
            $refund_array['refund_amount'] = sldPriceFormat($refund_amount);
            $refund_array['goods_num'] = $goods_num;
            $refund_array['buyer_message'] = $_POST['buyer_message'];
            $refund_array['add_time'] = time();

            $state = $model_refund->addRefundReturn($refund_array,$order,$goods);

            if ($state) {
                if ($order['order_state'] == $order_shipped) {
                    $model_refund->editOrderLock($order_id);
                }

                $now_refund_list = $model_refund->getRefundReturnList(array('refund_id' => $state));
                $now_refund_info = $now_refund_list[0];

                $refund_type_str = ($now_refund_info['refund_type'] == 2) ? '退货' : '退款';
                $first_msg = '您发起了'.$refund_type_str.'申请，请耐心等待结果';
                // 发送买家消息
                $param = array();
                $param['code'] = 'refund_return_notice';
                $param['member_id'] = $now_refund_info['buyer_id'];
                $param['param'] = array(
                    'refund_url' => $refund_array['refund_type']=='1'?urlShop('refund', 'index', array('refund_id' => $now_refund_info['refund_id'])):urlShop('return', 'index', array('refund_id' => $now_refund_info['refund_id'])),
                    'refund_sn' => $now_refund_info['refund_sn'],

                    'first' => $first_msg,
                    'keyword1' => $now_refund_info['refund_sn'],
                    'keyword2' => $now_refund_info['refund_amount'],
                    'keyword3' => $now_refund_info['goods_num'],
                    'keyword4' => $now_refund_info['buyer_message'],
                    'remark' => '点击【详情】查看退款退货详情',

                    'url' => WAP_SITE_URL.'/cwap_user_refund_info.html?refund_id='.$now_refund_info['refund_id']
                );
                $param['system_type']=4;
                $param['link']=WAP_SITE_URL.'/cwap_user_refund_info.html?refund_id='.$now_refund_info['refund_id'];
                //QueueClient::push('sendMemberMsg', $param);

                //showDialog(Language::get('保存成功'),'reload','succ','CUR_DIALOG.close();');
            } else {
                //showDialog(Language::get('保存失败'),'reload','error','CUR_DIALOG.close();');
            }
        }
        //Template::showpage('member_refund_add','null_layout');
    }
    /**
     * 添加全部退款即取消订单
     *
     */
    public function addRefundAll(){
        if(!input("member_id") || !input("order_id")){
            $data['errorCode'] = 10016;
            $data['message'] = lang("缺少参数");
        }
        $model_order = new \app\v1\model\Order();
        $model_trade = new Trade();
        $model_refund = new \app\v1\model\Refund();
        $order_id = intval(input('order_id'));
        $condition = array();
        $condition['buyer_id'] = input('member_id');
        $condition['order_id'] = $order_id;
        $order_list = $model_order->getOrderList($condition,1,'*',null,1);
        $order = $order_list[0];
        //Template::output('order',$order);
        $order_amount = $order['order_amount'];//订单金额
        $condition = array();
        $condition['buyer_id'] = $order['buyer_id'];
        $condition['order_id'] = $order['order_id'];
        $condition['gid'] = '0';
        $condition['seller_state'] = array('lt','3');
        $refund_list = $model_refund->getRefundReturnList($condition,1,'*',1);
        $refund = array();
        if (!empty($refund_list) && is_array($refund_list)) {
            $refund = $refund_list[0];
        }
        if (chksubmit()) {
            $order_paid = $model_trade->getOrderState('order_paid');//订单状态20:已付款
            $payment_code = $order['payment_code'];//支付方式
            if(isset($refund['refund_id']) )
            if (isset($refund['refund_id']) && $refund['refund_id'] > 0 || $order['order_state'] != $order_paid || $payment_code == 'offline') {//检查订单状态,防止页面刷新不及时造成数据错误
                //showDialog(Language::get('参数错误'),'reload','error','CUR_DIALOG.close();');
            }
            $refund_array = array();
            $refund_array['refund_type'] = '1';//类型:1为退款,2为退货
            $refund_array['seller_state'] = '1';//状态:1为待审核,2为同意,3为不同意
            $refund_array['order_lock'] = '2';//锁定类型:1为不用锁定,2为需要锁定
            $refund_array['gid'] = '0';
            $refund_array['order_gid'] = '0';
            $refund_array['goods_name'] = '订单商品全部退款';
            $refund_array['refund_amount'] = sldPriceFormat($order_amount);
            $refund_array['buyer_message'] = input('buyer_message');
            $refund_array['add_time'] = time();
            $state = $model_refund->addRefundReturn($refund_array,$order);
            if ($state) {
                $model_refund->editOrderLock($order_id);

                $now_refund_list = $model_refund->getRefundList(array('refund_id' => $state),0,10);
                $now_refund_info = $now_refund_list[0];

                $first_msg = '您发起了退款申请，请耐心等待结果';
                // 发送买家消息
                $param = array();
                $param['code'] = 'refund_return_notice';
                $param['member_id'] = $now_refund_info['buyer_id'];
                $param['param'] = array(
                    'refund_url' => urlShop('refund', 'index', array('refund_id' => $now_refund_info['refund_id'])),
                    'refund_sn' => $now_refund_info['refund_sn'],

                    'first' => $first_msg,
                    'keyword1' => $now_refund_info['refund_sn'],
                    'keyword2' => $now_refund_info['refund_amount'],
                    'keyword3' => $now_refund_info['goods_num'],
                    'keyword4' => $now_refund_info['buyer_message'],
                    'remark' => '点击【详情】查看退款退货详情',

                    'url' => WAP_SITE_URL.'/cwap_user_refund_info.html?refund_id='.$now_refund_info['refund_id']
                );
                $param['system_type']=4;
                $param['link']=WAP_SITE_URL.'/cwap_user_refund_info.html?refund_id='.$now_refund_info['refund_id'];
                $data['errorCode'] = 200;
                $data['message'] = lang("发起退款");
                //Queue::push('sendMemberMsg', $param);

                //showDialog(Language::get('保存成功'),'reload','succ','CUR_DIALOG.close();');
            } else {
               $data['errorCode']=10017;
               $data['message']=lang("不要重复提交");
                //showDialog(Language::get('保存失败'),'reload','error','CUR_DIALOG.close();');
            }
        }
        return json_encode($data,true);
        //Template::showpage('member_refund_all','null_layout');
    }
    /**
     * 退货记录列表页
     *
     */
    public function index(){
        $model_refund = new \app\v1\model\Refund();
        $condition = array();
        $condition['buyer_id'] = input("member_id");
        if (trim(input("key")) ){
            $condition['order_sn'] = array('like','%'.input('key').'%');
        }
        if (trim(input('add_time_from')) != '' || trim(input('add_time_to')) != ''){
            $add_time_from = strtotime(trim(input('add_time_from')));
            $add_time_to = strtotime(trim(input('add_time_to')));
            if ($add_time_from !== false || $add_time_to !== false){
                $condition['add_time'] = array('time',array($add_time_from,$add_time_to));
            }
        }
        $return_list = $model_refund->getReturnList($condition,10);
    }
    /**
     * 发货
     *
     */
    public function ship(){
        $model_refund = new \app\v1\model\Refund();
        $condition = array();
        $condition['buyer_id'] = input("member_id");
        $condition['refund_id'] = intval(input('return_id'));
        $return_list = $model_refund->getReturnList($condition);
        $return = $return_list[0];
        $red = new Red();
        $express_list  = ($h = $red->H('express')) ? $h : $red->H('express',true);
        if (chksubmit()) {
            if ($return['seller_state'] != '2' || $return['goods_state'] != '1') {//检查状态,防止页面刷新不及时造成数据错误
                //showDialog(Language::get('参数错误'),'reload','error','CUR_DIALOG.close();');
            }
            $refund_array = array();
            $refund_array['ship_time'] = time();
            $refund_array['delay_time'] = time();
            $refund_array['express_id'] = $_POST['express_id'];
            $refund_array['invoice_no'] = $_POST['invoice_no'];
            $refund_array['goods_state'] = '2';
            $state = $model_refund->editRefundReturn($condition, $refund_array);
            if ($state) {
                //showDialog(Language::get('保存成功'),'reload','succ','CUR_DIALOG.close();');
            } else {
                //showDialog(Language::get('保存失败'),'reload','error','CUR_DIALOG.close();');
            }
        }
        $model_trade = new Trade();
        $return_delay = $model_trade->getMaxDay('return_delay');//发货默认5天后才能选择没收到
        $model_trade->getMaxDay('return_confirm');//卖家不处理收货时按同意并弃货处理
    }
    /**
     * 延迟时间
     *
     */
    public function delay(){
        $model_refund = Model('refund_return');
        $condition = array();
        $condition['buyer_id'] = $_SESSION['member_id'];
        $condition['refund_id'] = intval($_GET['return_id']);
        $return_list = $model_refund->getReturnList($condition);
        $return = $return_list[0];
        if (chksubmit()) {
            if ($return['seller_state'] != '2' || $return['goods_state'] != '3') {//检查状态,防止页面刷新不及时造成数据错误
                //showDialog(Language::get('参数错误'),'reload','error','CUR_DIALOG.close();');
            }
            $refund_array = array();
            $refund_array['delay_time'] = time();
            $refund_array['goods_state'] = '2';
            $state = $model_refund->editRefundReturn($condition, $refund_array);
            if ($state) {
                //showDialog(Language::get('保存成功'),'reload','succ','CUR_DIALOG.close();');
            } else {
                //showDialog(Language::get('保存失败'),'reload','error','CUR_DIALOG.close();');
            }
        }
        $model_trade = new Trade();
        $return_delay = $model_trade->getMaxDay('return_delay');//发货默认5天后才能选择没收到
    }
    /**
     * 退货记录查看页
     *
     */
    public function view(){
        $model_refund = new \app\v1\model\Refund();
        $condition = array();
        $condition['buyer_id'] = input('member_id');
        $condition['refund_id'] = intval(input('return_id'));
        $return_list = $model_refund->getReturnList($condition);
        $return = $return_list[0];
        $redModel = new Red();
        $express_list  = ($h = $redModel->H('express')) ? $h : $redModel->H('express',true);
        if ($return['express_id'] > 0 && !empty($return['invoice_no'])) {
        }
    }

}