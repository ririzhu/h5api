<?php
defined('DYMall') or exit('Access Invalid!');
class pin_goodsAdd extends mobileHomeCtl
{

    public function __construct()
    {
        if(!(C('promotion_allow')==1 && C('sld_pintuan_ladder') && C('pin_ladder_isuse'))){
            echo json_encode(['status'=>255,'msg'=>'当前活动尚未开启']);die;
        }
        parent::__construct();
    }
    public function goods_detail($arr){
        //查询这个产品的拼团信息
        $common_id = $arr['goods_info']['goods_commonid'];
        $where['sld_goods_id'] = $common_id;
        $where['sld_start_time'] = array('lt',TIMESTAMP);
        $where['sld_end_time'] = array('gt',TIMESTAMP);
        $where['sld_status'] = 1;
        $where['sld_stock'] = array('gt',0);
        $where['sld_gid'] = $arr['goods_info']['gid'];
        $m_pin = M('pin');
        $pin_info=$m_pin->table('pin,pin_goods,goods,pin_team')
            ->field('pin.*,goods.gid,goods.goods_name,goods.goods_price,pin_goods.sld_pin_price,pin_goods.sld_stock,(select count(*) from bbc_order where pin_id = pin.id and bbc_order.order_state>2) as sales,sld_tuan_status')
            ->join('left')
            ->on('pin.id=pin_goods.sld_pin_id,pin_goods.sld_gid=goods.gid, pin.id=pin_team.sld_pin_id')
            ->where($where)
            ->find();


        //查询团队列表
        if($pin_info['sld_goods_id']) {
//            $arr['goods_info']['goods_storage'] = $pin_info['sld_stock'];
//            $pin_info['sld_pic'] = gthumb($pin_info['sld_pic'],'max');
//            $arr['goods_image'] = $pin_info['sld_pic'].','.$arr['goods_image'];
            $where2['sld_pin_id'] = $pin_info['id'];
            $where2['sld_tuan_status'] = 0;
            $where2['sld_start_time'] = array('lt',TIMESTAMP);
            $where2['sld_end_time'] = array('gt',TIMESTAMP);
            $where2['team_add_time'] = array('exp',' ( pin_team.sld_add_time > ( '.TIMESTAMP.'-sld_success_time*3600 ) )');
            $where2['order_state'] = array('gt',2);
            $uid = $arr['member']['member_id'];
            $order = 'pin_team.sld_add_time asc';
            if($uid){
                $order .= ',sld_leader_id = '.$uid.' desc';
            }
            if($_GET['team_id']){
                $order .= ',pin_team.id='.$_GET['team_id'].' desc';
            }

            $teams = M('pin')->table('pin,pin_team,pin_team_user,member,order')
                ->field('(pin.sld_team_count-count(pin_team_user.id)) as sheng,pin_team.id,member_name,member_avatar,member_provinceid,member_cityid,member_areaid,member_areainfo,pin_team.sld_add_time as team_add_time,pin.sld_end_time,sld_success_time,sld_leader_id,wx_nickname,wx_area')
                ->join('left')
                ->on('pin_team.sld_pin_id=pin.id,pin_team_user.sld_team_id=pin_team.id,pin_team.sld_leader_id=member.member_id, pin_team_user.sld_order_id=order.order_id')
                ->where($where2)
                ->group('pin_team.id')
                ->limit(20)
                ->order($order)
                ->select();

            foreach ($teams as $k=>$v){
                $end_time = $v['team_add_time'] + $v['sld_success_time'] * 3600 ;
                if($end_time>$v['sld_end_time']){
                    $end_time = $v['sld_end_time'];
                }
                if($end_time>TIMESTAMP){
                    $teams[$k]['end_time'] = formatDateTime($end_time);
                }else{
                    $teams[$k]['end_time'] = '已失败';
                }
                if($v['wx_nickname']){
                    $teams[$k]['member_name'] = $v['wx_nickname'];
                }
                if($v['wx_area']){
                    $teams[$k]['member_areainfo'] = $v['wx_area'];
                }

                $teams[$k]['avatar'] = $v['member_avatar']?UPLOAD_SITE_URL.DS.ATTACH_AVATAR."/".$v['member_avatar']:'../addons/pin/data/img/def.jpg';

                if($v['sld_leader_id']==$uid ){
                    $pin_info['pinging'] = $v['id'];
                    $teams[$k]['is_own'] = 1;
                }
                $teams[$k]['endd'] = date('Y/m/d H:i:s',$end_time);
            }
            $pin_info['sld_return_leader'] = floatval($pin_info['sld_return_leader']);
            $pin_info['sld_pin_price']=floatval($pin_info['sld_pin_price']);
            $pin_info['sld_pin_price_arr'] = explode('.',$pin_info['sld_pin_price']);
            if(count($pin_info['sld_pin_price_arr'])==1){
                $pin_info['sld_pin_price_arr'][]='';
            }
            $pin_info['sld_end_time'] = date('Y/m/d H:i:s',$pin_info['sld_end_time']);

            $pin_info['team_id'] = $_GET['team_id'];
//            $pin_info['team'] = array_merge($teams,$teams,$teams,$teams,$teams,$teams);
            $pin_info['team'] = $teams;

            $arr['pin'] = $pin_info;
        }



        unset($arr['member']);
        return $arr;
    }

