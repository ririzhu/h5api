<?php
/**
 * 取得IP
 *
 *
 * @return string 字符串类型的返回结果
 */

use OSS\Core\OssException;
use think\facade\Request;
use think\model;
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
/**
 * 解密函数
 *
 * @param string $txt 需要解密的字符串
 * @param string $key 密匙
 * @return string 字符串类型的返回结果
 */
function decrypt($txt, $key = '', $ttl = 0)
{
    if (empty($txt)) return $txt;
    if (empty($key)) $key = md5(MD5_KEY);

    $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_.";
    $ikey  = "-x6g6ZWm2G9g_vr0Bo.pOq3kRIxsZ6rm";
    $knum  = 0;
    $i     = 0;
    $tlen  = strlen($txt);
    while (isset($key{$i})) $knum += ord($key{$i++});
    $ch1   = $txt{$knum % $tlen};
    $nh1   = strpos($chars, $ch1);
    $txt   = substr_replace($txt, '', $knum % $tlen--, 1);
    $ch2   = $txt{$nh1 % $tlen};
    $nh2   = strpos($chars, $ch2);
    $txt   = substr_replace($txt, '', $nh1 % $tlen--, 1);
    $ch3   = $txt{$nh2 % $tlen};
    $nh3   = strpos($chars, $ch3);
    $txt   = substr_replace($txt, '', $nh2 % $tlen--, 1);
    $nhnum = $nh1 + $nh2 + $nh3;
    $mdKey = substr(md5(md5(md5($key . $ch1) . $ch2 . $ikey) . $ch3), $nhnum % 8, $knum % 8 + 16);
    $tmp   = '';
    $j     = 0;
    $k     = 0;
    $tlen  = strlen($txt);
    $klen  = strlen($mdKey);
    for ($i = 0; $i < $tlen; $i++) {
        $k = $k == $klen ? 0 : $k;
        $j = strpos($chars, $txt{$i}) - $nhnum - ord($mdKey{$k++});
        while ($j < 0) $j += 64;
        $tmp .= $chars{$j};
    }
    $tmp = str_replace(array('-', '_', '.'), array('+', '/', '='), $tmp);
    $tmp = trim(base64_decode($tmp));

    if (preg_match("/\d{10}_/s", substr($tmp, 0, 11))) {
        if ($ttl > 0 && (time() - substr($tmp, 0, 11) > $ttl)) {
            $tmp = null;
        } else {
            $tmp = substr($tmp, 11);
        }
    }
    return $tmp;
}
//访问客服的接口获取聊天的客服id
function getservice($uid)
{
    $url = Config('service_url') . '/admin/event/getservice/uid/' . $uid;
    return @file_get_contents($url);
}

//访问客服的接口获取聊天的最后一句的时间
function getservice_time($uid)
{
    $url = Config('service_url') . '/admin/event/getservice_time/uid/' . $uid;
    return @file_get_contents($url);
}

//给默认值 $judge 用于判断的变量，$not 没有则显示这个， $pre  有则在左面 加上$pre   $left 默认为$pre加在左面
function dft($judge, $not = '', $pre = '', $left = 'true')
{
    $return = '';
    if (empty($judge) || floatval($judge) == 0) {
        $return = $not;
    } else {
        $return .= $left ? $pre : '';
        $return .= $judge;
        $return .= $left ? '' : $pre;

    }

    return $return;
}
function Sec2Time($time){
    if(is_numeric($time)){
        $value = array(
            "years" => 0, "days" => 0, "hours" => 0,
            "minutes" => 0, "seconds" => 0,
        );
        $t = '';
        if($time >= 31556926){
            $value["years"] = floor($time/31556926);
            $time = ($time%31556926);
            $t.= $value["years"] .lang('年');
        }
        if($time >= 86400){
            $value["days"] = floor($time/86400);
            $time = ($time%86400);
            $t.=$value["days"] .lang("天")." ";
        }
        if($time >= 3600){
            $value["hours"] = floor($time/3600);
            $time = ($time%3600);
            $t.=$value["hours"] .lang("小时");
        }
        if($time >= 60){
            $value["minutes"] = floor($time/60);
            $time = ($time%60);
            $t.=$value["minutes"] .lang("分");
        }
        $value["seconds"] = floor($time);
        if($value['seconds']>0) {
            //return (array) $value;
            $t .= $value["seconds"] . lang('秒');
        }
        Return $t;

    }else{
        return (bool) FALSE;
    }
}
/**
 * 商城会员中心使用的URL链接函数，强制使用动态传参数模式
 *
 * @param string $app control文件名
 * @param string $mod op方法名
 * @param array $args URL其它参数
 * @param string $store_domian 店铺二级域名
 * @return string
 */
