<?php
defined('DYMall') or exit('Access Invalid!');
/**
 * Created by PhpStorm.
 * User: gxk
 * Date: 2018/10/9
 * Time: 16:02
 */
class ldj_dianModel extends Model
{
    public function __construct()
    {
        parent::__construct('dian');
    }

    /*
     * 获取门店列表
     */
    public function getDianList($condition,$field="*",$having='',$page='10',$order='')
    {
        return $this->table('dian')->where($condition)->field($field)->having($having)->page($page)->order($order)->select();
    }
    /*
     * 获取门店信息
     */
    public function getDianInfo($condition,$field='*')
    {
        return $this->table('dian')->where($condition)->field($field)->find();
    }
    /*
     * 通过门店id获取店铺分类列表
     */
    public function getVendorCate($vid)
    {
        $data = [];
        $goods_list = $this->table('dian_goods')->where(['dian_id'=>$vid,'stock'=>['gt',0],'off'=>0,'delete'=>0])->field('goods_id')->select();
        $goods_condition = implode(',',low_array_column($goods_list,'goods_id'));
        $stcids_list = $this->table('ldj_goods_extend')->where(['goods_id'=>['in',$goods_condition],'goods_stcids_1'=>['neq','']])->field('goods_id,goods_stcids_1')->select();
        foreach($stcids_list as $k=>$v){
            $stcid_name = $this->table('vendor_innercategory')->where(['stc_id'=>['in',trim($v['goods_stcids_1'],',')]])->select();
            foreach($stcid_name as $kk=>$vv){
                $data[$vv['stc_id']] = $vv;
            }
        }
        return $data;
    }
    public function getlocation()
    {
            $longitude = '';
            $latitude = '';
            //如果没位置信息 则根据ip定位
            $url = 'http://restapi.amap.com/v3/ip';
            $post_data['key'] = C('gaode_serverkey');
            $post_data['ip'] = getIp();
            $re = request_post($url, false, $post_data);
            if($re) {
                $re = json_decode($re,true);
                if ($re['rectangle']) {
                    $rect = explode(';', $re['rectangle']);
                    foreach ($rect as $k => $v) {
                        $rect[$k] = explode(',', $v);
                    }
                    $longitude = $rect[0][0] + ($rect[1][0] - $rect[0][0]);
                    $latitude = $rect[0][1] + ($rect[1][1] - $rect[0][1]);
                }
            }
            return [$longitude,$latitude];
        }
    /*
     * 修改门店信息
     */
    public function updateDian($condition,$update)
    {
        return $this->table('dian')->where($condition)->update($update);
    }

}