<?php
/**
 * 手机首页 多模板 接口
 *
 *
 */
defined('DYMall') or exit('Access Invalid!');
class sld_cwap_homeCtl extends SystemCtl{

	public function __construct(){
		parent::__construct();
	}

	/**
	 * 编辑联到家首页接口
	 */
	public function home_edit() {
		$model_cwap_home = M('cwap_home','ldj');
		$model_goods = Model('goods');
		$home_id = $_GET['id'];
		$home_info_list = $model_cwap_home->getHomeInfoByHomeID($home_id);
		$home_list_new = array();
		$home_list_new['special_id'] = $home_info_list['home_id'];
		$home_list_new['special_desc'] = $home_info_list['home_desc'];
		$home_list_new['sousuocolor'] = $home_info_list['home_sousuo_color'];
		$home_list_new['botnavcolor'] = $home_info_list['home_botnav_color'];
//		file_put_contents('cwaphome_edit.txt',$home_info_list['home_data'],FILE_APPEND);
//		file_put_contents('cwaphome_edit.txt','\n************'.date('Y-m-d H:i:s',time()).'************\n',FILE_APPEND);
		$use_fixed_search_type = false;

		foreach (unserialize($home_info_list['home_data']) as $key => $val){
			if(isset($val['data']) && !empty($val['data'])){
				foreach ($val['data'] as $i_k => $i_v) {
					if(isset($i_v['img'])){
						$i_v['img'] = (strpos($i_v['img'],'http') !==false) ? $i_v['img'] : getMbSpecialImageUrl($i_v['img']);
						$val['data'][$i_k] = $i_v;
					}
				}
			}
			if($val['type'] == 'tuijianshangpin'){
				//推荐商品如果没有商品的话这个数据就在前台展示了
				if(!empty($val['data']['gid'])&&is_array($val['data']['gid'])){
					//根据gid获取商品的pic  name  price
					foreach ($val['data']['gid'] as $k => $v){
						$goods_info = $model_goods -> getGoodsOnlineInfoByID($v,'gid,goods_name,goods_promotion_price,goods_price,goods_image');
						if(!empty($goods_info)){
							$goods_info['goods_image'] = thumb($goods_info, 320);
							$val['data']['goods_info'][] = $goods_info;
						}
					}

					// 获取最终价格
					$val['data']['goods_info'] = Model('goods_activity')->rebuild_goods_data($val['data']['goods_info']);

					$home_list_new['item_data'][] = $val;
				}
			}else if($val['type'] == 'nav'){
				$nav_data = array();
				$nav_data['type'] = $val['type'];
				$nav_data['style_set'] = $val['style_set'];
				$nav_data['icon_set'] = $val['icon_set'];
				$nav_data['slide'] = $val['slide'];
				foreach ( $val['data'] as $nav_k => $nav_v){
					$nav_data['data'][] = $nav_v;
				}
				$home_list_new['item_data'][] = $nav_data;
			}else if($val['type'] == 'dapei'){
				if(!empty($val['data']['gid'])&&is_array($val['data']['gid'])){
					//根据gid获取商品的pic  name  price
					foreach ($val['data']['gid'] as $k => $v){
						$goods_info = $model_goods -> getGoodsOnlineInfoByID($v,'gid,goods_name,goods_promotion_price,goods_price,goods_image');
						if(!empty($goods_info)){
							$goods_info['goods_image'] = thumb($goods_info, 320);
							$val['data']['goods_info'][] = $goods_info;
						}
					}

					// 获取最终价格
					$val['data']['goods_info'] = Model('goods_activity')->rebuild_goods_data($val['data']['goods_info']);

					$home_list_new['item_data'][] = $val;
				}else{
					$home_list_new['item_data'][] = array();
				}
			}else if($val['type'] == 'tupianzuhe'){
				$tpzh_data = array();
				$tpzh_data['type'] = $val['type'];
				$tpzh_data['sele_style'] = $val['sele_style'];
				foreach ( $val['data'] as $tpzh_k => $tpzh_v){
					$tpzh_data['data'][] = $tpzh_v;
				}
				$home_list_new['item_data'][] = $tpzh_data;
			}else if($val['type'] == 'lunbo'){
				$lunbo_data = array();
				$lunbo_data['type'] = $val['type'];
				foreach ( $val['data'] as $lunbo_k => $lunbo_v){
					$lunbo_data['data'][] = $lunbo_v;
				}
				$home_list_new['item_data'][] = $lunbo_data;
			}else if($val['type'] == 'huodong'){
				$huodong_data = array();
				$huodong_data['type'] = $val['type'];
				$huodong_data['sele_style'] = $val['sele_style'];

				$use_fixed_search_type = true;

				switch ($huodong_data['sele_style']) {
					case '1':
						// 限时折扣
						$model_xian = Model('p_xianshi_goods');
						$xianCondition = array();
						$xianCondition['state'] = $model_xian::XIANSHI_GOODS_STATE_NORMAL;
						$xianCondition['start_time'] = array('lt', TIMESTAMP);
						$xianCondition['end_time'] = array('gt', TIMESTAMP);
						$xian_goods_list = $model_xian->getXianshiGoodsList($xianCondition);
						$extend_data_list = array();
						$goods_ids = array();
						if (!empty($xian_goods_list)) {

							foreach ($xian_goods_list as $key => $value) {
								$goods_ids[] = $value['gid'];
								$value['sld_end_time'] = date("Y-m-d H:i:s",$value['end_time']);
								$extend_data_list[$value['gid']] = $value;
							}
						}
						break;
					case '2':
						// 团购
						$model_tuan = Model('tuan');
						$tuanCondition = array();
						$tuan_goods_list = $model_tuan->getTuanOnlineList($tuanCondition,'','','gid,tuan_price,virtual_quantity,buy_quantity,tuan_discount,vid');
						$extend_data_list = array();
						$goods_ids = array();
						foreach ($tuan_goods_list as $key => $value) {
							$value['sld_end_time'] = (strtotime($value['end_time_text']) > time()) ? $value['end_time_text'] : '';
							$value['buyed_quantity'] = $value['virtual_quantity'] + $value['buy_quantity'];
							$goods_ids[] = $value['gid'];
							$extend_data_list[$value['gid']] = $value;
						}
						break;

					default:
						// 拼团
						// 获取拼团 类型的商品(bbc_goods) id

						$allow_search_type = array(1);

						$model_pin = M('pin');
						$pinCondition = array();
						$pin_goods_list = $model_pin->getPinList($pinCondition,0);
						$extend_data_list = array();
						$goods_ids = array();
						foreach ($pin_goods_list as $key => $value) {
							$goods_ids[] = $value['gid'];
							$extend_data_list[$value['gid']] = $value;
						}
						break;
				}

				if (isset($val['data']) && is_array($val['data']) && !empty($val['data'])) {
					foreach ( $val['data'] as $huodong_k => $huodong_v){
						foreach ($huodong_v as $huodong_a_k => $huodong_a_v) {
							if (is_array($huodong_a_v) && !empty($huodong_a_v)) {
								foreach ($huodong_a_v as $huodong_b_k => $huodong_b_v) {
									if(isset($huodong_b_v['gid'])){
										if (is_array($huodong_b_v['gid']) && !empty($huodong_b_v['gid'])) {
											foreach ($huodong_b_v['gid'] as $huodong_c_k => $huodong_c_v) {
												// 获取 商品信息
												$goods_info = $model_goods -> getGoodsOnlineInfoByID($huodong_c_v,'gid,goods_name,goods_promotion_price,goods_marketprice,goods_price,goods_image');
												if(!empty($goods_info)){
													$goods_info['goods_image'] = thumb($goods_info, 320);
													$goods_info['extend_data'] = $extend_data_list[$huodong_c_v];
													$huodong_v[$huodong_a_k][$huodong_b_k]['goods_info'][$huodong_c_k] = $goods_info;
												}
											}
										}else{
											// 获取 商品信息
											$goods_info = $model_goods -> getGoodsOnlineInfoByID($huodong_b_v['gid'],'gid,goods_name,goods_promotion_price,goods_marketprice,goods_price,goods_image');
											if(!empty($goods_info)){
												$goods_info['goods_image'] = thumb($goods_info, 320);
												$goods_info['extend_data'] = $extend_data_list[$huodong_b_v['gid']];
												$huodong_v[$huodong_a_k][$huodong_b_k]['goods_info'] = $goods_info;
											}
										}
									}
								}
							}
						}

						// 获取最终价格
						$huodong_v[$huodong_a_k][$huodong_b_k]['goods_info'] = Model('goods_activity')->rebuild_goods_data($huodong_v[$huodong_a_k][$huodong_b_k]['goods_info']);

						$huodong_data['data'][$huodong_k] = $huodong_v;
					}
				}

				$home_list_new['item_data'][] = $huodong_data;
			}else{
				$home_list_new['item_data'][] = $val;
			}
		}
		// 商品检索 活动类型 列表
		$searchExtendFields = array();
		$searchExtendFields[0] = "选择参与活动类型";
		$searchExtendFields[4] = "手机专享";
		$is_allow_pin = Model()->table('addons')->where(array('sld_key' => 'pin'))->find();
		if ($is_allow_pin) {
			$searchExtendFields[1] = "拼团";
		}
		if (C('tuan_allow')) {
			$searchExtendFields[2] = "团购";
		}
		if (C('promotion_allow')) {
			$searchExtendFields[3] = "限时折扣";
		}
		$searchExtendFields = array("选择参与活动类型");
		Template::output('searchExtendFields', $searchExtendFields);

		Template::output('list', $home_list_new);

		Template::output('page', $model_cwap_home->showpage(2));
		Template::output('type', 'home');

		Template::output('current_id', $home_info_list['home_id']);
		$this->show_menu('index_edit');
		Template::showpage('cwap_page_edit');
	}

