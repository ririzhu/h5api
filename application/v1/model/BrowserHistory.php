<?php
namespace app\v1\model;

use app\v1\controller\Base;
use think\Model;
use think\db;
class BrowserHistory extends Model
{
    /**
     * 浏览过的商品
     *
     * @param int $member_id 会员ID（一般传递$_SESSION['member_id']）
     * @param int $shownum 查询的条数，默认0则为返回全部
     * @return array
     */
    public function getViewedGoodsList($member_id = 0,$shownum = 0) {
        $shownum = ($t = intval($shownum))>0?$t:0;
        $browselist = array();
        //如果会员ID存在，则读取数据库浏览历史，或者memcache
        if ($member_id > 0){
            if (!Config('cache_open')){//查询数据库
                $browselist_tmp = $this->table('bbc_goods_browsehistory')->where(array('member_id'=>$member_id))->order('browsetime desc')->limit($shownum)->select();
                if (!empty($browselist_tmp)){
                    foreach ($browselist_tmp as $k=>$v){
                        $browselist[$v['gid']] = $v;
                    }
                }
            } else {
                //生成缓存的键值
                $hash_key = $member_id;
                //先查找$hash_key缓存
                $base = new Base();
                $browse_goodsid = $base->rcache($hash_key,'goodsbrowse','goodsid');
                $goodsid_arr = $browse_goodsid['goodsid']?unserialize($browse_goodsid['goodsid']):array();
                unset($browse_goodsid['goodsid']);
                if ($goodsid_arr){
                    //截取需要的记录数
                    if ($shownum){
                        $goodsid_arr = array_slice($goodsid_arr,-$shownum,$shownum,true);
                    }
                    $goodsid_arr = array_reverse($goodsid_arr,true);//翻转数组，按照添加顺序倒序排列
                    //获得缓存的浏览商品信息
                    $browselist_tmp = $base->rcache($hash_key,'goodsbrowse',implode(',', $goodsid_arr));
                    foreach ($goodsid_arr as $v){
                        $browselist[$v] = $browselist_tmp[$v]?unserialize($browselist_tmp[$v]):array();
                    }
                }
            }
        }
        //查询浏览过的商品记录cookie
        if (!$member_id){
            $browselist = array();
            if(cookie('viewed_goods')){
                $string_viewed_goods = decrypt(cookie('viewed_goods'),MD5_KEY);
                if (get_magic_quotes_gpc()) $string_viewed_goods = stripslashes($string_viewed_goods);//去除斜杠
                $cookie_arr_tmp = unserialize($string_viewed_goods);
                $cookie_arr = array();
                foreach ((array)$cookie_arr_tmp as $k=>$v){
                    $info = explode("-", $v);
                    if (is_numeric($info[0])){
                        $cookie_arr[$info[0]] = intval($info[1]);
                    }
                }
                //截取需要的记录数
                if ($shownum){
                    $cookie_arr = array_slice($cookie_arr,0,$shownum,true);
                }
                $cookie_arr = array_reverse($cookie_arr,true);//翻转数组，按照添加顺序倒序排列
                if ($cookie_arr){
                    foreach ($cookie_arr as $k=>$v){
                        $browselist[$k] = array('browsetime'=>$v);
                    }
                }
            }
        }
        //查询商品数据
        $browselist_new = array();
        if ($browselist){
            $goods = new Goods();
            $ids = "";
            foreach ($browselist as $kk=>$vv){
                $ids .= $vv['gid'].",";
            }
            $ids = substr($ids,0,strlen($ids) - 1);
            $goods_list_tmp = $goods->getGoodsList("gid in ($ids)", 'gid, goods_name, goods_promotion_price, goods_promotion_type,goods_price, goods_marketprice, goods_image, vid, gc_id, gc_id_1, gc_id_2, gc_id_3,is_free');
            $goods_list = array();
            foreach ((array)$goods_list_tmp as $v){
                $goods_list[$v['gid']] = array();
                $goods_list[$v['gid']] = $v;
            }
            foreach ((array)$browselist as $k=>$v){
                if ($goods_list[$v['gid']]){
                    $browselist_new[] = array(
                        "gid"          => $goods_list[$k]['gid'],
                        "browsetime"        => $v['browsetime'],
                        "goods_name"        => $goods_list[$k]['goods_name'],
                        "goods_image"       => $goods_list[$k]['goods_image'],
                        "goods_marketprice" => $goods_list[$k]['goods_marketprice'],
                        "goods_price"       => $goods_list[$k]['goods_price'],
                        "goods_promotion_price"=> $goods_list[$k]['goods_promotion_price'],
                        "goods_promotion_type"=>$goods_list[$k]['goods_promotion_type'],
                        "gc_id"             => $goods_list[$k]['gc_id'],
                        "gc_id_1"           => $goods_list[$k]['gc_id_1'],
                        "gc_id_2"           => $goods_list[$k]['gc_id_2'],
                        "gc_id_3"           => $goods_list[$k]['gc_id_3'],
                        "vid"          => $goods_list[$k]['vid'],
                        "is_free"          => $goods_list[$k]['is_free']
                    );
                }
            }
        }
        return $browselist_new;
    }

