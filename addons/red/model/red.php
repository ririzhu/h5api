<?php
use think\db;
class redModel   {

    public function __construct() {

    }

    public $redtype = array(
        1 => '派发优惠券',
        2 => '注册优惠券',
        3 => '活动优惠券', //未使用
        4 => '推荐优惠券',
        5 => '进店优惠券', //未使用
        6 => '领券中心优惠券' //未使用
    );

    public $redstatus = array(
        0 => '失效',
        1 => '正常',
    );

    public $redused = array(
        true => '未使用',
        false => '已使用',
    );

    /**
     * 读取列表
     * @param array $condition 查询条件
     * @param int $page 分页数
     * @param string $order 排序
     * @param string $field 所需字段
     * @return array 团购列表
     *
     */
    public function getRedList($condition, $page = null, $order = 'red.id desc', $field = 'red.*,red_info.redinfo_money,red_info.redinfo_start,red_info.redinfo_end,red_info.redinfo_type,red_info.redinfo_ids,red_info.redinfo_self,red_info.redinfo_store,red_info.redinfo_full,red_info.redinfo_create,red_info.redinfo_together', $limit = 0) {
        $field.=',min(redinfo_money) min_money,
        max(redinfo_money) max_money,
        min(redinfo_start) as min_date,
        max(redinfo_end) as max_date';
        $condition['red_delete'] = 0;
        $red_list = $this->table('red_info,red')->join('right')
            ->on('red.id=red_info.red_id')->field($field)->where($condition)
            ->group('red_info.red_id')
            ->page($page)->order($order)->limit($limit)->select();
        return $red_list;
    }

    /**
     * 增加red
     * @param array $param 数组
     * @return int 添加主键
     *
     */
    public function addRed($param){
        // 发布团购锁定商品
        return $this->table('red')->insert($param);
    }

    /**
     * 增加red
     * @param array $param 数组
     * @return int 添加主键
     *
     */
    public function addRedInfo($param){
        // 发布团购锁定商品
        return $this->table('red_info')->insert($param);
    }

    /**
     * 查看red
     * @param array $where 条件
     * @return array red表和arr（red_info）
     *
     */
    public function getRedInfo($where){
        $red = $this->table('red')->where($where)->find();
        $redinfo = $this->table('red_info')->where(array('red_id'=>$red['id']))->select();
        $red['arr'] = $redinfo;
        return $red;
    }

    /*
     *定时操作 v2 v3
     *
     */
    public function getRedInfo_cron($where){
        $red = $this->table('red')->where($where)->find();
        $redinfo = $this->table('red_info')->where(array('red_id'=>$red['id']))->select();

        foreach ($redinfo as $key=>$value){
            $redinfo[$key]['redinfo_start']=time();
            $redinfo[$key]['redinfo_end']=time()+86400*15;
        }

        $red['arr'] = $redinfo;
        return $red;
    }


    /*
     *定时操作 plus会员
     *
     */
    public function getRedInfo_cron_plus($where){
        $red = $this->table('red')->where($where)->find();
        $redinfo = $this->table('red_info')->where(array('red_id'=>$red['id']))->select();

        foreach ($redinfo as $key=>$value){
            $redinfo[$key]['redinfo_start']=time();
            $redinfo[$key]['redinfo_end']=time()+86400*30;
        }

        $red['arr'] = $redinfo;
        return $red;
    }



    /**
     * 查看red
     * @param int $id red表id
     * @return array red表和arr（red_info）
     *
     */
    public function getCanSend($id){
        $red = $this->getRedInfo(array('id'=>$id,'red_type'=>1,'red_status'=>1));
        return $red;
    }

