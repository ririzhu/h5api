<?php
namespace app\V1\controller;

use app\V1\model\Red;
use think\console\command\make\Model;
use think\Db;

class Teacher extends Base
{
    public function index(){

        //获取行业分类
        $class =  DB::name("goods_class")->where(array("gc_parent_id"=>input("gc_parent_id"),"gc_show"=>1))->select();
        $data['class'] = $class;
        //Template::output('class5',$class);
        //获取讲师行业
        $trade =  DB::name('teacher_trade')->select();
        $data['teacher_trade'] =  $trade;
//        $trade = Model('multilingual')->magicLang($trade,'teacher_trade');


        //Template::output('trade',$trade);
        //获取地区
        $area['a1']=DB::name("area")->where(array('area_parent_id'=>0,'area_deep'=>1))->select();
        if(isset($_GET['a1'])){
            $area['a2'] = DB::name("area")->where(['area_parent_id'=>intval($_GET['a1'])])->select();
        }
        if(isset($_GET['a2'])){
            $area['a3'] = DB::name("area")->where(['area_parent_id'=>intval($_GET['a2'])])->select();
        }
        $data['area'] = $area;
        //Template::output('area',$area);



        //获得品牌列表
        $brand_c_list = DB::name('brand')->where(array('brand_apply'=>'1'))->order('brand_sort asc')->select();
        $brands = $this->_tidyBrand($brand_c_list);
        extract($brands);
        //Template::output('brand_c',$brand_listnew);
        //Template::output('brand_class',$brand_class);
        //Template::output('brand_r',$brand_r_list);
        //Template::output('html_title',Language::get('品牌申请'));


        //处理排序
        $order = 'e.member_id desc';
        if(input("key")) {
            if (in_array($_GET['key'], array('1', '2', '3'))) {
                $sequence = $_GET['sort'] == '1' ? 'asc' : 'desc';
                $order = str_replace(array('1', '2', '3'), array('price', 'goods_click', 'goods_price'), $_GET['key']);
                $order .= ' ' . $sequence;
            }
        }

        //处理查询条件
        $where = [];
        if(!empty($_GET['class_id'])){
            $where['category'] = $_GET['class_id'];
        }
        if(!empty($_GET['trade_id'])){
            $where['trades'] = ['exp','FIND_IN_SET('.$_GET['trade_id'].',trades)'];
        }
        if(!empty($_GET['a1'])){
            $where['member_provinceid'] = $_GET['a1'];
        }
        if(!empty($_GET['a2'])){
            $where['member_cityid'] = $_GET['a2'];
        }
        if(!empty($_GET['a3'])){
            $where['member_areaid'] = $_GET['a3'];
        }

        //教师列表
        $field = '*';
        $teachers = DB::name('member')->alias('m')->field($field)->join('teacher_extend e','m.member_id=e.member_id')->where($where)->order($order)->select();

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
        $data['teachers'] = $teachers;
        //Template::output('teachers',$teachers);



        //省市地区
        $pro=DB::name("area")->where(array('area_parent_id'=>0,'area_deep'=>1))->select();
        $city=DB::name("area")->where(array('area_deep'=>2))->select();
        $data['pro'] = $pro;
        $data['city'] = $city;
        return json_encode($data);
//        $area=$model->table("area")->where(array('area_deep'=>3))->select();
        //Template::output("pro", $pro);
        //Template::output("city", $city);
//        Template::output("area", $area);



        //页面输出
        //Template::output('index_sign','brand');
        Model('seo')->type('brand')->show();
        //Template::showpage('teacher');
    }
    /**
     * 整理品牌
     * 所有品牌全部显示在一级类目下，不显示二三级类目
     * @param array $brand_c_list
     * @return array
     */
    private function _tidyBrand($brand_c_list) {
        $brand_listnew = array();
        $brand_class = array();
        $brand_r_list = array();
        $model = new Red();
        if (!empty($brand_c_list) && is_array($brand_c_list)){
            $goods_class = $model->H('bbc_goods_class') ? $model->H('bbc_goods_class') : $model->H('bbc_goods_class', true);
            foreach ($brand_c_list as $key=>$brand_c){
                $gc_array = $this->_getTopClass($goods_class, $brand_c['class_id']);
                if (empty($gc_array)) {
                    $brand_listnew[0][] = $brand_c;
                    $brand_class[0]['brand_class'] = '其他';
                } else {
                    $brand_listnew[$gc_array['gc_id']][] = $brand_c;
                    $brand_class[$gc_array['gc_id']]['brand_class'] = $gc_array['gc_name'];
                }
                //推荐品牌
                if ($brand_c['brand_recommend'] == 1){
                    $brand_r_list[] = $brand_c;
                }
            }
        }
        krsort($brand_class);
        krsort($brand_listnew);
        return array('brand_listnew' => $brand_listnew, 'brand_class' => $brand_class, 'brand_r_list' => $brand_r_list);
    }
    /**
     * 获取顶级商品分类
     * 递归调用
     * @param array $goods_class
     * @param int $gc_id
     * @return array
     */
    private function _getTopClass($goods_class, $gc_id) {
        if (!isset($goods_class[$gc_id])) {
            return null;
        }
        return $goods_class[$gc_id]['gc_parent_id'] == 0 ? $goods_class[$gc_id] : $this->_getTopClass($goods_class, $goods_class[$gc_id]['gc_parent_id']);
    }
}