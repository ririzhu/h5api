<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 应用设置
// +----------------------------------------------------------------------
$source = '';//$_SERVER['HTTP_HOST'];   //判断是否是http或者https
$http_source = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
$url = $http_source.$source;
//获取动态二级域名--end
$config = array();
$config['mall_url'] = $url; //用于生产动态URL
//配置一个主域名，当二级域名不允许的情况下直接跳转主域名
$config['main_url_main'] = 'hyz.com';//'hyshop.55jimu.com';//商城主域名
$config['main_url'] = $http_source . $config['main_url_main'];//商城主域名
$config['mall_site_url'] = $url . '/mall';
$config['base_site_url'] = $url . '/mall';
$config['base_vendor_url'] = $url . '/vendor';
$config['base_dian_url'] = $url . '/dian';
$config['sns_site_url'] = $url . '/sns';
$config['admin_site_url'] = $url . '/SystemManage';
$config['mobile_site_url'] = $url . '/cmobile';
$config['wap_site_url'] = $url . '/cwap';
$config['upload_site_url'] = $url . '/data/upload';
$config['static_site_url'] = $url . '/static';
$config['version'] = '2016.08.01-V8.0.0';
$config['setup_date'] = '2016-08-01 21:18:47';
$config['gip'] = 0;
$config['dbdriver'] = 'mysqli';
$config['tablepre'] = 'bbc_';
$config['db'][1]['dbhost'] = '127.0.0.1';
$config['db'][1]['dbport'] = '3306';
$config['db'][1]['dbuser'] = 'root';
$config['db'][1]['dbpwd'] = '54709488';//'2012586';
$config['db'][1]['dbname'] = 'projekt';//'sld_hyshop';
$config['db'][1]['dbcharset'] = 'UTF-8';
$config['db']['slave'] = array();
$config['session_expire'] = 3600;
$config['lang_type'] = 'zh_cn';
$config['cookie_pre'] = '0D59_';
$config['tpl_name'] = 'default';
$config['thumb']['cut_type'] = 'gd';
$config['thumb']['impath'] = '';
//$config['cache']['type'] = 'file';
//$config['cache_open'] = true;
$config['cache']['type'] = 'file';
//$config['redis']['prefix']        = 'sld_';
//$config['redis']['master']['port']        = 6379;
//$config['redis']['master']['host']        = '127.0.0.1';
//$config['redis']['master']['pconnect']    = 0;
//$config['redis']['slave']             = array();
$config['debug'] = false;
$config['default_store_id'] = '1';
// 是否开启伪静态
$config['url_model'] = false;
// 二级域名后缀
$config['subdomain_suffix'] = '';


//是否开启404_debug  调试功能
$config['debug_404'] = false;
$config['dian'] = false;  //门店开关
$config['distribution'] = true;  //是否开启分销
$config['sld_city_site'] = false;//是否开启城市分站
$config['sld_supplier_isuse'] = false;// 是否开启 批发中心
$config['sld_xcx'] = true;// 是否开启 微信小程序功能
$config['sld_is_wap'] = true;// 是否有微商城，有的话会有微信公众号的配置
$config['sld_is_collection'] = false;// 是否开启采集商品功能
$config['member_grade_open'] = false;//会员等级折扣
$config['collection_cct_system'] = false;//采集评论开关
$config['pc_system_open'] = true;//pc端是否禁用

//更新商品二维码
$config['is_update_qr'] = true;

// addons
$config['sld_spreader'] = false;//是否开启推手系统
$config['spreader_wap_site_url'] = $url . '/ts';
$config['sld_cashersystem'] = false;//是否开启收银系统
$config['sld_pintuan'] = false;// 是否拼团的功能
$config['sld_pintuan_ladder'] = false;//是否开启阶梯拼团
$config['sld_presale_system'] = true;// 是否预售的功能
$config['points_wap_site_url'] = $url . '/points';
$config['sld_red'] = true;// 是否开启优惠券功能
$config['sld_ldjsystem'] = false;//是否开启联到家系统


/*********************七牛和OSS只能开启一个***************************/
$config['sld_oss_open'] = 0;// 是否有使用oss
$config['sld_oss_pre'] = '';// 是否有使用oss


$config['sld_qiniu_open'] = 0; //是否启用七牛云存储
$config['bucket'] = 'www-slodon-cn'; //七牛云存储桶
$config['accessKey'] = 'YI4n8HNWkyRxt8lA_5n_-ZIp4CBnd6ZR6xckTxNK'; //七牛云存储桶
$config['secretKey'] = '1gRffxkgdcI3uL_8fdl0KmVGRJFm2qqq70zrnCjH'; //七牛云存储桶
/******************************************************************/

