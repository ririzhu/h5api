<?php
/**
 * 首单优惠
 *
 */
defined('DYMall') or exit('Access Invalid!');
class firstModel extends Model{


    public function __construct() {
        parent::__construct('first_discount');
    }

    public function getInfo($vid,$cid){
        $re = $this->table('first_discount')->where(['vid'=>$vid,'cid'=>$cid])->find();
//        dd($this->getLastSql());
//        dd($re);
        return $re;
    }

    public function handle_buy_list($store_cart_list,$member_id){

        $_first = [];

        foreach ($store_cart_list as $k=>$v){
            $_cids = [];
            foreach ($v as $vv){
                $_cids[] = $vv['goods_commonid'];
            }
            $_first[$k] = $this->have_qualified($member_id,$_cids,$k);
        }

        foreach ($store_cart_list as $k => $v) {

            //表示订单中产品，在店铺中有免单设置
            if($_first[$k]) {

                $this_reduct = null;

                foreach ($v as $kk => $vv) {
                    //如果该产品属于首单免费产品

                    if ($vvv = $_first[$k][$vv['goods_commonid']]) {

                        //给店铺免单赋值
                        if($this_reduct == null){
                            $this_reduct = $vvv['reduction'];
                        }
//                        dd('余额剩'.$this_reduct);
                        if( $this_reduct>0){
                            if($vv['goods_price']>=$this_reduct){
//                                dd('余额不够');
//                                dd('产品原价'.$store_cart_list[$k][$kk]['goods_price']);
                                $store_cart_list[$k][$kk]['goods_price'] -= $this_reduct;
                                $store_cart_list[$k][$kk]['show_price'] -= $this_reduct;
                                $store_cart_list[$k][$kk]['first'] = $this_reduct;
//                                dd('抵扣'.$store_cart_list[$k][$kk]['first']);
//                                dd('产品剩 '.$store_cart_list[$k][$kk]['goods_price']);
                                $this_reduct=0;
                            }else{
//                                dd('余额够');
//                                dd('产品原价'.$store_cart_list[$k][$kk]['goods_price']);
                                $this_reduct-=$store_cart_list[$k][$kk]['goods_price'];
                                $store_cart_list[$k][$kk]['first'] = $store_cart_list[$k][$kk]['goods_price'];
//                                dd('抵扣'.$store_cart_list[$k][$kk]['first']);
//                                dd('产品剩 0');
                                $store_cart_list[$k][$kk]['goods_price'] =0;
                                $store_cart_list[$k][$kk]['show_price'] =0;
                            }
                        }
                    }
                }
            }
        }


        return $store_cart_list;

    }

    public function have_qualified($member_id,$cid,$vid){

        $where['buyer_id'] = $member_id;
        $where['order_state'] = ['neq',0];
        $where['vid'] = $vid;
        $have = $this->table('order')->where($where)->count();

        if($have>0){
            return false;
        }

        $where = [];
        $where['cid'] = ['in',join(',',$cid)];
        $where['vid'] = $vid;


        $have = $this->table('first_discount')->where($where)->key('cid')->select();

        return $have;
    }


}
