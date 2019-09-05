<?php
namespace app\V1\controller;
use Exception;
use think\facade\Cache;
use think\Controller;
use think\Lang;
use think\Request;
class Base extends Controller
{
    /**
     * init
     */
    public  function __construct() {
        // config info
        $headInfo = Request()->header();
       // Lang::load( "\lang\$headInfo['content-language']\$headInfo['content-language'].php");


        global $setting_config;
        //self::parse_conf($setting_config);
        /*define('MD5_KEY',md5($setting_config['md5_key']));
        if(function_exists('date_default_timezone_set')){
            if (is_numeric($setting_config['time_zone'])){
                @date_default_timezone_set('Asia/Shanghai');
            }else{
                @date_default_timezone_set($setting_config['time_zone']);
            }
        }*/
        //session start
        //self::start_session();
//短消息检查
        //$this->checkMessage();
        //购物车商品种数查询
        //$this->queryCart();

        //$this->getMemberAndGradeInfo(false);
        //检查会员登录权限
        //$this->checkMemberLogin();
        //pc端是否禁止
        //$this->checkpcopen();

        //$city_logic = Logic('city_site');
        //$city_logic -> checkDomain($_SERVER['HTTP_HOST']);

        //获取所有的开启的城市分站和热门推荐城市分站
        //$cityList = $city_logic->getCityAndHotList();
        //$sldCityList = $cityList['citylist'];
        //$sldHotCityList = $cityList['hotlist'];

        //根据域名获取相应的绑定的城市分站信息
        //$curCityInfo = $city_logic->getUrlCity($_SERVER['HTTP_HOST']);

        // 获取 商品分类导航的样式
        //$base_setting = H('setting');
        //$goods_class_style = isset($base_setting['goods_class_style']) ? intval($base_setting['goods_class_style']) : 1;
        //$goods_class_style_max_num = array(1=>14,2=>8);

        //Template::output('goods_class_style',$goods_class_style);
        //Template::output('goods_class_style_max_num',$goods_class_style_max_num);

        //Language::read('common,home_layout');

        //Template::setDir('home');
        //$nav = H('nav')?:H('nav',true);
        //foreach ($nav as $k=>$v){
            //if($v['lang']!=LANG_TYPE){
                //unset($nav[$k]);
            //}
        //}

        //Template::output('nav_list',$nav);
        //Template::output('sldCityList',$sldCityList);
        //Template::output('sldHotCityList',$sldHotCityList);
        //Template::output('curCitySite',$curCityInfo);
//        Template::setLayout('home_layout');
        //       Template::output('hot_search',@explode(',',C('hot_search')));
        //获得语言
        //$langlist = Model('lang_sites')->where(['state'=>1])->select();
//        Template::output('langlist',$langlist);

        //$model_class = Model('goods_class');
        //$goods_class = $model_class->get_all_category();
        //$model_channel = Model('web_channel');
        //$goods_channel = $model_channel->getChannelList(array('channel_show'=>'1'));
        //foreach ($goods_class as $key => $value) {
            // foreach ($goods_channel as $k=> $v) {
            //     if($value['gc_id']==$v['gc_id']){
            //         $goods_class[$value['gc_id']]['channel_gc_id'] =$v['gc_id'];
            //         $goods_class[$value['gc_id']]['channel_id'] =$v['channel_id'];
            //     }
            //     if(!empty($value['class2'])&&is_array($value['class2'])){
            //         foreach ($value['class2'] as $kk=> $vv) {
            //             if($vv['gc_id']==$v['gc_id']){
            //                 $goods_class[$value['gc_id']]['class2'][$vv['gc_id']]['channel_gc_id'] =$v['gc_id'];
            //                 $goods_class[$value['gc_id']]['class2'][$vv['gc_id']]['channel_id'] =$v['channel_id'];
            //             }
            //         }
            //     }
            // }
            // 获取专题ID
            //$model_web_home_page = Model('web_home_page');
            //$goods_class[$value['gc_id']]['topic_id'] = $model_web_home_page->getTopicIdByGcId($value['gc_id']);
            //if(!empty($value['class2'])&&is_array($value['class2'])){
                //foreach ($value['class2'] as $kk=> $vv) {
                    //$goods_class[$value['gc_id']]['class2'][$vv['gc_id']]['topic_id'] = $model_web_home_page->getTopicIdByGcId($vv['gc_id']);
                //}
            //}
        //}
//        Template::output('show_goods_class',$goods_class);
//        echo '<pre>';
//        print_r($goods_class);die;

        //if ($_GET['column'] && strtoupper(CHARSET) == 'GBK'){
            //$_GET = Language::getGBK($_GET);
        //}
        //$this->articles();//文章输出
        //if(!C('site_status')) ShowQuillContent(C('closed_reason'),'<div class="container">网站正在升级，给您带来的不便深感抱歉</div>');
        // 自动登录
        //$this->auto_login();
    }
    /**
     * get setting
     */
    private static function parse_conf($setting_config){
        $bbc_config = $GLOBALS['config'];
        if(is_array($bbc_config['db']['slave']) && !empty($bbc_config['db']['slave'])){
            $dbslave = $bbc_config['db']['slave'];
            $sid     = array_rand($dbslave);
            $bbc_config['db']['slave'] = $dbslave[$sid];
        }else{
            $bbc_config['db']['slave'] = $bbc_config['db'][1];
        }
        $bbc_config['db']['master'] = $bbc_config['db'][1];
        $setting_config = $bbc_config;
        $setting = ($setting = H('setting')) ? $setting : H('setting',true);


        if (Config('sld_spreader')) {
            $ssys_setting = ($ssys_setting = H('ssys_setting')) ? $ssys_setting : H('ssys_setting',true);
            $setting = array_merge_recursive($setting,$ssys_setting);
            unset($ssys_setting);
        }
        if (Config('sld_cashersystem')) {
            $cashsys_setting = ($cashsys_setting = H('cashsys_setting')) ? $cashsys_setting : H('cashsys_setting',true);
            $setting = array_merge_recursive($setting,$cashsys_setting);
            unset($cashsys_setting);
        }
        //联到家设置
        if (Config('sld_ldjsystem')) {
            $ldj_setting = ($ldj_setting = H('ldj_setting')) ? $ldj_setting : H('ldj_setting',true);
            $setting = array_merge_recursive($setting,$ldj_setting);
            unset($ldj_setting);
        }

        $setting_config = array_merge_recursive($setting,$bbc_config);
    }
    public function test(){
        echo "whello ,vechain";
    }
    /**
     * KV缓存 读
     *
     * @param string $key 缓存名称
     * @param boolean $callback 缓存读取失败时是否使用回调 true代表使用cache.model中预定义的缓存项 默认不使用回调
     * @param callable $callback 传递非boolean值时 通过is_callable进行判断 失败抛出异常 成功则将$key作为参数进行回调
     * @return mixed
     */
    public static function rkcache($key, $callback = false)
    {

        /*if (Config('cache_open')) {
            $cacher = Cache(Config('cache.type'));
        } else {
            $cacher = Cache("cache", false);
        }
        if (!$cacher) {
            throw new Exception('Cannot fetch cache object!');
        }*/

        $value = Cache($key);
        if (($value === false || empty($value)) && $callback !== false) {
            if ($callback === true) {
                $callback = array(new \app\V1\model\Cache(), 'call');
            }
            if (!is_callable($callback)) {
                throw new Exception('Invalid rkcache callback!');
            }
            $value = call_user_func($callback, $key);
            self::wkcache($key, $value);
        }
        return $value;
    }
    /**
     * KV缓存 删
     *
     * @param string $key 缓存名称
     * @return boolean
     */
    public static function dkcache($key)
    {
        /*if (Config('cache_open')) {
            $cacher = Cache::getInstance(C('cache.type'));
        } else {
            $cacher = Cache::getInstance(C('cache.type'), null);
        }
        if (!$cacher) {
            throw new Exception('Cannot fetch cache object!');
        }*/
        return Cache::rm($key);
    }
    /**
     * KV缓存 写
     *
     * @param string $key 缓存名称
     * @param mixed $value 缓存数据 若设为否 则下次读取该缓存时会触发回调（如果有）
     * @param int $expire 缓存时间 单位秒 null代表不过期
     * @return boolean
     */
    public static function wkcache($key, $value, $expire = null)
    {
        /*if (Config('cache_open')) {
            $cacher = Cache::init(Config('cache.type'));
        } else {
            $cacher = Cache::init(Config('cache.type'), null);
        }
        if (!$cacher) {
            throw new Exception('Cannot fetch cache object!');
        }*/
        return cache($key, $value, null, $expire);
    }
    /**
     * 读取缓存信息（只适用于内存缓存）
     *
     * @param string $key 要取得缓存 键
     * @param string $prefix 键值前缀
     * @param bool $unserialize 是否需要反序列化
     * @return array/bool
     */
    function rcache($key = null, $prefix = '', $unserialize = true)
    {
        //if (empty($key) || Config('cache.type') == 'file') return false;
        //$obj_cache = \Cache::getInstance(Config('cache.type'));
        $value = Cache($prefix.$key);
       // $data      = cache::get($key, false);
        return $unserialize ? unserialize($value) : $value;
    }