function urlShop($app = '', $mod = '', $args = array(), $store_domain = '')
{
    // 开启店铺二级域名
    if (intval(Config('enabled_subdomain')) == 1 && !empty($store_domain)) {
        return 'http://' . $store_domain . '.' . SUBDOMAIN_SUFFIX . '/';
    }
    $id           = array();
    // 默认标志为不开启伪静态
    $rewrite_flag = false;

    // 如果平台开启伪静态开关，并且为伪静态模块，修改标志为开启伪静态
    $rewrite_item = array(
        'goods:index',
        'goods:comments_list',
        'goodslist:index',
        'vendor:index',
        'vendor:all',
        'article:index',
        'document:index',
        'brand:list',
        'brand:index',
        'tuan:index',
        'tuan:tuan_comming',
        'tuan:tuan_history',
        'tuan:tuandetail',
        'pointprod:index',
        'pointvoucher:index',
        'pointprod:pinfo',
        'pointprod:plist',
        'v_dt:index'
    );
    if (URL_MODEL && in_array($app . ':' . $mod, $rewrite_item)) {
        $rewrite_flag = true;
        $tpl_args     = array();        // url参数临时数组
        $id           = array();
        switch ($app . ':' . $mod) {
            case 'goodslist:index':
                if (isset($args['keyword'])) {
                    $rewrite_flag = false;
                    break;
                }
                $id['cid']        = empty($args['cid']) ? 0 : $args['cid'];
                $id['b_id']       = empty($args['b_id']) || intval($args['b_id']) == 0 ? 0 : $args['b_id'];
                $id['a_id']       = empty($args['a_id']) || intval($args['a_id']) == 0 ? 0 : $args['a_id'];
                $tpl_args['key']  = empty($args['key']) ? 0 : $args['key'];
                $tpl_args['sort'] = empty($args['sort']) ? 0 : $args['sort'];
                $tpl_args['t']    = empty($args['t']) ? 0 : $args['t'];
                $id['area_id']    = empty($args['area_id']) ? 0 : $args['area_id'];
                $tpl_args['pn']   = empty($args['pn']) ? 0 : $args['pn'];
                $args             = $tpl_args;
                break;
            case 'vendor:all':
                if (isset($args['keyword'])) {
                    $rewrite_flag = false;
                    break;
                }
                $id['vid']        = empty($args['vid']) ? 0 : $args['vid'];
                $id['stc_id']     = empty($args['stc_id']) ? 0 : $args['stc_id'];
                $tpl_args['key']  = empty($args['key']) ? 0 : $args['key'];
                $tpl_args['sort'] = empty($args['sort']) ? 0 : $args['sort'];
                $tpl_args['pn']   = empty($args['pn']) ? 0 : $args['pn'];
                $args             = $tpl_args;
                break;
            case 'vendor:index':
                if (isset($args['keyword'])) {
                    $rewrite_flag = false;
                    break;
                }
                $id   = $args;
                $args = array();
                break;
            case 'brand:list':
                $id['brand']         = empty($args['brand']) ? 0 : $args['brand'];
                $tpl_args['key']     = empty($args['key']) ? 0 : $args['key'];
                $tpl_args['order']   = empty($args['order']) ? 0 : $args['order'];
                $tpl_args['type']    = empty($args['type']) ? 0 : $args['type'];
                $tpl_args['area_id'] = empty($args['area_id']) ? 0 : $args['area_id'];
                $tpl_args['pn']      = empty($args['pn']) ? 0 : $args['pn'];
                $args                = $tpl_args;
                break;
            case 'tuan:index':
            case 'tuan:tuan_comming':
            case 'tuan:tuan_history':
                $id['area_id']     = empty($args['area_id']) ? 0 : $args['area_id'];
                $id['tuan_class']  = empty($args['tuan_class']) ? 0 : $args['tuan_class'];
                $id['tuan_price']  = empty($args['tuan_price']) ? 0 : $args['tuan_price'];
                $tpl_args['psort'] = empty($args['psort']) ? 0 : $args['psort'];
//                $tpl_args['tuan_order'] = empty($args['tuan_order']) ? 0 : $args['tuan_order'];
                $tpl_args['pn'] = empty($args['pn']) ? 0 : $args['pn'];
                $args           = $tpl_args;
                break;
            case 'tuan:tuandetail':
                $id   = $args;
                $args = array();
                break;
            case 'goods:comments_list':
                $id['gid']        = empty($args['gid']) ? 0 : $args['gid'];
                $tpl_args['type'] = empty($args['type']) ? 0 : $args['type'];
                $tpl_args['pn']   = empty($args['pn']) ? 0 : $args['pn'];
                $args             = $tpl_args;
                break;
            case 'article:index':
                $id   = $args;
                $args = array();
                break;
            case 'goods:index':
                $id   = $args;
                $args = array();
                break;
            case 'v_dt:index':
//                $id['vid'] = empty($args['vid']) ? 0 : $args['vid'];
//                if(!empty($args['t'])){
//                    $id['t'] = $args['t'];
//                }
                $id   = $args;
                $args = array();
                break;
            default:
                break;
        }
    }
    return urlById($app, $mod, $id, $args, $rewrite_flag, MALL_URL);
}
/**
 * 拼接动态URL，参数需要小写
 *
 * 调用示例
 *
 * 若指向网站首页，可以传空:
 * url() => 表示app和mod均为index，返回当前站点网址
 *
 * url('search,'index','array('cid'=>2)); 实际指向 index.php?app=goodslist&mod=index&cid=2
 * 传递数组参数时，若app（或mod）值为index,则可以省略
 * 上面示例等同于
 * url('search','',array('app'=>'search','cid'=>2));
 *
 * @param string $app control文件名
 * @param string $mod op方法名
 * @param $id
 * @param array $args URL其它参数
 * @param boolean $model 默认取当前系统配置
 * @param string $site_url 生成链接的网址，默认取当前网址
 * @return string
 */
