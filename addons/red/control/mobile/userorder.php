<?php
defined('DYMall') or exit('Access Invalid!');
class red_userorderAdd extends mobileHomeCtl
{

    public function __construct()
    {
        parent::__construct();
    }

    public function order_info($red_id){  //获取用户可使用优惠券
        $model_red = M('red');
        $red_info = $model_red->getRedUserList(array('red_user.id'=>$red_id));
        if(count($red_info)>0){
            return '使用'.floatval($red_info[0]['redinfo_money']).'元优惠券';
        }else{
            return 0;
        }

    }

    public function order_info_xcx($red_id){  //获取用户可使用优惠券
        $model_red = M('red');
        $red_info = $model_red->getRedUserList(array('red_user.id'=>$red_id));
        if(count($red_info)>0){
            return '使用'.floatval($red_info[0]['redinfo_money']).'元优惠券';
        }else{
            return 0;
        }

    }


}
?>