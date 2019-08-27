<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/10/25
 * Time: 16:21
 */
class commonCtl{

    protected $dian_info = array();

    public function __construct()
    {
            if(!(C('sld_cashersystem') || C('sld_ldjsystem'))){
                echo json_encode(['status'=>255,'msg'=>'该模块不存在,请联系平台运营商后再试']);die;
            }
            if(!(C('cashersystem_isuse') || C('ldj_isuse'))){
                echo json_encode(['status'=>255,'msg'=>'平台未开启该产品,请联系平台运营商后再试']);die;
            }

    }

    // 行业 列表(登录时选择)
    public function industryClassList()
    {
        $state = 200;
        $data = '';
        $message = 'success';

        $data = array(
            array(
                "id" => 1,
                "name" => "餐饮行业"
            ),
            array(
                "id" => 2,
                "name" => "零售行业"
            )
        );

        $return_last = array(
            'state' => $state,
            'data' => $data,
            'msg' => $message,
        );

        echo json_encode($return_last);
    }
    /**
     * @api {get} index.php?app=common&mod=statistics&sld_addons=common 统计首页
     * @apiVersion 0.1.0
     * @apiName statistics
     * @apiGroup App
     * @apiDescription 首页统计
     * @apiExample 请求地址:
     * curl -i http://site2.slodon.cn/bmobile/index.php?app=common&mod=statistics&sld_addons=common
     * @apiParam {String} key 店铺登录key值
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} data 订单信息
     * @apiSuccessExample {json} 成功的例子:
     *       {
     *               "status": 200,
     *               "data": {
     *               "goods_info": {
     *                               "goods_all_num": "9",
     *                               "normal_num": "6",
     *                               "normal_stock": "5195"
     *                       },
     *               "member_info": {
     *                               "cash_num": "0",
     *                               "member_num": "0"
     *                       },
     *               "order_info": {
     *                               "today": {
     *                                               "order_num": 0,
     *                                               "order_amount": 0
     *                                       },
     *                               "toweek": {
     *                                               "order_num": 1,
     *                                               "order_amount": 216
     *                                       },
     *                               "tomonth": {
     *                                               "order_num": 1,
     *                                               "order_amount": 216
     *                                       }
     *                               }
     *               }
     *       }
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "请登录"
     *      }
     *
     */
    /*
     *  首页统计
     */
    public function statistics()
    {

        $this->checkToken();

        $dian_id = $this->dian_info['id'];
        $common_model = M('common_goods','common');
        $return_data = [];
        //商品统计
        $goods_all_num =  $common_model->table('dian_goods,goods')->join('inner')->on('dian_goods.goods_id=goods.gid')->where(['dian_goods.dian_id'=>$dian_id,'goods.goods_state'=>1,'goods.goods_verify'=>1])->field('count(goods_id) as goods_num')->find();
        $normal_num = $common_model->table('dian_goods,goods')->join('inner')->on('dian_goods.goods_id=goods.gid')->where(['dian_goods.dian_id'=>$dian_id,'dian_goods.stock'=>['gt',0],'dian_goods.off'=>0,"delete"=>['exp',' `delete` = 0'],'goods.goods_state'=>1,'goods.goods_verify'=>1])->field('count(goods_id) as goods_num,sum(stock) as stock')->find();
        //组装
        $goods_info = [
            'goods_all_num'=>$goods_all_num['goods_num']?:0,
            'normal_num'=>$normal_num['goods_num']?:0,
            'normal_stock'=>$normal_num['stock']?:0,
        ];
        $return_data['goods_info'] = $goods_info;
        //人员统计
        $cash_num = $common_model->table('cashsys_users')->where(['dian_id'=>$dian_id,'is_leader'=>0])->field('count(id) as cash_num')->find();
        $member_num = $common_model->table('cashsys_member_common')->where(['dian_id'=>$dian_id])->field('count(member_id) as member_num')->find();
        //组装
        $member_info = [
            'cash_num'=>$cash_num['cash_num'],
            'member_num'=>$member_num['member_num']
        ];
        $return_data['member_info'] = $member_info;

        //订单
        $common_order_model = M('common_order','common');

        //计算时间
        $time = time();
        $today['start_time'] = strtotime('today');
        $today['end_time'] = $time;
        $time_condition['today'] = $today;
        $now_week = intval(date('w',$time));
        if($now_week == 0){
            $now_week = 7;
        }
        $toweek['start_time'] = strtotime('today')-($now_week-1)*(24*60*60);
        $toweek['end_time'] = $time;
        $time_condition['toweek'] = $toweek;
        $tomonth['start_time'] = strtotime(date('Y-m-1',$time));
        $tomonth['end_time'] = $time;
        $time_condition['tomonth'] = $tomonth;
        $ldj_order = [];
        $cash_order = [];
        $shop_order = [];
        foreach($time_condition as $k=>$v){
            //联到家
            $condition = [
                'vid'=>$dian_id,
                'add_time'=>['time',[$v['start_time'],$v['end_time']]],
                'order_state'=>['in','20,30,40']
            ];
            $field = 'count(order_id) as order_num,sum(order_amount) as order_amount';
            $ldj_order[$k] = $common_order_model->order_info($condition,$field);
            //收银订单
            $condition = [
                'dian_id'=>$dian_id,
                'add_time'=>['time',[$v['start_time'],$v['end_time']]],
                'order_state'=>20
            ];
            $field = 'count(order_id) as order_num,sum(order_amount) as order_amount';
            $cash_order[$k] = $common_order_model->cash_order_info($condition,$field);
            //门店订单
            $condition = [
                'dian_id'=>$dian_id,
                'add_time'=>['time',[$v['start_time'],$v['end_time']]],
                'order_state'=>['in','20,30,40'],
                'refund_state'=>0,
                'lock_state'=>0
            ];
            $field = 'count(order_id) as order_num,sum(order_amount) as order_amount';
            $shop_order[$k] = $common_order_model->shop_order_info($condition,$field);
        }
        $index_time = ['today','toweek','tomonth'];
        foreach ($index_time as $kk=>$vv) {
            $data[$vv]['order_num'] = $ldj_order[$vv]['order_num']+$cash_order[$vv]['order_num']+$shop_order[$vv]['order_num'];
            $data[$vv]['order_amount'] = $ldj_order[$vv]['order_amount']+$cash_order[$vv]['order_amount']+$shop_order[$vv]['order_amount'];
        }
        $return_data['order_info'] = $data;
        echo json_encode(['status'=>200,'data'=>$return_data]);die;
    }
    // 店铺分类 1级列表
    public function vendorCateList()
    {

        // 验证token 是否有效
        $this->checkToken();

        $state = 200;
        $data = '';
        $message = 'success';

        $dian_id = $this->dian_info['id'];

        if(empty($dian_id)) {
            $state = 255;
            $message = '参数错误';
        }

        $vid = 0;

        // 根据dian_id 获取vid
        $dian_info = Model('dian')->getDianInfoByID('',$dian_id);
        if (isset($dian_info['vid']) && $dian_info['vid'] > 0) {
            $vid = $dian_info['vid'];
        }

        if ($vid) {
            $state = 200;
            // 实例化店铺商品分类模型
            $data = $store_goods_class = Model('my_goods_class')->getTreeClassList ( array (
                'vid' => $vid,
                'stc_state' => '1'
            ),1 );
        }

        $return_last = array(
            'state' => $state,
            'data' => $data,
            'msg' => $message,
        );

        echo json_encode($return_last);
    }

