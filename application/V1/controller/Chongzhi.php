<?php
namespace app\v1\controller;

use app\v1\model\Predeposit;

class Chongzhi extends  Base
{
    /**
     * 预存款变更日志
     */
    public function chongzhilist(){
        if(!input("member_id")){
            $data['message'] = lang("缺少参数");
            $data['error_code'] = 10016;
            return json_encode($data,true);
        }
        $page = input("page",0);
        $model_pd = new Predeposit();
        $condition = array();
        $condition['lg_member_id'] = input("member_id");
        $list = $model_pd->getPdLogList($condition,"*",'lg_id desc',10,$page);
        //切换充值记录语言
        /*if(!empty($list) && LANG_TYPE !='zh_cn'){
            import('function.rebuild');
            change_pd_log_desc($list);
        }*/
        //查询用户信息
        $model_member = new \app\v1\model\User();
        $member_info = $model_member->infoMember(array('member_id'=>input("member_id")));
        $data['list'] = $list;
        return json_encode($data,true);
    }
    /**
     * 切换充值记录语言
     */
    function change_pd_log_desc(&$list){
        foreach ($list as $k=>$v) {
            //语言模板
            $tem = lang($v['lg_type']);
            if($tem == false) continue;
            //判断类型
            switch ($v['lg_type']) {
                case 'order_pay':
                    $tpl = '下单，支付预存款，订单号: ';
                    break;
                case 'order_freeze':
                    $tpl = '下单，冻结预存款，订单号: ';
                    break;
                case 'order_cancel':
                    $tpl = '取消订单，订单号: ';
                    break;
                case 'huodong_cancel':
                    $tpl = '下单，支付预存款，订单号: ';
                    break;
                case 'order_comb_pay':
                    $tpl = '下单，支付被冻结的预存款，订单号: ';
                    break;
                case 'recharge':
                    $tpl = '充值，充值单号: ';
                    break;
                case 'refund':
                    $tpl = '确认退款，订单号: %s';
                    break;
                case 'cash_apply':
                    $tpl = '申请提现，冻结预存款，提现单号: ';
                    break;
                case 'cash_pay':
                    $tpl = '提现成功，提现单号: ';
                    break;
                case 'cash_del':
                    $tpl = '取消提现申请，解冻预存款，提现单号: ';
                    break;
                case 'cash_rebate':
                    $tpl = '下级会员消费现金返利，消费订单号: ';
                    break;
                case 'return_leader':
                    $tpl = '拼团返利冻结: ';
                    break;
                case 'return_leader2':
                    $tpl = '拼团返利解冻: ';
                    break;
                case 'return_leader3':
                    $tpl = '退款删除冻结资金: ';
                    break;
                case 'spreader_cash':
                    $tpl = '推手提现成功: ';
                    break;
                default:
                    continue;
            }
            //是否自定义
            $pos = strpos($v['lg_desc'], $tpl);
            if($pos === false) continue;
            //取出单号
            $order = substr($v['lg_desc'],$pos + strlen($tpl));
            //重组数据
            $list[$k]['lg_desc'] = sprintf($tem,$order);
        }
    }
    /**
     *提现申请
     */
    public function pd_cash_add(){
        if (chksubmit()){
            $obj_validate = new Validate();
            $pdc_amount = abs(floatval($_POST['pdc_amount']));

            $validate_arr[] = array("input"=>$pdc_amount, "require"=>"true",'validator'=>'Compare','operator'=>'>=',"to"=>'0.01',"message"=>lang('提现金额为大于或者等于0.01的数字'));
            $validate_arr[] = array("input"=>$_POST["pdc_bank_name"], "require"=>"true","message"=>lang('请填写收款银行'));
            $validate_arr[] = array("input"=>$_POST["pdc_bank_no"], "require"=>"true","message"=>lang('请填写收款账号'));
            $validate_arr[] = array("input"=>$_POST["pdc_bank_user"], "require"=>"true","message"=>lang('请填写收款人姓名'));
            $obj_validate -> validateparam = $validate_arr;
            $error = $obj_validate->validate();
            if ($error != ''){
                showDialog($error,'','error');
            }
            $model_pd = new predeposit();
            //验证金额是否足够
            $model_member = new \app\v1\model\User();
            $member_info = $model_member->infoMember(array('member_id'=>input("member_id")));

            // 校验是否满足 系统设置的最低提现金额
            $cash_min_money_num = floatval(Config('cash_min_money_num')) ? floatval(Config('cash_min_money_num')) :  0;
            if ($cash_min_money_num && $cash_min_money_num > $pdc_amount){
                showDialog(lang('提现金额为大于或者等于').$cash_min_money_num.lang('的数字'),'','error');
            }

            if (floatval($member_info['available_predeposit']) < $pdc_amount){
                showDialog(lang('预存款金额不足'),'index.php?app=chongzhi&mod=tixianlist','error');
            }
            try {
                $model_pd->beginTransaction();
                $pdc_sn = $model_pd->makeSn();
                $data = array();
                $data['pdc_sn'] = $pdc_sn;
                $data['pdc_member_id'] = $_SESSION['member_id'];
                $data['pdc_member_name'] = $_SESSION['member_name'];
                $data['pdc_amount'] = $pdc_amount;
                $data['pdc_bank_name'] = $pdc_bank_name = $_POST['pdc_bank_name'];
                $data['pdc_bank_no'] = $_POST['pdc_bank_no'];
                $data['pdc_bank_user'] = $_POST['pdc_bank_user'];
                $data['pdc_add_time'] = TIMESTAMP;
                $data['pdc_payment_state'] = 0;
                $insert = $model_pd->addPdCash($data);
                if (!$insert) {
                    throw new Exception(lang('提现信息添加失败'));
                }
                //冻结可用预存款
                $data = array();
                $data['member_id'] = $member_info['member_id'];
                $data['member_name'] = $member_info['member_name'];
                $data['amount'] = $pdc_amount;
                $data['order_sn'] = $pdc_sn;
                $model_pd->changePd('cash_apply',$data);
                $model_pd->commit();

                // 发送买家消息
                $param = array();
                $param['code'] = 'cash_apply_notice';
                $param['member_id'] = $data['member_id'];
                $data_msg['order_sn'] = $data['order_sn'];
                $data_msg['order_url'] = urlShop('chongzhi','tixianlist');

                $data_msg['first'] =  lang('您好，提现申请已经收到');
                $data_msg['keyword1'] =  sldPriceFormat($data['amount']);
                $data_msg['keyword2'] =  date('Y年m月d日 H时i分',time());
                $data_msg['keyword3'] =  $pdc_bank_name;
                $data_msg['remark'] =  lang('点击查看详情');
                $data_msg['url'] =  WAP_SITE_URL.'/cwap_my_yuer.html';

                $param['param'] = $data_msg;
                $param['link']=$data_msg['order_url'];
                $param['system_type']=6;
                QueueClient::push('sendMemberMsg', $param);

                showDialog(lang('您的提现申请已成功提交，请等待系统处理'),'index.php?app=chongzhi&mod=tixianlist','succ','CUR_DIALOG.close()');
            } catch (Exception $e) {
                $model_pd->rollback();
                showDialog($e->getMessage(),'index.php?app=chongzhi&mod=tixianlist','error');
            }
        }else {
            //查询会员信息
            $member_model = Model('member');
            $member_info = $member_model->infoMember(array('member_id'=>$_SESSION['member_id']),'member_id,available_predeposit');
            Template::output('member_info',$member_info);
            self::profile_menu('cashadd','cashadd');
            Template::output('menu_sign','predepositcash');
            Template::output('menu_sign_url','index.php?app=chongzhi&mod=pd_cash_add');
            Template::output('menu_sign1','predeposit_cashadd');
            Template::showpage('member_pd_cash.add');
        }
    }
}