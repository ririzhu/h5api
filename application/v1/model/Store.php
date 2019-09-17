<?php
namespace app\v1\model;

use think\Model;
use think\db;
class Store extends Model
{
    /**
     * 自营店铺的ID
     *
     * array(
     *   '店铺ID(int)' => '是否绑定了全部商品类目(boolean)',
     *   // ..
     * )
     */
    protected $ownShopIds;
    public function __construct(){
        parent::__construct('dian');
    }
    /**
     * 删除缓存自营店铺的ID
     */
    public function dropCachedOwnShopIds() {
        $this->ownShopIds = null;
        dkcache('own_shop_ids');
    }
    /**
     * 获取自营店铺的ID
     *
     * @param boolean $bind_all_gc = false 是否只获取绑定全部类目的自营店 默认否（即全部自营店）
     * @return array
     */
    public function getOwnShopIds($bind_all_gc = false) {
        $data = $this->ownShopIds;
        // 属性为空则取缓存
        if (!$data) {
            //$data = rkcache('own_shop_ids');
            $data = H('own_shop_ids')? H('own_shop_ids'):H('own_shop_ids',true);
            // 缓存为空则查库
            if (!$data) {
                $data = array();
                $all_own_shops = $this->table('vendor')->field('vid,bind_all_gc')->where(array(
                    'is_own_shop' => 1,
                ))->select();
                foreach ((array) $all_own_shops as $v) {
                    $data[$v['vid']] = (int) (bool) $v['bind_all_gc'];
                }
                // 写入缓存
                //               wkcache('own_shop_ids', $data);
            }
            // 写入属性
            $this->ownShopIds = $data;
        }
        return array_keys($bind_all_gc ? array_filter($data) : $data);
    }

    /**
     * 查询店铺列表
     *
     * @param array $condition 查询条件
     * @param int $page 分页数
     * @param string $order 排序
     * @param string $field 字段
     * @param string $limit 取多少条
     * @return array
     */
    public function getDianList($condition, $page = null, $order = 'add_time desc', $field = '*', $limit = '',$more=false) {
        if($more){
            $result = $this->field($field)->where($condition)->order($order)->limit($limit)->page($page)->select();
        }else{
            $result = $this->table('dian,dian_goods,order')->alias('dian,dg,o')->join('left')
                ->on('dian.id=dg.dian_id and dg.off=0 and dg.delete =0,dian.id=o.dian_id')
                ->field('dian.*,count(dg.id) as goods,count(o.order_id) as oo')->where($condition)->order($order)->group('dian.id')->limit($limit)->page($page)->select();
        }
        return $result;
    }