function urlById($app = '', $mod = '', $id = array(), $args = array(), $model = false, $site_url = '')
{
    //伪静态文件扩展名
    $ext = '.html';
    //入口文件名
    $file = 'index.php';
//    $site_url = empty($site_url) ? MALL_SITE_URL : $site_url;
    $app  = trim($app);
    $mod  = trim($mod);
    $args = !is_array($args) ? array() : $args;
    //定义变量存放返回url
    $url_string = '';
    if (empty($app) && empty($mod) && empty($args)) {
        return $site_url;
    }
    $app = !empty($app) ? $app : 'index';
    $mod = !empty($mod) ? $mod : 'index';

    $model = $model ? URL_MODEL : $model;

    if ($model) {
        //伪静态模式

        $url_perfix = "{$app}-{$mod}";

        if (!empty($id)) {
            $url_perfix .= '-';
        }
        //$url_string = $url_perfix.http_build_query($args,'','-').$ext;
        $url_string = $url_perfix . http_build_query($id, '', '-') . $ext;
        $url_string = str_replace('=', '-', $url_string) . (empty($args) ? '' : ('?' . http_build_query($args)));
    } else {
        //默认路由模式
        if ($mod == 'index') {
            $url_perfix = "app={$app}";
        } else {
            $url_perfix = "app={$app}&mod={$mod}";
        }
//        $url_perfix = "app={$app}&mod={$mod}";
        if (!empty($args)) {
            $url_perfix .= '&';
        }

        $url_string = $file . '?' . $url_perfix . http_build_query($args);//http_build_query($id).(empty($args)?'':('&'.http_build_query($args)));
    }
    //将商品、店铺、分类、品牌、文章自动生成的伪静态URL使用短URL代替
    $reg_match_from = array(
        '/^goods-index-gid-(\d+)\.html$/',
        '/^vendor-index-vid-(\d+)\.html$/',
        '/^vendor-all-vid-(\d+)-stc_id-(\d+)\.html/',
        '/^article-index-id-(\d+)\.html$/',
        '/^article-index-acid-(\d+)\.html$/',
        '/^document-index-code-([a-z_]+)\.html$/',
        '/^goodslist-index-cid-(\d+)-b_id-([0-9_]+)-a_id-([0-9_]+)-area_id-(\d+).html?/',
        '/^brand-list-brand-(\d+)-key-([0-3])-order-([0-2])-type-([0-2])-area_id-(\d+)-pn-(\d+)\.html$/',
        '/^brand-index\.html$/',
        '/^tuan-index-area_id-(\d+)-tuan_class-(\d+)-tuan_price-(\d+)\.html/',
        '/^tuan-tuan_comming-area_id-(\d+)-tuan_class-(\d+)-tuan_price-(\d+)\.html/',
        '/^tuan-tuan_history-area_id-(\d+)-tuan_class-(\d+)-tuan_price-(\d+)\.html/',
        '/^tuan-tuandetail-tuan_id-(\d+).html$/',
        '/^pointprod-index.html$/',
        '/^pointprod-plist.html$/',
        '/^pointprod-pinfo-id-(\d+).html$/',
        '/^pointvoucher-index.html$/',
        '/^goods-comments_list-gid-(\d+)-type-([0-3])-pn-(\d+).html$/',
        '/^v_dt-index-vid-(\d+)-t-([0-4]).html/',
        '/^v_dt-index-vid-(\d+).html/'
    );
    $reg_match_to   = array(
        'product-\\1.html',
        'v-\\1.html',
        'vl-\\1-\\2.html',
        'help-\\1.html',
        'list-\\1.html',
        'document-\\1.html',
        'cat-\\1-\\2-\\3-\\4.html',
        'brand-\\1-\\2-\\3-\\4-\\5-\\6.html',
        'brand.html',
        't-\\1-\\2-\\3.html',
        't0-\\1-\\2-\\3.html',  //未开团
        't1-\\1-\\2-\\3.html',   //往期团
        'td-\\1.html',                 //团购详情
        'point.html',
        'point_list.html',
        'point_item-\\1.html',
        'quan.html',
        'comments-\\1-\\2-\\3.html',
        'v_dt-\\1-\\2.html',
        'v_dt-\\1.html',
    );
    $url_string     = preg_replace($reg_match_from, $reg_match_to, $url_string);
    return rtrim($site_url, '/') . '/' . $url_string;
}
/**
 * 取得商品缩略图的完整URL路径，接收商品信息数组，返回所需的商品缩略图的完整URL
 *
 * @param array $goods 商品信息数组
 * @param string $type 缩略图类型  值为60,160,240,310,1280
 * @return string
 */
