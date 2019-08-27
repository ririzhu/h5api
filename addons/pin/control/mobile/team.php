<?php
/**
 * 队伍表
 *
 */


defined('DYMall') or exit('Access Invalid!');
class teamCtl extends mobileHomeCtl{

    public function __construct() {
        parent::__construct();
    }

    /**
     * 列表页
     *
     */
    public function data() {
        $key=$_POST['key'];
        if (!empty($key)) {
            $member_info = array();
            $model_mb_user_token = Model('mb_user_token');
            //判断是否收藏
            $member_info = $model_mb_user_token->getMbUserTokenInfoByToken($key);
        }else{
            return array('error' => '请重新登陆');
        }


        $rows=10;
        $leader = $_GET['tid']?intval($_GET['tid']):0;
        if($leader){
            $where['sld_leader_id'] = $member_info['member_id'];
        }else{
            $where['sld_leader_id'] = array('neq',$member_info['member_id']);
        }
        $where['sld_user_id'] = $member_info['member_id'];
        $md = M('pin');
        $team=$md->table('pin_team_user,pin_team,goods,pin_goods,pin')
            ->field('*,(select count(*) from bbc_order where pin_id = pin_team.sld_pin_id and bbc_order.order_state>2) as sales')
            ->join('left')
            ->on('pin_team_user.sld_team_id=pin_team.id,pin_team_user.sld_gid=goods.gid, pin_team_user.sld_gid=pin_goods.sld_gid, pin_team.sld_pin_id=pin.id')
            ->where($where)
            ->order('pin_team_user.sld_add_time desc')
            ->group('pin_team_user.sld_order_id')
            ->page($rows)
            ->select();
        $page_count = $md->gettotalpage();

        foreach ($team as $k=>$v){
            $team[$k]['sheng'] = $v['goods_price']- $v['sld_pin_price'];
            $team[$k]['sld_pin_price'] = floatval($v['sld_pin_price']);
            $team[$k]['sld_pic'] = thumb($v);
        }


        output_data(array('goods' => $team), mobile_page($page_count));
    }

    /*更多*/
    public function more(){
        $rows=10;
        $pin_id = $_GET['pin_id']?intval($_GET['pin_id']):0;
        if($pin_id){
            $where['pin.id'] = array('neq',$pin_id);
        }

        $where['pin.sld_start_time'] = array('lt',TIMESTAMP);
        $where['pin.sld_end_time'] = array('gt',TIMESTAMP);
        $where['pin.sld_status'] = 1;
        $where['pin_goods.sld_pin_price'] = array('gt',0);
        $md=M('pin');
        $goods=$md->table('pin,goods,pin_goods,pin_team')
            ->field('pin.*,goods.gid,goods.goods_name,goods.goods_price,goods.goods_image,goods.vid,pin_goods.sld_pin_price,pin_team.id as team_id,(select count(*) from bbc_order where pin_id = pin.id and bbc_order.order_state>2) as sales')
            ->join('left')
            ->on('pin.sld_goods_id=goods.goods_commonid,pin.id=pin_goods.sld_pin_id, pin.id=pin_team.sld_pin_id')
            ->where($where)
            ->group('pin.id')
            ->page($rows)
            ->select();
        $page_count = $md->gettotalpage();

        foreach ($goods as $k=>$v){
            $goods[$k]['sheng'] = $v['goods_price']- $v['sld_pin_price'];
            $goods[$k]['sld_pin_price'] = floatval($v['sld_pin_price']);
            $goods[$k]['sld_pic'] = thumb($v);
        }

        output_data(array('goods' => $goods), mobile_page($page_count));
    }

