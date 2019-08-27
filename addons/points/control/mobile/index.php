<?php
/**
 * WAP首页
 *
 */


defined('DYMall') or exit('Access Invalid!');
class indexCtl extends mobileHomeCtl{

	public function __construct() {
        parent::__construct();
    }

    /**
     * @api {get} index.php?app=index&sld_addons=points 积分商城首页
     * @apiVersion 0.1.0
     * @apiName index
     * @apiGroup Points
     * @apiDescription 积分商城首页
     * @apiExample 请求地址:
     * curl -i http://site2.slodon.cn/cmobile/index.php?app=index&sld_addons=points
     * @apiSuccess {Number} code 状态
     * @apiSuccess {String} data 返回数据
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "code":200
     *         "data": {
     *                  "has_more":0
     *                  "tmp_data":数据
     *               }
     *      }
     * @apiError {Number} status 状态
     * @apiError {String} msg 错误说明
     * @apiErrorExample {json} 失败的例子:
     *      {
     *          "status":155,
     *          "msg": "积分商城当前尚未开启"
     *     }
     */
    /**
     * 首页
     */
    public function index() {
        //判断是否开启
        $this->is_open_points();
        $model_mb_special = M('points_cwap_home');
        $model_goods = Model('pointprod');

        $shop_id = isset($_GET['shop_id']) ? $_GET['shop_id'] : 0;

        $condition['shop_id'] = $shop_id;

        // 城市分站
        $curSldCityId = intval($_GET['bid']) ? intval($_GET['bid']) : 0;
        if($curSldCityId){
            $condition['city_id'] = $curSldCityId;
        }

        //获取首页数据
        $data = $model_mb_special->getCwapHomeInfo($condition);
        $data =unserialize($data['home_data']);

        if ($shop_id == 0) {
            // 首页模版分步加载数据
            $limit_group_p = (isset($_GET['lp']) && intval($_GET['lp']) > 0) ? intval($_GET['lp']) : 0; // 第几组
            $has_more = 0; // 是否有下一组数据
            $limit = 0;//(isset($_GET['l']) && intval($_GET['l']) > 0) ? intval($_GET['l']) : 0; // 每组几条数据
            if (($data_count = count($data)) > $limit && $limit > 0) {
                $data_group = array_chunk($data,$limit);
                $data = $data_group[$limit_group_p];
                $next_group_index = $limit_group_p+1;
                if (isset($data_group[$next_group_index])) {
                    $has_more = 1;
                }
            }
        }
        //对数据重新排序
        $data_new = array();
//        print_r($data);die;
        $new_data = array();
        if ($data) {
            foreach ($data as $k => $v){
                if(isset($v['data']) && !empty($v['data'])){
                    foreach ($v['data'] as $i_k => $i_v) {
                        if(isset($i_v['img'])){
                            $i_v['img'] = (strpos($i_v['img'],'http') !==false) ? $i_v['img'] : getMbSpecialImageUrl($i_v['img']);
                            //url处理，用户app和小程序的url处理
                            if($i_v['url_type'] == 'url'){
                                $url_new = $this -> match_diy_url($i_v['url']);
                                $i_v['url_type_new'] = $url_new['url_type_new'];
                                $i_v['url_new'] = $url_new['url_new'];
                            }
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

                            $goods_info = $model_goods ->cwap_GetPointsGoodsList(['pgid'=>$val],'pgid as gid,pgoods_name as goods_name,pgoods_price,pgoods_points as show_price,pgoods_image')[0];
                            if(!empty($goods_info)){
                                $goods_info['goods_image'] = pointprodThumb($goods_info['pgoods_image']);
                                $v['data']['goods_info'][] = $goods_info;
                            }
                        }
                        $data_new[] = $v;
                    }
                }else if($v['type'] == 'dapei') {
                    //推荐商品模块如果没有添加商品就保存的话就直接不显示了
                    if (!empty($v['data']['gid']) && is_array($v['data']['gid'])) {
                        foreach ($v['data']['gid'] as $key => $val) {
                            $goods_info = $model_goods ->cwap_GetPointsGoodsList(['pgid'=>$val],'pgid as gid,pgoods_name as goods_name,pgoods_price,pgoods_points as show_price,pgoods_image')[0];
                            if(!empty($goods_info)){
                                $goods_info['goods_image'] = pointprodThumb($goods_info['pgoods_image']);
                                $v['data']['goods_info'][] = $goods_info;
                            }
                        }

                        // 获取最终价格
//                        $v['data']['goods_info'] = Model('goods_activity')->rebuild_goods_data($v['data']['goods_info']);

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
                    list($width,$height)=getimagesize($lunbo_data[0]['img']);
                    $v['width'] = 750;
                    $v['height'] = 750*$height/$width;
                    $data_new[] = $v;

                }else if($v['type'] == 'tupianzuhe'){
                    $tupianzuhe_data = array();
                    $tupianzuhe_data['type'] = $v['type'];
                    $tupianzuhe_data['sele_style'] = $v['sele_style'];
                    $new_data = array();
                    foreach ($v['data'] as $lb_k => $lb_v){
                        $new_data[] = $lb_v;
                    }
                    $tupianzuhe_data['data'] = $new_data;
                    $data_new[] = $tupianzuhe_data;
                }

            }
        }
        if ($shop_id == 0) {
            $site_name = C('site_name') ? C('site_name') : '';
            $this->_output_special(array('tmp_data'=>$data_new,'has_more'=>$has_more,'site_name'=>$site_name), $_GET['type']);
        }else{
            $this->_output_special($data_new, $_GET['type']);
        }
    }
    public function is_open_points()
    {
        
        //判断系统是否开启积分和积分中心功能
        if (C('points_isuse') != 1 || C('pointshop_isuse') != 1 || C('pointprod_isuse') != 1){
            echo json_encode(['status'=>155,'msg'=>'积分商城当前尚未开启']);die;
        }
    }

    /**
     * @api {get} index.php?app=index&mod=index_title&sld_addons=points 获取首页title和搜索栏颜色
     * @apiVersion 0.1.0
     * @apiName index_title
     * @apiGroup Points
     * @apiDescription 获取首页title和搜索栏颜色
     * @apiExample 请求地址:
     * curl -i http://site2.slodon.cn/cmobile/index.php?app=index&mod=index_title&sld_addons=points
     * @apiSuccess {Number} code 状态
     * @apiSuccess {String} data 返回数据
     * @apiSuccessExample {json} 成功的例子:
     *      {
     *         "code":200
     *         "data": {
     *                  "sousuo_color":"",
     *                  "title":"热舞人情味"
     *               }
     *      }
     */
    public function index_title() {

        $model_mb_special = M('points_cwap_home');
        //获取首页数据
        $data = $model_mb_special->getCwapHomeInfo();
        //对数据重新排序
        $data_new = array();
        $data_new['title'] = $data['home_desc'];
        $data_new['sousuo_color'] = $data['home_sousuo_color'];
        $this->_output_special($data_new, $_GET['type']);
    }
    /**
     * 获取底部导航栏颜色
     */
    public function botnav_color() {
        $model_mb_special = Model('cwap_home');
        //获取首页数据
        $data = $model_mb_special->getCwapHomeInfo();
        //对数据重新排序
        $data_new = array();
        //获取一下门店配置
        $data_new['dian_open'] = (C('dian') && C('dian_isuse'));
        $data_new['botnav_color'] = $data['home_botnav_color'];
//        print_r($data);die;
        $this->_output_special($data_new, $_GET['type']);
    }

    /**
     * 输出专题
     */
    private function _output_special($data, $type = 'json', $special_id = 0) {
        $model_special = Model('mb_special');
        if($_GET['type'] == 'html') {
            $html_path = $model_special->getMbSpecialHtmlPath($special_id);
            if(!is_file($html_path)) {
                ob_start();
                Template::output('list', $data);
                Template::showpage('mb_special');
                file_put_contents($html_path, ob_get_clean());
            }
            header('Location: ' . $model_special->getMbSpecialHtmlUrl($special_id));
            die;
        } else {

            output_data($data);
        }
    }
    /*
     根据URL路径匹配页面的跳转(商品/店铺/店铺列表/优惠券列表/商品列表/签到页面/专题列表)
     *
     */
    public function match_diy_url($url){
        $result = array();
        //        $url = 'http://www.slodon.cn/cwap/cwap_product_detail.html?gid=1177';
        //        $url = 'http://www.slodon.cn/cwap/cwap_go_shop.html?vid=19';
        //        $url = 'http://www.slodon.cn/cwap/cwap_subject.html?topic_id=6';
//                $url = 'http://www.slodon.cn/cwap/cwap_shop_list.html';

        //        $url = 'http://www.slodon.cn/cwap/cwap_product_list.html?keyword=T%E6%81%A4';
        //        $url = 'https://www.guirengou.com/cwap/red_get_list.html';
        //        $url = 'http://www.slodon.cn/cwap/cwap_user_points.html';
        //        $url = 'cwap_product_list.html?gc_id=316';
        //        $url = 'https://www.guirengou.com/cwap/cwap_product_list.html?keyword=%E9%98%B2%E6%99%92%20';

        $url = htmlspecialchars_decode($url);
        if(strstr($url,'index.php?app=goods')){
            //url转为商品详情页
            $arr = $this->parse_url_param($url);
            if($arr['gid'] > 0){
                $result['url_type_new'] = 'goods';
                $result['url_new'] = $arr['gid'];
            }
        }else if(strstr($url,'cwap_product_detail.html?gid')){
            //url转为商品详情页
            $arr = $this->parse_url_param($url);
            if($arr['gid'] > 0){
                $result['url_type_new'] = 'goods';
                $result['url_new'] = $arr['gid'];
            }
        }else if(strstr($url,'cwap_go_shop.html?vid')){
            //url转为店铺
            $arr = $this->parse_url_param($url);
            if($arr['vid'] > 0){
                $result['url_type_new'] = 'vendor';
                $result['url_new'] = $arr['vid'];
            }
        }else if(strstr($url,'cwap_subject.html?topic_id')){
            //url转为专题
            $arr = $this->parse_url_param($url);
            if($arr['topic_id'] > 0){
                $result['url_type_new'] = 'special';
                $result['url_new'] = $arr['topic_id'];
            }
        }else if(strstr($url,'cwap_shop_list.html')){
            //url转为店铺列表
            $result['url_type_new'] = 'vendorlist';
            $result['url_new'] = '';

        }else if(strstr($url,'cwap_product_list.html?gc_id')){
            //url转为商品列表（按分类）
            $arr = $this->parse_url_param($url);
            if($arr['gc_id'] > 0){
                $result['url_type_new'] = 'goodscat';
                $result['url_new'] = $arr['gc_id'];
            }
        }else if(strstr($url,'cwap_product_list.html?keyword')){
            //url转为商品列表（按关键词）
            $arr = $this->parse_url_param($url);
            if(isset($arr['keyword'])){
                $result['url_type_new'] = 'goodslist';
                $result['url_new'] = urldecode($arr['keyword']);
            }
        }else if(strstr($url,'cwap_user_points.html')){
            //url转为签到
            $result['url_type_new'] = 'sighlogin';
            $result['url_new'] = '';
        }else if(strstr($url,'red_get_list.html')){
            //url转为优惠券列表
            $result['url_type_new'] = 'voucherlist';
            $result['url_new'] = '';
        }else if(strstr($url,'cwap_pro_cat.html')){
            //url转为优惠券列表
            $result['url_type_new'] = 'fenlei';
            $result['url_new'] = '';
        }else if(strstr($url,'cwap_cart.html')){
            //url转为优惠券列表
            $result['url_type_new'] = 'cart';
            $result['url_new'] = '';
        }else if(strstr($url,'cwap_user.html')){
            //url转为优惠券列表
            $result['url_type_new'] = 'usercenter';
            $result['url_new'] = '';
        }else if(strstr($url,'pin_index.html')){
            //url拼团列表页
            $result['url_type_new'] = 'pin_index';
            $result['url_new'] = '';
        }else if(strstr($url,'cwap_tuan.html')){
            //url团购列表页
            $result['url_type_new'] = 'tuan_index';
            $result['url_new'] = '';
        }else if(strstr($url,'cwap_discount.html')){
            //url团购列表页
            $result['url_type_new'] = 'xianshi_index';
            $result['url_new'] = '';
        }else if(strstr($url,'points')){
            //url积分商城首页
            $result['url_type_new'] = 'points_shop';
            $result['url_new'] = '';
        }else if(strstr($url,'ldj')){
            //url联到家首页
            $result['url_type_new'] = 'ldj';
            $result['url_new'] = '';
        }else{
            //不满足以上条件则不跳转
            $result['url_type_new'] = '';
            $result['url_new'] = '';
        }
        return $result;
    }

    /**
     * 获取url中的各个参数
     * 类似于 pay_code=alipay&bank_code=ICBC-DEBIT
     * @param type $str
     * @return type
     */
    public function parse_url_param($str)
    {
        $data = array();
        $arr=array();
        $p=array();
        $arr=explode('?', $str);
        $p = explode('&', $arr[1]);
        foreach ($p as $val) {
            $tmp = explode('=', $val);
            $data[$tmp[0]] = $tmp[1];
        }
        return $data;
    }
}
