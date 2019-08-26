<?php
namespace app\V1\controller;
use Exception;
use think\facade\Cache;
use think\Controller;
class Base extends Controller
{
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
        if (empty($key) || Config('cache.type') == 'file') return false;
        $obj_cache = \Cache::getInstance(Config('cache.type'));
        $data      = $obj_cache->get($key, $prefix);
        return $unserialize ? unserialize($data) : $data;
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
        $ins = Cache::getInstance(C('cache.type'));
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
        $obj_cache = Cache::getInstance(C('cache.type'));
        if (is_null($ttl)) $ttl = C('session_expire');
        $obj_cache->set($key, $serialize ? serialize($data) : $data, $prefix, $ttl);
        return true;
    }
}