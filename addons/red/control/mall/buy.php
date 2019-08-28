<?php
include(dirname(__FILE__)."/../../model/red.php");
class red extends \app\V1\controller\Base
{

    public function __construct()
    {
        parent::__construct();
    }

    public static function confirm($buy_list){  //获取用户可使用平台优惠券

        $model_red = new redModel();
        $member_info = $buy_list['member'];

        $goods_list = array();
        foreach ($buy_list['store_cart_list'] as $k=>$v){
            $goods_list[$k] = $v;
        }

        //获得可用优惠券
        $condition['reduser_use'] = array( 'eq',0);
        $condition['redinfo_end'] = array( 'gt',TIMESTAMP);
        $condition['redinfo_start'] = array( 'lt',TIMESTAMP);
        $condition['reduser_uid'] = $member_info['member_id'];
        $conditions = "reduser_use = 0 and redinfo_end >".TIMESTAMP." and redinfo_start <".TIMESTAMP ." and reduser_uid = ".$member_info['member_id'];
//        $condition['red.vid'] = 0;
        $red_list = $model_red->getRedUserList($conditions);
        $red_list = $model_red->filter_red($goods_list,$red_list);

        $vendor_red_list = [];
        //循环优惠券把店铺优惠券排除
        foreach ($red_list as $k=>$v){
            if($v['red_vid'] && $v['red_vid']!=0){
                $vendor_red_list[$v['red_vid']][] = $v;
                unset($red_list[$k]);
            }
        }


        $newlist['red'] = $red_list;
        $newlist['vred'] = $vendor_red_list;

        return $newlist;
    }


}
?>