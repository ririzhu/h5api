<?php
namespace app\v1\controller;

use app\v1\model\EvaluateGoods;
use app\v1\model\EvaluateStore;
use app\v1\model\Points;
use app\v1\model\SnsAlbum;
use app\v1\model\UserOrder;
use app\v1\model\VendorInfo;

class Comments extends Base
{
    public function __construct(){
        parent::__construct() ;
    }
    /**
     * 订单评价界面
     */
    public function index(){
        $order_id = intval(input('order_id'));
        if (!$order_id){
            //(lang('参数错误'),'index.php?app=userorder','html','error');
        }

        $model_order = new UserOrder();
        $model_store = new VendorInfo();
        $model_evaluate_goods = new EvaluateGoods();
        $model_evaluate_store = new EvaluateStore();
        //$model_evaluate_teacher = new evaluateTeacher();

        //获取订单信息
        //订单为'已收货'状态，并且未评论
        $order_info = $model_order->getOrderInfo(array('order_id' => $order_id));
        $order_info['evaluate_able'] = $model_order->getOrderOperateState('evaluation',$order_info);
        if (empty($order_info) || !$order_info['evaluate_able']){
            //showMsg(Language::get('订单信息错误'),'index.php?app=userorder','html','error');
        }

        //查询店铺信息
        $store_info = $model_store->getStoreInfoByID($order_info['vid']);
        if(empty($store_info)){
            lang('店铺信息错误');
        }

        //获取订单商品
        $field = "o.*,m.member_id,m.member_name,g.gc_id_1,g.course_type";
        $order_goods = $model_order->name('order_goods')->alias('o')->join(' bbc_member m','o.teacher=m.member_id')->join("bbc_goods g","o.gid=g.gid")->field($field)->where(array('o.order_id'=>$order_id))->select();
        //******************************************************************************************************

        // // 获取最终价格
        // $order_goods = Model('goods_activity')->rebuild_goods_data($order_goods,'pc');

        if(empty($order_goods)){
            //showMsg(Language::get('订单信息错误'),'index.php?app=userorder','html','error');
        }
        //判断是否为页面

            for ($i = 0, $j = count($order_goods); $i < $j; $i++) {
                $order_goods[$i]['goods_image_url'] = cthumb($order_goods[$i]['goods_image'], 60, $store_info['vid']);
            }
            //处理积分、经验值计算说明文字
            $ruleexplain = '';
            $exppoints_rule = Config("exppoints_rule")?unserialize(Config("exppoints_rule")):array();
            $exppoints_rule['exp_comments'] = intval($exppoints_rule['exp_comments']);
            $points_comments = intval(Config('points_comments'));
            if ($exppoints_rule['exp_comments'] > 0 || $points_comments > 0){
                $ruleexplain .= lang('评价完成将获得');
                if ($exppoints_rule['exp_comments'] > 0){
                    $ruleexplain .= (lang(' “').$exppoints_rule['exp_comments'].lang('经验值”'));
                }
                if ($points_comments > 0){
                    $ruleexplain .= (lang(' “').$points_comments.lang('积分”'));
                }
                $ruleexplain .= lang('。');
            }
            $data['ruleexplain'] = $ruleexplain;

            $model_sns_alumb = new SnsAlbum();
            $ac_id = $model_sns_alumb->getSnsAlbumClassDefault($_SESSION['member_id']);
            $data['ac_id'] = $ac_id;
            $data['order_info'] = $order_info;
            $data['order_goods'] = $order_goods;
            return json_encode($data,true);

    }
    /**
     * 订单添加评价
     */
    public function add(){
        $order_id = intval(input('order_id'));
        if (!$order_id){
            //(lang('参数错误'),'index.php?app=userorder','html','error');
        }
        if(!input("member_id")){
            $data['error_code'] = 10016;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }
        $model_order = new UserOrder();
        $model_store = new VendorInfo();
        $model_evaluate_goods = new EvaluateGoods();
        $model_evaluate_store = new EvaluateStore();
        //$model_evaluate_teacher = new evaluateTeacher();

        //获取订单信息
        //订单为'已收货'状态，并且未评论
        $order_info = $model_order->getOrderInfo(array('order_id' => $order_id));
        $order_info['evaluate_able'] = $model_order->getOrderOperateState('evaluation',$order_info);
        if (empty($order_info) || !$order_info['evaluate_able']){
            //showMsg(Language::get('订单信息错误'),'index.php?app=userorder','html','error');
        }

        //查询店铺信息
        $store_info = $model_store->getStoreInfoByID($order_info['vid']);
        if(empty($store_info)){
            lang('店铺信息错误');
        }

        //获取订单商品
        $field = "o.*,m.member_id,m.member_name,g.gc_id_1,g.course_type";
        $order_goods = $model_order->name('order_goods')->alias('o')->join(' bbc_member m','o.teacher=m.member_id')->join("bbc_goods g","o.gid=g.gid")->field($field)->where(array('o.order_id'=>$order_id))->select();
        //******************************************************************************************************

        // // 获取最终价格
        // $order_goods = Model('goods_activity')->rebuild_goods_data($order_goods,'pc');

        if(empty($order_goods)){
            //showMsg(Language::get('订单信息错误'),'index.php?app=userorder','html','error');
        }
            $evaluate_goods_array = array();
            $evaluate_teacher_array = array();
            foreach ($order_goods as $value){
                //如果未评分，默认为5分
                $evaluate_score = intval($_POST['goods'][$value['gid']]['score']);
                if($evaluate_score <= 0 || $evaluate_score > 5) {
                    $evaluate_score = 5;
                }
                //默认评语
                $evaluate_comment = $_POST['goods'][$value['gid']]['comment'];
                if(empty($evaluate_comment)) {
                    $evaluate_comment = lang('不错哦');
                }

                //老师评分
                $evaluate_t_score = intval($_POST['goods'][$value['gid']]['t_score']);
                if($evaluate_t_score <= 0 || $evaluate_t_score > 5) {
                    $evaluate_t_score = 5;
                }
                //默认评语
                $evaluate_t_comment = $_POST['goods'][$value['gid']]['t_comment'];
                if(empty($evaluate_t_comment)) {
                    $evaluate_t_comment = lang('不错哦');
                }

                $geval_image = '';
                if (isset($_POST['goods'][$value['rec_id']]['evaluate_image']) && is_array($_POST['goods'][$value['rec_id']]['evaluate_image'])) {
                    foreach ($_POST['goods'][$value['rec_id']]['evaluate_image'] as $val) {
                        if(!empty($val)) {
                            $geval_image .= $val . ',';
                        }
                    }
                }
                $geval_image = rtrim($geval_image, ',');
                $model_member = new \app\v1\model\User();
                $member_info = $model_member->getMemberInfo(array('member_id'=> input("member_id")));
                $memberName = $member_info['member_name'];

                $evaluate_goods_info = array();
                $evaluate_goods_info['geval_orderid'] = $order_id;
                $evaluate_goods_info['geval_orderno'] = $order_info['order_sn'];
                $evaluate_goods_info['geval_ordergoodsid'] = $value['rec_id'];
                $evaluate_goods_info['geval_goodsid'] = $value['gid'];
                $evaluate_goods_info['geval_goodsname'] = $value['goods_name'];
                $evaluate_goods_info['geval_goodsprice'] = $value['goods_price'];
                $evaluate_goods_info['geval_scores'] = $evaluate_score;
                $evaluate_goods_info['geval_content'] = $evaluate_comment;
                $evaluate_goods_info['geval_isanonymous'] = $_POST['anony']?1:0;
                $evaluate_goods_info['geval_addtime'] = TIMESTAMP;
                $evaluate_goods_info['geval_storeid'] = $store_info['vid'];
                $evaluate_goods_info['geval_storename'] = $store_info['store_name'];
                $evaluate_goods_info['geval_frommemberid'] = input("member_id");
                $evaluate_goods_info['geval_frommembername'] = $memberName;
                $evaluate_goods_info['geval_image'] = $geval_image;
                $evaluate_goods_array[] = $evaluate_goods_info;

                //添加老师评价****************************************************************************************
                $model_order = new UserOrder();
                $evaluate_teacher_info = array();
                $evaluate_teacher_info['teval_orderid'] = $order_id;
                $evaluate_teacher_info['teval_orderno'] = $order_info['order_sn'];
                $evaluate_teacher_info['teval_ordergoodsid'] = $value['rec_id'];
                $evaluate_teacher_info['teval_member_id'] = $_POST['goods'][$value['gid']]['member_id'];
                $evaluate_teacher_info['teval_teacher_name'] = $_POST['goods'][$value['gid']]['member_name'];
                $evaluate_teacher_info['teval_goods_name'] = $value['goods_name'];
                $evaluate_teacher_info['teval_goodsprice'] = $value['goods_price'];
                $evaluate_teacher_info['teval_scores'] = $evaluate_t_score;
                $evaluate_teacher_info['teval_content'] = $evaluate_t_comment;
                $evaluate_teacher_info['teval_addtime'] = TIMESTAMP;
                $evaluate_teacher_info['teval_storeid'] = $store_info['vid'];
                $evaluate_teacher_info['teval_storename'] = $store_info['store_name'];
                $evaluate_teacher_info['teval_frommemberid'] = input("member_id");
                $evaluate_teacher_info['teval_frommembername'] = $memberName;
                $evaluate_teacher_info['teval_image'] = $geval_image;
                $evaluate_teacher_array[] = $evaluate_teacher_info;
                //***************************************************************************************************

            }
            $model_evaluate_goods->addEvaluateGoodsArray($evaluate_goods_array);
            $user = new \app\v1\model\User();
            $memberId = input("member_id");
            $memberinfo = $user->getMemberInfo(array("member_id"=>$memberId));
            $member_name = $memberinfo['member_name'];
            //保存老师评价
            //$model_evaluate_teacher->insertAll($evaluate_teacher_array);
//            $res = $model_evaluate_teacher->getLastsql();

            $store_desccredit = intval($_POST['store_desccredit']);
            if($store_desccredit <= 0 || $store_desccredit > 5) {
                $store_desccredit= 5;
            }
            $store_servicecredit = intval($_POST['store_servicecredit']);
            if($store_servicecredit <= 0 || $store_servicecredit > 5) {
                $store_servicecredit = 5;
            }
            $store_deliverycredit = intval($_POST['store_deliverycredit']);
            if($store_deliverycredit <= 0 || $store_deliverycredit > 5) {
                $store_deliverycredit = 5;
            }

            if($order_goods[0]['course_type'] == 1){
                //添加店铺评价
                $evaluate_store_info = array();
                $evaluate_store_info['seval_orderid'] = $order_id;
                $evaluate_store_info['seval_orderno'] = $order_info['order_sn'];
                $evaluate_store_info['seval_addtime'] = time();
                $evaluate_store_info['seval_storeid'] = $store_info['vid'];
                $evaluate_store_info['seval_storename'] = $store_info['store_name'];
                $evaluate_store_info['seval_memberid'] = $memberId;
                $evaluate_store_info['seval_membername'] = $member_name;
                $evaluate_store_info['seval_desccredit'] = $store_desccredit;
                $evaluate_store_info['seval_servicecredit'] = $store_servicecredit;
                $evaluate_store_info['seval_deliverycredit'] = $store_deliverycredit;
                $model_evaluate_store->addEvaluateStore($evaluate_store_info);
            }

            //更新订单信息并记录订单日志
            $state = $model_order->editOrder(array('evaluation_state'=>1), array('order_id' => $order_id));
            $model_order->editOrderCommon(array('evaluation_time'=>TIMESTAMP), array('order_id' => $order_id));
            if ($state){
                $data = array();
                $data['order_id'] = $order_id;
                $data['log_role'] = 'buyer';
                $data['log_msg'] = lang('评价了交易');
                $model_order->addOrderLog($data);
            }

            //添加会员积分
            if ($GLOBALS['setting_config']['points_isuse'] == 1){
                $points_model = new Points();
                $points_model->savePointsLog('comments',array('pl_memberid'=>$memberId,'pl_membername'=>$member_name));
            }


            $data['error_code']=200;
            $data['message'] = lang("成功");
            return json_encode($data,true);

    }