    public function goods_detail_xcx($arr){
        //查询这个产品的拼团信息
        $common_id = $arr['goods_info']['goods_commonid'];
        $where['sld_goods_id'] = $common_id;
        $where['sld_start_time'] = array('lt',TIMESTAMP);
        $where['sld_end_time'] = array('gt',TIMESTAMP);
        $where['sld_status'] = 1;
        $where['sld_stock'] = array('gt',0);
        $where['sld_gid'] = $arr['goods_info']['gid'];
        $pin_info=M('pin')->table('pin,pin_goods,goods,pin_team')
            ->field('pin.*,goods.gid,goods.goods_name,goods.goods_price,pin_goods.sld_pin_price,pin_goods.sld_stock,(select count(*) from bbc_order where pin_id = pin.id and bbc_order.order_state>2) as sales,sld_tuan_status')
            ->join('left')
            ->on('pin.id=pin_goods.sld_pin_id,pin_goods.sld_gid=goods.gid, pin.id=pin_team.sld_pin_id')
            ->where($where)
            ->find();

        //查询团队列表
        if($pin_info['sld_goods_id']) {
//            $arr['goods_info']['goods_storage'] = $pin_info['sld_stock'];
//            $pin_info['sld_pic'] = gthumb($pin_info['sld_pic'],'max');
//            $arr['goods_image'] = $pin_info['sld_pic'].','.$arr['goods_image'];
            $where2['sld_pin_id'] = $pin_info['id'];
            $where2['sld_tuan_status'] = 0;
            $where2['sld_start_time'] = array('lt',TIMESTAMP);
            $where2['sld_end_time'] = array('gt',TIMESTAMP);
            $where2['team_add_time'] = array('exp',' ( pin_team.sld_add_time > ( '.TIMESTAMP.'-sld_success_time*3600 ) )');
            $where2['order_state'] = array('gt',2);
            $uid = $arr['member']['member_id'];
            $order = 'pin_team.sld_add_time asc';
            if($uid){
                $order .= ',sld_leader_id = '.$uid.' desc';
            }
            if($_GET['team_id']){
                $order .= ',pin_team.id='.$_GET['team_id'].' desc';
            }

            $teams = M('pin')->table('pin,pin_team,pin_team_user,member,order')
                ->field('(pin.sld_team_count-count(pin_team_user.id)) as sheng,pin_team.id,member_name,member_avatar,member_provinceid,member_cityid,member_areaid,member_areainfo,pin_team.sld_add_time as team_add_time,pin.sld_end_time,sld_success_time,sld_leader_id,wx_nickname,wx_area')
                ->join('left')
                ->on('pin_team.sld_pin_id=pin.id,pin_team_user.sld_team_id=pin_team.id,pin_team.sld_leader_id=member.member_id, pin_team_user.sld_order_id=order.order_id')
                ->where($where2)
                ->group('pin_team.id')
                ->limit(20)
                ->order($order)
                ->select();

            foreach ($teams as $k=>$v){
                $end_time = $v['team_add_time'] + $v['sld_success_time'] * 3600 ;
                if($end_time>$v['sld_end_time']){
                    $end_time = $v['sld_end_time'];
                }
                if($end_time>TIMESTAMP){
                    $teams[$k]['end_time'] = formatDateTime($end_time);
                }else{
                    $teams[$k]['end_time'] = '已失败';
                }
                if($v['wx_nickname']){
                    $teams[$k]['member_name'] = $v['wx_nickname'];
                }
                if($v['wx_area']){
                    $teams[$k]['member_areainfo'] = $v['wx_area'];
                }
                
                $teams[$k]['avatar'] = $v['member_avatar']?UPLOAD_SITE_URL.DS.ATTACH_AVATAR."/".$v['member_avatar']:MALL_URL.'/addons/pin/data/img/def.jpg';

                if($v['sld_leader_id']==$uid ){
                    $pin_info['pinging'] = $v['id'];
                    $teams[$k]['is_own'] = 1;
                }
                $teams[$k]['endd'] = date('Y/m/d H:i:s',$end_time);
            }
            $pin_info['sld_return_leader'] = floatval($pin_info['sld_return_leader']);
            $pin_info['sld_pin_price']=floatval($pin_info['sld_pin_price']);
            $pin_info['sld_pin_price_arr'] = explode('.',$pin_info['sld_pin_price']);
            if(count($pin_info['sld_pin_price_arr'])==1){
                $pin_info['sld_pin_price_arr'][]='';
            }
            $pin_info['sld_end_time'] = date('Y/m/d H:i:s',$pin_info['sld_end_time']);

            $pin_info['team_id'] = $_GET['team_id'];
//            $pin_info['team'] = array_merge($teams,$teams,$teams,$teams,$teams,$teams);
            $pin_info['team'] = $teams;

            $arr['pin'] = $pin_info;
        }



        unset($arr['member']);
        return $arr;
    }
}
?>