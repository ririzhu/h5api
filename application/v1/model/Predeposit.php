<?php
/**
 * 预存款
 */
namespace app\v1\model;

use Exception;
use QueueClient;
use think\Model;
use think\Db;

class Predeposit extends Model
{
    /**
     * 生成充值编号
     * @return string
     */
    public function makeSn() {
        return mt_rand(10,99)
            . sprintf('%010d',time() - 946656000)
            . sprintf('%03d', (float) microtime() * 1000)
            . sprintf('%03d', (int) $_SESSION['member_id'] % 1000);
    }

    /**
     * 取得充值列表
     * @param unknown $condition
     * @param string $pagesize
     * @param string $fields
     * @param string $order
     */
    public function getPdRechargeList($condition = array(), $pagesize = '', $fields = '*', $order = '', $limit = '') {
        return $this->table('pd_recharge')->where($condition)->field($fields)->order($order)->limit($limit)->page($pagesize)->select();
    }

    /**
     * 添加充值记录
     * @param array $data
     */
    public function addPdRecharge($data) {
        return $this->table('pd_recharge')->insert($data);
    }

    /**
     * 编辑
     * @param unknown $data
     * @param unknown $condition
     */
    public function editPdRecharge($data,$condition = array()) {
        return $this->table('pd_recharge')->where($condition)->update($data);
    }

    /**
     * 取得单条充值信息
     * @param unknown $condition
     * @param string $fields
     */
    public function getPdRechargeInfo($condition = array(), $fields = '*') {
        return $this->table('pd_recharge')->where($condition)->field($fields)->find();
    }

    /**
     * 取充值信息总数
     * @param unknown $condition
     */
    public function getPdRechargeCount($condition = array()) {
        return $this->table('pd_recharge')->where($condition)->count();
    }

    /**
     * 获取充值成功总额
     * @param unknown $condition
     */
    public function getPdRechargeAmount($condition = array()) {
        return $this->table('pd_recharge')->field('sum(pdr_amount) as num')->where($condition)->select();
    }

    /**
     * 取提现单信息总数
     * @param unknown $condition
     */
    public function getPdCashCount($condition = array()) {
        return $this->table('pd_cash')->where($condition)->count();
    }

    /**
     * 取日志总数
     * @param unknown $condition
     */
    public function getPdLogCount($condition = array()) {
        return $this->table('pd_log')->where($condition)->count();
    }

    /**
     * 取得预存款变更日志列表
     * @param array $condition
     * @param string $fields
     * @param string $order
     * @param string $limit
     * @param string $pagesize
     */
    public function getPdLogList($condition, $fields = '*', $order = '', $limit = 10, $pagesize = '') {
        return DB::name('pd_log')->where($condition)->field($fields)->order($order)->limit($limit)->page($pagesize)->select();
    }

