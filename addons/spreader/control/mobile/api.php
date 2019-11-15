<?php
/**
 * WAP首页
 *
 */

defined('DYMall') or exit('Access Invalid!');
class apiCtl{
    public function __construct() {
        // parent::__construct();
    }

    // 插件开启状态
    public function addons_status()
    {
        $return = (C('spreader_isuse') && C('sld_spreader')) ? true : false;

        echo json_encode($return);
    }

    // 获取 分享超时天数
    public function get_share_setting()
    {
        $return = array();

        $return['ssys_expire_day'] = C('ssys_share_valid_time') ? C('ssys_share_valid_time') : 0;

        echo json_encode($return);
    }

    // 获取 提现配置
    public function get_cash_setting()
    {
        $return = array();

        $return['ssys_min_cash_amount_once'] = C('ssys_min_cash_amount_once') ? C('ssys_min_cash_amount_once') : 100;

        echo json_encode($return);
    }

    // 商城用户成为推手
    public function become_spreader()
    {
        $state = 255;
        $data = '';
        $message = '请求错误';

        // 根据key 获取商城用户信息
        $key = $_REQUEST['key'];
        if ($key) {
            $model_mb_user_token = Model('mb_user_token');
            $mb_user_token_info = $model_mb_user_token->getMbUserTokenInfoByToken($key);
            $model_member = Model('member');
            $shop_member_info = $model_member->getMemberInfoByID($mb_user_token_info['member_id']);
        }else{
            $shop_member_info = array();
        }
        if (!empty($shop_member_info) && isset($shop_member_info['member_id']) && $shop_member_info['member_id'] > 0) {
            // 检查 该商城用户 是否已有推手账号
            $ssys_member = M('ssys_member','spreader');
            $has_spreader_condition['shop_member_id'] = $shop_member_info['member_id'];
            $spreader_member_info = $ssys_member->getMemberInfo($has_spreader_condition,'member_id');
            if (!empty($spreader_member_info) && isset($spreader_member_info['member_id']) && $spreader_member_info['member_id'] > 0) {
                // 已成为推手
                $state = 256;
                $data['spreader_url'] = MALL_URL.'/ts/';
                $data['tips'] = '您已成为推手,可用当前商城账号进行登录。';
                $message = '已成为推手';
            }else{
                // 若不存在 将当前商城用户 创建为 推手账号
                $member_info    = array();
                $member_info['member_name']         = $shop_member_info['member_name'];
                $member_info['member_passwd']       = $shop_member_info['member_passwd'];
                $member_info['member_time']         = time();
                $member_info['member_mobile']       = $shop_member_info['member_mobile'];
                $member_info['member_mobile_bind']      = $shop_member_info['member_mobile_bind'];
                $member_info['member_email']        = $shop_member_info['member_email'];
                $member_info['member_email_bind']       = $shop_member_info['member_email_bind'];
                $member_info['member_truename']     = $shop_member_info['member_truename'];
                $member_info['member_qq']           = $shop_member_info['member_qq'];
                $member_info['member_sex']          = $shop_member_info['member_sex'];
                $member_info['member_avatar']       = $shop_member_info['member_avatar'];
                $member_info['member_qqopenid']     = $shop_member_info['member_qqopenid'];
                $member_info['member_qqinfo']       = $shop_member_info['member_qqinfo'];
                $member_info['member_sinaopenid']   = $shop_member_info['member_sinaopenid'];
                $member_info['member_sinainfo']     = $shop_member_info['member_sinainfo'];
                $member_info['wx_openid']           = $shop_member_info['wx_openid'];
                $member_info['wx_nickname']         = $shop_member_info['wx_nickname'];
                $member_info['wx_area']             = $shop_member_info['wx_area'];
                $member_info['weixin_unionid'] = $shop_member_info['weixin_unionid'];
                $member_info['shop_member_id'] = $shop_member_info['member_id'];
                
                $insert_id  = $ssys_member->insert_member_data($member_info);
                if($insert_id) {
                    // 成功
                    $state = 200;
                    $message = '成功成为推手';
                }else{
                    // 成为推手失败
                    $message = '成为推手失败';
                }
            }
        }else{
            // 无效的key
            $message = '非法请求';
        }


        $return_last = array(
                'state' => $state,
                'data' => $data,
                'msg' => $message,
            );

        echo json_encode($return_last);
    }

