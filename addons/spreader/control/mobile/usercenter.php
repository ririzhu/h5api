<?php
/**
 * WAP端个人中心
 *
 */
defined('DYMall') or exit('Access Invalid!');

class ssys_mobileMemberCtl extends mobileCtl
{

    protected $member_info = array();

    public function __construct()
    {
        parent::__construct();
        $agent = $_SERVER['HTTP_USER_AGENT'];
        if (strpos($agent, "MicroMessenger") && $_GET["app"] == 'auto') {
            $this->appId = C('app_weixin_appid');
            $this->appSecret = C('app_weixin_secret');;
        } else {
            $model_mb_user_token = M('ssys_mb_user_token','spreader');
            $key = $_REQUEST['ssys_key'];
            $mb_user_token_info = $model_mb_user_token->getMbUserTokenInfoByToken($key);
            if (empty($mb_user_token_info)) {
                output_error(Language::get('请登录'), array('login' => '0'));
            }

            $model_member = M('ssys_member');
            $this->member_info = $model_member->getMemberInfoByID($mb_user_token_info['member_id']);
            if(empty($this->member_info)) {
                output_error(Language::get('请登录'), array('login' => '0'));
            } else {
                $this->member_info['client_type'] = $mb_user_token_info['client_type'];
                $this->member_info['openid'] = $mb_user_token_info['openid'];
                $this->member_info['token'] = $mb_user_token_info['token'];
            }
        }
    }
    public function getOpenId()
    {
        return $this->member_info['openid'];
    }

    public function setOpenId($openId)
    {
        $this->member_info['openid'] = $openId;
        M('ssys_mb_user_token')->updateMemberOpenId($this->member_info['token'], $openId);
    }
}

class usercenterCtl extends ssys_mobileMemberCtl {

    public function __construct(){
        parent::__construct();
    }

    /**
     * 我的商城
     */
    public function index() {
        $member_info = array();
        $member_info['user_name'] = $this->member_info['member_name'];
        $member_info['avator'] = getMemberAvatarForID($this->member_info['member_id']);
        $member_info['point'] = $this->member_info['member_points'];
        $member_info['predepoit'] = $this->member_info['available_predeposit'];
        output_data(array('member_info' => $member_info));
    }

    //获取会员详细信息
    public function getMemberInfo(){
        $data=$this->member_info;
        $data['member_avatar']=UPLOAD_SITE_URL.DS.ATTACH_AVATAR."/".$data['member_avatar'];
        unset($data['member_passwd']);
        unset($data['member_paypwd']);
        unset($data['member_login_time']);
        unset($data['member_old_login_time']);
        unset($data['member_login_ip']);
        unset($data['member_old_login_ip']);
        unset($data['inviter3_id']);
        unset($data['inviter2_id']);
        unset($data['token']);
        output_data($data);
    }

