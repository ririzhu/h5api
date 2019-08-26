<?php
namespace app\V1\model;

use think\Model;
use think\db;
class GoodsActivity extends Model
{
    public function __construct(){
        parent::__construct('goods');
    }

    const STATE1 = 1;       // 出售中

    /**
     *
     * 返回商品 重组数组
     *
     * @param array goods_data
     * @param string from ('wap','pc')
     * @return array $return
     */
    public function rebuild_goods_data($goods_data,$from='wap',$extent=[]){
        $activity_cache=new ActivityCache();
        $activity_g=$activity_cache->Activity_Gid(array('xianshi', 'p_mbuy', 'pin_tuan', 'pin_ladder_tuan','tuan','sld_presale'));
        $keys=array_keys($activity_g);
        if (isset($goods_data['gid']) && $goods_data['gid']) {
            // 单个商品
            $goods_item = $goods_data;
            $goods_item_id = $goods_data['gid'];
            //活动标识
            if(in_array($goods_item_id,$keys)){
                if(empty($activity_g[$goods_item_id]['end_time']) && $activity_g[$goods_item_id]['promotion_type']=='p_mbuy'){
                    $goods_item['promotion_type']='p_mbuy';
                    $goods_item['promotion_price']=$activity_g[$goods_item_id]['promotion_price'];
                    $goods_item['show_price']=$activity_cache->goods_Price_End($activity_g,$goods_item,$from);

                }else if(!empty($activity_g[$goods_item_id]['end_time']) && $activity_g[$goods_item_id]['end_time']>time()){
                    $goods_item['promotion_type']=$activity_g[$goods_item_id]['promotion_type'];
                    $goods_item['promotion_price']=$activity_g[$goods_item_id]['promotion_price'];

                    $goods_item['show_price']=$activity_cache->goods_Price_End($activity_g,$goods_item,$from);

                    if ($activity_g[$goods_item_id]['start_time']) {
                        $goods_item['promotion_start_time']=date('Y年m月d日 H:i',$activity_g[$goods_item_id]['start_time']);
                    }
                    if ($activity_g[$goods_item_id]['end_time']) {
                        $goods_item['promotion_end_time']=date('Y年m月d日 H:i',$activity_g[$goods_item_id]['end_time']);
                    }

                    // 重构 限时折扣 详情页 数据
                    if($activity_g[$goods_item_id]['promotion_type'] == 'xianshi' && !empty($activity_g[$goods_item_id]['start_time']) && !empty($activity_g[$goods_item_id]['end_time']) ){
                        $goods_item['promotion_start_time']=date('H:i',$activity_g[$goods_item_id]['start_time']);

                        // 获取限时折扣 购买下限 (根据商品ID 获取 所在限时折扣活动 的购买下限限制)
                        $xianshi_condition['gid'] = $activity_g[$goods_item_id]['gid'];
                        $xianshi_detail = DB::table('bbc_p_xianshi_goods')->where($xianshi_condition)->find();
                        if (!empty($xianshi_detail['lower_limit'])) {
                            $xianshi_low_limit = $xianshi_detail['lower_limit'];
                        }else{
                            $xianshi_low_limit = 1;
                        }
                        $goods_item['lower_limit'] = $xianshi_low_limit;

                        if($activity_g[$goods_item_id]['start_time']>time()){
                            //活动未开始
                            $goods_item['promotion_run_flag'] = 0;
                            $goods_item['show_price'] = $goods_item['goods_price'];
                        }else if($activity_g[$goods_item_id]['end_time']<time()){
                            //活动已经结束
                            $goods_item['promotion_run_flag'] = -1;
                            $goods_item['show_price'] = $goods_item['goods_price'];
                        }else if($activity_g[$goods_item_id]['start_time']<time() && $activity_g[$goods_item_id]['end_time']>time()){
                            //活动进行中
                            $goods_item['promotion_run_flag'] = 1;
                        }
                        $goods_item['promotion_end_time']=date('Y/m/d H:i:s',$activity_g[$goods_item_id]['end_time']);
                        $goods_item['promotion_start_time']=date('Y/m/d H:i:s',$activity_g[$goods_item_id]['start_time']);

                        // 已售卖的 数量
                        $saled_num = $goods_item['goods_salenum'];
                        // 库存
                        $storage_num = $goods_item['goods_storage'];
                        // 售卖进度
                        $progress_num = ($saled_num / $storage_num) * 100;
                        $goods_item['saled_num'] = $saled_num;
                        $goods_item['sale_storge'] = $storage_num;
                        $goods_item['progress_num'] = $progress_num;
                        $goods_item['折扣'] = number_format($xianshi_detail['xianshi_price'] / $xianshi_detail['goods_price'] * 10, 1).'折';
                        $goods_item['down_price'] = sldPriceFormat($goods_item['goods_price'] - $xianshi_detail['xianshi_price']);
                    }else if($activity_g[$goods_item_id]['promotion_type'] == 'tuan' && !empty($activity_g[$goods_item_id]['start_time']) && !empty($activity_g[$goods_item_id]['end_time']) ){

                        if ($activity_g[$goods_item_id]['start_time'] <= time() && $activity_g[$goods_item_id]['end_time'] >= time()) {
                            // 获取 团购商品详细数据
                            $model_tuan = new Tuan();
                            $tuanCondition['gid'] = $activity_g[$goods_item_id]['gid'];
                            $tuan_goods_info = $model_tuan->getTuanOnlineInfo($tuanCondition);

                            // 已买数量
                            $goods_item['saled_num'] = $tuan_goods_info['virtual_quantity'] + $tuan_goods_info['buy_quantity'];
                            // 参团人数
                            $goods_item['saled_member_num'] = $tuan_goods_info['buyer_count'];

                            $goods_item['promotion_start_time']=date('Y/m/d H:i:s',$activity_g[$goods_item_id]['start_time']);
                            $goods_item['promotion_end_time']=date('Y/m/d H:i:s',$activity_g[$goods_item_id]['end_time']);

                            $goods_item['sheng_price'] = number_format($tuan_goods_info['goods_price']-$tuan_goods_info['tuan_price'],2);

                        }else{
                            $goods_item['promotion_type'] = 'none';
                            //unset($goods_item['show_price']);
                        }
                    }else if($activity_g[$goods_item_id]['promotion_type'] == 'pin_tuan' && !empty($activity_g[$goods_item_id]['start_time']) && !empty($activity_g[$goods_item_id]['end_time']) ){
                        $goods_item['promotion_start_time']=date('Y/m/d H:i:s',$activity_g[$goods_item_id]['start_time']);
                        $goods_item['promotion_end_time']=date('Y/m/d H:i:s',$activity_g[$goods_item_id]['end_time']);
                    } else if($activity_g[$goods_item_id]['promotion_type'] == 'pin_ladder_tuan' && !empty($activity_g[$goods_item_id]['start_time']) && !empty($activity_g[$goods_item_id]['end_time']) ){
                        $goods_item['promotion_start_time']=date('Y/m/d H:i:s',$activity_g[$goods_item_id]['start_time']);
                        $goods_item['promotion_end_time']=date('Y/m/d H:i:s',$activity_g[$goods_item_id]['end_time']);
                    } else if($activity_g[$goods_item_id]['promotion_type'] == 'sld_presale' && !empty($activity_g[$goods_item_id]['start_time']) && !empty($activity_g[$goods_item_id]['end_time']) ){
                        $goods_item['promotion_start_time']=date('Y/m/d H:i:s',$activity_g[$goods_item_id]['start_time']);
                        $goods_item['promotion_end_time']=date('Y/m/d H:i:s',$activity_g[$goods_item_id]['end_time']);
                    }
                }
            }

            switch ($from) {
                case 'pc':
                    if(isset($goods_item['show_price']))
                    $goods_item['show_price'] = $goods_item['show_price'] ? $goods_item['show_price'] : sldPriceFormat($goods_item['goods_price']);
                    break;

                default:
                    if(isset($goods_item['show_price']))
                    $goods_item['show_price'] = $goods_item['show_price'] ? $goods_item['show_price'] : $goods_item['goods_price']*1;
                    break;
            }

            $goods_data = $goods_item;
        }else{
            // 多个商品
            foreach ($goods_data as $key => $value) {
                $goods_item = $value;
                $goods_item_id = $value['gid'];

                //活动标识
                if(in_array($goods_item_id,$keys)){
                    if(empty($activity_g[$goods_item_id]['end_time']) && $activity_g[$goods_item_id]['promotion_type']=='p_mbuy'){
                        $goods_item['promotion_type']='p_mbuy';
                        $goods_item['promotion_price']=$activity_g[$goods_item_id]['promotion_price'];
                        $goods_item['show_price']=$activity_cache->goods_Price_End($activity_g,$goods_item,$from);

                    }else if(!empty($activity_g[$goods_item_id]['end_time']) && $activity_g[$goods_item_id]['end_time']>time()){
                        $goods_item['promotion_type']=$activity_g[$goods_item_id]['promotion_type'];
                        $goods_item['promotion_price']=$activity_g[$goods_item_id]['promotion_price'];

                        $goods_item['show_price']=$activity_cache->goods_Price_End($activity_g,$goods_item,$from);

                        if ($activity_g[$goods_item_id]['start_time']) {
                            $goods_item['start_time']=$activity_g[$goods_item_id]['start_time'];
                            $goods_item['promotion_start_time']=date('Y年m月d日 H:i',$activity_g[$goods_item_id]['start_time']);
                        }
                        if ($activity_g[$goods_item_id]['end_time']) {
                            $goods_item['end_time']=$activity_g[$goods_item_id]['end_time'];
                            $goods_item['promotion_end_time']=date('Y年m月d日 H:i',$activity_g[$goods_item_id]['end_time']);
                        }

                    }
                }

                switch ($from) {
                    case 'pc':
                        $goods_item['show_price'] = $goods_item['show_price'] ? $goods_item['show_price'] : sldPriceFormat($goods_item['goods_price']);
                        break;

                    default:
                        $goods_item['show_price'] = $goods_item['show_price'] ? $goods_item['show_price'] : $goods_item['goods_price']*1;
                        break;
                }

                $goods_data[$key] = $goods_item;
            }
        }
        //会员等级重新处理价格,只处理cwap端和pc会员端
        if(Config('member_grade_open')){
            if(APP_ID == 'mobile' || APP_ID == 'mall'){
                if($extent['grade']){
                    $goods_data = $this->grade_price($goods_data);
                }
            }
        }
        return $goods_data;
    }
    /*
     * 会员等级价格
     */
    public function grade_price($goods_data)
    {
        $grade_model = model('grade');
        if(isset($goods_data['gid']) && !empty($goods_data['gid'])){
            //单个商品
            $goods_data = $grade_model->getgradeprice($goods_data);
        }else{
            //多个商品
            foreach($goods_data as $k=>$v){
                //条件
                $goods_data[$k] = $grade_model->getgradeprice($v);
            }
        }
        return $goods_data;
    }

