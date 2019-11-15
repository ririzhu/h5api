<?php
/**
 * 拼团
 *
 */
defined('DYMall') or exit('Access Invalid!');
class pinCtl extends BaseHomeCtl {
	private $templatestate_arr;
	public function __construct() {
		parent::__construct();

	}

	public function index(){
	    $model_pin = M('pin');
        $tid = intval($_GET['tid']);
        $where['sld_parent_id'] = 0;
	    $pin_types = $model_pin->getPinTypes($where);
        Template::output('pin_types',$pin_types);
        $pin_type = $pin_types;

        $temp=array();
        foreach ($pin_type as $k=>$v){
            if($tid){
                if($tid==$v['id']){
                    $temp[$v['id']] = $v;
                }
            }else {
                $temp[$v['id']] = $v;
            }
        }
        $pin_type=$temp;


        if($tid) {
            $pin_list = $model_pin->getPinList(array('pin_type.id'=>$tid),8);
            $page_count = $model_pin->gettotalpage();
            $pin_type[$tid]['data'] = $pin_list;
            Template::output('page_count',$page_count);
        }else{
            foreach ($pin_type as $k=>$v){
                $pin_list = $model_pin->getPinList(array('pin_type.id'=>$v['id']),8);

                if(count($pin_list)>0) {
                    $pin_type[$k]['data'] = $pin_list;
                }else{
                    unset($pin_type[$k]);
                }
            }
        }

        Template::output('type_list',$pin_type);

		Template::showpage('pin_list');
	}

	public function ajax(){
        $model_pin = M('pin');
        $tid = intval($_GET['tid']);
        $now = intval($_GET['pn']);

        $return['data'] = $model_pin->getPinList(array('pin_type.id'=>$tid),8);
        if(count($return['data'])<1){
            $return['hasmore'] = false;
        }else{
            $return['hasmore'] = !($now == $model_pin->gettotalpage());
        }
        exit(json_encode($return));

    }


}
