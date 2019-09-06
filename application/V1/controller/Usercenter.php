<?php
namespace app\V1\controller;
use app\V1\model\BrowserHistory;
use app\V1\model\Favorites;
use app\V1\model\Grade;
use app\V1\model\SnsVisitor;
use app\V1\model\UserCart;
use app\V1\model\UserOrder;
use app\V1\model\Goods;
use app\V1\model\User;
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
	 * SNS首页
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
	 * 添加SNS分享心情
	 */
	public function addtrace(){
		$obj_validate = new Validate();
		$validate_arr[] = array("input"=>$_POST["content"], "require"=>"true","message"=>Language::get('请填写心情'));
		$validate_arr[] = array("input"=>$_POST["content"], "validator"=>'Length',"min"=>0,"max"=>140,"message"=>Language::get('不能超过140字'));
		//发帖数超过最大次数出现验证码
		if(intval(cookie('weibonum'))>=self::MAX_RECORDNUM){
			$validate_arr[] = array("input"=>$_POST["captcha"], "require"=>"true","message"=>Language::get('请填写验证码'));
		}
		$obj_validate -> validateparam = $validate_arr;
		$error = $obj_validate->validate();
		if ($error != ''){
			showDialog($error,'','error');
		}
		//发帖数超过最大次数出现验证码
		if(intval(cookie('weibonum'))>=self::MAX_RECORDNUM){
			if (!checkSeccode($_POST['sldcode'],$_POST['captcha'])){
				showDialog(Language::get('验证码错误'),'','error');
			}
		}
		//查询会员信息
		$member_model = Model('member');
		$member_info = $member_model->infoMember(array('member_id'=>"{$_SESSION['member_id']}",'member_state'=>'1'));
		if (empty($member_info)){
			showDialog(Language::get('会员信息错误'),'','error');
		}
		$tracelog_model = Model('sns_tracelog');
		$insert_arr = array();
		$insert_arr['trace_originalid'] = '0';
		$insert_arr['trace_originalmemberid'] = '0';
		$insert_arr['trace_memberid'] = $_SESSION['member_id'];
		$insert_arr['trace_membername'] = $_SESSION['member_name'];
		$insert_arr['trace_memberavatar'] = $member_info['member_avatar'];
		$insert_arr['trace_title'] = $_POST['content'];
		$insert_arr['trace_content'] = '';
		$insert_arr['trace_addtime'] = time();
		$insert_arr['trace_state'] = '0';
		$insert_arr['trace_privacy'] = intval($_POST["privacy"])>0?intval($_POST["privacy"]):0;
		$insert_arr['trace_commentcount'] = 0;
		$insert_arr['trace_copycount'] = 0;
		$result = $tracelog_model->tracelogAdd($insert_arr);
		if ($result){
			//建立cookie
			if (cookie('weibonum') != null && intval(cookie('weibonum')) >0){
				setBbcCookie('weibonum',intval(cookie('weibonum'))+1,2*3600);//保存2小时
			}else{
				setBbcCookie('weibonum',1,2*3600);//保存2小时
			}
			$js = "var obj = $(\"#weiboform\").find(\"[bbc_type='formprivacytab']\");$(obj).find('span').removeClass('selected');$(obj).find('ul li:nth-child(1)').find('span').addClass('selected');";
			$js .= "$(\"#content_weibo\").val('');$(\"#privacy\").val('0');$('#friendtrace').lazyshow({url:\"index.php?app=usercenter&mod=tracelist&pn=1\",'iIntervalId':true});";
			showDialog(Language::get('分享成功'),'','succ',$js);
		}else {
			showDialog(Language::get('分享失败'),'','error');
		}
	}
	/**
	 * 添加分享已买到的宝贝
	 */
	public function sharegoods(){
		if ($_POST['form_submit'] == 'ok'){
			$obj_validate = new Validate();
			$validate_arr[] = array("input"=>$_POST["choosegoodsid"], "require"=>"true","message"=>Language::get('选择一件分享的商品~'));
			$validate_arr[] = array("input"=>$_POST["comment"], "validator"=>'Length',"min"=>0,"max"=>140,"message"=>Language::get('不能超过140字'));
			//发帖数超过最大次数出现验证码
			if(intval(cookie('weibonum'))>=self::MAX_RECORDNUM){
				$validate_arr[] = array("input"=>$_POST["captcha"], "require"=>"true","message"=>Language::get('请填写验证码'));
			}
			$obj_validate -> validateparam = $validate_arr;
			$error = $obj_validate->validate();
			if (intval($_POST["choosegoodsid"]) <= 0){
				$error .= Language::get('商品已下架或者已被删除，无法进行分享');
			}
			if ($error != ''){
				showDialog($error,'','error');
			}
			//发帖数超过最大次数出现验证码
			if(intval(cookie('weibonum'))>=self::MAX_RECORDNUM){
				if (!checkSeccode($_POST['sldcode'],$_POST['captcha'])){
					showDialog(Language::get('验证码错误'),'','error');
				}
			}
			//查询会员信息
			$member_model = Model('member');
			$member_info = $member_model->infoMember(array('member_id'=>"{$_SESSION['member_id']}",'member_state'=>'1'));
			if (empty($member_info)){
				showDialog(Language::get('会员信息错误'),'','error');
			}
			//查询商品信息
			$goods_model = Model('goods');
			$condition = array();
			$condition['gid'] = intval($_POST['choosegoodsid']);
			$goods_info = $goods_model->getGoodsOnlineInfo($condition,'gid,goods_name,goods_image,goods_price,goods_freight,goods_collect,vid,store_name');
			if (empty($goods_info)){
				showDialog(Language::get('商品已下架或者已被删除，无法进行分享'),'','error');
			}
			$sharegoods_model = Model('sns_sharegoods');
			//判断该商品是否已经存在分享或者喜欢记录
			$sharegoods_info = $sharegoods_model->getSharegoodsInfo(array('share_memberid'=>"{$_SESSION['member_id']}",'share_goodsid'=>"{$goods_info['gid']}"));
			$result = false;
			if (empty($sharegoods_info)){
				//添加分享商品信息
				$insert_arr = array();
				$insert_arr['share_goodsid'] = $goods_info['gid'];
				$insert_arr['share_memberid'] = $_SESSION['member_id'];
				$insert_arr['share_membername'] = $_SESSION['member_name'];
				$insert_arr['share_content'] = $_POST['comment']?$_POST['comment']:Language::get('分享了商品');
				$insert_arr['share_addtime'] = time();
				$insert_arr['share_privacy'] = intval($_POST["gprivacy"])>0?intval($_POST["gprivacy"]):0;
				$insert_arr['share_commentcount'] = 0;
				$insert_arr['share_isshare'] = 1;
				$result = $sharegoods_model->sharegoodsAdd($insert_arr);
				unset($insert_arr);
			}else {
				//更新分享商品信息
				$update_arr = array();
				$update_arr['share_content'] = $_POST['comment']?$_POST['comment']:Language::get('分享了商品');
				$update_arr['share_addtime'] = time();
				$update_arr['share_privacy'] = intval($_POST["gprivacy"])>0?intval($_POST["gprivacy"]):0;
				$update_arr['share_isshare'] = 1;
				$result = $sharegoods_model->editSharegoods($update_arr,array('share_id'=>"{$sharegoods_info['share_id']}"));
				unset($update_arr);
			}
			if ($result){
				//商品缓存数据更新
				//生成缓存的键值
				$hash_key = $goods_info['gid'];
				//先查找$hash_key缓存
				if ($_cache = rcache($hash_key,'product')){
					$_cache['sharenum'] = intval($_cache['sharenum'])+1;
					//缓存商品信息
					wmemcache($hash_key,$_cache,'product');
				}
				//更新SNS商品表信息
				$snsgoods_model = Model('sns_goods');
				$snsgoods_info = $snsgoods_model->getGoodsInfo(array('snsgoods_goodsid'=>"{$goods_info['gid']}"));
				if (empty($snsgoods_info)){
					//添加SNS商品
					$insert_arr = array();
					$insert_arr['snsgoods_goodsid'] = $goods_info['gid'];
					$insert_arr['snsgoods_goodsname'] = $goods_info['goods_name'];
					$insert_arr['snsgoods_goodsimage'] = $goods_info['goods_image'];
					$insert_arr['snsgoods_goodsprice'] = $goods_info['goods_price'];
					$insert_arr['snsgoods_storeid'] = $goods_info['vid'];
					$insert_arr['snsgoods_storename'] = $goods_info['store_name'];
					$insert_arr['snsgoods_addtime'] = time();
					$insert_arr['snsgoods_likenum'] = 0;
					$insert_arr['snsgoods_sharenum'] = 1;
					$snsgoods_model->goodsAdd($insert_arr);
					unset($insert_arr);
				}else {
					//更新SNS商品
					$update_arr = array();
					$update_arr['snsgoods_sharenum'] = intval($snsgoods_info['snsgoods_sharenum'])+1;
					$snsgoods_model->editGoods($update_arr,array('snsgoods_goodsid'=>"{$goods_info['gid']}"));
				}
				//添加分享动态
				$tracelog_model = Model('sns_tracelog');
				$insert_arr = array();
				$insert_arr['trace_originalid'] = '0';
				$insert_arr['trace_originalmemberid'] = '0';
				$insert_arr['trace_memberid'] = $_SESSION['member_id'];
				$insert_arr['trace_membername'] = $_SESSION['member_name'];
				$insert_arr['trace_memberavatar'] = $member_info['member_avatar'];
				$insert_arr['trace_title'] = $_POST['comment']?$_POST['comment']:Language::get('分享了商品');
				$content_str = '';
				$content_str .= "<div class=\"fd-media\">
					<div class=\"goodsimg\"><a style=\"display: table-cell;vertical-align: middle;width: 120px;height: 120px;font-size: 0;line-height: 0;\" target=\"_blank\" href=\"".urlShop('goods', 'index', array('gid'=>$goods_info['gid']))."\"><img src=\"".thumb($goods_info, 240)."\" style=\"max-width:120px;max-height:120px;\" alt=\"{$goods_info['goods_name']}\"></a></div>
					<div class=\"goodsinfo\">
						<dl>
							<dt><a target=\"_blank\" href=\"".urlShop('goods', 'index', array('gid'=>$goods_info['gid']))."\">".$goods_info['goods_name']."</a></dt>
							<dd>".Language::get('价&nbsp;&nbsp;格').Language::get('：').Language::get('元').$goods_info['goods_price']."</dd>
							<dd>".Language::get('运&nbsp;&nbsp;费').Language::get('：').Language::get('元').$goods_info['goods_freight']."</dd>
	                  		<dd bbctype=\"collectbtn_{$goods_info['gid']}\"><a href=\"javascript:void(0);\" onclick=\"javascript:collect_goods(\'{$goods_info['gid']}\',\'succ\',\'collectbtn_{$goods_info['gid']}\');\">".Language::get('收藏该宝贝')."</a></dd>
	                  	</dl>
	                  </div>
	             </div>";
				$insert_arr['trace_content'] = $content_str;
				$insert_arr['trace_addtime'] = time();
				$insert_arr['trace_state'] = '0';
				$insert_arr['trace_privacy'] = intval($_POST["gprivacy"])>0?intval($_POST["gprivacy"]):0;
				$insert_arr['trace_commentcount'] = 0;
				$insert_arr['trace_copycount'] = 0;
				$result = $tracelog_model->tracelogAdd($insert_arr);
				//建立cookie
				if (cookie('weibonum') != null && intval(cookie('weibonum')) >0){
					setBbcCookie('weibonum',intval(cookie('weibonum'))+1,2*3600);//保存2小时
				}else{
					setBbcCookie('weibonum',1,2*3600);//保存2小时
				}
				//站外分享功能
				if (C('share_isuse') == 1){
					$model = Model('sns_binding');
					//查询该用户的绑定信息
					$bind_list = $model->getUsableApp($_SESSION['member_id']);
					//分享内容数组
					$params = array();
					$params['title'] = Language::get('分享了商品');
					$params['url'] = urlShop('goods' , 'index', array('gid'=>$goods_info['gid']));
					$params['comment'] = $goods_info['goods_name'].$_POST['comment'];
					$params['images'] = thumb($goods_info, 240);
					//分享之qqweibo
					if (isset($_POST['checkapp_qqweibo']) && !empty($_POST['checkapp_qqweibo']) && $bind_list['qqweibo']['isbind'] == true){
						$model->addQQWeiboPic($bind_list['qqweibo'],$params);
					}
					//分享之sinaweibo
					if (isset($_POST['checkapp_sinaweibo']) && !empty($_POST['checkapp_sinaweibo']) && $bind_list['sinaweibo']['isbind'] == true){
						$model->addSinaWeiboUpload($bind_list['sinaweibo'],$params);
					}
				}
				//输出js
				$js = "DialogManager.close('sharegoods');var countobj=$('[bbc_type=\'sharecount_{$goods_info['gid']}\']');$(countobj).html(parseInt($(countobj).text())+1);";
				$url = '';
				if ($_GET['irefresh']){
					$js .= "$('#friendtrace').lazyshow({url:\"index.php?app=usercenter&mod=tracelist&pn=1\",'iIntervalId':true});";
				}else{
					$url = 'reload';
				}
				showDialog(Language::get('分享成功'),$url,'succ',$js);
			}else {
				showDialog(Language::get('分享失败'),$url,'error');
			}
		} else {
			//查询已购买商品信息
			$order_model = Model('order');
			$condition = array();
			$condition['buyer_id'] = $_SESSION['member_id'];
			$ordergoods_list = $order_model->getOrderGoodsList($condition);
			unset($condition);
			$order_goodsid = array();
			if (!empty($ordergoods_list)){
				foreach ($ordergoods_list as $v){
					$order_goodsid[] = $v['gid'];
				}
			}

			// 查询收藏商品
			$favorites_list = Model()->table('favorites')->field('fav_id')->where(array('member_id'=>$_SESSION['member_id'], 'fav_type'=>'goods'))->select();
			$favorites_goodsid = array();
			if(!empty($favorites_list)){
				foreach ($favorites_list as $v){
					$favorites_goodsid[] = $v['fav_id'];
				}
			}

			$gid = array_merge($order_goodsid, $favorites_goodsid);
			//查询商品信息
			$goods_model = Model('goods');
			$condition = array();
			$condition['gid'] = array('in', $gid);
			$goods_list = $goods_model->getGoodsOnlineList($condition,'gid,goods_name,goods_image,vid');
			if(!empty($goods_list)){
				foreach ($goods_list as $k=>$v){
					if(in_array($v['gid'], $order_goodsid)){
						$goods_list[$k]['order'] = true;
					}
					if(in_array($v['gid'], $favorites_goodsid)){
						$goods_list[$k]['favorites'] = true;
					}
				}
			}
			if (C('share_isuse') == 1){
			    $model = Model('sns_binding');
			    $app_arr = $model->getUsableApp($_SESSION['member_id']);
			    Template::output('app_arr',$app_arr);
			}
			//验证码
			Template::output('sldcode',substr(md5(MALL_SITE_URL.$_GET['app'].$_GET['mod']),0,8));
			Template::output('goods_list',$goods_list);
			Template::showpage('member_snssharegoods','null_layout');
		}
	}
	/**
	 * 分享店铺
	 */
	public function sharestore(){
		if ($_POST['form_submit'] == 'ok'){
			$obj_validate = new Validate();
			$validate_arr[] = array("input"=>$_POST["choosestoreid"], "require"=>"true","message"=>Language::get('选择一家要分享的店铺~'));
			$validate_arr[] = array("input"=>$_POST["comment"], "validator"=>'Length',"min"=>0,"max"=>140,"message"=>Language::get('不能超过140字'));
			//发帖数超过最大次数出现验证码
			if(intval(cookie('weibonum'))>=self::MAX_RECORDNUM){
				$validate_arr[] = array("input"=>$_POST["captcha"], "require"=>"true","message"=>Language::get('请填写验证码'));
			}
			$obj_validate -> validateparam = $validate_arr;
			$error = $obj_validate->validate();
			if ($error != ''){
				showDialog($error,'','error');
			}
			//发帖数超过最大次数出现验证码
			if(intval(cookie('weibonum'))>=self::MAX_RECORDNUM){
				if (!checkSeccode($_POST['sldcode'],$_POST['captcha'])){
					showDialog(Language::get('验证码错误'),'','error');
				}
			}
			//查询会员信息
			$member_model = Model('member');
			$member_info = $member_model->infoMember(array('member_id'=>"{$_SESSION['member_id']}",'member_state'=>'1'));
			if (empty($member_info)){
				showDialog(Language::get('会员信息错误'),'','error');
			}
			//查询店铺信息
			$store_model = Model('vendor');
			$store_info = $store_model->getStoreInfoByID($_POST['choosestoreid']);
			if (empty($store_info)){
				showDialog(Language::get('店铺信息错误'),'','error');
			}
			$sharestore_model = Model('sns_sharestore');
			//判断该商品是否已经分享过
			$sharestore_info = $sharestore_model->getSharestoreInfo(array('share_memberid'=>"{$_SESSION['member_id']}",'share_storeid'=>"{$store_info['vid']}"));
			$result = false;
			if (empty($sharestore_info)){
				//添加分享商品信息
				$insert_arr = array();
				$insert_arr['share_storeid'] = $store_info['vid'];
				$insert_arr['share_storename'] = $store_info['store_name'];
				$insert_arr['share_memberid'] = $_SESSION['member_id'];
				$insert_arr['share_membername'] = $_SESSION['member_name'];
				$insert_arr['share_content'] = $_POST['comment'];
				$insert_arr['share_addtime'] = time();
				$insert_arr['share_privacy'] = intval($_POST["sprivacy"])>0?intval($_POST["sprivacy"]):0;
				$result = $sharestore_model->sharestoreAdd($insert_arr);
				unset($insert_arr);
			}else {
				//更新分享商品信息
				$update_arr = array();
				$update_arr['share_content'] = $_POST['comment'];
				$update_arr['share_addtime'] = time();
				$update_arr['share_privacy'] = intval($_POST["sprivacy"])>0?intval($_POST["sprivacy"]):0;
				$result = $sharestore_model->editSharestore($update_arr,array('share_id'=>"{$sharestore_info['share_id']}"));
				unset($update_arr);
			}
			if ($result){
				//添加分享动态
				$tracelog_model = Model('sns_tracelog');
				$insert_arr = array();
				$insert_arr['trace_originalid'] = '0';
				$insert_arr['trace_originalmemberid'] = '0';
				$insert_arr['trace_memberid'] = $_SESSION['member_id'];
				$insert_arr['trace_membername'] = $_SESSION['member_name'];
				$insert_arr['trace_memberavatar'] = $member_info['member_avatar'];
				$insert_arr['trace_title'] = $_POST['comment']?$_POST['comment']:Language::get('分享了店铺');
				$content_str = '';
				$store_info['store_label'] = empty($store_info['store_label']) ? getCimg('default_store_logo') : UPLOAD_SITE_URL.DS.ATTACH_STORE.DS.$store_info['store_label'];
				$store_info['store_url'] = urlShop('vendor', 'index', array('vid'=>$store_info['vid']));
				$content_str .= "<div class=\"fd-media\">
					<div class=\"goodsimg\"><a target=\"_blank\" style =\"display: table-cell;vertical-align: middle;width: 120px;height: 120px;font-size: 0;line-height: 0;\" href=\"{$store_info['store_url']}\"><img src=\"{$store_info['store_label']}\" style=\"max-width: 120px;max-height: 120px;\" alt=\"{$store_info['store_name']}\"></a></div>
					<div class=\"goodsinfo\">
						<dl>
							<dt><a target=\"_blank\" href=\"{$store_info['store_url']}\">".$store_info['store_name']."</a></dt>
	                  		<dd bbctype=\"storecollectbtn_{$store_info['vid']}\"><a href=\"javascript:void(0);\" onclick=\"javascript:follow_v(\'{$store_info['vid']}\',\'succ\',\'storecollectbtn_{$store_info['vid']}\');\">".Language::get('收藏该店铺')."</a></dd>
	                  	</dl>
	                  </div>
	             </div>";
				$insert_arr['trace_content'] = $content_str;
				$insert_arr['trace_addtime'] = time();
				$insert_arr['trace_state'] = '0';
				$insert_arr['trace_privacy'] = intval($_POST["sprivacy"])>0?intval($_POST["sprivacy"]):0;
				$insert_arr['trace_commentcount'] = 0;
				$insert_arr['trace_copycount'] = 0;
				$result = $tracelog_model->tracelogAdd($insert_arr);
				//建立cookie
				if (cookie('weibonum') != null && intval(cookie('weibonum')) >0){
					setBbcCookie('weibonum',intval(cookie('weibonum'))+1,2*3600);//保存2小时
				}else{
					setBbcCookie('weibonum',1,2*3600);//保存2小时
				}
				//输出js
				$js = "DialogManager.close('sharestore');";
				$url = '';
				if ($_GET['irefresh']){
					$js.="$('#friendtrace').lazyshow({url:\"index.php?app=usercenter&mod=tracelist&pn=1\",'iIntervalId':true});";
				}else{
					$url = 'reload';
				}
				showDialog(Language::get('分享成功'),$url,'succ',$js);
			}else {
				showDialog(Language::get('分享失败'),$url,'error');
			}
		} else {
			//查询收藏店铺信息
			$favorites_model = Model('favorites');
			$condition = array();
			$condition['member_id'] = $_SESSION['member_id'];
			$favorites_list = $favorites_model->getStoreFavoritesList($condition);
			unset($condition);
			$store_list = array();
			if (!empty($favorites_list)){
				$vid = array();
				foreach ($favorites_list as $v){
					$vid[] = $v['fav_id'];
				}
				//查询商品信息
				$store_model = Model('vendor');
				$condition = array();
				$condition['vid'] = array('in', $vid);
				$store_list = $store_model->getStoreOnlineList($condition);
			}
			//验证码
			Template::output('sldcode',substr(md5(MALL_SITE_URL.$_GET['app'].$_GET['mod']),0,8));
			Template::output('vendorlist',$store_list);
			Template::showpage('member_snssharestore','null_layout');
		}
	}
	/**
	 * 删除动态
	 */
	public function deltrace(){
		$id = intval($_GET['id']);
		if ($id <= 0){
			showDialog(Language::get('参数错误'),'','error');
		}
		$tracelog_model = Model('sns_tracelog');
		//删除动态
		$condition = array();
		$condition['trace_id'] = "$id";
		$condition['trace_memberid'] = "{$_SESSION['member_id']}";
		$result = $tracelog_model->delTracelog($condition);
		if ($result){
			//修改该动态的转帖信息
			$tracelog_model->tracelogEdit(array('trace_originalstate'=>'1'),array('trace_originalid'=>"$id"));
			//删除对应的评论
			$comment_model = Model('sns_comment');
			$condition = array();
			$condition['comment_originalid'] = "$id";
			$condition['comment_originaltype'] = "0";
			$comment_model->delComment($condition);
			if ($_GET['type'] == 'href'){
				showDialog(Language::get('删除成功'),'index.php?app=userhome&mod=trace&mid='.$_SESSION['member_id'],'succ');
			}else {
				$js = "location.reload();";
				showDialog(Language::get('删除成功'),'','succ',$js);
			}
		} else {
			showDialog(Language::get('删除失败'),'','error');
		}
	}
	/**
	 * SNS动态列表
	 */
	public function tracelist(){
		//查询关注以及好友列表
		$friend_model = Model('sns_friend');
		$friend_list = $friend_model->listFriend(array('friend_frommid'=>"{$_SESSION['member_id']}"),'*','','simple');
		$mutualfollowid_arr = array();
		$followid_arr = array();
		if (!empty($friend_list)){
			foreach ($friend_list as $k=>$v){
				$followid_arr[] = $v['friend_tomid'];
				if ($v['friend_followstate'] == 2){
					$mutualfollowid_arr[] = $v['friend_tomid'];
				}
			}
		}
		$tracelog_model = Model('sns_tracelog');
		//条件
		$condition = array();
		$condition['allowshow'] = '1';
		$condition['allowshow_memberid'] = "{$_SESSION['member_id']}";
		$condition['allowshow_followerin'] = "";
		if (!empty($followid_arr)){
			$condition['allowshow_followerin'] = implode("','",$followid_arr);
		}
		$condition['allowshow_friendin'] = "";
		if (!empty($mutualfollowid_arr)){
			$condition['allowshow_friendin'] = implode("','",$mutualfollowid_arr);
		}
		$condition['trace_state'] = "0";
		$count = $tracelog_model->countTrace($condition);
		//分页
		$page	= new Page();
		$page->setEachNum(30);
		$page->setStyle('admin');
		$page->setTotalNum($count);
		$delaypage = intval($_GET['delaypage'])>0?intval($_GET['delaypage']):1;//本页延时加载的当前页数
		$lazy_arr = lazypage(10,$delaypage,$count,true,$page->getNowPage(),$page->getEachNum(),$page->getLimitStart());
		//动态列表
		$condition['limit'] = $lazy_arr['limitstart'].",".$lazy_arr['delay_eachnum'];
		$tracelist = $tracelog_model->getTracelogList($condition);
		if (!empty($tracelist)){
			foreach ($tracelist as $k=>$v){
				if ($v['trace_title']){
					$v['trace_title'] = str_replace("%siteurl%", MALL_SITE_URL.DS, $v['trace_title']);
					$v['trace_title_forward'] = '|| @'.$v['trace_membername'].Language::get('：').preg_replace("/<a(.*?)href=\"(.*?)\"(.*?)>@(.*?)<\/a>([\s|:]|$)/is",'@${4}${5}',$v['trace_title']);
				}
				if(!empty($v['trace_content'])){
					//替换内容中的siteurl
					$v['trace_content'] = str_replace("%siteurl%", MALL_SITE_URL.DS, $v['trace_content']);
				}
				$tracelist[$k] = $v;
			}
		}
		Template::output('hasmore',$lazy_arr['hasmore']);
		Template::output('tracelist',$tracelist);
		Template::output('show_page',$page->show());
		Template::output('type','index');
		Template::showpage('member_snstracelist','null_layout');
	}
	/**
	 * 编辑分享商品的可见权限(主人登录后操作)
	 */
	public function editprivacy(){
		$id = intval($_GET['id']);
		if ($id <= 0){
			showDialog(Language::get('参数错误'),'','error');
		}
		$sharegoods_model = Model("sns_sharegoods");
		$condition = array();
		$condition['share_id'] = "$id";
		$condition['share_memberid'] = "{$_SESSION['member_id']}";
		$privacy = in_array($_GET['privacy'],array(0,1,2))?$_GET['privacy']:0;
		$result = $sharegoods_model->editSharegoods(array('share_privacy'=>"$privacy"),$condition);
		if ($result){
			$privacy_item = $privacy+1;
			$js = "var obj = $(\"#recordone_{$id}\").find(\"[bbc_type='privacytab']\"); $(obj).find('span').removeClass('selected');$(obj).find('li:nth-child(".$privacy_item.")').find('span').addClass('selected');";
			showDialog(Language::get('设置成功'),'','succ',$js);
		}else {
			showDialog(Language::get('设置失败'),'','error');
		}
	}
	/**
	 * 删除分享和喜欢商品
	 */
	public function delgoods(){
		$id = intval($_GET['id']);
		if ($id <= 0){
			showDialog(Language::get('参数错误'),'','error');
		}
		$sharegoods_model = Model("sns_sharegoods");
		//查询分享和喜欢商品信息
		$condition = array();
		$condition['share_id'] = "$id";
		$condition['share_memberid'] = "{$_SESSION['member_id']}";
		if ($_GET['type'] == 'like'){//删除喜欢
			$condition['share_islike'] = "1";
		}elseif ($_GET['type'] == 'share'){
			$condition['share_isshare'] = "1";
		}
		$sharegoods_info = $sharegoods_model->getSharegoodsInfo($condition);
		if (empty($sharegoods_info)){
			showDialog(Language::get('删除失败'),'','error');
		}
		unset($condition);
		$update_arr = array();
		if ($_GET['type'] == 'like'){//删除喜欢
			$update_arr['share_islike'] = "0";
		}elseif ($_GET['type'] == 'share'){
			$update_arr['share_isshare'] = "0";
		}
		$result = $sharegoods_model->editSharegoods($update_arr,array('share_id'=>"{$sharegoods_info['share_id']}"));
		if ($result){
			//更新SNS商品喜欢次数
			if ($_GET['type'] == 'like'){
				$snsgoods_model = Model('sns_goods');
				$snsgoods_info = $snsgoods_model->getGoodsInfo(array('snsgoods_goodsid'=>"{$sharegoods_info['share_goodsid']}"));
				if (!empty($snsgoods_info)){
					$update_arr = array();
					$update_arr['snsgoods_likenum'] = (intval($snsgoods_info['snsgoods_likenum'])-1)>0?(intval($snsgoods_info['snsgoods_likenum'])-1):0;
					$likemember_arr = array();
					if (!empty($snsgoods_info['snsgoods_likemember'])){
						$likemember_arr = explode(',',$snsgoods_info['snsgoods_likemember']);
						unset($likemember_arr[array_search($_SESSION['member_id'],$likemember_arr)]);
					}
					$update_arr['snsgoods_likemember'] = implode(',',$likemember_arr);
					$snsgoods_model->editGoods($update_arr,array('snsgoods_goodsid'=>"{$snsgoods_info['snsgoods_goodsid']}"));
				}
			}
			$js = "location.reload();";
			showDialog(Language::get('删除成功'),'','succ',$js);
		}else {
			showDialog(Language::get('删除失败'),'','error');
		}
	}
	/**
	 * 删除分享店铺
	 */
	public function delstore(){
		$id = intval($_GET['id']);
		if ($id <= 0){
			showDialog(Language::get('参数错误'),'','error');
		}
		$sharestore_model = Model("sns_sharestore");
		//删除分享店铺信息
		$condition = array();
		$condition['share_id'] = "$id";
		$condition['share_memberid'] = "{$_SESSION['member_id']}";
		$result = $sharestore_model->delSharestore($condition);
		if ($result){
			$js = "location.reload();";
			showDialog(Language::get('删除成功'),'','succ',$js);
		}else {
			showDialog(Language::get('删除失败'),'','error');
		}
	}
	/**
	 * 编辑分享店铺的可见权限(主人登录后操作)
	 */
	public function storeprivacy(){
		$id = intval($_GET['id']);
		if ($id <= 0){
			showDialog(Language::get('参数错误'),'','error');
		}
		$sharestore_model = Model("sns_sharestore");
		$condition = array();
		$condition['share_id'] = "$id";
		$condition['share_memberid'] = "{$_SESSION['member_id']}";
		$privacy = in_array($_GET['privacy'],array(0,1,2))?$_GET['privacy']:0;
		$result = $sharestore_model->editSharestore(array('share_privacy'=>"$privacy"),$condition);
		if ($result){
			$privacy_item = $privacy+1;
			$js = "var obj = $(\"#recordone_{$id}\").find(\"[bbc_type='privacytab']\"); $(obj).find('span').removeClass('selected');$(obj).find('li:nth-child(".$privacy_item.")').find('span').addClass('selected');";
			showDialog(Language::get('设置成功'),'','succ',$js);
		}else {
			showDialog(Language::get('设置失败'),'','error');
		}
	}
	/**
	 * 添加评论(访客登录后操作)
	 */
	public function addcomment(){
		$originalid = intval($_POST["originalid"]);
		if($originalid <= 0){
			showDialog(Language::get('参数错误'),'','error');
		}
		$obj_validate = new Validate();
		$originaltype = intval($_POST['originaltype'])>0?intval($_POST['originaltype']):0;
		$validate_arr[] = array("input"=>$_POST["commentcontent"], "require"=>"true","message"=>Language::get('需要评论点内容~'));
		$validate_arr[] = array("input"=>$_POST["commentcontent"], "validator"=>'Length',"min"=>0,"max"=>140,"message"=>Language::get('不能超过140字'));
		//评论数超过最大次数出现验证码
		if(intval(cookie('commentnum'))>=self::MAX_RECORDNUM){
			$validate_arr[] = array("input"=>$_POST["captcha"], "require"=>"true","message"=>Language::get('请填写验证码'));
		}
		$obj_validate -> validateparam = $validate_arr;
		$error = $obj_validate->validate();
		if ($error != ''){
			showDialog($error,'','error');
		}
		//发帖数超过最大次数出现验证码
		if(intval(cookie('commentnum'))>=self::MAX_RECORDNUM){
			if (!checkSeccode($_POST['sldcode'],$_POST['captcha'])){
				showDialog(Language::get('验证码错误验证码错误'),'','error');
			}
		}
		//查询会员信息
		$member_model = Model('member');
		$member_info = $member_model->infoMember(array('member_id'=>"{$_SESSION['member_id']}",'member_state'=>'1'));
		if (empty($member_info)){
			showDialog(Language::get('会员信息错误'),'','error');
		}
		$owner_id = 0;
		if ($originaltype == 1){
			//查询分享和喜欢商品信息
			$sharegoods_model = Model('sns_sharegoods');
			$sharegoods_info = $sharegoods_model->getSharegoodsInfo(array('share_id'=>"{$originalid}"));
			if (empty($sharegoods_info)){
				showDialog(Language::get('评论失败，请重试'),'','error');
			}
			$owner_id = $sharegoods_info['share_memberid'];
		}else {
			//查询原帖信息
			$tracelog_model = Model('sns_tracelog');
			$tracelog_info = $tracelog_model->getTracelogRow(array('trace_id'=>"{$originalid}",'trace_state'=>'0'));
			if (empty($tracelog_info)){
				showDialog(Language::get('评论失败，请重试'),'','error');
			}
			$owner_id = $tracelog_info['trace_memberid'];
		}
		$comment_model = Model('sns_comment');
		$insert_arr = array();
		$insert_arr['comment_memberid'] = $_SESSION['member_id'];
		$insert_arr['comment_membername'] = $_SESSION['member_name'];
		$insert_arr['comment_memberavatar'] = $member_info['member_avatar'];
		$insert_arr['comment_originalid'] = $originalid;
		$insert_arr['comment_originaltype'] = $originaltype;
		$insert_arr['comment_content'] = $_POST['commentcontent'];
		$insert_arr['comment_addtime'] = time();
		$insert_arr['comment_ip'] = getIp();
		$insert_arr['comment_state'] = '0';//正常
		$result = $comment_model->commentAdd($insert_arr);
		if ($result){
			if ($originaltype == 1){
				//更新商品的评论数
				$update_arr = array();
				$update_arr['share_commentcount'] = array('sign'=>'increase','value'=>'1');
				$sharegoods_model->editSharegoods($update_arr,array('share_id'=>"{$originalid}"));
			}else {
				//更新动态统计信息
				$update_arr = array();
				$update_arr['trace_commentcount'] = array('sign'=>'increase','value'=>'1');
				if (intval($tracelog_info['trace_originalid'])== 0){
					$update_arr['trace_orgcommentcount'] = array('sign'=>'increase','value'=>'1');
				}
				$tracelog_model->tracelogEdit($update_arr,array('trace_id'=>"$originalid"));
				unset($update_arr);
				//更新所有转帖的原帖评论次数
				if (intval($tracelog_info['trace_originalid'])== 0){
					$tracelog_model->tracelogEdit(array('trace_orgcommentcount'=>$tracelog_info['trace_orgcommentcount']+1),array('trace_originalid'=>"$originalid"));
				}
			}
			//建立cookie
			if (cookie('commentnum') != null && intval(cookie('commentnum')) >0){
				setBbcCookie('commentnum',intval(cookie('commentnum'))+1,2*3600);//保存2小时
			}else{
				setBbcCookie('commentnum',1,2*3600);//保存2小时
			}
			$js = "$(\"#content_comment{$originalid}\").val('');";
			if ($_POST['showtype'] == 1){
				$js .="$(\"#tracereply_{$originalid}\").load('index.php?app=userhome&mod=commenttop&mid={$owner_id}&id={$originalid}&type={$originaltype}');";
			}else {
				$js .="$(\"#tracereply_{$originalid}\").load('index.php?app=userhome&mod=commentlist&mid={$owner_id}&id={$originalid}&type={$originaltype}');";
			}
			showDialog(Language::get('评论成功'),'','succ',$js);
		}
	}
	/**
	 * 删除评论(访客登录后操作)
	 */
	public function delcomment(){
		$id = intval($_GET['id']);
		if ($id <= 0){
			showDialog(Language::get('参数错误'),'','error');
		}
		$comment_model = Model('sns_comment');
		//查询评论信息
		$comment_info = $comment_model->getCommentRow(array('comment_id'=>"$id",'comment_memberid'=>"{$_SESSION['member_id']}"));
		if (empty($comment_info)){
			showDialog(Language::get('评论信息错误'),'','error');
		}
		//删除评论
		$condition = array();
		$condition['comment_id'] = "$id";
		$result = $comment_model->delComment($condition);
		if ($result){
			if ($comment_info['comment_originaltype'] == 1){
				//更新商品评论数
				$sharegoods_model = Model('sns_sharegoods');
				$update_arr = array();
				$update_arr['share_commentcount'] = array('sign'=>'decrease','value'=>'1');
				$sharegoods_model->editSharegoods($update_arr,array('share_id'=>"{$comment_info['comment_originalid']}"));
			}else {
				//更新动态统计信息
				$tracelog_model = Model('sns_tracelog');
				$update_arr = array();
				$update_arr['trace_commentcount'] = array('sign'=>'decrease','value'=>'1');
				$tracelog_model->tracelogEdit($update_arr,array('trace_id'=>"{$comment_info['comment_originalid']}"));
			}
			$js .="$('.comment-list [bbc_type=\"commentrow_{$id}\"]').remove();";
			showDialog(Language::get('删除成功'),'','succ',$js);
		}else {
			showDialog(Language::get('删除失败'),'','error');
		}
	}
	/**
	 * 喜欢商品(访客登录后操作)
	 */
	public function editlike(){
		$obj_validate = new Validate();
		$validate_arr[] = array("input"=>$_GET["id"], "require"=>"true","message"=>Language::get('选择一件喜欢的商品呗~'));
		$obj_validate -> validateparam = $validate_arr;
		$error = $obj_validate->validate();
		if ($error != ''){
			showDialog($error,'','error');
		}
		//查询会员信息
		$member_model = Model('member');
		$member_info = $member_model->infoMember(array('member_id'=>"{$_SESSION['member_id']}",'member_state'=>'1'));
		if (empty($member_info)){
			showDialog(Language::get('会员信息错误'),'','error');
		}
		//查询商品信息
		$goods_model = Model('goods');
		$condition = array();
		$condition['gid'] = intval($_GET["id"]);
		$goods_info = $goods_model->getGoodsOnlineInfo($condition,'gid,goods_name,goods_image,goods_price,goods_freight,goods_collect,vid,store_name');
		if (empty($goods_info)){
			showDialog(Language::get('商品信息错误'),'','error');
		}
		$sharegoods_model = Model('sns_sharegoods');
		//判断该商品是否已经存在分享记录
		$sharegoods_info = $sharegoods_model->getSharegoodsInfo(array('share_memberid'=>"{$_SESSION['member_id']}",'share_goodsid'=>"{$goods_info['gid']}"));
		if (!empty($sharegoods_info) && $sharegoods_info['share_islike'] == 1){
			showDialog(Language::get('您已经喜欢过了'),'','error');
		}
		if (empty($sharegoods_info)){
			//添加分享商品信息
			$insert_arr = array();
			$insert_arr['share_goodsid'] = $goods_info['gid'];
			$insert_arr['share_memberid'] = $_SESSION['member_id'];
			$insert_arr['share_membername'] = $_SESSION['member_name'];
			$insert_arr['share_content'] = '';
			$insert_arr['share_likeaddtime'] = time();
			$insert_arr['share_privacy'] = 0;
			$insert_arr['share_commentcount'] = 0;
			$insert_arr['share_islike'] = 1;
			$result = $sharegoods_model->sharegoodsAdd($insert_arr);
			unset($insert_arr);
		}else {
			//更新分享商品信息
			$update_arr = array();
			$update_arr['share_likeaddtime'] = time();
			$update_arr['share_islike'] = 1;
			$result = $sharegoods_model->editSharegoods($update_arr,array('share_id'=>"{$sharegoods_info['share_id']}"));
			unset($update_arr);
		}
		if ($result){
			//商品缓存数据更新
			//生成缓存的键值
			$hash_key = $goods_info['gid'];
			//先查找$hash_key缓存
			if ($_cache = rcache($hash_key,'product')){
				$_cache['likenum'] = intval($_cache['likenum'])+1;
				//缓存商品信息
				wmemcache($hash_key,$_cache,'product');
			}
			//更新SNS商品表信息
			$snsgoods_model = Model('sns_goods');
			$snsgoods_info = $snsgoods_model->getGoodsInfo(array('snsgoods_goodsid'=>"{$goods_info['gid']}"));
			if (empty($snsgoods_info)){
				//添加SNS商品
				$insert_arr = array();
				$insert_arr['snsgoods_goodsid'] = $goods_info['gid'];
				$insert_arr['snsgoods_goodsname'] = $goods_info['goods_name'];
				$insert_arr['snsgoods_goodsimage'] = $goods_info['goods_image'];
				$insert_arr['snsgoods_goodsprice'] = $goods_info['goods_price'];
				$insert_arr['snsgoods_storeid'] = $goods_info['vid'];
				$insert_arr['snsgoods_storename'] = $goods_info['store_name'];
				$insert_arr['snsgoods_addtime'] = time();
				$insert_arr['snsgoods_likenum'] = 1;
				$insert_arr['snsgoods_likemember'] = "{$_SESSION['member_id']}";
				$insert_arr['snsgoods_sharenum'] = 0;
				$snsgoods_model->goodsAdd($insert_arr);
				unset($insert_arr);
			}else {
				//更新SNS商品
				$update_arr = array();
				$update_arr['snsgoods_likenum'] = intval($snsgoods_info['snsgoods_likenum'])+1;
				$likemember_arr = array();
				if (!empty($snsgoods_info['snsgoods_likemember'])){
					$likemember_arr = explode(',',$snsgoods_info['snsgoods_likemember']);
				}
				$likemember_arr[] = $_SESSION['member_id'];
				$update_arr['snsgoods_likemember'] = implode(',',$likemember_arr);
				$snsgoods_model->editGoods($update_arr,array('snsgoods_goodsid'=>"{$goods_info['gid']}"));
			}
			//添加喜欢动态
			$tracelog_model = Model('sns_tracelog');
			$insert_arr = array();
			$insert_arr['trace_originalid'] = '0';
			$insert_arr['trace_originalmemberid'] = '0';
			$insert_arr['trace_memberid'] = $_SESSION['member_id'];
			$insert_arr['trace_membername'] = $_SESSION['member_name'];
			$insert_arr['trace_memberavatar'] = $member_info['member_avatar'];
			$insert_arr['trace_title'] = Language::get('我很喜欢这个哦~');
			$content_str = '';
			$content_str .= "<div class=\"fd-media\">
				<div class=\"goodsimg\"><a style=\"display: table-cell;vertical-align: middle;width: 120px;height: 120px;\" target=\"_blank\" href=\"".urlShop('goods', 'index', array('gid'=>$goods_info['gid']))."\"><img src=\"".thumb($goods_info, 240)."\" style=\"max-width:120px;max-height:120px;\" alt=\"{$goods_info['goods_name']}\"></a></div>
				<div class=\"goodsinfo\">
					<dl>
						<dt><a target=\"_blank\" href=\"".urlShop('goods', 'index', array('gid'=>$goods_info['gid']))."\">".$goods_info['goods_name']."</a></dt>
						<dd>".Language::get('价&nbsp;&nbsp;格').Language::get('：').Language::get('元').$goods_info['goods_price']."</dd>
						<dd>".Language::get('运&nbsp;&nbsp;费').Language::get('：').Language::get('元').$goods_info['goods_freight']."</dd>
                  		<dd bbctype=\"collectbtn_{$goods_info['gid']}\"><a href=\"javascript:void(0);\" onclick=\"javascript:collect_goods(\'{$goods_info['gid']}\',\'succ\',\'collectbtn_{$goods_info['gid']}\');\">".Language::get('收藏该宝贝')."</a>&nbsp;&nbsp;(".$goods_info['goods_collect'].Language::get('人收藏').")</dd>
                  	</dl>
                  </div>
             </div>";
			$insert_arr['trace_content'] = $content_str;
			$insert_arr['trace_addtime'] = time();
			$insert_arr['trace_state'] = '0';
			$insert_arr['trace_privacy'] = 0;
			$insert_arr['trace_commentcount'] = 0;
			$insert_arr['trace_copycount'] = 0;
			$result = $tracelog_model->tracelogAdd($insert_arr);
			$js = "var obj = $(\"#likestat_{$goods_info['gid']}\"); $(\"#likestat_{$goods_info['gid']}\").find('i').addClass('noaction');$(obj).find('a').addClass('noaction'); var countobj=$('[bbc_type=\'likecount_{$goods_info['gid']}\']');$(countobj).html(parseInt($(countobj).text())+1);";
			showDialog(Language::get('操作成功'),'','succ',$js);
		}else {
			showDialog(Language::get('操作失败'),'','error');
		}
	}
	/**
	 * 添加转发
	 */
	public function addforward(){
		$obj_validate = new Validate();
		$originalid = intval($_POST["originalid"]);
		$validate_arr[] = array("input"=>$originalid, "require"=>"true",'validator'=>'Compare',"operator"=>' > ','to'=>0,"message"=>Language::get('转发失败，请重试'));
		$validate_arr[] = array("input"=>$_POST["forwardcontent"], "validator"=>'Length',"min"=>0,"max"=>140,"message"=>Language::get('不能超过140字'));
		//发帖数超过最大次数出现验证码
		if(intval(cookie('forwardnum'))>=self::MAX_RECORDNUM){
			$validate_arr[] = array("input"=>$_POST["captcha"], "require"=>"true","message"=>Language::get('请填写验证码'));
		}
		$obj_validate -> validateparam = $validate_arr;
		$error = $obj_validate->validate();
		if ($error != ''){
			showDialog($error,'','error');
		}
		//发帖数超过最大次数出现验证码
		if(intval(cookie('forwardnum'))>=self::MAX_RECORDNUM){
			if (!checkSeccode($_POST['sldcode'],$_POST['captcha'])){
				showDialog(Language::get('验证码错误验证码错误'),'','error');
			}
		}
		//查询会员信息
		$member_model = Model('member');
		$member_info = $member_model->infoMember(array('member_id'=>"{$_SESSION['member_id']}",'member_state'=>'1'));
		if (empty($member_info)){
			showDialog(Language::get('会员信息错误'),'','error');
		}
		//查询原帖信息
		$tracelog_model = Model('sns_tracelog');
		$tracelog_info = $tracelog_model->getTracelogRow(array('trace_id'=>"{$originalid}",'trace_state'=>"0"));
		if (empty($tracelog_info)){
			showDialog(Language::get('转发失败，请重试'),'','error');
		}
		$insert_arr = array();
		$insert_arr['trace_originalid'] = $tracelog_info['trace_originalid']>0?$tracelog_info['trace_originalid']:$originalid;//如果被转发的帖子为原帖的话，那么为原帖ID；如果被转发的帖子为转帖的话，那么为该转帖的原帖ID（即最初始帖子ID）
		$insert_arr['trace_originalmemberid'] = $tracelog_info['trace_originalid']>0?$tracelog_info['trace_originalmemberid']:$tracelog_info['trace_memberid'];
		$insert_arr['trace_memberid'] = $_SESSION['member_id'];
		$insert_arr['trace_membername'] = $_SESSION['member_name'];
		$insert_arr['trace_memberavatar'] = $member_info['member_avatar'];
		$insert_arr['trace_title'] = $_POST['forwardcontent']?$_POST['forwardcontent']:Language::get('转发');
		if ($tracelog_info['trace_originalid'] > 0 || $tracelog_info['trace_from'] != 1){
			$insert_arr['trace_content'] = addslashes($tracelog_info['trace_content']);
		}else {
			$content_str ="<div class=\"title\"><a href=\"%siteurl%index.php?app=userhome&mid={$tracelog_info['trace_memberid']}\" target=\"_blank\" class=\"uname\">{$tracelog_info['trace_membername']}</a>";
			$content_str .= Language::get('：')."{$tracelog_info['trace_title']}</div>";
			$content_str .=addslashes($tracelog_info['trace_content']);
			$insert_arr['trace_content'] = $content_str;
		}
		$insert_arr['trace_addtime'] = time();
		$insert_arr['trace_state'] = '0';
		if ($tracelog_info['trace_privacy'] >0){
			$insert_arr['trace_privacy'] = 2;//因为动态可见权限跟转帖功能，本身就是矛盾的，为了防止可见度无法控制，所以如果原帖不为所有人可见，那么转帖的动态权限就为仅自己可见，否则为所有人可见
		}else {
			$insert_arr['trace_privacy'] = 0;
		}
		$insert_arr['trace_commentcount'] = 0;
		$insert_arr['trace_copycount'] = 0;
		$insert_arr['trace_orgcommentcount'] = $tracelog_info['trace_orgcommentcount'];
		$insert_arr['trace_orgcopycount'] = $tracelog_info['trace_orgcopycount'];
		$result = $tracelog_model->tracelogAdd($insert_arr);
		if ($result){
			//更新动态转发次数
			$tracelog_model = Model('sns_tracelog');
			$update_arr = array();
			$update_arr['trace_copycount'] = array('sign'=>'increase','value'=>'1');
			$update_arr['trace_orgcopycount'] = array('sign'=>'increase','value'=>'1');
			$condition = array();
			//原始贴和被转帖都增加转帖次数
			if ($tracelog_info['trace_originalid'] > 0){
				$condition['traceid_in'] = "{$tracelog_info['trace_originalid']}','{$originalid}";
			}else {
				$condition['trace_id'] = "$originalid";
			}
			$tracelog_model->tracelogEdit($update_arr,$condition);
			unset($condition);
			//更新所有转帖的原帖转发次数
			$condition = array();
			//原始贴和被转帖都增加转帖次数
			if ($tracelog_info['trace_originalid'] > 0){
				$condition['trace_originalid'] = "{$tracelog_info['trace_originalid']}";
			}else {
				$condition['trace_originalid'] = "$originalid";
			}
			$tracelog_model->tracelogEdit(array('trace_orgcopycount'=>$tracelog_info['trace_orgcopycount']+1),$condition);
			if ($_GET['irefresh']){
				//建立cookie
				if (cookie('forwardnum') != null && intval(cookie('forwardnum')) >0){
					setBbcCookie('forwardnum',intval(cookie('forwardnum'))+1,2*3600);//保存2小时
				}else{
					setBbcCookie('forwardnum',1,2*3600);//保存2小时
				}
				if ($_GET['type']=='home'){
					$js = "$('#friendtrace').lazyshow({url:\"index.php?app=userhome&mod=tracelist&mid={$tracelog_info['trace_memberid']}&pn=1\",'iIntervalId':true});";
				}else if ($_GET['type']=='snshome'){
					$js = "$('#forward_".$originalid."').hide();";
				}else {
					$js = "$('#friendtrace').lazyshow({url:\"index.php?app=usercenter&mod=tracelist&pn=1\",'iIntervalId':true});";
				}
				showDialog(Language::get('转发成功'),'','succ',$js);
			}else {
				showDialog(Language::get('转发成功'),'','succ');
			}
		}else {
			showDialog(Language::get('转发失败，请重试'),'','error');
		}
	}
	/**
	 * 商品收藏页面和商品详细页面分享商品
	 */
	public function sharegoods_one(){
		Language::read('usershare');
		$gid = intval($_GET['gid']);
		if ($gid<=0){
			showDialog(Language::get('参数错误'),'','error');
		}
		if ($_GET['dialog']){
			$js = "CUR_DIALOG = ajax_form('sharegoods', '".Language::get('分享给好友')."', 'index.php?app=usercenter&mod=sharegoods_one&gid={$gid}', 480);";
			showDialog('','','js',$js);
		}
		//查询商品信息
		$goods_info = Model('goods')->getGoodsInfo(array('gid'=>$gid));
		//判断系统是否开启站外分享功能
		if (C('share_isuse') == 1){
			//站外分享接口
			$model = Model('sns_binding');
			$app_arr = $model->getUsableApp($_SESSION['member_id']);
			Template::output('app_arr',$app_arr);
		}
		//信息输出
		Template::output('sldcode',substr(md5(MALL_SITE_URL.$_GET['app'].$_GET['mod']),0,8));
		Template::output('goods_info',$goods_info);
		Template::showpage('member_snssharegoods_one','null_layout');
	}
	/**
	 * 店铺收藏页面分享店铺
	 */
	public function sharestore_one(){
		Language::read('usershare');
		$sid = intval($_GET['sid']);
		if ($sid<=0){
			showDialog(Language::get('参数错误'),'','error');
		}
		if ($_GET['dialog']){
			$js = "ajax_form('sharestore', '".Language::get('分享店铺')."', 'index.php?app=usercenter&mod=sharestore_one&sid={$sid}', 480);";
			showDialog('','','js',$js);
		}
		//查询店铺信息
		$store_model = Model('vendor');
        $store_info = $store_model->getStoreInfoByID($sid);
		if (empty($store_info) || $store_info['store_state'] == 0){
			showDialog(Language::get('店铺关闭，无法进行分享'),'','error');
		}
		$store_info['store_url'] = urlShop('vendor', 'index', array('vid'=>$store_info['vid']));
		//判断系统是否开启站外分享功能
		if (C('share_isuse') == 1){
		    //站外分享接口
		    $model = Model('sns_binding');
		    $app_arr = $model->getUsableApp($_SESSION['member_id']);
		    Template::output('app_arr',$app_arr);
		}
		//信息输出
		Template::output('sldcode',substr(md5(MALL_SITE_URL.$_GET['app'].$_GET['mod']),0,8));
		Template::output('store_info',$store_info);
		Template::showpage('member_snssharestore_one','null_layout');
	}

}