	/**
	 * 手机页面自定义页面内容保存_联到家
	 */
	public function zdy_save() {

		$model_cwap_topic = Model('cwap_topic');
		$model_cwap_home = M('cwap_home','ldj');
		$type = $_POST['page_type'];
		$data = array();
//		file_put_contents('cwaphome.txt',serialize($_POST['item_data']),FILE_APPEND);
//		file_put_contents('cwaphome.txt','\n************'.date('Y-m-d H:i:s',time()).'************\n',FILE_APPEND);
		if($type == 'home'){
			$data['home_id'] = $_POST['id'];
			$data['home_data'] = serialize($_POST['item_data']);
			$data['home_desc'] = $_POST['page_title'];
			$data['home_sousuo_color'] = $_POST['sousuocolor'];
			$data['home_botnav_color'] = $_POST['botnavcolor'];
			$result = $model_cwap_home -> editCwapHome_index($data);
			if($result) {
				$this->log('编辑手机首页' . '[ID:' . $_POST['id']. ']', 1);
				showMsg(L('保存成功'),'');
			} else {
				$this->log('编辑手机首页' . '[ID:' . $_POST['id']. ']', 0);
				showMsg(L('保存失败'),'');
			}
		}else if($type == 'topic'){
			$data['topic_id'] = $_POST['id'];
			$data['topic_data'] = serialize($_POST['item_data']);
			$data['topic_desc'] = $_POST['page_title'];
			$result = $model_cwap_topic -> editCwapTopic_index($data);
			if($result) {
				$this->log('编辑手机专题页' . '[ID:' . $_POST['id']. ']', 1);
				showMsg(L('保存成功'),'');
			} else {
				$this->log('编辑手机专题页' . '[ID:' . $_POST['id']. ']', 0);
				showMsg(L('保存失败'),'');
			}
		}else if($type == 'add_home'){
			//新建首页
			$data['home_data'] = serialize($_POST['item_data']);
			$data['home_state'] = 1;
			//根据模板id获取模板描述
			$template_info = $model_cwap_home->getTempInfoByID($_POST['id']);
			$data['home_desc'] = $_POST['page_title'];
			$data['home_sousuo_color'] = $_POST['sousuocolor'];
			$data['home_botnav_color'] = $_POST['botnavcolor'];
			$result = $model_cwap_home -> addHome($data);
			if($result) {
				$this->log('新增手机首页' . '[ID:' . $result. ']', 1);
				showMsg(L('保存成功'),'');
			} else {
				$this->log('新增手机首页', 0);
				showMsg(L('保存失败'),'');
			}
		}else if($type == 'add_topic'){
			//新建专题页
			$data['topic_data'] = serialize($_POST['item_data']);
			$data['topic_desc'] = $_POST['page_title'];
			$result = $model_cwap_topic -> addTopic($data);
			if($result) {
				$this->log('新增手机专题页' . '[ID:' . $result. ']', 1);
				showMsg(L('保存成功'),'');
			} else {
				$this->log('新增手机专题页', 0);
				showMsg(L('保存失败'),'');
			}

		}
	}
	/**
	 * 商品列表_手机端自定义用
	 */
	public function goods_list_zdy() {
		$model_goods = Model('goods');

		$goods_activity_type = $_REQUEST['goods_activity_type'];
		$keyword = $_REQUEST['keyword'];

		$page = 10;

		$condition = array();

		$searchExtendCondition = array();
		switch ($goods_activity_type) {
			case '1':
				// 获取拼团 类型的商品(bbc_goods) id
				$model_pin = M('pin');
				$pinCondition = array();
				$pin_goods_list = $model_pin->getPinList($pinCondition,0);
				$extend_data_list = array();
				$goods_ids = array();
				foreach ($pin_goods_list as $key => $value) {
					$goods_ids[] = $value['gid'];
					$extend_data_list[$value['gid']] = $value;

				}
				$searchExtendCondition['gid'] = array("IN",$goods_ids);
				break;
			case '2':
				// 团购
				$model_tuan = Model('tuan');
				$tuanCondition = array();
				$tuan_goods_list = $model_tuan->getTuanOnlineList($tuanCondition,'','','gid,tuan_price,virtual_quantity,buy_quantity,tuan_discount,vid');
				$extend_data_list = array();
				$goods_ids = array();
				foreach ($tuan_goods_list as $key => $value) {
					$value['sld_end_time'] = (strtotime($value['end_time_text']) > time()) ? $value['end_time_text'] : '';
					$value['buyed_quantity'] = $value['virtual_quantity'] + $value['buy_quantity'];
					$goods_ids[] = $value['gid'];
					$extend_data_list[$value['gid']] = $value;
				}
				$searchExtendCondition['gid'] = array("IN",$goods_ids);
				break;
			case '3':
				// 限时折扣
				$model_xian = Model('p_xianshi_goods');
				$xianCondition = array();
				$xianCondition['state'] = $model_xian::XIANSHI_GOODS_STATE_NORMAL;
				$xianCondition['start_time'] = array('lt', TIMESTAMP);
				$xianCondition['end_time'] = array('gt', TIMESTAMP);
				$xian_goods_list = $model_xian->getXianshiGoodsList($xianCondition);
				$extend_data_list = array();
				$goods_ids = array();
				if (!empty($xian_goods_list)) {

					foreach ($xian_goods_list as $key => $value) {
						$goods_ids[] = $value['gid'];
						$value['sld_end_time'] = date("Y-m-d H:i:s",$value['end_time']);
						$extend_data_list[$value['gid']] = $value;
					}
				}
				$searchExtendCondition['gid'] = array("IN",$goods_ids);
				break;
			case '4':
				// 手机专享
				$model_p_mbuy = Model('p_mbuy');
				$p_mbuyCondition = array();
				$p_mbuyCondition['mbuy_state'] = $model_p_mbuy::STATE1;
				$p_mbuyCondition['mbuy_quota_endtime'] = array('gt', TIMESTAMP);
				$p_mbuy_list = $model_p_mbuy->getSoleQuotaList($p_mbuyCondition);
				$vendor_ids = array();
				if (!empty($p_mbuy_list)) {
					foreach ($p_mbuy_list as $key => $value) {
						$vendor_ids[] = $value['vid'];
					}
				}
				// 获取 所有自营店铺ID
				$model_vendor = Model('vendor');
				$vendorCondition = array(
					'is_own_shop' => 1,
					'sld_is_supplier' => 0,
					'store_state' => 1,
				);
				$own_vendor_list = $model_vendor->where($vendorCondition)->select();
				if (!empty($own_vendor_list)) {
					foreach ($own_vendor_list as $key => $value) {
						$vendor_ids[] = $value['vid'];
					}
				}
				$mbuyCondition['vid'] = array("IN",$vendor_ids);
				$mbuyCondition['mbuy_state'] = $model_p_mbuy::STATE1;

				$mbuy_goods_list = $model_p_mbuy->getSoleGoodsList($mbuyCondition,'gid');
				$goods_ids = array();
				foreach ($mbuy_goods_list as $key => $value) {
					$goods_ids[] = $value['gid'];
				}
				$searchExtendCondition['gid'] = array("IN",$goods_ids);
				break;

			default:
				break;
		}

		if($keyword=='限时折扣'){
			$model_xianshi=Model('p_xianshi_goods');
//            $condition, $page=null, $order='', $field='*', $limit = 0
			$time=time();
			$condition['state']='1';
			$condition['start_time']=array('lt',$time);
			$condition['end_time']=array('gt',$time);
			$condition['goods_type'] = 0;
			$goods_list=$model_xianshi->getXianshiGoodsList($condition,$page,'','*',0);
			foreach($goods_list as $k=>$v){
				$goods_list[$k]['goods_promotion_price']=$goods_list[$k]['xianshi_price'];
			}
		}else{

			//如果绑定的城市分站id存在的话，需要增加城市分站的筛选条件
			$admininfo = $this->admin_info;
			if($admininfo['admin_sld_city_site_id']>0){
				$condition['province_id|city_id|area_id'] = $admininfo['admin_sld_city_site_id'];
			}
			$condition['goods_name'] = array('like', '%' . $keyword . '%');
			$condition['goods_type'] = 0;

			$condition = array_merge($condition,$searchExtendCondition);
			//去掉不在门店里面的商品
			$goodsid = model()->table('dian_goods')->where(['stock'=>['gt',0],'off'=>0,'delete'=>['exp','`delete` <> 0']])->key('goods_id')->select();
			$goodsid = array_keys($goodsid);

			$condition['gid'] = ['in',$goodsid];

			$goods_list = $model_goods->getGoodsOnlineList($condition, 'gid,goods_name,goods_promotion_price,goods_marketprice,goods_price,goods_image', $page);


		}

		// 扩展搜索结果
		switch ($goods_activity_type) {
			case '1':
				if ($goods_list && isset($extend_data_list) && !empty($extend_data_list)) {
					foreach ($goods_list as $key => $value) {
						if (isset($extend_data_list[$value['gid']]) && !empty($extend_data_list[$value['gid']])) {
							$goods_list[$key]['extend_data'] = $extend_data_list[$value['gid']];
						}
					}
				}
				break;
			case '2':
				if ($goods_list && isset($extend_data_list) && !empty($extend_data_list)) {
					foreach ($goods_list as $key => $value) {
						if (isset($extend_data_list[$value['gid']]) && !empty($extend_data_list[$value['gid']])) {
							$goods_list[$key]['extend_data'] = $extend_data_list[$value['gid']];
						}
					}
				}
				break;
			case '3':
				if ($goods_list && isset($extend_data_list) && !empty($extend_data_list)) {
					foreach ($goods_list as $key => $value) {
						if (isset($extend_data_list[$value['gid']]) && !empty($extend_data_list[$value['gid']])) {
							$goods_list[$key]['extend_data'] = $extend_data_list[$value['gid']];
						}
					}
				}
				break;
			case '4':
				# code...
				break;
			default:
				break;
		}

		// 获取最终价格
		$goods_list = Model('goods_activity')->rebuild_goods_data($goods_list);

		$now_page = $model_goods->wq_pagecmd('obj');

		$now_page->set('page_url','./index.php?app=cwap_topic&mod=goods_list_zdy&goods_activity_type='.$goods_activity_type.'&keyword='.$keyword.'&pn=');

		Template::output('goods_list', $goods_list);
		Template::output('show_page', $model_goods->showpage(null,'show_total'));//自己新建了一个分页模板
		Template::showpage('mb_special_widget_zdy', 'null_layout');
	}
	/**
	 * @api {get} /SystemManage/index.php?app=sld_cwap_home&mod=getTemplatePages 获取 模板列表
	 * @apiVersion 0.0.1
	 * @apiName getTemplatePages
	 * @apiGroup cwapTemplates
	 *
	 * @apiDescription 获取 模板列表
	 *
	 * @apiParam {String} pageSize 		页面数据条数
	 * @apiParam {String} currentPage 	当前页
	 * @apiParam {String} urlType 		跳转链接前端代码
	 *
	 * @apiExample Example usage:
	 * curl -i /SystemManage/index.php?app=sld_cwap_home&mod=getTemplatePages
	 *
	 * @apiSuccess {Object}	list    						获得的数据.
	 * @apiSuccess {Object}	pagination    					分页相关数据
	 * @apiSuccessExample {json} 有数据 时的实例
	 * {
	 *   "list": [
	 *       {
	 *           "home_id": "32",
	 *	         "home_desc": "天仙配",
	 *	         "home_state": "0",
		         "home_data": "",
		         "home_sousuo_color": "e83357",
		         "home_botnav_color": "cf2c39",
		         "decorate_url": "/index.php?app=cwap_home&mod=home_edit&id=32&type=home"
	 *       }
	 *   ],
	 *   "pagination": {
	 *       "current": 1,
	 *       "pageSize": 10,
	 *       "total": 1
	 *   }
	 * }
	 *
	 */
	public function getTemplatePages()
	{
        $pageSize = intval($_GET['pageSize'] > 0 ? $_GET['pageSize'] : 10);
        $_GET['pn'] = intval($_GET['currentPage'] > 0 ? $_GET['currentPage'] : 1);
        $urlType = trim($_GET['urlType']) ? DS.$_GET['urlType'] : '';
        $cityId = trim($this->admin_info['admin_sld_city_site_id']) ? intval($this->admin_info['admin_sld_city_site_id']) : 0;

        $condition = array();

        $model_cwap_home = M('cwap_home','ldj');

        $condition['city_id'] = $cityId;

        $page_list = $model_cwap_home->getWapHomeList($condition,$pageSize);
        foreach ($page_list as $key => $val){
		    $page_list[$key]['decorate_url'] = MALL_URL.$urlType.'/index.php?app=sld_cwap_home&mod=home_edit&id='.$val['home_id'].'&type=home&sld_addons=ldj';
		}

        $return_last = array(
        		'list' => $page_list,
        		'pagination' => array(
        				'current' => $_GET['pn'],
        				'pageSize' => $pageSize,
        				'total' => intval($model_cwap_home->gettotalnum()),
        			),
        	);

        echo json_encode($return_last);

    }

