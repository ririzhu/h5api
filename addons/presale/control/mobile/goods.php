<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/11/28
 * Time: 22:17
 */
class goodsCtl extends mobileHomeCtl{
    private $pre_model;
    private $cate_model;
    private $model;
    public function __construct() {
        if(!(C('promotion_allow')==1 && C('sld_presale_system') && C('pin_presale_isuse'))){
            echo json_encode(['status'=>255,'msg'=>'当前活动尚未开启']);die;
        }
        $this->pre_model = M('pre_presale','presale');
        $this->cate_model = M('pre_category','presale');
        $this->model = model();
        parent::__construct();
    }

    /**
     * @api {get} index.php?app=goods&mod=index&sld_addons=presale 预售活动首页
     * @apiVersion 0.1.0
     * @apiName goods
     * @apiGroup Presale
     * @apiDescription 预售活动首页
     * @apiExample 请求地址:
     * curl -i http://site7.55jimu.com/cmobile/index.php?app=goods&mod=index&sld_addons=presale
     * @apiParam {String} class_id 分类id
     * @apiParam {Number} page 显示数量
     * @apiParam {Number} pn 当前页
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} data 信息
     * @apiSuccessExample {json} 成功的例子:
{
    "status": 200,
    "data": {
        "list": [
            {
                "pre_id": "299",
                "pre_goods_commonid": "1440",
                "vid": "8",
                "pre_category": "2",
                "pre_pic": "8_05967546718027499.jpg",
                "pre_start_time": "1542679140",
                "pre_end_time": "1546704000",
                "pre_max_buy": "3",
                "pre_limit_time": "10",
                "pre_status": "1",
                "is_rollback": "0",
                "gid": "2044",
                "goods_price": "280.00",
                "pre_sale_price": "2.00",
                "goods_image": "http://site7.55jimu.com/data/upload/mall/store/goods/8/8_05955244440880378_240.png"
            }
        ],
        "ismore": {
            "hasmore": false,
            "page_total": 1
        }
    }
}
 */
    public function index()
    {
        $page = $_GET['page']?:10;
        $condition = [];
        if(isset($_GET['class_id']) && !empty($_GET['class_id'])){
            $condition['pre_category'] = $_GET['class_id'];
        }
        $condition['pre_start_time'] = ['lt',time()];
        $condition['pre_end_time'] = ['gt',time()];
        $condition['pre_status'] = 1;
        $list = $this->pre_model->getlist($condition,'*',$page);
        $ismore = mobile_page($this->pre_model->gettotalpage());
        foreach($list as $k=>$v){
            $goods_info = $this->model->table('pre_goods')->where(['pre_id'=>$v['pre_id']])->find();
            $list[$k]['gid'] = $goods_info['gid'];
            $list[$k]['goods_price'] = $goods_info['goods_price'];
            $list[$k]['pre_sale_price'] = $goods_info['pre_sale_price'];
            $list[$k]['pre_pic'] = gthumb($v['pre_pic'],'max');
            $list[$k]['goods_name'] = $goods_info['goods_name'];
        }
        echo json_encode(['status'=>200,'data'=>[
            'list'=>$list,
            'ismore'=>$ismore,
        ]]);die;
    }