    /**
     * 详情页
     *
     */
    public function detail() {
        $key=$_POST['key'];
        if (!empty($key)) {
            $member_info = array();
            $model_mb_user_token = Model('mb_user_token');
            //判断是否收藏
            $member_info = $model_mb_user_token->getMbUserTokenInfoByToken($key);
        }
        $team_id = $_GET['team_id']?intval($_GET['team_id']):0;
        if($team_id){
            $where['pin_team.id'] = $team_id;
        }else{
            return array('error' => '参数错误');
        }
        $md = M('pin');
        $pin_info=M('pin')->table('pin_team_user,pin_team,goods,pin_goods,pin,order')
            ->field('*,(select count(*) from bbc_order where pin_id = pin_team.sld_pin_id and bbc_order.order_state>2) as sales')
            ->join('left')
            ->on('pin_team_user.sld_team_id=pin_team.id,pin_team_user.sld_gid=goods.gid, pin_team_user.sld_gid=pin_goods.sld_gid, pin_team.sld_pin_id=pin.id,pin_team_user.sld_order_id=order.order_id')
            ->where($where)
            ->find();
        $pin_info['sheng'] = $pin_info['goods_price']- $pin_info['sld_pin_price'];
        $end_time = $pin_info['sld_add_time'] + $pin_info['sld_success_time'] * 3600 ;
        if($end_time>$pin_info['sld_end_time']){
            $end_time = $pin_info['sld_end_time'];
        }
        if($end_time<=TIMESTAMP){
            if($pin_info['sld_tuan_status']<1){
                $re = M('pin')->team_timeout($team_id);
                if($re){
                    $pin_info['sld_tuan_status'] = 2;
                }
            }
        }

        $pin_info['sld_end_datetime'] = date('Y/m/d H:i:s',$end_time);

        $pin_info['sld_pic'] = cthumb($pin_info['goods_image'], 350, $pin_info['vid']);
        $pin_info['sld_pin_price'] = floatval($pin_info['sld_pin_price']);
        $where=array();
        $where['sld_team_id'] = $team_id;
        $where['order_state'] = array('gt',2);
        $list=$md->table('pin_team_user,member,order')
            ->field('pin_team_user.*,member.member_avatar,order.order_state')
            ->join('left')
            ->on('pin_team_user.sld_user_id=member.member_id,pin_team_user.sld_order_id=order.order_id')
            ->where($where)
            ->order('sld_add_time asc')
            ->select();

        $pin_info['sld_team_count2']  =  count($list);



        $pin_info['cha'] = $pin_info['sld_team_count'] - count($list);
        $pin_info['has_join'] = 0;


        for ($x=0; $x<count($list); $x++) {
            if($member_info&&$member_info['member_id']==$list[$x]['sld_user_id'] && $pin_info['has_join']==0){
                $pin_info['has_join'] = 1;
            }
            if($list[$x]['sld_user_id']==$pin_info['sld_leader_id']){
                $list[$x]['leader'] = 1;
            }
            if($list[$x]){
                $list[$x]['avatar'] = $list[$x]['member_avatar'] ? getMemberAvatar($list[$x]['member_avatar']) : getCimg('default_user_portrait');
            }else{
                if($pin_info['sld_tuan_status']!=1) {
                    $list[$x]['avatar'] = '../addons/pin/data/img/def.jpg';
                }else{
                    $list[$x]['avatar'] = '../addons/pin/data/img/pin_icon.png';
                }
            }
            $new_list[] = $list[$x];
        }

        output_data(array('info' => $pin_info,'list'=>$new_list));
    }



    /**
     * 首页(用于微信小程序)
     */
    public function index_xcx() {
        $model_mb_special = Model('cwap_home');
        $model_goods = Model('goods');
        //获取首页数据
        $data = $model_mb_special->getCwapHomeInfo();
        $data =unserialize($data['home_data']);
        //对数据重新排序
        $data_new = array();
//        print_r($data);die;
        $new_data = array();
        foreach ($data as $k => $v){
            if(isset($v['data']) && !empty($v['data'])){
                foreach ($v['data'] as $i_k => $i_v) {
                    if(isset($i_v['img'])){
                        $i_v['img'] = (strpos($i_v['img'],'http') !==false) ? $i_v['img'] : getMbSpecialImageUrl($i_v['img']);
                        $v['data'][$i_k] = $i_v;
                    }
                }
            }
            if($v['type'] == 'fuwenben'){
                $v['text'] = htmlspecialchars_decode($v['text']);
                $data_new[] = $v;
            }else if($v['type'] == 'tuijianshangpin') {
                //推荐商品模块如果没有添加商品就保存的话就直接不显示了
                if (!empty($v['data']['gid']) && is_array($v['data']['gid'])) {
                    foreach ($v['data']['gid'] as $key => $val) {
                        $goods_info = $model_goods->getGoodsOnlineInfoByID($val, 'gid,goods_name,goods_promotion_price,goods_price,goods_image');
                        if (!empty($goods_info)) {
                            $goods_info['goods_image'] = thumb($goods_info, 310);
                            $v['data']['goods_info'][] = $goods_info;
                        }
                    }

                    // 获取最终价格
                    $v['data']['goods_info'] = Model('goods_activity')->rebuild_goods_data($v['data']['goods_info']);

                    $data_new[] = $v;
                }
            }else if($v['type'] == 'dapei') {
                //推荐商品模块如果没有添加商品就保存的话就直接不显示了
                if (!empty($v['data']['gid']) && is_array($v['data']['gid'])) {
                    foreach ($v['data']['gid'] as $key => $val) {
                        $goods_info = $model_goods->getGoodsOnlineInfoByID($val, 'gid,goods_name,goods_promotion_price,goods_price,goods_image');
                        if (!empty($goods_info)) {
                            $goods_info['goods_image'] = thumb($goods_info, 310);
                            $v['data']['goods_info'][] = $goods_info;
                        }
                    }
                
                    // 获取最终价格
                    $v['data']['goods_info'] = Model('goods_activity')->rebuild_goods_data($v['data']['goods_info']);

                    $data_new[] = $v;
                }
            }else if($v['type'] == 'fzkb'){
//                $v['text'] = round($v['text']/23,2);//把像素转化为rem 用于做适配
                $data_new[] = $v;
            }else if($v['type'] == 'lunbo'){
                $lunbo_data = array();
                foreach ($v['data'] as $lb_k => $lb_v){
                    $lunbo_data[] = $lb_v;
                }
                $v['data'] = $lunbo_data;
                $data_new[] = $v;
            }else{
                $data_new[] = $v;
            }

        }
        $this->_output_special($data_new, $_GET['type']);
    }
    /**
     * 获取首页title和搜索栏颜色
     */
    public function index_title() {
        $model_mb_special = Model('cwap_home');
        //获取首页数据
        $data = $model_mb_special->getCwapHomeInfo();
        //对数据重新排序
        $data_new = array();
        $data_new['title'] = $data['home_desc'];
        $data_new['sousuo_color'] = $data['home_sousuo_color'];
//        print_r($data);die;
        $this->_output_special($data_new, $_GET['type']);
    }

