<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/11/12
 * Time: 11:40
 */
class billCtl
{
    private $vendor_info;
    private $bill_state = ['1'=>'已出账','2'=>'已确认','3'=>'已审核','4'=>'已结算'];
    private $bill_payment = ['1'=>'银行','2'=>'支付宝','3'=>'微信'];
    public function __construct()
    {
        if(!((C('sld_cashersystem') && C('cashersystem_isuse')) || (C('sld_ldjsystem') && C('ldj_isuse')))){
            echo json_encode(['status'=>255,'msg'=>'当前模块未开启']);die;
        }
        $this->checkToken();
    }
    /**
     * @api {get} index.php?app=bill&mod=billIndex&sld_addons=common 门店结算列表
     * @apiVersion 0.1.0
     * @apiName billIndex
     * @apiGroup App
     * @apiDescription 门店结算列表
     * @apiExample 请求地址:
     * curl -i http://site2.slodon.cn/bmobile/index.php?app=bill&mod=billIndex&sld_addons=common
     * @apiParam {String} key 店铺登录的key值
     * @apiParam {Number} type 1:已出账,2:已确认,3:已审核,4:已结算
     * @apiParam {Number} page 每页显示数量
     * @apiParam {Number} pn 第几页
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} data 结算列表
     * @apiSuccessExample {json} 成功的例子:
     *            {
     *                   "status": 200,
     *                   "data": {
     *                           "list": [
     *                                       {
     *                                           "ob_id": "470",
     *                                           "ob_no": "0",
     *                                           "ob_start_date": "2018-09-17",
     *                                           "ob_end_date": "2018-09-23",
     *                                           "ob_state": "1",
     *                                           "ob_state_str": "已出账",
     *                                           "ob_result_totals": "0.00"
     *                                       }
     *                           ],
     *                           "ismore": {
     *                                           "hasmore": true,
     *                                           "page_total": 6
     *                           }
     *                   }
     *           }
     *
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *
     */
    public function billIndex()
    {
        $dian_id = $this->vendor_info['id'];
        $bill_model = M('common_bill','common');
        $type = isset($_GET['type'])?intval($_GET['type']):1;
        $page = isset($_GET['page'])?intval($_GET['page']):10;
        try{
            $condition = [
                'ob_vid'=>$dian_id,
                'ob_state'=>$type
            ];
            $field = ['ob_id','ob_no','ob_start_date','ob_end_date','ob_state','ob_result_totals'];
            $field = implode(',',$field);
            $return_data =  $bill_model->getbilllist($condition,$field,$page,'',$order='ob_id desc');
            $ismore = mobile_page($bill_model->gettotalpage());
            if(!$return_data){
                throw new Exception('暂无相关数据');
            }
            foreach($return_data as $k=>$v){
                $return_data[$k]['ob_start_date'] = date('Y-m-d',$v['ob_start_date']);
                $return_data[$k]['ob_end_date'] = date('Y-m-d',$v['ob_end_date']);
                $return_data[$k]['ob_state_str'] = $this->bill_state[$v['ob_state']];
            }
        }catch(Exception $e){
            echo json_encode(['status'=>200,'data'=>['list'=>[],'ismore'=>$ismore],'msg'=>$e->getMessage()]);die;
        }
        echo json_encode(['status'=>200,'data'=>['list'=>$return_data,'ismore'=>$ismore]]);die;
    }

