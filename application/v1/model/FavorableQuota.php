<?php
namespace app\v1\model;

use think\Model;

class FavorableQuota extends Model
{
    /**
     * 读取满即送套餐列表
     * @param array $condition 查询条件
     * @param int $page 分页数
     * @param string $order 排序
     * @param string $field 所需字段
     * @return array 满即送套餐列表
     *
     */
    public function getMansongQuotaList($condition, $page=null, $order='', $field='*') {
        $result = $this->field($field)->where($condition)->page($page)->order($order)->select();
        return $result;
    }

    /**
     * 读取单条记录
     * @param array $condition
     *
     */
    public function getMansongQuotaInfo($condition) {
        $result = $this->where($condition)->find();
        return $result;
    }

    /**
     * 获取当前可用套餐
     * @param int $vid
     * @return array
     *
     */
    public function getMansongQuotaCurrent($vid) {
        $condition = array();
        $condition['vid'] = $vid;
        $condition['end_time'] = array('gt', TIMESTAMP);
        $mansong_quota_list = $this->getMansongQuotaList($condition, null, 'end_time desc');
        $mansong_quota_info = $mansong_quota_list[0];
        return $mansong_quota_info;
    }

    /*
     * 增加
     * @param array $param
     * @return bool
     *
     */
    public function addMansongQuota($param){
        return $this->insert($param);
    }

    /*
	 * 更新
	 * @param array $update
	 * @param array $condition
	 * @return bool
     *
	 */
    public function editMansongQuota($update, $condition){
        return $this->where($condition)->update($update);
    }

    /*
     * 删除
     * @param array $condition
     * @return bool
     *
     */
    public function delMansongQuota($condition){
        return $this->where($condition)->delete();
    }
}