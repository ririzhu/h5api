<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/11/24
 * Time: 14:32
 */
class pin_ladderCtl extends SystemCtl
{
    private $cate_model;
    private $model;
    private $state_list = [
        ['id'=>1,'name'=>'进行中'],
        ['id'=>2,'name'=>'已结束'],
        ['id'=>3,'name'=>'等待开始'],
    ];
    public function __construct()
    {
        parent::__construct();
        $this->cate_model = M('pin_category','pin_ladder');
        $this->model = Model();
    }
    /**
     * @api {get} index.php?app=pin_ladder&mod=category&sld_addons=pin_ladder 阶梯团购分类列表
     * @apiVersion 0.1.0
     * @apiName category
     * @apiGroup Admin
     * @apiDescription 阶梯团购分类列表
     * @apiExample 请求地址:
     * curl -i http://site7.55jimu.com/SystemManage/index.php?app=pin_ladder&mod=category&sld_addons=pin_ladder
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} data 信息
     * @apiSuccessExample {json} 成功的例子:
{
    "status": 200,
    "data": [
        {
            "id": "5",
            "class_name": "水果",
            "sort": "0",
            "huodong_num": "0"
        },
        {
            "id": "6",
            "class_name": "水果",
            "sort": "0",
            "huodong_num": "0"
        },
        {
            "id": "1",
            "class_name": "美食",
            "sort": "255",
            "huodong_num": "2"
        },
        {
            "id": "2",
            "class_name": "美妆",
            "sort": "255",
            "huodong_num": "0"
        },
        {
            "id": "3",
            "class_name": "美女",
            "sort": "255",
            "huodong_num": "1"
        },
        {
            "id": "4",
            "class_name": "美景",
            "sort": "255",
            "huodong_num": "1"
        }
    ]
}
     *
     */
    public function category()
    {
        $list = $this->cate_model->getlist();
        $list = $list?:[];
        foreach($list as $k=>$v){
            $list[$k]['huodong_num'] = $this->model->table('pin_ladder')->where(['sld_type'=>$v['id']])->count();
        }
        echo json_encode(['status'=>200,'data'=>$list]);die;
    }
    /**
     * @api {post} index.php?app=pin_ladder&mod=add&sld_addons=pin_ladder 阶梯团购添加分类
     * @apiVersion 0.1.0
     * @apiName add
     * @apiGroup Admin
     * @apiDescription 阶梯团购添加分类
     * @apiExample 请求地址:
     * curl -i http://site7.55jimu.com/SystemManage/index.php?app=pin_ladder&mod=add&sld_addons=pin_ladder
     * @apiParam {String} class_name 分类名称
     * @apiParam {Number} sort 排序
     * @apiParam {Number} is_show 是否显示
     * @apiSuccess {Number} status 状态
     * @apiSuccess {String} msg 信息
     * @apiSuccessExample {json} 成功的例子:
{
    "status": 200,
    "msg": "操作成功"
}
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
    {
    "status": 255,
    "msg": "操作失败"
    }

     *
     */
    public function add()
    {
        try {
            if (empty($_POST['class_name'])) {
                throw new Exception('名称不能为空');
            }
            $insert = [
                'class_name'=>trim($_POST['class_name']),
                'sort'=>intval($_POST['sort']),
                'is_show'=>intval($_POST['is_show'])?:0,
            ];
            $res = $this->cate_model->save($insert);
            if(!$res){
                throw new Exception('操作失败');
            }
            echo json_encode(['status'=>200,'msg'=>'操作成功']);die;
        } catch (Exception $e) {
            echo json_encode(['status'=>255,'msg'=>$e->getMessage()]);die;
        }
    }
    /**
     * @api {post} index.php?app=pin_ladder&mod=edit&sld_addons=pin_ladder 阶梯团购分类编辑
     * @apiVersion 0.1.0
     * @apiName edit
     * @apiGroup Admin
     * @apiDescription 阶梯团购分类编辑
     * @apiExample 请求地址:
     * curl -i http://site7.55jimu.com/SystemManage/index.php?app=pin_ladder&mod=edit&sld_addons=pin_ladder
     * @apiParam {Number} id 分类id(必填)
     * @apiParam {String} class_name 分类名称
     * @apiParam {Number} sort 排序
     * @apiParam {Number} is_show 是否显示
     * @apiSuccess {Number} status 状态
     * @apiSuccess {String} msg 信息
     * @apiSuccessExample {json} 成功的例子:
    {
    "status": 200,
    "msg": "操作成功"
    }
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
    {
    "status": 255,
    "msg": "操作失败"
    }
     *
     */
    public function edit()
    {
        $id = intval($_POST['id']);
        try {
            if (!empty($_POST['class_name'])) {
                $data['class_name'] = trim($_POST['class_name']);
            }
            if (!empty($_POST['sort'])) {
                $data['sort'] = trim($_POST['sort']);
            }
            if (isset($_POST['is_show'])) {
                $data['is_show'] = intval($_POST['is_show']);
            }
            if(empty($data)){
                echo json_encode(['status'=>200,'msg'=>'操作成功']);die;
            }
            $res = $this->cate_model->edit(['id'=>$id],$data);
            if(!$res){
                throw new Exception('操作失败');
            }
            echo json_encode(['status'=>200,'msg'=>'操作成功']);die;
        } catch (Exception $e) {
            echo json_encode(['status'=>255,'msg'=>$e->getMessage()]);die;
        }
    }

