<?php
/**
 * 推手系统 商品
 */

defined('DYMall') or exit('Access Invalid!');

class ssys_goodsModel extends Model
{
	
	public function __construct() 
	{
        parent::__construct("ssys_goods");
    }

    // 推手平台 是否参与分销推广及 分销商品 佣金
    public function get_vendor_spreader_goods_info($condition)
    {
        $result_array = array();

        if ($condition) {
            $goods_info = $this->get_spreader_goods_info($condition,"yj_amount");
            if (!empty($goods_info)) {
                $result_array['is_spreader_goods'] = 1;
                $result_array['spreader_goods_yj_amount'] = $goods_info['yj_amount'];
            }else{
                $result_array['is_spreader_goods'] = 0;
                $result_array['spreader_goods_yj_amount'] = '0.00';
            }
        }

        return $result_array;
    }

    // 推手品台 获取 分销推广的 商品信息
    public function get_spreader_goods_info($condition,$field="*")
    {
        $goods_info = $this->table('ssys_goods')->where($condition)->field($field)->find();

        // 返回分类名称
        if ($goods_info['cate_id'] > 0) {
            $ssys_goods_cates = M('ssys_goods_cates','spreader');
            $cate_condition['id'] = $goods_info['cate_id'];
            $cate_info = $ssys_goods_cates->getGoodsCatesInfo($cate_condition);
            $goods_info['cate_name'] = $cate_info['g_name'];
        }

        return $goods_info;
    }

    // 推手品台 获取 分销推广的 商品列表
    public function get_spreader_goods_list($condition,$field="*", $page = null,$order='add_time desc')
    {
        $goods_list = $this->table('ssys_goods')->where($condition)->field($field)->page($page)->order($order)->limit(false)->select();
        
        foreach ($goods_list as $key => $value) {
            // 返回分类名称
            if ($value['cate_id'] > 0) {
                $ssys_goods_cates = M('ssys_goods_cates','spreader');
                $cate_condition['id'] = $value['cate_id'];
                $cate_info = $ssys_goods_cates->getGoodsCatesInfo($cate_condition);
                $value['cate_name'] = $cate_info['g_name'];
            }
            if ($value['goods_commonid']) {
                
                // 检查 该商品 common_id 是否存在多个gid
                $check_condition['goods_commonid'] = $value['goods_commonid'];
                $check_has_more = $this->table('ssys_goods')->where($check_condition)->count();
                if ($check_has_more > 1) {
                    $value['has_more_yj'] = 1;
                    $yj_amount_more = $this->table('ssys_goods')->where($check_condition)->field('min(yj_amount) as min_yj_amount,max(yj_amount) as max_yj_amount')->find();
                    if (!empty($yj_amount_more)) {
                        $value['min_yj_amount'] = $yj_amount_more['min_yj_amount'];
                        $value['max_yj_amount'] = $yj_amount_more['max_yj_amount'];
                    }
                }else{
                    $value['has_more_yj'] = 0;
                }

            }
            $goods_list[$key] = $value;
        }

        return $goods_list;
    }

    // 推手平台 新增分销商品信息
    public function insert_ssys_goods($insert)
    {
        $insert['add_time'] = time();
        $insert['update_time'] = time();
        $result = $this->table('ssys_goods')->insert($insert);
        return $result;
    }

    // 推手平台 更新分销商品信息
    public function update_ssys_goods($update,$condition)
    {
        $update['update_time'] = time();
        $result = $this->table('ssys_goods')->where($condition)->update($update);
        return $result;
    }

    // 推手平台 删除分销商品信息
    public function delete_ssys_goods($condition)
    {
        $result = $this->table('ssys_goods')->where($condition)->delete();
        return $result;
    }
    
}