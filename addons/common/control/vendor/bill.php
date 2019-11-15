<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/10/23
 * Time: 11:53
 */
class billCtl{
    private $vendor_info;
    private $bill_state = ['1'=>'已出账','2'=>'门店已确认','3'=>'店铺已审核','4'=>'结算完成'];
    private $bill_payment = ['1'=>'银行','2'=>'支付宝','3'=>'微信'];
    public function __construct(){
        $this->checkToken();
    }
    /**
     * @api {get} index.php?app=bill&mod=bll_list&sld_addons=common 商户后台门店结算列表
     * @apiVersion 0.1.0
     * @apiName bll_list
     * @apiGroup Vendor
     * @apiDescription 商户后台门店结算列表
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/vendor/index.php?app=bill&mod=bll_list&sld_addons=common
     * @apiParam {String} key 店铺登录的key值
     * @apiParam {String} keyworld 关键字搜索,可选
     * @apiParam {Number} ob_state :1默认2门店已确认3店铺已审核4(店铺已打钱)结算完成
     * @apiParam {Number} page 分页
     * @apiParam {Number} pn 第几页
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} list 结算列表
     * @apiSuccess {Json} pagination 分页信息
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200,
     *         "list":[
     *              {
     *                   "ob_vid": "16",
     *                   "ob_store_name": "徐家汇永丰店",
     *                   "time": "2018-02-11 - 2018-10-08",
     *                   "total_money": "5307.85",
     *                   "balance_cycle": "30",
     *                   "profit": "5",
     *                   "allnum": "8",
     *                   "has_total": 20,
     *                   "handle_num": "8"
     *              }
     *          ],
     *         "pagination":{
     *                   "current": "1",
     *                   "pageSize": "10",
     *                   "total": "47"
     *               }
     *
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "暂无相关数据"
     *      }
     *      /
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *
     */
    public function bll_list()
    {

        $bill_model = M('common_bill','common');
        try{
            $vid = $this->vendor_info['vid'];
//            $vid = 8;
            //取出店铺下面搜索的门店
            $condition = [
                'vid'=>$vid
            ];
            $Adian_id = $bill_model->table('dian')->where($condition)->field('id')->select();
            if(!$Adian_id){
                throw new Exception('暂无相关记录');
            }
            $Sdian_id = implode(',',low_array_column($Adian_id,'id'));
            $page = $_GET['page']?:10;

            $condition = [
                'ob_vid'=>['in',$Sdian_id]
            ];
            if(isset($_GET['ob_state']) && !empty($_GET['ob_state'])){
                $condition['ob_state'] = intval($_GET['ob_state']);
            }
            if(isset($_GET['keyworld']) && !empty($_GET['keyworld'])){
                $condition['ob_store_name'] = ['like','%'.trim($_GET['keyworld']).'%'];
            }
            $order = 'ob_id desc';
            $group = 'ob_vid';
            $bill_list = $bill_model->getbilllist($condition,'ob_vid',$page,$group,$order);
            $total = $bill_model->gettotalnum();
            foreach($bill_list as $k=>$v){
                $dian_info = $bill_model->table('dian')->where(['id'=>$v['ob_vid']])->field('balance_cycle,profit')->find();
                $bill_info = $bill_model->table('dian_bill')->where(['ob_vid'=>$v['ob_vid']])->field('ob_id,ob_vid,ob_store_name,min( ob_start_date ) AS start_time,max( ob_end_date ) AS end_time,sum(ob_result_totals) AS total,count(*) as allnum')->find();
                $bill_has_handle = $bill_model->table('dian_bill')->where(['ob_vid'=>$v['ob_vid'],'ob_state'=>4])->field('sum(ob_result_totals) AS total')->find();
                $bill_no_handle = $bill_model->table('dian_bill')->where(['ob_vid'=>$v['ob_vid'],'ob_state'=>1])->field('count(*) AS num')->find();

                $bill_list[$k]['ob_store_name'] = $bill_info['ob_store_name'];
                $bill_list[$k]['time'] = date('Y-m-d',$bill_info['start_time']).' - '.date('Y-m-d',$bill_info['end_time']);
                $bill_list[$k]['total_money'] = $bill_info['total'];
                $bill_list[$k]['balance_cycle'] = $dian_info['balance_cycle'];
                $bill_list[$k]['profit'] = $dian_info['profit'];
                $bill_list[$k]['allnum'] = $bill_info['allnum'];
                $bill_list[$k]['has_total'] = $bill_has_handle['total']?:0;
                $bill_list[$k]['handle_num'] = $bill_no_handle['num']?:0;

            }
            echo json_encode([
                'status'=>200,
                'list'=>$bill_list,
                'pagination'=>['current'=>$_GET['pn'],'pageSize'=>$page,'total'=>$total]
            ]);die;
        }catch(Exception $e){
            echo json_encode(['status'=>255,'msg'=>$e->getMessage()]);die;
        }

    }
    /**
     * @api {get} index.php?app=bill&mod=dian_bill_list&sld_addons=common 单个门店结算总列表
     * @apiVersion 0.1.0
     * @apiName dian_bill_list
     * @apiGroup Vendor
     * @apiDescription 单个门店结算总列表
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/vendor/index.php?app=bill&mod=dian_bill_list&sld_addons=common
     * @apiParam {String} key 店铺登录的key值
     * @apiParam {String} dian_id 门店id
     * @apiParam {Number} page 分页
     * @apiParam {Number} pn 第几页
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} list 结算列表
     * @apiSuccess {Json} pagination 分页信息
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200,
     *         "data":[
     *              {
     *                   数据...
     *              }
     *          ],
     *         "pagination":{
     *                   "current": "1",
     *                   "pageSize": "10",
     *                   "total": "47"
     *               }
     *
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "暂无相关数据"
     *      }
     *      /
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *
     */
    public function dian_bill_list()
    {
        $dian_id = intval($_GET['dian_id']);
//        $dian_id = 15;
        $bill_model = M('common_bill','common');
        $page = $_GET['page']?:10;
        $_GET['pn'] = $_GET['pn']?:1;
        $return_data = [];
        try{
            $condition = [
                'ob_vid'=>$dian_id
            ];
            $list = $bill_model->getbilllist($condition,'*',$page,'','ob_id desc');
            $total = $bill_model->gettotalnum();
            foreach($list as $k=>$v) {
                $return_data[$k]['ob_id'] = $v['ob_id'];
                $return_data[$k]['ob_store_name'] = $v['ob_store_name'];
                $return_data[$k]['ob_vid'] = $v['ob_vid'];
                $return_data[$k]['start_date'] = date('Y-m-d',$v['ob_start_date']);
                $return_data[$k]['end_date'] = date('Y-m-d',$v['ob_end_date']);
                $return_data[$k]['ob_order_totals'] = $v['ob_order_totals'] + $v['cash_order_totals'] + $v['ldj_shipping_totals'];
                $return_data[$k]['ob_shipping_totals'] = $v['ob_shipping_totals'] + $v['ldj_shipping_totals'];
                $return_data[$k]['ob_commis_totals'] = $v['ob_commis_totals'] + $v['cash_commis_totals'] + $v['ldj_commis_totals'];
                $return_data[$k]['profit'] = $v['profit'];
                $return_data[$k]['ob_order_return_totals'] = $v['ob_order_return_totals'];
                $return_data[$k]['ob_commis_return_totals'] = $v['ob_commis_return_totals'];
                $return_data[$k]['order_yongjin_totals'] = $v['order_yongjin_totals'];
                $return_data[$k]['ob_result_totals'] = $v['ob_result_totals'];
                $return_data[$k]['ob_create_date'] = date('Y-m-d',$v['ob_create_date']);
                $return_data[$k]['ob_state_str'] = $this->bill_state[$v['ob_state']];
            }
            echo json_encode([
                'status'=>200,
                'data'=>$return_data,
                'pagination'=>['current'=>$_GET['pn'],'pageSize'=>$page,'total'=>$total]
            ]);
        }catch(Exception $e){
            echo json_encode(['status'=>255,'msg'=>$e->getMessage()]);die;
        }
    }


