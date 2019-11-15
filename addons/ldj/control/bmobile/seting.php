<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/10/18
 * Time: 14:11
 */
class setingCtl extends mobileHomeCtl {
    private $dian_info;
    public function __construct(){
        parent::__construct();

            if(!((C('sld_cashersystem') && C('cashersystem_isuse')) || (C('sld_ldjsystem') && C('ldj_isuse')))){
                echo json_encode(['status'=>255,'msg'=>'当前模块未开启']);die;
            }
        //登录信息
        if($_POST['key']){
            $key = trim($_POST['key']);
        }else{
            $key = trim($_GET['key']);
        }
        if(!$key){
            echo json_encode(['status'=>266,'msg'=>Language::get('请登录')]);die;
        }

        $token_model = M('cashsys_token','common');
        $token_info = $token_model->getTokenInfoByToken($key);

        if(!$token_info){
            echo json_encode(['status'=>266,'msg'=>Language::get('请登录')]);die;
        }
        $cash_info = $token_model->table('cashsys_users')->where(['id'=>$token_info['casher_id']])->find();
        if(!$cash_info){
            echo json_encode(['status'=>266,'msg'=>Language::get('请登录')]);die;
        }
        $dian_info = $token_model->table('dian')->where(['id'=>$cash_info['dian_id']])->find();
        if(!$dian_info){
            echo json_encode(['status'=>266,'msg'=>Language::get('请登录')]);die;
        }
        if($cash_info['is_leader']){
            if(! (
                (C('sld_cashersystem') && C('cashersystem_isuse') && $dian_info['cash_status'])
                ||
                (C('sld_ldjsystem') && C('ldj_isuse') && $dian_info['ldj_status'])
            )){
                echo json_encode(['status'=>255,'msg'=>'该功能已关闭']);die;
            }
        }else{
            if(! (C('sld_cashersystem') && C('cashersystem_isuse') && $dian_info['cash_status'])){
                echo json_encode(['status'=>255,'msg'=>'该功能已关闭']);die;
            }
        }
        $this->dian_info =  $dian_info;
    }
 /**
     * @api {post} index.php?app=seting&mod=dianDistanceSeting&sld_addons=ldj 店铺配送距离设置
     * @apiVersion 0.1.0
     * @apiName dianDistanceSeting
     * @apiGroup Bmobile
     * @apiDescription 店铺配送距离设置
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/bmobile/index.php?app=seting&mod=dianDistanceSeting&sld_addons=ldj
     * @apiParam {String} key 门店登录key值
     * @apiParam {String} type 类型:save保存,如果不是save或其他表示获取店铺配送范围配置
     * @apiParam {Float} ldj_delivery_order_MinPrice 下单最低起送价
     * @apiParam {Number} ldj_delivery_order_MinDistance 最近配送范围（km）
     * @apiParam {Number} ldj_delivery_order_MaxDistance 最远配送范围（km）
     * @apiParam {Float} ldj_delivery_order_Price 基础配送费（元）
     * @apiParam {Number} ldj_delivery_order_PerDistance 超出最近配送范围后单位距离（km）
     * @apiParam {Float} ldj_delivery_order_PerPrice 超出最近配送范围内每单位距离内额外收取配送费（元）
     * @apiSuccess {Number} status 状态
     * @apiSuccess {String} msg 返回说明信息
     * @apiSuccess {Json} data 配置信息
     * @apiSuccessExample {json} 成功的例子:
     *     //当type是save时
     *      {
     *         "status":200
     *         "msg": "保存成功",
     *         "data": {
     *                          配置信息...
     *                     }
     *      }
     *      //当type不为save时
      *      {
      *         "status":200
      *         "data": {
      *                          配置信息...
      *                     }
      *      }
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "修改失败"
     *      }
     *     /
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *
     */
    public function dianDistanceSeting()
    {
        try{
            $dian_id = $this->dian_info['id'];
            $dian_model = M('ldj_dian','ldj');
            if($_POST['type'] == 'save'){
                $update_data = [];
                if($_POST['ldj_delivery_order_MinPrice']>=0){
                    $update_data['ldj_delivery_order_MinPrice'] = $_POST['ldj_delivery_order_MinPrice'];
                }
                if($_POST['ldj_delivery_order_MinDistance']>=0){
                    $update_data['ldj_delivery_order_MinDistance'] = $_POST['ldj_delivery_order_MinDistance'];
                }
                if($_POST['ldj_delivery_order_MaxDistance']>=0){
                    $update_data['ldj_delivery_order_MaxDistance'] = $_POST['ldj_delivery_order_MaxDistance'];
                }
                if($_POST['ldj_delivery_order_Price']>=0){
                    $update_data['ldj_delivery_order_Price'] = $_POST['ldj_delivery_order_Price'];
                }
//                if($_POST['ldj_delivery_order_PerDistance']>=0){
//                    $update_data['ldj_delivery_order_PerDistance'] = $_POST['ldj_delivery_order_PerDistance'];
//                }
                if($_POST['ldj_delivery_order_PerPrice']>=0){
                    $update_data['ldj_delivery_order_PerPrice'] = $_POST['ldj_delivery_order_PerPrice'];
                }
                $res = $dian_model->updateDian(['id'=>$dian_id],$update_data);
                if(!$res){
                    throw new Exception('修改失败');
                }
                $dian_info = $this->getseting($dian_id);
                echo json_encode(['status'=>200,'msg'=>'保存成功','data'=>$dian_info]);die;
            }else{
                $dian_info = $this->getseting($dian_id);
                echo json_encode(['status'=>200,'data'=>$dian_info]);die;
            }
        }catch(Exception $e){
            echo json_encode(['status'=>255,'msg'=>$e->getMessage()]);die;
        }

    }
    /**
     * @api {post} index.php?app=seting&mod=dianSalesSeting&sld_addons=ldj 店铺营业时间,开关接口
     * @apiVersion 0.1.0
     * @apiName dianDistanceSeting
     * @apiGroup Bmobile
     * @apiDescription 店铺营业时间,开关接口
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/bmobile/index.php?app=seting&mod=dianSalesSeting&sld_addons=ldj
     * @apiParam {String} key 门店登录key值
     * @apiParam {String} type 类型:save保存,如果不是save或其他表示获取店铺营业时间开关公告配置
     * @apiParam {Number} status 门店开关
     * @apiParam {String} start_time 营业开始时间  08:30
     * @apiParam {String} end_time 营业结束时间  22:30
     * @apiParam {String} ldj_notice 店铺公告
     * @apiSuccess {Number} status 状态
     * @apiSuccess {String} msg 返回说明信息
     * @apiSuccess {Json} data 配置信息
     * @apiSuccessExample {json} 成功的例子:
     *     //当type是save时
     *      {
     *         "status":200
     *         "msg": "保存成功",
     *         "data": {
     *                          配置信息...
     *                     }
     *      }
     *      //当type不为save时
     *      {
     *         "status":200
     *         "data": {
     *                          配置信息...
     *                     }
     *      }
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "修改失败"
     *      }
     *     /
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *
     */
    public function dianSalesSeting(){
        try{
            $dian_id = $this->dian_info['id'];
            $dian_model = M('ldj_dian','ldj');
            if($_POST['type'] == 'save'){
                $update_data = [];
                //店铺状态
                $update_data['status'] = intval($_POST['status'])>=1?1:0;

                //营业时间
                $time = explode(':',$_POST['start_time']);
                if(count($time) != 2 || $time[0]<0 || $time[0]>24 || $time[1]<0 || $time[1]>60){
                    throw new Exception('开始时间不正确');
                }
                $start_time = $time[0]*60+$time[1];
                $time = explode(':',$_POST['end_time']);
                if(count($time) != 2 || $time[0]<0 || $time[0]>24 || $time[1]<0 || $time[1]>60){
                    throw new Exception('结束时间不正确');
                }
                $end_time = $time[0]*60+$time[1];
                $update_data['operation_time'] = $start_time.','.$end_time;

                //公告
                $update_data['ldj_notice'] = trim($_POST['ldj_notice']);
                $res = $dian_model->updateDian(['id'=>$dian_id],$update_data);
                if(!$res){
                    throw new Exception('修改失败');
                }
                $dian_info = $this->getseting($dian_id);
                echo json_encode(['status'=>200,'msg'=>'保存成功','data'=>$dian_info]);die;
            }else{
                $dian_info = $this->getseting($dian_id);
                echo json_encode(['status'=>200,'data'=>$dian_info]);die;
            }
        }catch(Exception $e){
            echo json_encode(['status'=>255,'msg'=>$e->getMessage()]);die;
        }
    }
    /*
     * 获取店铺配置
     * dian_id
     */
    private function getseting($dian_id){
        $return_data = [];
        $dian_model = M('ldj_dian','ldj');
        $condition = [
            'id'=>$dian_id
        ];
        $dian_info = $dian_model->getDianInfo($condition,$field='*');
        if(!$dian_info){
            throw new Exception('店铺不存在');
        }
        $return_data['id'] = $dian_info['id'];
        $return_data['dian_name'] = $dian_info['dian_name'];
        //下单最低起送价
        $return_data['ldj_delivery_order_MinPrice'] = $dian_info['ldj_delivery_order_MinPrice'];
        //基础配送范围（km）
        $return_data['ldj_delivery_order_MinDistance'] = $dian_info['ldj_delivery_order_MinDistance'];
        //最远配送范围（km）
        $return_data['ldj_delivery_order_MaxDistance'] = $dian_info['ldj_delivery_order_MaxDistance'];
        //基础配送费（元）
        $return_data['ldj_delivery_order_Price'] = $dian_info['ldj_delivery_order_Price'];
        //每超出基础配送范围（km）
//        $return_data['ldj_delivery_order_PerDistance'] = $dian_info['ldj_delivery_order_PerDistance'];
        //每超出基础配送范围额外收取配送费（元）
        $return_data['ldj_delivery_order_PerPrice'] = $dian_info['ldj_delivery_order_PerPrice'];
        //营业状态
        $return_data['status'] = $dian_info['status'];
        //营业时间
        $return_data['operation_time'] = $dian_info['operation_time'];
        $time = explode(',',$return_data['operation_time']);

        $return_data['start_time'] = sprintf("%'02d",floor($time[0]/60)).':'.sprintf("%'02d",floor($time[0]%60));
        $return_data['end_time'] = sprintf("%'02d",floor($time[1]/60)).':'.sprintf("%'02d",floor($time[1]%60));
        //公告
        $return_data['ldj_notice'] = $dian_info['ldj_notice'];
        return $return_data;
    }
    /*
     * 门店状态营业接口
     * key
     */
    public function mendianInfo()
    {
        $dian_id = $this->dian_info['id'];
        $dian_model = M('ldj_dian','ldj');
        $dian_status = $dian_model->getDianInfo(['id'=>$dian_id],$field='status');
        $res = $dian_status['status']?'营业中':'已关闭';
        echo json_encode(['status'=>200,'data'=>$res]);die;
    }
}