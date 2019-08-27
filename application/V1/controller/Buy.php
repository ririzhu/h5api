<?php
namespace app\V1\controller;

use app\V1\model\UserBuy;

class Buy extends Base
{
    public function __construct() {
        parent::__construct();
        if(!input("member_id")){
            $data["error_code"] = 10016;
            $data['message'] = "缺少参数";
        }
    }

    /**
     * 购物车、直接购买第一步:选择收获地址和配置方式
     */
    public function confirm() {
        $model_buy = new UserBuy();

        $is_supplier = $_POST['is_supplier'] ? intval($_POST['is_supplier']) : 0;

        $extends_data = array();

        $extends_data['from'] = 'pc';
        $invalid_cart= input("invalid_cart");
        $result = $model_buy->buyStep1($_POST['cart_id'], $_POST['ifcart'], $invalid_cart, input("member_id"), null,$is_supplier,$extends_data);
        $memberId = input("member_id");

        if(!empty($result['error'])) {
            showMsg($result['error'], '', 'html', 'error');
        }

        $returns['is_supplier']= $is_supplier;
        $returns['ifcart'] = $result['ifcart'];
        //商品金额计算(分别对每个商品/优惠套装小计、每个店铺小计)
        //平台优惠券
        //if(!Config('sld_red') && !Config('red_isuse')) {
            $red=con_addons('red', $result + ['member' => array('member_id'=>$memberId)],'confirm','buy');

            $returns['red'] = $red['red'];
            $returns['vred'] = $red['vred'];
        //}

        $returns['store_cart_list'] = $result['store_cart_list'];
        $returns['store_goods_total']= $result['store_goods_total'];
        if ($is_supplier) {
            # code...
        }else{
            //取得店铺优惠 - 满即送(赠品列表，店铺满送规则列表)
            $returns['store_premiums_list']= $result['store_premiums_list'];
            $returns['store_mansong_rule_list']= $result['store_mansong_rule_list'];
            //返回店铺可用的优惠券
            if(isset($result['store_voucher_list']))
            $returns['store_voucher_list']= $result['store_voucher_list'];

            if((Config('sld_cashersystem') && Config('cashersystem_isuse')) || (Config('sld_ldjsystem') && Config('ldj_isuse'))){
                //输出自提点信息
                $returns['dian_list']= $result['dian_list'];
            }
        }
        //返回需要计算运费的店铺ID数组 和 不需要计算运费(满免运费活动的)店铺ID及描述
        $returns['need_calc_sid_list']= $result['need_calc_sid_list'];
        $returns['cancel_calc_sid_list']= $result['cancel_calc_sid_list'];

        //将商品ID、数量、运费模板、运费序列化，加密，输出到模板，选择地区AJAX计算运费时作为参数使用
        $returns['freight_hash']= $result['freight_list'];
        //输出用户默认收货地址
        $returns['address_info']= $result['address_info'];
        //输出有货到付款时，在线支付和货到付款及每种支付下商品数量和详细列表
        if(!empty($result['pay_goods_list'])) {
            $returns['pay_goods_list'] = $result['pay_goods_list'];
            $returns['ifshow_offpay'] = $result['ifshow_offpay'];
        }
        if(isset($result['deny_edit_payment']))
        $returns['deny_edit_payment']= $result['deny_edit_payment'];
        //不提供增值税发票时抛出true(模板使用)
        if(!empty($result['vat_deny'])) {
            $returns['vat_deny'] = $result['vat_deny'];
            //增值税发票哈希值(php验证使用)
            $returns['vat_hash'] = $result['vat_hash'];
        }
        //输出默认使用的发票信息
        $returns['inv_info']= $result['inv_info'];
        //显示使用预存款支付及会员预存款
        if(isset($result['available_predeposit']))
        $returns['available_pd_amount']= $result['available_predeposit'];
        if(isset($result['member_points']))
        $returns['member_points']= $result['member_points'];
        if(isset($result['points_max_use']))
        $returns['points_max_use']= $result['points_max_use'];
        if(isset($result['points_purpose_rebate']))
        $returns['points_purpose_rebate']= $result['points_purpose_rebate'];
        if(isset($result['gid']))
        $returns['gid']= $result['gid'];
        $returns['paymethod']= 'againpay';

        //标识 购买流程执行第几步
        $returns['buy_step']='step2';
        return json_encode($returns,true);
    }
    public function ajaxvred()
    {
        $vid = intval($_GET['vid']);
        //平台优惠券
        if(C('sld_red') && C('red_isuse')) {
            $model_red = M('red');
            $condition = [];
            //获得可用优惠券
            $condition['reduser_use'] = array( 'eq',0);
            $condition['redinfo_end'] = array( 'gt',TIMESTAMP);
            $condition['redinfo_start'] = array( 'lt',TIMESTAMP);
            $condition['reduser_uid'] = $_SESSION['member_id'];
            $condition['red_vid'] = $vid;
            $red_list = $model_red->getRedUserList($condition);
            if($red_list)
            {
                $red_list = $model_red->getUseInfo($red_list);
            }
            echo json_encode($red_list);
            die;
        }else{
            echo 0;
            die;
        }
    }
    /**
     * 购物车、直接购买第二步:保存订单入库，产生订单号，开始选择支付方式
     *
     */
    public function submitorder() {
        $model_buy = new UserBuy();
        //处理优惠券
        $_POST['red'] = $_POST['red_id'];
        $_POST['order_from'] = 1;

        $_POST['vred'] = array_filter($_POST['vred']);

        $extends_data = array();

        $extends_data['from'] = 'pc';

        $result = $model_buy->buyStep2($_POST, $_SESSION['member_id'], $_SESSION['member_name'], $_SESSION['member_email'],$extends_data);

        if(!empty($result['error'])) {
            showMsg($result['error'], '', 'html', 'error');
        }

        //转向到商城支付页面
        $pay_url = 'index.php?app=buy&mod=pay&pay_sn='.$result['pay_sn'];
        redirect($pay_url);
    }

