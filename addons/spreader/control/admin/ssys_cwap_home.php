<?php
/**
 * 手机首页 多模板 接口
 *
 *
 */
defined('DYMall') or exit('Access Invalid!');
class ssys_cwap_homeCtl extends SystemCtl{

	public function __construct(){
		parent::__construct();
	}


	/**
	 * @api {get} /SystemManage/index.php?app=ssys_cwap_home&mod=getTemplatePages 获取 模板列表
	 * @apiVersion 0.0.1
	 * @apiName getTemplatePages
	 * @apiGroup ssys_cwapTemplates
	 *
	 * @apiDescription 获取 模板列表
	 *
	 * @apiParam {String} pageSize 		页面数据条数
	 * @apiParam {String} currentPage 	当前页
	 * @apiParam {String} urlType 		跳转链接前端代码
	 *
	 * @apiExample Example usage:
	 * curl -i /SystemManage/index.php?app=ssys_cwap_home&mod=getTemplatePages
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
		         "decorate_url": "/index.php?app=cwap_home&mod=home_edit&id=32&type=home&sld_addons=spreader"
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

        $model_cwap_home = M('ssys_cwap_home');

        $condition['city_id'] = $cityId;

        $page_list = $model_cwap_home->getWapHomeList($condition,$pageSize);
        foreach ($page_list as $key => $val){
		    $page_list[$key]['decorate_url'] = MALL_URL.$urlType.'/index.php?app=cwap_home&mod=home_edit&id='.$val['home_id'].'&type=home&sld_addons=spreader';
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
	 * @api {post} /SystemManage/index.php?app=ssys_cwap_home&mod=saveTemplateInfo 保存 模板信息
	 * @apiVersion 0.0.1
	 * @apiName saveTemplateInfo
	 * @apiGroup ssys_cwapTemplates
	 *
	 * @apiDescription 保存 模板信息
	 *
	 * @apiParam {Array} tplData 				需要保存的模板数据
	 *
	 * @apiExample Example usage:
	 * curl -i /SystemManage/index.php?app=ssys_cwap_home&mod=saveTemplateInfo
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
        	$model_cwap_home = M('ssys_cwap_home');
        	if ($tplData['template_id']) {
	        	$condition = array(
	        			'template_id' => $tplData['template_id'],
	        		);
	        	$page_arr = $model_cwap_home->getWapTemplateList($condition,'template_id asc','template_data');

	        	$tplData['home_data'] = isset($page_arr[0]['template_data']) ? $page_arr[0]['template_data'] : '';
	        	unset($tplData['template_id']);
        	}

        	$tplData['city_id'] = $add_condition['city_id'] = $cityId;
        	$save_flag = $model_cwap_home->addHome($tplData,$add_condition);
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
	 * @api {post} /SystemManage/index.php?app=ssys_cwap_home&mod=updateTemplateInfo 更新 模板信息
	 * @apiVersion 0.0.1
	 * @apiName updateTemplateInfo
	 * @apiGroup ssys_cwapTemplates
	 *
	 * @apiDescription 更新 模板信息
	 *
	 * @apiParam {Int} id 						模板ID
	 * @apiParam {Array} tplData 				需要更新的模板数据
	 *
	 * @apiExample Example usage:
	 * curl -i /SystemManage/index.php?app=ssys_cwap_home&mod=updateTemplateInfo
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

        $state = 255;
        $message = L('操作失败');

        if (is_array($tplData) && !empty($tplData) && $page_id) {
        	$model_cwap_home = M('ssys_cwap_home');

        	// 校验 当前是否为 设为默认的 模板
        	$check_condition = array(
        			'home_state' => 1,
        			'home_id' => $page_id,
        		);
        	$d = $model_cwap_home->getWapHomeList($check_condition);
        	$is_default = count($d);
        	// 不能将最后一个 默认模板 取消掉默认
        	if (isset($tplData['home_state']) && $tplData['home_state'] == 0 && $is_default) {
        		$message = '当前模板为默认模板，请先设置其他模板为默认模版。';
        	}else{
	        	$update_flag = $model_cwap_home->editCwapHome($tplData, $page_id);

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
	 * @api {post} /SystemManage/index.php?app=ssys_cwap_home&mod=deleteTemplate 删除 模板
	 * @apiVersion 0.0.1
	 * @apiName deleteTemplate
	 * @apiGroup ssys_cwapTemplates
	 *
	 * @apiDescription 删除 模板
	 *
	 * @apiParam {Array} 	ids 			模板ID数组
	 *
	 * @apiExample Example usage:
	 * curl -i /SystemManage/index.php?app=ssys_cwap_home&mod=deleteTemplate
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
        	$model_cwap_home = M('ssys_cwap_home');

        	// 校验 当前要删除的ID中是否为 设为默认的 模板
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
	 * @api {get} /SystemManage/index.php?app=ssys_cwap_home&mod=getDefaultTemplateList 获取 待选模板列表
	 * @apiVersion 0.0.1
	 * @apiName getDefaultTemplateList
	 * @apiGroup ssys_cwapTemplates
	 *
	 * @apiDescription 获取 待选模板列表
	 *
	 * @apiParam {String} pageSize 		页面数据条数
	 * @apiParam {String} currentPage 	当前页
	 * @apiParam {Int} type 			类型
	 *
	 * @apiExample Example usage:
	 * curl -i /SystemManage/index.php?app=ssys_cwap_home&mod=getDefaultTemplateList
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

    	$model_cwap_home = M('ssys_cwap_home');

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
	 * @api {get} /SystemManage/index.php?app=ssys_cwap_home&mod=getDefaultTemplateInfo 获取 待选模板代码
	 * @apiVersion 0.0.1
	 * @apiName getDefaultTemplateInfo
	 * @apiGroup ssys_cwapTemplates
	 *
	 * @apiDescription 获取 待选模板列表
	 *
	 * @apiParam {Int} id 			模板ID
	 *
	 * @apiExample Example usage:
	 * curl -i /SystemManage/index.php?app=ssys_cwap_home&mod=getDefaultTemplateInfo
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
        	$model_cwap_home = M('ssys_cwap_home');
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

}