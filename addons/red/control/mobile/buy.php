<?php
defined('DYMall') or exit('Access Invalid!');
class red_buyAdd extends mobileHomeCtl
{

    public function __construct()
    {
        parent::__construct();
    }

    public function confirm($buy_list){  //获取用户可使用优惠券
        $model_red = M('red');
        $member_info = $buy_list['member'];

        $goods_list =low_array_column($buy_list['store_cart_list'],'goods_list');

        //获得可用优惠券
        $condition['reduser_use'] = array( 'eq',0);
        $condition['redinfo_end'] = array( 'gt',TIMESTAMP);
        $condition['redinfo_start'] = array( 'lt',TIMESTAMP);
        $condition['reduser_uid'] = $member_info['member_id'];

        $red_list = $model_red->getRedUserList($condition);
        $red_list = $model_red->filter_red($goods_list,$red_list);

        $vendor_red_list = [];
        //循环优惠券把店铺优惠券排除
        foreach ($red_list as $k=>$v){
            if($v['red_vid'] && $v['red_vid']!=0){
                $vendor_red_list[$v['red_vid']][] = $v;
                unset($red_list[$k]);
            }
        }

        foreach ($buy_list['store_cart_list'] as $k=>$v){
            $buy_list['store_cart_list'][$k]['red'] = $vendor_red_list[$v['vid']];
        }

        $buy_list['red'] = $red_list;

        return $buy_list;
    }

    public function confirm_xcx($buy_list){  //获取用户可使用优惠券
        $model_red = M('red');
        $member_info = $buy_list['member'];

        $goods_list =low_array_column($buy_list['store_cart_list'],'goods_list');

        //获得可用优惠券
        $condition['reduser_use'] = array( 'eq',0);
        $condition['redinfo_end'] = array( 'gt',TIMESTAMP);
        $condition['redinfo_start'] = array( 'lt',TIMESTAMP);
        $condition['reduser_uid'] = $member_info['member_id'];
        $red_list = $model_red->getRedUserList($condition);
        $red_list = $model_red->filter_red($goods_list,$red_list);

        $vendor_red_list = [];
        //循环优惠券把店铺优惠券排除
        foreach ($red_list as $k=>$v){
            if($v['red_vid'] && $v['red_vid']!=0){
                $vendor_red_list[$v['red_vid']][] = $v;
                unset($red_list[$k]);
            }
        }

        foreach ($buy_list['store_cart_list'] as $k=>$v){
            $buy_list['store_cart_list'][$k]['red'] = $vendor_red_list[$v['vid']];
        }

        $buy_list['red'] = $red_list;

        return $buy_list;
    }




}
?>