<?php
namespace app\v1\controller;
use app\v1\model\BrowserHistory;
use app\v1\model\Favorites;
use app\v1\model\Fenxiao;
use app\v1\model\GoodsActivity;
use app\v1\model\Grade;
use app\v1\model\Points;
use app\v1\model\Predeposit;
use app\v1\model\SnsVisitor;
use app\v1\model\UserCart;
use app\v1\model\UserOrder;
use app\v1\model\Goods;
use app\v1\model\User;
use think\db;
use think\facade\Request;

/**
 * SNS首页
 *
 */
defined('DYMall') or exit('Access Invalid!');

class Usercenter extends Base {
	const MAX_RECORDNUM = 20;//允许插入新记录的最大条数(注意在sns中该常量是一样的，注意与member_snshome中的该常量一致)

	public function __construct(){
		parent::__construct();
		/*Template::output('relation','3');//为了跟home页面保持一致所以输出此变量
		Language::read('member_sns');
		//允许插入新记录的最大条数
		Template::output('max_recordnum',self::MAX_RECORDNUM);*/
	}

    /**
     * 个人中心
     * @return false|string
     */
	public function index(){
        if(!input("member_id")){
            $data['error_code']=10016;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }
        $member_id = input('member_id');
        //获取我的订单的3条记录
        $model_order = new UserOrder();
        $condition = array();
        $condition['bbc_order.buyer_id'] = $member_id;
        $condition['order_state'] = 10;
        $order_list = $model_order->getOrderList($condition, '', '*', 'bbc_order.order_id desc',3, array('order_common','order_goods','store'));
        //获取收藏的商品
        $favorites_model = new Favorites();
        $favorites_list = $favorites_model->getGoodsFavoritesList(array('member_id'=>$member_id), '*',30);
        if (!empty($favorites_list)) {
            $favorites_id = array();//收藏的商品编号
            foreach ($favorites_list as $key => $favorites) {
                $fav_id = $favorites['fav_id'];
                $favorites_id[] = $favorites['fav_id'];
                $favorites_key[$fav_id] = $key;
            }
            $goods_model = new Goods();
            $field = 'goods.gid,goods.goods_name,goods.vid,goods.goods_image,goods.goods_price,goods.evaluation_count,goods.goods_salenum,goods.goods_collect,vendor.store_name,vendor.member_id,vendor.member_name,vendor.store_qq,vendor.store_ww,vendor.store_domain';
            $goods_list = $goods_model->getGoodsStoreList(array('gid' => $favorites_id), $field);
            if (!empty($goods_list)) {
                foreach ($goods_list as $key => $fav) {
                    $fav_id = $fav['gid'];
                    $fav['goods_member_id'] = $fav['member_id'];
                    $key = $favorites_key[$fav_id];
                    $favorites_list[$key]['goods'] = $fav;
                }
            }
        }
        //获取我的足迹 20条
        $model = new BrowserHistory();
        $where = array();
        $where['member_id'] = $member_id;
        $browselist_tmp = $model->getGoodsbrowseList($where, '', 0, 20, 'browsetime desc');
        $browselist = array();
        foreach ((array)$browselist_tmp as $k=>$v){
            $browselist[$v['gid']] = $v;
        }
        $browselist_new = array();
        if ($browselist){
            $goods_list_tmp = $goods_model->getGoodsList(array('gid' => array_keys($browselist)), 'gid, goods_name, goods_price,goods_promotion_price,goods_promotion_type, goods_marketprice, goods_image, vid, gc_id, gc_id_1, gc_id_2, gc_id_3');

            $goods_list = array();
            foreach ((array)$goods_list_tmp as $v){
                $goods_list[$v['gid']] = $v;
            }
            foreach ($browselist as $k=>$v){
                if (isset($goods_list[$k])){
                    $tmp = $goods_list[$k];
                    $tmp["browsetime"] = $v['browsetime'];
                    if (date('Y-m-d',$v['browsetime']) == date('Y-m-d',time())){
                        $tmp['browsetime_day'] = '今天';
                    } elseif (date('Y-m-d',$v['browsetime']) == date('Y-m-d',(time()-86400))){
                        $tmp['browsetime_day'] = '昨天';
                    } else {
                        $tmp['browsetime_day'] = date('Y年m月d日',$v['browsetime']);
                    }
                    $tmp['browsetime_text'] = $tmp['browsetime_day'].date('H:i',$v['browsetime']);
                    $browselist_new[] = $tmp;
                }
            }
        }

        //		查询会员等级--新增 --start 2017.7.13
        $model_member = new User();
        $a = $model_member->checkLogin($condition);
        $member_info = $model_member->getMemberInfoByID($member_id);

        //当前登录会员等级信息
//        $membergrade_info = $model_member->getOneMemberGrade($member_info['member_growthvalue'], true);
//        $member_info = array_merge($member_info, $membergrade_info);
		$grade_model = new Grade();
		$grade = $grade_model->getmembergrade($member_id,LANG_TYPE);

        //获得会员升级进度
//        $membergrade_arr = $model_member->getMemberGradeArr(true, $member_info['member_growthvalue'],$member_info['level']);
//        Template::output('membergrade_arr',$grade);
        $data['membergrade_arr'] = $grade;
//		查询会员等级--新增 --end

        //获取购物车里的数据  6条
        $model_cart	= new UserCart();
        //取出购物车信息
        $cart_lists	= $model_cart->listCart('db',array('buyer_id'=>$member_id));
//        Template::output('cart_goods_list',$cart_lists);
        $data['cart_goods_list'] = $cart_lists;
        //获取购物车数量
        $cart_num = $model_cart->getCartNum('db',array('buyer_id'=>$member_id));
//        Template::output('cart_goods_num',$cart_num);
        $data['cart_goods_num'] = $cart_num;
        //获取收藏的店铺 16条
        $favorites_list_store = $favorites_model->getStoreFavoritesList(array('f.member_id'=>$member_id), '*', 16);
        //获取优惠券数量(未用的没有过期的)
        $voucher_count= Db::name('red_user')->where(array('reduser_uid'=>$member_id,'reduser_use'=>0,'redinfo_end'=>array('gt',time())))->count();
//        Template::output('voucher_count',$voucher_count);
        $data['voucher_count'] = $voucher_count;
		//查询会员信息
		//$this->get_member_info();
		//查询谁来看过我
		$visitor_model = new SnsVisitor();
		$visitme_list = $visitor_model->getVisitorList(array('v_ownermid'=>"{$member_id}"),9);
		if (!empty($visitme_list)){
			foreach ($visitme_list as $k=>$v){
				$v['adddate_text'] = $this->formatDate($v['v_addtime']);
				$v['addtime_text'] = @date('H:i',$v['v_addtime']);
				$visitme_list[$k] = $v;
			}
		}
		//查询我访问过的人
		$visitother_list = $visitor_model->getVisitorList(array('v_mid'=>"{$member_id}"),9);
		if (!empty($visitother_list)){
			foreach ($visitother_list as $k=>$v){
				$v['adddate_text'] = $this->formatDate($v['v_addtime']);
				$visitother_list[$k] = $v;
			}
		}
        //我的订单 3条
//        Template::output('order_list',$order_list);
        $data['order_list'] = $order_list;
        //我的收藏 30条
//        Template::output('favorites_list',$favorites_list);
        $data['favorites_list'] = $favorites_list;
        //我的足迹 20条
//        Template::output('browselist_new',$browselist_new);
        $data['browselist_new'] = $browselist_new;
        //收藏的店铺 16条
//        Template::output('favorites_list_store',$favorites_list_store);
        $data['favorites_list_store'] = $favorites_list_store;
//        print_r($favorites_list_store);
		/*Template::output('visitme_list',$visitme_list);
		Template::output('visitother_list',$visitother_list);*/
        $data['visitme_list'] = $visitme_list;
        $data['visitother_list'] = $visitother_list;
		//信息输出
//		Template::output('sldcode',substr(md5(MALL_SITE_URL.$_GET['app'].$_GET['mod']),0,8));
        $data['sldcode'] = substr(md5(MALL_SITE_URL.Request::controller().Request::action()),0,8);
//		Template::output('menu_sign','snsindex');
        $data['menu_sign'] = 'snsindex';
		echo json_encode($data);
	}
	private function formatDate($time){
		$handle_date = @date('Y-m-d',$time);//需要格式化的时间
		$reference_date = @date('Y-m-d',time());//参照时间
		$handle_date_time = strtotime($handle_date);//需要格式化的时间戳
		$reference_date_time = strtotime($reference_date);//参照时间戳
		if ($reference_date_time == $handle_date_time){
			$timetext = @date('H:i',$time);//今天访问的显示具体的时间点
		}elseif (($reference_date_time-$handle_date_time)==60*60*24){
			$timetext = Lang('昨天');
		}elseif ($reference_date_time-$handle_date_time==60*60*48){
			$timetext = Lang('前天');
		}else {
			$month_text = Lang('月');
			$day_text = Lang('日');
			$timetext = @date("m{$month_text}d{$day_text}",$time);
		}
		return $timetext;
	}

