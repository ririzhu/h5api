<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/10/19
 * Time: 10:40
 */
class settingCtl extends SystemCtl{
    public function __construct(){
        parent::__construct();
    }
    /**
     * @api {post} index.php?app=setting&mod=open_setting&sld_addons=ldj 总后台联到家开关
     * @apiVersion 0.1.0
     * @apiName open_setting
     * @apiGroup Admin
     * @apiDescription 总后台联到家开关
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/SystemManage/index.php?app=setting&mod=open_setting&sld_addons=ldj
     * @apiParam {String} type :save表示保存开关设置,不写或者空表示获取开关信息
     * @apiParam {String} ldj_isuse 开关状态1/0
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} data 开关设置
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200,
     *         "data": {
     *                      "ldj_isuse": 1
     *                  }
     *      }
     *      /
     *      {
     *          status:200,
     *          msg:"保存成功"
     *      }
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "保存失败"
     *      }
     *
     */
    public function open_setting()
    {
        $model_setting = M('ldj_setting','ldj');
        if($_POST['type'] == 'save'){
            //保存信息
            $update_array = array();
            if (isset($_POST['ldj_isuse'])) {
                $update_array['ldj_isuse'] = intval($_POST['ldj_isuse']);
            }

            $result = $model_setting->updateSetting($update_array);
            if ($result === true){
                $this->log(L('bbc_edit,web_set'),1);
                echo json_encode(array('status'=>200,'msg'=>'保存成功'));die;
            }else {
                $this->log(L('bbc_edit,web_set'),0);
                echo json_encode(array('status'=>255,'msg'=>'保存失败'));die;
            }
        }else{
            $list_setting = $model_setting->getListSetting();
            //只返回需要的信息
            $list_setting_new = array();
            $list_setting_new['ldj_isuse'] = $list_setting['ldj_isuse'] ? $list_setting['ldj_isuse'] : 0;

            echo json_encode(array('status'=>200,'data'=>$list_setting_new));die;
        }
    }
    /**
     * @api {post} index.php?app=setting&mod=order_setting&sld_addons=ldj 总后台联到家获取订单取消设置
     * @apiVersion 0.1.0
     * @apiName order_setting
     * @apiGroup Admin
     * @apiDescription 总后台联到家获取订单取消设置
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/SystemManage/index.php?app=setting&mod=order_setting&sld_addons=ldj
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} data 取消订单设置设置
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200,
     *         "data": {
     *                      "order_cancel_time": 1
     *                      "vendor_cancel_time": 60
     *                      "member_stop_cancel_time": 60
     *                  }
     *      }
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *        {
     *          无返回
     *      }
     *
     */
    public function order_setting()
    {
        $setting_model = M('ldj_setting','ldj');
        $setting = $setting_model->getListSetting();
        $return_data = [
            'order_cancel_time'=>$setting['order_cancel_time'],
            'vendor_cancel_time'=>$setting['vendor_cancel_time'],
            'member_stop_cancel_time'=>$setting['member_stop_cancel_time'],
        ];
        echo json_encode(['status'=>200,'data'=>$return_data]);die;
    }
    /**
     * @api {post} index.php?app=setting&mod=order_setting_save&sld_addons=ldj 总后台修改联到家订单取消设置
     * @apiVersion 0.1.0
     * @apiName order_setting_save
     * @apiGroup Admin
     * @apiDescription 总后台修改联到家订单取消设置
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/SystemManage/index.php?app=setting&mod=order_setting_save&sld_addons=ldj
     * @apiParam {String} order_cancel_time 订单自动取消时间(小时),0表示无限制
     * @apiParam {String} vendor_cancel_time 店铺多少时间内未配货自动取消(分钟),0表示无限制
     * @apiParam {String} member_stop_cancel_time 字体订单会员多少时间内未取货不能取消订单(分钟),0表示无限制
     * @apiSuccess {Number} status 状态
     * @apiSuccess {String} msg 说明信息
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *          status:200,
     *          msg:"保存成功"
     *      }
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "保存失败"
     *      }
     *
     */
    public function order_setting_save()
    {
        $setting_model = M('ldj_setting','ldj');
        $update = [
            'order_cancel_time'=>$_POST['order_cancel_time'],
            'vendor_cancel_time'=>$_POST['vendor_cancel_time'],
            'member_stop_cancel_time'=>$_POST['member_stop_cancel_time'],
        ];
        $res = $setting_model->updateSetting($update);
        if($res){
            echo json_encode(['status'=>200,'msg'=>'修改成功']);die;
        }
        echo json_encode(['status'=>255,'msg'=>'修改失败']);die;
    }
    /**
     * @api {post} index.php?app=setting&mod=order_setting&sld_addons=ldj 总后台获取支付配置表
     * @apiVersion 0.1.0
     * @apiName pay_setting
     * @apiGroup Admin
     * @apiDescription 联到家获取订单取消设置
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/SystemManage/index.php?app=setting&mod=pay_setting&sld_addons=ldj
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200,
     *         "data": {
     *                          数据...
     *                  }
     *      }
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *       {
     *          无返回
     *      }
     */
    public function pay_setting()
    {
        $model_payment = M('ldj_payment','ldj');
            $payment_list = $model_payment->table('ldj_payment')->select();
            if(!empty($payment_list)){
                foreach ($payment_list as $key => $val){
                    if($val['payment_config'] != ''){
                        $payment_list[$key]['payment_config'] = unserialize($val['payment_config']);
                    }
                }
            }
            echo json_encode(array('status'=>200,'data'=>$payment_list));die;

    }
    /**
     * @api {post} index.php?app=setting&mod=settingsave&sld_addons=ldj 支付设置表保存
     * @apiVersion 0.1.0
     * @apiName settingsave
     * @apiGroup Admin
     * @apiDescription 支付设置表保存
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/SystemManage/index.php?app=setting&mod=settingsave&sld_addons=ldj
     * @apiParam {String} payment_id 支付方式id
     * @apiParam {String} type 支付方式code : alipay/wxpay_jsapi/yue/mini_wxpay/weixin
     * @apiParam {String} payment_state 开启状态:1/0
     * @apiParam {String} attention :请看说明:支付参数的键值为获取配置时返回的键值,请原样传回,与type一一对应
     * @apiSuccess {Number} status 状态
     * @apiSuccess {String} msg 说明信息
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *          status:200,
     *          msg:"保存成功"
     *      }
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "保存失败"
     *      }
     *
     */

