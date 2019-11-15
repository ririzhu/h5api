<?php
defined('DYMall') or exit('Access Invalid!');
class pin_userorderAdd extends mobileHomeCtl
{

    public function __construct()
    {
        if(!(C('promotion_allow')==1 && C('sld_pintuan_ladder') && C('pin_ladder_isuse'))){
            echo json_encode(['status'=>255,'msg'=>'当前活动尚未开启']);die;
        }
        parent::__construct();
    }

    public function order_list_state($arr){
        $uid = $arr['uid'];
        unset($arr['uid']);
        $order_ids = array();
        foreach ($arr as $k=>$v){
            foreach ($v['order_list'] as $kk=>$vv){
                $order_ids[] = $vv['order_id'];
            }
        }
        $where['order_id'] = array('in',join(',',$order_ids));
        $team_list = M('pin')->table('order,pin_team_user,pin_team,pin')
            ->join('left')
            ->on('order.order_id=pin_team_user.sld_order_id, pin_team_user.sld_team_id=pin_team.id, pin_team.sld_pin_id=pin.id')
            ->where($where)
            ->field('pin_team_user.*,pin_team.*,pin.*')
            ->group('order.order_id')
            ->select();
        foreach ($team_list as $k=>$v){
            if($v['id']){
                $temp[$v['sld_order_id']] = $v;
            }

        }
        $team_list = $temp;

        foreach ($arr as $k=>$v) {
            foreach ($v['order_list'] as $kk => $vv) {
                if($team_list[$vv['order_id']]) {
                    $arr[$k]['order_list'][$kk]['if_pin'] = 1;
                    $arr[$k]['order_list'][$kk]['pin'] = $team_list[$vv['order_id']];
                    if($vv['state_desc']=='待付款'){
                        $arr[$k]['order_list'][$kk]['state_desc'] = '拼团订单，待付款';
                    }
                    if($vv['state_desc']=='待发货'){
                        if($team_list[$vv['order_id']]['sld_tuan_status']==1) {
                            $arr[$k]['order_list'][$kk]['state_desc'] = '拼团成功，待发货';
                        }else{
                            $arr[$k]['order_list'][$kk]['state_desc'] = '付款成功，待成团';
                        }
                    }
                    if($vv['state_desc']=='已取消'){
                        if($team_list[$vv['order_id']]['sld_tuan_status']==2) {
                            $arr[$k]['order_list'][$kk]['state_desc'] = '拼团失败，已退款';
                        }else{
                            $arr[$k]['order_list'][$kk]['state_desc'] = '未付款，已取消';
                        }
                    }
                }
            }
        }

        return $arr;
    }

    public function order_list_state_xcx($arr){
        $uid = $arr['uid'];
        unset($arr['uid']);
        $order_ids = array();
        foreach ($arr as $k=>$v){
            foreach ($v['order_list'] as $kk=>$vv){
                $order_ids[] = $vv['order_id'];
            }
        }
        $where['order_id'] = array('in',join(',',$order_ids));
        $team_list = M('pin')->table('order,pin_team_user,pin_team,pin')
            ->join('left')
            ->on('order.order_id=pin_team_user.sld_order_id, pin_team_user.sld_team_id=pin_team.id, pin_team.sld_pin_id=pin.id')
            ->where($where)
            ->field('pin_team_user.*,pin_team.*,pin.*')
            ->group('order.order_id')
            ->select();
        foreach ($team_list as $k=>$v){
            if($v['id']){
                $temp[$v['sld_order_id']] = $v;
            }

        }
        $team_list = $temp;

        foreach ($arr as $k=>$v) {
            foreach ($v['order_list'] as $kk => $vv) {
                if($team_list[$vv['order_id']]) {
                    $arr[$k]['order_list'][$kk]['if_pin'] = 1;
                    $arr[$k]['order_list'][$kk]['pin'] = $team_list[$vv['order_id']];
                    if($vv['state_desc']=='待付款'){
                        $arr[$k]['order_list'][$kk]['state_desc'] = '拼团订单，待付款';
                    }
                    if($vv['state_desc']=='待发货'){
                        if($team_list[$vv['order_id']]['sld_tuan_status']==1) {
                            $arr[$k]['order_list'][$kk]['state_desc'] = '拼团成功，待发货';
                        }else{
                            $arr[$k]['order_list'][$kk]['state_desc'] = '付款成功，待成团';
                        }
                    }
                    if($vv['state_desc']=='已取消'){
                        if($team_list[$vv['order_id']]['sld_tuan_status']==2) {
                            $arr[$k]['order_list'][$kk]['state_desc'] = '拼团失败，已退款';
                        }else{
                            $arr[$k]['order_list'][$kk]['state_desc'] = '未付款，已取消';
                        }
                    }
                }
            }
        }

        return $arr;
    }
}
?>