    //获取会员详细信息,聊天专用
    public function getMemberInfo_im(){
        $data=$this->member_info;
        $data_new = array();
        $data_new['member_id']=$data['member_id'];
        $data_new['member_avatar']=$data['member_avatar']?UPLOAD_SITE_URL.DS.ATTACH_AVATAR."/".$data['member_avatar']:getCimg('default_user_portrait');
        //根据用户id获取店铺名字，组合用户名返回
        $result = Model('vendor')->getStoreNameBymid($this->member_info['member_id']);
        if(!empty($result)){
            $data_new['member_name'] = $result['store_name'].'('.$data['member_name'].')';
        }
        output_data($data_new);
    }
    public function editUserInfo(){
        $model_member=Model('member');
        $member_array   = array();
        if(isset($_POST['member_truename'])){
            $member_array['member_truename']=$_POST['member_truename'];
        }
        if($_POST['member_sex']=='0' || $_POST['member_sex']){
            $member_array['member_sex'] = $_POST['member_sex'];
        }


        if($_POST['member_qq']){
            $member_array['member_qq']          = $_POST['member_qq'];
        }
        if($_POST['member_ww']){
            $member_array['member_ww']          = $_POST['member_ww'];
        }
        if($_POST['area_id']){
            $member_array['member_areaid']      = $_POST['area_id'];
        }
        if($_POST['city_id']){
            $member_array['member_cityid']      = $_POST['city_id'];
        }
        if($_POST['province_id']){
            $member_array['member_provinceid']  = $_POST['province_id'];
        }
        if($_POST['area_info']){
            $member_array['member_areainfo']    = $_POST['area_info'];
        }
        if($_POST['member_mobile']){
            $member_array['member_mobile'] =$_POST['member_mobile'];
        }

        $member_array['member_id']          = $this->member_info['member_id'];
        if($_POST['birthday']){
            if (strlen($_POST['birthday']) == 10){
                $member_array['member_birthday']    = $_POST['birthday'];
            }
        }
        if($_POST['privacy']){
            $member_array['member_privacy']     = serialize($_POST['privacy']);
        }
        $update = $model_member->editMember(array('member_id'=>$this->member_info['member_id']),$member_array);
        //获取用户信息\
        if($update){
            $data=$model_member->getMemberInfo(array('member_id'=>$this->member_info['member_id']));

        }
//        $data=$model_member->getMemberInfo(array('member_id'=>$this->member_info['member_id']));
        $data['member_avatar']=UPLOAD_SITE_URL.DS.ATTACH_AVATAR."/".$data['member_avatar'];
        output_data(array('member_info' => $data));
    }
//    用户头像
    public function avatars(){
        import('function.thumb');
        $member_id = $this->member_info['member_id'];
        //上传图片
//        $mm=implode('====',$_FILES['pic']);
        $mm=json_encode($_FILES);
//        file_put_contents('./ss.txt',$mm);
        $final=explode('/',$_FILES['pic']['type']);
        $final=$final['1'];
        $upload = new UploadFile();
        $upload->set('thumb_width', 500);
        $upload->set('thumb_height',499);
        $ext = strtolower(pathinfo($_FILES['pic']['name'], PATHINFO_EXTENSION));
        $upload->set('file_name',"avatar_".$member_id.$ext);
        $upload->set('thumb_ext','_app');
        $upload->set('ifremove',true);
        $upload->set('default_dir',ATTACH_AVATAR);
        if (!empty($_FILES['pic']['tmp_name'])){
            $result = $upload->upfile('pic');
            if (!$result){
                $msg='修改用户头像失败';
                output_data(array('msg'=>$msg,'mm'=>'aa'));
            }else{
                $msg='修改用户头像成功';
            }
        }else{
            $msg='修改用户头像失败';
            output_data(array('msg'=>$msg,'mm'=>'bb'));
        }
        $model_member   = Model('member');
        $member_array=array();
        $member_array['member_avatar']='avatar_'.$member_id."_app.".$final;
        $member_array['member_id']=$member_id;
        $update = $model_member->update($member_array);
        $url=UPLOAD_SITE_URL.DS.ATTACH_AVATAR."/".$member_array['member_avatar'];
        $dataMember=$this->member_info;
        $dataMember['member_avatar']=$url;
        output_data(array('member_info' => $dataMember,'member_avatar'=>$url));
    }
    //添加用户浏览商品记录
    public function addUserBrowserGoods(){
        $member_id=$this->member_info['member_id'];
        $vid=$this->member_info['vid'];
        $gid=$_POST['gid'];
        $browser=Model('goods_browsehistory');
        $data=$browser->addViewedGoods($gid,$member_id,$vid);
        output_data(array('data'=>1));
        //修改商品的浏览量
    }
    //添加用户浏览商品记录(用于微信小程序)
    public function addUserBrowserGoods_xcx(){
        $member_id=$this->member_info['member_id'];
        $vid=$this->member_info['vid'];
        $gid=$_GET['gid'];
        $browser=Model('goods_browsehistory');
        $data=$browser->addViewedGoods($gid,$member_id,$vid);
        $state = 200;
        if(!$data){
            $state = 250;
        }
        echo json_encode(array('state'=>$state));
        //修改商品的浏览量
    }
    //获取浏览记录列表
    public function getUserFootorList()
    {
        $model = Model('goods_browsehistory');
        $rows=$_REQUEST['rows'];
        //商品分类缓存
        //$gc_list = Model('goods_class')->getGoodsClassForCacheModel()
        $gc_list = H('goods_class') ? H('goods_class') : H('goods_class', true);
        //查询浏览记录
        $where = array();
        $where['member_id'] = $this->member_info['member_id'];
        $gc_id = intval($_GET['gc_id']);
        if ($gc_id > 0) {
            $where['gc_id_' . $gc_list[$gc_id]['depth']] = $gc_id;
        }
        $browselist_tmp = $model->getGoodsbrowseList($where, '', $rows, 0, 'browsetime desc');
        $page_count = $model->gettotalpage();
        $browselist = array();
        foreach ((array)$browselist_tmp as $k => $v) {
            $browselist[$v['gid']] = $v;
        }
        //查询商品信息
        $browselist_new = array();
        if ($browselist) {

            $goods_list_tmp = Model('goods')->getGoodsList(array('gid' => array('in', array_keys($browselist))), 'gid, goods_name, goods_price,goods_promotion_price,goods_promotion_type, goods_marketprice, goods_image, vid, gc_id, gc_id_1, gc_id_2, gc_id_3');

            $goods_list = array();
            foreach ((array)$goods_list_tmp as $v) {
                $goods_list[$v['gid']] = $v;
            }

            // 获取最终价格
            $goods_list = Model('goods_activity')->rebuild_goods_data($goods_list);

            foreach ($browselist as $k => $v) {
                if ($goods_list[$k]) {
                    $tmp = array();
                    $tmp = $goods_list[$k];
                    $tmp["browsetime"] = $v['browsetime'];
                    if (date('Y-m-d', $v['browsetime']) == date('Y-m-d', time())) {
                        $tmp['browsetime_day'] = '今天';
                    } elseif (date('Y-m-d', $v['browsetime']) == date('Y-m-d', (time() - 86400))) {
                        $tmp['browsetime_day'] = '昨天';
                    } else {
                        $tmp['browsetime_day'] = date('Y年m月d日', $v['browsetime']);
                    }
                    $tmp['browsetime_text'] = $tmp['browsetime_day'] . date('H:i', $v['browsetime']);
                    $browselist_new[] = $tmp;
                }
            }

            //将浏览记录按照时间重新组数组
            $browselist_new_date = array();
            foreach ($browselist_new as $ks => $vs) {
                $browselist_new_date[$vs['browsetime_day']][] = $vs;
            }

        }
        foreach($browselist_new as $kk=>$vv){
            $urlimg=explode('_',$browselist_new[$kk]['goods_image']);
            $browselist_new[$kk]['goods_image']=UPLOAD_SITE_URL . '/' . ATTACH_GOODS.'/'.$urlimg[0].'/'
.$browselist_new[$kk]['goods_image'];
        }
        output_data(array('browse_totalnum'=>sizeof($browselist_new),'browse_history'=>$browselist_new),mobile_page($page_count));

    }
    public function delFooter(){
        $return_arr = array();
        $model = Model('goods_browsehistory');
        $member_id=$this->member_info['member_id'];
        if (trim($_POST['gid']) == 'all') {
            if ($model->delGoodsbrowse(array('member_id'=>$member_id))){
                $return_arr = array('done'=>true);
            } else {
                $return_arr = array('done'=>false,'msg'=>'删除失败');
            }
        } elseif (intval($_POST['gid']) >= 0) {
            $gid = intval($_POST['gid']);
            if ($model->delGoodsbrowse(array('member_id'=>$member_id,'gid'=>$gid))){
                $return_arr = array('done'=>true);
            } else {
                $return_arr = array('done'=>false,'msg'=>'删除失败');
            }
        } else {
            $return_arr = array('done'=>false,'msg'=>'参数错误');
        }
        output_data(array('data' => $return_arr));
    }
    //修改密码
    public function editPassword(){
        $member_info=Model('member');
        $member_id=$this->member_info['member_id'];
        $opassword=$_POST['opassword'];//原密码
        $npassword=$_POST['npassword'];//新密码
        $result = $member_info->where(array('member_id'=>$member_id,'member_passwd'=>md5(trim($_POST['opassword']))))->find();
        if($result){
            $update = $member_info->update(array('member_passwd'=>md5(trim($_POST['npassword'])),'member_id'=>$member_id));
            if($update){
                output_data(array('data' => '修改密码成功','state'=>'success'));
            }else{
                output_data(array('data' => '修改密码失败','state'=>'failuer'));
            }
        }else{
            output_data(array('data' => '原密码和用户名不匹配修改密码失败','state'=>'failuer'));
        }
    }
    //获取我的界面的推荐商品
    public function getRecGoodsop(){
        $goods=Model('goods');
        $data=$goods->getRecGoods();
        $page_count = $goods->gettotalpage();
        output_data(array('goods_list'=>$data),mobile_page($page_count));
    }
    //获取用户积分明细
    public function gerUserPointsInfo(){
        $member_id=$this->member_info['member_id'];
        $points=$this->member_info['member_points'];
        $order=Model('order');
        $data=$order->getUserPointsInfo($member_id);
        $page_count = $order->gettotalpage();
        foreach($data as $k=>$v){
            $data[$k]['wap_time']=date('Y-m-d H:i:s',$data[$k]['pl_addtime']);
        }
        output_data(array('points'=>$points,'points_list'=>$data));
    }
    //获取用户的积分 优惠券 收藏的值
    public  function  getUserInfoNumop(){
        $num=array();
        $member_id=$this->member_info['member_id'];
        $num['points']=$this->member_info['member_points'];
        $num['cashfree']=$this->member_info['available_predeposit'];
        $favorites=Model('favorites');
        $fnum=$favorites->getFavorotesNum($member_id);
        $num['goodsNum']=$fnum['goodsNum']['count'];
        $num['storeNum']=$fnum['storeNum']['count'];
        $num['BrowseHistoryNum']=$fnum['BrowseHistoryNum']['count'];
        $num['voucherNum']=$fnum['voucherNum']['count'];
        $num['return_num']=$fnum['return_num']['count'];
        $order=Model('order');
        //获取订单的数量
        $condition=array();
        $condition['buyer_id']=$member_id;
        $num['order_newNum']=$order->getOrderStateNewCount($condition);//未付款订单数量
        $num['order_payNum']=$order->getOrderStatePayCount($condition);
        $num['order_sendNum']=$order->getOrderStateSendCount($condition);
        $num['order_receiveNum']=$num['order_payNum']+$num['order_sendNum'];
        $num['order_commentNum']=$order->getOrderStateEvalCount($condition);
        $num['order_totalNum']=$order->getOrderCount($condition);
        $num['aviable_price']=$this->member_info['available_predeposit'];
        //获取用户的会员等级
        $model_member   = Model('member');
        $grade=$this->member_info['member_growthvalue'];
        $member_grade=$model_member->getMemberGradeArr(ture,$grade);
        $rule=array();
        foreach($member_grade as $kk=>$vv){
            array_push($rule,$vv['growthvalue']);
        }
        if(!$grade){
            $num['grade_id']=$member_grade[0]['level_name'];
        }
        if($rule[0]<$grade&&$grade<=$rule[1]){
            $num['grade_id']=$member_grade[1]['level_name'];
        }
        if($rule[1]<$grade&&$grade<=$rule[2]){
            $num['grade_id']=$member_grade[2]['level_name'];
        }
        if($rule[2]<$grade&&$grade<=$rule[3]){
            $num['grade_id']=$member_grade[3]['level_name'];
        }
        if($rule[3]<$grade){
            $num['grade_id']=$member_grade[3]['level_name'];
        }
        output_data(array('num_Info' => $num));
    }

