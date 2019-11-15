<?php
/**
 * 订单管理
 *
 */
defined('DYMall') or exit('Access Invalid!');
class ssys_orderModel extends Model {

    // 插入多条订单信息
    public function saveOrders($data) {
        foreach ($data as $key => $value) {
            $value['update_time'] = $value['add_time'] = time();
            $data[$key] = $value;
        }
        return $this->table('ssys_order')->insertAll($data);
    }

    // 获取 推手 订单列表
    public function getSpreaderOrderInfos($condition, $field = '*', $page = 0, $order = ''){
        return $this->table('ssys_order')->where($condition)->field($field)->page($page)->order($order)->select();
    }

    // 更新 订单 数据
    public function editOrder($data,$condition){
        $data['update_time'] = time();
        return $this->table('ssys_order')->where($condition)->update($data);
    }

    /**
     * 买家订单状态操作
     *
     */
    public function memberChangeState($state_type, $order_info, $extend_msg) {
        try {
            if ($state_type == 'order_cancel') {
                $this->_memberChangeStateOrderCancel($order_info, $extend_msg);
            } elseif ($state_type == 'order_receive') {
                $this->_memberChangeStateOrderReceive($order_info, $extend_msg);
            } elseif($state_type == 'refund') {
                $this->_memberChangeStateOrderRefund($order_info, $extend_msg);
            }

        } catch (Exception $e) {
            $this->rollback();
        }

    }

    /**
     * 取消订单操作
     * @param unknown $order_info
     */
    private function _memberChangeStateOrderCancel($order_info, $extend_msg) {
        $order_id = $order_info['order_id'];

        // 根据订单号 获取 佣金信息
        $condition['order_id'] = $order_id;
        $order_infos = $this->getSpreaderOrderInfos($condition);

        if (is_array($order_infos) && !empty($order_infos)) {
            $ssys_yj = M('ssys_yj','spreader');
            $data_pd_datas = array();
            foreach ($order_infos as $key => $value) {
                if ($value['yj_amount']) {
                    $data_pd = array();
                    $data_pd['member_id'] = $spreader_member_id = $value['member_id'];
                    $spreader_member_info = M('ssys_member','spreader')->getMemberInfoByID($spreader_member_id);
                    $data_pd['member_name'] = $spreader_member_info['member_name'];
                    $data_pd['amount'] = $value['yj_amount'];
                    $data_pd['order_sn'] = $value['order_sn'];
                    $data_pd['gid'] = $value['gid'];
                    $data_pd_datas[] = $data_pd;
                }
            }
            $ssys_yj->updateMemberYj('order_cancel',$data_pd_datas);
        }

        //更新订单佣金状态信息 -1 为佣金已失效
        $update_order = array('yj_status' => -1);
        $update = $this->editOrder($update_order,array('order_id'=>$order_id));
        if (!$update) {
            throw new Exception('保存失败');
        }
        
    }

    /**
     * 收货操作
     * @param unknown $order_info
     */
    private function _memberChangeStateOrderReceive($order_info, $extend_msg) {
        $order_id = $order_info['order_id'];

    }

    // 退款/退货操作
    private function _memberChangeStateOrderRefund($order_info, $extend_msg) {
        $order_id = $order_info['order_id'];

        // 根据订单号 获取 佣金信息
        $condition['order_id'] = $order_id;
        $order_infos = $this->getSpreaderOrderInfos($condition);

        if (is_array($order_infos) && !empty($order_infos)) {
            $ssys_yj = M('ssys_yj','spreader');
            $data_pd_datas = array();
            foreach ($order_infos as $key => $value) {
                if ($value['yj_amount']) {
                    $data_pd = array();
                    $data_pd['member_id'] = $spreader_member_id = $value['member_id'];
                    $spreader_member_info = M('ssys_member','spreader')->getMemberInfoByID($spreader_member_id);
                    $data_pd['member_name'] = $spreader_member_info['member_name'];
                    $data_pd['amount'] = $value['yj_amount'];
                    $data_pd['order_sn'] = $value['order_sn'];
                    $data_pd['gid'] = $value['gid'];
                    $data_pd_datas[] = $data_pd;
                }
            }
            $ssys_yj->updateMemberYj('refund',$data_pd_datas);
        }
        
        //更新订单佣金状态信息 -1 为佣金已失效
        $update_order = array('yj_status' => -1);
        $update = $this->editOrder($update_order,array('order_id'=>$order_id));
        if (!$update) {
            throw new Exception('保存失败');
        }
    }