    public function get_spu_all_gid(){
        $spreader_gid = $_REQUEST['spreader_gid'];

        $state = 255;
        $data = '';
        $message = '请求错误';

        $goods_ids = array();

        if ($spreader_gid) {
            // 获取 当前商品 common_id 下的所有 gid
            $goods_model = Model('goods');

            $goods_common_id_data = $goods_model->getGoodsInfoByID($spreader_gid,'goods_commonid');

            if (isset($goods_common_id_data['goods_commonid']) && $goods_common_id_data['goods_commonid']) {
                $condition['goods_commonid'] = $goods_common_id_data['goods_commonid'];
                $goods_ids_data = $goods_model->getGoodsList($condition,'gid');
                $goods_ids = low_array_column($goods_ids_data, 'gid');

                $state = 200;
                $message = 'success';
                $data = $goods_ids;
            }

        }

        $return_last = array(
                'state' => $state,
                'data' => $data,
                'msg' => $message,
            );

        echo json_encode($return_last);
    }

}

class spreader_apiAdd{

	public function __construct() {
		
    }

    // 商城用户 与 推手用户 关系建立
    public function create_market_member_speader($par)
    {
    	$market_member = (isset($par['market_member']) && $par['market_member'] > 0) ? $par['market_member'] : 0;
    	$spreader_member = isset($par['spreader_member']) ? $par['spreader_member'] : 0;
    	if ($market_member && $spreader_member) {
	    	$check_flag = self::check_market_member($market_member,$spreader_member);
	    	if (!$check_flag) {
	    		// 该用户还未有推广关系
	    		$spreader_member_id = self::decode_spreader_code($spreader_member);
	    		$ssys_member = M('ssys_member','spreader');
	    		$data['member_id'] = $spreader_member_id;
	    		$data['shop_member_id'] = $market_member;
	    		$ssys_member->save_member_nexus_data($data);
	    	}
    	}
    }
    //推手自己下单后,判断是否可以添加到统计表中
    public function create_ssys_ts_order($data)
    {
        $model = model();
        //只要有一个商品不是标注商品,就取消这个是推手购买订单
        $istsgou = 1;
        foreach($data['extend_order_goods'] as $k=>$v)
        {
            $res = $model->table('ssys_goods')->where(['gid'=>$v['gid'],'is_buy_condition'=>1])->find();
            if(!$res){
                $istsgou = 0;
                break;
            }
        }
        //判断会员是否是推手
        $member_info = $model->table('ssys_member')->where(['shop_member_id'=>$data['buyer_id'],'ts_member_state'=>['neq',2]])->find();
        if($istsgou && $member_info){
            $insert = [
                'ssysorder_member_id'=>$member_info['member_id'],
                'ssysorder_shop_member_id'=>$member_info['shop_member_id'],
                'ssysorder_order_id'=>$data['order_id']
            ];
            $model->table('ssys_ts_order')->insert($insert);
        }
    }
    // 下单后 订单信息 存储 
    public function create_order_speader($par)
    {
    	$spreader_flag = isset($par['spreader_flag']) ? $par['spreader_flag'] : 0;
    	$order_info = isset($par['order_info']) ? $par['order_info'] : 0;

        $spreader_goods = array();

        if ($spreader_flag) {

            // 组装 标示数据
            $spreader_goods_l = explode(',', $spreader_flag);
            foreach ($spreader_goods_l as $key => $value) {
                $spreader_goods_item_l = explode('|', $value);
                if (is_array($spreader_goods_item_l) && !empty($spreader_goods_item_l)) {
                    $spreader_goods[$spreader_goods_item_l[0]] = $spreader_goods_item_l[1];
                }
                unset($spreader_goods_item_l);
            }
            unset($spreader_goods_l);
        }

    	if ($order_info) {

    		$order_data = array();

    		$order_common['order_id'] = $order_info['order_id'];
    		$order_common['order_sn'] = $order_info['order_sn'];
    		if (!empty($order_info['extend_order_goods'])) {
    			foreach ($order_info['extend_order_goods'] as $key => $value) {
                    $goods_num = $value['goods_num'];
                    $spreader_member_id = 0;

                    // 订单带过来的 商品 的 分享标示
                    if ($spreader_goods[$value['gid']]) {
                        $spreader_member = $spreader_goods[$value['gid']];
                        // 解码 分享标示
                        $spreader_member_id = self::decode_spreader_code($spreader_member);
                    }else{
                        // 若不存在 检查 用户 是否有绑定的推手
                        $check_spreader_condition['shop_member_id'] = $order_info['buyer_id'];
                        $check_nexus_info = M('ssys_member','spreader')->get_member_nexus_find($check_spreader_condition,'member_id');
                        if (is_array($check_nexus_info) && !empty($check_nexus_info) && $check_nexus_info['member_id']) {
                            $spreader_member_id = $check_nexus_info['member_id'];
                        }
                    }

			    	if ($spreader_member_id > 0) {

                        // 绑定关系
                        // $create_par['market_member'] = $order_info['buyer_id'];
                        // $create_par['spreader_member'] = $spreader_member;
                        // // 发送请求 绑定关系
                        // con_addons('spreader',$create_par,'create_market_member_speader','api');

                        // 获取当前 用户 的 上级 （最多三级）
                        $more_level_spreader_member = M('ssys_yj','spreader')->get_mutil_level_spreader($spreader_member_id);

                        if (is_array($more_level_spreader_member) && !empty($more_level_spreader_member)) {
                            foreach ($more_level_spreader_member as $m_l_s_m_k => $m_l_s_m_v) {
                                $order_goods_item_data = $order_common;
                                $order_goods_item_data['member_id'] = $m_l_s_m_v['member_id'];
                                $order_goods_item_data['gid'] = $value['gid'];
                                // 获取佣金金额
                                $yj_amount = M('ssys_yj','spreader')->getGoodsYjAmount($value['gid'],$m_l_s_m_v['member_id'],$m_l_s_m_v['deep']);
                                $order_goods_item_data['yj_status'] = '0';
                                $order_goods_item_data['yj_amount'] = $yj_amount * $goods_num;

                                $yj_data_item['member_id'] = $m_l_s_m_v['member_id'];
                                $spreader_member_info = M('ssys_member','spreader')->getMemberInfoByID($m_l_s_m_v['member_id']);
                                $yj_data_item['member_name'] = $spreader_member_info['member_name'];
                                $yj_data_item['amount'] = $order_goods_item_data['yj_amount'];
                                $yj_data_item['order_sn'] = $order_goods_item_data['order_sn'];
                                $yj_data_item['gid'] = $order_goods_item_data['gid'];
                                $yj_data[] = $yj_data_item;

                                $order_data[] = $order_goods_item_data;
                            }
                        }
			    	}
    			}
    		}

            if (!empty($yj_data) && !empty($order_data)) {
                $ssys_order = M('ssys_order','spreader');
                $ssys_order->saveOrders($order_data);

                // 更新 用户 佣金金额
                $ssys_yj = M('ssys_yj','spreader');
                $ssys_yj->updateMemberYj('order_pay',$yj_data);
            }

    	}
    }