function thumb($goods = array(), $type = ''){
    $sld_fixture_types = array('real');
    if (!in_array($type,$sld_fixture_types)) {
        $type_array = explode(',_', ltrim(GOODS_IMAGES_EXT, '_'));
        if (!in_array($type, $type_array)) {
            $type = '240';
        }
    }else{
        $type = '';
    }
    if (array_key_exists('apic_cover', $goods) && !empty($goods)) {
        $goods['goods_image'] = $goods['apic_cover'];
    }

    //oss先处理
    if(OSS_ENABLE||QINIU_ENABLE){
        $search_array = explode(',', GOODS_IMAGES_EXT);
        $file = str_ireplace($search_array,'',$goods['goods_image']);
        $fname = basename($file);
        //取店铺ID
        if (preg_match('/^(\d+_)/',$fname)){
            $vid = substr($fname,0,strpos($fname,'_'));
        }else{
            $vid = $goods['vid'];
        }
        if(!$goods['vid'] && array_key_exists('apic_cover', $goods) && $type == '') {
            $full_url = UPLOAD_SITE_URL.'/'.ATTACH_GOODS.'/'.'fixture'.'/'.$file;
        }else{
            $full_url = UPLOAD_SITE_URL . '/' . ATTACH_GOODS . '/' . $vid . '/' . $file;
        }

        if(OSS_ENABLE){
            if (oss_exists($full_url)) {
                if($type) {
                    return $full_url . '?x-oss-process=image/resize,m_lfit,h_' . $type . ',w_' . $type;
                }else{
                    return $full_url;
                }
            }else{
                $file = $type == '' ? $file : str_ireplace('.', '_' . $type . '.', $file);

                // 验证内置装修图片是否存在
                if (is_file(BASE_STATIC_PATH.'/'.FIXTURE_PATH.'/'.'vendor'.'/'.$file)) {
                    return STATIC_SITE_URL.'/'.FIXTURE_PATH.'/'.'vendor'.'/'.$file;
                }else{
                    return UPLOAD_SITE_URL.'/'.defaultGoodsImage($type);
                }
            }
        }else if(QINIU_ENABLE){
            if(qiniu_exists($full_url)){
                if($type) {
                    return $full_url . '?imageView2/1/'.'w/'.$type.'/h/'.$type;
                }else{
                    return $full_url;
                }
            }
        }
    }


    if (empty($goods)){
        return UPLOAD_SITE_URL.'/'.defaultGoodsImage($type);
    }
    if (empty($goods['goods_image'])) {
        return UPLOAD_SITE_URL.'/'.defaultGoodsImage($type);
    }
    $search_array = explode(',', GOODS_IMAGES_EXT);
    $file = str_ireplace($search_array,'',$goods['goods_image']);
    $fname = basename($file);
    //取店铺ID
    if (preg_match('/^(\d+_)/',$fname)){
        $vid = substr($fname,0,strpos($fname,'_'));
    }else{
        $vid = $goods['vid'];
    }
    $file = $type == '' ? $file : str_ireplace('.', '_' . $type . '.', $file);

    if (!$goods['vid'] && array_key_exists('apic_cover', $goods) && $type == '') {
        // 验证内置装修图片是否存在
        if (is_file(BASE_UPLOAD_PATH.'/'.ATTACH_GOODS.'/'.'fixture'.'/'.$file)) {
            return UPLOAD_SITE_URL.'/'.ATTACH_GOODS.'/'.'fixture'.'/'.$file;
        }else{
            return UPLOAD_SITE_URL.'/'.defaultGoodsImage($type);
        }
    }else{
        if (!is_file(BASE_UPLOAD_PATH.'/'.ATTACH_GOODS.'/'.$vid.'/'.$file)){
            return UPLOAD_SITE_URL.'/'.defaultGoodsImage($type);
        }
        $thumb_host = UPLOAD_SITE_URL.'/'.ATTACH_GOODS;
        return $thumb_host.'/'.$vid.'/'.$file;
    }
}
/**
 * 判断七牛云文件是否存在
 *
 * @param string $bucket 文件路径
 * @return null
 */
