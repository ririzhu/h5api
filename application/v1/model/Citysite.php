<?php
namespace app\v1\model;

use think\Model;
use think\Db;
class Citysite extends Model
{
    /**
     * 城市分站列表
     * @param unknown $condition
     * @param string $pagesize
     * @param string $field
     * @param string $order
     * @param string $limit
     *
     */
    public function getCitySiteList($condition, $pagesize = '', $field = '*', $order = 'id desc', $limit = ''){
        $list = DB::name('city_site')->field($field)->where($condition)->page($pagesize)->order($order)->limit($limit)->select();
        return $list;
    }
    /**
     * 根据获取的绑定城市id查找信息
     * @param string $condition
     *
     */
    public function getCitySite($condition){
        $list = DB::name('city_site')->field('*')->where($condition)->select();
        return $list;
    }

    /**
     * 构造检索条件
     *
     * @param array $condition 检索条件
     * @return string 字符串类型的返回结果
     */
    public function _condition($condition){
        $condition_str = '';

        if ($condition['admin_id'] != ''){
            $condition_str .= " and admin_id = '". $condition['admin_id'] ."'";
        }
        if ($condition['admin_name'] != ''){
            $condition_str .= " and admin_name = '". $condition['admin_name'] ."'";
        }
        if ($condition['admin_sld_truename'] != ''){
            $condition_str .= " and admin_sld_truename = '". $condition['admin_sld_truename'] ."'";
        }
        if ($condition['admin_sld_iscitysite'] != ''){
            $condition_str .= " and admin_sld_iscitysite = '". $condition['admin_sld_iscitysite'] ."'";
        }
        if ($condition['admin_password'] != ''){
            $condition_str .= " and admin_password = '". $condition['admin_password'] ."'";
        }

        return $condition_str;
    }

    /**
     * 取单个管理员的内容
     *
     * @param int $admin_id 管理员ID
     * @return array 数组类型的返回结果
     */
    public function getOneAdmin($admin_id){
        if (intval($admin_id) > 0){
            $param = array();
            $param['table'] = 'admin';
            $param['field'] = 'admin_id';
            $param['value'] = intval($admin_id);
            $result = Db::name('admin')->field("admin_id")->where(array("admin_id"=>$admin_id))->find();
            return $result;
        }else {
            return false;
        }
    }
    /**
     * 获取管理员信息
     *
     * @param	array $param 管理员条件
     * @param	string $field 显示字段
     * @return	array 数组格式的返回结果
     */
    public function infoAdmin($param, $field = '*') {
        if(empty($param)) {
            return false;
        }
        //得到条件语句
        $condition_str	= $this->_condition($param);
        $param	= array();
        $param['table']	= 'admin';
        $param['where']	= $condition_str;
        $param['field']	= $field;
        $admin_info	= Db::select($param);
        return $admin_info[0];
    }

    /**
     * 新增城市分站
     *
     * @param array $param 参数内容
     * @return bool 布尔类型的返回结果
     */
    public function addSldCitySite($param){
        if (empty($param)){
            return false;
        }
        return $this->name('city_site')->insert($param);
    }

    /**
     * 更新信息
     *
     * @param array $param 更新数据
     * @return bool 布尔类型的返回结果
     */
    public function editSldCitySite($param,$condition){
        return $this->name('city_site')->where($condition)->update($param);
    }

    /**
     * 删除
     *
     * @param int $id 记录ID
     * @return array $rs_row 返回数组形式的查询结果
     */
    public function delAdmin($id){
        if (intval($id) > 0){
            $where = " admin_id = '". intval($id) ."'";
            $result = Db::delete('admin',$where);
            return $result;
        }else {
            return false;
        }
    }

    /**
     * 取数量
     * @param unknown $condition
     */
    public function getCitySiteCount($condition = array()) {
        return $this->name('city_site')->where($condition)->count();
    }
}