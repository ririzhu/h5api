<?php
namespace app\v1\model;

use think\Model;
use think\db;

class Grade extends Model
{
    public function __construct() {
        parent::__construct('grade');
    }
    /*
     * 获取列表
     * $condition 条件
     * $field 字段
     * $page 分页
     * $order 排序
     * return array
     */
    public function getlist($condition=[],$field='*',$page='',$order='')
    {
        $list = DB::name('grade')->where($condition)->field($field)->page($page)->order($order)->select();
        if($list){
            array_walk($list,function(&$v){
                $v['grade_img_name'] = $v['grade_img'];
                if(!empty($v['grade_img'])){
                    $v['grade_img'] = $this->getimg($v['grade_img']);
                }
            });
        }
        return $list;
    }
    /*
     * 获取会员的当前等级信息
     */
    public function getmembergrade($member_id,$lang_type = false)
    {
        $member_info = DB::name('member')->where(['member_id'=>$member_id])->field('member_growthvalue')->find();
        if($member_info){
            $list = $this->getlist([],'*','','grade_value asc');

            foreach($list as $k=>$v){
                $list[$k]['is_besthigh'] = 0;
                if($k == 0){
                    if($member_info['member_growthvalue'] <= $v['grade_value']){
                        return $list[$k];
                    }elseif($member_info['member_growthvalue'] > $v['grade_value'] && $member_info['member_growthvalue'] <= $list[$k+1]['grade_value']){
                        return $list[$k+1];
                    }elseif($member_info['member_growthvalue'] > $v['grade_value'] && count($list) == 1){
                        return $list[$k];
                    }
                }elseif($v['id'] == end($list)['id']){
                    $list[$k]['is_besthigh'] = 1;
                    return $list[$k];
                }else{
                    if($member_info['member_growthvalue'] > $list[$k]['grade_value'] && $member_info['member_growthvalue'] <= $list[$k+1]['grade_value']){
                        return $list[$k];
                    }
                }
            }
        }

        return false;
    }
    /*
     * 获取会员
     */
    public function getgradeprice($goods_data)
    {
        //检测当前是移动还是pc
        $url_path = explode('/',parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH));
        $url_path = array_filter($url_path);
        $url_path = array_values($url_path);

        $system_path = parse_url(C('base_site_url'),PHP_URL_PATH);
        $system_path = explode('/',$system_path);
        $system_path = array_filter($system_path);
        $system_path = array_values($system_path);//mall

        //会员id
        $member_id = 0;
        if($url_path[0] == 'index.php' || $url_path[0] == $system_path[0]){
            //pc || 没有阶梯团购和预售 所以暂时加上
            if(
                !(isset($goods_data['promotion_type']) && !empty($goods_data['promotion_type']))
                || in_array($goods_data['promotion_type'],['pin_ladder_tuan','sld_presale'])
            ){
                $member_id = $_SESSION['member_id'];
            }
        }else{
            //cwap
            if(!(isset($goods_data['promotion_type']) && !empty($goods_data['promotion_type']))){
                if(isset($_POST['key']) && !empty($_POST['key'])){
                    $key = $_POST['key'];
                }else{
                    $key = $_GET['key'];
                }
                //获取会员id
                $model_mb_user_token = Model('mb_user_token');
                $mb_user_token_info = $model_mb_user_token->getMbUserTokenInfoByToken($key);
                $model_member = Model('member');
                $member_info = $model_member->getMemberInfoByID($mb_user_token_info['member_id']);
                $member_id = $member_info['member_id'];
            }
        }
        if($member_id){
            //检测店铺是够开启会员折扣
            $is_suport = model()->table('goods,vendor')->join('left')->on('goods.vid=vendor.vid')->where(['goods.gid'=>$goods_data['gid']])->field('vendor.grade_on_price')->find();
            $grade_info = $this->getmembergrade($member_id);
            if(intval($grade_info['grade_discount']) > 0 && $is_suport['grade_on_price'] > 0){
                if(isset($goods_data['show_price']) && !empty($goods_data['show_price'])){
                    $goods_data['show_price'] = $goods_data['show_price']*intval($grade_info['grade_discount'])/100;
                    $goods_data['grade_discount'] = intval($grade_info['grade_discount'])/10;
                }else{
                    $goods_data['goods_price'] = $goods_data['goods_price']*intval($grade_info['grade_discount'])/100;
                    $goods_data['grade_discount'] = intval($grade_info['grade_discount'])/10;
                }
            }
        }
        return $goods_data;
    }
    /*
     * 获取等级图片的路径
     */
    public function getimg($img)
    {
        if($img){
            return C('upload_site_url').DS.ATTACH_COMMON.DS.$img;
        }else{
            return '';
        }
    }
    /*
     * 获取单条记录
     * $condition 条件
     * $field 字段
     * return array
     */
    public function getone($condition=[],$field='*')
    {
        return $this->table('grade')->where($condition)->field($field)->find();
    }
    /*
     * 插入
     * $insert 数据
     * return id
     */
    public function add($insert)
    {
        return $this->table('grade')->insert($insert);
    }
    /*
     * 编辑
     * $condition 条件
     * $update 数据
     * return bool
     */
    public function edit($condition,$update)
    {
        return $this->table('grade')->where($condition)->update($update);
    }
    /*
     * 删除
     * $condition 条件
     * return bool
     */
    public function drop($condition)
    {
        return $this->table('grade')->where($condition)->delete();
    }

    /*
     * 获取语言等级列表
     */
    public function getGradeByLang($lang_type,$field='*',$page='',$order='')
    {
        return $this->table('grade')
            ->field($field)
            ->page($page)
            ->order($order)
            ->select();
    }
}