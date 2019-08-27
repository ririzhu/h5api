<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/11/15
 * Time: 22:19
 */
class pin_ladderModel extends Model{

    private $tuan_state_array = array(
        0 => '全部',
        1 => '等待开始',
        2 => '进行中',
        3 => '已结束',
    );

    public function __construct() {
        parent::__construct('pin_ladder');
    }
    /*
     * 检测商品在阶梯中是否正常
     * $pin_id 拼团id
     * $gid 商品gid
     * $number 数量
     * 返回商品拼团信息
     */
    public function testGoodsState($pin_id,$gid,$number=1)
    {
        $return_data = [];
        try {
            $goods_info = $this->table('goods')->where(['gid' =>$gid])->find();
            $vendor_info = $this->table('vendor')->where(['vid' => $goods_info['vid']])->find();
            if($vendor_info['store_state'] != 1){
                throw new Exception('店铺已经关闭');
            }
            if (!$goods_info) {
                throw new Exception('商品不存在');
            }
            if ($goods_info['goods_state'] != 1 || $goods_info['goods_verify'] != 1) {
                throw new Exception('商品已经下架');
            }
//            if($goods_info['goods_storage'] < $number){
//                throw new Exception('商品库存不足');
//            }
            $return_data['gid'] = $goods_info['gid'];
            $return_data['goods_name'] = $goods_info['goods_name'];
            $return_data['goods_price'] = $goods_info['goods_price'];
            $return_data['goods_image'] = cthumb($goods_info['goods_image']);
            $return_data['goods_spec'] = array_values(unserialize($goods_info['goods_spec']));
            $ladder_goods = $this->table('pin_goods_ladder')->where(['sld_gid' => $gid,'sld_pin_id'=>$pin_id])->find();
            if (!$ladder_goods) {
                throw new Exception('活动商品不存在');
            }
            if ($ladder_goods['sld_stock'] < $number) {
                throw new Exception('库存不足');
            }
            $return_data['sld_pin_id'] = $ladder_goods['sld_pin_id'];
            $return_data['sld_deposit_money'] = $ladder_goods['sld_pin_price'];
            $return_data['deposit_money_all'] = $number*$ladder_goods['sld_pin_price'];
            $return_data['goods_num'] = $number;
            $pin_ladder = $this->table('pin_ladder')->where(['id' => $ladder_goods['sld_pin_id']])->find();
            if (!$pin_ladder) {
                throw new Exception('活动不存在');
            }
            if($pin_ladder['sld_status'] != 1){
                throw new Exception('活动已关闭');
            }
            if(intval($pin_ladder['sld_max_buy']) > 0){
                if($pin_ladder['sld_max_buy'] < $number){
                    throw new Exception('超出限购数量');
                }
            }
            if(!(time() >= $pin_ladder['sld_start_time'] && time() <= $pin_ladder['sld_end_time'])){
                throw new Exception('活动已关闭');
            }
            $return_data['is_tui'] = $pin_ladder['is_tui'];
        } catch (Exception $e) {
            return ['status'=>255,'msg'=>$e->getMessage()];
        }
        return ['status'=>200,'data'=>$return_data];
    }
    /*
     * 检测用户能否交定金购买商品,
     */
    public function testMemberBuyLadderGoods($pin_id,$gid,$member_id)
    {
        $pin_goods_info = $this->table('pin_goods_ladder')->where(['sld_gid'=>$gid,'sld_pin_id'=>$pin_id])->find();
        $pin_info = $this->table('pin_ladder')->where(['id'=>$pin_goods_info['sld_pin_id']])->find();
        $order_info = $this->table('pin_order')->where(['pin_id'=>$pin_goods_info['sld_pin_id'],'buyer_id'=>$member_id,'order_state'=>['in','20,30']])->find();
        //统计人数
        $num = $this->table('pin_team_user_ladder')->where(['sld_gid'=>$pin_goods_info['sld_gid'],'sld_pin_id'=>$pin_goods_info['sld_pin_id']])->count();
        $max_num = $this->table('pin_money_ladder')->where(['gid'=>$pin_goods_info['sld_gid'],'pin_id'=>$pin_goods_info['sld_pin_id']])->order('people_num desc')->find();
        if(!$pin_info['is_overflow']){
                if($num >= $max_num['people_num']){
                    return false;
                }
        }
        $is_team = $this->table('pin_team_user_ladder')->where(['sld_user_id'=>$member_id,'sld_gid'=>$pin_goods_info['sld_gid'],'sld_order_id'=>$order_info['order_id'], 'sld_pin_id'=>$pin_goods_info['sld_pin_id']])->find();
        if(!$is_team){
            return true;
        }
        return false;
    }