    /**
     * 写入缓存
     *
     * @param string $key 缓存键值
     * @param array $data 缓存数据
     * @param string $prefix 键值前缀
     * @param int $period 缓存周期  单位分，0为永久缓存
     * @return bool 返回值
     */
    function wcache($key = null, $data = array(), $prefix, $period = 0)
    {
        if ($key === null || !Config('cache_open') || !is_array($data)) return;
        $period = intval($period);
        if ($period != 0) {
            $data['cache_expiration_time'] = TIMESTAMP + $period * 60;
        }
        $ins = Cache::getInstance(Config('cache.type'));

        $ins->hset($key, $prefix, $data);
//    $cache_info = $ins->hget($key, $prefix);

        return true;
    }

    /**
     * 删除缓存
     * @param string $key 缓存键值
     * @param string $prefix 键值前缀
     * @return boolean
     */
    function dcache($key = null, $prefix = '')
    {
        if ($key === null || !Config('cache_open')) return true;
        $ins = Cache::getInstance(Config('cache.type'));
        return $ins->hdel($key, $prefix);
    }


    /**
     * 写入缓存（只适用于内存缓存）
     *
     * @param string $key 缓存键值
     * @param array $data 缓存数据
     * @param string $prefix 键值前缀
     * @param int $ttl 缓存周期
     * @param string $perfix 存入的键值前缀
     * @param bool $serialize 是否序列化后保存
     * @return bool 返回值
     */
    function wmemcache($key = null, $data = array(), $prefix = '', $ttl = null, $serialize = true)
    {
        if (empty($key) || Config('cache.type') == 'file') return false;
        $obj_cache = Cache::store(Config('cache.type'));
        if (is_null($ttl)) $ttl = Config('session_expire');
        $obj_cache->set($key, $serialize ? serialize($data) : $data, $prefix, $ttl);
        return true;
    }
    /*
         * 通过用户ID 店铺ID 检查 批发中心权限
         *
         * */
    protected function checkSupplierRule($member_id,$vid,$rule_type)
    {
        $pass_flag = false;
        $rule_array = array('visit'=>'sld_is_visit','buy'=>'sld_is_buy');
        if (isset($rule_array[$rule_type])) {
            $member_id = is_numeric($member_id) ? intval($member_id) : 0;
            $vid = is_numeric($vid) ? intval($vid) : 0;
            if ($member_id && $vid) {
                // 店铺
                $sld_level_type = 1;
                $special_condition['sld_level_type'] = $sld_level_type;
                $special_condition['sld_shop_id'] = $vid;
            }else{
                $sld_level_type = 0;
                if ($member_id) {
                    $special_condition['sld_level_type'] = $sld_level_type;
                    $special_condition['sld_member_id'] = $member_id;
                }else{
                    // 未登录
                    $special_condition = array();
                }
            }

            $special_rule = array();
            $model_supplier = Model('sld_supplier');
            if (!empty($special_condition)) {
                // 获取特殊权限
                $special_rule = $model_supplier->getSpecialRuleInfo($special_condition);
            }
            if (!empty($special_rule)) {
                // 特殊权限
                $rule_value = (isset($special_rule[$rule_array[$rule_type]]) && $special_rule[$rule_array[$rule_type]]) ? $special_rule[$rule_array[$rule_type]] : 0;
                $pass_flag = ($rule_value == 1) ? true : false;
            }else{
                // 无特殊权限
                // 获取普通权限
                if ($sld_level_type == 1) {
                    // 店铺等级权限
                    // 店铺等级
                    $store_grade_id = $_SESSION['grade_id'];
                    if ($store_grade_id == 0) {
                        $pass_flag = true;
                    }else{
                        $store_rules = $model_supplier->getNormalRules($sld_level_type);
                        $rule_type_field = 'sld_'.$rule_type.'_level_ids';
                        if (isset($store_rules[$rule_type_field])) {
                            $store_rule = unserialize($store_rules[$rule_type_field]);
                            if (is_array($store_rule) && !empty($store_rule)) {
                                $pass_flag = in_array($store_grade_id, $store_rule) ? true : false;
                            }
                        }
                    }
                }else{
                    if ($member_id) {
                        $grade_info = Model('grade')->getmembergrade($member_id);
                        $member_level_id = $grade_info['id'];
                    }else{
                        // $pass_flag = true;
                        $member_level_id = 'nologin';
                    }

                    $member_rules = $model_supplier->getNormalRules($sld_level_type);
                    $rule_type_field = 'sld_'.$rule_type.'_level_ids';
                    if (isset($member_rules[$rule_type_field])) {
                        $member_rule = unserialize($member_rules[$rule_type_field]);
                        if (is_array($member_rule) && !empty($member_rule)) {
                            $pass_flag = in_array($member_level_id, $member_rule) ? true : false;
                        }
                    }

                }
            }

            if ($rule_type == 'visit') {
                if (!$pass_flag) {
                    // 访问权限 直接报错
                    showMsg(L($rule_type.'_error'));
                }
            }else{
                // 其他权限 返回
                return $pass_flag;
            }
        }else{
            showMsg(Language::get('未找到对应的权限'));
        }
    }
    /*
     * 检查会员登录权限
     */
    public function checkMemberLogin()
    {
        if($_SESSION['is_login'] && $_SESSION['member_id']){
            $member_info = model()->table('member')->where(['member_id'=>$_SESSION['member_id']])->find();
            if(!$member_info['member_state']){
                session_unset();
                session_destroy();
            }
        }
    }
    /*
     * 检测pc是否禁用
     */
    public function checkpcopen()
    {
        if(!C('pc_system_open') || !C('pc_system_isuse')){
            if($_GET['app'] == 'index' && $_GET['mod'] == 'index'){
                header('location:'.C('wap_site_url'));
            }else{
                die;
            }
        }

    }
}