    /**
     * 删除浏览记录
     *
     * @param array $where
     * @return array
     */
    public function delGoodsbrowse($where){
        return $this->table('bbc_goods_browsehistory')->where($where)->delete();
    }

    /**
     * 添加单条浏览记录
     *
     * @param array $where
     * @return array
     */
    public function addGoodsbrowse($insert_arr){
        $this->table('bbc_goods_browsehistory')->insert($insert_arr);
    }
    /**
     * 添加多条浏览记录
     *
     * @param array $where
     * @return array
     */
    public function addGoodsbrowseAll($insert_arr){
        $this->table('bbc_goods_browsehistory')->insertAll($insert_arr);
    }

    /**
     * 查询单条浏览记录
     *
     * @param array $where
     * @return array
     */
    public function getGoodsbrowseOne($where, $field = '*', $order = '', $group = '') {
        $this->table('bbc_goods_browsehistory')->field($field)->where($where)->order($order)->group($group)->find();
    }
    /**
     * 查询单条浏览记录
     *
     * @param array $where
     * @return array
     */
    public function getGoodsbrowseList($where, $field = '*', $page = 0, $limit = 0, $order = '', $group = '') {
        if (is_array($page)){
            if ($page[1] > 0){
                return DB::name('goods_browsehistory')->field($field)->where($where)->page($page[0],$page[1])->limit($limit)->order($order)->group($group)->select();
            } else {
                return DB::name('goods_browsehistory')->field($field)->where($where)->page($page[0])->limit($limit)->order($order)->group($group)->select();
            }
        } else {
            return DB::name('goods_browsehistory')->field($field)->where($where)->page($page)->limit($limit)->order($order)->group($group)->select();
        }
    }
    /**
     * 获取猜你喜欢商品
     * @param array $shownum
     * @return array
     */
    public function getGuessLikeGoods($member_id = 0,$shownum = 0){
        $shownum = ($t = intval($shownum))>0?$t:0;
        $browseclass_arr = array();//浏览历史商品分类数组
        //如果会员ID存在，则读取数据库浏览历史，或者memcache
        if ($member_id > 0){
            if (!Config('cache_open')){//查询数据库
                $browseclass_list = $this->getGoodsbrowseList(array('member_id'=>$member_id), 'gc_id', 0, 20, 'rand()', 'gc_id');//随机抽取20条信息
                foreach ((array)$browseclass_list as $k=>$v){
                    $browseclass_arr[] = $v['gc_id'];
                }
            } else {
                $base = new Base();
                //生成缓存的键值
                $hash_key = $member_id;
                //先查找$hash_key缓存
                $browse_goodsid = $base->rcache($hash_key,'goodsbrowse','goodsid');
                $goodsid_arr_tmp = $browse_goodsid['goodsid']?unserialize($browse_goodsid['goodsid']):array();
                unset($browse_goodsid['goodsid']);

                $browseclass_arr = array();
                if ($goodsid_arr_tmp){
                    $num = count($goodsid_arr_tmp) < 20?count($goodsid_arr_tmp):20;
                    $goodsidkey_arr = $goodsid_arr_tmp?@array_rand($goodsid_arr_tmp,$num):array();//随机抽取20条信息
                    foreach ((array) $goodsidkey_arr as $v){
                        $goodsid_arr[] = $goodsid_arr_tmp[$v];
                    }
                    unset($num);
                    //获得缓存的浏览商品信息
                    $browselist_tmp = $base->rcache($hash_key,'goodsbrowse',implode(',', $goodsid_arr));
                    foreach ((array)$browselist_tmp as $k=>$v){
                        $v = unserialize($v);
                        if (!$browseclass_arr || !in_array($v['gc_id'],$browseclass_arr)){
                            $browseclass_arr[] = $v['gc_id'];
                        }
                    }
                }
            }
        }
        //如果会员ID不存在，则查询浏览过的商品记录cookie
        if (!$member_id){
            $browselist = array();
            if(cookie('viewed_goods')){
                $string_viewed_goods = decrypt(cookie('viewed_goods'),MD5_KEY);
                if (get_magic_quotes_gpc()) $string_viewed_goods = stripslashes($string_viewed_goods);//去除斜杠
                $cookie_arr_tmp = unserialize($string_viewed_goods);
                if (count($cookie_arr_tmp) >= 20){
                    shuffle($cookie_arr_tmp);//对数组进行随机排序
                    $cookie_arr_tmp = array_slice($cookie_arr_tmp, 0, 20);//随机抽取20条信息
                }
                $cookie_arr = array();
                foreach ((array)$cookie_arr_tmp as $k=>$v){
                    $info = explode("-", $v);//每项cookie的值为商品ID-访问时间
                    if (is_numeric($info[0])){
                        $cookie_arr[$info[0]] = intval($info[1]);
                    }
                }
                $browselist = array();
                if ($cookie_arr){
                    $viewed_list = Model('goods')->getGoodsList(array('gid' => array('in', array_keys($cookie_arr))), 'gc_id');
                    foreach ((array)$viewed_list as $v){
                        if (!in_array($v['gc_id'],$browseclass_arr)){
                            $browseclass_arr[] = $v['gc_id'];
                        }
                    }
                }
            }
        }
        if(empty($browseclass_arr)){
            $viewed_list = Model('goods')->getGoodsList(array('goods_state' =>1,'goods_verify' =>1 ), 'gc_id','rand()','',20);
            foreach ((array)$viewed_list as $v){
                $browseclass_arr[] = $v['gc_id'];
            }
        }
        //商品分类缓存
//        $gc_list = Model('goods_class')->getGoodsClassForCacheModel();
        //处理商品分类
        $browseclass_arrtmp = array();
        foreach ((array)$browseclass_arr as $k=>$v){
            $browseclass_arrtmp[] = $v;
            $browseclass_arrtmp = array_merge($browseclass_arrtmp,($t = $gc_list[$v]['child'])?explode(',',$t):array());
            $browseclass_arrtmp = array_merge($browseclass_arrtmp,($t = $gc_list[$v]['childchild'])?explode(',',$t):array());
        }
        $where['gc_id'] = array('in',$browseclass_arrtmp);
        $where['booth_state'] = 1;

        //随机显示符合以上分类的推荐展位商品
        $boothgoodslist = Model('p_booth')->getBoothGoodsList($where, '', 0, $shownum, 'rand()');
        $goodsid_arr = array();
        foreach ((array)$boothgoodslist as $k=>$v){
            $goodsid_arr[] = $v['gid'];
        }
        $goodslist = Model('goods')->getGoodsOnlineList(array('gid'=>array('in',$goodsid_arr)));

        // 获取最终价格
        $goodslist = Model('goods_activity')->rebuild_goods_data($goodslist,'pc');

        return $goodslist;
    }