    /**
     *  根据收货地址  看是否有门店
     *
     * @param array 订单信息
     * @return int 1 可以派单  0 没有店铺满足条件
     */
    public function getDiansCountByAddress($order_info) {
        $gids=array();
        $get=array();
        foreach ($order_info['extend_order_goods'] as $v){
            $gids[] = $v['gid'];
            $get['s'][]=$v['goods_num'];
        }
        $get['g']=$gids;
        $gids=join(',',$gids);
        $dian_list = $this->getDiansByGid($gids,null,array('dian.vid'=>$order_info['vid'],'dian.status'=>1));
        if(count($dian_list)<1){
            return 0;
        }

        $add_arr = explode('&nbsp;', $order_info['extend_order_common']['reciver_info']['address']);

        $add_arr[0] = explode(' ', $add_arr[0]);

        $ii=0;


        foreach ($dian_list as $k=>$dian_info){
            if($ii<1) {

                if($dian_info['has_refuse']==1) {
                    if ($dian_info['delivery_tpl'] == 1) {//如果全国模版
                        $whole_tpls = json_decode(html_entity_decode($dian_info['whole_tpl']), true);
                        foreach ($whole_tpls as $v) {
                            $new_tpls[] = $v[1];
                        }
                        $_add_arr=str_replace(array("\t","\n","\r"," ","　"),"_",$add_arr[0])[0];
                        $whole_tpls = $new_tpls;
                        foreach (explode('_',$_add_arr) as $v) {
                            if (in_array($v, $whole_tpls)) {
                                //合格再判断一下库存
                                $get['d'] = $dian_info['id'];
                                if($this->get_dian_stock($get)>0) {
                                    $ii++;
                                    break;
                                }
                            }
                        }
                    } else { //如果自定区域 就要计算位置坐标
                        $same_tpl = json_decode(html_entity_decode($dian_info['same_tpl']), true);
                        foreach ($same_tpl as $polygon) {
                            $url = 'http://restapi.amap.com/v3/place/polygon';
                            $post_data['key'] = C('gaode_serverkey');
                            $post_data['polygon'] = $polygon['path'];
                            $add_str = '';
                            foreach ($add_arr[0] as $v) {
                                $add_str .= $v . ' ';
                            }

                            $aaa = isset($add_arr[1])?$add_arr[1]:$add_arr[0][0];
                            $ci = json_decode(file_get_contents(BASE_PATH.DS.'word.json'),true);

                            foreach ($ci as $v){
                                if(strpos($aaa,$v)){
                                    $aaa = str_replace($v,$v.'|',$aaa);
                                }
                            }

                            $post_data['keywords'] = isset($add_arr[1]) ? $add_str . $aaa : $aaa;

                            $re = request_post($url, false, $post_data);
                            if (!$re) {
                                continue;
                            }
                            $re = json_decode($re, true);
                            if ($re['infocode'] != 10000) {//根据状态码 确认交互成功
                                continue;
                            }
                            if (count($re['pois']) < 1) { //没有匹配地址
                                continue;
                            } else {
                                //合格再判断一下库存
                                $get['d'] = $dian_info['id'];
                                if($this->get_dian_stock($get)>0) {
                                    $ii++;
                                    break;
                                }
                            }

                        }
                    }
                }
            }else{
                break;
            }
        }

        return $ii;
    }

    /**
     * 查询店铺列表 根据收货地址
     *
     * @param array 订单信息
     * @return array 匹配门店列表
     */
    public function getDiansListByAddress($order_info) {
        $get=array();
        $gids=array();
        foreach ($order_info['extend_order_goods'] as $v){
            $gids[] = $v['gid'];
            $get['s'][]=$v['goods_num'];
        }
        $get['g']=$gids;
        $gids=join(',',$gids);
        $dian_list = $this->getDiansByGid($gids,null,array('dian.vid'=>$order_info['vid']));
        if(count($dian_list)<1){
            return array();
        }

        $add_arr = explode('&nbsp;', $order_info['extend_order_common']['reciver_info']['address']);
        $add_arr[0] = explode(' ', $add_arr[0]);

        $re_array=array();

        foreach ($dian_list as $k=>$dian_info){
            if($dian_info['auto_jiedan']==1) {
                if ($dian_info['delivery_tpl'] == 1) {//如果全国模版
                    $whole_tpls = json_decode(html_entity_decode($dian_info['whole_tpl']), true);
                    foreach ($whole_tpls as $v) {
                        $new_tpls[] = $v[1];
                    }
                    $_add_arr=str_replace(array("\t","\n","\r"," ","　"),"_",$add_arr[0])[0];
                    $whole_tpls = $new_tpls;
                    foreach (explode('_',$_add_arr) as $v) {
                        if (in_array($v, $whole_tpls)) {
                            //合格再判断一下库存
                            $get['d'] = $dian_info['id'];
                            if($this->get_dian_stock($get)>0) {
                                $re_array[$dian_info['id']] = $dian_info;
                                break;
                            }
                        }
                    }

                } else { //如果自定区域 就要计算位置坐标
                    $same_tpl = json_decode(html_entity_decode($dian_info['same_tpl']), true);
                    foreach ($same_tpl as $polygon) {
                        $url = 'http://restapi.amap.com/v3/place/polygon';
                        $post_data['key'] = C('gaode_serverkey');
                        $post_data['polygon'] = $polygon['path'];
                        $add_str = '';
                        foreach ($add_arr[0] as $v) {
                            $add_str .= $v . ' ';
                        }

                        $aaa = isset($add_arr[1])?$add_arr[1]:$add_arr[0][0];
                        $ci = json_decode(file_get_contents(BASE_PATH.DS.'word.json'),true);

                        foreach ($ci as $v){
                            if(strpos($aaa,$v)){
                                $aaa = str_replace($v,$v.'|',$aaa);
                            }
                        }

                        $post_data['keywords'] = isset($add_arr[1]) ? $add_str . $aaa : $aaa;

                        $re = request_post($url, false, $post_data);
                        if (!$re) {
                            continue;
                        }
                        $re = json_decode($re, true);
                        if ($re['infocode'] != 10000) {//根据状态码 确认交互成功
                            continue;
                        }
                        if (count($re['pois']) < 1) { //没有匹配地址
                            continue;
                        } else {
                            //合格再判断一下库存
                            $get['d'] = $dian_info['id'];
                            if($this->get_dian_stock($get)>0) {
                                $re_array[$dian_info['id']] = $dian_info;
                                break;
                            }
                        }
                    }
                }

            }
        }

        return $re_array;
    }

