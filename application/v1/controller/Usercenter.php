<?php
namespace app\v1\controller;
use app\v1\model\Area;
use app\v1\model\BrowserHistory;
use app\v1\model\Favorites;
use app\v1\model\Fenxiao;
use app\v1\model\LangSites;
use app\v1\model\Points;
use app\v1\model\Predeposit;
use app\v1\model\Goods;
use app\v1\model\Red;
use app\v1\model\SsysYj;
use app\v1\model\User;
use app\v1\model\Sms;
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
	public function index()
    {
        if(!input("member_id")){
            $data['code'] = 10001;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }
        $member_id = input('member_id');

        $field = 'member_name,member_avatar,available_predeposit';
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

        //获取优惠券数
        $red = new Red();
        $red_condition[] = ['reduser_use','eq',0];
        $red_condition[] = ['redinfo_end','gt',TIMESTAMP];
        $red_condition[] = ['reduser_uid','eq',$member_id];
        $red_count = $red->getRedUserCount($red_condition);
        $member_info['red_count'] = $red_count;

		$data['code'] = 200;
		$data['message'] = '请求成功';
		$data['member_info'] = $member_info;
        echo json_encode($data,true);
	}

    /**
     * 用户个人信息
     * @return false|string
     */
	public function memberInfo()
    {
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
    public function memberBrowserHistory()
    {
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
	    $member_history = $history->getGoodsBrowseHistory($param,'*','browsetime desc');
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
//                    $tmp["browsetime"] = $v['browsetime'];
                    if (date('Y-m-d',$v['browsetime']) == date('Y-m-d',time())){
                        $tmp['browsetime_day'] = lang('今天');
                    } elseif (date('Y-m-d',$v['browsetime']) == date('Y-m-d',(time()-86400))){
                        $tmp['browsetime_day'] =  lang('昨天');
                    } else {
                        $tmp['browsetime_day'] = date('Y/m/d',$v['browsetime']);
                    }
//                    $tmp['browsetime_text'] = $tmp['browsetime_day'].date('H:i',$v['browsetime']);
                    $browser_list_new[] = $tmp;
                }
            }

            //重组数组
            foreach ($browser_list_new as $kk=>$vv){
                $browser_list_new_date[$vv['browsetime_day']][] = $vv;
            }
        }
        $data['code'] = 200;
        $data['message'] = '请求成功';