    /**
	 * @api {post} /SystemManage/index.php?app=sld_cwap_home&mod=saveTemplateInfo 保存 模板信息
	 * @apiVersion 0.0.1
	 * @apiName saveTemplateInfo
	 * @apiGroup cwapTemplates
	 *
	 * @apiDescription 保存 模板信息
	 *
	 * @apiParam {Array} tplData 				需要保存的模板数据
	 *
	 * @apiExample Example usage:
	 * curl -i /SystemManage/index.php?app=sld_cwap_home&mod=saveTemplateInfo
	 *
 	 * @apiSuccess (status=200) {Number}	status        					返回自定义状态值.
	 * @apiSuccess (status=200) {String}	msg    							文本提示
	 * @apiSuccessExample {json} 200 时的实例
	 * {
	 *    "state": 200,
	 *    "msg": "操作成功"
	 * }
	 *
	 *
	 * @apiError (Error status) 255    	操作失败
	 * 
	 */
	public function saveTemplateInfo()
	{
		$tplData = $_POST['tplData'];

        $state = 255;
        $message = L('操作失败');

        // 城市分站
        $cityId = trim($this->admin_info['admin_sld_city_site_id']) ? intval($this->admin_info['admin_sld_city_site_id']) : 0;

        if (is_array($tplData) && !empty($tplData)) {
        	$model_cwap_home = M('cwap_home','ldj');
        	if ($tplData['template_id']) {
	        	$condition = array(
	        			'template_id' => $tplData['template_id'],
	        		);
	        	$page_arr = $model_cwap_home->getWapTemplateList($condition,'template_id asc','template_data');

	        	$tplData['home_data'] = isset($page_arr[0]['template_data']) ? $page_arr[0]['template_data'] : '';
	        	unset($tplData['template_id']);
        	}

        	$tplData['city_id'] = $add_condition['city_id'] = $cityId;

        	// 检查是否同名
        	$check_flag = true;
        	$check_condition = array();
        	$check_condition['home_desc'] = $tplData['home_desc'];
        	$check_condition['shop_id'] = 0;
        	$check_condition['city_id'] = $tplData['city_id'];
        	$has_tmp_data = $model_cwap_home->getTemplateInfo($check_condition);
        	
        	if (!empty($has_tmp_data)) {
        		$check_flag = false;
        		$message = '模版名称已存在';
        	}

        	$save_flag = false;
        	if ($check_flag) {
        		$save_flag = $model_cwap_home->addHome($tplData,$add_condition);
        	}

        	if ($save_flag) {
        		$state = 200;
        		$message = L('操作成功');
        	}
        }else{
        	$message = L('参数错误');
        }

        $return_last = array(
        		'state' => $state,
        		'msg' => $message,
        	);

        echo json_encode($return_last);
	}