//聊天系统配置
$config['service_url'] = 'http://newim.55jimu.com/';//聊天系统访问地址
$config['service_wsHost'] = 'newim.55jimu.com';//后面不可以带/
$config['service_port'] = '9090';//配置端口，要跟安装聊天系统的端口一致
$config['service_appid'] = 'b054014693241bcd9c20';//无需修改

$config['service_dbname'] = 'new_im2';//聊天系统数据库名

//推送配置
$config['jpush_open'] = true;
$config['jpush_appkey'] = 'b660716da4a05e0dc4c4904e';
$config['jpush_secret'] = 'e20e956981b0dca619a73f70';
define('BASE_ROOT_PATH',str_replace('\\','/',dirname(__FILE__)));
define('TIMESTAMP',time());
define('DS','/');
define('DYMall',true);
define('StartTime',microtime(true));
define('DIR_SHOP','mall');
define('DIR_VENDOR','vendor');
define('DIR_DIAN','dian');
define('DIR_CMS','cms');
define('DIR_SNS','sns');
define('DIR_WXSHOP','wxshop');
define('DIR_API','api');
define('DIR_MOBILE','mobile');
define('DIR_WAP','wap');

define('DIR_RESOURCE','data/resource');
define('DIR_UPLOAD','data/upload');

define('ATTACH_PATH','mall');
define('ATTACH_COMMON','mall/common');
define('ATTACH_AVATAR','mall/avatar');
define('ATTACH_EDITOR','mall/editor');
define('ATTACH_MEMBERTAG','mall/membertag');
define('ATTACH_STORE','mall/store');
define('ATTACH_STORE_video','upload/mall/store/video');
define('ATTACH_STORE_file','upload/mall/store/file');
define('ATTACH_GOODS','mall/store/goods');
define('ATTACH_GOODS_CLASS','mall/goods_class');
define('ATTACH_LOGIN','mall/login');
define('ATTACH_ARTICLE','mall/article');
define('ATTACH_BRAND','mall/brand');
define('ATTACH_MOBILE_PIC','mall/mobile');
define('ATTACH_ADV','mall/asdsv');
define('ATTACH_ACTIVITY','mall/activity');
define('ATTACH_WATERMARK','mall/watermark');
define('ATTACH_POINTPROD','mall/pointprod');
define('ATTACH_TUAN','mall/tuan');
define('ATTACH_SLIDE','mall/store/slide');
define('ATTACH_VOUCHER','mall/voucher');
define('ATTACH_STORE_JOININ','mall/store_joinin');
define('ATTACH_REC_POSITION','mall/rec_position');
define('ATTACH_MOBILE','mobile');//指向data/upload/mobile
define('UPLOAD_WEBIM','webim');//指向 data/upload/webim
define('ATTACH_SNS','sns');
define('ATTACH_CMS','cms');
define('ATTACH_MALBUM','mall/member');
define('ATTACH_WXSHOP','wxshop');
define('TPL_SNS_NAME', 'default');
define('TPL_WXSHOP_NAME', 'default');
define('TPL_CMS_NAME', 'default');
define('TPL_ADMIN_NAME', 'default');
define('FIXTURE_PATH','fixture');

/*
 * 商家入驻状态定义
 */
//新申请
define('STORE_JOIN_STATE_NEW', 10);
//完成付款
define('STORE_JOIN_STATE_PAY', 11);
//初审成功
define('STORE_JOIN_STATE_VERIFY_SUCCESS', 20);
//初审失败
define('STORE_JOIN_STATE_VERIFY_FAIL', 30);
//付款审核失败
define('STORE_JOIN_STATE_PAY_FAIL', 31);
//开店成功
define('STORE_JOIN_STATE_FINAL', 40);

//默认颜色规格id(前台显示图片的规格)
define('DEFAULT_SPEC_COLOR_ID', 1);


/**
 * 商品图片
 */
define('GOODS_IMAGES_WIDTH', '60,240,350,1280');
define('GOODS_IMAGES_HEIGHT', '60,240,350,12800');
define('GOODS_IMAGES_EXT', '_60,_240,_350,_1280');

/**
 *  订单状态
 */
//已取消
define('ORDER_STATE_CANCEL', 0);
//已产生但未支付
define('ORDER_STATE_NEW', 10);
//已支付
define('ORDER_STATE_PAY', 20);
//已发货
define('ORDER_STATE_SEND', 30);
//已收货，交易成功
define('ORDER_STATE_SUCCESS', 40);

//订单结束后可评论时间，15天，60*60*24*15
define('ORDER_EVALUATE_TIME', 1296000);


//订单超过N小时未支付自动取消
define('ORDER_AUTO_CANCEL_TIME', 3);
//订单超过N天未收货自动收货
define('ORDER_AUTO_RECEIVE_DAY', 10);