function qiniu_exists($object)
{
    return true;

}
/**
 * 判断oss文件是否存在
 *
 * @param string $bucket 文件路径
 * @return null
 */
function oss_exists($object)
{
    $object          = 'data/upload' . str_replace(UPLOAD_SITE_URL, '', $object);
    $accessKeyId     = $GLOBALS['setting_config']['oss_key'];
    $accessKeySecret = $GLOBALS['setting_config']['oss_sc'];
    $endpoint        = $GLOBALS['setting_config']['oss_url'];
    $bucket          = $GLOBALS['setting_config']['oss_pre'];
    $ossClient       = new \OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);
    try {
        $exist = $ossClient->doesObjectExist($bucket, $object);
    } catch (OssException $e) {
        $e->getErrorMessage();
    }
    return $exist;
}
/**
 * 返回以原数组某个值为下标的新数据
 *
 * @param array $array
 * @param string $key
 * @param int $type 1一维数组2二维数组
 * @return array
 */
function array_under_reset($array, $key, $type = 1)
{
    if (is_array($array)) {
        $tmp = array();
        foreach ($array as $v) {
            if ($type === 1) {
                $tmp[$v[$key]] = $v;
            } elseif ($type === 2) {
                $tmp[$v[$key]][] = $v;
            }
        }
        return $tmp;
    } else {
        return $array;
    }
}

/**
 * 数组转字符串
 * @param $arr
 */
function arrayToString($arr){
    $ids = "";
    foreach($arr as $k=>$v){
        $ids .=$v.",";
    }
    $ids = substr($ids,0,strlen($ids)-1);
    return $ids;
}
function request_post($url = '', $ispost = true, $post_data = array())
{
    if (empty($url) || empty($post_data)) {
        return false;
    }

    $o = "";
    foreach ($post_data as $k => $v) {
        $o .= "$k=" . urlencode($v) . "&";
    }
    $post_data = substr($o, 0, -1);

    if ($ispost) {
        $url = $url;
    } else {
        $url = $url . '?' . $post_data;
    }

    $curlPost = 'key=' . $post_data['key'];
    header("Content-type: text/html; charset=utf-8");
    $ch = curl_init();//初始化curl
    curl_setopt($ch, CURLOPT_URL, $url);//抓取指定网页
    curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
    if ($ispost) {
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
    }
    $data = curl_exec($ch);//运行curl
    curl_close($ch);
    return $data;
}
/**
 * 取得订单状态文字输出形式
 *
 * @param array $order_info 订单数组
 * @return string $order_state 描述输出
 */