    // 推手的 分享标示 加密 
    public function encode_spreader_code($id)
    {
    	$encode_code = base64_encode(intval($id)*20181111);
    	return $encode_code;
    }

    // 推手的 分享标示 解密
    public function decode_spreader_code($code)
    {
    	$decode_code = intval(base64_decode($code)/20181111);
    	return $decode_code;
    }

    // 检查 当前 商城用户 是否 已经成为了 推手的下级 且 是否是 推手自己的商城系统用户
    public function check_market_member($market_member,$spreader_member='')
    {
    	$return_flag = false;

    	$ssys_member = M('ssys_member','spreader');

    	$condition['shop_member_id'] = $market_member;

    	$nexus_info = $ssys_member->get_member_nexus_find($condition,'member_id');

    	if (!empty($nexus_info) && isset($nexus_info['member_id']) && $nexus_info['member_id'] > 0) {
			$spreader_member_id = self::decode_spreader_code($spreader_member);
            if ($spreader_member_id > 0 && $nexus_info['member_id'] == $spreader_member_id) {
                $return_flag = false;
            }else{
                $return_flag = true;
            }
    	}

    	return $return_flag;
    }

    // 检查商城用户 是否已成为 推手用户
    public function check_market_member_is_spreader($par){
        $market_member = $par['member_id'];
        $return_flag = false;
        // 检查 该商城用户 是否已有推手账号
        $ssys_member = M('ssys_member','spreader');
        $has_spreader_condition['shop_member_id'] = $market_member;
        $spreader_member_info = $ssys_member->getMemberInfo($has_spreader_condition,'member_id');
        if (!empty($spreader_member_info) && isset($spreader_member_info['member_id']) && $spreader_member_info['member_id'] > 0) {
            $return_flag = 1;
        }else{
            $return_flag = 0;
        }

        return $return_flag;
    }