    /**
     * 评价列表
     */
    public function lists(){
        $model_evaluate_goods = Model('evaluate_goods');

        $condition = array();
        $condition['geval_frommemberid'] = $_SESSION['member_id'];
        $goodsevallist = $model_evaluate_goods->getEvaluateGoodsList($condition, 10, 'geval_id desc');
        Template::output('goodsevallist',$goodsevallist);
        Template::output('show_page',$model_evaluate_goods->showpage());

        $this->get_member_info();
        Template::output('menu_sign','evaluatemanage');
        Template::output('menu_sign_url','index.php?app=comments');
        Template::showpage('evaluation.index');
    }

    public function add_image() {
        $geval_id = intval($_GET['geval_id']);

        $model_evaluate_goods = Model('evaluate_goods');
        $model_goods = Model('goods');
        $model_sns_alumb = Model('sns_album');

        $geval_info = $model_evaluate_goods->getEvaluateGoodsInfoByID($geval_id);

        if(!empty($geval_info['geval_image'])) {
            showMsg(Language::get('该商品已经发表过晒单'), '', '', 'error');
        }

        if($geval_info['geval_frommemberid'] != $_SESSION['member_id']) {
            showMsg(L('参数错误'), '', '', 'error');
        }
        Template::output('geval_info', $geval_info);

        $goods_info = $model_goods->getGoodsInfo(array('gid' => $geval_info['geval_goodsid']));
        Template::output('goods_info', $goods_info);

        $ac_id = $model_sns_alumb->getSnsAlbumClassDefault($_SESSION['member_id']);
        Template::output('acid', $ac_id);

        //不显示左菜单
        Template::output('left_show','order_view');
        Template::showpage('evaluation.add_image');
    }