    /**
     * 用户个人信息
     * @return false|string
     */
	public function memberInfo(){
        if(!input("member_id")){
            $data['code']=1;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }
        $member_id = input("member_id");
        $field = 'member_name,member_avatar,member_sex,member_birthday,member_areaid,member_cityid,member_provinceid,member_countryid,member_areainfo,member_area_detail';
        $member = new User();
        $param = [
            'member_id' =>$member_id,
        ];
        $member_info = $member->getMemberInfo($param,$field);
        /*if (empty($member_info)){
            $data['code']=2;
            $data['message'] = "用户不存在";
            return json_encode($data,true);
        }*/
        $data['code'] = 0;
        $data['message'] = '成功';
        $data['member_info'] = $member_info;
        return json_encode($data,true);
    }

    /**
     * 用户足迹
     * @return false|string
     */
    public function memberBrowserHistory(){
        if(!input("member_id")){
            $data['code']=1;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }
        $member_id = input("member_id");
	    $history = new BrowserHistory();
        $param = [
            'member_id' =>$member_id,
        ];
	    $member_history = $history->getGoodsBrowseHistory($param,'*',1,10,'browsetime desc');
        $browser_list_new = [];
        $browser_list_new_date = [];
	    if ($member_history){
	        $goods = new Goods();
	        foreach ($member_history as $k => $v){
	            $browserList[] = $v['gid'];
            }
	        $goods_field = 'gid, goods_name, goods_price,goods_promotion_price,goods_promotion_type, goods_marketprice, goods_image, vid, gc_id, gc_id_1, gc_id_2, gc_id_3';
	        $goods_list = $goods->getGoods(array('gid' => $browserList),$goods_field);

            foreach ($member_history as $k=>$v){
                if ($goods_list[$v['gid']]){
                    $tmp = $goods_list[$v['gid']];
                    $tmp["browsetime"] = $v['browsetime'];
                    if (date('Y-m-d',$v['browsetime']) == date('Y-m-d',time())){
                        $tmp['browsetime_day'] = lang('今天');
                    } elseif (date('Y-m-d',$v['browsetime']) == date('Y-m-d',(time()-86400))){
                        $tmp['browsetime_day'] =  lang('昨天');
                    } else {
                        $tmp['browsetime_day'] = date('Y/m/d',$v['browsetime']);
                    }
                    $tmp['browsetime_text'] = $tmp['browsetime_day'].date('H:i',$v['browsetime']);
                    $browser_list_new[] = $tmp;
                }
            }

            //将浏览记录按照时间重新组数组
            foreach ($browser_list_new as $kk=>$vv){
                $browser_list_new_date[$vv['browsetime_day']][] = $vv;
            }
        }
        $data['code'] = 0;
        $data['message'] = '成功';
        $data['browser_list'] = $browser_list_new;
//        $data['browser_list_new_date'] = $browser_list_new_date;
        return json_encode($data,true);
    }