    /**
     * @api {get} index.php?app=bill&mod=bill_desc&sld_addons=common 单期结算详情
     * @apiVersion 0.1.0
     * @apiName bill_desc
     * @apiGroup Vendor
     * @apiDescription 单期结算详情
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/vendor/index.php?app=bill&mod=bill_desc&sld_addons=common
     * @apiParam {String} key 店铺登录的key值
     * @apiParam {String} ob_id 结算id
     * @apiParam {Number} type 类型(可选) 1:商城订单 2:收银订单 3:O2O订单
     * @apiParam {String} start_time 开始时间(可选) 2018-05-20
     * @apiParam {String} end_time 结束时间(可选) 2018-05-20
     * @apiParam {Number} page 分页
     * @apiParam {Number} pn 第几页
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} list 结算列表
     * @apiSuccess {Json} pagination 分页信息
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200,
     *         "bill_info":{
     *                  结算详情...
     *          }
     *         "list":{
     *                  订单列表...
     *          }
     *         "pagination":{
     *                   "current": "1",
     *                   "pageSize": "10",
     *                   "total": "47"
     *               }
     *
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "暂无相关数据"
     *      }
     *      /
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *
     */
    public function bill_desc()
    {
        $ob_id = intval($_GET['ob_id']);
        $bill_model = M('common_bill','common');
        if(isset($_GET['type']) && !empty($_GET['type'])){
            $type = intval($_GET['type']);
        }else{
            $type = 0;
        }
        $return_data = [];
        try{
            $bill_info = $bill_model->table('dian_bill')->where(['ob_id'=>$ob_id])->find();
            if(!$bill_info){
                throw new Exception('未找到结算数据');
            }
            //条件组装
            if(isset($_GET['start_time']) && !empty($_GET['start_time'])){
                $start_time = strtotime(trim($_GET['start_time']));
                if($start_time > $bill_info['ob_start_date']){
                    $bill_info['ob_start_date'] = $start_time;
                }
            }
            if(isset($_GET['end_time']) && !empty($_GET['end_time'])){
                $end_time = strtotime(trim($_GET['end_time']));
                if($end_time < $bill_info['ob_end_date']){
                    $bill_info['ob_end_date'] = $end_time;
                }
            }

            switch($type){
                case 1:
                    $sld_order = $this->sld_order($bill_info);
                    break;
                case 2:
                    $cash_order = $this->cash_order($bill_info);
                    break;
                case 3:
                    $ldj_order = $this->ldj_order($bill_info);
                    break;
                default:
                    $sld_order = $this->sld_order($bill_info);
                    $cash_order = $this->cash_order($bill_info);
                    $ldj_order = $this->ldj_order($bill_info);
                    break;
            }
            if(isset($sld_order['sld']) && is_array($sld_order['sld']) && !empty($sld_order['sld'])){
                foreach($sld_order['sld'] as $k=>$v){
                    $return_data[] = $v;
                }
            }
            if(isset($cash_order['cash']) && is_array($cash_order['cash']) && !empty($cash_order['cash'])){
                foreach($cash_order['cash'] as $k=>$v){
                    $return_data[] = $v;
                }
            }
            if(isset($ldj_order['ldj']) && is_array($ldj_order['ldj']) && !empty($ldj_order['ldj'])){
                foreach($ldj_order['ldj'] as $k=>$v){
                    $return_data[] = $v;
                }
            }
            if(empty($return_data)){
                throw new Exception('暂无相关数据');
            }
            $totalnum = [$sld_order['total'],$cash_order['total'],$ldj_order['total']];
            sort($totalnum,1);
            $total = end($totalnum);
            array_multisort(low_array_column($return_data,'finnshed_time'),SORT_ASC,SORT_NUMERIC,$return_data);
            //结算详细数据
            $bill_info['ob_start_date'] = date('Y-m-d',$bill_info['ob_start_date']);
            $bill_info['ob_end_date'] = date('Y-m-d',$bill_info['ob_end_date']);
            $bill_info['ob_create_date'] = date('Y-m-d',$bill_info['ob_create_date']);
            $bill_info['ob_state_str'] = $this->bill_state[$bill_info['ob_state']];
            $dian_info = $bill_model->table('dian,jiesuan_account')->join('left')->on('dian.id=jiesuan_account.dian_id')->where(['dian.id'=>$bill_info['ob_vid']])->field('dian.dian_phone,dian.dian_address,jiesuan_account.is_dian,jiesuan_account.is_default,jiesuan_account.j_type,jiesuan_account.j_bank,jiesuan_account.j_name')->find();
            $phone = explode(',',$dian_info['dian_phone'])[0];
            $bill_info['area_str'] =  $dian_info['dian_address']." ({$phone})";
//            dd($dian_info);die;
            if($dian_info['is_dian'] != 1 || $dian_info['is_default'] != 1){
                $bill_info['jiesuan'] = '门店未设置结算账号';
            }else{
                $bill_info['jiesuan'] = $this->bill_payment[$bill_info['j_type']].' '.$bill_info['j_name']."({$bill_info['j_bank']})";
            }
            echo json_encode([
                'status'=>200,
                'bill_info'=>$bill_info,
                'list'=>$return_data,
                'pagination'=>['current'=>$_GET['pn'],'pageSize'=>$_GET['page'],'total'=>$total]
            ]);die;
        }catch(Exception $e){
            echo json_encode(['status'=>255,'msg'=>$e->getMessage()]);die;
        }
    }
    //商城订单
    public function sld_order($bill_info){
        $model_order = model('order');
        $bill_model = M('common_bill','common');
        $sld_order = $bill_model->table('order')->where([
            'dian_id'=>$bill_info['ob_vid'],
            'finnshed_time'=>['between',$bill_info['ob_start_date'].','.$bill_info['ob_end_date']],
            'order_state'=>ORDER_STATE_SUCCESS
        ])->field('order_id,order_sn,buyer_name,order_amount,shipping_fee,add_time,finnshed_time')->page(intval($_GET['page']))->select();
        $totalnum = $bill_model->gettotalnum();
        foreach($sld_order as $k=>$v){
            $sld_order[$k]['order_type'] = 1;
            $sld_order[$k]['order_type_str'] = '门店订单';
            $sld_order[$k]['add_time'] = date('Y-m-d',$v['add_time']);
            $sld_order[$k]['finnshed_time1'] = date('Y-m-d',$v['finnshed_time']);
            $order_goods_condition = array();
            $order_goods_condition['order_id'] = $v['order_id'];
            $field = 'SUM(ROUND(goods_pay_price*commis_rate/100,2)) as commis_amoun,SUM(goods_yongjin) as fenxiao_yongjin_amount';
            $order_goods_info = $model_order->getOrderGoodsInfo($order_goods_condition,$field);
            //平台佣金
            $sld_order[$k]['commis_amoun'] = $order_goods_info['commis_amoun']?:'0.00';
            //分销佣金
            $sld_order[$k]['fenxiao_yongjin_amount'] = $order_goods_info['fenxiao_yongjin_amount']?:'0.00';
            //退款退货
            $refound = $model_order->table('refund_return')->where(['refund_return'=>$v['order_id'],'refund_state'=>3])->field('refund_amount,ROUND(refund_amount*commis_rate/100,2) as amount')->find();
            //退款金额
            $sld_order[$k]['refund_amount'] = $refound['refund_amount']?:'0.00';
            //退款佣金
            $sld_order[$k]['refund_amount_yongjin'] = $refound['amount']?:'0.00';
        }
        return ['sld'=>$sld_order,'total'=>$totalnum];
    }
    //收银订单
    public function cash_order($bill_info){
        $model_order = model('order');
        $bill_model = M('common_bill','common');
        $sld_order = $bill_model->table('cashsys_order')->where([
            'dian_id'=>$bill_info['ob_vid'],
            'finnshed_time'=>['between',$bill_info['ob_start_date'].','.$bill_info['ob_end_date']],
            'order_state'=>ORDER_STATE_SUCCESS
        ])->field('order_id,order_sn,buyer_name,order_amount,add_time,finnshed_time')->page(intval($_GET['page']))->select();
        $totalnum = $bill_model->gettotalnum();
        foreach($sld_order as $k=>$v){
            $sld_order[$k]['order_type'] = 2;
            $sld_order[$k]['order_type_str'] = '收银机';
            $sld_order[$k]['add_time'] = date('Y-m-d',$v['add_time']);
            $sld_order[$k]['finnshed_time1'] = date('Y-m-d',$v['finnshed_time']);
            $sld_order[$k]['shipping_fee'] = '0.00';
            $order_goods_condition = array();
            $order_goods_condition['order_id'] = $v['order_id'];
            $field = 'SUM(ROUND(goods_pay_price*commis_rate/100,2)) as commis_amoun';
            $order_goods_info = $model_order->table('cashsys_order_goods')->where($order_goods_condition)->field($field)->find();
            //平台佣金
            $sld_order[$k]['commis_amoun'] = $order_goods_info['commis_amoun']?:'0.00';
            //分销佣金
            $sld_order[$k]['fenxiao_yongjin_amount'] = '0.00';
            //退款退货
//            $refound = $model_order->table('refund_return')->where(['refund_return'=>$v['order_id'],'refund_state'=>3])->field('refund_amount,ROUND(refund_amount*commis_rate/100,2) as amount')->find();
            //退款金额
            $sld_order[$k]['refund_amount'] = '0.00';
            //退款佣金
            $sld_order[$k]['refund_amount_yongjin'] = '0.00';
        }
        return ['cash'=>$sld_order,'total'=>$totalnum];
    }
    //联到家订单
    public function ldj_order($bill_info){
        $model_order = model('order');
        $bill_model = M('common_bill','common');
        $sld_order = $bill_model->table('ldj_order')->where([
            'vid'=>$bill_info['ob_vid'],
            'finnshed_time'=>['between',$bill_info['ob_start_date'].','.$bill_info['ob_end_date']],
            'order_state'=>ORDER_STATE_SUCCESS
        ])->field('order_id,order_sn,buyer_name,order_amount,shipping_fee,add_time,finnshed_time')->page(intval($_GET['page']))->select();
        $totalnum = $bill_model->gettotalnum();
        foreach($sld_order as $k=>$v){
            $sld_order[$k]['order_type'] = 3;
            $sld_order[$k]['order_type_str'] = 'O2O到家';
            $sld_order[$k]['add_time'] = date('Y-m-d',$v['add_time']);
            $sld_order[$k]['finnshed_time1'] = date('Y-m-d',$v['finnshed_time']);
            $order_goods_condition = array();
            $order_goods_condition['order_id'] = $v['order_id'];
            $field = 'SUM(ROUND(goods_pay_price*commis_rate/100,2)) as commis_amoun';
            $order_goods_info = $model_order->table('cashsys_order_goods')->where($order_goods_condition)->field($field)->find();
            //平台佣金
            $sld_order[$k]['commis_amoun'] = $order_goods_info['commis_amoun']?:'0.00';
            //分销佣金
            $sld_order[$k]['fenxiao_yongjin_amount'] = '0.00';
            //退款退货
//            $refound = $model_order->table('refund_return')->where(['refund_return'=>$v['order_id'],'refund_state'=>3])->field('refund_amount,ROUND(refund_amount*commis_rate/100,2) as amount')->find();
            //退款金额
            $sld_order[$k]['refund_amount'] = '0.00';
            //退款佣金
            $sld_order[$k]['refund_amount_yongjin'] = '0.00';
        }
        return ['ldj'=>$sld_order,'total'=>$totalnum];
    }
    /**
     * @api {get} index.php?app=bill&mod=bill_edit&sld_addons=common 修改结算单状态
     * @apiVersion 0.1.0
     * @apiName bill_edit
     * @apiGroup Vendor
     * @apiDescription 修改结算单状态
     * @apiExample 请求地址:
     * curl -i http://ldj.55jimu.com/vendor/index.php?app=bill&mod=bill_edit&sld_addons=common
     * @apiParam {String} key 店铺登录的key值
     * @apiParam {Number} state 状态:3:店铺审核 4:结算完成
     * @apiSuccess {Number} status 状态
     * @apiSuccess {String} msg 消息说明
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200,
     *         "msg":"操作成功"
     *      }
     *
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "操作失败"
     *      }
     *      /
     *      {
     *          "status":266,
     *          "msg": "请登录"
     *      }
     *
     */
    public function bill_edit()
    {
        $ob_id = intval($_GET['ob_id']);
        $type = intval($_GET['state']);
        $bill_model = M('common_bill','common');
        try {
            $bill_info = $bill_model->table('dian_bill')->where(['ob_id'=>$ob_id])->find();
            if(!$bill_info){
                throw new Exception('操作失败');
            }
            switch($type){
                case 3:
                    if($bill_info['ob_state'] != 2){
                        throw new Exception('门店还未确定账单');
                    }
                    break;
                case 4:
                    if($bill_info['ob_state'] != 3){
                        throw new Exception('店铺还未审核账单');
                    }
                    break;
                default:
                    throw new Exception('操作失败');
                    break;
            }
            $res = $bill_model->table('dian_bill')->where(['ob_id'=>$ob_id])->update(['ob_state'=>$type]);
            if(!$res){
                throw new Exception('操作失败');
            }
            echo json_encode(['status'=>200,'msg'=>'操作成功']);die;
        } catch (Exception $e) {
            echo json_encode(['status'=>255,'msg'=>$e->getMessage()]);die;
        }
    }
    // 校验token
    public function checkToken()
    {
        $check_flag = true;
        // 校验token
        $token = $_REQUEST['key'];

        $model_bwap_vendor_token = Model('bwap_vendor_token');
        $bwap_vendor_token_info = $model_bwap_vendor_token->getSellerTokenInfoByToken($token);

        if (empty($bwap_vendor_token_info)) {
            $check_flag = false;
        }

        $model_vendor = Model('vendor');
        $seller_info = model()->table('seller')->where(['seller_id'=>$bwap_vendor_token_info['seller_id']])->find();
        $this->vendor_info = $model_vendor->getStoreInfo(array('vid'=>$seller_info['vid']));
        if(empty($this->vendor_info)) {
            $check_flag = false;
        } else {
            $this->vendor_info['token'] = $bwap_vendor_token_info['token'];
        }

        if (!$check_flag) {
            $state = 266;
            $data = '';
            $message = Language::get('请登录');
            $return_last = array(
                'state' => $state,
                'data' => $data,
                'msg' => $message,
            );

            echo json_encode($return_last);exit;
        }
    }
}