//预订尾款支付期限(小时)
define('BOOK_AUTO_END_TIME', 72);

//门店支付订单支付提货期限(天)
define('CHAIN_ORDER_PAYPUT_DAY', 7);
/**
 * 订单删除状态
 */
//默认未删除
define('ORDER_DEL_STATE_DEFAULT', 0);
//已删除
define('ORDER_DEL_STATE_DELETE', 1);
//彻底删除
define('ORDER_DEL_STATE_DROP', 2);

//OSS
define('OSS_ID', 'LTAIITPH93chXaGR');
define('OSS_KEY', '1nK6SnnIQZkSrfiNSmLHu2Hw0wlFcA');
define('OSS_HOST', 'http://oss-cn-beijing.aliyuncs.com');
define('OSS_HOST_FULL', 'http://sld-huanyun.oss-cn-beijing.aliyuncs.com');
define('OSS_BACKET', 'sld-huanyun');


//if((APP_ID == 'mall' || APP_ID == 'mobile')){
    $config['lang_type'] = "zh";
//}
define('DEFAULT_PLATFORM_STORE_ID', $config['default_store_id']);

define('URL_MODEL',$config['url_model']);
define('SUBDOMAIN_SUFFIX', $config['subdomain_suffix']);
define('MALL_URL',$config['mall_url']);
define('BASE_SITE_URL', $config['base_site_url']);
define('MALL_SITE_URL', $config['mall_site_url']);
define('TPL_SHOP_NAME',$config['tpl_name']);//前台展示的模板
define('BASE_VENDOR_URL', $config['base_vendor_url']);
define('BASE_DIAN_URL', $config['base_dian_url']);
define('CMS_SITE_URL', '');
define('SNS_SITE_URL', $config['sns_site_url']);
define('WXSHOP_SITE_URL', '');
define('ADMIN_SITE_URL', $config['admin_site_url']);
define('MOBILE_SITE_URL', $config['mobile_site_url']);
define('WAP_SITE_URL', $config['wap_site_url']);
define('STATIC_SITE_URL',$config['static_site_url']);
define('LOGIN_SITE_URL', $config['base_site_url']);

define('BASE_NEW_SHOP_PATH',BASE_ROOT_PATH.'/shop');//shop目录
define('BASE_DATA_PATH',BASE_ROOT_PATH.'/data');
define('BASE_LIBRARY_PATH',BASE_ROOT_PATH.'/library');
define('BASE_STATIC_PATH',BASE_ROOT_PATH.'/static');//根目录下面的static
//define('STATIC_SITE_URL',$config['static_site_url']);//根目录下面的
define('BASE_UPLOAD_PATH',BASE_DATA_PATH.'/upload');
define('BASE_RESOURCE_PATH',BASE_DATA_PATH.'/resource');
//define('BASE_RESOURCE_PATH',BASE_STATIC_PATH.'/resource');
define('BASE_WAP_PATH',BASE_ROOT_PATH.'/cwap');
define('NODE_SITE_URL','');
define('CHAT_SITE_URL','');
define('WEBIM_SITE_URL','');
define('WEBIM_SOCKET','');
define('CHARSET','');
define('DBDRIVER',$config['dbdriver']);
define('SESSION_EXPIRE',$config['session_expire']);
define('LANG_TYPE',$config['lang_type']);
define('COOKIE_PRE',$config['cookie_pre']);
// define('XCX_APPID',$config['xcx_appid']);//微信小程序的appid
// define('XCX_SECRET',$config['xcx_secret']);//微信小程序的secret
// define('GZH_APPID',$config['gzh_appid']);//微信公众号的appid
// define('GZH_SECRET',$config['gzh_secret']);//微信公众号的secret


define('QINIU_BUCKET', $config['bucket']);
define('QINIU_ACCESSKEY', $config['accessKey']);
define('QINIU_SECRETKEY', $config['secretKey']);

