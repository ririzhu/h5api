<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/10/24
 * Time: 16:53
 */
class goodsCtl{

    protected $casher_info = array();

    public function __construct()
    {

            if(!((C('sld_cashersystem') && C('cashersystem_isuse')) || (C('sld_ldjsystem') && C('ldj_isuse')))){
                echo json_encode(['status'=>255,'msg'=>'当前模块未开启']);die;
        }

        // 验证token 是否有效
        $this->checkToken();

    }

    // 商品列表,收银员添加商品结算,暂时用不到
    public function getGoodsList()
    {
        $dian_id = $this->casher_info['dian_id'];
        $cate_id = isset($_GET['cate_id']) ? intval($_GET['cate_id']) : 0;
        $search_val = isset($_GET['search']) ? trim($_GET['search']) : '';
        $pageSize = intval($_GET['pageSize'] > 0 ? $_GET['pageSize'] : 10);
        $_GET['pn'] = intval($_GET['currentPage'] > 0 ? $_GET['currentPage'] : 1);

        $state = 200;
        $data = '';
        $message = 'success';

        $model_goods = M('cashsys_goods','common');
        if ($search_val) {
            $condition['goods.goods_name|goods.goods_barcode|goods.goods_serial'] = array("LIKE","%".$search_val."%");
        }
        if ($cate_id) {
            $son_condition['goods_stcids_1'] = array('LIKE','%,'.$cate_id.',%');
            $cate_goods_ids = $model_goods->table('cashsys_goods_extend')->where($son_condition)->field('goods_id')->select();
            $cate_goods_ids = low_array_column($cate_goods_ids,'goods_id');
            $condition['gid'] = array("IN", $cate_goods_ids);
        }
        $condition['dian_goods.dian_id'] = $dian_id;
        $condition['goods.goods_state']= 1;
        $condition['goods.goods_verify']= 1;
        $condition['dian_goods.delete'] = 0 ;

        $page_list = $model_goods->getGoodsList($condition,'*',$pageSize,'goods.gid');

        foreach ($page_list as $key => $value) {
            $value['goods_image_url'] = cthumb($value['goods_image']);
            $page_list[$key] = $value;
        }

        if (!empty($page_list)) {

            // 获取最终价格
            $page_list = Model('goods_activity')->rebuild_goods_data($page_list);

            $data = array(
                'list' => $page_list,
                'pagination' => array(
                    'current' => $_GET['pn'],
                    'pageSize' => $pageSize,
                    'total' => intval($model_goods->gettotalnum()),
                ),
            );
        }else{
            $state = 255;
            $data = '';
            $message = Language::get('没有数据');
        }

        $return_last = array(
            'state' => $state,
            'data' => $data,
            'msg' => $message,
        );

        echo json_encode($return_last);

    }
    /**
     * @api {get} index.php?app=goods&mod=getStoreGoodsList&sld_addons=common 店铺商品列表（用于店长添加商品至门店)
     * @apiVersion 0.1.0
     * @apiName getStoreGoodsList
     * @apiGroup App
     * @apiDescription 店铺商品列表（用于店长添加商品至门店)
     * @apiExample 请求地址:
     * curl -i http://site2.slodon.cn/bmobile/index.php?app=goods&mod=getStoreGoodsList&sld_addons=common
     * @apiParam {String} key 店铺登录key值
     * @apiParam {Number} cate_id 分类id
     * @apiParam {String} search 搜索词
     * @apiParam {Number} pageSize 当前页显示数量
     * @apiParam {Number} currentPage 当前第几页
     * @apiSuccess {Number} state 状态
     * @apiSuccess {Json} data 商品信息
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "state":200,
     *           data:{
     *                list:{
     *                       商品列表...
     *                  }
     *               pagination:{
     *                               current:2,
     *                               pageSize:10,
     *                               total:5,
     *                      }
     *                  }
     *
     *      }
     *
     * @apiError {Number} state 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "state":255,
     *          "data":[],
     *          "msg": 商品不存在
     *      }
     *      /
     *      {
     *                   "code": 200,
     *                   "login": "0",
     *                   "datas": {
     *                   "error": "请登录"
     *                   }
     *     }
     *
     */
    public function getStoreGoodsList()
    {
        $cate_id = isset($_GET['cate_id']) ? intval($_GET['cate_id']) : 0;
        $search_val = isset($_GET['search']) ? trim($_GET['search']) : '';
        $pageSize = intval($_GET['pageSize'] > 0 ? $_GET['pageSize'] : 10);
        $_GET['pn'] = intval($_GET['currentPage'] > 0 ? $_GET['currentPage'] : 1);

        $state = 200;
        $data = '';
        $message = 'success';

        // 获取 商品 列表
        $model_goods = Model('goods');
        $condition['vid'] = $this->casher_info['vid'];
        if ($search_val) {
            $condition['gid|goods_name|goods_barcode'] = array("LIKE","%".$search_val."%");
        }

        // 获取已添加到门店的商品ID集合 用于过滤
        $dian_goods_ids = array();
        $model_cashsys_goods = M('cashsys_goods','common');
        $dian_goods_condition['dian_goods.dian_id'] = $this->casher_info['dian_id'];
        $dian_goods_condition['goods.goods_state']= 1;
        $dian_goods_condition['goods.goods_verify']= 1;
        $dian_goods_list = $model_cashsys_goods->getGoodsList($dian_goods_condition,'goods.gid',0);
        $dian_goods_ids = low_array_column($dian_goods_list,'gid');

        $gid_condition_value = array();
        if ($cate_id) {
            $son_condition['goods_stcids_1'] = array('LIKE','%,'.$cate_id.',%');
            $cate_goods_ids = $model_goods->table('cashsys_goods_extend')->where($son_condition)->field('goods_id')->select();
            $cate_goods_ids = low_array_column($cate_goods_ids,'goods_id');
            $condition['gid'] = array("IN", $cate_goods_ids);
        }
        if (!empty($dian_goods_ids)) {
            $condition['bbc_goods.gid'] = array("NOT IN", $dian_goods_ids);
        }

        $goods_list = $model_goods->getGoodsOnlineList($condition,'*',$pageSize);

        if (!empty($goods_list)) {
            foreach ($goods_list as $key => $value) {
                $value['goods_image_url'] = cthumb($value['goods_image']);
                $goods_list[$key] = $value;
            }

            // 获取最终价格
            $goods_list = Model('goods_activity')->rebuild_goods_data($goods_list);

            $data = array(
                'list' => $goods_list,
                'pagination' => array(
                    'current' => $_GET['pn'],
                    'pageSize' => $pageSize,
                    'total' => intval($model_goods->gettotalnum()),
                ),
            );
        }else{
            $state = 255;
            $data = [];
            $message = Language::get('没有数据');
        }

        $return_last = array(
            'state' => $state,
            'data' => $data,
            'msg' => $message,
        );

        echo json_encode($return_last);
    }
    /**
     * @api {post} index.php?app=goods&mod=addGoodsToDian&sld_addons=common 添加商品至门店
     * @apiVersion 0.1.0
     * @apiName addGoodsToDian
     * @apiGroup App
     * @apiDescription 添加商品至门店
     * @apiExample 请求地址:
     * curl -i http://site2.slodon.cn/bmobile/index.php?app=goods&mod=addGoodsToDian&sld_addons=common
     * @apiParam {String} key 店铺登录key值
     * @apiParam {String} ids 商品id,可多个 id,如1,2,3
     * @apiSuccess {Number} state 状态
     * @apiSuccess {Json} data 商品信息
     * @apiSuccess {String} msg 信息说明
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "state":200,
     *           data:[],
     *          msg:success
     *
     *      }
     *
     * @apiError {Number} state 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "state":255,
     *          "data":[],
     *          "msg": 商品不存在
     *      }
     *      /
     *      {
     *                   "code": 200,
     *                   "login": "0",
     *                   "datas": {
     *                   "error": "请登录"
     *                   }
     *     }
     *
     */
    public function addGoodsToDian()
    {
        $ids = $_POST['ids'];

        $state = 200;
        $data = '';
        $message = 'success';

        if (!empty($ids)) {
            Model('dian_goods')->addGoodsAll($this->casher_info['dian_id'],$ids,'dian_goods',true);
        }

        $return_last = array(
            'state' => $state,
            'data' => $data,
            'msg' => $message,
        );

        echo json_encode($return_last);

    }
    /**
     * @api {get} index.php?app=goods&mod=getGoodsListOfDian&sld_addons=common 门店商品列表
     * @apiVersion 0.1.0
     * @apiName getGoodsListOfDian
     * @apiGroup App
     * @apiDescription 门店商品列表
     * @apiExample 请求地址:
     * curl -i http://site2.slodon.cn/bmobile/index.php?app=goods&mod=getGoodsListOfDian&sld_addons=common
     * @apiParam {String} key 店铺登录key值
     * @apiParam {String} state 上架0/下架1,不写表示全部
     * @apiParam {String} search 搜索词
     * @apiParam {Number} pageSize 当前页显示数量
     * @apiParam {Number} currentPage 当前第几页
     * @apiSuccess {Number} state 状态
     * @apiSuccess {Json} data 商品信息
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "state":200,
     *           data:{
     *                list:{
     *                       商品列表...
     *                  }
     *               pagination:{
     *                               current:2,
     *                               pageSize:10,
     *                               total:5,
     *                      }
     *                  }
     *
     *      }
     *
     * @apiError {Number} state 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "state":255,
     *          "data":[],
     *          "msg": 商品不存在
     *      }
     *      /
     *      {
     *                   "code": 200,
     *                   "login": "0",
     *                   "datas": {
     *                   "error": "请登录"
     *                   }
     *     }
     *
     */
    public function getGoodsListOfDian()
    {
        $dian_id = $this->casher_info['dian_id'];
        $search_val = isset($_GET['search']) ? trim($_GET['search']) : '';
        $pageSize = intval($_GET['pageSize'] > 0 ? $_GET['pageSize'] : 10);
        $_GET['pn'] = intval($_GET['currentPage'] > 0 ? $_GET['currentPage'] : 1);

        $state = 200;
        $data = '';
        $message = 'success';

        // 获取 商品 列表
        $model_goods = M('cashsys_goods','common');
        $condition['dian_goods.dian_id'] = $dian_id;
        $condition['goods.goods_state']= 1;
        $condition['goods.goods_verify']= 1;
        $condition['dian_goods.delete'] = 0 ;
        if(isset($_GET['state']) && is_numeric($_GET['state'])){
            $condition['dian_goods.off'] = intval($_GET['state']);
        }
        if ($search_val) {
            $condition['goods.gid|goods.goods_name|goods.goods_barcode'] = array("LIKE","%".$search_val."%");
        }

        $page_list = $model_goods->getGoodsList($condition,'*',$pageSize,'goods.gid');

        if (!empty($page_list)) {
            foreach ($page_list as $key => $value) {
                $value['goods_image_url'] = cthumb($value['goods_image']);
                $page_list[$key] = $value;
            }

            // 获取最终价格
            $page_list = Model('goods_activity')->rebuild_goods_data($page_list);

            $data = array(
                'list' => $page_list,
                'pagination' => array(
                    'current' => $_GET['pn'],
                    'pageSize' => $pageSize,
                    'total' => intval($model_goods->gettotalnum()),
                ),
            );
        }else{
            $state = 200;
            $data = array(
                'list' => [],
                'pagination' =>[]
            );;
            $message = Language::get('没有数据');
        }
        $return_last = array(
            'state' => $state,
            'data' => $data,
            'msg' => $message,
        );

        echo json_encode($return_last);

    }
    /**
     * @api {post} index.php?app=goods&mod=changeStock&sld_addons=common 更新门店商品库存
     * @apiVersion 0.1.0
     * @apiName changeStock
     * @apiGroup App
     * @apiDescription 更新门店商品库存
     * @apiExample 请求地址:
     * curl -i http://site2.slodon.cn/bmobile/index.php?app=goods&mod=changeStock&sld_addons=common
     * @apiParam {String} key 店铺登录key值
     * @apiParam {String} gid 商品id,如果多个请使用1,,5,6格式
     * @apiParam {Number} stock_val 库存数量
     * @apiSuccess {Number} state 状态
     * @apiSuccess {Json} data 商品信息
     * @apiSuccess {String} msg 信息说明
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "state":200,
     *           data:[],
     *          msg:操作成功
     *
     *      }
     *
     * @apiError {Number} state 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "state":255,
     *          "data":[],
     *          "msg": 操作失败
     *      }
     *      /
     *      {
     *                   "code": 200,
     *                   "login": "0",
     *                   "datas": {
     *                   "error": "请登录"
     *                   }
     *     }
     *
     */

