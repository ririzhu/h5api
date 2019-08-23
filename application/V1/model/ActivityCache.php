<?php
namespace app\V1\model;

use think\Model;

class ActivityCache extends Model
{
    public function __construct()
    {
        parent::__construct('address');
    }

    //生成缓存的方法
    public function Activity($type, $data)
    {
        switch ($type) {
            case 'tuan':
                $this->handle_Date($type, $data);
                break;
            case 'xianshi':
                $this->handle_Date($type, $data);
                break;
            case 'p_mbuy':
                $this->handle_Date($type, $data);
                break;
            case 'pin_tuan':
                $this->handle_Date($type, $data);
                break;
            case 'pin_ladder_tuan':
                $this->handle_Date($type, $data);
                break;
            case 'sld_presale':
                $this->handle_Date($type, $data);
                break;

        }
    }


    public function handle_Date($type, $data)
    {

        switch ($type) {
            case 'tuan':
                $new_activity = array();

                foreach ($data as $value) {

                    $item_value = array(
                        'start_time' => $value['start_time'],
                        'end_time' => $value['end_time'],
                        'gid' => $value['gid'],
                        'promotion_type' => $type,
                        'promotion_price' => $value['tuan_price'] * 1,
                    );

                    $new_activity[$value['gid']][] = $item_value;
                }

                // 存储缓存文件 的生成时间
                // 获取 缓存文件 的最新更新时间
                $last_data['create_time'] = time();
                // 更新缓存最新时间到 数据库表
                Model('cache_time')->saveNewCacheTime($type);

                $last_data['data'] = $new_activity;

                dkcache('tuan_gid');
                wkcache('tuan_gid', $last_data);

                break;
            case 'xianshi':
                $new_activity = array();
                foreach ($data as $value) {

                    $item_value = array(
                        'start_time' => $value['start_time'],
                        'end_time' => $value['end_time'],
                        'gid' => $value['gid'],
                        'promotion_type' => $type,
                        'promotion_price' => $value['xianshi_price'] * 1,
                    );


                    $new_activity[$value['gid']][] = $item_value;
                }

                // 存储缓存文件 的生成时间
                // 获取 缓存文件 的最新更新时间
                $last_data['create_time'] = time();
                // 更新缓存最新时间到 数据库表
                Model('cache_time')->saveNewCacheTime($type);

                $last_data['data'] = $new_activity;

                dkcache('xianshi_gid');
                wkcache('xianshi_gid', $last_data);

                break;
            case 'p_mbuy':
                $new_activity = array();
                foreach ($data as $value) {

                    $item_value = array(
                        'gid' => $value['gid'],
                        'promotion_type' => $type,
                        'promotion_price' => $value['mbuy_price'] * 1,
                    );

                    $new_activity[$value['gid']][] = $item_value;

                }

                // 存储缓存文件 的生成时间
                // 获取 缓存文件 的最新更新时间
                $last_data['create_time'] = time();
                // 更新缓存最新时间到 数据库表
                Model('cache_time')->saveNewCacheTime($type);

                $last_data['data'] = $new_activity;

                dkcache('p_mbuy_gid');
                wkcache('p_mbuy_gid', $last_data);

                break;
            case 'pin_tuan':
                $new_activity = array();

                foreach ($data as $value) {

                    $item_value = array(
                        'start_time' => $value['sld_start_time'],
                        'end_time' => $value['sld_end_time'],
                        'gid' => $value['sld_gid'],
                        'promotion_type' => $type,
                        'promotion_price' => $value['sld_pin_price'] * 1,
                    );

                    $new_activity[$value['gid']][] = $item_value;
                }

                // 存储缓存文件 的生成时间
                // 获取 缓存文件 的最新更新时间
                $last_data['create_time'] = time();
                // 更新缓存最新时间到 数据库表
                Model('cache_time')->saveNewCacheTime($type);

                $last_data['data'] = $new_activity;

                dkcache('pin_tuan_gid');
                wkcache('pin_tuan_gid', $last_data);
                break;
            case 'pin_ladder_tuan':
                $new_activity = array();

                foreach ($data as $value) {

                    $item_value = array(
                        'start_time' => $value['sld_start_time'],
                        'end_time' => $value['sld_end_time'],
                        'gid' => $value['sld_gid'],
                        'promotion_type' => $type,
                        'promotion_price' => $value['sld_pin_price'] * 1,
                    );

                    $new_activity[$value['gid']][] = $item_value;
                }

                // 存储缓存文件 的生成时间
                // 获取 缓存文件 的最新更新时间
                $last_data['create_time'] = time();
                // 更新缓存最新时间到 数据库表
                Model('cache_time')->saveNewCacheTime($type);

                $last_data['data'] = $new_activity;

                dkcache('pin_ladder_tuan_gid');
                wkcache('pin_ladder_tuan_gid', $last_data);
                break;
            case 'sld_presale':
                $new_activity = array();

                foreach ($data as $value) {

                    $item_value = array(
                        'start_time' => $value['pre_start_time'],
                        'end_time' => $value['pre_end_time'],
                        'gid' => $value['gid'],
                        'promotion_type' => $type,
                        'promotion_price' => $value['pre_sale_price'] * 1,
                    );

                    $new_activity[$value['gid']][] = $item_value;
                }

                // 存储缓存文件 的生成时间
                // 获取 缓存文件 的最新更新时间
                $last_data['create_time'] = time();
                // 更新缓存最新时间到 数据库表
                Model('cache_time')->saveNewCacheTime($type);

                $last_data['data'] = $new_activity;

                dkcache('sld_presale');
                wkcache('sld_presale', $last_data);
                break;
        }

    }