    /**
     * 根据产品id得到门店列表
     *
     * @param array $condition 查询条件
     * @param int $page 分页数
     * @param string $order 排序
     * @param string $field 字段
     * @param string $limit 取多少条
     * @return array
     */
    public function getDiansByGid($gid,$regions=null,$condition=array(),$lng=null,$lat=null,$is_gaode=0) {
        if(is_array($gid)){
            $condition['dian_goods.goods_id'] = array('in',join(',',$gid));
        }else {
            $condition['dian_goods.goods_id'] = $gid;
        }
        $condition['dian_goods.delete']=0;
        $condition['dian_goods.stock']=array('gt',0);
        $condition['dian.status']=1;



        $address = str_replace('	','+',$address);
        $address = str_replace('&nbsp;','+',$address);


        if(!$lng || !$lat){
            //如果没位置信息 则根据ip定位
            $url = 'http://restapi.amap.com/v3/ip';
            $post_data['key'] = C('gaode_serverkey');
            $post_data['ip'] = getIp();
            $re = request_post($url, false, $post_data);
            if($re) {
                $re = json_decode($re,true);
                if ($re['rectangle']) {
                    $rect = explode(';', $re['rectangle']);
                    foreach ($rect as $k => $v) {
                        $rect[$k] = explode(',', $v);
                    }
                    $lng = $rect[0][0] + ($rect[1][0] - $rect[0][0]);
                    $lat = $rect[0][1] + ($rect[1][1] - $rect[0][1]);
                }
            }
        }else{
            if(!$is_gaode) {
                //gps转化成高德坐标
                $url = 'http://restapi.amap.com/v3/assistant/coordinate/convert';
                $post_data['key'] = C('gaode_serverkey');
                $post_data['locations'] = $lng . ',' . $lat;
                $post_data['coordsys'] = 'gps';
                $re = request_post($url, false, $post_data);
                if ($re) {
                    $re = json_decode($re, true);
                    if ($re['locations']) {
                        $lng = explode(',', $re['locations'])[0];
                        $lat = explode(',', $re['locations'])[1];
                    }
                }
            }
        }
        if(!$lng){
            //所需字段
            $fieldstr = 'dian.*';
            //排序方式
            $order = 'id asc ';
        }else{
            //所需字段
            $fieldstr = 'dian.*,(2 * 6378.137* ASIN(SQRT(POW(SIN(PI()*('.$lng.'-dian_lng)/360),2)+COS(PI()*'.$lat.'/180)* COS(dian_lat * PI()/180)*POW(SIN(PI()*('.$lat.'-dian_lat)/360),2)))) as juli';
            //排序方式
            $order = 'juli asc ';
        }


        if($regions) {
            $tpls = " ( dian.dian_region like '".$regions.",%' or dian.dian_region like '%,".$regions."' or dian.dian_region like '%,".$regions.",%' ";
            $tpls.=' )';
            $condition['dian.whole_tpl'] = array('exp',$tpls);
        }
        $result = $this->table('dian,dian_goods')->join('left')->on('dian.id=dian_goods.dian_id')->field($fieldstr)->where($condition)->order($order)->group('dian.id')->page(10)->select();
        $i=0;
        foreach ($result as $k=>$v){
            $result[$k]['juli'] = number_format( $v['juli'],1);
            $result[$k]['danwei'] = 'km';
            if($v['juli']<1){
                $result[$k]['danwei'] = 'm';
                $result[$k]['juli'] = round($v['juli'] * 1000);
            }else{
                if($v['juli']>999){
                    $v['juli']='>999';
                }
            }
            $result[$k]['dian_phone'] = explode(',',$v['dian_phone']);
            $result[$k]['dian_phone_arr'] = $result[$k]['dian_phone'];
            $result[$k]['operation_time'] = $result[$k]['operation_time_arr'] = explode(',',$v['operation_time']);
            $result[$k]['position'] = array($v['dian_lng'],$v['dian_lat']);
            $result[$k]['operation_time'][0] = sprintf("%02d",$result[$k]['operation_time_arr'][0]%1440/60).":".sprintf("%02d",$result[$k]['operation_time'][0]%60);
            $result[$k]['operation_time'][1] = ($result[$k]['operation_time_arr'][1]==1440 ? '24' : sprintf("%02d",$result[$k]['operation_time_arr'][1]%1440/60)).":".sprintf("%02d",$result[$k]['operation_time'][1]%60);
            $result[$k]['dian_pic'] = UPLOAD_SITE_URL.DS.ATTACH_PATH.DS.'dian'.DS.$v['vid'].DS.$result[$k]['dian_logo'];
            $result[$k]['ind'] = $i;
            $i++;
        }
        if($result) {
            $result['lng']=$lng;
            $result['lat']=$lat;
        }
        return $result;
    }