    /**
     * 变更预存款
     * @param unknown $change_type
     * @param unknown $data
     * @throws Exception
     * @return unknown
     */
    public function changePd($change_type,$data = array()) {
        $data_log = array();
        $data_pd = array();
        $data_msg = array();
        $data_log['lg_member_id'] = $data['member_id'];
        $data_log['lg_member_name'] = $data['member_name'];
        $data_log['lg_add_time'] = TIMESTAMP;
        $data_log['lg_type'] = $change_type;

        $data_msg['time'] = date('Y-m-d H:i:s');
        $data_msg['pd_url'] = urlShop('chongzhi', 'chongzhilist');
        switch ($change_type){
            case 'order_pay':
                $data_log['lg_av_amount'] = -$data['amount'];
                $data_log['lg_desc'] = $data['lg_desc']?:'下单，支付预存款，订单号: '.$data['order_sn'];
                $data_pd['available_predeposit'] = array('exp','available_predeposit-'.$data['amount']);

                $data_msg['av_amount'] = -$data['amount'];
                $data_msg['freeze_amount'] = 0;
                $data_msg['desc'] = $data_log['lg_desc'];
                $data_msg['change_amount'] = $data['amount'];
                break;
            case 'order_freeze':
                $data_log['lg_av_amount'] = -$data['amount'];
                $data_log['lg_freeze_amount'] = $data['amount'];
                $data_log['lg_desc'] = '下单，冻结预存款，订单号: '.$data['order_sn'];
                $data_pd['freeze_predeposit'] = array('exp','freeze_predeposit+'.$data['amount']);
                $data_pd['available_predeposit'] = array('exp','available_predeposit-'.$data['amount']);

                $data_msg['av_amount'] = -$data['amount'];
                $data_msg['freeze_amount'] = $data['amount'];
                $data_msg['desc'] = $data_log['lg_desc'];
                $data_msg['change_amount'] = $data['amount'];
                break;
            case 'order_cancel':
                $data_log['lg_av_amount'] = $data['amount'];
                $data_log['lg_freeze_amount'] = -$data['amount'];
                $data_log['lg_desc'] = $data['lg_desc']?:'取消订单，订单号: '.$data['order_sn'];
                $data_pd['freeze_predeposit'] = array('exp','freeze_predeposit-'.$data['amount']);
                $data_pd['available_predeposit'] = array('exp','available_predeposit+'.$data['amount']);

                $data_msg['av_amount'] = $data['amount'];
                $data_msg['freeze_amount'] = -$data['amount'];
                $data_msg['desc'] = $data_log['lg_desc'];
                $data_msg['change_amount'] = $data['amount'];
                break;
            case 'huodong_cancel':
                $data_log['lg_av_amount'] = $data['amount'];
                $data_log['lg_freeze_amount'] = -$data['amount'];
                $data_log['lg_desc'] = $data['lg_desc']?:'取消订单，订单号: '.$data['order_sn'];
                $data_pd['available_predeposit'] = array('exp','available_predeposit+'.$data['amount']);
                $data_msg['av_amount'] = $data['amount'];
                $data_msg['freeze_amount'] = -$data['amount'];
                $data_msg['desc'] = $data_log['lg_desc'];
                $data_msg['change_amount'] = $data['amount'];
                break;
            case 'order_comb_pay':
                $data_log['lg_freeze_amount'] = -$data['amount'];
                $data_log['lg_desc'] = '下单，支付被冻结的预存款，订单号: '.$data['order_sn'];
                $data_pd['freeze_predeposit'] = array('exp','freeze_predeposit-'.$data['amount']);

                $data_msg['av_amount'] = 0;
                $data_msg['freeze_amount'] = $data['amount'];
                $data_msg['desc'] = $data_log['lg_desc'];
                $data_msg['change_amount'] = $data['amount'];
                break;
            case 'recharge':
                $data_log['lg_av_amount'] = $data['amount'];
                $data_log['lg_desc'] = '充值，充值单号: '.$data['pdr_sn'];
                $data_log['lg_admin_name'] = $data['admin_name'];
                $data_pd['available_predeposit'] = array('exp','available_predeposit+'.$data['amount']);

                $data_msg['av_amount'] = $data['amount'];
                $data_msg['freeze_amount'] = 0;
                $data_msg['desc'] = $data_log['lg_desc'];
                $data_msg['change_amount'] = $data['amount'];
                break;
            case 'refund':
                $data_log['lg_av_amount'] = $data['amount'];
                $data_log['lg_desc'] = '确认退款，订单号: '.$data['order_sn'];
                $data_pd['available_predeposit'] = array('exp','available_predeposit+'.$data['amount']);

                $data_msg['av_amount'] = $data['amount'];
                $data_msg['freeze_amount'] = 0;
                $data_msg['desc'] = $data_log['lg_desc'];
                $data_msg['change_amount'] = $data['amount'];
                break;
            case 'cash_apply':
                $data_log['lg_av_amount'] = -$data['amount'];
                $data_log['lg_freeze_amount'] = $data['amount'];
                $data_log['lg_desc'] = '申请提现，冻结预存款，提现单号: '.$data['order_sn'];
                $data_pd['available_predeposit'] = array('exp','available_predeposit-'.$data['amount']);
                $data_pd['freeze_predeposit'] = array('exp','freeze_predeposit+'.$data['amount']);

                $data_msg['av_amount'] = -$data['amount'];
                $data_msg['freeze_amount'] = $data['amount'];
                $data_msg['desc'] = $data_log['lg_desc'];
                $data_msg['change_amount'] = $data['amount'];
                break;
            case 'cash_pay':
                $data_log['lg_freeze_amount'] = -$data['amount'];
                $data_log['lg_desc'] = '提现成功，提现单号: '.$data['order_sn'];
                $data_log['lg_admin_name'] = $data['admin_name'];
                $data_pd['freeze_predeposit'] = array('exp','freeze_predeposit-'.$data['amount']);

                $data_msg['av_amount'] = 0;
                $data_msg['freeze_amount'] = -$data['amount'];
                $data_msg['desc'] = $data_log['lg_desc'];
                $data_msg['change_amount'] = $data['amount'];
                break;
            case 'cash_del':
                $data_log['lg_av_amount'] = $data['amount'];
                $data_log['lg_freeze_amount'] = -$data['amount'];
                $data_log['lg_desc'] = '取消提现申请，解冻预存款，提现单号: '.$data['order_sn'];
                $data_log['lg_admin_name'] = $data['admin_name'];
                $data_pd['available_predeposit'] = array('exp','available_predeposit+'.$data['amount']);
                $data_pd['freeze_predeposit'] = array('exp','freeze_predeposit-'.$data['amount']);

                $data_msg['av_amount'] = $data['amount'];
                $data_msg['freeze_amount'] = -$data['amount'];
                $data_msg['desc'] = $data_log['lg_desc'];
                $data_msg['change_amount'] = $data['amount'];
                break;
            case 'cash_rebate':
                $data_log['lg_av_amount'] = $data['amount'];
                $data_log['lg_desc'] = '下级会员消费现金返利，消费订单号: '.$data['order_sn'];
                $data_pd['available_predeposit'] = array('exp','available_predeposit+'.$data['amount']);

                $data_msg['av_amount'] = $data['amount'];
                $data_msg['freeze_amount'] = 0;
                $data_msg['desc'] = $data_log['lg_desc'];
                $data_msg['change_amount'] = $data['amount'];
                break;
            case 'return_leader':
                $data_log['lg_freeze_amount'] = $data['amount'];
                $data_log['lg_desc'] = '拼团返利冻结: '.$data['order_sn'];
                $data_pd['freeze_predeposit'] = array('exp','freeze_predeposit+'.$data['amount']);

                $data_msg['freeze_amount'] = $data['amount'];
                $data_msg['desc'] = $data_log['lg_desc'];
                break;
            case 'return_leader2':
                $data_log['lg_av_amount'] = $data['amount'];
                $data_log['lg_freeze_amount'] = -$data['amount'];
                $data_log['lg_desc'] = '拼团返利解冻: '.$data['order_sn'];
                $data_log['lg_admin_name'] = $data['admin_name'];
                $data_pd['available_predeposit'] = array('exp','available_predeposit+'.$data['amount']);
                $data_pd['freeze_predeposit'] = array('exp','freeze_predeposit-'.$data['amount']);

                $data_msg['av_amount'] = $data['amount'];
                $data_msg['freeze_amount'] = -$data['amount'];
                $data_msg['desc'] = $data_log['lg_desc'];
                break;
            case 'return_leader3':
                $data_log['lg_freeze_amount'] = -$data['amount'];
                $data_log['lg_desc'] = '退款删除冻结资金: '.$data['order_sn'];
                $data_pd['freeze_predeposit'] = array('exp','freeze_predeposit-'.$data['amount']);

                $data_msg['freeze_amount'] = -$data['amount'];
                $data_msg['desc'] = $data_log['lg_desc'];
                break;
            case 'spreader_cash':
                // 推手系统提现
                $data_log['lg_av_amount'] = $data['amount'];
                $data_log['lg_desc'] = '推手提现成功: '.$data['order_sn'];
                $data_pd['available_predeposit'] = array('exp','available_predeposit+'.$data['amount']);

                $data_msg['av_amount'] = $data['amount'];
                $data_msg['freeze_amount'] = 0;
                $data_msg['desc'] = $data_log['lg_desc'];
                $data_msg['change_amount'] = $data['amount'];
                break;
            default:
                throw new Exception('参数错误');
                break;
        }
        $update = $this->table('member')->where(array('member_id'=>$data['member_id']))->update($data_pd);
        if (!$update) {
            throw new Exception('操作失败');
        }
        $insert = $this->table('pd_log')->insert($data_log);
        if (!$insert) {
            throw new Exception('操作失败');
        }
        $now_member_info = $this->table('member')->where(array('member_id'=>$data['member_id']))->field('available_predeposit')->find();
        // 支付成功发送买家消息
        $param = array();
        $param['code'] = 'predeposit_change';
        $param['member_id'] = $data['member_id'];
        $data_msg['av_amount'] = sldPriceFormat($data_msg['av_amount']);
        $data_msg['freeze_amount'] = sldPriceFormat($data_msg['freeze_amount']);
        $data_msg['first'] =  '您好，您有新的资金变动提醒';
        $data_msg['keyword1'] =  date('Y年m月d日 H时i分',time());
        $data_msg['keyword2'] =  sldPriceFormat($data_msg['change_amount']);
        $data_msg['keyword3'] =  sldPriceFormat($now_member_info['available_predeposit']);
        $data_msg['remark'] =  '点击查看详情';
        $data_msg['url'] =  WAP_SITE_URL.'/cwap_user_msg_info.html?t=preposit';

        $param['param'] = $data_msg;
        $param['link']=$data_msg['pd_url'];
        $param['system_type']=3;
        QueueClient::push('addConsume', array('member_id'=>$data['member_id'],'member_name'=>$data['member_name'],
            'consume_amount'=>$data['amount'],'consume_time'=>time(),'consume_remark'=>$data_log['lg_desc']));
        QueueClient::push('sendMemberMsg', $param);
        return $insert;
    }