    /**
     * 发放推荐优惠券
     * @param string $type 类型
     *
     */
    public function SendRedInvite($member_id){
        $member_info = Model('member')->getMemberInfo(array('member_id'=>$member_id));  //购买用户
        if($member_info['inviter_id']) {
            //有推荐人，再继续
            $parent_info = Model('member')->getMemberInfo(array('member_id' => $member_info['inviter_id']));
            $red_info = $this->getRedInfo(array('red_type'=>4,'red_status'=>1,'red_receive_start'=>array('lt',TIMESTAMP),'red_receive_end'=>array('gt',TIMESTAMP),'red_hasget'=>array('exp',' red_hasget < red_limit or red_limit = 0')));
            if($red_info && count($red_info['arr'])) {
                //平台有推荐优惠券，再继续
                $geshu = $this->table('red_user')->where(array('reduser_uid'=>$parent_info['member_id'],'red_id'=>$red_info['id']))->count();
                if($geshu<$red_info['red_rach_max']) {
                    //这个人领得推荐优惠券没超过限制，再继续
                    $this->SendRed(array(0 => $parent_info['member_id']), $red_info);
                }
            }
        }
    }

    /**
     * 插入优惠券
     * @param array $ids  多个会员id
     * @return int 发送成功个数
     *
     */
    public function SendRed($ids,$par){
        if(!$ids){
            return 0;
        }
        $sql = 'insert into `'.DBPRE.'red_user` (reduser_proof,reduser_uid,red_id,redinfo_id,reduser_get,redinfo_start,redinfo_end) values ';
        foreach ($ids as $v) {
            $yCode = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
            foreach ($par['arr'] as $vv) {
                $orderSn = $yCode[intval(date('Y')) - 2011] . strtoupper(dechex(date('m'))) . date('d') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf('%02d', rand(0, 99));
                $sql .= '(\''.$orderSn.'\','.$v.','.$par['id'].','.$vv['id'].','.TIMESTAMP.','.$vv['redinfo_start'].','.$vv['redinfo_end'].'),';
            }
        }
        $sql = substr($sql,0,strlen($sql)-1);
        $geshu = Db::query($sql);
        if($geshu){
            $this->table('red')->where(array('id'=>$par['id']))->update(array('red_hasget'=>array('exp','red_hasget+'.intval($geshu))));
        }
        return $geshu;
    }

    /**
     * 根据会员等级获得会员id
     * @param int $grade 会员等级id
     * @param int 优惠券表id 如果不需要判断重复不用传
     * @return int 发送成功个数
     *
     */
    public function getUserIdsByGrade($grade,$redid=0,$id=0){
        $list_setting = Model('setting')->getListSetting('member_grade');
        $grades = unserialize($list_setting);
        //获取等级数据
        $min = isset($grades[$grade - 1]) ? $grades[$grade - 1]['growthvalue'] : 0 ;
        $max = isset($grades[$grade]) ? $grades[$grade]['growthvalue'] : 0 ;

        if($grade==0){
            $condition['member_growthvalue'] = array('elt', $min);
        }elseif ($grade==count($grades)) {
            $condition['member_growthvalue'] = array('egt', $min);
        }elseif($grade!=count($grades)){
            $condition['member_growthvalue'] = array('between',array($min,$max-1));
        }

        $ids = $this->table('member')->where($condition)->field('member_id')->col('member_id');
        $user_ids = join(',',$ids);

        return $user_ids;

    }