	/**
	 * @api {post} /SystemManage/index.php?app=sld_cwap_home&mod=updateTemplateInfo 更新 模板信息
	 * @apiVersion 0.0.1
	 * @apiName updateTemplateInfo
	 * @apiGroup cwapTemplates
	 *
	 * @apiDescription 更新 模板信息
	 *
	 * @apiParam {Int} id 						模板ID
	 * @apiParam {Array} tplData 				需要更新的模板数据
	 *
	 * @apiExample Example usage:
	 * curl -i /SystemManage/index.php?app=sld_cwap_home&mod=updateTemplateInfo
	 *
 	 * @apiSuccess (status=200) {Number}	status        					返回自定义状态值.
	 * @apiSuccess (status=200) {String}	msg    							文本提示
	 * @apiSuccessExample {json} 200 时的实例
	 * {
	 *    "state": 200,
	 *    "msg": "操作成功"
	 * }
	 *
	 *
	 * @apiError (Error status) 255    	操作失败
	 * 
	 */
	public function updateTemplateInfo()
	{

		$tplData = $_POST['tplData'];
		$page_id = intval($_POST['id']);
        $cityId = trim($this->admin_info['admin_sld_city_site_id']) ? intval($this->admin_info['admin_sld_city_site_id']) : 0;
		$tplData['city_id'] = $cityId;

        $state = 255;
        $message = L('操作失败');

        if (is_array($tplData) && !empty($tplData) && $page_id) {
        	$model_cwap_home = M('cwap_home','ldj');

        	// 校验 当前是否为 设为默认的 模板
        	$check_condition = array(
        			'home_state' => 1,
        			'home_id' => $page_id,
        			'city_id' => $cityId,
        		);
        	$d = $model_cwap_home->getWapHomeList($check_condition);
        	$is_default = count($d);
        	// 不能将最后一个 默认模板 取消掉默认
        	if (isset($tplData['home_state']) && $tplData['home_state'] == 0 && $is_default) {
        		$message = '当前模板为默认模板，请先设置其他模板为默认模版。';
        	}else{
        		// 检查是否同名
	        	$check_flag = true;
	        	$check_condition = array();
	        	$check_condition['home_desc'] = $tplData['home_desc'];
	        	$check_condition['shop_id'] = 0;
	        	$check_condition['city_id'] = $tplData['city_id'];
	        	$check_condition['home_id'] = array("NEQ",$page_id);
	        	$has_tmp_data = $model_cwap_home->getTemplateInfo($check_condition);
	        	
	        	if (!empty($has_tmp_data)) {
	        		$check_flag = false;
	        		$message = '模版名称已存在';
	        	}

	        	$update_flag = false;
	        	if ($check_flag) {
	        		$update_flag = $model_cwap_home->editCwapHome($tplData, $page_id);
	        	}

	        	if ($update_flag) {
	        		$state = 200;
	        		$message = L('操作成功');
	        	}
        	}
        }else{
        	$message = L('参数错误');
        }
        
        $return_last = array(
        		'state' => $state,
        		'msg' => $message,
        	);

        echo json_encode($return_last);
	}