    /**
     * 用户信息
     */
    public function memberInfo() {
        $member_info = array();
        $member_id=$this->member_info['member_id'];
        $member_info['member_id'] = $this->member_info['member_id'];
        $member_info['user_name'] = $this->member_info['wx_nickname']?$this->member_info['wx_nickname']:$this->member_info['member_name'];
//        $member_info['avator'] = getMemberAvatarForID($this->member_info['member_id']);
        $member_info['avator'] = UPLOAD_SITE_URL.'/'.ATTACH_AVATAR.'/'.$this->member_info['member_avatar'];
        $member_info['point'] = $this->member_info['member_points'];
        $member_info['predepoit'] = $this->member_info['available_predeposit'];
        $favorites=Model('favorites');
        $fnum=$favorites->getFavorotesNum($member_id);
        $member_info['goodsNum']=$fnum['goodsNum']['count'];
        $member_info['storeNum']=$fnum['storeNum']['count'];
        $member_info['BrowseHistoryNum']=$fnum['BrowseHistoryNum']['count'];
        $member_info['voucherNum']=$fnum['voucherNum']['count'];




        // 可用佣金、冻结佣金、失效佣金
        $member_info['available_yongjin'] = floatval($this->member_info['available_yongjin']);
        $member_info['freeze_yongjin'] = floatval($this->member_info['freeze_yongjin'])*1;
        $member_info['disable_yongjin'] = floatval($this->member_info['disable_yongjin'])*1;

        output_data(array('member_info' => $member_info));
    }