    /**
     * 下单时支付页面
     */
    public function pay() {
        $pay_sn	= $_GET['pay_sn'];

        if (!preg_match('/^\d{18}$/',$pay_sn)){
            showMsg(Language::get('该订单不存在'),'index.php?app=userorder','html','error');
        }

        //查询支付单信息
        $model_order= Model('order');
        $pay_info = $model_order->getOrderPayInfo(array('pay_sn'=>$pay_sn,'buyer_id'=>$_SESSION['member_id']));
        if(empty($pay_info)){
            showMsg(Language::get('该订单不存在'),'','html','error');
        }
        $returns['pay_info']=$pay_info;

        //取子订单列表
        $condition = array();
        $condition['pay_sn'] = $pay_sn;
        $condition['order_state'] = array('in',array(ORDER_STATE_NEW,ORDER_STATE_PAY,ORDER_STATE_SEND,ORDER_STATE_SUCCESS));
        $order_list = $model_order->getOrderList($condition,'','order_id,order_state,payment_code,order_amount,pd_amount,order_sn,dian_id,pd_points,red_id,vred_id');
        if (empty($order_list)) {
            showMsg(Language::get('未找到需要支付的订单'),'index.php?app=userorder','html','error');
        }

        //重新计算在线支付金额
        $pay_amount_online = 0;
        $pay_amount_offline = 0;
        //订单总支付金额(不包含货到付款)
        $pay_amount = 0;
        foreach ($order_list as $key => $order_info) {
            //计算相关支付金额
            if ($order_info['payment_code'] != 'offline') {
                if ($order_info['order_state'] == ORDER_STATE_NEW) {
                    $pay_amount_online += sldPriceFormat(floatval($order_info['order_amount'])-floatval($order_info['pd_amount']));
                }
                $pay_amount += floatval($order_info['order_amount']);
            } else {
                $pay_amount_offline += floatval($order_info['order_amount']);
            }

            //显示支付方式与支付结果
            if ($order_info['payment_code'] == 'offline') {
                $order_list[$key]['payment_state'] = '货到付款';
            } else {
                $order_list[$key]['payment_state'] = '在线支付';
                if (floatval($order_info['pd_amount']) > 0) {
                    if ($order_info['order_state'] == ORDER_STATE_PAY) {
                        $order_list[$key]['payment_state'] .= " ( 已使用预存款完全支付，支付金额 ￥ {$order_info['pd_amount']} )";
                    } else {
                        $order_list[$key]['payment_state'] .= " ( 已使用预存款部分支付，支付金额 ￥ {$order_info['pd_amount']} )";
                    }
                }
            }
        }
        $returns['order_list']=$order_list;
        $pd_amount=0;
        //如果线上线下支付金额都为0，转到支付成功页
        if (empty($pay_amount_online) && empty($pay_amount_offline)) {
            //redirect('index.php?app=buy&mod=pay_ok&pay_sn='.$pay_sn.'&pay_amount='.sldPriceFormat($pay_amount));
        }

        //输入订单描述
        if (empty($pay_amount_online)) {
            $order_remind = '下单成功，我们会尽快为您发货，请保持电话畅通！';
        } elseif (empty($pay_amount_offline)) {
            $order_remind = '请您及时付款，以便订单尽快处理！';
        } else {
            $order_remind = '部分商品需要在线支付，请尽快付款！';
        }
        $returns['order_remind']=$order_remind;
        $returns['pay_amount_online']=sldPriceFormat($pay_amount_online);
        $returns['pd_amount']=sldPriceFormat($pd_amount);

        //显示支付接口列表
        if ($pay_amount_online > 0) {
            $model_payment = Model('payment');
            $condition = array();
            $payment_list = $model_payment->getPaymentOpenList($condition);
            if (!empty($payment_list)) {
                unset($payment_list['predeposit']);
                unset($payment_list['offline']);
            }
            if (empty($payment_list)) {
                showMsg(Language::get('暂未找到合适的支付方式'),'index.php?app=userorder','html','error');
            }
            $returns['payment_list']=$payment_list;
        }

        //标识 购买流程执行第几步
        $returns['buy_step']='step3';
        return json_encode($returns);
    }