    public function changeStock()
    {
        $gid = $_GET['gid'];
        if(is_numeric($gid)){
            $gid = intval($gid);
        }else{
            $gid = array_filter(explode(',',$gid));
        }
        $stock_val = isset($_GET['stock_val']) ? intval($_GET['stock_val']) : '';

        $run_flag = true;

        $state = 200;
        $data = '';
        $message = 'success';

        if ($stock_val < 0) {
            $state = 255;
            $message = '请填写库存数';
            $run_flag = false;
        }

        if ($run_flag && $gid) {
            $where['goods_id']=  ['in',$gid];
            $where['dian_id'] = $this->casher_info['dian_id'];

            $update = array('stock' => $stock_val);
            $return = Model('dian_goods')->editProducesNoLock($where, $update);
            if ($return) {
                // 添加操作日志
                $this->recordSellerLog('更改库存，商品编号：'.$gid);

                $state = 200;
                $message = '操作成功';
            } else {
                $state = 255;
                $message = '操作失败';
            }
        }

        $return_last = array(
            'state' => $state,
            'data' => $data,
            'msg' => $message,
        );

        echo json_encode($return_last);

    }

    public function dropGoods()
    {
        $gid = $_GET['gid'];

        $state = 200;
        $data = '';
        $message = 'success';

        if ($gid) {
            $where['goods_id']=  $gid;
            $where['dian_id'] = $this->casher_info['dian_id'];

            $update = array('`delete`' => 1);
            $return = Model('dian_goods')->editProducesNoLock($where, $update);
            if ($return) {
                // 添加操作日志
                $this->recordSellerLog('更改库存，商品编号：'.$gid);

                $state = 200;
                $message = '操作成功';
            } else {
                $state = 255;
                $message = '操作失败';
            }
        }

        $return_last = array(
            'state' => $state,
            'data' => $data,
            'msg' => $message,
        );

        echo json_encode($return_last);
    }

