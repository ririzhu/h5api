<?php
/**
 * 取得IP
 *
 *
 * @return string 字符串类型的返回结果
 */

function getIp()
{
    if (@$_SERVER['HTTP_CLIENT_IP'] && $_SERVER['HTTP_CLIENT_IP'] != 'unknown') {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (@$_SERVER['HTTP_X_FORWARDED_FOR'] && $_SERVER['HTTP_X_FORWARDED_FOR'] != 'unknown') {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return preg_match('/^\d[\d.]+\d$/', $ip) ? $ip : '';
}
/**
 * 价格格式化
 *
 * @param int $price
 * @return string    $price_format
 */
function sldPriceFormat($price)
{
    $price_format = number_format($price, 2, '.', '');
    return $price_format;
}
/**
 * 取得商品缩略图的完整URL路径，接收图片名称与店铺ID
 *
 * @param string $file 图片名称
 * @param string $type 缩略图尺寸类型，值为60,160,240,310,1280
 * @param mixed $vid 店铺ID 如果传入，则返回图片完整URL,如果为假，返回系统默认图
 * @return string
 */
function cthumb($file, $type = '', $vid = false) {
    $type_array = explode(',_', ltrim(GOODS_IMAGES_EXT, '_'));
    if (!in_array($type, $type_array)) {
        $type = '240';
    }
    if (empty($file)) {
        return UPLOAD_SITE_URL . '/' . defaultGoodsImage ( $type );
    }
    $search_array = explode(',', GOODS_IMAGES_EXT);

    $file = str_ireplace($search_array,'',$file);
    $fname = basename($file);
    // 取店铺ID
    if ($vid === false || !is_numeric($vid)) {
        $vid = substr ( $fname, 0, strpos ( $fname, '_' ) );
    }

    if(OSS_ENABLE||QINIU_ENABLE){
        $full_url = UPLOAD_SITE_URL . '/' . ATTACH_GOODS . '/' . $vid . '/' . $file;
        if(OSS_ENABLE){
            if (oss_exists($full_url)) {
                if($type) {
                    return $full_url . '?x-oss-process=image/resize,m_lfit,h_' . $type . ',w_' . $type;
                }else{
                    return $full_url;
                }
            }else{
                return UPLOAD_SITE_URL . '/' . defaultGoodsImage ( $type );
            }
        }else if(QINIU_ENABLE){
            if (qiniu_exists($full_url)) {
                if($type) {
                    return $full_url . '?imageView2/1/'.'w/'.$type.'/h/'.$type;
                }else{
                    return $full_url;
                }
            }
        }
    }

    // 本地存储时，增加判断文件是否存在，用默认图代替
    if (!is_file(BASE_UPLOAD_PATH . '/' . ATTACH_GOODS . '/' . $vid . '/' . ($type == '' ? $file : str_ireplace('.', '_' . $type . '.', $file)))) {
        return UPLOAD_SITE_URL . '/' . defaultGoodsImage($type);
    }
    $thumb_host = UPLOAD_SITE_URL . '/' . ATTACH_GOODS;
    return $thumb_host . '/' . $vid . '/' . ($type == '' ? $file : str_ireplace('.', '_' . $type . '.', $file));
}
/**
 * KV缓存 读
 *
 * @param string $key 缓存名称
 * @param boolean $callback 缓存读取失败时是否使用回调 true代表使用cache.model中预定义的缓存项 默认不使用回调
 * @param callable $callback 传递非boolean值时 通过is_callable进行判断 失败抛出异常 成功则将$key作为参数进行回调
 * @return mixed
 */
function rkcache($key, $callback = false)
{

    if (Config('cache_open')) {
        $cacher = Cache::getInstance(Config('cache.type'));
    } else {
        $cacher = Cache::getInstance("cache", true);
    }
    if (!$cacher) {
        throw new Exception('Cannot fetch cache object!');
    }

    $value = $cacher->get($key);
    if (($value === false || empty($value)) && $callback !== false) {
        if ($callback === true) {
            $callback = array(Model('cache'), 'call');
        }
        if (!is_callable($callback)) {
            throw new Exception('Invalid rkcache callback!');
        }
        $value = call_user_func($callback, $key);
        wkcache($key, $value);
    }
    return $value;
}
/**
 * KV缓存 删
 *
 * @param string $key 缓存名称
 * @return boolean
 */
function dkcache($key)
{
    if (C('cache_open')) {
        $cacher = Cache::getInstance(C('cache.type'));
    } else {
        $cacher = Cache::getInstance(C('cache.type'), null);
    }
    if (!$cacher) {
        throw new Exception('Cannot fetch cache object!');
    }
    return $cacher->rm($key);
}