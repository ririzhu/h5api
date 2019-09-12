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
}