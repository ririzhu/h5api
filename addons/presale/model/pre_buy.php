<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/11/29
 * Time: 16:04
 */
class pre_buyModel extends Model
{
    public function __construct() {
        parent::__construct();
    }
    /*
     * 验证用户能否添加定金订单
     * $gid 商品id
     * $pre_id 预售活动id
     * member_id 会员id
     * num 数量
     */
    public function testMemberPayDeposit($gid,$pre_id,$member_id,$num=1)
    {
        try {
            //检测店铺,商品状态
            $goods_info = $this->table('goods')->where(['gid' => $gid])->find();
            if (!$goods_info) {
                throw new Exception('商品不存在');
            }
            if ($goods_info['goods_state'] != 1 || $goods_info['goods_verify'] != 1) {
                throw new Exception('商品已经下架');
            }
            $vendor_info = $this->table('vendor')->where(['vid' => $goods_info['vid']])->find();
            if(!$vendor_info){
                throw new Exception('店铺不存在');
            }
            if($vendor_info['store_state'] != 1){
                throw new Exception('店铺已关闭');
            }
            //检测活动的状态
            $pre_info = $this->table('presale,pre_goods')->join('left')->on('presale.pre_id=pre_goods.pre_id')->where([
                'presale.pre_id'=>$pre_id,
                'pre_goods.gid'=>$gid,
            ])->find();
            if (!$pre_info) {
                throw new Exception('活动不存在');
            }
            if($pre_info['pre_status'] != 1){
                throw new Exception('活动已关闭');
            }
            if($pre_info['pre_start_time'] > time()){
                throw new Exception('活动尚未开启');
            }
            if($pre_info['pre_end_time'] < time()){
                throw new Exception('活动已经结束');
            }
            if($pre_info['pre_max_buy'] > 0){
                if($pre_info['pre_max_buy'] < $num){
                    throw new Exception('超过限购数量');
                }
            }
            if($pre_info['goods_stock'] < $num){
                throw new Exception('商品库存不足');
            }
            //检测用户是已经买过
            $order_info = $this->table('pre_order')->where(['buyer_id'=>$member_id,'order_state'=>['in','20,30'],'pre_id'=>$pre_id,'gid'=>$gid])->find();
            if($order_info){
                throw new Exception('你已经参与过此活动,不能重复参加');
            }
            return ['status'=>200];
        } catch (Exception $e) {
            return ['status'=>255,'msg'=>$e->getMessage()];
        }
    }
    /*
    * 验证用户能否支付定金 (与上面函数的区别在于库存的检测)
    * $gid 商品id
    * $pre_id 预售活动id
    * member_id 会员id
    */
    public function testMemberPayDepositOrder($gid,$pre_id,$member_id)
    {
        try {
            //检测店铺,商品状态
            $goods_info = $this->table('goods')->where(['gid' => $gid])->find();
            if (!$goods_info) {
                throw new Exception('商品不存在');
            }
            if ($goods_info['goods_state'] != 1 || $goods_info['goods_verify'] != 1) {
                throw new Exception('商品已经下架');
            }
            $vendor_info = $this->table('vendor')->where(['vid' => $goods_info['vid']])->find();
            if(!$vendor_info){
                throw new Exception('店铺不存在');
            }
            if($vendor_info['store_state'] != 1){
                throw new Exception('店铺已关闭');
            }
            //检测活动的状态
            $pre_info = $this->table('presale,pre_goods')->join('left')->on('presale.pre_id=pre_goods.pre_id')->where([
                'presale.pre_id'=>$pre_id,
                'pre_goods.gid'=>$gid,
            ])->find();
            if (!$pre_info) {
                throw new Exception('活动不存在');
            }
            if($pre_info['pre_status'] != 1){
                throw new Exception('活动已关闭');
            }
            if($pre_info['pre_start_time'] > time()){
                throw new Exception('活动尚未开启');
            }
            if($pre_info['pre_end_time'] < time()){
                throw new Exception('活动已经结束');
            }
            //检测用户是已经买过
            $order_info = $this->table('pre_order')->where(['buyer_id'=>$member_id,'order_state'=>['in','20,30'],'pre_id'=>$pre_id,'gid'=>$gid])->find();
            if($order_info){
                throw new Exception('你已经参与过此活动定金,不能重复参加');
            }
            return ['status'=>200];
        } catch (Exception $e) {
            return ['status'=>255,'msg'=>$e->getMessage()];
        }
    }
    /*
    * 验证用户能否支付尾款
    * $gid 商品id
    * $pre_id 预售活动id
    * member_id 会员id
    */
    public function testMemberPayFinishOrder($gid,$pre_id,$member_id)
    {
        try {
            //检测店铺,商品状态
            $goods_info = $this->table('goods')->where(['gid' => $gid])->find();
            if (!$goods_info) {
                throw new Exception('商品不存在');
            }
            if ($goods_info['goods_state'] != 1 || $goods_info['goods_verify'] != 1) {
                throw new Exception('商品已经下架');
            }
            $vendor_info = $this->table('vendor')->where(['vid' => $goods_info['vid']])->find();
            if(!$vendor_info){
                throw new Exception('店铺不存在');
            }
            if($vendor_info['store_state'] != 1){
                throw new Exception('店铺已关闭');
            }
            //检测活动的状态
            $pre_info = $this->table('presale,pre_goods')->join('left')->on('presale.pre_id=pre_goods.pre_id')->where([
                'presale.pre_id'=>$pre_id,
                'pre_goods.gid'=>$gid,
            ])->find();
            if (!$pre_info) {
                throw new Exception('活动不存在');
            }
            if($pre_info['pre_status'] != 1){
                throw new Exception('活动已关闭');
            }
            if(!($pre_info['pre_end_time'] < time() && ($pre_info['pre_end_time']+($pre_info['pre_limit_time']*3600)) > time())){
                throw new Exception('不在支付尾款期限内');
            }
            //检测用户是已经买过
            $order_info = $this->table('pre_order')->where(['buyer_id'=>$member_id,'order_state'=>20,'pre_id'=>$pre_id,'gid'=>$gid])->find();
            if(!$order_info){
                throw new Exception('请先支付定金');
            }
            return ['status'=>200];
        } catch (Exception $e) {
            return ['status'=>255,'msg'=>$e->getMessage()];
        }
    }
    //下单生成定金订单,插入订单
    public function buy_step2($param)
    {
//        dd($param);die;
        $param['add_time'] = time();
        $order_id = $this->table('pre_order')->insert($param);
        if(!$order_id){
            throw new Exception('生成订单失败');
        }
        //生成订单号
        $order_sn = $this->makeOrderSn($order_id);
        $insert_all = $this->table('pre_order')->where(['order_id'=>$order_id])->update(['order_sn'=>$order_sn]);
        if(!$insert_all){
            throw new Exception('生成订单失败');
        }
        //减掉库存
        $res = $this->table('pre_goods')->where(['gid'=>$param['gid'],'pre_id'=>$param['pre_id']])->update(['goods_stock'=>['exp','goods_stock - '.$param['goods_num']]]);
        if(!$res){
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
        $order_model = M('pre_order','presale');
        $member_info = $this->table('member')->where(['member_id'=>$member_id])->find();
        $order_info = $this->table('pre_order')->where(['order_sn'=>$order_sn])->find();
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
            $data_pd['lg_desc'] = '预售活动支付定金，使用预存款，订单号: '.$order_info['order_sn'];
            $model_pd->changePd('order_pay',$data_pd);
            //修改订单状态
            $update = [
                'order_state'=>20,
                'first_time'=>time(),
                'payment_code'=>'predeposit',
            ];
            $res_update =  $order_model->edit(['order_id'=>$order_info['order_id']],$update);
            if(!$res_update){
                throw new  Exception('支付失败');
            }

            //取消这个活动未付款的订单
            $this->pre_cancelorder(['buyer_id'=>$order_info['buyer_id'],'order_state'=>10,'pre_id'=>$order_info['pre_id'],'gid'=>$order_info['gid']]);

        }elseif($order_info['order_state'] == 20){
            //交尾款
            $order_amount = $order_info['goods_price_finish'] * $order_info['goods_num'];
            if($order_amount > $member_info['available_predeposit']){
                throw new Exception('预存款余额不足');
            }
            $data_pd = array();
            $data_pd['member_id'] = $member_id;
            $data_pd['member_name'] = $member_info['member_name'];
            $data_pd['amount'] = $order_amount;
            $data_pd['order_sn'] = $order_info['order_sn'];
            $data_pd['lg_desc'] = '预售支付尾款，使用预存款，订单号: '.$order_info['order_sn'];
            $model_pd->changePd('order_pay',$data_pd);
            $res = $this->handleordertype_2($order_sn,['payment_code'=>'predeposit']);
            if(!$res){
                throw new Exception('支付失败');
            }
        }else{
            throw new Exception('支付失败');
        }
    }
    /*
     * 获取预售尾款
     * pre_id 预售活动id
     * gid 商品id
     * return 尾款
     */
    public function getPrePrice($pre_id,$gid)
    {
        $goods = $this->table('goods')->where(['gid'=>$gid])->find();
        $pre_goods = $this->table('pre_goods')->where(['pre_id'=>$pre_id,'gid'=>$gid])->find();
        if(!$pre_goods){
            return $goods['goods_price'];
        }
        $money = $pre_goods['pre_sale_price'] - $pre_goods['pre_deposit_price'];
        return $money>0?$money:0;
    }
    /*
     * 取消订单
     * condition
     */
    public function pre_cancelorder($condition)
    {
        $order_list = $this->table('pre_order')->where($condition)->select();
        foreach($order_list as $k=>$v){
                $res1 = $this->table('pre_order')->where(['order_id'=>$v['order_id']])->update(['order_state'=>0]);
                $res2 = $this->table('pre_goods')->where(['pre_id'=>$v['pre_id'],'gid'=>$v['gid']])->update(['goods_stock'=>['exp','goods_stock + '.$v['goods_num']]]);
            if(!$res1 || !$res2){
                throw new Exception('操作失败');
            }
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
        return (date('y',time()) % 9+1) . sprintf('%013d', $pay_id) . 'ys';
    }

    /*
 * 生成订单(用于支付尾款回调)
 */
    public function handleordertype_2($out_trade_no,$extend_data)
    {
        $order_model = M('pre_order','presale');

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
                $edit = $order_model->edit(['order_sn'=>$out_trade_no],$update);
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
                    'order_amount'=>($order_info['goods_price'] * $order_info['goods_num']) + ($order_info['goods_price_finish'] * $order_info['goods_num']),
                    'order_state'=>20,
                    'order_from'=>0,
                    'pre_order_id'=>$order_info['order_id'],
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
                    'goods_type'=>10,
                    'goods_yongjin'=>$goods_info['fenxiao_yongjin'],
                    'commis_rate'=>$this->getcommis_rate($goods_info),
                ];
                $rec_id = $model->table('order_goods')->insert($order_goods_inseret);
                if(!$rec_id){
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