    /**
     * 用户信息（用于微信小程序）
     */
    public function memberInfo_xcx() {
        $member_info = array();
        $member_id=$this->member_info['member_id'];
        $member_info['member_id'] = $this->member_info['member_id'];
        $member_info['user_name'] = $this->member_info['member_name'];
        $member_info['avator'] = getMemberAvatarForID($this->member_info['member_id']);
        $member_info['point'] = $this->member_info['member_points'];
        $member_info['predepoit'] = $this->member_info['available_predeposit'];
        $favorites=Model('favorites');
        $fnum=$favorites->getFavorotesNum($member_id);
        $member_info['goodsNum']=$fnum['goodsNum']['count'];
        $member_info['storeNum']=$fnum['storeNum']['count'];
        $member_info['BrowseHistoryNum']=$fnum['BrowseHistoryNum']['count'];
        $member_info['voucherNum']=$fnum['voucherNum']['count'];
        //获取订单的数量
        $order=Model('order');
        // $condition=array();
        // $condition['buyer_id']=$member_id;
        // $member_info['order_newNum']=$order->getOrderStateNewCount($condition);//未付款订单数量
        // $member_info['order_payNum']=$order->getOrderStatePayCount($condition);
        // $member_info['order_sendNum']=$order->getOrderStateSendCount($condition);
        // $member_info['order_receiveNum']=$num['order_payNum']+$num['order_sendNum'];
        // $member_info['order_commentNum']=$order->getOrderStateEvalCount($condition);
        // $member_info['order_totalNum']=$order->getOrderCount($condition);
        $dai_fu=$order->getOrderCount(array('buyer_id'=>$member_id,'order_state'=>ORDER_STATE_NEW));
        $dai_fahuo=$order->getOrderCount(array('buyer_id'=>$member_id,'order_state'=>ORDER_STATE_PAY));
        $dai_send=$order->getOrderCount(array('buyer_id'=>$member_id,'order_state'=>ORDER_STATE_SEND));
        $dai_ping=$order->getOrderCount(array('buyer_id'=>$member_id,'order_state'=>ORDER_STATE_SUCCESS));
        $model_refund = Model('refund_return');
        $condition=array();
        $condition['refund_type']=array('in',array(1,2));
        $condition['buyer_id']=$this->member_info['member_id'];
        $refund_count = $model_refund->getRefundReturnCount($condition);
        
        $member_info['dai_fu']=$dai_fu;
        $member_info['dai_fahuo']=$dai_fahuo;
        $member_info['dai_send']=$dai_send;
        $member_info['dai_ping']=$dai_ping;
        $member_info['refund_count']=$refund_count;
        
        //获取用户的会员等级
        $model_member   = Model('member');
        $grade=$this->member_info['member_growthvalue'];
        $member_grade=$model_member->getMemberGradeArr(ture,$grade);
        $rule=array();
        foreach($member_grade as $kk=>$vv){
            array_push($rule,$vv['growthvalue']);
        }
        if(!$grade){
            $member_info['grade_id']=$member_grade[0]['level_name'];
        }
        if($rule[0]<$grade&&$grade<=$rule[1]){
            $member_info['grade_id']=$member_grade[1]['level_name'];
        }
        if($rule[1]<$grade&&$grade<=$rule[2]){
            $member_info['grade_id']=$member_grade[2]['level_name'];
        }
        if($rule[2]<$grade&&$grade<=$rule[3]){
            $member_info['grade_id']=$member_grade[3]['level_name'];
        }
        if($rule[3]<$grade){
            $member_info['grade_id']=$member_grade[3]['level_name'];
        }

        // 用户余额、冻结金额、总金额
        $member_info['available_predeposit'] = floatval($this->member_info['available_predeposit']);
        $member_info['freeze_predeposit'] = floatval($this->member_info['freeze_predeposit'])*1;
        $member_info['total_predeposit'] = floatval($member_info['available_predeposit'])*1 + floatval($member_info['freeze_predeposit'])*1;
        // 是否 有 open_id 
        $model_member = Model('member');
        $member_info_xcx=$model_member->getMemberInfo(array('member_id'=>$this->member_info['member_id']));
        $member_info['has_open_id'] = $member_info_xcx['wx_openid'] ? true : false;
        
        echo json_encode(array('state'=>200,'member_info' => $member_info));
    }
    /**
     * 获取用户的等级和经验值信息——张金凤wap——app
     */
    public function membergrowth() {
        $member_info = array();
        $member_id=$this->member_info['member_id'];
        $member_info['user_name'] = $this->member_info['member_name'];
        $member_info['avator'] = getMemberAvatar($this->member_info['member_avatar']);
        $model_member   = Model('member');
        $grade=$this->member_info['member_growthvalue'];
        $member_grade=$model_member->getMemberGradeArr(ture,$grade);
        $rule=array();
        foreach($member_grade as $kk=>$vv){
            array_push($rule,$vv['growthvalue']);
        }
        if(!$grade){
            $member_info['grade_id']=$member_grade[0]['level_name'];
        }
        if($rule[0]<$grade&&$grade<=$rule[1]){
            $member_info['grade_id']=$member_grade[0]['level_name'];
        }
        if($rule[1]<$grade&&$grade<=$rule[2]){
            $member_info['grade_id']=$member_grade[1]['level_name'];
        }
        if($rule[2]<$grade&&$grade<=$rule[3]){
            $member_info['grade_id']=$member_grade[2]['level_name'];
        }
        if($rule[3]<$grade){
            $member_info['grade_id']=$member_grade[3]['level_name'];
        }
        $member_info['growthvalue'] = $grade;
        output_data(array('member_info' => $member_info));
    }
    /**
     * 我的推广二维码
     */
    public function tuiguang_qrcode(){
        if (file_exists(BASE_WAP_PATH.DS.'images'.DS.'userqrcode'.DS.'qrcode' .'_'.$this->member_info['member_id'].'.png')){
            $tgsrc = WAP_SITE_URL.DS.'images'.DS.'userqrcode'.DS.'qrcode' .'_'.$this->member_info['member_id'].'.png';
            output_data(array('tgsrc'=>$tgsrc,'member_id'=>$this->member_info['member_id']));exit();
        }
        $member_name=$this->member_info['member_name'];
        $mb_name=base64_encode(intval($this->member_info['member_id'])*999999999);
        // 生成二维码
        require_once(BASE_STATIC_PATH.DS.'phpqrcode'.DS.'index.php');
        $PhpQRCode = new PhpQRCode();
        $PhpQRCode->set('pngTempDir',BASE_WAP_PATH.DS.'images'.DS.'userqrcode'.DS);
        //邀请链接
        $qrcode_invite_link=WAP_SITE_URL."/cwap_register_tel.html?u=".$mb_name;
        $PhpQRCode->set('date', $qrcode_invite_link);
        $PhpQRCode->set('matrixPointSize', 100);
        $PhpQRCode->set('pngTempName', 'qrcode' .'_'.$this->member_info['member_id'].'.png');             //原始图片
        $PhpQRCode->init();

        $tgsrc = WAP_SITE_URL.DS.'images'.DS.'userqrcode'.DS.'qrcode' .'_'.$this->member_info['member_id'].'.png';
        output_data(array('tgsrc'=>$tgsrc,'member_id'=>$this->member_info['member_id']));
    }
    /*推广总人数（包含一级  二级  三级）
     *
     */
    public function getTuigNum(){
        $model_user=Model('member');
        $member_list1=$model_user->getMemberList(array('inviter_id'=>$this->member_info['member_id']));
        $member_list2=$model_user->getMemberList(array('inviter2_id'=>$this->member_info['member_id']));
        $member_list3=$model_user->getMemberList(array('inviter3_id'=>$this->member_info['member_id']));
        $total = count($member_list1)+count($member_list2)+count($member_list3);
        output_data(array('total'=>$total,'grade1'=>count($member_list1),'grade2'=>count($member_list2),'grade3'=>count($member_list3)));
    }
    /*每一级别的会员列表
     *
     */
    public function childListInfo(){
        $model_user=Model('member');
        $member_list = array();
        $grade = '一级会员';
        if($_GET['type'] == 1){
            $member_list=$model_user->getMemberList(array('inviter_id'=>$this->member_info['member_id']));
            $grade = '一级会员';
        }else if($_GET['type'] == 2){
            $member_list=$model_user->getMemberList(array('inviter2_id'=>$this->member_info['member_id']));
            $grade = '二级会员';
        }else if($_GET['type'] == 3){
            $member_list=$model_user->getMemberList(array('inviter3_id'=>$this->member_info['member_id']));
            $grade = '三级会员';
        }
        foreach ($member_list as $key => $val){
            $member_list[$key]['regester_time'] = date('Y-m-d H:i:s',$val['member_time']);
            $member_list[$key]['grade_level'] = $grade;
        }
        output_data(array('member_info'=>$member_list));
    }
    /*
     * 佣金明细
     */
    public function gerDisIncomeDetail(){
        $model_fenxiao=Model('fenxiao');
        //已获得返利佣金
        $fenxiaolist=$model_fenxiao->getCommissionInfo(array('reciver_member_id'=>$this->member_info['member_id']));
        $total_get = 0;
        foreach ($fenxiaolist as $key => $val){
            $fenxiaolist[$key]['wap_time'] = date('Y-m-d H:i:s',$val['add_time']);
            if($val['status'] == 1){
                $total_get += $val['yongjin'];
            }
        }
        output_data(array('fenxiao_info'=>$fenxiaolist,'total_get' => $total_get));
    }
    /**
     * 用户等级规则信息——张金凤wap——app
     */
    public function memberGradeRule() {
        $member_info = array();
        $member_id=$this->member_info['member_id'];
        $member_info['user_name'] = $this->member_info['member_name'];
        $member_info['avator'] = getMemberAvatarForID($this->member_info['member_id']);
        $member_info['point'] = $this->member_info['member_points'];
        $member_info['predepoit'] = $this->member_info['available_predeposit'];

        //获取用户的会员等级
        $model_member   = Model('member');
        $grade=$this->member_info['member_growthvalue'];
        $member_grade=$model_member->getMemberGradeArr(ture,$grade);
        foreach ($member_grade as $key=>$val){
            if($key == 3){
                $member_grade[$key]['graderule'] = $val['growthvalue'].' 以上经验值';
            }else{
                $member_grade[$key]['graderule'] = $val['growthvalue']." ~ ".$member_grade[$key+1]['growthvalue'].'经验值';
            }
        }
        output_data(array('member_grade_rule' => $member_grade));
    }
    /**
     * 获取用户升级信息——张金凤wap——app
     */
    public function memberUpgrade() {
        $member_info = array();
        $member_id=$this->member_info['member_id'];
        $member_info['user_name'] = $this->member_info['member_name'];
        $member_info['avator'] = getMemberAvatarForID($this->member_info['member_id']);
        $member_info['point'] = $this->member_info['member_points'];
        $member_info['predepoit'] = $this->member_info['available_predeposit'];

        //获取用户的会员等级
        $model_member   = Model('member');
        $grade=$this->member_info['member_growthvalue'];
        $memberupgrade_info = $model_member->getOneMemberGrade($grade, true);
        output_data(array('memberupgrade_info' => $memberupgrade_info));
    }
    /**
     * 获取用户经验值明细——张金凤wap——app
     */
    public function growthLog() {
        $model_growthvalue = Model('growthvalue');
        $where = array();
        $where['growth_memberid'] = $member_id=$this->member_info['member_id'];
        $list_log = $model_growthvalue->getGrowthValueList($where, '*', 20, 0, 'growth_id desc');
        foreach ($list_log as $key=>$val){
            $list_log[$key]['growth_addtimes'] = date('Y-m-d H:i:s',$val['growth_addtime']);
        }
        output_data(array('list_log' => $list_log));
    }
    /**
     * 获取用户预存款明细——张金凤wap——app
     */
    public function predepositlog() {
        $model_pd = Model('predeposit');
        $page = new Page();
        $condition = array();
        $condition['lg_member_id'] = $this->member_info['member_id'];
        $list = $model_pd->getPdLogList($condition,20,'*','lg_id desc');
        $page_count = $model_pd->gettotalpage();
        foreach ($list as $key=>$val){
            $list[$key]['lg_add_times'] = date('Y-m-d H:i:s',$val['lg_add_time']);
        }
        output_data(array('list' => $list),mobile_page($page_count));
    }
    /**
     * 获取用户的充值记录——张金凤wap——app
     */
    public function pdrechargelist() {
//        $pdr_id = intval($_GET["id"]);
        $model_pd = Model('predeposit');
        $condition = array();
        $condition['pdr_member_id'] = $this->member_info['member_id'];
//        $condition['pdr_id'] = $pdr_id;
        $condition['pdr_payment_state'] = 1;
        $page_count = $model_pd->gettotalpage();
        $list = $model_pd->getPdRechargeList($condition);
        foreach ($list as $key=>$val){
            $list[$key]['pdr_add_time'] = date('Y-m-d H:i:s',$val['pdr_add_time']);
        }
        output_data(array('list' => $list),mobile_page($page_count));
    }
    /**
     * 获取用户的提现记录——张金凤wap——app
     */
    public function pdcashlist() {
        $condition = array();
        $condition['pdc_member_id'] =  $this->member_info['member_id'];
        if (preg_match('/^\d+$/',$_GET['sn_search'])) {
            $condition['pdc_sn'] = $_GET['sn_search'];
        }
        if (isset($_GET['paystate_search'])){
            $condition['pdc_payment_state'] = intval($_GET['paystate_search']);
        }
        $model_pd = Model('predeposit');
        $cash_list = $model_pd->getPdCashList($condition,30,'*','pdc_id desc');
        foreach ($cash_list as $key=>$val){
            $cash_list[$key]['pdc_add_time'] = date('Y-m-d H:i:s',$val['pdc_add_time']);
        }
        $page_count = $model_pd->gettotalpage();
        output_data(array('list' => $cash_list),mobile_page($page_count));
    }
    /**
     * 获取用户的账户余额——张金凤wap——app
     */
    public function my_asset() {
        $predepoit = $this->member_info['available_predeposit'];
        output_data(array('predepoit' => $predepoit));
    }
        //wap端浏览记录
    public function getUserFootorListsop()
    {
        $model = Model('goods_browsehistory');
        //商品分类缓存
        //$gc_list = Model('goods_class')->getGoodsClassForCacheModel()
        $gc_list = H('goods_class') ? H('goods_class') : H('goods_class', true);
        //查询浏览记录
        $where = array();
        $where['member_id'] = $this->member_info['member_id'];
        $gc_id = intval($_GET['gc_id']);
        if ($gc_id > 0) {
            $where['gc_id_' . $gc_list[$gc_id]['depth']] = $gc_id;
        }
        $browselist_tmp = $model->getGoodsbrowseList($where, '', 20, 0, 'browsetime desc');
        $browselist = array();
        foreach ((array)$browselist_tmp as $k => $v) {
            $browselist[$v['gid']] = $v;
        }
        //查询商品信息
        $browselist_new = array();
        if ($browselist) {

            $goods_list_tmp = Model('goods')->getGoodsList(array('gid' => array('in', array_keys($browselist))), 'gid, goods_name, goods_price,goods_promotion_price,goods_promotion_type, goods_marketprice, goods_image, vid, gc_id, gc_id_1, gc_id_2, gc_id_3');

            $goods_list = array();
            foreach ((array)$goods_list_tmp as $v) {
                $goods_list[$v['gid']] = $v;
            }
            foreach ($browselist as $k => $v) {
                if ($goods_list[$k]) {
                    $tmp = array();
                    $tmp = $goods_list[$k];
                    $tmp["browsetime"] = $v['browsetime'];
                    $tmp['browsetime_day'] = date('Y-m-d', $v['browsetime']);
                    $tmp['browsetime_text'] = $tmp['browsetime_day'] . date('H:i', $v['browsetime']);
                    $browselist_new[] = $tmp;
                }
            }

            //将浏览记录按照时间重新组数
            $browselist_new_date = array();
            foreach ($browselist_new as $ks => $vs) {
                $browselist_new_date[$vs['browsetime_day']][] = $vs;
            }

        }
        foreach($browselist_new as $kk=>$vv){
            $urlimg=explode('_',$browselist_new[$kk]['goods_image']);
            $browselist_new[$kk]['goods_image']=UPLOAD_SITE_URL . '/' . ATTACH_GOODS.'/'.$urlimg[0].'/'
                .$browselist_new[$kk]['goods_image'];
        }
        output_data(array('browse_totalnum'=>sizeof($browselist_new),'browse_history'=>$browselist_new_date));
    }
    //获取购物车数量
    public  function getCartNum(){
        $cartModel=Model('cart');
        $num=$cartModel->getCartNums($this->member_info['member_id']);
        output_data(array('num'=>$num['num']));
    }
    //用户头像
    public function avatar(){
        import('function.thumb');
        $member_id = $this->member_info['member_id'];
        $upload = new UploadFile();
        $upload_dir = DS.ATTACH_AVATAR;
        $upload->set('default_dir',$upload_dir.$upload->getSysSetPath());
        $thumb_width	= '240,1024';
        $thumb_height	= '2048,1024';
        $upload->set('max_size',C('image_max_filesize'));
        $upload->set('thumb_width', $thumb_width);
        $upload->set('thumb_height',$thumb_height);
        $upload->set('fprefix',$member_id);
        $upload->set('thumb_ext', '_240,_1024');
        $result = $upload->upfile('pic');
        if (!$result){
            output_data(array('state'=>'false','message'=>'上传图片失败'));

        }
        $img_path = $upload->getSysSetPath().$upload->file_name;
        $model_member	= Model('member');
        $member_array=array();
        $member_array['member_avatar']=$img_path;
        $member_array['member_id']=$member_id;
        $update = $model_member->update($member_array);
        $url=UPLOAD_SITE_URL.DS.ATTACH_AVATAR."/".$member_array['member_avatar'];
        $dataMember=$this->member_info;
        $dataMember['member_avatar']=$url;
        output_data(array('member_info' => $dataMember,'member_avatar'=>$url));
    }