    /**
     * 删除充值记录
     * @param unknown $condition
     */
    public function delPdRecharge($condition) {
        return $this->table('pd_recharge')->where($condition)->delete();
    }

    /**
     * 取得提现列表
     * @param array $condition
     * @param string $fields
     * @param string $order
     */
    public function getPdCashList($condition, $fields = '*', $order = '') {
        return DB::name('pd_cash')->where($condition)->field($fields)->order($order)->select();
    }

    /**
     * 添加提现记录
     * @param array $data
     */
    public function addPdCash($data) {
        return $this->table('pd_cash')->insert($data);
    }

    /**
     * 编辑提现记录
     * @param unknown $data
     * @param unknown $condition
     */
    public function editPdCash($data,$condition = array()) {
        return $this->table('pd_cash')->where($condition)->update($data);
    }

    /**
     * 取得单条提现信息
     * @param unknown $condition
     * @param string $fields
     */
    public function getPdCashInfo($condition = array(), $fields = '*') {
        return $this->table('pd_cash')->where($condition)->field($fields)->find();
    }

    /**
     * 删除提现记录
     * @param unknown $condition
     */
    public function delPdCash($condition) {
        return $this->table('pd_cash')->where($condition)->delete();
    }
    //充值回调对数据库的操作
    public function changePdamount($condition,$update){
        return $this->table('pd_recharge')->where($condition)->update($update);
    }
    //判断返回值类型
    public function checkPdr($condition,$update){
        return $this->table('pd_recharge')->where($condition)->find();
    }
    //插入预存款变更日志表
    public function changeAviableAmount($data_log){
        return $this->table('pd_log')->insert($data_log);
    }
    /**
     * 添加提现账号
     * @param array $data
     */
    public function addPdCashAccount($data) {
        return $this->table('pd_cash_account')->insert($data);
    }