    // 定时任务 订单 冻结转可用
    public function spreader_freeze_to_av(){
        // 获取 系统配置 设置的 结算周期（确认收货后多长时间 冻结转可用）
        $rechange_day_num = C('ssys_freeze_to_av_days') ? C('ssys_freeze_to_av_days') : 15;
        $rechang_day_time = time() - 60*60*24*$rechange_day_num;
        // 获取 当前 未完成的订单信息（冻结状态的推手订单）
        $condition['yj_status'] = 0;
        $spreader_orders = $this->getSpreaderOrderInfos($condition);

        $order_sn_list = low_array_column($spreader_orders,'order_sn');

        $spreader_order_gid_arr = array();
        $spreader_order_member_arr = array();
        foreach ($spreader_orders as $s_o_k => $s_o_v) {
            $spreader_order_gid_arr[$s_o_v['order_id']][] = $s_o_v['gid'];
            $spreader_order_member_arr[$s_o_v['order_id']][$s_o_v['gid']][] = $s_o_v['member_id'];
            $spreader_order_member_yj_amount_arr[$s_o_v['order_id']][$s_o_v['gid']][$s_o_v['member_id']] = $s_o_v['yj_amount'];
        }

        // 去重
        $order_sn_list = array_flip($order_sn_list);
        $order_sn_list = array_flip($order_sn_list);
        $order_sn_list = array_values($order_sn_list);

        // 查询 待处理订单 （完成状态，且完成时间 已满足 冻结转可用 周期）
        $order_condition['order_sn'] = array('IN',$order_sn_list);
        $order_condition['order_state'] = ORDER_STATE_SUCCESS;
        $order_condition['finnshed_time'] = array('lt',$rechang_day_time);
        $wait_order_list = Model('order')->getOrderList($order_condition,'','order_id,order_sn');

        if (is_array($wait_order_list) && !empty($wait_order_list)) {
            $wait_order_ids = low_array_column($wait_order_list,'order_id');

            // 处理 确认收货的 订单 冻结转可用
            foreach ($wait_order_list as $key => $value) {

                if (is_array($spreader_order_gid_arr) && !empty($spreader_order_gid_arr) && $spreader_order_gid_arr[$value['order_id']]) {
                    $order_goods_ids = $spreader_order_gid_arr[$value['order_id']];

                    // 去重
                    $order_goods_ids = array_flip($order_goods_ids);
                    $order_goods_ids = array_flip($order_goods_ids);
                    $order_goods_ids = array_values($order_goods_ids);

                    foreach ($order_goods_ids as $o_g_i_k => $o_g_i_v) {
                        if (is_array($spreader_order_member_arr[$value['order_id']][$o_g_i_v]) && !empty($spreader_order_member_arr[$value['order_id']][$o_g_i_v])) {

                            $spreader_member_ids = $spreader_order_member_arr[$value['order_id']][$o_g_i_v];
                            // 去重
                            $spreader_member_ids = array_flip($spreader_member_ids);
                            $spreader_member_ids = array_flip($spreader_member_ids);
                            $spreader_member_ids = array_values($spreader_member_ids);

                            foreach ($spreader_member_ids as $m_k => $m_v) {
                                $spreader_member_id = $m_v;
                                // 获取佣金金额
                                $yj_amount = $spreader_order_member_yj_amount_arr[$value['order_id']][$o_g_i_v][$spreader_member_id];
                                if ($yj_amount) {
                                    $yj_data_item['member_id'] = $spreader_member_id;
                                    $spreader_member_info = M('ssys_member','spreader')->getMemberInfoByID($spreader_member_id);

                                    //判断推手状态是否是正式推手
                                    if($spreader_member_info['ts_member_state'] != 2){
                                        continue;
                                    }

                                    $yj_data_item['member_name'] = $spreader_member_info['member_name'];
                                    $yj_data_item['amount'] = $yj_amount;
                                    $yj_data_item['order_sn'] = $value['order_sn'];
                                    $yj_data_item['gid'] = $o_g_i_v;
                                    $yj_data[] = $yj_data_item;
                                }
                            }
                            
                        }
                    }
                }
            }

            if (!empty($yj_data)) {
                M('ssys_yj','spreader')->updateMemberYj('order_over',$yj_data);

                $edit_order_condition['order_id'] = array("IN",$wait_order_ids);
                $order_data['yj_status'] = 1;
                $this->editOrder($order_data,$edit_order_condition);
            }

        }

    }
    /*
     * 会员查看自己成为推手的任务进度
     * @param int member_id 会员id
     * return array/bool [condition1=>money,'condition2'=>money/int] 统计结果数据,如果条件达成则返回true
     */
    public function StatisticsMemberCondition($member_id)
    {
        //可退款退货天数
        $days = Model('trade')->getMaxDay('order_refund');
        //条件1的满足金额
        $money1 = C('ssys_ts_condition1_money');
        //条件2满足的金额/件数
        $money2 = C('ssys_ts_condition2_goodsmoney');
        //成为推手条件开关
        $state = C('ssys_become_ts_open');


        $member_arr = $this->table('ssys_member')->where(['member_id'=>$member_id])->field('member_id,shop_member_id')->select();
        //需要返回的数组数据
        $data = [];

        foreach($member_arr as $k=>$member){
            //如果成为推手开关是关闭的状态则会员变成永久状态
            if(!$state){
                M('ssys_member','spreader')->editMember(['member_id'=>$member['member_id']],['ts_member_state'=>2]);
                return true;
            }


            //1-2 条件1统计
            //先小范围再大范围,防止重复查询,所以先判断升级,再判断降级

            //条件1升级
            $condition = [];
            $condition = [
                'order_state'=>40,
                'buyer_id'=>$member['shop_member_id'],
                'lock_state'=>0,
                'finnshed_time'=>['lt',time()-($days*24*60*60)]
            ];
            $fifty_real_money1 = $this->table('order')->where($condition)->field('sum(order_amount) as order_money,sum(refund_amount) as refund_money')->find();
            //升级1成功
            if(($fifty_real_money1['order_money']-$fifty_real_money1['refund_money']) >= $money1){
                M('ssys_member','spreader')->editMember(['member_id'=>$member['member_id']],['ts_member_state'=>2]);
                return true;
            }

            //1-2 条件2统计
            //条件2升级
            $condition = [];
            $condition = [
                'ssys_ts_order.ssysorder_member_id'=>$member['member_id'],
                'order.order_state'=>40,
                'order.buyer_id'=>$member['shop_member_id'],
                'order.lock_state'=>0,
                'order.refund_state'=>['neq',2],
                'order.finnshed_time'=>['lt',time()-($days*24*60*60)]
            ];
            $fifty_real_money2 = $this->table('ssys_ts_order,order')->join('left')->on('ssys_ts_order.ssysorder_order_id=order.order_id')->where($condition)->field('sum(order_amount) as order_money,sum(refund_amount) as refund_money')->find();

            //条件2升级成功
            if($money2>0){
                //如果条件为金额限制,则判断是否大于等于这个金额
                if(($fifty_real_money2['order_money']-$fifty_real_money2['refund_money']) >= $money2){
                    M('ssys_member','spreader')->editMember(['member_id'=>$member['member_id']],['ts_member_state'=>2]);
                    return true;
                }
            }else{
                //如果条件为购买一件,只要总金额-退款金额大于0说明已经购买过一件,这里排除了全部退款订单
                if(($fifty_real_money2['order_money']-$fifty_real_money2['refund_money']) > 0){
                    M('ssys_member','spreader')->editMember(['member_id'=>$member['member_id']],['ts_member_state'=>2]);
                    return true;
                }
            }


            //0-1/1-0 条件1
            $condition = [];
            $condition = [
                'order_state'=>40,
                'buyer_id'=>$member['shop_member_id'],
                'lock_state'=>0
            ];
            $all_real_money1 = $this->table('order')->where($condition)->field('sum(order_amount) as order_money,sum(refund_amount) as refund_money')->find();
            //升级1成功
            if(($all_real_money1['order_money']-$all_real_money1['refund_money']) >= $money1){
                M('ssys_member','spreader')->editMember(['member_id'=>$member['member_id']],['ts_member_state'=>1]);
                return true;
            }else{
                M('ssys_member','spreader')->editMember(['member_id'=>$member['member_id']],['ts_member_state'=>0]);
                $data['condition1'] = $all_real_money1['order_money']-$all_real_money1['refund_money'];

            }

            //0-1/1-0 条件2
            //条件2升级
            $condition = [];
            $condition = [
                'ssys_ts_order.ssysorder_member_id'=>$member['member_id'],
                'order.order_state'=>40,
                'order.refund_state'=>['neq',2],
                'order.buyer_id'=>$member['shop_member_id'],
                'order.lock_state'=>0,
            ];
            $all_real_money2 = $this->table('ssys_ts_order,order')->join('left')->on('ssys_ts_order.ssysorder_order_id=order.order_id')->where($condition)->field('sum(order_amount) as order_money,sum(refund_amount) as refund_money')->find();
            //条件2升级成功

            //如果条件是金额限制,则判断是够达到满足金额
            if($money2>0){
                if(($all_real_money2['order_money']-$all_real_money2['refund_money']) >= $money2){
                    M('ssys_member','spreader')->editMember(['member_id'=>$member['member_id']],['ts_member_state'=>1]);
                    return true;
                }else{
                    M('ssys_member','spreader')->editMember(['member_id'=>$member['member_id']],['ts_member_state'=>0]);
                    $data['condition2'] = $all_real_money2['order_money']-$all_real_money2['refund_money'];
                }
            }else{
                //如果条件是数量大于1件则判断总金额减去退款是欧服大于0,这里排除了全部退款订单
                if(($all_real_money2['order_money']-$all_real_money2['refund_money']) > 0){
                    M('ssys_member','spreader')->editMember(['member_id'=>$member['member_id']],['ts_member_state'=>1]);
                    return true;
                }else{
                    M('ssys_member','spreader')->editMember(['member_id'=>$member['member_id']],['ts_member_state'=>0]);
                    $data['condition2'] = '0';
                }
            }
        }
        return $data;
    }
    /*
     * 会员注册时根据会员以往的消费进行状态判断
     * @param int member_id 会员id
     * return bool
     */
    public function StatisticsMemberOrderMoney($member_id)
    {
        //可退款退货天数
        $days = Model('trade')->getMaxDay('order_refund');
        //条件1的满足金额
        $money1 = C('ssys_ts_condition1_money');
        //条件2满足的金额/件数
        $money2 = C('ssys_ts_condition2_goodsmoney');
        //成为推手条件开关
        $state = C('ssys_become_ts_open');
        //先取出需要检测的会员
        $member_arr = $this->table('ssys_member')->where(['member_id'=>$member_id])->field('member_id,shop_member_id')->select();


        foreach($member_arr as $k=>$member){
            //如果成为推手开关是关闭的状态则会员变成永久状态
            if(!$state){
                M('ssys_member','spreader')->editMember(['member_id'=>$member['member_id']],['ts_member_state'=>2]);
                continue;
            }


            //1-2 条件1统计
            //先小范围再大范围,防止重复查询,所以先判断升级,再判断降级

            //条件1升级
            $condition = [];
            $condition = [
                'order_state'=>40,
                'buyer_id'=>$member['shop_member_id'],
                'lock_state'=>0,
                'finnshed_time'=>['lt',time()-($days*24*60*60)]
            ];
            $fifty_real_money1 = $this->table('order')->where($condition)->field('sum(order_amount) as order_money,sum(refund_amount) as refund_money')->find();
            //升级1成功
            if(($fifty_real_money1['order_money']-$fifty_real_money1['refund_money']) >= $money1){
                M('ssys_member','spreader')->editMember(['member_id'=>$member['member_id']],['ts_member_state'=>2]);
                continue;
            }


            //0-1/1-0 条件1
            $condition = [];
            $condition = [
                'order_state'=>40,
                'buyer_id'=>$member['shop_member_id'],
                'lock_state'=>0
            ];
            $all_real_money1 = $this->table('order')->where($condition)->field('sum(order_amount) as order_money,sum(refund_amount) as refund_money')->find();
            //升级1成功
            if(($all_real_money1['order_money']-$all_real_money1['refund_money']) >= $money1){
                M('ssys_member','spreader')->editMember(['member_id'=>$member['member_id']],['ts_member_state'=>1]);
                continue;
            }else{
                M('ssys_member','spreader')->editMember(['member_id'=>$member['member_id']],['ts_member_state'=>0]);
            }
        }

    }

