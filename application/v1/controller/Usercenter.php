<?php
namespace app\v1\controller;
use app\v1\model\Area;
use app\v1\model\BrowserHistory;
use app\v1\model\Favorites;
use app\v1\model\Fenxiao;
use app\v1\model\Points;
use app\v1\model\Predeposit;
use app\v1\model\Goods;
use app\v1\model\User;
use think\db;

/**
 * SNS首页
 *
 */

class Usercenter extends Base {

	public function __construct(){
		parent::__construct();
	}

    /**
     * 个人中心
     * @return false|string
     */
	public function index(){
        if(!input("member_id")){
            $data['code'] = 10001;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }
        $member_id = input('member_id');

        $field = 'member_name,member_avatar';
        $member = new User();
        $param = [
            'member_id' =>$member_id,
        ];
        $member_info = $member->getMemberInfo($param,$field);
        if (empty($member_info)){
            $data['code'] = 10002;
            $data['message'] = "用户不存在";
            return json_encode($data,true);
        }

        //获取收藏的数量
        $favorites = new Favorites();
        $favorites_goods_count = $favorites->getFavoritesCount(array('f.member_id'=>$member_id),'goods');
        $favorites_store_count = $favorites->getFavoritesCount(array('f.member_id'=>$member_id),'store');
        $favorites_count = $favorites_goods_count + $favorites_store_count;
        $member_info['favorites_count'] = $favorites_count;

        //获取我的足迹数量
        $brower_history = new BrowserHistory();
        $history_count = $brower_history->getGoodsBrowseHistoryCount(array('member_id'=>$member_id));
        $member_info['history_count'] = $history_count;

		$data['code'] = 200;
		$data['message'] = '请求成功';
		$data['member_info'] = $member_info;
        echo json_encode($data,true);
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
            $data['code'] = 10001;
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
        if (empty($member_info)){
            $data['code'] = 10002;
            $data['message'] = "用户不存在";
            return json_encode($data,true);
        }
        $data['code'] = 200;
        $data['message'] = '请求成功';
        $data['member_info'] = $member_info;
        return json_encode($data,true);
    }

    /**
     * 用户足迹
     * @return false|string
     */
    public function memberBrowserHistory(){
        if(!input("member_id")){
            $data['code'] = 10001;
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
        $data['code'] = 200;
        $data['message'] = '请求成功';
//        $data['browser_list'] = $browser_list_new;
        $data['browser_list_new_date'] = $browser_list_new_date;
        return json_encode($data,true);
    }

    /**
     * 账户余额变动详情
     * @return false|string
     */
    public function memberPdLog()
    {
        if(!input("member_id")){
            $data['code'] = 10001;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }
        $member_id = input("member_id");
        $predeposit = new Predeposit();
        $param = [
            'lg_member_id' =>$member_id,
        ];
        $pd_log = $predeposit->getPdLogList($param);
        $data['code'] = 200;
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
            $data['code'] = 10001;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }
        $member_id = input("member_id");
        
        $member = new User();
        $member_condition = [
            'inviter_id' => $member_id,
            'inviter2_id' => $member_id,
            'inviter3_id' => $member_id,
        ];
        $member_field = 'member_id';
        $team = $member->getChildMember($member_condition,$member_field);
        $count = count($team);

        $fenxiao = new Fenxiao();
        $fenxiao_condition = [
            'reciver_member_id' =>$member_id,
        ];
        $fenxiao_field = 'yongjin,add_time,status';
        $fenxiao_list = $fenxiao->getCommissionInfo($fenxiao_condition,$fenxiao_field);

        $total_income = 0;
        $account = 0;
        $list = [];
        if (!empty($fenxiao_list)) {
            foreach ($fenxiao_list as $key => $val) {
                $list1['type'] = 1;
                $list1['amount'] = $val['yongjin'];
                $list1['add_time'] = $val['add_time'];
                $list1['status'] = $val['status'];
                if ($val['status'] == 1) {
                    $total_income += $val['yongjin'];
                    $account += $val['yongjin'];
                }
                $list[] = $list1;
            }
        }

        $predepoist = new Predeposit();
        $predepoist_codition = [
            'pdc_member_id' => $member_id,
        ];
        $predepoist_field = 'pdc_amount,pdc_add_time,pdc_payment_state';
        $pdcash_list = $predepoist->getPdCashList($predepoist_codition,$predepoist_field);
        if (!empty($pdcash_list)){
            foreach ($pdcash_list as $key => $val) {
                $list1['type'] = 2;
                $list1['amount'] = -$val['pdc_amount'];
                $list1['add_time'] = $val['pdc_add_time'];
                $list1['status'] = $val['pdc_payment_state'];
                if ($val['pdc_payment_state'] == 1) {
                    $account -= $val['pdc_amount'];
                }
                $list[] = $list1;
            }
        }
        //根据时间排序
        $add_time = array_column($list,'add_time');
        array_multisort($add_time,SORT_DESC,$list);

        foreach ($list as $key => $val){
            $list[$key]['add_time'] = date('m-d H:i:s',$val['add_time']);
        }

        $data['code'] = 200;
        $data['message'] = '请求成功';
        $data['list'] = $list;
        $data['count'] = $count;
        $data['account'] = $account;
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
            $data['code'] = 10001;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }
        $member_id = input("member_id");
        $points = new Points();
        $param = [
            'pl_memberid' =>$member_id,
        ];
        $points_list = $points->getPointList($param,'*','pl_addtime desc');

        $data['code'] = 200;
        $data['message'] = '请求成功';
        $data['points_list'] = $points_list;
        return json_encode($data,true);
    }

    /**
     * 我的团队
     * @return false|string
     */
    public function userTree()
    {
        if(!input("member_id")){
            $data['code'] = 10001;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }
        $member_id = input("member_id");

        $member = new User();
        $condition = [
            'inviter_id' => $member_id,
            'inviter2_id' => $member_id,
            'inviter3_id' => $member_id,
        ];
        $field= 'member_id,member_name,member_avatar,member_mobile,member_time';
        $child_member = $member->getChildMember($condition,$field);
        foreach ($child_member as $k => $v){
            $new_condition = [
                'inviter_id' => $v['member_id'],
                'inviter2_id' => $v['member_id'],
                'inviter3_id' => $v['member_id'],
            ];
            $new_child = $member->getChildMember($new_condition,$field);
            $child_member[$k]['num'] = count($new_child);
            $child_member[$k]['member_time'] = date("Y-m-d H:i:s",$v['member_time']);
        }

        $data['code'] = 200;
        $data['message'] = '请求成功';
        $data['list'] = $child_member;
        return json_encode($data,true);
    }

    /**
     * 地区设置
     * @return false|string
     */
    public function worldArea()
    {
        $area = new Area();

        $condition['area_parent_id'] = 0;
        $field = 'area_id,name';
        $area_list = $area->getWorldAreaList($condition,$field);
        return json_encode($area_list,true);
    }


}
