<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/10/19
 * Time: 11:07
 */
class ldj_settingModel extends Model
{
    public function __construct()
    {
        parent::__construct('ldj_order');
    }
    /**
     * 读取系统设置列表
     *
     * @param
     * @return array 数组格式的返回结果
     */
    public function getListSetting($key=null){
        $param = array();
        $param['table'] = 'ldj_setting';
        if($key) {
            $param['where']	= "name='".$key."'";
        }
        $result = Db::select($param);
        /**
         * 整理
         */
        if (is_array($result)){
            $list_setting = array();
            foreach ($result as $k => $v){
                $list_setting[$v['name']] = $v['value'];
            }
        }
        if($key){
            return $list_setting[$key];
        }
        return $list_setting;
    }
    /**
     * 更新信息
     *
     * @param array $param 更新数据
     * @return bool 布尔类型的返回结果
     */
    public function updateSetting($param){
        if (empty($param)){
            return false;
        }

        if (is_array($param)){
            foreach ($param as $k => $v){
                $tmp = array();
                $specialkeys_arr = array('statistics_code');
                $tmp['value'] = (in_array($k,$specialkeys_arr) ? htmlentities($v,ENT_QUOTES) : $v);
                $where = " name = '". $k ."'";
                $result = Db::update('ldj_setting',$tmp,$where);
                if ($result !== true){
                    return $result;
                }
            }
            H('ldj_setting',true);
            delete_file(BASE_DATA_PATH.DS.'cache'.DS.'ldj_setting.php');
            return true;
        }else {
            return false;
        }
    }
}