    /*
     *通过gid查找属于哪一个活动
     *
     */
    public function GoodsActivity($gid){

        $model_goods=Model('goods');
        $condition=array();
        $condition['gid']=$gid;

        $goods_info=$model_goods->getGoodsInfoByID($gid,'gid,goods_name,goods_price,goods_hid');
        $goods_info['goods_price']=$goods_info['goods_price']*1;

        //查找属于哪个活动
        $Activity_info=$this->OtherActivity_Pan($goods_info);

        if(!empty($Activity_info['start_time'])){
            $Activity_info['start_time']=date('Y-m-d H:i:s',$Activity_info['start_time']);
        }

        if(!empty($Activity_info['end_time'])){
            $Activity_info['end_time']=date('Y-m-d H:i:s',$Activity_info['end_time']);
        }

        return $Activity_info;
    }

    /**
     * 判断商品 在时间段内是否有其他活动
     *
     * @param int gid
     * @param int commonid
     * @param string $start  开始时间戳
     * @param string $end  结束时间戳
     * @param string $nokey  不判断哪个活动  tuan xianshi pin zhuanxiang
     * @return boolean
     */
    public function OtherActivity_Pan($goods_info){

        //限时折扣
        if (C('promotion_allow')) {
            $xianshi=rkcache('xianshi_gid');
            $result=$this->searchActivity($goods_info,$xianshi);

            if(!empty($result)){

                return $result;
            }
        }

        //团购
        if (C('tuan_allow')) {
            $tuan_list=rkcache('tuan_gid');
            $result=$this->searchActivity($goods_info,$tuan_list);

            if(!empty($result)){
                return $result;
            }
        }


        //手机专享
        $xianshi=rkcache('p_mbuy_gid');
        $result=$this->searchPmbuy($goods_info,$xianshi);

        if(!empty($result)){
            return $result;
        }

        //拼团的
        $pin_tuan=rkcache('pin_tuan_gid');
        if(!empty($pin_tuan)){
            $result=$this->searchActivity($goods_info,$pin_tuan);

            if(!empty($result)){
                return $result;
            }
        }
        //阶梯拼团的
        $pin_tuan=rkcache('pin_ladder_tuan_gid');
        if(!empty($pin_tuan)){
            $result=$this->searchActivity($goods_info,$pin_tuan);
            if(!empty($result)){
                return $result;
            }
        }
        //预售的
        $pin_tuan=rkcache('sld_presale');
        if(!empty($pin_tuan)){
            $result=$this->searchActivity($goods_info,$pin_tuan);
            if(!empty($result)){
                return $result;
            }
        }
        //满送的
        $man_jian=rkcache('man_song_gid');

        if(!empty($man_jian)){
            $result=$this->searchActivity($goods_info,$man_jian);

            if(!empty($result)){
                return $result;
            }
        }


        return '';

    }