    public  function  get_dian_stock($get){
        $gs=array();
        foreach ($get['g'] as $k=>$v){
            $gs[$v]=$get['s'][$k];
        }
        $where['dian.id'] = $get['d'];
        $where['dian_goods.goods_id'] = array('in',join(',',$get['g']));
        $dian_info = Model('dian')->table('dian,dian_goods')->join('right')->field('dian_goods.*')->on('dian.id=dian_goods.dian_id')->where($where)->select();

        foreach ($dian_info as $k=>$v) {
            if($v['stock']<$gs[$v['goods_id']]){
                return json_encode(0);
            }
        }
        return json_encode(count($dian_info));
    }

    /*判断是否有门店*/
    public function getDianCountByGid($gid,$regions=null,$condition=array()) {
        if(is_array($gid)){
            $gids = "";
            foreach($gid as $k=>$v){
                $gids .=$v.",";
            }
            $gids = substr($gids,0,strlen($gids) - 1);
            $condition['bbc_dian_goods.goods_id'] = array('in',$gids);
        }else {
            $condition['bbc_dian_goods.goods_id'] = $gid;
        }
        $condition['bbc_dian_goods.delete']=0;
        $condition['bbc_dian_goods.stock']=array('gt',0);

        if($regions) {
            $tpls = " ( ";
            foreach ($regions as $k=>$v) {
                if($k==0)
                    $tpls .= " dian.dian_region like '".$v.",%' ";
                elseif ($k==1)
                    $tpls .= " or dian.dian_region like '%,".$v."'% ";
                else
                    $tpls .= " or dian.dian_region like '%,".$v."' ";
            }
            $tpls.=' )';
            $condition['dian.whole_tpl'] = array('exp',$tpls);
        }
        $result = DB::table("bbc_dian")->alias("dian")->join('bbc_dian_goods','dian.id=bbc_dian_goods.dian_id')->field("dian.id")->group('dian.id')->where($condition)->count();
        return $result;
    }