    //获取用户余额
    public function useryue()
    {
        $yue_res = model()->table('pd_log')->where(['lg_member_id'=>$this->member_info['member_id']])->order('lg_id desc')->select();
        foreach($yue_res as $k=>$v)
        {
            if(!empty($v['lg_desc']) && $v['lg_av_amount'] != 0)
            {
                $res[$k]['pl_points'] = $v['lg_av_amount'];
                $res[$k]['pl_desc'] = $v['lg_desc'];
                $res[$k]['pl_addtime'] = date('Y-m-d H:i:s',$v['lg_add_time']);
            }
        }
        output_data(array('points_list' => $res));
    }

    /*
     *我的预存款
     */
    public function pointNum()
    {
        $points = $this->member_info['member_point_special'];
        $money = $this->member_info['available_predeposit'];
        output_data(array('points' => $points,'yucunqian'=>$money));
    }

    // 我的签到记录
    public function myCheckInLog()
    {
        $return_arr = array();
        $log_list = array();

        $page_count = 0;
        $eachNum = 10;

        if ($GLOBALS['setting_config']['points_isuse'] == 1){

            $points_model = Model('points');


           $page = new Page();
           $page->setEachNum($eachNum);

            $total_points = 0;

            // 获取 当前会员的签到记录列表
            $log_condition = array();
            $log_condition['pl_memberid'] = $this->member_info['member_id'];
            $log_condition['pl_stage'] = 'checkin';
            $log_condition['order'] = 'pl_addtime desc';
            $all_log_list = $points_model->getPointsLogList($log_condition);
            foreach ($all_log_list as $key => $value) {
                $total_points += $value['pl_points'];
            }
            $log_list = $points_model->getPointsLogList($log_condition,$page);

            $page_count = count($all_log_list);

            foreach ($log_list as $key => $value) {
                $value['pl_addtime_str'] = date('Y.m.d',$value['pl_addtime']);
                $log_list[$key] = $value;
            }

            $return_arr['list'] = $log_list;
            $return_arr['total_count'] = $page_count;
            $return_arr['total_points'] = $total_points;

            $state = 'success';
            $message = '';
        }else{
            $state = 'failuer';
            $message = '积分功能未开启';
        }

        $return_arr['state'] = $state;
        $return_arr['msg'] = $message;

        if ($page_count) {
            output_data($return_arr,mobile_page($page_count));
        }else{
            output_data($return_arr);
        }

    }