    // 获取支付方式
    public function getPaymentList()
    {
        $state = 200;
        $data = '';
        $message = 'success';

        $model_payment = M('cashsys_payment','cashersystem');
        $payment_condition['payment_state'] = 1;
        $data = $payment_list = $model_payment->getPaymentList($payment_condition);

        if(!empty($payment_list)){
            foreach ($payment_list as $key => $val){
                if($val['payment_config'] != ''){
                    $payment_list[$key]['payment_config'] = unserialize($val['payment_config']);
                }
            }
        }

        $return_last = array(
            'state' => $state,
            'data' => $data,
            'msg' => $message,
        );

        echo json_encode($return_last);

    }

    // 登录
    public function login()
    {
        $state = 200;
        $data = '';
        $message = 'success';

        $user_name = trim($_POST['username']);
        $password = trim($_POST['password']);
        $client = trim($_POST['client']);
        $choose_industry = intval($_POST['industry']);
        $model_cashsys_users = M('cashsys_users','common');
        if(empty($user_name) || empty($password) || empty($choose_industry)) {
            echo json_encode(['status'=>255,'msg'=>'请输入账号名或密码']);die;
        }
                //登录判断
                $array = array();
                $array['casher_name|casher_phone']   = $user_name;
                $array['casher_pwd'] = md5($password);

                $casher_info = $model_cashsys_users->getCashsysUsersInfo($array);
                if(!$casher_info){
                    echo json_encode(['status'=>255,'msg'=>'账号或密码不正确请重新输入']);die;
                }
                $model = model();
                //检测总店铺是否被平台关闭
                $status = $model->table('vendor')->where(['vid'=>$casher_info['vid']])->field('store_state')->find();
                if($status['store_state'] != 1){
                    echo json_encode(['status'=>255,'msg'=>'您的门店所属店铺已被关闭,请联系平台运营商后再试']);die;
                }
                $token = $this->_get_token($casher_info['id'], $casher_info['casher_name'],$client);
                if(!$token){
                    echo json_encode(['status'=>255,'msg'=>'登录失败1']);die;
                }
                        // 获取门店名称
                        $dian_info = Model('dian')->getDianInfoByID('',$casher_info['dian_id']);
                        if(!$dian_info){
                            echo json_encode(['status'=>255,'msg'=>'暂无相关门店']);die;
                        }
                        $casher_info['dian_name'] = $dian_info['dian_name'];
                        $data = array(
                            'casher_id' => $casher_info['id'],
                            'casher_name' => $casher_info['casher_name'],
                            'is_leader' => $casher_info['is_leader'],
                            'dian_id' => $casher_info['dian_id'],
                            'dian_name' => $casher_info['dian_name'],
                            'casher_phone' => $casher_info['casher_phone'],
                            'vid' => $casher_info['vid'],
                            'token' => $token,
                            'industry' => $choose_industry
                        );
                        // 更新当前收银员的最后一次登录时间
                        $last_update_data['last_login_time'] = time();
                        $last_update_condition['id'] = $casher_info['id'];
                        $editres = $model_cashsys_users->editCasherData($last_update_data,$last_update_condition);

                        if(!$editres){
                                echo json_encode(['status'=>255,'msg'=>$last_update_condition['id']]);die;
                        }
                        // 添加登录日志
                        $log_data['user_id'] = $casher_info['id'];
                        $log_data['log_type'] = 'login';
                        $log_data['log_time'] = time();
                        $editres = $model_cashsys_users->addCasherLoginLog($log_data);
                        if(!$editres){
                            echo json_encode(['status'=>255,'msg'=>'登录失败3']);die;
                        }
//            }
//            else{
//                //门店登录
//                $array = array();
//                $array['casher_name|casher_phone']   = $user_name;
//                $array['casher_pwd'] = md5($password);
//                $casher_info = $model_cashsys_users->getCashsysUsersInfo($array);
//                if(!$casher_info){
//                    echo json_encode(['status'=>255,'msg'=>'用户名密码错误']);die;
//                }
//                //检测门店联到家是否被关闭
//                $status = $model_cashsys_users->table('dian')->where(['id'=>$casher_info['dian_id']])->field('ldj_status')->find();
//                if(!$status){
//                    echo json_encode(['status'=>255,'msg'=>'门店不存在']);die;
//                }
//                if(!$status['ldj_status']){
//                    echo json_encode(['status'=>255,'msg'=>'门店已关闭']);die;
//                }
//                //检测总店铺是否被平台关闭
//                $status = $model_cashsys_users->table('vendor')->where(['vid'=>$casher_info['vid']])->field('store_state')->find();
//                if($status['store_state'] != 1){
//                    echo json_encode(['status'=>255,'msg'=>'所属店铺已关闭']);die;
//                }
//                $token = $this->_get_token($casher_info['id'], $casher_info['casher_name'],$client);
//                if(!$token){
//                    echo json_encode(['status'=>255,'msg'=>'登录失败']);die;
//                }
//
//                // 获取门店名称
//                $dian_info = Model('dian')->getDianInfoByID('',$casher_info['dian_id']);
//                $casher_info['dian_name'] = $dian_info['dian_name'];
//                $state = 200;
//                $data = array(
//                    'casher_id' => $casher_info['id'],
//                    'casher_name' => $casher_info['casher_name'],
//                    'login_type' => $type,
//                    'is_leader' => $casher_info['is_leader'],
//                    'dian_id' => $casher_info['dian_id'],
//                    'dian_name' => $casher_info['dian_name'],
//                    'casher_phone' => $casher_info['casher_phone'],
//                    'vid' => $casher_info['vid'],
//                    'token' => $token,
//                    'industry' => $choose_industry
//                );
//                // 更新当前登录用户的最后一次登录时间
//                $last_update_data['last_login_time'] = time();
//                $last_update_condition['id'] = $casher_info['id'];
//                $model_cashsys_users->editCasherData($last_update_data,$last_update_condition);
//                // 添加登录日志
//                $log_data['user_id'] = $casher_info['id'];
//                $log_data['log_type'] = 'login';
//                $log_data['log_time'] = time();
//                $model_cashsys_users->addCasherLoginLog($log_data);
//                $state = 200;
//                $message = '登录成功';
//            }
            $status = $model_cashsys_users->table('dian')->where(['id'=>$data['dian_id']])->field('cash_status,ldj_status')->find();
            //如果是店长登录
            if($data['is_leader']){
                $res = ((C('sld_cashersystem') && C('cashersystem_isuse') && $status['cash_status'])) || ((C('sld_ldjsystem') && C('ldj_isuse') && $status['ldj_status']));
            }else{
                //店员登录
                $res = $status['cash_status'] && C('sld_cashersystem') && C('cashersystem_isuse');
            }
            if(!$res){
                echo json_encode(['status'=>255,'msg'=>'您的门店未购买本产品,请联系店铺运营商后再试']);die;
            }
            //判断返回状态
            $type = 0;
            if(C('sld_cashersystem') && C('cashersystem_isuse') && $status['cash_status']){
                $type = 1;
            }
            if(C('sld_ldjsystem') && C('ldj_isuse') && $status['ldj_status']){
                $type = 2;
            }
            if(C('sld_cashersystem') && C('cashersystem_isuse') && $status['cash_status'] && C('sld_ldjsystem') && C('ldj_isuse')&& $status['ldj_status']){
                $type = 3;
            }
            $data['login_type'] =  $type;
        $return_last = array(
            'state' => 200,
            'data' => $data,
            'msg' => '登录成功',
        );
        echo json_encode($return_last);
    }