    public function settingsave()
    {
        $payment_id = intval($_POST["payment_id"]);
        $payment_state = $_POST['payment_state']==1?1:0;
            switch ($_POST['type']) {
                case 'alipay':
                    $payment_config = array(
                        'app_id' => $_POST['app_id'],
                        'alipay_partner' => $_POST['alipay_partner'],
                        'alipay_public_key' => $_POST['alipay_public_key'],
                        'merchant_private_key' => $_POST['merchant_private_key'],
                    );
                    break;
                case 'yue':
                    $payment_config = array();
                    break;
                case 'wxpay_jsapi':
                    $payment_config = array(
                        'appId'     => $_POST['appId'],
                        'appSecret' => $_POST['appSecret'],
                        'partnerId' => $_POST['partnerId'],
                        'apiKey' => $_POST['apiKey'],
                    );
                    break;
                case 'mini_wxpay':
                    $payment_config = array(
                        'appId'     => $_POST['appId'],
                        'appSecret' => $_POST['appSecret'],
                        'partnerId' => $_POST['partnerId'],
                        'apiKey' => $_POST['apiKey'],
                    );
                    break;
                case 'weixin':
                    $payment_config = array(
                        'appId'     => $_POST['appId'],
                        'appSecret' => $_POST['appSecret'],
                        'partnerId' => $_POST['partnerId'],
                        'apiKey' => $_POST['apiKey'],
                    );
                    break;
                default:
                    echo json_encode(array('state'=>255,'msg'=>'支付方式错误~~~'));die;
            }
            $model_mb_payment = Model();
            if(!empty($payment_config)){
                $payment_config = serialize($payment_config);
            }
            $result = $model_mb_payment->table('ldj_payment')->where(array('payment_id' => $payment_id))->update(array('payment_state'=>$payment_state,'payment_config'=>$payment_config));
            if($result) {
                echo json_encode(array('status'=>200,'msg'=>'保存成功'));die;
            } else {
                echo json_encode(array('status'=>255,'msg'=>'保存失败'));die;
            }
    }

    /**
     * @api {get} index.php?app=setting&mod=sldChangePayState&sld_addons=ldj 支付状态修改
     * @apiVersion 0.1.0
     * @apiName sldChangePayState
     * @apiGroup Admin
     * @apiDescription 支付状态修改
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/SystemManage/index.php?app=setting&mod=sldChangePayState&sld_addons=ldj
     * @apiParam {String} id 支付方式id
     * @apiParam {String} value 1/0
     * @apiSuccess {Number} status 状态
     * @apiSuccess {String} msg 说明信息
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *          status:200,
     *          msg:"保存成功"
     *      }
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "保存失败"
     *      }
     *
     */
    public function sldChangePayState(){
        $payment_id = $_GET['id'];
        $val = $_GET['value'];
        $model_mb_payment = Model();
        $result = $model_mb_payment->table('ldj_payment')->where(['payment_id'=>$payment_id])->update(['payment_state'=>$val]);
        if($result) {
            echo json_encode(array('status'=>200,'msg'=>'保存成功'));die;
        } else {
            echo json_encode(array('status'=>255,'msg'=>'保存失败'));die;
        }
    }
}