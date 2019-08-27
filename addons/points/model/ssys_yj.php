<?php
/**
 * 预存款
 *
 */
defined('DYMall') or exit('Access Invalid!');
class ssys_yjModel extends Model {
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
        return $this->table('ssys_pd_cash')->where($condition)->count();
    }

    /**
     * 取日志总数
     * @param unknown $condition
     */
    public function getPdLogCount($condition = array()) {
        return $this->table('ssys_yj_log')->where($condition)->count();
    }

    /**
     * 取得预存款变更日志列表
     * @param unknown $condition
     * @param string $pagesize
     * @param string $fields
     * @param string $order
     */
    public function getPdLogList($condition = array(), $pagesize = '', $fields = '*', $order = '', $limit = '') {
        return $this->table('ssys_yj_log')->where($condition)->field($fields)->order($order)->limit($limit)->page($pagesize)->select();
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
        $data_statistics = array();
        // $data_msg = array();
        $data_log['lg_member_id'] = $data['member_id'];
        $data_log['lg_member_name'] = $data['member_name'];
        $data_log['lg_add_time'] = TIMESTAMP;
        $data_log['lg_type'] = $change_type;

        // $data_msg['time'] = date('Y-m-d H:i:s');
        // $data_msg['pd_url'] = urlShop('chongzhi', 'chongzhilist');
        switch ($change_type){
            case 'order_pay':
                $data_log['lg_freeze_amount'] = $data['amount'];
                $data_log['lg_desc'] = '下单，获得冻结佣金，订单号: '.$data['order_sn']."，商品ID：".$data['gid'];
                $data_pd['freeze_yongjin'] = array('exp','freeze_yongjin+'.$data['amount']);

                $data_statistics['yj_freeze_amount'] = $data['amount'];

                // $data_msg['av_amount'] = -$data['amount'];
                // $data_msg['freeze_amount'] = 0;
                // $data_msg['desc'] = $data_log['lg_desc'];
                break;
            case 'order_cancel':
                $data_log['lg_disable_amount'] = $data['amount'];
                $data_log['lg_freeze_amount'] = -$data['amount'];
                $data_log['lg_desc'] = '取消订单，订单号: '.$data['order_sn']."，商品ID：".$data['gid'];
                $data_pd['freeze_yongjin'] = array('exp','freeze_yongjin-'.$data['amount']);
                $data_pd['disable_yongjin'] = array('exp','disable_yongjin+'.$data['amount']);

                $data_statistics['yj_freeze_amount_minus'] = $data['amount'];

                // $data_msg['av_amount'] = $data['amount'];
                // $data_msg['freeze_amount'] = -$data['amount'];
                // $data_msg['desc'] = $data_log['lg_desc'];
                break;
            case 'order_over':
                $data_log['lg_av_amount'] = $data['amount'];
                $data_log['lg_freeze_amount'] = -$data['amount'];
                $data_log['lg_desc'] = '订单完成，订单号: '.$data['order_sn']."，商品ID：".$data['gid'];
                $data_pd['freeze_yongjin'] = array('exp','freeze_yongjin-'.$data['amount']);
                $data_pd['available_yongjin'] = array('exp','available_yongjin+'.$data['amount']);

                $data_statistics['yj_freeze_amount_minus'] = $data['amount'];
                $data_statistics['yj_av_amount'] = $data['amount'];

                break;
        	case 'refund':
                $data_log['lg_disable_amount'] = $data['amount'];
        	    $data_log['lg_freeze_amount'] = $data['amount'];
        	    $data_log['lg_desc'] = '确认退款，订单号: '.$data['order_sn']."，商品ID：".$data['gid'];
                $data_pd['freeze_yongjin'] = array('exp','freeze_yongjin-'.$data['amount']);
                $data_pd['disable_yongjin'] = array('exp','disable_yongjin+'.$data['amount']);

                $data_statistics['yj_freeze_amount_minus'] = $data['amount'];

                // $data_msg['av_amount'] = $data['amount'];
                // $data_msg['freeze_amount'] = 0;
                // $data_msg['desc'] = $data_log['lg_desc'];
        	    break;
        	case 'cash_apply':
        	    $data_log['lg_av_amount'] = -$data['amount'];
        	    $data_log['lg_freeze_amount'] = $data['amount'];
        	    $data_log['lg_desc'] = '申请提现，冻结佣金，提现单号: '.$data['order_sn'];
        	    $data_pd['available_yongjin'] = array('exp','available_yongjin-'.$data['amount']);
        	    $data_pd['freeze_yongjin'] = array('exp','freeze_yongjin+'.$data['amount']);

                $data_statistics['yj_freeze_amount'] = $data['amount'];
                $data_statistics['yj_av_amount_minus'] = $data['amount'];

                // $data_msg['av_amount'] = -$data['amount'];
                // $data_msg['freeze_amount'] = $data['amount'];
                // $data_msg['desc'] = $data_log['lg_desc'];
        	    break;
    	    case 'cash_pay':
    	        $data_log['lg_freeze_amount'] = -$data['amount'];
    	        $data_log['lg_desc'] = '提现成功，提现单号: '.$data['order_sn'];
    	        $data_log['lg_admin_name'] = $data['admin_name'];
    	        $data_pd['freeze_yongjin'] = array('exp','freeze_yongjin-'.$data['amount']);

                $data_statistics['yj_freeze_amount_minus'] = $data['amount'];

                // $data_msg['av_amount'] = 0;
                // $data_msg['freeze_amount'] = -$data['amount'];
                // $data_msg['desc'] = $data_log['lg_desc'];
    	        break;
	        case 'cash_del':
	            $data_log['lg_av_amount'] = $data['amount'];
	            $data_log['lg_freeze_amount'] = -$data['amount'];
	            $data_log['lg_desc'] = '取消提现申请，解冻预存款，提现单号: '.$data['order_sn'];
	            $data_log['lg_admin_name'] = $data['admin_name'];
	            $data_pd['available_yongjin'] = array('exp','available_yongjin+'.$data['amount']);
	            $data_pd['freeze_yongjin'] = array('exp','freeze_yongjin-'.$data['amount']);

                $data_statistics['yj_freeze_amount_minus'] = $data['amount'];
                $data_statistics['yj_av_amount'] = $data['amount'];

                // $data_msg['av_amount'] = $data['amount'];
                // $data_msg['freeze_amount'] = -$data['amount'];
                // $data_msg['desc'] = $data_log['lg_desc'];
	            break;
        	default:
        	    throw new Exception('参数错误');
        	    break;
        }

        $update = $this->table('ssys_member')->where(array('member_id'=>$data['member_id']))->update($data_pd);
        if (!$update) {
            throw new Exception('操作失败');
        }
        $insert = $this->table('ssys_yj_log')->insert($data_log);
        if (!$insert) {
            throw new Exception('操作失败');
        }
        if (!empty($data_statistics)) {
            M('ssys_statistics_log','spreader')->save_statistics_log($data_statistics);
        }
        // 支付成功发送买家消息
        // $param = array();
        // $param['code'] = 'predeposit_change';
        // $param['member_id'] = $data['member_id'];
        // $data_msg['av_amount'] = sldPriceFormat($data_msg['av_amount']);
        // $data_msg['freeze_amount'] = sldPriceFormat($data_msg['freeze_amount']);
        // $param['param'] = $data_msg;
        // QueueClient::push('addConsume', array('member_id'=>$data['member_id'],'member_name'=>$data['member_name'],
        //     'consume_amount'=>$data['amount'],'consume_time'=>time(),'consume_remark'=>$data_log['lg_desc']));
        // QueueClient::push('sendMemberMsg', $param);
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
     * @param unknown $condition
     * @param string $pagesize
     * @param string $fields
     * @param string $order
     */
    public function getPdCashList($condition = array(), $pagesize = '', $fields = '*', $order = '', $limit = '') {
        return $this->table('ssys_pd_cash')->where($condition)->field($fields)->order($order)->limit($limit)->page($pagesize)->select();
    }

    /**
     * 添加提现记录
     * @param array $data
     */
    public function addPdCash($data) {
        return $this->table('ssys_pd_cash')->insert($data);
    }

    /**
     * 编辑提现记录
     * @param unknown $data
     * @param unknown $condition
     */
    public function editPdCash($data,$condition = array()) {
        return $this->table('ssys_pd_cash')->where($condition)->update($data);
    }

    /**
     * 取得单条提现信息
     * @param unknown $condition
     * @param string $fields
     */
    public function getPdCashInfo($condition = array(), $fields = '*') {
        return $this->table('ssys_pd_cash')->where($condition)->field($fields)->find();
    }

    /**
     * 删除提现记录
     * @param unknown $condition
     */
    public function delPdCash($condition) {
        return $this->table('ssys_pd_cash')->where($condition)->delete();
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
        return $this->table('ssys_yj_log')->insert($data_log);
    }

    // 佣金 更新
    public function updateYj($data){
        // 根据gid 获取佣金金额 按照后台佣金比例 进行计算
        echo "<pre>";var_dump($data);exit;
    }

    // 获取 商品 佣金金额 当前推手ID
    public function getGoodsYjAmount($gid,$member_id,$spreader_deep=1){

        $yj_percent = 100;

        $last_yj_amount = 0;

        // 获取当前推手分佣级别 获取对应的分佣比例
        if (C('ssys_yj_type') >= 0) {
            // 设置的佣金比例
            $yj_percent_values = unserialize(C('ssys_yj_percent'));

            // 当前用户 属于 几级分佣
            $now_member_yj_type = $spreader_deep;

            if (is_array($yj_percent_values) && !empty($yj_percent_values)) {
                switch ($now_member_yj_type) {
                    case '1':
                        // 一级分佣
                        // 一级分佣比例
                        $yj_percent = (isset($yj_percent_values[0]) && $yj_percent_values[0] > 0) ? $yj_percent_values[0] : 1;
                        break;

                    case '2':
                        // 二级分佣
                        // 二级分佣比例
                        $yj_percent = (isset($yj_percent_values[1]) && $yj_percent_values[1] > 0) ? $yj_percent_values[1] : 1;
                        break;

                    case '3':
                        // 三级分佣
                        // 三级分佣比例
                        $yj_percent = (isset($yj_percent_values[2]) && $yj_percent_values[2] > 0) ? $yj_percent_values[2] : 1;
                        break;
                }
            }
        }

        $ssys_goods = M('ssys_goods','spreader');
        $condition['gid'] = $gid;
        $goods_info = $ssys_goods->get_spreader_goods_info($condition,'yj_amount');

        if (is_array($goods_info) && !empty($goods_info) && isset($goods_info['yj_amount']) && $yj_percent) {
            $last_yj_amount = $goods_info['yj_amount'] * ($yj_percent/100);
        }

        return $last_yj_amount;

    }

    // 更新多条 佣金记录
    public function updateMemberYj($change_type,$data = array()){

        foreach ($data as $key => $value) {
            $this->changePd($change_type,$value);
        }

    }

    // 获取当前推手的 上级 最多三级
    public function get_mutil_level_spreader($spreader_member_id){
        $return_array = array();

        // 获取当前推手信息 可得到 关联的 商城系统member_id
        $ssys_member = M('ssys_member','spreader');
        $member_info = $ssys_member->getMemberInfoByID($spreader_member_id);

        $shop_member_id = $member_info['shop_member_id'];

        if ($shop_member_id) {
            $first_deep['member_id'] = $spreader_member_id;
            $first_deep['deep'] = 1;

            $return_array[] = $first_deep;

            // 查询 是否有上级推手
            $nexus_condition['shop_member_id'] = $shop_member_id;
            $parent_spreader_info = $ssys_member->get_member_nexus_find($nexus_condition);
            $parent_spreader_member_id = $parent_spreader_info['member_id'];
            if ($parent_spreader_member_id) {
                $second_deep['member_id'] = $parent_spreader_member_id;
                $second_deep['deep'] = 2;

                $return_array[] = $second_deep;

                // 查询 是否有上级推手
                $nexus_condition['shop_member_id'] = $parent_spreader_member_id;
                $parent_parent_spreader_info = $ssys_member->get_member_nexus_find($nexus_condition);
                $parent_parent_spreader_member_id = $parent_parent_spreader_info['member_id'];

                if ($parent_parent_spreader_member_id) {
                    $last_deep['member_id'] = $parent_parent_spreader_member_id;
                    $last_deep['deep'] = 3;

                    $return_array[] = $last_deep;
                }

            }
        }


        return $return_array;
    }


}
