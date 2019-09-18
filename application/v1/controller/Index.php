<?php

namespace app\v1\controller;

use app\v1\controller\Base;
use app\v1\model\Area;
use app\v1\model\GoodsActivity;
use app\v1\model\Message;
use app\v1\model\Red;
use think\cache\driver\Redis;
use think\console\command\make\Model;
use think\db;
use think\captcha\Captcha;
use think\cache;

class Index extends Base
{
    public function index()
    {
        $this->isLogin();
        return $this->test();
    }

    public function isLogin()
    {
        $list = Db::table('bbc_goods')->order('gid', 'desc')->select();
        return $list;
    }

    /**
     * 获取图片验证码
     *
     **/
    public function picCode()
    {
        if (!input("id")) {
            $id = microtime(true);
        } else {
            $id = input("id");
        }
        $captcha = new Captcha();
        return $captcha->entry($id);
    }

    /**
     * 验证图片验证码
     *
     **/
    public function veriPicCode()
    {
        if (!input("id") || !input("code") || captcha_check(input("code"), input("id"))) {
            $data['error_code'] = 10006;
            $data['message'] = "验证码错误";
            return json_encode($data, true);
        } else {
            $data['error_code'] = 200;
            $data['message'] = "验证码正确";
            return json_encode($data, true);
        }
    }
    /**
     * 获取分类列表
     */
    function categoryList(){
        if(input("gc_parent_id")){
            $data = DB::name("goods_class")->where(array("gc_parent_id"=>input("gc_parent_id"),"gc_show"=>1))->select();
        }else{
            $data = DB::name("goods_class")->where(array("gc_show"=>1))->select();
        }
        return json_encode($data);
    }
    /**
     * 读取列表
     * @param array $condition 查询条件
     * @param int $page 分页数
     * @param string $order 排序
     * @param string $field 所需字段
     * @return array 团购列表
     *
     */
    public function getRedList($condition, $page = null, $order = 'bbc_red.id desc', $field = 'bbc_red.*,bbc_red_info.redinfo_money,red_info.redinfo_start,red_info.redinfo_end,red_info.redinfo_type,red_info.redinfo_ids,red_info.redinfo_self,red_info.redinfo_store,red_info.redinfo_full,red_info.redinfo_create,red_info.redinfo_together', $limit = 0) {
        $field.=',min(redinfo_money) min_money,
        max(redinfo_money) max_money,
        min(redinfo_start) as min_date,
        max(redinfo_end) as max_date';
        $condition['red_delete'] = 0;
        $red_list = DB::name('red_info')->join('bbc_red','bbc_red.id=bbc_red_info.red_id')->field($field)->where($condition)
            ->group('bbc_red_info.red_id')
            ->page($page)->order($order)->limit($limit)->select();
        return $red_list;
    }
    //选择地区城市
    public function get_city(){
        $id = input("id",99999999);
        $md = new Area();
        $type = input("type",2);
        $word=$type == 2? lang('省份'): ($type==3? lang("市"):lang("区"));
        $pro = $md->getAreaList(['area_parent_id'=>$id,'area_deep'=>$type], 'area_id,(case when merger_name is not null then name else area_name end) as area_name','',0);
        return json_encode($pro,true);
    }
    /**
     * 首页内容
     */
    function homePage(){
        $redis = new Redis();
        $lang = input("lang","zh_cn");
        if($lang == "en"){
            $store_id = 13;
        }
        else{
            $store_id = 0;
        }
        if($redis->has("homepage")){
            $data['data']=$redis->get("homepage");
        }else{
        //获取轮播图数据
            $result = DB::name("tpl_data")->where("sld_shop_id=$store_id and sld_tpl_type = 6")->field("sld_tpl_data")->find();
            $serializeData = $result['sld_tpl_data'];
            $imagelist = unserialize($serializeData)['pic_list'];
            $result = array();
            foreach($imagelist as $k=>$v){
                $result[$k]['pic'] = DB::name("fixture_album_pic")->field("sld_pic_name,sld_pic_width,sld_pic_height")->where("id=".$v['pic_id'])->find();
            }
            $data['album'] = $result;
            //获取通知
            $data['annoucelist'] = DB::name("article")->where("acid=1")->order("article_sort",'desc')->select();
            //热门课程
            $goods_list = unserialize((DB::name("tpl_data")->where("sld_tpl_type=2 and sld_is_vaild=1 and sld_tpl_code ='goods_floor2'")->field("sld_tpl_data")->find())['sld_tpl_data']);
            $goods_list = $goods_list['goods'];
            $model_goods = new \app\v1\model\Goods();
            $lession = array();
            foreach($goods_list as $k=>$v){
                $gid = $goods_list[$k]['goods_id'];
                unset($goods_list[$k]);
                $a =$model_goods->getGoodsList("gid = $gid", "*","","",1,0,1,1);
                if(!empty($a)) {
                    $goods_list[$k] = $a[0];
                }
                $goods_list[$k]['gid'] = $gid;
            }
            $ga = new GoodsActivity();
            $goods_list = $ga->rebuild_goods_data($goods_list,'web');
            foreach ($goods_list as $k=>$v){
               // if($goods_list[$v]) {
                    $lession[$k]['goods_name'] = $v['goods_name'];
                    $lession[$k]['gid'] = $v['gid'];
                    $lession[$k]['goods_price'] = $v['goods_price'];
                    $lession[$k]['goods_image'] = $v['goods_image'];
               //}
             }
            $data['hot_lession'] = $lession;
                //教师列表
            $field = '*';
            $teachers = DB::name('member')->alias('m')->field($field)->join('teacher_extend e','m.member_id=e.member_id')->limit(9)->select();

            //行业
            $trade_list = DB::name('teacher_trade')->field("trade_id,trade_name")->select();
            foreach ($trade_list as $val){
                $arr1[] = $val['trade_id'];
                $arr2[] = $val['trade_name'];
            }
            foreach ($teachers as &$val) {
                $arr= explode(',',$val['trades']);
                $res = str_replace($arr1,$arr2,$arr);
                $res = implode(',',$res);
                $val['trades'] = $res;
            }
            //$data['teachers'] = $teachers;
            //培训
            $trade_list = DB::name('peixun')->field("peixun_id,title,company_name")->limit(3)->select();
            $data['peixun'] = $trade_list;
            $redis->set("homepage",$data,120);
            $data=array();
            $data['data'] = $redis->get("homepage");
        }
        return json_encode($data,true);
    }
    function messageCount(){
        if(!input("member_id")){
            return json_encode($data['count']=0,true);
        }
        $memberId = input("member_id");
        //查询新接收到普通的消息
        $newcommon = $this->receivedCommonNewNum($memberId);
        //查询新接收到系统的消息
        $newsystem = $this->receivedSystemNewNum($memberId);
        //查询新接收到卖家的消息
        $newpersonal = $this->receivedPersonalNewNum($memberId);
        $count = $newcommon + $newsystem + $newpersonal;
        $data['count'] = $count;
        return json_encode($data);
    }
    /**
     * 统计收到站内信未读条数
     *
     * @return int
     */
    private function receivedCommonNewNum($memberId){
        $model_message	= new Message();
        $countnum = $model_message->countMessage(array('message_type'=>'2','to_member_id_common'=>$memberId,'no_message_state'=>'2','message_open_common'=>'0'));
        return $countnum;
    }
    /**
     * 统计系统站内信未读条数
     *
     * @return int
     */
    private function receivedSystemNewNum($memberId){
        $message_model =  new Message();
        $condition_arr = array();
        $condition_arr['message_type'] = '1';//系统消息
        $condition_arr['to_member_id'] = $memberId;
        $condition_arr['no_del_member_id'] = $memberId;
        $condition_arr['no_read_member_id'] = $memberId;
        $countnum = $message_model->countMessage($condition_arr);
        return $countnum;
    }
    /**
     * 统计私信未读条数
     *
     * @return int
     */
    private function receivedPersonalNewNum($memberId){
        $model_message = new Message();
        $countnum = $model_message->countMessage(array('message_type'=>'0','to_member_id_common'=>$memberId,'no_message_state'=>'2','message_open_common'=>'0'));
        return $countnum;
    }
    /**
     * 领券中心页
     *
     */
    public function redGetList()
    {
        if(input("member_id")){
            $memberId = input("member_id");
        }else{
            $memberId = null;
        }
        $page = input("page",1);
        $model_red = new Red();
        //$condition['bbc_red.red_type'] = array('neq','3');
        $condition['red_status'] = 1;
        $condition['red_front_show'] = 1;
        if(isset($_GET['red_id'])){
            $condition['red.id'] = $_GET['red_id'];
        }


        $red_list = $model_red->getRedLingList($memberId,$condition,$page);

        $red_list = $model_red->getUseInfo($red_list);
        return json_encode($red_list,true);

    }