    /**
     * @api {get} index.php?app=goods&mod=getclasslist&sld_addons=presale 获取预售活动分类
     * @apiVersion 0.1.0
     * @apiName getclasslist
     * @apiGroup Presale
     * @apiDescription 获取预售活动分类
     * @apiExample 请求地址:
     * curl -i http://site7.55jimu.com/cmobile/index.php?app=goods&mod=getclasslist&sld_addons=presale
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} list 信息
     * @apiSuccessExample {json} 成功的例子:
{
    "status": 200,
    "list": [
        {
            "id": "6",
            "class_name": "运动",
            "sort": "3",
            "is_show": "1"
        },
        {
            "id": "5",
            "class_name": "水果",
            "sort": "23",
            "is_show": "1"
        },
        {
            "id": "1",
            "class_name": "美食",
            "sort": "255",
            "is_show": "1"
        },
        {
            "id": "2",
            "class_name": "美妆",
            "sort": "255",
            "is_show": "1"
        },
        {
            "id": "3",
            "class_name": "美女",
            "sort": "255",
            "is_show": "1"
        },
        {
            "id": "4",
            "class_name": "美景",
            "sort": "255",
            "is_show": "1"
        }
    ]
}
     */
    public function getclasslist()
    {
        $list = $this->cate_model->getlist(['is_show'=>1]);
        $list = array_values($list);
        echo json_encode(['status'=>200,'list'=>$list]);
    }
    /**
     * @api {get} index.php?app=goods&mod=goods_detail&sld_addons=presale 预售商品详情
     * @apiVersion 0.1.0
     * @apiName goods_detail
     * @apiGroup Presale
     * @apiDescription 预售商品详情
     * @apiExample 请求地址:
     * curl -i http://site7.55jimu.com/cmobile/index.php?app=goods&mod=goods_detail&sld_addons=presale
     * @apiParam {Number} gid 商品id
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Json} data 信息
     * @apiSuccessExample {json} 成功的例子:
{
    "status": 200,
    "data": {
        "pre_id": "299",
        "pre_goods_commonid": "1440",
        "vid": "8",
        "pre_category": "2",
        "pre_pic": "8_05967546718027499.jpg",
        "pre_start_time": "1542679140",
        "pre_end_time": "1546704000",
        "pre_max_buy": "3",
        "pre_limit_time": "10",
        "pre_status": "1",
        "is_rollback": "0",
        "id": "885",
        "gid": "2044",
        "goods_price": "280.00",
        "goods_name": "11月规格测试 a c",
        "goods_image": "8_05955244440880378.png",
        "pre_deposit_price": "1.00",
        "pre_sale_price": "2.00",
        "goods_stock": "900",
        "pre_end_time_str": "2019年01月06日 00:00结束",
        "pre_weikuan_time_str": "2019年01月06日 00:00 - 2019年01月06日 10:00"
    }
}
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "活动已关闭"
     *      }
     *
     */
    public function goods_detail()
    {
        $gid = intval($_GET['gid']);
        try{
            $condition = [
                'pre_goods.gid'=>$gid,
                'presale.pre_start_time'=>['lt',time()],
                'presale.pre_end_time'=>['gt',time()],
            ];
            $pre_info = $this->model->table('presale,pre_goods')->join('left')->on('presale.pre_id=pre_goods.pre_id')->where($condition)->find();
            if(!$pre_info){
                throw new Exception('活动暂未开启');
            }
            if($pre_info['pre_status'] == 0){
                throw new Exception('活动已关闭');
            }
            if($pre_info['pre_start_time']<time() && $pre_info['pre_end_time']>time()){
                $pre_info['pre_end_time_str'] = date('Y年m月d日 H:i',$pre_info['pre_end_time']).'结束';
            }else{
                if($pre_info['pre_start_time']>time() ){
                    $pre_info['pre_end_time_str'] = date('Y年m月d日 H:i',$pre_info['pre_start_time']).'开始';
                }else{
                    $pre_info['pre_end_time_str'] = '已结束';
                }
            }
            $pre_info['pre_weikuan_time_str'] = date('Y年m月d日 H:i',$pre_info['pre_end_time']).' - '. date('Y年m月d日 H:i',$pre_info['pre_end_time']+($pre_info['pre_limit_time']*3600));
            $pre_info['goods_image'] = cthumb($pre_info['goods_image']);
            echo json_encode(['status'=>200,'data'=>$pre_info]);die;
        }catch(Exception $e){
            echo json_encode(['status'=>255,'msg'=>$e->getMessage()]);
        }
    }

}