    // 登出
    public function logout()
    {
        $state = 200;
        $data = '';
        $message = 'success';
        $model_cashsys_token = M('cashsys_token','common');
        $token = trim($_POST['token']);
        $client = trim($_POST['client']);

        if(empty($token) || empty($client)) {
            $state = 255;
            $message = '参数错误';
        }
        $cashsys_token_info = $model_cashsys_token->table('cashsys_user_token')->where(['token'=>$token,'client_type'=>$client])->find();
        if(!$cashsys_token_info){
                echo json_encode(['status' => 200,
                    'data' => [],
                    'msg' =>'注销成功']);die;
        }
            $condition = array();
            $condition['client_type'] = $client;
            //账号退出的时候只删除当前的key
            $condition['token'] = $token;
            $clearFlag = $model_cashsys_token->delToken($condition);


        if ($clearFlag) {
            $state = 200;
            $message = '注销成功';

            $model_cashsys_users = M('cashsys_users','common');
            // 更新当前收银员的最后一次登录时间
            $last_update_data['last_logout_time'] = time();
            $last_update_condition['id'] = $cashsys_token_info['casher_id'];
            $model_cashsys_users->editCasherData($last_update_data,$last_update_condition);
            // 添加登录日志
            $log_data['user_id'] = $cashsys_token_info['casher_id'];
            $log_data['log_type'] = 'logout';
            $log_data['log_time'] = time();
            $model_cashsys_users->addCasherLoginLog($log_data);
        }else{
            $state = 255;
            $message = '注销失败';
        }

        $return_last = array(
            'status' => $state,
            'data' => $data,
            'msg' => $message,
        );

        echo json_encode($return_last);
    }