    /**
     * 预存款充值下单时支付页面
     */
    public function pd_pay() {
        $pay_sn	= $_GET['pay_sn'];
        if (!preg_match('/^\d{18}$/',$pay_sn)){
            showMsg(Language::get('参数错误'),'index.php?app=chongzhi','html','error');
        }

        //查询支付单信息
        $model_order= Model('predeposit');
        $pd_info = $model_order->getPdRechargeInfo(array('pdr_sn'=>$pay_sn,'pdr_member_id'=>$_SESSION['member_id']));
        if(empty($pd_info)){
            showMsg(Language::get('参数错误'),'','html','error');
        }
        if (intval($pd_info['pdr_payment_state'])) {
            showMsg(Language::get('您的订单已经支付，请勿重复支付'),'index.php?app=chongzhi','html','error');
        }
        $returns['pdr_info']=$pd_info;

        //显示支付接口列表
        $model_payment = Model('payment');
        $condition = array();
        $condition['payment_code'] = array('not in',array('offline','predeposit'));
        $condition['payment_state'] = 1;
        $payment_list = $model_payment->getPaymentList($condition);
        $returns['payment_list']=$payment_list;

        //标识 购买流程执行第几步
        $returns['buy_step']='step3';
        return json_encode($returns,true);
        //Template::showpage('balancepay');
    }

    /**
     * 支付成功页面
     */
    public function pay_ok() {
        $pay_sn	= $_GET['pay_sn'];
        if (!preg_match('/^\d{18}$/',$pay_sn)){
            showMsg(Language::get('该订单不存在'),'index.php?app=userorder','html','error');
        }

        //查询支付单信息
        $model_order= Model('order');
        $pay_info = $model_order->getOrderPayInfo(array('pay_sn'=>$pay_sn,'buyer_id'=>$_SESSION['member_id']));
        if(empty($pay_info)){
            showMsg(Language::get('该订单不存在'),'index.php?app=userorder','html','error');
        }
        $returns['pay_info']=$pay_info;

        $returns['buy_step']='step4';
        return json_encode($returns,true);
        Template::showpage('paysuccess');
    }