    // 检查当前用户是否已签到
    public function UserCheckinIsChecked(){
        $checkin_stage = 'checkin';
        $return_arr = array();
        $log_list = array();

        if ($GLOBALS['setting_config']['points_isuse'] == 1){

            // 校验 该用户 今天是否 签到；已签到用户不能再次签到
            $points_model = Model('points');
            $condition = array();
            $condition['pl_memberid'] = $this->member_info['member_id'];
            $s_time = strtotime(date('Y-m-d',time()));
            $e_time = $s_time + 86400;
            $condition['saddtime'] = $s_time;
            $condition['eaddtime'] = $e_time;
            $condition['pl_stage'] = $checkin_stage;
            $has_checked_flag = $points_model->getPointsInfo($condition,'pl_id');

            $return_arr['flag'] = $has_checked_flag ? true : false;

            $state = 'success';
            $message = '';
        }else{
            $state = 'failuer';
            $message = '积分功能未开启';
        }

        $return_arr['state'] = $state;
        $return_arr['msg'] = $message;

        output_data($return_arr);
    }

    // 签到操作
    public function checkInAction()
    {
        $checkin_stage = 'checkin';
        $return_arr = array();
        $log_list = array();

        $page_count = 0;
        $eachNum = 10;

        if ($GLOBALS['setting_config']['points_isuse'] == 1){

            // 校验 该用户 今天是否 签到；已签到用户不能再次签到
            $points_model = Model('points');
            $condition = array();
            $condition['pl_memberid'] = $this->member_info['member_id'];
            $s_time = strtotime(date('Y-m-d',time()));
            $e_time = $s_time + 86400;
            $condition['saddtime'] = $s_time;
            $condition['eaddtime'] = $e_time;
            $condition['pl_stage'] = $checkin_stage;
            $has_checked_flag = $points_model->getPointsInfo($condition,'pl_id');

            if (!$has_checked_flag) {
                //添加会员积分
                $points_model->savePointsLog($checkin_stage,array('pl_memberid'=>$this->member_info['member_id'],'pl_membername'=>$this->member_info['member_name']));

                $state = 'success';
                $message = '签到成功';
                // 获取 当前会员的签到记录列表
                $log_condition = array();
                $log_condition['pl_memberid'] = $this->member_info['member_id'];
                $log_condition['pl_stage'] = $checkin_stage;
                $log_condition['order'] = 'pl_addtime desc';
                $log_list = $points_model->getPointsLogList($log_condition,$eachNum);
                // $page_count = $points_model->gettotalpage();
            }else{
                $state = 'failuer';
                $message = '每日只可签到一次';
            }
        }else{
            $state = 'failuer';
            $message = '积分功能未开启';
        }

        $return_arr['state'] = $state;
        $return_arr['msg'] = $message;
        if (isset($log_list) && !empty($log_list)) {
            $return_arr['list'] = $log_list;
        }

        if ($page_count) {
            output_data($return_arr,mobile_page($page_count));
        }else{
            output_data($return_arr);
        }
    }
    