function orderState($order_info) {
    switch ($order_info['order_state']) {
        case ORDER_STATE_CANCEL:
            $order_state = Lang('状态文字：已取消');
            break;
        case ORDER_STATE_NEW:
            $order_state = Lang('状态文字：待付款');
            break;
        case ORDER_STATE_PAY:
            $order_state = Lang('状态文字：待发货');
            break;
        case ORDER_STATE_SEND:
            if($order_info['dian_id']>0){
                if($order_info['ziti']==1){
                    $order_state = Lang('状态文字：待自提');
                }else{
                    $order_state = Lang('状态文字：门店配送');
                }
            }else{
                $order_state = Lang('状态文字：待收货');
            }

            break;
        case ORDER_STATE_SUCCESS:
            $order_state = Lang('状态文字：交易完成');
            break;
    }
    return $order_state;
}
/**
 * 取得订单支付类型文字输出形式
 *
 * @param array $payment_code
 * @return string
 */
function orderPaymentName($payment_code) {

    $zh_cn = array('货到付款','在线付款','支付宝','财付通','网银在线','预存款','微信扫码支付','微信公众号支付','微信APP支付','微信小程序支付','微信支付');
    $en = array('cod','online','alipay','tenpay','chinabank','deposit','wechat scan','wechat h5','wechat app','wehcat mini','wechat');

    return str_replace(
        array('offline','online','alipay','tenpay','chinabank','predeposit','wx_saoma','wxpay_jsapi','weixin','mini_wxpay','wxpay'),
        LANG_TYPE=='zh_cn'?$zh_cn:$en,
        $payment_code);
}
/**
 * 取得订单商品销售类型文字输出形式
 *
 * @param array $goods_type
 * @return string 描述输出
 */
function orderGoodsType($goods_type) {
    return strtr($goods_type,[
        '1'=>'',
        '2'=>'团购',
        '3'=>'限时折扣',
        '4'=>'优惠套装',
        '5'=>'赠品',
        '6'=>'批发商品',
        '7'=>'拼团商品',
        '8'=>'手机专享',
        '9'=>'阶梯团购',
        '10'=>'预售',
    ]);
}
/**
 * 取得订单状态文字输出形式---商户后台专用
 *
 * @param array $order_info 订单数组
 * @return string $order_state 描述输出
 */
function orderStateVendor($order_info) {
    switch ($order_info['order_state']) {
        case ORDER_STATE_CANCEL:
            $order_state = '已取消';
            break;
        case ORDER_STATE_NEW:
            $order_state = '待付款';
            break;
        case ORDER_STATE_PAY:
            $order_state = '待发货';
            break;
        case ORDER_STATE_SEND:
            if($order_info['dian_id']>0){
                if($order_info['ziti']==1){
                    $order_state = '待自提';
                }else{
                    $order_state = '门店配送';
                }
            }else{
                $order_state = '待收货';
            }

            break;
        case ORDER_STATE_SUCCESS:
            $order_state = '交易完成';
            break;
    }
    return $order_state;
}
/**
 * 检测FORM是否提交
 * @param  $check_token 是否验证token
 * @param  $check_captcha 是否验证验证码
 * @param  $return_type 'alert','num'
 * @return boolean
 */
function chksubmit($check_token = false, $check_captcha = false, $return_type = 'alert')
{
    $submit = isset($_POST['form_submit']) ? $_POST['form_submit'] : $_GET['form_submit'];
    if ($submit != 'ok') return false;
    if ($check_token && !Security::checkToken()) {
        if ($return_type == 'alert') {
            showDialog('Token error!');
        } else {
            return -11;
        }
    }
    if ($check_captcha) {
        if (!checkSeccode(getSldhash(), $_POST['captcha'])) {
            setBbcCookie('randcode' . $_POST['sldcode'], '', -3600);
            if ($return_type == 'alert') {
                showDialog('验证码错误!');
            } else {
                return -12;
            }
        }
    }
    return true;
}
/**
 * 行为模型实例
 *
 * @param string $model 模型名称
 * @return obj 对象形式的返回结果
 */
function Logic($model = null, $base_path = null)
{
    static $_cache = array();
    $cache_key = $model . '.' . $base_path;
    if (!is_null($model) && isset($_cache[$cache_key])) return $_cache[$cache_key];
    $file_name  = BASE_PATH . '/operate/' . $model . '.php';
    $class_name = $model;
    if (!file_exists($file_name)) {
        return $_cache[$cache_key] = new $model();
    } else {
        require_once($file_name);
        if (!class_exists($class_name)) {
            $error = 'Operate Error:  Class ' . $class_name . ' is not exists!';
            java_throw_exceptions($error);
        } else {
            return $_cache[$cache_key] = new $class_name();
        }
    }
}