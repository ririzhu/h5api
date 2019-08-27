<?php
defined('DYMall') or exit('Access Invalid!');
class pin_buyAdd extends mobileHomeCtl
{

    public function __construct()
    {
        parent::__construct();
    }

    public function confirm($goods_info){  //开团
        $pin_id     =  $_POST['pin'];

        if($pin_id){
            $team_id    =  $_POST['team'];
            $where['sld_start_time'] = array('lt',TIMESTAMP);
            $where['sld_end_time'] = array('gt',TIMESTAMP);
            $where['sld_status'] = 1;
            $where['pin.id'] = $pin_id;
            $where['sld_stock'] = array('gt',0);
            $where['sld_gid'] = $goods_info['gid'];
            $where['sld_status'] = 1;
            $pin_info=M('pin')->table('pin,pin_goods,goods')
                ->field('pin.*,goods.gid,goods.goods_name,goods.goods_price,pin_goods.sld_pin_price,pin_goods.sld_stock,(select count(*) from bbc_order where pin_id = pin.id and bbc_order.order_state>2) as sales')
                ->join('left')
                ->on('pin.id=pin_goods.sld_pin_id,pin_goods.sld_gid=goods.gid,')
                ->where($where)
                ->find();
            if($pin_info) {
                if($goods_info['goods_num'] > $pin_info['sld_max_buy'] && $pin_info['sld_max_buy'] != 0){
                    $goods_info['goods_num'] = $pin_info['sld_max_buy'];
                }
                    $goods_info['goods_price'] = $pin_info['sld_pin_price'];
                    $goods_info['goods_storage'] = $pin_info['sld_stock'];
                    $arr['goods_info'] = $goods_info;
                    $arr['pin'] = $pin_info;
            }

        }
        return $arr;
    }

    public function confirm_xcx($goods_info){  //开团
        $pin_id     =  $_GET['pin'];
        if($pin_id){
            $team_id    =  $_GET['team'];
            $where['sld_start_time'] = array('lt',TIMESTAMP);
            $where['sld_end_time'] = array('gt',TIMESTAMP);
            $where['sld_status'] = 1;
            $where['pin.id'] = $pin_id;
            $where['sld_stock'] = array('gt',0);
            $where['sld_gid'] = $goods_info['gid'];
            $where['sld_status'] = 1;
            $pin_info=M('pin')->table('pin,pin_goods,goods')
                ->field('pin.*,goods.gid,goods.goods_name,goods.goods_price,pin_goods.sld_pin_price,pin_goods.sld_stock,(select count(*) from bbc_order where pin_id = pin.id and bbc_order.order_state>2) as sales')
                ->join('left')
                ->on('pin.id=pin_goods.sld_pin_id,pin_goods.sld_gid=goods.gid,')
                ->where($where)
                ->find();
            if($pin_info) {
                $goods_info['goods_price'] = $pin_info['sld_pin_price'];
                $goods_info['goods_storage'] = $pin_info['sld_stock'];
                $arr['goods_info'] = $goods_info;
                $arr['pin'] = $pin_info;
            }

        }
        return $arr;
    }

    public function submitorder($goods_info){
        $key=$_POST['key'];
        if (!empty($key)) {
            $member_info = array();
            $model_mb_user_token = Model('mb_user_token');
            //判断是否收藏
            $member_info = $model_mb_user_token->getMbUserTokenInfoByToken($key);
        }else{
            return array('error' => '请重新登陆');
        }
        $pin_id     =  $_POST['pin'];

//        判断是否已经下过单
//        $where2['sld_tuan_status'] = 0;
//        $where2['sld_pin_id'] = $pin_id;
//        $where2['sld_user_id'] = $member_info['member_id'];
//        $where2['sld_gid'] = $goods_info['gid'];
//        $where2['pin_id'] = $pin_id;
//        $haspin = M('pin')->table('pin_team,pin_team_user,order')->join('left')->on('pin_team.id=pin_team_user.sld_team_id, pin_team_user.sld_order_id=order.order_id')->field('pay_sn')->where($where2)->find();
//        if($haspin){
//            $arr['goods_info'] = $goods_info;
//            $arr['pin'] = array('haspin'=>$haspin['pay_sn']);
//            return $arr;
//        }

        if($pin_id){
            $where['sld_start_time'] = array('lt',TIMESTAMP);
            $where['sld_end_time'] = array('gt',TIMESTAMP);
            $where['sld_status'] = 1;
            $where['pin.id'] = $pin_id;
            $where['sld_stock'] = array('gt',0);
            $where['sld_gid'] = $goods_info['gid'];
            $where['sld_status'] = 1;

            $pin_info=M('pin')->table('pin,pin_goods,goods')
                ->field('pin.*,goods.gid,goods.goods_name,goods.goods_price,pin_goods.sld_pin_price,pin_goods.sld_stock')
                ->join('left')
                ->on('pin.id=pin_goods.sld_pin_id,pin_goods.sld_gid=goods.gid,')
                ->where($where)
                ->find();



            if($pin_info) {

                //判断是否参加过本团
                $db = M('pin');
                $wherecanjia['order_state'] = '20';
                $wherecanjia['sld_pin_id'] = $pin_id;
                $wherecanjia['sld_user_id'] = $member_info['member_id'];
                $canjia = $db->table('pin_team,pin_team_user,order')
                    ->alias('pt,ptu,o')
                    ->field('ptu.id')
                    ->join('left')
                    ->on('pt.id=ptu.sld_team_id,ptu.sld_order_id=o.order_id')
                    ->where($wherecanjia)->count();


                if($canjia > $pin_info['sld_max_buy']) {
                    return array('error' => '超过限购数量');
                }


                $goods_info['goods_price'] = $pin_info['sld_pin_price'];
                $goods_info['goods_storage'] = $pin_info['sld_stock'];

                $arr['goods_info'] = $goods_info;
                $arr['pin'] = $pin_info;
            }else{
                return array('error' => '拼团信息不存在，请重新进入');
            }

        }
        return $arr;
    }

