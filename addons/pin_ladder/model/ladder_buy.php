<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/11/19
 * Time: 18:04
 */
class ladder_buyModel extends Model{
    private $tuan_state_array = array(
        0 => '全部',
        1 => '等待开始',
        2 => '进行中',
        3 => '已结束',
    );

    public function __construct() {
        parent::__construct('pin_order');
    }
    //下单生成定金订单,插入订单
    public function buy_step2($param)
    {
//        dd($param);die;
        $param['add_time'] = time();
        $order_id = $this->table('pin_order')->insert($param);
        if(!$order_id){
                throw new Exception('生成订单失败');
        }
        //生成订单号
        $order_sn = $this->makeOrderSn($order_id);
        $insert_all = $this->table('pin_order')->where(['order_id'=>$order_id])->update(['order_sn'=>$order_sn]);
        if(!$insert_all){
            throw new Exception('生成订单失败');
        }
        return $order_sn;
    }
    /*
     * 定金和尾款的支付
     */
    public function pd_pay($order_sn,$member_id)
    {
        $model_pd = Model('predeposit');
        $order_model = M('ladder_order','pin_ladder');
        $member_info = $this->table('member')->where(['member_id'=>$member_id])->find();
        $order_info = $this->table('pin_order')->where(['order_sn'=>$order_sn])->find();
        //判断是第一次交定金还是交尾款
        if($order_info['order_state'] == 10){
            $goods_price = floatval($order_info['goods_price'] * $order_info['goods_num']);
            if($goods_price > $member_info['available_predeposit']){
                throw new Exception('预存款余额不足');
            }
            $data_pd = array();
            $data_pd['member_id'] = $member_id;
            $data_pd['member_name'] = $member_info['member_name'];
            $data_pd['amount'] = $goods_price;
            $data_pd['order_sn'] = $order_info['order_sn'];
            $data_pd['lg_desc'] = '阶梯团购支付定金，使用预存款，订单号: '.$order_info['order_sn'];
            $model_pd->changePd('order_pay',$data_pd);
            //修改订单状态
            $update = [
                'order_state'=>20,
                'first_time'=>time(),
                'payment_code'=>'predeposit',
            ];
            $res_update =  $order_model->editorder(['order_id'=>$order_info['order_id']],$update);
            if(!$res_update){
                throw new  Exception('支付失败');
            }
            //插入阶梯团的队伍表
            $insert = [
                'sld_gid'=>$order_info['gid'],
                'sld_order_id'=>$order_info['order_id'],
                'sld_pin_id'=>$order_info['pin_id'],
                'sld_user_id'=>$order_info['buyer_id'],
                'sld_add_time'=>time()
            ];

            $res_insert =  $this->table('pin_team_user_ladder')->insert($insert);
            if(!$res_insert){
                throw new Exception('支付失败');
            }

        }elseif($order_info['order_state'] == 20){
            if($order_info['order_amount'] > $member_info['available_predeposit']){
                throw new Exception('预存款余额不足');
            }
            $data_pd = array();
            $data_pd['member_id'] = $member_id;
            $data_pd['member_name'] = $member_info['member_name'];
            $data_pd['amount'] = $order_info['order_amount'];
            $data_pd['order_sn'] = $order_info['order_sn'];
            $data_pd['lg_desc'] = '阶梯团购支付尾款，使用预存款，订单号: '.$order_info['order_sn'];
            $model_pd->changePd('order_pay',$data_pd);
            $res = $this->handleordertype_2($order_sn,['payment_code'=>'predeposit']);
            if(!$res){
                throw new Exception('支付失败');
            }
        }else{
            throw new Exception('支付失败');
        }



    }
    /**
     * 订单编号生成规则，n(n>=1)个订单表对应一个支付表，
     * 生成订单编号(年取1位 + $pay_id取13位 + 第N个子订单取2位)
     * 1000个会员同一微秒提订单，重复机率为1/100
     * @param $pay_id 支付表自增ID
     * @return string
     */
    public function makeOrderSn($pay_id) {
        //记录生成子订单的个数，如果生成多个子订单，该值会累加
        static $num;
        if (empty($num)) {
            $num = 1;
        } else {
            $num ++;
        }
        return (date('y',time()) % 9+1) . sprintf('%013d', $pay_id) . jt;
    }
    /*
     * 获取阶梯价格,如果不足则按照原价
     * pin_id 拼团id
     * $gid 商品id
     * return price
     */
    public function getLadderPrice($pin_id,$gid)
    {
        $goods_info = $this->table('goods')->where(['gid'=>$gid])->field('goods_price')->find();
        if(!$goods_info){
            return 0;
        }
        $goods_pin_price_ladder = $this->table('pin_goods_ladder')->where(['sld_gid'=>$gid,'sld_pin_id'=>$pin_id])->find();
        $goods_pin_ladder = $this->table('pin_money_ladder')->where(['gid'=>$gid,'pin_id'=>$pin_id])->select();
        if(!$goods_pin_ladder){
            return 0;
        }
        $already = $this->table('pin_team_user_ladder')->where(['sld_pin_id'=>$pin_id,'sld_gid'=>$gid])->count();
        array_multisort(low_array_column($goods_pin_ladder,'people_num'),SORT_DESC,$goods_pin_ladder);
        if(end($goods_pin_ladder)['people_num'] > $already){
                return $goods_info['goods_price'];
        }
        foreach($goods_pin_ladder as $k=>$v){
                if($already >= $v['people_num']){
                        return $v['pay_money']-$goods_pin_price_ladder['sld_pin_price'];
                }
        }
    }
    /*
     * 生成订单(用于支付尾款回调)
     */
    public function handleordertype_2($out_trade_no,$extend_data)
    {
        $order_model = M('ladder_order','pin_ladder');

        $buy_model = model('buy');
        $model = model();
        $order_info = $order_model->getone(['order_sn'=>$out_trade_no]);
        $goods_info = $model->table('goods')->where(['gid'=>$order_info['gid']])->find();
        if(!$order_info) {
            return false;
        }else{
            try {
                $order_model->begintransaction();
                //修改数据
                $update = [
                    'order_state'=>30,
                    'finished_time'=>time(),
                    'payment_code'=>$extend_data['payment_code'],
                ];
                $edit = $order_model->editorder(['order_sn'=>$out_trade_no],$update);
                if(!$edit){
                    throw new Exception('失败');
                }
                //添加信息到order_pay
                $order_pay_insert = [
                    'pay_sn'=>$buy_model->makePaySn($order_info['buyer_id']),
                    'buyer_id'=>$order_info['buyer_id'],
                    'api_pay_state'=>1
                ];
                $pay_id = $model->table('order_pay')->insert($order_pay_insert);
                if(!$pay_id){
                    throw new Exception('失败');
                }
                //添加信息到order表
                $order_insert = [
                    'order_sn'=>$buy_model->makeOrderSn($pay_id),
                    'pay_sn'=>$order_pay_insert['pay_sn'],
                    'vid'=>$order_info['vid'],
                    'store_name'=>$order_info['store_name'],
                    'buyer_id'=>$order_info['buyer_id'],
                    'buyer_name'=>$order_info['buyer_name'],
                    'add_time'=>time(),
                    'payment_code'=>$extend_data['payment_code'],
                    'payment_time'=>time(),
                    'goods_amount'=>$goods_info['goods_price'] * $order_info['goods_num'],
                    'order_amount'=>($order_info['goods_price'] * $order_info['goods_num']) + $order_info['order_amount'],
                    'pd_points'=>$order_info['pd_points']?:0,
                    'points_ratio'=>$order_info['points_ratio'],
                    'order_state'=>20,
                    'order_from'=>0,
                    'dian_id'=>$order_info['dian_id'],
                    'ziti'=>$order_info['ziti'],
                    'red_id'=>$order_info['red_id'],
                    'red_money'=>$order_info['red_money'],
                    'vred_id'=>$order_info['vred_id'],
                    'pin_order_id'=>$order_info['order_id'],
                ];
                $order_id = $model->table('order')->insert($order_insert);
                $order_insert['order_id'] = $order_id;
                if(!$order_id){
                    throw new Exception('失败');
                }
                $order_common_insert = [
                    'order_id'=>$order_id,
                    'vid'=>$order_insert['vid'],
                    'order_message'=>$order_info['member_message'],
                    'reciver_name'=>$order_info['true_name'],
                    'reciver_info'=>$order_info['address_info'],
                    'invoice_info'=>serialize([]),
                ];
                $common_id = $model->table('order_common')->insert($order_common_insert);
                if(!$common_id){
                    throw new Exception('失败');
                }
                $order_goods_inseret = [
                    'order_id'=>$order_id,
                    'gid'=>$order_info['gid'],
                    'goods_name'=>$order_info['goods_name'],
                    'goods_price'=>$goods_info['goods_price'],
                    'goods_num'=>$order_info['goods_num'],
                    'goods_image'=>$order_info['goods_image'],
                    'goods_pay_price'=>$order_insert['order_amount'],
                    'vid'=>$order_insert['vid'],
                    'buyer_id'=>$order_insert['buyer_id'],
                    'goods_type'=>9,
                    'goods_yongjin'=>$goods_info['fenxiao_yongjin'],
                    'commis_rate'=>$this->getcommis_rate($goods_info),
                ];
                $rec_id = $model->table('order_goods')->insert($order_goods_inseret);
                if(!$rec_id){
                    throw new Exception('失败');
                }
                //修改库存
                $stock = $model->table('pin_goods_ladder')->where(['sld_pin_id'=>$order_info['pin_id'],'sld_gid'=>$order_info['gid']])->update(['sld_stock'=>['exp','sld_stock - '.$order_info['goods_num']]]);
                if(!$stock){
                    throw new Exception('失败');
                }
                //订单记录
                $buy_model->addOrderLog([$order_insert]);
                $order_model->commit();
            } catch (Exception $e) {
                $order_model->rollback();
                return false;
            }
//             支付成功发送店铺消息
//            根据pay_sn获取订单的信息
            $order_info_new = $order_insert;
            $param = array();
            $param['code'] = 'new_order';
            $param['vid'] = $order_info_new['vid'];
            $param['param'] = array(
                'order_sn' => $order_info_new['order_sn']
            );
            QueueClient::push('sendStoreMsg', $param);
            //发送门店提醒
            if($order_info_new['dian_id']>0){
                $param = array();
                $param['code'] = 'dian_new_order';
                $param['vid'] = $order_info_new['dian_id'];
                $param['param'] = array(
                    'order_sn' => $order_info_new['order_sn']
                );
                QueueClient::push('sendDianMsg', $param);
            }
            return true;
        }
    }
    /*
     * 获取商品平台佣金值
     */
    public function getcommis_rate($goods_info)
    {
        $vid = $goods_info['vid'];
        $model = model();
        $commis = $model->table('vendor_bind_category')->where([
            'class_1|class_2|class_3'=>$goods_info['gc_id'],
            'vid'=>$vid
        ])->find();
        return $commis['commis_rate']?:0;
    }
}