	/**
	 * @api {post} /SystemManage/index.php?app=sld_cwap_home&mod=deleteTemplate 删除 模板
	 * @apiVersion 0.0.1
	 * @apiName deleteTemplate
	 * @apiGroup cwapTemplates
	 *
	 * @apiDescription 删除 模板
	 *
	 * @apiParam {Array} 	ids 			模板ID数组
	 *
	 * @apiExample Example usage:
	 * curl -i /SystemManage/index.php?app=sld_cwap_home&mod=deleteTemplate
	 *
 	 * @apiSuccess (status=200) {Number}	status        					返回自定义状态值.
	 * @apiSuccess (status=200) {String}	msg    							文本提示
	 * @apiSuccessExample {json} 200 时的实例
	 * {
	 *    "state": 200,
	 *    "msg": "操作成功"
	 * }
	 *
	 *
	 * @apiError (Error status) 255    	操作失败
	 * 
	 */
	public function deleteTemplate()
	{
		$page_ids = $_POST['ids'];

        $state = 255;
        $message = L('操作失败');

        if (is_array($page_ids) && !empty($page_ids)) {
			// 删除模板信息
        	$model_cwap_home = M('cwap_home','ldj');

        	// 校验 当前要删除的ID中是否为 设为默认的 模板
			$check_condition = '';
        	$check_condition .= ' home_id IN ('.implode(',', $page_ids).') ';
        	$check_condition .= ' AND home_state = 1 ';
        	$model_cwap_home->getWapHomeList($check_condition,1);
        	$has_default = $model_cwap_home->gettotalnum();
        	if ($has_default) {
        		$message = '删除的模板中有默认模板，请先设置其他模板为默认模版。';
        	}else{

	        	// $where .= ' home_id IN ('.implode(',', $page_ids).') ';
	        	$condition['home_id'] = array("IN",$page_ids);
	        	$delete_page_flag = $model_cwap_home->delCwapHomeByIDs($condition);

	        	if ($delete_page_flag) {
	        		$state = 200;
	        		$message = L('操作成功');
	        	}
        	}

        }else{
        	$message = L('参数错误');
        }
        
        $return_last = array(
        		'state' => $state,
        		'msg' => $message,
        	);

        echo json_encode($return_last);
	}

