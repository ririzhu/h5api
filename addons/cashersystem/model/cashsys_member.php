<?php
/**
 * 门店会员管理
 *
 */
defined('DYMall') or exit('Access Invalid!');
class cashsys_memberModel extends Model {

    public function __construct(){
        parent::__construct('member');
    }
    
    /**
     * 会员详细信息
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getMemberInfo($condition, $field = '*') {

        return $this->table('member,cashsys_member_common')->on('member.member_id = cashsys_member_common.member_id')->field($field)->where($condition)->find();
    }

    /**
     * 会员列表 总数
     * @param array $condition
     * @param string $field
     * @param number $page
     * @param string $order
     */
    public function getMemberCount($condition = array()) {
        $count_info = $this->table('member,cashsys_member_common')->on('member.member_id = cashsys_member_common.member_id')->where($condition)->field('count(*) as count')->find();
        return $count_info['count'] ? $count_info['count'] : 0;
    }

    /**
     * 会员列表
     * @param array $condition
     * @param string $field
     * @param number $page
     * @param string $order
     */
    public function getMemberList($condition = array(), $field = '*', $page = 0,$limit='', $order = 'member.member_id desc') {
        return $this->table('member,cashsys_member_common')->on('member.member_id = cashsys_member_common.member_id')->where($condition)->field($field)->order($order)->limit($limit)->select();
    }

    /**
     * 更新会员信息
     *
     * @param   array $data 更改信息
     * @param   array $member_id 会员ID
     * @return  array 数组格式的返回结果
     */
    public function updateMember($data,$condition) {
        if (isset($data['remark'])) {
            $common_data['remark'] = $data['remark'];
            unset($data['remark']);
        }
        $result = $this->table('member')->where(array('member_id'=>$condition['member_id']))->update($data);
        if (!empty($common_data) && $result) {
            $result = $this->table('cashsys_member_common')->where($condition)->update($common_data);   
        }
        return $result;
    }


    /**
     * 添加会员信息
     *
     * @param   array $data 存储信息
     * @return  array 数组格式的返回结果
     */
    public function saveMember($data) {
        return $this->table('cashsys_member_common')->insert($data);
    }

}