    /**
     * 读取优惠券发放列表
     * @param array $condition 查询条件
     * @param int $page 分页数
     * @param string $order 排序
     * @param string $field 所需字段
     * @return array
     *
     */
    public function getRedUserList($condition, $page = null, $order = 'reduser_use desc,bbc_red_user.id asc',$to="list") {

        //优惠券用户、优惠券数据
        $red_user_list = DB::table('bbc_red_user')->join("bbc_red",'bbc_red_user.red_id=bbc_red.id')->
        field('bbc_red_user.*,bbc_red.red_title,bbc_red.red_type,red_status')->where($condition)->page($page)->order($order)->select();

        //用户数据
        $member_ids = low_array_column($red_user_list,'reduser_uid');
        $where['member_id'] = array('in',arrayToString($member_ids));
        //$member_list = DB::table('bbc_member')->field('member_id,member_name,member_truename,wx_nickname')->where($where)->force("member_id")->select();//column("member_id,member_id,member_name,member_truename,wx_nickname","member_id");
        $member_list = DB::table('bbc_member')->field('member_id,member_name,member_truename,wx_nickname')->where($where)->select();//column("member_id,member_id,member_name,member_truename,wx_nickname","member_id");
        $all_list = array();
        if($to=="list"){
            foreach($red_user_list as $k=>$v){
                //$all_list[$k] = DB::table('bbc_red_info')->where(" red_id = ".$v['red_id'])->force("id")->find();
                $all_list[$k] = DB::table('bbc_red_info')->where(" red_id = ".$v['red_id'])->find();
                $all_list[$k]['red_type']=$red_user_list[$k]['red_type'];
                $all_list[$k]['red_status']=$red_user_list[$k]['red_status'];
            }
            return $all_list;
        }
        //优惠券信息数据
        $redinfo_ids = low_array_column($red_user_list,'redinfo_id');
        $where2['id'] = array('in',arrayToString($redinfo_ids));
        //$redinfo_list = DB::table('bbc_red_info')->where(" id in (".arrayToString(array_unique($redinfo_ids)).")")->force("id")->select();
        $redinfo_list = DB::table('bbc_red_info')->where(" id in (".arrayToString(array_unique($redinfo_ids)).")")->select();
        $all_list = array();
        //全数据
        foreach ($red_user_list as $k=>$v){
            $all_list[] = array_merge($member_list[$v['reduser_uid']], $redinfo_list[$v['redinfo_id']], $v);
        }
        foreach ($all_list as $k=>$v){
            if($v['redinfo_end']-TIMESTAMP < 259200){
                $all_list[$k]['sheng'] = ceil(($v['redinfo_end']-TIMESTAMP) / 86400 ) ;
            }else{
                $all_list[$k]['sheng'] = '';
            }
        }
        return $all_list;
    }

    /**
     * 读取可领优惠列表  以优惠券信息表为准
     * @param array $condition 查询条件
     * @param int $page 分页数
     * @param string $order 排序
     * @param string $field 所需字段
     * @return array
     *
     */
    public function getRedLingList($member_id='',$condition, $page = null, $order = 'red_info.id desc') {

        if($_GET['vid']){
            $order = 'red.red_vid='.$_GET['vid'].' desc,'.$order;
        }

        //优惠券信息、优惠券数据
        $red_list = $this->table('red_info,red')->join('left')->on('red_info.red_id=red.id')->
        field('red_info.*,red.id as red_id,red.red_title,red.red_type,red_status,red.red_limit,red.red_hasget,red.red_rach_max,red.red_receive_end,red.red_receive_start')->where($condition)->page($page)->order($order)->select();
        $red_ids = low_array_column($red_list,'id');


        //读取用户优惠券数据
        if($member_id){
            $where['reduser_uid'] = $member_id;
        }
        $where['redinfo_id'] = array('in',join(',',$red_ids));
        $use_red_ids = $this->table('red_user')->where($where)->field('redinfo_id,reduser_use,count(*) as num')->group('redinfo_id')->key('redinfo_id')->select();

        foreach ($red_list as $k=>$v){
            //计算领取百分比
            if($v['red_limit']) {
                $red_list[$k]['prent'] = ceil($v['red_hasget'] / $v['red_limit'] * 100);
            }else{
                $red_list[$k]['prent'] = ceil((TIMESTAMP - $v['red_receive_start']) / ($v['red_receive_end'] - $v['red_receive_start']) *100 );
            }
//            $red_list[$k]['prent'] = $red_list[$k]['prent']==100?99:$red_list[$k]['prent'];

            //结束时间格式化
            $red_list[$k]['red_receive_end_text'] = date('Y/m/d H:i:s', $v['red_receive_end']);

            //判断用户是否已领
            if($use_red_ids[$v['id']] && $member_id && $use_red_ids[$v['id']]['num']>=$v['red_rach_max']){
                if($use_red_ids[$v['id']]['reduser_use']){
                    unset($red_list[$k]);
                    continue;
                }
                $red_list[$k]['have'] = $use_red_ids[$v['id']]['num'];
            }else{
                $red_list[$k]['have'] = 0;
            }
        }

        return $red_list;
    }