    /**
     * 取得提现账号列表
     * @param unknown $condition
     * @param string $pagesize
     * @param string $fields
     * @param string $order
     */
    public function getPdCashAccountList($condition = array(), $pagesize = '', $fields = '*', $order = '', $limit = '') {
        return $this->table('pd_cash_account')->where($condition)->field($fields)->order($order)->limit($limit)->page($pagesize)->select();
    }
    /**
     * 取得提现账号总数
     * @param unknown $condition
     */
    public function getPdCashAccountCount($condition = array()) {
        return $this->table('pd_cash_account')->where($condition)->count();
    }
    /**
     * 取得提现账号信息
     * @param unknown $condition
     */
    public function getPdCashAccountInfo($condition = array()) {
        return $this->table('pd_cash_account')->where($condition)->find();
    }
    /**
     * 删除 提现账号
     * @param unknown $condition
     */
    public function delPdCashAccount($condition = array()) {
        return $this->table('pd_cash_account')->where($condition)->delete();
    }

    /**
     * add by zhengyifan 2019-09-25
     * 获取预存款变更日志
     * @param $condition
     * @param string $fields
     * @param string $order
     * @return array
     */
    public function getPdLog($condition, $fields = '*', $order = '') {
        return DB::name('pd_log')->where($condition)->field($fields)->order($order)->select();
    }
}