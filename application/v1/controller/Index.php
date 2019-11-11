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
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\LabelAlignment;
use Endroid\QrCode\QrCode;
use ImageOCR;
use think\swoole\command\Swoole;

class Index extends Base
{
    protected $_qr;
    protected $_encoding        = 'UTF-8';              // 编码类型
    protected $_logo_url        = './../public/logo.png';                   // logo图片路径
    protected $_size            = 300;                  // 二维码大小
    protected $_logo            = true;                // 是否需要带logo的二维码
    protected $_logo_size       = 160;                   // logo大小
    protected $_title           = true;                // 是否需要二维码title
    protected $_title_content   = '';                   // title内容
    protected $_generate        = 'display';            // display-直接显示  writefile-写入文件
    protected $_file_name       = '../../public/static/qrcode';    // 写入文件路径
    const MARGIN           = 10;                        // 二维码内容相对于整张图片的外边距
    const WRITE_NAME       = 'png';                     // 写入文件的后缀名
    const FOREGROUND_COLOR = ['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0];          // 前景色
    const BACKGROUND_COLOR = ['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0];    // 背景色
    public function index()
    {
        header('Content-type: text/html');
        return $this->fetch();
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
        ob_start();//不加这个是不行的(貌似不加可以)
        if (!input("id")) {
            $id = microtime(true);
        } else {
            $id = input("id");
        }
        $captcha = new Captcha();
        ob_end_clean();
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
        if(!input("page")) {
            if ($redis->has("homepage")) {
                $data['data'] = $redis->get("homepage");
            } else {
                //获取轮播图数据
                $result = DB::name("tpl_data")->where("sld_shop_id=$store_id and sld_tpl_type = 6")->field("sld_tpl_data")->find();
                $serializeData = $result['sld_tpl_data'];
                $imagelist = unserialize($serializeData)['pic_list'];
                $result = array();
                foreach ($imagelist as $k => $v) {
                    $result[$k]['pic'] = DB::name("fixture_album_pic")->field("sld_pic_name,sld_pic_width,sld_pic_height")->where("id=" . $v['pic_id'])->find();
                    $result[$k]['pic']['sld_pic_name']="http://192.168.2.252:9999/data/upload/fixture/".$result[$k]['pic']['sld_pic_name'];
                }
                $data['album'] = $result;
                //获取通知
                $data['annoucelist'] = DB::name("article")->where("acid=1")->order("article_sort", 'desc')->select();
                //热门课程
                $goods_list = unserialize((DB::name("tpl_data")->where("sld_tpl_type=2 and sld_is_vaild=1 and sld_tpl_code ='goods_floor2'")->field("sld_tpl_data")->find())['sld_tpl_data']);
                $goods_list = $goods_list['goods'];
                $model_goods = new \app\v1\model\Goods();
                $data['peixun_tag_name'] = "培训专场";
                $lession = array();
                $new_goods_list = array();
                foreach ($goods_list as $k => $v) {
                    $gid = $goods_list[$k]['goods_id'];
                    unset($goods_list[$k]);
                    $a = $model_goods->getGoodsList("gid = $gid", "*", "", "", 1, 0, 1, 1);
                    if (!empty($a)) {
                        $goods_list[$k] = $a[0];
                    }
                    $goods_list[$k]['gid'] = $gid;
                    $new_goods_list[$k]=$goods_list[$k];
                    if($k==2){
                        break;
                    }
                }
                $ga = new GoodsActivity();
                //$goods_list = $ga->rebuild_goods_data($goods_list,'web');
                foreach ($new_goods_list as $k => $v) {
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
                $teachers = DB::name('member')->alias('m')->field($field)->join('teacher_extend e', 'm.member_id=e.member_id')->limit(3)->select();

                //行业
                $trade_list = DB::name('teacher_trade')->field("trade_id,trade_name")->select();
                foreach ($trade_list as $val) {
                    $arr1[] = $val['trade_id'];
                    $arr2[] = $val['trade_name'];
                }
                foreach ($teachers as &$val) {
                    $arr = explode(',', $val['trades']);
                    $res = str_replace($arr1, $arr2, $arr);
                    $res = implode(',', $res);
                    $val['trades'] = $res;
                }
                //$data['teachers'] = $teachers;
                //培训
                //$trade_list = DB::name('peixun')->field("peixun_id,title,company_name")->limit(3)->select();
                if($redis->has("peixun")){
                    $goods_list = $redis->get("peixun");
                }else {
                    $peixunClassList = db::name("goods_class_tag")->where(array("type_id" => 1))->select();
                    $goods_list = array();
                    foreach ($peixunClassList as $k => $v) {
                        $list = db::name("goods")->where("gc_id=" . $v['gc_id'] . " and goods_state=1")->select();
                        foreach ($list as $kk => $vv) {
                            $goods_list[$kk]['goods_name'] = $vv['goods_name'];
                            $goods_list[$kk]['gid'] = $vv['gid'];
                            $goods_list[$kk]['del_price'] = $vv['goods_price'];
                            if (isset($goods_list[$kk]['show_price']))
                                $goods_list[$kk]['show_price'] = $vv['show_price'];
                            else
                                $goods_list[$kk]['show_price'] = $vv['goods_price'];
                            if (isset($goods_list[$kk]['goods_promotion_type']) && $goods_list[$k]['goods_promotion_type'] == 2) {
                                $goods_list[$kk]['tag'][0] = "限时优惠";
                            }
                            $goods_list[$kk]['goods_image'] = $vv['goods_image'];
                        }
                    }

                    //print_r($goods_list);die;
                    $ga = new GoodsActivity();
                    $goods_list = $ga->rebuild_goods_data($goods_list, 'pc');
                    $redis->set("peixun",$goods_list,30);
                }
                $data['count'] = count($goods_list);
                $a = 0;
                $new_goods_list = array();
                foreach($goods_list as $k=>$v){
                    $new_goods_list[$a]=$v;
                    $a++;
                    if($a==8){
                        break;
                    }
                }
                $trade_list = DB::name('peixun')->field("peixun_id,title,company_name")->limit(3)->select();
                $data['peixun'] = $new_goods_list;
                $redis->set("homepage", $data, 120);
                $data = array();
                $data['data'] = $redis->get("homepage");
            }

            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        }else{
            $page = input("page");
            if ($redis->has("homepage_$page")) {
                $data['data'] = $redis->get("homepage_$page");
            }else{
                if($redis->has("peixun")){
                    $goods_list = $redis->get("peixun");
                }else {
                    $peixunClassList = db::name("goods_class_tag")->where(array("type_id" => 1))->select();
                    $goods_list = array();
                    foreach ($peixunClassList as $k => $v) {
                        $list = db::name("goods")->where("gc_id=" . $v['gc_id'] . " and goods_state=1")->select();
                        foreach ($list as $kk => $vv) {
                            $goods_list[$kk]['goods_name'] = $vv['goods_name'];
                            $goods_list[$kk]['gid'] = $vv['gid'];
                            $goods_list[$kk]['del_price'] = $vv['goods_price'];
                            if (isset($goods_list[$kk]['show_price']))
                                $goods_list[$kk]['show_price'] = $vv['show_price'];
                            else
                                $goods_list[$kk]['show_price'] = $vv['goods_price'];
                            if (isset($goods_list[$kk]['goods_promotion_type']) && $goods_list[$k]['goods_promotion_type'] == 2) {
                                $goods_list[$kk]['tag'][0] = "限时优惠";
                            }
                            $goods_list[$kk]['goods_image'] = $vv['goods_image'];
                        }
                    }

                    //print_r($goods_list);die;
                    $ga = new GoodsActivity();
                    $goods_list = $ga->rebuild_goods_data($goods_list, 'pc');
                    $redis->set("peixun",$goods_list);
                }
                $a = $page * 8;
                $c=0;
                $new_goods_list = array();
                foreach($goods_list as $k=>$v){
                    if(isset($goods_list[$a])) {
                        $new_goods_list[$c] = $v;
                        $a++;
                        $c++;
                        if ($a == $page * 8 + 8) {
                            break;
                        }
                    }
                }
                $redis->set("homepage_$page", $new_goods_list, 120);
                $data = array();

                $data['data'] = $redis->get("homepage_$page");
            }
            return json_encode($data, JSON_UNESCAPED_UNICODE);
        }
    }
    function messageCount(){
        if(!input("member_id") || input("member_id")==""){
            return null;
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
        $vid = input("vid");
        if($vid==0){

        }
        $memberId = input('member_id');
        $page = input("page",0);
        $model_red = new Red();
        $conditionstr = " 1=1";
        if($vid==0){
            $conditionstr .=" and bbc_red.red_vid=0";
        }else{
            $conditionstr .=" and bbc_red.red_vid<>0";
        }
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
        $conditionstr .= " and reduser_uid =$memberId";
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
        $redis = new Redis();
        $articleId = input("article_id");
        if(!input('article_id')){
            $data['error_code'] = 10016;
            $data['message'] = lang("缺少参数");
            return json_encode($data,true);
        }
        $data['error_code'] = 200;
        if($redis->has("article_$articleId")){
            $datas=$redis->get("article_$articleId");
            if(empty($datas)){
                return null;
            }
            $data['title'] = $datas['title'];
            $data['html'] = $datas['html'];
        }else {
            $article = db::name("article")->where(['id' => $articleId])->find();
            if(empty($article)){
                return null;
            }
            $data['title'] = $article['article_title'];
            $data['html'] = str_replace("\n", "", strip_tags($article['article_content']));
            $data['html'] = str_replace("\r", "", $data['html']);
            $data['html'] = trim(str_replace("\t", "", $data['html']));
            $data['html'] = str_replace("&nbsp;", "", $data['html']);
            $redis->set("article_$articleId",$data);
        }
        return json_encode($data,true);
    }
    /**
     * 热搜
     */
    public function hotSearch(){
        return json_encode(db::name("hot_search")->where("status =1 and starttime<=".TIMESTAMP." and endtime>=".TIMESTAMP)->order("sort,searchtimes","desc")->limit(6)->select());
    }
    public function erweima(){
        // 自定义二维码配置
        $config = [
            'title'         => true,
            'title_content' => 'test',
            'logo'          => true,
            'logo_url'      => './logo.png',
            'logo_size'     => 80,
        ];

        // 直接输出
        //$qr_url = 'http://www.baidu.com?id=' . rand(1000, 9999);
        $qr_url ="http://www.horizou.com";
        //$qr_code = new QrcodeServer($config);
        //$qr_img = $qr_code->createServer($qr_url);
        $this->_qr = new QrCode($qr_url);
        $this->_qr->setSize($this->_size);
        $this->_qr->setWriterByName(self::WRITE_NAME);
        $this->_qr->setMargin(self::MARGIN);
        $this->_qr->setEncoding($this->_encoding);
        $this->_qr->setErrorCorrectionLevel(ErrorCorrectionLevel::HIGH);   // 容错率
        $this->_qr->setForegroundColor(self::FOREGROUND_COLOR);
        $this->_qr->setBackgroundColor(self::BACKGROUND_COLOR);
        // 是否需要title
        if ($this->_title) {
            $this->_qr->setLabel($this->_title_content, 16, null, LabelAlignment::CENTER);
        }
        // 是否需要logo
        if ($this->_logo) {
            $this->_qr->setLogoPath($this->_logo_url);
            $this->_qr->setLogoWidth($this->_logo_size);
        }

        $this->_qr->setValidateResult(false);

        if ($this->_generate == 'display') {
            // 展示二维码
            // 前端调用 例：<img src="http://localhost/qr.php?url=base64_url_string">
            header('Content-Type: ' . $this->_qr->getContentType());
            return $this->_qr->writeString();
        } else if ($this->_generate == 'writefile') {
            // 写入文件
            $file_name = $this->_file_name;
            return $this->generateImg($file_name);
        } else {
            return ['success' => false, 'message' => 'the generate type not found', 'data' => ''];
        }

        // 写入文件
        $qr_url = '这是个测试二维码';
        $file_name = './static/qrcode';  // 定义保存目录

        $config['file_name'] = $file_name;
        $config['generate']  = 'writefile';

        //$qr_code = new QrcodeServer($config);
        //$rs = $qr_code->createServer($qr_url);
        print_r($rs);

        exit;
    }
    /**
     * 反馈
     */
    public function feedback(){
        if(!input("member_id") || !input("reason") || !input("contactname") || !input("mobile") || !input("content") ){
            $datas['error_code'] = 10016;
            $datas['message'] = lang("缺少参数");
            return json_encode($datas,true);exit();
        }
        $data['member_id'] = input("member_id");
        $data['reason'] = input("reason");
        $data['isanonymous'] = input("isanonymous",0);
        $data['contactname'] = input("contactname");
        $data['mobile'] = input("mobile");
        $data['content'] = input("content");
        $file = request()->file("images");

        $info = $file->move('uploads/feedback');
        if ($info) {
            $data['image'] = "http://192.168.2.252:7777/".$info->getPathname();
        } else {
            //上传失败获取错误信息
            $this->error($file->getError());
        }
        $res = db::name("feedback")->insert($data);
        if($res){
            $datas['error_code'] = 200;
            $datas['message'] = lang("操作成功");
            return json_encode($datas,true);
        }else{
            $datas['error_code'] = 10202;
            $datas['message'] = lang("操作失败");
            return json_encode($datas,true);
        }
    }

    /**
     * 对象 转 数组
     *
     * @param object $obj 对象
     * @return array
     */
    function object_to_array($array)
    {

        if (is_object($array)) {
            $array = (array)$array;
        }
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                $array[$key] = $this->object_to_array($value);
            }
        }
        return $array;
    }

    /**
     * 获取协议
     */
    public function getAgreements()
    {
        //$data['error_code'] = 200;
        $redis = new Redis();
        if(!$redis->has("agreements")) {
            $data['titles'] = db::name("article")->where("id in(86,87,88)")->field("article_title")->select();
            $data['agreement1'] = (db::name("article")->where("id =86")->field("article_content")->find())['article_content'];
            $data['agreement2'] = (db::name("article")->where("id =87")->field("article_content")->find())['article_content'];
            $data['agreement3'] = (db::name("article")->where("id =88")->field("article_content")->find())['article_content'];
            $redis->set("agreements",$data,3600);
            $data['error_code'] = 200;
        }else{
            $datas = $redis->get("agreements");
            $data['titles'] = $datas['titles'];
            $data['agreement1'] = $datas['agreement1'];
            $data['agreement2'] = $datas['agreement2'];
            $data['agreement3'] = $datas['agreement3'];
            $data['error_code'] = 200;
        }
        return json_encode($data);
    }
    /**
     * 换一换
     */
    public function huanyihuan(){
        $page = input("page",0);
        $goods_list = unserialize((DB::name("tpl_data")->where("sld_tpl_type=2 and sld_is_vaild=1 and sld_tpl_code ='goods_floor2'")->limit(1)->page($page)->field("sld_tpl_data")->find())['sld_tpl_data']);
        $goods_list = $goods_list['goods'];
        $model_goods = new \app\v1\model\Goods();
        $lession = array();
        $kk = $page*3;
        $new_goods_list = array();
        if(count($goods_list)>=$kk) {
            foreach ($goods_list as $k => $v) {
                if (($kk) % 3 == 0 && $kk != $page * 3) {
                    break;
                }
                if(isset($goods_list[$kk])) {
                    $gid = $goods_list[$kk]['goods_id'];
                    //unset($goods_list[$k]);
                    $a = $model_goods->getGoodsList("gid = $gid", "*", "", "", 1, 1, 1, 1);
                    if (!empty($a)) {
                        $goods_list[$k] = $a[0];
                    }
                    $goods_list[$k]['gid'] = $gid;
                    $new_goods_list[$k] = $goods_list[$k];
                    $kk++;
                }
            }
        }else{
            $new_goods_list = array();
        }
        $ga = new GoodsActivity();
        //$goods_list = $ga->rebuild_goods_data($goods_list,'web');
        $lession = array();
        if(count($new_goods_list)>0)
        foreach ($new_goods_list as $k => $v) {
            // if($goods_list[$v]) {
            //$lession[$k]['goods_name'] = $v['goods_name'];
            $lession[$k]['gid'] = $v['gid'];
            //$lession[$k]['goods_price'] = $v['goods_price'];
            $lession[$k]['goods_image'] = $v['goods_image'];
            //}
        }
        if(count($lession)==0){
            $data['last'] = false;
        }else{
            $data['last'] = true;
        }
        $data['hot_lession'] = $lession;
        return json_encode($data,true);
    }
    /**
     *
     */
    public function calC(){
        $m = input("m");
        $n = input("n");
        $mresult = 1;
        $nresult = 1;
        $mnresult = 1;
        for($i = 1;$i <= $m;$i++){
            $mresult *=$i;
        }
        for($i = 1;$i <= $n;$i++){
            $nresult *=$i;
        }
        for($i = 1;$i <=$m-$n;$i++){
            $mnresult *=$i;
        }
        echo ($mresult)/($nresult*$mnresult);
    }
    /**
     * 计算相关系数
     */
    public function calR(){
        $x = input("x");
        $y = input("y");
        $x_list = explode(",",$x);
        $y_list = explode(",",$y);
        $xsum = array_sum($x_list);//x的和
        $ysum = array_sum($y_list);//y的和
        $x2sum = 0;//x平方的和
        $y2sum = 0;//y平方的和
        $count = count($x_list);//数量
        $xy = 0;//xy的乘积之和；
        for($i = 0;$i<count($x_list);$i++){
            $xy += $x_list[$i]*$y_list[$i];
            $x2sum +=($x_list[$i])*($x_list[$i]);
            $y2sum +=($y_list[$i])*($y_list[$i]);
        }
        //公式 r = (nE(xi*yi)-ExEy)/(开根号（nE（x平方的和）-（Ex的平方））*开根号（nE（y平方的和）-（Ey的平方））);
        //计算x的平均值
        $xavg = array_sum($x_list)/count($x_list);
        $r = ($count*$xy - $xsum*$ysum)/(sqrt($count*$x2sum-pow($xsum,2)) * sqrt($count*$y2sum-pow($ysum,2)));
        echo $r;
    }
    /**
     * 测试swoole
     */
    public function testswoole($url,$methods,$param){
        $client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
        $ret = $client->connect("127.0.0.1", 9501);
        $client->send("error", function(swoole_client $cli,$methods,$param,$url){
                $curl = curl_init();
                $datas = array();
                $datas['methods'] = $methods;
                $datas['data'] = $param;
                $SSL = substr($url, 0, 8) == "https://" ? true : false;
                //$data['timestamp']=time();
                curl_setopt($curl, CURLOPT_URL, $url);//登陆后要从哪个页面获取信息
                curl_setopt($curl, CURLOPT_HEADER, 0);//获取头部
                curl_setopt ($curl, CURLOPT_POST, 1 );
                curl_setopt($curl, CURLOPT_USERAGENT, 'MQQBrowser/Mini3.1 (Nokia3050/07.42) Via: MQQBrowser');
                // curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); 这里要不要都没用
                //if(strtoupper($method)=="POST"){
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt ($curl, CURLOPT_POSTFIELDS, http_build_query($datas));

                //}
                //if ($SSL) {

                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书

                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0); // 检查证书中是否设置域名

                //}
                $html = curl_exec($curl);//获取html页面
                curl_close($curl);
                $re1=$html;
//        if (substr($re1, 0,3) == pack("CCC",0xef,0xbb,0xbf)) {
//            $re1 = substr($re1, 3);
//        }
                echo $html;
            });

            $client->send('blue');//这里只是简单的实现了发送的内容


        echo 'success';

    }
    public  function testweixin(){
        $url = "https://syb.allinpay.com/apiweb/h5unionpay/unionorder";
        $param['cusid'] = TLCUID;
        $param['appid'] = TLAPPID;
        $param['version'] = 12;
        $param['trxamt'] = 1;
        $param['reqsn'] = 433;
        $param['charset'] = "UTF-8";
        $param['returl'] = "http://www.baidu.com";
        $param['notify_url'] = "http://www.baidu.com";
        $param['body'] = "订单";
        $param['remark'] = "支付";
        $param['randomstr'] = "HORIZOU";
        $param['validtime'] = 10;
        //$param['limit_pay'] = TLAPPID;
        //$param['asinfo'] = TLCUID;
        $param['sign'] = self::SignArray($param,15202156609);
        //$param['key'] = TLPUBLICKEY;
        ksort($param);
        $base = new \app\v1\controller\Base();
        return response($base->curl("POST",$url,$param));
    }
    /**
     * 将参数数组签名
     */
    public static function SignArray(array $array,$appkey){
        $array['key'] = $appkey;// 将key放到数组中一起进行排序和组装
        ksort($array);
        $blankStr = self::ToUrlParams($array);
        $sign = md5($blankStr);
        return $sign;
    }
    public static function ToUrlParams(array $array)
    {
        $buff = "";
        foreach ($array as $k => $v)
        {
            if($v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }
    /**
     * 校验签名
     * @param array 参数
     * @param unknown_type appkey
     */
    public static function ValidSign(array $array,$appkey){
        $sign = $array['sign'];
        unset($array['sign']);
        $array['key'] = $appkey;
        $mySign = self::SignArray($array, $appkey);
        return strtolower($sign) == strtolower($mySign);
    }

}