    //
    /**
     * 返回 商品id总合（当前活动集合的商品ID）
     *
     * @param array activity_names 当前活动标示（可多个活动）
     * @return array $return
     *
     */
    public function Activity_Gid($activity_names=array())
    {
        $all_activity_arr = array('xianshi', 'bl', 'p_mbuy', 'suite', 'pin_tuan', 'pin_ladder_tuan', 'tuan','sld_presale');

        // 需要展示的活动标示集合
        $need_activity_arr = array_intersect($all_activity_arr,$activity_names);

        $result = array();

        // array('tuan','xianshi','p_mbuy','pin_tuan','man_song','zero_area');
        // 验证当前缓存 是否为 最新缓存
        $cache_time_model = new CacheTime();
        if (in_array('tuan', $need_activity_arr)) {
            $cache_time['tuan'] = $cache_time_model->getNewCacheTime('tuan');

            /*$tuan_gid = rkcache('tuan_gid',true);
            $tuan_key_gid = array();

            // 校验 获取文件时间 是否与 服务器最新时间一样
            if(isset($tuan_gid['create_time']) && $tuan_gid['create_time'] == $cache_time['tuan']){
                unset($tuan_gid['create_time']);
                $tuan_gid = $tuan_gid['data'];
            }else{
                dkcache('tuan_gid');
                $tuan_gid = rkcache('tuan_gid',true);
                unset($tuan_gid['create_time']);
                $tuan_gid = $tuan_gid['data'];
            }

            if (empty($tuan_gid)) {
                $tuan_gid = array();
            }else{
                $result = array_merge($result, $tuan_gid);
            }*/
        }
        if (in_array('xianshi', $need_activity_arr)) {
            $cache_time['xianshi'] = $cache_time_model->getNewCacheTime('xianshi');

            /*$xianshi_gid = rkcache('xianshi_gid',true);
            $xianshi_key_gid = array();

            // 校验 获取文件时间 是否与 服务器最新时间一样
            if(isset($xianshi_gid['create_time']) && $xianshi_gid['create_time'] == $cache_time['xianshi']){
                unset($xianshi_gid['create_time']);
                $xianshi_gid = $xianshi_gid['data'];
            }else{
                dkcache('xianshi_gid');
                $xianshi_gid = rkcache('xianshi_gid',true);
                unset($xianshi_gid['create_time']);
                $xianshi_gid = $xianshi_gid['data'];
            }

            if (empty($xianshi_gid)) {
                $xianshi_gid = array();
            }else{
                $result = array_merge($result, $xianshi_gid);
            }*/
        }
        if (in_array('p_mbuy', $need_activity_arr)) {
            $cache_time['p_mbuy'] = $cache_time_model->getNewCacheTime('p_mbuy');

            /*$p_mbuy_gid = rkcache('p_mbuy_gid',true);
            $p_mbuy_key_gid = array();

            // 校验 获取文件时间 是否与 服务器最新时间一样
            if(isset($p_mbuy_gid['create_time']) && $p_mbuy_gid['create_time'] == $cache_time['p_mbuy']){
                unset($p_mbuy_gid['create_time']);
                $p_mbuy_gid = $p_mbuy_gid['data'];
            }else{
                dkcache('p_mbuy_gid');
                $p_mbuy_gid = rkcache('p_mbuy_gid',true);
                unset($p_mbuy_gid['create_time']);
                $p_mbuy_gid = $p_mbuy_gid['data'];
            }

            if (empty($p_mbuy_gid)) {
                $p_mbuy_gid = array();
            }else{
                $result = array_merge($result, $p_mbuy_gid);
            }*/
        }
        if (in_array('pin_tuan', $need_activity_arr)) {
            $cache_time['pin_tuan'] = $cache_time_model->getNewCacheTime('pin_tuan');

            /*$pin_tuan_gid = rkcache('pin_tuan_gid',true);

            $pin_tuan_key_gid = array();

            // 校验 获取文件时间 是否与 服务器最新时间一样
            if(isset($pin_tuan_gid['create_time']) && $pin_tuan_gid['create_time'] == $cache_time['pin_tuan']){
                unset($pin_tuan_gid['create_time']);
                $pin_tuan_gid = $pin_tuan_gid['data'];
            }else{
                dkcache('pin_tuan_gid');
                $pin_tuan_gid = rkcache('pin_tuan_gid',true);
                unset($pin_tuan_gid['create_time']);
                $pin_tuan_gid = $pin_tuan_gid['data'];
            }

            if (empty($pin_tuan_gid)) {
                $pin_tuan_gid = array();
            }else{
                $result = array_merge($result, $pin_tuan_gid);
            }*/
        }
        //阶梯拼团
        if (in_array('pin_ladder_tuan', $need_activity_arr)) {
            $cache_time['pin_tuan'] = $cache_time_model->getNewCacheTime('pin_ladder_tuan');
            /*$pin_tuan_gid = rkcache('pin_ladder_tuan_gid',true);
            $pin_tuan_key_gid = array();
            // 校验 获取文件时间 是否与 服务器最新时间一样
            if(isset($pin_tuan_gid['create_time']) && $pin_tuan_gid['create_time'] == $cache_time['pin_tuan']){
                unset($pin_tuan_gid['create_time']);
                $pin_tuan_gid = $pin_tuan_gid['data'];
            }else{
                dkcache('pin_ladder_tuan_gid');
                $pin_tuan_gid = rkcache('pin_ladder_tuan_gid',true);
                unset($pin_tuan_gid['create_time']);
                $pin_tuan_gid = $pin_tuan_gid['data'];
            }
            if (empty($pin_tuan_gid)) {
                $pin_tuan_gid = array();
            }else{
                $result = array_merge($result, $pin_tuan_gid);
            }*/
        }
        //预售活动
        if (in_array('sld_presale', $need_activity_arr)) {
            $cache_time['presale'] = $cache_time_model->getNewCacheTime('sld_presale');
            /*$pin_tuan_gid = rkcache('sld_presale',true);
            $pin_tuan_key_gid = array();
            // 校验 获取文件时间 是否与 服务器最新时间一样
            if(isset($pin_tuan_gid['create_time']) && $pin_tuan_gid['create_time'] == $cache_time['presale']){
                unset($pin_tuan_gid['create_time']);
                $pin_tuan_gid = $pin_tuan_gid['data'];
            }else{
                dkcache('sld_presale');
                $pin_tuan_gid = rkcache('sld_presale',true);
                unset($pin_tuan_gid['create_time']);
                $pin_tuan_gid = $pin_tuan_gid['data'];
            }
            if (empty($pin_tuan_gid)) {
                $pin_tuan_gid = array();
            }else{
                $result = array_merge($result, $pin_tuan_gid);
            }*/
        }
        $new_arr = array();
        foreach ($result as $key => $value) {
            $nums = count($value);
            if ($nums > 1) {
                foreach ($value as $key1 => $value1) {
                    if (!empty($value1['end_time']) && $value1['end_time'] > time()) {
                        $gid = $value1['gid'];
                        $info = $value1;
                        break;
                    }

                }
                $new_arr[$gid] = $info;
            } else {
                $new_arr[$value[0]['gid']] = $value[0];
            }
        }
        if (in_array('bl', $need_activity_arr)) {
            // 获取所有优惠套装的 商品ID
            $goods_data = Model('p_bundling')->getBundlingGoodsList(1);
            // $bl_gid = low_array_column($goods_data,'gid');

            $bl_gid = array();
            foreach ($goods_data as $key => $value) {
                $bl_gid[$value['gid']]['gid'] = $value['gid'];
            }

            if (empty($bl_gid)) {
                $bl_gid = array();
            }else{
                $new_arr = array_merge($new_arr, $bl_gid);
            }
        }
        if (in_array('suite', $need_activity_arr)) {
            // 获取所有产品组合的 商品ID
            $goods_data = Model('p_suite_goods')->getComboGoodsList(1);
            // $suite_gid = low_array_column($goods_data,'combo_goodsid');

            $suite_gid = array();
            foreach ($goods_data as $key => $value) {
                $suite_gid[$value['combo_goodsid']]['gid'] = $value['combo_goodsid'];
            }

            if (empty($suite_gid)) {
                $suite_gid = array();
            }else{
                $new_arr = array_merge($new_arr, $suite_gid);
            }
        }

        return $new_arr;
    }

