<?php
namespace app\v1\model;

use think\Db;
use think\Exception;
use think\Model;

class Red extends Model
{
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
    public function getRedList($condition, $page = null, $order = 'bbc_red.id desc', $field = 'bbc_red.*,bbc_red_info.redinfo_money,bbc_red_info.redinfo_start,bbc_red_info.redinfo_end,bbc_red_info.redinfo_type,bbc_red_info.redinfo_ids,bbc_red_info.redinfo_self,bbc_red_info.redinfo_store,bbc_red_info.redinfo_full,bbc_red_info.redinfo_create,bbc_red_info.redinfo_together', $limit = 0) {
        $field.=',min(redinfo_money) min_money,
        max(redinfo_money) max_money,
        min(redinfo_start) as min_date,
        max(redinfo_end) as max_date';
        $condition['red_delete'] = 0;
        $red_list = DB::name('red_info')->join('red','bbc_red.id=bbc_red_info.red_id')->field($field)->where($condition)
            ->group('bbc_red_info.red_id')
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
        if(isset($where['red_receive_start'])){
            unset($where["red_receive_start"]);
            unset($where["red_receive_end"]);
            $red = DB::name('red')->where($where)->where("red_receive_start<".TIMESTAMP)->where("red_receive_end >".TIMESTAMP)->find();
        }else {
            $red = DB::name('red')->where($where)->find();
        }
        $redinfo = DB::name('red_info')->where(array('red_id'=>$red['id']))->select();
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
                $data['reduser_proof']= $orderSn;
                $data['reduser_uid']=$v;
                $data['red_id'] = $par['id'];
                $data['redinfo_id']=$vv['id'];
                $data['reduser_get']=TIMESTAMP;
                $data['redinfo_start']=$vv['redinfo_start'];
                $data['redinfo_end']=$vv['redinfo_end'];
            }
        }
        //$sql = substr($sql,0,strlen($sql)-1);
        $geshu = DB::name("red_user")->insert($data);
        if($geshu){
            DB::name('red')->where(array('id'=>$par['id']))->update(array('red_hasget'=>array('inc',$geshu)));
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
        $member_list = DB::table('bbc_member')->field('member_id,member_name,member_truename,wx_nickname')->where($where)->force("member_id")->select();//column("member_id,member_id,member_name,member_truename,wx_nickname","member_id");
        $all_list = array();
        if($to=="list"){
            foreach($red_user_list as $k=>$v){
                $all_list[$k] = DB::table('bbc_red_info')->where(" red_id = ".$v['red_id'])->force("id")->find();
                $all_list[$k]['red_type']=$red_user_list[$k]['red_type'];
                $all_list[$k]['red_status']=$red_user_list[$k]['red_status'];
            }
            return $all_list;
        }
        //优惠券信息数据
        $redinfo_ids = low_array_column($red_user_list,'redinfo_id');
        $where2['id'] = array('in',arrayToString($redinfo_ids));
        $redinfo_list = DB::table('bbc_red_info')->where(" id in (".arrayToString(array_unique($redinfo_ids)).")")->force("id")->select();
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
    public function getRedLingList($member_id='',$condition, $page = null, $order = 'bbc_red_info.id desc') {

        if(isset($_GET['vid'])){
            $order = 'bbc_red.red_vid='.$_GET['vid'].' desc,'.$order;
        }

        //优惠券信息、优惠券数据
        $red_list = DB::table('bbc_red_info')->join('bbc_red','bbc_red_info.red_id=bbc_red.id')->
        field('bbc_red_info.*,red_limit,bbc_red.id as red_id,bbc_red.red_title,bbc_red.red_type,red_status,bbc_red.red_limit,bbc_red.red_hasget,bbc_red.red_rach_max,bbc_red.red_receive_end,bbc_red.red_receive_start')->where($condition)->where("red_receive_start<".TIMESTAMP)->where("red_receive_end >".TIMESTAMP)->page($page)->order($order)->select();
        //print_r($red_list);die;
        $red_ids = low_array_column($red_list,'id');
        //读取用户优惠券数据
        if($member_id){
            $where['reduser_uid'] = $member_id;
        }
        $where['bbc_red_user.redinfo_id'] = array('in',arrayToString($red_ids));
        $use_red_ids = DB::table('bbc_red_user')->where($where)->field('redinfo_id,reduser_use,count(*) as num')->group("redinfo_id")->force('redinfo_id')->select();
        //$use_red_ids = DB::table('bbc_red_user')->where($where)->field('redinfo_id,reduser_use,count(*) as num')->group('redinfo_id')->cache('redinfo_id')->select();

        //print_r($use_red_ids);die;
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
            //print_r($use_red_ids);die;
            //判断用户是否已领
            if(isset($use_red_ids[$v['id']]) && $member_id && $use_red_ids[$v['id']]['num']>=$v['red_rach_max']){
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
    function lingRed($member_id,$red_id){


        if(!$member_id){
            return '请先登陆，再领券';
        }

        $condition['red_status'] = 1;
        $condition['red_info.id'] = $red_id;
        $condition['red_front_show'] = 1;
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
                    return true;
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
                $where['bbc_red_user.id'] = $wqv;
                $where=" reduser_use = 0 and redinfo_end > ".TIMESTAMP." and redinfo_start <".TIMESTAMP." and bbc_red_user.reduser_uid=".$member_id." and bbc_red_user.id=".$wqv;
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
                if($zong == 0)
                    $bili =0;
                else
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
                    if(isset($vv['goods_freight']) && $store_cart_list[$red_info['red_vid']][$kk]['goods_total']<$vv['goods_freight']){
                        $store_cart_list[$red_info['red_vid']][$kk]['goods_total'] = $vv['goods_freight'];
                    }
                    $store_final_order_total[$vv['vid']] -= $hua ;
                    if(isset($vv['goods_freight']) && $store_final_order_total[$vv['vid']]<$vv['goods_freight']){
                        $store_final_order_total[$vv['vid']] =  $vv['goods_freight'];
                    }
                    $i++;
                }
                $reds[$red_info['red_vid']] = $wqv;
                $where = array();
                $where['id'] = $wqv;
                $where['reduser_uid'] = $member_id;
                $this->table('bbc_red_user')->where($where)->update(array('reduser_use' => TIMESTAMP));
                $red_info = db::table('bbc_red_user')->where($where)->find();
                DB::table('bbc_red')->where(array('id' => $red_info['red_id']))->update(array('red_hasuse'=>array('inc','red_hasuse+1')));

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
        if(!empty($store_cart_list))
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

    public static function confirm($buy_list){  //获取用户可使用平台优惠券

        $member_info = $buy_list['member'];

        $goods_list = array();
        foreach ($buy_list['store_cart_list'] as $k=>$v){
            $goods_list[$k] = $v;
        }

        //获得可用优惠券
        $condition['reduser_use'] = array( 'eq',0);
        $condition['redinfo_end'] = array( 'gt',TIMESTAMP);
        $condition['redinfo_start'] = array( 'lt',TIMESTAMP);
        $condition['reduser_uid'] = $member_info['member_id'];
        $conditions = "reduser_use = 0 and redinfo_end >".TIMESTAMP." and redinfo_start <".TIMESTAMP ." and reduser_uid = ".$member_info['member_id'];
//        $condition['red.vid'] = 0;
        $red_list = getRedUserList($conditions);
        $red_list = filter_red($goods_list,$red_list);

        $vendor_red_list = [];
        //循环优惠券把店铺优惠券排除
        foreach ($red_list as $k=>$v){
            if($v['red_vid'] && $v['red_vid']!=0){
                $vendor_red_list[$v['red_vid']][] = $v;
                unset($red_list[$k]);
            }
        }


        $newlist['red'] = $red_list;
        $newlist['vred'] = $vendor_red_list;

        return $newlist;
    }
    /**
     * 列表页
     *
     */
    public function red_list()
    {

        $model_red = M('red');


        if (isset($_GET['red_status']) && $_GET['red_status']!=='') { //使用状态筛选
            if($_GET['red_status']=='used'){  //使用过
                $condition['reduser_use'] = array( 'neq',0);
            }elseif($_GET['red_status']=='not_used'){  //未使用
                $condition['reduser_use'] = array( 'eq',0);
                $condition['redinfo_end'] = array( 'gt',TIMESTAMP);
            }elseif($_GET['red_status']=='expired'){  //过期
                $condition['redinfo_end'] = array( 'lt',TIMESTAMP);
            }
        }else{
            $condition['reduser_use'] = array( 'eq',0);
            $condition['redinfo_end'] = array( 'gt',TIMESTAMP);
        }
        $condition['reduser_uid'] = $_SESSION['member_id'];

        $red_list = $model_red->getRedUserList($condition,8);

        $red_list = $model_red->getUseInfo($red_list);

        $page_count = $model_red->gettotalpage();
        Template::output('list', $red_list);
        Template::output('show_page',$model_red->showpage(2)) ;

        $this->profile_menu('red_list');
        Template::output('menu_sign','myred');
        Template::output('menu_sign_url','index.php?app=red_list&sld_addons=red');
        Template::output('menu_sign1','member_red');
        Template::showpage('red.list');

    }

    //优惠券使用
    public function use_reds(){
        $redinfo_id = $_GET['redinfo_id'];
        $model_red = M('red');
        $red_info = $model_red->getRedList(array('red_info.id'=>$redinfo_id));
        $gids = 0;
        $gc_ids = 0;
        if($red_info[0]['redinfo_type'] == 2){//是商品的时候
            $gids = $red_info[0]['redinfo_ids']?$red_info[0]['redinfo_ids']:0;
            $gc_ids = 0;
        }else if($red_info[0]['redinfo_type'] == 1){//是分类
            $gc_ids = $red_info[0]['redinfo_ids']?$red_info[0]['redinfo_ids']:0;
            $gids = 0;
        }
        $store_self = $red_info[0]['redinfo_self']?$red_info[0]['redinfo_self']:0;
        $red_vid = $red_info[0]['red_vid']?$red_info[0]['red_vid']:0;
        exit(json_encode(array('red_ids'=>$gids,'red_vid'=>$red_vid,'red_gc_id'=>$gc_ids,'store_self'=>$store_self)));
    }
    //优惠券转赠
    public function give_red(){
        Template::output('user_red',$_GET['user_red']);
        Template::showpage('give.red','null_layout');
    }
    //转赠
    public function edit_red_user(){
        $give_member_name = $_POST['give_member_name'];
        $user_red = $_POST['user_red'];
        $model_red = M('red');
        $model_member = Model('member');

        //查询用户领取的用户卷信息 red_user
        $user_red_info = $model_red->getUserRed(array('id'=>$user_red));
        if(!$user_red_info){
            exit(json_encode(array('state'=>255,'msg'=>'优惠券不存在')));
        }
        $red_id = $user_red_info['red_id'];

        //查询被转赠人的用户信息
        $member_info = $model_member->getMemberInfo(array('member_name'=>$give_member_name));
        if(!$member_info){
            exit(json_encode(array('state'=>255,'msg'=>'用户不存在')));
        }

        //查询被赠送人该优惠券的拥有数量
        $member_red = $model_red->getRedUserList(array('red_user.red_id'=>$red_id,'red_user.reduser_uid'=>$member_info['member_id']));
        //该用户已有该优惠券的数量
        $num = 0;
        if(!$member_red){
            $num = 0;
        }else{
            foreach ($member_red as $v){
                $num += 1;
            }
        }
        //查询该优惠券的信息
        $red_info = $model_red->getRedInfo(array('id'=>$red_id));
        //判断优惠券每人限领
        if($num >= $red_info['red_rach_max']){
            exit(json_encode(array('state'=>255,'msg'=>'该用户拥有优惠券已达最大限额')));
        }

        //修改领取优惠券表中的用户id
        $edit = $model_red->editUserRed(array('id'=>$user_red),array('reduser_uid'=>$member_info['member_id']));
        if($edit){
            exit(json_encode(array('state'=>200,'msg'=>'转赠成功')));
        }else{
            exit(json_encode(array('state'=>255,'msg'=>'转赠失败')));
        }
    }

    /**
     * 用户中心右边，小导航
     *
     * @param string	$menu_type	导航类型
     * @param string 	$menu_key	当前导航的menu_key
     * @param array 	$array		附加菜单
     * @return
     */
    private function profile_menu($menu_key='') {
        $menu_array = array(
            1=>array('menu_key'=>'red_list','menu_name'=>'我的优惠券','menu_url'=>'index.php?app=red_list&sld_addons=red'),
        );
        Template::output('member_menu',$menu_array);
        Template::output('menu_key',$menu_key);
    }
    /**
     * 领券中心页
     *
     */
    public function red_get_list()
    {

        $model_red = M('red');
        $condition['red.red_type'] = array('neq','3');
        $condition['red_status'] = 1;
        $condition['red_front_show'] = 1;
        $condition['red_receive_start'] = array('lt',TIMESTAMP);
        $condition['red_receive_end'] = array('gt',TIMESTAMP);
        if(isset($_GET['red_id'])){
            $condition['red.id'] = $_GET['red_id'];
        }


        $red_list = $model_red->getRedLingList($_SESSION['member_id'],$condition,$_GET['page']);

        $red_list = $model_red->getUseInfo($red_list);


        //Template::output('list', $red_list);
        //Template::output('show_page',$model_red->showpage(2)) ;

        //Template::showpage('red.get.list');

    }

    /**
     * 领取优惠券
     *
     */
    public function send_red()
    {

        $red_id = $_GET['red_id'];

        if(!$_SESSION['member_id']){
            exit('请先登录再领取！');
        }

        $msg = M('red')->ling_red($_SESSION['member_id'],$red_id);

        exit($msg);

    }

    /**
     * 加载买家发票列表，最多显示10条
     *
     */
    public function loadred() {
        $model_buy = Model('buy');

        $condition = array();
        if ($model_buy->buyDecrypt($_GET['vat_hash'], $_SESSION['member_id']) == 'allow_vat') {
        } else {
            Template::output('vat_deny',true);
            $condition['inv_state'] = 1;
        }
        $condition['member_id'] = $_SESSION['member_id'];

        $model_inv = Model('invoice');
        //如果传入ID，先删除再查询
        if (intval($_GET['del_id']) > 0) {
            $model_inv->delInv(array('inv_id'=>intval($_GET['del_id']),'member_id'=>$_SESSION['member_id']));
        }
        $list = $model_inv->getInvList($condition,10);
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                if ($value['inv_state'] == 1) {
                    $list[$key]['content'] = '普通发票'.' '.$value['inv_title'].' '.$value['inv_content'].' '.$value['inv_code'];
                } else {
                    $list[$key]['content'] = '增值税发票'.' '.$value['inv_company'].' '.$value['inv_code'].' '.$value['inv_reg_addr'];
                }
            }
        }
        Template::output('inv_list',$list);
        Template::showpage('buy_red.load','null_layout');
    }


}