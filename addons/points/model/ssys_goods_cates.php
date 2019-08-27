<?php
/**
 * 推手系统 商品 分类
 */

defined('DYMall') or exit('Access Invalid!');

class ssys_goods_catesModel extends Model
{
	
	public function __construct() 
	{
        parent::__construct("ssys_goods_cates");
    }

    // 获取 分类列表
    public function getGoodsCatesList($condition,$page)
    {
		$result = $this->table('ssys_goods_cates')->where($condition)->order('order_num desc,create_time desc')->page($page)->select();
		return $result;

    }

    // 获取 分类信息
    public function getGoodsCatesInfo($condition)
    {
		$result	= $this->table('ssys_goods_cates')->where($condition)->find();
		return $result;
    }

    // 添加 分类信息
    public function saveGoodsCateInfo($data)
    {
		$cate_id = $this->table('ssys_goods_cates')->insert($data);
        
		return $cate_id;
    }

    // 更新 分类信息
    public function updateGoodsCateInfo($condition,$data)
    {
        if (is_array($data)){
        	unset($data['id']);
            $result = $this->table('ssys_goods_cates')->where($condition)->update($data);
			return $result;
		} else {
			return false;
		}
    }

    // 删除 模板信息
    public function deleteGoodsCates($condition)
    {
		$result = $this->table('ssys_goods_cates')->where($condition)->delete();
		return $result;
    }

}