    public function goods_Price_End($activity_g, $goods_info, $from = 'wap')
    {
        if ($from == 'wap') {
            $allow_activity_type = array('tuan','xianshi','p_mbuy','pin_tuan');
        }else{
            $allow_activity_type = array('tuan','xianshi','pin_tuan');
        }

        $gid = $goods_info['gid'];
        $keys_gid = array_keys($activity_g);
        if (in_array($gid, $keys_gid)) {
            $promotion_type = $activity_g[$gid]['promotion_type'];
            if (!in_array($promotion_type, $allow_activity_type)) {
                $promotion_type = 'other';
            }
            switch ($promotion_type) {

                case 'tuan': //团购

                    //时间
                    if($activity_g[$gid]['end_time'] <time()){
                        $show_price = $goods_info['goods_price'];
                    }else{
                        $show_price = $activity_g[$gid]['promotion_price'];
                    }

                    break;

                case 'xianshi'://限时购

                    //时间
                    if($activity_g[$gid]['end_time'] <time()){
                        $show_price = $goods_info['goods_price'];
                    }else{
                        $show_price = $activity_g[$gid]['promotion_price'];
                    }
                    break;

                case 'p_mbuy'://手机专享

                    $show_price = $activity_g[$gid]['promotion_price'];
                    break;

                case 'pin_tuan'://拼团

                    //时间
                    if($activity_g[$gid]['end_time'] <time()){
                        $show_price = $goods_info['goods_price'];
                    }else{
                        $show_price = $activity_g[$gid]['promotion_price'];
                    }

                    break;
                default:
                    $show_price = $goods_info['goods_price'];
                    break;
            }


        }else{

            $show_price=$goods_info['goods_price'];

        }

        switch ($from) {
            case 'pc':
                $show_price = sldPriceFormat($show_price);
                break;

            default:
                $show_price = $show_price * 1;
                break;
        }
        return $show_price;

    }
}