    public function add_image_save() {
        $geval_id = intval($_POST['geval_id']);
        $geval_image = '';
        foreach ($_POST['evaluate_image'] as $value) {
            if(!empty($value)) {
                $geval_image .= $value . ',';
            }
        }
        $geval_image = rtrim($geval_image, ',');

        $model_evaluate_goods = Model('evaluate_goods');

        $geval_info = $model_evaluate_goods->getEvaluateGoodsInfoByID($geval_id);
        if(empty($geval_info)) {
            showDialog(L('参数错误'));
        }

        $update = array();
        $update['geval_image'] = $geval_image;
        $condition = array();
        $condition['geval_id'] = $geval_id;
        $result = $model_evaluate_goods->editEvaluateGoods($update, $condition);

        list($sns_image) = explode(',', $geval_image);
        $goods_url = urlShop('goods', 'index', array('gid' => $geval_info['geval_goodsid']));
        //同步到sns
        $content = "
            <div class='fd-media'>
            <div class='goodsimg'><a target=\"_blank\" href=\"{$goods_url}\"><img src=\"".snsThumb($sns_image, 240)."\" title=\"{$geval_info['geval_goodsname']}\" alt=\"{$geval_info['geval_goodsname']}\"></a></div>
            <div class='goodsinfo'>
            <dl>
            <dt><a target=\"_blank\" href=\"{$goods_url}\">{$geval_info['geval_goodsname']}</a></dt>
            <dd>".Language::get('价格').Language::get('：').Language::get('&yen;').$geval_info['geval_goodsprice']."</dd>
            <dd><a target=\"_blank\" href=\"{$goods_url}\">".Language::get('去看看')."</a></dd>
            </dl>
            </div>
            </div>
            ";

        $tracelog_model = Model('sns_tracelog');
        $insert_arr = array();
        $insert_arr['trace_originalid'] = '0';
        $insert_arr['trace_originalmemberid'] = '0';
        $insert_arr['trace_memberid'] = $_SESSION['member_id'];
        $insert_arr['trace_membername'] = $_SESSION['member_name'];
        $insert_arr['trace_memberavatar'] = $_SESSION['member_avatar'];
        $insert_arr['trace_title'] = Language::get('发表了商品晒单');
        $insert_arr['trace_content'] = $content;
        $insert_arr['trace_addtime'] = TIMESTAMP;
        $insert_arr['trace_state'] = '0';
        $insert_arr['trace_privacy'] = 0;
        $insert_arr['trace_commentcount'] = 0;
        $insert_arr['trace_copycount'] = 0;
        $insert_arr['trace_from'] = '1';
        $result = $tracelog_model->tracelogAdd($insert_arr);

        if($result) {
            showDialog(L('保存成功'), urlShop('comments', 'lists'), 'succ');
        } else {
            showDialog(L('保存成功'), urlShop('comments', 'lists'));
        }
    }
}