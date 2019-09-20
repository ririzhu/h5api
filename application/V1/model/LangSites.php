<?php
/**
 * 多语言
 *
 * 
 *
 *
 */
namespace app\v1\model;

use think\Model;
use think\Db;

class LangSites extends Model {

    public function __construct() {
       parent::__construct('lang_sites');
    }

    public function getlist($condition=[],$field='*',$page='',$order='')
    {
        return DB::name('lang_sites')->where($condition)->field($field)->page($page)->order($order)->select();
    }
    /*
     * 获取单条记录
     * $condition 条件
     * $field 字段
     * return array
     */
    public function getone($condition=[],$field='*')
    {
        return DB::name('lang_sites')->where($condition)->field($field)->find();
    }
    /*
     * 插入
     * $insert 数据
     * return id
     */
    public function add($insert)
    {
        return DB::name('lang_sites')->insert($insert);
    }
    /*
     * 编辑
     * $condition 条件
     * $update 数据
     * return bool
     */
    public function edit($condition,$update)
    {
        return DB::name('lang_sites')->where($condition)->update($update);
    }
    /*
     * 删除
     * $condition 条件
     * return bool
     */
    public function del($condition)
    {
        return DB::name('lang_sites')->where($condition)->delete();
    }

}