    /**
     * 登录之后把cookie中的浏览记录存入数据库
     */
    public function mergebrowse($member_id, $vid = 0){
        if ($member_id <= 0){
            return array('state'=>false,'msg'=>'参数错误');
        }
        //获取浏览历史cookie商品列表
        $cookie_list = $this->getViewedGoodsList();
        $cookie_list = array_reverse($cookie_list,true);//翻转数组，按照添加顺序排列
        if (empty($cookie_list)){
            return array('state'=>true);
        }
        $cookie_goodsid = array();
        foreach ((array)$cookie_list as $k=>$v){
            $cookie_goodsid[] = $v['gid'];
        }

        //cookie中浏览记录,去除店铺自己的商品，并加入数据库
        if (!empty($cookie_list)){
            $insert_arr = array();
            foreach ($cookie_list as $v){
                if ($v['vid'] != $vid){
                    $tmp = array();
                    $tmp['gid']         = $v['gid'];
                    $tmp['member_id']        = $member_id;
                    $tmp['browsetime']       = $v['browsetime'];
                    $tmp['gc_id']            = $v['gc_id'];
                    $tmp['gc_id_1']          = $v['gc_id_1'];
                    $tmp['gc_id_2']          = $v['gc_id_2'];
                    $tmp['gc_id_3']          = $v['gc_id_3'];
                    $goods_info[$v['gid']] = $tmp;
                }
            }
            if ($goods_info){
                if (!C('cache_open')){//存入数据库
                    $result = $this->addViewedGoodsToDatabase(array_keys($goods_info), $member_id, 0, $goods_info);
                } else {//存入缓存
                    $result = $this->addViewedGoodsToCache(array_keys($goods_info), $member_id, 0, $goods_info);
                }
            }
        }
        //最后清空浏览记录cookie
        setBbcCookie('viewed_goods','',-3600);
        return $result;
    }
    /**
     * 浏览过的商品
     *
     * @param int $member_id 会员ID（一般传递$_SESSION['member_id']）
     * @param int $shownum 查询的条数，默认0则为返回全部
     * @return array
     */
    public function addViewedGoods($gid,$member_id = 0,$vid = 0) {
        //未登录生成浏览过产品cookie
        if($member_id <= 0){
            $result = $this->addViewedGoodsToCookie($gid);
        }
        //登录后记录浏览历史
        if($member_id > 0){
//            if (C('cache_open')){//存入数据库
            $result = $this->addViewedGoodsToDatabase($gid, $member_id,$vid);
//            } else {//存入缓存
//                $result = $this->addViewedGoodsToCache($gid, $member_id,$vid);
//            }
        }
        return $result;
    }
    /**
     * 浏览过的商品加入浏览历史cache
     *
     * @param mixed $gid 商品ID或者商品ID数组
     * @param int $member_id 会员ID（一般传递$_SESSION['member_id']）
     * @param int $vid 店铺ID（一般传递$_SESSION['vid']）
     * @param array $goods_info 如果已经获取了商品信息则可传递至函数，避免重复查询
     * @return array
     */
    public function addViewedGoodsToCache($gid,$member_id,$vid = 0,$goods_info = array()) {
        if (!$gid || $member_id <= 0){
            return array('state'=>false,'msg'=>'参数错误');
        }
        $browsetime = time();
        if (!$goods_info){
            //查询商品详细信息
            $model_goods = Model('goods');
            if (is_array($gid)){
                $goods_infotmp = $model_goods->getGoodsList(array('gid'=>array('in',$gid)), 'gid,gc_id,gc_id_1,gc_id_2,gc_id_3,vid');
                if (!$goods_infotmp){
                    return array('state'=>true);
                }
                foreach ($goods_infotmp as $k=>$v){
                    if($vid <> $goods_infotmp['vid']){//店铺浏览自己的商品不加入浏览历史
                        $goods_infotmp[$v['gid']] = $v;
                    }
                }
                if (!$goods_infotmp){
                    return array('state'=>true);
                }
                //对数组按照浏览先后进行排序
                foreach ($gid as $v){
                    if ($goods_infotmp[$v]){
                        $goods_info[$v] = $goods_infotmp[$v];
                    }
                }
            } else {
                $goods_infotmp = $model_goods->getGoodsInfoByID($gid, 'gid,gc_id,gc_id_1,gc_id_2,gc_id_3,vid');
                if(!$goods_infotmp || $vid == $goods_infotmp['vid']){//店铺浏览自己的商品不加入浏览历史
                    return array('state'=>true);
                }
                $goods_info[$gid] = $goods_infotmp;
            }
        }
        //生成缓存的键值
        $hash_key = $member_id;

        //处理浏览历史cache中商品ID
        $_cache = rcache($hash_key,'goodsbrowse');
        $goodsid_arr = $_cache['goodsid']?unserialize($_cache['goodsid']):array();
        if ($goodsid_arr){
            //如果商品ID已经存在则先删除该ID，然后在数组末尾新增该ID
            $goodsid_arr = array_diff($goodsid_arr,array_keys($goods_info));
            //array_push($goodsid_arr,implode(',', array_keys($goods_info)));
            foreach(array_keys($goods_info) as $v){
                $goodsid_arr[] = $v;
            }
        } else {
            $goodsid_arr = array_keys($goods_info);
        }
        $_cache['goodsid'] = serialize($goodsid_arr);
        //处理浏览历史cache中商品详细信息
        foreach ($goods_info as $k=>$v){
            $tmp_arr = array();
            $tmp_arr['gid'] = $v['gid'];
            $tmp_arr['member_id'] = $member_id;
            $tmp_arr['browsetime'] = $v['browsetime']?$v['browsetime']:$browsetime;
            $tmp_arr['gc_id'] = $v['gc_id'];
            $tmp_arr['gc_id_1'] = $v['gc_id_1'];
            $tmp_arr['gc_id_2'] = $v['gc_id_2'];
            $tmp_arr['gc_id_3'] = $v['gc_id_3'];
            $_cache[$k] = serialize($tmp_arr);
        }
        //缓存商品信息
        wcache($hash_key,$_cache,'goodsbrowse');
        return array('state'=>true);
    }
    /**
     * 浏览过的商品加入浏览历史数据库
     *
     * @param mixed $gid 商品ID或者商品ID数组
     * @param int $member_id 会员ID（一般传递$_SESSION['member_id']）
     * @param int $vid 店铺ID（一般传递$_SESSION['vid']）
     * @param array $goods_info 如果已经获取了商品信息则可传递至函数，避免重复查询
     * @return array
     */
    public function addViewedGoodsToDatabase($gid,$member_id,$vid = 0,$goods_info = array()) {
        if (!$gid || $member_id <= 0){
            return array('state'=>false,'msg'=>'参数错误');
        }
        $browsetime = time();
        if (!$goods_info){
            //查询商品详细信息
            $model_goods = Model('goods');
            if (is_array($gid)){
                $goods_infotmp = $model_goods->getGoodsList(array('gid'=>array('in',$gid)), 'gid,gc_id,gc_id_1,gc_id_2,gc_id_3,vid');
                if (!$goods_infotmp){
                    return array('state'=>true);
                }
                foreach ($goods_infotmp as $k=>$v){
                    //店铺浏览自己的商品不加入浏览历史
                    if($vid <> $goods_infotmp['vid']){
                        $goods_infotmp[$v['gid']] = $v;
                    }
                }
                if (!$goods_infotmp){
                    return array('state'=>true);
                }
                //对数组按照浏览先后进行排序
                foreach ($gid as $v){
                    if ($goods_infotmp[$v]){
                        $goods_info[$v] = $goods_infotmp[$v];
                    }
                }
            } else {
                //$goods_infotmp = $model_goods->getGoodsInfoByID($gid, 'gid,gc_id,gc_id_1,gc_id_2,gc_id_3,vid');
                $goods_infotmp = $model_goods->getGoodsInfo(array('gid'=>$gid));//getGoodsInfoByID($gid, 'gid,gc_id,gc_id_1,gc_id_2,gc_id_3,vid');
                if(!$goods_infotmp || $vid == $goods_infotmp['vid']){//店铺浏览自己的商品不加入浏览历史
                    return array('state'=>true);
                }
                $goods_info[$gid] = $goods_infotmp;
            }
        }

        //构造新增的数组
        $insert_arr = array();
        //处理浏览历史cache中商品详细信息
        foreach ($goods_info as $k=>$v){
            if (empty($v['gid'])) continue;
            $tmp_arr = array();
            $tmp_arr['gid'] = $v['gid'];
            $tmp_arr['member_id'] = $member_id;
            $tmp_arr['browsetime'] = $v['browsetime']?$v['browsetime']:$browsetime;
            $tmp_arr['gc_id'] = $v['gc_id'];
            $tmp_arr['gc_id_1'] = $v['gc_id_1'];
            $tmp_arr['gc_id_2'] = $v['gc_id_2'];
            $tmp_arr['gc_id_3'] = $v['gc_id_3'];
            $insert_arr[] = $tmp_arr;
        }
        //删除已存在的该goods_id记录
        $this->delGoodsbrowse(array('gid'=>array('in',array_keys($goods_info)),'member_id'=>$member_id));
        $result = $this->addGoodsbrowseAll($insert_arr);
        return array('state'=>true);
    }
    /**
     * 浏览过的商品加入浏览历史数据库
     *
     * @param mixed $gid 商品ID或者商品ID数组
     * @return array
     */
    public function addViewedGoodsToCookie($gid){
        if (!$gid){
            return array('state'=>false,'msg'=>'参数错误');
        }

        //浏览时间
        $browsetime = time();

        //构造cookie的一项值，每项cookie的值为商品ID-访问时间
        if (is_array($gid)){
            $goods_idarr = $gid;
            foreach ($gid as $v){
                $cookievalue[] = $v . '-' . $browsetime;
            }
        } else {
            $cookievalue[] = $gid . '-' . $browsetime;
            $goods_idarr[] = $gid;
        }
        unset($gid);

        if (cookie('viewed_goods')) {//如果cookie已经存在
            $string_viewed_goods = decrypt(cookie('viewed_goods'), MD5_KEY);
            if (get_magic_quotes_gpc()) {
                $string_viewed_goods = stripslashes($string_viewed_goods); // 去除斜杠
            }
            $vg_ca = @unserialize($string_viewed_goods);
            if (!empty($vg_ca) && is_array($vg_ca)) {
                foreach ($vg_ca as $vk => $vv) {
                    $vv_arr = explode('-',$vv);
                    if (in_array($vv_arr[0], $goods_idarr)) {//如果该商品的浏览记录已经存在，则删除它
                        unset($vg_ca[$vk]);
                    }
                }
            } else {
                $vg_ca = array();
            }
            //将新浏览历史加入cookie末尾
            array_push($vg_ca,implode(',', $cookievalue));

            //cookie中最多存储50条浏览信息
            if (count($vg_ca) > 50) {
                $vg_ca = array_slice($vg_ca, -50, 50);
            }
        } else {
            $vg_ca = $cookievalue;
        }
        $vg_ca = encrypt(serialize($vg_ca), MD5_KEY);
        setBbcCookie('viewed_goods', $vg_ca);
    }

    /**
     * add by zhengyifan 2019-09-09
     * 获取用户浏览记录
     * @param $condition
     * @param string $field
     * @param string $order
     * @param int $page
     * @param int $limit
     * @return array|\PDOStatement|string|\think\Collection
     */
    public function getGoodsBrowseHistory($condition, $field = '*', $order = '', $page = 0, $limit = 100) {
        return DB::name('goods_browsehistory')
            ->field($field)
            ->where($condition)
            ->page($page)
            ->limit($limit)
            ->order($order)
            ->select();
    }

    /**
     * add by zhengyifan 2019-09-19
     * 删除足迹
     * @param $condition
     * @return int
     */
    public function delGoodsbrowseHistory($condition){
        return DB::name('goods_browsehistory')->where($condition)->delete();
    }

    /**
     * add by zhengyifan 2019-09-12
     * 获取足迹数量
     * @param $condition
     * @return float|string
     */
    public function getGoodsBrowseHistoryCount($condition) {
        return DB::name('goods_browsehistory')->where($condition)->count();
    }
}