    public function submitorder_xcx($goods_info){
        $key=$_GET['key'];
        if (!empty($key)) {
            $member_info = array();
            $model_mb_user_token = Model('mb_user_token');
            //判断是否收藏
            $member_info = $model_mb_user_token->getMbUserTokenInfoByToken($key);
        }else{
            return array('error' => '请重新登陆');
        }
        $pin_id     =  $_GET['pin'];

//        判断是否已经下过单
//        $where2['sld_tuan_status'] = 0;
//        $where2['sld_pin_id'] = $pin_id;
//        $where2['sld_user_id'] = $member_info['member_id'];
//        $where2['sld_gid'] = $goods_info['gid'];
//        $where2['pin_id'] = $pin_id;
//        $haspin = M('pin')->table('pin_team,pin_team_user,order')->join('left')->on('pin_team.id=pin_team_user.sld_team_id, pin_team_user.sld_order_id=order.order_id')->field('pay_sn')->where($where2)->find();
//        if($haspin){
//            $arr['goods_info'] = $goods_info;
//            $arr['pin'] = array('haspin'=>$haspin['pay_sn']);
//            return $arr;
//        }

        if($pin_id){
            $where['sld_start_time'] = array('lt',TIMESTAMP);
            $where['sld_end_time'] = array('gt',TIMESTAMP);
            $where['sld_status'] = 1;
            $where['pin.id'] = $pin_id;
            $where['sld_stock'] = array('gt',0);
            $where['sld_gid'] = $goods_info['gid'];
            $where['sld_status'] = 1;

            $pin_info=M('pin')->table('pin,pin_goods,goods')
                ->field('pin.*,goods.gid,goods.goods_name,goods.goods_price,pin_goods.sld_pin_price,pin_goods.sld_stock')
                ->join('left')
                ->on('pin.id=pin_goods.sld_pin_id,pin_goods.sld_gid=goods.gid,')
                ->where($where)
                ->find();
            if($pin_info) {
                $goods_info['goods_price'] = $pin_info['sld_pin_price'];
                $goods_info['goods_storage'] = $pin_info['sld_stock'];

                $arr['goods_info'] = $goods_info;
                $arr['pin'] = $pin_info;
            }else{
                return array('error' => '拼团信息不存在，请重新进入');
            }

        }
        return $arr;
    }

    public function insertTeam($par){
        $order = $par['order'];
        $pin_info = $par['pin_info'];
        $team_id = $par['team_id'];
        if(!$team_id){ //如果是开团
            $team_par['sld_pin_id']     = $pin_info['id'];
            $team_par['sld_common_id']  = $pin_info['sld_goods_id'];
            $team_par['sld_add_time']   = TIMESTAMP;
            $team_par['sld_leader_id']  = $order['buyer_id'];
            $team_id = M('pin')->table('pin_team')->insert($team_par);
        }
        if(!$team_id){
            return array('succ'=>0,'msg'=>'开团失败，团队表没有正确插入');
        }
        $team_user_par['sld_gid']       = $pin_info['gid'];
        $team_user_par['sld_order_id']  = $order['order_id'];
        $team_user_par['sld_team_id']   = $team_id;
        $team_user_par['sld_user_id']   = $order['buyer_id'];
        $team_user_par['sld_add_time']  = TIMESTAMP;
        if(!$par['team_id']){
            $team_user_par['sld_fanli'] = 1;
        }
        $team_user_id = M('pin')->table('pin_team_user')->insert($team_user_par);
        if(!$team_user_id){
            return array('succ'=>0,'msg'=>'开团失败，队员表没有正确插入');
        }
        return array('succ'=>1,'data'=>array('team_id'=>$team_id,'team_user_id'=>$team_user_id));
    }

}
?>