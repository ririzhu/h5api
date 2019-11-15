<?php
/**
 * 装修模板
 *
 */
defined('DYMall') or exit('Access Invalid!');
class fixture {

	public function dotran($str) {
        $str = str_replace('"','\"',$str);
        $str = str_replace("\r",'',$str);
        $str = str_replace("'","&apos;",$str);
        // $str = str_replace("/t",'//t',$str);
        // $str = str_replace("//",'//',$str);
        $str = str_replace("\n",'',$str);
        $str = str_replace("\t",'',$str);
        // $str = str_replace("/b",'//b',$str);
        return $str;
    }

	public function redotran($str) {
        $str = str_replace('\"','"',$str);
        $str = str_replace("&apos;","'",$str);
        $str = str_replace("&amp;","&",$str);
        return $str;
    }

    // 装修模板 图片链接
    public function fixture_pic_url($file_name)
    {

    	// 验证 data 文件是否存在
    	$fixture_pic_base_path = BASE_UPLOAD_PATH.'/'.FIXTURE_PATH.'/'.$file_name;
    	$fixture_pic_url = UPLOAD_SITE_URL.'/'.FIXTURE_PATH.'/'.$file_name;

    	$check_static_pic = false;
    	if (OSS_ENABLE) {
	    	if (oss_exists($fixture_pic_url)) {

	    		$return_pic_url = $fixture_pic_url;
	    	}else{
	    		$check_static_pic = true;
	    	}
    	}else if(QINIU_ENABLE){
            $return_pic_url = $fixture_pic_url;
        }else{
	    	if (is_file($fixture_pic_base_path)) {
	            $return_pic_url = UPLOAD_SITE_URL.'/'.FIXTURE_PATH.'/'.$file_name;
	        }else{
	    		$check_static_pic = true;
	        }
    	}
    	if ($check_static_pic) {
        	// 验证 内置模版 文件是否存在
        	if (is_file(BASE_STATIC_PATH.'/'.FIXTURE_PATH.'/'.'main'.'/'.$file_name)) {
        		// 验证 系统后台 装修图片
        		$return_pic_url = STATIC_SITE_URL.'/'.FIXTURE_PATH.'/'.'main'.'/'.$file_name;
        	}else{
    			$return_pic_url = $fixture_pic_url;
        	}
    	}

    	return $return_pic_url;

    }

	// 获取 装修好的模板
	public function run_index($page_type,$tpl_page_id,$show_edit='',$write_cache=false)
	{


		if (rkcache('template_'.$page_type.'_'.$tpl_page_id) && !$show_edit && !$write_cache) {
			$tplDataJsonArr = rkcache('template_'.$page_type.'_'.$tpl_page_id);
		}else{
			$model_web_home = Model('web_home');
			$condition = array(
					'sld_page' => $page_type,
					'sld_tpl_page_id' => $tpl_page_id,
				);
			if (!$show_edit) {
				$condition['sld_is_vaild'] = 1;
			}
			$tplData = $model_web_home->getTplData($condition);

			$tplDataJsonArr = array();
			foreach ($tplData as $key => $tpl_data) {
				$ext_info['floor_nav_title'] = $tpl_data['sld_floor_nav_title'];
				$template_html = Logic('fixture')->reBuildTpl($tpl_data,$show_edit);
				$tplDataJsonArr[$key] = array(
						'code' => $tpl_data['sld_tpl_code'],
						'data' => $this->dotran($tpl_data['sld_tpl_data']),
						'ext_info' => $ext_info,
						'file' => $this->dotran($template_html),
						'format_is_valid' => $tpl_data['sld_is_vaild'] ? '隐藏' : '显示',
						'is_valid' => $tpl_data['sld_is_vaild'],
						'page' => $tpl_data['sld_page'],
						'shop_id' => $tpl_data['sld_shop_id'],
						'city_id' => $tpl_data['sld_city_id'],
						'site_id' => '0',
						'sort' => $tpl_data['sld_sort'],
						'tpl_id' => '',
						'tpl_name' => $tpl_data['sld_tpl_name'],
						'tpl_title' => '',
						'type' => $tpl_data['sld_tpl_type'],
						'uid' => $tpl_data['sld_tpl_id'],
						'page_id' => $tpl_data['sld_tpl_page_id'],
					);
			}
			if ($write_cache) {
				dkcache('template_'.$page_type.'_'.$tpl_page_id);
				delCacheFile('tmp_c');
			}else{
				if (!$show_edit) {
					wkcache('template_'.$page_type.'_'.$tpl_page_id,$tplDataJsonArr);
					delCacheFile('tmp_c');
				}	
			}
		}

		return $tplDataJsonArr;

	}