    /**
     * 加载买家收货地址
     *
     */
    public function load_addr() {
        $model_addr = Model('address');
        //如果传入ID，先删除再查询
        if (!empty($_GET['id']) && intval($_GET['id']) > 0) {
            $model_addr->delAddress(array('address_id'=>intval($_GET['id']),'member_id'=>$_SESSION['member_id']));
        }
        $list = $model_addr->getAddressList(array('member_id'=>$_SESSION['member_id']));
        $returns['address_list']=$list;
        return json_encode($returns);
        //Template::showpage('buy_address.load','null_layout');
    }

    /**
     * 选择不同地区时，异步处理并返回每个店铺总运费以及本地区是否能使用货到付款
     * 如果店铺统一设置了满免运费规则，则运费模板无效
     * 如果店铺未设置满免规则，且使用运费模板，按运费模板计算，如果其中有商品使用相同的运费模板，则两种商品数量相加后再应用该运费模板计算（即作为一种商品算运费）
     * 如果未找到运费模板，按免运费处理
     * 如果没有使用运费模板，商品运费按快递价格计算，运费不随购买数量增加
     */
    public function change_addr() {
        $model_buy = Model('buy');

        $data = $model_buy->changeAddr($_POST['freight_hash'], $_POST['city_id'], $_POST['area_id'], $_SESSION['member_id']);
        if(!empty($data)) {
            exit(json_encode($data));
        } else {
            exit();
        }
    }

    /**
     * 添加新的收货地址
     *
     */
    public function add_addr(){
        $model_addr = Model('address');
        if (chksubmit()){
            //验证表单信息
            $obj_validate = new Validate();
            $obj_validate->validateparam = array(
                array("input"=>$_POST["true_name"],"require"=>"true","message"=>Language::get('请填写收货人姓名')),
                array("input"=>$_POST["area_id"],"require"=>"true","validator"=>"Number","message"=>Language::get('请选择所在地区')),
                array("input"=>$_POST["address"],"require"=>"true","message"=>Language::get('请填写收货人详细地址'))
            );
            $error = $obj_validate->validate();
            if ($error != ''){
                $error = strtoupper(CHARSET) == 'GBK' ? Language::getUTF8($error) : $error;
                exit(json_encode(array('state'=>false,'msg'=>$error)));
            }
            $data = array();
            $data['member_id'] = $_SESSION['member_id'];
            $data['true_name'] = $_POST['true_name'];
            $data['area_id'] = intval($_POST['area_id']);
            $data['city_id'] = intval($_POST['city_id']);
            $data['area_info'] = $_POST['area_info'];
            $data['address'] = $_POST['address'];
            $data['tel_phone'] = $_POST['tel_phone'];
            $data['mob_phone'] = $_POST['mob_phone'];
            //转码
            $data = strtoupper(CHARSET) == 'GBK' ? Language::getGBK($data) : $data;
            $insert_id = $model_addr->addAddress($data);
            if ($insert_id){
                exit(json_encode(array('state'=>true,'addr_id'=>$insert_id)));
            }else {
                exit(json_encode(array('state'=>false,'msg'=>Language::get('新地址添加失败','UTF-8'))));
            }
        } else {
            Template::showpage('buy_address.add','null_layout');
        }
    }

    /**
     * 加载买家发票列表，最多显示10条
     *
     */
    public function loadinvoice() {
        $model_buy = Model('buy');

        $condition = array();
        if ($model_buy->buyDecrypt($_GET['vat_hash'], $_SESSION['member_id']) == 'allow_vat') {
        } else {
            $returns['vat_deny']=true;
            $condition['inv_state'] = 1;
        }
        $condition['member_id'] = $_SESSION['member_id'];

        $model_inv = Model('invoice');
        //如果传入ID，先删除再查询
        if (intval($_GET['del_id']) > 0) {
            $model_inv->delInv(array('inv_id'=>intval($_GET['del_id']),'member_id'=>$_SESSION['member_id']));
        }
        $list = $model_inv->getInvList($condition,10);
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                if ($value['inv_state'] == 1) {
                    $list[$key]['content'] = Language::get('普通发票').' '.$value['inv_title'].' '.$value['inv_content'].' '.$value['inv_code'];
                } else {
                    $list[$key]['content'] = Language::get('增值税发票').' '.$value['inv_company'].' '.$value['inv_code'].' '.$value['inv_reg_addr'];
                }
            }
        }

        $invoice_content_list = Model('invoice')->invoice_content_list;

        foreach ($invoice_content_list as &$v){
            $v = Language::get($v);
        }
