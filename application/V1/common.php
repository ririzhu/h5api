<?php
/**
 * 取得IP
 *
 *
 * @return string 字符串类型的返回结果
 */
use think\facade\Request;
define("BASE_PATH",str_replace('\\','/',dirname(__FILE__)));
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
function con_addons($addons, $par = array(), $mod = '', $app = '', $app_id = '')
{
    if (!$addons) {
        if (Config('debug_404')) {
            ShowNoDebug();
        } else {
            throw_exception('参数不正确');
        }
    }
    if (!$app) {
        $app = $addons;//$_GET['app'];
    }
    if (!$mod) {

        $mod = '';Request::action();
    }
    if ($app_id) {
        if (APP_ID == 'mall') {
            $addons_path = '../addons/' . $addons . '/control/' . $app_id . '/' . $app . '.php';
        } else {
            $addons_path = '../addons/' . $addons . '/control/' . $app_id . '/' . $app . '.php';
        }
    } else {
        if (APP_ID == 'mall') {
            $addons_path = '../addons/' . $addons . '/control/' . APP_ID . '/' . $app . '.php';
        } else {
            $addons_path = '../addons/' . $addons . '/control/' . APP_ID . '/' . $app . '.php';
        }
    }
    if (!include_once($addons_path)) {
        if (Config('debug_404')) {
            ShowNoDebug();
        } else {
            throw_exception("插件目录没有找到");
        }
    }
    $class_name = $addons ;//. '_' . $app;// . "Add";
    if (!class_exists($class_name)) {
        if (Config('debug_404')) {
            //ShowNoDebug();
        } else {
            //throw_exception("类没有找到");
        }
    }
    $re = $class_name::$mod($par);

    return $re;
}
/**
 * 取得商品默认大小图片
 *
 * @param string $key 图片大小 small tiny
 * @return string
 */
function defaultGoodsImage($key)
{
    if (OSS_ENABLE) {
        $file = Config('default_goods_image') . '?x-oss-process=image/resize,m_lfit,h_' . $key . ',w_' . $key;;
    } else if (QINIU_ENABLE) {
        $file = Config('default_goods_image') . '?imageView2/1/' . 'w/' . $key . 'h/' . $key;
    } else {
        $file = str_ireplace('.', '_' . $key . '.', Config('default_goods_image'));
    }
    return ATTACH_COMMON . DS . $file;
}
/**
 * 加密函数
 *
 * @param string $txt 需要加密的字符串
 * @param string $key 密钥
 * @return string 返回加密结果
 */
function encrypt($txt, $key = '')
{
    if (empty($txt)) return $txt;
    if (empty($key)) $key = md5(MD5_KEY);
    $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_.";
    $ikey  = "-x6g6ZWm2G9g_vr0Bo.pOq3kRIxsZ6rm";
    $nh1   = rand(0, 64);
    $nh2   = rand(0, 64);
    $nh3   = rand(0, 64);
    $ch1   = $chars{$nh1};
    $ch2   = $chars{$nh2};
    $ch3   = $chars{$nh3};
    $nhnum = $nh1 + $nh2 + $nh3;
    $knum  = 0;
    $i     = 0;
    while (isset($key{$i})) $knum += ord($key{$i++});
    $mdKey = substr(md5(md5(md5($key . $ch1) . $ch2 . $ikey) . $ch3), $nhnum % 8, $knum % 8 + 16);
    $txt   = base64_encode(time() . '_' . $txt);
    $txt   = str_replace(array('+', '/', '='), array('-', '_', '.'), $txt);
    $tmp   = '';
    $j     = 0;
    $k     = 0;
    $tlen  = strlen($txt);
    $klen  = strlen($mdKey);
    for ($i = 0; $i < $tlen; $i++) {
        $k   = $k == $klen ? 0 : $k;
        $j   = ($nhnum + strpos($chars, $txt{$i}) + ord($mdKey{$k++})) % 64;
        $tmp .= $chars{$j};
    }
    $tmplen = strlen($tmp);
    $tmp    = substr_replace($tmp, $ch3, $nh2 % ++$tmplen, 0);
    $tmp    = substr_replace($tmp, $ch2, $nh1 % ++$tmplen, 0);
    $tmp    = substr_replace($tmp, $ch1, $knum % ++$tmplen, 0);
    return $tmp;
}
/**
 * 低于php 5.5 的 array_column 方法
 *
 * @param $input  二维数组
 * @param $columnKey   提取字段
 * @return null
 */

function low_array_column($input, $columnKey, $indexKey = NULL)
{
    if (!function_exists('array_column')) {
        $columnKeyIsNumber = (is_numeric($columnKey)) ? true : false;
        $indexKeyIsNull    = (is_null($indexKey)) ? true : false;
        $indexKeyIsNumber  = (is_numeric($indexKey)) ? true : false;
        $result            = array();
        foreach ((array)$input as $key => $row) {
            if ($columnKeyIsNumber) {
                $tmp = array_slice($row, $columnKey, 1);
                $tmp = (is_array($tmp) && !empty($tmp)) ? current($tmp) : null;
            } else {
                $tmp = isset($row[$columnKey]) ? $row[$columnKey] : $row;
            }
            if (!$indexKeyIsNull) {
                if ($indexKeyIsNumber) {
                    $key = array_slice($row, $indexKey, 1);
                    $key = (is_array($key) && !empty($key)) ? current($key) : null;
                    $key = is_null($key) ? 0 : $key;
                } else {
                    $key = isset($row[$indexKey]) ? $row[$indexKey] : 0;
                }

            } else {
                if ($indexKeyIsNumber) {
                    $key = array_slice($row, $indexKey, 1);
                    $key = (is_array($key) && !empty($key)) ? current($key) : null;
                    $key = is_null($key) ? 0 : $key;
                } else {
                    $key = isset($row[$indexKey]) ? $row[$indexKey] : $key;
                }
            }
            $result[$key] = $tmp;
        }

        return $result;
    } else {
        return array_column($input, $columnKey, $indexKey);
    }
    return $result;
}
