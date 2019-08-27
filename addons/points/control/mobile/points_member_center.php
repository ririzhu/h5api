<?php
/**
 * 个人积分控制器
 * Design by gxk
 */


defined('DYMall') or exit('Access Invalid!');
class points_member_centerCtl extends mobileMemberCtl {

    public function __construct(){
        parent::__construct();
    }

    /**
     * @api {get} index.php?app=points_member_center&mod=getUserMemberInfo&&sld_addons=points 获取用户积分
     * @apiVersion 0.1.0
     * @apiName getUserMemberInfo
     * @apiGroup Points
     * @apiDescription 获取用户积分
     * @apiExample 请求地址:
     * curl -i http://site2.slodon.cn/cmobile/index.php?app=points_member_center&mod=getUserMemberInfo&&sld_addons=points
     * @apiParam {String} key 会员登录key值
     * @apiSuccess {Number} status 状态
     * @apiSuccess {Number} data 积分
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200
     *         "data": 1000
     *      }
     *
     */

    //获取用户积分
    public function getUserMemberInfo()
    {
       echo json_encode(['status'=>200,'data'=>$this->member_info['member_points']]);die;
    }

    /**
     * @api {get} index.php?app=points_member_center&mod=getUserPointsDesc&sld_addons=points 获取用户积分变更列表
     * @apiVersion 0.1.0
     * @apiName GetUser
     * @apiGroup Points
     * @apiDescription 获取用户积分变更列表
     * @apiExample 请求地址:
     * curl -i http://site2.slodon.cn/cmobile/index.php?app=points_member_center&mod=getUserPointsDesc&sld_addons=points
     * @apiParam {String} key 会员登录key值
     * @apiParam {String} type add(增加)/desc(减少)/空(全部)
     * @apiParam {Number} page 分页
     * @apiParam {Number} pn 请求第几页
     * @apiSuccess {Number} status 状态
     * @apiSuccess {String} data 返回数据
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "status":200
     *         "data": {
     *                  'ishasmore':{hasmore: true, page_total: 4},
     *                  "list":数据
     *               }
     *      }
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":255,
     *          "msg": "内容不存在"
     *     }
     *
     */
    //获取用户积分变更列表
    public function getUserPointsDesc()
    {
        $page = intval($_GET['page']);
        $type = $_GET['type'];
        $member_id = $this->member_info['member_id'];
        $condition = [
            'pl_memberid'=>$member_id
        ];
        switch($type){
            case 'add':
                $condition['pl_points'] = ['gt',0];
                break;
            case 'desc':
                $condition['pl_points'] = ['lt',0];
                break;
            default:
                break;
        }
        $points_model = M('points');
        $points_list = $points_model->getPointsLog($condition,'pl_id,pl_addtime,pl_desc,pl_points','pl_id desc',$page);
        $page_count = $points_model->gettotalpage();
        if($points_list){
            array_walk($points_list,function(&$v){
                $v['time'] = date('Y.m.d',$v['pl_addtime']);
                $v['points'] = $v['pl_points']>0?'+'.$v['pl_points']:$v['pl_points'];
            });
            echo json_encode(['status'=>200,'data'=>['list'=>$points_list,'ishasmore'=>mobile_page($page_count)]]);die;
        }
        echo json_encode(['status'=>255]);die;
    }
}