    /*
     *一键反馈
     *
     */
    public function user_feedback(){

        $feedback=array();
        $feedback['content']=trim($_POST['content']);
        $feedback['member_id']=$this->member_info['member_id'];
        $feedback['member_name']=$this->member_info['member_name'];
        $feedback['member_mobile']=$this->member_info['member_mobile'];
        $feedback['ftime']=time();
        $feedback['type']=!empty($_POST['type']) ? $_POST['type'] : 1;

        $obj_validate = new Validate();
        $obj_validate->validateparam = array(
            array("input"=>$_POST["content"],"require"=>"true","message"=>'姓名不能为空'),
        );
        $error = $obj_validate->validate();
        if ($error != ''){
            output_data(array('status' => -1,'msg'=>'反馈内容不能为空'));
        }

        $user_feed=Model('user_feedback');
        $result=$result=$user_feed->addfeedback($feedback);

        if($result) {
            output_data(array('status' => 1, 'msg' => '反馈成功'));
        }
    }

    /*
     *每个人反馈列表
     *
     */
    public function feedback_list(){
        $condition=array();
        $condition['member_id']=$this->member_info['member_id'];

        $user_feed=Model('user_feedback');

        $feedback= $user_feed->getfeedbackList($condition);

        $page_count = $user_feed->gettotalpage();
        output_data(array('feedback' => $feedback,'status'=>1),mobile_page($page_count));

    }