    /**
     * 收银登录生成token
     */
    private function _get_token($casher_id, $casher_name,$client) {
        $model_cashsys_token = M('cashsys_token','common');

        //生成新的token
        $cashsys_token_info = array();
        $token = md5($casher_name . strval(TIMESTAMP) . strval(rand(0,999999)));
        $cashsys_token_info['casher_id'] = $casher_id;
        $cashsys_token_info['casher_name'] = $casher_name;
        $cashsys_token_info['token'] = $token;
        $cashsys_token_info['login_time'] = TIMESTAMP;
        $cashsys_token_info['client_type'] = $client;

        $result = $model_cashsys_token->addToken($cashsys_token_info);

        if($result) {
            return $token;
        } else {
            return null;
        }

    }
    /**
     * 懒到家登录生成token
     */
    private function _ldj_get_token($member_id, $member_name,$client) {
        $model = model();

        //生成新的token
        $token_info = array();
        $token = md5($member_name . strval(TIMESTAMP) . strval(rand(0,999999)));
        $token_info['member_id'] = $member_id;
        $token_info['member_name'] = $member_name;
        $token_info['token'] = $token;
        $token_info['login_time'] = TIMESTAMP;
        $token_info['client_type'] = $client;

        $result = $model->table('dian_user_token')->insert($token_info);

        if($result) {
            return $token;
        } else {
            return null;
        }

    }