	/**
	 * @api {get} /SystemManage/index.php?app=sld_cwap_home&mod=getDefaultTemplateList 获取 待选模板列表
	 * @apiVersion 0.0.1
	 * @apiName getDefaultTemplateList
	 * @apiGroup cwapTemplates
	 *
	 * @apiDescription 获取 待选模板列表
	 *
	 * @apiParam {String} pageSize 		页面数据条数
	 * @apiParam {String} currentPage 	当前页
	 * @apiParam {Int} type 			类型
	 *
	 * @apiExample Example usage:
	 * curl -i /SystemManage/index.php?app=sld_cwap_home&mod=getDefaultTemplateList
	 *
	 * @apiSuccess {Object}	list    						获得的数据.
	 * @apiSuccess {Object}	pagination    					分页相关数据
	 * 
	 * @apiSuccessExample {json} 有数据 时的实例
	 * {
	 *   "list": [
	 *       {
	 *           "template_id": "1",
     *           "template_desc": "默认模板一",
     *           "template_data": "",
     *           "template_img": "empty.jpg",
     *           "template_type": "1",
     *           "sousuo_color": null,
     *           "botnav_color": null
	 *       }
	 *   ],
	 *   "pagination": {
	 *       "current": 1,
	 *       "pageSize": 10,
	 *       "total": 1
	 *   }
	 * }
	 *
	 *
	 * @apiError (Error status) 255    	操作失败
	 * 
	 */
	public function getDefaultTemplateList()
	{	
        $pageSize = intval($_GET['pageSize'] > 0 ? $_GET['pageSize'] : 10);
        $_GET['pn'] = intval($_GET['currentPage'] > 0 ? $_GET['currentPage'] : 1);
        $template_type = intval($_GET['type']);

        $page_list = array();

    	$model_cwap_home = M('cwap_home','ldj');

    	$condition = array(
    			'template_type' => $template_type,
    		);

    	$page_list = $model_cwap_home->getWapTemplateList($condition,$pageSize,'template_id,template_desc,template_img,template_type');
    	foreach ($page_list as $key => $value) {
    		$page_list[$key]['template_img'] = ADMIN_TEMPLATES_URL.'/images/cwap_template/'.$value['template_img'];
    	}

        $return_last = array(
        		'list' => $page_list,
        		'pagination' => array(
        				'current' => $_GET['pn'],
        				'pageSize' => $pageSize,
        				'total' => intval($model_cwap_home->gettotalnum()),
        			),
        	);

        echo json_encode($return_last);

	}

