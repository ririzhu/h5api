<?php

namespace app\V1\controller;

use app\V1\controller\Base;
use think\db;
use think\captcha\Captcha;

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
}