    /**
     * @api {get} index.php?app=bill&mod=billDesc&sld_addons=common 结算详情
     * @apiVersion 0.1.0
     * @apiName billDesc
     * @apiGroup App
     * @apiDescription  结算详情
     * @apiExample 请求地址:
     * curl -i http://site2.slodon.cn/bmobile/index.php?app=bill&mod=billDesc&sld_addons=common
     * @apiParam {String} key 店铺登录的key值
     * @apiParam {Number} ob_id 结算id
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} data 结算列表
     * @apiSuccessExample {json} 成功的例子:
     *       {
     *               "status": 200,
     *               "data": {
     *                       "ob_id": "672",
     *                       "ob_no": "0",
     *                       "ob_result_totals": "618.60",
     *                       "ob_start_date": "2018-11-09",
     *                       "ob_end_date": "2018-11-09",
     *                       "ob_create_date": "2018-11-12",
     *                       "ob_pay_date": "0",
     *                       "ob_state": "1",
     *                       "ob_state_str": "已出账",
     *                       "bill_desc": "¥618.60 = (432.00(订单金额) - 0(平台佣金) - 0.00(分销佣金) - 0.00(退款金额) + 0.00(退款佣金) + 0.00(O2O收银总金额) + 216.00(O2O到家金额)) * 95%(结算比例) + 3(总运费)"
     *              }
     *       }
     *
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *
     */
    public function billDesc()
    {
        $ob_id = intval($_GET['ob_id']);
        $bill_model = M('common_bill','common');
        $return_data = [];
        try{
            $condition = [
                'ob_id'=>$ob_id,
                'ob_vid'=>$this->vendor_info['id']
            ];
            $bill_desc = $bill_model->getBillDesc($condition);
            if(!$bill_desc){
                throw new Exception('暂无相关数据');
            }
            $return_data['ob_id'] = $bill_desc['ob_id'];
            $return_data['ob_no'] = $bill_desc['ob_no'];
            $return_data['ob_result_totals'] = $bill_desc['ob_result_totals'];
            $return_data['ob_start_date'] = date('Y-m-d',$bill_desc['ob_start_date']);
            $return_data['ob_end_date'] = date('Y-m-d',$bill_desc['ob_end_date']);
            $return_data['ob_create_date'] = date('Y-m-d',$bill_desc['ob_create_date']);
            $return_data['ob_pay_date'] = $bill_desc['ob_pay_date']?date('Y-m-d',$bill_desc['ob_pay_date']):$bill_desc['ob_pay_date'];
            $return_data['ob_state'] = $bill_desc['ob_state'];
            $return_data['ob_state_str'] = $this->bill_state[$bill_desc['ob_state']];
            //总佣金
            $all_ob_commis_totals = $bill_desc['ob_commis_totals']+$bill_desc['cash_commis_totals']+$bill_desc['ldj_commis_totals'];
            //总运费
            $all_shipping_total = $bill_desc['ob_shipping_totals'] + $bill_desc['ldj_shipping_totals'];
            //结算比例
            $ratio = 100-$bill_desc['profit'];
            $return_data['bill_desc'] = "¥{$bill_desc['ob_result_totals']} = ({$bill_desc['ob_order_totals']}(订单金额) - {$all_ob_commis_totals}(平台佣金) - {$bill_desc['order_yongjin_totals']}(分销佣金) - {$bill_desc['ob_order_return_totals']}(退款金额) + {$bill_desc['ob_commis_return_totals']}(退款佣金) + {$bill_desc['cash_order_totals']}(O2O收银总金额) + {$bill_desc['ldj_order_totals']}(O2O到家金额)) * {$ratio}%(结算比例) + {$all_shipping_total}(总运费)";
        }catch(Exception $e){
                echo json_encode(['status'=>200,'data'=>[],'msg'=>$e->getMessage()]);die;
        }
        echo json_encode(['status'=>200,'data'=>$return_data]);die;
    }
    /**
     * @api {get} index.php?app=bill&mod=handle_bill&sld_addons=common 确认结算单
     * @apiVersion 0.1.0
     * @apiName handle_bill
     * @apiGroup App
     * @apiDescription  确认结算单
     * @apiExample 请求地址:
     * curl -i http://site2.slodon.cn/bmobile/index.php?app=bill&mod=handle_bill&sld_addons=common
     * @apiParam {String} key 店铺登录的key值
     * @apiParam {Number} ob_id 结算id
     * @apiSuccess {Number} status 状态
     * @apiSuccess {String} msg 提示信息
     * @apiSuccessExample {json} 成功的例子:
     *       {
     *               "status": 200,
     *               "msg": 操作成功
     *       }
     *
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *      /
     *      {
     *          "status":255,
     *          "msg": "结算单无权操作"
     *      }
     *
     */
    public function handle_bill()
    {
        $ob_id = intval($_GET['ob_id']);
        $bill_model = M('common_bill','common');
        try{
            $condition = [
                'ob_id'=>$ob_id,
                'ob_vid'=>$this->vendor_info['id']
            ];
            $bill_desc = $bill_model->getBillDesc($condition);
            if(!$bill_desc){
                throw new Exception('结算单不存在');
            }
            if($bill_desc['ob_state'] != 1){
                throw new Exception('结算单无权操作');
            }
            //检测门店是够设置结算账号信息
            $jiesuan_info = $bill_model->table('jiesuan_account')->where(['dian_id'=>$this->vendor_info['id'],'is_dian'=>1,'is_default'=>1])->find();
            if(!$jiesuan_info){
                throw new Exception('您还未设置结算账号');
            }
            $res = $bill_model->table('dian_bill')->where( $condition)->update(['ob_state'=>2]);
            if(!$res){
                throw new Exception('操作失败');
            }
        }catch(Exception $e){
                echo json_encode(['status'=>255,'msg'=>$e->getMessage()]);die;
        }
        echo json_encode(['status'=>200,'msg'=>'操作成功']);die;
    }
    /**
     * @api {get} index.php?app=bill&mod=addAccount&sld_addons=common 添加/编辑结算账号
     * @apiVersion 0.1.0
     * @apiName addAccount
     * @apiGroup App
     * @apiDescription  添加/编辑结算账号
     * @apiExample 请求地址:
     * curl -i http://site2.slodon.cn/bmobile/index.php?app=bill&mod=addAccount&sld_addons=common
     * @apiParam {String} key 店铺登录的key值
     * @apiParam {String} type add:新增,edit:编辑
     * @apiParam {Number} id 结算账号信息id (type=edit时必填)
     * @apiParam {Number} is_default 是否是默认选项:1/0
     * @apiParam {Number} j_type 付款方式 1 银行 2 支付宝 3 微信
     * @apiParam {String} j_bank 收款人账号
     * @apiParam {String} j_name 收款人姓名
     * @apiSuccess {Number} status 状态
     * @apiSuccess {String} msg 提示信息
     * @apiSuccessExample {json} 成功的例子:
     *       {
     *               "status": 200,
     *               "msg":操作成功
     *       }
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *      /
     *      {
     *          "status":255,
     *          "msg": "操作失败"
     *      }
     *
     */
    public function addAccount()
    {
        $account_model = M('common_account','common');
        $type = trim($_GET['type']);
        $j_type = $_GET['j_type'];
        $j_bank = $_GET['j_bank'];
        $j_name = $_GET['j_name'];
            try{
                    if(empty($j_type) || empty($j_bank) || empty($j_name)){
                            throw new Exception('请填写完整账号信息');
                    }
                $account_model->begintransaction();
                if($type == 'add'){
                    $insert = [
                        'dian_id'=>$this->vendor_info['id'],
                        'is_dian'=>1,
                        'is_default'=>intval($_GET['is_default']),
                        'j_type'=>$j_type,
                        'j_bank'=>$j_bank,
                        'j_name'=>$j_name
                    ];
                    if($insert['is_default']){
                        $account_model->editAccountInfo(['dian_id'=>$this->vendor_info['id'],'is_dian'=>1,'is_default'=>1],['is_default'=>0]);
                    }
                    $res = $account_model->addAccountInfo($insert);
                    if(!$res){
                        throw new Exception('添加失败');
                    }
                }else{
                    if(intval($_GET['id']) <1){
                        throw new Exception('操作失败');
                    }
                    $update = [
                        'is_default'=>intval($_GET['is_default']),
                        'j_type'=>$j_type,
                        'j_bank'=>$j_bank,
                        'j_name'=>$j_name
                    ];
                    if($update['is_default']){
                        $account_model->editAccountInfo(['dian_id'=>$this->vendor_info['id'],'is_dian'=>1,'is_default'=>1],['is_default'=>0]);
                    }
                    $res = $account_model->editAccountInfo(['dian_id'=>$this->vendor_info['id'],'is_dian'=>1,'id'=>$_GET['id']],$update);
                    if(!$res){
                        throw new Exception('操作失败');
                    }
                }
                $account_model->commit();
                echo json_encode(['status'=>200,'msg'=>'操作成功']);
            }catch(Exception $e){
                $account_model->rollback();
                echo json_encode(['status'=>255,'msg'=>$e->getMessage()]);die;
            }
    }
    /**
     * @api {get} index.php?app=bill&mod=getAccountInfo&sld_addons=common 获取结算账号详细信息
     * @apiVersion 0.1.0
     * @apiName getAccountInfo
     * @apiGroup App
     * @apiDescription  获取结算账号详细信息
     * @apiExample 请求地址:
     * curl -i http://site2.slodon.cn/bmobile/index.php?app=bill&mod=getAccountInfo&sld_addons=common
     * @apiParam {String} key 店铺登录的key值
     * @apiParam {Number} id 结算账号信息id
     * @apiSuccess {Number} status 状态
     * @apiSuccess {String} data 提示信息
     * @apiSuccessExample {json} 成功的例子:
     *       {
     *               "status": 200,
     *               "data": {
     *                           id:1,
     *                           dian_id:8,
     *                           is_dian:1,
     *                           is_default:1,
     *                           j_type:2,
     *                           j_type_str:支付宝,
     *                           j_bank:22737373727,
     *                           j_name:陈彦伯,
     *                       }
     *       }
     *
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *
     */
    public function getAccountInfo()
    {
        $account_model = M('common_account','common');
        $id = intval($_GET['id']);
        try{
                $condition = [
                    'id'=>$id,
                    'dian_id'=>$this->vendor_info['id'],
                    'is_dian'=>1
                ];
            $accountInfo = $account_model->getAcountInfo($condition);
            $accountInfo['j_type_str'] = $this->bill_payment[$accountInfo['$accountInfo']];
            echo json_encode(['status'=>200,'data'=>$accountInfo]);die;
        }catch(Exception $e){
            echo json_encode(['status'=>255,'msg'=>$e->getMessage()]);die;
        }
    }
    /**
     * @api {get} index.php?app=bill&mod=deleteAccount&sld_addons=common 删除结算账号信息
     * @apiVersion 0.1.0
     * @apiName deleteAccount
     * @apiGroup App
     * @apiDescription  删除结算账号信息
     * @apiExample 请求地址:
     * curl -i http://site2.slodon.cn/bmobile/index.php?app=bill&mod=deleteAccount&sld_addons=common
     * @apiParam {String} key 店铺登录的key值
     * @apiParam {Number} id 结算账号信息id
     * @apiSuccess {Number} status 状态
     * @apiSuccess {String} msg 提示信息
     * @apiSuccessExample {json} 成功的例子:
     *       {
     *               "status": 200,
     *               "msg": 操作成功
     *       }
     *
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *      /
     *      {
     *          "status":255,
     *          "msg": "操作失败"
     *      }
     *
     */
    public function deleteAccount()
    {
        $account_model = M('common_account','common');
        $id = intval($_GET['id']);
        try{
            if($id < 1){
                    throw new Exception('操作失败');
            }
            $condition = [
                'id'=>$id,
                'dian_id'=>$this->vendor_info['id'],
                'is_dian'=>1,
            ];
            $res = $account_model->deleteAccountInfo($condition);
            if(!$res){
                throw new  Exception('操作失败');
            }
        }catch(Exception $e){
                echo json_encode(['status'=>255,'msg'=>$e->getMessage()]);die;
        }
        echo json_encode(['status'=>200,'msg'=>'操作成功']);die;
    }
    // 校验token
    private function checkToken()
    {
        //登录信息
        if($_POST['key']){
            $key = trim($_POST['key']);
        }else{
            $key = trim($_GET['key']);
        }
        if(!$key){
            echo json_encode(['status'=>266,'msg'=>Language::get('请登录')]);die;
        }
        //如果收银开启
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
            echo json_encode(['status'=>266,'msg'=>'门店不存在']);die;
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
        $this->vendor_info =  $dian_info;
        $this->vendor_info['casher_name'] =  $cash_info['casher_name'];

    }
}