    /**
     * 判断优惠券可领取
     * @param int $member_id 用户id
     * @param int $red_id  红包信息表id
     * @return array
     *
     */
    function ling_red($member_id,$red_id){


        if(!$member_id){
            return '请先登陆，再领券';
        }

        $condition['red_status'] = 1;
        $condition['red_info.id'] = $red_id;
        $condition['red_front_show'] = 1;
        $condition['red_receive_start'] = array('lt',TIMESTAMP);
        $condition['red_receive_end'] = array('gt',TIMESTAMP);
        $red_info  = $this->getRedLingList($member_id,$condition);

        if($red_info && count($red_info)){
            $red_info = $red_info[0];
            if($red_info['have']>=$red_info['red_rach_max']){
                return '该优惠券最多可领取'.$red_info['red_rach_max'].'次';
            }else{
                if($red_info['red_limit']!=0 && $red_info['red_hasget']>=$red_info['red_limit']){
                    return '优惠券已被抢光了';
                }

                $red_info2 = $this->getRedInfo(array('id'=>$red_info['red_id'],'red_receive_start'=>array('lt',TIMESTAMP),'red_receive_end'=>array('gt',TIMESTAMP)));
                if(!$red_info2 || !$red_info2['arr']){
                    return '领取失败，优惠券找不到了';
                }
                if( $this->SendRed( array(0 => $member_id), $red_info2 ) ){
                    return '1';
                }else{
                    return '领取失败';
                }
            }
        }else{
            return '无法找到该优惠券';
        }
    }

