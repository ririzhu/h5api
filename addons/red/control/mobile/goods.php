<?php
defined('DYMall') or exit('Access Invalid!');
class red_goodsAdd extends mobileHomeCtl
{

    public function __construct()
    {
        parent::__construct();
    }

    public function goods_detail($arr){


        $member_info = $arr['member'];
        $goods_info = $arr['goods_info'];

        $_GET['vid'] = $goods_info['vid'];

        $model_red = M('red');
        $condition['red_type'] = array('neq','3');
        $condition['red_status'] = 1;
        $condition['red_front_show'] = 1;
        $condition['red_receive_start'] = array('lt',TIMESTAMP);
        $condition['red_receive_end'] = array('gt',TIMESTAMP);

        //判断不是自营店的话
        $own_ids = Model('vendor')->getOwnShopIds();
        if(!in_array($goods_info['vid'],$own_ids)){
            $condition['redinfo_self'] = 0;
        }
        $condition['red.red_vid'] = array('in','0,'.$goods_info['vid']);

        //判断使用范围
        $gid = $goods_info['gid'];
        $cid = $goods_info['gc_id_1'];
        $condition['redinfo_type'] = array('exp',"case redinfo_type when 1 then FIND_IN_SET('$cid',redinfo_ids) when 2 then FIND_IN_SET('$gid',redinfo_ids) else 1 end");

        //判断是否参与其他活动
        if( Model('goods')->getOtherActivity($gid,$goods_info['goods_commonid'],TIMESTAMP,TIMESTAMP+1,'zhuanxiang')!=0){
            $condition['redinfo_together'] = 1;
        }

        $red_list = $model_red->getRedLingList($member_info['member_id'],$condition);

        $red_list = $model_red->getUseInfo($red_list);

        $arr['red'] = array_values($red_list);

        unset($arr['member']);
        return $arr;
    }

    public function goods_detail_xcx($arr){
        $member_info = $arr['member'];
        $goods_info = $arr['goods_info'];

        $model_red = M('red');
        $condition['red_type'] = array('neq','3');
        $condition['red_status'] = 1;
        $condition['red_front_show'] = 1;
        $condition['red_receive_start'] = array('lt',TIMESTAMP);
        $condition['red_receive_end'] = array('gt',TIMESTAMP);

        //判断不是自营店的话
        $own_ids = Model('vendor')->getOwnShopIds();
        if(!in_array($goods_info['vid'],$own_ids)){
            $condition['redinfo_self'] = 0;
        }

        //判断使用范围
        $gid = $goods_info['gid'];
        $cid = $goods_info['gc_id_1'];
        $condition['redinfo_type'] = array('exp',"case redinfo_type when 1 then FIND_IN_SET('$cid',redinfo_ids) when 2 then FIND_IN_SET('$gid',redinfo_ids) else 1 end");

        //判断是否参与其他活动
        if( Model('goods')->getOtherActivity($gid,$goods_info['goods_commonid'],TIMESTAMP,TIMESTAMP+1,'zhuanxiang')!=0){
            $condition['redinfo_together'] = 1;
        }

        $red_list = $model_red->getRedLingList($member_info['member_id'],$condition);

        $red_list = $model_red->getUseInfo($red_list);

        $arr['red'] = array_values($red_list);

        unset($arr['member']);
        return $arr;
    }
}