    public function searchActivity($goods_info,$activity){

        $keys=array_keys($activity);

        if(in_array($goods_info['gid'],$keys) && time()<$activity[$goods_info['gid']]['end_time']){
            $activity[$goods_info['gid']]['goods_price']=$goods_info['goods_price']*1;
            return $activity[$goods_info['gid']];
        }else{

            return '';
        }

    }


    public function searchPmbuy($goods_info,$activity){

        $keys=array_keys($activity);

        if(in_array($goods_info['gid'],$keys)){
            $activity[$goods_info['gid']]['goods_price']=$goods_info['goods_price']*1;
            return $activity[$goods_info['gid']];
        }else{
            return '';
        }

    }

    /**
     * 返回 除当前活动 已参与其他活动的商品ID集合（限时折扣[xianshi]、优惠套装[bl]、移动专享[p_mbuy]、产品组合[suite]、拼团[pin_tuan]、团购[tuan]）
     *
     * @param int vid 商户ID
     * @param array activity_names 当前活动标示（可多个活动）
     * @return array $return
     *
     */
    public function get_gids_other_activiting($vid,$activity_names=array())
    {
        $all_activity_arr = array('xianshi', 'bl', 'p_mbuy', 'suite', 'pin_tuan', 'pin_ladder_tuan', 'tuan','sld_presale');

        $need_activity_arr = array_diff($all_activity_arr,$activity_names);

        $return_last = array();

        // 获取 活动商品ID集合
        $activity_cache=Model('activity_cache');

        $activity_g=$activity_cache->Activity_Gid($need_activity_arr);

        $activity_gids = low_array_column($activity_g,'gid');
        //过滤掉锁定的商品
        $model = model();
        if(!$vid){
            $vid = $_SESSION['vid'];
        }
        $common = $model->table('goods_common,goods')->join('left')->on('goods.goods_commonid=goods_common.goods_commonid')->where(['goods_common.vid'=>$vid,'goods_common.goods_lock'=>1])->field('goods_common.goods_commonid,goods.gid')->limit(false)->select();
        $lockgid = low_array_column($common,'gid');
        if($lockgid){
            $activity_gids = array_merge($activity_gids,$lockgid);
            $activity_gids = array_unique( $activity_gids);
        }
        if (!empty($activity_gids)) {
            $return_last = $activity_gids;
        }

        return $return_last;
    }