    /**
     * 使用优惠券 包含验证 和 使用
     * @param int $member_id   用户id
     * @param int $red_user_id   red_user表id
     * @param array $store_cart_list   产品数组
     * @return array 返回新的产品价格数组
     *
     */
    function use_red($member_id,$red_user_id,$store_cart_list,$store_final_order_total){

        //店铺优惠券
        if($red_user_id['vred']) {
            // 店铺 =》优惠券
            $reds = array();

            foreach ($red_user_id['vred'] as $wqk=>$wqv) {
                $where = [];
                $where['reduser_use'] = array('eq', 0);
                $where['redinfo_end'] = array('gt', TIMESTAMP);
                $where['redinfo_start'] = array('lt', TIMESTAMP);
                $where['reduser_uid'] = $member_id;
                $where['red_user.id'] = $wqv;
                $red_list = $this->getRedUserList($where);
                if (empty($red_list)) {
                    return array('error' => '您的店铺优惠券已失效，不能使用');
                }
                $red_list = $this->filter_red($store_cart_list, $red_list, false);
                if (empty($red_list)) {
                    return array('error' => '验证优惠券错误，不能使用');
                }

                $red_info = $red_list[0];

                $zong = 0;
                $num = 0;
                foreach ($store_cart_list[$red_info['red_vid']] as $vv) {
                    $zong += $vv['goods_num'] * $vv['goods_price'];
                    if($vv['goods_num'] * $vv['goods_price']>0){
                        $num++;
                    }
                }
                $bili = $red_info['redinfo_money'] / $zong;

                $i = 0;
                $hua = $red_info['redinfo_money'];

                foreach ($store_cart_list[$red_info['red_vid']] as $kk => $vv) {
                    if ($i != ($num - 1)) {
                        $cha = round($bili * $vv['goods_num'] * $vv['goods_price'], 2);
                        $hua -= $cha;
                    } else {
                        $cha = $hua;
                    }
                    $store_cart_list[$red_info['red_vid']][$kk]['goods_total'] -= $cha;
                    if($store_cart_list[$red_info['red_vid']][$kk]['goods_total']<$vv['goods_freight']){
                        $store_cart_list[$red_info['red_vid']][$kk]['goods_total'] = $vv['goods_freight'];
                    }
                    $store_final_order_total[$vv['vid']] -= $hua ;
                    if($store_final_order_total[$vv['vid']]<$vv['goods_freight']){
                        $store_final_order_total[$vv['vid']] =  $vv['goods_freight'];
                    }
                    $i++;
                }
                $reds[$red_info['red_vid']] = $wqv;
                $where = array();
                $where['id'] = $wqv;
                $where['reduser_uid'] = $member_id;
                $this->table('red_user')->where($where)->update(array('reduser_use' => TIMESTAMP));
                $red_info = $this->table('red_user')->where($where)->find();
                $this->table('red')->where(array('id' => $red_info['red_id']))->update(array('red_hasuse'=>array('exp','red_hasuse+1')));

            }

        }

        //平台优惠券
        if($red_user_id['red']) {
            $where = array();
            $where['reduser_use'] = array('eq', 0);
            $where['redinfo_end'] = array('gt', TIMESTAMP);
            $where['redinfo_start'] = array('lt', TIMESTAMP);
            $where['reduser_uid'] = $member_id;
            $where['red_user.id'] = $red_user_id['red'];
            $red_list = $this->getRedUserList($where);
            if (empty($red_list)) {
                return array('error' => '您的平台优惠券已失效，不能使用');
            }
            $red_list = $this->filter_red($store_cart_list, $red_list, false);
            if (empty($red_list)) {
                return array('error' => '验证优惠券错误，不能使用');
            }
            $red_info = $red_list[0];

            $zong = 0;
            $num = 0;
            foreach ($store_cart_list as $v) {
                foreach ($v as $vv) {
                    $zong += $vv['goods_num'] * $vv['goods_price'];
                    $num++;
                }
            }
            $bili = $red_info['redinfo_money'] / $zong;

            $i = 0;
            $hua = $red_info['redinfo_money'];
            foreach ($store_cart_list as $k => $v) {
                foreach ($v as $kk => $vv) {
                    if ($i != ($num - 1)) {
                        $cha = round($bili * $vv['goods_num'] * $vv['goods_price'], 2);
                        $hua -= $cha;
                    } else {
                        $cha = $hua;
                    }
                    $store_cart_list[$k][$kk]['goods_total'] -= $cha;
                    if($store_cart_list[$k][$kk]['goods_total'] < $vv['goods_freight']){
                        $store_cart_list[$k][$kk]['goods_total'] = $vv['goods_freight'];
                    }
                    $store_final_order_total[$vv['vid']] -= $cha;
                    if($store_final_order_total[$vv['vid']]<$vv['goods_freight']){
                        $store_final_order_total[$vv['vid']] = $vv['goods_freight'];
                    }
                    $i++;
                }
            }

            $where = array();
            $where['id'] = $red_user_id['red'];
            $where['reduser_uid'] = $member_id;
            $this->table('red_user')->where($where)->update(array('reduser_use' => TIMESTAMP));
            $red_info = $this->table('red_user')->where($where)->find();
            $this->table('red')->where(array('id' => $red_info['red_id']))->update(array('red_hasuse'=>array('exp','red_hasuse+1')));
        }

        return array(0=>$store_cart_list,1=>$store_final_order_total,2=>$reds);
    }

