<?php
/**
 * 积分商城手机首页设置
 *
 *
 *
 */
defined('DYMall') or exit('Access Invalid!');
class points_cwap_homeModel extends Model{


    public function __construct() {
        parent::__construct('points_cwap_home');
    }

    /**
     * 读取首页列表
     * @param array $condition
     *
     */
    public function getWapHomeList($condition, $page='', $order='home_id desc', $field='*') {
        $condition['shop_id'] = isset($condition['shop_id']) ? $condition['shop_id'] : 0;
        $list = $this->table('points_cwap_home')->field($field)->where($condition)->page($page)->order($order)->select();
        return $list;
    }
    /**
     * 读取商城内置的手机模板
     * @param array $condition
     *
     */
    public function getWapTemplateList($condition, $page='', $order='template_id asc', $field='*') {
        $list = $this->table('points_cwap_template')->field($field)->where($condition)->page($page)->order($order)->select();
        return $list;
    }
    /**
     * 获取首页详细信息  根据home_id
     * @param int $home_id
     *
     */
    public function getHomeInfoByHomeID($home_id) {
        $home_id = intval($home_id);
        if($home_id < 0) {
            return false;
        }
        $condition = array();
        $condition['home_id'] = $home_id;
        $home_info = $this->table('points_cwap_home')->where($condition)->find();
        return $home_info;
    }
    /**
     * 获取首页详细信息
     *
     */
    public function getCwapHomeInfo($condition=array()) {
        // $condition = array();
        $condition['home_state'] = 1;
        $condition['shop_id'] = isset($condition['shop_id']) ? $condition['shop_id'] : 0;
        $home_info = $this->table('points_cwap_home')->where($condition)->find();
        return $home_info;
    }
    /**
     * 获取商城内置模板的详细信息  根据id
     * @param int $home_id
     *
     */
    public function getTempInfoByID($id) {
        $id = intval($id);
        if($id < 0) {
            return false;
        }
        $condition = array();
        $condition['template_id'] = $id;
        $temp_info = $this->table('cwap_template')->where($condition)->find();
        return $temp_info;
    }
    /*
     * 新增首页
     * @param array $param
     * @param array $condition
     * @return array $item_info
     *
     */
    public function addHome($param,$condition=array()) {
        //先把别的首页状态置0
        // $condition = array();
        $condition['home_state'] = 1;
        $condition['shop_id'] = isset($condition['shop_id']) ? $condition['shop_id'] : 0;
        $condition['city_id'] = isset($condition['city_id']) ? $condition['city_id'] : 0;
        $result = $this->table('points_cwap_home')->where($condition)->update(array('home_state'=>0));
        $result = $this->table('points_cwap_home')->insert($param);
        if($result) {
            return $result;
        } else {
            return false;
        }
    }
    /*
     * 更新首页——2017.10.20
     * @param array $update
     * @param array $condition
     * @return bool
     *
     */
    public function editCwapHome($update, $home_id) {
        $home_id = intval($home_id);
        if($home_id <= 0) {
            return false;
        }
        $condition = array();
        $condition['home_id'] = $home_id;
        $result = $this->table('points_cwap_home')->where($condition)->update($update);
        if ($update['home_state']) {
            $page_info = $this->getHomeInfoByHomeID($home_id);
            // 更新 其他数据 为 0
            $where['city_id'] = $page_info['city_id'];
            $where['shop_id'] = $page_info['shop_id'];
            $where['home_id'] = array('neq',$page_info['home_id']);
            // 将其他 设置为失效
            // $where = ' home_id != '.$home_id.' ';
            $upData = array(
                'home_state' => 0,
            );
            $result = $this->table('points_cwap_home')->where($where)->update($upData);
        }
        if($result) {
            return $home_id;
        } else {
            return false;
        }
    }
    /*
     * 编辑首页
     * @param array $param
     * @return array $home_info
     *
     */
    public function editCwapHome_index($param) {
        $home_info = $this->getHomeInfoByHomeID($param['home_id']);
        if(empty($home_info)){
            return false;
        }else{
            $update = array();
            $update['home_desc'] = $param['home_desc'];
            $update['home_sousuo_color'] = $param['home_sousuo_color'];
            $update['home_botnav_color'] = $param['home_botnav_color'];
            if(isset($param['home_data'])){
                $update['home_data'] = $param['home_data'];
            }else{
                $update['home_data'] = '';
            }
            $condition['shop_id'] = isset($home_info['shop_id']) ? $home_info['shop_id'] : 0;
            $condition['city_id'] = isset($home_info['city_id']) ? $home_info['city_id'] : 0;
            $condition['home_id'] = $param['home_id'];
            $result = $this->table('points_cwap_home')->where($condition)->update($update);
        }
        if($result) {
            $param['id'] = $result;
            return $param;
        } else {
            return false;
        }
    }
    /*
     * 设为首页——2017.10.21
     * @param array $update
     * @param array $condition
     * @return bool
     *
     */
    public function setCwapHome($home_id) {
        $home_id = intval($home_id);
        if($home_id <= 0) {
            return false;
        }
        $home_tpl_info = $this->getHomeInfoByHomeID($home_id);
        $condition = array();
        $condition['home_state'] = 1;
        $condition['shop_id'] = isset($home_tpl_info['shop_id']) ? $home_tpl_info['shop_id'] : 0;
        $condition['city_id'] = isset($home_tpl_info['city_id']) ? $home_tpl_info['city_id'] : 0;
        $result = $this->table('cwap_home')->where($condition)->update(array('home_state'=>0));
        $condition_new = array();
        $condition_new['home_id'] = $home_id;
        $result = $this->table('cwap_home')->where($condition_new)->update(array('home_state'=>1));
        if($result) {
            return $home_id;
        } else {
            return false;
        }
    }
    /*
     * 删除首页
     * @param int $home_id
     * @return bool
     *
     */
    public function delCwapHomeByID($home_id) {
        $home_id = intval($home_id);
        if($home_id <= 0) {
            return false;
        }

        $condition = array();
        $condition['home_id'] = $home_id;

        return $this->table('cwap_home')->where($condition)->delete();
    }

    /*
     * 删除首页(多条删除)
     * @param array $home_ids
     * @return bool
     *
     */
    public function delCwapHomeByIDs($condition) {
        $condition['shop_id'] = isset($extends['shop_id']) ? $extends['shop_id'] : 0;
//        $condition .= 'and shop_id='.$conditions['shop_id'];
        $result = $this->table('points_cwap_home')->where($condition)->delete();
        return $result;
    }

    // 获取 模板信息
    public function getTemplateInfo($condition)
    {
        $condition['shop_id'] = isset($extends['shop_id']) ? $extends['shop_id'] : 0;
        $result = $this->table('cwap_home')->where($condition)->field('home_id,home_desc,home_state')->find();
        return $result;
    }



}
