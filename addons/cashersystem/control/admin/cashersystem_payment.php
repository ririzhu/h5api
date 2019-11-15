<?php
/**
 * 支付方式
 */
defined('DYMall') or exit('Access Invalid!');
class cashersystem_paymentCtl extends SystemCtl{
	public function __construct(){
		parent::__construct();
		Language::read('cashsys_payment');
	}

    /**
     * 获取支付方式信息
     */
    public function getPayInfo(){
        $model_payment = M('cashsys_payment','cashersystem');
        $payment_list = $model_payment->getPaymentList();

        if(!empty($payment_list)){
            foreach ($payment_list as $key => $val){
                if($val['payment_config'] != ''){
                    $payment_list[$key]['payment_config'] = unserialize($val['payment_config']);
                    if ($payment_list[$key]['payment_config']['apiKey']) {
                        $payment_list[$key]['payment_config']['appKey'] = $payment_list[$key]['payment_config']['apiKey'];
                    }
                }
            }
        }
        echo json_encode(array('data'=>$payment_list));
    }
    /**
     * 编辑保存信息
     */
    public function savePayInfo(){
        $model_payment = M('cashsys_payment','cashersystem');
        $payment_id = intval($_POST["payment_id"]);
        $payment_state = 0;
        $data = array();

        //pc的支付方式
        if($_POST['type'] == 'alipay'){
            $data['alipay_public_key'] = $_POST['alipay_public_key'];
            $data['merchant_private_key'] = $_POST['merchant_private_key'];
            $data['app_id'] = $_POST['app_id'];
        }else if($_POST['type'] == 'cash'){
        }else if($_POST['type'] == 'wx'){
            $data['appId'] = $_POST['appId'];
            $data['partnerId'] = $_POST['appMid'];
            $data['apiKey'] = $_POST['appKey'];
            $data['appSecret'] = $_POST['appSecret'];
        }
        $payment_config = serialize($data);
        $result = $model_payment->editPayment(array('payment_config'=>$payment_config),array('payment_id'=>$payment_id));
        if($result){
            echo json_encode(array('state'=>200,'msg'=>'设置成功！'));die;
        }else{
            echo json_encode(array('state'=>255,'msg'=>'设置失败，请稍后重试~~~'));die;
        }
    }
    /**
     * slodon支付方式开启关闭状态
     */
    public function sldChangePayState(){
        $type = $_GET['type'];
        $payment_id = $_GET['id'];
        $val = $_GET['value'];

        $model_payment = M('cashsys_payment','cashersystem');
        $result = $model_payment->editPayment(array('payment_state'=>$val),array('payment_id'=>$payment_id));
        
        if($result) {
            echo json_encode(array('state'=>200,'msg'=>Language::get('保存成功')));die;
        } else {
            echo json_encode(array('state'=>255,'msg'=>Language::get('保存失败')));die;
        }
    }

}