    /**
     * 系统消息
     *
     * @param
     * @return
     */
    public function systemmsg(){

        $model_message  = Model('message');

        $condition=array();
        $condition['to_member_id']=$this->member_info['member_id'];
        $condition['message_type']=1;
        $condition['from_member_id']=0;


        switch($_GET['type']){
            case 'fahuo':
                $this->showmsgbatch(1);
                $condition['system_type']=1;
                break;
            case 'order_pay':
                $this->showmsgbatch(2);
                $condition['system_type']=2;
                break;
            case 'preposit':
                $this->showmsgbatch(3);
                $condition['system_type']=3;
                break;
            case 'tui':
                $this->showmsgbatch(4);
                $condition['system_type']=4;
        }

        $message_array  = $model_message->listMessage($condition,10);

        if(!empty($message_array)){
            // 过滤掉 其中的a标签及a标签内容
            foreach ($message_array as $key => $value) {
                $message_array[$key]['message_time_str'] = date('Y-m-d H:i:s',$value['message_time']);
                $message_array[$key]['message_body'] = preg_replace("/<a[^>]*>(.*?)<\/a>/is", "", $value['message_body']);
                
                // 验证链接是否 正确
                if ($value['link']) {
                    $rules = array(
                        'fahuo'=>'/app=userorder&mod=show_order&order_id=(\d*)/i',
                        'order_pay'=>'/app=userorder&mod=show_order&order_id=(\d*)/i',
                        'preposit'=>'/app=chongzhi&mod=chongzhilist/i',
                        'tui'=>'/app=member_refund&return_id=(\d*)/i',
                    );
                    $replace_urls = array(
                        'fahuo'=> 'cwap_order_detail.html?order_id=',
                        'order_pay'=> 'cwap_order_detail.html?order_id=',
                        'preposit'=> 'cwap_user.html',
                        'tui'=> 'cwap_user_refund_info.html?refund_id=',
                    );
                    $rule = $rules[$_GET['type']];
                    if (preg_match_all($rule,$value['link'],$matches)) {
                        if (!empty($matches) && $matches[0]) {
                            $has_id = false;
                            $need_id = 0;
                            if ($matches[1]) {
                                $has_id = true;
                                $need_id = $matches[1][0];
                            }
                            if (isset($replace_urls[$_GET['type']])) {
                                $last_url = $replace_urls[$_GET['type']];
                                if ($need_id) {
                                    $last_url .= $need_id;
                                }
                                $message_array[$key]['link'] = $last_url;
                            }
                        }
                    }
                }
            }

            $page_count = $model_message->gettotalpage();
            output_data(array('message_list' => $message_array,'status'=>1),mobile_page($page_count));
        }else{
            output_data(array('status'=>-1,'msg'=>'暂无数据'));
        }

    }


    /**
     * 统计系统站内信未读条数
     *
     * @return int
     */
    public function receivedSystemNewNum(){
        $message_model = Model('message');
        $condition_arr = array();
        $condition_arr['message_type'] = '1';//系统消息
        $condition_arr['to_member_id'] = $this->member_info['member_id'];
        $condition_arr['no_read_member_id'] = $this->member_info['member_id'];
        $condition_arr['system_type_sys'] = '(1,2,3,4)';
        $countnum = $message_model->countMessage($condition_arr);

        if($countnum>0){
            output_data(array('status'=>1,'countnum'=>$countnum));
        }else{
            output_data(array('status'=>-1,'msg'=>'暂无未读消息'));
        }
    }


    /*
     *查看详情将消息置为已读
     *
     * 参数 message_id 消息id
     *
     */
    public function showmsgbatch($system_type) {
        $model_message  = Model('message');
        $member_id=$this->member_info['member_id'];

        //更新为已读信息
        $tmp_readid_str = ",{$member_id},";

        $model_message->updateCommonMessage(array('read_member_id'=>$tmp_readid_str),array('system_type'=>$system_type,'message_type'=>1,'to_member_id'=>$member_id));

    }

    // 当前推手 的下级商家用户
    public function memberSubordinates(){

        $last_return = array();

        $ssys_member = M('ssys_member','spreader');

        $condition['member_id'] = $this->member_info['member_id'];
        $sub_member_list = $ssys_member->get_member_nexus_select($condition);

        $page_count = $ssys_member->gettotalpage();

        // 获取 当前推手的订单信息(冻结金额及已结算金额)
        $ssys_order = M('ssys_order','spreader');
        $order_condition['member_id'] =  $this->member_info['member_id'];
        $order_condition['yj_status'] = array("IN",array(0,1));
        $order_condition['delete_state'] = 0;
        $spreader_orders = $ssys_order->getSpreaderOrderInfos($order_condition,'order_id,order_sn,gid,member_id,yj_amount');
        $shop_order_ids = low_array_column($spreader_orders, 'order_id');

        $spreader_yj_amounts = array();
        
        foreach ($spreader_orders as $s_o_k => $s_o_v) {
            if (isset($spreader_yj_amounts[$s_o_v['order_id']])) {
                $spreader_yj_amounts[$s_o_v['order_id']] += $s_o_v['yj_amount']*1;
            }else{
                $spreader_yj_amounts[$s_o_v['order_id']] = $s_o_v['yj_amount']*1;
            }
        }

        // 去重
        $shop_order_ids = array_flip($shop_order_ids);
        $shop_order_ids = array_flip($shop_order_ids);
        $shop_order_ids = array_values($shop_order_ids);

        $return_data = array();
        if (is_array($sub_member_list) && !empty($sub_member_list)) {

            $shop_member_ids = low_array_column($sub_member_list, 'shop_member_id');

            // 去重
            $shop_member_ids = array_flip($shop_member_ids);
            $shop_member_ids = array_flip($shop_member_ids);
            $shop_member_ids = array_values($shop_member_ids);

            // 获取用户信息
            $model_member = Model('member');
            $member_condition['member_id'] = array('IN',$shop_member_ids);
            $shop_member_infos = $model_member->getMemberList($member_condition,'member_id,member_time,member_name');

            $ready_order_data = array();
            if (is_array($shop_order_ids) && !empty($shop_order_ids)) {
                // 根据订单id 获取对应的商城购买者用户
                $model_order = Model('order');
                $shop_order_condition['order_id'] = array('IN',$shop_order_ids);
                $shop_order_condition['buyer_id'] = array('IN',$shop_member_ids);
                $shop_order_list = $model_order->getOrderList($shop_order_condition,'','order_id,buyer_id');
                foreach ($shop_order_list as $s_o_l_k => $s_o_l_v) {
                    if (isset($spreader_yj_amounts[$s_o_l_v['order_id']])) {
                        $ready_order_data[$s_o_l_v['buyer_id']][$s_o_l_v['order_id']] = $spreader_yj_amounts[$s_o_l_v['order_id']];
                    }
                }
            }

            foreach ($shop_member_infos as $key => $value) {
                $member_total_yj_amount = array_sum($ready_order_data[$value['member_id']]);
                $value['yj_amount'] = $member_total_yj_amount ? $member_total_yj_amount : 0;
                $value['member_time'] = date('Y-m-d',$value['member_time']);
                $value['member_name'] = mb_substr($value['member_name'],0,1,'utf8').'******'.mb_substr($value['member_name'],-1,1,'utf8');
                $shop_member_infos[$key] = $value;
            }

            if (is_array($shop_member_infos) && !empty($shop_member_infos)) {
                $return_data = $shop_member_infos;
            }
        }

        $last_return = $return_data;


        output_data(array('list'=>$last_return),mobile_page($page_count));

    }


}