	// 重构 模板
	public function reBuildTpl($tpl_data,$show_edit='',$lang = 'zh_cn')
	{
		$template_code = $tpl_data['sld_tpl_code'];
		$template_valid = $tpl_data['sld_is_vaild'];
		$template_id = $tpl_data['sld_tpl_id'];
		$shop_id = $tpl_data['sld_shop_id'];
		$city_id = $tpl_data['sld_city_id'];
		if($_GET['lang']){
		    $lang = $_GET['lang'];
        }


		$model_web_home = Model('web_home');

		// 根据code 获取 装修模板信息
		$fixture_tpl_info = $model_web_home->getFixtrueTplInfo($template_code);

		$template_html = $fixture_tpl_info['sld_html'];
		$design_arr = unserialize($fixture_tpl_info['sld_tpl_edit_btns']);
		$template_list_item = unserialize($fixture_tpl_info['sld_tpl_list_item']);

		if ($show_edit) {
			if (is_array($design_arr)) {
				foreach ($design_arr as $key => $val) {
					$a_attr_str = '';
					$need_replace_name = '{'.$key.'}';
					foreach ($val as $key => $value) {
						$a_attr_str .=' data-'.$key.'="'.$value.'"';
					}

					$a_attr_str.=' data-lang='.$lang;

					$template_edit_btn_html = '
					<a class="selector title-selector SLD-T-SEL-SLD"'.$a_attr_str.'>
						<i class="fa fa-edit"></i>
						编辑
					</a>';

					$template_html = str_replace($need_replace_name, $template_edit_btn_html, $template_html);
				}
			}

			$template_html = $template_valid ? str_replace('{INVALID}', '', $template_html) : str_replace('{INVALID}', 'invalid', $template_html);
		}else{
			if (is_array($design_arr)) {
				foreach ($design_arr as $key => $val) {
					$a_attr_str = '';
					$need_replace_name = '{'.$key.'}';

					$template_html = str_replace($need_replace_name, '', $template_html);
				}
			}
			$template_html = str_replace('{INVALID}', '', $template_html);
		}

		// 调用装修模板的装修方法
		$fixture_tpl_function = 'fixture_tpl_'.$template_code;
		$template_html = $this->$fixture_tpl_function($show_edit,$design_arr,$template_list_item,$tpl_data,$template_html);

		$template_html = str_replace('{TEMPLATE_ID}', $template_id, $template_html);
		$template_html = str_replace('{SHOP_ID}', $shop_id, $template_html);
		$template_html = str_replace('{CITY_ID}', $city_id, $template_html);

		return $template_html;
	}
	// 重构 编辑 模板
	public function reBuildLayerTpl($layer_template_id,$layData)
	{
		$cat_id = $layData['cat_id'];
		$type = $layData['type'];
		$shop_id = $layData['shop_id'];
		$city_id = $layData['city_id'];

		$max_number = '';
		$max_item_number = '';

		// 获取 模板的 装修数据
		$model_web_home = Model('web_home');
		$tpl_data = $model_web_home->getTplByTplId($layData['uid']);

		// 根据code 获取 装修模板信息
		$fixture_tpl_info = $model_web_home->getFixtrueLayerTplInfo($cat_id,$type);


		$template_html = $fixture_tpl_info['sld_html'];

		$js_define_fields = unserialize($fixture_tpl_info['sld_js_define_fields']);


		// $layer_tpl_top_tips_html = $fixture_tpl_info['sld_tpl_top_tips'];
		// $layer_tpl_selected_list_item_html = $fixture_tpl_info['sld_selected_list_item'];
		// $layer_tpl_list_item_html = $fixture_tpl_info['sld_list_item'];

		$define_js_data = 'var type = "'.$type.'";';
		$define_js_data .= 'var cat_id = "'.$cat_id.'";';
		$define_js_data .= 'var uid = "'.$layData['uid'].'";';

		if ($js_define_fields) {
			foreach ($js_define_fields as $key => $value) {
				switch ($key) {
					case 'max_number':
							$max_number = $layData[$value];
						break;
					case 'max_item_number':
							$max_item_number = $layData[$value];
						break;
					case 'max_sub_number':
						// 子标题 字数限制
							$max_sub_number = $layData[$value];
						break;
					case 'sub_title_field_name':
						// 子标题 名称
							$sub_title_field_name = $layData[$value];
						break;
					case 'bg_color_field_name':
						// 背景颜色 名称
							$bg_color_field_name = $layData[$value];
						break;
					case 'font_color_field_name':
						// 文字颜色 名称
							$font_color_field_name = $layData[$value];
						break;
					case 'pic_w':
							$pic_w = $layData[$value];
						break;
					case 'pic_h':
							$pic_h = $layData[$value];
						break;
					case 'level_deep':
							$level_deep = $layData[$value] ? $layData[$value] : 3;
						break;
				}
				$define_js_data .= 'var '.$key.' = "'.htmlspecialchars_decode($layData[$value]).'";';
			}
		}


		$template_html = str_replace('{DEFINEJSDATA}', $define_js_data, $template_html);

		if ($fixture_tpl_info['sld_function']) {
			$fixture_layer_function_name = 'fixture_edit_layer_'.$fixture_tpl_info['sld_function'];
			$template_html = $this->$fixture_layer_function_name($tpl_data,$layData,$fixture_tpl_info,$template_html);
		}

		$template_html = str_replace('{PICWIDTH}', $pic_w, $template_html);
		$template_html = str_replace('{PICHEIGHT}', $pic_h, $template_html);
		$template_html = str_replace('{MAXITEMNUMBER}', $max_item_number, $template_html);
		$template_html = str_replace('{MAXNUMBER}', $max_number, $template_html);
		$template_html = str_replace('{MAXSUBNUMBER}', $max_sub_number, $template_html);
		$template_html = str_replace('{SUBTITLEFIELDNAME}', $sub_title_field_name, $template_html);
		$template_html = str_replace('{BGCOLORFIELDNAME}', $bg_color_field_name, $template_html);
		$template_html = str_replace('{FONTCOLORFIELDNAME}', $font_color_field_name, $template_html);
		$template_html = str_replace('{LEVELDEEP}', $level_deep, $template_html);
		$template_html = str_replace('{LAYERTEMPLATEID}', $layer_template_id, $template_html);

		return $template_html;
	}
	// 商品选择器 起始数据 及分页
	public function goodsLayerStartData($each_num,$selected_ids,$layer_tpl_list_item_html,$dataCondition=array())
	{
		$empty_data_html = '<tr><td colspan="10"><div class="text-center">无数据</div></td></tr>';
		$model_goods = Model('goods');

		$page	= new Page();
		$page->setEachNum($each_num);
		$page->setStyle('admin');

		$where = array();
		if ($dataCondition['vid']) {
			$vendorCondition['vid'] = $dataCondition['vid'];
			// 店铺商品
			$where = array_merge($where,$vendorCondition);
		}
		if ($dataCondition['city_id']) {
			// 城市分站 商品条件
			$cityCondition['province_id|city_id|area_id'] = $dataCondition['city_id'];
			$where = array_merge($where,$cityCondition);
		}
		if ($dataCondition['is_supplier']) {
			$supplierCondition['goods_type'] = 1;
			$where = array_merge($where,$supplierCondition);
		}
		if ($dataCondition['goods_name']) {
			$searchCondition['goods_name|goods_jingle'] = array('like', '%' . $dataCondition['goods_name'] . '%');

			$where = array_merge($where,$searchCondition);	
		}
		if ($dataCondition['activity_type']) {
			$searchExtendCondition = array();
			switch ($dataCondition['activity_type']) {
				case '1':
					// 获取拼团 类型的商品(bbc_goods) id
					$model_pin = M('pin');
					$pinCondition = array();
					$pin_goods_list = $model_pin->getPinList($pinCondition,0);
					$goods_ids = array();
					foreach ($pin_goods_list as $key => $value) {
						$goods_ids[] = $value['gid'];
					}
					$searchExtendCondition['gid'] = array("IN",$goods_ids);
					break;
				case '2':
					// 团购
					$model_tuan = Model('tuan');
					$tuanCondition = array();
					$tuan_goods_list = $model_tuan->getTuanOnlineList($tuanCondition,'','','gid');
					$goods_ids = array();
					foreach ($tuan_goods_list as $key => $value) {
						$goods_ids[] = $value['gid'];
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
					$goods_ids = array();
					if (!empty($xian_goods_list)) {
						
					foreach ($xian_goods_list as $key => $value) {
						$goods_ids[] = $value['gid'];
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

			$where = array_merge($where,$searchExtendCondition);	
		}
		//语言条件
		if(isset($dataCondition['sites'])){
			$where['sites'] = $dataCondition['sites'];
		}
		
		$goods_list = $model_goods->getGoodsListByColorDistinct($where,'bbc_goods.*','gid asc',$each_num);

        // 获取最终价格
        $goods_list = Model('goods_activity')->rebuild_goods_data($goods_list);

		$page->setTotalNum($model_goods->gettotalnum());
		$page->set('page_url','#');
		$list_data = '';
		foreach ($goods_list as $key => $value) {

			$layer_tpl_list_item = $layer_tpl_list_item_html;

			$goods_checked_class_name = '';
			if (in_array($value['gid'],$selected_ids)) {
				$goods_check_name = '已选';
				$goods_checked_class_name = 'active';
			}else{
				$goods_check_name = '选择';
			}

			$layer_tpl_list_item = str_replace('{GOODSSRC}', thumb($value,'real'), $layer_tpl_list_item);
			$layer_tpl_list_item = str_replace('{GOODSID}', $value['gid'], $layer_tpl_list_item);
			$layer_tpl_list_item = str_replace('{GOODSNAME}', $value['goods_name'], $layer_tpl_list_item);
			$layer_tpl_list_item = str_replace('{GOODSPRICE}', $value['show_price'], $layer_tpl_list_item);
			$layer_tpl_list_item = str_replace('{GOODSCHECKEDCLASSNAME}', $goods_checked_class_name, $layer_tpl_list_item);
			$layer_tpl_list_item = str_replace('{GOODSCHECKSTRING}', $goods_check_name, $layer_tpl_list_item);

			$list_data .= $layer_tpl_list_item;
		}

		$result_data['page_show'] = $page->show();
		$result_data['list_data'] = $list_data ? $list_data : $empty_data_html;
		$result_data['now_page'] = $page->get('now_page');

		return $result_data;
	}

	// 图片选择器 起始数据 及分页
	public function picLayerStartData($each_num,$selected_ids,$layer_tpl_list_item_html,$dataCondition=array())
	{
		$empty_data_html = '<tr><td colspan="10"><div class="text-center">无数据</div></td></tr>';

		$page	= new Page();
		$page->setEachNum($each_num);
		$page->setStyle('admin');

		$list_data = '';

		if (isset($dataCondition['vid']) && $dataCondition['vid']) {
			// 获取店铺图片列表
			$model_album = Model('imagespace');

			$imgCondition['aclass_id'] = $param['aclass_id'] = $dataCondition['album_id'];
			$imgCondition['vid'] = $param['imagespace.vid']	= $dataCondition['vid'];
			$param['order']		= 'upload_time desc';

			$fixture_pic_list = $model_album->getPicList($param,$page);

			$page->setTotalNum($model_album->getCount($imgCondition));
			$page->set('page_url','#');

			if ($fixture_pic_list) {
				foreach ($fixture_pic_list as $key => $value) {
					$sld_pic_width = '';
					$sld_pic_height = '';
					if ($value['apic_spec']) {
						list($sld_pic_width,$sld_pic_height) = explode('x', $value['apic_spec']);
					}

					$layer_tpl_list_item = $layer_tpl_list_item_html;

					$layer_tpl_list_item = str_replace('{PICID}', $value['apic_id'], $layer_tpl_list_item);
					$layer_tpl_list_item = str_replace('{PICNAME}', $value['apic_name'], $layer_tpl_list_item);
					$layer_tpl_list_item = str_replace('{PICSRC}', thumb($value, 'real'), $layer_tpl_list_item);
					$layer_tpl_list_item = str_replace('{PICWIDTH}', $sld_pic_width, $layer_tpl_list_item);
					$layer_tpl_list_item = str_replace('{PICHEIGHT}', $sld_pic_height, $layer_tpl_list_item);

					$list_data .= $layer_tpl_list_item;

				}
			}


		}else{
			$model_fixture_pic = Model('web_home');

			$fixture_pic_list = $model_fixture_pic->getFixturePicList($condition,$each_num);

			$page->setTotalNum($model_fixture_pic->gettotalnum());
			$page->set('page_url','#');

			foreach ($fixture_pic_list as $key => $value) {
				$layer_tpl_list_item = $layer_tpl_list_item_html;

				$layer_tpl_list_item = str_replace('{PICID}', $value['id'], $layer_tpl_list_item);
				$layer_tpl_list_item = str_replace('{PICNAME}', $value['sld_pic_name'], $layer_tpl_list_item);
				$layer_tpl_list_item = str_replace('{PICSRC}', $this->fixture_pic_url($value['sld_pic_name']), $layer_tpl_list_item);
				$layer_tpl_list_item = str_replace('{PICWIDTH}', $value['sld_pic_width'], $layer_tpl_list_item);
				$layer_tpl_list_item = str_replace('{PICHEIGHT}', $value['sld_pic_height'], $layer_tpl_list_item);

				$list_data .= $layer_tpl_list_item;

			}
		}

		$result_data['page_show'] = $page->show();
		$result_data['list_data'] = $list_data ? $list_data : $empty_data_html;
		$result_data['now_page'] = $page->get('now_page');

		return $result_data;
	}

	// 品牌选择器 起始数据 及分页
	public function brandLayerStartData($each_num,$selected_ids,$layer_tpl_list_item_html,$dataCondition=array())
	{
		$empty_data_html = '<tr><td colspan="10"><div class="text-center">无数据</div></td></tr>';
		$model_brand = Model();

		$page	= new Page();
		$page->setEachNum($each_num);
		$page->setStyle('admin');

		$condition = array();
		if ($dataCondition['vid']) {
			$vendorCondition['vid'] = $dataCondition['vid'];
			// 店铺商品
			$condition = array_merge($condition,$vendorCondition);
		}
		// if ($dataCondition['city_id']) {
		// 	// 城市分站 商品条件
		// 	$cityCondition['province_id|city_id|area_id'] = $dataCondition['city_id'];
		// 	$condition = array_merge($condition,$cityCondition);
		// }
		// if ($dataCondition['is_supplier']) {
		// 	$supplierCondition['goods_type'] = 1;
		// 	$condition = array_merge($condition,$supplierCondition);
		// }
		if ($dataCondition['brand_name']) {
			$searchCondition['brand_name'] = array('like', '%' . $dataCondition['brand_name'] . '%');

			$condition = array_merge($condition,$searchCondition);	
		}

		// $condition = " brand_apply = 1 AND brand_pic != '' ";
		$condition['brand_apply'] = 1;
		$condition['brand_pic'] = array("NEQ",'');
		$brand_data_list = $model_brand->table('brand')->where($condition)->order('brand_id desc')->page($each_num)->select();

		$page->setTotalNum($model_brand->gettotalnum());
		$page->set('page_url','#');
		
		$list_data = '';
		foreach ($brand_data_list as $key => $value) {
			$layer_tpl_list_item = $layer_tpl_list_item_html;

			$goods_checked_class_name = '';
			if (in_array($value['brand_id'],$selected_ids)) {
				$goods_check_name = '已选';
				$goods_checked_class_name = 'active';
			}else{
				$goods_check_name = '选择';
			}

			$layer_tpl_list_item = str_replace('{BRANDSRC}', brandImage($value['brand_pic']), $layer_tpl_list_item);
			$layer_tpl_list_item = str_replace('{BRANDID}', $value['brand_id'], $layer_tpl_list_item);
			$layer_tpl_list_item = str_replace('{BRANDNAME}', $value['brand_name'], $layer_tpl_list_item);
			$layer_tpl_list_item = str_replace('{BRANDCHECKEDCLASSNAME}', $goods_checked_class_name, $layer_tpl_list_item);
			$layer_tpl_list_item = str_replace('{BRANDCHECKSTRING}', $goods_check_name, $layer_tpl_list_item);

			$list_data .= $layer_tpl_list_item;
		}

		$result_data['page_show'] = $page->show();
		$result_data['list_data'] = $list_data ? $list_data : $empty_data_html;
		$result_data['now_page'] = $page->get('now_page');

		return $result_data;
	}

	// 品牌选择器 起始数据 及分页
	public function shopLayerStartData($each_num,$selected_ids,$layer_tpl_list_item_html,$dataCondition=array())
	{
		$empty_data_html = '<tr><td colspan="10"><div class="text-center">无数据</div></td></tr>';
		// 获取店铺列表
		$model_store = Model('vendor');

		$page	= new Page();
		$page->setEachNum($each_num);
		$page->setStyle('admin');

		$condition = array();
		$g_condition = array();
		if ($dataCondition['city_id']) {
			// 城市分站 店铺条件
			$cityCondition['province_id|area_id|city_id'] = $dataCondition['city_id'];
			$condition = array_merge($condition,$cityCondition);
		}
		if ($dataCondition['is_supplier']) {
			$supplierCondition['sld_is_supplier'] = 1;
			$condition = array_merge($condition,$supplierCondition);
		}
		if ($dataCondition['shop_name']) {
			$searchCondition['store_name'] = array('like', '%' . $dataCondition['shop_name'] . '%');

			$condition = array_merge($condition,$searchCondition);	
		}

		$brand_data_list = $model_store->getStoreOnlineList($condition, $each_num);

		//店铺等级
		$grade_names = array();
		$model_grade = Model('store_grade');
		$grade_list = $model_grade->getGradeList($g_condition);
		foreach ($grade_list as $key => $value) {
			$grade_names[$value['sg_id']] = $value['sg_name'];
		}

		$page->setTotalNum($model_store->gettotalnum());
		$page->set('page_url','#');
		
		$list_data = '';
		foreach ($brand_data_list as $key => $value) {

			$layer_tpl_list_item = $layer_tpl_list_item_html;

			$shop_checked_class_name = '';
			if (in_array($value['vid'],$selected_ids)) {
				$shop_check_name = '已选';
				$shop_checked_class_name = 'active';
			}else{
				$shop_check_name = '选择';
			}

			$layer_tpl_list_item = str_replace('{SHOPID}', $value['vid'], $layer_tpl_list_item);
			$layer_tpl_list_item = str_replace('{SHOPNAME}', $value['store_name'], $layer_tpl_list_item);
			$layer_tpl_list_item = str_replace('{SHOPLEVEL}', $grade_names[$value['grade_id']], $layer_tpl_list_item);
			$layer_tpl_list_item = str_replace('{SHOPLOGO}', getStoreLogo($value['store_label']), $layer_tpl_list_item);
			$layer_tpl_list_item = str_replace('{SHOPCHECKEDCLASSNAME}', $shop_checked_class_name, $layer_tpl_list_item);
			$layer_tpl_list_item = str_replace('{SHOPCHECKSTRING}', $shop_check_name, $layer_tpl_list_item);

			$list_data .= $layer_tpl_list_item;
		}

		$result_data['page_show'] = $page->show();
		$result_data['list_data'] = $list_data ? $list_data : $empty_data_html;
		$result_data['now_page'] = $page->get('now_page');

		return $result_data;
	}

	public function backupLayerStartData($each_num,$condition,$layer_tpl_list_item_html,$dataCondition=array())
	{
		$empty_data_html = '<tr><td colspan="10"><div class="text-center">无数据</div></td></tr>';
		// 获取 装修模板备份数据 分页数据
		$model_web_home = Model('web_home');

		$page	= new Page();
		$page->setEachNum($each_num);
		$page->setStyle('admin');

		$condition = array();
		if ($dataCondition['bak_name']) {
			$searchCondition['sld_title'] = array('like', '%' . $dataCondition['bak_name'] . '%');

			$condition = array_merge($condition,$searchCondition);	
		}

		$back_data_list = $model_web_home->getBackupTplData($condition,$each_num);

		$page->setTotalNum($model_web_home->gettotalnum());
		$page->set('page_url','#');

		$list_data = '';
		foreach ($back_data_list as $key => $value) {
			$layer_tpl_list_item = $layer_tpl_list_item_html;

			$layer_tpl_list_item = str_replace('{TEMPLAGETITLE}', $value['sld_title'], $layer_tpl_list_item);
			$layer_tpl_list_item = str_replace('{CREATETIME}', date('Y-m-d H:i:s',$value['sld_create_time']), $layer_tpl_list_item);
			$layer_tpl_list_item = str_replace('{PAGENAME}', L($value['sld_page']), $layer_tpl_list_item);
			$layer_tpl_list_item = str_replace('{TPLNAME}', $value['sld_tpl_name'], $layer_tpl_list_item);
			$layer_tpl_list_item = str_replace('{TEMPLATEDATAID}', $value['id'], $layer_tpl_list_item);

			$list_data .= $layer_tpl_list_item;
		}

		$result_data['page_show'] = $page->show();
		$result_data['list_data'] = $list_data ? $list_data : $empty_data_html;
		$result_data['now_page'] = $page->get('now_page');

		return $result_data;
	}

	public function loadCategoryList($parent_id,$level)
	{
		$model_class = Model('goods_class');
		$tmp_list = $model_class->getGoodsClassList(array('gc_parent_id' => $parent_id), 'gc_id, gc_name, type_id');
		// $tmp_list = $model_class->getTreeClassList(3);
		if (is_array($tmp_list)){
			foreach ($tmp_list as $k => $v){
				// 验证是否有子类
				$has_child = $model_class->getGoodsClassList(array('gc_parent_id' => $v['gc_id']), 'gc_id');
				if ($has_child) {
					$v['has_child'] = 1;
				}
				$class_list[] = $v;
			}
		}

		$categoryTopListHtml = '';

		foreach ($class_list as $key => $value) {
			$categoryTopListHtml .= '<li class="">';
			$categoryTopListHtml .= '<a href="javascript:void(0)" class="category-name" data-has_child="'.$value['has_child'].'" data-id="'.$value['gc_id'].'" data-name="'.$value['gc_name'].'" data-level="'.($level+1).'">';
			if ($value['have_child']) {
				$categoryTopListHtml .= '<i class="fa fa-angle-right"></i>';	
			}
			$categoryTopListHtml .= $value['gc_name'];
			$categoryTopListHtml .= '</a>';
			$categoryTopListHtml .= '</li>';
			
		}

		return $categoryTopListHtml;
	}

	//////////////////////-----装修模板的方法 sld_start-----/////////////////////////////
	public function fixture_tpl_nav_login($show_edit,$design_arr,$template_list_item,$tpl_data,$template_html)
	{
		// 模板相关变量嵌套
		// 获取默认头像 
        $model_setting = Model('setting');
        $default_user_portrait = $model_setting->getRowSetting('default_user_portrait');

		$default_user_avator = (isset($default_user_portrait['value']) && $default_user_portrait['value']) ? UPLOAD_SITE_URL.DS.ATTACH_COMMON.DS.$default_user_portrait['value'] : UPLOAD_SITE_URL.'/mall/common/05593905042949007.png';
		$default_welcome_string = L('您好').",".L('欢迎来到').$GLOBALS['setting_config']['site_name'];
		// 获取装修模板的数据
		$tpl_data_data = unserialize($tpl_data['sld_tpl_data']);
		$tpl_t_data = $tpl_data_data['t'];
		$welcome_string = $tpl_t_data ? $tpl_t_data[0]['name'] : $default_welcome_string;
		
		$template_html = str_replace('{USER_AVATOR}', $default_user_avator, $template_html);
		$template_html = str_replace('{WELCOMESTRING}', $welcome_string, $template_html);

		$template_html = str_replace('{LOGEDURL}', urlShop('usercenter'), $template_html);
		$template_html = str_replace('{LOGINURL}', urlShop('login'), $template_html);
		$template_html = str_replace('{APPLYJOININ}', urlShop('applyjoinin','index'), $template_html);
		$template_html = str_replace('{LOGINFONT}', L('登录'), $template_html);

		return $template_html;
	}
	public function fixture_tpl_nav_shop_apply($show_edit,$design_arr,$template_list_item,$tpl_data,$template_html)
	{
		$template_html = str_replace('{APPLYJOININ}', urlShop('applyjoinin','index'), $template_html);	
		$template_html = str_replace('{VENDORLOGIN}', urlVendor('login','show_login'), $template_html);
		return $template_html;
	}
	public function fixture_tpl_ad_one_column($show_edit,$design_arr,$template_list_item,$tpl_data,$template_html)
	{
		// 获取装修模板的数据
		$tpl_all_data = unserialize($tpl_data['sld_tpl_data']);

		// pic_list
		// 获取图片列表
		$tpl_pic_list_data = $tpl_all_data['pic_list'];

		// 获取图片列表
		$model_fixture_pic = Model('web_home');

		$total_num = $design_arr['TEMPLATE_EDIT']['length'];
		$has_img_num = 0;

		$selected_list_data = '';

		if ($tpl_pic_list_data) {
			$has_img_num = count($tpl_pic_list_data);

			// 排序
			$arr_sort = array(  
			        'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序  
			        'field'     => 'sort',       //排序字段  
			);  
			$arrSort = array();  
			foreach($tpl_pic_list_data AS $uniqid => $row){  
			    foreach($row AS $key=>$value){  
			        $arrSort[$key][$uniqid] = $value;  
			    }  
			}
			if($arr_sort['direction']){  
				array_multisort($arrSort[$arr_sort['field']], constant($arr_sort['direction']), $tpl_pic_list_data);  
			}

			foreach ($tpl_pic_list_data as $key => $value) {
				$pic_item_tpl = $template_list_item['PICLIST']['real'];

				// 获取信息
				if ($tpl_data['sld_shop_id']) {
					$model_album = Model('imagespace');
					$pic_info = $model_album->getOne(array('apic_id'=>$value['pic_id']));
					$pic_item_tpl = str_replace('{PICSRC}', thumb($pic_info, 'real'), $pic_item_tpl);
				}else{
					$pic_info = $model_fixture_pic->getFixturePicInfo(array('id'=>$value['pic_id']),array('id','sld_pic_name'));
					$pic_item_tpl = str_replace('{PICSRC}', $this->fixture_pic_url($pic_info['sld_pic_name']), $pic_item_tpl);
				}

				$pic_link = $show_edit ? 'javascript:;' : ($value['links'] ? $value['links'] : 'javascript:;') ;

				$pic_item_tpl = str_replace('{PICLINK}', $pic_link, $pic_item_tpl);

				$pic_list_html .= $pic_item_tpl;
			}
		}

		if (!$has_img_num && $show_edit) {
			for ($i=0; $i < ($total_num-$has_img_num); $i++) { 
				$pic_item_tpl = $template_list_item['PICLIST']['example'];

				$pic_item_tpl = str_replace('{PICWIDTH}', $design_arr['TEMPLATE_EDIT']['pic_w'], $pic_item_tpl);

				$pic_item_tpl = str_replace('{PICHEIGHT}', $design_arr['TEMPLATE_EDIT']['pic_h'] ? $design_arr['TEMPLATE_EDIT']['pic_h'] : '高度不限', $pic_item_tpl);

				$pic_list_html .= $pic_item_tpl;

			}
		}

		$template_html = str_replace('{PICLIST}', $pic_list_html, $template_html);

		$template_html = str_replace('{VENDORLOGIN}', urlVendor('login','show_login'), $template_html);
		$template_html = str_replace('{APPLYJOININ}', urlShop('applyjoinin','index'), $template_html);

		return $template_html;
	}
	public function fixture_tpl_ad_five_column($show_edit,$design_arr,$template_list_item,$tpl_data,$template_html)
	{
		// 获取装修模板的数据
		$tpl_all_data = unserialize($tpl_data['sld_tpl_data']);

		// pic_list
		// 获取图片列表
		$tpl_pic_list_data = $tpl_all_data['pic_list'];

		// 获取图片列表
		$model_fixture_pic = Model('web_home');

		$total_num = $design_arr['TEMPLATE_EDIT']['length'];
		$has_img_num = 0;

		$selected_list_data = '';

		if ($tpl_pic_list_data) {
			$has_img_num = count($tpl_pic_list_data);

			// 排序
			$arr_sort = array(  
			        'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序  
			        'field'     => 'sort',       //排序字段  
			);  
			$arrSort = array();  
			foreach($tpl_pic_list_data AS $uniqid => $row){  
			    foreach($row AS $key=>$value){  
			        $arrSort[$key][$uniqid] = $value;  
			    }  
			}
			if($arr_sort['direction']){  
				array_multisort($arrSort[$arr_sort['field']], constant($arr_sort['direction']), $tpl_pic_list_data);  
			}

			foreach ($tpl_pic_list_data as $key => $value) {
				$pic_item_tpl = $template_list_item['PICLIST']['real'];

				// 获取信息
				if ($tpl_data['sld_shop_id']) {
					$model_album = Model('imagespace');
					$pic_info = $model_album->getOne(array('apic_id'=>$value['pic_id']));
					$pic_item_tpl = str_replace('{PICSRC}', thumb($pic_info, 'real'), $pic_item_tpl);
				}else{
					$pic_info = $model_fixture_pic->getFixturePicInfo(array('id'=>$value['pic_id']),array('id','sld_pic_name'));
					$pic_item_tpl = str_replace('{PICSRC}', $this->fixture_pic_url($pic_info['sld_pic_name']), $pic_item_tpl);
				}

				$pic_link = $show_edit ? 'javascript:;' : ($value['links'] ? $value['links'] : 'javascript:;') ;

				$pic_item_tpl = str_replace('{PICLINK}', $pic_link, $pic_item_tpl);

				$pic_list_html .= $pic_item_tpl;
			}
		}

		if (!$has_img_num && $show_edit) {
			for ($i=0; $i < ($total_num-$has_img_num); $i++) { 
				$pic_item_tpl = $template_list_item['PICLIST']['example'];

				$pic_item_tpl = str_replace('{PICWIDTH}', $design_arr['TEMPLATE_EDIT']['pic_w'], $pic_item_tpl);

				$pic_item_tpl = str_replace('{PICHEIGHT}', $design_arr['TEMPLATE_EDIT']['pic_h'] ? $design_arr['TEMPLATE_EDIT']['pic_h'] : '高度不限', $pic_item_tpl);

				$pic_list_html .= $pic_item_tpl;

			}
		}

		$template_html = str_replace('{PICLIST}', $pic_list_html, $template_html);

		return $template_html;
	}
	public function fixture_tpl_ad_three_column($show_edit,$design_arr,$template_list_item,$tpl_data,$template_html)
	{
		$template_html = $this->fixture_tpl_ad_five_column($show_edit,$design_arr,$template_list_item,$tpl_data,$template_html);

		return $template_html;
	}
	public function fixture_tpl_ad_four_column($show_edit,$design_arr,$template_list_item,$tpl_data,$template_html)
	{
		$template_html = $this->fixture_tpl_ad_five_column($show_edit,$design_arr,$template_list_item,$tpl_data,$template_html);

		return $template_html;
	}
	public function fixture_tpl_ad_floating_layer($show_edit,$design_arr,$template_list_item,$tpl_data,$template_html)
	{
		// 获取装修模板的数据
		$tpl_all_data = unserialize($tpl_data['sld_tpl_data']);

		// pic_list
		// 获取图片列表
		$tpl_pic_list_data = $tpl_all_data['pic_list'];

		// 获取图片列表
		$model_fixture_pic = Model('web_home');

		$total_num = $design_arr['TEMPLATE_EDIT']['length'];
		$has_img_num = 0;

		$selected_list_data = '';

		if ($show_edit) {
			// 后台 装修 展示
			$tmplate_type_code = 'EDITPICLIST';
		}else{
			// 前台展示
			$tmplate_type_code = 'PICLIST';
		}

		if ($tpl_pic_list_data) {
			$has_img_num = count($tpl_pic_list_data);

			// 排序
			$arr_sort = array(  
			        'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序  
			        'field'     => 'sort',       //排序字段  
			);  
			$arrSort = array();  
			foreach($tpl_pic_list_data AS $uniqid => $row){  
			    foreach($row AS $key=>$value){  
			        $arrSort[$key][$uniqid] = $value;  
			    }  
			}
			if($arr_sort['direction']){  
				array_multisort($arrSort[$arr_sort['field']], constant($arr_sort['direction']), $tpl_pic_list_data);  
			}

			foreach ($tpl_pic_list_data as $key => $value) {
				$pic_item_tpl = $template_list_item[$tmplate_type_code]['real'];

				// 获取信息
				if ($tpl_data['sld_shop_id']) {
					$model_album = Model('imagespace');
					$pic_info = $model_album->getOne(array('apic_id'=>$value['pic_id']));
					$pic_item_tpl = str_replace('{PICSRC}', thumb($pic_info, 'real'), $pic_item_tpl);
				}else{
					$pic_info = $model_fixture_pic->getFixturePicInfo(array('id'=>$value['pic_id']),array('id','sld_pic_name'));
					$pic_item_tpl = str_replace('{PICSRC}', $this->fixture_pic_url($pic_info['sld_pic_name']), $pic_item_tpl);
				}

				$pic_link = $show_edit ? 'javascript:;' : ($value['links'] ? $value['links'] : 'javascript:;') ;

				$pic_item_tpl = str_replace('{PICLINK}', $pic_link, $pic_item_tpl);

				$pic_list_html .= $pic_item_tpl;
			}
		}

		if (!$has_img_num && $show_edit) {
			for ($i=0; $i < ($total_num-$has_img_num); $i++) { 
				$pic_item_tpl = $template_list_item[$tmplate_type_code]['example'];

				$pic_item_tpl = str_replace('{PICWIDTH}', $design_arr['TEMPLATE_EDIT']['pic_w'], $pic_item_tpl);

				$pic_item_tpl = str_replace('{PICHEIGHT}', $design_arr['TEMPLATE_EDIT']['pic_h'] ? $design_arr['TEMPLATE_EDIT']['pic_h'] : '高度不限', $pic_item_tpl);

				$pic_list_html .= $pic_item_tpl;

			}
		}

		if ($show_edit) {
			$template_html = str_replace('{PICWIDTH}', $design_arr['TEMPLATE_EDIT']['pic_w'], $template_html);

			$template_html = str_replace('{PICHEIGHT}', $design_arr['TEMPLATE_EDIT']['pic_h'] ? $design_arr['TEMPLATE_EDIT']['pic_h'] : '高度不限', $template_html);
			$template_html = str_replace('{PICLIST}', $pic_list_html, $template_html);
		}else{
			$script_html = '<script>';
			$script_html .= 'if(!localStorage.fixed_ad_layer_'.$tpl_data['sld_tpl_id'].'){';
			$script_html .= '$(".fixed-ad-layer").show();';
			$script_html .= '}';
			$script_html .= '$("body").on("click",".close-fixed-ad",function(){';
			$script_html .= 'localStorage.fixed_ad_layer_'.$tpl_data['sld_tpl_id'].' = true;';
			$script_html .= '$(".fixed-ad-layer").hide();';
			$script_html .= '});';
			$script_html .= '</script>';
			$pic_list_html .= $script_html;
			$template_html = $pic_list_html;
		}

		return $template_html;
	}
	public function fixture_tpl_ad_special_groups1($show_edit,$design_arr,$template_list_item,$tpl_data,$template_html)
	{
		// 获取装修模板的数据
		$tpl_all_data = unserialize($tpl_data['sld_tpl_data']);

		// special_name_1,special_name_2
		// 获取图片列表
		$special_name_design_list = array(
			array('data_name'=>'special_name_1','edit_btn'=>'TEMPLATE_EDIT','list_index'=>1),
			array('data_name'=>'special_name_2','edit_btn'=>'TEMPLATE_EDIT_2','list_index'=>2),
		);

		foreach ($special_name_design_list as $item_key => $item_value) {

			$special_name_title_html = $show_edit ? '添加标题' : '';
			$special_name_sub_title_html = $show_edit ? '添加子标题' : '';
			$special_name_title_link_html = 'javascript:;';

			$now_index =  $item_value['list_index'] ? $item_value['list_index'] : '';
			$tpl_t_data = $tpl_all_data[$item_value['data_name']];

			if ($tpl_t_data) {
				$special_name_title_html = $tpl_t_data[0]['name'];
				$special_name_title_link_html = $show_edit ? $special_name_title_link_html : $tpl_t_data[0]['title_link'];
				$special_name_sub_title_html = $tpl_t_data[0]['sub_title'];
			}

			$template_html = str_replace('{TSTRING'.$now_index.'}', $special_name_title_html, $template_html);
			$template_html = str_replace('{TITLELINK'.$now_index.'}', $special_name_title_link_html, $template_html);
			$template_html = str_replace('{SUBTITLE'.$now_index.'}', $special_name_sub_title_html, $template_html);
		}

		// pic_list_1,pic_list_2,pic_list_3,pic_list_4
		// 获取图片列表
		$pic_list_design_list = array(
			array('data_name'=>'pic_list_1','edit_btn'=>'TEMPLATE_EDIT_1'),
			array('data_name'=>'pic_list_2','edit_btn'=>'TEMPLATE_EDIT_3'),
			array('data_name'=>'pic_list_3','edit_btn'=>'TEMPLATE_EDIT_4'),
			array('data_name'=>'pic_list_4','edit_btn'=>'TEMPLATE_EDIT_5'),
			array('data_name'=>'pic_list_5','edit_btn'=>'TEMPLATE_EDIT_6')
		);

		// 获取图片列表
		$model_fixture_pic = Model('web_home');

		foreach ($pic_list_design_list as $item_key => $item_value) {
			$now_index = ($item_key+1);

			$total_num = $design_arr[$item_value['edit_btn']]['length'];
			$has_img_num = 0;

			$tpl_pic_list_data = $tpl_all_data[$item_value['data_name']];

			$pic_list_html = '';

			if ($tpl_pic_list_data) {
				$has_img_num = count($tpl_pic_list_data);

				// 排序
				$arr_sort = array(  
				        'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序  
				        'field'     => 'sort',       //排序字段  
				);  
				$arrSort = array();  
				foreach($tpl_pic_list_data AS $uniqid => $row){  
				    foreach($row AS $key=>$value){  
				        $arrSort[$key][$uniqid] = $value;  
				    }  
				}
				if($arr_sort['direction']){  
					array_multisort($arrSort[$arr_sort['field']], constant($arr_sort['direction']), $tpl_pic_list_data);  
				}

				foreach ($tpl_pic_list_data as $key => $value) {
					$pic_item_tpl = $template_list_item['PICLIST']['real'];

					// 获取信息
					if ($tpl_data['sld_shop_id']) {
						$model_album = Model('imagespace');
						$pic_info = $model_album->getOne(array('apic_id'=>$value['pic_id']));
						$pic_item_tpl = str_replace('{PICSRC}', thumb($pic_info, 'real'), $pic_item_tpl);
					}else{
						$pic_info = $model_fixture_pic->getFixturePicInfo(array('id'=>$value['pic_id']),array('id','sld_pic_name'));
						$pic_item_tpl = str_replace('{PICSRC}', $this->fixture_pic_url($pic_info['sld_pic_name']), $pic_item_tpl);
					}

					$pic_link = $show_edit ? 'javascript:;' : ($value['links'] ? $value['links'] : 'javascript:;') ;

					$pic_item_tpl = str_replace('{PICLINK}', $pic_link, $pic_item_tpl);

					$pic_list_html .= $pic_item_tpl;
				}

			}

			if (!$has_img_num && $show_edit) {
				for ($i=0; $i < ($total_num-$has_img_num); $i++) { 
					$pic_item_tpl = $template_list_item['PICLIST']['example'];

					$pic_item_tpl = str_replace('{PICWIDTH}', $design_arr[$item_value['edit_btn']]['pic_w'], $pic_item_tpl);

					$pic_item_tpl = str_replace('{PICHEIGHT}', $design_arr[$item_value['edit_btn']]['pic_h'] ? $design_arr[$item_value['edit_btn']]['pic_h'] : '高度不限', $pic_item_tpl);

					$pic_list_html .= $pic_item_tpl;

				}
			}

			$template_html = str_replace('{PICLIST'.$now_index.'}', $pic_list_html, $template_html);

		}

		return $template_html;
	}
	public function fixture_tpl_ad_special_groups2($show_edit,$design_arr,$template_list_item,$tpl_data,$template_html)
	{
		// 获取装修模板的数据
		$tpl_all_data = unserialize($tpl_data['sld_tpl_data']);

		// special_name_1,special_name_2
		// 获取图片列表
		$special_name_design_list = array(
			array('data_name'=>'special_name_1','edit_btn'=>'TEMPLATE_EDIT_2')
		);

		foreach ($special_name_design_list as $item_key => $item_value) {

			$special_name_title_html = $show_edit ? '添加标题' : '';
			$special_name_sub_title_html = $show_edit ? '添加子标题' : '';
			$special_name_title_link_html = 'javascript:void(0);';
		
			$now_index = ($item_key+1);
			$tpl_t_data = $tpl_all_data[$item_value['data_name']];
			if ($tpl_t_data) {
				$special_name_title_html = $tpl_t_data[0]['name'];
				$special_name_title_link_html = $show_edit ? $special_name_title_link_html : $tpl_t_data[0]['title_link'];
				$special_name_sub_title_html = $tpl_t_data[0]['sub_title'];
			}

			$template_html = str_replace('{TSTRING'.$now_index.'}', $special_name_title_html, $template_html);
			$template_html = str_replace('{TITLELINK'.$now_index.'}', $special_name_title_link_html, $template_html);
			$template_html = str_replace('{SUBTITLE'.$now_index.'}', $special_name_sub_title_html, $template_html);
		}

		// pic_list_1,pic_list_2,pic_list_3,pic_list_4
		// 获取图片列表
		$pic_list_design_list = array(
			array('data_name'=>'pic_list_1','edit_btn'=>'TEMPLATE_EDIT'),
			array('data_name'=>'pic_list_2','edit_btn'=>'TEMPLATE_EDIT_1'),
			array('data_name'=>'pic_list_3','edit_btn'=>'TEMPLATE_EDIT_3'),
			array('data_name'=>'pic_list_4','edit_btn'=>'TEMPLATE_EDIT_4')
		);

		// 获取图片列表
		$model_fixture_pic = Model('web_home');

		foreach ($pic_list_design_list as $item_key => $item_value) {
			$now_index = ($item_key+1);

			$total_num = $design_arr[$item_value['edit_btn']]['length'];
			$has_img_num = 0;

			$tpl_pic_list_data = $tpl_all_data[$item_value['data_name']];

			$pic_list_html = '';

			if ($tpl_pic_list_data) {
				$has_img_num = count($tpl_pic_list_data);

				// 排序
				$arr_sort = array(  
				        'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序  
				        'field'     => 'sort',       //排序字段  
				);  
				$arrSort = array();  
				foreach($tpl_pic_list_data AS $uniqid => $row){  
				    foreach($row AS $key=>$value){  
				        $arrSort[$key][$uniqid] = $value;  
				    }  
				}
				if($arr_sort['direction']){  
					array_multisort($arrSort[$arr_sort['field']], constant($arr_sort['direction']), $tpl_pic_list_data);  
				}

				foreach ($tpl_pic_list_data as $key => $value) {
					$pic_item_tpl = $template_list_item['PICLIST']['real'];

					$li_class = '';
					if ($key%2 == 1) {
						// 第二个
						$li_class = 'right-item';
					}

					// 获取信息
					if ($tpl_data['sld_shop_id']) {
						$model_album = Model('imagespace');
						$pic_info = $model_album->getOne(array('apic_id'=>$value['pic_id']));
						$pic_item_tpl = str_replace('{PICSRC}', thumb($pic_info, 'real'), $pic_item_tpl);
					}else{
						$pic_info = $model_fixture_pic->getFixturePicInfo(array('id'=>$value['pic_id']),array('id','sld_pic_name'));
						$pic_item_tpl = str_replace('{PICSRC}', $this->fixture_pic_url($pic_info['sld_pic_name']), $pic_item_tpl);
					}

					$pic_link = $show_edit ? 'javascript:;' : ($value['links'] ? $value['links'] : 'javascript:;') ;

					$pic_item_tpl = str_replace('{PICLINK}', $pic_link, $pic_item_tpl);
					$pic_item_tpl = str_replace('{LICLASS}', $li_class, $pic_item_tpl);

					$pic_list_html .= $pic_item_tpl;
				}

			}

			if (!$has_img_num && $show_edit) {
				for ($i=0; $i < ($total_num-$has_img_num); $i++) { 
					$pic_item_tpl = $template_list_item['PICLIST']['example'];

					$li_class = '';
					if ($i%2 == 1) {
						// 第二个
						$li_class = 'right-item';
					}

					$pic_item_tpl = str_replace('{PICWIDTH}', $design_arr[$item_value['edit_btn']]['pic_w'], $pic_item_tpl);

					$pic_item_tpl = str_replace('{PICHEIGHT}', $design_arr[$item_value['edit_btn']]['pic_h'] ? $design_arr[$item_value['edit_btn']]['pic_h'] : '高度不限', $pic_item_tpl);
					$pic_item_tpl = str_replace('{LICLASS}', $li_class, $pic_item_tpl);

					$pic_list_html .= $pic_item_tpl;

				}
			}

			$template_html = str_replace('{PICLIST'.$now_index.'}', $pic_list_html, $template_html);

		}

		return $template_html;
	}
	public function fixture_tpl_ads_three_carousel($show_edit,$design_arr,$template_list_item,$tpl_data,$template_html)
	{
		// 获取装修模板的数据
		$tpl_all_data = unserialize($tpl_data['sld_tpl_data']);

		// pic_list
		// 获取图片列表
		$tpl_pic_list_data = $tpl_all_data['pic_list'];

		// 获取图片列表
		$model_fixture_pic = Model('web_home');

		$total_num = $design_arr['TEMPLATE_EDIT']['length'];
		$has_img_num = 0;

		$selected_list_data = '';

		if ($tpl_pic_list_data) {
			$has_img_num = count($tpl_pic_list_data);

			// 排序
			$arr_sort = array(  
			        'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序  
			        'field'     => 'sort',       //排序字段  
			);  
			$arrSort = array();  
			foreach($tpl_pic_list_data AS $uniqid => $row){  
			    foreach($row AS $key=>$value){  
			        $arrSort[$key][$uniqid] = $value;  
			    }  
			}
			if($arr_sort['direction']){  
				array_multisort($arrSort[$arr_sort['field']], constant($arr_sort['direction']), $tpl_pic_list_data);  
			}

			foreach ($tpl_pic_list_data as $key => $value) {
				$pic_item_tpl = $template_list_item['PICLIST']['real'];

				$li_class = '';
				if ($key%3 == 0) {
					// 第一个
					$li_class = 'three-first';
				}elseif ($key%3==1) {
					// 第二个
					$li_class = 'three-middle';
				}
 
				// 获取信息
				if ($tpl_data['sld_shop_id']) {
					$model_album = Model('imagespace');
					$pic_info = $model_album->getOne(array('apic_id'=>$value['pic_id']));
					$pic_item_tpl = str_replace('{PICSRC}', thumb($pic_info, 'real'), $pic_item_tpl);
				}else{
					$pic_info = $model_fixture_pic->getFixturePicInfo(array('id'=>$value['pic_id']),array('id','sld_pic_name'));
					$pic_item_tpl = str_replace('{PICSRC}', $this->fixture_pic_url($pic_info['sld_pic_name']), $pic_item_tpl);
				}

				$pic_link = $show_edit ? 'javascript:;' : ($value['links'] ? $value['links'] : 'javascript:;') ;

				$pic_item_tpl = str_replace('{PICLINK}', $pic_link, $pic_item_tpl);
				$pic_item_tpl = str_replace('{LICLASS}', $li_class, $pic_item_tpl);

				$pic_list_html .= $pic_item_tpl;
			}
		}

		if (!$has_img_num && $show_edit) {
			for ($i=0; $i < ($total_num-$has_img_num); $i++) { 
				$pic_item_tpl = $template_list_item['PICLIST']['example'];

				$li_class = '';
				if ($i%3 == 0) {
					// 第一个
					$li_class = 'three-first';
				}elseif ($i%3==1) {
					// 第二个
					$li_class = 'three-middle';
				}

				$pic_item_tpl = str_replace('{PICWIDTH}', $design_arr['TEMPLATE_EDIT']['pic_w'], $pic_item_tpl);

				$pic_item_tpl = str_replace('{PICHEIGHT}', $design_arr['TEMPLATE_EDIT']['pic_h'] ? $design_arr['TEMPLATE_EDIT']['pic_h'] : '高度不限', $pic_item_tpl);

				$pic_item_tpl = str_replace('{LICLASS}', $li_class, $pic_item_tpl);

				$pic_list_html .= $pic_item_tpl;

			}
		}

		$template_html = str_replace('{PICLIST}', $pic_list_html, $template_html);

		return $template_html;
	}
	public function fixture_tpl_ad_muti_group1($show_edit,$design_arr,$template_list_item,$tpl_data,$template_html)
	{
		// 获取装修模板的数据
		$tpl_all_data = unserialize($tpl_data['sld_tpl_data']);

		// pic_list_1,pic_list_2,pic_list_3
		// 获取图片列表
		$pic_list_design_list = array(
			array('data_name'=>'pic_list_1','edit_btn'=>'TEMPLATE_EDIT'),
			array('data_name'=>'pic_list_2','edit_btn'=>'TEMPLATE_EDIT_1'),
			array('data_name'=>'pic_list_3','edit_btn'=>'TEMPLATE_EDIT_2')
		);

		// 获取图片列表
		$model_fixture_pic = Model('web_home');

		foreach ($pic_list_design_list as $item_key => $item_value) {
			$now_index = ($item_key+1);

			$total_num = $design_arr[$item_value['edit_btn']]['length'];
			$has_img_num = 0;

			$tpl_pic_list_data = $tpl_all_data[$item_value['data_name']];

			$pic_list_html = '';

			if ($tpl_pic_list_data) {
				$has_img_num = count($tpl_pic_list_data);

				// 排序
				$arr_sort = array(  
				        'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序  
				        'field'     => 'sort',       //排序字段  
				);  
				$arrSort = array();  
				foreach($tpl_pic_list_data AS $uniqid => $row){  
				    foreach($row AS $key=>$value){  
				        $arrSort[$key][$uniqid] = $value;  
				    }  
				}
				if($arr_sort['direction']){  
					array_multisort($arrSort[$arr_sort['field']], constant($arr_sort['direction']), $tpl_pic_list_data);  
				}

				foreach ($tpl_pic_list_data as $key => $value) {
					$pic_item_tpl = $template_list_item['PICLIST']['real'];

					$li_class = '';
					// if ($key%2 == 1) {
					// 	// 第二个
					// 	$li_class = 'right-item';
					// }

					// 获取信息
					if ($tpl_data['sld_shop_id']) {
						$model_album = Model('imagespace');
						$pic_info = $model_album->getOne(array('apic_id'=>$value['pic_id']));
						$pic_item_tpl = str_replace('{PICSRC}', thumb($pic_info, 'real'), $pic_item_tpl);
					}else{
						$pic_info = $model_fixture_pic->getFixturePicInfo(array('id'=>$value['pic_id']),array('id','sld_pic_name'));
						$pic_item_tpl = str_replace('{PICSRC}', $this->fixture_pic_url($pic_info['sld_pic_name']), $pic_item_tpl);
					}

					$pic_link = $show_edit ? 'javascript:;' : ($value['links'] ? $value['links'] : 'javascript:;') ;

					$pic_item_tpl = str_replace('{PICLINK}', $pic_link, $pic_item_tpl);
					$pic_item_tpl = str_replace('{LICLASS}', $li_class, $pic_item_tpl);

					$pic_list_html .= $pic_item_tpl;
				}

			}

			if (!$has_img_num && $show_edit) {
				for ($i=0; $i < ($total_num-$has_img_num); $i++) { 
					$pic_item_tpl = $template_list_item['PICLIST']['example'];

					$li_class = '';
					// if ($i%2 == 1) {
					// 	// 第二个
					// 	$li_class = 'right-item';
					// }

					$pic_item_tpl = str_replace('{PICWIDTH}', $design_arr[$item_value['edit_btn']]['pic_w'], $pic_item_tpl);

					$pic_item_tpl = str_replace('{PICHEIGHT}', $design_arr[$item_value['edit_btn']]['pic_h'] ? $design_arr[$item_value['edit_btn']]['pic_h'] : '高度不限', $pic_item_tpl);
					$pic_item_tpl = str_replace('{LICLASS}', $li_class, $pic_item_tpl);

					$pic_list_html .= $pic_item_tpl;

				}
			}

			$template_html = str_replace('{PICLIST'.$now_index.'}', $pic_list_html, $template_html);

		}

		return $template_html;
	}
	public function fixture_tpl_ad_muti_group2($show_edit,$design_arr,$template_list_item,$tpl_data,$template_html)
	{
		// 获取装修模板的数据
		$tpl_all_data = unserialize($tpl_data['sld_tpl_data']);

		// pic_list_1,pic_list_2,pic_list_3
		// 获取图片列表
		$pic_list_design_list = array(
			array('data_name'=>'pic_list_1','edit_btn'=>'TEMPLATE_EDIT'),
			array('data_name'=>'pic_list_2','edit_btn'=>'TEMPLATE_EDIT_1'),
			array('data_name'=>'pic_list_3','edit_btn'=>'TEMPLATE_EDIT_2')
		);

		// 获取图片列表
		$model_fixture_pic = Model('web_home');

		foreach ($pic_list_design_list as $item_key => $item_value) {
			$now_index = ($item_key+1);

			$total_num = $design_arr[$item_value['edit_btn']]['length'];
			$has_img_num = 0;

			$tpl_pic_list_data = $tpl_all_data[$item_value['data_name']];

			$pic_list_html = '';

			if ($tpl_pic_list_data) {
				$has_img_num = count($tpl_pic_list_data);

				// 排序
				$arr_sort = array(  
				        'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序  
				        'field'     => 'sort',       //排序字段  
				);  
				$arrSort = array();  
				foreach($tpl_pic_list_data AS $uniqid => $row){  
				    foreach($row AS $key=>$value){  
				        $arrSort[$key][$uniqid] = $value;  
				    }  
				}
				if($arr_sort['direction']){  
					array_multisort($arrSort[$arr_sort['field']], constant($arr_sort['direction']), $tpl_pic_list_data);  
				}

				foreach ($tpl_pic_list_data as $key => $value) {
					$pic_item_tpl = $template_list_item['PICLIST']['real'];

					$li_class = '';
					if ($key%2 == 1) {
						// 第二个
						$li_class = 'right-item';
					}

					// 获取信息
					if ($tpl_data['sld_shop_id']) {
						$model_album = Model('imagespace');
						$pic_info = $model_album->getOne(array('apic_id'=>$value['pic_id']));
						$pic_item_tpl = str_replace('{PICSRC}', thumb($pic_info, 'real'), $pic_item_tpl);
					}else{
						$pic_info = $model_fixture_pic->getFixturePicInfo(array('id'=>$value['pic_id']),array('id','sld_pic_name'));
						$pic_item_tpl = str_replace('{PICSRC}', $this->fixture_pic_url($pic_info['sld_pic_name']), $pic_item_tpl);
					}

					$pic_link = $show_edit ? 'javascript:;' : ($value['links'] ? $value['links'] : 'javascript:;') ;

					$pic_item_tpl = str_replace('{PICLINK}', $pic_link, $pic_item_tpl);
					$pic_item_tpl = str_replace('{LICLASS}', $li_class, $pic_item_tpl);

					$pic_list_html .= $pic_item_tpl;
				}

			}

			if (!$has_img_num && $show_edit) {
				for ($i=0; $i < ($total_num-$has_img_num); $i++) { 
					$pic_item_tpl = $template_list_item['PICLIST']['example'];

					$li_class = '';
					if ($i%2 == 1) {
						// 第二个
						$li_class = 'right-item';
					}

					$pic_item_tpl = str_replace('{PICWIDTH}', $design_arr[$item_value['edit_btn']]['pic_w'], $pic_item_tpl);

					$pic_item_tpl = str_replace('{PICHEIGHT}', $design_arr[$item_value['edit_btn']]['pic_h'] ? $design_arr[$item_value['edit_btn']]['pic_h'] : '高度不限', $pic_item_tpl);
					$pic_item_tpl = str_replace('{LICLASS}', $li_class, $pic_item_tpl);

					$pic_list_html .= $pic_item_tpl;

				}
			}

			$template_html = str_replace('{PICLIST'.$now_index.'}', $pic_list_html, $template_html);

		}

		return $template_html;
	}
	public function fixture_tpl_ad_recommend_group($show_edit,$design_arr,$template_list_item,$tpl_data,$template_html)
	{
		// 获取装修模板的数据
		$tpl_all_data = unserialize($tpl_data['sld_tpl_data']);

		// t_1,t_2
		// 获取标题列表
		$t_design_list = array(
			array('data_name'=>'t_1','edit_btn'=>'TEMPLATE_EDIT'),
			array('data_name'=>'t_2','edit_btn'=>'TEMPLATE_EDIT_1'),
			array('data_name'=>'t_3','edit_btn'=>'TEMPLATE_EDIT_2'),
		);

		foreach ($t_design_list as $item_key => $item_value) {

			$t_title_html = $show_edit ? '添加标题' : '';
			$t_bg_color_html = '';
			$t_font_color_html = '';
		
			$now_index = ($item_key+1);
			$tpl_t_data = $tpl_all_data[$item_value['data_name']];
			if ($tpl_t_data) {
				$t_title_html = $tpl_t_data[0]['name'];
				$t_bg_color_html = $tpl_t_data[0]['bg_color'] ? '#'.$tpl_t_data[0]['bg_color'] : '';
				$t_font_color_html = $tpl_t_data[0]['font_color'] ? '#'.$tpl_t_data[0]['font_color'] : '';
			}

			$template_html = str_replace('{TSTRING'.$now_index.'}', $t_title_html, $template_html);
			$template_html = str_replace('{TITLEBGCOLOR'.$now_index.'}', $t_bg_color_html, $template_html);
			$template_html = str_replace('{TITLEFONTCOLOR'.$now_index.'}', $t_font_color_html, $template_html);
		}

		// pic_list_1,pic_list_2,pic_list_3
		// 获取图片列表
		$pic_list_design_list = array(
			array('data_name'=>'pic_list_1','edit_btn'=>'TEMPLATE_EDIT_3'),
			array('data_name'=>'pic_list_2','edit_btn'=>'TEMPLATE_EDIT_4'),
			array('data_name'=>'pic_list_3','edit_btn'=>'TEMPLATE_EDIT_5')
		);

		// 获取图片列表
		$model_fixture_pic = Model('web_home');

		foreach ($pic_list_design_list as $item_key => $item_value) {
			$now_index = ($item_key+1);

			$total_num = $design_arr[$item_value['edit_btn']]['length'];
			$has_img_num = 0;

			$tpl_pic_list_data = $tpl_all_data[$item_value['data_name']];

			$pic_list_html = '';

			if ($tpl_pic_list_data) {
				$has_img_num = count($tpl_pic_list_data);

				// 排序
				$arr_sort = array(  
				        'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序  
				        'field'     => 'sort',       //排序字段  
				);  
				$arrSort = array();  
				foreach($tpl_pic_list_data AS $uniqid => $row){  
				    foreach($row AS $key=>$value){  
				        $arrSort[$key][$uniqid] = $value;  
				    }  
				}
				if($arr_sort['direction']){  
					array_multisort($arrSort[$arr_sort['field']], constant($arr_sort['direction']), $tpl_pic_list_data);  
				}

				foreach ($tpl_pic_list_data as $key => $value) {
					$pic_item_tpl = $template_list_item['PICLIST']['real'];

					$col_num = 1;
					switch ($item_value['data_name']) {
						case 'pic_list_1':
							$col_num = 2;
							break;

						case 'pic_list_2':
							$col_num = 1;
							break;

						case 'pic_list_3':
							$col_num = 3;
							break;
					}
					$li_class = '';
					if ($key%$col_num == 0) {
						// $col_num 个一行 每行第一个
						$li_class = 'row-first ';
					}
					// 最后一行
					if ($key >= ($total_num - $col_num) ) {
						$li_class .= 'row-no-border-bottom';
					}

					// 获取信息
					if ($tpl_data['sld_shop_id']) {
						$model_album = Model('imagespace');
						$pic_info = $model_album->getOne(array('apic_id'=>$value['pic_id']));
						$pic_item_tpl = str_replace('{PICSRC}', thumb($pic_info, 'real'), $pic_item_tpl);
					}else{
						$pic_info = $model_fixture_pic->getFixturePicInfo(array('id'=>$value['pic_id']),array('id','sld_pic_name'));
						$pic_item_tpl = str_replace('{PICSRC}', $this->fixture_pic_url($pic_info['sld_pic_name']), $pic_item_tpl);
					}

					$pic_link = $show_edit ? 'javascript:;' : ($value['links'] ? $value['links'] : 'javascript:;') ;

					$pic_item_tpl = str_replace('{PICLINK}', $pic_link, $pic_item_tpl);
					$pic_item_tpl = str_replace('{LICLASS}', $li_class, $pic_item_tpl);

					$pic_list_html .= $pic_item_tpl;
				}

			}

			if (!$has_img_num && $show_edit) {
				for ($i=0; $i < ($total_num-$has_img_num); $i++) { 
					$pic_item_tpl = $template_list_item['PICLIST']['example'];

					$col_num = 1;
					switch ($item_value['data_name']) {
						case 'pic_list_1':
							$col_num = 2;
							break;

						case 'pic_list_2':
							$col_num = 1;
							break;

						case 'pic_list_3':
							$col_num = 3;
							break;
					}
					$li_class = '';
					if ($i%$col_num == 0) {
						// $col_num 个一行 每行第一个
						$li_class = 'row-first ';
					}
					// 最后一行
					if ($i >= ($total_num - $col_num) ) {
						$li_class .= 'row-no-border-bottom';
					}

					$pic_item_tpl = str_replace('{PICWIDTH}', $design_arr[$item_value['edit_btn']]['pic_w'], $pic_item_tpl);

					$pic_item_tpl = str_replace('{PICHEIGHT}', $design_arr[$item_value['edit_btn']]['pic_h'] ? $design_arr[$item_value['edit_btn']]['pic_h'] : '高度不限', $pic_item_tpl);
					$pic_item_tpl = str_replace('{LICLASS}', $li_class, $pic_item_tpl);

					$pic_list_html .= $pic_item_tpl;

				}
			}

			$template_html = str_replace('{PICLIST'.$now_index.'}', $pic_list_html, $template_html);

		}

		return $template_html;
	}
	public function fixture_tpl_ad_mutil_column1($show_edit,$design_arr,$template_list_item,$tpl_data,$template_html)
	{
		// 获取装修模板的数据
		$tpl_all_data = unserialize($tpl_data['sld_tpl_data']);

		// pic_list_1,pic_list_2
		// 获取图片列表
		$pic_list_design_list = array(
			array('data_name'=>'pic_list','edit_btn'=>'TEMPLATE_EDIT','list_index'=>''),
			array('data_name'=>'pic_list_1','edit_btn'=>'TEMPLATE_EDIT_1','list_index'=>1)
		);

		// 获取图片列表
		$model_fixture_pic = Model('web_home');

		foreach ($pic_list_design_list as $item_key => $item_value) {
			$now_index = $item_value['list_index'] ? $item_value['list_index'] : '';

			$total_num = $design_arr[$item_value['edit_btn']]['length'];
			$has_img_num = 0;

			$tpl_pic_list_data = $tpl_all_data[$item_value['data_name']];

			$pic_list_html = '';

			if ($tpl_pic_list_data) {
				$has_img_num = count($tpl_pic_list_data);

				// 排序
				$arr_sort = array(  
				        'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序  
				        'field'     => 'sort',       //排序字段  
				);  
				$arrSort = array();  
				foreach($tpl_pic_list_data AS $uniqid => $row){  
				    foreach($row AS $key=>$value){  
				        $arrSort[$key][$uniqid] = $value;  
				    }  
				}
				if($arr_sort['direction']){  
					array_multisort($arrSort[$arr_sort['field']], constant($arr_sort['direction']), $tpl_pic_list_data);  
				}

				foreach ($tpl_pic_list_data as $key => $value) {
					$pic_item_tpl = $template_list_item['PICLIST']['real'];

					// 获取信息
					if ($tpl_data['sld_shop_id']) {
						$model_album = Model('imagespace');
						$pic_info = $model_album->getOne(array('apic_id'=>$value['pic_id']));
						$pic_item_tpl = str_replace('{PICSRC}', thumb($pic_info, 'real'), $pic_item_tpl);
					}else{
						$pic_info = $model_fixture_pic->getFixturePicInfo(array('id'=>$value['pic_id']),array('id','sld_pic_name'));
						$pic_item_tpl = str_replace('{PICSRC}', $this->fixture_pic_url($pic_info['sld_pic_name']), $pic_item_tpl);
					}

					$pic_link = $show_edit ? 'javascript:;' : ($value['links'] ? $value['links'] : 'javascript:;') ;

					$pic_item_tpl = str_replace('{PICLINK}', $pic_link, $pic_item_tpl);

					$pic_list_html .= $pic_item_tpl;
				}

			}

			if (!$has_img_num && $show_edit) {
				for ($i=0; $i < ($total_num-$has_img_num); $i++) { 
					$pic_item_tpl = $template_list_item['PICLIST']['example'];

					$pic_item_tpl = str_replace('{PICWIDTH}', $design_arr[$item_value['edit_btn']]['pic_w'], $pic_item_tpl);

					$pic_item_tpl = str_replace('{PICHEIGHT}', $design_arr[$item_value['edit_btn']]['pic_h'] ? $design_arr[$item_value['edit_btn']]['pic_h'] : '高度不限', $pic_item_tpl);

					$pic_list_html .= $pic_item_tpl;

				}
			}

			$template_html = str_replace('{PICLIST'.$now_index.'}', $pic_list_html, $template_html);

		}

		return $template_html;
	}
	public function fixture_tpl_ad_mutil_column2($show_edit,$design_arr,$template_list_item,$tpl_data,$template_html)
	{
		// 获取装修模板的数据
		$tpl_all_data = unserialize($tpl_data['sld_tpl_data']);

		// pic_list_1,pic_list_2,pic_list_3
		// 获取图片列表
		$pic_list_design_list = array(
			array('data_name'=>'pic_list','edit_btn'=>'TEMPLATE_EDIT','list_index'=>''),
			array('data_name'=>'pic_list_1','edit_btn'=>'TEMPLATE_EDIT_1','list_index'=>1),
			array('data_name'=>'pic_list_2','edit_btn'=>'TEMPLATE_EDIT_2','list_index'=>2),
		);

		// 获取图片列表
		$model_fixture_pic = Model('web_home');

		foreach ($pic_list_design_list as $item_key => $item_value) {
			$now_index = $item_value['list_index'] ? $item_value['list_index'] : '';

			$total_num = $design_arr[$item_value['edit_btn']]['length'];
			$has_img_num = 0;

			$tpl_pic_list_data = $tpl_all_data[$item_value['data_name']];

			$pic_list_html = '';

			if ($tpl_pic_list_data) {
				$has_img_num = count($tpl_pic_list_data);

				// 排序
				$arr_sort = array(  
				        'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序  
				        'field'     => 'sort',       //排序字段  
				);  
				$arrSort = array();  
				foreach($tpl_pic_list_data AS $uniqid => $row){  
				    foreach($row AS $key=>$value){  
				        $arrSort[$key][$uniqid] = $value;  
				    }  
				}
				if($arr_sort['direction']){  
					array_multisort($arrSort[$arr_sort['field']], constant($arr_sort['direction']), $tpl_pic_list_data);  
				}

				foreach ($tpl_pic_list_data as $key => $value) {
					$pic_item_tpl = $template_list_item['PICLIST']['real'];

					$li_class = '';
					if ($key > 0) {
						// 第二个后
						$li_class = 'bottom-ad-img';
					}

					// 获取信息
					if ($tpl_data['sld_shop_id']) {
						$model_album = Model('imagespace');
						$pic_info = $model_album->getOne(array('apic_id'=>$value['pic_id']));
						$pic_item_tpl = str_replace('{PICSRC}', thumb($pic_info, 'real'), $pic_item_tpl);
					}else{
						$pic_info = $model_fixture_pic->getFixturePicInfo(array('id'=>$value['pic_id']),array('id','sld_pic_name'));
						$pic_item_tpl = str_replace('{PICSRC}', $this->fixture_pic_url($pic_info['sld_pic_name']), $pic_item_tpl);
					}

					$pic_link = $show_edit ? 'javascript:;' : ($value['links'] ? $value['links'] : 'javascript:;') ;

					$pic_item_tpl = str_replace('{PICLINK}', $pic_link, $pic_item_tpl);
					$pic_item_tpl = str_replace('{LICLASS}', $li_class, $pic_item_tpl);

					$pic_list_html .= $pic_item_tpl;
				}

			}

			if (!$has_img_num && $show_edit) {
				for ($i=0; $i < ($total_num-$has_img_num); $i++) { 
					$pic_item_tpl = $template_list_item['PICLIST']['example'];

					$li_class = '';
					if ($i > 0) {
						// 第二个后
						$li_class = 'bottom-ad-img';
					}

					$pic_item_tpl = str_replace('{PICWIDTH}', $design_arr[$item_value['edit_btn']]['pic_w'], $pic_item_tpl);

					$pic_item_tpl = str_replace('{PICHEIGHT}', $design_arr[$item_value['edit_btn']]['pic_h'] ? $design_arr[$item_value['edit_btn']]['pic_h'] : '高度不限', $pic_item_tpl);
					$pic_item_tpl = str_replace('{LICLASS}', $li_class, $pic_item_tpl);

					$pic_list_html .= $pic_item_tpl;

				}
			}

			$template_html = str_replace('{PICLIST'.$now_index.'}', $pic_list_html, $template_html);

		}

		return $template_html;
	}
	public function fixture_tpl_ad_mutil_column3($show_edit,$design_arr,$template_list_item,$tpl_data,$template_html)
	{
		$template_html = $this->fixture_tpl_ad_mutil_column2($show_edit,$design_arr,$template_list_item,$tpl_data,$template_html);
		return $template_html;
	}
	public function fixture_tpl_ad_mutil_column4($show_edit,$design_arr,$template_list_item,$tpl_data,$template_html)
	{
		$template_html = $this->fixture_tpl_ad_mutil_column2($show_edit,$design_arr,$template_list_item,$tpl_data,$template_html);
		return $template_html;
	}
	public function fixture_tpl_brand_s1($show_edit,$design_arr,$template_list_item,$tpl_data,$template_html)
	{
		// 获取装修模板的数据
		$tpl_all_data = unserialize($tpl_data['sld_tpl_data']);

		// brand
		// 获取品牌列表
		$tpl_brand_data = $tpl_all_data['brand'];

		// 获取品牌列表
		$model_brand = Model();

		$total_num = $design_arr['TEMPLATE_EDIT']['length'];
		$has_img_num = 0;

		$selected_list_data = '';
		$selected_shop_ids = array();

		if ($tpl_brand_data) {
			$has_img_num = count($tpl_brand_data);

			// 排序
			$arr_sort = array(  
			        'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序  
			        'field'     => 'sort',       //排序字段  
			);  
			$arrSort = array();  
			foreach($tpl_brand_data AS $uniqid => $row){  
			    foreach($row AS $key=>$value){  
			        $arrSort[$key][$uniqid] = $value;  
			    }  
			}
			if($arr_sort['direction']){  
				array_multisort($arrSort[$arr_sort['field']], constant($arr_sort['direction']), $tpl_brand_data);  
			}

			foreach ($tpl_brand_data as $key => $value) {
				$brand_item_tpl = $template_list_item['BRANDLIST']['real'];

				// 获取信息
				$brand_info = $model_brand->table('brand')->find($value['brand_id']);

				$brand_link = $show_edit ? 'javascript:;' : ($value['brand_id'] ? urlShop('brand', 'lists', array('brand'=>$value['brand_id'])) : 'javascript:;') ;

				$brand_item_tpl = str_replace('{BRANDSRC}', brandImage($brand_info['brand_pic']), $brand_item_tpl);
				$brand_item_tpl = str_replace('{BRANDLINK}', $brand_link, $brand_item_tpl);
				$brand_item_tpl = str_replace('{BRANDNAME}', $brand_info['brand_name'], $brand_item_tpl);

				$brand_list_html .= $brand_item_tpl;
			}
		}

		if (!$has_img_num && $show_edit) {
			for ($i=0; $i < ($total_num-$has_img_num); $i++) { 
				$brand_item_tpl = $template_list_item['BRANDLIST']['example'];

				$brand_list_html .= $brand_item_tpl;
			}
		}

		$template_html = str_replace('{BRANDLIST}', $brand_list_html, $template_html);

		return $template_html;
	}
	public function fixture_tpl_brand_s2($show_edit,$design_arr,$template_list_item,$tpl_data,$template_html)
	{
		// 获取装修模板的数据
		$tpl_all_data = unserialize($tpl_data['sld_tpl_data']);

		// pic_list_1,pic_list_2
		// 获取图片列表
		$pic_list_design_list = array(
			array('data_name'=>'pic_list_1','edit_btn'=>'TEMPLATE_EDIT','list_index'=>1),
			array('data_name'=>'pic_list_2','edit_btn'=>'TEMPLATE_EDIT_1','list_index'=>2)
		);

		// 获取图片列表
		$model_fixture_pic = Model('web_home');

		foreach ($pic_list_design_list as $item_key => $item_value) {
			$now_index = $item_value['list_index'] ? $item_value['list_index'] : '';

			$total_num = $design_arr[$item_value['edit_btn']]['length'];
			$has_img_num = 0;

			$tpl_pic_list_data = $tpl_all_data[$item_value['data_name']];

			$pic_list_html = '';

			if ($tpl_pic_list_data) {
				$has_img_num = count($tpl_pic_list_data);

				// 排序
				$arr_sort = array(  
				        'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序  
				        'field'     => 'sort',       //排序字段  
				);  
				$arrSort = array();  
				foreach($tpl_pic_list_data AS $uniqid => $row){  
				    foreach($row AS $key=>$value){  
				        $arrSort[$key][$uniqid] = $value;  
				    }  
				}
				if($arr_sort['direction']){  
					array_multisort($arrSort[$arr_sort['field']], constant($arr_sort['direction']), $tpl_pic_list_data);  
				}

				foreach ($tpl_pic_list_data as $key => $value) {
					$pic_item_tpl = $template_list_item['PICLIST']['real'];

					$li_class = '';
					if ($key%3 == 2) {
						// 第三个
						$li_class = 'big-ad-img';
					}

					// 获取信息
					if ($tpl_data['sld_shop_id']) {
						$model_album = Model('imagespace');
						$pic_info = $model_album->getOne(array('apic_id'=>$value['pic_id']));
						$pic_item_tpl = str_replace('{PICSRC}', thumb($pic_info, 'real'), $pic_item_tpl);
					}else{
						$pic_info = $model_fixture_pic->getFixturePicInfo(array('id'=>$value['pic_id']),array('id','sld_pic_name'));
						$pic_item_tpl = str_replace('{PICSRC}', $this->fixture_pic_url($pic_info['sld_pic_name']), $pic_item_tpl);
					}

					$pic_link = $show_edit ? 'javascript:;' : ($value['links'] ? $value['links'] : 'javascript:;') ;

					$pic_item_tpl = str_replace('{PICLINK}', $pic_link, $pic_item_tpl);
					$pic_item_tpl = str_replace('{LICLASS}', $li_class, $pic_item_tpl);

					$pic_list_html .= $pic_item_tpl;
				}

			}

			if (!$has_img_num && $show_edit) {
				for ($i=0; $i < ($total_num-$has_img_num); $i++) { 
					$pic_item_tpl = $template_list_item['PICLIST']['example'];

					$li_class = '';
					if ($i%3 == 2) {
						// 第三个
						$li_class = 'big-ad-img';
						$design_arr[$item_value['edit_btn']]['pic_w'] = $design_arr[$item_value['edit_btn']]['pic_w'] * 2 + 10;
					}

					$pic_item_tpl = str_replace('{PICWIDTH}', $design_arr[$item_value['edit_btn']]['pic_w'], $pic_item_tpl);

					$pic_item_tpl = str_replace('{PICHEIGHT}', $design_arr[$item_value['edit_btn']]['pic_h'] ? $design_arr[$item_value['edit_btn']]['pic_h'] : '高度不限', $pic_item_tpl);
					$pic_item_tpl = str_replace('{LICLASS}', $li_class, $pic_item_tpl);

					$pic_list_html .= $pic_item_tpl;

				}
			}

			$template_html = str_replace('{PICLIST'.$now_index.'}', $pic_list_html, $template_html);

		}

		// brand
		// 获取品牌列表
		$tpl_brand_data = $tpl_all_data['brand'];

		// 获取品牌列表
		$model_brand = Model();

		$total_num = $design_arr['TEMPLATE_EDIT_2']['length'];
		$has_img_num = 0;

		$selected_list_data = '';
		$selected_shop_ids = array();

		if ($tpl_brand_data) {
			$has_img_num = count($tpl_brand_data);

			// 排序
			$arr_sort = array(  
			        'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序  
			        'field'     => 'sort',       //排序字段  
			);  
			$arrSort = array();  
			foreach($tpl_brand_data AS $uniqid => $row){  
			    foreach($row AS $key=>$value){  
			        $arrSort[$key][$uniqid] = $value;  
			    }  
			}
			if($arr_sort['direction']){  
				array_multisort($arrSort[$arr_sort['field']], constant($arr_sort['direction']), $tpl_brand_data);  
			}

			foreach ($tpl_brand_data as $key => $value) {
				$brand_item_tpl = $template_list_item['BRANDLIST']['real'];

				$li_class = '';
				if ($key%6 == 5) {
					// 每行最后一个
					$li_class = 'row-last-img';
				}
				if ($key > 5) {
					$li_class .= ' bottom-img';
				}

				// 获取信息
				$brand_info = $model_brand->table('brand')->find($value['brand_id']);

				$brand_link = $show_edit ? 'javascript:;' : ($value['brand_id'] ? urlShop('brand', 'lists', array('brand'=>$value['brand_id'])) : 'javascript:;') ;

				$brand_item_tpl = str_replace('{BRANDSRC}', brandImage($brand_info['brand_pic']), $brand_item_tpl);
				$brand_item_tpl = str_replace('{BRANDLINK}', $brand_link, $brand_item_tpl);
				$brand_item_tpl = str_replace('{BRANDNAME}', $brand_info['brand_name'], $brand_item_tpl);
				$brand_item_tpl = str_replace('{LICLASS}', $li_class, $brand_item_tpl);

				$brand_list_html .= $brand_item_tpl;
			}
		}

		if (!$has_img_num && $show_edit) {
			for ($i=0; $i < ($total_num-$has_img_num); $i++) { 
				$brand_item_tpl = $template_list_item['BRANDLIST']['example'];

				$li_class = '';
				if ($i%6 == 5) {
					// 每行最后一个
					$li_class = 'row-last-img';
				}
				if ($i > 5) {
					$li_class .= ' bottom-img';
				}

				$brand_item_tpl = str_replace('{PICWIDTH}', $design_arr['TEMPLATE_EDIT_2']['pic_w'], $brand_item_tpl);

				$brand_item_tpl = str_replace('{PICHEIGHT}', $design_arr['TEMPLATE_EDIT_2']['pic_h'] ? $design_arr['TEMPLATE_EDIT_2']['pic_h'] : '高度不限', $brand_item_tpl);
				$brand_item_tpl = str_replace('{LICLASS}', $li_class, $brand_item_tpl);

				$brand_list_html .= $brand_item_tpl;
			}
		}

		$template_html = str_replace('{BRANDLIST}', $brand_list_html, $template_html);

		return $template_html;	
	}
	public function fixture_tpl_goods_promotion_s2($show_edit,$design_arr,$template_list_item,$tpl_data,$template_html)
	{
		// 获取装修模板的数据
		$tpl_all_data = unserialize($tpl_data['sld_tpl_data']);

		// goods_1,goods_2,goods_3,goods_4,goods_5
		// 获取商品列表信息
		$goods_design_list = array('goods_1','goods_2','goods_3','goods_4','goods_5');
		$tpl_goods_cat_default_name = $show_edit ? '添加商品' : '';

		$model_goods = Model('goods');

		$total_goods_num = $design_arr['TEMPLATE_EDIT_1']['number'];
		foreach ($goods_design_list as $key => $value) {
			$selected_goods_list_data = '';
			$has_goods_num = 0;
			$now_index = ($key+1);
			$tpl_goods_cat_data = $tpl_all_data['extend'][$value]['cat_name'];
			if ($tpl_goods_cat_data) {
				$tpl_goods_data = $tpl_all_data[$value];

				if ($tpl_goods_data) {
					$has_goods_num = count($tpl_goods_data);

					foreach($tpl_goods_data AS $uniqid => $row){  
					    foreach($row AS $key=>$value){  
					        $arrSort[$key][$uniqid] = $value;  
					    }  
					}

					if($arr_sort['direction']){  
						@array_multisort($arrSort[$arr_sort['field']], constant($arr_sort['direction']), $tpl_goods_data);  
					}

					foreach ($tpl_goods_data as $key => $value) {
						$goods_item_tpl = $template_list_item['GOODSLIST']['real'];
						// 获取信息
						$goods_info = $model_goods->getGoodsOnlineInfo(array('gid'=>$value['goods_id']),array('gid','goods_name','goods_image','goods_price'));

				        // 获取最终价格
				        $goods_info = Model('goods_activity')->rebuild_goods_data($goods_info,'pc');

						$goods_link = $show_edit ? 'javascript:;' : ($value['goods_id'] ? urlShop('goods','index',array('gid'=>$value['goods_id'])) : 'javascript:;') ;

						$goods_item_tpl = str_replace('{GOODSLINK}', $goods_link, $goods_item_tpl);
						$goods_item_tpl = str_replace('{GOODSNAME}', $goods_info['goods_name'], $goods_item_tpl);
						$goods_item_tpl = str_replace('{GOODSSRC}', thumb($goods_info), $goods_item_tpl);
						$goods_item_tpl = str_replace('{GOODSPRICE}', $goods_info['show_price'], $goods_item_tpl);

						$selected_goods_list_data .= $goods_item_tpl;

					}
				}

				if (!$has_goods_num && $show_edit) {
					for ($i=0; $i < ($total_goods_num-$has_goods_num); $i++) { 
						$goods_item_tpl = $show_edit ? $template_list_item['GOODSLIST']['example'] : '';

						$selected_goods_list_data .= $goods_item_tpl;
					}
				}


				$template_html = str_replace('{CATGOODSLIST'.$now_index.'}', $selected_goods_list_data, $template_html);
				$template_html = str_replace('{GOODSCATNAME'.$now_index.'}', $tpl_goods_cat_data, $template_html);
			}else{
				for ($i=0; $i < ($total_goods_num); $i++) { 

					$goods_item_tpl =$show_edit ? $template_list_item['GOODSLIST']['example'] : '';

					$selected_goods_list_data .= $goods_item_tpl;
				}

				$template_html = str_replace('{CATGOODSLIST'.$now_index.'}', $selected_goods_list_data, $template_html);
				$template_html = str_replace('{GOODSCATNAME'.$now_index.'}', $tpl_goods_cat_default_name, $template_html);
			}
		}

		// t
		// 获取标题
		$tpl_t_data = $tpl_all_data['t'];
		$shop_street_t_html = $show_edit ? '添加标题' : '';
		if ($tpl_t_data) {
			$shop_street_t_html = $tpl_t_data[0]['name'];
		}
		$template_html = str_replace('{TSTRING}', $shop_street_t_html, $template_html);

		return $template_html;
	}
	public function fixture_tpl_goods_floor($show_edit,$design_arr,$template_list_item,$tpl_data,$template_html)
	{
		// 获取装修模板的数据
		$tpl_all_data = unserialize($tpl_data['sld_tpl_data']);

		// pic_list
		// 获取 图片列表信息
		$tpl_pic_list_data = $tpl_all_data['pic_list'];

		// 获取图片列表
		$model_fixture_pic = Model('web_home');

		$total_num = $design_arr['TEMPLATE_EDIT_5']['length'];
		$has_img_num = 0;

		$selected_list_data = '';
		$pic_list_html = '';

		if ($tpl_pic_list_data) {
			$has_img_num = count($tpl_pic_list_data);

			// 排序
			$arr_sort = array(  
			        'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序  
			        'field'     => 'sort',       //排序字段  
			);  
			$arrSort = array();  
			foreach($tpl_pic_list_data AS $uniqid => $row){  
			    foreach($row AS $key=>$value){  
			        $arrSort[$key][$uniqid] = $value;  
			    }  
			}
			if($arr_sort['direction']){  
				array_multisort($arrSort[$arr_sort['field']], constant($arr_sort['direction']), $tpl_pic_list_data);  
			}

			foreach ($tpl_pic_list_data as $key => $value) {
				$pic_item_tpl = $template_list_item['PICLIST']['real'];

				// 获取信息
				if ($tpl_data['sld_shop_id']) {
					$model_album = Model('imagespace');
					$pic_info = $model_album->getOne(array('apic_id'=>$value['pic_id']));
					$pic_item_tpl = str_replace('{PICSRC}', thumb($pic_info, 'real'), $pic_item_tpl);
				}else{
					$pic_info = $model_fixture_pic->getFixturePicInfo(array('id'=>$value['pic_id']),array('id','sld_pic_name'));
					$pic_item_tpl = str_replace('{PICSRC}', $this->fixture_pic_url($pic_info['sld_pic_name']), $pic_item_tpl);
				}

				$pic_link = $show_edit ? 'javascript:;' : ($value['links'] ? $value['links'] : 'javascript:;') ;

				$pic_item_tpl = str_replace('{PICLINK}', $pic_link, $pic_item_tpl);

				$pic_list_html .= $pic_item_tpl;
			}
		}

		if (!$has_img_num && $show_edit) {
			for ($i=0; $i < ($total_num-$has_img_num); $i++) { 
				$pic_item_tpl = $template_list_item['PICLIST']['example'];

				$pic_item_tpl = str_replace('{PICWIDTH}', $design_arr['TEMPLATE_EDIT_5']['pic_w'], $pic_item_tpl);
				$pic_item_tpl = str_replace('{PICHEIGHT}', $design_arr['TEMPLATE_EDIT_5']['pic_h'] ? $design_arr['TEMPLATE_EDIT_5']['pic_h'] : '高度不限', $pic_item_tpl);

				$pic_list_html .= $pic_item_tpl;

			}
		}

		$template_html = str_replace('{PICLIST}', $pic_list_html, $template_html);

		// goods_1,goods_2,goods_3,goods_4
		// 获取商品列表信息
		$goods_design_list = array('goods_1','goods_2','goods_3','goods_4');
		$tpl_goods_cat_default_name = $show_edit ? '添加商品' : '';

		$model_goods = Model('goods');

		$total_goods_num = $design_arr['TEMPLATE_EDIT_1']['length'];

		foreach ($goods_design_list as $key => $value) {
			$selected_goods_list_data = '';
			$has_goods_num = 0;
			$now_index = ($key+1);
			$tpl_goods_cat_data = $tpl_all_data['extend'][$value]['cat_name'];
			if ($tpl_goods_cat_data) {
				$tpl_goods_data = $tpl_all_data[$value];

				if ($tpl_goods_data) {
					$has_goods_num = count($tpl_goods_data);

					foreach($tpl_goods_data AS $uniqid => $row){  
					    foreach($row AS $key=>$value){  
					        $arrSort[$key][$uniqid] = $value;  
					    }  
					}

					if($arr_sort['direction']){  
						@array_multisort($arrSort[$arr_sort['field']], constant($arr_sort['direction']), $tpl_goods_data);  
					}

					foreach ($tpl_goods_data as $key => $value) {
						$goods_item_tpl = $template_list_item['GOODSLIST']['real'];
						// 获取信息
						$goods_info = $model_goods->getGoodsOnlineInfo(array('gid'=>$value['goods_id']),array('gid','goods_name','goods_image','goods_price'));

				        // 获取最终价格
				        $goods_info = Model('goods_activity')->rebuild_goods_data($goods_info,'pc');

						$goods_link = $show_edit ? 'javascript:;' : ($value['goods_id'] ? urlShop('goods','index',array('gid'=>$value['goods_id'])) : 'javascript:;') ;

						$goods_item_tpl = str_replace('{GOODSLINK}', $goods_link, $goods_item_tpl);
						$goods_item_tpl = str_replace('{GOODSNAME}', $goods_info['goods_name'], $goods_item_tpl);
						$goods_item_tpl = str_replace('{GOODSSRC}', thumb($goods_info), $goods_item_tpl);
						$goods_item_tpl = str_replace('{GOODSPRICE}', $goods_info['show_price'], $goods_item_tpl);

						$selected_goods_list_data .= $goods_item_tpl;

					}
				}

				if (!$has_goods_num && $show_edit) {
					for ($i=0; $i < ($total_goods_num-$has_goods_num); $i++) { 
						$goods_item_tpl = $template_list_item['GOODSLIST']['example'];

						$selected_goods_list_data .= $goods_item_tpl;
					}
				}


				$template_html = str_replace('{CATGOODSLIST'.$now_index.'}', $selected_goods_list_data, $template_html);
				$template_html = str_replace('{GOODSCATNAME'.$now_index.'}', $tpl_goods_cat_data, $template_html);
			}else{
				if ($show_edit) {
					for ($i=0; $i < ($total_goods_num); $i++) { 
						$goods_item_tpl = $template_list_item['GOODSLIST']['example'];

						$selected_goods_list_data .= $goods_item_tpl;
					}
				}

				$template_html = str_replace('{CATGOODSLIST'.$now_index.'}', $selected_goods_list_data, $template_html);
				$template_html = str_replace('{GOODSCATNAME'.$now_index.'}', $tpl_goods_cat_default_name, $template_html);
			}
		}

		// t
		// 获取标题
		$tpl_t_data = $tpl_all_data['t'];
		$shop_street_t_html = $show_edit ? '添加楼层标题' : '';
		if ($tpl_t_data) {
			$shop_street_t_html = $tpl_t_data[0]['name'];
		}
		$template_html = str_replace('{TSTRING}', $shop_street_t_html, $template_html);

		// 分类
		// 获取分类列表
		$tpl_category_data = $tpl_all_data['category'];

		$model_class = Model('goods_class');

		$total_cate_num = $design_arr['TEMPLATE_EDIT_6']['number'];
		$has_cate_num = 0;

		$selected_list_data = '';

		// 排序
		$arr_sort = array(  
		        'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序  
		        'field'     => 'sort',       //排序字段  
		);
		$arrSort = array();  
		if ($tpl_category_data) {
			$has_cate_num = count($tpl_category_data);

			foreach($tpl_category_data AS $uniqid => $row){  
			    foreach($row AS $key=>$value){  
			        $arrSort[$key][$uniqid] = $value;  
			    }  
			}

			if($arr_sort['direction']){  
				@array_multisort($arrSort[$arr_sort['field']], constant($arr_sort['direction']), $tpl_category_data);  
			}

			foreach ($tpl_category_data as $key => $value) {
				$goods_cate_item_tpl = $template_list_item['GOODSCATELIST']['real'];

				// 获取信息
				$category_info = $model_class->getGoodsClassList(array('gc_id' => $value['cat_id']), 'gc_id,gc_name');

				$goods_cate_link = $show_edit ? 'javascript:;' : ($value['cat_id'] ? urlShop('goodslist','index',array('cid'=> $value['cat_id'])) : 'javascript:;') ;

				$goods_cate_item_tpl = str_replace('{GOODSCATENAME}', $category_info[0]['gc_name'], $goods_cate_item_tpl);
				$goods_cate_item_tpl = str_replace('{GOODSCATELINK}', $goods_cate_link, $goods_cate_item_tpl);

				$selected_list_data .= $goods_cate_item_tpl;
			}
		}


		if (!$has_cate_num && $show_edit) {
			for ($i=0; $i < ($total_cate_num-$has_cate_num); $i++) { 
				$goods_cate_item_tpl = $template_list_item['GOODSCATELIST']['example'];

				$selected_list_data .= $goods_cate_item_tpl;
			}
		}
                        

		$template_html = str_replace('{CATEGORYLIST}', $selected_list_data, $template_html);

		return $template_html;
	}
	public function fixture_tpl_goods_floor2($show_edit,$design_arr,$template_list_item,$tpl_data,$template_html)
	{
		// 获取装修模板的数据
		$tpl_all_data = unserialize($tpl_data['sld_tpl_data']);

		$model_goods = Model('goods');

		$total_goods_num = $design_arr['TEMPLATE_EDIT_1']['number'];

		$selected_goods_list_data = '';
		$has_goods_num = 0;

		$tpl_goods_data = $tpl_all_data['goods'];
		if ($tpl_goods_data) {

			$has_goods_num = count($tpl_goods_data);

			foreach($tpl_goods_data AS $uniqid => $row){  
			    foreach($row AS $key=>$value){  
			        $arrSort[$key][$uniqid] = $value;  
			    }  
			}

			if($arr_sort['direction']){  
				@array_multisort($arrSort[$arr_sort['field']], constant($arr_sort['direction']), $tpl_goods_data);  
			}

			foreach ($tpl_goods_data as $key => $value) {
				$goods_item_tpl = $template_list_item['GOODSLIST']['real'];
				// 获取信息
				$goods_info = $model_goods->getGoodsOnlineInfo(array('gid'=>$value['goods_id']),array('gid','goods_name','goods_image','goods_price'));

		        // 获取最终价格
		        $goods_info = Model('goods_activity')->rebuild_goods_data($goods_info,'pc');

				$goods_link = $show_edit ? 'javascript:;' : ($value['goods_id'] ? urlShop('goods','index',array('gid'=>$value['goods_id'])) : 'javascript:;') ;

				$liClass = '';
				if ($key%5==4) {
					// 每行最后一个
					$liClass = 'row-last-item';
				}

				$goods_item_tpl = str_replace('{GOODSLINK}', $goods_link, $goods_item_tpl);
				$goods_item_tpl = str_replace('{GOODSNAME}', $goods_info['goods_name'], $goods_item_tpl);
				$goods_item_tpl = str_replace('{GOODSSRC}', thumb($goods_info), $goods_item_tpl);
				$goods_item_tpl = str_replace('{GOODSPRICE}', $goods_info['show_price'], $goods_item_tpl);
				$goods_item_tpl = str_replace('{LICLASS}', $liClass, $goods_item_tpl);

				$selected_goods_list_data .= $goods_item_tpl;

			}

		}

		if (!$has_goods_num && $show_edit) {
			for ($i=0; $i < ($total_goods_num); $i++) { 
				$goods_item_tpl = $template_list_item['GOODSLIST']['example'];

				$liClass = '';
				if ($i%5==4) {
					// 每行最后一个
					$liClass = 'row-last-item';
				}

				$goods_item_tpl = str_replace('{PICWIDTH}', $design_arr['TEMPLATE_EDIT_1']['pic_w'], $goods_item_tpl);
				$goods_item_tpl = str_replace('{PICHEIGHT}', $design_arr['TEMPLATE_EDIT_1']['pic_h'], $goods_item_tpl);

				$goods_item_tpl = str_replace('{LICLASS}', $liClass, $goods_item_tpl);

				$selected_goods_list_data .= $goods_item_tpl;
			}
		}

		$template_html = str_replace('{GOODSLIST}', $selected_goods_list_data, $template_html);

		// t
		// 获取标题
		$tpl_t_data = $tpl_all_data['t'];
		$shop_street_t_html = $show_edit ? '添加标题' : '';
		if ($tpl_t_data) {
			$shop_street_t_html = $tpl_t_data[0]['name'];
		}
		$template_html = str_replace('{TSTRING}', $shop_street_t_html, $template_html);

		return $template_html;
	}
	public function fixture_tpl_goods_floor3($show_edit,$design_arr,$template_list_item,$tpl_data,$template_html)
	{
		// 获取装修模板的数据
		$tpl_all_data = unserialize($tpl_data['sld_tpl_data']);

		// t
		// 获取标题
		$tpl_t_data = $tpl_all_data['t'];
		$floor_t_html = $show_edit ? '添加标题' : '';
		$floor_sub_title_html = $show_edit ? '添加子标题' : '';
		$floor_title_link_html = 'javascript:;';
		if ($tpl_t_data) {
			$floor_t_html = $tpl_t_data[0]['name'];
			$floor_sub_title_html = $tpl_t_data[0]['sub_title'];
			$floor_title_link_html = $show_edit ? $floor_title_link_html : $tpl_t_data[0]['title_link'];

		}
		$template_html = str_replace('{TSTRING}', $floor_t_html, $template_html);
		$template_html = str_replace('{SUBTITLE}', $floor_sub_title_html, $template_html);
		$template_html = str_replace('{TITLELINK}', $floor_title_link_html, $template_html);

		// 左侧图片
		// pic_list
		// 获取 图片列表信息
		$tpl_pic_list_data = $tpl_all_data['pic_list'];

		// 获取图片列表
		$model_fixture_pic = Model('web_home');

		$total_num = $design_arr['TEMPLATE_EDIT_1']['length'];
		$has_img_num = 0;

		$selected_list_data = '';
		$pic_list_html = '';

		if ($tpl_pic_list_data) {
			$has_img_num = count($tpl_pic_list_data);

			// 排序
			$arr_sort = array(  
			        'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序  
			        'field'     => 'sort',       //排序字段  
			);  
			$arrSort = array();  
			foreach($tpl_pic_list_data AS $uniqid => $row){  
			    foreach($row AS $key=>$value){  
			        $arrSort[$key][$uniqid] = $value;  
			    }  
			}
			if($arr_sort['direction']){  
				array_multisort($arrSort[$arr_sort['field']], constant($arr_sort['direction']), $tpl_pic_list_data);  
			}

			foreach ($tpl_pic_list_data as $key => $value) {
				$pic_item_tpl = $template_list_item['PICLIST']['real'];
				
				// 获取信息
				if ($tpl_data['sld_shop_id']) {
					$model_album = Model('imagespace');
					$pic_info = $model_album->getOne(array('apic_id'=>$value['pic_id']));
					$pic_item_tpl = str_replace('{PICSRC}', thumb($pic_info, 'real'), $pic_item_tpl);
				}else{
					$pic_info = $model_fixture_pic->getFixturePicInfo(array('id'=>$value['pic_id']),array('id','sld_pic_name'));
					$pic_item_tpl = str_replace('{PICSRC}', $this->fixture_pic_url($pic_info['sld_pic_name']), $pic_item_tpl);
				}

				$pic_link = $show_edit ? 'javascript:;' : ($value['links'] ? $value['links'] : 'javascript:;') ;

				$pic_item_tpl = str_replace('{PICLINK}', $pic_link, $pic_item_tpl);

				$pic_list_html .= $pic_item_tpl;
			}
		}

		if (!$has_img_num && $show_edit) {
			for ($i=0; $i < ($total_num-$has_img_num); $i++) { 
				$pic_item_tpl = $template_list_item['PICLIST']['example'];

				$pic_item_tpl = str_replace('{PICWIDTH}', $design_arr['TEMPLATE_EDIT_1']['pic_w'], $pic_item_tpl);
				$pic_item_tpl = str_replace('{PICHEIGHT}', $design_arr['TEMPLATE_EDIT_1']['pic_h'] ? $design_arr['TEMPLATE_EDIT_1']['pic_h'] : '高度不限', $pic_item_tpl);

				$pic_list_html .= $pic_item_tpl;

			}
		}

		$template_html = str_replace('{PICLIST}', $pic_list_html, $template_html);

		// 分类
		// 获取分类列表
		$tpl_category_data = $tpl_all_data['category'];

		$model_class = Model('goods_class');

		$total_cate_num = $design_arr['TEMPLATE_EDIT_2']['number'];
		$has_cate_num = 0;


		$tpl_cate_title_data = $tpl_all_data['extend']['category']['cat_name'];

		$tpl_cattitle_default_name = $show_edit ? '添加标题' : '';

		$selected_list_data = '';

		// 排序
		$arr_sort = array(  
		        'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序  
		        'field'     => 'sort',       //排序字段  
		);
		$arrSort = array();  
		if ($tpl_category_data) {
			$has_cate_num = count($tpl_category_data);

			foreach($tpl_category_data AS $uniqid => $row){  
			    foreach($row AS $key=>$value){  
			        $arrSort[$key][$uniqid] = $value;  
			    }  
			}

			if($arr_sort['direction']){  
				@array_multisort($arrSort[$arr_sort['field']], constant($arr_sort['direction']), $tpl_category_data);  
			}


			foreach ($tpl_category_data as $key => $value) {
				$goods_cate_item_tpl = $template_list_item['CATEGORYLIST']['real'];

				// 获取信息
				$category_info = $model_class->getGoodsClassList(array('gc_id' => $value['cat_id']), 'gc_id,gc_name');

				$goods_cate_link = $show_edit ? 'javascript:;' : ($value['cat_id'] ? urlShop('goodslist','index',array('cid'=> $value['cat_id'])) : 'javascript:;') ;

				$goods_cate_item_tpl = str_replace('{GOODSCATENAME}', $category_info[0]['gc_name'], $goods_cate_item_tpl);
				$goods_cate_item_tpl = str_replace('{GOODSCATELINK}', $goods_cate_link, $goods_cate_item_tpl);

				$selected_list_data .= $goods_cate_item_tpl;
			}
		}


		if (!$has_cate_num && $show_edit) {
			for ($i=0; $i < ($total_cate_num-$has_cate_num); $i++) { 
				$goods_cate_item_tpl = $template_list_item['CATEGORYLIST']['example'];

				$selected_list_data .= $goods_cate_item_tpl;
			}
		}        

		$template_html = str_replace('{CATEGORYTITLE}', $tpl_cate_title_data ? $tpl_cate_title_data : $tpl_cattitle_default_name, $template_html);
		$template_html = str_replace('{CATEGORYLIST}', $selected_list_data, $template_html);

		// goods_1,goods_2
		// 获取图片列表
		$goods_design_list = array(
			array('data_name'=>'goods_1','edit_btn'=>'TEMPLATE_EDIT_3','list_index'=>1),
			array('data_name'=>'goods_2','edit_btn'=>'TEMPLATE_EDIT_4','list_index'=>2)
		);
		$tpl_goods_cat_default_name = $show_edit ? '添加标题' : '';

		$model_goods = Model('goods');

		foreach ($goods_design_list as $item_key => $item_value) {
			$selected_goods_list_data = '';
			$has_goods_num = 0;
			$now_index =  $item_value['list_index'] ? $item_value['list_index'] : '';
			$tpl_goods_cat_data = $tpl_all_data['extend'][$item_value['data_name']]['cat_name'];

			$total_goods_num = $design_arr[$item_value['edit_btn']]['number'];

			$tpl_goods_data = $tpl_all_data[$item_value['data_name']];

			$tpl_goods_cat_data = $tpl_goods_cat_data ? $tpl_goods_cat_data : $tpl_goods_cat_default_name;

			if ($tpl_goods_data) {
				$has_goods_num = count($tpl_goods_data);

				foreach($tpl_goods_data AS $uniqid => $row){  
				    foreach($row AS $key=>$value){  
				        $arrSort[$key][$uniqid] = $value;  
				    }  
				}

				if($arr_sort['direction']){  
					@array_multisort($arrSort[$arr_sort['field']], constant($arr_sort['direction']), $tpl_goods_data);  
				}

				foreach ($tpl_goods_data as $key => $value) {

					$liClass = '';
					if ($item_value['data_name'] == 'goods_1') {
						if ($key == 0) {
							$goods_item_tpl = $template_list_item['BIGGOODSLIST']['real'];
						}else{
							$goods_item_tpl = $template_list_item['GOODSLIST']['real'];
							if ($key>1) {
								$liClass = 'bottom-item';
							}
						}
					}else{
						$goods_item_tpl = $template_list_item['MINGOODSLIST']['real'];
					}

					// 获取信息
					$goods_info = $model_goods->getGoodsOnlineInfo(array('gid'=>$value['goods_id']),array('gid','goods_name','goods_image','goods_price'));

			        // 获取最终价格
			        $goods_info = Model('goods_activity')->rebuild_goods_data($goods_info,'pc');

					$goods_link = $show_edit ? 'javascript:;' : ($value['goods_id'] ? urlShop('goods','index',array('gid'=>$value['goods_id'])) : 'javascript:;') ;

					$goods_item_tpl = str_replace('{GOODSLINK}', $goods_link, $goods_item_tpl);
					$goods_item_tpl = str_replace('{GOODSNAME}', $goods_info['goods_name'], $goods_item_tpl);
					$goods_item_tpl = str_replace('{GOODSSRC}', thumb($goods_info), $goods_item_tpl);
					$goods_item_tpl = str_replace('{GOODSPRICE}', $goods_info['show_price'], $goods_item_tpl);
					$goods_item_tpl = str_replace('{LICLASS}', $liClass, $goods_item_tpl);

					$selected_goods_list_data .= $goods_item_tpl;

				}
			}

			if (!$has_goods_num && $show_edit) {
				for ($i=0; $i < ($total_goods_num-$has_goods_num); $i++) { 
					$liClass = '';
					if ($item_value['data_name'] == 'goods_1') {
						if ($i == 0) {
							$goods_item_tpl = $template_list_item['BIGGOODSLIST']['example'];
						}else{
							$goods_item_tpl = $template_list_item['GOODSLIST']['example'];
							if ($i>1) {
								$liClass = 'bottom-item';
							}
						}
					}else{
						$goods_item_tpl = $template_list_item['MINGOODSLIST']['example'];
					}

					$goods_item_tpl = str_replace('{PICWIDTH}', $design_arr[$item_value['edit_btn']]['pic_w'], $goods_item_tpl);
					$goods_item_tpl = str_replace('{PICHEIGHT}', $design_arr[$item_value['edit_btn']]['pic_h'] ? $design_arr[$item_value['edit_btn']]['pic_h'] : '高度不限', $goods_item_tpl);

					$goods_item_tpl = str_replace('{LICLASS}', $liClass, $goods_item_tpl);

					$selected_goods_list_data .= $goods_item_tpl;
				}
			}

			$template_html = str_replace('{GOODSLIST'.$now_index.'}', $selected_goods_list_data, $template_html);
			$template_html = str_replace('{GOODSCATNAME'.$now_index.'}', $tpl_goods_cat_data, $template_html);
		}

		return $template_html;
	}
	public function fixture_tpl_picfont_group1($show_edit,$design_arr,$template_list_item,$tpl_data,$template_html)
	{
		// 获取装修模板的数据
		$tpl_all_data = unserialize($tpl_data['sld_tpl_data']);

		// 左侧图片
		// pic_list
		// 获取 图片列表信息
		$tpl_pic_list_data = $tpl_all_data['pic_list_1'];

		// 获取图片列表
		$model_fixture_pic = Model('web_home');

		$total_num = $design_arr['TEMPLATE_EDIT']['length'];
		$has_img_num = 0;

		$selected_list_data = '';
		$pic_list_html = '';

		if ($tpl_pic_list_data) {
			$has_img_num = count($tpl_pic_list_data);

			// 排序
			$arr_sort = array(  
			        'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序  
			        'field'     => 'sort',       //排序字段  
			);  
			$arrSort = array();  
			foreach($tpl_pic_list_data AS $uniqid => $row){  
			    foreach($row AS $key=>$value){  
			        $arrSort[$key][$uniqid] = $value;  
			    }  
			}
			if($arr_sort['direction']){  
				array_multisort($arrSort[$arr_sort['field']], constant($arr_sort['direction']), $tpl_pic_list_data);  
			}

			foreach ($tpl_pic_list_data as $key => $value) {
				$pic_item_tpl = $template_list_item['LEFTPIC']['real'];

				// 获取信息
				if ($tpl_data['sld_shop_id']) {
					$model_album = Model('imagespace');
					$pic_info = $model_album->getOne(array('apic_id'=>$value['pic_id']));
					$pic_item_tpl = str_replace('{PICSRC}', thumb($pic_info, 'real'), $pic_item_tpl);
				}else{
					$pic_info = $model_fixture_pic->getFixturePicInfo(array('id'=>$value['pic_id']),array('id','sld_pic_name'));
					$pic_item_tpl = str_replace('{PICSRC}', $this->fixture_pic_url($pic_info['sld_pic_name']), $pic_item_tpl);
				}

				$pic_link = $show_edit ? 'javascript:;' : ($value['links'] ? $value['links'] : 'javascript:;') ;

				$pic_item_tpl = str_replace('{PICLINK}', $pic_link, $pic_item_tpl);

				$pic_list_html .= $pic_item_tpl;
			}

		}

		if (!$has_img_num && $show_edit) {
			for ($i=0; $i < ($total_num-$has_img_num); $i++) { 
				$pic_item_tpl = $template_list_item['LEFTPIC']['example'];

				$pic_item_tpl = str_replace('{PICWIDTH}', $design_arr['TEMPLATE_EDIT']['pic_w'], $pic_item_tpl);
				$pic_item_tpl = str_replace('{PICHEIGHT}', $design_arr['TEMPLATE_EDIT']['pic_h'] ? $design_arr['TEMPLATE_EDIT']['pic_h'] : '高度不限', $pic_item_tpl);

				$pic_list_html .= $pic_item_tpl;

			}
		}

		$template_html = str_replace('{PICLIST}', $pic_list_html, $template_html);

		// pic_list_2,pic_list_3,pic_list_4,pic_list_5,pic_list_6,pic_list_7,pic_list_8,pic_list_9
		// 获取图片列表
		$goods_design_list = array(
			array('data_name'=>'pic_list_2','edit_btn'=>'TEMPLATE_EDIT_1'),
			array('data_name'=>'pic_list_3','edit_btn'=>'TEMPLATE_EDIT_2'),
			array('data_name'=>'pic_list_4','edit_btn'=>'TEMPLATE_EDIT_3'),
			array('data_name'=>'pic_list_5','edit_btn'=>'TEMPLATE_EDIT_4'),
			array('data_name'=>'pic_list_6','edit_btn'=>'TEMPLATE_EDIT_5'),
			array('data_name'=>'pic_list_7','edit_btn'=>'TEMPLATE_EDIT_6'),
			array('data_name'=>'pic_list_8','edit_btn'=>'TEMPLATE_EDIT_7'),
			array('data_name'=>'pic_list_9','edit_btn'=>'TEMPLATE_EDIT_8')
		);
		$tpl_goods_cat_default_name = $show_edit ? '添加内容' : '';

		foreach ($goods_design_list as $key => $item_value) {
			$selected_goods_list_data = '';
			$has_goods_num = 0;
			$now_index = ($key+1);
			$tpl_goods_cat_data = $tpl_all_data['extend'][$item_value['data_name']]['cat_name'];

			$total_goods_num = $design_arr[$item_value['edit_btn']]['length'];


			if ($tpl_goods_cat_data) {
				$tpl_goods_data = $tpl_all_data[$item_value['data_name']];

				if ($tpl_goods_data) {
					$has_goods_num = count($tpl_goods_data);

					foreach($tpl_goods_data AS $uniqid => $row){  
					    foreach($row AS $key=>$value){  
					        $arrSort[$key][$uniqid] = $value;  
					    }  
					}

					if($arr_sort['direction']){  
						@array_multisort($arrSort[$arr_sort['field']], constant($arr_sort['direction']), $tpl_goods_data);  
					}

					foreach ($tpl_goods_data as $key => $value) {
						$pic_item_tpl = $template_list_item['RIGHTPIC']['real'];

						$liClass = '';
						if ($key>4) {
							$liClass = 'bottom-item';
						}

						// 获取信息
						if ($tpl_data['sld_shop_id']) {
							$model_album = Model('imagespace');
							$pic_info = $model_album->getOne(array('apic_id'=>$value['pic_id']));
							$pic_item_tpl = str_replace('{PICSRC}', thumb($pic_info, 'real'), $pic_item_tpl);
						}else{
							$pic_info = $model_fixture_pic->getFixturePicInfo(array('id'=>$value['pic_id']),array('id','sld_pic_name'));
							$pic_item_tpl = str_replace('{PICSRC}', $this->fixture_pic_url($pic_info['sld_pic_name']), $pic_item_tpl);
						}

						$pic_link = $show_edit ? 'javascript:;' : ($value['links'] ? $value['links'] : 'javascript:;') ;

						$pic_title = $value['pic_title'] ? $value['pic_title'] : '图片标题';
						$pic_sub_title = $value['pic_sub_title'] ? $value['pic_sub_title'] : '图片子标题';

						$pic_item_tpl = str_replace('{PICLINK}', $pic_link, $pic_item_tpl);

						$pic_item_tpl = str_replace('{PICTITLE}', $pic_title, $pic_item_tpl);
						$pic_item_tpl = str_replace('{PICSUBTITLE}', $pic_sub_title, $pic_item_tpl);

						$pic_item_tpl = str_replace('{LICLASS}', $liClass, $pic_item_tpl);

						$selected_goods_list_data .= $pic_item_tpl;

					}
				}

				if (!$has_goods_num && $show_edit) {
					for ($i=0; $i < ($total_goods_num-$has_goods_num); $i++) { 
						$pic_item_tpl = $template_list_item['RIGHTPIC']['example'];

						$liClass = '';
						if ($i>4) {
							$liClass = 'bottom-item';
						}

						$pic_item_tpl = str_replace('{PICWIDTH}', $design_arr['TEMPLATE_EDIT_1']['pic_w'], $pic_item_tpl);
						$pic_item_tpl = str_replace('{PICHEIGHT}', $design_arr['TEMPLATE_EDIT_1']['pic_h'] ? $design_arr['TEMPLATE_EDIT_1']['pic_h'] : '高度不限', $pic_item_tpl);

						$pic_item_tpl = str_replace('{LICLASS}', $liClass, $pic_item_tpl);

						$selected_goods_list_data .= $pic_item_tpl;
					}
				}


				$template_html = str_replace('{PICLIST'.$now_index.'}', $selected_goods_list_data, $template_html);
				$template_html = str_replace('{GOODSCATNAME'.$now_index.'}', $tpl_goods_cat_data, $template_html);
			}else{
				if ($show_edit) {
					for ($i=0; $i < ($total_goods_num); $i++) { 
						$pic_item_tpl = $template_list_item['RIGHTPIC']['example'];
						
						$liClass = '';
						if ($i>4) {
							$liClass = 'bottom-item';
						}

						$pic_item_tpl = str_replace('{PICWIDTH}', $design_arr['TEMPLATE_EDIT_1']['pic_w'], $pic_item_tpl);
						$pic_item_tpl = str_replace('{PICHEIGHT}', $design_arr['TEMPLATE_EDIT_1']['pic_h'] ? $design_arr['TEMPLATE_EDIT_1']['pic_h'] : '高度不限', $pic_item_tpl);

						$pic_item_tpl = str_replace('{LICLASS}', $liClass, $pic_item_tpl);

						$selected_goods_list_data .= $pic_item_tpl;
					}
				}

				$template_html = str_replace('{PICLIST'.$now_index.'}', $selected_goods_list_data, $template_html);
				$template_html = str_replace('{GOODSCATNAME'.$now_index.'}', $tpl_goods_cat_default_name, $template_html);
			}
		}

		return $template_html;
	}
	public function fixture_tpl_picfont_group2($show_edit,$design_arr,$template_list_item,$tpl_data,$template_html)
	{
		// 获取装修模板的数据
		$tpl_all_data = unserialize($tpl_data['sld_tpl_data']);

		// 获取图片列表
		$model_fixture_pic = Model('web_home');

		// 左侧图片
		// pic_list_1,pic_list_8
		// 获取图片列表
		$pic_left_design_list = array(
			array('data_name'=>'pic_list_1','edit_btn'=>'TEMPLATE_EDIT','list_index'=>0),
			array('data_name'=>'pic_list_8','edit_btn'=>'TEMPLATE_EDIT_7','list_index'=>7)
		);
		foreach ($pic_left_design_list as $key => $item_value) {
			$now_index = $item_value['list_index'] ? $item_value['list_index'] : '';

			$total_num = $design_arr[$item_value['edit_btn']]['length'];
			$has_img_num = 0;

			// 获取 图片列表信息
			$tpl_pic_list_data = $tpl_all_data[$item_value['data_name']];

			$selected_list_data = '';
			$pic_list_html = '';

			if ($tpl_pic_list_data) {
				$has_img_num = count($tpl_pic_list_data);

				// 排序
				$arr_sort = array(  
				        'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序  
				        'field'     => 'sort',       //排序字段  
				);  
				$arrSort = array();  
				foreach($tpl_pic_list_data AS $uniqid => $row){  
				    foreach($row AS $key=>$value){  
				        $arrSort[$key][$uniqid] = $value;  
				    }  
				}
				if($arr_sort['direction']){  
					array_multisort($arrSort[$arr_sort['field']], constant($arr_sort['direction']), $tpl_pic_list_data);  
				}

				foreach ($tpl_pic_list_data as $key => $value) {
					$pic_item_tpl = $template_list_item['LEFTPIC']['real'];

					// 获取信息
					if ($tpl_data['sld_shop_id']) {
						$model_album = Model('imagespace');
						$pic_info = $model_album->getOne(array('apic_id'=>$value['pic_id']));
						$pic_item_tpl = str_replace('{PICSRC}', thumb($pic_info, 'real'), $pic_item_tpl);
					}else{
						$pic_info = $model_fixture_pic->getFixturePicInfo(array('id'=>$value['pic_id']),array('id','sld_pic_name'));
						$pic_item_tpl = str_replace('{PICSRC}', $this->fixture_pic_url($pic_info['sld_pic_name']), $pic_item_tpl);
					}

					$pic_link = $show_edit ? 'javascript:;' : ($value['links'] ? $value['links'] : 'javascript:;') ;

					$pic_item_tpl = str_replace('{PICLINK}', $pic_link, $pic_item_tpl);

					$pic_list_html .= $pic_item_tpl;
				}
			}

			if (!$has_img_num && $show_edit) {
				for ($i=0; $i < ($total_num-$has_img_num); $i++) { 
					$pic_item_tpl = $template_list_item['LEFTPIC']['example'];

					$pic_item_tpl = str_replace('{PICWIDTH}', $design_arr['TEMPLATE_EDIT']['pic_w'], $pic_item_tpl);
					$pic_item_tpl = str_replace('{PICHEIGHT}', $design_arr['TEMPLATE_EDIT']['pic_h'] ? $design_arr['TEMPLATE_EDIT']['pic_h'] : '高度不限', $pic_item_tpl);

					$pic_list_html .= $pic_item_tpl;

				}
			}

			$template_html = str_replace('{PICLIST'.$now_index.'}', $pic_list_html, $template_html);

		}

		// 右侧tab 图片
		// pic_list_2,pic_list_3,pic_list_4,pic_list_5,pic_list_9,pic_list_10,pic_list_11,pic_list_12
		// 获取图片列表
		$goods_design_list = array(
			array('data_name'=>'pic_list_2','edit_btn'=>'TEMPLATE_EDIT_1','list_index'=>1),
			array('data_name'=>'pic_list_3','edit_btn'=>'TEMPLATE_EDIT_2','list_index'=>2),
			array('data_name'=>'pic_list_4','edit_btn'=>'TEMPLATE_EDIT_3','list_index'=>3),
			array('data_name'=>'pic_list_5','edit_btn'=>'TEMPLATE_EDIT_4','list_index'=>4),
			array('data_name'=>'pic_list_9','edit_btn'=>'TEMPLATE_EDIT_8','list_index'=>8),
			array('data_name'=>'pic_list_10','edit_btn'=>'TEMPLATE_EDIT_9','list_index'=>9),
			array('data_name'=>'pic_list_11','edit_btn'=>'TEMPLATE_EDIT_10','list_index'=>10),
			array('data_name'=>'pic_list_12','edit_btn'=>'TEMPLATE_EDIT_11','list_index'=>11)
		);
		$tpl_goods_cat_default_name = $show_edit ? '添加内容' : '';

		foreach ($goods_design_list as $key => $item_value) {
			$selected_goods_list_data = '';
			$has_goods_num = 0;
			$now_index = $item_value['list_index'] ? $item_value['list_index'] : '';
			$tpl_goods_cat_data = $tpl_all_data['extend'][$item_value['data_name']]['cat_name'];

			$total_goods_num = $design_arr[$item_value['edit_btn']]['length'];


			if ($tpl_goods_cat_data) {
				$tpl_goods_data = $tpl_all_data[$item_value['data_name']];

				if ($tpl_goods_data) {
					$has_goods_num = count($tpl_goods_data);

					foreach($tpl_goods_data AS $uniqid => $row){  
					    foreach($row AS $key=>$value){  
					        $arrSort[$key][$uniqid] = $value;  
					    }  
					}

					if($arr_sort['direction']){  
						@array_multisort($arrSort[$arr_sort['field']], constant($arr_sort['direction']), $tpl_goods_data);  
					}

					foreach ($tpl_goods_data as $key => $value) {
						$pic_item_tpl = $template_list_item['RIGHTPIC']['real'];

						$liClass = '';
						if ($key>4) {
							$liClass = 'bottom-item';
						}

						// 获取信息
						if ($tpl_data['sld_shop_id']) {
							$model_album = Model('imagespace');
							$pic_info = $model_album->getOne(array('apic_id'=>$value['pic_id']));
							$pic_item_tpl = str_replace('{PICSRC}', thumb($pic_info, 'real'), $pic_item_tpl);
						}else{
							$pic_info = $model_fixture_pic->getFixturePicInfo(array('id'=>$value['pic_id']),array('id','sld_pic_name'));
							$pic_item_tpl = str_replace('{PICSRC}', $this->fixture_pic_url($pic_info['sld_pic_name']), $pic_item_tpl);
						}

						$pic_link = $show_edit ? 'javascript:;' : ($value['links'] ? $value['links'] : 'javascript:;') ;

						$pic_title = $value['pic_title'] ? $value['pic_title'] : '图片标题';
						$pic_sub_title = $value['pic_sub_title'] ? $value['pic_sub_title'] : '图片子标题';

						$pic_item_tpl = str_replace('{PICLINK}', $pic_link, $pic_item_tpl);

						$pic_item_tpl = str_replace('{PICTITLE}', $pic_title, $pic_item_tpl);
						$pic_item_tpl = str_replace('{PICSUBTITLE}', $pic_sub_title, $pic_item_tpl);

						$pic_item_tpl = str_replace('{LICLASS}', $liClass, $pic_item_tpl);

						$selected_goods_list_data .= $pic_item_tpl;

					}
				}

				if (!$has_goods_num && $show_edit) {
					for ($i=0; $i < ($total_goods_num-$has_goods_num); $i++) { 
						$pic_item_tpl = $template_list_item['RIGHTPIC']['example'];

						$liClass = '';
						if ($i>2) {
							$liClass = 'bottom-item';
						}

						$pic_item_tpl = str_replace('{PICWIDTH}', $design_arr['TEMPLATE_EDIT_1']['pic_w'], $pic_item_tpl);
						$pic_item_tpl = str_replace('{PICHEIGHT}', $design_arr['TEMPLATE_EDIT_1']['pic_h'] ? $design_arr['TEMPLATE_EDIT_1']['pic_h'] : '高度不限', $pic_item_tpl);

						$pic_item_tpl = str_replace('{LICLASS}', $liClass, $pic_item_tpl);

						$selected_goods_list_data .= $pic_item_tpl;
					}
				}


				$template_html = str_replace('{PICLIST'.$now_index.'}', $selected_goods_list_data, $template_html);
				$template_html = str_replace('{GOODSCATNAME'.$now_index.'}', $tpl_goods_cat_data, $template_html);
			}else{
				if ($show_edit) {
					for ($i=0; $i < ($total_goods_num); $i++) { 
						$pic_item_tpl = $template_list_item['RIGHTPIC']['example'];
						
						$liClass = '';
						if ($i>2) {
							$liClass = 'bottom-item';
						}

						$pic_item_tpl = str_replace('{PICWIDTH}', $design_arr['TEMPLATE_EDIT_1']['pic_w'], $pic_item_tpl);
						$pic_item_tpl = str_replace('{PICHEIGHT}', $design_arr['TEMPLATE_EDIT_1']['pic_h'] ? $design_arr['TEMPLATE_EDIT_1']['pic_h'] : '高度不限', $pic_item_tpl);

						$pic_item_tpl = str_replace('{LICLASS}', $liClass, $pic_item_tpl);

						$selected_goods_list_data .= $pic_item_tpl;
					}
				}

				$template_html = str_replace('{PICLIST'.$now_index.'}', $selected_goods_list_data, $template_html);
				$template_html = str_replace('{GOODSCATNAME'.$now_index.'}', $tpl_goods_cat_default_name, $template_html);
			}
		}

		// 底部图片
		// pic_list_6,pic_list_7,pic_list_13,pic_list_14
		// 获取图片列表
		$pic_left_design_list = array(
			array('data_name'=>'pic_list_6','edit_btn'=>'TEMPLATE_EDIT_5','list_index'=>5),
			array('data_name'=>'pic_list_7','edit_btn'=>'TEMPLATE_EDIT_6','list_index'=>6),
			array('data_name'=>'pic_list_13','edit_btn'=>'TEMPLATE_EDIT_12','list_index'=>12),
			array('data_name'=>'pic_list_14','edit_btn'=>'TEMPLATE_EDIT_13','list_index'=>13)
		);
		foreach ($pic_left_design_list as $key => $item_value) {
			$now_index = $item_value['list_index'] ? $item_value['list_index'] : '';

			$total_num = $design_arr[$item_value['edit_btn']]['length'];
			$has_img_num = 0;

			// 获取 图片列表信息
			$tpl_pic_list_data = $tpl_all_data[$item_value['data_name']];

			$selected_list_data = '';
			$pic_list_html = '';

			if ($tpl_pic_list_data) {
				$has_img_num = count($tpl_pic_list_data);

				// 排序
				$arr_sort = array(  
				        'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序  
				        'field'     => 'sort',       //排序字段  
				);  
				$arrSort = array();  
				foreach($tpl_pic_list_data AS $uniqid => $row){  
				    foreach($row AS $key=>$value){  
				        $arrSort[$key][$uniqid] = $value;  
				    }  
				}
				if($arr_sort['direction']){  
					array_multisort($arrSort[$arr_sort['field']], constant($arr_sort['direction']), $tpl_pic_list_data);  
				}

				foreach ($tpl_pic_list_data as $key => $value) {
					$pic_item_tpl = $template_list_item['BOTTOMPIC']['real'];

					// 获取信息
					if ($tpl_data['sld_shop_id']) {
						$model_album = Model('imagespace');
						$pic_info = $model_album->getOne(array('apic_id'=>$value['pic_id']));
						$pic_item_tpl = str_replace('{PICSRC}', thumb($pic_info, 'real'), $pic_item_tpl);
					}else{
						$pic_info = $model_fixture_pic->getFixturePicInfo(array('id'=>$value['pic_id']),array('id','sld_pic_name'));
						$pic_item_tpl = str_replace('{PICSRC}', $this->fixture_pic_url($pic_info['sld_pic_name']), $pic_item_tpl);
					}

					$pic_link = $show_edit ? 'javascript:;' : ($value['links'] ? $value['links'] : 'javascript:;') ;
					
					$pic_title = $value['pic_title'] ? $value['pic_title'] : '图片标题';
					$pic_sub_title = $value['pic_sub_title'] ? $value['pic_sub_title'] : '图片子标题';

					$pic_item_tpl = str_replace('{PICLINK}', $pic_link, $pic_item_tpl);

					$pic_item_tpl = str_replace('{PICTITLE}', $pic_title, $pic_item_tpl);
					$pic_item_tpl = str_replace('{PICSUBTITLE}', $pic_sub_title, $pic_item_tpl);

					$pic_list_html .= $pic_item_tpl;
				}
			}

			if (!$has_img_num && $show_edit) {
				for ($i=0; $i < ($total_num-$has_img_num); $i++) { 
					$pic_item_tpl = $template_list_item['BOTTOMPIC']['example'];

					$pic_item_tpl = str_replace('{PICWIDTH}', $design_arr[$item_value['edit_btn']]['pic_w'], $pic_item_tpl);
					$pic_item_tpl = str_replace('{PICHEIGHT}', $design_arr[$item_value['edit_btn']]['pic_h'] ? $design_arr[$item_value['edit_btn']]['pic_h'] : '高度不限', $pic_item_tpl);

					$pic_list_html .= $pic_item_tpl;

				}
			}

			$template_html = str_replace('{PICLIST'.$now_index.'}', $pic_list_html, $template_html);

		}

		return $template_html;
	}

	public function fixture_tpl_picfont_group3($show_edit,$design_arr,$template_list_item,$tpl_data,$template_html)
	{
		// 获取装修模板的数据
		$tpl_all_data = unserialize($tpl_data['sld_tpl_data']);

		// t_1,t_2
		// 获取标题列表
		$t_design_list = array(
			array('data_name'=>'t_1','edit_btn'=>'TEMPLATE_EDIT','list_index'=>1),
			array('data_name'=>'t_2','edit_btn'=>'TEMPLATE_EDIT_7','list_index'=>2)
		);

		foreach ($t_design_list as $item_key => $item_value) {

			$t_title_html = $show_edit ? '添加标题' : '';
			$t_bg_color_html = '';
			$t_font_color_html = '';
		
			$now_index = $item_value['list_index'] ? $item_value['list_index'] : '';
			$tpl_t_data = $tpl_all_data[$item_value['data_name']];
			if ($tpl_t_data) {
				$t_title_html = $tpl_t_data[0]['name'];
				$t_bg_color_html = $tpl_t_data[0]['bg_color'] ? '#'.$tpl_t_data[0]['bg_color'] : '';
				$t_font_color_html = $tpl_t_data[0]['font_color'] ? '#'.$tpl_t_data[0]['font_color'] : '';
			}

			$template_html = str_replace('{TSTRING'.$now_index.'}', $t_title_html, $template_html);
			$template_html = str_replace('{TITLEBGCOLOR'.$now_index.'}', $t_bg_color_html, $template_html);
			$template_html = str_replace('{TITLEFONTCOLOR'.$now_index.'}', $t_font_color_html, $template_html);
		}

		// 获取图片列表
		$model_fixture_pic = Model('web_home');

		// 左侧图片 及 底部图片
		// pic_list_4,pic_list_10,pic_list_5,pic_list_6,pic_list_11,pic_list_12
		// 获取图片列表
		$pic_left_design_list = array(
			array('data_name'=>'pic_list_4','edit_btn'=>'TEMPLATE_EDIT_4','list_index'=>4),
			array('data_name'=>'pic_list_10','edit_btn'=>'TEMPLATE_EDIT_11','list_index'=>10),
			array('data_name'=>'pic_list_5','edit_btn'=>'TEMPLATE_EDIT_5','list_index'=>5),
			array('data_name'=>'pic_list_6','edit_btn'=>'TEMPLATE_EDIT_6','list_index'=>6),
			array('data_name'=>'pic_list_11','edit_btn'=>'TEMPLATE_EDIT_12','list_index'=>11),
			array('data_name'=>'pic_list_12','edit_btn'=>'TEMPLATE_EDIT_13','list_index'=>12),
		);
		foreach ($pic_left_design_list as $key => $item_value) {
			$now_index = $item_value['list_index'] ? $item_value['list_index'] : '';

			$total_num = $design_arr[$item_value['edit_btn']]['length'];
			$has_img_num = 0;

			// 获取 图片列表信息
			$tpl_pic_list_data = $tpl_all_data[$item_value['data_name']];

			$selected_list_data = '';
			$pic_list_html = '';

			if ($tpl_pic_list_data) {
				$has_img_num = count($tpl_pic_list_data);

				// 排序
				$arr_sort = array(  
				        'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序  
				        'field'     => 'sort',       //排序字段  
				);  
				$arrSort = array();  
				foreach($tpl_pic_list_data AS $uniqid => $row){  
				    foreach($row AS $key=>$value){  
				        $arrSort[$key][$uniqid] = $value;  
				    }  
				}
				if($arr_sort['direction']){  
					array_multisort($arrSort[$arr_sort['field']], constant($arr_sort['direction']), $tpl_pic_list_data);  
				}

				foreach ($tpl_pic_list_data as $key => $value) {
					$pic_item_tpl = $template_list_item['LEFTPIC']['real'];

					// 获取信息
					if ($tpl_data['sld_shop_id']) {
						$model_album = Model('imagespace');
						$pic_info = $model_album->getOne(array('apic_id'=>$value['pic_id']));
						$pic_item_tpl = str_replace('{PICSRC}', thumb($pic_info, 'real'), $pic_item_tpl);
					}else{
						$pic_info = $model_fixture_pic->getFixturePicInfo(array('id'=>$value['pic_id']),array('id','sld_pic_name'));
						$pic_item_tpl = str_replace('{PICSRC}', $this->fixture_pic_url($pic_info['sld_pic_name']), $pic_item_tpl);
					}

					$pic_link = $show_edit ? 'javascript:;' : ($value['links'] ? $value['links'] : 'javascript:;') ;

					$pic_item_tpl = str_replace('{PICLINK}', $pic_link, $pic_item_tpl);

					$pic_list_html .= $pic_item_tpl;
				}
			}

			if (!$has_img_num && $show_edit) {
				for ($i=0; $i < ($total_num-$has_img_num); $i++) { 
					$pic_item_tpl = $template_list_item['LEFTPIC']['example'];

					$pic_item_tpl = str_replace('{PICWIDTH}', $design_arr[$item_value['edit_btn']]['pic_w'], $pic_item_tpl);
					$pic_item_tpl = str_replace('{PICHEIGHT}', $design_arr[$item_value['edit_btn']]['pic_h'] ? $design_arr[$item_value['edit_btn']]['pic_h'] : '高度不限', $pic_item_tpl);

					$pic_list_html .= $pic_item_tpl;

				}
			}

			$template_html = str_replace('{PICLIST'.$now_index.'}', $pic_list_html, $template_html);

		}

		// 右侧tab 图片
		// pic_list_1,pic_list_2,pic_list_3,pic_list_7,pic_list_8,pic_list_9
		// 获取图片列表
		$goods_design_list = array(
			array('data_name'=>'pic_list_1','edit_btn'=>'TEMPLATE_EDIT_1','list_index'=>1),
			array('data_name'=>'pic_list_2','edit_btn'=>'TEMPLATE_EDIT_2','list_index'=>2),
			array('data_name'=>'pic_list_3','edit_btn'=>'TEMPLATE_EDIT_3','list_index'=>3),
			array('data_name'=>'pic_list_7','edit_btn'=>'TEMPLATE_EDIT_8','list_index'=>7),
			array('data_name'=>'pic_list_8','edit_btn'=>'TEMPLATE_EDIT_9','list_index'=>8),
			array('data_name'=>'pic_list_9','edit_btn'=>'TEMPLATE_EDIT_10','list_index'=>9)
		);
		$tpl_goods_cat_default_name = $show_edit ? '添加内容' : '';

		foreach ($goods_design_list as $key => $item_value) {
			$selected_goods_list_data = '';
			$has_goods_num = 0;
			$now_index = $item_value['list_index'] ? $item_value['list_index'] : '';
			$tpl_goods_cat_data = $tpl_all_data['extend'][$item_value['data_name']]['cat_name'];

			$total_goods_num = $design_arr[$item_value['edit_btn']]['length'];


			if ($tpl_goods_cat_data) {
				$tpl_goods_data = $tpl_all_data[$item_value['data_name']];

				if ($tpl_goods_data) {
					$has_goods_num = count($tpl_goods_data);

					foreach($tpl_goods_data AS $uniqid => $row){  
					    foreach($row AS $key=>$value){  
					        $arrSort[$key][$uniqid] = $value;  
					    }  
					}

					if($arr_sort['direction']){  
						@array_multisort($arrSort[$arr_sort['field']], constant($arr_sort['direction']), $tpl_goods_data);  
					}

					foreach ($tpl_goods_data as $key => $value) {
						$pic_item_tpl = $template_list_item['RIGHTPIC']['real'];

						$liClass = '';
						if ($key>1) {
							$liClass = 'bottom-item';
						}

						// 获取信息
						if ($tpl_data['sld_shop_id']) {
							$model_album = Model('imagespace');
							$pic_info = $model_album->getOne(array('apic_id'=>$value['pic_id']));
							$pic_item_tpl = str_replace('{PICSRC}', thumb($pic_info, 'real'), $pic_item_tpl);
						}else{
							$pic_info = $model_fixture_pic->getFixturePicInfo(array('id'=>$value['pic_id']),array('id','sld_pic_name'));
							$pic_item_tpl = str_replace('{PICSRC}', $this->fixture_pic_url($pic_info['sld_pic_name']), $pic_item_tpl);
						}

						$pic_link = $show_edit ? 'javascript:;' : ($value['links'] ? $value['links'] : 'javascript:;') ;

						$pic_title = $value['pic_title'] ? $value['pic_title'] : '图片标题';
						$pic_sub_title = $value['pic_sub_title'] ? $value['pic_sub_title'] : '图片子标题';

						$pic_item_tpl = str_replace('{PICLINK}', $pic_link, $pic_item_tpl);

						$pic_item_tpl = str_replace('{PICTITLE}', $pic_title, $pic_item_tpl);
						$pic_item_tpl = str_replace('{PICSUBTITLE}', $pic_sub_title, $pic_item_tpl);

						$pic_item_tpl = str_replace('{LICLASS}', $liClass, $pic_item_tpl);

						$selected_goods_list_data .= $pic_item_tpl;

					}
				}

				if (!$has_goods_num && $show_edit) {
					for ($i=0; $i < ($total_goods_num-$has_goods_num); $i++) { 
						$pic_item_tpl = $template_list_item['RIGHTPIC']['example'];

						$liClass = '';
						if ($i>1) {
							$liClass = 'bottom-item';
						}

						$pic_item_tpl = str_replace('{PICWIDTH}', $design_arr[$item_value['edit_btn']]['pic_w'], $pic_item_tpl);
						$pic_item_tpl = str_replace('{PICHEIGHT}', $design_arr[$item_value['edit_btn']]['pic_h'] ? $design_arr[$item_value['edit_btn']]['pic_h'] : '高度不限', $pic_item_tpl);

						$pic_item_tpl = str_replace('{LICLASS}', $liClass, $pic_item_tpl);

						$selected_goods_list_data .= $pic_item_tpl;
					}
				}


				$template_html = str_replace('{PICLIST'.$now_index.'}', $selected_goods_list_data, $template_html);
				$template_html = str_replace('{GOODSCATNAME'.$now_index.'}', $tpl_goods_cat_data, $template_html);
			}else{
				if ($show_edit) {
					for ($i=0; $i < ($total_goods_num); $i++) { 
						$pic_item_tpl = $template_list_item['RIGHTPIC']['example'];
						
						$liClass = '';
						if ($i>1) {
							$liClass = 'bottom-item';
						}

						$pic_item_tpl = str_replace('{PICWIDTH}', $design_arr[$item_value['edit_btn']]['pic_w'], $pic_item_tpl);
						$pic_item_tpl = str_replace('{PICHEIGHT}', $design_arr[$item_value['edit_btn']]['pic_h'] ? $design_arr[$item_value['edit_btn']]['pic_h'] : '高度不限', $pic_item_tpl);

						$pic_item_tpl = str_replace('{LICLASS}', $liClass, $pic_item_tpl);

						$selected_goods_list_data .= $pic_item_tpl;
					}
				}

				$template_html = str_replace('{PICLIST'.$now_index.'}', $selected_goods_list_data, $template_html);
				$template_html = str_replace('{GOODSCATNAME'.$now_index.'}', $tpl_goods_cat_default_name, $template_html);
			}
		}

		return $template_html;
	}
	public function fixture_tpl_shop_street($show_edit,$design_arr,$template_list_item,$tpl_data,$template_html)
	{
		// 获取装修模板的数据
		$tpl_all_data = unserialize($tpl_data['sld_tpl_data']);

		// pic_list
		// 获取 图片列表信息
		$tpl_pic_list_data = $tpl_all_data['pic_list'];

		// 获取图片列表
		$model_fixture_pic = Model('web_home');

		$total_num = $design_arr['TEMPLATE_EDIT_1']['length'];
		$has_img_num = 0;

		$selected_list_data = '';

		if ($tpl_pic_list_data) {
			$has_img_num = count($tpl_pic_list_data);

			// 排序
			$arr_sort = array(  
			        'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序  
			        'field'     => 'sort',       //排序字段  
			);  
			$arrSort = array();  
			foreach($tpl_pic_list_data AS $uniqid => $row){  
			    foreach($row AS $key=>$value){  
			        $arrSort[$key][$uniqid] = $value;  
			    }  
			}
			if($arr_sort['direction']){  
				array_multisort($arrSort[$arr_sort['field']], constant($arr_sort['direction']), $tpl_pic_list_data);  
			}

			foreach ($tpl_pic_list_data as $key => $value) {
				$pic_item_tpl = $template_list_item['PICLIST']['real'];

				// 获取信息
				if ($tpl_data['sld_shop_id']) {
					$model_album = Model('imagespace');
					$pic_info = $model_album->getOne(array('apic_id'=>$value['pic_id']));
					$pic_item_tpl = str_replace('{PICSRC}', thumb($pic_info, 'real'), $pic_item_tpl);
				}else{
					$pic_info = $model_fixture_pic->getFixturePicInfo(array('id'=>$value['pic_id']),array('id','sld_pic_name'));
					$pic_item_tpl = str_replace('{PICSRC}', $this->fixture_pic_url($pic_info['sld_pic_name']), $pic_item_tpl);
				}

				$pic_link = $show_edit ? 'javascript:;' : ($value['links'] ? $value['links'] : 'javascript:;') ;

				$pic_item_tpl = str_replace('{PICLINK}', $pic_link, $pic_item_tpl);

				$pic_list_html .= $pic_item_tpl;
			}
		}

		if (!$has_img_num && $show_edit) {
			for ($i=0; $i < ($total_num-$has_img_num); $i++) { 
				$pic_item_tpl = $template_list_item['PICLIST']['example'];

				$pic_item_tpl = str_replace('{PICWIDTH}', $design_arr['TEMPLATE_EDIT_1']['pic_w'], $pic_item_tpl);
				$pic_item_tpl = str_replace('{PICHEIGHT}', $design_arr['TEMPLATE_EDIT_1']['pic_h'], $pic_item_tpl);

				$pic_list_html .= $pic_item_tpl;

			}
		}

		$template_html = str_replace('{PICLIST}', $pic_list_html, $template_html);

		// t
		// 获取标题
		$tpl_t_data = $tpl_all_data['t'];
		$shop_street_t_html = $show_edit ? '添加标题' : '';
		if ($tpl_t_data) {
			$shop_street_t_html = $tpl_t_data[0]['name'];
		}
		$template_html = str_replace('{TSTRING}', $shop_street_t_html, $template_html);

		// store
		// 获取店铺列表
		$tpl_store_data = $tpl_all_data['store'];
		$model_store = Model('vendor');

		$total_num = $design_arr['TEMPLATE_EDIT_2']['length'];
		$has_img_num = 0;

		$selected_list_data = '';
		$store_list_html ='';
		$selected_shop_ids = array();

		if ($tpl_store_data) {
			$has_img_num = count($tpl_store_data);

			// 排序
			$arr_sort = array(  
			        'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序  
			        'field'     => 'sort',       //排序字段  
			);  
			$arrSort = array();  
			foreach($tpl_store_data AS $uniqid => $row){  
			    foreach($row AS $key=>$value){  
			        $arrSort[$key][$uniqid] = $value;  
			    }  
			}
			if($arr_sort['direction']){  
				array_multisort($arrSort[$arr_sort['field']], constant($arr_sort['direction']), $tpl_store_data);  
			}

			foreach ($tpl_store_data as $key => $value) {
				$shop_item_tpl = $template_list_item['SHOPLIST']['real'];

				// 获取信息
				$shop_info = $model_store->getStoreOnlineInfoByID($value['shop_id']);

				$goods_cate_link = $show_edit ? 'javascript:;' : ($value['shop_id'] ? urlShop('vendor','index',array('vid'=> $value['shop_id'])) : 'javascript:;') ;

				$shop_item_tpl = str_replace('{SHOPLINK}', $goods_cate_link, $shop_item_tpl);
				$shop_item_tpl = str_replace('{SHOPSRC}', getStoreLogo($shop_info['store_label']), $shop_item_tpl);

				$store_list_html .= $shop_item_tpl;
			}

		}

		if ($show_edit) {
			for ($i=0; $i < ($total_num-$has_img_num); $i++) { 
				$shop_item_tpl = $template_list_item['SHOPLIST']['example'];

				$store_list_html .= $shop_item_tpl;
			}
		}

		$template_html = str_replace('{STORELIST}', $store_list_html, $template_html);

		return $template_html;
	}
	public function fixture_tpl_shop_floor_s1($show_edit,$design_arr,$template_list_item,$tpl_data,$template_html)
	{

		// 获取装修模板的数据
		$tpl_all_data = unserialize($tpl_data['sld_tpl_data']);

		// 获取商品列表信息

		$tpl_goods_data = $tpl_all_data['goods'];

		$model_goods = Model('goods');

		$total_goods_num = $design_arr['TEMPLATE_EDIT_1']['length'];
		$row_num = 5;

		$selected_goods_list_data = '';
		$has_goods_num = 0;
		$now_index = ($key+1);
		
		if ($tpl_goods_data) {
			$has_goods_num = count($tpl_goods_data);

			// 排序
			$arr_sort = array(  
			        'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序  
			        'field'     => 'sort',       //排序字段  
			);  

			foreach($tpl_goods_data AS $uniqid => $row){  
			    foreach($row AS $key=>$value){  
			        $arrSort[$key][$uniqid] = $value;  
			    }  
			}

			if($arr_sort['direction']){  
				@array_multisort($arrSort[$arr_sort['field']], constant($arr_sort['direction']), $tpl_goods_data);  
			}

			foreach ($tpl_goods_data as $key => $value) {
				$row_frist_class = '';
				if ($key%$row_num==0) {
					$row_frist_class = 'first';
				}
				$goods_item_tpl = $template_list_item['GOODSLIST']['real'];
				// 获取信息
				$goods_info = $model_goods->getGoodsOnlineInfo(array('gid'=>$value['goods_id']),array('gid','goods_name','goods_image','goods_price'));

		        // 获取最终价格
		        $goods_info = Model('goods_activity')->rebuild_goods_data($goods_info,'pc');

				$goods_link = $show_edit ? 'javascript:;' : ($value['goods_id'] ? urlShop('goods','index',array('gid'=>$value['goods_id'])) : 'javascript:;') ;

				$goods_item_tpl = str_replace('{GOODSLINK}', $goods_link, $goods_item_tpl);
				$goods_item_tpl = str_replace('{GOODSNAME}', $goods_info['goods_name'], $goods_item_tpl);
				$goods_item_tpl = str_replace('{GOODSSRC}', thumb($goods_info), $goods_item_tpl);
				$goods_item_tpl = str_replace('{GOODSPRICE}', $goods_info['show_price'], $goods_item_tpl);
				$goods_item_tpl = str_replace('{ISROWFRIST}', $row_frist_class, $goods_item_tpl);

				$selected_goods_list_data .= $goods_item_tpl;

			}
		}

		if (!$has_goods_num && $show_edit) {
			for ($i=0; $i < ($total_goods_num-$has_goods_num); $i++) { 
				$row_frist_class = '';
				if ($i%$row_num==0) {
					$row_frist_class = 'first';
				}
				$goods_item_tpl = $template_list_item['GOODSLIST']['example'];

				$goods_item_tpl = str_replace('{ISROWFRIST}', $row_frist_class, $goods_item_tpl);

				$selected_goods_list_data .= $goods_item_tpl;
			}
		}


		$template_html = str_replace('{GOODSLIST'.$now_index.'}', $selected_goods_list_data, $template_html);
		// t
		// 获取标题
		$tpl_t_data = $tpl_all_data['t'];
		$shop_street_t_html = $show_edit ? '添加标题' : '';
		$shop_street_t_link_html = 'javascript:void(0);';
		if ($tpl_t_data) {
			$shop_street_t_html = $tpl_t_data[0]['name'];
			$shop_street_t_link_html = $show_edit ? $shop_street_t_link_html : $tpl_t_data[0]['title_link'];
		}
		$template_html = str_replace('{TSTRING}', $shop_street_t_html, $template_html);
		$template_html = str_replace('{TITLELINK}', $shop_street_t_link_html, $template_html);

		return $template_html;
	}
	public function fixture_tpl_shop_floor_s2($show_edit,$design_arr,$template_list_item,$tpl_data,$template_html)
	{

		// 获取装修模板的数据
		$tpl_all_data = unserialize($tpl_data['sld_tpl_data']);

		// pic_list
		// 获取 图片列表信息
		$tpl_pic_list_data = $tpl_all_data['pic_list'];

		// 获取图片列表
		$model_fixture_pic = Model('web_home');

		$total_num = $design_arr['TEMPLATE_EDIT_1']['length'];
		$has_img_num = 0;

		$selected_list_data = '';
		$pic_list_html = '';

		if ($tpl_pic_list_data) {
			$has_img_num = count($tpl_pic_list_data);

			// 排序
			$arr_sort = array(  
			        'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序  
			        'field'     => 'sort',       //排序字段  
			);  
			$arrSort = array();  
			foreach($tpl_pic_list_data AS $uniqid => $row){  
			    foreach($row AS $key=>$value){  
			        $arrSort[$key][$uniqid] = $value;  
			    }  
			}
			if($arr_sort['direction']){  
				array_multisort($arrSort[$arr_sort['field']], constant($arr_sort['direction']), $tpl_pic_list_data);  
			}

			foreach ($tpl_pic_list_data as $key => $value) {
				$pic_item_tpl = $template_list_item['PICLIST']['real'];

				// 获取信息
				if ($tpl_data['sld_shop_id']) {
					$model_album = Model('imagespace');
					$pic_info = $model_album->getOne(array('apic_id'=>$value['pic_id']));
					$pic_item_tpl = str_replace('{PICSRC}', thumb($pic_info, 'real'), $pic_item_tpl);
				}else{
					$pic_info = $model_fixture_pic->getFixturePicInfo(array('id'=>$value['pic_id']),array('id','sld_pic_name'));
					$pic_item_tpl = str_replace('{PICSRC}', $this->fixture_pic_url($pic_info['sld_pic_name']), $pic_item_tpl);
				}

				$pic_link = $show_edit ? 'javascript:;' : ($value['links'] ? $value['links'] : 'javascript:;') ;

				$pic_item_tpl = str_replace('{PICLINK}', $pic_link, $pic_item_tpl);

				$pic_list_html .= $pic_item_tpl;
			}
		}

		if (!$has_img_num && $show_edit) {
			for ($i=0; $i < ($total_num-$has_img_num); $i++) { 
				$pic_item_tpl = $template_list_item['PICLIST']['example'];

				$pic_item_tpl = str_replace('{PICWIDTH}', $design_arr['TEMPLATE_EDIT_1']['pic_w'], $pic_item_tpl);
				$pic_item_tpl = str_replace('{PICHEIGHT}', $design_arr['TEMPLATE_EDIT_1']['pic_h'] ? $design_arr['TEMPLATE_EDIT_1']['pic_h'] : '高度不限', $pic_item_tpl);

				$pic_list_html .= $pic_item_tpl;

			}
		}

		$template_html = str_replace('{PICLIST}', $pic_list_html, $template_html);

		// 获取商品列表信息

		$tpl_goods_data = $tpl_all_data['goods'];

		$model_goods = Model('goods');

		$total_goods_num = $design_arr['TEMPLATE_EDIT_2']['length'];
		$row_num = 4;

		$selected_goods_list_data = '';
		$has_goods_num = 0;
		$now_index = 2;
		
		if ($tpl_goods_data) {
			$has_goods_num = count($tpl_goods_data);

			// 排序
			$arr_sort = array(  
			        'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序  
			        'field'     => 'sort',       //排序字段  
			);  

			foreach($tpl_goods_data AS $uniqid => $row){  
			    foreach($row AS $key=>$value){  
			        $arrSort[$key][$uniqid] = $value;  
			    }  
			}

			if($arr_sort['direction']){  
				@array_multisort($arrSort[$arr_sort['field']], constant($arr_sort['direction']), $tpl_goods_data);  
			}

			foreach ($tpl_goods_data as $key => $value) {
				$row_frist_class = '';
				if ($key%$row_num==0) {
					$row_frist_class = 'shop-goods-spe';
				}
				$goods_item_tpl = $template_list_item['GOODSLIST']['real'];
				// 获取信息
				$goods_info = $model_goods->getGoodsOnlineInfo(array('gid'=>$value['goods_id']),array('gid','goods_name','goods_image','goods_price'));

		        // 获取最终价格
		        $goods_info = Model('goods_activity')->rebuild_goods_data($goods_info,'pc');

				$goods_link = $show_edit ? 'javascript:;' : ($value['goods_id'] ? urlShop('goods','index',array('gid'=>$value['goods_id'])) : 'javascript:;') ;

				$goods_item_tpl = str_replace('{GOODSLINK}', $goods_link, $goods_item_tpl);
				$goods_item_tpl = str_replace('{GOODSNAME}', $goods_info['goods_name'], $goods_item_tpl);
				$goods_item_tpl = str_replace('{GOODSSRC}', thumb($goods_info), $goods_item_tpl);
				$goods_item_tpl = str_replace('{GOODSPRICE}', $goods_info['show_price'], $goods_item_tpl);
				$goods_item_tpl = str_replace('{ISROWFRIST}', $row_frist_class, $goods_item_tpl);

				$selected_goods_list_data .= $goods_item_tpl;

			}
		}

		if (!$has_goods_num && $show_edit) {
			for ($i=0; $i < ($total_goods_num-$has_goods_num); $i++) { 
				$row_frist_class = '';
				if ($i%$row_num==0) {
					$row_frist_class = 'shop-goods-spe';
				}
				$goods_item_tpl = $template_list_item['GOODSLIST']['example'];

				$goods_item_tpl = str_replace('{ISROWFRIST}', $row_frist_class, $goods_item_tpl);

				$selected_goods_list_data .= $goods_item_tpl;
			}
		}


		$template_html = str_replace('{GOODSLIST'.$now_index.'}', $selected_goods_list_data, $template_html);
		// t
		// 获取标题
		$tpl_t_data = $tpl_all_data['t'];
		$shop_street_t_html = $show_edit ? '添加标题' : '';
		$shop_street_t_link_html = 'javascript:void(0);';
		if ($tpl_t_data) {
			$shop_street_t_html = $tpl_t_data[0]['name'];
			$shop_street_t_link_html = $show_edit ? $shop_street_t_link_html : $tpl_t_data[0]['title_link'];
		}
		$template_html = str_replace('{TSTRING}', $shop_street_t_html, $template_html);
		$template_html = str_replace('{TITLELINK}', $shop_street_t_link_html, $template_html);

		return $template_html;
	}
	public function fixture_tpl_topic_cate_s1($show_edit,$design_arr,$template_list_item,$tpl_data,$template_html)
	{
		// 获取装修模板的数据
		$tpl_all_data = unserialize($tpl_data['sld_tpl_data']);

		// 分类
		// 获取分类列表
		$tpl_category_data = $tpl_all_data['category'];

		$model_class = Model('goods_class');

		$total_cate_num = $design_arr['TEMPLATE_EDIT']['number'];
		$has_cate_num = 0;

		$selected_list_data = '';

		// 排序
		$arr_sort = array(  
		        'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序  
		        'field'     => 'sort',       //排序字段  
		);
		$arrSort = array();  
		if ($tpl_category_data) {
			$has_cate_num = count($tpl_category_data);

			foreach($tpl_category_data AS $uniqid => $row){  
			    foreach($row AS $key=>$value){  
			        $arrSort[$key][$uniqid] = $value;  
			    }  
			}

			if($arr_sort['direction']){  
				@array_multisort($arrSort[$arr_sort['field']], constant($arr_sort['direction']), $tpl_category_data);  
			}

			foreach ($tpl_category_data as $key => $value) {
				$goods_cate_item_tpl = $template_list_item['CATEGORYLIST']['real'];
				$goods_cate_item_sub_tpl = $template_list_item['ITEMSUBLIST']['real'];
				$goods_cate_sub_item_tpl = $template_list_item['SUBITEMLIST']['real'];

				$liClass = ($key%2==1) ? 'odd' : 'even';

				// 获取信息
				$category_info = $model_class->getGoodsClassList(array('gc_id' => $value['cat_id']), 'gc_id,gc_name');

				$goods_cate_link = $show_edit ? 'javascript:;' : ($value['cat_id'] ? urlShop('goodslist','index',array('cid'=> $value['cat_id'])) : 'javascript:;') ;

				// 获取 子分类
				$sub_category_list = $model_class->getGoodsClassList(array('gc_parent_id' => $value['cat_id']), 'gc_id,gc_name');

				$sub_category_arr = array();
				if (!empty($sub_category_list)) {
					$item_sub_list_data_html = '';
					$sub_item_list_data_html = '';
					foreach ($sub_category_list as $sub_key => $sub_value) {
						$item_sub_list_data = $goods_cate_item_sub_tpl;
						$sub_item_list_data = $goods_cate_sub_item_tpl;
						$goods_cate_item_sub_link = $show_edit ? 'javascript:;' : ($sub_value['gc_id'] ? urlShop('goodslist','index',array('cid'=> $sub_value['gc_id'])) : 'javascript:;') ;
						if ($sub_key < 2) {
							$item_sub_list_data = str_replace('{ITEMSUBLINK}', $goods_cate_item_sub_link, $item_sub_list_data);
							$item_sub_list_data = str_replace('{ITEMSUBNAME}', $sub_value['gc_name'], $item_sub_list_data);
							$item_sub_list_data_html .= $item_sub_list_data;
							$sub_category_arr['item_sub'][] = $sub_value;
						}else{
							$sub_item_list_data = str_replace('{SUBITEMLINK}', $goods_cate_item_sub_link, $sub_item_list_data);
							$sub_item_list_data = str_replace('{SUBITEMNAME}', $sub_value['gc_name'], $sub_item_list_data);
							$sub_item_list_data_html .= $sub_item_list_data;
							$sub_category_arr['sub_item'][] = $sub_value;
						}
					}
				}

				$goods_cate_item_tpl = str_replace('{LICLASS}', $liClass, $goods_cate_item_tpl);
				$goods_cate_item_tpl = str_replace('{CATENAME}', $category_info[0]['gc_name'], $goods_cate_item_tpl);
				$goods_cate_item_tpl = str_replace('{CATELINK}', $goods_cate_link, $goods_cate_item_tpl);

				$goods_cate_item_tpl = str_replace('{ITEMSUBLIST}', $item_sub_list_data_html, $goods_cate_item_tpl);
				$goods_cate_item_tpl = str_replace('{SUBITEMLIST}', $sub_item_list_data_html, $goods_cate_item_tpl);

				$selected_list_data .= $goods_cate_item_tpl;
			}
		}


		if (!$has_cate_num && $show_edit) {
			for ($i=0; $i < ($total_cate_num-$has_cate_num); $i++) { 
				$goods_cate_item_tpl = $template_list_item['CATEGORYLIST']['example'];
				$goods_cate_item_sub_tpl = $template_list_item['ITEMSUBLIST']['example'];
				$goods_cate_sub_item_tpl = $template_list_item['SUBITEMLIST']['example'];

				$liClass = ($key%2==1) ? 'odd' : 'even';
				$item_sub_list_data_html = $goods_cate_item_sub_tpl;
				$sub_item_list_data_html = $goods_cate_sub_item_tpl;

				$goods_cate_item_tpl = str_replace('{LICLASS}', $liClass, $goods_cate_item_tpl);
				$goods_cate_item_tpl = str_replace('{ITEMSUBLIST}', $item_sub_list_data_html, $goods_cate_item_tpl);
				$goods_cate_item_tpl = str_replace('{ITEMSUBLIST}', $sub_item_list_data_html, $goods_cate_item_tpl);

				$selected_list_data .= $goods_cate_item_tpl;
			}
		}

		$template_html = str_replace('{CATEGORYLIST}', $selected_list_data, $template_html);

		return $template_html;
	}

	public function fixture_tpl_banner_slide($show_edit,$design_arr,$template_list_item,$tpl_data,$template_html)
	{
		// 获取装修模板的数据
		$tpl_all_data = unserialize($tpl_data['sld_tpl_data']);

		// pic_list
		// 获取 图片列表信息
		$tpl_pic_list_data = $tpl_all_data['pic_list'];

		// 获取图片列表
		$model_fixture_pic = Model('web_home');

		$total_num = $design_arr['TEMPLATE_EDIT']['length'];
		$has_img_num = 0;

		$selected_list_data = '';
		$pic_list_html = '';

		if ($tpl_pic_list_data) {
			$has_img_num = count($tpl_pic_list_data);

			// 排序
			$arr_sort = array(  
			        'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序  
			        'field'     => 'sort',       //排序字段  
			);  
			$arrSort = array();  
			foreach($tpl_pic_list_data AS $uniqid => $row){  
			    foreach($row AS $key=>$value){  
			        $arrSort[$key][$uniqid] = $value;  
			    }  
			}
			if($arr_sort['direction']){  
				array_multisort($arrSort[$arr_sort['field']], constant($arr_sort['direction']), $tpl_pic_list_data);  
			}

			foreach ($tpl_pic_list_data as $key => $value) {
				$pic_item_tpl = $template_list_item['PICLIST']['real'];

				// 获取信息
				if ($tpl_data['sld_shop_id']) {
					$model_album = Model('imagespace');
					$pic_info = $model_album->getOne(array('apic_id'=>$value['pic_id']));
					$pic_item_tpl = str_replace('{PICSRC}', thumb($pic_info, 'real'), $pic_item_tpl);
				}else{
					$pic_info = $model_fixture_pic->getFixturePicInfo(array('id'=>$value['pic_id']),array('id','sld_pic_name'));
					$pic_item_tpl = str_replace('{PICSRC}', $this->fixture_pic_url($pic_info['sld_pic_name']), $pic_item_tpl);
				}

				$pic_link = $show_edit ? 'javascript:;' : ($value['links'] ? $value['links'] : 'javascript:;') ;

				$pic_item_tpl = str_replace('{PICLINK}', $pic_link, $pic_item_tpl);

				$pic_list_html .= $pic_item_tpl;
			}
		}

		if (!$has_img_num && $show_edit) {
			// for ($i=0; $i < ($total_num-$has_img_num); $i++) { 
				$pic_item_tpl = $template_list_item['PICLIST']['example'];

				$pic_item_tpl = str_replace('{PICWIDTH}', $design_arr['TEMPLATE_EDIT']['pic_w'], $pic_item_tpl);
				$pic_item_tpl = str_replace('{PICHEIGHT}', $design_arr['TEMPLATE_EDIT']['pic_h'] ? $design_arr['TEMPLATE_EDIT']['pic_h'] : '高度不限', $pic_item_tpl);

				$pic_list_html .= $pic_item_tpl;

			// }
		}

		$template_html = str_replace('{PICLIST}', $pic_list_html, $template_html);

		return $template_html;
	}
	public function fixture_tpl_activity_goods_1($show_edit,$design_arr,$template_list_item,$tpl_data,$template_html)
	{
		// 获取装修模板的数据
		$tpl_all_data = unserialize($tpl_data['sld_tpl_data']);

		// pic_list_1,pic_list_2
		// 获取图片列表
		$pic_list_design_list = array(
			array('data_name'=>'pic_list','edit_btn'=>'TEMPLATE_EDIT','list_index'=>''),
			array('data_name'=>'pic_list_1','edit_btn'=>'TEMPLATE_EDIT_2','list_index'=>1)
		);

		// 获取图片列表
		$model_fixture_pic = Model('web_home');

		foreach ($pic_list_design_list as $item_key => $item_value) {
			$now_index = $item_value['list_index'] ? $item_value['list_index'] : '';

			$total_num = $design_arr[$item_value['edit_btn']]['length'];
			$has_img_num = 0;

			$tpl_pic_list_data = $tpl_all_data[$item_value['data_name']];

			$tpl_bg_color = $tpl_all_data['extend'][$item_value['data_name']]['bg_color'] ? '#'.$tpl_all_data['extend'][$item_value['data_name']]['bg_color'] : '';

			$pic_list_html = '';

			if ($tpl_pic_list_data) {
				$has_img_num = count($tpl_pic_list_data);

				// 排序
				$arr_sort = array(  
				        'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序  
				        'field'     => 'sort',       //排序字段  
				);  
				$arrSort = array();  
				foreach($tpl_pic_list_data AS $uniqid => $row){  
				    foreach($row AS $key=>$value){  
				        $arrSort[$key][$uniqid] = $value;  
				    }  
				}
				if($arr_sort['direction']){  
					array_multisort($arrSort[$arr_sort['field']], constant($arr_sort['direction']), $tpl_pic_list_data);  
				}

				foreach ($tpl_pic_list_data as $key => $value) {
					$pic_item_tpl = $template_list_item['PICLIST']['real'];

					$row_frist_class = '';
					if ($key==0) {
						$row_frist_class = 'first-item';
					}

					// 获取信息
					if ($tpl_data['sld_shop_id']) {
						$model_album = Model('imagespace');
						$pic_info = $model_album->getOne(array('apic_id'=>$value['pic_id']));
						$pic_item_tpl = str_replace('{PICSRC}', thumb($pic_info, 'real'), $pic_item_tpl);
					}else{
						$pic_info = $model_fixture_pic->getFixturePicInfo(array('id'=>$value['pic_id']),array('id','sld_pic_name'));
						$pic_item_tpl = str_replace('{PICSRC}', $this->fixture_pic_url($pic_info['sld_pic_name']), $pic_item_tpl);
					}

					$pic_link = $show_edit ? 'javascript:;' : ($value['links'] ? $value['links'] : 'javascript:;') ;

					$pic_item_tpl = str_replace('{PICLINK}', $pic_link, $pic_item_tpl);

					$pic_item_tpl = str_replace('{LICLASS}', $row_frist_class, $pic_item_tpl);

					$pic_list_html .= $pic_item_tpl;
				}

			}

			if (!$has_img_num && $show_edit) {
				for ($i=0; $i < ($total_num-$has_img_num); $i++) { 
					$pic_item_tpl = $template_list_item['PICLIST']['example'];

					$row_frist_class = '';
					if ($i==0) {
						$row_frist_class = 'first-item';
					}

					$pic_item_tpl = str_replace('{PICWIDTH}', $design_arr[$item_value['edit_btn']]['pic_w'], $pic_item_tpl);

					$pic_item_tpl = str_replace('{PICHEIGHT}', $design_arr[$item_value['edit_btn']]['pic_h'] ? $design_arr[$item_value['edit_btn']]['pic_h'] : '高度不限', $pic_item_tpl);
					$pic_item_tpl = str_replace('{LICLASS}', $row_frist_class, $pic_item_tpl);

					$pic_list_html .= $pic_item_tpl;

				}
			}

			$template_html = str_replace('{PICLIST'.$now_index.'}', $pic_list_html, $template_html);
			$template_html = str_replace('{BGCOLOR'.$now_index.'}', $tpl_bg_color, $template_html);

		}

		// 获取商品列表信息
		$tpl_goods_data = $tpl_all_data['goods'];

		$model_goods = Model('goods');

		$total_goods_num = $design_arr['TEMPLATE_EDIT_1']['number'];
		$row_num = 5;

		$selected_goods_list_data = '';
		$has_goods_num = 0;
		
		if ($tpl_goods_data) {
			$has_goods_num = count($tpl_goods_data);

			// 排序
			$arr_sort = array(  
			        'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序  
			        'field'     => 'sort',       //排序字段  
			);  

			foreach($tpl_goods_data AS $uniqid => $row){  
			    foreach($row AS $key=>$value){  
			        $arrSort[$key][$uniqid] = $value;  
			    }  
			}

			if($arr_sort['direction']){  
				@array_multisort($arrSort[$arr_sort['field']], constant($arr_sort['direction']), $tpl_goods_data);  
			}

			foreach ($tpl_goods_data as $key => $value) {
				$row_frist_class = '';
				if ($key==0) {
					$row_frist_class = 'first-item';
				}

				$goods_item_tpl = $template_list_item['GOODSLIST']['real'];
				// 获取信息
				$goods_info = $model_goods->getGoodsOnlineInfo(array('gid'=>$value['goods_id']),array('gid','goods_name','goods_image','goods_price','goods_marketprice','goods_salenum'));

		        // 获取最终价格
		        $goods_info = Model('goods_activity')->rebuild_goods_data($goods_info,'pc');

				$goods_link = $show_edit ? 'javascript:;' : ($value['goods_id'] ? urlShop('goods','index',array('gid'=>$value['goods_id'])) : 'javascript:;') ;

				$goods_item_tpl = str_replace('{GOODSLINK}', $goods_link, $goods_item_tpl);
				$goods_item_tpl = str_replace('{GOODSNAME}', $goods_info['goods_name'], $goods_item_tpl);
				$goods_item_tpl = str_replace('{GOODSSRC}', thumb($goods_info), $goods_item_tpl);
				$goods_item_tpl = str_replace('{GOODSPRICE}', $goods_info['show_price'], $goods_item_tpl);

				$goods_item_tpl = str_replace('{GOODSMARKETPRICE}', $goods_info['goods_marketprice'], $goods_item_tpl);
				$goods_item_tpl = str_replace('{GOODSSALEDNUM}', $goods_info['goods_salenum'], $goods_item_tpl);

				$goods_item_tpl = str_replace('{LICLASS}', $row_frist_class, $goods_item_tpl);

				$selected_goods_list_data .= $goods_item_tpl;

			}
		}

		if (!$has_goods_num && $show_edit) {
			for ($i=0; $i < ($total_goods_num-$has_goods_num); $i++) { 
				$row_frist_class = '';
				if ($i==0) {
					$row_frist_class = 'first-item';
				}

				$goods_item_tpl = $template_list_item['GOODSLIST']['example'];

				$goods_item_tpl = str_replace('{PICWIDTH}', $design_arr['TEMPLATE_EDIT_1']['pic_w'], $goods_item_tpl);

				$goods_item_tpl = str_replace('{PICHEIGHT}', $design_arr['TEMPLATE_EDIT_1']['pic_h'] ? $design_arr['TEMPLATE_EDIT_1']['pic_h'] : '高度不限', $goods_item_tpl);
				$goods_item_tpl = str_replace('{LICLASS}', $row_frist_class, $goods_item_tpl);

				$selected_goods_list_data .= $goods_item_tpl;
			}
		}


		$template_html = str_replace('{GOODSLIST}', $selected_goods_list_data, $template_html);

		return $template_html;
	}
	//////////////////////-----装修模板的方法 sld_end-----///////////////////////////////
	//////////////////////////////////////////////////////////////////////////////////
	//////////////////////-----装修模板编辑窗口的方法 sld_start-----///////////////////////
	public function fixture_edit_layer_goods($tpl_data,$layData,$fixture_tpl_info,$template_html)
	{
		$top_tips = '';

		$searchextendfields_html = '';
		
		$layer_tpl_selected_list_item_html = $fixture_tpl_info['sld_selected_list_item'];
		$layer_tpl_list_item_html = $fixture_tpl_info['sld_list_item'];

		$dataCondition = array();

		if(isset($layData['id'])){
			$id = (int)$layData['id'];
			$lang_type = Model('tpl_page')->where(['id'=>$id])->field('lang_type')->one();
			$dataCondition['sites'] = $lang_type;
		}

		if ($layData['shop_id']) {
			$dataCondition['vid'] = $layData['shop_id'];
		}
		if ($layData['city_id']) {
			$dataCondition['city_id'] = $layData['city_id'];
		}
		// 根据所属页面类型 增加数据过滤条件
		switch ($tpl_data['sld_page']) {
			case 's_index':
				$dataCondition['is_supplier'] = 1;
				break;
		}

		$has_layer_title = isset($layData['has_title']) ? $layData['has_title'] : 1;
		$top_title = '';
		if ($has_layer_title) {
			$top_title_html = '<div class="table-content m-t-10 clearfix"><div class="simple-form-field"><div class="form-group"><label for="text4" class="col-sm-3 control-label"><span class="ng-binding"><span class="text-danger ng-binding">*</span>分类名称：</span></label><div class="col-sm-7"><div class="form-control-box"><input class="form-control" type="text" value="{GOODSCATNAME}" id="cat_name"></div><div class="help-block help-block-t"><div class="help-block help-block-t">分类名称不能为空，最多输入{MAXNUMBER}个字</div></div></div></div></div>';

			$top_title = $top_title_html;
			
		}

		// 是否有温馨提示
		if ($layData['has_top_tips']) {
			$layer_tpl_top_tips_html = $fixture_tpl_info['sld_tpl_top_tips'];
			if ($has_layer_title) {
				$top_tips_html = '<li><span>为达到页面效果，建议上传{MAXITEMNUMBER}个商品，分类名称不超过{MAXNUMBER}个字</span></li>';
			}else{
				$top_tips_html = '<li><span>为达到页面效果，建议上传{MAXITEMNUMBER}个商品</span></li>';
			}

			$layer_tpl_top_tips_html = str_replace('{LAYERTOPTIPSLIST}', $top_tips_html, $layer_tpl_top_tips_html);

			$top_tips = $layer_tpl_top_tips_html;
		}

		// 扩展 搜索条件
		$search_extend_activity_type_flag = ture;
		if ($search_extend_activity_type_flag) {
			$searchextendfields_html .= '<div class="simple-form-field">';
			$searchextendfields_html .= '<label>参与活动类型：</label>';
			$searchextendfields_html .= '<select class="form-control search-goods-activity-type">';
			$searchextendfields_html .= '<option value="0">选择参与活动类型</option>';
			$is_allow_pin = Model()->table('addons')->where(array('sld_key' => 'pin'))->find();
			if ($is_allow_pin) {
				$searchextendfields_html .= '<option value="1">拼团</option>';
			}
			if (C('tuan_allow')) {
				$searchextendfields_html .= '<option value="2">团购</option>';
			}
			if (C('promotion_allow')) {
				$searchextendfields_html .= '<option value="3">限时折扣</option>';
			}
			$searchextendfields_html .= '<option value="4">手机专享</option>';
			$searchextendfields_html .= '</select>';
			$searchextendfields_html .= '</div>';
		}

		$model_goods = Model('goods');

		// 获取装修数据
		$tpl_data_data = unserialize($tpl_data['sld_tpl_data']);

		$selected_data = $tpl_data_data[$layData['data_name']];

		// 扩展信息
		$cat_name = $tpl_data_data['extend'][$layData['data_name']]['cat_name'] ? $tpl_data_data['extend'][$layData['data_name']]['cat_name'] : '';

		$selected_goods_ids = array();
		$selected_list_data = '';
		if ($selected_data) {
			foreach ($selected_data as $key => $value) {
				// 获取信息
				$goods_info = $model_goods->getGoodsOnlineInfo(array('gid'=>$value['goods_id']),array('gid','goods_name','goods_image','goods_price'));

		        // 获取最终价格
		        $goods_info = Model('goods_activity')->rebuild_goods_data($goods_info,'pc');

				$tr_h = $layer_tpl_selected_list_item_html;

				$tr_h = str_replace('{GOODSSRC}', thumb($goods_info), $tr_h);
				$tr_h = str_replace('{GOODSID}', $value['goods_id'], $tr_h);
				$tr_h = str_replace('{GOODSNAME}', $goods_info['goods_name'], $tr_h);
				$tr_h = str_replace('{GOODSPRICE}', $goods_info['show_price'], $tr_h);
				$tr_h = str_replace('{GOODSSORT}', $value['sort'], $tr_h);

				$selected_list_data .= $tr_h;

				$selected_goods_ids[] = $value['goods_id'];
			}
		}

		$extend_js_data = '';
		// ajax 分页处理
		$select_count = count($selected_data);
		$extend_js_data = 'var select_count = '.$select_count.';';

		$each_num = 6;

		if ($fixture_tpl_info['sld_function']) {
			$fixture_layer_function_name = $fixture_tpl_info['sld_function'].'LayerStartData';
			$default_data = $this->$fixture_layer_function_name($each_num,$selected_goods_ids,$layer_tpl_list_item_html,$dataCondition);
		}

		$template_html = str_replace('{GOODSTITLE}', $top_title, $template_html);
		$template_html = str_replace('{PN}', $default_data['now_page'], $template_html);
		$template_html = str_replace('{EACHNUM}', $each_num, $template_html);
		$template_html = str_replace('{PAGINATION}', $default_data['page_show'], $template_html);
		$template_html = str_replace('{LISTDATA}', $default_data['list_data'], $template_html);
		$template_html = str_replace('{SELECTEDLISTDATA}', $selected_list_data, $template_html);
		$template_html = str_replace('{EXTENDJSDATA}', $extend_js_data, $template_html);
		$template_html = str_replace('{GOODSCATNAME}', $cat_name, $template_html);

		$template_html = str_replace('{SEARCHEXTENDFIELDS}', $searchextendfields_html, $template_html);

		$template_html = str_replace('{TOPTIPS}', $top_tips, $template_html);

		return $template_html;
	}
	// param allow_show_link_type_arr 允许展示的链接类型数组
	// param type 	(0 => 获取链接类型 1 => 获取可用 类型相关子选项)
	// param selected_id  (选中值)
	public function get_pic_link_type_html($allow_show_link_type_arr,$type,$selected_id=array()){
		$return_html = '';

		$show_link_type_arr = array(
			0 => array(
				'id' => 0,
				'name' => '自定义链接'
			),
			1 => array(
				'id' => 1,
				'name' => '商品分类'
			),
			2 => array(
				'id' => 2,
				'name' => '文章分类'
			),
			3 => array(
				'id' => 3,
				'name' => '品牌'
			),
			4 => array(
				'id' => 4,
				'name' => '专题'
			)
		);

		if ($type==0) {
			$piclinktypeshtml = '';
			if (is_array($selected_id) && !empty($selected_id)) {
				// 获取 图片链接类型
				$piclinktypeshtml .= '<select name="pic_link_type" class="pic_link_type form-control middle">';
				foreach ($allow_show_link_type_arr as $key => $value) {
					if (isset($show_link_type_arr[$value])) {
						$selected_flag = '';
						if ($selected_id[0] == $show_link_type_arr[$value]['id']) {
							$selected_flag = ' selected="selected"';
						}
						$piclinktypeshtml .= '<option'.$selected_flag.' value="'.$show_link_type_arr[$value]['id'].'">'.$show_link_type_arr[$value]['name'].'</option>';
					}
				}
				$piclinktypeshtml .= '</select>';
			}else{
				// 获取 图片链接类型
				$piclinktypeshtml .= '<select name=\"pic_link_type\" class=\"pic_link_type form-control middle\">';
				foreach ($allow_show_link_type_arr as $key => $value) {
					if (isset($show_link_type_arr[$value])) {
						$selected_flag = '';
						if ($select_id == $show_link_type_arr[$value]['id']) {
							$selected_flag = ' selected=\"selected\"';
						}
						$piclinktypeshtml .= '<option'.$selected_flag.' value=\"'.$show_link_type_arr[$value]['id'].'\">'.$show_link_type_arr[$value]['name'].'</option>';
					}
				}
				$piclinktypeshtml .= '</select>';
			}

			$return_html = $piclinktypeshtml;
		}else{
			$piclinktypesselecthtml = '';
			// 商品分类链接 select 内容
			/**
			 * 商品分类
			 */
			if (in_array(1,$allow_show_link_type_arr)) {
				$link_type_number = 1;
				if (is_array($selected_id) && !empty($selected_id)) {
					$show_flag = '';
					if ($selected_id[0] == $link_type_number) {
						$show_flag = ' style="display:block;"';
					}
					$piclinktypesselecthtml .= '<div'.$show_flag.' data-now_index="'.$link_type_number.'" class="pic_link_type_sub_select other_select_'.$link_type_number.'">';
					$piclinktypesselecthtml .= '<select class="form-control large pic_link_item_id">';
					$model_goods_class = Model('goods_class');
					$goods_class_list = $model_goods_class->getTreeClassList(3);
					if (is_array($goods_class_list)){
						foreach ($goods_class_list as $k => $v){
							$selected_flag = '';
							if ($selected_id[1] == $v['gc_id']) {
								$selected_flag = ' selected="selected"';
							}
							$now_name = '';
							$now_link = '';
							$goods_class_list[$k]['gc_name'] = $now_name = str_repeat("&nbsp;",$v['deep']*2).$v['gc_name'];
							$goods_class_list[$k]['link'] = $now_link = MALL_URL.'/index.php?app=goodslist&cid='.$v['gc_id'];
							$piclinktypesselecthtml .= '<option'.$selected_flag.' data-url="'.$now_link.'" value="'.$v['gc_id'].'">'.$now_name.'</option>';
						}
					}
			        $piclinktypesselecthtml .= '</select>';
			        $piclinktypesselecthtml .= '</div>';
				}else{
					$piclinktypesselecthtml .= '<div data-now_index=\"'.$link_type_number.'\" class=\"pic_link_type_sub_select other_select_'.$link_type_number.'\">';
					$piclinktypesselecthtml .= '<select class=\"form-control large pic_link_item_id\">';
					$model_goods_class = Model('goods_class');
					$goods_class_list = $model_goods_class->getTreeClassList(3);
					if (is_array($goods_class_list)){
						foreach ($goods_class_list as $k => $v){
							$selected_flag = '';
							if ($select_id == $v['gc_id']) {
								$selected_flag = ' selected=\"selected\"';
							}
							$now_name = '';
							$now_link = '';
							$goods_class_list[$k]['gc_name'] = $now_name = str_repeat("&nbsp;",$v['deep']*2).$v['gc_name'];
							$goods_class_list[$k]['link'] = $now_link = MALL_URL.'/index.php?app=goodslist&cid='.$v['gc_id'];
							$piclinktypesselecthtml .= '<option'.$selected_flag.' data-url=\"'.$now_link.'\" value=\"'.$v['gc_id'].'\">'.$now_name.'</option>';
						}
					}
			        $piclinktypesselecthtml .= '</select>';
			        $piclinktypesselecthtml .= '</div>';
				}
			}
			/**
			 * 文章分类
			 */
			if (in_array(2,$allow_show_link_type_arr)) {
				$link_type_number = 2;
				if (is_array($selected_id) && !empty($selected_id)) {
					$show_flag = '';
					if ($selected_id[0] == $link_type_number) {
						$show_flag = ' style="display:block;"';
					}
					$piclinktypesselecthtml .= '<div'.$show_flag.' data-now_index="'.$link_type_number.'" class="pic_link_type_sub_select other_select_'.$link_type_number.'">';
					$piclinktypesselecthtml .= '<select class="form-control large pic_link_item_id">';
					$model_article_class = Model('article_class');
					$article_class_list = $model_article_class->getTreeClassList(2);
					if (is_array($article_class_list)){
						foreach ($article_class_list as $k => $v){
							$selected_flag = '';
							if ($selected_id[1] == $v['acid']) {
								$selected_flag = ' selected="selected"';
							}
							$now_name = '';
							$now_link = '';
							$article_class_list[$k]['ac_name'] = $now_name = str_repeat("&nbsp;",$v['deep']*2).$v['ac_name'];
							$article_class_list[$k]['link'] = $now_link = MALL_URL.'/index.php?app=article&id='.$v['acid'];
							$piclinktypesselecthtml .= '<option'.$selected_flag.' data-url="'.$now_link.'" value="'.$v['acid'].'">'.$now_name.'</option>';
						}
					}
			        $piclinktypesselecthtml .= '</select>';
			        $piclinktypesselecthtml .= '</div>';
				}else{
					$piclinktypesselecthtml .= '<div data-now_index=\"'.$link_type_number.'\" class=\"pic_link_type_sub_select other_select_'.$link_type_number.'\">';
					$piclinktypesselecthtml .= '<select class=\"form-control large pic_link_item_id\">';
					$model_article_class = Model('article_class');
					$article_class_list = $model_article_class->getTreeClassList(2);
					if (is_array($article_class_list)){
						foreach ($article_class_list as $k => $v){
							$selected_flag = '';
							if ($select_id == $v['acid']) {
								$selected_flag = ' selected=\"selected\"';
							}
							$now_name = '';
							$now_link = '';
							$article_class_list[$k]['ac_name'] = $now_name = str_repeat("&nbsp;",$v['deep']*2).$v['ac_name'];
							$article_class_list[$k]['link'] = $now_link = MALL_URL.'/index.php?app=article&id='.$v['acid'];
							$piclinktypesselecthtml .= '<option'.$selected_flag.' data-url=\"'.$now_link.'\" value=\"'.$v['acid'].'\">'.$now_name.'</option>';
						}
					}
			        $piclinktypesselecthtml .= '</select>';
			        $piclinktypesselecthtml .= '</div>';
				}
			}

			/**
			 * 品牌
			 */
			if (in_array(3,$allow_show_link_type_arr)) {
				$link_type_number = 3;
				if (is_array($selected_id) && !empty($selected_id)) {
					$show_flag = '';
					if ($selected_id[0] == $link_type_number) {
						$show_flag = ' style="display:block;"';
					}
					$piclinktypesselecthtml .= '<div'.$show_flag.' data-now_index="'.$link_type_number.'" class="pic_link_type_sub_select other_select_'.$link_type_number.'">';
					$piclinktypesselecthtml .= '<select class="form-control large pic_link_item_id">';
					$brand_model = Model('brand');
					$brand_list = $brand_model->getBrandPassList(array(),'brand_id,brand_name');
					if (is_array($brand_list)){
						foreach ($brand_list as $k => $v){
							$selected_flag = '';
							if ($selected_id[1] == $v['brand_id']) {
								$selected_flag = ' selected="selected"';
							}
							$now_name = '';
							$now_link = '';
							$now_name = $v['brand_name'];
							$brand_list[$k]['link'] = $now_link = MALL_URL.'/index.php?app=brand&mod=lists&brand='.$v['brand_id'];
							$piclinktypesselecthtml .= '<option'.$selected_flag.' data-url="'.$now_link.'" value="'.$v['brand_id'].'">'.$now_name.'</option>';
						}
					}
			        $piclinktypesselecthtml .= '</select>';
			        $piclinktypesselecthtml .= '</div>';
				}else{
					$piclinktypesselecthtml .= '<div data-now_index=\"'.$link_type_number.'\" class=\"pic_link_type_sub_select other_select_'.$link_type_number.'\">';
					$piclinktypesselecthtml .= '<select class=\"form-control large pic_link_item_id\">';
					$brand_model = Model('brand');
					$brand_list = $brand_model->getBrandPassList(array(),'brand_id,brand_name');
					if (is_array($brand_list)){
						foreach ($brand_list as $k => $v){
							$selected_flag = '';
							if ($select_id == $v['brand_id']) {
								$selected_flag = ' selected=\"selected\"';
							}
							$now_name = '';
							$now_link = '';
							$now_name = $v['brand_name'];
							$brand_list[$k]['link'] = $now_link = MALL_URL.'/index.php?app=brand&mod=lists&brand='.$v['brand_id'];
							$piclinktypesselecthtml .= '<option'.$selected_flag.' data-url=\"'.$now_link.'\" value=\"'.$v['brand_id'].'\">'.$now_name.'</option>';
						}
					}
			        $piclinktypesselecthtml .= '</select>';
			        $piclinktypesselecthtml .= '</div>';
				}
			}

			/**
			 * 专题
			 */
			if (in_array(4,$allow_show_link_type_arr)) {
				$link_type_number = 4;
				if (is_array($selected_id) && !empty($selected_id)) {
					$show_flag = '';
					if ($selected_id[0] == $link_type_number) {
						$show_flag = ' style="display:block;"';
					}
					$piclinktypesselecthtml .= '<div'.$show_flag.' data-now_index="'.$link_type_number.'" class="pic_link_type_sub_select other_select_'.$link_type_number.'">';
					$piclinktypesselecthtml .= '<select class="form-control large pic_link_item_id">';
					$topic_model = Model('web_home_page');
					$topic_condition['sld_page_type'] = 5;
					$topic_condition['sld_is_vaild'] = 1;
					$topic_list = $topic_model->getTemplatesList($topic_condition,'');
					if (is_array($topic_list)){
						foreach ($topic_list as $k => $v){
							$selected_flag = '';
							if ($selected_id[1] == $v['id']) {
								$selected_flag = ' selected="selected"';
							}
							$now_name = '';
							$now_link = '';
							$now_name = $v['sld_page_name'];
							$topic_list[$k]['link'] = $now_link = MALL_URL.'/index.php?app=topic&id='.$v['id'];
							$piclinktypesselecthtml .= '<option'.$selected_flag.' data-url="'.$now_link.'" value="'.$v['id'].'">'.$now_name.'</option>';
						}
					}
			        $piclinktypesselecthtml .= '</select>';
			        $piclinktypesselecthtml .= '</div>';
				}else{
					$piclinktypesselecthtml .= '<div data-now_index=\"'.$link_type_number.'\" class=\"pic_link_type_sub_select other_select_'.$link_type_number.'\">';
					$piclinktypesselecthtml .= '<select class=\"form-control large pic_link_item_id\">';
					$topic_model = Model('web_home_page');
					$topic_condition['sld_page_type'] = 5;
					$topic_condition['sld_is_vaild'] = 1;
					$topic_list = $topic_model->getTemplatesList($topic_condition,'');
					if (is_array($topic_list)){
						foreach ($topic_list as $k => $v){
							$selected_flag = '';
							if ($select_id == $v['id']) {
								$selected_flag = ' selected=\"selected\"';
							}
							$now_name = '';
							$now_link = '';
							$now_name = $v['sld_page_name'];
							$topic_list[$k]['link'] = $now_link = MALL_URL.'/index.php?app=topic&id='.$v['id'];
							$piclinktypesselecthtml .= '<option'.$selected_flag.' data-url=\"'.$now_link.'\" value=\"'.$v['id'].'\">'.$now_name.'</option>';
						}
					}
			        $piclinktypesselecthtml .= '</select>';
			        $piclinktypesselecthtml .= '</div>';
				}
			}

			$return_html = $piclinktypesselecthtml;
		}

		return $return_html;
	}
	public function fixture_edit_layer_pic($tpl_data,$layData,$fixture_tpl_info,$template_html)
	{
		$top_tips = '';

		// 允许展示的链接类型
		$allow_show_link_type_arr = array(0,1,2,3,4);
		$piclinktypeshtml = '';
		$piclinktypesselecthtml = '';
		
		$layer_tpl_selected_list_item_html = $fixture_tpl_info['sld_selected_list_item'];
		$layer_tpl_list_item_html = $fixture_tpl_info['sld_list_item'];

		$dataCondition = array();
		if ($layData['shop_id']) {
			$dataCondition['vid'] = $layData['shop_id'];
		}

		// 获取装修数据
		$tpl_data_data = unserialize($tpl_data['sld_tpl_data']);

		// 扩展信息
		$cat_name = $tpl_data_data['extend'][$layData['data_name']]['cat_name'] ? $tpl_data_data['extend'][$layData['data_name']]['cat_name'] : '';
		$bg_color = $tpl_data_data['extend'][$layData['data_name']]['bg_color'] ? $tpl_data_data['extend'][$layData['data_name']]['bg_color'] : '';

		$has_layer_title = isset($layData['has_title']) ? $layData['has_title'] : 0;
		// 图片标题
		$has_layer_item_title = isset($layData['has_item_title']) ? $layData['has_item_title'] : 0;
		// 图片子标题
		$has_layer_item_sub_title = isset($layData['has_item_sub_title']) ? $layData['has_item_sub_title'] : 0;
		// 颜色选择器
		$has_layer_bg_color = isset($layData['has_bg_color']) ? $layData['has_bg_color'] : 0;
		$top_title = '';
		if ($has_layer_title) {
			$top_title_html = '<div class="table-content m-t-10 clearfix"><div class="simple-form-field"><div class="form-group"><label for="text4" class="col-sm-3 control-label"><span class="ng-binding"><span class="text-danger ng-binding">*</span>分类名称：</span></label><div class="col-sm-7"><div class="form-control-box"><input class="form-control" type="text" value="{GOODSCATNAME}" id="cat_name"></div><div class="help-block help-block-t"><div class="help-block help-block-t">标题不能为空，最多输入{MAXNUMBER}个字</div></div></div></div></div>';

			$top_title = $top_title_html;
			
		}
		$item_title = '';
		$js_item_title = '';
		if ($has_layer_item_title) {
			$item_title_html = '<input class="form-control pic_title w180" type="text" name="pic_title" value="{PICTITLE}" placeholder="输入标题"><br/>';
			$js_item_title_html = '<input class=\"form-control pic_title w180\" type=\"text\" name=\"pic_title\" value=\"\" placeholder=\"输入标题\"><br/>';

			$item_title = $item_title_html;
			$js_item_title = $js_item_title_html;
		}
		$item_sub_title = '';
		$js_item_sub_title = '';
		if ($has_layer_item_sub_title) {
			$item_sub_title_html = '<input class="form-control pic_sub_title w180" type="text" name="pic_sub_title" value="{PICSUBTITLE}" placeholder="输入子标题"><br/>';
			$js_item_sub_title_html = '<input class=\"form-control pic_sub_title w180\" type=\"text\" name=\"pic_sub_title\" value=\"\" placeholder=\"输入子标题\"><br/>';

			$item_sub_title = $item_sub_title_html;
			$js_item_sub_title = $js_item_sub_title_html;
		}
		if ($has_layer_bg_color) {
			$layer_bg_color_html = '<div class="simple-form-field"><div class="form-group"><label for="text4" class="col-sm-4 control-label"><span class="ng-binding">{BGCOLORFIELDNAME}：</span></label><div class="col-sm-6"><div><span><input class="form-control w180" type="text" style="display:none;" id="bg_color" name="bg_color" value="{BGCOLORVAL}" placeholder="输入{BGCOLORFIELDNAME}"><span class="fzkb_picker bg_color" style="background-color:#{BGCOLORVAL};"></span></span></div></div></div></div>';
			$layer_bg_color_html .= '<link href="/static/colpick/css/colpick.css" rel="stylesheet" />
			<script src="/static/colpick/js/colpick.js"></script>';

			$layer_bg_color_html .='
			<script>
			$(".bg_color").colpick({onChange: function () {
				var index_v = $(".fzkb_picker").index($(".bg_color"));
			    var colos = $(".colpick_hex_field input").eq(index_v).val();
			    $(".bg_color").css("background-color","#"+colos);
			    $("#bg_color").val(colos);
			}});
			</script>';

			$layer_bg_color = $layer_bg_color_html;
		}

		if ($layData['has_top_tips']) {
			$layer_tpl_top_tips_html = $fixture_tpl_info['sld_tpl_top_tips'];
			$top_tips_html = '<li><span>为达到页面效果，建议上传{MAXNUMBER}张 {PICWIDTH}*{PICHEIGHT}大小的图片</span></li><li><span>图片链接填写时，请带上 https:// 或 http:// 。</span></li>';

			if ($has_layer_title) {
				$top_tips_html .= '<li><span>分类名称不能为空，最多输入{MAXNUMBER}个字</span></li>';
			}

			$layer_tpl_top_tips_html = str_replace('{LAYERTOPTIPSLIST}', $top_tips_html, $layer_tpl_top_tips_html);

			$top_tips = $layer_tpl_top_tips_html;
		}

		// 链接类型 获取 html 代码 （写成方法）
		$piclinktypeshtml = $this->get_pic_link_type_html($allow_show_link_type_arr,0);
		$piclinktypesselecthtml = $this->get_pic_link_type_html($allow_show_link_type_arr,1);

		$picfilter = '';
		if ($layData['shop_id']) {
			// 图片 相册 过滤
			$model_album = Model('imagespace');

			/**
			 * 验证是否存在默认相册
			 */
			$return = $model_album->checkAlbum(array('album_aclass.vid'=>$layData['shop_id'],'is_default'=>'1'));
			if(!$return){
				$album_arr = array();
				$album_arr['aclass_name'] = Language::get('默认相册');
				$album_arr['vid'] = $layData['shop_id'];
				$album_arr['aclass_des'] = '';
				$album_arr['aclass_sort'] = '255';
				$album_arr['aclass_cover'] = '';
				$album_arr['upload_time'] = time();
				$album_arr['is_default'] = '1';
				$model_album->addClass($album_arr);
			}

			// 获取 相册 
			$param['album_aclass.vid']	= $layData['shop_id'];
			$param['order']					= 'aclass_sort desc';
			$aclass_info = $model_album->getClassList($param);

			$default_album = 0;
			$picfilter .= '<select id="pic_album">';
			foreach ($aclass_info as $key => $value) {
				if ($value['is_default']) {
					$default_album = $value['aclass_id'];
					$picfilter .= '<option value="'.$value['aclass_id'].'">';
				}else{
					$picfilter .= '<option value="'.$value['aclass_id'].'">';
				}
				$picfilter .= $value['aclass_name'];
				$picfilter .= '</option>';
			}
			$picfilter .= '</select>';
			$picfilter .= '<input type="hidden" id="album_id" value="'.$default_album.'">';

			$dataCondition['album_id'] = $default_album;
		}

		// 获取装修数据
		$tpl_data_data = unserialize($tpl_data['sld_tpl_data']);

		$selected_data = $tpl_data_data[$layData['data_name']];

		$model_fixture_pic = Model('web_home');

		$selected_goods_ids = array();
		$selected_list_data = '';
		if ($selected_data) {

			foreach ($selected_data as $key => $value) {
				$itempiclinktypeshtml = '';
				$itempiclinktypesselecthtml = '';

				$tr_h = $layer_tpl_selected_list_item_html;

				// 获取信息
				if ($layData['shop_id']) {
					$pic_info = $model_album->getOne(array('apic_id'=>$value['pic_id']));
					$tr_h = str_replace('{PICSRC}', thumb($pic_info, 'real'), $tr_h);
				}else{
					$pic_info = $model_fixture_pic->getFixturePicInfo(array('id'=>$value['pic_id']),array('id','sld_pic_name'));
					$tr_h = str_replace('{PICSRC}', $this->fixture_pic_url($pic_info['sld_pic_name']), $tr_h);
				}

				$tr_h = str_replace('{PICITEMTITLE}', $item_title, $tr_h);
				$tr_h = str_replace('{PICITEMSUBTITLE}', $item_sub_title, $tr_h);

				$tr_h = str_replace('{PICID}', $value['pic_id'], $tr_h);
				$tr_h = str_replace('{PICLINK}', $value['links'], $tr_h);
				$tr_h = str_replace('{PICSORT}', $value['sort'], $tr_h);
				$tr_h = str_replace('{PICTITLE}', $value['pic_title'] ? $value['pic_title'] : '', $tr_h);
				$tr_h = str_replace('{PICSUBTITLE}', $value['pic_sub_title'] ? $value['pic_sub_title'] : '', $tr_h);
				
				$item_selected_id = array($value['link_type'],$value['link_type_item_id']);
				$itempiclinktypeshtml = $this->get_pic_link_type_html($allow_show_link_type_arr,0,$item_selected_id);
				$itempiclinktypesselecthtml = $this->get_pic_link_type_html($allow_show_link_type_arr,1,$item_selected_id);

				$pic_link_hide = $value['link_type_item_id'] ? true : false ;
				$pic_link_hide_html = $pic_link_hide ? ' style="display:none; "' : '';

				$tr_h = str_replace('{PICLINKHIDE}', $pic_link_hide_html, $tr_h);

		        // 链接类型 select
		        $tr_h = str_replace('{ITEMPICLINKTYPESHTML}', $itempiclinktypeshtml, $tr_h);
		        $tr_h = str_replace('{ITEMPICLINKTYPESSELECTHTML}', $itempiclinktypesselecthtml, $tr_h);

				$selected_list_data .= $tr_h;

			}
		}

		$extend_js_data = '';
		// ajax 分页处理
		$select_count = count($selected_data);
		$extend_js_data = 'var select_count = '.$select_count.';';

		$each_num = 12;

		if ($fixture_tpl_info['sld_function']) {
			$fixture_layer_function_name = $fixture_tpl_info['sld_function'].'LayerStartData';
			$default_data = $this->$fixture_layer_function_name($each_num,$selected_goods_ids,$layer_tpl_list_item_html,$dataCondition);
		}

		$template_html = str_replace('{GOODSTITLE}', $top_title, $template_html);
		$template_html = str_replace('{PICFILTER}', $picfilter, $template_html);
		$template_html = str_replace('{PN}', $default_data['now_page'], $template_html);
		$template_html = str_replace('{EACHNUM}', $each_num, $template_html);
		$template_html = str_replace('{PAGINATION}', $default_data['page_show'], $template_html);
		$template_html = str_replace('{LISTDATA}', $default_data['list_data'], $template_html);
		$template_html = str_replace('{SELECTEDLISTDATA}', $selected_list_data, $template_html);
		$template_html = str_replace('{EXTENDJSDATA}', $extend_js_data, $template_html);
		$template_html = str_replace('{GOODSCATNAME}', $cat_name, $template_html);
		$template_html = str_replace('{BGCOLOR}', $layer_bg_color, $template_html);
		$template_html = str_replace('{BGCOLORVAL}', $bg_color, $template_html);

        // 链接类型 select
        $template_html = str_replace('{PICLINKTYPESHTML}', $piclinktypeshtml, $template_html);
        $template_html = str_replace('{PICLINKTYPESSELECTHTML}', $piclinktypesselecthtml, $template_html);

		$template_html = str_replace('{PICITEMTITLE}', $js_item_title, $template_html);
		$template_html = str_replace('{PICITEMSUBTITLE}', $js_item_sub_title, $template_html);
		
		$template_html = str_replace('{TOPTIPS}', $top_tips, $template_html);

		return $template_html;
	}
	public function fixture_edit_layer_title($tpl_data,$layData,$fixture_tpl_info,$template_html)
	{
		// 获取装修模板的数据
		$tpl_data_data = unserialize($tpl_data['sld_tpl_data']);

		$tpl_t_data = $tpl_data_data[$layData['data_name']];

		$top_tips = '';
		
		$layer_tpl_selected_list_item_html = $fixture_tpl_info['sld_selected_list_item'];
		$layer_tpl_list_item_html = $fixture_tpl_info['sld_list_item'];

		$dataCondition = array();
		if ($layData['shop_id']) {
			$dataCondition['vid'] = $layData['shop_id'];
		}

		$has_layer_link = isset($layData['has_link']) ? $layData['has_link'] : 0;
		$has_layer_sub_title = isset($layData['has_sub_title']) ? $layData['has_sub_title'] : 0;
		$has_layer_bg_color = isset($layData['has_bg_color']) ? $layData['has_bg_color'] : 0;
		$has_layer_font_color = isset($layData['has_font_color']) ? $layData['has_font_color'] : 0;
		$layer_link = '';
		$layer_sub_title = '';
		if ($has_layer_link) {

			// 允许展示的链接类型
			$allow_show_link_type_arr = array(0,1,2,3,4);
			$piclinktypeshtml = '';
			$piclinktypesselecthtml = '';

			$link_type = $tpl_t_data[0]['link_type'] ? $tpl_t_data[0]['link_type'] : 0;
			$link_type_item_id = $tpl_t_data[0]['link_type_item_id'] ? $tpl_t_data[0]['link_type_item_id'] : 0;

			$selected_liks_id = array($link_type,$link_type_item_id);
			// 链接类型 获取 html 代码 （写成方法）
			$piclinktypeshtml = $this->get_pic_link_type_html($allow_show_link_type_arr,0,$selected_liks_id);
			$piclinktypesselecthtml = $this->get_pic_link_type_html($allow_show_link_type_arr,1,$selected_liks_id);

			$layer_link_html = '<div class="simple-form-field"><div class="form-group"><label for="text4" class="col-sm-4 control-label"><span class="ng-binding">链接：</span></label><div class="col-sm-9"><div><span id="link_change">{ITEMPICLINKTYPESHTML}<input class="form-control w180"{PICLINKHIDE} type="text" id="title_link" name="link" value="{TITLELINKVAL}" placeholder="输入链接地址">{ITEMPICLINKTYPESSELECTHTML}</span></div></div></div></div>';

			$layer_link_html .= '
			<script>
			function reload_url_to_input(now_index,td_obj){
			var now_url = "";
			if(now_index){
			now_url = td_obj.find(".pic_link_type_sub_select.other_select_"+now_index).find("select > option:selected").data("url");
			}
			td_obj.find("#title_link").val(now_url);
			}
			$("#{LAYERTEMPLATEID}").on("change",".pic_link_item_id",function(e){
			var now_index = $(this).parents(".pic_link_type_sub_select").data("now_index");
			var td_obj = $(this).parents("div");
			reload_url_to_input(now_index,td_obj);
			});
			$("#{LAYERTEMPLATEID}").on("change",".pic_link_type",function(e){
			var now_index = $(this).val();
			var td_obj = $(this).parents("div");
			td_obj.find(".pic_link_type_sub_select").hide();
			if(now_index > 0){
			td_obj.find("#title_link").hide();
			td_obj.find(".pic_link_type_sub_select.other_select_"+now_index).show();
			}else{
			td_obj.find("#title_link").show();
			}
			reload_url_to_input(now_index,td_obj);
			});
			</script>';

			$link_hide = $link_type_item_id ? true : false ;
			$link_hide_html = $link_hide ? ' style="display:none; "' : '';

			$layer_link_html = str_replace('{PICLINKHIDE}', $link_hide_html, $layer_link_html);

	        // 链接类型 select
	        $layer_link_html = str_replace('{ITEMPICLINKTYPESHTML}', $piclinktypeshtml, $layer_link_html);
	        $layer_link_html = str_replace('{ITEMPICLINKTYPESSELECTHTML}', $piclinktypesselecthtml, $layer_link_html);

			$layer_link = $layer_link_html;
		}
		if ($has_layer_sub_title) {
			$layer_sub_title_html = '<div class="simple-form-field"><div class="form-group"><label for="text4" class="col-sm-4 control-label"><span class="text-danger ng-binding">*</span><span class="ng-binding">{SUBTITLEFIELDNAME}：</span></label><div class="col-sm-9"><div><span id="link_change"><input class="form-control w180" type="text" id="sub_title" name="sub_title" value="{SUBTITLEVAL}" placeholder="输入{SUBTITLEFIELDNAME}"></span></div><div class="help-block help-block-t"><div class="help-block help-block-t">{SUBTITLEFIELDNAME}不能为空，最多输入{MAXSUBNUMBER}个字</div></div></div></div></div>';

			$layer_sub_title = $layer_sub_title_html;
		}
		if ($has_layer_bg_color) {
			$layer_bg_color_html = '<div class="simple-form-field"><div class="form-group"><label for="text4" class="col-sm-4 control-label"><span class="ng-binding">{BGCOLORFIELDNAME}：</span></label><div class="col-sm-9"><div><span><input class="form-control w180" type="text" style="display:none;" id="bg_color" name="bg_color" value="{BGCOLORVAL}" placeholder="输入{BGCOLORFIELDNAME}"><span class="fzkb_picker bg_color" style="background-color:#{BGCOLORVAL};"></span></span></div></div></div></div>';
			$layer_bg_color_html .= '<link href="/static/colpick/css/colpick.css" rel="stylesheet" />
			<script src="/static/colpick/js/colpick.js"></script>';

			$layer_bg_color_html .='
			<script>
			$(".bg_color").colpick({onChange: function () {
				var index_v = $(".fzkb_picker").index($(".bg_color"));
			    var colos = $(".colpick_hex_field input").eq(index_v).val();
			    $(".bg_color").css("background-color","#"+colos);
			    $("#bg_color").val(colos);
			}});
			</script>';

			$layer_bg_color = $layer_bg_color_html;
		}
		if ($has_layer_font_color) {
			$layer_font_color_html = '<div class="simple-form-field"><div class="form-group"><label for="text4" class="col-sm-4 control-label"><span class="ng-binding">{FONTCOLORFIELDNAME}：</span></label><div class="col-sm-9"><div><span><input class="form-control w180" type="text" style="display:none;" id="font_color" name="font_color" value="{FONTCOLORVAL}" placeholder="输入{FONTCOLORFIELDNAME}"><span class="fzkb_picker layer_font_color" data-colpickId="666" style="background-color:#{FONTCOLORVAL};"></span></span></div></div></div></div>';
			$layer_font_color_html .= '<link href="/static/colpick/css/colpick.css" rel="stylesheet" />
			<script src="/static/colpick/js/colpick.js"></script>';

			$layer_font_color_html .='
			<script>
			$(".layer_font_color").colpick({onChange: function () {
				var index_v = $(".fzkb_picker").index($(".layer_font_color"));
			    var colos = $(".colpick_hex_field input").eq(index_v).val();
			    $(".layer_font_color").css("background-color","#"+colos);
			    $("#font_color").val(colos);
			}});
			</script>';

			$layer_font_color = $layer_font_color_html;
		}

		// 是否有温馨提示
		if ($layData['has_top_tips']) {
			$layer_tpl_top_tips_html = $fixture_tpl_info['sld_tpl_top_tips'];

			if ($tpl_data['sld_tpl_code'] == 'nav_login') {
				$top_tips_html = '<li><span>如果你希望登录后显示用户名请用“{0}”代替用户名。例：欢迎{0}来到商城</span></li>';
			}else{
				$top_tips_html = '<li><span>标题不能为空，最多输入{MAXNUMBER}个字。</span></li>';
				if ($has_layer_sub_title) {
					$top_tips_html .= '<li><span>{SUBTITLEFIELDNAME}不能为空，最多输入{MAXSUBNUMBER}个字。</span></li>';
				}
			}

			$layer_tpl_top_tips_html = str_replace('{LAYERTOPTIPSLIST}', $top_tips_html, $layer_tpl_top_tips_html);

			$top_tips = $layer_tpl_top_tips_html;
		}

		$welcome_string = $tpl_t_data[0]['name'] ? $tpl_t_data[0]['name'] : '';
		$title_link = $tpl_t_data[0]['title_link'] ? $tpl_t_data[0]['title_link'] : '';
		$sub_title = $tpl_t_data[0]['sub_title'] ? $tpl_t_data[0]['sub_title'] : '';
		$bg_color = $tpl_t_data[0]['bg_color'] ? $tpl_t_data[0]['bg_color'] : '';
		$font_color = $tpl_t_data[0]['font_color'] ? $tpl_t_data[0]['font_color'] : '';

		$template_html = str_replace('{TITLELINK}', $layer_link, $template_html);
		$template_html = str_replace('{TITLELINKVAL}', $title_link, $template_html);
		$template_html = str_replace('{SUBTITLE}', $layer_sub_title, $template_html);
		$template_html = str_replace('{SUBTITLEVAL}', $sub_title, $template_html);
		$template_html = str_replace('{BGCOLOR}', $layer_bg_color, $template_html);
		$template_html = str_replace('{BGCOLORVAL}', $bg_color, $template_html);
		$template_html = str_replace('{FONTCOLOR}', $layer_font_color, $template_html);
		$template_html = str_replace('{FONTCOLORVAL}', $font_color, $template_html);
		$template_html = str_replace('{WELCOMESTRING}', $welcome_string, $template_html);
		
		$template_html = str_replace('{TOPTIPS}', $top_tips, $template_html);

		return $template_html;
	}
	public function fixture_edit_layer_brand($tpl_data,$layData,$fixture_tpl_info,$template_html)
	{
		$top_tips = '';
		
		$layer_tpl_selected_list_item_html = $fixture_tpl_info['sld_selected_list_item'];
		$layer_tpl_list_item_html = $fixture_tpl_info['sld_list_item'];

		$dataCondition = array();
		if ($layData['shop_id']) {
			$dataCondition['vid'] = $layData['shop_id'];
		}

		// 是否有温馨提示
		if ($layData['has_top_tips']) {
			$layer_tpl_top_tips_html = $fixture_tpl_info['sld_tpl_top_tips'];

			// 未上传品牌LOGO的品牌无法显示在品牌列表中，
			$top_tips_html = '<li><span>最多可以选择{MAXNUMBER}个品牌</span></li>';

			$layer_tpl_top_tips_html = str_replace('{LAYERTOPTIPSLIST}', $top_tips_html, $layer_tpl_top_tips_html);

			$top_tips = $layer_tpl_top_tips_html;
		}

		// 获取品牌列表
		$model_brand = Model();

		// 获取装修数据
		$tpl_data_data = unserialize($tpl_data['sld_tpl_data']);

		$selected_data = $tpl_data_data['brand'];

		$selected_brand_ids = array();
		$selected_list_data = '';
		if ($selected_data) {
			foreach ($selected_data as $key => $value) {
				// 获取信息
				$shop_info = $model_brand->table('brand')->find($value['brand_id']);

				$tr_h = $layer_tpl_selected_list_item_html;

				$tr_h = str_replace('{BRANDID}', $value['brand_id'], $tr_h);
				$tr_h = str_replace('{BRANDSRC}', brandImage($shop_info['brand_pic']), $tr_h);
				$tr_h = str_replace('{BRANDNAME}', $shop_info['brand_name'], $tr_h);
				$tr_h = str_replace('{BRANDSORT}', $value['sort'], $tr_h);

				$selected_list_data .= $tr_h;

				$selected_brand_ids[] = $value['brand_id'];
			}
		}

		$extend_js_data = '';
		// ajax 分页处理
		$select_count = count($selected_data);
		$extend_js_data = 'var select_count = '.$select_count.';';

		$each_num = 6;

		if ($fixture_tpl_info['sld_function']) {
			$fixture_layer_function_name = $fixture_tpl_info['sld_function'].'LayerStartData';
			$default_data = $this->$fixture_layer_function_name($each_num,$selected_brand_ids,$layer_tpl_list_item_html,$dataCondition);
		}

		$template_html = str_replace('{PN}', $default_data['now_page'], $template_html);
		$template_html = str_replace('{EACHNUM}', $each_num, $template_html);
		$template_html = str_replace('{PAGINATION}', $default_data['page_show'], $template_html);
		$template_html = str_replace('{LISTDATA}', $default_data['list_data'], $template_html);
		$template_html = str_replace('{SELECTEDLISTDATA}', $selected_list_data, $template_html);
		$template_html = str_replace('{EXTENDJSDATA}', $extend_js_data, $template_html);
		
		$template_html = str_replace('{TOPTIPS}', $top_tips, $template_html);

		return $template_html;
	}
	public function fixture_edit_layer_goodscates($tpl_data,$layData,$fixture_tpl_info,$template_html)
	{
		$top_tips = '';
		
		// 默认三级可点
		$level_deep = $layData['level_deep'] ? $layData['level_deep'] : 3;

		$layer_tpl_selected_list_item_html = $fixture_tpl_info['sld_selected_list_item'];
		$layer_tpl_list_item_html = $fixture_tpl_info['sld_list_item'];

		$dataCondition = array();
		if ($layData['shop_id']) {
			$dataCondition['vid'] = $layData['shop_id'];
		}

		$has_layer_title = isset($layData['has_title']) ? $layData['has_title'] : 0;
		$top_title = '';
		if ($has_layer_title) {
			$top_title_html = '<div class="table-content m-t-10 clearfix"><div class="simple-form-field"><div class="form-group"><label for="text4" class="col-sm-3 control-label"><span class="ng-binding"><span class="text-danger ng-binding">*</span>分类标题：</span></label><div class="col-sm-7"><div class="form-control-box"><input class="form-control" type="text" value="{GOODSCATNAME}" id="cat_name"></div><div class="help-block help-block-t"><div class="help-block help-block-t">分类标题不能为空，最多输入{MAXNUMBER}个字</div></div></div></div></div>';

			$top_title = $top_title_html;
			
		}

		// 是否有温馨提示
		if ($layData['has_top_tips']) {
			$layer_tpl_top_tips_html = $fixture_tpl_info['sld_tpl_top_tips'];

			if ($has_layer_title) {
				$top_tips_html = '<li><span>为达到页面效果，建议上传{MAXITEMNUMBER}个分类，分类名称不超过{MAXNUMBER}个字</span></li>';
			}else{
				$top_tips_html = '<li><span>为达到页面效果，建议上传{MAXITEMNUMBER}个分类</span></li>';
			}

			$layer_tpl_top_tips_html = str_replace('{LAYERTOPTIPSLIST}', $top_tips_html, $layer_tpl_top_tips_html);

			$top_tips = $layer_tpl_top_tips_html;
		}

		$model_class = Model('goods_class');

		// 获取装修数据
		$tpl_data_data = unserialize($tpl_data['sld_tpl_data']);

		$selected_data = $tpl_data_data['category'];

		// 扩展信息
		$cat_name = $tpl_data_data['extend'][$layData['data_name']]['cat_name'] ? $tpl_data_data['extend'][$layData['data_name']]['cat_name'] : '';

		$selected_brand_ids = array();
		$selected_list_data = '';
		if ($selected_data) {
			foreach ($selected_data as $key => $value) {
				// 获取信息
				$category_info = $model_class->getGoodsClassList(array('gc_id' => $value['cat_id']), 'gc_id,gc_name');

				$tr_h = $layer_tpl_selected_list_item_html;

				$tr_h = str_replace('{CATEID}', $value['cat_id'], $tr_h);
				$tr_h = str_replace('{CATENAME}', $category_info[0]['gc_name'], $tr_h);
				$tr_h = str_replace('{CATESORT}', $value['sort'], $tr_h);

				$selected_list_data .= $tr_h;
			}
		}

		$extend_js_data = '';

		$select_count = count($selected_data);
		$extend_js_data = 'var select_count = '.$select_count.';';

		// 一级分类列表
		$parent_id = 0;
		$tmp_list = $model_class->getTreeClassList(3);
		if (is_array($tmp_list)){
			foreach ($tmp_list as $k => $v){
				if ($v['gc_parent_id'] == $parent_id){
					//判断是否有子类
					if ($tmp_list[$k+1]['deep'] > $v['deep']){
						$v['have_child'] = 1;
					}
					$class_list[] = $v;
				}
			}
		}

		$categoryTopListHtml = '';

		foreach ($class_list as $key => $value) {
			if ($level_deep > 1) {
				$value['have_child'] = $value['have_child'] ? 1 : 0;
			}else{
				$value['have_child'] = 0;
			}
			$categoryTopListHtml .= '<li class="">';
			$categoryTopListHtml .= '<a href="javascript:void(0)" class="category-name" data-has_child="'.$value['have_child'].'" data-id="'.$value['gc_id'].'" data-name="'.$value['gc_name'].'" data-level="1">';
			if ($value['have_child']) {
				$categoryTopListHtml .= '<i class="fa fa-angle-right"></i>';	
			}
			$categoryTopListHtml .= $value['gc_name'];
			$categoryTopListHtml .= '</a>';
			$categoryTopListHtml .= '</li>';
			
		}


		$template_html = str_replace('{GOODSTITLE}', $top_title, $template_html);
		$template_html = str_replace('{SELECTEDLISTDATA}', $selected_list_data, $template_html);
		$template_html = str_replace('{EXTENDJSDATA}', $extend_js_data, $template_html);
		$template_html = str_replace('{CATEGORYTOPLIST}', $categoryTopListHtml, $template_html);
		$template_html = str_replace('{GOODSCATNAME}', $cat_name, $template_html);
		
		$template_html = str_replace('{TOPTIPS}', $top_tips, $template_html);

		return $template_html;
	}
	public function fixture_edit_layer_shop($tpl_data,$layData,$fixture_tpl_info,$template_html)
	{
		$top_tips = '';

		$layer_tpl_selected_list_item_html = $fixture_tpl_info['sld_selected_list_item'];
		$layer_tpl_list_item_html = $fixture_tpl_info['sld_list_item'];

		$dataCondition = array();
		if ($layData['shop_id']) {
			$dataCondition['vid'] = $layData['shop_id'];
		}
		if ($layData['city_id']) {
			$dataCondition['city_id'] = $layData['city_id'];
		}
		// 根据所属页面类型 增加数据过滤条件
		switch ($tpl_data['sld_page']) {
			case 's_index':
				$dataCondition['is_supplier'] = 1;
				$condition['sld_is_supplier'] = 1;
				break;
		}

		// 是否有温馨提示
		if ($layData['has_top_tips']) {
			$layer_tpl_top_tips_html = $fixture_tpl_info['sld_tpl_top_tips'];

			$top_tips_html = '<li><span>您最多可以添加个{MAXNUMBER}店铺</span></li>';

			$layer_tpl_top_tips_html = str_replace('{LAYERTOPTIPSLIST}', $top_tips_html, $layer_tpl_top_tips_html);

			$top_tips = $layer_tpl_top_tips_html;
		}

		// 获取店铺列表
		$model_store = Model('vendor');

		//店铺等级
		$grade_names = array();
		$model_grade = Model('store_grade');
		$grade_list = $model_grade->getGradeList($condition);
		foreach ($grade_list as $key => $value) {
			$grade_names[$value['sg_id']] = $value['sg_name'];
		}

		// 获取装修数据
		$tpl_data_data = unserialize($tpl_data['sld_tpl_data']);

		$selected_data = $tpl_data_data['store'];

		$selected_list_data = '';
		$selected_shop_ids = array();

		if ($selected_data) {
			foreach ($selected_data as $key => $value) {
				// 获取信息
				$shop_info = $model_store->getStoreOnlineInfoByID($value['shop_id']);

				$tr_h = $layer_tpl_selected_list_item_html;

				$tr_h = str_replace('{SHOPID}', $value['shop_id'], $tr_h);
				$tr_h = str_replace('{SHOPNAME}', $shop_info['store_name'], $tr_h);
				$tr_h = str_replace('{SHOPLEVEL}', $grade_names[$shop_info['grade_id']], $tr_h);
				$tr_h = str_replace('{SHOPLOGO}', getStoreLogo($shop_info['store_label']), $tr_h);
				$tr_h = str_replace('{SHOPSORT}', $value['sort'], $tr_h);

				$selected_list_data .= $tr_h;

				$selected_shop_ids[] = $value['shop_id'];
			}
		}

		$extend_js_data = '';
		// ajax 分页处理
		$select_count = count($selected_data);
		$extend_js_data = 'var select_count = '.$select_count.';';

		$each_num = 6;

		if ($fixture_tpl_info['sld_function']) {
			$fixture_layer_function_name = $fixture_tpl_info['sld_function'].'LayerStartData';
			$default_data = $this->$fixture_layer_function_name($each_num,$selected_shop_ids,$layer_tpl_list_item_html,$dataCondition);
		}

		$template_html = str_replace('{PN}', $default_data['now_page'], $template_html);
		$template_html = str_replace('{EACHNUM}', $each_num, $template_html);
		$template_html = str_replace('{PAGINATION}', $default_data['page_show'], $template_html);
		$template_html = str_replace('{LISTDATA}', $default_data['list_data'], $template_html);
		$template_html = str_replace('{SELECTEDLISTDATA}', $selected_list_data, $template_html);
		$template_html = str_replace('{EXTENDJSDATA}', $extend_js_data, $template_html);
		
		$template_html = str_replace('{TOPTIPS}', $top_tips, $template_html);

		return $template_html;
	}
	public function fixture_edit_layer_backup($tpl_data,$layData,$fixture_tpl_info,$template_html)
	{
		$top_tips = '';

		$layer_tpl_selected_list_item_html = $fixture_tpl_info['sld_selected_list_item'];
		$layer_tpl_list_item_html = $fixture_tpl_info['sld_list_item'];

		// 获取 已备份的 数据备份 列表 （当前装修模板类型的）
		$condition = array(
				'sld_page' => $tpl_data['sld_page'],
				'sld_type' => $tpl_data['sld_tpl_type'],
			);


		if ($layData['shop_id']) {
			$condition['sld_shop_id'] = $layData['shop_id'];
		}
		if ($layData['city_id']) {
			$condition['sld_city_id'] = $layData['city_id'];
		}

		$extend_js_data = '';
		// ajax 分页处理

		$each_num = 6;

		$default_data = $this->backupLayerStartData($each_num,$condition,$layer_tpl_list_item_html);

		$template_html = str_replace('{PN}', $default_data['now_page'], $template_html);
		$template_html = str_replace('{EACHNUM}', $each_num, $template_html);
		$template_html = str_replace('{PAGINATION}', $default_data['page_show'], $template_html);
		$template_html = str_replace('{LISTDATA}', $default_data['list_data'], $template_html);
		$template_html = str_replace('{EXTENDJSDATA}', $extend_js_data, $template_html);
		
		$template_html = str_replace('{TOPTIPS}', $top_tips, $template_html);

		return $template_html;
	}
	public function fixture_edit_layer_floornav($tpl_data,$layData,$fixture_tpl_info,$template_html)
	{
		$top_tips = '';

		$floor_nav_key = 'floornav';
		$nav_title = $tpl_data['sld_floor_nav_title'];

		$template_html = str_replace('{MAXNUMBER}', 4, $template_html);
		$template_html = str_replace('{WELCOMESTRING}', $nav_title, $template_html);
		$template_html = str_replace('{TOPTIPS}', $top_tips, $template_html);

		return $template_html;
	}
	//////////////////////-----装修模板编辑窗口的方法 sld_end-----/////////////////////////
	//////////////////////-----内置模版生成的方法 sld_start-----/////////////////////////
	// 将模版数据导入到 内置模版 中
	// param tpl_page_id 要设置为内置模版的ID
	// param built_in_data 内置模版的相关信息

	// // 需要导出作为 内置模板的模版ID
	// $need_export_page_id = 1;
	// // 内置模版资料
	// $built_in_data['sld_title'] = "炫酷首页模版一号";
	// $built_in_data['sld_page_type'] = 0;// 0 , 1 , 2 , 3 , 4 , 5
	// $built_in_data['sld_tpl_thumb'] = "123.jpg";
	// Logic('fixture')->import_into_built_tpl_data($need_export_page_id,$built_in_data);
	public function import_into_built_tpl_data($tpl_page_id,$built_in_data)
	{

		// 店铺装修的模板类型ID；1
		$vendor_page_types = array(1);

		$pic_ids = array();

		$sql = '';

		$sql .= "\n\n--------------- 内置模版 导入SQL START -------------------\n\n";

		$sql .= "select @maxid;";
		$sql .= "\n";

		$sql .= "select @maxid:=if(max(id),max(id)+1,1) from `".DBPRE."tpl_built_in` where 1;";
		$sql .= "\n";

		// 构建 内置模版页 数据
		if (!empty($built_in_data)) {
			$built_in_page_data = array();
			$built_in_page_data['sld_title'] = $built_in_data['sld_title'];
			$built_in_page_data['sld_page_type'] = $built_in_data['sld_page_type'];
			$built_in_page_data['sld_tpl_thumb'] = $built_in_data['sld_tpl_thumb'];
			$built_in_page_data['sld_sort'] = isset($built_in_data['sld_sort']) ? $built_in_data['sld_sort'] : 0 ;
			$built_in_page_data['sld_create_time'] = time();
			$built_in_page_data['sld_update_time'] = time();

			$insert_built_in_sql = "-- 新增内置模版";
			$insert_built_in_sql .= "\n";
	        $insert_built_in_sql .= "INSERT INTO `".DBPRE."tpl_built_in` (`sld_title`, `sld_page_type`,`sld_tpl_thumb`, `sld_sort`, `sld_create_time`, `sld_update_time`)";
	        $insert_built_in_sql .= " VALUES ";

	    	$sql_values_item = "(";
	    	$sql_values_item .= "'".$built_in_page_data['sld_title']."',";
	    	$sql_values_item .= "'".$built_in_page_data['sld_page_type']."',";
	    	$sql_values_item .= "'".$built_in_page_data['sld_tpl_thumb']."',";
	    	$sql_values_item .= $built_in_page_data['sld_sort'].",";
	    	$sql_values_item .= $built_in_page_data['sld_create_time'].",";
	    	$sql_values_item .= $built_in_page_data['sld_update_time'].");";

	    	$sql .= $insert_built_in_sql . $sql_values_item . "\n\n";
		}

		// 获取 要导入的数据
		$condition['sld_tpl_page_id'] = $tpl_page_id;
		$tpl_data_list = Model('web_home')->getTplData($condition);
		if ($tpl_data_list) {
			$insert_built_in_tpl_data_sql = "-- 增加内置模版相关数据";
			$insert_built_in_tpl_data_sql .= "\n";
			$insert_built_in_tpl_data_sql .= "INSERT INTO `".DBPRE."tpl_built_in_data` (`sld_page`, `sld_tpl_type`, `sld_tpl_name`, `sld_is_vaild`, `sld_tpl_code`,`sld_tpl_data`,`sld_sort`, `sld_tpl_page_id`)";
			$insert_built_in_tpl_data_sql .= " VALUES ";

			$sql_values_arr = [];
			$pic_ids = array();
			foreach ($tpl_data_list as $key => $value) {
				$sql_values_item = '';
				$sql_values_item .= "(";
				$sql_values_item .= "'".$value['sld_page']."',";
				$sql_values_item .= $value['sld_tpl_type'].",";
				$sql_values_item .= "'".$value['sld_tpl_name']."',";
				$sql_values_item .= $value['sld_is_vaild'].",";
				$sql_values_item .= "'".$value['sld_tpl_code']."',";
				$sql_values_item .= "'".$value['sld_tpl_data']."',";

				$sld_tpl_data = unserialize($value['sld_tpl_data']);

				if (!empty($sld_tpl_data)) {
					foreach ($sld_tpl_data as $sld_k => $sld_v) {
						if (is_array($sld_v) && !empty($sld_v)) {
							foreach ($sld_v as $p_k => $p_v) {
								if(array_key_exists('pic_id',$p_v)){
									// 获取 需要导入的图片ID
									$pic_ids[] = $p_v['pic_id'];
								}
							}
						}
					}
				}

				$sql_values_item .= $value['sld_sort'].",";
				$sql_values_item .= "@maxid)";

				$sql_values_arr[] = $sql_values_item;
			}
			$insert_built_in_tpl_data_sql .= implode(',', $sql_values_arr);
            $insert_built_in_tpl_data_sql .= ";". "\n\n";

            $sql .= $insert_built_in_tpl_data_sql;
            //////////////////////////////////////////////////////////////////////////////////////////// 

            // 生成 相关图片数据
            $pic_ids = array_unique($pic_ids);
            if (!empty($pic_ids)) {
				$pic_list_data = array();
				$pic_path_data = '';

	            if (in_array($built_in_data['sld_page_type'],$vendor_page_types)) {
	            	// 获取 店铺 图片 相关数据
					$model_album = Model('imagespace');

					foreach ($pic_ids as $key => $value) {
						$imgCondition['apic_id'] = $value;
						$pic_info_data = $model_album->getPicList($imgCondition);
						if (isset($pic_info_data[0]) && !empty($pic_info_data[0])) {
							$pic_list_data[] = $pic_info_data[0];
						}
					}
					if (!empty($pic_list_data)) {
						$pic_path_data .= '-- 店铺图片 原图地址';
						$pic_path_data .= "\n";
						$pic_path_data .= "将以下图片 存放到 ".BASE_STATIC_PATH.'/'.FIXTURE_PATH."/vendor/ 目录下";
						$pic_path_data .= "\n";

		            	$pic_insert_sql = '-- 增加店铺模版相关图片数据';
		            	$pic_insert_sql .= "\n";
		            	$pic_insert_sql .= "INSERT INTO `".DBPRE."imagespace` (`apic_id`, `apic_name`, `apic_tag`, `aclass_id`, `apic_cover`, `apic_size`, `apic_spec`, `vid`, `upload_time`)";
						$pic_insert_sql .= " VALUES ";
						$sql_values_arr = [];
						foreach ($pic_list_data as $key => $value) {
							$sql_values_item = '';
							$sql_values_item .= "(";
							$sql_values_item .= $value['apic_id'].",";
							$sql_values_item .= "'".$value['apic_name']."',";
							$sql_values_item .= "'".$value['apic_tag']."',";
							$sql_values_item .= $value['aclass_id'].",";
							$sql_values_item .= "'".$value['apic_cover']."',";
							$sql_values_item .= $value['apic_size'].",";
							$sql_values_item .= "'".$value['apic_spec']."',";
							$sql_values_item .= "0,";
							$sql_values_item .= time().")";

							$sql_values_arr[] = $sql_values_item;

							// $type_array = explode(',_', ltrim(GOODS_IMAGES_EXT, '_'));
							// foreach ($type_array as $t_k => $t_v) {
								// $file_name = str_ireplace('.', '_' . $t_v . '.', $value['apic_cover']);
								$pic_path_data .= BASE_UPLOAD_PATH.'/'.ATTACH_GOODS.'/'.$value['vid'].'/'.$value['apic_cover'];
								$pic_path_data .= "\n";
							// }
						}
						$pic_insert_sql .= implode(',', $sql_values_arr);
			            $pic_insert_sql .= ";". "\n\n";
					}

	            }else{
	            	// 后台 装修图片 相关数据
					$fixturePicCondition['id'] = array("IN",$pic_ids);
					$pic_list_data = Model('web_home')->getFixturePicList($fixturePicCondition,'');

					if ($pic_list_data) {
						$pic_path_data .= '-- 系统后台图片 原图地址';
						$pic_path_data .= "\n";
						$pic_path_data .= "将以下图片 存放到 ".BASE_STATIC_PATH.'/'.FIXTURE_PATH."/main/ 目录下";
						$pic_path_data .= "\n";

						$pic_insert_sql = '-- 增加模版相关图片数据';
						$pic_insert_sql .= "\n";
						$pic_insert_sql .= "INSERT INTO `bbc_fixture_album_pic` (`id`, `sld_album_id`, `sld_pic_name`, `sld_pic_width`, `sld_pic_height`, `sld_pic_size`, `sld_create_time`)";
						$pic_insert_sql .= " VALUES ";

						$sql_values_arr = [];
						foreach ($pic_list_data as $key => $value) {
							$sql_values_item = '';
							$sql_values_item .= "(";
							$sql_values_item .= $value['id'].",";
							$sql_values_item .= $value['sld_album_id'].",";
							$sql_values_item .= "'".$value['sld_pic_name']."',";
							$sql_values_item .= $value['sld_pic_width'].",";
							$sql_values_item .= $value['sld_pic_height'].",";
							$sql_values_item .= $value['sld_pic_size'].",";
							$sql_values_item .= time().")";

							$sql_values_arr[] = $sql_values_item;

							$pic_path_data .= BASE_UPLOAD_PATH.'/'.FIXTURE_PATH.'/'.$value['sld_pic_name'];
							$pic_path_data .= "\n";
						}
						$pic_insert_sql .= implode(',', $sql_values_arr);
			            $pic_insert_sql .= ";". "\n\n";
					}

	            }

	            $sql .= $pic_insert_sql;

	            $sql .= $pic_path_data;
            }

		}

		$sql .= "\n\n--------------- 内置模版 导入SQL END -------------------\n\n";


		import('function.log');
		log_output($sql);

	}
	//////////////////////-----内置模版生成的方法 sld_end-----///////////////////////////

	//////////////////////-----扩展 sld_start-----///////////////////////////
	// 根据链接 获取gid
	public function getGidByLink($link){
		$gid = 0;

		$rule = "/index\.php\?app=goods\&gid\=(\d+)/i";

		if (preg_match_all($rule,$link,$matches)) {
			if (!empty($matches[1])) {
				foreach ($matches[1] as $key => $value) {
					$gid = $value;
				}
			}
			unset($matches);
		}

		return $gid;
	}
	// 获取 当前模板数据的所有gid(包含链接)
	public function getFixtureAllGids($jsonArr){
		$gids = array();

		if (!empty($jsonArr)) {
			foreach ($jsonArr as $key => $value) {
				if ($value['is_valid'] == 1) {
					if (isset($value['data']) && $value['data']) {
						$data = $this->redotran($value['data']);
						$data = unserialize($data);
				
						if (is_array($data) && !empty($data)) {
							foreach ($data as $d_k => $d_v) {
								$all_links = array();
								$links = low_array_column($d_v, 'links');
								if (!empty($links)) {
									$all_links = array_merge($all_links,$links);
								}
								$title_links = low_array_column($d_v, 'title_links');
								if (!empty($title_links)) {
									$all_links = array_merge($all_links,$title_links);
								}
								$goods = low_array_column($d_v, 'goods_id');
								
								// 正则匹配 商品链接 获取 商品ID
								foreach ($all_links as $l_k => $l_v) {
									$goods_id_by_link = $this->getGidByLink($l_v);
									if ($goods_id_by_link) {
										array_push($gids, $goods_id_by_link);
										unset($goods_id_by_link);
									}
								}
								// 含有商品链接
								if (!empty($goods)) {
									$gids = array_merge($gids,$goods);
								}
								unset($goods);
								unset($links);
								unset($title_links);
								unset($all_links);
							}
						}
					}
				}
			}
			$gids = array_flip($gids);
            $gids = array_flip($gids);
            $gids = array_values($gids);
            sort($gids);
		}

		return $gids;
	}
	//////////////////////-----扩展 sld_end-----///////////////////////////
}