    /**
     * @api {post} index.php?app=pin_ladder&mod=del&sld_addons=pin_ladder 阶梯团购分类删除
     * @apiVersion 0.1.0
     * @apiName del
     * @apiGroup Admin
     * @apiDescription 阶梯团购分类删除
     * @apiExample 请求地址:
     * curl -i http://site7.55jimu.com/SystemManage/index.php?app=pin_ladder&mod=del&sld_addons=pin_ladder
     * @apiParam {Number} id 分类id(必填),多个删除格式:1,2,3,4
     * @apiSuccess {Number} status 状态
     * @apiSuccess {String} msg 信息
     * @apiSuccessExample {json} 成功的例子:
    {
    "status": 200,
    "msg": "操作成功"
    }
     *
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
    {
    "status": 255,
    "msg": "操作失败"
    }
     *
     */
    public function del()
    {
        if(is_numeric($_POST['id'])){
            $id = intval($_POST['id']);
        }else{
            $id = array_filter(explode($_POST['id']));
        }
        $res = $this->cate_model->drop(['id'=>['in',$id]]);
        if(!$res){
            echo json_encode(['status'=>255,'msg'=>'操作失败']);die;
        }
        echo json_encode(['status'=>200,'msg'=>'操作成功']);die;
    }
    /**
     * @api {get} index.php?app=pin_ladder&mod=pin_list&sld_addons=pin_ladder 阶梯团购列表
     * @apiVersion 0.1.0
     * @apiName pin_list
     * @apiGroup Admin
     * @apiDescription 阶阶梯团购列表
     * @apiExample 请求地址:
     * curl -i http://site7.55jimu.com/SystemManage/index.php?app=pin_ladder&mod=pin_list&sld_addons=pin_ladder
     * @apiParam {String} goods_name 商品名称
     * @apiParam {String} vendor_name 店铺名称
     * @apiParam {Number} pin_state 状态id 返回的状态
     * @apiParam {Number} pin_category 栏目分类
     * @apiParam {Number} page 当前显示条数
     * @apiParam {Number} pn 当前页数
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} data 信息
     * @apiSuccessExample {json} 成功的例子:
{
    "status": 200,
    "list": [
        {
            "id": "198",
            "sld_pic": "8_05956071814157322.jpg",
            "sld_start_time": "1542643100",
            "sld_end_time": "1574395241",
            "sld_type": "3",
            "sld_status": "0",
            "store_name": "商联达家居店",
            "time": "2018-11-19 23:58:20~2019-11-22 12:00:41",
            "class_name": "美女",
            "goods_name": "分佣测试",
            "goods_price": "180.00",
            "stock": "1000",
            "pin_price": "禁用",
            "pin_member_number": "0"
        }
    ],
    "pagination": {
        "current": "1",
        "pageSize": "10",
        "total": 1
    },
    "searchlist": {
        "goods_name": "测试"
    },
    "tuan_state_array": [
        {
            "id": 1,
            "name": "进行中"
        },
        {
            "id": 2,
            "name": "已结束"
        },
        {
            "id": 3,
            "name": "等待开始"
        }
    ],
    "types": [
        {
            "id": "5",
            "class_name": "水果",
            "sort": "0"
        },
        {
            "id": "6",
            "class_name": "运动",
            "sort": "0"
        },
        {
            "id": "1",
            "class_name": "美食",
            "sort": "255"
        },
        {
            "id": "2",
            "class_name": "美妆",
            "sort": "255"
        },
        {
            "id": "3",
            "class_name": "美女",
            "sort": "255"
        },
        {
            "id": "4",
            "class_name": "美景",
            "sort": "255"
        }
    ]
}

     */
    public function pin_list()
    {
        $condition = [];
        $return_condition = [];
        $category = $this->cate_model->getlist();
        if(isset($_GET['goods_name']) && !empty($_GET['goods_name'])){
            $condition['goods_common.goods_name'] = ['like','%'.$_GET['goods_name'].'%'];
            $return_condition['goods_name'] = $_GET['goods_name'];
        }
        if(isset($_GET['vendor_name']) && !empty($_GET['vendor_name'])){
            $condition['goods_common.store_name'] = ['like','%'.$_GET['vendor_name'].'%'];
            $return_condition['vendor_name'] = $_GET['vendor_name'];
        }
        if(isset($_GET['pin_state']) && !empty($_GET['pin_state'])){
            switch($_GET['pin_state']){
                case 1:
                    $condition['pin_ladder.sld_start_time'] = ['lt',time()];
                    $condition['pin_ladder.sld_end_time'] = ['gt',time()];
                    break;
                case 2:
                    $condition['pin_ladder.sld_end_time'] = ['lt',time()];
                    break;
                case 3:
                    $condition['pin_ladder.sld_start_time'] = ['gt',time()];
                    break;
                default:
                    break;
            }
            $return_condition['pin_state'] = $_GET['pin_state'];
        }
        if(isset($_GET['pin_category']) && !empty($_GET['pin_category'])){
            $condition['pin_ladder.sld_type'] = $_GET['pin_category'];
            $return_condition['pin_category'] = $_GET['pin_category'];
        }
        $list = $this->model->table('pin_ladder,goods_common')->join('left')->on('pin_ladder.sld_goods_id=goods_common.goods_commonid')->where($condition)->field([
            'pin_ladder.id',
            'pin_ladder.sld_pic',
            'pin_ladder.sld_start_time',
            'pin_ladder.sld_end_time',
            'pin_ladder.sld_type',
            'pin_ladder.sld_status',
            'goods_common.store_name',
        ])->order('pin_ladder.id desc')->select();
        if(!$list){
            echo json_encode([
                'status'=>200,
                'list'=>[],
                'pagination'=>[],
                'searchlist'=>$return_condition,
                'tuan_state_array'=>$this->state_list,
                'types'=>$category,
            ]);die;
        }
        $page_number = $this->model->gettotalnum();
        foreach($list as $k=>$v){
            $list[$k]['time'] = date('Y-m-d H:i:s',$v['sld_start_time']) .'~'. date('Y-m-d H:i:s',$v['sld_end_time']);
            $list[$k]['class_name'] = $this->cate_model->getone(['id'=>$v['sld_type']])['class_name'];
            $pin_goods = $this->model->table('pin_goods_ladder')->where(['sld_pin_id'=>$v['id'],'sld_pin_id'=>$v['id']])->field('sum(sld_stock) as stock,sld_gid')->find();
            $goods_info = $this->model->table('goods')->where(['gid'=>$pin_goods['sld_gid']])->field('goods_name,goods_price')->find();
            $pin_money_ladder = $this->model->table('pin_money_ladder')->where(['gid'=>$pin_goods['sld_gid'],'pin_id'=>$v['id']])->field('pay_money')->order('pay_money desc')->find();
            $list[$k]['goods_name'] = $goods_info['goods_name'];
            $list[$k]['goods_price'] = $goods_info['goods_price'];
            $list[$k]['stock'] = $pin_goods['stock'];
            $list[$k]['pin_price'] = $pin_money_ladder['pay_money'];
            $list[$k]['sld_status'] = $v['sld_status']?'正常':'禁用';
            $list[$k]['pic_url'] = gthumb($v['sld_pic'],'max');
            $list[$k]['url'] = C('main_url').'/index.php?app=goods&gid='.$pin_goods['sld_gid'];
            $list[$k]['pin_member_number'] = $this->model->table('pin_team_user_ladder')->where(['sld_pin_id'=>$v['id']])->count();
        }
        echo json_encode([
            'status'=>200,
            'list'=>$list,
            'pagination'=>[
                'current'=>$_GET['pn'],
                'pageSize'=>$_GET['page'],
                'total'=>$page_number,
            ],
            'searchlist'=>$return_condition,
            'tuan_state_array'=>$this->state_list,
            'types'=>$category,
        ]);die;
    }
}