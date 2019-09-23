<?php
namespace app\v1\controller;
use app\admin\model\Token;
use Exception;
use think\facade\Cache;
use think\Controller;
use think\Lang;
use think\Request;
use Firebase\JWT\JWT;
use think\db;
class Base extends Controller
{
    /**
     * init
     */
    public  function __construct() {
        global $setting_config;
        //$request = new Request();
        $controller=request()->controller();
        $action=request()->action();
        if(!$this->checkouth()){
            json($data['msg']="missing token" )->code(201)->send();exit;
            exit;
        };
        //获取头部token，查询有无权限
        $headertoken = str_replace("Bearer ","",request()->header('Authorization'));
        $user = db::name("api_user")->where("token='$headertoken'")->find();
        if(count($user)>0){
            if($user['name']=="Horizou"){

            }else{
                //查询会员状态
                if($user['endtime']<=TIMESTAMP && $user['endtime']!=""){
                    json($data['msg']="expired token" )->code(201)->send();exit;
                }else if($user['status']==0){
                    json($data['msg']="not normal status token" )->code(201)->send();exit;
                }else{
                    //查询组别状态
                    $groupinfo = db::name("api_group")->where("id=".$user['groupid'])->find();
                    if($groupinfo['status']==0){
                        json($data['msg']="not normal group status" )->code(201)->send();exit;
                    }else{
                        //查询你的访问的接口是否在allowlist中
                        if($groupinfo['allowlist']!=""){
                            $modulelist = db::name("api_module")->field("modulename,id")->where("id in (".$groupinfo['allowlist'].") and modulename='$controller'")->find();
                            if(count($modulelist)>0){
                                $apiid = $modulelist['id'];
                                $apistatus = (db::name("api")->field("status")->where("id=$apiid and name='$controller/$action'")->find())['status'];
                                if($apistatus==0){
                                    json($data['msg']="this api server is off" )->code(201)->send();exit;
                                }
                            }else{
                                json($data['msg']="your token has not enough Right to visit this api" )->code(201)->send();exit;
                            }
                        }else{
                            $modulelist = db::name("api_module")->field("modulename,id")->where("modulename='$controller'")->find();
                            if(count($modulelist)>0){
                                $apiid = $modulelist['id'];
                                $apistatus = (db::name("api")->field("status")->where("id=$apiid and name='$controller/$action'")->find())['status'];
                                if($apistatus==0){
                                    json($data['msg']="this api server is off" )->code(201)->send();exit;
                                }
                            }else{
                                json($data['msg']="your token has not enough Right to visit this api" )->code(201)->send();exit;
                            }
                        }
                    }
                }
            }
        }else{
            json($data['msg']="wrong token" )->code(201)->send();exit;
            exit;
        }
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
                $callback = array(new \app\v1\model\Cache(), 'call');
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
/**
 * 获得几天前，几小时前，几月前
 * @param int $time 时间戳
 * @param array $unit 时间单位
 * @return bool|string
 */
function date_before($time, $unit = null) {
    $time = intval($time);
    $unit = is_null($unit) ? array("年", "月", "星期", "天", "小时", "分钟", "秒") : $unit;
    switch (true) {
        case $time < (time() - 31536000) :
            return floor((time() - $time) / 31536000) . $unit[0] . '前';
        case $time < (time() - 2592000) :
            return floor((time() - $time) / 2592000) . $unit[1] . '前';
        case $time < (time() - 604800) :
            return floor((time() - $time) / 604800) . $unit[2] . '前';
        case $time < (time() - 86400) :
            return floor((time() - $time) / 86400) . $unit[3] . '前';
        case $time < (time() - 3600) :
            return floor((time() - $time) / 3600) . $unit[4] . '前';
        case $time < (time() - 60) :
            return floor((time() - $time) / 60) . $unit[5] . '前';
        default :
            return '刚刚';
    }
}
    public function checkouth(){
     $token =new Token();
     $headertoken = cookie("name");
     if(!isset($headertoken)){
         return false;
         exit;
         }
        else if($token->checkToken($headertoken)){
            return true;
        }else{
            return false;
            exit;
        };
    }
}