    //推手会员状态信息变更
    public function ts_member_change_state()
    {
        //可退款退货天数
        $days = Model('trade')->getMaxDay('order_refund');
        //条件1的满足金额
        $money1 = C('ssys_ts_condition1_money');
        //条件2满足的金额/件数
        $money2 = C('ssys_ts_condition2_goodsmoney');
        //成为推手条件开关
        $state = C('ssys_become_ts_open');
        //先取出推手会员状态不等于2的会员
        $member_arr = $this->table('ssys_member')->where(['ts_member_state'=>['neq',2]])->field('member_id,shop_member_id')->select();


        foreach($member_arr as $k=>$member){
            //如果成为推手开关是关闭的状态则会员变成永久状态
            if(!$state){
                M('ssys_member','spreader')->editMember(['member_id'=>$member['member_id']],['ts_member_state'=>2]);
                continue;
            }


            //1-2 条件1统计
            //先小范围再大范围,防止重复查询,所以先判断升级,再判断降级

            //条件1升级
            $condition = [];
            $condition = [
                'order_state'=>40,
                'buyer_id'=>$member['shop_member_id'],
                'lock_state'=>0,
                'finnshed_time'=>['lt',time()-($days*24*60*60)]
            ];
            $fifty_real_money1 = $this->table('order')->where($condition)->field('sum(order_amount) as order_money,sum(refund_amount) as refund_money')->find();
            //升级1成功


            if(($fifty_real_money1['order_money']-$fifty_real_money1['refund_money']) >= $money1){
                M('ssys_member','spreader')->editMember(['member_id'=>$member['member_id']],['ts_member_state'=>2]);
                continue;
            }

            //1-2 条件2统计
            //条件2升级
            $condition = [];
            $condition = [
                'ssys_ts_order.ssysorder_member_id'=>$member['member_id'],
                'order.order_state'=>40,
                'order.buyer_id'=>$member['shop_member_id'],
                'order.lock_state'=>0,
                'order.refund_state'=>['neq',2],
                'order.finnshed_time'=>['lt',time()-($days*24*60*60)]
            ];
            $fifty_real_money2 = $this->table('ssys_ts_order,order')->join('left')->on('ssys_ts_order.ssysorder_order_id=order.order_id')->where($condition)->field('sum(order_amount) as order_money,sum(refund_amount) as refund_money')->find();

                //条件2升级成功
                if($money2>0){
                    //如果条件为金额限制,则判断是否大于等于这个金额
                    if(($fifty_real_money2['order_money']-$fifty_real_money2['refund_money']) >= $money2){
                        M('ssys_member','spreader')->editMember(['member_id'=>$member['member_id']],['ts_member_state'=>2]);
                        continue;
                    }
                }else{
                    //如果条件为购买一件,只要总金额-退款金额大于0说明已经购买过一件,这里排除了全部退款订单
                    if(($fifty_real_money2['order_money']-$fifty_real_money2['refund_money']) > 0){
                        M('ssys_member','spreader')->editMember(['member_id'=>$member['member_id']],['ts_member_state'=>2]);
                        continue;
                    }
                }


            //0-1/1-0 条件1
            $condition = [];
            $condition = [
                'order_state'=>40,
                'buyer_id'=>$member['shop_member_id'],
                'lock_state'=>0
            ];
            $all_real_money1 = $this->table('order')->where($condition)->field('sum(order_amount) as order_money,sum(refund_amount) as refund_money')->find();
            //升级1成功
            if(($all_real_money1['order_money']-$all_real_money1['refund_money']) >= $money1){
                M('ssys_member','spreader')->editMember(['member_id'=>$member['member_id']],['ts_member_state'=>1]);
                continue;
            }else{
                M('ssys_member','spreader')->editMember(['member_id'=>$member['member_id']],['ts_member_state'=>0]);
            }

            //0-1/1-0 条件2
            //条件2升级
            $condition = [];
            $condition = [
                'ssys_ts_order.ssysorder_member_id'=>$member['member_id'],
                'order.order_state'=>40,
                'order.refund_state'=>['neq',2],
                'order.buyer_id'=>$member['shop_member_id'],
                'order.lock_state'=>0,
            ];
            $all_real_money2 = $this->table('ssys_ts_order,order')->join('left')->on('ssys_ts_order.ssysorder_order_id=order.order_id')->where($condition)->field('sum(order_amount) as order_money,sum(refund_amount) as refund_money')->find();
                //条件2升级成功

                //如果条件是金额限制,则判断是够达到满足金额
                if($money2>0){
                    if(($all_real_money2['order_money']-$all_real_money2['refund_money']) >= $money2){
                        M('ssys_member','spreader')->editMember(['member_id'=>$member['member_id']],['ts_member_state'=>1]);
                        continue;
                    }else{
                        M('ssys_member','spreader')->editMember(['member_id'=>$member['member_id']],['ts_member_state'=>0]);
                    }
                }else{
                    //如果条件是数量大于1件则判断总金额减去退款是欧服大于0,这里排除了全部退款订单
                    if(($all_real_money2['order_money']-$all_real_money2['refund_money']) > 0){
                        M('ssys_member','spreader')->editMember(['member_id'=>$member['member_id']],['ts_member_state'=>1]);
                        continue;
                    }else{
                        M('ssys_member','spreader')->editMember(['member_id'=>$member['member_id']],['ts_member_state'=>0]);
                    }
                }
        }
    }

}

