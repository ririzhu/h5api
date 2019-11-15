<?php
/**
 * 收银员/店长管理
 *
 */
defined('DYMall') or exit('Access Invalid!');
class cashsys_usersModel extends Model {

    public function __construct()
    {
        parent::__construct('cashsys_users');
    }

    /**
     * 获取 收银员/店长列表
     * @param array $condition
     * @param array $data
     */
    public function getCashsysUsersList($condition = array(), $field = '*', $page = 0, $order = 'id desc')
    {
    	return $this->where($condition)->field($field)->page($page)->order($order)->select();
    }


    /**
     * 新增 收银员/店长 数据
     * @param array $data
     */
    public function addCasherData($data) {
        return $this->insert($data);
    }

    /**
     * 编辑 收银员/店长 数据
     * @param array $condition
     * @param array $data
     */
    public function editCasherData($data, $condition) {
        $update = $this->where($condition)->update($data);
        return $update;
    }

    /**
     * 编辑 收银员/店长 数据
     * @param array $condition
     * @param array $data
     */
    public function deleteCasherData($condition) {
        $result = $this->where($condition)->delete();
        return $result;
    }

    /**
     * 获取 收银员/店长详情
     * @param array $condition
     * @param array $data
     */
    public function getCashsysUsersInfo($condition = array(), $field = '*')
    {
    	return $this->where($condition)->field($field)->find();
    }
    
    /**
     * 新增 收银员/店长 登录/退出 日志
     * @param array $data
     */
    public function addCasherLoginLog($data) {
        return $this->table('cashsys_users_login_log')->insert($data);
    }



}