    /**
     * 店铺数量
     * @param array $condition
     * @return int
     */
    public function getDianCount($condition) {
        return $this->where($condition)->count();
    }

    /**
     * 按店铺编号查询店铺的开店信息
     *
     * @param array $storeid_array 店铺编号
     * @return array
     */
    public function getDianMemberIDList($storeid_array) {
        $store_list = $this->table('dian')->where(array('dian_id'=> array('in', $storeid_array)))->field('id,vid,member_id')->key('id')->select();
        return $store_list;
    }

    /**
     * 查询店铺信息
     *
     * @param array $condition 查询条件
     * @return array
     */
    public function getDianInfo($condition) {
        $store_info = $this->where($condition)->find();
        $member_model = Model('member');
        if(!empty($store_info)) {
            if(!empty($store_info['store_presales'])){
                $store_info['store_presales'] = unserialize($store_info['store_presales']);
                foreach ($store_info['store_presales'] as $key => $val){
                    $member_info = $member_model -> getMemberInfoByID($val['num'],'member_name,member_avatar');
                    $store_info['store_presales'][$key]['member_name'] = $member_info['member_name'];
                    $store_info['store_presales'][$key]['member_avatar'] = $member_info['member_avatar']?UPLOAD_SITE_URL.DS.ATTACH_AVATAR.DS.$member_info['member_avatar']:UPLOAD_SITE_URL.'/'.ATTACH_COMMON.DS.C('default_user_portrait');
                }
            }

            if(!empty($store_info['store_aftersales'])) {
                $store_info['store_aftersales'] = unserialize($store_info['store_aftersales']);
                //获取店铺客服的用户名和头像

                foreach ($store_info['store_aftersales'] as $key => $val){
                    $member_info = $member_model -> getMemberInfoByID($val['num'],'member_name,member_avatar');
                    $store_info['store_aftersales'][$key]['member_name'] = $member_info['member_name'];
                    $store_info['store_aftersales'][$key]['member_avatar'] = $member_info['member_avatar']?UPLOAD_SITE_URL.DS.ATTACH_AVATAR.DS.$member_info['member_avatar']:UPLOAD_SITE_URL.'/'.ATTACH_COMMON.DS.C('default_user_portrait');
                }
            }

            //商品数
            $model_goods = Model('goods');
            $store_info['goods_count'] = $model_goods->getGoodsOnlineCount(array('vid' => $store_info['vid']));

            //店铺评价
            $model_evaluate_store = Model('evaluate_store');
            $store_evaluate_info = $model_evaluate_store->getEvaluateStoreInfoByStoreID($store_info['vid'], $store_info['sc_id']);

            $store_info = array_merge($store_info, $store_evaluate_info);
        }
        return $store_info;
    }

    /**
     * 查询店铺信息
     *
     * @param array $condition 查询条件
     * @return array
     */
    public function getStoreInfo($condition) {
        $store_info = db::name('dian')->where($condition)->find();
        $member_model = new User();
        if(!empty($store_info)) {
            if(!empty($store_info['store_presales'])){
                $store_info['store_presales'] = unserialize($store_info['store_presales']);
                foreach ($store_info['store_presales'] as $key => $val){
                    $member_info = $member_model -> getMemberInfoByID($val['num'],'member_name,member_avatar');
                    $store_info['store_presales'][$key]['member_name'] = $member_info['member_name'];
                    $store_info['store_presales'][$key]['member_avatar'] = $member_info['member_avatar']?UPLOAD_SITE_URL.DS.ATTACH_AVATAR.DS.$member_info['member_avatar']:UPLOAD_SITE_URL.'/'.ATTACH_COMMON.DS.C('default_user_portrait');
                }
            }

            if(!empty($store_info['store_aftersales'])) {
                $store_info['store_aftersales'] = unserialize($store_info['store_aftersales']);
                //获取店铺客服的用户名和头像

                foreach ($store_info['store_aftersales'] as $key => $val){
                    $member_info = $member_model -> getMemberInfoByID($val['num'],'member_name,member_avatar');
                    $store_info['store_aftersales'][$key]['member_name'] = $member_info['member_name'];
                    $store_info['store_aftersales'][$key]['member_avatar'] = $member_info['member_avatar']?UPLOAD_SITE_URL.DS.ATTACH_AVATAR.DS.$member_info['member_avatar']:UPLOAD_SITE_URL.'/'.ATTACH_COMMON.DS.C('default_user_portrait');
                }
            }

            //商品数
            $model_goods = Model('goods');
            $store_info['goods_count'] = $model_goods->getGoodsOnlineCount(array('vid' => $store_info['vid']));

            //店铺评价
            $model_evaluate_store = Model('evaluate_store');
            $store_evaluate_info = $model_evaluate_store->getEvaluateStoreInfoByStoreID($store_info['vid'], $store_info['sc_id']);

            $store_info = array_merge($store_info, $store_evaluate_info);
        }
        return $store_info;
    }