    /**
     * 根据商品列表和红包列表 获取满足的红包
     * @param array $store_cart_list 店铺商品数组
     * @param array $red_list 红包列表
     * @return array
     *
     */
    function filter_red($store_cart_list,$red_list,$info=true)
    {
        //获得自营店铺id
        $vendor =new \app\v1\model\VendorInfo();
        $own_ids = $vendor->getOwnShopIds();

        //初始 多个总价
        $all_total = 0;
        $own_total = 0;
        //分类总价数组
        $cat_total_arr = [];
        //产品总价数组
        $goods_total_arr = [];
        //是否参与优惠标识
        $has_discount = 0;
        foreach ($store_cart_list as $k => $goods_list) {
            foreach ($goods_list as $v) {
                //总价累计
                $zong = $v['goods_num'] * $v['goods_price'];
                $all_total += $zong;
                //自营店累计
                if (in_array($v['vid'], $own_ids)) {
                    $own_total += $zong;
                }

                //分类总价
                if(!empty($v['gc_id_1'])){
                    $cat_total_arr[$v['gc_id_1']] = $zong;
                }
                else if (!empty($v['gc_id_1']) && !isset($cat_total_arr[$v['gc_id_1']]) ) {
                    $cat_total_arr[$v['gc_id_1']] = $zong;
                } else {
                    if(!empty($v['gc_id_1'])){
                        $cat_total_arr[$v['gc_id_1']] += $zong;
                    }
                }
                //商品总价
                if (!isset($goods_total_arr[$v['gid']])) {
                    $goods_total_arr[$v['gid']] = $zong;
                } else {
                    $goods_total_arr[$v['gid']] += $zong;
                }
                //判断是否参与其他活动
                if (isset($v['promotion_type']) && !empty($v['promotion_type'])) {
                    $has_discount = 1;
                }
            }
        }

        //排除优惠券
        foreach ($red_list as $k => $v) {

            if ($v['redinfo_money'] == 0) {
                unset($red_list[$k]);
                continue;
            }

            //有商品存在优惠了
            if ($v['redinfo_together'] == 0 && $has_discount == 1) {
                unset($red_list[$k]);
                continue;
            }
            //判断只能自营使用
            if ($v['redinfo_self'] == 1) {
                //判断自营总价
                if ($v['redinfo_full'] > $own_total) {
                    unset($red_list[$k]);
                    continue;
                }
            }

            //判断总价
            if ($v['redinfo_full'] > $all_total) {
                unset($red_list[$k]);
                continue;
            }

            //判断使用范围
            if ($v['redinfo_ids']) {
                //如果使用范围有分类

                $gou = 0;

                if ($v['redinfo_type'] == 1) {
                    //分类总价
                    foreach (explode(',', $v['redinfo_ids']) as $vv) {
                        //分类总价不够
                        if ($v['redinfo_full'] <= floatval($cat_total_arr[$vv])) {
                            $gou++;
                        }
                    }

                } elseif ($v['redinfo_type'] == 2 && $v['redinfo_ids']) {  //如果使用范围是产品

                    //产品总价
                    foreach (explode(',', $v['redinfo_ids']) as $vv) {
                        //产品总价不够
                        if ($v['redinfo_full'] <= floatval($goods_total_arr[$vv]) && $goods_total_arr[$vv]) {
                            $gou++;
                        }
                    }

                }


                if($gou<1){
                        unset($red_list[$k]);
                }
            }

        }


        if ($info) {
            $red_list = $this->getUseInfo($red_list);
        }
        return $red_list;
    }