    protected function recordSellerLog($content = '', $state = 1){
        $vendorinfo = array();
        $vendorinfo['log_content'] = $content;
        $vendorinfo['log_time'] = TIMESTAMP;
        $vendorinfo['log_seller_id'] = $this->casher_info['id'];
        $vendorinfo['log_seller_name'] = $this->casher_info['casher_name'];
        $vendorinfo['log_vid'] = $this->casher_info['vid'];
        $vendorinfo['log_seller_ip'] = getIp();
        $vendorinfo['log_url'] = $_GET['app'].'&'.$_GET['mod'];
        $vendorinfo['log_state'] = $state;
        $model_vendor_log = Model('dian_log');
        $model_vendor_log->addSellerLog($vendorinfo);
    }
    /**
     * @api {get} index.php?app=goods&mod=editgoodsstate&sld_addons=common 商品下架下架删除
     * @apiVersion 0.1.0
     * @apiName editgoodsstate
     * @apiGroup App
     * @apiDescription 商品下架下架删除
     * @apiExample 请求地址:
     * curl -i http://site2.slodon.cn/bmobile/index.php?app=goods&mod=editgoodsstate&sld_addons=common
     * @apiParam {String} key 店铺登录key值
     * @apiParam {Number} gid 商品id,可以是多个gid,例如:21,22,23
     * @apiParam {Number} off 上架0/1下架
     * @apiParam {Number} del 删除:1
     * @apiSuccess {Number} status 状态
     * @apiSuccess {String} msg 订单信息
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200,
     *         "msg": "操作成功"
     *      }
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "操作失败"
     *      }
     *      /
     *      {
     *                   "code": 200,
     *                   "login": "0",
     *                   "datas": {
     *                   "error": "请登录"
     *                   }
     *           }
     *
     */
    public function editgoodsstate()
    {
        if(is_numeric($_GET['gid'])){
            $gid = intval($_GET['gid']);
        }else{
            $gid = trim($_GET['gid'],',');
        }

        $goods_model = M('common_goods','common');
        try{
            $update = [];
            if(isset($_GET['off'])){
                $update['off'] = $_GET['off']?1:0;
            }
            if(isset($_GET['del'])){
                $update['`delete`'] = $_GET['del']?1:0;
            }
            $res = $goods_model->table('dian_goods')->where(['goods_id'=>['in',$gid],'dian_id'=>$this->casher_info['dian_id']])->update($update);
            if(!$res){
                throw new Exception('操作失败');
            }
            echo json_encode(['status'=>200,'msg'=>'操作成功']);die;
        }catch(Exception $e){
            echo json_encode(['status'=>255,'msg'=>$e->getMessage()]);die;
        }
    }
    // 校验token
    public function checkToken()
    {
        $check_flag = true;
        // 校验token
        $token = $_REQUEST['token'];
        //如果收银开启
//        if(C('sld_cashersystem') && C('cashersystem_isuse')) {
            $model_cashsys_token = M('cashsys_token', 'common');
            $cashsys_token_info = $model_cashsys_token->getTokenInfoByToken($token);
            if (empty($cashsys_token_info)) {
                $check_flag = false;
            }

            $model_users = M('cashsys_users','common');
        $cash_info = $model_users->getCashsysUsersInfo(array('id' => $cashsys_token_info['casher_id']));
        $this->casher_info = $cash_info;
        $dian_info = $model_cashsys_token->table('dian')->where(['id'=> $this->casher_info['dian_id']])->find();
        if(!$dian_info){
            echo json_encode(['status'=>266,'msg'=>'门店不存在']);die;
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

            if (empty($this->casher_info)) {
                $check_flag = false;
            } else {
                $this->casher_info['token'] = $cashsys_token_info['token'];
            }
//        }
        if (!$check_flag) {
            $state = 255;
            $data = '';
            $message = Language::get('请登录');
            $return_last = array(
                'state' => $state,
                'data' => $data,
                'msg' => $message,
            );
            echo json_encode($return_last);exit;
        }
    }


}