    /*
 * 检测商品在阶梯中是否正常(尾款)
 * $pin_id 拼团id
 * $gid 商品gid
 * $number 数量
 * 返回商品拼团信息
 */
    public function testFinishGoodsState($pin_id,$gid,$number=1)
    {
        $return_data = [];
        try {
            $goods_info = $this->table('goods')->where(['gid' => $gid])->find();
            $vendor_info = $this->table('vendor')->where(['vid' => $goods_info['vid']])->find();
            if($vendor_info['store_state'] != 1){
                throw new Exception('店铺已经关闭');
            }
            if (!$goods_info) {
                throw new Exception('商品不存在');
            }
            if ($goods_info['goods_state'] != 1 || $goods_info['goods_verify'] != 1) {
                throw new Exception('商品已经下架');
            }
//            if($goods_info['goods_storage'] < $number){
//                throw new Exception('商品库存不足');
//            }
            $return_data['gid'] = $goods_info['gid'];
            $return_data['goods_name'] = $goods_info['goods_name'];
            $return_data['goods_price'] = $goods_info['goods_price'];
            $return_data['goods_image'] = cthumb($goods_info['goods_image']);
            $return_data['goods_spec'] = array_values(unserialize($goods_info['goods_spec']));
            $ladder_goods = $this->table('pin_goods_ladder')->where(['sld_gid' => $gid,'sld_pin_id'=>$pin_id])->find();
            if (!$ladder_goods) {
                throw new Exception('活动商品不存在');
            }
            if ($ladder_goods['sld_stock'] < $number) {
                throw new Exception('库存不足');
            }
            $return_data['sld_pin_id'] = $ladder_goods['sld_pin_id'];
            $return_data['sld_deposit_money'] = $ladder_goods['sld_pin_price'];
            $return_data['deposit_money_all'] = $number*$ladder_goods['sld_pin_price'];
            $return_data['goods_num'] = $number;
            $pin_ladder = $this->table('pin_ladder')->where(['id' => $ladder_goods['sld_pin_id']])->find();
            if (!$pin_ladder) {
                throw new Exception('活动不存在');
            }
            if($pin_ladder['sld_status'] != 1){
                throw new Exception('活动已关闭');
            }
            if(intval($pin_ladder['sld_max_buy']) > 0){
                if($pin_ladder['sld_max_buy'] < $number){
                    throw new Exception('超出限购数量');
                }
            }
            if(!(time() >= $pin_ladder['sld_end_time'] && time() <= ($pin_ladder['sld_end_time']+$pin_ladder['sld_success_time']*3600))){
                throw new Exception('活动已关闭');
            }
            //未达到第一阶梯
            $ladder_price = $this->table('pin_money_ladder')->where(['gid'=>$gid,'pin_id'=>$pin_id])->order('people_num asc')->find();
            $allnum = $this->table('pin_team_user_ladder')->where(['sld_pin_id'=>$pin_id,'sld_gid'=>$gid])->field('count(*) as allnum')->find();
            if($allnum['allnum'] < $ladder_price['people_num']){
                throw new Exception('该活动未达到最低阶梯');
            }
        } catch (Exception $e) {
            return ['status'=>255,'msg'=>$e->getMessage()];
        }
        return ['status'=>200,'data'=>$return_data];
    }
    /**
     * 团购状态数组
     */
    public function getTuanStateArray() {
        return $this->tuan_state_array;
    }
}