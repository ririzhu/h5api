<?php
/**
 * 预存款管理
 *
 */
defined('DYMall') or exit('Access Invalid!');
class ssys_cashCtl extends SystemCtl{
	
	public function __construct(){
		parent::__construct();
        Language::read('ssys_yj','spreader');
	}

    /**
     * 提现列表
     */
    public function CashManageList(){
        $condition = array();
        $search = array();
        //分页检索条件(如果传值 按照传的值 否则默认10页)
        $pageSize = intval($_GET['pageSize'] > 0 ? $_GET['pageSize'] : 10);
        $_GET['pn'] = intval($_GET['currentPage'] > 0 ? $_GET['currentPage'] : 1);
        $search['stime'] = $_GET['stime'];
        $search['etime'] = $_GET['etime'];
        $if_start_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/',$_GET['stime']);
        $if_end_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/',$_GET['etime']);
        $start_unixtime = $if_start_date ? strtotime($_GET['stime']) : null;
        $end_unixtime = $if_end_date ? strtotime($_GET['etime']): null;
        if ($start_unixtime || $end_unixtime) {
            $condition['pdc_add_time'] = array('BETWEEN',array($start_unixtime,$end_unixtime));
        }
        if (!empty($_GET['mname'])){
            $search['mname'] = $_GET['mname'];
            $condition['pdc_member_name'] = $_GET['mname'];
        }
        if (!empty($_GET['pdc_bank_user'])){
            $search['pdc_bank_user'] = $_GET['pdc_bank_user'];
            $condition['pdc_bank_user'] = $_GET['pdc_bank_user'];
        }
        if ($_GET['paystate_search'] != ''){
            $search['paystate_search'] = $_GET['paystate_search'];
            $condition['pdc_payment_state'] = $_GET['paystate_search'];
        }

        $ssys_yj = M('ssys_yj','spreader');
        $cash_list = $ssys_yj->getPdCashList($condition,$pageSize,'*','pdc_payment_state asc,pdc_id asc');
        foreach ($cash_list as $key => $val){
            $cash_list[$key]['pdc_add_time'] = $val['pdc_add_time']==0?'':date('Y-m-d H:i:s',$val['pdc_add_time']);
            $cash_list[$key]['pdc_payment_time'] = $val['pdc_payment_time']==0?'':date('Y-m-d H:i:s',$val['pdc_payment_time']);
        }
        echo json_encode(array('list' => $cash_list, 'pagination' => array('current' => $_GET['pn'], 'pageSize' => $pageSize, 'total' => intval($ssys_yj->gettotalnum())),'searchlist'=>$search));
    }

