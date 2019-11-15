<?php

defined('DYMall') or exit('Access Invalid!');
class points_goodsCtl extends mobileHomeCtl{

	public function __construct() {
        parent::__construct();
    }


    /**
     * @api {get} index.php?app=points_goods&mod=goods_detail&sld_addons=points 商品详请页数据
     * @apiVersion 0.1.0
     * @apiName goods_detail
     * @apiGroup PointsGoods
     * @apiDescription 商品详请页数据
     * @apiExample 请求地址:
     * curl -i http://site2.slodon.cn/cmobile/index.php?app=points_goods&mod=goods_detail&sld_addons=points
     * @apiParam {Number} gid 积分商品id
     * @apiSuccess {Number} status 状态
     * @apiSuccess {String} data 返回数据
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200
     *         "data": 商品数据,
     *         "lunbo":{
     *                  0:{
     *                      "avart":"http://site2.slodon.cn/data/upload/mall/common/05713309930850473.png",
     *                      "time":"2018-09-27",
     *                      "point_buyername":"dd914",
     *                      "point_goodsnum":1,
     *                      "point_buyerid":"251",
     *                      "point_addtime":"1538016042",
     *                  }
     *
     *                }
     *      }
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "商品已下架"
     *     }
     *
     */
    public function goods_detail()
    {
//        dd($_GET);die;
        $pgoods_model = M('points_goods');
        $condition = [
            'pgid'=>intval($_GET['gid']),
            'pgoods_show'=>1,
            'pgoods_state'=>0,
        ];
        $goods_info = $pgoods_model->getGoodsDetail($condition);
        $lunbo = M('points_goods')->table('points_ordergoods,points_order')->join('left')->on('points_ordergoods.point_orderid=points_order.point_orderid')->where(['points_ordergoods.point_goodsid'=>intval($_GET['gid']),'points_order.point_orderstate'=>['gt',11]])->order('points_order.point_addtime desc')->field('points_order.point_buyerid,points_order.point_buyername,points_order.point_addtime,points_ordergoods.point_goodsnum')->select();
        foreach($lunbo as $kk=>$vv){
            $lunbo[$kk]['time'] = date('Y-m-d',$vv['point_addtime']);
            $lunbo[$kk]['avart'] = $this->getMemberAvatarForID($vv['point_buyerid']);

        }
        //获取
        if($goods_info){
            $goods_info['goods_image'] = pointprodThumb($goods_info['pgoods_image']);
            echo json_encode(['status'=>200,'data'=>$goods_info,'lunbo'=>$lunbo]);die;
        }
        echo json_encode(['status'=>255,'msg'=>'商品已下架']);die;
    }
    /*
     * 获取用户头像
     */
    public function getMemberAvatarForID($id)
{
//    return UPLOAD_SITE_URL . '/' . ATTACH_AVATAR . '/avatar_' . $id . '.jpg';
    if (file_exists(BASE_UPLOAD_PATH . '/' . ATTACH_AVATAR . '/avatar_' . $id . '.jpg')) {
        return UPLOAD_SITE_URL . '/' . ATTACH_AVATAR . '/avatar_' . $id . '.jpg';
    } else {
        return UPLOAD_SITE_URL . '/' . ATTACH_COMMON . DS . C('default_user_portrait');
    }
}


    /**
     * @api {get} index.php?app=points_goods&mod=goods_body&sld_addons=points 商品body数据
     * @apiVersion 0.1.0
     * @apiName goods_body
     * @apiGroup PointsGoods
     * @apiDescription 商品body数据
     * @apiExample 请求地址:
     * curl -i http://site2.slodon.cn/cmobile/index.php?app=points_goods&mod=goods_body&sld_addons=points
     * @apiParam {Number} gid 积分商品id
     * @apiSuccess {String} body数据 返回数据
     * @apiSuccessExample {json} 成功的例子:
     *          <p><img src="http://site2.slodon.cn/data/upload/mall/editor/05906685662032642.png" alt="image" /></p>
     *
     */
    public function goods_body()
    {
        $gid = intval($_GET ['gid']);
        $condition['pgid'] = $gid;
        $pgoods_model = M('points_goods');

        $goods_info = $pgoods_model->getGoodsDetail($condition,'pgoods_body');
        Template::output('goods_body_info', $goods_info);
        Template::showpage('goods_body');
    }


    /**
     * @api {post} index.php?app=points_goods&mod=addUserBrowserGoods&sld_addons=points 更新积分商品浏览量
     * @apiVersion 0.1.0
     * @apiName addUserBrowserGoods
     * @apiGroup PointsGoods
     * @apiDescription 更新积分商品浏览量
     * @apiExample 请求地址:
     * curl -i http://site2.slodon.cn/cmobile/index.php?app=points_goods&mod=addUserBrowserGoods&sld_addons=points
     * @apiParam {Number} gid 积分商品id
     * @apiSuccess {.} . 无返回值
     */
    public function addUserBrowserGoods()
    {
        $condition['pgid'] = intval($_POST['gid']);
        $update['pgoods_view'] = ['exp','pgoods_view + 1'];
        M('points_goods')->updatePointsGoods($condition,$update);
    }




    /**
     * @api {post/get} index.php?app=points_goods&mod=points_goods_tuijian&sld_addons=points 获取推荐商品
     * @apiVersion 0.1.0
     * @apiName points_goods_tuijian
     * @apiGroup PointsGoods
     * @apiDescription 获取推荐商品前4个
     * @apiExample 请求地址:
     * curl -i http://site2.slodon.cn/cmobile/index.php?app=points_goods&mod=points_goods_tuijian&sld_addons=points
     * @apiSuccess {Number} status 状态
     * @apiSuccess {String} data 返回数据
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200
     *         "data":{
     *                   0:{
     *                      "pgid":62,
     *                      "pgoods_image":"http://site2.slodon.cn/data/upload/mall/pointprod/05907726132560107.jpg",
     *                      "pgoods_name":"青苹果",
     *                      "pgoods_points":"100",
     *                      "pgoods_price":"10000.00",
     *                      "url":"http://site2.slodon.cn/points/cwap_goods_datail.html?gid=62"
     *                      }
     *                      .
     *                      .
     *                      .
     *                }
     *      }
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "未找到相关推荐"
     *     }
     *
     */
    public function points_goods_tuijian()
    {
        $condition['pgoods_state'] = 0;
        $condition['pgoods_commend'] = 1;
        $condition['pgoods_show'] = 1;
        $tuijian_list = M('points_goods')->getPointsGoodslist($condition,'pgid,pgoods_image,pgoods_name,pgoods_price,pgoods_points','pgid desc','',4);
//        dd(M('points_goods')->getlastsql());
        if($tuijian_list){
            array_walk($tuijian_list,function(&$v){
                $v['pgoods_name'] = mb_strlen($v['pgoods_name'],"utf-8")<25?$v['pgoods_name']:mb_substr($v['pgoods_name'],0,25,"utf-8").'...';
                $v['pgoods_image'] = pointprodThumb($v['pgoods_image']);
                $v['url'] = POINTS_WAP_SITE_URL.'/cwap_goods_datail.html?gid='.$v['pgid'];
            });
            echo json_encode(['status'=>200,'data'=>$tuijian_list]);die;
        }
            echo json_encode(['status'=>255,'msg'=>"未找到相关推荐"]);
    }

}