    // 校验token
    public function checkToken()
    {
        //登录信息
        if($_POST['key']){
            $key = trim($_POST['key']);
        }else{
            $key = trim($_GET['key']);
        }
        if(!$key){
            echo json_encode(['status'=>255,'msg'=>Language::get('请登录')]);die;
        }

            $token_model = M('cashsys_token','common');
            $token_info = $token_model->getTokenInfoByToken($key);
            if(!$token_info){
                echo json_encode(['status'=>255,'msg'=>Language::get('请登录')]);die;
            }
            $cash_info = $token_model->table('cashsys_users')->where(['id'=>$token_info['casher_id']])->find();
            if(!$cash_info){
                echo json_encode(['status'=>255,'msg'=>Language::get('请登录')]);die;
            }
            $dian_info = $token_model->table('dian')->where(['id'=>$cash_info['dian_id']])->find();
            if(!$dian_info){
                echo json_encode(['status'=>255,'msg'=>'门店不存在']);die;
            }

        if($cash_info['is_leader']){
            if(! (
                (C('sld_cashersystem') && C('cashersystem_isuse') && $dian_info['cash_status'])
                ||
                (C('sld_ldjsystem') && C('ldj_isuse') && $dian_info['ldj_status'])
            )){
                echo json_encode(['status'=>255,'msg'=>'该功能已关闭']);die;
            }
        }else{
            if(! (C('sld_cashersystem') && C('cashersystem_isuse') && $dian_info['cash_status'])){
                echo json_encode(['status'=>255,'msg'=>'该功能已关闭']);die;
            }
        }
            $this->dian_info =  $dian_info;
    }

}