    /**
     * 删除未审核状态下的提现记录
     */
    public function delCash(){
        $pdc_id = intval($_GET["id"]);
        if ($pdc_id <= 0){
            echo json_encode(array('state'=>255,'msg'=>Language::get('参数错误')));die;
        }
        $ssys_yj = M('ssys_yj','spreader');
        $condition = array();
        $condition['pdc_id'] = $pdc_id;
        $condition['pdc_payment_state'] = 0;
        $info = $ssys_yj->getPdCashInfo($condition);
        if (!$info) {
            echo json_encode(array('state'=>255,'msg'=>Language::get('参数错误')));die;
        }
        try {
            $update = array();
            $admininfo = $this->getAdminInfo();
            $update['pdc_payment_state'] = -1;
            $update['pdc_payment_admin'] = $admininfo['name'];
            $update['pdc_payment_time'] = TIMESTAMP;
            $log_msg = L('admin_predeposit_cash_edit_state').',拒绝提现,'.L('admin_predeposit_cs_sn').':'.$info['pdc_sn'];
            $result = $ssys_yj->editPdCash($update,$condition);
            if (!$result) {
                echo json_encode(array('state'=>255,'msg'=>Language::get('提现信息删除失败')));die;
            }
            //退还冻结的预存款
            $model_member = M('ssys_member','spreader');
            $member_info = $model_member->infoMember(array('member_id'=>$info['pdc_member_id']));
            //扣除冻结的预存款
            $admininfo = $this->getAdminInfo();
            $data = array();
            $data['member_id'] = $member_info['member_id'];
            $data['member_name'] = $member_info['member_name'];
            $data['amount'] = $info['pdc_amount'];
            $data['order_sn'] = $info['pdc_sn'];
            $data['admin_name'] = $admininfo['name'];
            $ssys_yj->changePd('cash_del',$data);
            $ssys_yj->commit();
            $this->log($log_msg,1);
            echo json_encode(array('state'=>200,'msg'=>Language::get('提现信息删除成功')));die;

        } catch (Exception $e) {
            $ssys_yj->commit();
            $this->log($log_msg,0);
            echo json_encode(array('state'=>255,'msg'=>$e->getMessage()));die;
        }
    }
    /**
     * 更改提现为支付状态
     */
    public function changeCashState(){
        $id = intval($_GET['id']);
        if ($id <= 0){
            echo json_encode(array('state'=>255,'msg'=>Language::get('参数错误')));die;
        }
        $ssys_yj = M('ssys_yj','spreader');
        $condition = array();
        $condition['pdc_id'] = $id;
        $condition['pdc_payment_state'] = 0;
        $info = $ssys_yj->getPdCashInfo($condition);
        if (!is_array($info) || count($info)<0 || empty($info)){
            echo json_encode(array('state'=>255,'msg'=>Language::get('记录信息错误')));die;
        }

        //查询用户信息
        $ssys_member = M('ssys_member','spreader');
        $member_info = $ssys_member->infoMember(array('member_id'=>$info['pdc_member_id']));
        if (!$member_info['shop_member_id']) {
            echo json_encode(array('state'=>255,'msg'=>'该推手未绑定商城账户，无法进行提现。'));die;
        }

        $update = array();
        $admininfo = $this->getAdminInfo();
        $update['pdc_payment_state'] = 1;
        $update['pdc_payment_admin'] = $admininfo['name'];
        $update['pdc_payment_time'] = TIMESTAMP;
        $log_msg = L('admin_predeposit_cash_edit_state').','.L('admin_predeposit_cs_sn').':'.$info['pdc_sn'];

        try {
            $ssys_yj->beginTransaction();
            $result = $ssys_yj->editPdCash($update,$condition);
            if (!$result) {
                echo json_encode(array('state'=>255,'msg'=>Language::get('admin_predeposit_cash_edit_fail')));die;
            }
            // //扣除冻结的预存款
            $data = array();
            $data['member_id'] = $member_info['member_id'];
            $data['member_name'] = $member_info['member_name'];
            $data['amount'] = $info['pdc_amount'];
            $data['order_sn'] = $info['pdc_sn'];
            $data['admin_name'] = $admininfo['name'];
            $ssys_yj->changePd('cash_pay',$data);
            $this->add_shop_member_predeposit_amount_by_api($member_info['shop_member_id'],$info['pdc_amount'],$info['pdc_sn']);
            $ssys_yj->commit();
            $this->log($log_msg,1);
            echo json_encode(array('state'=>200,'msg'=>Language::get('admin_predeposit_cash_edit_success')));die;
        } catch (Exception $e) {
            $ssys_yj->rollback();
            $this->log($log_msg,0);
            echo json_encode(array('state'=>255,'msg'=>$e->getMessage()));die;
        }
    }

    // 向 推手关联的 商城用户 增加可用余额
    public function add_shop_member_predeposit_amount_by_api($member_id,$amount,$pay_sn){
        $member_info = Model('member')->getMemberInfo(array('member_id'=>$member_id),'member_name');

        $model_pdr = Model('predeposit');
        $data = array();
        $data['member_id'] = $member_id;
        $data['member_name'] = $member_info['member_name'];
        $data['amount'] = $amount;
        $data['order_sn'] = $pay_sn;
        $data['admin_name'] = 'system';
        $model_pdr->changePd('spreader_cash',$data);
    }

}