    /**
     * 返回 订单的活动类型
     *
     * @param array order_data
     * @return array $return
     *
     */
    public function rebuild_order_data($order_data)
    {
        // 活动展示 文字
        $promotion_type_str_data = array(
            'normal' => '',
            'tuan' => '团购',
            'xianshi' => '限时折扣',
            'p_mbuy' => '手机专享',
            'pin_tuan' => '拼团',
            'bundling' => '优惠套装',
            'pin_ladder_tuan' => '阶梯团购',
            'sld_presale' => '预售',
        );
        // 店铺活动 展示文字
        $store_promotion_type_str_data = array(
            'normal' => '',
            'mansong' => '满即送',
        );
        // 校验是订单列表 还是 单个订单
        if (isset($order_data['order_id'])) {
            $item_order_promotion_type = 'normal';
            $item_order_store_promotion_type = 'normal';
            if(isset($order_data['extend_order_goods']) && !empty($order_data['extend_order_goods'])){
                $item_order_goods_list = $order_data['extend_order_goods'];
                if (count($item_order_goods_list)) {
                    switch ($item_order_goods_list[0]['goods_type']) {
                        case '1':
                            // 普通订单
                            break;
                        case '2':
                            $item_order_promotion_type = 'tuan';
                            break;
                        case '3':
                            $item_order_promotion_type = 'xianshi';
                            break;
                        case '4':
                            $item_order_promotion_type = 'bundling';
                            break;
                        case '6':
                            break;
                        case '7':
                            // $item_order_promotion_type = 'pin_tuan';
                            break;
                        case '8':
                            $item_order_promotion_type = 'p_mbuy';
                            break;

                        default:
                            # code...
                            break;
                    }
                }
            }
            // 根据订单 pin_id 是否有值 判断是否为 拼团订单
            if ($order_data['pin_id']) {
                $item_order_promotion_type = 'pin_tuan';
            }
            if ($order_data['pin_order_id']) {
                $item_order_promotion_type = 'pin_ladder_tuan';
            }
            if ($order_data['pre_order_id']) {
                $item_order_promotion_type = 'sld_presale';
            }

            $order_data['promotion_type'] = $item_order_promotion_type;
            $order_data['promotion_type_str'] = isset($promotion_type_str_data[$item_order_promotion_type]) ? $promotion_type_str_data[$item_order_promotion_type] : '';

            // 验证当前订单是否参与 店铺活动 满就送
            if(isset($order_data['extend_order_common']) && !empty($order_data['extend_order_common'])){
                $item_order_common_info = $order_data['extend_order_common'];
                if (isset($item_order_common_info['promotion_info']) && $item_order_common_info['promotion_info']) {
                    $item_order_store_promotion_type = 'mansong';
                }
            }
            $order_data['store_promotion_type'] = $item_order_store_promotion_type;
            $order_data['store_promotion_type_str'] = isset($store_promotion_type_str_data[$item_order_store_promotion_type]) ? $store_promotion_type_str_data[$item_order_store_promotion_type] : '';
        }else{
            foreach ($order_data as $key => $value) {
                $item_order_promotion_type = 'normal';
                $item_order_store_promotion_type = 'normal';
                if(isset($value['extend_order_goods']) && !empty($value['extend_order_goods'])){
                    $item_order_goods_list = $value['extend_order_goods'];
                    if (count($item_order_goods_list)) {
                        switch ($item_order_goods_list[0]['goods_type']) {
                            case '1':
                                // 普通订单
                                break;
                            case '2':
                                $item_order_promotion_type = 'tuan';
                                break;
                            case '3':
                                $item_order_promotion_type = 'xianshi';
                                break;
                            case '4':
                                $item_order_promotion_type = 'bundling';
                                break;
                            case '6':
                                break;
                            case '7':
                                // $item_order_promotion_type = 'pin_tuan';
                                break;
                            case '8':
                                $item_order_promotion_type = 'p_mbuy';
                                break;

                            default:
                                # code...
                                break;
                        }
                    }
                }

                // 根据订单 pin_id 是否有值 判断是否为 拼团订单
                if ($value['pin_id']) {
                    $item_order_promotion_type = 'pin_tuan';
                }
                if ($value['pin_order_id']) {
                    $item_order_promotion_type = 'pin_ladder_tuan';
                }
                if ($value['pre_order_id']) {
                    $item_order_promotion_type = 'sld_presale';
                }

                $order_data[$key]['promotion_type'] = $item_order_promotion_type;
                $order_data[$key]['promotion_type_str'] = isset($promotion_type_str_data[$item_order_promotion_type]) ? $promotion_type_str_data[$item_order_promotion_type] : '';

                // 验证当前订单是否参与 店铺活动 满就送
                if(isset($value['extend_order_common']) && !empty($value['extend_order_common'])){
                    $item_order_common_info = $value['extend_order_common'];
                    if (isset($item_order_common_info['promotion_info']) && $item_order_common_info['promotion_info']) {
                        $item_order_store_promotion_type = 'mansong';
                    }
                }
                $order_data[$key]['store_promotion_type'] = $item_order_store_promotion_type;
                $order_data[$key]['store_promotion_type_str'] = isset($store_promotion_type_str_data[$item_order_store_promotion_type]) ? $store_promotion_type_str_data[$item_order_store_promotion_type] : '';
            }
        }

        return $order_data;
    }

}