//        $data['browser_list'] = $browser_list_new;
        $data['browser_list'] = $browser_list_new_date;
        return json_encode($data,true);
    }

    /**
     * 删除足迹
     * @return false|string
     */
    public function memberBrowserHistoryDelete()
    {
        if(!input('member_id') || !input('gid')){
            $data['code'] = 10001;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }
        $gid = explode(',',input('gid'));

        $history = new BrowserHistory();
        $condition = [
            'gid' => $gid,
            'member_id' => input('member_id'),
        ];
        $del = $history->delGoodsbrowseHistory($condition);
        if ($del){
            $data['code'] = 200;
            $data['message'] = '删除成功';
            return json_encode($data,true);
        }else{
            $data['code'] = 10002;
            $data['message'] = '删除失败，请检查是否存在该记录';
            return json_encode($data,true);
        }
    }

    /**
     * 账户余额
     * @return false|string
     */
    public function memberBalance()
    {
        if(!input('member_id')){
            $data['code'] = 10001;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }
        $member_id = input('member_id');
        $member = new User();
        $condition = [
            'member_id' =>$member_id,
        ];
        $member_info = $member->getMemberInfo($condition,'available_predeposit');
        if (empty($member_info)){
            $data['code'] = 10002;
            $data['message'] = "用户不存在";
            return json_encode($data,true);
        }

        $data['code'] = 200;
        $data['message'] = '请求成功';
        $data['available_predeposit'] = floatval($member_info['available_predeposit']);
        return json_encode($data,true);
    }

    /**
     * 零钱明细
     * @return false|string
     */
    public function memberBalanceDetails()
    {
        if(!input("member_id")){
            $data['code'] = 10001;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }
        $member_id = input("member_id");

        $predeposit = new Predeposit();
        $condition = [
            'lg_member_id' =>$member_id,
        ];
        $field = 'lg_id,lg_type,lg_av_amount,lg_freeze_amount,lg_add_time,lg_desc';
        $pd_log = $predeposit->getPdLog($condition,$field,'lg_add_time desc');
        foreach ($pd_log as $key => $val){
            $pd_log[$key]['lg_add_time'] = date('Y-m-d',$val['lg_add_time']);
        }
        $data['code'] = 200;
        $data['message'] = '请求成功';
        $data['list'] = $pd_log;
        return json_encode($data,true);
    }

    /**
     * 我的收益(应该要改)
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

        $condition = [
            'member_id' =>$member_id,
        ];
        $member_info = $member->getMemberInfo($condition,'available_predeposit');

        $member_condition = [
            'inviter_id' => $member_id,
            'inviter2_id' => $member_id,
            'inviter3_id' => $member_id,
        ];
        $member_field = 'member_id';
        $team = $member->getChildMember($member_condition,$member_field);
        $count = count($team);

        $model_fenxiao = new Fenxiao();
        $fenxiao_condition = [
            'reciver_member_id' =>$member_id,
        ];
        $fenxiao_field = 'yongjin,add_time,status';
        $fenxiao_list = $model_fenxiao->getCommissionInfo($fenxiao_condition,$fenxiao_field);

        $total_income = 0;
        $account = 0;
        $list = [];
        if (!empty($fenxiao_list)) {
            foreach ($fenxiao_list as $key => $val) {
                $arr['type'] = 1;
                $arr['des'] = date('Ym').'月佣金';
                $arr['amount'] = $val['yongjin'];
                $arr['add_time'] = $val['add_time'];
                $arr['status'] = $val['status'];
                if ($val['status'] == 1) {
                    $total_income += $val['yongjin'];
                    $account += $val['yongjin'];
                }
                $list[] = $arr;
            }
        }

        $model_yj = new SsysYj();
        $cash_codition = [
            'pdc_member_id' => $member_id,
        ];
        $cash_field = 'pdc_amount,pdc_add_time,pdc_payment_state';
        $yj_cash_list = $model_yj->getPdCashList($cash_codition,$cash_field);
        if (!empty($yj_cash_list)){
            foreach ($yj_cash_list as $key => $val){
                $arr['type'] = 2;
                $arr['des'] = date('Ym').'月提现';
                $arr['amount'] = -$val['pdc_amount'];
                $arr['add_time'] = $val['pdc_add_time'];
                $arr['status'] = $val['pdc_payment_state'];
                if ($val['pdc_payment_state'] == 1){
                    $account -= $val['pdc_amount'];
                }
                $list[] = $arr;
            }
        }

        //根据时间排序
        $add_time = array_column($list,'add_time');
        array_multisort($add_time,SORT_DESC,$list);

        foreach ($list as $key => $val){
            $list[$key]['add_time'] = date('m-d H:i',$val['add_time']);
        }

        $data['code'] = 200;
        $data['message'] = '请求成功';
        $data['list'] = $list;
        $data['count'] = $count;
//        $data['available_yj'] = floatval($member_info['available_predeposit']);
        $data['available_yj'] = $account;
        $data['total_income'] = $total_income;
        return json_encode($data,true);
    }

    /**
     * 我的积分
     * @return false|string
     */
    public function memberPoints()
    {
        if(!input('member_id') || !input('points_type')){
            $data['code'] = 10001;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }
        $member_id = input("member_id");
        $points_type = input('points_type');

        $member = new User();
        $member_condition = [
            'member_id' =>$member_id,
        ];
        $member_info = $member->getMemberInfo($member_condition,'member_points');
        if (empty($member_info)){
            $data['code'] = 10002;
            $data['message'] = "用户不存在";
            return json_encode($data,true);
        }

        $points = new Points();
        $points_condition[] = ['pl_memberid','eq',$member_id];
        if ($points_type == 1){
            $points_condition[] = ['pl_points','gt',0];
        }elseif ($points_type == 2){
            $points_condition[] = ['pl_points','lt',0];
        }
        $points_field = 'pl_id,pl_points,pl_addtime,pl_desc';
        $points_list = $points->getPointList($points_condition,$points_field,'pl_addtime desc');
        foreach ($points_list as $key => $val){
            $points_list[$key]['pl_addtime'] = date('Y-m-d H:i:s',$val['pl_addtime']);
        }

        $data['code'] = 200;
        $data['message'] = '请求成功';
        $data['points'] = $member_info['member_points'];
        $data['points_list'] = $points_list;
        return json_encode($data,true);
    }

    /**
     * 我的团队
     * @return false|string
     */
    public function userTree()
    {
        if(!input('member_id')){
            $data['code'] = 10001;
            $data['message'] = lang('缺少参数');
            return json_encode($data,true);
        }
        $member_id = input('member_id');

        $member = new User();
        if (input('type') == 1){
            $condition = [
                'inviter_id' => $member_id,
            ];
        }elseif (input('type') == 2){
            $condition = [
                'inviter2_id' => $member_id,
                'inviter3_id' => $member_id,
            ];
        }else {
            $condition = [
                'inviter_id' => $member_id,
                'inviter2_id' => $member_id,
                'inviter3_id' => $member_id,
            ];
        }
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
     * 语言设置
     * @return false|string
     */
    public function langList()
    {
        $lang = new LangSites();

        $condition['state'] = 1;
        $field = 'id,lang,lang_name_ch,lang_name';
        $lang_list = $lang->getlist($condition,$field);
        return json_encode($lang_list,true);
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

    /**
     * 编辑用户资料
     * @return false|string
     */
    public function updateInfo()
    {
        if(!input("member_id")){
            $data['code'] = 10001;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }
        $member = new User();

        $member_array = [];
        if (input('member_avatar')){
            $member_array['member_avatar']	= input('member_avatar');
        }
        if (input('member_name')) {
            $member_array['member_name'] = input('member_name');
        }
        if (input('member_birthday')) {
            $member_array['member_birthday'] = input('birthday');
        }
        if (input('member_sex')) {
            $member_array['member_sex'] = input('member_sex');
        }
        if (input('member_areaid')) {
            $member_array['member_areaid'] = input('area_id');
        }
        if (input('member_cityid')) {
            $member_array['member_cityid'] = input('city_id');
        }
        if (input('member_provinceid')) {
            $member_array['member_provinceid'] = input('province_id');
        }
        if (input('member_countryid')) {
            $member_array['member_countryid'] = input('country_id');
        }
        if (input('member_areainfo')) {
            $member_array['member_areainfo'] = input('area_info');
        }

        $update = $member->updateMember($member_array,input('member_id'));

        if ($update){
            $data['code'] = 200;
            $data['message'] = '编辑成功';
            return json_encode($data,true);
        }else{
            $data['code'] = 10002;
            $data['message'] = '编辑失败，请重试';
            return json_encode($data,true);
        }
    }

    /**
     * 修改密码
     * @return false|string
     */
    public function updatePassword()
    {
        if(!input("member_id")
            //|| !input("mobile")
            || !input("snscode")
            || !input("password")
            || !input("new_password")
            || !input("new_confirm_password")
        ){
            $data['code'] = 10001;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }

        if (trim(input('password')) == trim(input('new_password'))){
            $data['code'] = 10002;
            $data['message'] = lang("密码不能与原密码重复");
            return json_encode($data,true);
        }

        if (trim(input('new_password')) != trim(input('new_confirm_password'))){
            $data['code'] = 10003;
            $data['message'] = lang("两次输入的密码不一致");
            return json_encode($data,true);
        }

        $member = new User();

        $member_condition = [
            'member_id' => input('member_id'),
            'member_mobile' => input('mobile'),
            'member_passwd' => md5(trim(input('password'))),
        ];
        $member_info = $member->getMemberInfo($member_condition,'member_id');
        if (empty($member_info)){
            $data['code'] = 10005;
            $data['message'] = "绑定的手机号或原始密码错误";
            return json_encode($data,true);
        }

//        $countryCode = input("country_code")?86:input("country_code");
//        $phone = $countryCode.input("mobile");
        $phone = input('mobile');
        $captcha = input('snscode');
        $condition = array();
        $condition['log_phone'] = $phone;
        $condition['log_captcha'] = $captcha;
        $condition['log_type'] = 3;
        $model_sms_log = new Sms();
        $sms_log = $model_sms_log->getSmsInfo($condition);
        if(empty($sms_log) || ($sms_log['add_time'] < TIMESTAMP-1800)) {//半小时内进行验证为有效
            $data['code'] = 10004;
            $data['message'] = '动态码错误或已过期，重新输入';
            return json_encode($data,true);
        }

        $param = [
            'member_passwd'=>md5(trim(input('new_password')))
        ];
        $update = $member->updateMember($param,input('member_id'));
        
        if ($update){
            $data['code'] = 200;
            $data['message'] = '密码修改成功';
            return json_encode($data,true);
        }else{
            $data['code'] = 10006;
            $data['message'] = '密码修改失败，请重试';
            return json_encode($data,true);
        }
    }

    /**
     * 修改手机号
     * @return false|string
     */
    public function updatePhone_1()
    {
        if (!input('member_id')
            //|| !input("mobile")
            || !input("snscode")
        ){
            $data['code'] = 10001;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }

        $member = new User();

        $member_condition = [
            'member_id' => input('member_id'),
            'member_mobile' => input('mobile'),
        ];
        $member_info = $member->getMemberInfo($member_condition,'member_id');
        if (empty($member_info)){
            $data['code'] = 10002;
            $data['message'] = "绑定的手机号错误";
            return json_encode($data,true);
        }

//        $countryCode = input("country_code")?86:input("country_code");
//        $phone = $countryCode.input("mobile");
        $phone = input('mobile');
        $captcha = input('snscode');
        $condition = array();
        $condition['log_phone'] = $phone;
        $condition['log_captcha'] = $captcha;
        $condition['log_type'] = 2;
        $model_sms_log = new Sms();
        $sms_log = $model_sms_log->getSmsInfo($condition);
        if(empty($sms_log) || ($sms_log['add_time'] < TIMESTAMP-1800)) {//半小时内进行验证为有效
            $data['code'] = 10003;
            $data['message'] = '动态码错误或已过期，重新输入';
            return json_encode($data,true);
        }

        $data['code'] = 200;
        $data['message'] = '验证通过';
        return json_encode($data,true);
    }

    /**
     * 修改手机号
     * @return false|string
     */
    public function updatePhone_2()
    {
        if (!input('member_id')
            || !input("mobile")
            || !input("snscode")
        ){
            $data['code'] = 10001;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }

//        $countryCode = input("country_code")?86:input("country_code");
//        $phone = $countryCode.input("mobile");
        $phone = input('mobile');
        $captcha = input('snscode');
        $condition = array();
        $condition['log_phone'] = $phone;
        $condition['log_captcha'] = $captcha;
        $condition['log_type'] = 2;
        $model_sms_log = new Sms();
        $sms_log = $model_sms_log->getSmsInfo($condition);
        if(empty($sms_log) || ($sms_log['add_time'] < TIMESTAMP-1800)) {//半小时内进行验证为有效
            $data['code'] = 10002;
            $data['message'] = '动态码错误或已过期，重新输入';
            return json_encode($data,true);
        }

        $member = new User();

        $member_condition[] = ['member_id','neq',input('member_id')];
        $member_condition[] = ['member_mobile','eq',input('mobile')];
        $member_info = $member->getMemberInfo($member_condition,'member_id');
        if ($member_info){
            $data['code'] = 10003;
            $data['message'] = "该手机号已被注册";
            return json_encode($data,true);
        }

        $param = [
            'member_mobile' => input('mobile')
        ];
        $update = $member->updateMember($param,input('member_id'));

        if ($update){
            $data['code'] = 200;
            $data['message'] = '修改成功';
            return json_encode($data,true);
        }else{
            $data['code'] = 10004;
            $data['message'] = '修改失败，请重试';
            return json_encode($data,true);
        }
    }


}
