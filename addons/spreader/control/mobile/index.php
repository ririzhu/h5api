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
     * 首页
     */
	public function index() {
        $model_mb_special = M('ssys_cwap_home');

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
                            $v['data'][$i_k] = $i_v;
                        }
                    }
                }
                if($v['type'] == 'fuwenben'){
                    $v['text'] = htmlspecialchars_decode($v['text']);
                    $data_new[] = $v;
                }else if($v['type'] == 'fzkb'){
                    $data_new[] = $v;
                }else if($v['type'] == 'lunbo'){
                    $lunbo_data = array();
                    foreach ($v['data'] as $lb_k => $lb_v){
                        $lunbo_data[] = $lb_v;
                    }
                    $v['data'] = $lunbo_data;
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
                }else{
                    $data_new[] = $v;
                }

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

    /**
     * 获取（平台客服电话）
     */
    public function get_site_phone() {
        output_data(array('site_phone' => C('ssys_site_phone')));
    }


    /*
    *
    *帮助中心文章列表
    *
    */
    public function article_list(){
        $model_article=M('ssys_article');

        $condition=array();
        $condition['acid']=$_GET['acid'];

        $article_list=$model_article->getJoinList($condition);


        foreach ($article_list as $key=>$value){
            $article_list[$key]['article_time']=date('Y-m-d H:i;s',$value['article_time']);
        }

        if(empty($article_list)){
            output_data(array('status'=>-1,'msg'=>'暂无数据'));
        }else{
            output_data(array('status'=>1,'article_list'=>$article_list));
        }

    }


    /*
     *
     *帮助中心的子分类
     *
     */
    public function article_help(){
        $model_article_class=M('ssys_article_class');

        $condition=array();
        $condition['ac_parent_id']=2;


        $article_list_class=$model_article_class->getClassList($condition);


        if(empty($article_list_class)){
            output_data(array('status'=>-1,'msg'=>'暂无数据'));
        }else{
            output_data(array('status'=>1,'article_list_class'=>$article_list_class));
        }

    }

    // 获取 推手系统规则信息
    public function rule_page(){

        $rule_content = C('ssys_rulepage_set');

        if(empty($rule_content)){
            output_data(array('status'=>-1,'msg'=>'暂无数据'));
        }else{
            output_data(array('status'=>1,'rule_content'=>$rule_content));
        }
    }




    /*
     *根据文章id获得内容
     *
     */
    public function article_detail(){
        $model_article=M('ssys_article');

        $id=intval($_GET['id']);

        $article_detail=$model_article->getOneArticle($id);

        if(empty($article_detail)){
            output_data(array('status'=>-1,'msg'=>'暂无数据'));
        }else{
            $article_detail['article_time']=date('Y-m-d H:i:s',$article_detail['article_time']);
            output_data(array('status'=>1,'article_detail'=>$article_detail));
        }

    }

    // 统计商品分享数量
    public function add_up_goods_num(){
        // 获取推手信息
        $member_id = 0;

        $model_mb_user_token = M('ssys_mb_user_token','spreader');
        $key = $_REQUEST['ssys_key'];
        $mb_user_token_info = $model_mb_user_token->getMbUserTokenInfoByToken($key);
        if (!empty($mb_user_token_info)) {
            $model_member = M('ssys_member');
            $member_info = $model_member->getMemberInfoByID($mb_user_token_info['member_id']);
            if(!empty($member_info)) {
                $member_id = $member_info['member_id'];
            }
        }

        $gid = intval($_REQUEST['gid']);
        if ($member_id > 0 && $gid > 0 ) {
            $data['share_num'] = 1;
            M('ssys_statistics_log')->save_statistics_log($data);
        }
    }
    //推手条件设置
    public function ts_condition()
    {
        $data = [
            'ssys_become_ts_open' => C('ssys_become_ts_open')?:0,
            'ssys_ts_condition1_money'=>C('ssys_ts_condition1_money')?:0,
            'ssys_ts_condition2_goodsmoney'=>C('ssys_ts_condition2_goodsmoney')?:0
        ];
        echo json_encode(['status'=>200,'data'=>$data]);die;
    }
    //推手购买条件商品
    public function ts_condition_goods()
    {
        $model = model();
        //会员信息
        $model_mb_user_token = M('ssys_mb_user_token','spreader');
        $key = $_POST['ssys_key'];
        $mb_user_token_info = $model_mb_user_token->getMbUserTokenInfoByToken($key);
        if (empty($mb_user_token_info)) {
            //未登录
            echo json_encode(['status'=>355,'msg'=>Language::get('请登录')]);die;
        }
        $model_member = M('ssys_member');
        $member_info = $model_member->getMemberInfoByID($mb_user_token_info['member_id']);
        if(!$member_info){
            //未登录
            echo json_encode(['status'=>355,'msg'=>Language::get('请登录')]);die;
        }
        $goods_info = $model->table('ssys_goods,goods')
            ->join('left')
            ->on('ssys_goods.gid=goods.gid')
            ->where([
                'ssys_goods.is_buy_condition'=>1,

            ])
            ->field('goods.gid as gid,goods.goods_image as goods_image,goods.goods_name as goods_name,goods.goods_price as goods_price')
            ->order()
            ->select();
        if($goods_info){
            array_walk($goods_info,function(&$v){
                $v['goods_image'] = cthumb($v['goods_image'],240);
            });

            echo json_encode(['status'=>200,'data'=>$goods_info,'member_info'=>$member_info]);die;
        }
        echo json_encode(['status'=>255,'msg'=>'未找到设置的指定商品']);die;
    }
    //推手查看自己的条件完成
    public function judge_ts_condition($key = null)
    {
        $model_mb_user_token = M('ssys_mb_user_token','spreader');
        if(!$key) {
        $key = $_POST['ssys_key'];
        }
        $mb_user_token_info = $model_mb_user_token->getMbUserTokenInfoByToken($key);
        if (empty($mb_user_token_info)) {
            //未登录
            echo json_encode(['status'=>355,'msg'=>'未登录']);die;
        }
        $model_member = M('ssys_member');
        $member_info = $model_member->getMemberInfoByID($mb_user_token_info['member_id']);
        if(!$member_info){
            //未登录
            echo json_encode(['status'=>355,'msg'=>Language::get('请登录')]);die;
        }
        //查看推手完成条件
        $re = M('ssys_order','spreader')->StatisticsMemberCondition($member_info['member_id']);
        if(!is_array($re) && $re){
            if($_POST['ssys_key']) {
                echo json_encode(['status' => 200]);
                die;
            }else{
                return true;
            }
        }
        if(C('ssys_ts_condition2_goodsmoney')>0){
            $re['condition2_p'] = '条件一: 购买活动专区任意商品达到'.C('ssys_ts_condition2_goodsmoney').'元';
            $re['condition2'] = $re['condition2'].'元。';
        }else{
            $re['condition2_p'] = '条件一: 购买活动专区任意一件商品';
            $re['condition2'] = $re['condition2'].'件。';
        }
        $re['condition1_p'] = '条件二: 历史个人累计购买商品满'.C('ssys_ts_condition1_money').'元';
        echo json_encode(['status'=>255,'data'=>$re]);die;
    }
    //根据推手状态判断权限
    public function check_Jurisdiction()
    {
        $model_mb_user_token = M('ssys_mb_user_token','spreader');
        $key = $_POST['ssys_key'];
        $mb_user_token_info = $model_mb_user_token->getMbUserTokenInfoByToken($key);
        if (empty($mb_user_token_info)) {
            //未登录
            echo json_encode(['status'=>255]);die;
        }
        $model_member = M('ssys_member');
        $member_info = $model_member->getMemberInfoByID($mb_user_token_info['member_id']);
        if(empty($member_info)) {
            echo json_encode(['status'=>255]);die;
        } else {
            if($member_info['ts_member_state'] == 0){
                //权限不够
                if(C('ssys_become_ts_open')!=1){
                    if($this->judge_ts_condition($key)){
                        echo json_encode(['status'=>200]);die;
                    }
                }
                echo json_encode(['status'=>155]);die;
            }else{
                echo json_encode(['status'=>200]);die;
            }
        }
    }

}