    /**
     * 通过店铺编号查询店铺信息
     *
     * @param int $vid 店铺编号
     * @return array
     */
    public function getDianInfoByID($vid=null,$dian_id) {
        if($vid!=null){
            $where['vid']=$vid;
        }
        $where['id'] = $dian_id;
        $store_info = rcache($vid, 'dian_info');
        if(empty($store_info)) {
            $store_info = $this->getStoreInfo($where);
            wmemcache($dian_id, $store_info, 'dian_info');
        }
        return $store_info;
    }

    public function getStoreOnlineInfoByID($vid) {
        $store_info = $this->getStoreInfoByID($vid);
        if(empty($store_info) || $store_info['store_state'] == '0') {
            return null;
        } else {
            return $store_info;
        }
    }

    public function getDianIDString($condition) {
        $store_list = $this->getDianList($condition,null,null,'id');
        $store_id_string = array();
        foreach ($store_list as $value) {
            $store_id_string[] = $value['id'];
        }
        $store_id_string = join(',',$store_id_string);
        return $store_id_string;
    }

    /*
     * 添加店铺
     *
     * @param array $param 店铺信息
     * @return bool
     */
    public function addStore($param){
        return $this->insert($param);
    }

    /*
     * 编辑店铺
     *
     * @param array $update 更新信息
     * @param array $condition 条件
     * @return bool
     */
    public function editStore($update, $condition){
        //清空缓存
        $store_list = $this->getDianList($condition);
        foreach ($store_list as $value) {
            wmemcache($value['id'], array(), 'dian_info');
        }

        return $this->table('dian')->where($condition)->update($update);
    }

    /*
     * 删除店铺
     *
     * @param array $condition 条件
     * @return bool
     */
    public function delStore($condition){
        $store_info = $this->getStoreInfo($condition);
        //删除店铺相关图片
        delete_file(BASE_UPLOAD_PATH.DS.ATTACH_STORE.DS.$store_info['store_label']);
        delete_file(BASE_UPLOAD_PATH.DS.ATTACH_STORE.DS.$store_info['store_banner']);
        if($store_info['store_slide'] != ''){
            foreach(explode(',', $store_info['store_slide']) as $val){
                delete_file(BASE_UPLOAD_PATH.DS.ATTACH_SLIDE.DS.$val);
            }
        }

        //清空缓存
        wmemcache($store_info['vid'], array(), 'store_info');

        return $this->where($condition)->delete();
    }

    /**
     * 获取商品销售排行
     *
     * @param int $vid 店铺编号
     * @param int $limit 数量
     * @return array	商品信息
     */
    public function getHotSalesList($vid, $limit = 5,$page=0) {
        $prefix = 'store_hot_sales_list_' . $limit;
        $hot_sales_list = rcache($vid, $prefix);
        if(empty($hot_sales_list)) {
            $model_goods = Model('goods');
            $hot_sales_list = $model_goods->getGoodsOnlineList(array('vid' => $vid), '*', $page, 'goods_salenum desc', $limit);
            wmemcache($vid, $hot_sales_list, $prefix);
        }
        return $hot_sales_list;
    }