//        dd($invoice_content_list);die;

        $returns['inv_list']=$list;
        $returns['invoice_content_list']=$invoice_content_list;
        return json_encode($returns,true);
    }

    /**
     * 新增发票信息  怀疑没地方使用
     *
     */
    public function addinvoice(){
        $model_inv = Model('invoice');
        if (chksubmit()){
            //如果是增值税发票验证表单信息
            if ($_POST['invoice_type'] == 2) {
                if (empty($_POST['inv_company']) || empty($_POST['inv_code']) || empty($_POST['inv_reg_addr'])) {
                    exit(json_encode(array('state'=>false,'msg'=>Language::get('保存失败','UTF-8'))));
                }
            }
            $data = array();
            if ($_POST['invoice_type'] == 1) {
                $data['inv_state'] = 1;
                $data['inv_title'] = $_POST['inv_title_select'] == 'person' ? Language::get('个人') : $_POST['inv_title'];
                $data['inv_content'] = $_POST['inv_content'];
                $data['inv_code'] = $_POST['inv_title_shuihao'];//纳税人识别号（单位也需要要税号）
            } else {
                $data['inv_state'] = 2;
                $data['inv_company'] = $_POST['inv_company'];
                $data['inv_code'] = $_POST['inv_code'];
                $data['inv_reg_addr'] = $_POST['inv_reg_addr'];
                $data['inv_reg_phone'] = $_POST['inv_reg_phone'];
                $data['inv_reg_bname'] = $_POST['inv_reg_bname'];
                $data['inv_reg_baccount'] = $_POST['inv_reg_baccount'];
                $data['inv_rec_name'] = $_POST['inv_rec_name'];
                $data['inv_rec_mobphone'] = $_POST['inv_rec_mobphone'];
                $data['inv_rec_province'] = $_POST['area_info'];
                $data['inv_goto_addr'] = $_POST['inv_goto_addr'];
            }
            $data['member_id'] = $_SESSION['member_id'];
            //转码
            $data = strtoupper(CHARSET) == 'GBK' ? Language::getGBK($data) : $data;
            $insert_id = $model_inv->addInv($data);
            if ($insert_id) {
                exit(json_encode(array('state'=>'success','id'=>$insert_id)));
            } else {
                exit(json_encode(array('state'=>'fail','msg'=>Language::get('保存失败','UTF-8'))));
            }
        } else {
            Template::showpage('buy_address.add','null_layout');
        }
    }


    /**
     * AJAX验证登录密码
     */
    public function check_pay_pwd(){
        if (empty($_GET['password'])) exit('0');
        $buyer_info	= Model('member')->infoMember(array('member_id' => $_SESSION['member_id']));
        echo $buyer_info['member_passwd'] === md5($_GET['password']) ? '1' : '0';
    }
    /*
     * 再来一单控制器
     */
    public function buy_again()
    {
        $order_id = intval($_GET['order_id']);
        $order_model = Model('order');
        try {
            if ($order_id < 1) {
                throw new Exception(Language::get('下单错误'));
            }
            //取可下单的列表
            $goods_list = $order_model->getOrderAgainGoodsList($order_id);
            if(empty($goods_list)){
                throw new Exception(Language::get('商品不存在'));
            }
            $this->confirm_again($goods_list);
        } catch (Exception $e) {
            showMsg($e->getMessage());
        }
    }
    /*
   * 再来一单confirm页
   */
    public function confirm_again($goods_list) {
        $model_buy = Model('buy');

        $is_supplier = $_POST['is_supplier'] ? intval($_POST['is_supplier']) : 0;

        $extends_data = array();

        $extends_data['from'] = 'pc';

        $result = $model_buy->buyStep1($goods_list, 1, '', $_SESSION['member_id'], $_SESSION['vid'],$is_supplier,$extends_data,1);

        if(!empty($result['error'])) {
            showMsg($result['error'], '', 'html', 'error');
        }

        $returns['is_supplier']= $is_supplier;
        $returns['ifcart']= 1;
        //商品金额计算(分别对每个商品/优惠套装小计、每个店铺小计)
        //平台优惠券

        if(C('red_isuse')) {
            $red=con_addons('red', $result + ['member' => array('member_id'=>$_SESSION['member_id'])],'confirm','buy');
            $returns['red']= $red['red'];
            $returns['vred']= $red['vred'];
        }
        $returns['store_cart_list']= $result['store_cart_list'];
        $returns['store_goods_total']= $result['store_goods_total'];
        if ($is_supplier) {
            # code...
        }else{
            //取得店铺优惠 - 满即送(赠品列表，店铺满送规则列表)
            $returns['store_premiums_list']= $result['store_premiums_list'];
            $returns['store_mansong_rule_list']= $result['store_mansong_rule_list'];
            //返回店铺可用的优惠券
            $returns['store_voucher_list']= $result['store_voucher_list'];

            if((Config('sld_cashersystem') && Config('cashersystem_isuse')) || (Config('sld_ldjsystem') && Config('ldj_isuse'))){
                //输出自提点信息
                $returns['dian_list']= $result['dian_list'];
            }
        }
        //返回需要计算运费的店铺ID数组 和 不需要计算运费(满免运费活动的)店铺ID及描述
        $returns['need_calc_sid_list']= $result['need_calc_sid_list'];
        $returns['cancel_calc_sid_list']= $result['cancel_calc_sid_list'];

        //将商品ID、数量、运费模板、运费序列化，加密，输出到模板，选择地区AJAX计算运费时作为参数使用
        $returns['freight_hash']= $result['freight_list'];
        //输出用户默认收货地址
        $returns['address_info']= $result['address_info'];
        //输出有货到付款时，在线支付和货到付款及每种支付下商品数量和详细列表
        $returns['pay_goods_list']= $result['pay_goods_list'];
        $returns['ifshow_offpay']= $result['ifshow_offpay'];
        $returns['deny_edit_payment']= $result['deny_edit_payment'];
        //不提供增值税发票时抛出true(模板使用)
        $returns['vat_deny']= $result['vat_deny'];
        //增值税发票哈希值(php验证使用)
        $returns['vat_hash']= $result['vat_hash'];
        //输出默认使用的发票信息
        $returns['inv_info']= $result['inv_info'];
        //显示使用预存款支付及会员预存款
        $returns['available_pd_amount']= $result['available_predeposit'];
        $returns['member_points']= $result['member_points'];
        $returns['points_max_use']= $result['points_max_use'];
        $returns['points_purpose_rebate']= $result['points_purpose_rebate'];
        $returns['gid']= $result['gid'];

        //标识 购买流程执行第几步
        $returns['buy_step']='step2';
        return json_encode($returns);
    }
    /**
     * 再来一单保存订单入库，产生订单号，开始选择支付方式
     *
     */
    public function submitorder_again() {
        $model_buy = Model('buy');
        //处理优惠券
        $_POST['red'] = $_POST['red_id'];
        $_POST['order_from'] = 1;

        $_POST['vred'] = array_filter($_POST['vred']);

        $extends_data = array();

        $extends_data['from'] = 'pc';

        $result = $model_buy->buyStep2($_POST, $_SESSION['member_id'], $_SESSION['member_name'], $_SESSION['member_email'],$extends_data,1);

        if(!empty($result['error'])) {
            showMsg($result['error'], '', 'html', 'error');
        }

        //转向到商城支付页面
        $pay_url = 'index.php?app=buy&mod=pay&pay_sn='.$result['pay_sn'];
        redirect($pay_url);
    }
}