    /**
     * 领取优惠券
     *
     */
    public function sendRed()
    {

        $red_id = input('red_id');

        if(!input('member_id') || !input("red_id")){
            $data['message'] = lang("缺少参数");
            return json_encode($data);
        }

        $red = new Red();
        $msg = $red->lingRed(input('member_id'),$red_id);

        if($msg==true){
            $data['message'] = lang("领取成功");
            $data['error_code'] = 200;
        }else{
            $data['message'] = lang("领取失败");
            $data['error_code'] =10202;
        }
        return json_encode($data,true);

    }
    /**
     * 我的优惠券列表
     * @param member_id
     */
    public function myRed(){
        if(!input('member_id')){
            $data['error_code'] = 10016;
            $data['message'] = lang("缺少参数");
        }
        $page = input("page",0);
        $model_red = new Red();
        $conditionstr = " 1=1";
        if (input('red_status')!=='') { //使用状态筛选
            if(input('red_status')=='used'){  //使用过
                $condition['reduser_use'] = array( 'neq',0);
                $conditionstr .=" and reduser_use <>0";
            }elseif(input('red_status')=='not_used'){  //未使用
                $condition['reduser_use'] = array( 'eq',0);
                $conditionstr .=" and reduser_use =0";
                $condition['redinfo_end'] = array( '<',TIMESTAMP);
                $conditionstr .=" and redinfo_end >=".TIMESTAMP;
            }elseif(input('red_status')=='expired'){  //过期
                $condition['redinfo_end'] = array( '>',TIMESTAMP);
                $conditionstr .=" and redinfo_end <".TIMESTAMP;
            }
        }else{
            $condition['reduser_use'] = array( 'eq',0);
            $conditionstr .= " and reduser_use =0";
            $condition['redinfo_end'] = array( 'gt',TIMESTAMP);
            $conditionstr .= " and redinfo_end >".TIMESTAMP;
        }
        $condition['reduser_uid'] = input("member_id");

        $red_list = $model_red->getRedUserList($conditionstr,$page,"","list");
        $red_list = $model_red->getUseInfo($red_list);
        $data['error_code'] = 200;
        $data['list']= $red_list;
        return json_encode($data,true);
    }
    /**
     * 公告详情
     */
    function articleDetail(){
        if(!input('article_id')){
            $data['error_code'] = 10016;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }
        $articleId = input("article_id");
        $article = db::name("article")->where(['id'=>$articleId])->find();
        $data['error_code'] = 200;
        $data['title'] = $article['article_title'];
        $data['html'] = str_replace("\n","",strip_tags($article['article_content']));
        $data['html'] = str_replace("\r","",$data['html']);
        $data['html'] = trim(str_replace("\t","",$data['html']));
        $data['html'] = str_replace("&nbsp;","",$data['html']);
        return json_encode($data,true);
    }
    /**
     * 热搜
     */
    public function hotSearch(){
        return json_encode(db::name("hot_search")->where("status =1 and starttime<=".TIMESTAMP." and endtime>=".TIMESTAMP)->order("sort,searchtimes","desc")->limit(6)->select());
    }
}