    /**
     * 账户余额变动详情
     * @return false|string
     */
    public function memberPdLog()
    {
        if(!input("member_id")){
            $data['code']=1;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }
        $member_id = input("member_id");
        $predeposit = new Predeposit();
        $param = [
            'lg_member_id' =>$member_id,
        ];
        $pd_log = $predeposit->getPdLogList($param);
        $data['code'] = 0;
        $data['message'] = '请求成功';
        $data['pd_log'] = $pd_log;
        return json_encode($data,true);
    }

    /**
     * 我的收益
     * @return false|string
     */
    public function memberIncome()
    {
        if(!input("member_id")){
            $data['code']=1;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }
        $member_id = input("member_id");
        $fenxiao = new Fenxiao();
        $param = [
            'reciver_member_id' =>$member_id,
        ];
        $fenxiao_list = $fenxiao->getCommissionInfo($param);
        $total_income = 0;
        foreach ($fenxiao_list as $key => $val){
            if($val['status'] == 1){
                $total_income += $val['yongjin'];
            }
        }
        $data['code'] = 0;
        $data['message'] = '请求成功';
        $data['income_list'] = $fenxiao_list;
        $data['total_income'] = $total_income;
        return json_encode($data,true);
    }

    /**
     * 我的积分
     * @return false|string
     */
    public function memberPoints()
    {
        if(!input("member_id")){
            $data['code']=1;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }
        $member_id = input("member_id");
        $points = new Points();
        $param = [
            'pl_memberid' =>$member_id,
        ];
        $points_list = $points->getPointList($param,'*','pl_addtime desc');

        $data['code'] = 0;
        $data['message'] = '请求成功';
        $data['points_list'] = $points_list;
        return json_encode($data,true);
    }


}