    // 订单状态更新 对应的 冻结金额 及 推手系统订单 状态变化
    public function update_order_status_speader($par)
    {
    	$state_type = $par['state_type'];
    	$order_info = $par['order_info'];
    	$extend_msg = $par['extend_msg'];
    	
    	$ssys_order = M('ssys_order','spreader');
    	$ssys_order->memberChangeState($state_type,$order_info,$extend_msg);

    }

    // 创建 商城用户
    public function create_shop_member_register($par){
        $ssys_member = $par['ssys_member'];
        $register_info = $par['register_info'];

        $model_member   = Model('member');

        $has_shop_member = false;

        // 查询手机号是否已经注册过 注册过 则获取 用户ID
        if ($register_info['mobile']) {
            $check_condition['member_mobile'] = $register_info['mobile'];
            $has_shop_member = $model_member->getMemberCount($check_condition);
        }

        if ($has_shop_member) {
            $member_info = $model_member->getMemberInfo($check_condition);
            if(!$member_info){
                return false;
            }
        }else{
            $member_info = $model_member->mobileRegister_wap($register_info);
            if(isset($member_info['error'])){
                return false;
            }
        }

        if ($member_info) {
            $ssys_member_update_data['shop_member_id'] = $shop_member_id = $member_info['member_id'];
            $ssys_member_model = M('ssys_member','spreader');
            //检测此会员是否已经绑定推手关联
            $res = $ssys_member_model->where(['shop_member_id'=>$shop_member_id])->find();
            if($res){
                return false;
            }
            $ssys_member_model->updateMember($ssys_member_update_data,$ssys_member['member_id']);
            return true;
        }else{
            return false;
        }

    }

    // 订单已付款 状态更新 更新统计记录
    public function add_up_order_num($par){
        // 检查 订单是否为 推手订单
        $check_flag = false;
        $condition['order_id'] = $par['order_id'];

        $has_order = M('ssys_order','spreader')->getSpreaderOrderInfos($condition);

        if (is_array($has_order) && !empty($has_order)) {
            $check_flag = true;
        }
        if ($check_flag) {
            $data = array();
            $data['order_num'] = 1;
            $data['order_amount'] = $par['order_amount'];
            M('ssys_statistics_log','spreader')->save_statistics_log($data);
        }
    }

    // 更新推手 密码
    public function update_spreader_password($par){
        $member_id = 0;
        $password = $par['password'];
        $market_member_id = $par['member_id'];
        // 根据 商城系统的 会员ID 获取 该商城用户的推手ID
        $spreader_member_info = M('ssys_member','spreader')->getMemberInfo(array('shop_member_id'=>$market_member_id),'member_id');
        if (!empty($spreader_member_info) && isset($spreader_member_info['member_id']) && $spreader_member_info['member_id']) {
            $member_id = $spreader_member_info['member_id'];
        }
        if ($password && $member_id) {
            M('ssys_member','spreader')->editMember(array('member_id'=>$member_id),array('member_passwd'=>$password));
        }
    }

}