define('DBPRE',($config['db'][1]['dbname']).'`.`'.($config['tablepre']));
if($config['sld_oss_open']){
    define('OSS_ENABLE',1);
    define('QINIU_ENABLE',0);
    $config['upload_site_url']		= 'http://'.$config['sld_oss_pre'].'.oss-cn-beijing.aliyuncs.com/data/upload';
}else if($config['sld_qiniu_open']){
    define('QINIU_ENABLE',1);
    define('OSS_ENABLE',0);
    $config['upload_site_url']		= 'http://img.slodon.cn'.'/data/upload';
}else{
    $config['upload_site_url']      = $config['main_url'].'/data/upload';
    define('OSS_ENABLE',0);
    define('QINIU_ENABLE',0);
}
define('UPLOAD_SITE_URL',$config['upload_site_url']);
define('MD5_KEY','7253def33a4f7130018a00dd8b9c8c43');
return [
    // 应用名称
    'app_name'               => '',
    // 应用地址
    'app_host'               => '',
    // 应用调试模式
    'app_debug'              => true,
    // 应用Trace
    'app_trace'              => false,
    // 是否支持多模块
    'app_multi_module'       => true,
    // 入口自动绑定模块
    'auto_bind_module'       => false,
    // 注册的根命名空间
    'root_namespace'         => [],
    // 默认输出类型
    'default_return_type'    => 'html',
    // 默认AJAX 数据返回格式,可选json xml ...
    'default_ajax_return'    => 'json',
    // 默认JSONP格式返回的处理方法
    'default_jsonp_handler'  => 'jsonpReturn',
    // 默认JSONP处理方法
    'var_jsonp_handler'      => 'callback',
    // 默认时区
    'default_timezone'       => 'Asia/Shanghai',
    // 是否开启多语言
    'lang_switch_on'         => false,
    // 默认全局过滤方法 用逗号分隔多个
    'default_filter'         => '',
    // 默认语言
    'default_lang'           => 'zh-cn',
    // 应用类库后缀
    'class_suffix'           => false,
    // 控制器类后缀
    'controller_suffix'      => false,

    // +----------------------------------------------------------------------
    // | 模块设置
    // +----------------------------------------------------------------------

    // 默认模块名
    'default_module'         => 'index',
    // 禁止访问模块
    'deny_module_list'       => ['common'],
    // 默认控制器名
    'default_controller'     => 'Index',
    // 默认操作名
    'default_action'         => 'index',
    // 默认验证器
    'default_validate'       => '',
    // 默认的空模块名
    'empty_module'           => '',
    // 默认的空控制器名
    'empty_controller'       => 'Error',
    // 操作方法前缀
    'use_action_prefix'      => false,
    // 操作方法后缀
    'action_suffix'          => '',
    // 自动搜索控制器
    'controller_auto_search' => false,

    // +----------------------------------------------------------------------
    // | URL设置
    // +----------------------------------------------------------------------

    // PATHINFO变量名 用于兼容模式
    'var_pathinfo'           => 's',
    // 兼容PATH_INFO获取
    'pathinfo_fetch'         => ['ORIG_PATH_INFO', 'REDIRECT_PATH_INFO', 'REDIRECT_URL'],
    // pathinfo分隔符
    'pathinfo_depr'          => '/',
    // HTTPS代理标识
    'https_agent_name'       => '',
    // IP代理获取标识
    'http_agent_ip'          => 'X-REAL-IP',
    // URL伪静态后缀
    'url_html_suffix'        => 'html',
    // URL普通方式参数 用于自动生成
    'url_common_param'       => false,
    // URL参数方式 0 按名称成对解析 1 按顺序解析
    'url_param_type'         => 0,
    // 是否开启路由延迟解析
    'url_lazy_route'         => false,
    // 是否强制使用路由
    'url_route_must'         => false,
    // 合并路由规则
    'route_rule_merge'       => false,
    // 路由是否完全匹配
    'route_complete_match'   => false,
    // 使用注解路由
    'route_annotation'       => false,
    // 域名根，如thinkphp.cn
    'url_domain_root'        => '',
    // 是否自动转换URL中的控制器和操作名
    'url_convert'            => true,
    // 默认的访问控制器层
    'url_controller_layer'   => 'controller',
    // 表单请求类型伪装变量
    'var_method'             => '_method',
    // 表单ajax伪装变量
    'var_ajax'               => '_ajax',
    // 表单pjax伪装变量
    'var_pjax'               => '_pjax',
    // 是否开启请求缓存 true自动缓存 支持设置请求缓存规则
    'request_cache'          => false,
    // 请求缓存有效期
    'request_cache_expire'   => null,
    // 全局请求缓存排除规则
    'request_cache_except'   => [],
    // 是否开启路由缓存
    'route_check_cache'      => false,
    // 路由缓存的Key自定义设置（闭包），默认为当前URL和请求类型的md5
    'route_check_cache_key'  => '',
    // 路由缓存类型及参数
    'route_cache_option'     => [],

    // 默认跳转页面对应的模板文件
    'dispatch_success_tmpl'  => Env::get('think_path') . 'tpl/dispatch_jump.tpl',
    'dispatch_error_tmpl'    => Env::get('think_path') . 'tpl/dispatch_jump.tpl',

    // 异常页面的模板文件
    'exception_tmpl'         => Env::get('think_path') . 'tpl/think_exception.tpl',

    // 错误显示信息,非调试模式有效
    'error_message'          => '页面错误！请稍后再试～',
    // 显示错误信息
    'show_error_msg'         => false,
    // 异常处理handle类 留空使用 \think\exception\Handle
    'exception_handle'       => '',

];