    /**
     * 获取专题页标题
     */
    public function topic_title() {
        $model_mb_special = Model('cwap_topic');
        //获取专题页数据
        $data = $model_mb_special->getTopicInfoByTopicID($_GET['topic_id']);

        //对数据重新排序
        $data_new = array();
        $data_new['title'] = $data['topic_desc'];
        $this->_output_special($data_new, $_GET['type']);
    }

    //获取首页分页的商品数据
    public function getGoodsByPage(){
        $page=$_GET['page'];
        $model_mb_special = Model('mb_special');
        $rows=$_GET['rows'];
        $total=$page*$rows;
        $totals=($page+1)*$rows;
        $data = $model_mb_special->getMbSpecialItemUsableListByID($_GET['special_id']);
        foreach($data as $k=>$v){
            if($k==4){
                $length=count($v['goods']['item']);
                $arr=array();
                if($totals<=$length){
                    //从截取数组长度
                    for($i=$total;$i<$totals;$i++){
                        array_push($arr,$v['goods']['item'][$i]);
                    }
                    $hasmore=1;
                }
                if($total>$length){
                    $hasmore=0;
                }
                if($total<$length && $length<=$totals){
                    $arr=array();
                    for($i=$total;$i<$length;$i++){
                        array_push($arr,$v['goods']['item'][$i]);
                    }
                    $hasmore=0;
                }
            }
        }
        output_data(array('goods_info'=>array_values($arr),'hasmore'=>$hasmore));
    }
    /**
     * 默认搜索词列表
     */
    public function search_key_list() {
        //热门搜索
        $list = @explode(',',C('hot_search'));
        if (!$list || !is_array($list)) {
            $list = array();
        }

        //历史搜索
        if (cookie('his_sh') != '') {
            $his_search_list = explode('~', cookie('his_sh'));
        }

        $data['list'] = $list;
        $data['his_list'] = is_array($his_search_list) ? $his_search_list : array();
        output_data($data);
    }
    /**
     * 默认搜索词列表(用于微信小程序)
     */
    public function search_key_list_xcx() {
        //热门搜索
        $list = @explode(',',C('hot_search'));
        if (!$list || !is_array($list)) {
            $list = array();
        }
        echo json_encode(array('hot_list'=>$list));
    }



    /**
     * 高级搜索
     */
    public function search_adv() {
        $area_list = Model('area')->getAreaList(array('area_deep'=>1),'area_id,area_name');
        if (C('contract_allow') == 1) {
            $contract_list = Model('contract')->getContractItemByCache();
            $_tmp = array();$i = 0;
            foreach ($contract_list as $k => $v) {
                $_tmp[$i]['id'] = $v['cti_id'];
                $_tmp[$i]['name'] = $v['cti_name'];
                $i++;
            }
        }
        output_data(array('area_list'=>$area_list ? $area_list : array(),'contract_list'=>$_tmp));
    }
}