    /**
     * 获取商品收藏排行
     *
     * @param int $vid 店铺编号
     * @param int $limit 数量
     * @return array	商品信息
     */
    public function getHotCollectList($vid, $limit = 5) {
        $prefix = 'store_collect_sales_list_' . $limit;
        $hot_collect_list = rcache($vid, $prefix);
        if(empty($hot_collect_list)) {
            $model_goods = Model('goods');
            $hot_collect_list = $model_goods->getGoodsOnlineList(array('vid' => $vid), '*', 0, 'goods_collect desc', $limit);
            wmemcache($vid, $hot_collect_list, $prefix);
        }
        return $hot_collect_list;
    }

    /**
     * 获取店铺列表页附加信息
     *
     * @param array $store_array 店铺数组
     * @return array $store_array 包含近期销量和8个推荐商品的店铺数组
     */
    public function getStoreSearchList($store_array) {
        $store_array_new = array();
        if(!empty($store_array)){
            $model = Model();
            $no_cache_store = array();
            foreach ($store_array as $value) {
                //$store_search_info = rcache($value['vid'],'store_search_info');
                //print_r($store_array);exit();
                //if($store_search_info !== FALSE) {
                //	$store_array_new[$value['vid']] = $store_search_info;
                //} else {
                //	$no_cache_store[$value['vid']] = $value;
                //}
                $no_cache_store[$value['vid']] = $value;
            }
            if(!empty($no_cache_store)) {
                //获取店铺商品数
                $no_cache_store = $this->getStoreInfoBasic($no_cache_store);
                //获取店铺近期销量
                $no_cache_store = $this->getGoodsCountJq($no_cache_store);
                //获取店铺推荐商品
                $no_cache_store = $this->getGoodsListBySales($no_cache_store);
                //写入缓存
                foreach ($no_cache_store as $value) {
                    wcache($value['vid'],$value,'store_search_info');
                }
                $store_array_new = array_merge($store_array_new,$no_cache_store);
            }
        }
        return $store_array_new;
    }
    /**
     * 获得店铺标志、信用、商品数量、店铺评分等信息
     *
     * @param	array $param 店铺数组
     * @return	array 数组格式的返回结果
     */
    public function getStoreInfoBasic($list,$day = 0){
        $list_new = array();
        if (!empty($list) && is_array($list)){
            foreach ($list as $key=>$value) {
                if(!empty($value)) {
                    $value['store_logo'] = getStoreLogo($value['store_logo']);
                    //店铺评价
                    $model_evaluate_store = Model('evaluate_store');
                    $store_evaluate_info = $model_evaluate_store->getEvaluateStoreInfoByStoreID($value['vid'], $value['sc_id']);
                    $value = array_merge($value, $store_evaluate_info);
                    if(!empty($value['store_presales'])) $value['store_presales'] = unserialize($value['store_presales']);
                    if(!empty($value['store_aftersales'])) $value['store_aftersales'] = unserialize($value['store_aftersales']);
                    $list_new[$value['vid']] = $value;
                    $list_new[$value['vid']]['goods_count'] = 0;
                }
            }
            //全部商品数直接读取缓存
            if($day > 0) {
                $store_id_string = implode(',',array_keys($list_new));
                //指定天数直接查询数据库
                $condition = array();
                $condition['goods_show'] = '1';
                $condition['vid'] = array('in',$store_id_string);
                $condition['goods_add_time'] = array('gt',strtotime("-{$day} day"));
                $model = Model();
                $goods_count_array = $model->table('goods')->field('vid,count(*) as goods_count')->where($condition)->group('vid')->select();
                if (!empty($goods_count_array)){
                    foreach ($goods_count_array as $value){
                        $list_new[$value['vid']]['goods_count'] = $value['goods_count'];
                    }
                }
            } else {
                $list_new = $this->getGoodsCountByStoreArray($list_new);
            }
        }
        return $list_new;
    }
    /**
     * 获取店铺商品数
     *
     * @param array $store_array 店铺数组
     * @return array $store_array 包含商品数goods_count的店铺数组
     */
    public function getGoodsCountByStoreArray($store_array) {
        $store_array_new = array();
        $model = Model();
        $no_cache_store = '';
        foreach ($store_array as $value) {
            $goods_count = rcache($value['vid'],'store_goods_count');
            if(!empty($goods_count)&&$goods_count !== FALSE) {
                //有缓存的直接赋值
                $value['goods_count'] = $goods_count;
            } else {
                //没有缓存记录store_id，统计从数据库读取
                $no_cache_store .= $value['vid'].',';
                $value['goods_count'] = '0';
            }
            $store_array_new[$value['vid']] = $value;
        }
        if(!empty($no_cache_store)) {
            //从数据库读取店铺商品数赋值并缓存
            $no_cache_store = rtrim($no_cache_store,',');
            $condition = array();
            $condition['goods_state'] = '1';
            $condition['vid'] = array('in',$no_cache_store);
            $goods_count_array = $model->table('goods')->field('vid,count(*) as goods_count')->where($condition)->group('vid')->select();
            if (!empty($goods_count_array)){
                foreach ($goods_count_array as $value){
                    $store_array_new[$value['vid']]['goods_count'] = $value['goods_count'];
                    wcache($value['vid'],$value['goods_count'],'store_goods_count');
                }
            }
        }
        return $store_array_new;
    }
    //获取近期销量
    private function getGoodsCountJq($store_array) {
        $model = Model();
        $order_count_array = $model->table('order')->field('vid,count(*) as order_count')->where(array('vid'=>array('in',implode(',',array_keys($store_array))),'add_time'=>array('gt',TIMESTAMP-3600*24*90)))->group('vid')->select();
        foreach ((array)$order_count_array as $value) {
            $store_array[$value['vid']]['num_sales_jq'] = $value['order_count'];
        }
        return $store_array;
    }
    //获取店铺8个销量最高商品
    private function getGoodsListBySales($store_array) {
        $model = Model();
        $field = 'gid,vid,goods_name,goods_image,goods_price,goods_salenum';
        foreach ($store_array as $value) {
            $store_array[$value['vid']]['search_list_goods'] = $model->table('goods')->field($field)->where(array('vid'=>$value['vid'],'goods_state'=>1))->order('goods_salenum desc')->limit(8)->select();
        }
        return $store_array;
    }
    public function add_rebate_data($param){
        return $this->table('rebate_temp')->insert($param);
    }
    public function get_rebate_data($condition){//获得返利
        return $this->table('rebate_temp')->where($condition)->select();
    }
    public function get_rebate_single($condition){//获得返利单条
        return $this->table('rebate_temp')->where($condition)->find();
    }
    public function edit_rebate_data($condition,$data){
        return $this->table('rebate_temp')->where($condition)->update($data);
    }
    public function del_rebate_data($condition){//删除返利
        return $this->table('rebate_temp')->where($condition)->delete();
    }
    public function getStoreName($vid){
        $condition['vid']=$vid;
        return $this->table('vendor')->field('store_name')->where($condition)->find();

    }
    /*
     * 根据用户id查找店铺名字
     */
    public function getStoreNameBymid($member_id){
        $condition['member_id']=$member_id;
        return $this->table('vendor')->field('store_name')->where($condition)->find();

    }
    //
    public function getStoreDongTaiNum($vid=null){
        $condition=array();
        $condition['strace_storeid']=$vid;
        $condition['strace_state']=1;
        //        $Num['goodsNum']=$this->table('favorites')->field('count(*) as count')->where(array('member_id'=>$member_id,'fav_type'=>'goods'))->find();
        return $this->table('store_sns_tracelog')->field('strace_id')->where($condition)->group('strace_time')->select();

    }
}