    /** 获取 优惠券说明文字 */
    function getUseInfo($red_list)
    {
        //处理所得到的优惠券
        $cat_ids = [];
        $goods_ids = [];
        $vendor_ids = [];
        foreach ($red_list as $k => $v) {
            $red_list[$k]['red_type_text'] = $this->redtype[$v['red_type']];
            $red_list[$k]['red_status_text'] = $this->redstatus[$v['red_status']];
            $red_list[$k]['redinfo_start_text'] = date('Y.m.d', $v['redinfo_start']);
            $red_list[$k]['redinfo_end_text'] = date('Y.m.d', $v['redinfo_end']);
            $red_list[$k]['redinfo_money'] = floatval($v['redinfo_money']);
            $red_list[$k]['redinfo_full'] = floatval($v['redinfo_full']);
            //把所有产品id，分类id存成数组
            if ($v['redinfo_ids']) {
                if ($v['redinfo_type'] == 1) {
                    $cat_ids = $cat_ids + explode(',', $v['redinfo_ids']);
                } elseif ($v['redinfo_type'] == 2) {

                    foreach (explode(',', $v['redinfo_ids']) as $vv){
                        if(!in_array($vv,$goods_ids) && $vv){
                            $goods_ids[] =  $vv;
                        }
                    }
                }
            }
            //店铺id存一下
            if ($v['red_vid'] && !in_array($v['red_vid'], $vendor_ids)) {
                $vendor_ids[] = $v['red_vid'];
            }
            //超时 直接设置成不能使用
            //if ($_GET['red_status'] == 'expired') {
                $red_list[$k]['reduser_use'] = 1;
            //}
        }

        //查所用到的分类
        $all_cat = DB::table('bbc_goods_class')->where(['gc_id' => ['in', join(',', $cat_ids)]])->select();

        //查所用到的分类
        $all_goods = DB::table('bbc_goods')->where(['gid' => ['in', join(',', $goods_ids)]])->select();

        //查用到的店铺
        $all_vendor = DB::table('bbc_vendor')->where(['vid' => ['in', join(',', $vendor_ids)]])->select();

        //再循环一遍。。。
        foreach ($red_list as $k => $v) {
            $str = [];
            //店铺名称
            if ($v['red_vid']) {
                //$str[] = '“' . $all_vendor[$v['red_vid']]['store_name'] . '”'.Language::get('店铺使用');
            }
            //根据类型转换文字
            if ($v['redinfo_ids']) {
                $tmp = [];
                if ($v['redinfo_type'] == 1) {
                    foreach (explode(',', $v['redinfo_ids']) as $vv) {
                        $tmp[] = $all_cat[$vv]['gc_name'];
                    }
                    //$str[] = Language::get('仅限') . join('、', $tmp) . Language::get('类商品');
                } elseif ($v['redinfo_type'] == 2) {
                    $iii = 0;
                    foreach (explode(',', $v['redinfo_ids']) as $vv) {
                        if ($iii < 3 && $vv) {
                            $tmp[] = $all_goods[$vv]['goods_name'];
                        }
                        $iii++;
                    }
                    //$str[] = Language::get('仅限') . join('、', $tmp);
                }
            }
            //只限自营店
            if ($v['redinfo_self'] == 1) {
                //$str[] = Language::get('仅限自营店铺');
            }
            //不能与店铺红包叠加
            if ($v['redinfo_store'] === '0') {
                //$str[] = Language::get('不能与店铺优惠券共用');
            }
            //原价购买
            if (!$v['red_vid'] && $v['redinfo_store'] === '0') {
                //$str[] = Language::get('不参与其他优惠活动');
            }
            if (count($str) < 1) {
                //$red_list[$k]['str'] = Language::get('任意商品可使用');
            } else {
                //$red_list[$k]['str'] = join('，', $str);
            }
        }

        return $red_list;
    }

    /**
     * 读取优惠券发放列表
     * @param array $get 条件数组
     * @return array
     *
     */
    public function getRedCondition($get) {
        if(APP_ID=='vendor'){
            $condition['red.red_vid'] = $_SESSION['vid']?$_SESSION['vid']:0;
        }
        if (isset($get['red_status']) && $get['red_status']!=='') { //活动状态不筛选
            $condition['red_status'] = $get['red_status'];
        }
        if ($get['red_title']) {  //活动名称筛选
            $condition['red_title'] = array('like', '%' . $get['red_title'] . '%');
        }
        if ($get['red_type']) {  //类型筛选
            $condition['red_type'] = $get['red_type'];
        }

        //（S2 <= E1）AND (S1 <= E2）
        $start = $get['redinfo_start'] ? $get['redinfo_start'] : 0;
        $end = $get['redinfo_end'] ? $get['redinfo_end'] : 0;

        if($start && $end){
            $condition['redinfo_start'] = array('exp',' ( '.strtotime($start). '<= redinfo_end ) and ( redinfo_start <='.(strtotime($end)+86399).' )');
        }else{
            if ($start) {  //时间有效期筛选开始
                $condition['redinfo_end'] = array('exp',  'redinfo_start >'. strtotime($start).' or redinfo_end>'.strtotime($start) );
            }
            if ($end) {
                $condition['redinfo_start'] = array('exp',  'redinfo_start <'. (strtotime($end)+86399).' or redinfo_end<'.(strtotime($end)+86399) );
            }
        }
        return $condition;
    }
    /*
    * 更新
    * @param array $update
    * @param array $condition
    * @return bool
    *
    */
    public function editRed($update, $condition){
        return $this->table('red')->where($condition)->update($update);
    }
    //查询用户优惠券的详细信息
    public function getUserRed($condition,$field="*"){
        return $this->table('red_user')->field($field)->where($condition)->find();
    }
    //修改用户优惠券的信息
    public function editUserRed($condition,$update){
        return $this->table('red_user')->where($condition)->update($update);
    }
    //
}