	/**
	 * @api {get} /SystemManage/index.php?app=sld_cwap_home&mod=getDefaultTemplateInfo 获取 待选模板代码
	 * @apiVersion 0.0.1
	 * @apiName getDefaultTemplateInfo
	 * @apiGroup cwapTemplates
	 *
	 * @apiDescription 获取 待选模板列表
	 *
	 * @apiParam {Int} id 			模板ID
	 *
	 * @apiExample Example usage:
	 * curl -i /SystemManage/index.php?app=sld_cwap_home&mod=getDefaultTemplateInfo
	 *
 	 * @apiSuccess (status=200) {Number}	status        					返回自定义状态值.
	 * @apiSuccess (status=200) {Object}	data    						模板信息
	 * @apiSuccess (status=200) {String}	msg    							文本提示
	 * 
	 * @apiSuccessExample {json} 有数据 时的实例
	 * {
	 *    "state": 200,
	 *    "data": "",
	 *    "msg": "操作成功"
	 * }
	 *
	 *
	 * @apiError (Error status) 255    	操作失败
	 * 
	 */
	public function getDefaultTemplateInfo()
	{	
        $page_id = intval($_GET['id']);

        $state = 255;
        $data = '';
        $message = L('操作失败');

        if ($page_id) {
        	$condition = array(
        			'template_id' => $page_id,
        		);
        	$model_cwap_home = Model('cwap_home');
        	$page_arr = $model_cwap_home->getWapTemplateList($condition,'template_id asc','template_data');
        	$page_info = isset($page_arr[0]) ? $page_arr[0]['template_data'] : false;

        	if ($page_info) {
        		$state = 200;
        		$data = $page_info;
        		$message = L('操作成功');
        	}
        }else{
        	$message = L('参数错误');
        }

        $return_last = array(
        		'state' => $state,
        		'data' => $data,
        		'msg' => $message,
        	);

        echo json_encode($return_last);

	}
	/**
	 * 页面内导航菜单
	 * @param string 	$menu_key	当前导航的menu_key
	 * @param array 	$array		附加菜单
	 * @return
	 */
	private function show_menu($menu_key='') {
		$menu_array = array();
		if($menu_key == 'index_edit') {
			$menu_array[] = array('menu_key'=>'index_edit', 'menu_name'=>'编辑', 'menu_url'=>'javascript:;');
		} else if($menu_key == 'home_list'){
			$menu_array[] = array('menu_key'=>'home_list', 'menu_name'=>'首页', 'menu_url'=>'javascript:;');
		}else {
			$menu_array[] = array('menu_key'=>'special_list','menu_name'=>'专题列表', 'menu_url'=>urlAdmin('mb_special', 'special_list'));
		}
		if($menu_key == 'special_item_list') {
			$menu_array[] = array('menu_key'=>'special_item_list', 'menu_name'=>'编辑专题', 'menu_url'=>'javascript:;');
		}
		if($menu_key == 'index_edit') {
			Template::output('item_title', '首页编辑');
		} else {
			Template::output('item_title', '专题设置');
		}
		Template::output('menu', $menu_array);
		Template::output('menu_key', $menu_key);
	}
}